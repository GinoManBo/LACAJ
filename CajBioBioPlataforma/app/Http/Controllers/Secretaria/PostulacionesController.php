<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Consultorio;
use App\Models\Postulacion;
use App\Models\Practica;
use App\Models\Region;
use App\Services\Auditoria;
use App\Services\ImportadorPostulaciones;
use App\Services\Postulaciones;
use App\Services\Practicas;
use App\Support\Scope;
use Illuminate\Http\Request;

class PostulacionesController extends Controller
{
    private const UNIVERSITARIOS = ['alumno_regular', 'concentracion_notas', 'certificado_egreso', 'curriculum', 'otro'];

    public function index(Request $request)
    {
        // Jurídica Central cuenta con su pantalla separada de supervisión nacional
        if ($request->user()->rol === 'juridica_central') {
            return redirect()->route('secretaria.juridica.postulaciones');
        }

        $region = Scope::regionId($request->user());
        $q = trim((string) $request->query('q'));

        $postulaciones = Postulacion::with('postulante', 'preferencias.consultorio')
            ->where('region_id', $region)->where('estado', 'pendiente')
            ->when($q !== '', fn ($query) => $query->whereHas('postulante', fn ($p) => $p
                ->where('nombre', 'like', "%$q%")->orWhere('apellido', 'like', "%$q%")
                ->orWhere('rut', 'like', "%$q%")->orWhere('universidad', 'like', "%$q%")))
            ->orderBy('fecha_postulacion')->orderBy('id')->get();

        $ruts = $postulaciones->pluck('postulante.rut')->filter()->unique()->values();
        $cancelacionesPorRut = $ruts->isNotEmpty()
            ? Practica::where('estado', 'cancelada')
                ->where(function ($query) use ($ruts) {
                    $query->whereHas('practicante.postulante', fn ($q) => $q->whereIn('rut', $ruts))
                        ->orWhereHas('postulacion.postulante', fn ($q) => $q->whereIn('rut', $ruts));
                })
                ->with(['consultorio', 'canceladaPor'])
                ->orderByDesc('cancelacion_fecha')
                ->orderByDesc('id')
                ->get()
                ->groupBy(function ($pr) {
                    return $pr->practicante?->postulante?->rut ?? $pr->postulacion?->postulante?->rut;
                })
            : collect();

        return view('secretaria.postulaciones.index', [
            'postulaciones' => $postulaciones,
            'cancelacionesPorRut' => $cancelacionesPorRut,
            'q' => $q,
        ]);
    }

    public function importar(Request $request)
    {
        $request->validate(
            ['archivo' => ['required', 'file', 'extensions:xlsx', 'max:2048']],
            ['archivo.required' => 'Seleccione el archivo Excel.', 'archivo.extensions' => 'El archivo debe ser un Excel (.xlsx).', 'archivo.max' => 'El archivo no puede pesar más de 2 MB.', 'archivo.file' => 'Seleccione el archivo Excel.'],
        );

        try {
            $r = ImportadorPostulaciones::importar($request->file('archivo')->getRealPath(), $request->user(), Scope::regionId($request->user()));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['archivo' => $e->getMessage()], 'importar');
        }

        $msg = $r['creadas'].' '.($r['creadas'] === 1 ? 'postulación importada' : 'postulaciones importadas').'.';

