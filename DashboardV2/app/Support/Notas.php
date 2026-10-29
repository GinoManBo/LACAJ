<?php

namespace App\Support;

final class Notas
{
    public const MINIMA_APROBACION = 4.0;

    public const CRITERIOS = [
        'conocimiento_juridico' => 'Conocimiento y criterio jurídico',
        'responsabilidad' => 'Responsabilidad',
        'iniciativa' => 'Iniciativa',
        'sentido_social' => 'Sentido social y de colaboración',
        'conducta' => 'Conducta',
        'honorabilidad' => 'Honorabilidad',
        'asistencia_puntualidad' => 'Asistencia y puntualidad',
    ];

    public static function concepto(float $nota): string
    {
        return match (true) {
            $nota >= 6.5 => 'SOBRESALIENTE',
            $nota >= 5.5 => 'MUY BUENO',
            $nota >= 4.5 => 'BUENO',
            $nota >= 4.0 => 'SUFICIENTE',
            default => 'DEFICIENTE',
        };
    }

    /** @param array<string,float|int|string> $puntajes */
    public static function promedio(array $puntajes): float
    {
        $valores = array_map(fn ($k) => (float) $puntajes[$k], array_keys(self::CRITERIOS));

        return round(array_sum($valores) / count($valores), 2);
    }

    public static function aprobada(float $promedio): bool
    {
        return $promedio >= self::MINIMA_APROBACION;
    }

    /** @param float[] $notas */
    public static function promedioInstancias(array $notas): ?float
    {
        return $notas === [] ? null : round(array_sum($notas) / count($notas), 2);
    }
}
