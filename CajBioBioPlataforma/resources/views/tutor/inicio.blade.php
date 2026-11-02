@extends('layouts.portal', ['migas' => [[$esSegundaInstancia ? 'Segunda Instancia (Corte)' : 'Mis postulantes']]])
@section('titulo', $esSegundaInstancia ? 'Segunda Instancia (Corte de Apelaciones)' : 'Mis postulantes')

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
        <p class="texto-suave">
            @if ($esSegundaInstancia)
                Abogado Jefe de Segunda Instancia · Corte de Apelaciones (Región {{ $abogado->region?->nombre ?? 'Regional' }})
            @else
                Abogado Jefe de Consultorio{{ $abogado?->area ? ' · Área '.($areas[$abogado->area] ?? ucfirst($abogado->area)) : '' }}
            @endif
        </p>
    </div>
</header>

<div class="rejilla-indicadores tp-entra" style="--i:1">
    @if ($esSegundaInstancia)
        <x-indicador :valor="$metricas['total_region'] ?? 0" etiqueta="Expedientes regionales" detalle="Visibilidad transversal de la región" />
        <x-indicador :valor="$metricas['evaluadas_corte'] ?? 0" etiqueta="Evaluadas en Corte" detalle="Calificación de 2ª instancia emitida" />
        <x-indicador :valor="$metricas['pendientes_corte'] ?? 0" etiqueta="Pendientes en Corte" detalle="Alegatos sin calificación registrada" />
        <x-indicador :valor="$noLeidas" etiqueta="Notificaciones sin leer" :critico="$noLeidas > 0" :href="route('notificaciones.index')" detalle="Ver notificaciones" />
    @else
        <x-indicador :valor="$metricas['activos'] ?? 0" etiqueta="Postulantes en curso" :detalle="$practicas->count().' '.($practicas->count() === 1 ? 'práctica en lista' : 'prácticas en lista')" />
        <x-indicador :valor="$metricas['por_terminar'] ?? 0" etiqueta="Por terminar (alerta)" :critico="($metricas['por_terminar'] ?? 0) > 0" detalle="A 2 semanas de los 6 meses" />
        <x-indicador :valor="$noLeidas" etiqueta="Notificaciones sin leer" :critico="$noLeidas > 0" :href="route('notificaciones.index')" detalle="Ver notificaciones" />
        <x-indicador :valor="$consultorios->count()" etiqueta="{{ $consultorios->count() === 1 ? 'Consultorio asignado' : 'Consultorios asignados' }}" :detalle="$consultorios->pluck('nombre')->take(2)->join(', ')" />
    @endif
</div>

<x-tarjeta :titulo="$esSegundaInstancia ? 'Evaluaciones de Segunda Instancia (Corte de Apelaciones)' : 'Mis postulantes (Consultorio)'" class="tp-entra" style="--i:2">
    <form method="GET" action="{{ route('tutor.inicio') }}" class="tp-filtros" role="search" data-sin-validar>
        <x-campo name="q" label="Buscar" :value="$filtros['q']" placeholder="Nombre, RUT o universidad" maxlength="100" type="search" />
        <x-campo name="estado" label="Estado" :opciones="$opcionesEstado" :value="$filtros['estado']" placeholder="Todos" />
        <div class="tp-filtros-acciones">
            <x-boton type="submit">Buscar</x-boton>
            @if ($conFiltros)<x-boton tipo="secundario" :href="route('tutor.inicio')" aria-label="Limpiar búsqueda">Limpiar</x-boton>@endif
        </div>
    </form>

    <div style="margin-top:1.25rem">
        @if ($practicas->isEmpty())
            <x-estado-vacio :titulo="$conFiltros ? 'Sin resultados para su búsqueda' : ($esSegundaInstancia ? 'No hay expedientes regionales disponibles' : 'Aún no tiene postulantes asignados')"
                :texto="$conFiltros ? 'Pruebe con otro nombre, universidad o estado.' : ($esSegundaInstancia ? 'Los postulantes de su región aparecerán aquí para calificar alegatos ante la Corte.' : 'Cuando la secretaría asigne un postulante a su cargo, aparecerá aquí.')">
                @if ($conFiltros)<x-boton tipo="secundario" :href="route('tutor.inicio')">Quitar filtros</x-boton>@endif
            </x-estado-vacio>
        @else
            @if ($esSegundaInstancia)
                <x-tabla :columnas="['Postulante', 'Universidad', 'Consultorio', 'Período', '1ª Instancia', '2ª Instancia (Corte)']" descripcion="Expedientes regionales para calificar en Corte de Apelaciones">
                    @foreach ($practicas as $p)
                        @php
                            $pos = $p->practicante->postulante;
                            $c1 = $p->calificacionPrimeraInstancia();
                            $c2 = $p->calificacionSegundaInstancia();
                        @endphp
                        <tr data-href="{{ route('tutor.practicantes.ver', $p->id) }}">
                            <td class="tp-persona">
                                <a href="{{ route('tutor.practicantes.ver', $p->id) }}"><strong>{{ $pos->nombre_completo }}</strong></a>
                                <span>{{ $pos->rut }} · {{ $pos->email }}</span>
                            </td>
                            <td>{{ $pos->universidad }}</td>
                            <td>{{ $p->consultorio->nombre ?? '—' }}</td>
                            <td>{{ $fmt($p->fecha_inicio) }} al {{ $fmt($p->fecha_termino) }}</td>
                            <td>
                                @if ($c1 || $p->evaluacion)
                                    <span class="badge" style="background:#e0f2fe;color:#0369a1;padding:0.2rem 0.5rem;border-radius:9999px;font-size:0.8rem;font-weight:600;">
                                        {{ \App\Support\Notas::concepto($c1?->nota ?? $p->evaluacion?->promedio) }}
                                    </span>
                                @else
                                    <span class="texto-suave" style="font-size:0.8rem;">En curso</span>
                                @endif
                            </td>
                            <td>
                                @if ($c2)
                                    <span class="badge" style="background:#f0fdf4;color:#166534;padding:0.2rem 0.5rem;border-radius:9999px;font-size:0.8rem;font-weight:600;">
                                        {{ \App\Support\Notas::concepto($c2->nota) }}
                                    </span>
                                @else
                                    <span class="badge" style="background:#fef3c7;color:#92400e;padding:0.2rem 0.5rem;border-radius:9999px;font-size:0.8rem;font-weight:600;">
                                        Por calificar
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-tabla>
            @else
                <x-tabla :columnas="['Postulante', 'Universidad', 'Consultorio', 'Período', 'Estado', 'Evaluación']" descripcion="Prácticas asignadas a usted">
                    @foreach ($practicas as $p)
                        @php
                            $pos = $p->practicante->postulante;
                            $alerta = $p->estado_mostrado === 'por_terminar';
                            $c1 = $p->calificacionPrimeraInstancia();
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
                                        Cierra pronto (día 166)
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($p->evaluacion || $c1)
                                    <span class="badge" style="background:#f0fdf4;color:#166534;padding:0.2rem 0.5rem;border-radius:9999px;font-size:0.8rem;font-weight:600;">
                                        {{ \App\Support\Notas::concepto($c1?->nota ?? $p->evaluacion?->promedio) }}
                                    </span>
                                @else
                                    <span class="texto-suave">Pendiente</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-tabla>
            @endif
        @endif
    </div>
</x-tarjeta>
@endsection
