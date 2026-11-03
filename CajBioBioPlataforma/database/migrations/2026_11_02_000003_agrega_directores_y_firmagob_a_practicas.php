<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('practicas', function (Blueprint $t) {
            // Visación y validación por Director Regional / Jefatura UCOE (Fase 3 Regional)
            if (! Schema::hasColumn('practicas', 'validada_director_at')) {
                $t->dateTime('validada_director_at')->nullable()->after('finalizada_at');
            }
            if (! Schema::hasColumn('practicas', 'validada_director_por')) {
                $t->unsignedInteger('validada_director_por')->nullable()->after('validada_director_at');
            }
            if (! Schema::hasColumn('practicas', 'observaciones_director')) {
                $t->text('observaciones_director')->nullable()->after('validada_director_por');
            }

            // Submódulo de Revisión Colegiada en Biobío (Jefatura de UCOE)
            if (! Schema::hasColumn('practicas', 'revisor_colegiado_id')) {
                $t->unsignedInteger('revisor_colegiado_id')->nullable()->after('observaciones_director');
            }
            if (! Schema::hasColumn('practicas', 'revisor_colegiado_nombre')) {
                $t->string('revisor_colegiado_nombre', 80)->nullable()->after('revisor_colegiado_id');
            }

            // Suscripción con FirmaGob por el Director General (Cierre Vinculante Corte Suprema)
            if (! Schema::hasColumn('practicas', 'firmado_director_general_at')) {
                $t->dateTime('firmado_director_general_at')->nullable()->after('despachada_at');
            }
            if (! Schema::hasColumn('practicas', 'firmado_director_general_por')) {
                $t->unsignedInteger('firmado_director_general_por')->nullable()->after('firmado_director_general_at');
            }
            if (! Schema::hasColumn('practicas', 'firmagob_folio')) {
                $t->string('firmagob_folio', 100)->nullable()->after('firmado_director_general_por');
            }
            if (! Schema::hasColumn('practicas', 'firmagob_url')) {
                $t->string('firmagob_url', 255)->nullable()->after('firmagob_folio');
            }
            if (! Schema::hasColumn('practicas', 'certificado_firmagob_id')) {
                $t->unsignedInteger('certificado_firmagob_id')->nullable()->after('firmagob_url');
            }
        });

        // Creación segura de claves foráneas
        try {
            Schema::table('practicas', function (Blueprint $t) {
                $t->foreign('validada_director_por')->references('id')->on('usuarios')->nullOnDelete();
                $t->foreign('revisor_colegiado_id')->references('id')->on('usuarios')->nullOnDelete();
                $t->foreign('firmado_director_general_por')->references('id')->on('usuarios')->nullOnDelete();
                $t->foreign('certificado_firmagob_id')->references('id')->on('documentos')->nullOnDelete();
            });
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        try {
            Schema::table('practicas', function (Blueprint $t) {
                $t->dropForeign(['validada_director_por']);
                $t->dropForeign(['revisor_colegiado_id']);
                $t->dropForeign(['firmado_director_general_por']);
                $t->dropForeign(['certificado_firmagob_id']);
            });
        } catch (\Throwable) {
        }

        Schema::table('practicas', function (Blueprint $t) {
            $cols = array_filter([
                'validada_director_at', 'validada_director_por', 'observaciones_director',
                'revisor_colegiado_id', 'revisor_colegiado_nombre',
                'firmado_director_general_at', 'firmado_director_general_por',
                'firmagob_folio', 'firmagob_url', 'certificado_firmagob_id',
            ], fn ($c) => Schema::hasColumn('practicas', $c));

            if ($cols !== []) {
                $t->dropColumn($cols);
            }
        });
    }
};