        return redirect()->route('secretaria.postulaciones')
            ->with($r['creadas'] > 0 ? 'ok' : 'importacion_vacia', $msg)
            ->with('importacion_omitidas', $r['omitidas']);
    }

    public function ver(Request $request, int $id)
    {
        $p = $this->postulacion($request, $id)->load(
            'postulante.comuna', 'postulante.region', 'postulante.especialidad',
            'preferencias.consultorio.comuna', 'documentos', 'notas.autor', 'derivaciones.regionOrigen', 'derivaciones.regionDestino',
        );
        $esJuridica = $request->user()->rol === 'juridica_central';
        $region = $esJuridica ? Region::where('codigo', 'BI')->value('id') : Scope::regionId($request->user());

        $postuloIds = $p->preferencias->pluck('consultorio_id')->all();
        $consultorios = Consultorio::with('region', 'comuna', 'abogados.usuario')->where('activo', true)->orderBy('nombre')->get()
            ->each(function ($c) use ($postuloIds, $region) {
                $c->postulo_aqui = in_array($c->id, $postuloIds, true);
                $c->otra_region = $c->region_id !== $region;
                $c->libres = $c->libres();
                $c->ocupados = $c->ocupados();
                $c->disponibilidad = Practicas::nivelDisponibilidad($c->capacidad, $c->libres);
            })
            ->sortBy(fn ($c) => [$c->postulo_aqui ? 0 : 1, $c->otra_region ? 1 : 0, $c->nombre])->values();

        $docs = $p->documentos;
        $cancelacionesPrevias = $p->postulante?->practicasPreviasCanceladas() ?? collect();

        return view('secretaria.postulaciones.ver', [
            'postulacion' => $p,
            'postulante' => $p->postulante,
            'cancelacionesPrevias' => $cancelacionesPrevias,
            'documentosCedula' => $docs->whereIn('tipo', ['cedula_frontal', 'cedula_reverso'])->values(),
            'documentosUniversitarios' => $docs->whereIn('tipo', self::UNIVERSITARIOS)->values(),
            'faltaCedula' => $docs->whereIn('tipo', ['cedula_frontal', 'cedula_reverso'])->count() < 2,
            'consultorios' => $consultorios,
            'regionPropiaId' => $region,
        ]);
    }

    public function aceptar(Request $request, int $id)
    {
        $p = $this->postulacion($request, $id);

        // Competencia territorial: Jurídica Central solo puede tramitar admisiones de la Región del Biobío
        if ($request->user()->rol === 'juridica_central' && $p->region?->codigo !== 'BI') {
            abort(403, 'La Secretaría Jurídica Central solo puede tramitar admisiones de la Región del Biobío. Las postulaciones de regiones externas son competencia exclusiva de su Secretaría Regional de origen.');
        }

        $datos = $request->validate([
            'consultorio_id' => ['required', 'integer', 'exists:consultorios,id'],
            'abogado_id' => ['nullable', 'integer'],
            'nota' => ['nullable', 'string', 'max:2000'],
        ], ['consultorio_id.required' => 'Seleccione un consultorio.', 'consultorio_id.exists' => 'Seleccione un consultorio válido.']);

        $r = Postulaciones::asignar($p, $request->user(), (int) $datos['consultorio_id'], $datos['abogado_id'] ?? null, $datos['nota'] ?? null);

        $rutaRedir = $request->user()->rol === 'juridica_central' ? 'secretaria.juridica.postulaciones' : 'secretaria.postulaciones';

        if ($r['tipo'] === 'derivada') {
            return redirect()->route($rutaRedir)->with('ok', 'Postulación derivada a otra región. Quedó a cargo de su secretaría.');
        }

        $redir = redirect()->route($rutaRedir)
            ->with('ok', 'Postulación aceptada. Ingrese a la ficha de la práctica para descargar la Carta de Presentación oficial, tramitar su firma física/electrónica y cargarla firmada para oficializar el inicio y notificar al Abogado Jefe.')
            ->with('practica_creada_id', $r['practica']->id);

        return $r['passwordTemporal']
            ? $redir->with('credenciales', ['email' => $p->postulante->email, 'password' => $r['passwordTemporal']])
            : $redir;
    }

    public function rechazar(Request $request, int $id)
    {
        $p = $this->postulacion($request, $id);

        // Competencia territorial: Jurídica Central solo puede tramitar admisiones de la Región del Biobío
        if ($request->user()->rol === 'juridica_central' && $p->region?->codigo !== 'BI') {
            abort(403, 'La Secretaría Jurídica Central solo puede tramitar admisiones de la Región del Biobío. Las postulaciones de regiones externas son competencia exclusiva de su Secretaría Regional de origen.');
        }

        $datos = $request->validate(['motivo' => ['nullable', 'string', 'max:2000']]);
        Postulaciones::rechazar($p, $request->user(), $datos['motivo'] ?? null);

        $rutaRedir = $request->user()->rol === 'juridica_central' ? 'secretaria.juridica.postulaciones' : 'secretaria.postulaciones';

        return redirect()->route($rutaRedir)->with('ok', 'Postulación rechazada.');
    }

    public function notaCrear(Request $request, int $id)
    {
        $p = $this->postulacion($request, $id)->load('postulante');
        $datos = $request->validate(['texto' => ['required', 'string', 'max:2000']], ['texto.required' => 'Escriba el comentario.']);
        $nota = $p->notas()->create(['autor_usuario_id' => $request->user()->id, 'texto' => $datos['texto']]);

        Auditoria::registrar('postulacion.nota_creada', 'postulacion', $p->id, [
            'nota_id' => $nota->id,
            'texto' => $datos['texto'],
            'rut_postulante' => $p->postulante?->rut,
        ], $request->user()->id);

        return back()->with('ok', 'Comentario agregado.');
    }

    public function notaEliminar(Request $request, int $id, int $nota)
    {
        $p = $this->postulacion($request, $id)->load('postulante');
        $n = $p->notas()->findOrFail($nota);
        abort_unless($n->autor_usuario_id === $request->user()->id, 403, 'Solo puede eliminar sus propios comentarios.');

        $textoEliminado = $n->texto;
        $notaId = $n->id;
        $creadaEl = $n->created_at?->toIso8601String();

        $n->delete();

        Auditoria::registrar('postulacion.nota_eliminada', 'postulacion', $p->id, [
            'nota_id' => $notaId,
            'texto_eliminado' => $textoEliminado,
            'rut_postulante' => $p->postulante?->rut,
            'creada_el' => $creadaEl,
            'motivo' => 'Eliminación de anotación administrativa interna (resguardo de trazabilidad)',
        ], $request->user()->id);

        return back()->with('ok', 'Comentario eliminado.');
    }

    private function postulacion(Request $request, int $id): Postulacion
    {
        $u = $request->user();
        $q = Postulacion::with(['postulante', 'region']);
        if ($u->rol === 'secretaria') {
            $q->where('region_id', Scope::regionId($u));
        }

        return $q->findOrFail($id);
    }
}
