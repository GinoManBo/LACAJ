<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Unifica el rol 'practicante' en 'postulante' en la tabla de usuarios.
     *
     * EXPLICACIÓN Y ROBUSTEZ:
     * - ¿Se caerá la base de datos si el WHERE no encuentra filas con rol = 'practicante'?
     *   NO. En SQL estándar y en Laravel Query Builder, una sentencia UPDATE con una condición WHERE
     *   que no encuentra coincidencias simplemente afecta 0 registros y concluye de forma totalmente limpia y exitosa.
     *   No produce excepciones, bloqueos ni caídas en el motor de base de datos.
     * - Además, se envuelve en Schema::hasTable('usuarios') y Schema::hasColumn('usuarios', 'rol')
     *   para garantizar máxima resiliencia sin importar el estado del schema al ejecutar 'php artisan migrate'.
     */
    public function up(): void
    {
        if (Schema::hasTable('usuarios') && Schema::hasColumn('usuarios', 'rol')) {
            // =========================================================================
            // UNIFICACIÓN EXPLÍCITA DE ROLES:
            // Se actualizan todos los usuarios con rol histórico 'practicante'
            // al rol unificado 'postulante'.
            //
            // Consulta SQL ejecutada:
            // UPDATE usuarios SET rol = 'postulante' WHERE rol = 'practicante';
            //
            // Si no existen usuarios con rol 'practicante', la sentencia afecta 0 filas
            // de manera segura y controlada.
            // =========================================================================
            DB::table('usuarios')
                ->where('rol', 'practicante')
                ->update(['rol' => 'postulante']);
        }
    }

    /**
     * Reversión de la unificación.
     * Restaura el rol histórico 'practicante' únicamente para aquellos usuarios
     * que posean una ficha activa en la tabla 'practicantes'.
     */
    public function down(): void
    {
        if (Schema::hasTable('usuarios') && Schema::hasTable('practicantes')) {
            // =========================================================================
            // REVERSIÓN EXPLÍCITA:
            // Restablece el rol 'practicante' a los usuarios vinculados en 'practicantes'.
            //
            // Consulta SQL equivalente:
            // UPDATE usuarios SET rol = 'practicante'
            // WHERE id IN (SELECT usuario_id FROM practicantes WHERE usuario_id IS NOT NULL);
            // =========================================================================
            DB::table('usuarios')
                ->whereIn('id', function ($query) {
                    $query->select('usuario_id')
                        ->from('practicantes')
                        ->whereNotNull('usuario_id');
                })
                ->update(['rol' => 'practicante']);
        }
    }
};
