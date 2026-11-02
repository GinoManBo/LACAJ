<?php

namespace App\Support;

final class Notas
{
    public const MINIMA_APROBACION = 4.0;
    public const NOTA_NIVELADA_OFICIO = 5.0; // Concepto "Buena"

    public const CRITERIOS = [
        'conocimiento_juridico' => 'Conocimiento y criterio jurídico',
        'responsabilidad' => 'Responsabilidad',
        'iniciativa' => 'Iniciativa',
        'sentido_social' => 'Sentido social y de colaboración',
        'conducta' => 'Conducta',
        'honorabilidad' => 'Honorabilidad',
        'asistencia_puntualidad' => 'Asistencia y puntualidad',
    ];

    /**
     * Opciones institucionales de calificación en palabras.
     * En la plataforma la calificación se gestiona y presenta en palabras,
     * asociando un ancla decimal interna para compatibilidad de base de datos.
     */
    public const OPCIONES = [
        'SOBRESALIENTE' => [
            'nombre' => 'Sobresaliente',
            'valor' => 7.0,
            'aprobatorio' => true,
        ],
        'MUY_BUENA' => [
            'nombre' => 'Muy Buena',
            'valor' => 6.0,
            'aprobatorio' => true,
        ],
        'BUENA' => [
            'nombre' => 'Buena',
            'valor' => 5.0,
            'aprobatorio' => true,
        ],
        'REGULAR' => [
            'nombre' => 'Regular',
            'valor' => 4.0,
            'aprobatorio' => true,
        ],
        'DEFICIENTE' => [
            'nombre' => 'Deficiente',
            'valor' => 3.0,
            'aprobatorio' => false,
        ],
    ];

    public const CONCEPTOS = [
        'SOBRESALIENTE' => 'Sobresaliente',
        'MUY_BUENA' => 'Muy Buena',
        'MUY BUENO' => 'Muy Buena',
        'BUENA' => 'Buena',
        'REGULAR' => 'Regular',
        'DEFICIENTE' => 'Deficiente',
    ];

    /**
     * Expresa la calificación en el concepto reglamentario de la institución (solo palabras).
     * Escala institucional:
     * - Sobresaliente (>= 6.5)
     * - Muy Buena (5.5 a 6.4)
     * - Buena (4.5 a 5.4, o nivelada de oficio)
     * - Regular (4.0 a 4.4, aprobatorio sujeto a revisión)
     * - Deficiente (< 4.0, reprobatorio)
     */
    public static function concepto(float|int|string|null $nota): string
    {
        if ($nota === null) {
            return 'Sin calificar';
        }
        $val = (float) $nota;

        return match (true) {
            $val >= 6.5 => 'Sobresaliente',
            $val >= 5.5 => 'Muy Buena',
            $val >= 4.5 => 'Buena',
            $val >= 4.0 => 'Regular',
            default => 'Deficiente',
        };
    }

    /**
     * Determina si la calificación califica para la regla de nivelación de oficio (concepto Regular).
     */
    public static function esNivelable(float|int|string|null $nota): bool
    {
        if ($nota === null) {
            return false;
        }
        $val = (float) $nota;

        return $val >= self::MINIMA_APROBACION && $val < self::NOTA_NIVELADA_OFICIO;
    }

    /**
     * Determina si el concepto es aprobatorio.
     */
    public static function esAprobatorio(string $concepto): bool
    {
        $c = mb_strtoupper(trim($concepto));

        return in_array($c, ['SOBRESALIENTE', 'MUY BUENA', 'MUY_BUENA', 'MUY BUENO', 'BUENA', 'BUENO', 'REGULAR', 'SUFICIENTE'], true);
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
