<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('abogados', function (Blueprint $table) {
            $table->boolean('es_segunda_instancia')->default(false)->after('area');
            $table->unsignedInteger('region_id')->nullable()->after('es_segunda_instancia');
            $table->foreign('region_id')->references('id')->on('regiones')->nullOnDelete();
        });

        Schema::table('practicas', function (Blueprint $table) {
            $table->string('carta_presentacion_firmada', 255)->nullable()->after('abogado_id');
            $table->dateTime('carta_presentacion_subida_at')->nullable()->after('carta_presentacion_firmada');
            $table->unsignedInteger('abogado_segunda_instancia_id')->nullable()->after('abogado_id');
            $table->foreign('abogado_segunda_instancia_id')->references('id')->on('abogados')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('practicas', function (Blueprint $table) {
            $table->dropForeign(['abogado_segunda_instancia_id']);
            $table->dropColumn(['abogado_segunda_instancia_id', 'carta_presentacion_subida_at', 'carta_presentacion_firmada']);
        });

        Schema::table('abogados', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropColumn(['region_id', 'es_segunda_instancia']);
        });
    }
};
