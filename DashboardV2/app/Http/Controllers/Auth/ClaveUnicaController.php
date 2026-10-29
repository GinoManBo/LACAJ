<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PostulacionBorrador;
use App\Models\Usuario;
use App\Services\Auditoria;
use App\Services\ClaveUnica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class ClaveUnicaController extends Controller
{
    public function redirigir(Request $request)
    {
        abort_unless(ClaveUnica::habilitada(), 404);

        [$url, $state] = ClaveUnica::urlAutorizacion();
        $request->session()->put('claveunica.state', $state);

        return redirect()->away($url);
    }

    public function callback(Request $request)
    {
        abort_unless(ClaveUnica::habilitada(), 404);

        $state = $request->session()->pull('claveunica.state');
        if ($request->filled('error') || ! $request->filled('code') || ! $state || ! hash_equals($state, (string) $request->query('state'))) {
            return $this->falla('No se pudo completar el ingreso con ClaveÚnica. Intente nuevamente.');
        }

        try {
            $perfil = ClaveUnica::perfil($request->query('code'), $state);
        } catch (RuntimeException $e) {
            report($e);

            return $this->falla('ClaveÚnica no respondió correctamente. Intente nuevamente.');
        }

        $usuario = Usuario::where('rut', $perfil['rut'])->first();
        if ($usuario && ! $usuario->activo) {
            return $this->falla('Su cuenta está desactivada. Contacte al administrador.');
        }
        $usuario ??= Usuario::create([
            'nombre' => $perfil['nombre'] ?: 'Postulante', 'apellido' => $perfil['apellido'], 'rut' => $perfil['rut'],
            'rol' => 'postulante', 'activo' => true, 'debe_cambiar_password' => false,
        ]);

        $usuario->forceFill(['ultimo_acceso' => now(), 'intentos_fallidos' => 0, 'bloqueado_hasta' => null])->save();
        $borradorSesion = $request->session()->get('postulacion');
        Auth::login($usuario);
        $request->session()->regenerate();
        if ($usuario->rol === 'postulante' && $borradorSesion) {
            PostulacionBorrador::firstOrCreate(['usuario_id' => $usuario->id], ['datos' => $borradorSesion]);
            $request->session()->forget('postulacion');
        }
        Auditoria::registrar('auth.login_claveunica', 'usuario', $usuario->id);

        return redirect()->intended($usuario->portalInicio());
    }

    private function falla(string $mensaje)
    {
        return redirect()->route('login')->withErrors(['claveunica' => $mensaje]);
    }
}
