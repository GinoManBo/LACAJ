<?php

namespace App\Services;

use App\Models\Practica;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Practicas
{
    public const MOTIVOS_CANCELACION = [
        'conducta' => 'Conducta del postulante',
        'decision' => 'Decisión del postulante',
        'error_registro' => 'Error de registro administrativo',
        'otro' => 'Otro motivo',
    ];

    public static function textoMotivoCancelacion(?string $motivo): string
    {
        if (! $motivo) {
            return 'No especificado';
        }

        return self::MOTIVOS_CANCELACION[$motivo] ?? ucfirst(str_replace('_', ' ', $motivo));
    }
    public const DIAS_POR_TERMINAR = 14;

    /** @return array{total:int, transcurridos:int, restantes:int, porcentaje:int} */
    public static function estadistica(Practica $p): array
    {
        $total = max(1, (int) $p->fecha_inicio->diffInDays($p->fecha_termino));
        $transcurridos = (int) max(0, min($total, $p->fecha_inicio->diffInDays(today(), false)));

        return [
            'total' => $total,
            'transcurridos' => $transcurridos,
            'restantes' => $total - $transcurridos,
            'porcentaje' => (int) round($transcurridos / $total * 100),
        ];
    }

    public static function porTerminar(Practica $p): bool
    {
        return $p->estado === 'activa'
            && $p->fecha_termino->between(today(), today()->addDays(self::DIAS_POR_TERMINAR));
    }

    public static function estadoVisible(Practica $p): string
    {
        return self::porTerminar($p) ? 'por_terminar' : $p->estado;
    }

    public static function cancelar(Practica $p, Usuario $actor, string $motivo, ?string $detalle): void
    {
        if (! in_array($p->estado, ['por_iniciar', 'activa'], true)) {
            throw ValidationException::withMessages(['motivo' => 'Esta práctica ya no se puede cancelar.']);
        }

        DB::transaction(function () use ($p, $actor, $motivo, $detalle) {
            $p->update([
                'estado' => 'cancelada', 'cancelacion_motivo' => $motivo, 'cancelacion_detalle' => $detalle,
                'cancelacion_fecha' => today(), 'cancelacion_antes_de_iniciar' => today()->lt($p->fecha_inicio),
                'cancelada_por' => $actor->id,
            ]);
            Auditoria::registrar('practica.cancelar', 'practica', $p->id, ['motivo' => $motivo], $actor->id);

            $usuarioId = $p->practicante->usuario_id;
            if ($usuarioId) {
                Notificaciones::crear($usuarioId, 'aviso', 'Práctica cancelada', 'Su práctica fue cancelada. Revise el documento de cancelación con su secretaría.', 'practica', $p->id);
            }
            if ($p->abogado) {
                Notificaciones::crear($p->abogado->usuario_id, 'aviso', 'Práctica cancelada',
                    "Se canceló la práctica de {$p->practicante->postulante->nombre} {$p->practicante->postulante->apellido}.", 'practica', $p->id);
            }
        });
    }

    public static function nivelDisponibilidad(int $capacidad, int $libres): string
    {
        if ($libres <= 0 || $capacidad <= 0) {
            return 'sin';
        }

        return $libres >= $capacidad / 2 ? 'muchos' : 'pocos';
    }
}
