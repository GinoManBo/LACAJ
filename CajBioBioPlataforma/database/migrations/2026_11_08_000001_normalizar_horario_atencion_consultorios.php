<?php

use App\Models\Consultorio;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('consultorios') || ! Schema::hasColumn('consultorios', 'horario_atencion')) {
            return;
        }

        Consultorio::query()
            ->whereNotNull('horario_atencion')
            ->each(function (Consultorio $c) {
                $raw = $c->getRawOriginal('horario_atencion');
                if ($raw && (str_contains($raw, '\n') || str_contains($raw, '\r') || preg_match('/[ \t]{5,}/', $raw))) {
                    $c->horario_atencion = $raw;
                    $c->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        // No destructivo
    }
};
