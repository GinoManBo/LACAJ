<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('regiones', function (Blueprint $t) {
            $t->unsignedSmallInteger('id')->autoIncrement();
            $t->string('codigo', 5)->unique();
            $t->string('nombre', 80)->unique();
            $t->tinyInteger('orden');
            $t->boolean('operativa')->default(false);
        });

        Schema::create('comunas', function (Blueprint $t) {
            $t->unsignedSmallInteger('id')->autoIncrement();
            $t->unsignedSmallInteger('region_id');
            $t->string('nombre', 80);
            $t->decimal('lat', 8, 5)->nullable();
            $t->decimal('lng', 8, 5)->nullable();
            $t->unique(['region_id', 'nombre']);
            $t->foreign('region_id')->references('id')->on('regiones');
        });

        Schema::create('especialidades', function (Blueprint $t) {
            $t->unsignedSmallInteger('id')->autoIncrement();
            $t->string('nombre', 120)->unique();
            $t->boolean('activo')->default(true);
            $t->smallInteger('orden')->default(0);
        });

        Schema::create('consultorios', function (Blueprint $t) {
            $t->unsignedInteger('id')->autoIncrement();
            $t->string('nombre', 140)->unique();
            $t->string('direccion', 200);
            $t->unsignedSmallInteger('comuna_id');
            $t->unsignedSmallInteger('region_id');
            $t->unsignedSmallInteger('capacidad')->default(0);
            $t->char('color', 7)->default('#0b3d91');
            $t->boolean('acepta_postulaciones')->default(true);
            $t->boolean('activo')->default(true);
            $t->timestamps();
            $t->index('region_id');
            $t->foreign('comuna_id')->references('id')->on('comunas');
            $t->foreign('region_id')->references('id')->on('regiones');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultorios');
        Schema::dropIfExists('especialidades');
        Schema::dropIfExists('comunas');
        Schema::dropIfExists('regiones');
    }
};
