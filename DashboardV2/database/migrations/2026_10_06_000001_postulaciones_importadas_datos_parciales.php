<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('postulantes', function (Blueprint $t) {
            $t->string('rut', 12)->nullable()->change();
            $t->string('telefono', 25)->nullable()->change();
            $t->string('genero', 20)->nullable()->change();
            $t->string('nacionalidad', 60)->nullable()->change();
            $t->unsignedSmallInteger('comuna_id')->nullable()->change();
            $t->string('direccion', 200)->nullable()->change();
            $t->string('anio_carrera', 20)->nullable()->change();
            $t->string('emergencia_nombre', 140)->nullable()->change();
            $t->string('emergencia_vinculo', 60)->nullable()->change();
            $t->string('emergencia_telefono', 25)->nullable()->change();
        });

        Schema::table('postulaciones', function (Blueprint $t) {
            $t->date('fecha_inicio_solicitada')->nullable()->change();
            $t->string('origen', 20)->default('formulario')->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('postulaciones', function (Blueprint $t) {
            $t->dropColumn('origen');
        });
    }
};
