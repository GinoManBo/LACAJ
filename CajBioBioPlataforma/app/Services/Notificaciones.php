<?php

namespace App\Services;

use App\Models\Notificacion;

class Notificaciones
{
    /** @param 'urgente'|'aviso'|'informativo' $tipo */
    public static function crear(int $usuarioDestinoId, string $tipo, string $titulo, string $mensaje, ?string $entidadTipo = null, ?int $entidadId = null): Notificacion
    {
        return Notificacion::create([
            'usuario_destino_id' => $usuarioDestinoId,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'entidad_tipo' => $entidadTipo,
            'entidad_id' => $entidadId,
            'creada_por' => auth()->id(),
        ]);
    }
}
