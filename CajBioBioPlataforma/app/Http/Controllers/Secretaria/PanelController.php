<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Postulacion;
use App\Models\Practica;
use App\Services\Practicas;
use App\Support\Scope;
use Illuminate\Http\Request;

class PanelController extends Controller
{
    public function __invoke(Request $request)
    {
        $region = Scope::regionId($request->user());

        $porTerminar = Practica::with('practicante.postulante', 'consultorio', 'abogado.usuario')
            ->whereHas('consultorio', fn ($q) => $q->where('region_id', $region))
            ->where('estado', 'activa')
            ->whereBetween('fecha_termino', [today(), today()->addDays(Practicas::DIAS_POR_TERMINAR)])
            ->orderBy('fecha_termino')->get();

        return view('secretaria.panel', [
            'region' => $request->user()->region,
            'pendientes' => Postulacion::where('region_id', $region)->where('estado', 'pendiente')->count(),
            'activas' => Practica::whereHas('consultorio', fn ($q) => $q->where('region_id', $region))->where('estado', 'activa')->count(),
            'porTerminarCount' => $porTerminar->count(),
            'ultimasPostulaciones' => Postulacion::with('postulante')->where('region_id', $region)->where('estado', 'pendiente')
                ->orderBy('fecha_postulacion')->orderBy('id')->limit(5)->get(),
            'practicasPorTerminar' => $porTerminar->take(5),
        ]);
    }
}
