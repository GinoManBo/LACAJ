<?php

namespace App\Services;

use App\Models\Abogado;
use App\Models\Calificacion;
use App\Models\Evaluacion;
use App\Models\Practica;
use App\Support\Notas;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvaluacionesPractica
{
    public static function bloqueo(Practica $p): ?string
    {
        if ($p->evaluacion()->exists()) {
            return 'Esta práctica ya fue evaluada.';
        }
        if ($p->estado !== 'activa') {
            return 'Solo se evalúan prácticas en curso.';
        }
        if (! InformesPractica::vigente($p)) {
            return 'El postulante aún no sube su informe de práctica; la encuesta se habilita al recibirlo.';
        }

        return null;
    }

    /** @param array<string,float> $puntajes claves de Notas::CRITERIOS */
    public static function evaluar(Practica $p, Abogado $abogado, array $puntajes, ?string $observaciones): Evaluacion
    {
        if ($mensaje = self::bloqueo($p)) {
            throw ValidationException::withMessages(['evaluacion' => $mensaje]);
        }

        return DB::transaction(function () use ($p, $abogado, $puntajes, $observaciones) {
            $p = Practica::whereKey($p->id)->lockForUpdate()->first();
            if ($p->evaluacion()->exists()) {
                throw ValidationException::withMessages(['evaluacion' => 'Esta práctica ya fue evaluada.']);
            }

            // Capturar estado previo para trazabilidad completa
            $estadoPrevio = $p->estado;
            $notaPrevia = $p->nota_practica;

            $promedio = Notas::promedio($puntajes);
            $aprobado = Notas::aprobada($promedio);
            $nota = round($promedio, 1);

            $evaluacion = Evaluacion::create($puntajes + [
                'practica_id' => $p->id, 'abogado_id' => $abogado->id,
                'promedio' => $promedio, 'aprobado' => $aprobado, 'observaciones' => $observaciones,
            ]);
            Calificacion::create([
                'practica_id' => $p->id, 'instancia' => 1, 'nota' => $nota, 'fecha' => now()->toDateString(),
                'registrada_por' => $abogado->usuario_id, 'observaciones' => $observaciones,
            ]);
            $p->update(['nota_practica' => $nota, 'estado' => 'finalizada', 'finalizada_at' => now()]);

            // Cargar relaciones para identificar actor y afectado
            $p->loadMissing(['practicante.postulante', 'consultorio.region']);
            $abogado->loadMissing('usuario');
            $postulante = $p->practicante?->postulante ?? $p->postulacion?->postulante;

            // Desglose de los 7 criterios reglamentarios institucionales
            $criteriosDetalle = [];
            foreach (Notas::CRITERIOS as $clave => $etiqueta) {
                $val = isset($puntajes[$clave]) ? (float) $puntajes[$clave] : null;
                $criteriosDetalle[$clave] = [
                    'etiqueta' => $etiqueta,
                    'nota' => $val,
                    'concepto' => Notas::concepto($val),
                ];
            }

            $datosAuditoria = [
                'antes' => [
                    'estado' => $estadoPrevio,
                    'nota_practica' => $notaPrevia,
                    'evaluacion_id' => null,
                ],
                'despues' => [
                    'estado' => 'finalizada',
                    'nota_practica' => $nota,
                    'promedio' => $promedio,
                    'concepto' => Notas::concepto($nota),
                    'aprobado' => $aprobado,
                    'criterios' => $puntajes,
                    'criterios_detalle' => $criteriosDetalle,
                    'observaciones' => $observaciones,
                ],
                'actor' => [
                    'usuario_id' => $abogado->usuario_id,
                    'abogado_id' => $abogado->id,
                    'nombre' => $abogado->usuario?->nombre_completo ?? 'Tutor',
                    'rut' => $abogado->usuario?->rut,
                ],
                'afectado' => [
                    'postulante_id' => $postulante?->id,
                    'practicante_id' => $p->practicante_id,
                    'rut' => $postulante?->rut,
                    'nombre' => $postulante?->nombre_completo,
                ],
                'consultorio' => [
                    'id' => $p->consultorio_id,
                    'nombre' => $p->consultorio?->nombre,
                    'region' => $p->consultorio?->region?->nombre ?? $p->consultorio?->region?->codigo,
                ],
                // Claves de conveniencia para filtros rápidos y consultas de auditoría
                'postulante_rut' => $postulante?->rut,
                'postulante_nombre' => $postulante?->nombre_completo,
                'promedio' => $promedio,
                'nota' => $nota,
                'concepto' => Notas::concepto($nota),
                'aprobado' => $aprobado,
                'observaciones' => $observaciones,
            ];

            Auditoria::registrar(
                'practica.evaluar',
                'practica',
                $p->id,
                $datosAuditoria,
                $abogado->usuario_id ?? auth()->id()
            );

            if ($usuario = $p->practicante?->usuario_id) {
                Notificaciones::crear(
                    $usuario, 'aviso', 'Práctica evaluada',
                    $aprobado
                        ? 'Tu abogado tutor registró tu evaluación final: práctica aprobada. Tus documentos estarán disponibles cuando el Gobernador Regional los autorice.'
                        : 'Tu abogado tutor registró tu evaluación final. Revisa tus calificaciones.',
                    'practica', $p->id,
                );
            }

            return $evaluacion;
        });
    }
}
