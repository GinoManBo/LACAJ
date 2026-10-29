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
            return 'El practicante aún no sube su informe de práctica; la encuesta se habilita al recibirlo.';
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

            Auditoria::registrar('practica.evaluar', 'practica', $p->id, ['promedio' => $promedio, 'aprobado' => $aprobado]);

            if ($usuario = $p->practicante->usuario_id) {
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
