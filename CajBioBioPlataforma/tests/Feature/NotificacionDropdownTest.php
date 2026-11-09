<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificacionDropdownTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): Usuario
    {
        return Usuario::create([
            'nombre' => 'María',
            'apellido' => 'Soto',
            'email' => 'maria@cajbiobio.cl',
            'rut' => '11111111-1',
            'rol' => 'administrador',
            'password' => 'secreta123',
            'debe_cambiar_password' => false,
            'activo' => true,
        ]);
    }

    public function test_dropdown_notificaciones_se_muestra_en_portal(): void
    {
        $u = $this->usuario();

        Notificacion::create([
            'usuario_destino_id' => $u->id,
            'titulo' => 'Postulación Asignada',
            'mensaje' => 'Se te ha asignado un nuevo postulante.',
            'tipo' => 'informativo',
            'leida_at' => null,
        ]);

        Notificacion::create([
            'usuario_destino_id' => $u->id,
            'titulo' => 'Informe Recibido',
            'mensaje' => 'Se ha recibido un informe mensual.',
            'tipo' => 'informativo',
            'leida_at' => now(),
        ]);

        $res = $this->actingAs($u)->get(route('notificaciones.index'));

        $res->assertOk();
        $res->assertSee('id="contenedor-notificaciones"', false);
        $res->assertSee('id="btn-campana"', false);
        $res->assertSee('id="dropdown-notificaciones"', false);
        $res->assertSee('Postulación Asignada');
        $res->assertSee('Informe Recibido');
        $res->assertSee('Ver más');
        $res->assertSee(route('notificaciones.index'));
        $res->assertSee(route('notificaciones.leer-todas'));
    }

    public function test_leer_todas_notificaciones_via_json(): void
    {
        $u = $this->usuario();

        $n1 = Notificacion::create([
            'usuario_destino_id' => $u->id,
            'titulo' => 'Notif 1',
            'mensaje' => 'Mensaje 1',
            'tipo' => 'informativo',
            'leida_at' => null,
        ]);

        $n2 = Notificacion::create([
            'usuario_destino_id' => $u->id,
            'titulo' => 'Notif 2',
            'mensaje' => 'Mensaje 2',
            'tipo' => 'aviso',
            'leida_at' => null,
        ]);

        $this->assertNull($n1->fresh()->leida_at);
        $this->assertNull($n2->fresh()->leida_at);

        $res = $this->actingAs($u)->postJson(route('notificaciones.leer-todas'));

        $res->assertOk();
        $res->assertJson(['ok' => true]);

        $this->assertNotNull($n1->fresh()->leida_at);
        $this->assertNotNull($n2->fresh()->leida_at);
    }

    public function test_leer_todas_notificaciones_via_form(): void
    {
        $u = $this->usuario();

        $n = Notificacion::create([
            'usuario_destino_id' => $u->id,
            'titulo' => 'Notif Form',
            'mensaje' => 'Mensaje Form',
            'tipo' => 'informativo',
            'leida_at' => null,
        ]);

        $res = $this->actingAs($u)->post(route('notificaciones.leer-todas'));

        $res->assertRedirect();
        $this->assertNotNull($n->fresh()->leida_at);
    }
}
