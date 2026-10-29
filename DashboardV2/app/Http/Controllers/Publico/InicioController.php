<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Postulacion\PostulacionController;
use App\Models\Consultorio;
use App\Models\Region;

class InicioController extends Controller
{
    public function __invoke()
    {
        $consultorios = Consultorio::with('comuna')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->groupBy('region_id');

        $regiones = Region::operativas()->orderBy('orden')->get()
            ->filter(fn ($r) => $consultorios->has($r->id))
            ->values();

        return view('publico.inicio', [
            'regiones' => $regiones,
            'consultorios' => $consultorios,
            'totalConsultorios' => $consultorios->flatten()->count(),
            'pasos' => PostulacionController::PASOS,
        ]);
    }
}
