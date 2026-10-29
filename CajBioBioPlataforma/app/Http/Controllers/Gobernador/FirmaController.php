<?php

namespace App\Http\Controllers\Gobernador;

use App\Http\Controllers\Controller;
use App\Services\GobernadorFirma;
use Illuminate\Http\Request;

class FirmaController extends Controller
{
    public function ver(Request $request)
    {
        $g = $request->user();

        return view('gobernador.firma', ['tieneFirma' => (bool) GobernadorFirma::archivo($g), 'actualizadaAt' => $g->firma_actualizada_at]);
    }

    public function subir(Request $request)
    {
        $request->validate(['firma' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg', 'max:2048']], [
            'firma.required' => 'Seleccione la imagen de su firma.', 'firma.image' => 'El archivo debe ser una imagen PNG o JPG.',
            'firma.mimes' => 'Formato no permitido: use PNG o JPG.', 'firma.max' => 'La imagen no puede superar los 2 MB.',
        ]);
        GobernadorFirma::guardar($request->user(), $request->file('firma'));

        return redirect()->route('gobernador.firma')->with('ok', 'Firma actualizada.');
    }

    public function imagen(Request $request)
    {
        $a = GobernadorFirma::archivo($request->user());
        abort_unless($a, 404);

        return response()->file($a['ruta'], ['Content-Type' => $a['mime'], 'Cache-Control' => 'private, no-store']);
    }
}
