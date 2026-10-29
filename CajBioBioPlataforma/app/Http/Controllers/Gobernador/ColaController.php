<?php

namespace App\Http\Controllers\Gobernador;

use App\Http\Controllers\Controller;
use App\Services\GobernadorAutorizaciones as Aut;
use Illuminate\Http\Request;

class ColaController extends Controller
{
    public function index(Request $request)
    {
        $g = $request->user();

        return view('gobernador.inicio', [
            'pendientes' => Aut::listar(),
            'tieneFirma' => (bool) $g->firma_nombre_almacenado,
            'firmaActualizadaAt' => $g->firma_actualizada_at,
        ]);
    }

    public function autorizar(Request $request, int $practica)
    {
        Aut::autorizar($practica, $request->user()->id);

        return back()->with('ok', 'Documentos autorizados.');
    }

    public function autorizarSeleccion(Request $request)
    {
        $d = $request->validate(['practicas' => ['required', 'array', 'min:1'], 'practicas.*' => ['integer']],
            ['practicas.required' => 'Seleccione al menos una práctica.', 'practicas.min' => 'Seleccione al menos una práctica.']);

        return $this->resultado(Aut::autorizarVarias($d['practicas'], $request->user()->id));
    }

    public function lote(Request $request)
    {
        $d = $request->validate(['cantidad' => ['required', 'integer', 'min:1', 'max:1000']],
            ['cantidad.required' => 'Indique cuántas prácticas autorizar.', 'cantidad.integer' => 'Ingrese un número entero.', 'cantidad.min' => 'Ingrese al menos 1.', 'cantidad.max' => 'El máximo por lote es 1000.']);

        return $this->resultado(Aut::autorizarLote((int) $d['cantidad'], $request->user()->id));
    }

    public function todo(Request $request)
    {
        return $this->resultado(Aut::autorizarTodo($request->user()->id));
    }

    private function resultado(array $r)
    {
        return back()->with('ok', "Se autorizaron {$r['autorizadas']} de {$r['solicitadas']} práctica(s).");
    }
}
