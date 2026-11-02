@extends('layouts.portal', ['migas' => [['Consultorios']]])
@section('titulo', 'Consultorios')
@section('contenido')
<div class="sec-cabecera">
    <div>
        <h1 class="titulo-pagina">Consultorios de la región</h1>
        <p class="texto-suave">Los cupos se modifican desde el portal del Administrador.</p>
    </div>
    <div class="sec-leyenda" aria-label="Leyenda de cupos"><x-badge estado="muchos" /><x-badge estado="pocos" /><x-badge estado="sin" /></div>
</div>

@if ($consultorios->isEmpty())
    <x-estado-vacio titulo="No hay consultorios en su región" texto="Pida al Administrador que registre los consultorios." />
@else
    <div class="sec-cons-rejilla">
        @foreach ($consultorios as $c)
            <a class="sec-cons-card" href="{{ route('secretaria.consultorios.ver', $c->id) }}">
                <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start">
                    <h2>{{ $c->nombre }}</h2>
                    <x-badge :estado="$c->disponibilidad" />
                </div>
                @if ($c->tipo_unidad)
                    <span class="badge badge-neutro" style="font-size:0.75rem;margin-bottom:0.25rem;display:inline-block">
                        {{ $c->tipo_unidad_texto }}
                    </span>
                @endif
                <p>{{ $c->direccion }}@if ($c->comuna), {{ $c->comuna->nombre }}@endif</p>
                @if ($c->telefono || $c->email)
                    <p style="font-size:0.8rem;color:#6b7280;margin:0.25rem 0">
                        {{ $c->telefono ? '📞 '.$c->telefono : '' }}
                        {{ $c->telefono && $c->email ? ' · ' : '' }}
                        {{ $c->email ? '✉️ '.$c->email : '' }}
                    </p>
                @endif
                <div class="sec-cifra"><span><strong>{{ $c->ocupados }}</strong> de {{ $c->capacidad }} ocupados</span><span>{{ $c->libres }} {{ $c->libres === 1 ? 'libre' : 'libres' }}</span></div>
                <div class="sec-barra es-{{ $c->disponibilidad }}" role="img" aria-label="Ocupación {{ $c->porcentaje_ocupacion }}%"><i style="--p:{{ $c->porcentaje_ocupacion }}%"></i></div>
            </a>
        @endforeach
    </div>
@endif
@endsection
