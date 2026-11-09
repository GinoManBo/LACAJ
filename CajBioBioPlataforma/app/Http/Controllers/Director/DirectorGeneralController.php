<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Certificado;
use App\Models\Consultorio;
use App\Models\Documento;
use App\Models\Practica;
use App\Models\Region;
use App\Models\Usuario;
use App\Services\Almacenamiento;
use App\Services\Auditoria;
use App\Services\CertificadosPractica;
use App\Services\InformesPractica;
use App\Services\Notificaciones;
use App\Services\Practicas;
use App\Support\Notas;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use ZipArchive;

class DirectorGeneralController extends Controller
{
    /**
     * Valida permisos para el Director General (o Administrador del sistema).
     */
    private function autorizar(Request $request): void
    {
        $u = $request->user();
        if (! $u) {
            abort(403);
        }

        if ($u->rol !== 'director_general' && $u->rol !== 'administrador') {
            abort(403, 'Acceso restringido al Director General de la Corporación de Asistencia Judicial.');
        }
    }

    /**
     * Bandeja Nacional de Firma Final (FirmaGob):
     * Lista los expedientes consolidados y auditados de todo el país listos para suscripción
     * con Firma Electrónica Avanzada del Estado (FirmaGob) para la Corte Suprema.
     */
    public function index(Request $request)
    {
        $this->autorizar($request);

        $q = trim((string) $request->query('q'));
        $regionId = $request->query('region_id');
        $estadoFiltro = $request->query('estado_firma', 'pendientes'); // 'pendientes', 'firmadas', 'todas'

        $base = Practica::whereIn('estado', ['remitida_biobio', 'nivelada_biobio', 'finalizada', 'cerrada_historial'])
            ->with(['practicante.postulante.comuna', 'consultorio.region', 'abogado.usuario', 'evaluacion', 'firmadoDirectorGeneralPor', 'certificadoFirmagob']);

        if ($q !== '') {
            $base->whereHas('practicante.postulante', fn ($p) => $p
                ->where('nombre', 'like', "%$q%")
                ->orWhere('apellido', 'like', "%$q%")
                ->orWhere('rut', 'like', "%$q%"));
        }

        if ($regionId && ctype_digit((string) $regionId)) {
            $base->whereHas('consultorio', fn ($c) => $c->where('region_id', (int) $regionId));
        }

        if ($estadoFiltro === 'pendientes') {
            $base->whereNull('firmado_director_general_at');
        } elseif ($estadoFiltro === 'firmadas') {
            $base->whereNotNull('firmado_director_general_at');
        }

        $expedientes = $base->orderByDesc('updated_at')->paginate(20)->withQueryString();

        $totales = [
            'pendientes_firma' => Practica::whereIn('estado', ['remitida_biobio', 'nivelada_biobio', 'finalizada', 'cerrada_historial'])
                ->whereNull('firmado_director_general_at')
                ->count(),
            'firmadas_firmagob' => Practica::whereNotNull('firmado_director_general_at')
                ->count(),
            'total_nacional' => Practica::whereIn('estado', ['remitida_biobio', 'nivelada_biobio', 'finalizada', 'cerrada_historial'])->count(),
        ];

        return view('director_general.index', [
            'expedientes' => $expedientes,
            'totales' => $totales,
            'regiones' => Region::operativas()->orderBy('orden')->get(['id', 'nombre', 'codigo']),
            'q' => $q,
            'regionFiltro' => $regionId,
            'estadoFiltro' => $estadoFiltro,
        ]);
    }

