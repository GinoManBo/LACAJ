@extends('layouts.portal', ['migas' => [['Administración', route('admin.inicio')], ['Postulantes']]])
@section('titulo', 'Gestión de postulantes')
@section('contenido')
@include('admin._nav')
<div>
    <h1 class="titulo-pagina">Gestión de postulantes</h1>
    <p class="texto-suave" style="margin:.25rem 0 0">{{ $practicas->total() }} {{ $practicas->total() === 1 ? 'práctica' : 'prácticas' }}{{ $q !== '' ? ' para «'.$q.'»' : ' en todas las regiones' }}.</p>
</div>

<form method="GET" action="{{ route('admin.practicantes.index') }}" class="barra-busqueda" role="search">
    <div class="campo" style="flex:1">
        <label for="q" class="sr-only">Buscar</label>
        <input id="q" name="q" type="search" value="{{ $q }}" class="campo-control" placeholder="Nombre, universidad, sede o abogado">
    </div>
    <x-boton type="submit">Buscar</x-boton>
    @if ($q !== '')<x-boton tipo="fantasma" href="{{ route('admin.practicantes.index') }}">Limpiar</x-boton>@endif
</form>

@if ($practicas->isEmpty())
    <x-estado-vacio titulo="No hay postulantes que coincidan" texto="Prueba con otro nombre, universidad o sede.">
        @if ($q !== '')<x-boton tipo="secundario" href="{{ route('admin.practicantes.index') }}">Ver todos</x-boton>@endif
    </x-estado-vacio>
@else
    <x-tabla :columnas="['Postulante', 'Universidad', 'Sede', 'Abogado tutor', 'Periodo', 'Estado']" descripcion="Prácticas registradas">
        @foreach ($practicas as $p)
            @php $po = $p->practicante->postulante; @endphp
            <tr data-href="{{ route('admin.practicantes.ver', $p) }}">
                <td><a href="{{ route('admin.practicantes.ver', $p) }}" class="enlace-fila"><strong>{{ $po->nombre }} {{ $po->apellido }}</strong></a><div class="texto-suave dato-menor">{{ $po->email }}</div></td>
                <td>{{ $po->universidad }}</td>
                <td>{{ $p->consultorio->nombre }}<div class="texto-suave dato-menor">{{ $p->consultorio->region->nombre }}</div></td>
                <td>{{ $p->abogado?->usuario ? $p->abogado->usuario->nombre.' '.$p->abogado->usuario->apellido : 'Sin tutor' }}</td>
                <td class="nowrap">{{ $p->fecha_inicio->format('d/m/Y') }} – {{ $p->fecha_termino->format('d/m/Y') }}</td>
                <td><x-badge :estado="$p->estado" /></td>
            </tr>
        @endforeach
    </x-tabla>
    <div class="paginacion">{{ $practicas->links() }}</div>
@endif
@endsection
