<?php

namespace Tests\Feature;

use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DatosPracticaB;
use Tests\TestCase;

class ExportacionesAuditoriaTest extends TestCase
{
    use DatosPracticaB, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_exportar_postulaciones_juridica_biobio_registra_auditoria(): void
    {
        $user = $this->usuario('juridica_central');
        $resp = $this->actingAs($user)->get(route('secretaria.juridica.postulaciones.exportar'));

        $resp->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $user->id,
            'accion' => 'postulacion.exportar_nacional',
            'entidad' => 'postulacion',
        ]);
    }

    public function test_exportar_expedientes_juridica_biobio_registra_auditoria(): void
    {
        $user = $this->usuario('juridica_central');
        $resp = $this->actingAs($user)->get(route('secretaria.juridica.exportar'));

        $resp->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $user->id,
            'accion' => 'expediente.exportar_excel',
            'entidad' => 'expediente',
        ]);
    }

    public function test_exportar_practicas_secretaria_registra_auditoria(): void
    {
        $user = $this->usuario('secretaria');
        $resp = $this->actingAs($user)->get(route('secretaria.practicas.exportar'));

        $resp->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $user->id,
            'accion' => 'practica.exportar_regional',
            'entidad' => 'practica',
        ]);
    }

    public function test_exportar_practicantes_admin_registra_auditoria(): void
    {
        $user = $this->usuario('administrador');
        $resp = $this->actingAs($user)->get(route('admin.practicantes.exportar'));

        $resp->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $user->id,
            'accion' => 'practica.exportar_nacional',
            'entidad' => 'practica',
        ]);
    }

    public function test_exportar_seguimiento_director_regional_registra_auditoria(): void
    {
        $region = Region::where('codigo', 'BI')->first();
        $user = $this->usuario('director_regional', ['region_id' => $region->id]);
        $resp = $this->actingAs($user)->get(route('director.seguimiento.exportar'));

        $resp->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $user->id,
            'accion' => 'seguimiento.exportar_regional',
            'entidad' => 'practica',
        ]);
    }

    public function test_exportar_seguimiento_director_general_registra_auditoria(): void
    {
        $user = $this->usuario('director_general');
        $resp = $this->actingAs($user)->get(route('director_general.seguimiento.exportar'));

        $resp->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $user->id,
            'accion' => 'seguimiento.exportar_nacional',
            'entidad' => 'practica',
        ]);
    }

    public function test_exportar_auditoria_admin_registra_auditoria(): void
    {
        $user = $this->usuario('administrador');
        $resp = $this->actingAs($user)->get(route('admin.auditoria.exportar'));

        $resp->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $user->id,
            'accion' => 'auditoria.exportar_excel',
            'entidad' => 'auditoria',
        ]);
    }

    public function test_exportar_estadisticas_admin_registra_auditoria(): void
    {
        $user = $this->usuario('administrador');
        $resp = $this->actingAs($user)->get(route('admin.estadisticas.exportar'));

        $resp->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $user->id,
            'accion' => 'estadisticas.exportar_excel',
            'entidad' => 'estadistica',
        ]);
    }
}
