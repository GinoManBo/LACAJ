@extends('layouts.portal', ['migas' => [['Prácticas activas', route('secretaria.practicas')], ['Estadísticas']]])
@section('titulo', 'Estadísticas de la práctica')
@section('contenido')
@php $po = $practica->practicante->postulante; $a = $practica->abogado?->usuario; @endphp
<div>
    <h1 class="titulo-pagina">Avance de {{ $po->nombre }} {{ $po->apellido }}</h1>
    <p class="sec-subtitulo">{{ $practica->consultorio->nombre }} · Tutor: {{ $a ? $a->nombre.' '.$a->apellido : 'sin asignar' }}</p>
</div>

<x-tarjeta acento>
    <div class="sec-porcentaje">{{ $estadistica['porcentaje'] }}%</div>
    <p class="texto-suave" style="margin:.25rem 0 1rem">de la práctica completada</p>
    <div class="sec-barra sec-barra-grande" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $estadistica['porcentaje'] }}" aria-label="Avance de la práctica"><i style="--p:{{ $estadistica['porcentaje'] }}%"></i></div>
    <div class="sec-hitos"><span>{{ $practica->fecha_inicio->format('d-m-Y') }}</span><span>{{ $practica->fecha_termino->format('d-m-Y') }}</span></div>
</x-tarjeta>

<div class="sec-cifras">
    <x-indicador :valor="$estadistica['total']" etiqueta="Días totales" />
    <x-indicador :valor="$estadistica['transcurridos']" etiqueta="Días transcurridos" />
    <x-indicador :valor="$estadistica['restantes']" etiqueta="Días restantes" />
</div>
<div><x-boton tipo="secundario" :href="route('secretaria.practicas')">@include('secretaria._icono', ['n' => 'volver']) Volver a Prácticas Activas</x-boton></div>
@endsection
