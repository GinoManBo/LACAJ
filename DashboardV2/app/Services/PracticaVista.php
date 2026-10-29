<?php

namespace App\Services;

use App\Models\Practica;

final class PracticaVista
{
    public static function diasRestantes(Practica $p): int
    {
        return (int) now()->startOfDay()->diffInDays($p->fecha_termino->copy()->startOfDay(), false);
    }

    public static function porTerminar(Practica $p): bool
    {
        $dias = self::diasRestantes($p);

        return $p->estado === 'activa' && $dias >= 0 && $dias <= config('caj_documentos.duracion_dias_por_terminar');
    }

    public static function estadoMostrado(Practica $p): string
    {
        return self::porTerminar($p) ? 'por_terminar' : $p->estado;
    }
}
