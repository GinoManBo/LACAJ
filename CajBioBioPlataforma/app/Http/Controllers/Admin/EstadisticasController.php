<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consultorio;
use App\Models\Postulacion;
use App\Models\Practica;
use App\Models\Region;
use App\Services\Auditoria;
use App\Services\Practicas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class EstadisticasController extends Controller
{
    /**
     * Panel de Monitoreo Global, Estadísticas y Reportes Macroinstitucionales.
     */
    public function index(Request $request)
    {
        // 1. Capacidad y Ocupación por Región
        $regiones = Region::operativas()->orderBy('orden')->get();
        $ocupacionPorRegion = [];
        $totalCapacidadNacional = 0;
        $totalOcupadosNacional = 0;

        foreach ($regiones as $reg) {
            $consultorios = Consultorio::where('region_id', $reg->id)->where('activo', true)->get();
            $capacidad = (int) $consultorios->sum('capacidad');
            
            // Ocupados se calculan considerando prácticas activas con fecha_termino > hoy
            $ocupados = Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', $reg->id))
                ->whereIn('estado', ['por_iniciar', 'activa'])
                ->whereDate('fecha_termino', '>', today())
                ->count();

            $libres = max(0, $capacidad - $ocupados);
            $pct = $capacidad > 0 ? (int) round(($ocupados / $capacidad) * 100) : 0;

            $ocupacionPorRegion[] = [
                'region' => $reg,
                'consultorios' => $consultorios->count(),
                'capacidad' => $capacidad,
                'ocupados' => $ocupados,
                'libres' => $libres,
                'porcentaje' => $pct,
                'disponibilidad' => Practicas::nivelDisponibilidad($capacidad, $libres),
            ];

            $totalCapacidadNacional += $capacidad;
            $totalOcupadosNacional += $ocupados;
        }

        $totalLibresNacional = max(0, $totalCapacidadNacional - $totalOcupadosNacional);
        $totalPctNacional = $totalCapacidadNacional > 0
            ? (int) round(($totalOcupadosNacional / $totalCapacidadNacional) * 100)
            : 0;

        // 2. Métricas Anuales de Tiempos Promedio de Asignación (Postulación -> Inicio de Práctica)
        $practicasAsignadas = Practica::whereNotNull('postulacion_id')
            ->whereNotNull('fecha_inicio')
            ->with(['postulacion', 'consultorio.region'])
            ->get();

        $tiemposPorAnio = [];
        $tiemposPorRegion = [];

        foreach ($practicasAsignadas as $p) {
            $fechaPostulacion = $p->postulacion?->fecha_postulacion ?? $p->postulacion?->created_at?->toDateString();
            if (! $fechaPostulacion || ! $p->fecha_inicio) {
                continue;
            }

            $inicio = $p->fecha_inicio->copy()->startOfDay();
            $post = \Carbon\Carbon::parse($fechaPostulacion)->startOfDay();
            $dias = max(0, (int) $post->diffInDays($inicio, false));

            $anio = $p->fecha_inicio->year;
            if (! isset($tiemposPorAnio[$anio])) {
                $tiemposPorAnio[$anio] = ['total' => 0, 'suma_dias' => 0, 'min' => 9999, 'max' => 0];
            }
            $tiemposPorAnio[$anio]['total']++;
            $tiemposPorAnio[$anio]['suma_dias'] += $dias;
            $tiemposPorAnio[$anio]['min'] = min($tiemposPorAnio[$anio]['min'], $dias);
            $tiemposPorAnio[$anio]['max'] = max($tiemposPorAnio[$anio]['max'], $dias);

            $regNombre = $p->consultorio?->region?->nombre ?? 'Sin región';
            if (! isset($tiemposPorRegion[$regNombre])) {
                $tiemposPorRegion[$regNombre] = ['total' => 0, 'suma_dias' => 0];
            }
            $tiemposPorRegion[$regNombre]['total']++;
            $tiemposPorRegion[$regNombre]['suma_dias'] += $dias;
        }

        ksort($tiemposPorAnio);
        foreach ($tiemposPorAnio as $a => &$dat) {
            $dat['promedio'] = $dat['total'] > 0 ? (int) round($dat['suma_dias'] / $dat['total']) : 0;
            if ($dat['min'] === 9999) {
                $dat['min'] = 0;
            }
        }
        unset($dat);

        foreach ($tiemposPorRegion as $r => &$dat) {
            $dat['promedio'] = $dat['total'] > 0 ? (int) round($dat['suma_dias'] / $dat['total']) : 0;
        }
        unset($dat);

        // 3. Métricas Anuales de Cancelaciones por Causal
        $practicasCanceladas = Practica::where('estado', 'cancelada')
            ->select('id', 'cancelacion_motivo', 'cancelacion_fecha', 'updated_at', 'created_at')
            ->get();

        $cancelacionesPorAnio = [];
        foreach ($practicasCanceladas as $pc) {
            $fecha = $pc->cancelacion_fecha ?? $pc->updated_at ?? $pc->created_at;
            $anio = $fecha ? $fecha->year : now()->year;

            if (! isset($cancelacionesPorAnio[$anio])) {
                $cancelacionesPorAnio[$anio] = [
                    'total' => 0,
                    'conducta' => 0,
                    'decision' => 0,
                    'error_registro' => 0,
                    'otro' => 0,
                ];
            }

            $motivo = $pc->cancelacion_motivo ?: 'otro';
            if (! isset($cancelacionesPorAnio[$anio][$motivo])) {
                $motivo = 'otro';
            }

            $cancelacionesPorAnio[$anio]['total']++;
            $cancelacionesPorAnio[$anio][$motivo]++;
        }
        krsort($cancelacionesPorAnio);

        // 4. Totales macro
        $totalesMacro = [
            'practicas_activas' => Practica::where('estado', 'activa')->count(),
            'practicas_por_iniciar' => Practica::where('estado', 'por_iniciar')->count(),
            'postulaciones_pendientes' => Postulacion::where('estado', 'pendiente')->count(),
            'practicas_finalizadas' => Practica::whereIn('estado', ['finalizada', 'remitida_biobio', 'nivelada_biobio'])->count(),
            'practicas_cerradas' => Practica::where('estado', 'cerrada_historial')->count(),
            'practicas_canceladas' => $practicasCanceladas->count(),
            'capacidad_nacional' => $totalCapacidadNacional,
            'ocupados_nacional' => $totalOcupadosNacional,
            'libres_nacional' => $totalLibresNacional,
            'pct_ocupacion_nacional' => $totalPctNacional,
            'tiempo_promedio_general' => count($practicasAsignadas) > 0 && array_sum(array_column($tiemposPorAnio, 'total')) > 0
                ? (int) round(array_sum(array_column($tiemposPorAnio, 'suma_dias')) / array_sum(array_column($tiemposPorAnio, 'total')))
                : 0,
        ];

        return view('admin.estadisticas.index', [
            'totalesMacro' => $totalesMacro,
            'ocupacionPorRegion' => $ocupacionPorRegion,
            'tiemposPorAnio' => $tiemposPorAnio,
            'tiemposPorRegion' => $tiemposPorRegion,
            'cancelacionesPorAnio' => $cancelacionesPorAnio,
        ]);
    }

    /**
     * Exportación de informe estadístico institucional a Excel.
     */
    public function exportar(Request $request)
    {
        $regiones = Region::operativas()->orderBy('orden')->get();

        $ruta = tempnam(sys_get_temp_dir(), 'stats_caj_').'.xlsx';
        $w = new Writer();
        $w->openToFile($ruta);

        // Hoja 1: Ocupación por Región
        $w->addRow(Row::fromValues([
            'REPORTE ESTADÍSTICO CONSOLIDADO — CAJ BIOBÍO ('.now()->format('d-m-Y H:i').')',
        ]));
        $w->addRow(Row::fromValues([]));
        $w->addRow(Row::fromValues([
            'Región',
            'Sedes Activas',
            'Capacidad Total Cupos',
            'Cupos Ocupados',
            'Cupos Libres',
            '% Ocupación',
        ]));

        foreach ($regiones as $reg) {
            $consultorios = Consultorio::where('region_id', $reg->id)->where('activo', true)->get();
            $capacidad = (int) $consultorios->sum('capacidad');
            $ocupados = Practica::whereHas('consultorio', fn ($c) => $c->where('region_id', $reg->id))
                ->whereIn('estado', ['por_iniciar', 'activa'])
                ->whereDate('fecha_termino', '>', today())
                ->count();
            $libres = max(0, $capacidad - $ocupados);
            $pct = $capacidad > 0 ? (int) round(($ocupados / $capacidad) * 100) : 0;

            $w->addRow(Row::fromValues([
                $reg->nombre.' ('.$reg->codigo.')',
                $consultorios->count(),
                $capacidad,
                $ocupados,
                $libres,
                $pct.'%',
            ]));
        }

        $w->addRow(Row::fromValues([]));
        $w->addRow(Row::fromValues(['HISTORIAL ANUAL DE CANCELACIONES']));
        $w->addRow(Row::fromValues([
            'Año',
            'Total Cancelaciones',
            'Conducta / Sanción',
            'Decisión Personal / Renuncia',
            'Error de Registro / Asignación',
            'Otro Motivo',
        ]));

        $canceladas = Practica::where('estado', 'cancelada')->get();
        $porAnio = [];
        foreach ($canceladas as $pc) {
            $a = $pc->cancelacion_fecha ? $pc->cancelacion_fecha->year : ($pc->created_at ? $pc->created_at->year : now()->year);
            if (! isset($porAnio[$a])) {
                $porAnio[$a] = ['total' => 0, 'conducta' => 0, 'decision' => 0, 'error_registro' => 0, 'otro' => 0];
            }
            $motivo = in_array($pc->cancelacion_motivo, ['conducta', 'decision', 'error_registro', 'otro'], true) ? $pc->cancelacion_motivo : 'otro';
            $porAnio[$a]['total']++;
            $porAnio[$a][$motivo]++;
        }
        krsort($porAnio);

        foreach ($porAnio as $a => $d) {
            $w->addRow(Row::fromValues([
                $a,
                $d['total'],
                $d['conducta'],
                $d['decision'],
                $d['error_registro'],
                $d['otro'],
            ]));
        }

        $w->close();

        Auditoria::registrar('estadisticas.exportar_excel', 'estadistica', null, [
            'motivo' => 'Exportación consolidada de métricas y estadísticas institucionales',
        ], $request->user()->id);

        return response()->download($ruta, 'estadisticas_globales_caj_'.now()->format('Ymd_His').'.xlsx')->deleteFileAfterSend();
    }
}
