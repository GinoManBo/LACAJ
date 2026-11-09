<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\Auditoria;
use App\Support\Rut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

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

        $rutNormalizado = Rut::normalizar($datos['rut']);
        $throttleKey = 'login:'.$rutNormalizado.'|'.$request->ip();
        $invalidas = ['rut' => 'RUT o contraseña incorrectos.'];

        // 1. Verificación de Rate Limiting por IP + RUT para evitar ataques de fuerza bruta y DoS
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_INTENTOS)) {
            $segundos = RateLimiter::availableIn($throttleKey);
            Auditoria::registrar('auth.login_bloqueado', 'usuario', null, [
                'rut' => $rutNormalizado,
                'motivo' => 'exceso_intentos_ip',
                'segundos_restantes' => $segundos,
            ]);

            return back()->withErrors([
                'rut' => "Demasiados intentos fallidos desde esta conexión. Por favor intente nuevamente en {$segundos} segundos.",
            ])->onlyInput('rut');
        }

        $usuario = Usuario::where('rut', $rutNormalizado)->first();

        // 2. Si el usuario no existe: respuesta genérica y registro de auditoría de fallo
        if (! $usuario) {
            RateLimiter::hit($throttleKey, self::BLOQUEO_MINUTOS * 60);
            Auditoria::registrar('auth.login_fallido', 'usuario', null, [
                'rut' => $rutNormalizado,
                'motivo' => 'usuario_no_encontrado',
            ]);

            return back()->withErrors($invalidas)->onlyInput('rut');
        }

        // 3. Verificación de credenciales (contraseña)
        $passwordValida = $usuario->password && Hash::check($datos['password'], $usuario->password);

        if (! $passwordValida) {
            RateLimiter::hit($throttleKey, self::BLOQUEO_MINUTOS * 60);
            $intentosUsuario = $usuario->intentos_fallidos + 1;

            $bloqueadoHasta = null;
            if ($intentosUsuario >= self::MAX_INTENTOS) {
                $bloqueadoHasta = now()->addMinutes(self::BLOQUEO_MINUTOS);
            }

            $usuario->forceFill([
                'intentos_fallidos' => $intentosUsuario,
                'bloqueado_hasta' => $bloqueadoHasta,
            ])->save();

            Auditoria::registrar('auth.login_fallido', 'usuario', $usuario->id, [
                'rut' => $rutNormalizado,
                'motivo' => 'password_incorrecta',
                'intentos' => $intentosUsuario,
            ], $usuario->id);

            if ($bloqueadoHasta) {
                Auditoria::registrar('auth.bloqueo', 'usuario', $usuario->id, [
                    'rut' => $rutNormalizado,
                    'bloqueado_hasta' => $bloqueadoHasta->toIso8601String(),
                    'intentos' => $intentosUsuario,
                ], $usuario->id);
            }

            // Mensaje idéntico y genérico para prevenir enumeración de usuarios
            return back()->withErrors($invalidas)->onlyInput('rut');
        }

        // 4. Si la contraseña es válida pero la cuenta está desactivada administrativamente:
        if (! $usuario->activo) {
            Auditoria::registrar('auth.login_fallido', 'usuario', $usuario->id, [
                'rut' => $rutNormalizado,
                'motivo' => 'cuenta_desactivada',
            ], $usuario->id);

            return back()->withErrors(['rut' => 'Su cuenta se encuentra desactivada. Contacte al administrador.'])->onlyInput('rut');
        }

        // 5. Autenticación exitosa:
        // Se limpian los intentos fallidos, el bloqueo temporal y el limitador de tasa
        RateLimiter::clear($throttleKey);
        $usuario->forceFill([
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'ultimo_acceso' => now(),
        ])->save();

        Auth::login($usuario);
        $request->session()->regenerate();
        Auditoria::registrar('auth.login', 'usuario', $usuario->id, [
            'rut' => $rutNormalizado,
            'rol' => $usuario->rol,
        ], $usuario->id);

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
