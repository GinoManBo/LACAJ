<?php

namespace Tests\Feature;

use Database\Seeders\ReferenciasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostulacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ReferenciasSeeder::class);
    }

    public function test_no_se_puede_saltar_pasos(): void
    {
        $this->get(route('postulacion.paso', 3))->assertRedirect(route('postulacion.paso', 1));
        $this->post(route('postulacion.enviar'))->assertRedirect(route('postulacion.paso', 1));
    }

    public function test_paso1_exige_aceptar_terminos(): void
    {
        $this->post(route('postulacion.guardar', 1), [])->assertSessionHasErrors('acepta');
        $this->post(route('postulacion.guardar', 1), ['acepta' => '1'])->assertRedirect(route('postulacion.paso', 2));
        $this->get(route('postulacion.paso', 3))->assertRedirect(route('postulacion.paso', 2));
    }

    public function test_paso2_valida_obligatorios_y_rut(): void
    {
        $this->post(route('postulacion.guardar', 1), ['acepta' => '1']);
        $this->post(route('postulacion.guardar', 2), ['rut' => '11111111-2'])
            ->assertSessionHasErrors(['nombre', 'apellido', 'rut', 'email', 'password', 'region_id', 'direccion', 'emergencia_nombre', 'antecedentes_salud']);
    }

    public function test_paso3_exige_certificado_egreso_obligatorio_y_no_pide_anio_carrera(): void
    {
        $this->post(route('postulacion.guardar', 1), ['acepta' => '1']);
        $region = \App\Models\Region::where('codigo', 'BI')->first();
        $this->post(route('postulacion.guardar', 2), [
            'nombre' => 'Ana', 'apellido' => 'Rojas', 'rut' => '20.000.001-3', 'fecha_nacimiento' => '2001-01-01',
            'email' => 'ana@mail.cl', 'telefono' => '+56911112222', 'genero' => 'femenino', 'nacionalidad' => 'Chilena',
            'password' => 'clave1234', 'password_confirmation' => 'clave1234',
            'discapacidad' => '0', 'region_id' => $region->id,
            'comuna_id' => \App\Models\Comuna::where('region_id', $region->id)->value('id'),
            'direccion' => 'Calle 1', 'emergencia_nombre' => 'Madre', 'emergencia_vinculo' => 'Madre',
            'emergencia_telefono' => '+56933334444', 'antecedentes_salud' => 'Ninguno',
        ]);

        $espId = \App\Models\Especialidad::value('id');
        $img = \Illuminate\Http\UploadedFile::fake()->image('cedula.jpg', 600, 400);

        // Sin certificado de egreso falla
        $this->post(route('postulacion.guardar', 3), [
            'universidad' => 'Universidad de Concepción',
            'especialidad_id' => $espId,
            'cedula_frontal' => $img,
            'cedula_reverso' => $img,
            'certificado_egreso' => $img,
        ])->assertSessionHasErrors('certificado_egreso');

        // Con certificado de egreso (PDF) y sin anio_carrera avanza con éxito a paso 4
        $pdf = \Illuminate\Http\UploadedFile::fake()->create('certificado_egreso.pdf', 500, 'application/pdf');
        $this->post(route('postulacion.guardar', 3), [
            'universidad' => 'Universidad de Concepción',
            'especialidad_id' => $espId,
            'cedula_frontal' => $img,
            'cedula_reverso' => $img,
            'certificado_egreso' => $pdf,
        ])->assertRedirect(route('postulacion.paso', 4))->assertSessionDoesntHaveErrors(['anio_carrera', 'certificado_egreso']);
    }

    public function test_paso3_acepta_cedula_en_formato_pdf(): void
    {
        $this->post(route('postulacion.guardar', 1), ['acepta' => '1']);
        $region = \App\Models\Region::where('codigo', 'BI')->first();
        $this->post(route('postulacion.guardar', 2), [
            'nombre' => 'Carlos', 'apellido' => 'Pérez', 'rut' => '20.000.002-1', 'fecha_nacimiento' => '2000-05-15',
            'email' => 'carlos@mail.cl', 'telefono' => '+56922223333', 'genero' => 'masculino', 'nacionalidad' => 'Chilena',
            'password' => 'clave1234', 'password_confirmation' => 'clave1234',
            'discapacidad' => '0', 'region_id' => $region->id,
            'comuna_id' => \App\Models\Comuna::where('region_id', $region->id)->value('id'),
            'direccion' => 'Calle 2', 'emergencia_nombre' => 'Padre', 'emergencia_vinculo' => 'Padre',
            'emergencia_telefono' => '+56944445555', 'antecedentes_salud' => 'Ninguno',
        ]);

        $espId = \App\Models\Especialidad::value('id');
        $cedulaFrentePdf = \Illuminate\Http\UploadedFile::fake()->create('cedula_frente.pdf', 800, 'application/pdf');
        $cedulaDorsoPdf = \Illuminate\Http\UploadedFile::fake()->create('cedula_dorso.pdf', 800, 'application/pdf');
        $egresoPdf = \Illuminate\Http\UploadedFile::fake()->create('certificado_egreso.pdf', 600, 'application/pdf');

        $this->post(route('postulacion.guardar', 3), [
            'universidad' => 'Universidad de Concepción',
            'especialidad_id' => $espId,
            'cedula_frontal' => $cedulaFrentePdf,
            'cedula_reverso' => $cedulaDorsoPdf,
            'certificado_egreso' => $egresoPdf,
        ])->assertRedirect(route('postulacion.paso', 4))
            ->assertSessionDoesntHaveErrors(['cedula_frontal', 'cedula_reverso', 'certificado_egreso']);
    }

    public function test_enviar_postulacion_crea_usuario_inicia_sesion_y_redirige_a_mi_postulacion(): void
    {
        $this->post(route('postulacion.guardar', 1), ['acepta' => '1']);
        $region = \App\Models\Region::where('codigo', 'BI')->first();
        $this->post(route('postulacion.guardar', 2), [
            'nombre' => 'Esteban', 'apellido' => 'Díaz', 'rut' => '20.000.003-k', 'fecha_nacimiento' => '1999-08-20',
            'email' => 'esteban@mail.cl', 'telefono' => '+56977778888', 'genero' => 'masculino', 'nacionalidad' => 'Chilena',
            'password' => 'miPasswordSegura123', 'password_confirmation' => 'miPasswordSegura123',
            'discapacidad' => '0', 'region_id' => $region->id,
            'comuna_id' => \App\Models\Comuna::where('region_id', $region->id)->value('id'),
            'direccion' => 'Avenida Principal 123', 'emergencia_nombre' => 'Hermano', 'emergencia_vinculo' => 'Hermano',
            'emergencia_telefono' => '+56911223344', 'antecedentes_salud' => 'Ninguno',
        ]);

        $espId = \App\Models\Especialidad::value('id');
        $cedulaFrente = \Illuminate\Http\UploadedFile::fake()->create('cedula_frente.pdf', 500, 'application/pdf');
        $cedulaDorso = \Illuminate\Http\UploadedFile::fake()->create('cedula_dorso.pdf', 500, 'application/pdf');
        $egreso = \Illuminate\Http\UploadedFile::fake()->create('egreso.pdf', 500, 'application/pdf');

        $this->post(route('postulacion.guardar', 3), [
            'universidad' => 'Universidad de Concepción',
            'especialidad_id' => $espId,
            'cedula_frontal' => $cedulaFrente,
            'cedula_reverso' => $cedulaDorso,
            'certificado_egreso' => $egreso,
        ])->assertRedirect(route('postulacion.paso', 4));

        $consId = \App\Models\Consultorio::where('region_id', $region->id)->value('id');
        $this->post(route('postulacion.guardar', 4), [
            'consultorios' => [$consId],
            'fecha_inicio' => now()->addDays(10)->toDateString(),
        ])->assertRedirect(route('postulacion.paso', 5));

        $resp = $this->post(route('postulacion.enviar'));
        $resp->assertRedirect(route('postulante.inicio'))
            ->assertSessionHas('ok', 'Tu postulación fue recibida exitosamente y se encuentra en revisión.');

        $this->assertTrue(auth()->check());
        $user = auth()->user();
        $this->assertSame('postulante', $user->rol);
        $this->assertSame('20000003-k', $user->rut);
        $this->assertFalse((bool) $user->debe_cambiar_password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('miPasswordSegura123', $user->password));
    }
}
