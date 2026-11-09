<?php

namespace App\Http\Controllers\Postulante;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Postulacion\PostulacionController;
use App\Models\Comuna;
use App\Models\Documento;
use App\Models\Especialidad;
use App\Models\Postulacion;
use App\Models\PostulacionBorrador;
use App\Models\Region;
use App\Models\Usuario;
use App\Services\Almacenamiento;
use App\Services\Auditoria;
use App\Services\Notificaciones;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    public function inicio(Request $request, PostulacionController $formulario)
    {
        $u = $request->user();
        $borrador = PostulacionBorrador::where('usuario_id', $u->id)->first();

        $enviada = Postulacion::with(['postulante', 'preferencias.consultorio', 'practica.consultorio'])
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
            'practica' => $enviada?->practica,
            'enRevision' => $enRevision,
        ]);
    }

    private function enRevision(Usuario $u): ?Postulacion
    {
        return Postulacion::with(['postulante', 'documentos'])->whereIn('estado', ['pendiente', 'derivada'])
            ->whereHas('postulante', fn ($q) => $q->where('rut', $u->rut))->latest('fecha_postulacion')->first();
    }

    public function datos(Request $request)
    {
        $postulacion = $this->enRevision($request->user());
        if (! $postulacion) {
            return redirect()->route('postulante.inicio')->with('aviso', 'Solo puede editar sus datos mientras su postulación está en revisión.');
        }

        return view('postulante.datos', [
            'usuario' => $request->user(),
            'postulante' => $postulacion->postulante,
            'postulacion' => $postulacion,
            'documentos' => $postulacion->documentos->keyBy('tipo'),
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

        // Validación de archivos adjuntos (cédula y certificado de egreso)
        $reglaCedula = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
        $reglaDoc = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.(config('caj.max_upload_mb', 8) * 1024)];

        $request->validate([
            'cedula_frontal' => $reglaCedula,
            'cedula_reverso' => $reglaCedula,
            'certificado_egreso' => $reglaDoc,
        ], [
            'file' => 'El archivo adjunto no es válido.',
            'mimes' => 'Formato no permitido (se acepta PDF, JPG o PNG).',
            'max' => 'El archivo supera el tamaño máximo permitido.',
        ]);

        $d = $formulario->validarEdicion($request->all());
        if (($d['universidad'] ?? '') === 'Otra institución no listada' && ! empty($request->input('universidad_otra'))) {
            $d['universidad'] = trim($request->input('universidad_otra'));
        }
        $d['email'] = mb_strtolower($d['email']);
        $d['discapacidad'] = (bool) $d['discapacidad'];
        $d['discapacidad_descripcion'] = $d['discapacidad'] ? ($d['discapacidad_descripcion'] ?? null) : null;

        $postulante = $postulacion->postulante;
        $postulante->fill($d);
        $cambios = array_keys($postulante->getDirty());

        $documentosActualizados = [];
        $archivosPermitidos = ['cedula_frontal', 'cedula_reverso', 'certificado_egreso'];

        foreach ($archivosPermitidos as $tipo) {
            if ($request->hasFile($tipo)) {
                $file = $request->file($tipo);
                $info = Almacenamiento::guardar($file);

                $docExistente = $postulacion->documentos()->where('tipo', $tipo)->first();
                if ($docExistente) {
                    $antiguo = $docExistente->nombre_almacenado;
                    $docExistente->update([
                        'nombre_original' => $info['nombre_original'],
                        'nombre_almacenado' => $info['nombre_almacenado'],
                        'mime' => $info['mime'],
                        'tamano_bytes' => $info['tamano_bytes'],
                        'sha256' => $info['sha256'],
                        'subido_por' => $request->user()->id,
                    ]);
                    if ($antiguo && Almacenamiento::existe($antiguo)) {
                        Almacenamiento::eliminar($antiguo);
                    }
                } else {
                    $postulacion->documentos()->create([
                        'tipo' => $tipo,
                        'nombre_original' => $info['nombre_original'],
                        'nombre_almacenado' => $info['nombre_almacenado'],
                        'mime' => $info['mime'],
                        'tamano_bytes' => $info['tamano_bytes'],
                        'sha256' => $info['sha256'],
                        'subido_por' => $request->user()->id,
                    ]);
                }

                $documentosActualizados[] = match ($tipo) {
                    'cedula_frontal' => 'Cédula (lado frontal)',
                    'cedula_reverso' => 'Cédula (lado reverso)',
                    'certificado_egreso' => 'Certificado de egreso',
                    default => $tipo,
                };

                Auditoria::registrar('postulacion.actualizar_documento', 'documento', $postulacion->id, [
                    'tipo' => $tipo,
                    'nombre_original' => $info['nombre_original'],
                ], $request->user()->id);
            }
        }

        if ($cambios === [] && empty($documentosActualizados)) {
            return redirect()->route('postulante.inicio')->with('ok', 'No había cambios que guardar.');
        }

        $postulante->datos_actualizados_at = now();
        $postulante->save();

        if ($request->user() && $request->user()->email !== $d['email']) {
            $request->user()->update(['email' => $d['email']]);
        }

        if ($cambios !== []) {
            Auditoria::registrar('postulante.actualizar_datos', 'postulante', $postulante->id, ['campos' => $cambios], $request->user()->id);
        }

        $notifMensaje = empty($documentosActualizados)
            ? "{$postulante->nombre} {$postulante->apellido} corrigió sus datos de postulación."
            : (empty($cambios)
                ? "{$postulante->nombre} {$postulante->apellido} actualizó sus documentos (" . implode(', ', $documentosActualizados) . ")."
                : "{$postulante->nombre} {$postulante->apellido} corrigió sus datos y actualizó documentos (" . implode(', ', $documentosActualizados) . ").");

        Usuario::where('rol', 'secretaria')->where('region_id', $postulacion->region_id)->where('activo', true)->each(
            fn ($s) => Notificaciones::crear($s->id, 'informativo', 'Postulante actualizó antecedentes', $notifMensaje, 'postulacion', $postulacion->id)
        );

        return redirect()->route('postulante.inicio')->with('ok', 'Sus datos y documentos se actualizaron exitosamente. La secretaría ya ve los cambios.');
    }
}
