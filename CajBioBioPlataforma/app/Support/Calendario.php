<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class Calendario
{
    public static function esFeriado(CarbonInterface|string $fecha): bool
    {
        return in_array(Carbon::parse($fecha)->toDateString(), config('feriados'), true);
    }

    public static function esHabil(CarbonInterface|string $fecha): bool
    {
        $f = Carbon::parse($fecha);

        return ! $f->isWeekend() && ! self::esFeriado($f);
    }
}
