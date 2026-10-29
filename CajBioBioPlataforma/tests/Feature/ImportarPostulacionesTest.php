<?php

namespace Tests\Feature;

use App\Models\Consultorio;
use App\Models\Postulacion;
use App\Models\Region;
use App\Models\Usuario;
use App\Services\ImportadorPostulaciones;
use Database\Seeders\ReferenciasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ImportarPostulacionesTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $sec;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(ReferenciasSeeder::class);
        $this->sec = Usuario::create([
            'nombre' => 'Sec', 'apellido' => 'Bi', 'email' => 'sec@test.cl', 'rol' => 'secretaria', 'password' => 'secreto123',
            'debe_cambiar_password' => false, 'activo' => true, 'region_id' => Region::where('codigo', 'BI')->value('id'),
        ]);
    }

    private function xlsx(array $filas, ?array $encabezado = null): UploadedFile
    {
        $ruta = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        $w = new Writer();
        $w->openToFile($ruta);
        $w->addRow(Row::fromValues($encabezado ?? ImportadorPostulaciones::COLUMNAS));
        foreach ($filas as $f) {
            $w->addRow(Row::fromValues($f));
        }
        $w->close();

        return new UploadedFile($ruta, 'postulaciones.xlsx', null, null, true);
    }

    public function test_importa_pendientes_en_la_region_de_la_secretaria(): void
    {
        $archivo = $this->xlsx([
            ['Ana Rojas Soto', 'U. de Concepción', '+56911112222', '03-10-2026', 'Ana@Mail.cl', '+56933334444'],
            ['Luis Pino', 'U. del Bío-Bío', '+56955556666', '', 'luis@mail.cl', ''],
        ]);

        $this->actingAs($this->sec)->post(route('secretaria.postulaciones.importar'), ['archivo' => $archivo])
            ->assertRedirect(route('secretaria.postulaciones'))->assertSessionHas('ok', '2 postulaciones importadas.');

        $p = Postulacion::with('postulante')->orderBy('id')->first();
        $this->assertSame('pendiente', $p->estado);
        $this->assertSame('excel', $p->origen);
        $this->assertSame($this->sec->region_id, $p->region_id);
        $this->assertSame('2026-10-03', $p->fecha_postulacion->toDateString());
        $this->assertSame('ana@mail.cl', $p->postulante->email);
        $this->assertSame('+56933334444', $p->postulante->emergencia_telefono);
        $this->assertNull($p->postulante->rut);
        $this->assertSame(2, Postulacion::count());

        $this->actingAs($this->sec)->get(route('secretaria.postulaciones'))->assertOk()->assertSee('Ana Rojas Soto');
        $this->actingAs($this->sec)->get(route('secretaria.postulaciones.ver', $p->id))->assertOk()->assertSee('Por completar');
    }

    public function test_omite_filas_invalidas_y_duplicadas_e_informa_el_motivo(): void
    {
        $archivo = $this->xlsx([
            ['Ana', 'U1', '', '', 'ana@mail.cl', ''],
            ['Ana otra vez', 'U1', '', '', 'ANA@mail.cl', ''],
            ['Sin correo', 'U1', '', '', '', ''],
            ['Fecha mala', 'U1', '', '31-02-2026', 'f@mail.cl', ''],
            ['', 'U1', '', '', 'x@mail.cl', ''],
        ]);

        $r = $this->actingAs($this->sec)->post(route('secretaria.postulaciones.importar'), ['archivo' => $archivo]);
        $r->assertSessionHas('ok', '1 postulación importada.');
        $omitidas = session('importacion_omitidas');
        $this->assertSame([3, 4, 5, 6], array_column($omitidas, 'fila'));
        $this->assertSame(1, Postulacion::count());
    }

    public function test_rechaza_archivo_con_columnas_distintas(): void
    {
        $archivo = $this->xlsx([['a', 'b', 'c', 'd', 'e', 'f']], ['Nombre', 'Correo', 'Telefono', 'Fecha', 'U', 'X']);
        $this->actingAs($this->sec)->post(route('secretaria.postulaciones.importar'), ['archivo' => $archivo])
            ->assertSessionHasErrorsIn('importar', 'archivo');
        $this->assertSame(0, Postulacion::count());
    }

    public function test_solo_acepta_xlsx_y_exige_sesion_de_secretaria(): void
    {
        $txt = UploadedFile::fake()->create('datos.csv', 1, 'text/csv');
        $this->actingAs($this->sec)->post(route('secretaria.postulaciones.importar'), ['archivo' => $txt])->assertSessionHasErrors('archivo');
        $this->post(route('secretaria.postulaciones.importar'), [])->assertRedirect();
        $this->assertSame(0, Postulacion::count());
    }

    public function test_no_se_puede_aceptar_una_importada_sin_rut(): void
    {
        $this->actingAs($this->sec)->post(route('secretaria.postulaciones.importar'), ['archivo' => $this->xlsx([['Ana', 'U1', '', '', 'ana@mail.cl', '']])]);
        $p = Postulacion::firstOrFail();

        $this->actingAs($this->sec)->post(route('secretaria.postulaciones.aceptar', $p->id), ['consultorio_id' => Consultorio::where('region_id', $p->region_id)->value('id')])
            ->assertSessionHasErrors('consultorio_id');
        $this->assertSame('pendiente', $p->fresh()->estado);
    }
}
