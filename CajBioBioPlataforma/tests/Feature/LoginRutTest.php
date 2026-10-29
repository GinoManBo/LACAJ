<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRutTest extends TestCase
{
    use RefreshDatabase;

    private function cuenta(): Usuario
    {
        return Usuario::create([
            'nombre' => 'Ana', 'apellido' => 'Pérez', 'email' => 'ana@cajbiobio.cl', 'rut' => '10000001-6',
            'rol' => 'administrador', 'password' => 'secreta123', 'debe_cambiar_password' => false, 'activo' => true,
        ]);
    }

    public function test_ingresa_con_rut_con_o_sin_formato(): void
    {
        $this->cuenta();
        foreach (['10.000.001-6', '10000001-6', '100000016'] as $rut) {
            $this->post(route('login'), ['rut' => $rut, 'password' => 'secreta123'])->assertRedirect();
            $this->assertAuthenticated();
            $this->post(route('logout'));
        }
    }

    public function test_el_correo_ya_no_sirve_como_usuario(): void
    {
        $this->cuenta();
        $this->post(route('login'), ['email' => 'ana@cajbiobio.cl', 'password' => 'secreta123'])->assertSessionHasErrors('rut');
        $this->assertGuest();
    }

    public function test_rut_invalido_o_clave_incorrecta_no_ingresa(): void
    {
        $this->cuenta();
        $this->post(route('login'), ['rut' => '10000001-5', 'password' => 'secreta123'])->assertSessionHasErrors('rut');
        $this->post(route('login'), ['rut' => '10000001-6', 'password' => 'mala'])->assertSessionHasErrors(['rut' => 'RUT o contraseña incorrectos.']);
        $this->assertGuest();
    }
}
