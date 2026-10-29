@extends('layouts.publico')
@section('titulo', 'Cambiar contraseña')

@section('contenido')
<div class="auth-formulario" style="min-height:calc(100vh - 9rem)">
    <div class="auth-caja">
        <div>
            <h1 class="titulo-pagina">Cambia tu contraseña</h1>
            <p class="texto-suave" style="margin:.4rem 0 0">Elige una contraseña nueva de al menos 8 caracteres.</p>
        </div>
        <form method="POST" action="{{ route('password.actualizar') }}" class="flex flex-col gap-4" novalidate>
            @csrf
            <x-campo name="actual" label="Contraseña actual" type="password" :obligatorio="true" autocomplete="current-password" />
            <x-campo name="nueva" label="Contraseña nueva" type="password" :obligatorio="true" minlength="8" autocomplete="new-password" />
            <x-campo name="nueva_confirmation" label="Repite la contraseña nueva" type="password" :obligatorio="true" autocomplete="new-password" />
            <x-boton type="submit" :bloque="true">Guardar contraseña</x-boton>
        </form>
    </div>
</div>
@endsection
