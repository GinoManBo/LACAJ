<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequiereRol
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $usuario = $request->user();
        if (! $usuario) {
            return redirect()->guest(route('login'));
        }
        if (! $usuario->activo) {
            auth()->logout();

            return redirect()->route('login')->withErrors(['email' => 'Cuenta desactivada.']);
        }
        // =========================================================================
        // UNIFICACIÓN DE ROLES (compatibilidad bidireccional):
        // Para asegurar que nada se rompa en rutas, vistas o controladores históricos,
        // 'postulante' y 'practicante' son reconocidos como roles equivalentes al
        // verificar la autorización de acceso a portales.
        // =========================================================================
        $esValido = in_array($usuario->rol, $roles, true);
        if (! $esValido) {
            if ($usuario->rol === 'postulante' && in_array('practicante', $roles, true)) {
                $esValido = true;
            } elseif ($usuario->rol === 'practicante' && in_array('postulante', $roles, true)) {
                $esValido = true;
            }
        }

        if (! $esValido) {
            abort(403, 'No tiene permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
