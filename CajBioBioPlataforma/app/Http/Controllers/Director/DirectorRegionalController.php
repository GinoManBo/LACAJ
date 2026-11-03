<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Calificacion;
use App\Models\Consultorio;
use App\Models\Practica;
use App\Models\Region;
use App\Models\Usuario;
use App\Services\Auditoria;
use App\Services\InformesPractica;
use App\Services\Notificaciones;
use App\Services\Practicas;
use App\Support\Notas;
use App\Support\Scope;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class DirectorRegionalController extends Controller
{
    /**
     * Valida permisos para Director Regional o Jefatura de UCOE Biobío.
     */
    private function autorizar(Request $request): void
    {
        $u = $request->user();
        if (! $u) {
            abort(403);
        }

        $rolesPermitidos = ['director_regional', 'jefe_ucoe', 'administrador'];
        if (in_array($u->rol, $rolesPermitidos, true)) {
            return;
        }

        // Revisores colegiados de UCOE en la Región del Biobío
        if ($u->rol === 'secretaria' && $u->region?->codigo === 'BI') {
            $nom = mb_strtolower($u->nombre_completo);
            if (str_contains($nom, 'cifuentes') || str_contains($nom, 'paredes')) {
                return;
            }
        }

        abort(403, 'Acceso restringido a Directores Regionales y Jefatura / Revisores de UCOE.');
    }

    /**
     * Obtiene el identificador de la región bajo la competencia del usuario.
     */
    private function regionId(Request $request): int
    {
        $u = $request->user();
        if ($u->rol === 'administrador') {
            $regQuery = $request->query('region_id');
            if ($regQuery && ctype_digit((string) $regQuery)) {
                return (int) $regQuery;
            }
            return Region::where('codigo', 'BI')->value('id') ?? 1;
        }

        if (! $u->region_id) {
            abort(403, 'El usuario no tiene una región asignada a su cargo directivo.');
        }

        return $u->region_id;
    }

    /**
     * Bandeja Principal: Validación de Evaluaciones (Fase 3 Regional).
     * Muestra los expedientes de su región que cuentan con evaluación de 1ª instancia
     * y están listos para la visación formal directiva antes de remitir a Jurídica Concepción.
     */
    public function index(Request $request)
    {
        $this->autorizar($request);
        $regionId = $this->regionId($request);
        $region = Region::findOrFail($regionId);

        $q = trim((string) $request->query('q'));
        $estadoValidacion = $request->query('estado_validacion', 'pendientes'); // 'pendientes', 'validadas', 'todas'

        $base = Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', $regionId))
            ->whereHas('evaluacion')
            ->where('estado', '!=', 'cancelada')
            ->with(['practicante.postulante.comuna', 'consultorio', 'abogado.usuario', 'evaluacion', 'calificaciones', 'validadaDirectorPor']);

        if ($q !== '') {
            $base->whereHas('practicante.postulante', fn ($p) => $p
                ->where('nombre', 'like', "%$q%")
                ->orWhere('apellido', 'like', "%$q%")
                ->orWhere('rut', 'like', "%$q%"));
        }

        if ($estadoValidacion === 'pendientes') {
            $base->whereNull('validada_director_at');
        } elseif ($estadoValidacion === 'validadas') {
            $base->whereNotNull('validada_director_at');
        }

        $practicas = $base->orderByDesc('updated_at')->paginate(20)->withQueryString();

        $totales = [
            'pendientes' => Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', $regionId))
                ->whereHas('evaluacion')
                ->where('estado', '!=', 'cancelada')
                ->whereNull('validada_director_at')
                ->count(),
            'validadas' => Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', $regionId))
                ->whereHas('evaluacion')
                ->where('estado', '!=', 'cancelada')
                ->whereNotNull('validada_director_at')
                ->count(),
            'activas_total' => Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', $regionId))
                ->where('estado', 'activa')
                ->count(),
        ];

        return view('director.inicio', [
            'region' => $region,
            'practicas' => $practicas,
            'totales' => $totales,
            'q' => $q,
            'estadoValidacion' => $estadoValidacion,
            'esUcoe' => $request->user()->rol === 'jefe_ucoe' || $region->codigo === 'BI',
        ]);
    }

    /**
     * Detalle del Expediente para Validación:
     * - Visor embebido / acceso directo al Informe de Práctica del SAJ en PDF.
     * - Rúbrica completa de Primera Instancia (Abogado Jefe Consultorio) y Segunda Instancia (Corte).
     * - Botonera para visación individual.
     */
    public function ver(Request $request, int $id)
    {
        $this->autorizar($request);
        $regionId = $this->regionId($request);

        $p = Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', $regionId))
            ->with([
                'practicante.postulante.comuna',
                'consultorio.region',
                'abogado.usuario',
                'evaluacion',
                'calificaciones.registradaPor',
                'postulacion.documentos',
                'validadaDirectorPor',
            ])->findOrFail($id);

        $evaluacion = $p->evaluacion;
        abort_unless($evaluacion, 404, 'La práctica aún no ha sido evaluada por el Abogado Jefe.');

        $criterios = [];
        foreach (Notas::CRITERIOS as $campo => $etiqueta) {
            $criterios[] = [
                'etiqueta' => $etiqueta,
                'nota' => (float) $evaluacion->{$campo},
                'concepto' => Notas::concepto((float) $evaluacion->{$campo}),
            ];
        }

        // Obtener informe vigente cargado por el postulante desde el SAJ
        $informeSaj = InformesPractica::vigente($p);

        $califPrimera = $p->calificacionPrimeraInstancia;
        $califSegunda = $p->calificacionSegundaInstancia;

        $claveActual = Notas::claveConcepto($p->nota_practica);
        $conceptoActual = Notas::concepto($p->nota_practica);
        $puedeModificar = ! in_array($p->estado, ['remitida_biobio', 'cerrada_historial'], true);

        return view('director.ver', [
            'practica' => $p,
            'postulante' => $p->practicante->postulante,
            'consultorio' => $p->consultorio,
            'evaluacion' => $evaluacion,
            'criterios' => $criterios,
            'conceptoFinal' => $conceptoActual,
            'claveActual' => $claveActual,
            'opcionesNotas' => Notas::OPCIONES,
            'informeSaj' => $informeSaj,
            'califPrimera' => $califPrimera,
            'califSegunda' => $califSegunda,
            'puedeValidar' => $p->validada_director_at === null,
            'puedeModificar' => $puedeModificar,
            'esUcoe' => $request->user()->rol === 'jefe_ucoe' || $p->consultorio->region?->codigo === 'BI',
        ]);
    }

    /**
     * Revisa, valida o modifica formalmente la calificación de la práctica.
     * El Director Regional (o Jefatura / Revisores de UCOE en Biobío) posee la potestad
     * reglamentaria de ratificar la propuesta del Abogado Jefe o modificar la nota final.
     * Al validar/fijar la calificación, habilita a la Secretaría Regional para remitir a Concepción.
     */
    public function validar(Request $request, int $id)
    {
        $this->autorizar($request);
        $regionId = $this->regionId($request);

        $p = Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', $regionId))
            ->with('practicante.postulante', 'consultorio.region', 'evaluacion')
            ->findOrFail($id);

        abort_unless($p->evaluacion, 422, 'La práctica aún no tiene evaluación.');

        if (in_array($p->estado, ['remitida_biobio', 'cerrada_historial'], true)) {
            return back()->withErrors(['validar' => 'El expediente ya fue remitido a Jurídica Central o cerrado, por lo que su calificación no puede ser modificada a nivel regional.']);
        }

        $datos = $request->validate([
            'concepto' => ['nullable', 'string', 'in:'.implode(',', array_keys(Notas::OPCIONES))],
            'observaciones_director' => ['nullable', 'string', 'max:3000'],
        ], [
            'concepto.in' => 'Seleccione una calificación válida (Sobresaliente, Buena, Regular, Mala).',
        ]);

        $notaAnterior = $p->nota_practica;
        $conceptoAnterior = Notas::concepto($notaAnterior);
        $claveAnterior = Notas::claveConcepto($notaAnterior);

        $claveNueva = $datos['concepto'] ?? $claveAnterior;
        $opcionNueva = Notas::OPCIONES[$claveNueva] ?? Notas::OPCIONES['BUENA'];
        $notaNueva = $opcionNueva['valor'];
        $conceptoNuevo = $opcionNueva['nombre'];
        $fueModificada = $claveNueva !== $claveAnterior;

        $obs = trim((string) ($datos['observaciones_director'] ?? ''));

        if ($fueModificada && empty($obs)) {
            return back()->withErrors(['observaciones_director' => 'Debe ingresar la justificación u observaciones de la Dirección al modificar la calificación propuesta.'])->withInput();
        }

        $actualizacion = [
            'validada_director_at' => now(),
            'validada_director_por' => $request->user()->id,
            'observaciones_director' => $obs ?: null,
        ];

        if ($fueModificada) {
            $actualizacion['nota_practica'] = $notaNueva;
        }

        $p->update($actualizacion);

        $notaActual = $p->nota_practica ?? $notaNueva;
        $esAprobatoria = Notas::esAprobatoria($notaActual);
        if ($p->evaluacion) {
            $p->evaluacion->update([
                'promedio' => $notaActual,
                'aprobado' => $esAprobatoria,
            ]);
        }

        $evento = $fueModificada ? 'director.modificar_evaluacion' : 'director.validar_evaluacion';
        Auditoria::registrar($evento, 'practica', $p->id, [
            'director_id' => $request->user()->id,
            'director_rol' => $request->user()->rol,
            'fue_modificada' => $fueModificada,
            'nota_anterior' => $notaAnterior,
            'concepto_anterior' => $conceptoAnterior,
            'nota_fijada' => $notaNueva,
            'concepto_fijado' => $conceptoNuevo,
            'observaciones' => $obs,
        ], $request->user()->id);

        // Notificar a las secretarias de la región
        Usuario::where('rol', 'secretaria')
            ->where('region_id', $regionId)
            ->where('activo', true)
            ->each(function ($sec) use ($p, $conceptoNuevo, $fueModificada) {
                $mensaje = $fueModificada
                    ? "La calificación de {$p->practicante->postulante->nombre_completo} fue revisada y modificada a «{$conceptoNuevo}» por la Dirección Regional. Expediente habilitado para remisión a Concepción."
                    : "La propuesta evaluativa («{$conceptoNuevo}») de {$p->practicante->postulante->nombre_completo} ha sido visada formalmente. Ya puede remitir el legajo a Jurídica Central Biobío.";

                Notificaciones::crear(
                    $sec->id,
                    'aviso',
                    $fueModificada ? 'Calificación modificada por Dirección Regional' : 'Expediente visado por Dirección Regional',
                    $mensaje,
                    'practica',
                    $p->id
                );
            });

        $msgExito = $fueModificada
            ? "Calificación revisada y modificada formalmente a «{$conceptoNuevo}» por la Dirección. La Secretaría Regional ya puede remitir el expediente a Concepción."
            : "Propuesta de evaluación («{$conceptoNuevo}») visada formalmente por la Dirección. La Secretaría Regional ya puede remitir el expediente a Concepción.";

        return back()->with('ok', $msgExito);
    }

    /**
     * Validación por Lotes (Aprobación masiva seleccionando múltiples expedientes).
     */
    public function validarLote(Request $request)
    {
        $this->autorizar($request);
        $regionId = $this->regionId($request);

        $datos = $request->validate([
            'practicas' => ['required', 'array', 'min:1'],
            'practicas.*' => ['integer'],
        ], [
            'practicas.required' => 'Seleccione al menos un expediente para validar.',
            'practicas.min' => 'Seleccione al menos un expediente.',
        ]);

        $practicas = Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', $regionId))
            ->whereIn('id', $datos['practicas'])
            ->whereHas('evaluacion')
            ->whereNull('validada_director_at')
            ->get();

        $aprobadas = 0;
        foreach ($practicas as $p) {
            $p->update([
                'validada_director_at' => now(),
                'validada_director_por' => $request->user()->id,
            ]);

            Auditoria::registrar('director.validar_evaluacion_lote', 'practica', $p->id, [
                'director_id' => $request->user()->id,
                'nota_validada' => $p->nota_practica,
            ], $request->user()->id);

            $aprobadas++;
        }

        // Notificar a secretarias
        Usuario::where('rol', 'secretaria')
            ->where('region_id', $regionId)
            ->where('activo', true)
            ->each(function ($sec) use ($aprobadas) {
                Notificaciones::crear(
                    $sec->id,
                    'aviso',
                    "Lote de {$aprobadas} expedientes visados",
                    "La Dirección Regional visó un lote de {$aprobadas} evaluaciones. Los expedientes quedaron habilitados para remisión a Concepción.",
                    'practica',
                    null
                );
            });

        return back()->with('ok', "Se validaron exitosamente {$aprobadas} expedientes en lote.");
    }

    /**
     * Módulo de Seguimiento de Prácticas Activas de la Región.
     * Grilla de consulta, filtros avanzados, detalle de cupos por unidad y exportación a Excel.
     */
    public function seguimiento(Request $request)
    {
        $this->autorizar($request);
        $regionId = $this->regionId($request);
        $region = Region::findOrFail($regionId);

        $q = trim((string) $request->query('q'));
        $consultorioId = $request->query('consultorio_id');
        $estado = $request->query('estado');

        $base = Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', $regionId))
            ->with(['practicante.postulante', 'consultorio', 'abogado.usuario', 'evaluacion']);

        if ($q !== '') {
            $base->whereHas('practicante.postulante', fn ($p) => $p
                ->where('nombre', 'like', "%$q%")
                ->orWhere('apellido', 'like', "%$q%")
                ->orWhere('rut', 'like', "%$q%"));
        }

        if ($consultorioId && ctype_digit((string) $consultorioId)) {
            $base->where('consultorio_id', (int) $consultorioId);
        }

        if ($estado && in_array($estado, ['por_iniciar', 'activa', 'finalizada', 'remitida_biobio', 'nivelada_biobio', 'rechazada_sancion', 'cerrada_historial', 'cancelada'], true)) {
            $base->where('estado', $estado);
        }

        $practicas = $base->orderBy('fecha_inicio')->paginate(25)->withQueryString();

        $consultorios = Consultorio::where('region_id', $regionId)->where('activo', true)->orderBy('nombre')->get();

        return view('director.seguimiento', [
            'region' => $region,
            'practicas' => $practicas,
            'consultorios' => $consultorios,
            'q' => $q,
            'consultorioId' => $consultorioId,
            'estado' => $estado,
            'esUcoe' => $request->user()->rol === 'jefe_ucoe' || $region->codigo === 'BI',
        ]);
    }

    /**
     * Exportación en Excel del seguimiento de prácticas regionales.
     */
    public function exportarSeguimiento(Request $request)
    {
        $this->autorizar($request);
        $regionId = $this->regionId($request);
        $region = Region::findOrFail($regionId);

        $q = trim((string) $request->query('q'));
        $consultorioId = $request->query('consultorio_id');
        $estado = $request->query('estado');

        $base = Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', $regionId))
            ->with(['practicante.postulante', 'consultorio', 'abogado.usuario']);

        if ($q !== '') {
            $base->whereHas('practicante.postulante', fn ($p) => $p
                ->where('nombre', 'like', "%$q%")
                ->orWhere('apellido', 'like', "%$q%")
                ->orWhere('rut', 'like', "%$q%"));
        }

        if ($consultorioId && ctype_digit((string) $consultorioId)) {
            $base->where('consultorio_id', (int) $consultorioId);
        }

        if ($estado && in_array($estado, ['por_iniciar', 'activa', 'finalizada', 'remitida_biobio', 'nivelada_biobio', 'rechazada_sancion', 'cerrada_historial', 'cancelada'], true)) {
            $base->where('estado', $estado);
        }

        $practicas = $base->orderBy('fecha_inicio')->get();

        $ruta = tempnam(sys_get_temp_dir(), 'seg_dir_').'.xlsx';
        $w = new Writer();
        $w->openToFile($ruta);
        $w->addRow(Row::fromValues([
            'Región',
            'Sede / Consultorio',
            'Practicante',
            'RUT',
            'Correo',
            'Teléfono',
            'Universidad',
            'Abogado Tutor',
            'Fecha Inicio',
            'Fecha Término',
            'Estado',
            'Visada por Dirección',
            'Fecha Visación',
        ]));

        foreach ($practicas as $p) {
            $po = $p->practicante?->postulante;
            $w->addRow(Row::fromValues([
                $region->nombre,
                $p->consultorio?->nombre ?? '—',
                $po ? $po->nombre.' '.$po->apellido : '—',
                $po?->rut ?? '—',
                $po?->email ?? '—',
                $po?->telefono ?? '—',
                $po?->universidad ?? '—',
                $p->abogado?->usuario?->nombre_completo ?? 'Sin asignar',
                $p->fecha_inicio ? $p->fecha_inicio->format('d-m-Y') : '—',
                $p->fecha_termino ? $p->fecha_termino->format('d-m-Y') : '—',
                Practicas::estadoVisible($p),
                $p->validada_director_at ? 'Sí' : 'No',
                $p->validada_director_at ? $p->validada_director_at->format('d-m-Y H:i') : '—',
            ]));
        }

        $w->close();

        return response()->download($ruta, 'seguimiento-practicas-'.$region->codigo.'-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
    }

    /**
     * Consulta de Consultorios y Capacidad de Cupos formativos de la región.
     */
    public function consultorios(Request $request)
    {
        $this->autorizar($request);
        $regionId = $this->regionId($request);
        $region = Region::findOrFail($regionId);

        $consultorios = Consultorio::with('comuna', 'abogados.usuario')
            ->where('region_id', $regionId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->each(function ($c) {
                $c->libres = $c->libres();
                $c->ocupados = $c->ocupados();
                $c->disponibilidad = Practicas::nivelDisponibilidad($c->capacidad, $c->libres);
            });

        return view('director.consultorios', [
            'region' => $region,
            'consultorios' => $consultorios,
            'esUcoe' => $request->user()->rol === 'jefe_ucoe' || $region->codigo === 'BI',
        ]);
    }

    /**
     * Submódulo de Revisión Colegiada (Exclusivo Jefe de UCOE - Región del Biobío).
     * Permite distribuir los expedientes recepcionados entre los 3 revisores designados en la Octava Región:
     * don Luis Núñez, doña Verónica y Graciela.
     */
    public function colegiada(Request $request)
    {
        $this->autorizar($request);
        $regionId = $this->regionId($request);
        $region = Region::findOrFail($regionId);

        $esRevisor = in_array($request->user()->rol, ['jefe_ucoe', 'administrador'], true)
            || ($request->user()->rol === 'secretaria' && $region->codigo === 'BI');
        if (! $esRevisor || $region->codigo !== 'BI') {
            abort(403, 'El submódulo de revisión colegiada es exclusivo de la Jefatura y Revisores de UCOE en la Región del Biobío.');
        }

        $revisorFiltro = $request->query('revisor'); // 'todos', 'luis', 'veronica', 'graciela', 'sin_asignar'
        $nomUser = mb_strtolower($request->user()->nombre_completo);
        $esRevisorEspecifico = str_contains($nomUser, 'cifuentes') || str_contains($nomUser, 'paredes');
        if (! $request->has('revisor') && $esRevisorEspecifico) {
            $revisorFiltro = str_contains($nomUser, 'cifuentes') ? 'veronica' : 'graciela';
        }

        $q = trim((string) $request->query('q'));

        $revisores = [
            'Luis Núñez Oviedo' => 'Luis Núñez Oviedo (Jefe de UCOE)',
            'Verónica Cifuentes' => 'Verónica Cifuentes (Revisora Colegiada)',
            'Graciela Paredes' => 'Graciela Paredes (Revisora Colegiada)',
        ];

        $base = Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', $regionId))
            ->whereHas('evaluacion')
            ->where('estado', '!=', 'cancelada')
            ->with(['practicante.postulante', 'consultorio', 'evaluacion', 'revisorColegiado']);

        if ($q !== '') {
            $base->whereHas('practicante.postulante', fn ($p) => $p
                ->where('nombre', 'like', "%$q%")
                ->orWhere('apellido', 'like', "%$q%")
                ->orWhere('rut', 'like', "%$q%"));
        }

        if ($revisorFiltro && $revisorFiltro !== 'todos') {
            if ($revisorFiltro === 'sin_asignar') {
                $base->whereNull('revisor_colegiado_id')->whereNull('revisor_colegiado_nombre');
            } else {
                $base->where(function ($w) use ($revisorFiltro) {
                    $w->where('revisor_colegiado_nombre', 'like', "%$revisorFiltro%")
                      ->orWhereHas('revisorColegiado', function ($u) use ($revisorFiltro) {
                          $u->where('nombre', 'like', "%$revisorFiltro%")
                            ->orWhere('apellido', 'like', "%$revisorFiltro%");
                      });
                });
            }
        }

        $expedientes = $base->orderByDesc('updated_at')->paginate(20)->withQueryString();

        return view('director.colegiada', [
            'region' => $region,
            'expedientes' => $expedientes,
            'revisores' => $revisores,
            'revisorFiltro' => $revisorFiltro,
            'q' => $q,
        ]);
    }

    /**
     * Resuelve el modelo Usuario asociado a un revisor colegiado por nombre institucional.
     */
    private function buscarRevisorUsuario(string $nombre): ?Usuario
    {
        $nom = mb_strtolower($nombre);
        if (str_contains($nom, 'luis') || str_contains($nom, 'núñez') || str_contains($nom, 'nunez')) {
            return Usuario::where('email', 'ucoe_biobio@cajbiobio.cl')
                ->orWhere(fn ($q) => $q->where('nombre', 'like', '%Luis%')->where('apellido', 'like', '%Núñez%'))
                ->orWhere(fn ($q) => $q->where('nombre', 'like', '%Luis%')->where('apellido', 'like', '%Nunez%'))
                ->first();
        }
        if (str_contains($nom, 'verónica') || str_contains($nom, 'veronica') || str_contains($nom, 'cifuentes')) {
            return Usuario::where('email', 'revisora_veronica@cajbiobio.cl')
                ->orWhere(fn ($q) => $q->where('nombre', 'like', '%Verónica%')->where('apellido', 'like', '%Cifuentes%'))
                ->orWhere(fn ($q) => $q->where('nombre', 'like', '%Veronica%')->where('apellido', 'like', '%Cifuentes%'))
                ->first();
        }
        if (str_contains($nom, 'graciela') || str_contains($nom, 'paredes')) {
            return Usuario::where('email', 'revisora_graciela@cajbiobio.cl')
                ->orWhere(fn ($q) => $q->where('nombre', 'like', '%Graciela%')->where('apellido', 'like', '%Paredes%'))
                ->first();
        }

        return null;
    }

    /**
     * Asigna un revisor colegiado a una práctica en UCOE.
     */
    public function asignarRevisor(Request $request, int $id)
    {
        $this->autorizar($request);
        $regionId = $this->regionId($request);

        $p = Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', $regionId))
            ->with('practicante.postulante')
            ->findOrFail($id);

        $datos = $request->validate([
            'revisor' => ['required', 'string', 'in:Luis Núñez Oviedo,Verónica Cifuentes,Graciela Paredes'],
        ]);

        $revisorUsuario = $this->buscarRevisorUsuario($datos['revisor']);

        $p->update([
            'revisor_colegiado_id' => $revisorUsuario?->id,
            'revisor_colegiado_nombre' => $datos['revisor'],
        ]);

        Auditoria::registrar('ucoe.asignar_revisor', 'practica', $p->id, [
            'revisor' => $datos['revisor'],
            'revisor_id' => $revisorUsuario?->id,
            'asignado_por' => $request->user()->id,
        ], $request->user()->id);

        if ($revisorUsuario) {
            Notificaciones::crear(
                $revisorUsuario->id,
                'aviso',
                'Expediente asignado para revisión colegiada UCOE',
                "Se le ha asignado la práctica de {$p->practicante->postulante->nombre_completo} para revisión colegiada.",
                'practica',
                $p->id
            );
        }

        return back()->with('ok', "Expediente asignado exitosamente al revisor {$datos['revisor']}.");
    }

    /**
     * Asigna un revisor colegiado a un lote de prácticas en UCOE.
     */
    public function asignarRevisoresLote(Request $request)
    {
        $this->autorizar($request);
        $regionId = $this->regionId($request);

        $datos = $request->validate([
            'practicas' => ['required', 'array', 'min:1'],
            'practicas.*' => ['integer'],
            'revisor' => ['required', 'string', 'in:Luis Núñez Oviedo,Verónica Cifuentes,Graciela Paredes'],
        ]);

        $revisorUsuario = $this->buscarRevisorUsuario($datos['revisor']);

        $afectadas = Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', $regionId))
            ->whereIn('id', $datos['practicas'])
            ->update([
                'revisor_colegiado_id' => $revisorUsuario?->id,
                'revisor_colegiado_nombre' => $datos['revisor'],
            ]);

        Auditoria::registrar('ucoe.asignar_revisores_lote', 'practica', null, [
            'revisor' => $datos['revisor'],
            'revisor_id' => $revisorUsuario?->id,
            'cantidad' => $afectadas,
            'asignado_por' => $request->user()->id,
        ], $request->user()->id);

        if ($revisorUsuario && $afectadas > 0) {
            Notificaciones::crear(
                $revisorUsuario->id,
                'aviso',
                'Lote de expedientes asignados para revisión colegiada UCOE',
                "Se le ha asignado un lote de {$afectadas} expedientes para revisión colegiada en la Octava Región.",
                'practica',
                null
            );
        }

        return back()->with('ok', "Se asignaron {$afectadas} expedientes a {$datos['revisor']} correctamente.");
    }
}
