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
            ->assertSessionHasErrors(['nombre', 'apellido', 'rut', 'email', 'region_id', 'direccion', 'emergencia_nombre', 'antecedentes_salud']);
    }
}
