<?php

namespace Tests\Feature;

use App\Models\Abogado;
use App\Models\Consultorio;
use App\Models\Documento;
use App\Models\Region;
use App\Services\Almacenamiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\DatosPracticaB;
use Tests\TestCase;

class DocumentosAuditoriaTest extends TestCase
{
    use DatosPracticaB, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
        Storage::fake('local');
    }

    public function test_ver_y_descargar_documento_privado_registra_auditoria(): void
    {
        $admin = $this->usuario('administrador');
        $sede = Consultorio::first();
        $p = $this->practica($sede);

        $archivo = UploadedFile::fake()->create('cedula_frontal.pdf', 100, 'application/pdf');
        $guardado = Almacenamiento::guardar($archivo);

        $doc = Documento::create([
            'practica_id' => $p->id,
            'tipo' => 'cedula_frontal',
            'nombre_original' => $guardado['nombre_original'],
            'nombre_almacenado' => $guardado['nombre_almacenado'],
            'mime' => $guardado['mime'],
            'tamano_bytes' => $guardado['tamano_bytes'],
            'sha256' => $guardado['sha256'],
        ]);

        // 1. Ver (inline)
        $respVer = $this->actingAs($admin)->get(route('documentos.ver', $doc->id));
        $respVer->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $admin->id,
            'accion' => 'documento.ver',
            'entidad' => 'documento',
            'entidad_id' => $doc->id,
        ]);

        // 2. Descargar (attachment)
        $respDesc = $this->actingAs($admin)->get(route('documentos.descargar', $doc->id));
        $respDesc->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $admin->id,
            'accion' => 'documento.descargar',
            'entidad' => 'documento',
            'entidad_id' => $doc->id,
        ]);
    }

    public function test_descargar_expediente_zip_juridica_biobio_registra_auditoria(): void
    {
        $user = $this->usuario('juridica_central');
        $sede = Consultorio::first();
        $p = $this->practica($sede, 'finalizada');
        $p->update(['documentos_autorizados' => true, 'nota_practica' => 6.5]);
        $this->evaluar($p, true);

        $resp = $this->actingAs($user)->get(route('secretaria.juridica.expediente', $p->id));
        $resp->assertOk();

        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $user->id,
            'accion' => 'expediente.descargar_zip',
            'entidad' => 'practica',
            'entidad_id' => $p->id,
        ]);
    }

    public function test_descargar_expediente_zip_historial_registra_auditoria(): void
    {
        $region = Region::where('codigo', 'BI')->first();
        $user = $this->usuario('secretaria', ['region_id' => $region->id]);
        $sede = Consultorio::where('region_id', $region->id)->first();
        $p = $this->practica($sede, 'finalizada');
        $p->update(['documentos_autorizados' => true, 'nota_practica' => 6.0]);
        $this->evaluar($p, true);

        $resp = $this->actingAs($user)->get(route('secretaria.historial.expediente', $p->id));
        $resp->assertOk();

        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $user->id,
            'accion' => 'expediente.descargar_zip',
            'entidad' => 'practica',
            'entidad_id' => $p->id,
        ]);
    }

    public function test_descargar_certificado_y_resolucion_juridica_biobio_registra_auditoria(): void
    {
        $user = $this->usuario('juridica_central');
        $sede = Consultorio::first();
        $p = $this->practica($sede, 'finalizada');
        $p->update(['documentos_autorizados' => true, 'nota_practica' => 6.0]);
        $this->evaluar($p, true);

        // Certificado
        $respCert = $this->actingAs($user)->get(route('secretaria.juridica.certificado', $p->id));
        $respCert->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $user->id,
            'accion' => 'practica.descarga_certificado',
            'entidad' => 'practica',
            'entidad_id' => $p->id,
        ]);

        // Resolución
        $respRes = $this->actingAs($user)->get(route('secretaria.juridica.resolucion', $p->id));
        $respRes->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $user->id,
            'accion' => 'practica.descarga_resolucion',
            'entidad' => 'practica',
            'entidad_id' => $p->id,
        ]);
    }

    public function test_descargar_certificado_y_resolucion_historial_registra_auditoria(): void
    {
        $region = Region::where('codigo', 'BI')->first();
        $user = $this->usuario('secretaria', ['region_id' => $region->id]);
        $sede = Consultorio::where('region_id', $region->id)->first();
        $p = $this->practica($sede, 'finalizada');
        $p->update(['documentos_autorizados' => true, 'nota_practica' => 6.0]);
        $this->evaluar($p, true);

        // Certificado
        $respCert = $this->actingAs($user)->get(route('secretaria.historial.certificado', $p->id));
        $respCert->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $user->id,
            'accion' => 'practica.descarga_certificado',
            'entidad' => 'practica',
            'entidad_id' => $p->id,
        ]);

        // Resolución
        $respRes = $this->actingAs($user)->get(route('secretaria.historial.resolucion', $p->id));
        $respRes->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $user->id,
            'accion' => 'practica.descarga_resolucion',
            'entidad' => 'practica',
            'entidad_id' => $p->id,
        ]);
    }

    public function test_descargar_certificado_y_resolucion_director_general_registra_auditoria(): void
    {
        $user = $this->usuario('director_general');
        $sede = Consultorio::first();
        $p = $this->practica($sede, 'finalizada');
        $p->update(['documentos_autorizados' => true, 'nota_practica' => 6.0]);
        $this->evaluar($p, true);

        // Certificado
        $respCert = $this->actingAs($user)->get(route('director_general.certificado', $p->id));
        $respCert->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $user->id,
            'accion' => 'practica.descarga_certificado',
            'entidad' => 'practica',
            'entidad_id' => $p->id,
        ]);

        // Resolución
        $respRes = $this->actingAs($user)->get(route('director_general.resolucion', $p->id));
        $respRes->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $user->id,
            'accion' => 'practica.descarga_resolucion',
            'entidad' => 'practica',
            'entidad_id' => $p->id,
        ]);
    }

    public function test_descargar_lote_firmagob_director_general_registra_auditoria(): void
    {
        $user = $this->usuario('director_general');
        $sede = Consultorio::first();
        $p1 = $this->practica($sede, 'finalizada');
        $p1->update(['documentos_autorizados' => true, 'nota_practica' => 6.0]);
        $this->evaluar($p1, true);

        $p2 = $this->practica($sede, 'finalizada');
        $p2->update(['documentos_autorizados' => true, 'nota_practica' => 5.5]);
        $this->evaluar($p2, true);

        $resp = $this->actingAs($user)->post(route('director_general.descargar_lote'), [
            'practicas' => [$p1->id, $p2->id],
        ]);
        $resp->assertOk();

        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $user->id,
            'accion' => 'firmagob.descarga_lote',
            'entidad' => 'practica',
        ]);
    }

    public function test_carta_presentacion_tipo_y_firmada_registra_auditoria(): void
    {
        $region = Region::where('codigo', 'BI')->first();
        $userSec = $this->usuario('secretaria', ['region_id' => $region->id]);
        $sede = Consultorio::where('region_id', $region->id)->first();
        $abogadoUser = $this->usuario('abogado');
        $abogado = Abogado::create([
            'usuario_id' => $abogadoUser->id,
            'area' => 'civil',
            'activo' => true,
        ]);
        $sede->abogados()->syncWithoutDetaching([$abogado->id]);
        $p = $this->practica($sede, 'por_iniciar', $abogado);

        // 1. Descarga Carta tipo
        $respCartaTipo = $this->actingAs($userSec)->get(route('secretaria.practicas.carta_presentacion', $p->id));
        $respCartaTipo->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $userSec->id,
            'accion' => 'practica.descarga_carta_presentacion',
            'entidad' => 'practica',
            'entidad_id' => $p->id,
        ]);

        // Simular carta firmada subida
        $archivoFirmada = UploadedFile::fake()->create('carta_firmada.pdf', 100, 'application/pdf');
        $guardadoFirmada = Almacenamiento::guardar($archivoFirmada);
        $p->update(['carta_presentacion_firmada' => $guardadoFirmada['nombre_almacenado']]);

        // 2. Ver carta firmada por Secretaría
        $respVerSec = $this->actingAs($userSec)->get(route('secretaria.practicas.ver_carta_firmada', $p->id));
        $respVerSec->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $userSec->id,
            'accion' => 'practica.ver_carta_firmada',
            'entidad' => 'practica',
            'entidad_id' => $p->id,
        ]);

        // 3. Ver carta firmada por Tutor
        $respVerTutor = $this->actingAs($abogadoUser)->get(route('tutor.practicantes.carta_firmada', $p->id));
        $respVerTutor->assertOk();
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $abogadoUser->id,
            'accion' => 'practica.ver_carta_firmada',
            'entidad' => 'practica',
            'entidad_id' => $p->id,
        ]);
    }

    public function test_documento_cancelacion_secretaria_registra_auditoria(): void
    {
        $region = Region::where('codigo', 'BI')->first();
        $userSec = $this->usuario('secretaria', ['region_id' => $region->id]);
        $sede = Consultorio::where('region_id', $region->id)->first();
        $p = $this->practica($sede, 'cancelada');
        $p->update(['cancelacion_motivo' => 'conducta']);

        $resp = $this->actingAs($userSec)->get(route('secretaria.cancelacion.documento', $p->id));
        $resp->assertOk();

        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $userSec->id,
            'accion' => 'practica.descarga_documento_cancelacion',
            'entidad' => 'practica',
            'entidad_id' => $p->id,
        ]);
    }
}
