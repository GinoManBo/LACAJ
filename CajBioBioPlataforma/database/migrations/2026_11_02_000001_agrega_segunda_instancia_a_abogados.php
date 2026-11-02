<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('abogados', function (Blueprint $table) {
            if (! Schema::hasColumn('abogados', 'es_segunda_instancia')) {
                $table->boolean('es_segunda_instancia')->default(false)->after('area');
            }
            if (! Schema::hasColumn('abogados', 'region_id')) {
                // regiones.id es unsignedSmallInteger, por lo que region_id debe ser unsignedSmallInteger para compatibilidad de foreign key en MySQL
                $table->unsignedSmallInteger('region_id')->nullable()->after('es_segunda_instancia');
            }
        });

        // Si la columna ya existía como INT UNSIGNED por una ejecución previa fallida en MySQL,
        // ajustamos el tipo a SMALLINT UNSIGNED para que coincida exactamente con regiones.id
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE abogados MODIFY region_id SMALLINT UNSIGNED NULL');
            } catch (\Throwable) {
            }
        }

        try {
            Schema::table('abogados', function (Blueprint $table) {
                $table->foreign('region_id')->references('id')->on('regiones')->nullOnDelete();
            });
        } catch (\Throwable) {
            // Clave foránea ya existente
        }

        Schema::table('practicas', function (Blueprint $table) {
            if (! Schema::hasColumn('practicas', 'carta_presentacion_firmada')) {
                $table->string('carta_presentacion_firmada', 255)->nullable()->after('abogado_id');
            }
            if (! Schema::hasColumn('practicas', 'carta_presentacion_subida_at')) {
                $table->dateTime('carta_presentacion_subida_at')->nullable()->after('carta_presentacion_firmada');
            }
            if (! Schema::hasColumn('practicas', 'abogado_segunda_instancia_id')) {
                $table->unsignedInteger('abogado_segunda_instancia_id')->nullable()->after('abogado_id');
            }
        });

        try {
            Schema::table('practicas', function (Blueprint $table) {
                $table->foreign('abogado_segunda_instancia_id')->references('id')->on('abogados')->nullOnDelete();
            });
        } catch (\Throwable) {
            // Clave foránea ya existente
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('practicas', function (Blueprint $table) {
                $table->dropForeign(['abogado_segunda_instancia_id']);
            });
        } catch (\Throwable) {
        }

        Schema::table('practicas', function (Blueprint $table) {
            $cols = array_filter(
                ['abogado_segunda_instancia_id', 'carta_presentacion_subida_at', 'carta_presentacion_firmada'],
                fn ($c) => Schema::hasColumn('practicas', $c)
            );
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });

        try {
            Schema::table('abogados', function (Blueprint $table) {
                $table->dropForeign(['region_id']);
            });
        } catch (\Throwable) {
        }

        Schema::table('abogados', function (Blueprint $table) {
            $cols = array_filter(
                ['region_id', 'es_segunda_instancia'],
                fn ($c) => Schema::hasColumn('abogados', $c)
            );
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
