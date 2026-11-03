<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Calificacion;
use App\Models\Postulacion;
use App\Models\Practica;
use App\Models\Region;
use App\Models\Usuario;
use App\Services\Auditoria;
use App\Services\CertificadosPractica;
use App\Services\Notificaciones;
use App\Services\Practicas;
use App\Support\Notas;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class JuridicaBiobioController extends Controller
{
    /**
     * Valida que el usuario tenga facultades de Secretaría Jurídica Central (Biobío - Concepción).
     */
    private function autorizar(Request $request): void
    {
        $u = $request->user();
        if (! $u) {
            abort(403);
        }

        $esJuridica = $u->rol === 'juridica_central';
        $esAdmin = $u->rol === 'administrador';
        $esSecBiobio = $u->rol === 'secretaria' && $u->region && $u->region->codigo === 'BI';

        if (! ($esJuridica || $esAdmin || $esSecBiobio)) {
            abort(403, 'Acceso restringido a la Secretaría Jurídica Central de Biobío (Concepción).');
        }
    }

    /**
     * Pantalla 1 (Antes de la práctica):
     * Supervisión Nacional de Postulaciones.
     * Gestión directa y asignación para la Región del Biobío;
     * Modo solo lectura para postulaciones de regiones externas.
     */
    public function postulaciones(Request $request)
    {
        $this->autorizar($request);

        $q = trim((string) $request->query('q'));
        $origen = $request->query('origen'); // 'todos', 'local_biobio', 'externo'
        $regionId = $request->query('region_id');

        $base = Postulacion::with(['postulante', 'region', 'preferencias.consultorio'])
            ->where('estado', 'pendiente');

        if ($q !== '') {
            $base->whereHas('postulante', fn ($p) => $p
                ->where('nombre', 'like', "%$q%")
                ->orWhere('apellido', 'like', "%$q%")
                ->orWhere('rut', 'like', "%$q%")
                ->orWhere('universidad', 'like', "%$q%"));
        }

        if ($origen === 'local_biobio') {
            $base->whereHas('region', fn ($r) => $r->where('codigo', 'BI'));
        } elseif ($origen === 'externo') {
            $base->whereHas('region', fn ($r) => $r->where('codigo', '!=', 'BI'));
        }

        if ($regionId && ctype_digit((string) $regionId)) {
            $base->where('region_id', (int) $regionId);
        }

        $postulaciones = $base->orderBy('fecha_postulacion')->orderBy('id')->get();

        $totales = [
            'pendientes_total' => Postulacion::where('estado', 'pendiente')->count(),
            'locales_biobio' => Postulacion::where('estado', 'pendiente')->whereHas('region', fn ($r) => $r->where('codigo', 'BI'))->count(),
            'externos' => Postulacion::where('estado', 'pendiente')->whereHas('region', fn ($r) => $r->where('codigo', '!=', 'BI'))->count(),
        ];

        return view('secretaria.juridica.postulaciones', [
            'postulaciones' => $postulaciones,
            'totales' => $totales,
            'q' => $q,
            'origenFiltro' => $origen,
            'regionFiltro' => $regionId,
            'regiones' => Region::operativas()->orderBy('orden')->get(['id', 'nombre', 'codigo']),
        ]);
    }

    /**
     * Exportación en Excel de postulaciones supervisadas a nivel nacional.
     */
    public function exportarPostulaciones(Request $request)
    {
        $this->autorizar($request);

        $q = trim((string) $request->query('q'));
        $origen = $request->query('origen');
        $regionId = $request->query('region_id');

        $base = Postulacion::with(['postulante', 'region', 'preferencias.consultorio'])
            ->where('estado', 'pendiente');

        if ($q !== '') {
            $base->whereHas('postulante', fn ($p) => $p
                ->where('nombre', 'like', "%$q%")
                ->orWhere('apellido', 'like', "%$q%")
                ->orWhere('rut', 'like', "%$q%")
                ->orWhere('universidad', 'like', "%$q%"));
        }

        if ($origen === 'local_biobio') {
            $base->whereHas('region', fn ($r) => $r->where('codigo', 'BI'));
        } elseif ($origen === 'externo') {
            $base->whereHas('region', fn ($r) => $r->where('codigo', '!=', 'BI'));
        }

        if ($regionId && ctype_digit((string) $regionId)) {
            $base->where('region_id', (int) $regionId);
        }

        $postulaciones = $base->orderBy('fecha_postulacion')->orderBy('id')->get();

        $ruta = tempnam(sys_get_temp_dir(), 'post_jur').'.xlsx';
        $w = new Writer();
        $w->openToFile($ruta);
        $w->addRow(Row::fromValues([
            'Origen Territorial',
            'Región',
            'Código Región',
            'Postulante',
            'RUT',
            'Email',
            'Teléfono',
            'Universidad',
            'Fecha Postulación',
            'Inicio Solicitado',
            'Preferencia Consultorio',
            'Atribución Jurídica Central',
        ]));

        foreach ($postulaciones as $p) {
            $po = $p->postulante;
            $esBiobio = $p->region && $p->region->codigo === 'BI';
            $origenTexto = $esBiobio ? 'Local (Biobío)' : 'Externo ('.$p->region?->nombre.')';
            $atribucion = $esBiobio ? 'Gestión Directa y Asignación' : 'Supervisión Solo Lectura (Competencia Regional)';
            $pref = $p->preferencias->first()?->consultorio?->nombre ?? 'Sin preferencia';

            $w->addRow(Row::fromValues([
                $origenTexto,
                $p->region?->nombre ?? 'Sin región',
                $p->region?->codigo ?? '—',
                $po ? $po->nombre.' '.$po->apellido : '—',
                $po?->rut ?? '—',
                $po?->email ?? '—',
                $po?->telefono ?? '—',
                $po?->universidad ?? '—',
                $p->fecha_postulacion ? $p->fecha_postulacion->format('d-m-Y') : '—',
                $p->fecha_inicio_solicitada ? $p->fecha_inicio_solicitada->format('d-m-Y') : '—',
                $pref,
                $atribucion,
            ]));
        }

        $w->close();

        return response()->download($ruta, 'postulaciones-nacionales-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
    }

    /**
     * Pantalla 2 (Después de la práctica):
     * Bandeja de Recepción Nacional de Expedientes remitidos a Jurídica Central.
     */
    public function index(Request $request)
    {
        $this->autorizar($request);

        $q = trim((string) $request->query('q'));
        $estado = $request->query('estado');
        $origen = $request->query('origen'); // 'todos', 'local_biobio', 'externo'
        $regionId = $request->query('region_id');

        $base = Practica::with(['practicante.postulante', 'consultorio.region', 'abogado.usuario', 'evaluacion'])
            ->whereIn('estado', ['remitida_biobio', 'nivelada_biobio', 'rechazada_sancion', 'cerrada_historial', 'finalizada']);

        if ($q !== '') {
            $base->whereHas('practicante.postulante', fn ($p) => $p
                ->where('nombre', 'like', "%$q%")
                ->orWhere('apellido', 'like', "%$q%")
                ->orWhere('rut', 'like', "%$q%"));
        }

        if ($estado && in_array($estado, ['remitida_biobio', 'nivelada_biobio', 'rechazada_sancion', 'cerrada_historial', 'finalizada'], true)) {
            $base->where('estado', $estado);
        }

        if ($origen === 'local_biobio') {
            $base->whereHas('consultorio.region', fn ($r) => $r->where('codigo', 'BI'));
        } elseif ($origen === 'externo') {
            $base->whereHas('consultorio.region', fn ($r) => $r->where('codigo', '!=', 'BI'));
        }

        if ($regionId && ctype_digit((string) $regionId)) {
            $base->whereHas('consultorio', fn ($c) => $c->where('region_id', (int) $regionId));
        }

        $expedientes = $base->orderByDesc('remitida_at')->orderByDesc('updated_at')->get();

        // Totales para las tarjetas resumen de control
        $totales = [
            'recibidos' => Practica::where('estado', 'remitida_biobio')->count(),
            'niveladas' => Practica::where('estado', 'nivelada_biobio')->count(),
            'cerradas' => Practica::where('estado', 'cerrada_historial')->count(),
            'rechazadas' => Practica::where('estado', 'rechazada_sancion')->count(),
            'locales_biobio' => Practica::whereIn('estado', ['remitida_biobio', 'nivelada_biobio', 'rechazada_sancion', 'cerrada_historial', 'finalizada'])
                ->whereHas('consultorio.region', fn ($r) => $r->where('codigo', 'BI'))->count(),
            'externos' => Practica::whereIn('estado', ['remitida_biobio', 'nivelada_biobio', 'rechazada_sancion', 'cerrada_historial', 'finalizada'])
                ->whereHas('consultorio.region', fn ($r) => $r->where('codigo', '!=', 'BI'))->count(),
        ];

        return view('secretaria.juridica.index', [
            'expedientes' => $expedientes,
            'totales' => $totales,
            'q' => $q,
            'estadoFiltro' => $estado,
            'origenFiltro' => $origen,
            'regionFiltro' => $regionId,
            'regiones' => Region::operativas()->orderBy('orden')->get(['id', 'nombre', 'codigo']),
        ]);
    }

    /**
     * Exportación en Excel de expedientes post-práctica remitidos a Jurídica Central.
     */
    public function exportarExpedientes(Request $request)
    {
        $this->autorizar($request);

        $q = trim((string) $request->query('q'));
        $estado = $request->query('estado');
        $origen = $request->query('origen');
        $regionId = $request->query('region_id');

        $base = Practica::with(['practicante.postulante', 'consultorio.region', 'abogado.usuario', 'evaluacion'])
            ->whereIn('estado', ['remitida_biobio', 'nivelada_biobio', 'rechazada_sancion', 'cerrada_historial', 'finalizada']);

        if ($q !== '') {
            $base->whereHas('practicante.postulante', fn ($p) => $p
                ->where('nombre', 'like', "%$q%")
                ->orWhere('apellido', 'like', "%$q%")
                ->orWhere('rut', 'like', "%$q%"));
        }

        if ($estado && in_array($estado, ['remitida_biobio', 'nivelada_biobio', 'rechazada_sancion', 'cerrada_historial', 'finalizada'], true)) {
            $base->where('estado', $estado);
        }

        if ($origen === 'local_biobio') {
            $base->whereHas('consultorio.region', fn ($r) => $r->where('codigo', 'BI'));
        } elseif ($origen === 'externo') {
            $base->whereHas('consultorio.region', fn ($r) => $r->where('codigo', '!=', 'BI'));
        }

        if ($regionId && ctype_digit((string) $regionId)) {
            $base->whereHas('consultorio', fn ($c) => $c->where('region_id', (int) $regionId));
        }

        $expedientes = $base->orderByDesc('remitida_at')->orderByDesc('updated_at')->get();

        $ruta = tempnam(sys_get_temp_dir(), 'exp_jur').'.xlsx';
        $w = new Writer();
        $w->openToFile($ruta);
        $w->addRow(Row::fromValues([
            'Origen Territorial',
            'Región',
            'Código Región',
            'Practicante',
            'RUT',
            'Universidad',
            'Consultorio / Sede',
            'Abogado Tutor',
            'Fecha Inicio',
            'Fecha Término',
            'Calificación Institucional',
            'Ajustada en Central',
            'Estado Expediente',
            'Fecha Remisión',
            'Fecha Ajuste',
            'Fecha Despacho Final',
        ]));

        foreach ($expedientes as $p) {
            $po = $p->practicante?->postulante;
            $reg = $p->consultorio?->region;
            $esBiobio = $reg && $reg->codigo === 'BI';
            $origenTexto = $esBiobio ? 'Local (Biobío)' : 'Externo ('.$reg?->nombre.')';
            $concepto = Notas::concepto($p->nota_practica);
            $esNivelada = $p->nivelada_at ? 'Sí' : 'No';

            $w->addRow(Row::fromValues([
                $origenTexto,
                $reg?->nombre ?? 'Sin región',
                $reg?->codigo ?? '—',
                $po ? $po->nombre.' '.$po->apellido : '—',
                $po?->rut ?? '—',
                $po?->universidad ?? '—',
                $p->consultorio?->nombre ?? '—',
                $p->abogado ? $p->abogado->usuario?->nombre_completo : 'Sin tutor',
                $p->fecha_inicio ? $p->fecha_inicio->format('d-m-Y') : '—',
                $p->fecha_termino ? $p->fecha_termino->format('d-m-Y') : '—',
                $concepto, // Solo palabras (Sobresaliente, Muy Buena, Buena, etc.)
                $esNivelada,
                Practicas::estadoVisible($p),
                $p->remitida_at ? $p->remitida_at->format('d-m-Y H:i') : '—',
                $p->nivelada_at ? $p->nivelada_at->format('d-m-Y H:i') : '—',
                $p->despachada_at ? $p->despachada_at->format('d-m-Y H:i') : '—',
            ]));
        }

        $w->close();

        return response()->download($ruta, 'expedientes-juridica-central-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
    }

    /**
     * Ficha de revisión del expediente nacional en Jurídica Biobío.
     */
    public function ver(Request $request, int $id)
    {
        $this->autorizar($request);

        $p = Practica::with([
            'practicante.postulante.comuna', 'consultorio.region',
            'abogado.usuario', 'evaluacion', 'calificaciones',
            'postulacion.documentos', 'remitidaPor', 'niveladaPor', 'despachadaPor', 'certificados',
        ])->findOrFail($id);

        $evaluacion = $p->evaluacion;
        $criterios = [];
        if ($evaluacion) {
            foreach (Notas::CRITERIOS as $campo => $etiqueta) {
                $criterios[] = [
                    'etiqueta' => $etiqueta,
                    'concepto' => Notas::concepto((float) $evaluacion->{$campo}),
                ];
            }
        }

        $notaActual = $p->nota_practica;
        $conceptoFinal = Notas::concepto($notaActual);
        $esNivelable = Notas::esNivelable($notaActual);

        // Preselección por defecto: si es Regular, sugiere BUENA por regla de nivelación; de lo contrario su concepto institucional
        $claveSugerida = match ($conceptoFinal) {
            'Sobresaliente', 'Muy Buena' => 'SOBRESALIENTE',
            'Buena' => 'BUENA',
            'Regular' => 'BUENA',
            'Mala' => 'MALA',
            default => 'MALA',
        };

        return view('secretaria.juridica.ver', [
            'practica' => $p,
            'postulante' => $p->practicante->postulante,
            'estadistica' => Practicas::estadistica($p),
            'evaluacion' => $evaluacion,
            'criterios' => $criterios,
            'criteriosList' => Notas::CRITERIOS,
            'conceptoFinal' => $conceptoFinal,
            'esNivelable' => $esNivelable,
            'opcionesNotas' => Notas::OPCIONES,
            'claveSugerida' => $claveSugerida,
            'puedeAjustarNota' => in_array($p->estado, ['remitida_biobio', 'finalizada', 'nivelada_biobio'], true),
            'puedeRechazar' => in_array($p->estado, ['remitida_biobio', 'finalizada', 'nivelada_biobio'], true),
            'puedeDespachar' => in_array($p->estado, ['remitida_biobio', 'nivelada_biobio', 'finalizada', 'cerrada_historial'], true)
                && $evaluacion
                && ($p->nota_practica !== null ? Notas::esAprobatoria($p->nota_practica) : $evaluacion->aprobado)
                && ! is_null($p->firmado_director_general_at),
            'documentos' => $p->postulacion->documentos,
        ]);
    }

    /**
     * Modificación manual de calificación institucional por la Secretaría Jurídica Central.
     * Permite modificar individualmente en conceptos los 7 criterios reglamentarios
     * (Conocimiento jurídico, Responsabilidad, Iniciativa, Sentido social, Conducta,
     * Honorabilidad, Asistencia y puntualidad) y registrar la justificación institucional.
     */
    public function nivelar(Request $request, int $id)
    {
        $this->autorizar($request);

        $p = Practica::with('practicante.postulante', 'evaluacion')->findOrFail($id);

        $reglas = [
            'justificacion' => ['required', 'string', 'min:10', 'max:2000'],
            'concepto' => ['nullable', 'string', 'in:'.implode(',', array_keys(Notas::OPCIONES))],
        ];

        foreach (array_keys(Notas::CRITERIOS) as $crit) {
            $reglas[$crit] = ['nullable', 'string', 'in:'.implode(',', array_keys(Notas::OPCIONES))];
        }

        $datos = $request->validate($reglas, [
            'justificacion.required' => 'Debe ingresar la justificación institucional para la modificación de calificación.',
            'justificacion.min' => 'La justificación debe contener al menos 10 caracteres.',
            'in' => 'Seleccione una calificación válida en palabras.',
        ]);

        // Determinar si se enviaron criterios individuales en la solicitud
        $hayCriteriosEnRequest = false;
        foreach (array_keys(Notas::CRITERIOS) as $crit) {
            if (! empty($datos[$crit])) {
                $hayCriteriosEnRequest = true;
                break;
            }
        }

        $puntajes = [];
        if ($hayCriteriosEnRequest) {
            foreach (array_keys(Notas::CRITERIOS) as $crit) {
                if (! empty($datos[$crit])) {
                    $puntajes[$crit] = Notas::valorDesdeConcepto($datos[$crit]);
                } elseif ($p->evaluacion && isset($p->evaluacion->{$crit})) {
                    $puntajes[$crit] = (float) $p->evaluacion->{$crit};
                } else {
                    $puntajes[$crit] = 5.0; // Valor por defecto Buena
                }
            }

            $promedioCalculado = Notas::promedio($puntajes);
            $notaPractica = round($promedioCalculado, 1);

            if (! empty($datos['concepto'])) {
                $claveConcepto = $datos['concepto'];
                $nombreConcepto = Notas::OPCIONES[$claveConcepto]['nombre'];
                if (Notas::concepto($notaPractica) !== $nombreConcepto) {
                    $notaPractica = (float) Notas::OPCIONES[$claveConcepto]['valor'];
                }
            } else {
                $nombreConcepto = Notas::concepto($notaPractica);
            }
        } else {
            // Solo se especificó el concepto general
            $claveConcepto = $datos['concepto'] ?? 'BUENA';
            $opcion = Notas::OPCIONES[$claveConcepto] ?? Notas::OPCIONES['BUENA'];
            $nombreConcepto = $opcion['nombre'];
            $notaPractica = (float) $opcion['valor'];
            foreach (array_keys(Notas::CRITERIOS) as $crit) {
                $puntajes[$crit] = $notaPractica;
            }
        }

        $conceptoAnterior = Notas::concepto($p->nota_practica);

        $p->update([
            'nota_practica' => $notaPractica,
            'estado' => 'nivelada_biobio',
            'nivelada_at' => now(),
            'nivelada_por' => $request->user()->id,
            'justificacion_nivelacion' => 'Calificación fijada en «'.$nombreConcepto.'»: '.$datos['justificacion'],
        ]);

        $esAprobatorio = Notas::esAprobatoria($notaPractica);
        if ($p->evaluacion) {
            $p->evaluacion->update($puntajes + [
                'promedio' => $notaPractica,
                'aprobado' => $esAprobatorio,
            ]);
        } else {
            $p->evaluacion()->create($puntajes + [
                'abogado_id' => $p->abogado_id ?? 1,
                'promedio' => $notaPractica,
                'aprobado' => $esAprobatorio,
            ]);
        }

        Calificacion::updateOrCreate(
            ['practica_id' => $p->id, 'instancia' => 3],
            [
                'nota' => $notaPractica,
                'fecha' => now()->toDateString(),
                'registrada_por' => $request->user()->id,
                'observaciones' => 'Modificación institucional de calificación a «'.$nombreConcepto.'» por Secretaría Jurídica Central: '.$datos['justificacion'],
            ]
        );

        Auditoria::registrar('practica.nivelar_oficio', 'practica', $p->id, [
            'concepto_anterior' => $conceptoAnterior,
            'concepto_nuevo' => $nombreConcepto,
            'justificacion' => $datos['justificacion'],
        ], $request->user()->id);

        if ($usuarioId = $p->practicante?->usuario_id) {
            Notificaciones::crear(
                $usuarioId,
                'aviso',
                'Calificación fijada a '.$nombreConcepto,
                'La Secretaría Jurídica Central de Biobío ha determinado formalmente su calificación final en concepto «'.$nombreConcepto.'» conforme a la normativa institucional.',
                'practica',
                $p->id
            );
        }

        return back()->with('ok', 'Calificación institucional modificada a «'.$nombreConcepto.'» correctamente.');
    }

    /**
     * Rechazo fundado por antecedentes sancionatorios graves.
     */
    public function rechazarSancion(Request $request, int $id)
    {
        $this->autorizar($request);

        $p = Practica::with('practicante.postulante')->findOrFail($id);

        $datos = $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'motivo.required' => 'Debe fundamentar los antecedentes de la causal disciplinaria.',
            'motivo.min' => 'El motivo debe contener al menos 10 caracteres.',
        ]);

        $p->update([
            'estado' => 'rechazada_sancion',
            'causal_rechazo_sancion' => $datos['motivo'],
            'finalizada_at' => now(),
        ]);

        Auditoria::registrar('practica.rechazar_sancion', 'practica', $p->id, [
            'motivo' => $datos['motivo'],
        ], $request->user()->id);

        if ($usuarioId = $p->practicante?->usuario_id) {
            Notificaciones::crear(
                $usuarioId,
                'urgente',
                'Práctica rechazada por causal disciplinaria',
                'La Secretaría Jurídica Central de Biobío ha resuelto el rechazo de la práctica por causal disciplinaria fundada.',
                'practica',
                $p->id
            );
        }

        return back()->with('ok', 'Expediente rechazado por causal disciplinaria fundada.');
    }

    /**
     * Despacho final de certificados por correo y archivo definitivo en historial central.
     */
    public function despachar(Request $request, int $id)
    {
        $this->autorizar($request);

        $p = Practica::with('practicante.postulante', 'evaluacion')->findOrFail($id);

        $estaAprobada = $p->nota_practica !== null ? Notas::esAprobatoria($p->nota_practica) : ($p->evaluacion && $p->evaluacion->aprobado);

        if (! $estaAprobada) {
            return back()->withErrors(['despacho' => 'Solo pueden despacharse prácticas con evaluación aprobada.']);
        }

        if (is_null($p->firmado_director_general_at)) {
            return back()->withErrors(['despacho' => 'No se puede despachar: el Certificado aún no ha sido suscrito con FirmaGob por el Director General.']);
        }

        // Autoriza documentos y emite cierre formal
        $p->update([
            'estado' => 'cerrada_historial',
            'documentos_autorizados' => true,
            'documentos_autorizados_at' => now(),
            'documentos_autorizados_por' => $request->user()->id,
            'despachada_at' => now(),
            'despachada_por' => $request->user()->id,
        ]);

        Auditoria::registrar('practica.despacho_final', 'practica', $p->id, [
            'despachada_por' => $request->user()->id,
            'nota_final' => $p->nota_practica,
            'concepto_final' => Notas::concepto($p->nota_practica),
        ], $request->user()->id);

        if ($usuarioId = $p->practicante?->usuario_id) {
            Notificaciones::crear(
                $usuarioId,
                'aviso',
                'Certificados disponibles en su portal',
                'La Secretaría Jurídica Central de Biobío ha autorizado y despachado su Certificado para la Corte Suprema y la Resolución de término. Ya se encuentran disponibles para descarga en su portal.',
                'practica',
                $p->id
            );
        }

        return back()->with('ok', 'Expediente formalmente autorizado, despachado al portal del postulante y archivado en Historial. Los documentos oficiales quedaron disponibles para descarga.');
    }

    /**
     * Descarga del Certificado Oficial para la Corte Suprema (PDF).
     */
    public function descargarCertificado(Request $request, int $id)
    {
        $this->autorizar($request);
        $p = Practica::with(['practicante.postulante', 'consultorio.region', 'evaluacion', 'certificadoFirmagob'])->findOrFail($id);

        if ($msg = CertificadosPractica::bloqueo($p)) {
            return back()->with('error', $msg)->withErrors(['descarga' => $msg]);
        }

        $pdfBin = CertificadosPractica::pdfCertificado($p, $request->user());
        $rut = $p->practicante?->postulante?->rut ?? 'postulante';

        return response($pdfBin, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Certificado_Corte_Suprema_'.$rut.'.pdf"',
        ]);
    }

    /**
     * Descarga de la Resolución Oficial de Término (PDF).
     */
    public function descargarResolucion(Request $request, int $id)
    {
        $this->autorizar($request);
        $p = Practica::with(['practicante.postulante', 'consultorio.region', 'evaluacion'])->findOrFail($id);

        if ($msg = CertificadosPractica::bloqueo($p)) {
            return back()->with('error', $msg)->withErrors(['descarga' => $msg]);
        }

        $pdfBin = CertificadosPractica::pdfResolucion($p, $request->user());
        $rut = $p->practicante?->postulante?->rut ?? 'postulante';

        return response($pdfBin, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Resolucion_Aprobacion_'.$rut.'.pdf"',
        ]);
    }

    /**
     * Descarga del Expediente Completo en ZIP para remisión manual o correo personal.
     */
    public function descargarExpediente(Request $request, int $id)
    {
        $this->autorizar($request);
        $p = Practica::with(['practicante.postulante', 'consultorio.region', 'evaluacion', 'certificadoFirmagob'])->findOrFail($id);

        if ($msg = CertificadosPractica::bloqueo($p)) {
            return back()->with('error', $msg)->withErrors(['descarga' => $msg]);
        }

        $zipPath = CertificadosPractica::zipExpediente($p, $request->user());
        $rut = $p->practicante?->postulante?->rut ?? 'postulante';

        return response()->download($zipPath, 'Expediente_Practica_'.$rut.'.zip')->deleteFileAfterSend();
    }

    /**
     * Carga asistida o registro de calificación de Segunda Instancia (Corte de Apelaciones).
     */
    public function calificarSegundaInstancia(Request $request, int $id)
    {
        $this->autorizar($request);

        $p = Practica::with('practicante.postulante')->findOrFail($id);

        $datos = $request->validate([
            'concepto' => ['required', 'string', 'in:'.implode(',', array_keys(Notas::OPCIONES))],
            'observaciones' => ['required', 'string', 'min:10', 'max:3000'],
        ], [
            'concepto.required' => 'Seleccione una calificación en palabras para segunda instancia.',
            'concepto.in' => 'Seleccione una opción válida de calificación.',
            'observaciones.required' => 'Debe ingresar las observaciones de los alegatos en la Corte de Apelaciones.',
            'observaciones.min' => 'Las observaciones deben contener al menos 10 caracteres.',
        ]);

        $opcion = Notas::OPCIONES[$datos['concepto']];

        Calificacion::updateOrCreate(
            ['practica_id' => $p->id, 'instancia' => 2],
            [
                'nota' => $opcion['valor'],
                'fecha' => now()->toDateString(),
                'registrada_por' => $request->user()->id,
                'observaciones' => 'Registro 2ª Instancia por Jurídica Biobío (Corte de Apelaciones - '.$opcion['nombre'].'): '.$datos['observaciones'],
            ]
        );

        Auditoria::registrar('practica.segunda_instancia_juridica', 'practica', $p->id, [
            'concepto' => $opcion['nombre'],
            'registrada_por' => $request->user()->id,
        ], $request->user()->id);

        return back()->with('ok', 'Calificación de Segunda Instancia (Corte de Apelaciones) registrada en concepto «'.$opcion['nombre'].'».');
    }
}
