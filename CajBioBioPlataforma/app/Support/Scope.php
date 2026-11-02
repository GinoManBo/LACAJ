<?php

namespace App\Support;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;

final class Scope
{
    public static function regionId(Usuario $u): ?int
    {
        if ($u->rol === 'administrador') {
            return null;
        }
        if ($u->rol === 'secretaria' && $u->region_id) {
            return $u->region_id;
        }
        abort(403, 'Sin región asignada.');
    }

    public static function porRegion(Builder $q, Usuario $u, string $columna = 'region_id'): Builder
    {
        $region = self::regionId($u);

        return $region === null ? $q : $q->where($columna, $region);
    }

    public static function abogadoId(Usuario $u): int
    {
        $id = $u->rol === 'abogado' ? $u->abogado?->id : null;
        abort_unless($id, 403);

        return $id;
    }

    public static function practicanteId(Usuario $u): int
    {
        // =========================================================================
        // UNIFICACIÓN DE ROLES:
        // Soporta tanto el rol unificado 'postulante' como el rol histórico 'practicante'.
        // Obtiene el identificador de la ficha 'practicantes' vinculada al usuario.
        // =========================================================================
        $id = in_array($u->rol, ['practicante', 'postulante'], true) ? $u->practicante?->id : null;
        abort_unless($id, 403);

        return $id;
    }
}
