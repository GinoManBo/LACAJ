<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('postulantes', function (Blueprint $t) {
            $t->unsignedInteger('id')->autoIncrement();
            $t->string('rut', 12)->unique();
            $t->string('nombre', 80);
            $t->string('apellido', 80);
            $t->date('fecha_nacimiento')->nullable();
            $t->string('email', 160);
            $t->string('telefono', 25);
            $t->string('genero', 20);
            $t->string('nacionalidad', 60);
            $t->boolean('discapacidad')->default(false);
            $t->text('discapacidad_descripcion')->nullable();
            $t->unsignedSmallInteger('region_id');
            $t->unsignedSmallInteger('comuna_id');
            $t->string('direccion', 200);
            $t->string('universidad', 160);
            $t->string('anio_carrera', 20);
            $t->unsignedSmallInteger('especialidad_id')->nullable();
            $t->text('antecedentes_salud')->nullable();
            $t->string('emergencia_nombre', 140);
            $t->string('emergencia_vinculo', 60);
            $t->string('emergencia_telefono', 25);
            $t->string('emergencia_email', 160)->nullable();
            $t->timestamps();
            $t->index('email');
            $t->index(['apellido', 'nombre']);
            $t->foreign('region_id')->references('id')->on('regiones');
            $t->foreign('comuna_id')->references('id')->on('comunas');
            $t->foreign('especialidad_id')->references('id')->on('especialidades');
        });

        Schema::create('postulaciones', function (Blueprint $t) {
            $t->unsignedInteger('id')->autoIncrement();
            $t->unsignedInteger('postulante_id');
            $t->string('estado', 20)->default('pendiente');
            $t->unsignedSmallInteger('region_id');
            $t->dateTime('fecha_postulacion');
            $t->date('fecha_inicio_solicitada');
            $t->unsignedInteger('revisada_por')->nullable();
            $t->dateTime('resuelta_at')->nullable();
            $t->text('motivo_rechazo')->nullable();
            $t->unsignedInteger('consultorio_asignado_id')->nullable();
            $t->timestamps();
            $t->index(['estado', 'region_id']);
            $t->index(['region_id', 'fecha_postulacion']);
            $t->foreign('postulante_id')->references('id')->on('postulantes');
            $t->foreign('region_id')->references('id')->on('regiones');
            $t->foreign('revisada_por')->references('id')->on('usuarios');
            $t->foreign('consultorio_asignado_id')->references('id')->on('consultorios');
        });

        Schema::create('postulacion_preferencias', function (Blueprint $t) {
            $t->unsignedInteger('id')->autoIncrement();
            $t->unsignedInteger('postulacion_id');
            $t->unsignedInteger('consultorio_id');
            $t->unsignedTinyInteger('orden');
            $t->unique(['postulacion_id', 'orden']);
            $t->unique(['postulacion_id', 'consultorio_id']);
            $t->foreign('postulacion_id')->references('id')->on('postulaciones')->cascadeOnDelete();
            $t->foreign('consultorio_id')->references('id')->on('consultorios');
        });

        Schema::create('postulacion_derivaciones', function (Blueprint $t) {
            $t->unsignedInteger('id')->autoIncrement();
            $t->unsignedInteger('postulacion_id');
            $t->unsignedSmallInteger('region_origen_id');
            $t->unsignedSmallInteger('region_destino_id');
            $t->unsignedInteger('usuario_id');
            $t->text('nota')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->foreign('postulacion_id')->references('id')->on('postulaciones');
            $t->foreign('region_origen_id')->references('id')->on('regiones');
            $t->foreign('region_destino_id')->references('id')->on('regiones');
            $t->foreign('usuario_id')->references('id')->on('usuarios');
        });

        Schema::create('postulacion_notas', function (Blueprint $t) {
            $t->unsignedInteger('id')->autoIncrement();
            $t->unsignedInteger('postulacion_id');
            $t->unsignedInteger('autor_usuario_id');
            $t->text('texto');
            $t->timestamp('created_at')->useCurrent();
            $t->index(['postulacion_id', 'created_at']);
            $t->foreign('postulacion_id')->references('id')->on('postulaciones')->cascadeOnDelete();
            $t->foreign('autor_usuario_id')->references('id')->on('usuarios');
        });

        Schema::create('practicantes', function (Blueprint $t) {
            $t->unsignedInteger('id')->autoIncrement();
            $t->unsignedInteger('postulante_id')->unique();
            $t->unsignedInteger('usuario_id')->nullable()->unique();
            $t->foreign('postulante_id')->references('id')->on('postulantes');
            $t->foreign('usuario_id')->references('id')->on('usuarios');
        });

        Schema::create('practicas', function (Blueprint $t) {
            $t->unsignedInteger('id')->autoIncrement();
            $t->unsignedInteger('postulacion_id')->unique();
            $t->unsignedInteger('practicante_id');
            $t->unsignedInteger('consultorio_id');
            $t->unsignedInteger('abogado_id')->nullable();
            $t->date('fecha_inicio');
            $t->date('fecha_termino');
            $t->string('estado', 20);
            $t->decimal('nota_practica', 2, 1)->nullable();
            $t->dateTime('finalizada_at')->nullable();
            $t->string('cancelacion_motivo', 20)->nullable();
            $t->text('cancelacion_detalle')->nullable();
            $t->date('cancelacion_fecha')->nullable();
            $t->boolean('cancelacion_antes_de_iniciar')->nullable();
            $t->unsignedInteger('cancelada_por')->nullable();
            $t->boolean('documentos_autorizados')->default(false);
            $t->unsignedInteger('documentos_autorizados_por')->nullable();
            $t->dateTime('documentos_autorizados_at')->nullable();
            $t->timestamps();
            $t->index(['estado', 'consultorio_id']);
            $t->index('practicante_id');
            $t->index('fecha_termino');
            $t->index('documentos_autorizados');
            $t->foreign('postulacion_id')->references('id')->on('postulaciones');
            $t->foreign('practicante_id')->references('id')->on('practicantes');
            $t->foreign('consultorio_id')->references('id')->on('consultorios');
            $t->foreign('abogado_id')->references('id')->on('abogados');
            $t->foreign('cancelada_por')->references('id')->on('usuarios');
            $t->foreign('documentos_autorizados_por')->references('id')->on('usuarios');
        });

        Schema::create('practica_traslados', function (Blueprint $t) {
            $t->unsignedInteger('id')->autoIncrement();
            $t->unsignedInteger('practica_id');
            $t->unsignedInteger('consultorio_origen_id');
            $t->unsignedInteger('consultorio_destino_id');
            $t->unsignedInteger('abogado_origen_id')->nullable();
            $t->unsignedInteger('abogado_destino_id')->nullable();
            $t->date('fecha');
            $t->text('motivo')->nullable();
            $t->unsignedInteger('usuario_id');
            $t->timestamp('created_at')->useCurrent();
            $t->foreign('practica_id')->references('id')->on('practicas');
            $t->foreign('consultorio_origen_id')->references('id')->on('consultorios');
            $t->foreign('consultorio_destino_id')->references('id')->on('consultorios');
            $t->foreign('usuario_id')->references('id')->on('usuarios');
        });
    }

    public function down(): void
    {
        foreach (['practica_traslados', 'practicas', 'practicantes', 'postulacion_notas', 'postulacion_derivaciones', 'postulacion_preferencias', 'postulaciones', 'postulantes'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
