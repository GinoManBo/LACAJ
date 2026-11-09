<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $q = Notificacion::where('usuario_destino_id', $request->user()->id)->orderByDesc('created_at')->orderByDesc('id');
        if ($request->query('solo') === 'no_leidas') {
            $q->whereNull('leida_at');
        }

        return view('notificaciones.index', [
            'notificaciones' => $q->paginate(20)->withQueryString(),
            'noLeidas' => Notificacion::where('usuario_destino_id', $request->user()->id)->whereNull('leida_at')->count(),
            'solo' => $request->query('solo'),
        ]);
    }

    public function leer(Request $request, int $id)
    {
        $n = Notificacion::where('usuario_destino_id', $request->user()->id)->findOrFail($id);
        $n->update(['leida_at' => $n->leida_at ?? now()]);

        return back();
    }

    public function leerTodas(Request $request)
    {
        Notificacion::where('usuario_destino_id', $request->user()->id)->whereNull('leida_at')->update(['leida_at' => now()]);

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('ok', 'Todas las notificaciones fueron marcadas como leídas.');
    }
}
