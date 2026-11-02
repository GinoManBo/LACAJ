<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Calificacion;
use App\Models\Notificacion;
use App\Models\Practica;
use App\Services\Almacenamiento;
use App\Services\Auditoria;
use App\Services\EvaluacionesPractica;
use App\Services\InformesPractica;
use App\Services\Notificaciones;
use App\Services\PracticaVista;
use App\Support\Notas;
use App\Support\Scope;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TutorController extends Controller
{
    public const ESTADOS_FILTRO = ['por_iniciar', 'activa', 'por_terminar', 'finalizada', 'cancelada'];

    /**
     * Consulta base según el alcance del Abogado Jefe:
     * - Abogado Jefe de 1ª Instancia: solo ve las prácticas de su propio consultorio asignado.
     * - Abogado Jefe de 2ª Instancia: visibilidad regional transversal (todos los postulantes de su región).
     */
    private function practicas(Request $request)
    {
        $usuario = $request->user();
        $abogado = $usuario->abogado;
        abort_unless($abogado, 403, 'Usuario sin ficha de abogado asociada.');

        if ($abogado->es_segunda_instancia) {
            return Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', $abogado->region_id))
                ->with(['practicante.postulante', 'consultorio.region', 'consultorio.comuna', 'evaluacion', 'calificaciones', 'abogado.usuario']);
        }

        return Practica::where('abogado_id', $abogado->id)
            ->with(['practicante.postulante', 'consultorio.region', 'consultorio.comuna', 'evaluacion', 'calificaciones', 'abogado.usuario']);
    }

    public function inicio(Request $request)
    {
        $request->validate([
            'estado' => ['nullable', Rule::in(self::ESTADOS_FILTRO)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $usuario = $request->user();
        $abogado = $usuario->abogado()->with('consultorios')->first();
        abort_unless($abogado, 403);

        $esSegundaInstancia = (bool) $abogado->es_segunda_instancia;

        $q = $this->practicas($request)->orderBy('fecha_termino');
        if ($texto = trim((string) $request->query('q'))) {
            $q->whereHas('practicante.postulante', fn ($w) => $w->where(fn ($x) => $x
                ->where('nombre', 'like', "%{$texto}%")
                ->orWhere('apellido', 'like', "%{$texto}%")
                ->orWhere('rut', 'like', "%{$texto}%")
                ->orWhere('universidad', 'like', "%{$texto}%")));
        }

        $estado = $request->query('estado');
        if ($estado === 'por_terminar') {
            $q->where('estado', 'activa')->whereBetween('fecha_termino', [today(), today()->addDays(config('caj_documentos.duracion_dias_por_terminar'))]);
        } elseif ($estado) {
            $q->where('estado', $estado);
        }

        $practicas = $q->get()->each(fn ($p) => $p->setAttribute('estado_mostrado', PracticaVista::estadoMostrado($p)));

        $metricas = [];
        if ($esSegundaInstancia) {
            $baseRegion = $this->practicas($request);
            $metricas = [
                'total_region' => (clone $baseRegion)->count(),
                'evaluadas_corte' => (clone $baseRegion)->whereHas('calificaciones', fn ($c) => $c->where('instancia', 2))->count(),
                'pendientes_corte' => (clone $baseRegion)->whereDoesntHave('calificaciones', fn ($c) => $c->where('instancia', 2))->count(),
            ];
        } else {
            $metricas = [
                'activos' => $this->practicas($request)->where('estado', 'activa')->count(),
                'por_terminar' => $this->practicas($request)->where('estado', 'activa')->whereBetween('fecha_termino', [today(), today()->addDays(config('caj_documentos.duracion_dias_por_terminar'))])->count(),
                'finalizadas' => $this->practicas($request)->where('estado', 'finalizada')->count(),
            ];
        }

        return view('tutor.inicio', [
            'abogado' => $abogado,
            'usuario' => $usuario,
            'consultorios' => $abogado->consultorios,
            'practicas' => $practicas,
            'esSegundaInstancia' => $esSegundaInstancia,
            'metricas' => $metricas,
            'noLeidas' => Notificacion::where('usuario_destino_id', $usuario->id)->whereNull('leida_at')->count(),
            'filtros' => ['q' => $request->query('q'), 'estado' => $estado],
            'estadosFiltro' => self::ESTADOS_FILTRO,
        ]);
    }

    public function ver(Request $request, int $practica)
    {
        $abogado = $request->user()->abogado;
        abort_unless($abogado, 403);

        $p = $this->practicas($request)
            ->with(['abogado.usuario', 'calificaciones.registradaPor', 'evaluacion'])
            ->findOrFail($practica);

        $calif1 = $p->calificaciones->firstWhere('instancia', 1);
        $calif2 = $p->calificaciones->firstWhere('instancia', 2);

        return view('tutor.practicante', [
            'practica' => $p,
            'postulante' => $p->practicante->postulante,
            'estadoMostrado' => PracticaVista::estadoMostrado($p),
            'porTerminar' => PracticaVista::porTerminar($p),
            'diasRestantes' => PracticaVista::diasRestantes($p),
            'informe' => InformesPractica::vigente($p),
            'bloqueoEvaluacion' => EvaluacionesPractica::bloqueo($p),
            'evaluacion' => $p->evaluacion,
            'calif1' => $calif1,
            'calif2' => $calif2,
            'concepto1' => $calif1 ? Notas::concepto($calif1->nota) : ($p->evaluacion ? Notas::concepto($p->evaluacion->promedio) : null),
            'concepto2' => $calif2 ? Notas::concepto($calif2->nota) : null,
            'criterios' => Notas::CRITERIOS,
            'opcionesNotas' => Notas::OPCIONES,
            'esSegundaInstancia' => (bool) $abogado->es_segunda_instancia,
        ]);
    }

    /**
     * Evaluación de Primera Instancia (Consultorio Jurídico):
     * Evalúa los 6 meses continuos sobre los 7 criterios reglamentarios expresados en palabras.
     * Cruce de permisos: Bloqueado para el Abogado Jefe de Segunda Instancia.
     */
    public function evaluar(Request $request, int $practica)
    {
        $abogado = $request->user()->abogado;
        abort_unless($abogado, 403);

        if ($abogado->es_segunda_instancia) {
            abort(403, 'El Abogado Jefe de 2ª Instancia no tiene atribución para modificar la evaluación de 1ª instancia.');
        }

        $p = $this->practicas($request)->findOrFail($practica);

        $reglas = ['observaciones' => ['nullable', 'string', 'max:3000']];
        foreach (Notas::CRITERIOS as $clave => $etiqueta) {
            $reglas[$clave] = ['required'];
        }

        $datos = $request->validate($reglas, [
            'required' => 'Califique este criterio.',
        ]);

        $puntajes = [];
        foreach (Notas::CRITERIOS as $clave => $etiqueta) {
            $puntajes[$clave] = Notas::valorDesdeConcepto($datos[$clave]);
        }

        $ev = EvaluacionesPractica::evaluar($p, $abogado, $puntajes, $datos['observaciones'] ?? null);
        $concepto = Notas::concepto($ev->promedio);

        return redirect()->route('tutor.practicantes.ver', $p->id)
            ->with('ok', 'Evaluación de 1ª Instancia enviada en concepto «'.$concepto.'» ('.($ev->aprobado ? 'Aprobado' : 'No aprobado').').');
    }

    /**
     * Evaluación de Segunda Instancia (Corte de Apelaciones):
     * Califica los alegatos ante el Tribunal de Alzada expresados en conceptos cualitativos y observaciones.
     * Cruce de permisos: Bloqueado para el Abogado Jefe de 1ª Instancia de consultorio.
     */
    public function evaluarSegundaInstancia(Request $request, int $practica)
    {
        $abogado = $request->user()->abogado;
        abort_unless($abogado, 403);

        if (! $abogado->es_segunda_instancia) {
            abort(403, 'Solo el Abogado Jefe de 2ª Instancia (Corte de Apelaciones) puede emitir esta calificación.');
        }

        $p = $this->practicas($request)->findOrFail($practica);

        $datos = $request->validate([
            'concepto' => ['required', 'string', 'in:'.implode(',', array_keys(Notas::OPCIONES))],
            'observaciones' => ['required', 'string', 'min:10', 'max:3000'],
        ], [
            'concepto.required' => 'Seleccione una calificación en palabras para segunda instancia.',
            'concepto.in' => 'Seleccione un concepto cualitativo válido.',
            'observaciones.required' => 'Ingrese las observaciones y antecedentes de los alegatos en la Corte de Apelaciones.',
            'observaciones.min' => 'Las observaciones deben contener al menos 10 caracteres.',
        ]);

        $opcion = Notas::OPCIONES[$datos['concepto']];
        Calificacion::updateOrCreate(
            ['practica_id' => $p->id, 'instancia' => 2],
            [
                'nota' => $opcion['valor'],
                'fecha' => now()->toDateString(),
                'registrada_por' => $request->user()->id,
                'observaciones' => 'Calificación 2ª Instancia (Corte de Apelaciones - '.$opcion['nombre'].'): '.$datos['observaciones'],
            ]
        );

        $p->update(['abogado_segunda_instancia_id' => $abogado->id]);

        Auditoria::registrar('practica.segunda_instancia_evaluar', 'practica', $p->id, [
            'concepto' => $opcion['nombre'],
            'abogado_id' => $abogado->id,
        ], $request->user()->id);

        if ($usuarioId = $p->practicante?->usuario_id) {
            Notificaciones::crear(
                $usuarioId,
                'aviso',
                'Calificación de Segunda Instancia (Corte de Apelaciones)',
                'El Abogado Jefe de Segunda Instancia ha emitido su evaluación de alegatos ante la Corte de Apelaciones en concepto «'.$opcion['nombre'].'».',
                'practica',
                $p->id
            );
        }

        return redirect()->route('tutor.practicantes.ver', $p->id)
            ->with('ok', 'Calificación de 2ª Instancia (Corte de Apelaciones) registrada en concepto «'.$opcion['nombre'].'».');
    }

    /**
     * Registro de observaciones formales al informe final para subsanación previa por parte del estudiante.
     */
    public function observarInforme(Request $request, int $practica)
    {
        $p = $this->practicas($request)->findOrFail($practica);

        $datos = $request->validate([
            'observaciones' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'observaciones.required' => 'Ingrese las observaciones al informe de práctica.',
            'observaciones.min' => 'Las observaciones deben contener al menos 5 caracteres.',
        ]);

        InformesPractica::observar($p, $request->user(), $datos['observaciones']);

        return back()->with('ok', 'Observaciones enviadas formalmente al postulante.');
    }

    public function descargarInforme(Request $request, int $practica)
    {
        $p = $this->practicas($request)->findOrFail($practica);
        $doc = InformesPractica::vigente($p);
        abort_unless($doc && Almacenamiento::existe($doc->nombre_almacenado), 404);

        return response()->download(Almacenamiento::ruta($doc->nombre_almacenado), $doc->nombre_original, ['Content-Type' => $doc->mime]);
    }
}
