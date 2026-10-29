<?php

namespace Tests\Feature;

use App\Models\Calificacion;
use App\Models\Evaluacion;
use App\Models\Practica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreaPracticas;
use Tests\TestCase;

class PracticanteDocumentosTest extends TestCase
{
    use RefreshDatabase, CreaPracticas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
        Storage::fake('local');
    }

    private function finalizar(Practica $p, bool $aprobado = true, bool $autorizada = false): void
    {
        $nota = $aprobado ? 6.0 : 3.0;
        Evaluacion::create(array_fill_keys(array_keys(\App\Support\Notas::CRITERIOS), $nota) + [
            'practica_id' => $p->id, 'abogado_id' => $p->abogado_id, 'promedio' => $nota, 'aprobado' => $aprobado,
        ]);
        Calificacion::create(['practica_id' => $p->id, 'instancia' => 1, 'nota' => $nota, 'fecha' => today(), 'registrada_por' => $p->abogado->usuario_id]);
        $p->update(['estado' => 'finalizada', 'nota_practica' => $nota, 'documentos_autorizados' => $autorizada]);
    }

    public function test_practicante_solo_ve_su_practica(): void
    {
        $ab = $this->crearAbogado();
        [$p1, $u1] = $this->crearPractica($ab);
        [$p2] = $this->crearPractica($ab);
        $this->finalizar($p1);
        $this->finalizar($p2, true, true);

        $this->actingAs($u1)->get(route('practicante.certificado'))->assertForbidden();
        $this->actingAs($u1)->get(route('practicante.inicio'))->assertOk()
            ->assertViewHas('practica', fn ($p) => $p->id === $p1->id);
    }

    public function test_otros_roles_no_entran(): void
    {
        $ab = $this->crearAbogado();
        $this->actingAs($ab->usuario)->get(route('practicante.inicio'))->assertForbidden();
    }

    public function test_documentos_bloqueados_sin_autorizacion_del_gobernador(): void
    {
        [$p, $u] = $this->crearPractica($this->crearAbogado());
        $this->finalizar($p, true, false);

        $this->actingAs($u)->get(route('practicante.certificado'))->assertForbidden();
        $this->actingAs($u)->get(route('practicante.resolucion'))->assertForbidden();
        $this->actingAs($u)->get(route('practicante.inicio'))
            ->assertViewHas('documentos', fn ($d) => str_contains($d[0]['bloqueo'], 'Gobernador'));
    }

    public function test_documentos_bloqueados_si_no_finalizada_o_reprobada(): void
    {
        [$p, $u] = $this->crearPractica($this->crearAbogado());
        $this->actingAs($u)->get(route('practicante.certificado'))->assertForbidden();

        $this->finalizar($p, false, true);
        $this->actingAs($u)->get(route('practicante.certificado'))->assertForbidden();
    }

    public function test_documentos_disponibles_con_autorizacion(): void
    {
        [$p, $u] = $this->crearPractica($this->crearAbogado());
        $this->finalizar($p, true, true);

        $r = $this->actingAs($u)->get(route('practicante.certificado'));
        $r->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $r->getContent());

        $r = $this->actingAs($u)->get(route('practicante.resolucion'));
        $r->assertOk();
        $this->assertStringStartsWith('%PDF', $r->getContent());
        $this->assertDatabaseHas('certificados', ['practica_id' => $p->id, 'tipo' => 'resolucion', 'numero_resolucion' => 1, 'anio' => now()->year]);
    }

    public function test_numeracion_de_resoluciones_consecutiva_y_estable(): void
    {
        $ab = $this->crearAbogado();
        $usuarios = [];
        foreach (range(1, 3) as $i) {
            [$p, $u] = $this->crearPractica($ab);
            $this->finalizar($p, true, true);
            $usuarios[$p->id] = $u;
        }

        foreach ($usuarios as $u) {
            $this->actingAs($u)->get(route('practicante.resolucion'))->assertOk();
        }
        $this->actingAs(reset($usuarios))->get(route('practicante.resolucion'))->assertOk();

        $numeros = \App\Models\Certificado::where('tipo', 'resolucion')->orderBy('numero_resolucion')->pluck('numero_resolucion')->all();
        $this->assertSame([1, 2, 3], $numeros);
        $this->assertSame(3, (int) \DB::table('resolucion_secuencia')->where('anio', now()->year)->value('ultimo_numero'));
    }

    public function test_practicante_sube_informe_y_se_reemplaza(): void
    {
        $ab = $this->crearAbogado();
        [$p, $u] = $this->crearPractica($ab);

        $this->actingAs($u)->post(route('practicante.informe.subir'), [])->assertSessionHasErrors('informe');
        $this->actingAs($u)->post(route('practicante.informe.subir'), ['informe' => UploadedFile::fake()->create('a.exe', 5)])->assertSessionHasErrors('informe');

        $this->actingAs($u)->post(route('practicante.informe.subir'), ['informe' => UploadedFile::fake()->create('a.pdf', 20, 'application/pdf')])->assertSessionHasNoErrors();
        $this->actingAs($u)->post(route('practicante.informe.subir'), ['informe' => UploadedFile::fake()->create('b.pdf', 20, 'application/pdf')])->assertSessionHasNoErrors();
        $this->assertSame(1, $p->documentos()->where('tipo', 'informe_practica')->count());
        $this->assertSame('b.pdf', $p->documentos()->first()->nombre_original);
        $this->assertDatabaseHas('notificaciones', ['usuario_destino_id' => $ab->usuario_id, 'entidad_id' => $p->id]);

        $this->finalizar($p);
        $this->actingAs($u)->post(route('practicante.informe.subir'), ['informe' => UploadedFile::fake()->create('c.pdf', 20, 'application/pdf')])->assertSessionHasErrors('informe');
    }
}
