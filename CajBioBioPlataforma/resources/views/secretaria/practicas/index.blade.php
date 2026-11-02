@extends('layouts.portal', ['migas' => [['Prácticas activas']]])
@section('titulo', 'Prácticas activas')
@section('contenido')
@if (session('documento_cancelacion'))
    <div class="alerta alerta-aviso sec-enlace-doc" role="status">
        @include('secretaria._icono', ['n' => 'descargar'])
        <span>La práctica quedó cancelada.</span>
        <x-boton tipo="secundario" tam="sm" :href="session('documento_cancelacion')">Descargar documento de cancelación</x-boton>
    </div>
@endif

<div class="sec-cabecera">
    <div>
        <h1 class="titulo-pagina">Prácticas activas</h1>
        <p class="texto-suave">{{ $practicas->count() }} {{ $practicas->count() === 1 ? 'práctica' : 'prácticas' }}{{ $q !== '' ? ' para «'.$q.'»' : ' por iniciar o en curso' }}.</p>
    </div>
    <x-boton tipo="secundario" :href="route('secretaria.practicas.exportar', ['q' => $q])">@include('secretaria._icono', ['n' => 'excel']) Exportar a Excel</x-boton>
</div>

<div class="rejilla-indicadores">
    <x-indicador :valor="$cuposTotales" etiqueta="Cupos totales" detalle="Capacidad de los consultorios de la región" />
    <x-indicador :valor="$cuposTotales - $cuposDisponibles" etiqueta="Cupos ocupados" detalle="Prácticas por iniciar y en curso" />
    <x-indicador :valor="$cuposDisponibles" etiqueta="Cupos disponibles" :critico="$cuposTotales > 0 && $cuposDisponibles === 0" />
</div>

<form method="GET" action="{{ route('secretaria.practicas') }}" class="sec-busqueda" role="search">
    <div class="campo"><label for="q" class="sr-only">Buscar práctica</label>
        <input id="q" name="q" type="search" value="{{ $q }}" class="campo-control" placeholder="Nombre o apellido del postulante"></div>
    <x-boton type="submit">@include('secretaria._icono', ['n' => 'buscar']) Buscar</x-boton>
    @if ($q !== '')<x-boton tipo="fantasma" :href="route('secretaria.practicas')">Limpiar</x-boton>@endif
</form>

@if ($practicas->isEmpty())
    <x-estado-vacio titulo="No hay prácticas activas" :texto="$q !== '' ? 'Ninguna coincide con su búsqueda.' : 'Cuando acepte una postulación, la práctica aparecerá aquí.'" />
@else
    <x-tabla :columnas="['Postulante', 'Consultorio', 'Abogado Jefe', 'Periodo', 'Avance', 'Estado / Carta', 'Acciones']" descripcion="Prácticas por iniciar y en curso">
        @foreach ($practicas as $p)
            @php $po = $p->practicante->postulante; $nom = $po->nombre.' '.$po->apellido; $est = $p->estado_visible; @endphp
            <tr data-href="{{ route('secretaria.practicas.ver', $p->id) }}">
                <td><a href="{{ route('secretaria.practicas.ver', $p->id) }}"><strong>{{ $nom }}</strong></a><span class="sec-menor">{{ $po->universidad }}</span></td>
                <td>{{ $p->consultorio->nombre }}</td>
                <td>{{ $p->abogado?->usuario ? $p->abogado->usuario->nombre.' '.$p->abogado->usuario->apellido : 'Sin asignar' }}</td>
                <td class="sec-num">{{ $p->fecha_inicio->format('d-m-Y') }}<span class="sec-menor">al {{ $p->fecha_termino->format('d-m-Y') }}</span></td>
                <td><div class="sec-avance"><div class="sec-barra" role="img" aria-label="Avance {{ $p->estadistica['porcentaje'] }}%"><i style="--p:{{ $p->estadistica['porcentaje'] }}%"></i></div>{{ $p->estadistica['porcentaje'] }}%</div></td>
                <td>
                    <div>
                        <x-badge :estado="$est" :texto="$est === 'por_terminar' ? 'Por terminar' : null" />
                    </div>
                    @if ($p->carta_presentacion_firmada)
                        <span class="badge" style="background:#f0fdf4;color:#166534;font-size:0.75rem;margin-top:0.25rem;display:inline-block;">Carta firmada</span>
                    @else
                        <span class="badge" style="background:#fffbeb;color:#92400e;border:1px solid #fef3c7;font-size:0.75rem;margin-top:0.25rem;display:inline-block;">Carta pendiente</span>
                    @endif
                </td>
                <td>
                    <div class="sec-acciones">
                        <a class="sec-ico-btn" href="{{ route('secretaria.practicas.ver', $p->id) }}" title="Ver detalle" aria-label="Ver detalle de {{ $nom }}">@include('secretaria._icono', ['n' => 'ojo'])</a>
                        <a class="sec-ico-btn" href="{{ route('secretaria.practicas.estadistica', $p->id) }}" title="Estadísticas" aria-label="Ver estadísticas de {{ $nom }}">@include('secretaria._icono', ['n' => 'grafico'])</a>
                        <button type="button" class="sec-ico-btn es-peligro" title="Cancelar práctica" aria-label="Cancelar la práctica de {{ $nom }}" data-modal-abrir="modal-cancelar" data-cancelar-url="{{ route('secretaria.practicas.cancelar', $p->id) }}" data-cancelar-nombre="{{ $nom }}">@include('secretaria._icono', ['n' => 'cancelar'])</button>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-tabla>
    @include('secretaria.practicas._modal-cancelar')
@endif
@endsection
