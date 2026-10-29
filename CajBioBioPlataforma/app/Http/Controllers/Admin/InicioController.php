<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consultorio;
use App\Models\Postulacion;
use App\Models\Practica;
use App\Models\Practicante;
use App\Models\Usuario;

class InicioController extends Controller
{
    public function __invoke()
    {
        $consultorios = Consultorio::where('activo', true)->withCount([
            'practicas as ocupados' => fn ($q) => $q->whereIn('estado', ['por_iniciar', 'activa']),
        ])->get();
        $cupos = (int) $consultorios->sum('capacidad');
        $ocupados = (int) $consultorios->sum('ocupados');

        return view('admin.inicio', [
            'indicadores' => [
                'practicas_activas' => Practica::where('estado', 'activa')->count(),
                'postulaciones_pendientes' => Postulacion::where('estado', 'pendiente')->count(),
                'ocupacion_pct' => $cupos > 0 ? (int) round($ocupados * 100 / $cupos) : 0,
                'practicantes_registrados' => Practicante::count(),
            ],
            'sistema' => [
                'version' => config('caj.version'),
                'usuarios' => Usuario::count(),
                'usuarios_activos' => Usuario::where('activo', true)->count(),
                'consultorios' => $consultorios->count(),
                'cupos_totales' => $cupos,
            ],
        ]);
    }
}
