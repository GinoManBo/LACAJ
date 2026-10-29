<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Models\Practica;
use App\Services\EvaluacionesPractica;
use App\Services\InformesPractica;
use App\Services\PracticaVista;
use App\Support\Notas;
use App\Support\Scope;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TutorController extends Controller
{
    public const ESTADOS_FILTRO = ['por_iniciar', 'activa', 'por_terminar', 'finalizada', 'cancelada'];

    private function practicas(Request $request)
    {
        return Practica::where('abogado_id', Scope::abogadoId($request->user()))
            ->with(['practicante.postulante', 'consultorio.comuna', 'evaluacion']);
    }

    public function inicio(Request $request)
    {
        $request->validate(['estado' => ['nullable', Rule::in(self::ESTADOS_FILTRO)], 'q' => ['nullable', 'string', 'max:100']]);
        $usuario = $request->user();
        $abogado = $usuario->abogado()->with('consultorios')->first();

        $q = $this->practicas($request)->orderBy('fecha_termino');
        if ($texto = trim((string) $request->query('q'))) {
            $q->whereHas('practicante.postulante', fn ($w) => $w->where(fn ($x) => $x
                ->where('nombre', 'like', "%{$texto}%")->orWhere('apellido', 'like', "%{$texto}%")
                ->orWhere('universidad', 'like', "%{$texto}%")));
        }
        $estado = $request->query('estado');
        if ($estado === 'por_terminar') {
            $q->where('estado', 'activa')->whereBetween('fecha_termino', [today(), today()->addDays(config('caj_documentos.duracion_dias_por_terminar'))]);
        } elseif ($estado) {
            $q->where('estado', $estado);
        }

        $practicas = $q->get()->each(fn ($p) => $p->setAttribute('estado_mostrado', PracticaVista::estadoMostrado($p)));

        return view('tutor.inicio', [
            'abogado' => $abogado,
            'usuario' => $usuario,
            'consultorios' => $abogado->consultorios,
            'practicas' => $practicas,
            'activos' => $this->practicas($request)->where('estado', 'activa')->count(),
            'noLeidas' => Notificacion::where('usuario_destino_id', $usuario->id)->whereNull('leida_at')->count(),
            'filtros' => ['q' => $request->query('q'), 'estado' => $estado],
            'estadosFiltro' => self::ESTADOS_FILTRO,
        ]);
    }

    public function ver(Request $request, int $practica)
    {
        $p = $this->practicas($request)->with(['abogado.usuario', 'calificaciones'])->findOrFail($practica);

        return view('tutor.practicante', [
            'practica' => $p,
            'postulante' => $p->practicante->postulante,
            'estadoMostrado' => PracticaVista::estadoMostrado($p),
            'porTerminar' => PracticaVista::porTerminar($p),
            'diasRestantes' => PracticaVista::diasRestantes($p),
            'informe' => InformesPractica::vigente($p),
            'bloqueoEvaluacion' => EvaluacionesPractica::bloqueo($p),
            'evaluacion' => $p->evaluacion,
            'criterios' => Notas::CRITERIOS,
            'concepto' => $p->evaluacion ? Notas::concepto($p->evaluacion->promedio) : null,
        ]);
    }

    public function evaluar(Request $request, int $practica)
    {
        $p = $this->practicas($request)->findOrFail($practica);

        $request->merge(collect(array_keys(Notas::CRITERIOS))->mapWithKeys(
            fn ($k) => [$k => is_string($request->input($k)) ? str_replace(',', '.', trim($request->input($k))) : $request->input($k)]
        )->all());

        $reglas = ['observaciones' => ['nullable', 'string', 'max:3000']];
        foreach (Notas::CRITERIOS as $clave => $etiqueta) {
            $reglas[$clave] = ['required', 'numeric', 'between:1,7', fn ($a, $v, $fail) => abs(round((float) $v, 1) - (float) $v) < 1e-9 ?: $fail('Use un decimal (por ejemplo 5,5).')];
        }
        $datos = $request->validate($reglas, [
            'required' => 'Califique este criterio.',
            'numeric' => 'La nota debe ser un número entre 1,0 y 7,0.',
            'between' => 'La nota debe estar entre 1,0 y 7,0.',
        ]);

        $puntajes = collect(array_keys(Notas::CRITERIOS))->mapWithKeys(fn ($k) => [$k => round((float) $datos[$k], 1)])->all();
        $ev = EvaluacionesPractica::evaluar($p, $request->user()->abogado, $puntajes, $datos['observaciones'] ?? null);

        return redirect()->route('tutor.practicantes.ver', $p->id)->with('ok', $ev->aprobado ? 'Evaluación enviada: HA APROBADO.' : 'Evaluación enviada: NO HA APROBADO.');
    }

    public function descargarInforme(Request $request, int $practica)
    {
        $p = $this->practicas($request)->findOrFail($practica);
        $doc = InformesPractica::vigente($p);
        abort_unless($doc && \App\Services\Almacenamiento::existe($doc->nombre_almacenado), 404);

        return response()->download(\App\Services\Almacenamiento::ruta($doc->nombre_almacenado), $doc->nombre_original, ['Content-Type' => $doc->mime]);
    }
}
