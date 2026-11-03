<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Usuario;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class AuditoriaController extends Controller
{
    /**
     * Visor del Log Inmutable de Auditoría Institucional.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $accion = $request->query('accion');
        $entidad = $request->query('entidad');
        $usuarioId = $request->query('usuario_id');
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $base = Auditoria::with('usuario');

        if ($q !== '') {
            $base->where(function ($query) use ($q) {
                $query->where('accion', 'like', "%{$q}%")
                    ->orWhere('entidad', 'like', "%{$q}%")
                    ->orWhere('ip', 'like', "%{$q}%")
                    ->orWhereHas('usuario', fn ($u) => $u
                        ->where('nombre', 'like', "%{$q}%")
                        ->orWhere('apellido', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('rut', 'like', "%{$q}%"));
            });
        }

        if ($accion) {
            $base->where('accion', $accion);
        }

        if ($entidad) {
            $base->where('entidad', $entidad);
        }

        if ($usuarioId && ctype_digit((string) $usuarioId)) {
            $base->where('usuario_id', (int) $usuarioId);
        }

        if ($desde) {
            $base->whereDate('created_at', '>=', $desde);
        }

        if ($hasta) {
            $base->whereDate('created_at', '<=', $hasta);
        }

        $registros = $base->orderByDesc('id')->paginate(30)->withQueryString();

        $accionesDisponibles = Auditoria::select('accion')->distinct()->orderBy('accion')->pluck('accion');
        $entidadesDisponibles = Auditoria::select('entidad')->distinct()->orderBy('entidad')->pluck('entidad');

        $totales = [
            'total' => Auditoria::count(),
            'ultimos_30d' => Auditoria::where('created_at', '>=', now()->subDays(30))->count(),
            'usuarios_activos' => Auditoria::whereNotNull('usuario_id')->distinct('usuario_id')->count('usuario_id'),
        ];

        return view('admin.auditoria.index', [
            'registros' => $registros,
            'acciones' => $accionesDisponibles,
            'entidades' => $entidadesDisponibles,
            'totales' => $totales,
            'q' => $q,
            'accionSeleccionada' => $accion,
            'entidadSeleccionada' => $entidad,
            'usuarioId' => $usuarioId,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }

    /**
     * Exportación del Log de Auditoría a archivo Excel (.xlsx).
     */
    public function exportar(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $accion = $request->query('accion');
        $entidad = $request->query('entidad');
        $usuarioId = $request->query('usuario_id');
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $base = Auditoria::with('usuario');

        if ($q !== '') {
            $base->where(function ($query) use ($q) {
                $query->where('accion', 'like', "%{$q}%")
                    ->orWhere('entidad', 'like', "%{$q}%")
                    ->orWhere('ip', 'like', "%{$q}%")
                    ->orWhereHas('usuario', fn ($u) => $u
                        ->where('nombre', 'like', "%{$q}%")
                        ->orWhere('apellido', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('rut', 'like', "%{$q}%"));
            });
        }

        if ($accion) {
            $base->where('accion', $accion);
        }

        if ($entidad) {
            $base->where('entidad', $entidad);
        }

        if ($usuarioId && ctype_digit((string) $usuarioId)) {
            $base->where('usuario_id', (int) $usuarioId);
        }

        if ($desde) {
            $base->whereDate('created_at', '>=', $desde);
        }

        if ($hasta) {
            $base->whereDate('created_at', '<=', $hasta);
        }

        $registros = $base->orderByDesc('id')->limit(5000)->get();

        $ruta = tempnam(sys_get_temp_dir(), 'audit_').'.xlsx';
        $w = new Writer();
        $w->openToFile($ruta);

        $w->addRow(Row::fromValues([
            'ID',
            'Fecha y Hora',
            'Usuario Responsable',
            'RUT',
            'Rol',
            'Correo',
            'Acción Ejecutada',
            'Entidad',
            'ID Entidad',
            'Dirección IP',
            'Detalles / Payload JSON',
        ]));

        foreach ($registros as $r) {
            $u = $r->usuario;
            $w->addRow(Row::fromValues([
                $r->id,
                $r->created_at ? $r->created_at->format('d-m-Y H:i:s') : '—',
                $u ? $u->nombre.' '.$u->apellido : 'Sistema / Anónimo',
                $u?->rut ?? '—',
                $u?->rol ?? '—',
                $u?->email ?? '—',
                $r->accion,
                $r->entidad,
                $r->entidad_id ?? '—',
                $r->ip ?? '—',
                $r->datos ? json_encode($r->datos, JSON_UNESCAPED_UNICODE) : '—',
            ]));
        }

        $w->close();

        return response()->download($ruta, 'log_auditoria_caj_'.now()->format('Ymd_His').'.xlsx')->deleteFileAfterSend();
    }
}
