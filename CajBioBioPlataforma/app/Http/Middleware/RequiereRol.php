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
        if (! in_array($usuario->rol, $roles, true)) {
            abort(403, 'No tiene permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
