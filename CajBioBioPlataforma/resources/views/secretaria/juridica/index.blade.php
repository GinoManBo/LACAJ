@extends('layouts.portal', ['migas' => [['Recepción Nacional de Expedientes']]])
@section('titulo', 'Recepción Nacional Jurídica Biobío')
@section('contenido')
<div class="sec-cabecera">
    <div>
        <h1 class="titulo-pagina">Recepción Nacional de Expedientes</h1>
        <p class="texto-suave">Etapa posterior a la práctica · Secretaría Jurídica Central (Biobío - Concepción). Nivelación de notas, causal disciplinaria y despacho final de certificados.</p>
    </div>
    <div>
        <x-boton tipo="secundario" :href="route('secretaria.juridica.exportar', request()->query())">
            @include('secretaria._icono', ['n' => 'excel']) Exportar Excel
        </x-boton>
    </div>
</div>

<div class="indicadores-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem;">
    <div class="tarjeta" style="padding:1rem;background:var(--blanco);border:1px solid var(--borde);border-radius:.5rem;">
        <div style="font-size:.82rem;color:var(--texto-suave);font-weight:600;text-transform:uppercase;">Recibidos de Regiones</div>
        <div style="font-size:1.8rem;font-weight:700;color:var(--azul-800);margin-top:.25rem;">{{ $totales['recibidos'] }}</div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-top:.25rem;">En espera de resolución central</div>
    </div>
    <div class="tarjeta" style="padding:1rem;background:var(--blanco);border:1px solid var(--borde);border-radius:.5rem;">
        <div style="font-size:.82rem;color:var(--texto-suave);font-weight:600;text-transform:uppercase;">Expedientes Biobío</div>
        <div style="font-size:1.8rem;font-weight:700;color:var(--azul-900);margin-top:.25rem;">{{ $totales['locales_biobio'] }}</div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-top:.25rem;">Sede local Biobío</div>
    </div>
    <div class="tarjeta" style="padding:1rem;background:var(--blanco);border:1px solid var(--borde);border-radius:.5rem;">
        <div style="font-size:.82rem;color:var(--texto-suave);font-weight:600;text-transform:uppercase;">Expedientes Externos</div>
        <div style="font-size:1.8rem;font-weight:700;color:#6b21a8;margin-top:.25rem;">{{ $totales['externos'] }}</div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-top:.25rem;">Otras 5 regiones externas</div>
    </div>
    <div class="tarjeta" style="padding:1rem;background:var(--blanco);border:1px solid var(--borde);border-radius:.5rem;">
        <div style="font-size:.82rem;color:var(--texto-suave);font-weight:600;text-transform:uppercase;">Calificaciones Ajustadas</div>
        <div style="font-size:1.8rem;font-weight:700;color:var(--verde-800);margin-top:.25rem;">{{ $totales['niveladas'] }}</div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-top:.25rem;">Fijadas en sede central</div>
    </div>
    <div class="tarjeta" style="padding:1rem;background:var(--blanco);border:1px solid var(--borde);border-radius:.5rem;">
        <div style="font-size:.82rem;color:var(--texto-suave);font-weight:600;text-transform:uppercase;">Cerradas y Despachadas</div>
        <div style="font-size:1.8rem;font-weight:700;color:var(--azul-900);margin-top:.25rem;">{{ $totales['cerradas'] }}</div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-top:.25rem;">Con certificado y archivo</div>
    </div>
    <div class="tarjeta" style="padding:1rem;background:var(--blanco);border:1px solid var(--borde);border-radius:.5rem;">
        <div style="font-size:.82rem;color:var(--texto-suave);font-weight:600;text-transform:uppercase;">Rechazadas por Sanción</div>
        <div style="font-size:1.8rem;font-weight:700;color:var(--rojo-800);margin-top:.25rem;">{{ $totales['rechazadas'] }}</div>
        <div style="font-size:.8rem;color:var(--texto-suave);margin-top:.25rem;">Causal disciplinaria grave</div>
    </div>
</div>

