<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaUserAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_auditoria_registrar_captura_user_agent_en_peticion_http(): void
    {
        $usuario = Usuario::create([
            'nombre' => 'Test',
            'apellido' => 'Admin',
            'rut' => '11111111-1',
            'email' => 'admin.test@cajbiobio.cl',
            'password' => bcrypt('123456'),
            'rol' => 'administrador',
            'activo' => true,
        ]);

        $customUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AntigravityTesting/1.0';

        $resp = $this->actingAs($usuario)
            ->withServerVariables(['HTTP_USER_AGENT' => $customUserAgent])
            ->get(route('admin.auditoria.exportar'));

        $resp->assertOk();

        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $usuario->id,
            'accion' => 'auditoria.exportar_excel',
            'entidad' => 'auditoria',
            'user_agent' => $customUserAgent,
        ]);
    }

    public function test_auditoria_index_filtra_por_user_agent(): void
    {
        $usuario = Usuario::create([
            'nombre' => 'Admin',
            'apellido' => 'General',
            'rut' => '22222222-2',
            'email' => 'admin2@cajbiobio.cl',
            'password' => bcrypt('123456'),
            'rol' => 'administrador',
            'activo' => true,
        ]);

        Auditoria::create([
            'usuario_id' => $usuario->id,
            'accion' => 'test.accion',
            'entidad' => 'usuario',
            'entidad_id' => $usuario->id,
            'ip' => '127.0.0.1',
            'user_agent' => 'CustomBrowserDevice/99.0',
        ]);

        $resp = $this->actingAs($usuario)->get(route('admin.auditoria.index', ['q' => 'CustomBrowserDevice']));
        $resp->assertOk();
        $resp->assertSee('CustomBrowserDevice/99.0');
    }
}
