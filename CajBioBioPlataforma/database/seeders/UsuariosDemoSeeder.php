<?php

namespace Database\Seeders;

use App\Models\Abogado;
use App\Models\Region;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class UsuariosDemoSeeder extends Seeder
{
    /**
     * Semilla de usuarios de demostración y pruebas operativas.
     *
     * ESTRUCTURA OPERATIVA INSTITUCIONAL:
     * - Sede Central Biobío (Concepción):
     *   - 2 Secretarias Regionales locales para Biobío (BI).
     *   - 1 Secretaria de Jurídica Central (rol: juridica_central), con potestad nacional
     *     para recepción centralizada, nivelación de notas y despacho final de expedientes.
     * - Regiones Externas:
     *   - 1 Secretaria Regional en cada una de las 5 regiones de la jurisdicción:
     *     Ñuble (NB), La Araucanía (AR), Los Ríos (LR), Los Lagos (LL) y Aysén (AI).
     *
     * NOTA DE FLEXIBILIDAD:
     * Esta dotación refleja la estructura operativa inicial sugerida, pero NO representa
     * una regla rígida de negocio: la plataforma permite crear dinámicamente cualquier número
     * de secretarías por región a través del Portal Administrador.
     */
    public function run(): void
    {
        $regiones = Region::whereIn('codigo', ['BI', 'NB', 'AR', 'LR', 'LL', 'AI'])->pluck('id', 'codigo');

        $cuentas = [
            // Administración General
            ['Mauricio', 'Decap Fernández', 'admin@cajbiobio.cl', '10000001-6', 'administrador', null, null],

            // Sede Central Biobío (BI) - Secretarías Regionales
            ['Paulina', 'Herrera Núñez', 'secretaria1@cajbiobio.cl', '10000002-4', 'secretaria', $regiones['BI'] ?? null, null],
            ['Soledad', 'Vergara Pino', 'secretaria2@cajbiobio.cl', '10000003-2', 'secretaria', $regiones['BI'] ?? null, null],

            // Jurídica Central Biobío (Concepción) - Potestad Nacional
            ['Lorena', 'Sanhueza Carrasco', 'juridica@cajbiobio.cl', '10000010-5', 'juridica_central', $regiones['BI'] ?? null, null],

            // Regiones Externas - Secretarías Regionales
            ['Loreto', 'Morales Bastías', 'secretaria_nuble@cajbiobio.cl', '10000011-3', 'secretaria', $regiones['NB'] ?? null, null],
            ['Claudia', 'Baeza Morales', 'secretaria_araucania@cajbiobio.cl', '10000012-1', 'secretaria', $regiones['AR'] ?? null, null],
            ['Marcela', 'Valenzuela Ríos', 'secretaria_losrios@cajbiobio.cl', '10000013-K', 'secretaria', $regiones['LR'] ?? null, null],
            ['Patricia', 'Cárdenas Soto', 'secretaria_loslagos@cajbiobio.cl', '10000014-8', 'secretaria', $regiones['LL'] ?? null, null],
            ['Pamela', 'Fuentes Almonacid', 'secretaria_aysen@cajbiobio.cl', '10000015-6', 'secretaria', $regiones['AI'] ?? null, null],

            // Abogados Tutores y Gobernador Regional
            ['Carlos', 'Muñoz Sepúlveda', 'abogado1@cajbiobio.cl', '10000004-0', 'abogado', null, 'civil'],
            ['Andrea', 'Rojas Fuentes', 'abogado2@cajbiobio.cl', '10000005-9', 'abogado', null, 'penal'],
            ['Rodrigo', 'Díaz Wörner', 'gobernador@cajbiobio.cl', '10000006-7', 'gobernador', null, null],
        ];

        foreach ($cuentas as [$nombre, $apellido, $email, $rut, $rol, $regionId, $area]) {
            $u = Usuario::updateOrCreate(['email' => $email], [
                'nombre' => $nombre, 'apellido' => $apellido, 'rut' => $rut, 'rol' => $rol,
                'region_id' => $regionId, 'password' => '123456',
                'debe_cambiar_password' => false, 'activo' => true,
                'intentos_fallidos' => 0, 'bloqueado_hasta' => null,
            ]);
            if ($area) {
                Abogado::updateOrCreate(['usuario_id' => $u->id], ['area' => $area]);
            }
        }
    }
}
