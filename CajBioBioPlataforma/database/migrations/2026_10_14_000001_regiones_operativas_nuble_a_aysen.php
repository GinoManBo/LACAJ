<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OPERATIVAS = ['NB', 'BI', 'AR', 'LR', 'LL', 'AI'];

    public function up(): void
    {
        DB::table('regiones')->update(['operativa' => false]);
        DB::table('regiones')->whereIn('codigo', self::OPERATIVAS)->update(['operativa' => true]);

        $fuera = DB::table('regiones')->where('operativa', false)->pluck('id');
        DB::table('consultorios')->whereIn('region_id', $fuera)->update(['activo' => false]);
    }

    public function down(): void {}
};
