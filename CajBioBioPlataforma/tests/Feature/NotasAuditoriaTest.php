<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Consultorio;
use App\Models\Postulacion;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DatosPracticaB;
use Tests\TestCase;

class NotasAuditoriaTest extends TestCase
{
    use DatosPracticaB, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_crear_nota_interna_registra_auditoria(): void
    {
        $region = Region::where('codigo', 'BI')->first();
        $userSec = $this->usuario('secretaria', ['region_id' => $region->id]);
        $sede = Consultorio::where('region_id', $region->id)->first();
        $practica = $this->practica($sede);
        $postulacion = $practica->postulacion;

        $resp = $this->actingAs($userSec)->post(route('secretaria.postulaciones.nota.crear', $postulacion->id), [
            'texto' => 'Postulante solicitó iniciar el 15 de marzo por motivos académicos.',
        ]);

        $resp->assertRedirect();
        $resp->assertSessionHas('ok', 'Comentario agregado.');

        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $userSec->id,
            'accion' => 'postulacion.nota_creada',
            'entidad' => 'postulacion',
            'entidad_id' => $postulacion->id,
        ]);

        $registro = Auditoria::where('accion', 'postulacion.nota_creada')
            ->where('entidad_id', $postulacion->id)
            ->first();

        $this->assertNotNull($registro);
        $this->assertSame('Postulante solicitó iniciar el 15 de marzo por motivos académicos.', $registro->datos['texto']);
        $this->assertSame($postulacion->postulante->rut, $registro->datos['rut_postulante']);
    }

    public function test_eliminar_nota_interna_registra_auditoria_y_resguarda_texto_eliminado(): void
    {
        $region = Region::where('codigo', 'BI')->first();
        $userSec = $this->usuario('secretaria', ['region_id' => $region->id]);
        $sede = Consultorio::where('region_id', $region->id)->first();
        $practica = $this->practica($sede);
        $postulacion = $practica->postulacion;

        // Crear nota previa
        $nota = $postulacion->notas()->create([
            'autor_usuario_id' => $userSec->id,
            'texto' => 'Nota confidencial con acuerdos de turno que luego se cancelaron.',
        ]);

        $resp = $this->actingAs($userSec)->delete(route('secretaria.postulaciones.nota.eliminar', [$postulacion->id, $nota->id]));

        $resp->assertRedirect();
        $resp->assertSessionHas('ok', 'Comentario eliminado.');

        $this->assertDatabaseMissing('postulacion_notas', [
            'id' => $nota->id,
        ]);

        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $userSec->id,
            'accion' => 'postulacion.nota_eliminada',
            'entidad' => 'postulacion',
            'entidad_id' => $postulacion->id,
        ]);

        $registro = Auditoria::where('accion', 'postulacion.nota_eliminada')
            ->where('entidad_id', $postulacion->id)
            ->first();

        $this->assertNotNull($registro);
        $this->assertSame($nota->id, $registro->datos['nota_id']);
        $this->assertSame('Nota confidencial con acuerdos de turno que luego se cancelaron.', $registro->datos['texto_eliminado']);
        $this->assertSame($postulacion->postulante->rut, $registro->datos['rut_postulante']);
    }
}
