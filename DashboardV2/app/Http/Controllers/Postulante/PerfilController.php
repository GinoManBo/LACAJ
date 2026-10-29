<?php

namespace App\Http\Controllers\Postulante;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Postulacion\PostulacionController;
use App\Models\Comuna;
use App\Models\Especialidad;
use App\Models\Postulacion;
use App\Models\PostulacionBorrador;
use App\Models\Region;
use App\Models\Usuario;
use App\Services\Auditoria;
use App\Services\Notificaciones;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    public function inicio(Request $request, PostulacionController $formulario)
    {
        $u = $request->user();
        $borrador = PostulacionBorrador::where('usuario_id', $u->id)->first();

        $enviada = Postulacion::with('postulante', 'preferencias.consultorio')
            ->whereHas('postulante', fn ($q) => $q->where('rut', $u->rut))
            ->latest('fecha_postulacion')->first();
        $enRevision = $enviada && in_array($enviada->estado, ['pendiente', 'derivada'], true);

        $siguiente = $formulario->pasoIncompleto($borrador?->datos ?? []);

        return view('postulante.inicio', [
            'usuario' => $u,
            'borrador' => $borrador,
            'pasos' => PostulacionController::PASOS,
            'siguiente' => $siguiente,
            'enviada' => $enviada,
            'enRevision' => $enRevision,
        ]);
    }

    private function enRevision(Usuario $u): ?Postulacion
    {
        return Postulacion::with('postulante')->whereIn('estado', ['pendiente', 'derivada'])
            ->whereHas('postulante', fn ($q) => $q->where('rut', $u->rut))->latest('fecha_postulacion')->first();
    }

    public function datos(Request $request)
    {
        $postulacion = $this->enRevision($request->user());
        if (! $postulacion) {
            return redirect()->route('postulante.inicio')->with('aviso', 'Solo puede editar sus datos mientras su postulación está en revisión.');
        }

        return view('postulante.datos', [
            'usuario' => $request->user(), 'postulante' => $postulacion->postulante, 'postulacion' => $postulacion,
            'regiones' => Region::operativas()->orderBy('orden')->get(['id', 'nombre']),
            'comunas' => Comuna::whereHas('region', fn ($q) => $q->operativas())->orderBy('nombre')->get(['id', 'region_id', 'nombre']),
            'generos' => ['femenino' => 'Femenino', 'masculino' => 'Masculino', 'otro' => 'Otro', 'prefiere_no_decir' => 'Prefiero no decirlo'],
            'especialidades' => Especialidad::where('activo', true)->orderBy('orden')->get(['id', 'nombre']),
        ]);
    }

    public function guardarDatos(Request $request, PostulacionController $formulario)
    {
        $postulacion = $this->enRevision($request->user());
        abort_unless($postulacion, 403, 'Su postulación ya no está en revisión.');

        $d = $formulario->validarEdicion($request->all());
        $d['email'] = mb_strtolower($d['email']);
        $d['discapacidad'] = (bool) $d['discapacidad'];
        $d['discapacidad_descripcion'] = $d['discapacidad'] ? ($d['discapacidad_descripcion'] ?? null) : null;

        $postulante = $postulacion->postulante;
        $postulante->fill($d);
        $cambios = array_keys($postulante->getDirty());
        if ($cambios === []) {
            return redirect()->route('postulante.inicio')->with('ok', 'No había cambios que guardar.');
        }

        $postulante->datos_actualizados_at = now();
        $postulante->save();

        Auditoria::registrar('postulante.actualizar_datos', 'postulante', $postulante->id, ['campos' => $cambios], $request->user()->id);
        Usuario::where('rol', 'secretaria')->where('region_id', $postulacion->region_id)->where('activo', true)->each(
            fn ($s) => Notificaciones::crear($s->id, 'informativo', 'Postulante actualizó sus datos', "{$postulante->nombre} {$postulante->apellido} corrigió sus datos de postulación.", 'postulacion', $postulacion->id)
        );

        return redirect()->route('postulante.inicio')->with('ok', 'Sus datos se actualizaron. La secretaría ya ve los cambios.');
    }
}
