@extends('layouts.portal', ['migas' => [['Supervisión Nacional de Postulaciones']]])
@section('titulo', 'Supervisión Nacional de Postulaciones')
@section('contenido')
<div class="sec-cabecera">
    <div>
        <h1 class="titulo-pagina">Supervisión Nacional de Postulantes</h1>
        <p class="texto-suave">Control integral nacional · Secretaría Jurídica Central (Biobío - Concepción). Supervisión transversal de postulantes en todos sus estados, gestión directa para Biobío y modo supervisión para regiones externas.</p>
    </div>
    <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
        <x-boton tipo="secundario" :href="route('secretaria.juridica.postulaciones.exportar', request()->query())" title="Exportar reporte oficial en Excel con hojas separadas para En Proceso y Finalizados">
            @include('secretaria._icono', ['n' => 'excel']) Exportar Excel (Libro Completo)
        </x-boton>
        <x-boton tipo="fantasma" tam="sm" :href="route('secretaria.juridica.postulaciones.exportar', array_merge(request()->query(), ['hoja' => 'proceso']))" title="Exportar solo postulantes que no han terminado">
            Solo En Proceso
        </x-boton>
        <x-boton tipo="fantasma" tam="sm" :href="route('secretaria.juridica.postulaciones.exportar', array_merge(request()->query(), ['hoja' => 'terminados']))" title="Exportar solo postulantes finalizados con rúbrica">
            Solo Finalizados
        </x-boton>
    </div>
</div>

<div class="indicadores-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem;">
    <div class="tarjeta" style="padding:1rem;background:var(--blanco);border:1px solid var(--borde);border-radius:.5rem;">
        <div style="font-size:.82rem;color:var(--texto-suave);font-weight:600;text-transform:uppercase;">Postulantes Totales</div>
        <div style="font-size:1.8rem;font-weight:700;color:var(--azul-800);margin-top:.25rem;">{{ $totales['total'] }}</div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-top:.25rem;">Registros nacionales</div>
    </div>
    <div class="tarjeta" style="padding:1rem;background:var(--blanco);border:1px solid var(--borde);border-radius:.5rem;">
        <div style="font-size:.82rem;color:var(--texto-suave);font-weight:600;text-transform:uppercase;">Pendientes en Espera</div>
        <div style="font-size:1.8rem;font-weight:700;color:#d97706;margin-top:.25rem;">{{ $totales['pendientes_total'] }}</div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-top:.25rem;">Por revisar o asignar</div>
    </div>
    <div class="tarjeta" style="padding:1rem;background:var(--blanco);border:1px solid var(--borde);border-radius:.5rem;">
        <div style="font-size:.82rem;color:var(--texto-suave);font-weight:600;text-transform:uppercase;">En Curso Activo</div>
        <div style="font-size:1.8rem;font-weight:700;color:var(--azul-600);margin-top:.25rem;">{{ $totales['activas_total'] }}</div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-top:.25rem;">Prácticas en desarrollo</div>
    </div>
    <div class="tarjeta" style="padding:1rem;background:var(--blanco);border:1px solid var(--borde);border-radius:.5rem;">
        <div style="font-size:.82rem;color:var(--texto-suave);font-weight:600;text-transform:uppercase;">Terminadas / Evaluadas</div>
        <div style="font-size:1.8rem;font-weight:700;color:var(--verde-800);margin-top:.25rem;">{{ $totales['terminadas_total'] }}</div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-top:.25rem;">Finalizadas o certificadas</div>
    </div>
    <div class="tarjeta" style="padding:1rem;background:var(--blanco);border:1px solid var(--borde);border-radius:.5rem;">
        <div style="font-size:.82rem;color:var(--texto-suave);font-weight:600;text-transform:uppercase;">Gestión Directa (Biobío)</div>
        <div style="font-size:1.8rem;font-weight:700;color:var(--verde-800);margin-top:.25rem;">{{ $totales['locales_biobio'] }}</div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-top:.25rem;">Competencia Jurídica Biobío</div>
    </div>
    <div class="tarjeta" style="padding:1rem;background:var(--blanco);border:1px solid var(--borde);border-radius:.5rem;">
        <div style="font-size:.82rem;color:var(--texto-suave);font-weight:600;text-transform:uppercase;">Supervisión (Regiones)</div>
        <div style="font-size:1.8rem;font-weight:700;color:#6b21a8;margin-top:.25rem;">{{ $totales['externos'] }}</div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-top:.25rem;">Solo lectura (gestión regional)</div>
    </div>
</div>