<form method="GET" action="{{ route('secretaria.juridica.index') }}" class="sec-busqueda" role="search" style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.25rem;">
    <div class="campo" style="flex:1;min-width:220px;">
        <label for="q" class="sr-only">Buscar por postulante o RUT</label>
        <input id="q" name="q" type="search" value="{{ $q }}" class="campo-control" placeholder="Nombre, apellido o RUT del postulante">
    </div>
    <div class="campo" style="min-width:180px;">
        <label for="origen" class="sr-only">Origen territorial</label>
        <select id="origen" name="origen" class="campo-control">
            <option value="">Todos los orígenes</option>
            <option value="local_biobio" @selected($origenFiltro === 'local_biobio')>Solo Biobío (Local)</option>
            <option value="externo" @selected($origenFiltro === 'externo')>Solo Regiones Externas</option>
        </select>
    </div>
    <div class="campo" style="min-width:180px;">
        <label for="region_id" class="sr-only">Región de origen</label>
        <select id="region_id" name="region_id" class="campo-control">
            <option value="">Todas las regiones</option>
            @foreach ($regiones as $reg)
                <option value="{{ $reg->id }}" @selected((string)$regionFiltro === (string)$reg->id)>{{ $reg->nombre }} ({{ $reg->codigo }})</option>
            @endforeach
        </select>
    </div>
    <div class="campo" style="min-width:180px;">
        <label for="estado" class="sr-only">Estado del expediente</label>
        <select id="estado" name="estado" class="campo-control">
            <option value="">Todos los estados</option>
            <option value="remitida_biobio" @selected($estadoFiltro === 'remitida_biobio')>Remitida a Biobío</option>
            <option value="nivelada_biobio" @selected($estadoFiltro === 'nivelada_biobio')>Nivelada en Biobío</option>
            <option value="cerrada_historial" @selected($estadoFiltro === 'cerrada_historial')>Cerrada / Despachada</option>
            <option value="rechazada_sancion" @selected($estadoFiltro === 'rechazada_sancion')>Rechazada por Sanción</option>
            <option value="finalizada" @selected($estadoFiltro === 'finalizada')>Finalizada regional</option>
        </select>
    </div>
    <x-boton type="submit">@include('secretaria._icono', ['n' => 'buscar']) Filtrar</x-boton>
    @if ($q !== '' || $origenFiltro || $regionFiltro || $estadoFiltro)
        <x-boton tipo="fantasma" :href="route('secretaria.juridica.index')">Limpiar</x-boton>
    @endif
</form>

@if ($expedientes->isEmpty())
    <x-estado-vacio titulo="No hay expedientes remitidos" texto="Los expedientes que las Secretarías Regionales empaqueten y remitan a Jurídica Central aparecerán aquí." />
@else
    <x-tabla :columnas="['Postulante / RUT', 'Origen Territorial', 'Región de Origen', 'Sede / Tutor', 'Calificación', 'Estado', 'Acción']" descripcion="Expedientes remitidos a nivel nacional">
        @foreach ($expedientes as $p)
            @php
                $po = $p->practicante->postulante;
                $reg = $p->consultorio->region;
                $esBiobio = $reg && $reg->codigo === 'BI';
                $concepto = \App\Support\Notas::concepto($p->nota_practica);
                $esNivelable = \App\Support\Notas::esNivelable($p->nota_practica);
            @endphp
            <tr data-href="{{ route('secretaria.juridica.ver', $p->id) }}">
                <td>
                    <a href="{{ route('secretaria.juridica.ver', $p->id) }}"><strong>{{ $po->nombre }} {{ $po->apellido }}</strong></a>
                    <span class="sec-menor">RUT: {{ $po->rut }} · {{ $po->universidad }}</span>
                </td>
                <td>
                    @if ($esBiobio)
                        <span class="sec-tag" style="background:#e0f2fe;color:#0369a1;font-weight:700;">LOCAL (Biobío)</span>
                    @else
                        <span class="sec-tag" style="background:#f3e8ff;color:#6b21a8;font-weight:700;">EXTERNO ({{ $reg?->codigo ?? 'EXT' }})</span>
                    @endif
                </td>
                <td><span style="font-weight:600;color:var(--azul-900);">{{ $reg->nombre }}</span></td>
                <td>{{ $p->consultorio->nombre }}<span class="sec-menor">{{ $p->abogado ? $p->abogado->usuario->nombre_completo : 'Sin tutor asignado' }}</span></td>
                <td>
                    <div style="font-weight:700;font-size:1rem;color:var(--azul-950);">{{ $concepto }}</div>
                    @if ($esNivelable && in_array($p->estado, ['remitida_biobio', 'finalizada']))
                        <span class="sec-tag" style="background:var(--amarillo-100);color:var(--amarillo-900);font-size:.72rem;">SUJETO A REVISIÓN</span>
                    @elseif ($p->estado === 'nivelada_biobio')
                        <span class="sec-tag" style="background:#dcfce7;color:#166534;font-size:.72rem;">AJUSTADA EN CENTRAL</span>
                    @endif
                </td>
                <td><x-badge :estado="$p->estado" /></td>
                <td>
                    <x-boton tipo="secundario" tam="sm" :href="route('secretaria.juridica.ver', $p->id)">Revisar</x-boton>
                </td>
            </tr>
        @endforeach
    </x-tabla>
@endif
@endsection
