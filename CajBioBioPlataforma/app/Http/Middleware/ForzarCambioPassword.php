<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForzarCambioPassword
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if ($u && $u->debe_cambiar_password && ! $request->routeIs('password.*', 'logout')) {
            return redirect()->route('password.cambiar');
        }

        return $next($request);
    }
}
