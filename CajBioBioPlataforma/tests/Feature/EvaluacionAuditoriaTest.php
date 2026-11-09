<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Calificacion;
use App\Models\Practica;
use App\Services\EvaluacionesPractica;
use App\Services\InformesPractica;
use App\Support\Notas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreaPracticas;
use Tests\TestCase;

class EvaluacionAuditoriaTest extends TestCase
{
    use RefreshDatabase, CreaPracticas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
        Storage::fake('local');
    }

    public function test_evaluacion_primera_instancia_registra_auditoria_completa_con_7_criterios_antes_despues_y_actores(): void
    {
        $abogado = $this->crearAbogado();
        [$practica, $usuarioPracticante] = $this->crearPractica($abogado);

        // Habilitar la evaluación subiendo el informe de práctica
        InformesPractica::subir($practica, UploadedFile::fake()->create('informe_final.pdf', 50, 'application/pdf'), $usuarioPracticante);

        $postulante = $practica->practicante->postulante;

        // Enviar evaluación con los 7 criterios reglamentarios y observaciones
        $puntajesFormulario = [
            'conocimiento_juridico' => 'SOBRESALIENTE', // 7.0
            'responsabilidad' => 'SOBRESALIENTE',       // 7.0
            'iniciativa' => 'BUENA',                    // 5.0
            'sentido_social' => 'BUENA',                // 5.0
            'conducta' => 'SOBRESALIENTE',              // 7.0
            'honorabilidad' => 'SOBRESALIENTE',         // 7.0
            'asistencia_puntualidad' => 'BUENA',        // 5.0
            'observaciones' => 'Desempeño sobresaliente en causas de familia y asistencia jurídica comunitaria.',
        ];

        $response = $this->actingAs($abogado->usuario)
            ->post(route('tutor.evaluacion.guardar', $practica->id), $puntajesFormulario);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('tutor.practicantes.ver', $practica->id));

        // Verificar que existe el registro de auditoría
        $log = Auditoria::where('accion', 'practica.evaluar')
            ->where('entidad', 'practica')
            ->where('entidad_id', $practica->id)
            ->first();

        $this->assertNotNull($log, 'El evento practica.evaluar debe quedar registrado en la tabla auditoria.');

        // 1. Verificar QUIÉN LO HIZO (Actor)
        $this->assertEquals($abogado->usuario_id, $log->usuario_id);
        $this->assertEquals($abogado->usuario_id, $log->datos['actor']['usuario_id']);
        $this->assertEquals($abogado->id, $log->datos['actor']['abogado_id']);
        $this->assertNotEmpty($log->datos['actor']['nombre']);

        // 2. Verificar QUIÉN FUE AFECTADO (Postulante / Practicante)
        $this->assertEquals($practica->practicante_id, $log->datos['afectado']['practicante_id']);
        $this->assertEquals($postulante->id, $log->datos['afectado']['postulante_id']);
        $this->assertEquals($postulante->rut, $log->datos['afectado']['rut']);
        $this->assertEquals($postulante->nombre_completo, $log->datos['afectado']['nombre']);
        $this->assertEquals($postulante->rut, $log->datos['postulante_rut']);

        // 3. Verificar REGISTRO ANTES
        $this->assertArrayHasKey('antes', $log->datos);
        $this->assertEquals('activa', $log->datos['antes']['estado']);
        $this->assertNull($log->datos['antes']['nota_practica']);
        $this->assertNull($log->datos['antes']['evaluacion_id']);

        // 4. Verificar REGISTRO DESPUÉS
        $this->assertArrayHasKey('despues', $log->datos);
        $this->assertEquals('finalizada', $log->datos['despues']['estado']);
        $this->assertEquals(6.1, $log->datos['despues']['nota_practica']); // (7*4 + 5*3)/7 = 43/7 = 6.14 => 6.1
        $this->assertTrue($log->datos['despues']['aprobado']);
        $this->assertEquals('Sobresaliente', $log->datos['despues']['concepto']);
        $this->assertEquals('Desempeño sobresaliente en causas de familia y asistencia jurídica comunitaria.', $log->datos['despues']['observaciones']);

        // 5. Verificar TRAZABILIDAD DE LOS 7 CRITERIOS REGLAMENTARIOS
        $criterios = $log->datos['despues']['criterios'];
        $this->assertCount(7, $criterios);
        $this->assertEquals(7.0, $criterios['conocimiento_juridico']);
        $this->assertEquals(7.0, $criterios['responsabilidad']);
        $this->assertEquals(5.0, $criterios['iniciativa']);
        $this->assertEquals(5.0, $criterios['sentido_social']);
        $this->assertEquals(7.0, $criterios['conducta']);
        $this->assertEquals(7.0, $criterios['honorabilidad']);
        $this->assertEquals(5.0, $criterios['asistencia_puntualidad']);

        // Verificar desglose detallado con etiquetas institucionales y conceptos
        $detalle = $log->datos['despues']['criterios_detalle'];
        $this->assertCount(7, $detalle);
        $this->assertEquals('Conocimiento y criterio jurídico', $detalle['conocimiento_juridico']['etiqueta']);
        $this->assertEquals(7.0, $detalle['conocimiento_juridico']['nota']);
        $this->assertEquals('Sobresaliente', $detalle['conocimiento_juridico']['concepto']);
    }

    public function test_evaluacion_directa_service_registra_trazabilidad(): void
    {
        $abogado = $this->crearAbogado();
        [$practica, $usuarioPracticante] = $this->crearPractica($abogado);

        InformesPractica::subir($practica, UploadedFile::fake()->create('informe.pdf', 20, 'application/pdf'), $usuarioPracticante);

        $puntajes = [
            'conocimiento_juridico' => 4.0,
            'responsabilidad' => 4.0,
            'iniciativa' => 4.0,
            'sentido_social' => 4.0,
            'conducta' => 4.0,
            'honorabilidad' => 4.0,
            'asistencia_puntualidad' => 4.0,
        ];
        $observaciones = 'Cumplimiento adecuado.';

        $ev = EvaluacionesPractica::evaluar($practica, $abogado, $puntajes, $observaciones);
        $this->assertInstanceOf(\App\Models\Evaluacion::class, $ev);

        $log = Auditoria::where('accion', 'practica.evaluar')
            ->where('entidad_id', $practica->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals(4.0, $log->datos['despues']['promedio']);
        $this->assertEquals('Regular', $log->datos['despues']['concepto']);
        $this->assertTrue($log->datos['despues']['aprobado']);
        $this->assertEquals('Cumplimiento adecuado.', $log->datos['despues']['observaciones']);
        $this->assertEquals($abogado->usuario_id, $log->usuario_id);
    }

    public function test_evaluacion_segunda_instancia_incluye_afectado_y_observaciones(): void
    {
        $abogado2 = $this->crearAbogado();
        $abogado2->update(['es_segunda_instancia' => true, 'region_id' => \App\Models\Consultorio::first()->region_id]);

        [$practica] = $this->crearPractica($abogado2);

        $this->actingAs($abogado2->usuario)
            ->post(route('tutor.evaluacion.segunda_instancia', $practica->id), [
                'concepto' => 'SOBRESALIENTE',
                'observaciones' => 'Excelente argumentación en alegato ante la Corte de Apelaciones.',
            ])
            ->assertRedirect(route('tutor.practicantes.ver', $practica->id));

        $log = Auditoria::where('accion', 'practica.segunda_instancia_evaluar')
            ->where('entidad_id', $practica->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($abogado2->usuario_id, $log->usuario_id);
        $this->assertEquals('Sobresaliente', $log->datos['concepto']);
        $this->assertEquals(7.0, $log->datos['nota']);
        $this->assertEquals($practica->practicante->postulante->rut, $log->datos['afectado']['rut']);
        $this->assertEquals('Excelente argumentación en alegato ante la Corte de Apelaciones.', $log->datos['observaciones']);
    }
}
