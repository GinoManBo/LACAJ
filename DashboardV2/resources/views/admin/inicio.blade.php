@extends('layouts.portal', ['migas' => [['Administración']]])
@section('titulo', 'Administración')
@section('contenido')
@include('admin._nav')
<div>
    <h1 class="titulo-pagina">Administración</h1>
    <p class="texto-suave" style="margin:.25rem 0 0">Estado general del sistema en todas las regiones.</p>
</div>

<div class="rejilla-indicadores">
    <x-indicador :valor="$indicadores['practicas_activas']" etiqueta="Prácticas en curso" href="{{ route('admin.practicantes.index') }}" />
    <x-indicador :valor="$indicadores['postulaciones_pendientes']" etiqueta="Postulaciones pendientes" detalle="Esperan respuesta de una secretaría" :critico="$indicadores['postulaciones_pendientes'] > 0" />
    <x-indicador :valor="$indicadores['ocupacion_pct'].'%'" etiqueta="Ocupación de cupos" href="{{ route('admin.capacidad.index') }}">
    </x-indicador>
    <x-indicador :valor="$indicadores['practicantes_registrados']" etiqueta="Practicantes registrados" />
</div>

<div class="rejilla-dos">
    <x-tarjeta titulo="Información del sistema">
        <dl class="lista-datos">
            <div><dt>Versión</dt><dd>{{ $sistema['version'] }}</dd></div>
            <div><dt>Usuarios</dt><dd>{{ $sistema['usuarios'] }} <span class="texto-suave">({{ $sistema['usuarios_activos'] }} activos)</span></dd></div>
            <div><dt>Consultorios</dt><dd>{{ $sistema['consultorios'] }}</dd></div>
            <div><dt>Cupos totales</dt><dd>{{ $sistema['cupos_totales'] }}</dd></div>
        </dl>
    </x-tarjeta>

    <x-tarjeta titulo="Ir a">
        <div class="accesos">
            <a href="{{ route('admin.practicantes.index') }}" class="acceso"><strong>Gestión de practicantes</strong><span>Editar fichas, trasladar de sede o cancelar una práctica.</span></a>
            <a href="{{ route('admin.capacidad.index') }}" class="acceso"><strong>Capacidad de consultorios</strong><span>Agregar o retirar cupos por sede.</span></a>
            <a href="{{ route('admin.usuarios.index') }}" class="acceso"><strong>Usuarios</strong><span>Crear cuentas, cambiar roles y desactivar accesos.</span></a>
        </div>
    </x-tarjeta>
</div>
@endsection
