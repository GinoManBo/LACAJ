<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Comuna;
use App\Models\Notificacion;
use App\Models\Postulacion;
use App\Models\Postulante;
use App\Models\Region;
use App\Models\Usuario;
use Database\Seeders\ReferenciasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditarDatosPostulanteTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $perfil;
    private Usuario $sec;
    private Postulante $po;
    private Postulacion $p;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(ReferenciasSeeder::class);
        $region = Region::where('codigo', 'BI')->first();
        $this->perfil = Usuario::create(['nombre' => 'Ana Paz', 'apellido' => 'Rojas', 'rut' => '20000001-3', 'rol' => 'postulante', 'activo' => true, 'debe_cambiar_password' => false]);
        $this->sec = Usuario::create(['nombre' => 'S', 'apellido' => 'B', 'email' => 's@test.cl', 'rut' => '10000002-4', 'rol' => 'secretaria', 'password' => 'secreto123', 'debe_cambiar_password' => false, 'activo' => true, 'region_id' => $region->id]);
        $this->po = Postulante::create([
            'rut' => '20000001-3', 'nombre' => 'Ana Paz', 'apellido' => 'Rojas', 'email' => 'ana@mail.cl', 'telefono' => '+56911112222',
            'fecha_nacimiento' => '2001-03-12', 'genero' => 'femenino', 'nacionalidad' => 'Chilena', 'region_id' => $region->id,
            'comuna_id' => Comuna::where('region_id', $region->id)->value('id'), 'direccion' => 'Calle 1', 'universidad' => 'U',
            'anio_carrera' => 'quinto', 'especialidad_id' => \App\Models\Especialidad::value('id'), 'antecedentes_salud' => 'Ninguno',
            'emergencia_nombre' => 'Madre', 'emergencia_vinculo' => 'Madre', 'emergencia_telefono' => '+56933334444',
        ]);
        $this->p = Postulacion::create(['postulante_id' => $this->po->id, 'estado' => 'pendiente', 'region_id' => $region->id, 'fecha_postulacion' => now(), 'fecha_inicio_solicitada' => now()->addDays(10)->toDateString()]);
    }

    private function datos(array $cambios = []): array
    {
        $p = $this->po->fresh();

        return $cambios + [
            'email' => $p->email, 'telefono' => $p->telefono, 'genero' => $p->genero, 'nacionalidad' => $p->nacionalidad, 'discapacidad' => '0',
            'region_id' => $p->region_id, 'comuna_id' => $p->comuna_id, 'direccion' => $p->direccion,
            'emergencia_nombre' => $p->emergencia_nombre, 'emergencia_vinculo' => $p->emergencia_vinculo, 'emergencia_telefono' => $p->emergencia_telefono,
            'antecedentes_salud' => $p->antecedentes_salud, 'universidad' => $p->universidad, 'anio_carrera' => $p->anio_carrera, 'especialidad_id' => $p->especialidad_id,
        ];
    }

    public function test_los_cambios_del_postulante_los_ve_la_secretaria_y_queda_registro(): void
    {
        $this->actingAs($this->perfil)->post(route('postulante.datos.guardar'), $this->datos(['telefono' => '+56999998888', 'direccion' => 'Av. Nueva 123', 'email' => 'Nuevo@Mail.cl']))
            ->assertRedirect(route('postulante.inicio'))->assertSessionHas('ok');

        $po = $this->po->fresh();
        $this->assertSame('+56999998888', $po->telefono);
        $this->assertSame('nuevo@mail.cl', $po->email);
        $this->assertNotNull($po->datos_actualizados_at);

        $this->actingAs($this->sec)->get(route('secretaria.postulaciones.ver', $this->p->id))->assertOk()
            ->assertSee('Av. Nueva 123')->assertSee('+56999998888');

        $a = Auditoria::where('accion', 'postulante.actualizar_datos')->firstOrFail();
        $this->assertEqualsCanonicalizing(['email', 'telefono', 'direccion'], $a->datos['campos']);
        $this->assertSame(1, Notificacion::where('usuario_destino_id', $this->sec->id)->where('titulo', 'Postulante actualizó sus datos')->count());
    }

    public function test_no_puede_cambiar_nombre_rut_ni_fecha_de_nacimiento(): void
    {
        $this->actingAs($this->perfil)->post(route('postulante.datos.guardar'), $this->datos(['telefono' => '+56977776666', 'nombre' => 'Otra', 'apellido' => 'Persona', 'rut' => '10000001-6', 'fecha_nacimiento' => '1990-01-01']));

        $po = $this->po->fresh();
        $this->assertSame('+56977776666', $po->telefono);
        $this->assertSame(['Ana Paz', 'Rojas', '20000001-3', '2001-03-12'], [$po->nombre, $po->apellido, $po->rut, $po->fecha_nacimiento->toDateString()]);
    }

    public function test_valida_con_las_mismas_reglas_del_formulario(): void
    {
        $this->actingAs($this->perfil)->post(route('postulante.datos.guardar'), $this->datos(['telefono' => 'abc', 'email' => 'no-es-correo']))
            ->assertSessionHasErrors(['telefono', 'email']);
        $this->assertNull($this->po->fresh()->datos_actualizados_at);
    }

    public function test_solo_edita_su_propia_ficha_y_solo_mientras_esta_en_revision(): void
    {
        $otro = Usuario::create(['nombre' => 'Otro', 'apellido' => 'X', 'rut' => '20000002-1', 'rol' => 'postulante', 'activo' => true, 'debe_cambiar_password' => false]);
        $this->actingAs($otro)->post(route('postulante.datos.guardar'), $this->datos(['telefono' => '+56900000000']))->assertForbidden();
        $this->assertSame('+56911112222', $this->po->fresh()->telefono);

        $this->p->update(['estado' => 'rechazada']);
        $this->actingAs($this->perfil)->post(route('postulante.datos.guardar'), $this->datos(['telefono' => '+56900000000']))->assertForbidden();
        $this->actingAs($this->perfil)->get(route('postulante.datos'))->assertRedirect(route('postulante.inicio'));
    }

    public function test_la_vista_de_edicion_carga_con_los_datos_actuales(): void
    {
        $this->actingAs($this->perfil)->get(route('postulante.datos'))->assertOk()->assertSee('Calle 1')->assertSee('20.000.001-3');
        $this->actingAs($this->sec)->get(route('postulante.datos'))->assertForbidden();
    }
}
