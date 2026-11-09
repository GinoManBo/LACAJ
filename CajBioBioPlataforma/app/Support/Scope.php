<?php

namespace App\Support;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;

final class Scope
{
    public static function regionId(Usuario $u): ?int
    {
        // Administrador y Director General tienen alcance nacional/central irrestricto
        if ($u->rol === 'administrador' || $u->rol === 'director_general') {
            return null;
        }
        // Jurídica Central opera con base en la Sede Central (Biobío - Concepción)
        if ($u->rol === 'juridica_central') {
            return $u->region_id ?? \App\Models\Region::where('codigo', 'BI')->value('id');
        }
        if (in_array($u->rol, ['secretaria', 'director_regional', 'jefe_ucoe'], true) && $u->region_id) {
            return $u->region_id;
        }
        abort(403, 'Sin región asignada.');
    }

    public static function esBiobioCentral(Usuario $u): bool
    {
        if ($u->rol === 'juridica_central' || $u->rol === 'administrador') {
            return true;
        }

        if ($u->rol === 'secretaria') {
            if ($u->region && $u->region->codigo === 'BI') {
                return true;
            }
            if ($u->region_id) {
                return \App\Models\Region::where('id', $u->region_id)->value('codigo') === 'BI';
            }
        }

        return false;
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
