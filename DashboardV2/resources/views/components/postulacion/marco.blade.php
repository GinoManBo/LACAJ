@props(['n', 'pasos', 'permitido', 'titulo', 'bajada' => null, 'ancho' => 'normal'])
@push('head')@vite('resources/js/postulacion.js')@endpush
<div class="post-marco">
    <div class="post-cabecera">
        <div class="contenedor post-contenedor">
            <x-pasos :pasos="$pasos" :actual="$n" :permitido="$permitido" />
        </div>
    </div>
    <div class="contenedor post-contenedor post-cuerpo">
        @if (session('aviso'))
            <div class="alerta alerta-aviso" role="status">{{ session('aviso') }}</div>
        @endif
        @if ($n === 5 && $errors->any())
            <div class="alerta alerta-error" role="alert"><div><strong>No pudimos enviar la postulación.</strong><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div></div>
        @endif
        @if (auth()->check() && auth()->user()->rol === 'postulante')
            <p class="post-aviso-avance" role="status">Tu avance se guarda en tu perfil. <a href="{{ route('postulante.inicio') }}">Ver mi postulación</a></p>
        @elseif (! auth()->check())
            <p class="post-aviso-avance">Si cierras esta pestaña perderás el avance. Ingresa con ClaveÚnica para guardarlo.@if (\App\Services\ClaveUnica::habilitada()) <a href="{{ route('claveunica.redirigir') }}">Ingresar con ClaveÚnica</a>@endif</p>
        @endif
        <header class="post-titulo">
            <p class="post-contador">Paso {{ $n }} de {{ count($pasos) }}</p>
            <h1>{{ $titulo }}</h1>
            @if ($bajada)<p class="texto-suave">{{ $bajada }}</p>@endif
        </header>
        {{ $slot }}
    </div>
</div>
