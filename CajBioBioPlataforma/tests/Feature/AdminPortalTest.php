<?php

namespace Tests\Feature;

use App\Models\Consultorio;
use App\Models\Region;
use App\Models\Usuario;
use App\Support\Rut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\DatosPracticaB;
use Tests\TestCase;

class AdminPortalTest extends TestCase
{
    use DatosPracticaB, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_solo_administrador_accede(): void
    {
        $this->get('/admin/usuarios')->assertRedirect(route('login'));
        foreach (['secretaria', 'abogado', 'practicante', 'gobernador'] as $rol) {
            $this->actingAs($this->usuario($rol))->get('/admin/usuarios')->assertForbidden();
            $this->actingAs($this->usuario($rol))->post('/admin/capacidad/1', ['accion' => 'mas'])->assertForbidden();
        }
    }

    public function test_capacidad_no_puede_ser_menor_a_ocupados(): void
    {
        $sede = Consultorio::orderBy('id')->first();
        $sede->update(['capacidad' => 2]);
        $this->practica($sede);
        $this->practica($sede, 'por_iniciar');
        $this->practica($sede, 'cancelada');

        $admin = $this->usuario('administrador');
        $this->actingAs($admin)->post(route('admin.capacidad.ajustar', $sede), ['accion' => 'menos'])
            ->assertSessionHasErrors('capacidad');
        $this->assertSame(2, $sede->refresh()->capacidad);

        $this->actingAs($admin)->post(route('admin.capacidad.ajustar', $sede), ['accion' => 'mas'])->assertSessionHasNoErrors();
        $this->assertSame(3, $sede->refresh()->capacidad);

        $this->actingAs($admin)->post(route('admin.capacidad.ajustar', $sede), ['capacidad' => 1])->assertSessionHasErrors('capacidad');
    }

