@extends('layouts.portal', ['migas' => [['Panel general']]])
@section('titulo', 'Panel general')
@section('contenido')
<div class="sec-cabecera">
    <div>
        <h1 class="titulo-pagina">Panel de secretaría</h1>
        <p class="texto-suave">Región de {{ $region->nombre }}</p>
    </div>
</div>

<div class="rejilla-indicadores">
    <x-indicador :valor="$pendientes" etiqueta="Postulaciones pendientes" detalle="Esperan su revisión" :href="route('secretaria.postulaciones')" :critico="$pendientes > 0" />
    <x-indicador :valor="$activas" etiqueta="Prácticas activas" detalle="Por iniciar y en curso" :href="route('secretaria.practicas')" />
    <x-indicador :valor="$porTerminarCount" etiqueta="Por terminar" detalle="Cierran en los próximos 30 días" :href="route('secretaria.practicas')" />
</div>

<div class="sec-dos">
    <x-tarjeta titulo="Postulaciones pendientes">
        <x-slot:acciones><x-boton tipo="secundario" tam="sm" :href="route('secretaria.postulaciones')">Ver todas</x-boton></x-slot:acciones>
        @if ($ultimasPostulaciones->isEmpty())
            <x-estado-vacio titulo="Sin postulaciones pendientes" texto="Cuando llegue una nueva postulación a su región aparecerá aquí." />
        @else
            <ul class="sec-lista">
                @foreach ($ultimasPostulaciones as $p)
                    <li>
                        <div><a href="{{ route('secretaria.postulaciones.ver', $p->id) }}">{{ $p->postulante->nombre }} {{ $p->postulante->apellido }}</a><span class="sec-menor">{{ $p->postulante->universidad }}</span></div>
                        <span class="sec-menor sec-num">{{ $p->fecha_postulacion->format('d-m-Y') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-tarjeta>

    <x-tarjeta titulo="Prácticas por terminar">
        <x-slot:acciones><x-boton tipo="secundario" tam="sm" :href="route('secretaria.practicas')">Ver todas</x-boton></x-slot:acciones>
        @if ($practicasPorTerminar->isEmpty())
            <x-estado-vacio titulo="Ninguna práctica por terminar" texto="Las prácticas próximas a cerrar se listarán en este espacio." />
        @else
            <ul class="sec-lista">
                @foreach ($practicasPorTerminar as $p)
                    <li>
                        <div><a href="{{ route('secretaria.practicas.ver', $p->id) }}">{{ $p->practicante->postulante->nombre }} {{ $p->practicante->postulante->apellido }}</a><span class="sec-menor">{{ $p->consultorio->nombre }}</span></div>
                        <span class="sec-menor sec-num">Termina {{ $p->fecha_termino->format('d-m-Y') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-tarjeta>
</div>
@endsection
