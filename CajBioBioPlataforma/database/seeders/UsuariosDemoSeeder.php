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

            // Abogados Jefes de 1ª Instancia (Consultorios)
            ['Carlos', 'Muñoz Sepúlveda', 'abogado1@cajbiobio.cl', '10000004-0', 'abogado', null, 'civil', false],
            ['Andrea', 'Rojas Fuentes', 'abogado2@cajbiobio.cl', '10000005-9', 'abogado', null, 'penal', false],

            // Abogado Jefe de 2ª Instancia (Corte de Apelaciones Concepción - Región del Biobío)
            // Estructura flexible: cada región puede contar con uno o más abogados jefes de 2ª instancia
            ['Mauricio', 'Vergara Toledo', 'corte_biobio@cajbiobio.cl', '10000016-4', 'abogado', $regiones['BI'] ?? null, 'corte', true],

            // Gobernador Regional
            ['Rodrigo', 'Díaz Wörner', 'gobernador@cajbiobio.cl', '10000006-7', 'gobernador', null, null, false],

            // Dirección General (Sede Central Concepción - Potestad Nacional y FirmaGob)
            ['Mauricio', 'Decap Fernández', 'director_general@cajbiobio.cl', '10000020-2', 'director_general', null, null, false],

            // Jefatura de la Unidad de Consultorios y Oficinas Especializadas (UCOE) (Región del Biobío - don Luis Núñez Oviedo)
            ['Luis', 'Núñez Oviedo', 'ucoe_biobio@cajbiobio.cl', '10000021-0', 'jefe_ucoe', $regiones['BI'] ?? null, null, false],

            // Revisores Colegiados de UCOE (Octava Región)
            ['Verónica', 'Cifuentes Matus', 'revisora_veronica@cajbiobio.cl', '10000027-8', 'jefe_ucoe', $regiones['BI'] ?? null, null, false],
            ['Graciela', 'Paredes Saavedra', 'revisora_graciela@cajbiobio.cl', '10000028-6', 'jefe_ucoe', $regiones['BI'] ?? null, null, false],

            // Directores Regionales (Regiones Externas)
            ['Ricardo', 'Salazar Valenzuela', 'director_nuble@cajbiobio.cl', '10000022-9', 'director_regional', $regiones['NB'] ?? null, null, false],
            ['Alejandro', 'Viñuela Hiriart', 'director_araucania@cajbiobio.cl', '10000023-7', 'director_regional', $regiones['AR'] ?? null, null, false],
            ['María Cristina', 'Valdés Silva', 'director_losrios@cajbiobio.cl', '10000024-5', 'director_regional', $regiones['LR'] ?? null, null, false],
            ['Consuelo', 'Bellido Almonacid', 'director_loslagos@cajbiobio.cl', '10000025-3', 'director_regional', $regiones['LL'] ?? null, null, false],
            ['Rodrigo', 'Morales Henríquez', 'director_aysen@cajbiobio.cl', '10000026-1', 'director_regional', $regiones['AI'] ?? null, null, false],
        ];

        foreach ($cuentas as $fila) {
            [$nombre, $apellido, $email, $rut, $rol, $regionId, $area] = array_slice($fila, 0, 7);
            $esSegundaInstancia = $fila[7] ?? false;

            $u = Usuario::updateOrCreate(['email' => $email], [
                'nombre' => $nombre, 'apellido' => $apellido, 'rut' => $rut, 'rol' => $rol,
                'region_id' => $regionId, 'password' => '123456',
                'debe_cambiar_password' => false, 'activo' => true,
                'intentos_fallidos' => 0, 'bloqueado_hasta' => null,
            ]);
            if ($area) {
                Abogado::updateOrCreate(['usuario_id' => $u->id], [
                    'area' => $area,
                    'es_segunda_instancia' => (bool) $esSegundaInstancia,
                    'region_id' => $esSegundaInstancia ? $regionId : null,
                ]);
            }
        }
    }
}
