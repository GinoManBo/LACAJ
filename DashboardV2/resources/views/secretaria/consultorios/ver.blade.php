@extends('layouts.portal', ['migas' => [['Consultorios', route('secretaria.consultorios')], [$consultorio->nombre]]])
@section('titulo', $consultorio->nombre)
@section('contenido')
@php $c = $consultorio; @endphp
<div class="sec-cabecera">
    <div>
        <div class="sec-titulo-ficha"><h1 class="titulo-pagina">{{ $c->nombre }}</h1><x-badge :estado="$c->disponibilidad" /></div>
        <p class="sec-subtitulo">{{ $c->direccion }}@if ($c->comuna), {{ $c->comuna->nombre }}@endif</p>
    </div>
    <x-boton tipo="secundario" :href="route('secretaria.consultorios')">@include('secretaria._icono', ['n' => 'volver']) Volver a consultorios</x-boton>
</div>

<div class="rejilla-indicadores">
    <x-indicador :valor="$c->capacidad" etiqueta="Capacidad" />
    <x-indicador :valor="$c->ocupados" etiqueta="Ocupados" />
    <x-indicador :valor="$c->libres" etiqueta="Libres" :critico="$c->libres === 0" />
</div>
<div class="sec-barra sec-barra-grande es-{{ $c->disponibilidad }}" role="img" aria-label="Ocupación {{ $c->porcentaje_ocupacion }}%"><i style="--p:{{ $c->porcentaje_ocupacion }}%"></i></div>

<h2 style="margin:.5rem 0 0;font-size:1.25rem">Practicantes asignados</h2>
@if ($asignados->isEmpty())
    <x-estado-vacio titulo="Sin practicantes asignados" texto="Cuando acepte una postulación a este consultorio, aparecerá aquí." />
@else
    <x-tabla :columnas="['Practicante', 'Universidad', 'Abogado tutor', 'Estado', 'Término']" descripcion="Practicantes asignados al consultorio">
        @foreach ($asignados as $p)
            @php $po = $p->practicante->postulante; $a = $p->abogado?->usuario; $est = $p->estado_visible; @endphp
            <tr data-href="{{ route('secretaria.practicas.ver', $p->id) }}">
                <td><a href="{{ route('secretaria.practicas.ver', $p->id) }}"><strong>{{ $po->nombre }} {{ $po->apellido }}</strong></a></td>
                <td>{{ $po->universidad }}</td>
                <td>{{ $a ? $a->nombre.' '.$a->apellido : 'Sin asignar' }}</td>
                <td><x-badge :estado="$est" :texto="$est === 'por_terminar' ? 'Por terminar' : null" /></td>
                <td class="sec-num">{{ $p->fecha_termino->format('d-m-Y') }}</td>
            </tr>
        @endforeach
    </x-tabla>
@endif
@endsection
