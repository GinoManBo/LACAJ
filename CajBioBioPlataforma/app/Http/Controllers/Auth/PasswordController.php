<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function mostrar()
    {
        return view('auth.cambiar-password');
    }

    public function actualizar(Request $request)
    {
        $datos = $request->validate([
            'actual' => ['required', 'string'],
            'nueva' => ['required', 'confirmed', Password::min(8)],
        ], [
            'actual.required' => 'Ingrese su contraseña actual.',
            'nueva.required' => 'Ingrese la nueva contraseña.',
            'nueva.confirmed' => 'La confirmación no coincide.',
            'nueva.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
        ]);

        $usuario = $request->user();
        if (! Hash::check($datos['actual'], (string) $usuario->password)) {
            return back()->withErrors(['actual' => 'La contraseña actual no es correcta.']);
        }

        $usuario->forceFill(['password' => $datos['nueva'], 'debe_cambiar_password' => false])->save();
        Auditoria::registrar('auth.cambiar_password', 'usuario', $usuario->id);
        $request->session()->regenerate();

        return redirect($usuario->portalInicio())->with('ok', 'Contraseña actualizada.');
    }
}
