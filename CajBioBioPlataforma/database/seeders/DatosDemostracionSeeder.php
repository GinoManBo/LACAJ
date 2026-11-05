<?php

namespace Database\Seeders;

use App\Models\Abogado;
use App\Models\Calificacion;
use App\Models\Certificado;
use App\Models\Comuna;
use App\Models\Consultorio;
use App\Models\Documento;
use App\Models\Evaluacion;
use App\Models\Postulacion;
use App\Models\PostulacionPreferencia;
use App\Models\Postulante;
use App\Models\Practica;
use App\Models\Practicante;
use App\Models\Region;
use App\Models\Usuario;
use App\Services\CertificadosPractica;
use App\Support\Notas;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatosDemostracionSeeder extends Seeder
{
    private string $pdfDummy;

    public function __construct()
    {
        $this->pdfDummy = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>\nendobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \ntrailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n190\n%%EOF";
    }

    public function run(): void
    {
        $regBiobio = Region::where('codigo', 'BI')->firstOrFail();
        $regNuble = Region::where('codigo', 'NB')->firstOrFail();

        $comunaConce = Comuna::where('nombre', 'Concepción')->where('region_id', $regBiobio->id)->first()
            ?? Comuna::where('region_id', $regBiobio->id)->firstOrFail();
        $comunaChillan = Comuna::where('nombre', 'Chillán')->where('region_id', $regNuble->id)->first()
            ?? Comuna::where('region_id', $regNuble->id)->firstOrFail();

        $cCentro = Consultorio::where('nombre', 'like', '%Centro — Concepción%')->first()
            ?? Consultorio::where('region_id', $regBiobio->id)->firstOrFail();
        $cTalcahuano = Consultorio::where('nombre', 'like', '%Talcahuano%')->first() ?? $cCentro;
        $cChillan = Consultorio::where('nombre', 'like', '%Chillán%')->first()
            ?? Consultorio::where('region_id', $regNuble->id)->firstOrFail();

        // 1. Asignar Consultorios a Abogados Jefes
        $uAbogado1 = Usuario::where('email', 'abogado1@cajbiobio.cl')->first();
        if ($uAbogado1 && $uAbogado1->abogado) {
            $uAbogado1->abogado->consultorios()->syncWithoutDetaching([$cCentro->id, $cTalcahuano->id]);
        }

        $uAbogado2 = Usuario::where('email', 'abogado2@cajbiobio.cl')->first();
        if ($uAbogado2 && $uAbogado2->abogado) {
            $uAbogado2->abogado->consultorios()->syncWithoutDetaching([$cCentro->id]);
        }

        $uCorte = Usuario::where('email', 'corte_biobio@cajbiobio.cl')->first();
        $uDirectorGeneral = Usuario::where('email', 'director_general@cajbiobio.cl')->first();
        $uSecretariaBiobio = Usuario::where('email', 'secretaria1@cajbiobio.cl')->first();

        // 2. Usuario con rol 'juridica_central' para pruebas de compatibilidad exclusiva
        Usuario::updateOrCreate(['email' => 'juridica.central@cajbiobio.cl'], [
            'nombre' => 'Verónica',
            'apellido' => 'Sanhueza (Jurídica)',
            'rut' => '10000037-7',
            'rol' => 'juridica_central',
            'region_id' => $regBiobio->id,
            'password' => '123456',
            'debe_cambiar_password' => false,
            'activo' => true,
        ]);

        // =========================================================================
        // CASO 1: Postulante Nuevo (Fase 1 - Postulación Pendiente de Revisión)
        // =========================================================================
        $this->crearPostulante(
            nombre: 'Camilo',
            apellido: 'Echeverría Solís',
            email: 'postulante@cajbiobio.cl',
            rut: '10000030-K',
            universidad: 'Universidad de Concepción',
            region: $regBiobio,
            comuna: $comunaConce,
            telefono: '+56987654321',
            configurar: function (Usuario $user, Postulante $postulante) use ($regBiobio, $cCentro, $cTalcahuano) {
                $postulacion = Postulacion::updateOrCreate(
                    ['postulante_id' => $postulante->id],
                    [
                        'estado' => 'pendiente',
                        'region_id' => $regBiobio->id,
                        'fecha_postulacion' => now()->subDays(3),
                        'fecha_inicio_solicitada' => now()->addDays(15),
                    ]
                );

                PostulacionPreferencia::updateOrCreate(['postulacion_id' => $postulacion->id, 'orden' => 1], ['consultorio_id' => $cCentro->id]);
                PostulacionPreferencia::updateOrCreate(['postulacion_id' => $postulacion->id, 'orden' => 2], ['consultorio_id' => $cTalcahuano->id]);

                $this->adjuntarDocumento($postulacion->id, null, 'cedula_frontal', 'cedula_frontal.pdf', $user->id);
                $this->adjuntarDocumento($postulacion->id, null, 'cedula_reverso', 'cedula_reverso.pdf', $user->id);
                $this->adjuntarDocumento($postulacion->id, null, 'certificado_egreso', 'certificado_egreso.pdf', $user->id);
                $this->adjuntarDocumento($postulacion->id, null, 'certificado_antecedentes', 'certificado_antecedentes.pdf', $user->id);
            },
            genero: 'masculino',
            fechaNacimiento: '1998-03-12'
        );

        // =========================================================================
        // CASO 2: Practicante Activo (Fase 2 - En Curso Normal ~4 meses)
        // =========================================================================
        $this->crearPostulante(
            nombre: 'Valentina',
            apellido: 'Morales Riquelme',
            email: 'practicante.activo@cajbiobio.cl',
            rut: '10000031-8',
            universidad: 'Universidad del Bío-Bío',
            region: $regBiobio,
            comuna: $comunaConce,
            telefono: '+56976543210',
            configurar: function (Usuario $user, Postulante $postulante) use ($regBiobio, $cCentro, $uAbogado1) {
                $postulacion = Postulacion::updateOrCreate(
                    ['postulante_id' => $postulante->id],
                    [
                        'estado' => 'aceptada',
                        'region_id' => $regBiobio->id,
                        'fecha_postulacion' => now()->subMonths(5),
                        'fecha_inicio_solicitada' => now()->subMonths(4),
                        'consultorio_asignado_id' => $cCentro->id,
                        'resuelta_at' => now()->subMonths(4),
                    ]
                );

                $practicante = Practicante::updateOrCreate(['postulante_id' => $postulante->id], ['usuario_id' => $user->id]);

                $docCarta = $this->adjuntarDocumento($postulacion->id, null, 'carta_presentacion_firmada', 'carta_presentacion_firmada.pdf', $uAbogado1?->id);

                $practica = Practica::updateOrCreate(
                    ['practicante_id' => $practicante->id],
                    [
                        'postulacion_id' => $postulacion->id,
                        'consultorio_id' => $cCentro->id,
                        'abogado_id' => $uAbogado1?->abogado?->id,
                        'estado' => 'activa',
                        'fecha_inicio' => today()->subMonths(4),
                        'fecha_termino' => today()->subMonths(4)->addMonths(6),
                        'carta_presentacion_firmada' => $docCarta->nombre_almacenado,
                        'carta_presentacion_subida_at' => now()->subMonths(4),
                    ]
                );

                $docCarta->update(['practica_id' => $practica->id]);
            },
            genero: 'femenino',
            fechaNacimiento: '1997-11-20'
        );

        // =========================================================================
        // CASO 3: Practicante Por Terminar con Informe Final Cargado (Listo para Evaluar)
        // =========================================================================
        $this->crearPostulante(
            nombre: 'Matías',
            apellido: 'Salgado Vega',
            email: 'practicante.termino@cajbiobio.cl',
            rut: '10000032-6',
            universidad: 'Universidad Católica de la Santísima Concepción',
            region: $regBiobio,
            comuna: $comunaConce,
            telefono: '+56965432109',
            configurar: function (Usuario $user, Postulante $postulante) use ($regBiobio, $cCentro, $uAbogado1) {
                $postulacion = Postulacion::updateOrCreate(
                    ['postulante_id' => $postulante->id],
                    [
                        'estado' => 'aceptada',
                        'region_id' => $regBiobio->id,
                        'fecha_postulacion' => now()->subMonths(7),
                        'consultorio_asignado_id' => $cCentro->id,
                    ]
                );

                $practicante = Practicante::updateOrCreate(['postulante_id' => $postulante->id], ['usuario_id' => $user->id]);

                $practica = Practica::updateOrCreate(
                    ['practicante_id' => $practicante->id],
                    [
                        'postulacion_id' => $postulacion->id,
                        'consultorio_id' => $cCentro->id,
                        'abogado_id' => $uAbogado1?->abogado?->id,
                        'estado' => 'activa',
                        'fecha_inicio' => today()->subMonths(6)->addDays(5),
                        'fecha_termino' => today()->addDays(5), // Alerta activa (< 14 días)
                    ]
                );

                $this->adjuntarDocumento($postulacion->id, $practica->id, 'informe_final', 'Informe_Final_Practica_Matias_Salgado.pdf', $user->id);
            },
            genero: 'masculino',
            fechaNacimiento: '1998-07-04'
        );

        // =========================================================================
        // CASO 4: Segunda Instancia en Corte de Apelaciones (Para Abogado de Corte)
        // =========================================================================
        $this->crearPostulante(
            nombre: 'Sofía',
            apellido: 'Henríquez Alarcón',
            email: 'postulante.corte@cajbiobio.cl',
            rut: '10000033-4',
            universidad: 'Universidad de Concepción',
            region: $regBiobio,
            comuna: $comunaConce,
            telefono: '+56954321098',
            configurar: function (Usuario $user, Postulante $postulante) use ($regBiobio, $cTalcahuano, $uAbogado1, $uCorte) {
                $postulacion = Postulacion::updateOrCreate(
                    ['postulante_id' => $postulante->id],
                    [
                        'estado' => 'aceptada',
                        'region_id' => $regBiobio->id,
                        'fecha_postulacion' => now()->subMonths(8),
                        'fecha_inicio_solicitada' => now()->subMonths(7),
                        'consultorio_asignado_id' => $cTalcahuano->id,
                    ]
                );

                $practicante = Practicante::updateOrCreate(['postulante_id' => $postulante->id], ['usuario_id' => $user->id]);

                $practica = Practica::updateOrCreate(
                    ['practicante_id' => $practicante->id],
                    [
                        'postulacion_id' => $postulacion->id,
                        'consultorio_id' => $cTalcahuano->id,
                        'abogado_id' => $uAbogado1?->abogado?->id,
                        'estado' => 'finalizada',
                        'fecha_inicio' => today()->subMonths(7),
                        'fecha_termino' => today()->subDays(10),
                        'nota_practica' => 5.2,
                        'abogado_segunda_instancia_id' => $uCorte?->abogado?->id,
                    ]
                );

                Evaluacion::updateOrCreate(
                    ['practica_id' => $practica->id],
                    array_fill_keys(array_keys(Notas::CRITERIOS), 5.2) + [
                        'abogado_id' => $uAbogado1?->abogado?->id ?? 1,
                        'promedio' => 5.2,
                        'aprobado' => true,
                        'observaciones' => 'Desempeño adecuado en consultorio. Requiere evaluación complementaria de alegatos en Corte.',
                    ]
                );

                Calificacion::updateOrCreate(
                    ['practica_id' => $practica->id, 'instancia' => 1],
                    [
                        'nota' => 5.2,
                        'fecha' => today()->subDays(10),
                        'registrada_por' => $uAbogado1?->id ?? 1,
                        'observaciones' => 'Primera instancia en Consultorio Talcahuano.',
                    ]
                );

                $this->adjuntarDocumento($postulacion->id, $practica->id, 'informe_final', 'Informe_Sofia_Henriquez.pdf', $user->id);
            },
            genero: 'femenino',
            fechaNacimiento: '1999-01-18'
        );

        // =========================================================================
        // CASO 5: Remitido desde Región Externa (Para Nivelación en Jurídica Biobío)
        // =========================================================================
        $this->crearPostulante(
            nombre: 'Gabriel',
            apellido: 'Navarro Sepúlveda',
            email: 'postulante.nuble@cajbiobio.cl',
            rut: '10000034-2',
            universidad: 'Universidad de Concepción (Campus Chillán)',
            region: $regNuble,
            comuna: $comunaChillan,
            telefono: '+56943210987',
            configurar: function (Usuario $user, Postulante $postulante) use ($regNuble, $cChillan, $uAbogado1, $uSecretariaBiobio) {
                $postulacion = Postulacion::updateOrCreate(
                    ['postulante_id' => $postulante->id],
                    [
                        'estado' => 'aceptada',
                        'region_id' => $regNuble->id,
                        'fecha_postulacion' => now()->subMonths(8),
                        'fecha_inicio_solicitada' => now()->subMonths(7),
                        'consultorio_asignado_id' => $cChillan->id,
                    ]
                );

                $practicante = Practicante::updateOrCreate(['postulante_id' => $postulante->id], ['usuario_id' => $user->id]);

                $practica = Practica::updateOrCreate(
                    ['practicante_id' => $practicante->id],
                    [
                        'postulacion_id' => $postulacion->id,
                        'consultorio_id' => $cChillan->id,
                        'abogado_id' => $uAbogado1?->abogado?->id,
                        'estado' => 'remitida_biobio',
                        'fecha_inicio' => today()->subMonths(7),
                        'fecha_termino' => today()->subDays(20),
                        'nota_practica' => 4.6, // Calificación "Regular", lista para subir a Buena (5.0)
                        'validada_director_at' => now()->subDays(5),
                        'remitida_at' => now()->subDays(2),
                        'remitida_por' => $uSecretariaBiobio?->id,
                    ]
                );

                Evaluacion::updateOrCreate(
                    ['practica_id' => $practica->id],
                    array_fill_keys(array_keys(Notas::CRITERIOS), 4.6) + [
                        'abogado_id' => $uAbogado1?->abogado?->id ?? 1,
                        'promedio' => 4.6,
                        'aprobado' => false,
                        'observaciones' => 'Desempeño regular sin faltas graves.',
                    ]
                );

                Calificacion::updateOrCreate(
                    ['practica_id' => $practica->id, 'instancia' => 1],
                    [
                        'nota' => 4.6,
                        'fecha' => today()->subDays(20),
                        'registrada_por' => $uAbogado1?->id ?? 1,
                        'observaciones' => 'Evaluación de 1ª instancia en Consultorio Chillán.',
                    ]
                );

                $this->adjuntarDocumento($postulacion->id, $practica->id, 'informe_final', 'Informe_Gabriel_Navarro.pdf', $user->id);
            },
            genero: 'masculino',
            fechaNacimiento: '1997-09-09'
        );

        // =========================================================================
        // CASO 6: Expediente Nivelado Listo para FirmaGob (Director General)
        // =========================================================================
        $this->crearPostulante(
            nombre: 'Javiera',
            apellido: 'Cárdenas Pino',
            email: 'postulante.firmagob@cajbiobio.cl',
            rut: '10000035-0',
            universidad: 'Universidad del Desarrollo',
            region: $regBiobio,
            comuna: $comunaConce,
            telefono: '+56932109876',
            configurar: function (Usuario $user, Postulante $postulante) use ($regBiobio, $cCentro, $cTalcahuano, $uAbogado1, $uSecretariaBiobio) {
                $postulacion = Postulacion::updateOrCreate(
                    ['postulante_id' => $postulante->id],
                    [
                        'estado' => 'aceptada',
                        'region_id' => $regBiobio->id,
                        'fecha_postulacion' => now()->subMonths(8),
                        'fecha_inicio_solicitada' => now()->subMonths(7),
                        'consultorio_asignado_id' => $cTalcahuano->id,
                    ]
                );

                $practicante = Practicante::updateOrCreate(['postulante_id' => $postulante->id], ['usuario_id' => $user->id]);

                $practica = Practica::updateOrCreate(
                    ['practicante_id' => $practicante->id],
                    [
                        'postulacion_id' => $postulacion->id,
                        'consultorio_id' => $cCentro->id,
                        'abogado_id' => $uAbogado1?->abogado?->id,
                        'estado' => 'nivelada_biobio',
                        'fecha_inicio' => today()->subMonths(7),
                        'fecha_termino' => today()->subDays(15),
                        'nota_practica' => 5.5, // Muy Buena
                        'validada_director_at' => now()->subDays(4),
                        'nivelada_at' => now()->subDays(2),
                        'nivelada_por' => $uSecretariaBiobio?->id,
                        'justificacion_nivelacion' => 'Calificación institucional fijada en «Muy Buena» conforme al rendimiento destacado.',
                    ]
                );

                Evaluacion::updateOrCreate(
                    ['practica_id' => $practica->id],
                    array_fill_keys(array_keys(Notas::CRITERIOS), 5.5) + [
                        'abogado_id' => $uAbogado1?->abogado?->id ?? 1,
                        'promedio' => 5.5,
                        'aprobado' => true,
                    ]
                );

                Calificacion::updateOrCreate(
                    ['practica_id' => $practica->id, 'instancia' => 3],
                    [
                        'nota' => 5.5,
                        'fecha' => today()->subDays(2),
                        'registrada_por' => $uSecretariaBiobio?->id ?? 1,
                        'observaciones' => 'Fijación formal por Secretaría Jurídica Central.',
                    ]
                );

                $this->adjuntarDocumento($postulacion->id, $practica->id, 'informe_final', 'Informe_Javiera_Cardenas.pdf', $user->id);
            },
            genero: 'femenino',
            fechaNacimiento: '1998-12-03'
        );

        // =========================================================================
        // CASO 7: Expediente Cerrado y Despachado (Postulante Titulado con Descarga de Certificados)
        // =========================================================================
        $this->crearPostulante(
            nombre: 'Rodrigo',
            apellido: 'Sepúlveda Castro',
            email: 'postulante.titulado@cajbiobio.cl',
            rut: '10000036-9',
            universidad: 'Universidad de Concepción',
            region: $regBiobio,
            comuna: $comunaConce,
            telefono: '+56921098765',
            configurar: function (Usuario $user, Postulante $postulante) use ($regBiobio, $cCentro, $cTalcahuano, $uAbogado1, $uDirectorGeneral, $uSecretariaBiobio) {
                $postulacion = Postulacion::updateOrCreate(
                    ['postulante_id' => $postulante->id],
                    [
                        'estado' => 'aceptada',
                        'region_id' => $regBiobio->id,
                        'fecha_postulacion' => now()->subMonths(8),
                        'fecha_inicio_solicitada' => now()->subMonths(7),
                        'consultorio_asignado_id' => $cTalcahuano->id,
                    ]
                );

                $practicante = Practicante::updateOrCreate(['postulante_id' => $postulante->id], ['usuario_id' => $user->id]);

                $docCertificado = $this->adjuntarDocumento($postulacion->id, null, 'certificado_firmagob', 'Certificado_Corte_Suprema_Firmado.pdf', $uDirectorGeneral?->id);

                $practica = Practica::updateOrCreate(
                    ['practicante_id' => $practicante->id],
                    [
                        'postulacion_id' => $postulacion->id,
                        'consultorio_id' => $cCentro->id,
                        'abogado_id' => $uAbogado1?->abogado?->id,
                        'estado' => 'cerrada_historial',
                        'fecha_inicio' => today()->subMonths(8),
                        'fecha_termino' => today()->subMonths(2),
                        'nota_practica' => 6.2, // Muy Buena
                        'validada_director_at' => now()->subMonths(1),
                        'nivelada_at' => now()->subDays(25),
                        'firmado_director_general_at' => now()->subDays(10),
                        'firmado_director_general_por' => $uDirectorGeneral?->id,
                        'firmagob_folio' => 'FG-2026-BIOBIO-0042',
                        'firmagob_url' => 'https://validador.firmagob.gob.cl/validar?folio=FG-2026-BIOBIO-0042',
                        'certificado_firmagob_id' => $docCertificado->id,
                        'documentos_autorizados' => true,
                        'documentos_autorizados_at' => now()->subDays(5),
                        'despachada_at' => now()->subDays(5),
                        'despachada_por' => $uSecretariaBiobio?->id,
                    ]
                );

                $docCertificado->update(['practica_id' => $practica->id]);

                Certificado::firstOrCreate(
                    ['practica_id' => $practica->id, 'tipo' => CertificadosPractica::CERTIFICADO],
                    [
                        'numero_resolucion' => 101,
                        'anio' => (int) date('Y'),
                        'documento_id' => $docCertificado->id,
                        'fecha_emision' => now()->subDays(5)->toDateString(),
                        'emitido_por' => $uDirectorGeneral?->id,
                    ]
                );

                Certificado::firstOrCreate(
                    ['practica_id' => $practica->id, 'tipo' => CertificadosPractica::RESOLUCION],
                    [
                        'numero_resolucion' => 101,
                        'anio' => (int) date('Y'),
                        'fecha_emision' => now()->subDays(5)->toDateString(),
                        'emitido_por' => $uDirectorGeneral?->id,
                    ]
                );

                Evaluacion::updateOrCreate(
                    ['practica_id' => $practica->id],
                    array_fill_keys(array_keys(Notas::CRITERIOS), 6.2) + [
                        'abogado_id' => $uAbogado1?->abogado?->id ?? 1,
                        'promedio' => 6.2,
                        'aprobado' => true,
                    ]
                );

                $this->adjuntarDocumento($postulacion->id, $practica->id, 'informe_final', 'Informe_Rodrigo_Sepulveda.pdf', $user->id);
            },
            genero: 'masculino',
            fechaNacimiento: '1996-04-25'
        );
    }

    private function crearPostulante(
        string $nombre,
        string $apellido,
        string $email,
        string $rut,
        string $universidad,
        Region $region,
        Comuna $comuna,
        string $telefono,
        callable $configurar,
        string $genero = 'masculino',
        ?string $fechaNacimiento = '1998-05-15'
    ): void {
        $user = Usuario::updateOrCreate(['email' => $email], [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'rut' => $rut,
            'rol' => 'postulante',
            'region_id' => $region->id,
            'password' => '123456',
            'debe_cambiar_password' => false,
            'activo' => true,
        ]);

        $postulante = Postulante::where('email', $email)->orWhere('rut', $rut)->first() ?? new Postulante();
        $postulante->fill([
            'rut' => $rut,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'fecha_nacimiento' => $fechaNacimiento,
            'email' => $email,
            'telefono' => $telefono,
            'genero' => $genero,
            'nacionalidad' => 'Chilena',
            'region_id' => $region->id,
            'comuna_id' => $comuna->id,
            'direccion' => 'Calle Los Aromos 123',
            'universidad' => $universidad,
            'anio_carrera' => 'egresado',
            'emergencia_nombre' => 'Contacto de Emergencia',
            'emergencia_vinculo' => 'Familiar',
            'emergencia_telefono' => '+56911223344',
        ]);
        $postulante->save();

        $configurar($user, $postulante);
    }

    private function adjuntarDocumento(
        ?int $postulacionId,
        ?int $practicaId,
        string $tipo,
        string $nombreOriginal,
        ?int $subidoPorId
    ): Documento {
        $uuid = (string) Str::uuid();

        // Almacenar archivo dummy en el disco privado local para que las descargas operen con éxito
        Storage::disk('local')->put('documentos/' . $uuid, $this->pdfDummy);

        return Documento::updateOrCreate(
            [
                'postulacion_id' => $postulacionId,
                'practica_id' => $practicaId,
                'tipo' => $tipo,
            ],
            [
                'nombre_original' => $nombreOriginal,
                'nombre_almacenado' => $uuid,
                'mime' => 'application/pdf',
                'tamano_bytes' => strlen($this->pdfDummy),
                'sha256' => hash('sha256', $this->pdfDummy),
                'subido_por' => $subidoPorId,
            ]
        );
    }
}
