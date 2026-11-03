@extends('layouts.portal', ['migas' => [['Administración', route('admin.inicio')], ['Métricas Globales']]])
@section('titulo', 'Métricas Globales y Estadísticas — Administración')

@section('contenido')
@include('admin._nav')

<div class="sec-cabecera" style="margin-top:1rem;">
    <div>
        <h1 class="titulo-pagina">Métricas Globales y Estadísticas</h1>
        <p class="texto-suave">
            Monitoreo macroinstitucional en tiempo real: tasa de ocupación de cupos por región, tiempos promedio de asignación y causales anuales de cancelación.
        </p>
    </div>
    <div class="sec-acciones">
        <x-boton tipo="secundario" :href="route('admin.estadisticas.exportar')">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right:0.25rem;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Exportar Consolidado a Excel
        </x-boton>
    </div>
</div>

<!-- Indicadores Macroinstitucionales -->
<div class="rejilla-indicadores">
    <x-indicador :valor="$totalesMacro['capacidad_nacional']" etiqueta="Capacidad Total Nacional" detalle="{{ $totalesMacro['ocupados_nacional'] }} ocupados ({{ $totalesMacro['pct_ocupacion_nacional'] }}%)" />
    <x-indicador :valor="$totalesMacro['libres_nacional']" etiqueta="Cupos Libres Inmediatos" detalle="Disponibles en toda la red" :critico="$totalesMacro['libres_nacional'] === 0" />
    <x-indicador :valor="$totalesMacro['tiempo_promedio_general'].' días'" etiqueta="Tiempo Promedio Asignación" detalle="Desde postulación hasta inicio" />
    <x-indicador :valor="$totalesMacro['practicas_activas']" etiqueta="Prácticas Activas en Curso" detalle="{{ $totalesMacro['postulaciones_pendientes'] }} postulaciones pendientes" />
</div>

