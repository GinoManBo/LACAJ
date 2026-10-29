<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $t) {
            $t->string('email', 160)->nullable()->change();
        });

        Schema::create('postulacion_borradores', function (Blueprint $t) {
            $t->unsignedInteger('id')->autoIncrement();
            $t->unsignedInteger('usuario_id')->unique();
            $t->json('datos');
            $t->timestamps();
            $t->foreign('usuario_id')->references('id')->on('usuarios')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postulacion_borradores');
    }
};
