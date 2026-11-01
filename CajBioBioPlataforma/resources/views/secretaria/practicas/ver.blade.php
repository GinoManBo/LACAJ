@extends('layouts.portal', ['migas' => [['Prácticas activas', route('secretaria.practicas')], ['Detalle']]])
@section('titulo', 'Práctica de '.$practica->practicante->postulante->nombre)
@section('contenido')
@php
    $postulante = $practica->practicante->postulante; $nom = $postulante->nombre.' '.$postulante->apellido;
    $est = $practica->estado_visible; $c = $practica->consultorio; $a = $practica->abogado?->usuario; $e = $practica->estadistica;
    $anio = ['quinto' => 'Quinto año', 'egresado' => 'Egresado/a'][$postulante->anio_carrera] ?? $postulante->anio_carrera;
@endphp
<div class="sec-cabecera">
    <div>
        <div class="sec-titulo-ficha"><h1 class="titulo-pagina">{{ $nom }}</h1><x-badge :estado="$est" :texto="$est === 'por_terminar' ? 'Por terminar' : null" /></div>
        <p class="sec-subtitulo">{{ $postulante->universidad }} · {{ $c->nombre }}</p>
    </div>
    <div class="sec-busqueda">
        <x-boton tipo="secundario" :href="route('secretaria.practicas.estadistica', $practica->id)">@include('secretaria._icono', ['n' => 'grafico']) Ver estadísticas</x-boton>
        <x-boton tipo="peligro" data-modal-abrir="modal-cancelar">Cancelar práctica</x-boton>
    </div>
</div>

<div class="sec-dos">
    <x-tarjeta titulo="Postulante">
        <dl class="datos">
            <div><dt>RUT</dt><dd>{{ $postulante->rut }}</dd></div>
            <div><dt>Año de carrera</dt><dd>{{ $anio }}</dd></div>
            <div><dt>Correo</dt><dd><a href="mailto:{{ $postulante->email }}">{{ $postulante->email }}</a></dd></div>
            <div><dt>Teléfono</dt><dd>{{ $postulante->telefono }}</dd></div>
            <div class="ancho-completo"><dt>Domicilio</dt><dd>{{ $postulante->direccion }}, {{ $postulante->comuna?->nombre }}</dd></div>
            <div class="ancho-completo"><dt>Discapacidad</dt><dd>{{ $postulante->discapacidad ? 'Sí' : 'No' }}@if ($postulante->discapacidad && $postulante->discapacidad_descripcion) — {{ $postulante->discapacidad_descripcion }}@endif</dd></div>
        </dl>
    </x-tarjeta>
    <x-tarjeta titulo="Práctica">
        <dl class="datos">
            <div class="ancho-completo"><dt>Consultorio</dt><dd>{{ $c->nombre }}<span class="sec-menor">{{ $c->direccion }}@if ($c->comuna), {{ $c->comuna->nombre }}@endif</span></dd></div>
            <div class="ancho-completo"><dt>Abogado tutor</dt><dd>{{ $a ? $a->nombre.' '.$a->apellido : 'Sin asignar' }}</dd></div>
            <div><dt>Inicio</dt><dd>{{ $practica->fecha_inicio->format('d-m-Y') }}</dd></div>
            <div><dt>Término</dt><dd>{{ $practica->fecha_termino->format('d-m-Y') }}</dd></div>
        </dl>
        <div class="sec-avance" style="margin-top:1.25rem;font-size:.88rem">
            <div class="sec-barra" role="img" aria-label="Avance {{ $e['porcentaje'] }}%"><i style="--p:{{ $e['porcentaje'] }}%"></i></div>
            <span>{{ $e['porcentaje'] }}% · {{ $e['transcurridos'] }} de {{ $e['total'] }} días · <a href="{{ route('secretaria.practicas.estadistica', $practica->id) }}">Ver estadísticas</a></span>
        </div>
    </x-tarjeta>
</div>
@include('secretaria.practicas._modal-cancelar', ['accion' => route('secretaria.practicas.cancelar', $practica->id), 'nombre' => $nom])
@endsection
