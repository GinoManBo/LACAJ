<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\Auditoria;
use App\Support\Rut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    private const MAX_INTENTOS = 5;
    private const BLOQUEO_MINUTOS = 15;

    public function mostrar()
    {
        if (Auth::check()) {
            return redirect(Auth::user()->portalInicio());
        }

        return view('auth.login');
    }

    public function entrar(Request $request)
    {
        $datos = $request->validate([
            'rut' => ['required', 'string', 'max:20', fn ($a, $v, $fail) => Rut::valido($v) ?: $fail('El RUT no es válido. Revise el dígito verificador.')],
            'password' => ['required', 'string'],
        ], [
            'rut.required' => 'Ingrese su RUT.',
            'password.required' => 'Ingrese su contraseña.',
        ]);

        $usuario = Usuario::where('rut', Rut::normalizar($datos['rut']))->first();
        $invalidas = ['rut' => 'RUT o contraseña incorrectos.'];

        if (! $usuario) {
            return back()->withErrors($invalidas)->onlyInput('rut');
        }
        if ($usuario->bloqueado_hasta && $usuario->bloqueado_hasta->isFuture()) {
            return back()->withErrors(['rut' => 'Cuenta bloqueada temporalmente por demasiados intentos fallidos.'])->onlyInput('rut');
        }
        if (! $usuario->activo) {
            return back()->withErrors(['rut' => 'Cuenta desactivada. Contacte al administrador.'])->onlyInput('rut');
        }
        if (! $usuario->password || ! Hash::check($datos['password'], $usuario->password)) {
            $intentos = $usuario->intentos_fallidos + 1;
            $usuario->forceFill([
                'intentos_fallidos' => $intentos,
                'bloqueado_hasta' => $intentos >= self::MAX_INTENTOS ? now()->addMinutes(self::BLOQUEO_MINUTOS) : null,
            ])->save();

            return back()->withErrors($invalidas)->onlyInput('rut');
        }

        $usuario->forceFill(['intentos_fallidos' => 0, 'bloqueado_hasta' => null, 'ultimo_acceso' => now()])->save();
        Auth::login($usuario);
        $request->session()->regenerate();
        Auditoria::registrar('auth.login', 'usuario', $usuario->id);

        return redirect()->intended($usuario->portalInicio());
    }

    public function salir(Request $request)
    {
        if ($id = Auth::id()) {
            Auditoria::registrar('auth.logout', 'usuario', $id);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('inicio');
    }
}
