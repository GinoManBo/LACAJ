<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('practicas', function (Blueprint $t) {
            if (! Schema::hasColumn('practicas', 'folio_caj')) {
                $t->string('folio_caj', 100)->nullable()->after('estado');
            }
            if (! Schema::hasColumn('practicas', 'folio_carta_presentacion')) {
                $t->string('folio_carta_presentacion', 50)->nullable()->after('carta_presentacion_firmada');
            }
            if (! Schema::hasColumn('practicas', 'resolucion_numero')) {
                $t->string('resolucion_numero', 50)->nullable()->after('nota_practica');
            }
            if (! Schema::hasColumn('practicas', 'resolucion_anio')) {
                $t->unsignedSmallInteger('resolucion_anio')->nullable()->after('resolucion_numero');
            }
            if (! Schema::hasColumn('practicas', 'oficio_daj_numero')) {
                $t->string('oficio_daj_numero', 50)->nullable()->after('resolucion_anio');
            }
            if (! Schema::hasColumn('practicas', 'oficio_daj_fecha')) {
                $t->date('oficio_daj_fecha')->nullable()->after('oficio_daj_numero');
            }
        });
    }

    public function down(): void
    {
        Schema::table('practicas', function (Blueprint $t) {
            $cols = ['folio_caj', 'folio_carta_presentacion', 'resolucion_numero', 'resolucion_anio', 'oficio_daj_numero', 'oficio_daj_fecha'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('practicas', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
