<?php

namespace App\Services;

use App\Models\Certificado;
use App\Models\Practica;
use App\Models\Usuario;
use App\Support\Notas;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class CertificadosPractica
{
    public const CERTIFICADO = 'certificado_corte_suprema';
    public const RESOLUCION = 'resolucion';

    public static function bloqueo(Practica $p): ?string
    {
        if ($p->estado === 'cancelada') {
            return 'La práctica fue cancelada.';
        }
        if ($p->estado === 'rechazada_sancion') {
            return 'La práctica fue rechazada por causal disciplinaria fundada.';
        }
        if (! in_array($p->estado, ['finalizada', 'remitida_biobio', 'nivelada_biobio', 'cerrada_historial'], true)) {
            return 'La práctica aún no está finalizada (estado actual: '.str_replace('_', ' ', $p->estado).').';
        }
        $ev = $p->evaluacion;
        if (! $ev) {
            return 'La práctica aún no tiene la evaluación final del abogado tutor.';
        }
        $estaAprobada = $p->nota_practica !== null ? Notas::esAprobatoria($p->nota_practica) : (bool) $ev->aprobado;
        if (! $estaAprobada) {
            return 'La práctica no fue aprobada, por lo que no corresponde emitir documentos.';
        }
        if (! $p->documentos_autorizados) {
            return 'Los documentos están a la espera de la suscripción formal del Director General con FirmaGob.';
        }

        return null;
    }

    public static function pdfCertificado(Practica $p, Usuario $emisor): string
    {
        if ($p->certificado_firmagob_id && $p->certificadoFirmagob && Almacenamiento::existe($p->certificadoFirmagob->nombre_almacenado)) {
            return file_get_contents(Almacenamiento::ruta($p->certificadoFirmagob->nombre_almacenado));
        }

        return DB::transaction(function () use ($p, $emisor) {
            Certificado::firstOrCreate(
                ['practica_id' => $p->id, 'tipo' => self::CERTIFICADO],
                ['fecha_emision' => now()->toDateString(), 'emitido_por' => $emisor->id],
            );

            return Pdf::loadView('pdf.certificado', self::datos($p))->setPaper('a4')->output();
        });
    }

    public static function pdfResolucion(Practica $p, Usuario $emisor): string
    {
        return DB::transaction(function () use ($p, $emisor) {
            $cert = Certificado::where('practica_id', $p->id)->where('tipo', self::RESOLUCION)->lockForUpdate()->first();

            if (! $cert) {
                $anio = (int) ($p->resolucion_anio ?: now()->year);
                $numero = $p->resolucion_numero;

                if (! $numero) {
                    DB::table('resolucion_secuencia')->insertOrIgnore(['anio' => $anio, 'ultimo_numero' => 0]);
                    $sec = DB::table('resolucion_secuencia')->where('anio', $anio)->lockForUpdate()->first();
                    $numero = $sec->ultimo_numero + 1;
                    DB::table('resolucion_secuencia')->where('anio', $anio)->update(['ultimo_numero' => $numero]);
                }

                $cert = Certificado::create([
                    'practica_id' => $p->id,
                    'tipo' => self::RESOLUCION,
                    'numero_resolucion' => (int) preg_replace('/\D/', '', (string) $numero),
                    'anio' => $anio,
                    'fecha_emision' => now()->toDateString(),
                    'emitido_por' => $emisor->id,
                ]);
            } else {
                if ($p->resolucion_numero) {
                    $numEntero = (int) preg_replace('/\D/', '', (string) $p->resolucion_numero);
                    $anioNuevo = (int) ($p->resolucion_anio ?: $cert->anio);
                    if ($cert->numero_resolucion !== $numEntero || $cert->anio !== $anioNuevo) {
                        $cert->update([
                            'numero_resolucion' => $numEntero,
                            'anio' => $anioNuevo,
                        ]);
                    }
                }
            }

            return Pdf::loadView('pdf.resolucion', self::datos($p) + ['certificado' => $cert])->setPaper('a4')->output();
        });
    }

    /** @return array<string,mixed> */
    private static function datos(Practica $p): array
    {
        $p->loadMissing(['practicante.postulante.comuna', 'consultorio.comuna', 'consultorio.region', 'evaluacion']);
        $post = $p->practicante->postulante;
        $femenino = $post->genero === 'femenino';

        $inst = config('caj_documentos');
        $dirGeneral = Usuario::where('rol', 'director_general')->where('activo', true)->first();
        if ($dirGeneral) {
            $inst['director_nombre'] = mb_strtoupper($dirGeneral->nombre.' '.$dirGeneral->apellido);
        }

        $directorRegional = $p->consultorio?->region_id
            ? Usuario::whereIn('rol', ['director_regional', 'jefe_ucoe'])
                ->where('region_id', $p->consultorio->region_id)
                ->where('activo', true)
                ->first()
            : null;

        $cargoRegional = null;
        if ($directorRegional) {
            $cargoRegional = $directorRegional->rol === 'jefe_ucoe' || $p->consultorio?->region?->codigo === 'BI'
                ? 'Jefe de la Unidad de Consultorios y Oficinas Especializadas (UCOE)'
                : 'Director Regional de '.($p->consultorio?->region?->nombre ?? 'la Región');
        }

        return [
            'practica' => $p,
            'postulante' => $post,
            'nombreLegal' => mb_strtoupper($post->nombre_completo),
            'femenino' => $femenino,
            'titulo' => $femenino ? 'Abogada' : 'Abogado',
            'rut' => \App\Support\Rut::formatear($post->rut),
            'evaluacion' => $p->evaluacion,
            'criterios' => Notas::CRITERIOS,
            'logo' => 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('img/logo-caj.png'))),
            'firma' => self::firmaBase64($p),
            'inst' => $inst,
            'director_regional' => $directorRegional,
            'director_regional_cargo' => $cargoRegional,
            'ucoe_cargo_oficial' => 'Jefe de la Unidad de Consultorios y Oficinas Especializadas (UCOE)',
            'hoy' => now(),
        ];
    }

    public static function zipExpediente(Practica $p, Usuario $emisor): string
    {
        $p->loadMissing(['practicante.postulante', 'certificadoFirmagob']);
        $rut = $p->practicante?->postulante?->rut ?? 'postulante';
        $pdfCert = self::pdfCertificado($p, $emisor);
        $pdfRes = self::pdfResolucion($p, $emisor);

        $zipPath = tempnam(sys_get_temp_dir(), 'expediente_').'.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString("Certificado_Corte_Suprema_{$rut}.pdf", $pdfCert);
        $zip->addFromString("Resolucion_Aprobacion_{$rut}.pdf", $pdfRes);
        $zip->close();

        return $zipPath;
    }

    private static function firmaBase64(Practica $p): ?string
    {
        $u = ($p->firmado_director_general_por ? Usuario::find($p->firmado_director_general_por) : null)
            ?? ($p->documentos_autorizados_por ? Usuario::find($p->documentos_autorizados_por) : null)
            ?? Usuario::where('rol', 'director_general')->whereNotNull('firma_nombre_almacenado')->first()
            ?? Usuario::where('rol', 'gobernador')->whereNotNull('firma_nombre_almacenado')->first();
        if (! $u || ! $u->firma_nombre_almacenado || ! Almacenamiento::existe($u->firma_nombre_almacenado)) {
            return null;
        }
        $mime = $u->firma_mime ?: 'image/png';

        return "data:{$mime};base64,".base64_encode(file_get_contents(Almacenamiento::ruta($u->firma_nombre_almacenado)));
    }
}
