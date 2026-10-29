<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $t) {
            $t->unsignedInteger('id')->autoIncrement();
            $t->string('nombre', 80);
            $t->string('apellido', 80);
            $t->string('email', 160)->unique();
            $t->string('rut', 12)->nullable()->unique();
            $t->string('password', 255)->nullable();
            $t->string('rol', 20);
            $t->unsignedSmallInteger('region_id')->nullable();
            $t->boolean('activo')->default(true);
            $t->boolean('debe_cambiar_password')->default(true);
            $t->dateTime('ultimo_acceso')->nullable();
            $t->unsignedTinyInteger('intentos_fallidos')->default(0);
            $t->dateTime('bloqueado_hasta')->nullable();
            $t->unsignedInteger('creado_por')->nullable();
            $t->char('firma_nombre_almacenado', 36)->nullable();
            $t->string('firma_mime', 100)->nullable();
            $t->dateTime('firma_actualizada_at')->nullable();
            $t->rememberToken();
            $t->timestamps();
            $t->index(['rol', 'activo']);
            $t->foreign('region_id')->references('id')->on('regiones');
            $t->foreign('creado_por')->references('id')->on('usuarios');
        });

        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->unsignedInteger('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });

        Schema::create('abogados', function (Blueprint $t) {
            $t->unsignedInteger('id')->autoIncrement();
            $t->unsignedInteger('usuario_id')->unique();
            $t->string('area', 20);
            $t->boolean('activo')->default(true);
            $t->foreign('usuario_id')->references('id')->on('usuarios');
        });

        Schema::create('abogado_consultorio', function (Blueprint $t) {
            $t->unsignedInteger('abogado_id');
            $t->unsignedInteger('consultorio_id');
            $t->primary(['abogado_id', 'consultorio_id']);
            $t->foreign('abogado_id')->references('id')->on('abogados');
            $t->foreign('consultorio_id')->references('id')->on('consultorios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abogado_consultorio');
        Schema::dropIfExists('abogados');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('usuarios');
    }
};