    public function test_desactivar_no_borra_e_invalida_sesiones(): void
    {
        $admin = $this->usuario('administrador');
        $sec = $this->usuario('secretaria');
        DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $sec->id, 'payload' => 'x', 'last_activity' => time()]);

        $this->actingAs($admin)->post(route('admin.usuarios.desactivar', $sec))->assertSessionHasNoErrors();

        $this->assertFalse($sec->refresh()->activo);
        $this->assertDatabaseHas('usuarios', ['id' => $sec->id]);
        $this->assertDatabaseMissing('sessions', ['id' => 'abc']);
        $this->assertDatabaseHas('auditoria', ['accion' => 'usuario.desactivar', 'entidad_id' => $sec->id]);

        $this->actingAs($admin)->post(route('admin.usuarios.activar', $sec));
        $this->assertTrue($sec->refresh()->activo);
    }

    public function test_admin_no_puede_desactivarse_a_si_mismo(): void
    {
        $admin = $this->usuario('administrador');
        $this->actingAs($admin)->post(route('admin.usuarios.desactivar', $admin))->assertSessionHasErrors('activo');
        $this->assertTrue($admin->refresh()->activo);
    }

    public function test_crear_secretaria_exige_region_y_abogado_exige_area(): void
    {
        $admin = $this->usuario('administrador');
        $base = ['nombre' => 'Ana', 'apellido' => 'Pérez', 'email' => 'ana@cajbiobio.cl', 'rut' => '10000001-6'];

        $this->actingAs($admin)->post(route('admin.usuarios.guardar'), $base + ['rol' => 'secretaria'])->assertSessionHasErrors('region_id');
        $this->actingAs($admin)->post(route('admin.usuarios.guardar'), $base + ['rol' => 'abogado'])->assertSessionHasErrors('area');
        $this->assertDatabaseMissing('usuarios', ['email' => 'ana@cajbiobio.cl']);

        $region = Region::where('codigo', 'BI')->value('id');
        $this->actingAs($admin)->post(route('admin.usuarios.guardar'), $base + ['rol' => 'secretaria', 'region_id' => $region])
            ->assertSessionHasNoErrors()->assertSessionHas('password_temporal');

        $u = Usuario::where('email', 'ana@cajbiobio.cl')->firstOrFail();
        $this->assertSame($region, $u->region_id);
        $this->assertTrue($u->debe_cambiar_password);
        $this->assertNotNull($u->password);
    }

    public function test_crear_usuario_valida_correo_y_rut_unicos(): void
    {
        $admin = $this->usuario('administrador');
        $rut = $this->rutDe(12345678);
        $this->actingAs($admin)->post(route('admin.usuarios.guardar'), [
            'nombre' => 'Luis', 'apellido' => 'Soto', 'email' => 'luis@x.cl', 'rol' => 'gobernador', 'rut' => $rut,
        ])->assertSessionHasNoErrors();
        $this->assertSame(Rut::normalizar($rut), Usuario::where('email', 'luis@x.cl')->value('rut'));

        $this->actingAs($admin)->post(route('admin.usuarios.guardar'), [
            'nombre' => 'Otro', 'apellido' => 'Soto', 'email' => 'luis@x.cl', 'rol' => 'gobernador', 'rut' => $rut,
        ])->assertSessionHasErrors(['email', 'rut']);

        $this->actingAs($admin)->post(route('admin.usuarios.guardar'), [
            'nombre' => 'Malo', 'apellido' => 'Soto', 'email' => 'malo@x.cl', 'rol' => 'gobernador', 'rut' => '11111111-2',
        ])->assertSessionHasErrors('rut');
    }

    public function test_resetear_password_muestra_temporal_y_fuerza_cambio(): void
    {
        $admin = $this->usuario('administrador');
        $sec = $this->usuario('secretaria', ['debe_cambiar_password' => false]);
        $this->actingAs($admin)->post(route('admin.usuarios.resetear', $sec))->assertSessionHas('password_temporal');
        $this->assertTrue($sec->refresh()->debe_cambiar_password);
    }

    public function test_traslado_valida_cupos_y_registra_historial(): void
    {
        [$a, $b] = Consultorio::orderBy('id')->take(2)->get();
        $a->update(['capacidad' => 3]);
        $b->update(['capacidad' => 1]);
        $p = $this->practica($a);
        $this->practica($b);
        $admin = $this->usuario('administrador');

        $this->actingAs($admin)->post(route('admin.practicantes.trasladar', $p), ['consultorio_id' => $b->id])
            ->assertSessionHasErrors('consultorio_id');
        $this->assertSame($a->id, $p->refresh()->consultorio_id);

        $b->update(['capacidad' => 2]);
        $this->actingAs($admin)->post(route('admin.practicantes.trasladar', $p), ['consultorio_id' => $b->id, 'motivo' => 'Cercanía'])
            ->assertSessionHasNoErrors();
        $this->assertSame($b->id, $p->refresh()->consultorio_id);
        $this->assertDatabaseHas('practica_traslados', ['practica_id' => $p->id, 'consultorio_origen_id' => $a->id, 'consultorio_destino_id' => $b->id]);
    }

    public function test_cancelar_y_reactivar_practica(): void
    {
        $sede = Consultorio::orderBy('id')->first();
        $sede->update(['capacidad' => 1]);
        $p = $this->practica($sede);
        $admin = $this->usuario('administrador');

        $this->actingAs($admin)->post(route('admin.practicantes.cancelar', $p), ['motivo' => 'otro'])->assertSessionHasErrors('detalle');
        $this->actingAs($admin)->post(route('admin.practicantes.cancelar', $p), ['motivo' => 'error_registro'])->assertSessionHasNoErrors();
        $p->refresh();
        $this->assertSame('cancelada', $p->estado);
        $this->assertSame('error_registro', $p->cancelacion_motivo);

        $datos = [
            'nombre' => 'Prac', 'apellido' => 'Prueba', 'universidad' => 'U Test', 'email' => $p->practicante->postulante->email,
            'telefono' => '+56911112222', 'fecha_inicio' => $p->fecha_inicio->toDateString(), 'fecha_termino' => $p->fecha_termino->toDateString(),
            'discapacidad' => 0, 'estado' => 'activa',
        ];
        $this->actingAs($admin)->put(route('admin.practicantes.actualizar', $p), $datos)->assertSessionHasNoErrors();
        $p->refresh();
        $this->assertSame('activa', $p->estado);
        $this->assertNull($p->cancelacion_motivo);

        $this->actingAs($admin)->put(route('admin.practicantes.actualizar', $p), ['estado' => 'cancelada'] + $datos)->assertSessionHasErrors('estado');
    }

    public function test_rut_es_obligatorio_al_crear_usuario(): void
    {
        $this->actingAs($this->usuario('administrador'))->post(route('admin.usuarios.guardar'), [
            'nombre' => 'Ana', 'apellido' => 'Pérez', 'email' => 'ana@cajbiobio.cl', 'rol' => 'gobernador',
        ])->assertSessionHasErrors('rut');
    }
}
