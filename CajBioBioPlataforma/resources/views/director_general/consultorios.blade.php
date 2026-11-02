@extends('layouts.portal', ['migas' => [['Directorio nacional de sedes']]])
@section('titulo', 'Directorio Nacional de Consultorios — Dirección General')

@section('contenido')
<div class="sec-cabecera">
    <div>
        <h1 class="titulo-pagina">Directorio Nacional de Consultorios y Capacidad</h1>
        <p class="texto-suave">
            Dirección General &bull; Dotación global, tutela jurídica y capacidad de cupos formativos en las 6 regiones de la CAJ Biobío.
        </p>
    </div>
</div>

@php
    $totalConsultorios = $consultorios->count();
    $totalCapacidad = $consultorios->sum('capacidad');
    $totalOcupados = $consultorios->sum('ocupados');
    $totalLibres = $consultorios->sum('libres');
@endphp

<div class="rejilla-indicadores">
    <x-indicador :valor="$totalConsultorios" etiqueta="Unidades Operativas" detalle="Consultorios jurídicos y oficinas activas" />
    <x-indicador :valor="$totalCapacidad" etiqueta="Capacidad Global de Cupos" detalle="Dotación máxima en la Corporación" />
    <x-indicador :valor="$totalOcupados" etiqueta="Cupos Ocupados" detalle="Practicantes activos a nivel país" />
    <x-indicador :valor="$totalLibres" etiqueta="Cupos Libres" detalle="Disponibilidad nacional inmediata" :critico="$totalLibres === 0" />
</div>

@if ($consultorios->isEmpty())
    <x-estado-vacio titulo="No hay consultorios registrados" texto="No se encontraron consultorios activos en la base de datos nacional." />
@else
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Región</th>
                    <th>Consultorio / Unidad</th>
                    <th>Comuna</th>
                    <th>Abogado(s) Asignado(s)</th>
                    <th style="text-align: center;">Capacidad</th>
                    <th style="text-align: center;">Ocupados</th>
                    <th style="text-align: center;">Disponibles</th>
                    <th style="text-align: right;">Disponibilidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($consultorios as $c)
                    @php
                        $badgeStyle = match ($c->disponibilidad) {
                            'alta' => 'background:#dcfce7; color:#15803d;',
                            'media' => 'background:#e0f2fe; color:#0369a1;',
                            'baja' => 'background:#fef3c7; color:#b45309;',
                            default => 'background:#fee2e2; color:#b91c1c;',
                        };
                        $dispTexto = match ($c->disponibilidad) {
                            'alta' => 'Alta',
                            'media' => 'Media',
                            'baja' => 'Baja',
                            default => 'Sin cupos',
                        };
                    @endphp
                    <tr>
                        <td>
                            <strong style="color:#1e3a8a;">{{ $c->region?->codigo }}</strong>
                            <div style="font-size:0.75rem; color:#64748b;">{{ $c->region?->nombre }}</div>
                        </td>
                        <td>
                            <div style="font-weight:600; color:#1e293b;">{{ $c->nombre }}</div>
                            <div style="font-size:0.75rem; color:#64748b;">{{ $c->direccion ?? 'Sin dirección' }}</div>
                        </td>
                        <td>{{ $c->comuna?->nombre ?? '—' }}</td>
                        <td>
                            @forelse ($c->abogados as $abg)
                                <div style="font-size:0.85rem;">{{ $abg->usuario?->nombre_completo ?? '—' }}</div>
                            @empty
                                <span style="font-size:0.8rem; color:#94a3b8;">Sin abogado asignado</span>
                            @endforelse
                        </td>
                        <td style="text-align: center; font-weight:600;">{{ $c->capacidad }}</td>
                        <td style="text-align: center; color:#b45309; font-weight:600;">{{ $c->ocupados }}</td>
                        <td style="text-align: center; color:#15803d; font-weight:600;">{{ $c->libres }}</td>
                        <td style="text-align: right;">
                            <span class="badge" style="{{ $badgeStyle }} font-weight:600; padding:0.25rem 0.5rem; border-radius:4px; font-size:0.8rem;">
                                {{ $dispTexto }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
