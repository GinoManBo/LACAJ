<?php

namespace Tests\Feature;

use App\Models\Abogado;
use App\Models\Comuna;
use App\Models\Consultorio;
use App\Models\Documento;
use App\Models\Notificacion;
use App\Models\Postulacion;
use App\Models\Postulante;
use App\Models\Practica;
use App\Models\Practicante;
use App\Models\Region;
use App\Models\Usuario;
use Database\Seeders\ReferenciasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecretariaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $secBiobio;

    private Usuario $secLosLagos;

    private Region $biobio;

    private Region $losLagos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(ReferenciasSeeder::class);
        $this->biobio = Region::where('codigo', 'BI')->first();
        $this->losLagos = Region::where('codigo', 'LL')->first();
        $this->secBiobio = $this->usuario('sec.bi@test.cl', 'secretaria', $this->biobio->id);
        $this->secLosLagos = $this->usuario('sec.ll@test.cl', 'secretaria', $this->losLagos->id);
    }

    private function usuario(string $email, string $rol, ?int $regionId = null): Usuario
    {
        return Usuario::create([
            'nombre' => 'Nombre', 'apellido' => 'Apellido', 'email' => $email, 'rol' => $rol,
            'region_id' => $regionId, 'password' => 'secret123', 'debe_cambiar_password' => false, 'activo' => true,
        ]);
    }

    private function postulacion(int $regionId, string $rut = '20114872-6', string $email = 'post@test.cl', ?string $inicio = null): Postulacion
    {
        $comuna = Comuna::where('region_id', $regionId)->first();
        $postulante = Postulante::create([
            'rut' => $rut, 'nombre' => 'Catalina', 'apellido' => 'Vera', 'email' => $email, 'telefono' => '+56911112222',
            'genero' => 'femenino', 'nacionalidad' => 'Chilena', 'region_id' => $regionId, 'comuna_id' => $comuna->id,
            'direccion' => 'Calle 1', 'universidad' => 'U de Prueba', 'anio_carrera' => 'quinto',
            'emergencia_nombre' => 'Madre', 'emergencia_vinculo' => 'Madre', 'emergencia_telefono' => '+56933334444',
        ]);

        return Postulacion::create([
            'postulante_id' => $postulante->id, 'estado' => 'pendiente', 'region_id' => $regionId,
            'fecha_postulacion' => now(), 'fecha_inicio_solicitada' => $inicio ?? now()->addDays(10)->toDateString(),
        ]);
    }

    private function consultorio(int $regionId): Consultorio
    {
        return Consultorio::where('region_id', $regionId)->first();
    }

    public function test_secretaria_de_otra_region_no_accede_ni_actua_sobre_postulacion(): void
    {
        $p = $this->postulacion($this->biobio->id);
        $c = $this->consultorio($this->biobio->id);

        $this->actingAs($this->secLosLagos)->get(route('secretaria.postulaciones.ver', $p->id))->assertNotFound();
        $this->actingAs($this->secLosLagos)->post(route('secretaria.postulaciones.aceptar', $p->id), ['consultorio_id' => $c->id])->assertNotFound();
        $this->actingAs($this->secLosLagos)->post(route('secretaria.postulaciones.rechazar', $p->id))->assertNotFound();
        $this->assertSame('pendiente', $p->fresh()->estado);
        $this->assertSame(0, Practica::count());
    }

    public function test_otros_roles_no_entran_al_portal_secretaria(): void
    {
        $this->get(route('secretaria.postulaciones'))->assertRedirect(route('login'));
        $abogado = $this->usuario('abo@test.cl', 'abogado');
        $this->actingAs($abogado)->get(route('secretaria.postulaciones'))->assertForbidden();
    }

    public function test_aceptar_con_cupo_crea_practica_y_cuenta_de_practicante(): void
    {
        $p = $this->postulacion($this->biobio->id);
        $c = $this->consultorio($this->biobio->id);
        $uAbogado = $this->usuario('abo@test.cl', 'abogado');
        $abogado = Abogado::create(['usuario_id' => $uAbogado->id, 'area' => 'civil']);
        $abogado->consultorios()->attach($c->id);

        $this->actingAs($this->secBiobio)
            ->post(route('secretaria.postulaciones.aceptar', $p->id), ['consultorio_id' => $c->id, 'abogado_id' => $abogado->id])
            ->assertRedirect(route('secretaria.postulaciones'))
            ->assertSessionHas('credenciales');

        $p->refresh();
        $this->assertSame('aceptada', $p->estado);
        $practica = Practica::firstOrFail();
        $this->assertSame('por_iniciar', $practica->estado);
        $this->assertSame($c->id, $practica->consultorio_id);
        $this->assertSame($abogado->id, $practica->abogado_id);
        $this->assertTrue($practica->fecha_termino->equalTo($practica->fecha_inicio->copy()->addMonths(6)));

        $cuenta = Usuario::where('email', 'post@test.cl')->firstOrFail();
        $this->assertSame('postulante', $cuenta->rol);
        $this->assertTrue($cuenta->debe_cambiar_password);
        $this->assertSame($cuenta->id, Practicante::firstOrFail()->usuario_id);
        $this->assertDatabaseHas('notificaciones', ['usuario_destino_id' => $uAbogado->id, 'tipo' => 'informativo']);
        $this->assertDatabaseHas('auditoria', ['accion' => 'postulacion.aceptar', 'entidad_id' => $p->id]);
    }

    public function test_aceptar_sin_cupo_falla_y_no_deja_rastro(): void
    {
        $c = $this->consultorio($this->biobio->id);
        $c->update(['capacidad' => 1]);
        $otra = $this->postulacion($this->biobio->id, '12345678-5', 'otra@test.cl');
        $this->actingAs($this->secBiobio)->post(route('secretaria.postulaciones.aceptar', $otra->id), ['consultorio_id' => $c->id])->assertSessionHasNoErrors();
        $this->assertSame(1, Practica::count());

        $p = $this->postulacion($this->biobio->id);
        $this->actingAs($this->secBiobio)
            ->post(route('secretaria.postulaciones.aceptar', $p->id), ['consultorio_id' => $c->id])
            ->assertSessionHasErrors('consultorio_id');

        $this->assertSame('pendiente', $p->fresh()->estado);
        $this->assertSame(1, Practica::count());
        $this->assertDatabaseMissing('usuarios', ['email' => 'post@test.cl']);
    }

    public function test_abogado_de_otro_consultorio_es_rechazado(): void
    {
        $p = $this->postulacion($this->biobio->id);
        $c = $this->consultorio($this->biobio->id);
        $abogado = Abogado::create(['usuario_id' => $this->usuario('abo@test.cl', 'abogado')->id, 'area' => 'civil']);

        $this->actingAs($this->secBiobio)
            ->post(route('secretaria.postulaciones.aceptar', $p->id), ['consultorio_id' => $c->id, 'abogado_id' => $abogado->id])
            ->assertSessionHasErrors('abogado_id');
        $this->assertSame('pendiente', $p->fresh()->estado);
    }

    public function test_consultorio_de_otra_region_deriva_la_postulacion(): void
    {
        $p = $this->postulacion($this->biobio->id);
        $destino = $this->consultorio($this->losLagos->id);

        $this->actingAs($this->secBiobio)
            ->post(route('secretaria.postulaciones.aceptar', $p->id), ['consultorio_id' => $destino->id, 'nota' => 'Vive en Puerto Montt'])
            ->assertRedirect(route('secretaria.postulaciones'));

        $p->refresh();
        $this->assertSame('pendiente', $p->estado);
        $this->assertSame($this->losLagos->id, $p->region_id);
        $this->assertSame(0, Practica::count());
        $this->assertDatabaseHas('postulacion_derivaciones', ['postulacion_id' => $p->id, 'region_origen_id' => $this->biobio->id, 'region_destino_id' => $this->losLagos->id]);
        $this->assertDatabaseHas('notificaciones', ['usuario_destino_id' => $this->secLosLagos->id]);

        $this->assertDatabaseHas('postulacion_notas', ['postulacion_id' => $p->id, 'texto' => 'Vive en Puerto Montt']);
        $this->actingAs($this->secBiobio)->get(route('secretaria.postulaciones.ver', $p->id))->assertNotFound();
        $this->vistasFalsas();
        $this->actingAs($this->secLosLagos)->get(route('secretaria.postulaciones.ver', $p->id))->assertOk();
    }

    private function vistasFalsas(): void
    {
        $dir = sys_get_temp_dir().'/vistas-falsas-secretaria/secretaria/postulaciones';
        @mkdir($dir, 0777, true);
        file_put_contents($dir.'/ver.blade.php', 'ok');
        view()->addLocation(dirname($dir, 2));
    }

    public function test_rechazar_y_no_se_puede_resolver_dos_veces(): void
    {
        $p = $this->postulacion($this->biobio->id);
        $this->actingAs($this->secBiobio)->post(route('secretaria.postulaciones.rechazar', $p->id), ['motivo' => 'Sin cupos'])->assertRedirect();
        $this->assertSame('rechazada', $p->fresh()->estado);
        $this->assertSame('Sin cupos', $p->fresh()->motivo_rechazo);

        $this->actingAs($this->secBiobio)->post(route('secretaria.postulaciones.rechazar', $p->id))->assertSessionHasErrors('estado');
    }

    public function test_solo_el_autor_elimina_su_nota(): void
    {
        $p = $this->postulacion($this->biobio->id);
        $otraSecretaria = $this->usuario('sec2.bi@test.cl', 'secretaria', $this->biobio->id);
        $nota = $p->notas()->create(['autor_usuario_id' => $this->secBiobio->id, 'texto' => 'Revisar']);

        $this->actingAs($otraSecretaria)->delete(route('secretaria.postulaciones.nota.eliminar', [$p->id, $nota->id]))->assertForbidden();
        $this->actingAs($this->secBiobio)->delete(route('secretaria.postulaciones.nota.eliminar', [$p->id, $nota->id]))->assertRedirect();
        $this->assertDatabaseMissing('postulacion_notas', ['id' => $nota->id]);
    }

    private function practicaActiva(): Practica
    {
        $p = $this->postulacion($this->biobio->id, inicio: now()->subDays(5)->toDateString());
        $c = $this->consultorio($this->biobio->id);
        $this->actingAs($this->secBiobio)->post(route('secretaria.postulaciones.aceptar', $p->id), ['consultorio_id' => $c->id]);

        return Practica::firstOrFail();
    }

    public function test_cancelar_practica_libera_cupo_y_registra_motivo(): void
    {
        $practica = $this->practicaActiva();
        $this->assertSame('activa', $practica->estado);
        $c = $practica->consultorio;
        $libresAntes = $c->libres();

        $this->post(route('secretaria.practicas.cancelar', $practica->id), [])->assertSessionHasErrors('motivo');

        $this->post(route('secretaria.practicas.cancelar', $practica->id), ['motivo' => 'decision', 'detalle' => 'Cambio de ciudad'])
            ->assertRedirect(route('secretaria.practicas'));

        $practica->refresh();
        $this->assertSame('cancelada', $practica->estado);
        $this->assertSame('decision', $practica->cancelacion_motivo);
        $this->assertFalse($practica->cancelacion_antes_de_iniciar);
        $this->assertSame($this->secBiobio->id, $practica->cancelada_por);
        $this->assertSame($libresAntes + 1, $c->fresh()->libres());

        $this->post(route('secretaria.practicas.cancelar', $practica->id), ['motivo' => 'otro'])->assertNotFound();
    }

    public function test_secretaria_de_otra_region_no_cancela_practica_ajena(): void
    {
        $practica = $this->practicaActiva();
        $this->actingAs($this->secLosLagos)->post(route('secretaria.practicas.cancelar', $practica->id), ['motivo' => 'otro'])->assertNotFound();
        $this->assertSame('activa', $practica->fresh()->estado);
    }

    public function test_documento_de_cancelacion_es_pdf(): void
    {
        $practica = $this->practicaActiva();
        $this->post(route('secretaria.practicas.cancelar', $practica->id), ['motivo' => 'conducta']);

        $r = $this->get(route('secretaria.cancelacion.documento', $practica->id));
        $r->assertOk();
        $this->assertSame('application/pdf', $r->headers->get('content-type'));
        $this->actingAs($this->secLosLagos)->get(route('secretaria.cancelacion.documento', $practica->id))->assertNotFound();
    }

    public function test_exportar_practicas_genera_xlsx(): void
    {
        $this->practicaActiva();
        $r = $this->get(route('secretaria.practicas.exportar'));
        $r->assertOk();
        $this->assertStringContainsString('spreadsheetml', $r->headers->get('content-type'));
    }

    public function test_documentos_se_autorizan_por_region(): void
    {
        $p = $this->postulacion($this->biobio->id);
        $nombre = (string) Str::uuid();
        Storage::disk('local')->put('documentos/'.$nombre, 'contenido');
        $doc = Documento::create([
            'tipo' => 'cedula_frontal', 'postulacion_id' => $p->id, 'nombre_original' => 'c.png',
            'nombre_almacenado' => $nombre, 'mime' => 'image/png', 'tamano_bytes' => 9, 'sha256' => str_repeat('a', 64),
        ]);

        $this->actingAs($this->secBiobio)->get(route('documentos.ver', $doc->id))->assertOk();
        $this->actingAs($this->secBiobio)->get(route('documentos.descargar', $doc->id))->assertOk()->assertHeader('content-disposition');
        $this->actingAs($this->secLosLagos)->get(route('documentos.ver', $doc->id))->assertNotFound();
        $this->actingAs($this->usuario('abo@test.cl', 'abogado'))->get(route('documentos.ver', $doc->id))->assertNotFound();
        $this->actingAs($this->usuario('admin@test.cl', 'administrador'))->get(route('documentos.ver', $doc->id))->assertOk();

        Storage::disk('local')->delete('documentos/'.$nombre);
    }

    public function test_comando_activa_practicas_y_avisa_una_sola_vez(): void
    {
        $p = $this->postulacion($this->biobio->id, inicio: now()->addDays(3)->toDateString());
        $c = $this->consultorio($this->biobio->id);
        $uAbogado = $this->usuario('abo@test.cl', 'abogado');
        $abogado = Abogado::create(['usuario_id' => $uAbogado->id, 'area' => 'civil']);
        $abogado->consultorios()->attach($c->id);
        $this->actingAs($this->secBiobio)->post(route('secretaria.postulaciones.aceptar', $p->id), ['consultorio_id' => $c->id, 'abogado_id' => $abogado->id]);

        $practica = Practica::firstOrFail();
        $practica->update(['fecha_inicio' => now()->subMonths(5)->toDateString(), 'fecha_termino' => now()->addDays(10)->toDateString()]);

        $this->artisan('practicas:actualizar-estados')->assertSuccessful();
        $this->artisan('practicas:actualizar-estados')->assertSuccessful();

        $this->assertSame('activa', $practica->fresh()->estado);
        $this->assertSame(1, Notificacion::where('tipo', 'urgente')->where('usuario_destino_id', $uAbogado->id)->count());
    }
}
