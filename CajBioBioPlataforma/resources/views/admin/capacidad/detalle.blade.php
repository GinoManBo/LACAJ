@extends('layouts.portal', ['migas' => [['Administración', route('admin.inicio')], ['Capacidad', route('admin.capacidad.index')], [$sede->nombre]]])
@section('titulo', 'Capacidad · '.$sede->nombre)
@php
    $disp = $libres === 0 ? 'sin' : ($libres >= $sede->capacidad / 2 ? 'muchos' : 'pocos');
    $pct = $sede->capacidad > 0 ? min(100, round($ocupados * 100 / $sede->capacidad)) : 0;
    $errorCap = $errors->first('capacidad');
@endphp
@section('contenido')
@include('admin._nav')
<div class="cabecera-ficha">
    <div>
        <h1 class="titulo-pagina">{{ $sede->nombre }}</h1>
        <p class="texto-suave" style="margin:.25rem 0 0">{{ $sede->region->nombre }}@if ($sede->comuna) · {{ $sede->comuna->nombre }}@endif</p>
    </div>
    <div style="display:flex;gap:0.75rem;align-items:center">
        <x-badge :estado="$disp" />
        <x-boton tipo="primario" tam="sm" :href="route('admin.consultorios.editar', $sede)">Editar Datos de Sede</x-boton>
    </div>
</div>

<div class="rejilla-ficha">
    <x-tarjeta titulo="Cupos de la sede">
        <div class="ajuste">
            <form method="POST" action="{{ route('admin.capacidad.ajustar', $sede) }}">
                @csrf <input type="hidden" name="accion" value="menos">
                <button type="submit" class="ajuste-boton" aria-label="Retirar un cupo" @disabled($libres === 0) data-permitir-repetir>&minus;</button>
            </form>
            <div class="ajuste-valor" aria-live="polite"><span>{{ $sede->capacidad }}</span><small>cupos en total</small></div>
            <form method="POST" action="{{ route('admin.capacidad.ajustar', $sede) }}">
                @csrf <input type="hidden" name="accion" value="mas">
                <button type="submit" class="ajuste-boton" aria-label="Agregar un cupo" data-permitir-repetir>+</button>
            </form>
        </div>
        @if ($errorCap)<p class="campo-error" style="display:block;text-align:center" role="alert">{{ $errorCap }}</p>@endif
        <div class="barra barra-grande" role="meter" aria-valuemin="0" aria-valuemax="{{ $sede->capacidad }}" aria-valuenow="{{ $ocupados }}" aria-label="Ocupación">
            <span class="barra-relleno barra-{{ $disp }}" style="--ancho: {{ $pct }}%"></span>
        </div>
        <div class="sede-numeros"><span><strong>{{ $ocupados }}</strong> ocupados</span><span><strong>{{ $libres }}</strong> libres</span></div>
        @if ($libres === 0)<p class="dato-menor texto-suave" style="margin:.75rem 0 0">No se pueden retirar más cupos: la capacidad ya iguala a los postulantes asignados. Traslada a alguien primero.</p>@endif
        <form method="POST" action="{{ route('admin.capacidad.ajustar', $sede) }}" class="fijar" novalidate>
            @csrf
            <label for="cap-directa" class="campo-etiqueta">Fijar capacidad exacta</label>
            <div class="fijar-fila">
                <input id="cap-directa" name="capacidad" type="number" min="{{ $ocupados }}" max="500" value="{{ $sede->capacidad }}" class="campo-control" style="max-width:8rem">
                <x-boton type="submit" tipo="secundario">Aplicar</x-boton>
            </div>
        </form>
    </x-tarjeta>

    <x-tarjeta titulo="Postulantes asignados ({{ $asignados->count() }})" sinPadding>
        @if ($asignados->isEmpty())
            <div style="padding:1.25rem"><x-estado-vacio titulo="Sin postulantes asignados" texto="Todos los cupos están libres." /></div>
        @else
            <ul class="lista-filas">
                @foreach ($asignados as $p)
                    @php $po = $p->practicante->postulante; @endphp
                    <li>
                        <a href="{{ route('admin.practicantes.ver', $p) }}">
                            <span><strong>{{ $po->nombre }} {{ $po->apellido }}</strong>
                            <span class="dato-menor texto-suave" style="display:block">{{ $p->abogado?->usuario ? 'Tutor: '.$p->abogado->usuario->nombre.' '.$p->abogado->usuario->apellido : 'Sin tutor' }} · termina {{ $p->fecha_termino->format('d/m/Y') }}</span></span>
                            <x-badge :estado="$p->estado" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-tarjeta>
</div>
@endsection