<!-- Sección 1: Capacidad y Ocupación Territorial por Región -->
<div class="tarjeta" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <h3 style="font-size:1.15rem; font-weight:700; color:#1e3a8a; margin-bottom:0.25rem;">
        Capacidad y Tasa de Ocupación por Región
    </h3>
    <p style="font-size:0.85rem; color:#64748b; margin-bottom:1rem;">
        Distribución de cupos formativos autorizados y dotación de practicantes activos en las 6 regiones de la Corporación.
    </p>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Región</th>
                    <th style="text-align: center;">Sedes Activas</th>
                    <th style="text-align: center;">Capacidad Cupos</th>
                    <th style="text-align: center;">Ocupados</th>
                    <th style="text-align: center;">Disponibles</th>
                    <th style="width: 220px;">Tasa de Ocupación</th>
                    <th style="text-align: right;">Disponibilidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($ocupacionPorRegion as $item)
                    @php
                        $r = $item['region'];
                        $badgeStyle = match ($item['disponibilidad']) {
                            'alta', 'muchos' => 'background:#dcfce7; color:#15803d;',
                            'media' => 'background:#e0f2fe; color:#0369a1;',
                            'baja', 'pocos' => 'background:#fef3c7; color:#b45309;',
                            default => 'background:#fee2e2; color:#b91c1c;',
                        };
                        $dispTexto = match ($item['disponibilidad']) {
                            'alta', 'muchos' => 'Alta',
                            'media' => 'Media',
                            'baja', 'pocos' => 'Baja',
                            default => 'Sin cupos',
                        };
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $r->nombre }}</strong>
                            <span style="font-size:0.8rem; color:#64748b;">({{ $r->codigo }})</span>
                        </td>
                        <td style="text-align: center;">{{ $item['consultorios'] }}</td>
                        <td style="text-align: center; font-weight:600;">{{ $item['capacidad'] }}</td>
                        <td style="text-align: center; color:#b45309; font-weight:600;">{{ $item['ocupados'] }}</td>
                        <td style="text-align: center; color:#15803d; font-weight:600;">{{ $item['libres'] }}</td>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.5rem;">
                                <div style="flex:1; background:#e2e8f0; height:8px; border-radius:4px; overflow:hidden;">
                                    <div style="width:{{ min(100, $item['porcentaje']) }}%; height:100%; background:{{ $item['porcentaje'] >= 90 ? '#ef4444' : ($item['porcentaje'] >= 70 ? '#f59e0b' : '#3b82f6') }};"></div>
                                </div>
                                <span style="font-size:0.8rem; font-weight:600; width:35px; text-align:right;">{{ $item['porcentaje'] }}%</span>
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <span class="badge" style="{{ $badgeStyle }} font-weight:600; padding:0.25rem 0.5rem; border-radius:4px; font-size:0.8rem;">
                                {{ $dispTexto }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="sec-dos" style="margin-bottom: 1.5rem;">
    <!-- Sección 2: Tiempos Anuales de Asignación -->
    <div class="tarjeta" style="padding: 1.25rem;">
        <h3 style="font-size:1.15rem; font-weight:700; color:#1e3a8a; margin-bottom:0.25rem;">
            Tiempos de Asignación
        </h3>
        <p style="font-size:0.85rem; color:#64748b; margin-bottom:1rem;">
            Días transcurridos en promedio desde el ingreso de la postulación hasta el inicio efectivo de la práctica.
        </p>

        @if (empty($tiemposPorAnio))
            <p style="color:#94a3b8; font-style:italic;">No hay registros históricos suficientes para calcular tiempos de asignación.</p>
        @else
            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Año</th>
                            <th style="text-align: center;">Asignaciones</th>
                            <th style="text-align: center;">Promedio</th>
                            <th style="text-align: right;">Rango (Min - Max)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tiemposPorAnio as $anio => $info)
                            <tr>
                                <td><strong>{{ $anio }}</strong></td>
                                <td style="text-align: center;">{{ $info['total'] }}</td>
                                <td style="text-align: center;">
                                    <span class="badge" style="background:#e0f2fe; color:#0369a1; font-weight:700;">
                                        {{ $info['promedio'] }} días
                                    </span>
                                </td>
                                <td style="text-align: right; font-size:0.85rem; color:#64748b;">
                                    {{ $info['min'] }} – {{ $info['max'] }} días
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (!empty($tiemposPorRegion))
                <div style="margin-top:1rem; border-top:1px solid #e2e8f0; padding-top:0.75rem;">
                    <div style="font-weight:600; font-size:0.85rem; color:#334155; margin-bottom:0.5rem;">Promedio histórico por región:</div>
                    <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                        @foreach ($tiemposPorRegion as $regNom => $rDat)
                            <span class="badge" style="background:#f1f5f9; color:#475569; padding:0.3rem 0.6rem; font-size:0.8rem;">
                                {{ $regNom }}: <strong>{{ $rDat['promedio'] }}d</strong> ({{ $rDat['total'] }})
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>

    <!-- Sección 3: Métricas de Cancelaciones por Causal -->
    <div class="tarjeta" style="padding: 1.25rem;">
        <h3 style="font-size:1.15rem; font-weight:700; color:#b91c1c; margin-bottom:0.25rem;">
            Causales de Cancelación
        </h3>
        <p style="font-size:0.85rem; color:#64748b; margin-bottom:1rem;">
            Clasificación anual de cancelaciones por causales reglamentarias e incidencias operativas.
        </p>

        @if (empty($cancelacionesPorAnio))
            <p style="color:#94a3b8; font-style:italic;">No se registran cancelaciones en el historial.</p>
        @else
            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Año</th>
                            <th style="text-align: center;">Total</th>
                            <th style="text-align: center;">Conducta</th>
                            <th style="text-align: center;">Renuncia / Personal</th>
                            <th style="text-align: center;">Error Registro</th>
                            <th style="text-align: right;">Otros</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cancelacionesPorAnio as $anio => $cDat)
                            <tr>
                                <td><strong>{{ $anio }}</strong></td>
                                <td style="text-align: center; font-weight:700; color:#b91c1c;">{{ $cDat['total'] }}</td>
                                <td style="text-align: center; color:#b45309;">{{ $cDat['conducta'] }}</td>
                                <td style="text-align: center; color:#475569;">{{ $cDat['decision'] }}</td>
                                <td style="text-align: center; color:#0284c7;">{{ $cDat['error_registro'] }}</td>
                                <td style="text-align: right; color:#64748b;">{{ $cDat['otro'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top:1rem; background:#fffbeb; border:1px solid #fef3c7; border-radius:6px; padding:0.6rem 0.85rem; font-size:0.8rem; color:#92400e;">
                <strong>Control reglamentario:</strong> Las cancelaciones administrativas no admiten calificación numérica y permiten al postulante reiniciar el proceso desde cero cuando no obedece a sanción disciplinaria.
            </div>
        @endif
    </div>
</div>
@endsection
