<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Consultorio;
use App\Services\Practicas;
use App\Support\Scope;
use Illuminate\Http\Request;

class ConsultoriosController extends Controller
{
    public function index(Request $request)
    {
        $consultorios = Consultorio::with('comuna')->where('region_id', Scope::regionId($request->user()))
            ->where('activo', true)->orderBy('nombre')->get()->each(fn ($c) => $this->resumen($c));

        return view('secretaria.consultorios.index', ['consultorios' => $consultorios]);
    }

    public function ver(Request $request, int $id)
    {
        $c = Consultorio::with('comuna')->where('region_id', Scope::regionId($request->user()))->findOrFail($id);
        $this->resumen($c);

        $asignados = $c->practicas()->with('practicante.postulante', 'abogado.usuario')
            ->whereIn('estado', ['por_iniciar', 'activa'])->orderBy('fecha_termino')->get()
            ->each(fn ($p) => $p->estado_visible = Practicas::estadoVisible($p));

        return view('secretaria.consultorios.ver', ['consultorio' => $c, 'asignados' => $asignados]);
    }

    private function resumen(Consultorio $c): void
    {
        $c->ocupados = $c->ocupados();
        $c->libres = max(0, $c->capacidad - $c->ocupados);
        $c->porcentaje_ocupacion = $c->capacidad > 0 ? (int) round($c->ocupados / $c->capacidad * 100) : 0;
        $c->disponibilidad = Practicas::nivelDisponibilidad($c->capacidad, $c->libres);
    }
}
