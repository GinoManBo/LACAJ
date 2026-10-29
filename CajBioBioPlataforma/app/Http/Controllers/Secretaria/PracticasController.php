<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Consultorio;
use App\Models\Practica;
use App\Services\Practicas;
use App\Support\Scope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class PracticasController extends Controller
{
    public function index(Request $request)
    {
        $region = Scope::regionId($request->user());
        $practicas = $this->consulta($request, $region)->get()->each(fn ($p) => $this->decorar($p));
        $capacidad = Consultorio::where('region_id', $region)->where('activo', true)->sum('capacidad');
        $ocupados = Practica::whereHas('consultorio', fn ($q) => $q->where('region_id', $region))->whereIn('estado', ['por_iniciar', 'activa'])->count();

        return view('secretaria.practicas.index', [
            'practicas' => $practicas,
            'q' => trim((string) $request->query('q')),
            'cuposTotales' => (int) $capacidad,
            'cuposDisponibles' => max(0, (int) $capacidad - $ocupados),
            'motivos' => Practicas::MOTIVOS_CANCELACION,
        ]);
    }

    public function ver(Request $request, int $id)
    {
        $p = $this->practica($request, $id)->load('practicante.postulante.comuna', 'consultorio.comuna', 'abogado.usuario', 'documentos');
        $this->decorar($p);

        return view('secretaria.practicas.ver', ['practica' => $p, 'motivos' => Practicas::MOTIVOS_CANCELACION]);
    }

    public function estadistica(Request $request, int $id)
    {
        $p = $this->practica($request, $id)->load('practicante.postulante', 'consultorio', 'abogado.usuario');
        $this->decorar($p);

        return view('secretaria.practicas.estadistica', ['practica' => $p, 'estadistica' => $p->estadistica]);
    }

    public function exportar(Request $request)
    {
        $region = Scope::regionId($request->user());
        $practicas = $this->consulta($request, $region)->get();

        $ruta = tempnam(sys_get_temp_dir(), 'prac').'.xlsx';
        $w = new Writer();
        $w->openToFile($ruta);
        $w->addRow(Row::fromValues(['Practicante', 'RUT', 'Universidad', 'Consultorio', 'Abogado tutor', 'Inicio', 'Término', 'Estado', 'Duración (días)', 'Días transcurridos', 'Días restantes', 'Avance (%)']));
        foreach ($practicas as $p) {
            $e = Practicas::estadistica($p);
            $po = $p->practicante->postulante;
            $w->addRow(Row::fromValues([
                $po->nombre.' '.$po->apellido, $po->rut, $po->universidad, $p->consultorio->nombre,
                $p->abogado ? $p->abogado->usuario->nombre.' '.$p->abogado->usuario->apellido : 'Sin asignar',
                $p->fecha_inicio->format('d-m-Y'), $p->fecha_termino->format('d-m-Y'), Practicas::estadoVisible($p),
                $e['total'], $e['transcurridos'], $e['restantes'], $e['porcentaje'],
            ]));
        }
        $w->close();

        return response()->download($ruta, 'practicas-activas-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
    }

    public function cancelar(Request $request, int $id)
    {
        $p = $this->practica($request, $id)->load('practicante.postulante', 'abogado');
        $datos = $request->validate([
            'motivo' => ['required', 'in:'.implode(',', array_keys(Practicas::MOTIVOS_CANCELACION))],
            'detalle' => ['nullable', 'string', 'max:2000'],
        ], ['motivo.required' => 'Seleccione el motivo de cancelación.', 'motivo.in' => 'Seleccione un motivo válido.']);

        Practicas::cancelar($p, $request->user(), $datos['motivo'], $datos['detalle'] ?? null);

        return redirect()->route('secretaria.practicas')->with('ok', 'Práctica cancelada.')
            ->with('documento_cancelacion', route('secretaria.cancelacion.documento', $p->id));
    }

    public function documentoCancelacion(Request $request, int $id)
    {
        $p = $this->practica($request, $id, ['cancelada'])->load('practicante.postulante', 'consultorio.region', 'abogado.usuario', 'canceladaPor');

        $pdf = Pdf::loadView('pdf.cancelacion', [
            'practica' => $p, 'postulante' => $p->practicante->postulante,
            'motivo' => Practicas::MOTIVOS_CANCELACION[$p->cancelacion_motivo] ?? $p->cancelacion_motivo,
            'emitidoPor' => $request->user(),
        ]);

        return $pdf->download('cancelacion-practica-'.$p->id.'.pdf');
    }

    private function consulta(Request $request, ?int $region)
    {
        $q = trim((string) $request->query('q'));

        return Practica::with('practicante.postulante', 'consultorio', 'abogado.usuario')
            ->whereHas('consultorio', fn ($c) => $c->where('region_id', $region))
            ->whereIn('estado', ['por_iniciar', 'activa'])
            ->when($q !== '', fn ($query) => $query->whereHas('practicante.postulante', fn ($p) => $p
                ->where('nombre', 'like', "%$q%")->orWhere('apellido', 'like', "%$q%")))
            ->orderBy('fecha_termino');
    }

    private function practica(Request $request, int $id, array $estados = ['por_iniciar', 'activa']): Practica
    {
        return Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', Scope::regionId($request->user())))
            ->whereIn('estado', $estados)->findOrFail($id);
    }

    private function decorar(Practica $p): Practica
    {
        $p->estadistica = Practicas::estadistica($p);
        $p->estado_visible = Practicas::estadoVisible($p);

        return $p;
    }
}
