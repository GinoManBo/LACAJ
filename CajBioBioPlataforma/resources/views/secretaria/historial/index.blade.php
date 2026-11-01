@extends('layouts.portal', ['migas' => [['Historial']]])
@section('titulo', 'Historial')
@section('contenido')
<div class="sec-cabecera">
    <div>
        <h1 class="titulo-pagina">Historial de prácticas</h1>
        <p class="texto-suave">{{ $practicas->count() }} {{ $practicas->count() === 1 ? 'práctica cerrada' : 'prácticas cerradas' }}{{ $q !== '' ? ' para «'.$q.'»' : '' }}{{ $anio ? ' en '.$anio : '' }}.</p>
    </div>
</div>

<form method="GET" action="{{ route('secretaria.historial') }}" class="sec-busqueda" role="search">
    <div class="campo"><label for="q" class="sr-only">Buscar en el historial</label>
        <input id="q" name="q" type="search" value="{{ $q }}" class="campo-control" placeholder="Nombre, universidad o consultorio"></div>
    <div class="campo"><label for="anio" class="sr-only">Año</label>
        <select id="anio" name="anio" class="campo-control">
            <option value="">Todos los años</option>
            @foreach ($anios as $a)<option value="{{ $a }}" @selected((string) $anio === (string) $a)>{{ $a }}</option>@endforeach
        </select></div>
    <x-boton type="submit">@include('secretaria._icono', ['n' => 'buscar']) Filtrar</x-boton>
    @if ($q !== '' || $anio)<x-boton tipo="fantasma" :href="route('secretaria.historial')">Limpiar</x-boton>@endif
</form>

@if ($practicas->isEmpty())
    <x-estado-vacio titulo="No hay prácticas cerradas" :texto="($q !== '' || $anio) ? 'Ninguna coincide con los filtros.' : 'Las prácticas finalizadas o canceladas quedarán registradas aquí.'" />
@else
    <x-tabla :columnas="['Postulante', 'Consultorio', 'Abogado tutor', 'Periodo', 'Nota', 'Resultado']" descripcion="Prácticas finalizadas y canceladas">
        @foreach ($practicas as $p)
            @php $po = $p->practicante->postulante; $a = $p->abogado?->usuario; $noInicio = $p->estado === 'cancelada' && $p->cancelacion_antes_de_iniciar; @endphp
            <tr data-href="{{ route('secretaria.historial.ver', $p->id) }}">
                <td><a href="{{ route('secretaria.historial.ver', $p->id) }}"><strong>{{ $po->nombre }} {{ $po->apellido }}</strong></a><span class="sec-menor">{{ $po->universidad }}</span></td>
                <td>{{ $p->consultorio->nombre }}</td>
                <td>{{ $a ? $a->nombre.' '.$a->apellido : 'Sin asignar' }}</td>
                <td class="sec-num">{{ $p->fecha_inicio->format('d-m-Y') }}<span class="sec-menor">al {{ $p->fecha_termino->format('d-m-Y') }}</span></td>
                <td class="sec-num">{{ $p->nota_practica !== null ? number_format($p->nota_practica, 1, ',', '') : '—' }}</td>
                <td><x-badge :estado="$p->estado" />@if ($noInicio) <span class="sec-tag sec-tag-no-inicio">NO INICIÓ</span>@endif</td>
            </tr>
        @endforeach
    </x-tabla>
@endif
@endsection
