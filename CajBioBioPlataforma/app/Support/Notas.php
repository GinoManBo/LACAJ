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
     * En la plataforma la calificación se gestiona y presenta en palabras:
     * Sobresaliente, Buena, Regular y Mala.
     */
    public const OPCIONES = [
        'SOBRESALIENTE' => [
            'nombre' => 'Sobresaliente',
            'valor' => 7.0,
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
        'MALA' => [
            'nombre' => 'Mala',
            'valor' => 3.0,
            'aprobatorio' => false,
        ],
    ];

    public const CONCEPTOS = [
        'SOBRESALIENTE' => 'Sobresaliente',
        'BUENA' => 'Buena',
        'REGULAR' => 'Regular',
        'MALA' => 'Mala',
        // Alias de compatibilidad histórica
        'MUY_BUENA' => 'Sobresaliente',
        'MUY BUENO' => 'Sobresaliente',
        'DEFICIENTE' => 'Mala',
    ];

    /**
     * Expresa la calificación en el concepto reglamentario de la institución (solo palabras).
     * Escala institucional:
     * - Sobresaliente (>= 6.0)
     * - Buena (5.0 a 5.9)
     * - Regular (4.0 a 4.9)
     * - Mala (< 4.0, reprobatoria)
     */
    public static function concepto(float|int|string|null $nota): string
    {
        if ($nota === null) {
            return 'Sin calificar';
        }
        $val = (float) $nota;

        return match (true) {
            $val >= 6.0 => 'Sobresaliente',
            $val >= 5.0 => 'Buena',
            $val >= 4.0 => 'Regular',
            default => 'Mala',
        };
    }

    /**
     * Retorna la clave de OPCIONES correspondiente a la nota.
     */
    public static function claveConcepto(float|int|string|null $nota): string
    {
        if ($nota === null) {
            return '';
        }
        $val = (float) $nota;

        return match (true) {
            $val >= 6.0 => 'SOBRESALIENTE',
            $val >= 5.0 => 'BUENA',
            $val >= 4.0 => 'REGULAR',
            default => 'MALA',
        };
    }

    /**
     * Obtiene el valor decimal base a partir de un concepto en palabras o clave.
     */
    public static function valorDesdeConcepto(string|float|int|null $concepto): float
    {
        if (is_numeric($concepto)) {
            return (float) $concepto;
        }
        $c = strtoupper(str_replace(' ', '_', trim((string) $concepto)));
        if (isset(self::OPCIONES[$c])) {
            return (float) self::OPCIONES[$c]['valor'];
        }
        if ($c === 'MUY_BUENA' || $c === 'MUY_BUENO') {
            return 7.0;
        }
        if ($c === 'DEFICIENTE') {
            return 3.0;
        }

        return 5.0;
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

    /**
     * Determina si una calificación (número o concepto) es aprobatoria (>= 4.0 o concepto aprobatorio).
     */
    public static function esAprobatoria(float|int|string|null $nota): bool
    {
        if ($nota === null) {
            return false;
        }
        if (is_numeric($nota)) {
            return (float) $nota >= self::MINIMA_APROBACION;
        }

        return self::esAprobatorio((string) $nota);
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
