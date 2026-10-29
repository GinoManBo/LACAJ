@extends('layouts.portal', ['migas' => [['Administración', route('admin.inicio')], ['Capacidad']]])
@section('titulo', 'Capacidad de consultorios')
@section('contenido')
@include('admin._nav')
@php
    $totalCap = $sedes->sum('capacidad'); $totalOcu = $sedes->sum('ocupados_n');
@endphp
<div>
    <h1 class="titulo-pagina">Capacidad de consultorios</h1>
    <p class="texto-suave" style="margin:.25rem 0 0">{{ $totalOcu }} de {{ $totalCap }} cupos ocupados en {{ $sedes->count() }} sedes.</p>
</div>

@if ($sedes->isEmpty())
    <x-estado-vacio titulo="No hay consultorios activos" texto="Cuando existan sedes activas aparecerán aquí con su ocupación." />
@else
    <div class="rejilla-sedes">
        @foreach ($sedes as $s)
            @php
                $disp = $s->libres_n === 0 ? 'sin' : ($s->libres_n >= $s->capacidad / 2 ? 'muchos' : 'pocos');
                $pct = $s->capacidad > 0 ? min(100, round($s->ocupados_n * 100 / $s->capacidad)) : 0;
            @endphp
            <a href="{{ route('admin.capacidad.ver', $s) }}" class="sede-tarjeta">
                <div class="sede-cabecera">
                    <div>
                        <h2>{{ $s->nombre }}</h2>
                        <p class="dato-menor texto-suave">{{ $s->region->nombre }}@if ($s->direccion) · {{ $s->direccion }}@endif</p>
                    </div>
                    <x-badge :estado="$disp" />
                </div>
                <div class="barra" role="meter" aria-valuemin="0" aria-valuemax="{{ $s->capacidad }}" aria-valuenow="{{ $s->ocupados_n }}" aria-label="Ocupación de {{ $s->nombre }}">
                    <span class="barra-relleno barra-{{ $disp }}" style="--ancho: {{ $pct }}%"></span>
                </div>
                <div class="sede-numeros"><span><strong>{{ $s->ocupados_n }}</strong> ocupados</span><span><strong>{{ $s->libres_n }}</strong> libres de {{ $s->capacidad }}</span></div>
            </a>
        @endforeach
    </div>
@endif
@endsection
