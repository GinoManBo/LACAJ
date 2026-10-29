<?php

namespace App\Support;

final class Rut
{
    public static function limpio(string $run): string
    {
        return strtoupper(preg_replace('/[.\-\s]/', '', $run) ?? '');
    }

    public static function valido(string $run): bool
    {
        $limpio = self::limpio($run);
        $cuerpo = substr($limpio, 0, -1);
        $dv = substr($limpio, -1);
        if (! preg_match('/^\d{7,8}$/', $cuerpo) || ! preg_match('/^[\dK]$/', $dv)) {
            return false;
        }

        $suma = 0;
        $factor = 2;
        for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
            $suma += (int) $cuerpo[$i] * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }
        $resto = 11 - ($suma % 11);
        $esperado = $resto === 11 ? '0' : ($resto === 10 ? 'K' : (string) $resto);

        return $dv === $esperado;
    }

    public static function formatear(string $run): string
    {
        $limpio = self::limpio($run);

        return number_format((int) substr($limpio, 0, -1), 0, '', '.').'-'.substr($limpio, -1);
    }

    public static function normalizar(string $run): string
    {
        $limpio = self::limpio($run);

        return substr($limpio, 0, -1).'-'.substr($limpio, -1);
    }
}
