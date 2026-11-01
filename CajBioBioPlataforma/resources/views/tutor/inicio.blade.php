@extends('layouts.portal', ['migas' => [['Mis postulantes']]])
@section('titulo', 'Mis postulantes')

@section('contenido')
@php
    $areas = ['civil' => 'Civil', 'penal' => 'Penal', 'laboral' => 'Laboral', 'familia' => 'Familia'];
    $etiquetasEstado = ['por_iniciar' => 'Por iniciar', 'activa' => 'En curso', 'por_terminar' => 'Por terminar', 'finalizada' => 'Finalizada', 'cancelada' => 'Cancelada'];
    $opcionesEstado = collect($estadosFiltro)->mapWithKeys(fn ($e) => [$e => $etiquetasEstado[$e] ?? $e])->all();
    $conFiltros = filled($filtros['q'] ?? null) || filled($filtros['estado'] ?? null);
    $fmt = fn ($f) => $f?->locale('es')->translatedFormat('j M Y');
@endphp

<header class="tp-cabecera tp-entra">
    <div>
        <h1 class="titulo-pagina">Hola, {{ $usuario->nombre }}</h1>
        <p class="texto-suave">Abogado tutor{{ $abogado?->area ? ' · Área '.($areas[$abogado->area] ?? ucfirst($abogado->area)) : '' }}</p>
    </div>
</header>

<div class="rejilla-indicadores tp-entra" style="--i:1">
    <x-indicador :valor="$activos" etiqueta="Postulantes en curso" :detalle="$practicas->count().' '.($practicas->count() === 1 ? 'práctica en la lista' : 'prácticas en la lista')" />
    <x-indicador :valor="$noLeidas" etiqueta="Notificaciones sin leer" :critico="$noLeidas > 0" :href="route('notificaciones.index')" detalle="Ver notificaciones" />
    <x-indicador :valor="$consultorios->count()" etiqueta="{{ $consultorios->count() === 1 ? 'Consultorio asignado' : 'Consultorios asignados' }}" :detalle="$consultorios->pluck('nombre')->take(2)->join(', ')" />
</div>

<x-tarjeta titulo="Mis postulantes" class="tp-entra" style="--i:2">
    <form method="GET" action="{{ route('tutor.inicio') }}" class="tp-filtros" role="search" data-sin-validar>
        <x-campo name="q" label="Buscar" :value="$filtros['q']" placeholder="Nombre o universidad" maxlength="100" type="search" />
        <x-campo name="estado" label="Estado" :opciones="$opcionesEstado" :value="$filtros['estado']" placeholder="Todos" />
        <div class="tp-filtros-acciones">
            <x-boton type="submit">Buscar</x-boton>
            @if ($conFiltros)<x-boton tipo="secundario" :href="route('tutor.inicio')" aria-label="Limpiar búsqueda">Limpiar</x-boton>@endif
        </div>
    </form>

    <div style="margin-top:1.25rem">
        @if ($practicas->isEmpty())
            <x-estado-vacio :titulo="$conFiltros ? 'Sin resultados para su búsqueda' : 'Aún no tiene postulantes asignados'"
                :texto="$conFiltros ? 'Pruebe con otro nombre, universidad o estado.' : 'Cuando la secretaría asigne un postulante a su cargo, aparecerá aquí.'">
                @if ($conFiltros)<x-boton tipo="secundario" :href="route('tutor.inicio')">Quitar filtros</x-boton>@endif
            </x-estado-vacio>
        @else
            <x-tabla :columnas="['Postulante', 'Universidad', 'Consultorio', 'Período', 'Estado', 'Evaluación']" descripcion="Prácticas asignadas a usted">
                @foreach ($practicas as $p)
                    @php
                        $pos = $p->practicante->postulante;
                        $alerta = $p->estado_mostrado === 'por_terminar';
                    @endphp
                    <tr data-href="{{ route('tutor.practicantes.ver', $p->id) }}" @class(['tp-fila-alerta' => $alerta])>
                        <td class="tp-persona">
                            <a href="{{ route('tutor.practicantes.ver', $p->id) }}"><strong>{{ $pos->nombre_completo }}</strong></a>
                            <span>{{ $pos->email }}@if ($pos->telefono) · {{ $pos->telefono }}@endif</span>
                        </td>
                        <td>{{ $pos->universidad }}</td>
                        <td>{{ $p->consultorio->nombre ?? '—' }}</td>
                        <td>{{ $fmt($p->fecha_inicio) }} al {{ $fmt($p->fecha_termino) }}</td>
                        <td>
                            <x-badge :estado="$p->estado_mostrado" :texto="$etiquetasEstado[$p->estado_mostrado] ?? null" />
                            @if ($alerta)
                                <div class="tp-aviso-cierre" role="note">
                                    <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4M12 17h.01M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                                    Cierra pronto
                                </div>
                            @endif
                        </td>
                        <td>@if ($p->evaluacion)<x-badge :estado="$p->evaluacion->aprobado ? 'aprobado' : 'reprobado'" :texto="$p->evaluacion->aprobado ? 'Aprobó' : 'No aprobó'" />@else<span class="texto-suave">Pendiente</span>@endif</td>
                    </tr>
                @endforeach
            </x-tabla>
        @endif
    </div>
</x-tarjeta>
@endsection
