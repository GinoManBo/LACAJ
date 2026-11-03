<?php

namespace Tests\Feature;

use App\Models\Abogado;
use App\Models\Comuna;
use App\Models\Consultorio;
use App\Models\Evaluacion;
use App\Models\Postulacion;
use App\Models\Postulante;
use App\Models\Practica;
use App\Models\Practicante;
use App\Models\Region;
use App\Models\Usuario;
use App\Support\Notas;
use Database\Seeders\ReferenciasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretariaBiobioUnificadaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $secBiobio;

    private Usuario $secNuble;

    private Region $biobio;

    private Region $nuble;

    private Abogado $abogado;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(ReferenciasSeeder::class);

        $this->biobio = Region::where('codigo', 'BI')->first();
        $this->nuble = Region::where('codigo', 'NB')->first();

        $this->secBiobio = Usuario::create([
            'nombre' => 'Paulina',
            'apellido' => 'Herrera',
            'email' => 'sec.biobio@cajbiobio.cl',
            'rut' => '11111111-1',
            'rol' => 'secretaria',
            'region_id' => $this->biobio->id,
            'password' => 'secret123',
            'activo' => true,
        ]);

        $this->secNuble = Usuario::create([
            'nombre' => 'Loreto',
            'apellido' => 'Morales',
            'email' => 'sec.nuble@cajbiobio.cl',
            'rut' => '22222222-2',
            'rol' => 'secretaria',
            'region_id' => $this->nuble->id,
            'password' => 'secret123',
            'activo' => true,
        ]);

        $uAbogado = Usuario::create([
            'nombre' => 'Carlos',
            'apellido' => 'Tutor',
            'email' => 'tutor@cajbiobio.cl',
            'rut' => '12121212-1',
            'rol' => 'abogado',
            'password' => 'secret123',
            'activo' => true,
        ]);

        $this->abogado = Abogado::create([
            'usuario_id' => $uAbogado->id,
            'area' => 'civil',
        ]);
    }

    private function crearPractica(Region $region, string $rut, string $email, string $estado, array $datos = []): Practica
    {
        $comuna = Comuna::where('region_id', $region->id)->first();
        $consultorio = Consultorio::where('region_id', $region->id)->first();

        $postulante = Postulante::create([
            'rut' => $rut,
            'nombre' => 'NombreTest',
            'apellido' => 'ApellidoTest',
            'email' => $email,
            'telefono' => '+56911223344',
            'genero' => 'femenino',
            'nacionalidad' => 'Chilena',
            'region_id' => $region->id,
            'comuna_id' => $comuna->id,
            'direccion' => 'Calle 123',
            'universidad' => 'Universidad',
            'anio_carrera' => 'egresado',
            'emergencia_nombre' => 'Contacto',
            'emergencia_vinculo' => 'Familiar',
            'emergencia_telefono' => '+56999887766',
        ]);

        $postulacion = Postulacion::create([
            'postulante_id' => $postulante->id,
            'region_id' => $region->id,
            'estado' => 'aceptada',
            'fecha_postulacion' => now()->subMonths(7),
        ]);

        $practicante = Practicante::create([
            'postulante_id' => $postulante->id,
        ]);

        return Practica::create(array_merge([
            'practicante_id' => $practicante->id,
            'postulacion_id' => $postulacion->id,
            'consultorio_id' => $consultorio->id,
            'abogado_id' => $this->abogado->id,
            'estado' => $estado,
            'fecha_inicio' => today()->subMonths(6),
            'fecha_termino' => today(),
        ], $datos));
    }

    private function crearEvaluacion(Practica $practica, float $nota, bool $aprobado): Evaluacion
    {
        return Evaluacion::create(array_fill_keys(array_keys(Notas::CRITERIOS), $nota) + [
            'practica_id' => $practica->id,
            'abogado_id' => $this->abogado->id,
            'promedio' => $nota,
            'aprobado' => $aprobado,
        ]);
    }

    public function test_secretaria_biobio_accede_a_panel_con_indicadores_centrales(): void
    {
        $res = $this->actingAs($this->secBiobio)->get(route('secretaria.panel'));

        $res->assertOk();
        $res->assertSee('Panel de Secretaría · Sede Central Biobío');
        $res->assertSee('Atribuciones de Jurídica Central (Nacional)');
        $res->assertSee('Bandeja Nacional de Expedientes');
    }

    public function test_secretaria_externa_no_ve_indicadores_centrales_en_panel(): void
    {
        $res = $this->actingAs($this->secNuble)->get(route('secretaria.panel'));

        $res->assertOk();
        $res->assertDontSee('Atribuciones de Jurídica Central (Nacional)');
    }

    public function test_secretaria_biobio_accede_a_supervision_nacional_y_bandeja_expedientes(): void
    {
        $resPost = $this->actingAs($this->secBiobio)->get(route('secretaria.juridica.postulaciones'));
        $resPost->assertOk();

        $resExp = $this->actingAs($this->secBiobio)->get(route('secretaria.juridica.index'));
        $resExp->assertOk();
        $resExp->assertSee('Recepción Nacional de Expedientes');
    }

    public function test_secretaria_externa_tiene_acceso_denegado_a_juridica_central(): void
    {
        $resPost = $this->actingAs($this->secNuble)->get(route('secretaria.juridica.postulaciones'));
        $resPost->assertForbidden();

        $resExp = $this->actingAs($this->secNuble)->get(route('secretaria.juridica.index'));
        $resExp->assertForbidden();
    }

    public function test_secretaria_biobio_puede_nivelar_nota_en_juridica_central(): void
    {
        $practica = $this->crearPractica($this->nuble, '33333333-3', 'juan@test.cl', 'remitida_biobio', [
            'nota_practica' => 4.5,
            'remitida_at' => now(),
        ]);

        $this->crearEvaluacion($practica, 4.5, false);

        $res = $this->actingAs($this->secBiobio)->post(route('secretaria.juridica.nivelar', $practica->id), [
            'concepto' => 'BUENA',
            'justificacion' => 'Nivelación institucional de oficio conforme a normativa interna sin causales disciplinarias.',
        ]);

        $res->assertSessionHas('ok');
        $practica->refresh();
        $this->assertEquals('nivelada_biobio', $practica->estado);
        $this->assertEquals(5.0, (float) $practica->nota_practica);
    }

    public function test_remision_desde_region_externa_notifica_a_secretaria_de_biobio(): void
    {
        $practica = $this->crearPractica($this->nuble, '44444444-4', 'maria@test.cl', 'finalizada', [
            'nota_practica' => 6.0,
            'validada_director_at' => now(),
        ]);

        $this->crearEvaluacion($practica, 6.0, true);

        $res = $this->actingAs($this->secNuble)->post(route('secretaria.historial.remitir_biobio', $practica->id));
        $res->assertSessionHas('ok');

        $this->assertDatabaseHas('notificaciones', [
            'usuario_destino_id' => $this->secBiobio->id,
            'tipo' => 'aviso',
            'titulo' => 'Expediente remitido desde región',
        ]);
    }
}
