<?php

namespace Tests\Concerns;

use App\Models\Abogado;
use App\Models\Comuna;
use App\Models\Consultorio;
use App\Models\Evaluacion;
use App\Models\Postulacion;
use App\Models\Postulante;
use App\Models\Practica;
use App\Models\Practicante;
use App\Models\Region;
use App\Models\Usuario;
use Database\Seeders\ReferenciasSeeder;

trait DatosPracticaB
{
    protected function sembrar(): void
    {
        $this->seed(ReferenciasSeeder::class);
        $this->withoutVite();
    }

    protected function usuario(string $rol, array $extra = []): Usuario
    {
        static $n = 0;
        $n++;

        return Usuario::create($extra + [
            'nombre' => 'Test', 'apellido' => ucfirst($rol), 'email' => "{$rol}{$n}@test.cl", 'rol' => $rol,
            'password' => 'secreto123', 'debe_cambiar_password' => false, 'activo' => true,
            'region_id' => $rol === 'secretaria' ? Region::where('codigo', 'BI')->value('id') : null,
        ]);
    }

    protected function practica(?Consultorio $consultorio = null, string $estado = 'activa', ?Abogado $abogado = null): Practica
    {
        static $n = 0;
        $n++;
        $consultorio ??= Consultorio::orderBy('id')->first();
        $region = Region::first();
        $comuna = Comuna::first();

        $pt = Postulante::create([
            'rut' => $this->rutDe(10000000 + $n), 'nombre' => "Prac{$n}", 'apellido' => 'Prueba',
            'email' => "prac{$n}@mail.cl", 'telefono' => '+56911112222', 'genero' => 'femenino', 'nacionalidad' => 'Chilena',
            'region_id' => $region->id, 'comuna_id' => $comuna->id, 'direccion' => 'Calle 1', 'universidad' => 'U Test',
            'anio_carrera' => 'quinto', 'emergencia_nombre' => 'X', 'emergencia_vinculo' => 'Madre', 'emergencia_telefono' => '+56933334444',
        ]);
        $post = Postulacion::create([
            'postulante_id' => $pt->id, 'estado' => 'aceptada', 'region_id' => $consultorio->region_id,
            'fecha_postulacion' => now(), 'fecha_inicio_solicitada' => now()->toDateString(),
            'consultorio_asignado_id' => $consultorio->id,
        ]);
        $user = $this->usuario('practicante');
        $prac = Practicante::create(['postulante_id' => $pt->id, 'usuario_id' => $user->id]);

        return Practica::create([
            'postulacion_id' => $post->id, 'practicante_id' => $prac->id, 'consultorio_id' => $consultorio->id,
            'abogado_id' => $abogado?->id, 'fecha_inicio' => now()->subMonth()->toDateString(),
            'fecha_termino' => now()->addMonths(5)->toDateString(), 'estado' => $estado,
        ]);
    }

    protected function evaluar(Practica $p, bool $aprobado = true): Evaluacion
    {
        $abogado = Abogado::first() ?? Abogado::create(['usuario_id' => $this->usuario('abogado')->id, 'area' => 'civil']);
        $nota = $aprobado ? 6.0 : 3.0;

        return Evaluacion::create([
            'practica_id' => $p->id, 'abogado_id' => $abogado->id, 'conocimiento_juridico' => $nota, 'responsabilidad' => $nota,
            'iniciativa' => $nota, 'sentido_social' => $nota, 'conducta' => $nota, 'honorabilidad' => $nota,
            'asistencia_puntualidad' => $nota, 'promedio' => $nota, 'aprobado' => $aprobado,
        ]);
    }

    protected function rutDe(int $cuerpo): string
    {
        $suma = 0;
        $f = 2;
        foreach (array_reverse(str_split((string) $cuerpo)) as $d) {
            $suma += (int) $d * $f;
            $f = $f === 7 ? 2 : $f + 1;
        }
        $r = 11 - ($suma % 11);

        return $cuerpo.'-'.($r === 11 ? '0' : ($r === 10 ? 'K' : $r));
    }
}
