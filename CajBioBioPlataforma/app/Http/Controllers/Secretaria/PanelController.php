<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Postulacion;
use App\Models\Practica;
use App\Models\Region;
use App\Services\Practicas;
use App\Support\Scope;
use Illuminate\Http\Request;

class PanelController extends Controller
{
    public function __invoke(Request $request)
    {
        $u = $request->user();
        $region = Scope::regionId($u);
        $esBiobioCentral = Scope::esBiobioCentral($u);

        $porTerminar = Practica::with('practicante.postulante', 'consultorio', 'abogado.usuario')
            ->whereHas('consultorio', fn ($q) => $q->where('region_id', $region))
            ->where('estado', 'activa')
            ->whereBetween('fecha_termino', [today(), today()->addDays(Practicas::DIAS_POR_TERMINAR)])
            ->orderBy('fecha_termino')->get();

        $regionModel = $u->region ?? ($region ? Region::find($region) : null);

        $datos = [
            'region' => $regionModel,
            'pendientes' => Postulacion::where('region_id', $region)->where('estado', 'pendiente')->count(),
            'activas' => Practica::whereHas('consultorio', fn ($q) => $q->where('region_id', $region))->where('estado', 'activa')->count(),
            'porTerminarCount' => $porTerminar->count(),
            'ultimasPostulaciones' => Postulacion::with('postulante')->where('region_id', $region)->where('estado', 'pendiente')
                ->orderBy('fecha_postulacion')->orderBy('id')->limit(5)->get(),
            'practicasPorTerminar' => $porTerminar->take(5),
            'esBiobioCentral' => $esBiobioCentral,
        ];

        if ($esBiobioCentral) {
            $datos['centralTotales'] = [
                'recibidos' => Practica::where('estado', 'remitida_biobio')->count(),
                'niveladas' => Practica::where('estado', 'nivelada_biobio')->count(),
                'por_despachar' => Practica::where('estado', 'nivelada_biobio')->whereNotNull('firmado_director_general_at')->whereNull('despachada_at')->count(),
                'postulaciones_nacionales' => Postulacion::where('estado', 'pendiente')->count(),
            ];
        }

        return view('secretaria.panel', $datos);
    }
}
