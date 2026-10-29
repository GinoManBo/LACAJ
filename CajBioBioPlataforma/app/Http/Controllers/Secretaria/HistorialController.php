<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Practica;
use App\Services\Practicas;
use App\Support\Notas;
use App\Support\Scope;
use Illuminate\Http\Request;

class HistorialController extends Controller
{
    public function index(Request $request)
    {
        $region = Scope::regionId($request->user());
        $q = trim((string) $request->query('q'));
        $anio = $request->query('anio');

        $base = fn () => Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', $region))->whereIn('estado', ['finalizada', 'cancelada']);

        $practicas = $base()->with('practicante.postulante', 'consultorio', 'abogado.usuario')
            ->when($q !== '', fn ($query) => $query->whereHas('practicante.postulante', fn ($p) => $p
                ->where('nombre', 'like', "%$q%")->orWhere('apellido', 'like', "%$q%")))
            ->when(ctype_digit((string) $anio), fn ($query) => $query->whereYear('fecha_inicio', $anio))
            ->orderByDesc('fecha_termino')->get();

        return view('secretaria.historial.index', [
            'practicas' => $practicas,
            'q' => $q,
            'anio' => $anio,
            'anios' => $base()->selectRaw('YEAR(fecha_inicio) as anio')->distinct()->orderByDesc('anio')->pluck('anio'),
        ]);
    }

    public function ver(Request $request, int $id)
    {
        $p = Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', Scope::regionId($request->user())))
            ->whereIn('estado', ['finalizada', 'cancelada'])
            ->with('practicante.postulante.comuna', 'consultorio', 'abogado.usuario', 'evaluacion', 'calificaciones', 'postulacion.documentos', 'canceladaPor')
            ->findOrFail($id);

        $evaluacion = $p->evaluacion;
        $criterios = [];
        if ($evaluacion) {
            foreach (Notas::CRITERIOS as $campo => $etiqueta) {
                $criterios[] = ['etiqueta' => $etiqueta, 'nota' => (float) $evaluacion->{$campo}, 'concepto' => Notas::concepto((float) $evaluacion->{$campo})];
            }
        }
        $docs = $p->postulacion->documentos;

        return view('secretaria.historial.ver', [
            'practica' => $p,
            'postulante' => $p->practicante->postulante,
            'estadistica' => Practicas::estadistica($p),
            'motivoCancelacion' => Practicas::MOTIVOS_CANCELACION[$p->cancelacion_motivo] ?? null,
            'evaluacion' => $evaluacion,
            'criterios' => $criterios,
            'conceptoFinal' => $p->nota_practica !== null ? Notas::concepto($p->nota_practica) : null,
            'documentosCedula' => $docs->whereIn('tipo', ['cedula_frontal', 'cedula_reverso'])->values(),
            'documentosUniversitarios' => $docs->whereNotIn('tipo', ['cedula_frontal', 'cedula_reverso'])->values(),
        ]);
    }
}