<form method="GET" action="{{ route('secretaria.juridica.postulaciones') }}" class="sec-busqueda" role="search" style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.25rem;">
    <div class="campo" style="flex:1;min-width:220px;">
        <label for="q" class="sr-only">Buscar por postulante o RUT</label>
        <input id="q" name="q" type="search" value="{{ $q }}" class="campo-control" placeholder="Nombre, apellido, RUT o universidad">
    </div>
    <div class="campo" style="min-width:180px;">
        <label for="estado" class="sr-only">Estado del postulante</label>
        <select id="estado" name="estado" class="campo-control">
            <option value="">Todos los estados</option>
            <optgroup label="Agrupaciones">
                <option value="en_proceso" @selected($estadoFiltro === 'en_proceso')>En Proceso (Pendientes y En Curso)</option>
                <option value="terminados" @selected($estadoFiltro === 'terminados')>Terminados / Evaluados</option>
            </optgroup>
            <optgroup label="Estados específicos">
                <option value="pendiente" @selected($estadoFiltro === 'pendiente')>Pendiente de asignación</option>
                <option value="por_iniciar" @selected($estadoFiltro === 'por_iniciar')>Por iniciar práctica</option>
                <option value="activa" @selected($estadoFiltro === 'activa')>En curso (Activa)</option>
                <option value="finalizada" @selected($estadoFiltro === 'finalizada')>Finalizada (Evaluada por tutor)</option>
                <option value="remitida_biobio" @selected($estadoFiltro === 'remitida_biobio')>Remitida a Jurídica Central</option>
                <option value="nivelada_biobio" @selected($estadoFiltro === 'nivelada_biobio')>Nivelada en Biobío</option>
                <option value="cerrada_historial" @selected($estadoFiltro === 'cerrada_historial')>Cerrada / Despachada (Certificado)</option>
                <option value="rechazada" @selected($estadoFiltro === 'rechazada')>Postulación rechazada</option>
                <option value="cancelada" @selected($estadoFiltro === 'cancelada')>Cancelada</option>
                <option value="rechazada_sancion" @selected($estadoFiltro === 'rechazada_sancion')>Rechazada por sanción</option>
            </optgroup>
        </select>
    </div>
    <div class="campo" style="min-width:180px;">
        <label for="origen" class="sr-only">Origen territorial</label>
        <select id="origen" name="origen" class="campo-control">
            <option value="">Todos los orígenes</option>
            <option value="local_biobio" @selected($origenFiltro === 'local_biobio')>Solo Biobío (Gestión directa)</option>
            <option value="externo" @selected($origenFiltro === 'externo')>Solo Regiones Externas (Supervisión)</option>
        </select>
    </div>
    <div class="campo" style="min-width:180px;">
        <label for="region_id" class="sr-only">Región específica</label>
        <select id="region_id" name="region_id" class="campo-control">
            <option value="">Todas las regiones</option>
            @foreach ($regiones as $reg)
                <option value="{{ $reg->id }}" @selected((string)$regionFiltro === (string)$reg->id)>{{ $reg->nombre }} ({{ $reg->codigo }})</option>
            @endforeach
        </select>
    </div>
    <x-boton type="submit">@include('secretaria._icono', ['n' => 'buscar']) Filtrar</x-boton>
    @if ($q !== '' || $origenFiltro || $regionFiltro || $estadoFiltro)
        <x-boton tipo="fantasma" :href="route('secretaria.juridica.postulaciones')">Limpiar</x-boton>
    @endif
</form>

@if ($postulaciones->isEmpty())
    <x-estado-vacio titulo="No se encontraron postulantes" texto="No hay registros que coincidan con los criterios de búsqueda o filtros seleccionados." />
