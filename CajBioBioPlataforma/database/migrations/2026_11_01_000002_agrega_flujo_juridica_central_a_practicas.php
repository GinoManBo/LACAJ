<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('practicas', function (Blueprint $t) {
            // Cierre regional y remisión a Jurídica Biobío (Concepción)
            $t->dateTime('remitida_at')->nullable()->after('finalizada_at');
            $t->unsignedInteger('remitida_por')->nullable()->after('remitida_at');

            // Nivelación de oficio en Jurídica Biobío (Regla >= 5.0 "Buena")
            $t->dateTime('nivelada_at')->nullable()->after('remitida_por');
            $t->unsignedInteger('nivelada_por')->nullable()->after('nivelada_at');
            $t->text('justificacion_nivelacion')->nullable()->after('nivelada_por');

            // Causal disciplinaria fundada de rechazo en Jurídica
            $t->text('causal_rechazo_sancion')->nullable()->after('justificacion_nivelacion');

            // Despacho final por correo y archivo en historial
            $t->dateTime('despachada_at')->nullable()->after('documentos_autorizados_at');
            $t->unsignedInteger('despachada_por')->nullable()->after('despachada_at');

            $t->foreign('remitida_por')->references('id')->on('usuarios');
            $t->foreign('nivelada_por')->references('id')->on('usuarios');
            $t->foreign('despachada_por')->references('id')->on('usuarios');
        });
    }

    public function down(): void
    {
        Schema::table('practicas', function (Blueprint $t) {
            $t->dropForeign(['remitida_por']);
            $t->dropForeign(['nivelada_por']);
            $t->dropForeign(['despachada_por']);
            $t->dropColumn([
                'remitida_at', 'remitida_por',
                'nivelada_at', 'nivelada_por', 'justificacion_nivelacion',
                'causal_rechazo_sancion',
                'despachada_at', 'despachada_por',
            ]);
        });
    }
};
