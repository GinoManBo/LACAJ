<?php

namespace Database\Seeders;

use App\Models\Abogado;
use App\Models\Region;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class UsuariosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $biobio = Region::where('codigo', 'BI')->value('id');

        $cuentas = [
            ['Mauricio', 'Decap Fernández', 'admin@cajbiobio.cl', '10000001-6', 'administrador', null, null],
            ['Paulina', 'Herrera Núñez', 'secretaria1@cajbiobio.cl', '10000002-4', 'secretaria', $biobio, null],
            ['Soledad', 'Vergara Pino', 'secretaria2@cajbiobio.cl', '10000003-2', 'secretaria', $biobio, null],
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
