<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultorios', function (Blueprint $table) {
            if (! Schema::hasColumn('consultorios', 'tipo_unidad')) {
                $table->string('tipo_unidad', 50)->nullable()->after('nombre');
            }
            if (! Schema::hasColumn('consultorios', 'telefono')) {
                $table->string('telefono', 255)->nullable()->after('direccion');
            }
            if (! Schema::hasColumn('consultorios', 'horario_atencion')) {
                $table->text('horario_atencion')->nullable()->after('telefono');
            }
            if (! Schema::hasColumn('consultorios', 'email')) {
                $table->string('email', 150)->nullable()->after('horario_atencion');
            }
        });

        // Ajuste de índice único: se flexibiliza el nombre para que pueda repetirse en comunas distintas
        // (por ejemplo: Oficina de Segunda Instancia en Chillán, Concepción, Temuco, etc.)
        try {
            Schema::table('consultorios', function (Blueprint $table) {
                $table->dropUnique('consultorios_nombre_unique');
            });
        } catch (\Throwable) {
            // Si el índice no existe o el motor no lo requiere, continúa
        }

        try {
            Schema::table('consultorios', function (Blueprint $table) {
                $table->unique(['nombre', 'comuna_id'], 'consultorios_nombre_comuna_unique');
            });
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        Schema::table('consultorios', function (Blueprint $table) {
            $cols = [];
            foreach (['tipo_unidad', 'telefono', 'horario_atencion', 'email'] as $col) {
                if (Schema::hasColumn('consultorios', $col)) {
                    $cols[] = $col;
                }
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