    /**
     * Ficha individual de firma y contraste criptográfico de FirmaGob.
     */
    public function ver(Request $request, int $id)
    {
        $this->autorizar($request);

        $p = Practica::with([
            'practicante.postulante.comuna',
            'consultorio.region',
            'abogado.usuario',
            'evaluacion',
            'calificaciones.registradaPor',
            'postulacion.documentos',
            'validadaDirectorPor',
            'firmadoDirectorGeneralPor',
            'certificadoFirmagob',
            'certificados',
        ])->findOrFail($id);

        $evaluacion = $p->evaluacion;
        abort_unless($evaluacion, 404, 'La práctica aún no ha sido evaluada.');

        $criterios = [];
        foreach (Notas::CRITERIOS as $campo => $etiqueta) {
            $criterios[] = [
                'etiqueta' => $etiqueta,
                'concepto' => Notas::concepto((float) $evaluacion->{$campo}),
            ];
        }

        $informeSaj = InformesPractica::vigente($p);

        return view('director_general.ver', [
            'practica' => $p,
            'postulante' => $p->practicante->postulante,
            'consultorio' => $p->consultorio,
            'region' => $p->consultorio->region,
            'evaluacion' => $evaluacion,
            'criterios' => $criterios,
            'conceptoFinal' => Notas::concepto($p->nota_practica),
            'informeSaj' => $informeSaj,
            'puedeFirmarCierre' => $p->estado !== 'cerrada_historial' && $p->firmado_director_general_at === null,
            'esCerrada' => $p->estado === 'cerrada_historial',
        ]);
    }

