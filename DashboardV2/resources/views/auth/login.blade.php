@extends('layouts.publico')
@section('titulo', 'Ingresar')
@section('clase-body', 'pagina-login')

@php
    $funciones = [
        ['Postular a la práctica profesional', 'M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8zM14 3v5h5M9 13h6M9 17h4'],
        ['Seguimiento de tu postulación', 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM12 7v5l3 2'],
        ['Asignación a consultorio y abogado tutor', 'M3 21h18M5 21V9l7-5 7 5v12M9 21v-6h6v6'],
        ['Evaluaciones de tu desempeño', 'M9 11l2 2 4-4M5 4h14a1 1 0 0 1 1 1v15a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z'],
        ['Certificado de práctica', 'M12 14a5 5 0 1 0 0-10 5 5 0 0 0 0 10zM8.5 13.5L7 21l5-3 5 3-1.5-7.5'],
    ];
@endphp

@section('contenido')
<div class="login-hero">
    <div class="login-hero-interior">

        <section class="login-texto" aria-labelledby="login-titulo">
            <h1 id="login-titulo">Bienvenido al sistema de gestión de práctica profesional</h1>
            <p>Plataforma de la Corporación de Asistencia Judicial del Biobío para quienes postulan o realizan su práctica para optar al título de abogado, y para los equipos que los acompañan. Ingresa con tu RUT y contraseña o con ClaveÚnica.</p>
            <ul class="login-funciones" role="list">
                @foreach ($funciones as [$texto, $trazo])
                    <li>
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $trazo }}"/></svg>
                        <span>{{ $texto }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="login-tarjeta" aria-labelledby="login-tarjeta-titulo">
            <h2 id="login-tarjeta-titulo">Ingresar al sistema</h2>
            <p class="login-subtitulo">Usa el RUT con que fuiste registrado.</p>

            <form method="POST" action="{{ route('login') }}" class="login-formulario" novalidate>
                @csrf
                <x-campo name="rut" label="RUT" placeholder="12.345.678-9" inputmode="text" maxlength="12" data-rut :obligatorio="true" autocomplete="username" autofocus />
                <x-campo name="password" label="Contraseña" type="password" :obligatorio="true" autocomplete="current-password" />
                <x-boton type="submit" :bloque="true">Ingresar</x-boton>
            </form>

            <div class="separador">o ingresa con</div>

            <div class="cu-centro">
                @if (\App\Services\ClaveUnica::habilitada())
                    <a href="{{ route('claveunica.redirigir') }}" class="cu-oficial" aria-label="Ingresar con ClaveÚnica">
                        <img src="{{ asset('img/claveunica.svg') }}" alt="" width="24" height="24">
                        <span>ClaveÚnica</span>
                    </a>
                @else
                    <button type="button" disabled aria-disabled="true" aria-describedby="cu-proximamente" class="cu-oficial cu-oficial-inactivo">
                        <img src="{{ asset('img/claveunica.svg') }}" alt="" width="24" height="24">
                        <span>ClaveÚnica</span>
                    </button>
                    <p id="cu-proximamente" class="cu-nota">Disponible próximamente</p>
                @endif
            </div>
            @if ($errors->first('claveunica'))
                <div class="alerta alerta-error login-alerta" role="alert">{{ $errors->first('claveunica') }}</div>
            @endif
        </section>

    </div>
</div>
@endsection

@section('pie')
<footer class="login-pie">© {{ date('Y') }} Corporación de Asistencia Judicial del Biobío</footer>
@endsection
