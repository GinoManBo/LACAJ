<?php

namespace Tests\Support;

use App\Models\Abogado;
use App\Models\Consultorio;
use App\Models\Postulacion;
use App\Models\Postulante;
use App\Models\Practica;
use App\Models\Practicante;
use App\Models\Usuario;
use Database\Seeders\ReferenciasSeeder;

trait CreaPracticas
{
    private int $seq = 0;

    protected function sembrar(): void
    {
        $this->seed(ReferenciasSeeder::class);
        $this->withoutVite();
        $this->vistasSustitutas(['practicante.inicio', 'tutor.inicio', 'tutor.practicante', 'notificaciones.index']);
    }

    protected function vistasSustitutas(array $nombres): void
    {
        $dir = sys_get_temp_dir().'/caj-vistas-stub';
        foreach ($nombres as $nombre) {
            if (view()->exists($nombre)) {
                continue;
            }
            $ruta = $dir.'/'.str_replace('.', '/', $nombre).'.blade.php';
            @mkdir(dirname($ruta), 0777, true);
            file_put_contents($ruta, 'stub');
        }
        view()->addLocation($dir);
    }

    protected function crearAbogado(): Abogado
    {
        $n = ++$this->seq;
        $u = Usuario::create(['nombre' => 'Tutor', 'apellido' => "N$n", 'email' => "tutor$n@test.cl", 'rol' => 'abogado', 'password' => 'secreto123', 'debe_cambiar_password' => false, 'activo' => true]);

        return Abogado::create(['usuario_id' => $u->id, 'area' => 'civil']);
    }

    /** @return array{0:Practica,1:Usuario} práctica y usuario practicante */
    protected function crearPractica(Abogado $abogado, string $estado = 'activa', string $genero = 'femenino'): array
    {
        $n = ++$this->seq;
        $consultorio = Consultorio::first();
        $post = Postulante::create([
            'rut' => '2011487'.($n % 10).'-'.['6', '6', '6', '6', '6', '6', '6', '6', '6', '6'][$n % 10], 'nombre' => "Ana$n", 'apellido' => 'Pérez Soto',
            'email' => "p$n@test.cl", 'telefono' => '+56911112222', 'genero' => $genero, 'nacionalidad' => 'Chilena',
            'region_id' => $consultorio->region_id, 'comuna_id' => $consultorio->comuna_id, 'direccion' => 'Calle 1',
            'universidad' => 'Universidad de Concepción', 'anio_carrera' => 'quinto',
            'emergencia_nombre' => 'Madre', 'emergencia_vinculo' => 'Madre', 'emergencia_telefono' => '+56911113333',
        ]);
        $usuario = Usuario::create(['nombre' => $post->nombre, 'apellido' => $post->apellido, 'email' => $post->email, 'rol' => 'postulante', 'password' => 'secreto123', 'debe_cambiar_password' => false, 'activo' => true]);
        $practicante = Practicante::create(['postulante_id' => $post->id, 'usuario_id' => $usuario->id]);
        $postulacion = Postulacion::create([
            'postulante_id' => $post->id, 'estado' => 'aceptada', 'region_id' => $consultorio->region_id,
            'fecha_postulacion' => now(), 'fecha_inicio_solicitada' => today(), 'consultorio_asignado_id' => $consultorio->id,
        ]);
        $practica = Practica::create([
            'postulacion_id' => $postulacion->id, 'practicante_id' => $practicante->id, 'consultorio_id' => $consultorio->id,
            'abogado_id' => $abogado->id, 'fecha_inicio' => today()->subMonths(5), 'fecha_termino' => today()->addDays(10), 'estado' => $estado,
        ]);

        return [$practica, $usuario];
    }
}