@else
    <x-tabla :columnas="['Postulante / RUT', 'Origen / Región', 'Estado', 'Consultorio / Asignación', 'Fechas Clave', 'Atribución Jurídica', 'Acción']" descripcion="Supervisión nacional de postulantes">
        @foreach ($postulaciones as $p)
            @php
                $po = $p->postulante;
                $pr = $p->practica;
                $reg = $p->region;
                $esBiobio = $reg && $reg->codigo === 'BI';
                $prefs = $p->preferencias->sortBy('orden')->values();
                $estadoItem = $pr?->estado ?? $p->estado;
                $urlVer = $pr && in_array($pr->estado, ['remitida_biobio', 'nivelada_biobio', 'rechazada_sancion', 'cerrada_historial'], true)
                    ? route('secretaria.juridica.ver', $pr->id)
                    : ($pr ? route('secretaria.practicas.ver', $pr->id) : route('secretaria.postulaciones.ver', $p->id));
                $cancList = ($cancelacionesPorRut ?? collect())->get($po?->rut);
                $cancPr = $cancList && $cancList->isNotEmpty() ? $cancList->first() : null;
            @endphp
            <tr data-href="{{ $urlVer }}">
                <td>
                    <a href="{{ $urlVer }}"><strong>{{ $po ? $po->nombre_completo : 'Sin postulante' }}</strong></a>
                    <span class="sec-menor">RUT: {{ $po?->rut ?? '—' }} · {{ $po?->universidad ?? '—' }}</span>
                    @if ($cancPr)
                        <span class="sec-tag sec-tag-peligro" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;font-weight:700;font-size:.75rem;display:inline-flex;align-items:center;gap:.25rem;margin-top:.25rem;" title="Cancelada el {{ $cancPr->cancelacion_fecha?->format('d-m-Y') }} · Motivo: {{ \App\Services\Practicas::textoMotivoCancelacion($cancPr->cancelacion_motivo) }}">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            Cancelación previa: {{ \App\Services\Practicas::textoMotivoCancelacion($cancPr->cancelacion_motivo) }}
                        </span>
                    @endif
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:.4rem;flex-wrap:wrap;">
                        @if ($esBiobio)
                            <span class="sec-tag" style="background:#e0f2fe;color:#0369a1;font-weight:700;">LOCAL (Biobío)</span>
                        @else
                            <span class="sec-tag" style="background:#f3e8ff;color:#6b21a8;font-weight:700;">EXTERNO ({{ $reg?->codigo ?? 'EXT' }})</span>
                        @endif
                        <span style="font-weight:600;color:var(--azul-900);font-size:.85rem;">{{ $reg?->nombre ?? 'Sin región' }}</span>
                    </div>
                </td>
                <td>
                    <x-badge :estado="$estadoItem" />
                    @if ($pr?->evaluacion)
                        <span class="sec-menor" style="display:block;margin-top:.2rem;">
                            {{ \App\Support\Notas::concepto($pr->nota_practica ?? $pr->evaluacion->promedio) }}
                        </span>
                    @endif
                </td>
                <td>
                    @if ($pr?->consultorio)
                        <strong>{{ $pr->consultorio->nombre }}</strong>
                    @elseif ($p->consultorioAsignado)
                        <strong>{{ $p->consultorioAsignado->nombre }}</strong>
                    @elseif ($prefs->isNotEmpty())
                        {{ $prefs->first()->consultorio->nombre }}
                        @if ($prefs->count() > 1)
                            <span class="sec-menor">+{{ $prefs->count() - 1 }} más</span>
                        @endif
                    @else
                        <span class="sec-menor">Sin preferencias</span>
                    @endif
                </td>
                <td class="sec-num" style="font-size:.85rem;">
                    <div>Post: {{ $p->fecha_postulacion ? $p->fecha_postulacion->format('d-m-Y') : '—' }}</div>
                    @if ($pr?->fecha_inicio)
                        <div class="sec-menor">Inicio: {{ $pr->fecha_inicio->format('d-m-Y') }}</div>
                    @elseif ($p->fecha_inicio_solicitada)
                        <div class="sec-menor">Solicitado: {{ $p->fecha_inicio_solicitada->format('d-m-Y') }}</div>
                    @endif
                </td>
                <td>
                    @if ($esBiobio)
                        @if ($p->estado === 'pendiente')
                            <span class="sec-tag" style="background:#dcfce7;color:#166534;font-weight:600;">Gestión directa</span>
                        @elseif ($pr && in_array($pr->estado, ['remitida_biobio', 'nivelada_biobio']))
                            <span class="sec-tag" style="background:#fef3c7;color:#92400e;font-weight:600;">Resolución Central</span>
                        @elseif ($pr && $pr->estado === 'cerrada_historial')
                            <span class="sec-tag" style="background:#e0e7ff;color:#3730a3;font-weight:600;">Certificado emitido</span>
                        @else
                            <span class="sec-tag" style="background:#f1f5f9;color:#334155;font-weight:600;">Seguimiento local</span>
                        @endif
                    @else
                        @if ($pr && in_array($pr->estado, ['remitida_biobio', 'nivelada_biobio', 'cerrada_historial']))
                            <span class="sec-tag" style="background:#e0e7ff;color:#3730a3;font-weight:600;">Recepción Central</span>
                        @else
                            <span class="sec-tag" style="background:#f1f5f9;color:#475569;font-weight:600;">Solo lectura</span>
                        @endif
                    @endif
                </td>
                <td>
                    <x-boton tam="sm" :tipo="$esBiobio && $p->estado === 'pendiente' ? 'primario' : 'secundario'" :href="$urlVer">
                        {{ $esBiobio && $p->estado === 'pendiente' ? 'Tramitar' : 'Ver detalle' }}
                    </x-boton>
                </td>
            </tr>
        @endforeach
    </x-tabla>
@endif
@endsection
