<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evaluaciones', function (Blueprint $t) {
            $t->unsignedInteger('id')->autoIncrement();
            $t->unsignedInteger('practica_id')->unique();
            $t->unsignedInteger('abogado_id');
            foreach (['conocimiento_juridico', 'responsabilidad', 'iniciativa', 'sentido_social', 'conducta', 'honorabilidad', 'asistencia_puntualidad'] as $c) {
                $t->decimal($c, 2, 1);
            }
            $t->decimal('promedio', 3, 2);
            $t->boolean('aprobado');
            $t->text('observaciones')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->foreign('practica_id')->references('id')->on('practicas');
            $t->foreign('abogado_id')->references('id')->on('abogados');
        });

        Schema::create('calificaciones', function (Blueprint $t) {
            $t->unsignedInteger('id')->autoIncrement();
            $t->unsignedInteger('practica_id');
            $t->unsignedTinyInteger('instancia');
            $t->decimal('nota', 2, 1);
            $t->date('fecha');
            $t->unsignedInteger('registrada_por');
            $t->text('observaciones')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['practica_id', 'instancia']);
            $t->foreign('practica_id')->references('id')->on('practicas');
            $t->foreign('registrada_por')->references('id')->on('usuarios');
        });

        Schema::create('documentos', function (Blueprint $t) {
            $t->unsignedInteger('id')->autoIncrement();
            $t->string('tipo', 30);
            $t->unsignedInteger('postulacion_id')->nullable();
            $t->unsignedInteger('practica_id')->nullable();
            $t->string('nombre_original', 255);
            $t->char('nombre_almacenado', 36)->unique();
            $t->string('mime', 100);
            $t->unsignedInteger('tamano_bytes');
            $t->char('sha256', 64);
            $t->unsignedInteger('subido_por')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['postulacion_id', 'tipo']);
            $t->index(['practica_id', 'tipo']);
            $t->foreign('postulacion_id')->references('id')->on('postulaciones');
            $t->foreign('practica_id')->references('id')->on('practicas');
        });

        Schema::create('archivos_staging', function (Blueprint $t) {
            $t->char('id', 36)->primary();
            $t->string('tipo', 30);
            $t->string('nombre_original', 255);
            $t->char('nombre_almacenado', 36);
            $t->string('mime', 100);
            $t->unsignedInteger('tamano_bytes');
            $t->char('sha256', 64);
            $t->timestamp('created_at')->useCurrent();
            $t->dateTime('expires_at');
            $t->dateTime('consumed_at')->nullable();
        });

        Schema::create('certificados', function (Blueprint $t) {
            $t->unsignedInteger('id')->autoIncrement();
            $t->unsignedInteger('practica_id');
            $t->string('tipo', 30);
            $t->unsignedSmallInteger('numero_resolucion')->nullable();
            $t->unsignedSmallInteger('anio')->nullable();
            $t->date('fecha_emision');
            $t->unsignedInteger('documento_id')->nullable()->unique();
            $t->unsignedInteger('emitido_por')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['practica_id', 'tipo']);
            $t->unique(['tipo', 'anio', 'numero_resolucion']);
            $t->foreign('practica_id')->references('id')->on('practicas');
            $t->foreign('documento_id')->references('id')->on('documentos');
            $t->foreign('emitido_por')->references('id')->on('usuarios');
        });

        Schema::create('resolucion_secuencia', function (Blueprint $t) {
            $t->unsignedSmallInteger('anio')->primary();
            $t->unsignedSmallInteger('ultimo_numero')->default(0);
        });

        Schema::create('notificaciones', function (Blueprint $t) {
            $t->unsignedInteger('id')->autoIncrement();
            $t->unsignedInteger('usuario_destino_id');
            $t->string('tipo', 20);
            $t->string('titulo', 160);
            $t->text('mensaje');
            $t->string('entidad_tipo', 40)->nullable();
            $t->unsignedInteger('entidad_id')->nullable();
            $t->unsignedInteger('creada_por')->nullable();
            $t->dateTime('leida_at')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['usuario_destino_id', 'leida_at']);
            $t->foreign('usuario_destino_id')->references('id')->on('usuarios');
            $t->foreign('creada_por')->references('id')->on('usuarios');
        });

        Schema::create('auditoria', function (Blueprint $t) {
            $t->unsignedBigInteger('id')->autoIncrement();
            $t->unsignedInteger('usuario_id')->nullable();
            $t->string('accion', 60);
            $t->string('entidad', 40);
            $t->unsignedInteger('entidad_id')->nullable();
            $t->json('datos')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['entidad', 'entidad_id']);
            $t->index(['usuario_id', 'created_at']);
            $t->foreign('usuario_id')->references('id')->on('usuarios');
        });
    }

    public function down(): void
    {
        foreach (['auditoria', 'notificaciones', 'resolucion_secuencia', 'certificados', 'archivos_staging', 'documentos', 'calificaciones', 'evaluaciones'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
