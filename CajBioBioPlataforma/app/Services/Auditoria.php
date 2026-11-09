<?php

namespace App\Services;

use App\Models\Auditoria as Registro;

class Auditoria
{
    public static function registrar(string $accion, string $entidad, ?int $entidadId = null, ?array $datos = null, ?int $usuarioId = null): void
    {
        Registro::create([
            'usuario_id' => $usuarioId ?? auth()->id(),
            'accion' => $accion,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'datos' => $datos,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent() ?: null,
        ]);
    }
}
