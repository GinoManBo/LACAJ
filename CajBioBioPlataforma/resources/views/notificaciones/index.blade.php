@extends('layouts.portal', ['migas' => [['Notificaciones']]])
@section('titulo', 'Notificaciones')

@section('contenido')
@php
    $soloNoLeidas = $solo === 'no_leidas';
    $fmt = fn ($f) => $f?->locale('es')->isoFormat('D MMM YYYY, HH:mm');
@endphp

<header class="tp-cabecera tp-entra">
    <div>
        <h1 class="titulo-pagina">Notificaciones @if ($noLeidas > 0)<span class="nt-contador" aria-label="{{ $noLeidas }} sin leer">{{ $noLeidas }}</span>@endif</h1>
        <p class="texto-suave">{{ $noLeidas > 0 ? 'Tiene '.$noLeidas.($noLeidas === 1 ? ' notificación sin leer.' : ' notificaciones sin leer.') : 'Está al día: no tiene notificaciones sin leer.' }}</p>
    </div>
</header>

<div class="nt-barra tp-entra" style="--i:1">
    <nav class="nt-filtros" aria-label="Filtrar notificaciones">
        <a href="{{ route('notificaciones.index') }}" @if (! $soloNoLeidas) aria-current="true" @endif>Todas</a>
        <a href="{{ route('notificaciones.index', ['solo' => 'no_leidas']) }}" @if ($soloNoLeidas) aria-current="true" @endif>Sin leer</a>
    </nav>
    @if ($noLeidas > 0)
        <form method="POST" action="{{ route('notificaciones.leer-todas') }}">
            @csrf
            <x-boton type="submit" tipo="secundario">Marcar todas como leídas</x-boton>
        </form>
    @endif
</div>

@if ($notificaciones->isEmpty())
    <x-estado-vacio :titulo="$soloNoLeidas ? 'No tiene notificaciones sin leer' : 'No tiene notificaciones'"
        :texto="$soloNoLeidas ? 'Ya revisó todo lo pendiente.' : 'Aquí verá avisos sobre sus prácticas, informes y documentos.'">
        @if ($soloNoLeidas)<x-boton tipo="secundario" :href="route('notificaciones.index')">Ver todas</x-boton>@endif
    </x-estado-vacio>
@else
    <ul class="nt-lista">
        @foreach ($notificaciones as $n)
            @php $nueva = is_null($n->leida_at); @endphp
            <li @class(['nt-item', 'tp-entra', 'nt-nueva' => $nueva, 'nt-'.$n->tipo]) style="--i:{{ min($loop->index, 6) + 2 }}">
                <span class="nt-punto" aria-hidden="true"></span>
                <div class="nt-cuerpo">
                    <div class="nt-cabecera">
                        <x-badge :estado="$n->tipo" />
                        <h2 class="nt-titulo">{{ $n->titulo }}@if ($nueva)<span class="sr-only"> (sin leer)</span>@endif</h2>
                    </div>
                    <p class="nt-mensaje">{{ $n->mensaje }}</p>
                    <div class="nt-pie">
                        <time datetime="{{ $n->created_at->toIso8601String() }}">{{ $fmt($n->created_at) }}</time>
                        @if ($nueva)
                            <form method="POST" action="{{ route('notificaciones.leer', $n->id) }}">
                                @csrf
                                <x-boton type="submit" tipo="fantasma" tam="sm">Marcar como leída</x-boton>
                            </form>
                        @else
                            <span>Leída</span>
                        @endif
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
    <div class="nt-paginas">{{ $notificaciones->links() }}</div>
@endif
@endsection
