<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Services\InformesPractica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreaPracticas;
use Tests\TestCase;

class TutorEvaluacionTest extends TestCase
{
    use RefreshDatabase, CreaPracticas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
        Storage::fake('local');
    }

    private function notas(float $valor = 6.0): array
    {
        return array_fill_keys(array_keys(\App\Support\Notas::CRITERIOS), (string) $valor) + ['observaciones' => 'Buen desempeño'];
    }

    public function test_tutor_no_ve_ni_evalua_practicas_ajenas(): void
    {
        $mio = $this->crearAbogado();
        $otro = $this->crearAbogado();
        [$ajena] = $this->crearPractica($otro);

        $this->actingAs($mio->usuario)->get(route('tutor.practicantes.ver', $ajena->id))->assertNotFound();
        $this->actingAs($mio->usuario)->post(route('tutor.evaluacion.guardar', $ajena->id), $this->notas())->assertNotFound();
        $this->assertDatabaseCount('evaluaciones', 0);
    }

    public function test_solo_rol_abogado_entra_al_portal(): void
    {
        [, $usuarioPracticante] = $this->crearPractica($this->crearAbogado());
        $this->actingAs($usuarioPracticante)->get(route('tutor.inicio'))->assertForbidden();
    }

    public function test_evaluacion_bloqueada_sin_informe(): void
    {
        $ab = $this->crearAbogado();
        [$p] = $this->crearPractica($ab);

        $this->actingAs($ab->usuario)->post(route('tutor.evaluacion.guardar', $p->id), $this->notas())
            ->assertSessionHasErrors('evaluacion');
        $this->assertDatabaseCount('evaluaciones', 0);
        $this->assertSame('activa', $p->fresh()->estado);
    }

    public function test_evaluacion_calcula_promedio_aprueba_y_finaliza(): void
    {
        $ab = $this->crearAbogado();
        [$p, $usuarioPracticante] = $this->crearPractica($ab);
        InformesPractica::subir($p, UploadedFile::fake()->create('informe.pdf', 50, 'application/pdf'), $usuarioPracticante);

        $datos = $this->notas(5.0);
        $datos['conducta'] = '7,0';
        $this->actingAs($ab->usuario)->post(route('tutor.evaluacion.guardar', $p->id), $datos)->assertSessionHasNoErrors();

        $p->refresh();
        $this->assertSame('finalizada', $p->estado);
        $this->assertNotNull($p->finalizada_at);
        $ev = $p->evaluacion;
        $this->assertEqualsWithDelta(5.29, $ev->promedio, 0.001);
        $this->assertTrue($ev->aprobado);
        $this->assertSame(5.3, $p->nota_practica);
        $this->assertDatabaseHas('calificaciones', ['practica_id' => $p->id, 'instancia' => 1, 'nota' => 5.3]);
        $this->assertTrue(Notificacion::where('usuario_destino_id', $usuarioPracticante->id)->where('titulo', 'Práctica evaluada')->exists());

        $this->actingAs($ab->usuario)->post(route('tutor.evaluacion.guardar', $p->id), $this->notas())->assertSessionHasErrors();
        $this->assertDatabaseCount('evaluaciones', 1);
    }

    public function test_reprobada_finaliza_pero_no_aprueba(): void
    {
        $ab = $this->crearAbogado();
        [$p, $up] = $this->crearPractica($ab);
        InformesPractica::subir($p, UploadedFile::fake()->create('i.pdf', 10, 'application/pdf'), $up);

        $this->actingAs($ab->usuario)->post(route('tutor.evaluacion.guardar', $p->id), $this->notas(3.0))->assertSessionHasNoErrors();
        $this->assertFalse($p->fresh()->evaluacion->aprobado);
        $this->assertSame('finalizada', $p->fresh()->estado);
    }

    public function test_exige_los_siete_criterios_en_rango(): void
    {
        $ab = $this->crearAbogado();
        [$p, $up] = $this->crearPractica($ab);
        InformesPractica::subir($p, UploadedFile::fake()->create('i.pdf', 10, 'application/pdf'), $up);

        $datos = $this->notas();
        unset($datos['honorabilidad']);
        $datos['conducta'] = '7.5';
        $this->actingAs($ab->usuario)->post(route('tutor.evaluacion.guardar', $p->id), $datos)->assertSessionHasErrors(['honorabilidad', 'conducta']);
        $this->assertDatabaseCount('evaluaciones', 0);
    }

    public function test_tutor_descarga_informe_de_su_practica(): void
    {
        $ab = $this->crearAbogado();
        [$p, $up] = $this->crearPractica($ab);
        InformesPractica::subir($p, UploadedFile::fake()->create('informe.pdf', 10, 'application/pdf'), $up);
        $this->actingAs($ab->usuario)->get(route('tutor.informe.descargar', $p->id))->assertOk();

        $otro = $this->crearAbogado();
        $this->actingAs($otro->usuario)->get(route('tutor.informe.descargar', $p->id))->assertNotFound();
    }
}
