@extends('layouts.portal', ['migas' => [['Administración', route('admin.inicio')], ['Postulantes']]])
@section('titulo', 'Gestión de postulantes y prácticas')
@section('contenido')
@include('admin._nav')

<div class="sec-cabecera" style="margin-top:1rem;">
    <div>
        <h1 class="titulo-pagina">Gestión Transversal de Postulantes</h1>
        <p class="texto-suave" style="margin:.25rem 0 0">
            Control y corrección operativa a nivel nacional &bull; {{ $practicas->total() }} {{ $practicas->total() === 1 ? 'práctica registrada' : 'prácticas registradas' }}.
        </p>
    </div>
    <div class="sec-acciones">
        <x-boton tipo="secundario" :href="route('admin.practicantes.exportar', request()->query())">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right:0.25rem;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Exportar a Excel
        </x-boton>
    </div>
</div>

<div class="tarjeta" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form method="GET" action="{{ route('admin.practicantes.index') }}" class="filtros-grilla" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;" role="search">
        <div style="flex:1; min-width:220px;">
            <label for="q" class="campo-etiqueta" style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block;">Búsqueda</label>
            <input id="q" name="q" type="search" value="{{ $q }}" class="campo-control" style="width:100%;" placeholder="Nombre, apellido, RUT, universidad o tutor...">
        </div>
        <div style="min-width:180px;">
            <label for="region_id" class="campo-etiqueta" style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block;">Región</label>
            <select id="region_id" name="region_id" class="campo-control" style="width:100%;">
                <option value="">Todas las regiones</option>
                @foreach ($regiones as $reg)
                    <option value="{{ $reg->id }}" {{ (string)$regionId === (string)$reg->id ? 'selected' : '' }}>
                        {{ $reg->nombre }} ({{ $reg->codigo }})
                    </option>
                @endforeach
            </select>
        </div>
        <div style="min-width:180px;">
            <label for="consultorio_id" class="campo-etiqueta" style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block;">Consultorio</label>
            <select id="consultorio_id" name="consultorio_id" class="campo-control" style="width:100%;">
                <option value="">Todos los consultorios</option>
                @foreach ($consultorios as $c)
                    <option value="{{ $c->id }}" {{ (string)$consultorioId === (string)$c->id ? 'selected' : '' }}>
                        {{ $c->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div style="min-width:150px;">
            <label for="estado" class="campo-etiqueta" style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block;">Estado</label>
            <select id="estado" name="estado" class="campo-control" style="width:100%;">
                <option value="">Todos los estados</option>
                <option value="por_iniciar" {{ $estado === 'por_iniciar' ? 'selected' : '' }}>Por iniciar</option>
                <option value="activa" {{ $estado === 'activa' ? 'selected' : '' }}>En curso (Activa)</option>
                <option value="finalizada" {{ $estado === 'finalizada' ? 'selected' : '' }}>Finalizada</option>
                <option value="remitida_biobio" {{ $estado === 'remitida_biobio' ? 'selected' : '' }}>Remitida a Jurídica</option>
                <option value="nivelada_biobio" {{ $estado === 'nivelada_biobio' ? 'selected' : '' }}>Nivelada de Oficio</option>
                <option value="rechazada_sancion" {{ $estado === 'rechazada_sancion' ? 'selected' : '' }}>Rechazada por sanción</option>
                <option value="cerrada_historial" {{ $estado === 'cerrada_historial' ? 'selected' : '' }}>Cerrada con FirmaGob</option>
                <option value="cancelada" {{ $estado === 'cancelada' ? 'selected' : '' }}>Cancelada</option>
            </select>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <x-boton type="submit">Filtrar</x-boton>
            @if ($q !== '' || $regionId || $consultorioId || $estado)
                <x-boton tipo="fantasma" :href="route('admin.practicantes.index')">Limpiar</x-boton>
            @endif
        </div>
    </form>
</div>

@if ($practicas->isEmpty())
    <x-estado-vacio titulo="No hay postulantes que coincidan" texto="Pruebe ajustando los filtros de búsqueda o la sede seleccionada.">
        @if ($q !== '' || $regionId || $consultorioId || $estado)
            <x-boton tipo="secundario" :href="route('admin.practicantes.index')">Ver todos</x-boton>
        @endif
    </x-estado-vacio>
@else
    <x-tabla :columnas="['Postulante', 'Universidad', 'Sede y Región', 'Abogado tutor', 'Periodo', 'Estado / Calificación', 'Acción']" descripcion="Prácticas registradas en todo el país">
        @foreach ($practicas as $p)
            @php
                $po = $p->practicante?->postulante;
                $concepto = $p->nota_practica !== null ? \App\Support\Notas::concepto($p->nota_practica) : null;
            @endphp
            <tr data-href="{{ route('admin.practicantes.ver', $p) }}">
                <td>
                    <a href="{{ route('admin.practicantes.ver', $p) }}" class="enlace-fila">
                        <strong>{{ $po ? $po->nombre.' '.$po->apellido : '—' }}</strong>
                    </a>
                    <div class="texto-suave dato-menor">
                        RUT: {{ $po?->rut ?? '—' }} &bull; {{ $po?->email ?? '—' }}
                    </div>
                </td>
                <td>{{ $po?->universidad ?? '—' }}</td>
                <td>
                    <div>{{ $p->consultorio?->nombre ?? '—' }}</div>
                    <div class="texto-suave dato-menor">{{ $p->consultorio?->region?->nombre ?? '—' }}</div>
                </td>
                <td>{{ $p->abogado?->usuario ? $p->abogado->usuario->nombre.' '.$p->abogado->usuario->apellido : 'Sin tutor' }}</td>
                <td class="nowrap" style="font-size:0.85rem;">
                    {{ $p->fecha_inicio ? $p->fecha_inicio->format('d/m/Y') : '—' }}
                    <span style="color:#64748b;">al</span>
                    {{ $p->fecha_termino ? $p->fecha_termino->format('d/m/Y') : '—' }}
                </td>
                <td>
                    <div><x-badge :estado="$p->estado" /></div>
                    @if ($concepto)
                        <div style="margin-top:0.25rem;">
                            <span class="badge" style="background:#e0f2fe; color:#0369a1; font-weight:600; font-size:0.75rem;">
                                {{ $concepto }}
                            </span>
                        </div>
                    @endif
                </td>
                <td>
                    <x-boton tipo="secundario" tam="sm" :href="route('admin.practicantes.ver', $p)">
                        Editar Ficha
                    </x-boton>
                </td>
            </tr>
        @endforeach
    </x-tabla>
    <div class="paginacion">{{ $practicas->links() }}</div>
@endif
@endsection