    /**
     * Descarga el Certificado Oficial para la Corte Suprema en PDF.
     */
    public function descargarCertificado(Request $request, int $id)
    {
        $this->autorizar($request);

        $p = Practica::with('practicante.postulante', 'consultorio.region', 'evaluacion')->findOrFail($id);

        $pdfBin = CertificadosPractica::pdfCertificado($p, $request->user());
        $rut = $p->practicante->postulante->rut;

        Auditoria::registrar('practica.descarga_certificado', 'practica', $p->id, [
            'rut_postulante' => $rut,
            'origen' => 'director_general',
            'rol' => $request->user()->rol,
        ], $request->user()->id);

        return response($pdfBin, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Certificado_Corte_Suprema_'.$rut.'.pdf"',
        ]);
    }

    /**
     * Descarga la Resolución oficial en PDF.
     */
    public function descargarResolucion(Request $request, int $id)
    {
        $this->autorizar($request);

        $p = Practica::with('practicante.postulante', 'consultorio.region', 'evaluacion')->findOrFail($id);

        $pdfBin = CertificadosPractica::pdfResolucion($p, $request->user());
        $rut = $p->practicante->postulante->rut;

        Auditoria::registrar('practica.descarga_resolucion', 'practica', $p->id, [
            'rut_postulante' => $rut,
            'origen' => 'director_general',
            'rol' => $request->user()->rol,
        ], $request->user()->id);

        return response($pdfBin, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Resolucion_Aprobacion_'.$rut.'.pdf"',
        ]);
    }

    /**
     * Guarda o actualiza los folios oficiales de la resolución y correlativos.
     */
    public function guardarFolios(Request $request, int $id)
    {
        $this->autorizar($request);

        $p = Practica::findOrFail($id);

        $validados = $request->validate([
            'resolucion_numero' => ['nullable', 'string', 'max:50'],
            'resolucion_anio' => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'oficio_daj_numero' => ['nullable', 'string', 'max:50'],
            'oficio_daj_fecha' => ['nullable', 'date'],
            'folio_caj' => ['nullable', 'string', 'max:100'],
            'folio_carta_presentacion' => ['nullable', 'string', 'max:50'],
        ]);

        $p->update([
            'resolucion_numero' => $request->filled('resolucion_numero') ? trim($validados['resolucion_numero']) : null,
            'resolucion_anio' => $validados['resolucion_anio'] ?? null,
            'oficio_daj_numero' => $request->filled('oficio_daj_numero') ? trim($validados['oficio_daj_numero']) : null,
            'oficio_daj_fecha' => $validados['oficio_daj_fecha'] ?? null,
            'folio_caj' => $request->filled('folio_caj') ? trim($validados['folio_caj']) : null,
            'folio_carta_presentacion' => $request->filled('folio_carta_presentacion') ? trim($validados['folio_carta_presentacion']) : null,
        ]);

        if ($request->filled('resolucion_numero')) {
            $cert = Certificado::where('practica_id', $p->id)->where('tipo', CertificadosPractica::RESOLUCION)->first();
            if ($cert) {
                $numEntero = (int) preg_replace('/\D/', '', (string) $validados['resolucion_numero']);
                $cert->update([
                    'numero_resolucion' => $numEntero,
                    'anio' => (int) ($validados['resolucion_anio'] ?? $cert->anio),
                ]);
            }
        }

        Auditoria::registrar('practica.folios_director_general', 'practica', $p->id, $validados, $request->user()->id);

        return back()->with('ok', 'Folios oficiales de resolución guardados exitosamente.');
    }

    /**
     * Descarga masiva de certificados en un archivo ZIP para firma externa por lotes con FirmaGob.
     */
    public function descargarLote(Request $request)
    {
        $this->autorizar($request);

        $datos = $request->validate([
            'practicas' => ['required', 'array', 'min:1'],
            'practicas.*' => ['integer'],
        ]);

        $practicas = Practica::whereIn('id', $datos['practicas'])
            ->with('practicante.postulante', 'consultorio.region', 'evaluacion')
            ->get();

        $zipFile = tempnam(sys_get_temp_dir(), 'firmagob_zip_');
        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($practicas as $p) {
            $estaAprobada = $p->nota_practica !== null ? Notas::esAprobatoria($p->nota_practica) : ($p->evaluacion && $p->evaluacion->aprobado);
            if ($estaAprobada) {
                $pdfBin = CertificadosPractica::pdfCertificado($p, $request->user());
                $rut = str_replace(['.', '-'], '', $p->practicante->postulante->rut);
                $zip->addFromString("Certificado_Corte_Suprema_{$rut}_{$p->id}.pdf", $pdfBin);
            }
        }

        $zip->close();

        Auditoria::registrar('firmagob.descarga_lote', 'practica', null, [
            'total_practicas' => $practicas->count(),
            'practicas_ids' => $practicas->pluck('id')->all(),
            'ruts' => $practicas->map(fn ($p) => $p->practicante?->postulante?->rut)->filter()->values()->all(),
            'motivo' => 'Descarga de lote masivo de certificados para firma offline FirmaGob',
        ], $request->user()->id);

        return response()->download($zipFile, 'Certificados_FirmaGob_Lote_'.now()->format('Ymd_His').'.zip')->deleteFileAfterSend();
    }

    /**
     * Módulo de integración con FirmaGob (Gatillador de Cierre Definitivo):
     * El Director General sube el documento PDF ya firmado con Firma Electrónica Avanzada (FirmaGob).
     * Esto ejecuta el cambio de estado a CerradaHistorial, autoriza los documentos para el postulante
     * y despacha las notificaciones institucionales de cierre.
     */
    public function subirFirmaGob(Request $request, int $id)
    {
        $this->autorizar($request);

        $p = Practica::with('practicante.postulante', 'consultorio.region', 'evaluacion')->findOrFail($id);

        $datos = $request->validate([
            'archivo_firmado' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'folio' => ['nullable', 'string', 'max:100'],
            'url_verificacion' => ['nullable', 'url', 'max:255'],
        ], [
            'archivo_firmado.required' => 'Seleccione el Certificado PDF firmado con FirmaGob.',
            'archivo_firmado.mimes' => 'El archivo debe ser un documento PDF válido.',
            'archivo_firmado.max' => 'El documento no puede superar los 10 MB.',
            'url_verificacion.url' => 'Ingrese una URL de verificación válida de FirmaGob.',
        ]);

        $file = $request->file('archivo_firmado');
        $g = Almacenamiento::guardar($file);

        // Registrar el documento oficial firmado con FirmaGob
        $doc = Documento::create([
            'tipo' => 'certificado_corte_suprema',
            'practica_id' => $p->id,
            'nombre_original' => $file->getClientOriginalName(),
            'nombre_almacenado' => $g['nombre_almacenado'],
            'mime' => $g['mime'],
            'tamano_bytes' => $g['tamano_bytes'],
            'sha256' => $g['sha256'],
            'subido_por' => $request->user()->id,
        ]);

        // Registrar o actualizar certificado en tabla certificados
        Certificado::updateOrCreate(
            ['practica_id' => $p->id, 'tipo' => CertificadosPractica::CERTIFICADO],
            [
                'fecha_emision' => now()->toDateString(),
                'documento_id' => $doc->id,
                'emitido_por' => $request->user()->id,
            ]
        );

        // Registro de la firma digital con FirmaGob por el Director General.
        // Decisión institucional: Secretaría Jurídica Central ejecuta el paso final de revisión y despacho formal.
        $p->update([
            'firmado_director_general_at' => now(),
            'firmado_director_general_por' => $request->user()->id,
            'firmagob_folio' => $datos['folio'] ?? null,
            'firmagob_url' => $datos['url_verificacion'] ?? null,
            'certificado_firmagob_id' => $doc->id,
        ]);

        Auditoria::registrar('director_general.firma_gob', 'practica', $p->id, [
            'documento_id' => $doc->id,
            'folio' => $datos['folio'] ?? null,
            'url_verificacion' => $datos['url_verificacion'] ?? null,
            'sha256' => $g['sha256'],
            'director_id' => $request->user()->id,
        ], $request->user()->id);

        // Notificación a la Secretaría Jurídica Central de Biobío de que el expediente cuenta con la firma del Director General y queda listo para su despacho formal
        $usuariosJuridica = Usuario::where('activo', true)
            ->whereIn('rol', ['secretaria', 'juridica_central'])
            ->whereHas('region', fn ($q) => $q->where('codigo', 'VIII'))
            ->get();

        $postulanteNombre = $p->practicante?->postulante
            ? ($p->practicante->postulante->nombre . ' ' . $p->practicante->postulante->apellido)
            : 'el postulante';

        foreach ($usuariosJuridica as $uj) {
            Notificaciones::crear(
                $uj->id,
                'aviso',
                'Certificado con FirmaGob listo para despacho',
                "El Director General ha firmado con FirmaGob el Certificado Oficial de {$postulanteNombre}. El expediente se encuentra listo para autorizar y despachar formalmente al portal.",
                'practica',
                $p->id
            );
        }

        return redirect()->route('director_general.ver', $p->id)->with('ok', 'Certificado suscrito con FirmaGob registrado exitosamente. El expediente ha quedado disponible en Secretaría Jurídica Central para su revisión final y despacho formal al postulante.');
    }

    /**
     * Módulo de Supervisión Nacional de Prácticas:
     * Grilla de prácticas a nivel corporativo (todas las 6 regiones).
     */
    public function seguimiento(Request $request)
    {
        $this->autorizar($request);

        $q = trim((string) $request->query('q'));
        $regionId = $request->query('region_id');
        $consultorioId = $request->query('consultorio_id');
        $estado = $request->query('estado');

        $base = Practica::with(['practicante.postulante', 'consultorio.region', 'abogado.usuario']);

        if ($q !== '') {
            $base->whereHas('practicante.postulante', fn ($p) => $p
                ->where('nombre', 'like', "%$q%")
                ->orWhere('apellido', 'like', "%$q%")
                ->orWhere('rut', 'like', "%$q%"));
        }

        if ($regionId && ctype_digit((string) $regionId)) {
            $base->whereHas('consultorio', fn ($c) => $c->where('region_id', (int) $regionId));
        }

        if ($consultorioId && ctype_digit((string) $consultorioId)) {
            $base->where('consultorio_id', (int) $consultorioId);
        }

        if ($estado && in_array($estado, ['por_iniciar', 'activa', 'finalizada', 'remitida_biobio', 'nivelada_biobio', 'rechazada_sancion', 'cerrada_historial', 'cancelada'], true)) {
            $base->where('estado', $estado);
        }

        $practicas = $base->orderBy('fecha_inicio')->paginate(25)->withQueryString();

        return view('director_general.seguimiento', [
            'practicas' => $practicas,
            'regiones' => Region::operativas()->orderBy('orden')->get(['id', 'nombre', 'codigo']),
            'q' => $q,
            'regionId' => $regionId,
            'consultorioId' => $consultorioId,
            'estado' => $estado,
        ]);
    }

    /**
     * Exportación en Excel de la supervisión nacional corporativa.
     */
    public function exportarSeguimiento(Request $request)
    {
        $this->autorizar($request);

        $q = trim((string) $request->query('q'));
        $regionId = $request->query('region_id');
        $consultorioId = $request->query('consultorio_id');
        $estado = $request->query('estado');

        $base = Practica::with(['practicante.postulante', 'consultorio.region', 'abogado.usuario']);

        if ($q !== '') {
            $base->whereHas('practicante.postulante', fn ($p) => $p
                ->where('nombre', 'like', "%$q%")
                ->orWhere('apellido', 'like', "%$q%")
                ->orWhere('rut', 'like', "%$q%"));
        }

        if ($regionId && ctype_digit((string) $regionId)) {
            $base->whereHas('consultorio', fn ($c) => $c->where('region_id', (int) $regionId));
        }

        if ($consultorioId && ctype_digit((string) $consultorioId)) {
            $base->where('consultorio_id', (int) $consultorioId);
        }

        if ($estado && in_array($estado, ['por_iniciar', 'activa', 'finalizada', 'remitida_biobio', 'nivelada_biobio', 'rechazada_sancion', 'cerrada_historial', 'cancelada'], true)) {
            $base->where('estado', $estado);
        }

        $practicas = $base->orderBy('fecha_inicio')->get();

        $ruta = tempnam(sys_get_temp_dir(), 'seg_nacional_').'.xlsx';
        $w = new Writer();
        $w->openToFile($ruta);
        $w->addRow(Row::fromValues([
            'Región',
            'Sede / Consultorio',
            'Practicante',
            'RUT',
            'Correo',
            'Universidad',
            'Abogado Tutor',
            'Fecha Inicio',
            'Fecha Término',
            'Estado',
            'Visada Regional',
            'Firmada FirmaGob',
            'Fecha Cierre',
        ]));

        foreach ($practicas as $p) {
            $po = $p->practicante?->postulante;
            $reg = $p->consultorio?->region;
            $w->addRow(Row::fromValues([
                $reg?->nombre ?? 'Sin región',
                $p->consultorio?->nombre ?? '—',
                $po ? $po->nombre.' '.$po->apellido : '—',
                $po?->rut ?? '—',
                $po?->email ?? '—',
                $po?->universidad ?? '—',
                $p->abogado?->usuario?->nombre_completo ?? 'Sin asignar',
                $p->fecha_inicio ? $p->fecha_inicio->format('d-m-Y') : '—',
                $p->fecha_termino ? $p->fecha_termino->format('d-m-Y') : '—',
                Practicas::estadoVisible($p),
                $p->validada_director_at ? 'Sí' : 'No',
                $p->firmado_director_general_at ? 'Sí' : 'No',
                $p->firmado_director_general_at ? $p->firmado_director_general_at->format('d-m-Y H:i') : '—',
            ]));
        }

        $w->close();

        Auditoria::registrar('seguimiento.exportar_nacional', 'practica', null, [
            'total_registros' => $practicas->count(),
            'filtros' => array_filter([
                'q' => $q,
                'region_id' => $regionId,
                'consultorio_id' => $consultorioId,
                'estado' => $estado,
            ]),
            'motivo' => 'Exportación nacional corporativa de seguimiento y métricas directivas (Ley N.° 19.628)',
        ], $request->user()->id);

        return response()->download($ruta, 'seguimiento-nacional-caj-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
    }

    /**
     * Consulta nacional de sedes y consultorios institucionales.
     */
    public function consultorios(Request $request)
    {
        $this->autorizar($request);

        $consultorios = Consultorio::with('region', 'comuna', 'abogados.usuario')
            ->where('activo', true)
            ->orderBy('region_id')
            ->orderBy('nombre')
            ->get()
            ->each(function ($c) {
                $c->libres = $c->libres();
                $c->ocupados = $c->ocupados();
                $c->disponibilidad = Practicas::nivelDisponibilidad($c->capacidad, $c->libres);
            });

        return view('director_general.consultorios', [
            'consultorios' => $consultorios,
            'regiones' => Region::operativas()->orderBy('orden')->get(['id', 'nombre', 'codigo']),
        ]);
    }
}
