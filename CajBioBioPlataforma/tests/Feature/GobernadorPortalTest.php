<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Services\GobernadorFirma;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\DatosPracticaB;
use Tests\TestCase;

class GobernadorPortalTest extends TestCase
{
    use DatosPracticaB, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
        Storage::fake('local');
    }

    public function test_solo_gobernador_accede(): void
    {
        $this->get('/gobernador')->assertRedirect(route('login'));
        foreach (['administrador', 'secretaria', 'abogado', 'practicante'] as $rol) {
            $this->actingAs($this->usuario($rol))->post('/gobernador/autorizar/todo')->assertForbidden();
            $this->actingAs($this->usuario($rol))->get('/gobernador/firma')->assertForbidden();
        }
    }

    public function test_autorizar_libera_documentos_audita_y_notifica(): void
    {
        $p = $this->practica(null, 'finalizada');
        $this->evaluar($p);
        $gob = $this->usuario('gobernador');

        $this->actingAs($gob)->post(route('gobernador.autorizar', $p->id))->assertSessionHasNoErrors();

        $p->refresh();
        $this->assertTrue($p->documentos_autorizados);
        $this->assertSame($gob->id, $p->documentos_autorizados_por);
        $this->assertDatabaseHas('auditoria', ['accion' => 'gobernador.autorizar_documentos', 'entidad_id' => $p->id]);
        $this->assertSame(1, Notificacion::where('usuario_destino_id', $p->practicante->usuario_id)->count());

        $this->actingAs($gob)->post(route('gobernador.autorizar', $p->id))->assertSessionHasErrors('practica');
    }

    public function test_no_autoriza_sin_evaluacion_aprobada(): void
    {
        $sinEval = $this->practica();
        $reprobada = $this->practica();
        $this->evaluar($reprobada, false);
        $gob = $this->usuario('gobernador');

        $this->actingAs($gob)->post(route('gobernador.autorizar', $sinEval->id))->assertSessionHasErrors('practica');
        $this->actingAs($gob)->post(route('gobernador.autorizar', $reprobada->id))->assertSessionHasErrors('practica');
        $this->assertFalse($sinEval->refresh()->documentos_autorizados);
    }

    public function test_lote_y_todo(): void
    {
        $ps = collect(range(1, 4))->map(fn () => tap($this->practica(), fn ($p) => $this->evaluar($p)));
        $gob = $this->usuario('gobernador');

        $this->actingAs($gob)->post(route('gobernador.autorizar.lote'), ['cantidad' => 0])->assertSessionHasErrors('cantidad');
        $this->actingAs($gob)->post(route('gobernador.autorizar.lote'), ['cantidad' => 2])->assertSessionHas('ok');
        $this->assertSame(2, $ps->filter(fn ($p) => $p->refresh()->documentos_autorizados)->count());

        $this->actingAs($gob)->post(route('gobernador.autorizar.todo'));
        $this->assertSame(4, $ps->filter(fn ($p) => $p->refresh()->documentos_autorizados)->count());
    }

    public function test_firma_sube_valida_y_se_sirve_solo_al_dueno(): void
    {
        $gob = $this->usuario('gobernador');
        $otro = $this->usuario('gobernador');

        $this->actingAs($gob)->post(route('gobernador.firma.subir'), ['firma' => UploadedFile::fake()->create('f.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('firma');
        $this->actingAs($gob)->post(route('gobernador.firma.subir'), ['firma' => UploadedFile::fake()->image('f.png', 200, 80)->size(3000)])
            ->assertSessionHasErrors('firma');

        $this->actingAs($gob)->post(route('gobernador.firma.subir'), ['firma' => UploadedFile::fake()->image('f.png', 200, 80)])
            ->assertSessionHasNoErrors();
        $gob->refresh();
        $this->assertNotNull($gob->firma_nombre_almacenado);
        $this->assertStringStartsWith('data:image/png;base64,', GobernadorFirma::dataUri($gob));

        $this->actingAs($gob)->get(route('gobernador.firma.imagen'))->assertOk();
        $this->actingAs($otro)->get(route('gobernador.firma.imagen'))->assertNotFound();
    }
}
