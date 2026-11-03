<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Calificacion;
use App\Models\Consultorio;
use App\Models\Practica;
use App\Services\Almacenamiento;
use App\Services\Auditoria;
use App\Services\CartaPresentacionService;
use App\Services\Notificaciones;
use App\Services\Practicas;
use App\Support\Notas;
use App\Support\Scope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class PracticasController extends Controller
{
    public function index(Request $request)
    {
        $region = Scope::regionId($request->user());
        $practicas = $this->consulta($request, $region)->get()->each(fn ($p) => $this->decorar($p));
        $capacidad = Consultorio::where('region_id', $region)->where('activo', true)->sum('capacidad');
        $ocupados = Practica::whereHas('consultorio', fn ($q) => $q->where('region_id', $region))
            ->whereIn('estado', ['por_iniciar', 'activa'])
            ->where('fecha_termino', '>', today())
            ->count();

        return view('secretaria.practicas.index', [
            'practicas' => $practicas,
            'q' => trim((string) $request->query('q')),
            'cuposTotales' => (int) $capacidad,
            'cuposDisponibles' => max(0, (int) $capacidad - $ocupados),
            'motivos' => Practicas::MOTIVOS_CANCELACION,
        ]);
    }

    public function ver(Request $request, int $id)
    {
        $p = $this->practica($request, $id, ['por_iniciar', 'activa', 'por_terminar', 'finalizada', 'cancelada'])
            ->load('practicante.postulante.comuna', 'consultorio.comuna', 'abogado.usuario', 'documentos', 'calificaciones.registradaPor');
        $this->decorar($p);

        $calif1 = $p->calificaciones->firstWhere('instancia', 1);
        $calif2 = $p->calificaciones->firstWhere('instancia', 2);

        return view('secretaria.practicas.ver', [
            'practica' => $p,
            'motivos' => Practicas::MOTIVOS_CANCELACION,
            'calif1' => $calif1,
            'calif2' => $calif2,
            'opcionesNotas' => Notas::OPCIONES,
        ]);
    }

    public function estadistica(Request $request, int $id)
    {
        $p = $this->practica($request, $id)->load('practicante.postulante', 'consultorio', 'abogado.usuario');
        $this->decorar($p);

        return view('secretaria.practicas.estadistica', ['practica' => $p, 'estadistica' => $p->estadistica]);
    }

    public function descargarCartaPresentacion(Request $request, int $id)
    {
        $p = $this->practica($request, $id, ['por_iniciar', 'activa', 'finalizada', 'remitida_biobio', 'nivelada_biobio', 'cerrada_historial']);
        $pdf = CartaPresentacionService::generarPdf($p);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="carta-presentacion-practica-'.$p->id.'.pdf"',
        ]);
    }

    public function subirCartaPresentacion(Request $request, int $id)
    {
        $p = $this->practica($request, $id, ['por_iniciar', 'activa'])->load('practicante.postulante', 'consultorio', 'abogado.usuario');

        $request->validate([
            'carta_firmada' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'carta_firmada.required' => 'Seleccione el archivo PDF con la Carta de Presentación firmada.',
            'carta_firmada.mimes' => 'El archivo debe ser un documento PDF.',
            'carta_firmada.max' => 'El archivo no puede pesar más de 5 MB.',
        ]);

        $guardado = Almacenamiento::guardar($request->file('carta_firmada'));

        $p->update([
            'carta_presentacion_firmada' => $guardado['nombre_almacenado'],
            'carta_presentacion_subida_at' => now(),
            'estado' => 'activa', // Inicio manual oficial
        ]);

        Auditoria::registrar('practica.carta_presentacion_firmada', 'practica', $p->id, [
            'archivo' => $guardado['nombre_original'],
        ], $request->user()->id);

        if ($p->abogado?->usuario_id) {
            Notificaciones::crear(
                $p->abogado->usuario_id,
                'aviso',
                'Nuevo practicante incorporado: Inicio oficial',
                "La Secretaría Regional ha cargado la Carta de Presentación firmada. El postulante {$p->practicante->postulante->nombre_completo} ha iniciado oficialmente su práctica en su consultorio.",
                'practica',
                $p->id
            );
        }

        return back()->with('ok', 'Carta de Presentación firmada registrada exitosamente. Se ha activado el inicio oficial y notificado al Abogado Jefe. Recuerde remitir formalmente este documento al Abogado Jefe.');
    }

    public function verCartaPresentacionFirmada(Request $request, int $id)
    {
        $p = $this->practica($request, $id, ['por_iniciar', 'activa', 'finalizada', 'remitida_biobio', 'nivelada_biobio', 'cerrada_historial']);

        if (! $p->carta_presentacion_firmada || ! Almacenamiento::existe($p->carta_presentacion_firmada)) {
            abort(404, 'No se ha encontrado la carta de presentación firmada.');
        }

        return response()->file(Almacenamiento::ruta($p->carta_presentacion_firmada), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function calificarSegundaInstancia(Request $request, int $id)
    {
        $p = $this->practica($request, $id, ['por_iniciar', 'activa', 'finalizada', 'remitida_biobio', 'nivelada_biobio'])->load('practicante.postulante');

        $datos = $request->validate([
            'concepto' => ['required', 'string', 'in:'.implode(',', array_keys(Notas::OPCIONES))],
            'observaciones' => ['required', 'string', 'min:10', 'max:3000'],
        ], [
            'concepto.required' => 'Seleccione una calificación en palabras para segunda instancia.',
            'concepto.in' => 'Seleccione una opción válida de calificación.',
            'observaciones.required' => 'Debe ingresar las observaciones de los alegatos en la Corte de Apelaciones.',
            'observaciones.min' => 'Las observaciones deben contener al menos 10 caracteres.',
        ]);

        $opcion = Notas::OPCIONES[$datos['concepto']];

        Calificacion::updateOrCreate(
            ['practica_id' => $p->id, 'instancia' => 2],
            [
                'nota' => $opcion['valor'],
                'fecha' => now()->toDateString(),
                'registrada_por' => $request->user()->id,
                'observaciones' => 'Carga asistida 2ª Instancia (Corte de Apelaciones - '.$opcion['nombre'].'): '.$datos['observaciones'],
            ]
        );

        Auditoria::registrar('practica.segunda_instancia_asistida', 'practica', $p->id, [
            'concepto' => $opcion['nombre'],
            'registrada_por' => $request->user()->id,
        ], $request->user()->id);

        return back()->with('ok', 'Calificación de Segunda Instancia (Corte de Apelaciones) registrada en concepto «'.$opcion['nombre'].'».');
    }

    public function exportar(Request $request)
    {
        $region = Scope::regionId($request->user());
        $practicas = $this->consulta($request, $region)->get();

        $ruta = tempnam(sys_get_temp_dir(), 'prac').'.xlsx';
        $w = new Writer();
        $w->openToFile($ruta);
        $w->addRow(Row::fromValues(['Practicante', 'RUT', 'Universidad', 'Consultorio', 'Abogado tutor', 'Inicio', 'Término', 'Estado', 'Duración (días)', 'Días transcurridos', 'Días restantes', 'Avance (%)']));
        foreach ($practicas as $p) {
            $e = Practicas::estadistica($p);
            $po = $p->practicante->postulante;
            $w->addRow(Row::fromValues([
                $po->nombre.' '.$po->apellido, $po->rut, $po->universidad, $p->consultorio->nombre,
                $p->abogado ? $p->abogado->usuario->nombre.' '.$p->abogado->usuario->apellido : 'Sin asignar',
                $p->fecha_inicio->format('d-m-Y'), $p->fecha_termino->format('d-m-Y'), Practicas::estadoVisible($p),
                $e['total'], $e['transcurridos'], $e['restantes'], $e['porcentaje'],
            ]));
        }
        $w->close();

        return response()->download($ruta, 'practicas-activas-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
    }

    public function cancelar(Request $request, int $id)
    {
        $p = $this->practica($request, $id)->load('practicante.postulante', 'abogado');
        $datos = $request->validate([
            'motivo' => ['required', 'in:'.implode(',', array_keys(Practicas::MOTIVOS_CANCELACION))],
            'detalle' => ['nullable', 'string', 'max:2000'],
        ], ['motivo.required' => 'Seleccione el motivo de cancelación.', 'motivo.in' => 'Seleccione un motivo válido.']);

        Practicas::cancelar($p, $request->user(), $datos['motivo'], $datos['detalle'] ?? null);

        return redirect()->route('secretaria.practicas')->with('ok', 'Práctica cancelada.')
            ->with('documento_cancelacion', route('secretaria.cancelacion.documento', $p->id));
    }

    public function documentoCancelacion(Request $request, int $id)
    {
        $p = $this->practica($request, $id, ['cancelada'])->load('practicante.postulante', 'consultorio.region', 'abogado.usuario', 'canceladaPor');

        $pdf = Pdf::loadView('pdf.cancelacion', [
            'practica' => $p, 'postulante' => $p->practicante->postulante,
            'motivo' => Practicas::MOTIVOS_CANCELACION[$p->cancelacion_motivo] ?? $p->cancelacion_motivo,
            'emitidoPor' => $request->user(),
        ]);

        return $pdf->download('cancelacion-practica-'.$p->id.'.pdf');
    }

    private function consulta(Request $request, ?int $region)
    {
        $q = trim((string) $request->query('q'));
        $qLimpio = preg_replace('/[^0-9kK]/', '', $q);

        return Practica::with('practicante.postulante', 'consultorio', 'abogado.usuario')
            ->whereHas('consultorio', fn ($c) => $c->where('region_id', $region))
            ->whereIn('estado', ['por_iniciar', 'activa'])
            ->when($q !== '', fn ($query) => $query->whereHas('practicante.postulante', fn ($p) => $p
                ->where('nombre', 'like', "%$q%")
                ->orWhere('apellido', 'like', "%$q%")
                ->orWhere('rut', 'like', "%$q%")
                ->when($qLimpio !== '', fn ($sub) => $sub->orWhere('rut', 'like', "%$qLimpio%"))
            ))
            ->orderBy('fecha_termino');
    }

    private function practica(Request $request, int $id, array $estados = ['por_iniciar', 'activa']): Practica
    {
        $u = $request->user();
        $q = Practica::whereIn('estado', $estados);
        if ($u->rol === 'secretaria') {
            $q->whereHas('consultorio', fn ($c) => $c->where('region_id', Scope::regionId($u)));
        }

        return $q->findOrFail($id);
    }

    private function decorar(Practica $p): Practica
    {
        $p->estadistica = Practicas::estadistica($p);
        $p->estado_visible = Practicas::estadoVisible($p);

        return $p;
    }
}
