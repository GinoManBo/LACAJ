<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('postulantes', function (Blueprint $t) {
            $t->dateTime('datos_actualizados_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('postulantes', function (Blueprint $t) {
            $t->dropColumn('datos_actualizados_at');
        });
    }
};
