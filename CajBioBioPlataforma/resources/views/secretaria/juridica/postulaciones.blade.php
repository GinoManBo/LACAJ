@extends('layouts.portal', ['migas' => [['Supervisión Nacional de Postulaciones']]])
@section('titulo', 'Supervisión Nacional de Postulaciones')
@section('contenido')
<div class="sec-cabecera">
    <div>
        <h1 class="titulo-pagina">Supervisión Nacional de Postulaciones</h1>
        <p class="texto-suave">Etapa previa a la práctica · Secretaría Jurídica Central (Biobío - Concepción). Competencia de gestión directa para Biobío y modo solo lectura para regiones externas.</p>
    </div>
    <div>
        <x-boton tipo="secundario" :href="route('secretaria.juridica.postulaciones.exportar', request()->query())">
            @include('secretaria._icono', ['n' => 'excel']) Exportar Excel
        </x-boton>
    </div>
</div>

<div class="indicadores-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem;">
    <div class="tarjeta" style="padding:1rem;background:var(--blanco);border:1px solid var(--borde);border-radius:.5rem;">
        <div style="font-size:.82rem;color:var(--texto-suave);font-weight:600;text-transform:uppercase;">Postulaciones Nacionales</div>
        <div style="font-size:1.8rem;font-weight:700;color:var(--azul-800);margin-top:.25rem;">{{ $totales['pendientes_total'] }}</div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-top:.25rem;">Total en espera en el país</div>
    </div>
    <div class="tarjeta" style="padding:1rem;background:var(--blanco);border:1px solid var(--borde);border-radius:.5rem;">
        <div style="font-size:.82rem;color:var(--texto-suave);font-weight:600;text-transform:uppercase;">Gestión Directa (Biobío)</div>
        <div style="font-size:1.8rem;font-weight:700;color:var(--verde-800);margin-top:.25rem;">{{ $totales['locales_biobio'] }}</div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-top:.25rem;">Admisibles por Jurídica Biobío</div>
    </div>
    <div class="tarjeta" style="padding:1rem;background:var(--blanco);border:1px solid var(--borde);border-radius:.5rem;">
        <div style="font-size:.82rem;color:var(--texto-suave);font-weight:600;text-transform:uppercase;">Supervisión (Regiones Externas)</div>
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
    @if ($q !== '' || $origenFiltro || $regionFiltro)
        <x-boton tipo="fantasma" :href="route('secretaria.juridica.postulaciones')">Limpiar</x-boton>
    @endif
</form>

@if ($postulaciones->isEmpty())
    <x-estado-vacio titulo="No hay postulaciones pendientes" texto="No se encontraron postulaciones pendientes con los filtros seleccionados." />
@else
    <x-tabla :columnas="['Postulante / RUT', 'Origen Territorial', 'Región / Sede', 'Consultorio Solicitado', 'Fecha Postulación', 'Atribución Central', 'Acción']" descripcion="Supervisión nacional de postulaciones">
        @foreach ($postulaciones as $p)
            @php
                $po = $p->postulante;
                $reg = $p->region;
                $esBiobio = $reg && $reg->codigo === 'BI';
                $prefs = $p->preferencias->sortBy('orden')->values();
            @endphp
            <tr data-href="{{ route('secretaria.postulaciones.ver', $p->id) }}">
                <td>
                    <a href="{{ route('secretaria.postulaciones.ver', $p->id) }}"><strong>{{ $po->nombre }} {{ $po->apellido }}</strong></a>
                    <span class="sec-menor">RUT: {{ $po->rut }} · {{ $po->universidad }}</span>
                </td>
                <td>
                    @if ($esBiobio)
                        <span class="sec-tag" style="background:#e0f2fe;color:#0369a1;font-weight:700;">LOCAL (Biobío)</span>
                    @else
                        <span class="sec-tag" style="background:#f3e8ff;color:#6b21a8;font-weight:700;">EXTERNO ({{ $reg?->codigo ?? 'EXT' }})</span>
                    @endif
                </td>
                <td>
                    <span style="font-weight:600;color:var(--azul-900);">{{ $reg?->nombre ?? 'Sin región' }}</span>
                </td>
                <td>
                    @if ($prefs->isNotEmpty())
                        {{ $prefs->first()->consultorio->nombre }}
                        @if ($prefs->count() > 1)
                            <span class="sec-menor">+{{ $prefs->count() - 1 }} más</span>
                        @endif
                    @else
                        <span class="sec-menor">Sin preferencias</span>
                    @endif
                </td>
                <td class="sec-num">{{ $p->fecha_postulacion ? $p->fecha_postulacion->format('d-m-Y') : '—' }}</td>
                <td>
                    @if ($esBiobio)
                        <span class="sec-tag" style="background:#dcfce7;color:#166534;font-weight:600;">Gestión directa</span>
                    @else
                        <span class="sec-tag" style="background:#f1f5f9;color:#475569;font-weight:600;">Solo lectura</span>
                    @endif
                </td>
                <td>
                    @if ($esBiobio)
                        <x-boton tam="sm" :href="route('secretaria.postulaciones.ver', $p->id)">Tramitar y Asignar</x-boton>
                    @else
                        <x-boton tipo="secundario" tam="sm" :href="route('secretaria.postulaciones.ver', $p->id)">Ver (Solo lectura)</x-boton>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-tabla>
@endif
@endsection
