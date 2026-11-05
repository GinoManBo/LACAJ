<?php

namespace Tests\Feature;

use App\Models\Comuna;
use App\Models\Consultorio;
use App\Models\Postulacion;
use App\Models\PostulacionBorrador;
use App\Models\Postulante;
use App\Models\Region;
use App\Models\Usuario;
use App\Services\Postulaciones;
use Database\Seeders\ReferenciasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BorradorPostulanteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(ReferenciasSeeder::class);
    }

    private function postulante(string $rut = '20000001-3'): Usuario
    {
        return Usuario::create(['nombre' => 'Ana Paz', 'apellido' => 'Rojas', 'rut' => $rut, 'rol' => 'postulante', 'activo' => true, 'debe_cambiar_password' => false]);
    }

    private function datosPaso2(array $extra = []): array
    {
        $region = Region::where('codigo', 'BI')->first();

        return $extra + [
            'nombre' => 'Ana Paz', 'apellido' => 'Rojas Soto', 'rut' => '20.000.001-3', 'fecha_nacimiento' => '2001-03-12',
            'email' => 'ana@mail.cl', 'telefono' => '+56911112222', 'genero' => 'femenino', 'nacionalidad' => 'Chilena',
            'discapacidad' => '0', 'region_id' => $region->id, 'comuna_id' => Comuna::where('region_id', $region->id)->value('id'),
            'direccion' => 'Calle 1', 'emergencia_nombre' => 'Madre', 'emergencia_vinculo' => 'Madre',
            'emergencia_telefono' => '+56933334444', 'antecedentes_salud' => 'Ninguno',
        ];
    }

    public function test_claveunica_crea_perfil_de_postulante_y_adopta_el_borrador_de_sesion(): void
    {
        config(['claveunica.enabled' => true, 'claveunica.client_id' => 'x', 'claveunica.client_secret' => 'y', 'claveunica.redirect_uri' => 'http://localhost/cb']);
        Http::fake([
            config('claveunica.token_url') => Http::response(['access_token' => 't']),
            config('claveunica.userinfo_url') => Http::response(['RolUnico' => ['numero' => 20000001, 'DV' => '3'], 'name' => ['nombres' => ['Ana', 'Paz'], 'apellidos' => ['Rojas', 'Soto']]]),
        ]);

        $this->withSession(['claveunica.state' => 'abc', 'postulacion' => ['acepta' => true]])
            ->get(route('claveunica.callback', ['code' => 'c', 'state' => 'abc']))
            ->assertRedirect(route('postulante.inicio'));

        $u = Usuario::where('rut', '20000001-3')->firstOrFail();
        $this->assertSame('postulante', $u->rol);
        $this->assertSame('Ana Paz', $u->nombre);
        $this->assertNull($u->password);
        $this->assertAuthenticatedAs($u);
        $this->assertTrue(PostulacionBorrador::where('usuario_id', $u->id)->firstOrFail()->datos['acepta']);
    }

    public function test_el_avance_se_guarda_en_el_perfil_y_se_retoma_en_otra_sesion(): void
    {
        $u = $this->postulante();

        $this->actingAs($u)->post(route('postulacion.guardar', 1), ['acepta' => '1'])->assertRedirect(route('postulacion.paso', 2));
        $this->actingAs($u)->post(route('postulacion.guardar', 2), $this->datosPaso2())->assertRedirect(route('postulacion.paso', 3));

        $b = PostulacionBorrador::where('usuario_id', $u->id)->firstOrFail()->datos;
        $this->assertTrue($b['acepta']);
        $this->assertSame('Ana Paz', $b['datos']['nombre']);

        $this->flushSession();
        $this->actingAs($u)->get(route('postulacion.paso', 3))->assertOk();
        $this->actingAs($u)->get(route('postulacion.paso', 4))->assertRedirect(route('postulacion.paso', 3));
        $this->actingAs($u)->get(route('postulacion.paso', 2))->assertOk()->assertSee('Rojas Soto');
    }

    public function test_el_rut_del_formulario_es_siempre_el_de_su_claveunica(): void
    {
        $u = $this->postulante();
        $this->actingAs($u)->post(route('postulacion.guardar', 1), ['acepta' => '1']);
        $this->actingAs($u)->post(route('postulacion.guardar', 2), $this->datosPaso2(['rut' => '10.000.001-6']));

        $this->assertSame('20.000.001-3', PostulacionBorrador::where('usuario_id', $u->id)->firstOrFail()->datos['datos']['rut']);
    }

    public function test_cada_postulante_ve_solo_su_borrador(): void
    {
        $a = $this->postulante('20000001-3');
        $b = $this->postulante('20000002-1');
        $this->actingAs($a)->post(route('postulacion.guardar', 1), ['acepta' => '1']);

        $this->actingAs($b)->get(route('postulacion.paso', 2))->assertRedirect(route('postulacion.paso', 1));
        $this->assertSame(1, PostulacionBorrador::count());
    }

    public function test_anonimo_sigue_usando_la_sesion(): void
    {
        $this->post(route('postulacion.guardar', 1), ['acepta' => '1'])->assertRedirect(route('postulacion.paso', 2));
        $this->assertSame(0, PostulacionBorrador::count());
        $this->assertTrue(session('postulacion.acepta'));
    }

    public function test_aceptar_reutiliza_el_perfil_de_postulante_como_practicante(): void
    {
        $perfil = $this->postulante();
        $region = Region::where('codigo', 'BI')->first();
        $sec = Usuario::create(['nombre' => 'S', 'apellido' => 'B', 'email' => 's@test.cl', 'rut' => '10000002-4', 'rol' => 'secretaria', 'password' => 'secreto123', 'debe_cambiar_password' => false, 'activo' => true, 'region_id' => $region->id]);
        $po = Postulante::create([
            'rut' => '20000001-3', 'nombre' => 'Ana Paz', 'apellido' => 'Rojas', 'email' => 'ana@mail.cl', 'telefono' => '+56911112222',
            'genero' => 'femenino', 'nacionalidad' => 'Chilena', 'region_id' => $region->id, 'comuna_id' => Comuna::where('region_id', $region->id)->value('id'),
            'direccion' => 'Calle 1', 'universidad' => 'U', 'anio_carrera' => 'quinto', 'emergencia_nombre' => 'M', 'emergencia_vinculo' => 'M', 'emergencia_telefono' => '+56933334444',
        ]);
        $p = Postulacion::create(['postulante_id' => $po->id, 'estado' => 'pendiente', 'region_id' => $region->id, 'fecha_postulacion' => now(), 'fecha_inicio_solicitada' => now()->addDays(10)->toDateString()]);

        $r = Postulaciones::asignar($p, $sec, Consultorio::where('region_id', $region->id)->value('id'), null, null);

        $this->assertNull($r['passwordTemporal']);
        $this->assertSame(1, Usuario::where('rut', '20000001-3')->count());
        $this->assertSame('postulante', $perfil->fresh()->rol);
        $this->assertSame('ana@mail.cl', $perfil->fresh()->email);
        $this->assertSame($perfil->id, $po->practicante->fresh()->usuario_id);
    }

    public function test_el_perfil_muestra_el_avance_y_el_paso_donde_continuar(): void
    {
        $u = $this->postulante();
        $this->actingAs($u)->get(route('postulante.inicio'))->assertOk()->assertSee('Ana Paz');

        $this->actingAs($u)->post(route('postulacion.guardar', 1), ['acepta' => '1']);
        $this->actingAs($u)->post(route('postulacion.guardar', 2), $this->datosPaso2());
        $this->actingAs($u)->get(route('postulante.inicio'))->assertOk()
            ->assertSee(route('postulacion.paso', 3), false)->assertSee('Antecedentes educativos');
    }

    public function test_el_perfil_muestra_la_postulacion_enviada_y_es_solo_para_postulantes(): void
    {
        $u = $this->postulante();
        $region = Region::where('codigo', 'BI')->first();
        $po = Postulante::create([
            'rut' => '20000001-3', 'nombre' => 'Ana Paz', 'apellido' => 'Rojas', 'email' => 'ana@mail.cl', 'telefono' => '+56911112222',
            'genero' => 'femenino', 'nacionalidad' => 'Chilena', 'region_id' => $region->id, 'comuna_id' => Comuna::where('region_id', $region->id)->value('id'),
            'direccion' => 'Calle 1', 'universidad' => 'U', 'anio_carrera' => 'quinto', 'emergencia_nombre' => 'M', 'emergencia_vinculo' => 'M', 'emergencia_telefono' => '+56933334444',
        ]);
        Postulacion::create(['postulante_id' => $po->id, 'estado' => 'pendiente', 'region_id' => $region->id, 'fecha_postulacion' => now(), 'fecha_inicio_solicitada' => now()->addDays(10)->toDateString()]);

        $this->actingAs($u)->get(route('postulante.inicio'))->assertOk()->assertSee('Tu postulación fue enviada');

        $admin = Usuario::create(['nombre' => 'A', 'apellido' => 'B', 'email' => 'a@t.cl', 'rut' => '10000001-6', 'rol' => 'administrador', 'password' => 'secreto123', 'debe_cambiar_password' => false, 'activo' => true]);
        $this->actingAs($admin)->get(route('postulante.inicio'))->assertForbidden();
    }
}
