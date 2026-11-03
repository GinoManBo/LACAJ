@extends('layouts.portal', ['migas' => [['Bandeja nacional de firma']]])
@section('titulo', 'Bandeja Nacional de Firma FirmaGob — Dirección General')

@section('contenido')
<div class="sec-cabecera">
    <div>
        <h1 class="titulo-pagina">Bandeja Nacional de Firma Final (FirmaGob)</h1>
        <p class="texto-suave">
            Dirección General &bull; Sede Central Concepción.
            Suscripción con Firma Electrónica Avanzada del Estado (FirmaGob) de Certificados Oficiales para la Corte Suprema y cierre definitivo de expedientes.
        </p>
    </div>
</div>

<div class="rejilla-indicadores">
    <x-indicador :valor="$totales['pendientes_firma']" etiqueta="Pendientes de Firma FirmaGob" detalle="Listos para suscripción FEA" :critico="$totales['pendientes_firma'] > 0" />
    <x-indicador :valor="$totales['firmadas_firmagob']" etiqueta="Despachados y Archivados" detalle="CerradaHistorial con firma oficial" />
    <x-indicador :valor="$totales['total_nacional']" etiqueta="Consolidados a Nivel Nacional" detalle="Expedientes ingresados a Jurídica Central" />
</div>

<!-- Filtros de Búsqueda -->
<div class="tarjeta" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form method="GET" action="{{ route('director_general.index') }}" class="filtros-grilla" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
        <div style="flex: 1; min-width: 240px;">
            <label for="q" class="campo-etiqueta" style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block;">Buscar postulante</label>
            <input type="text" id="q" name="q" value="{{ $q }}" placeholder="Nombre, apellido o RUT..." class="campo-control" style="width:100%;">
        </div>
        <div style="min-width: 200px;">
            <label for="region_id" class="campo-etiqueta" style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block;">Región de origen</label>
            <select id="region_id" name="region_id" class="campo-control" style="width:100%;">
                <option value="">Todas las regiones</option>
                @foreach ($regiones as $reg)
                    <option value="{{ $reg->id }}" {{ (string)$regionFiltro === (string)$reg->id ? 'selected' : '' }}>
                        {{ $reg->nombre }} ({{ $reg->codigo }})
                    </option>
                @endforeach
            </select>
        </div>
        <div style="min-width: 180px;">
            <label for="estado_firma" class="campo-etiqueta" style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block;">Estado de firma</label>
            <select id="estado_firma" name="estado_firma" class="campo-control" style="width:100%;">
                <option value="pendientes" {{ $estadoFiltro === 'pendientes' ? 'selected' : '' }}>Pendientes de firma FEA</option>
                <option value="firmadas" {{ $estadoFiltro === 'firmadas' ? 'selected' : '' }}>Firmadas y despachadas</option>
                <option value="todas" {{ $estadoFiltro === 'todas' ? 'selected' : '' }}>Todos los expedientes</option>
            </select>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <x-boton type="submit">Filtrar</x-boton>
            @if ($q !== '' || $regionFiltro || $estadoFiltro !== 'pendientes')
                <x-boton tipo="fantasma" :href="route('director_general.index')">Limpiar</x-boton>
            @endif
        </div>
    </form>
</div>

@if ($expedientes->isEmpty())
    <x-estado-vacio titulo="No hay expedientes en esta bandeja" :texto="$q !== '' || $regionFiltro ? 'No se encontraron expedientes con los filtros aplicados.' : 'No hay expedientes pendientes de suscripción FirmaGob.'" />
@else
    <!-- Descarga Masiva en ZIP para Firma por Lotes -->
    <form method="POST" action="{{ route('director_general.descargar_lote') }}" id="form-zip-lote">
        @csrf
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1rem; background:#f8fafc; padding:0.75rem 1rem; border-radius:8px; border:1px solid #e2e8f0;">
            <div style="font-size:0.875rem; color:#475569;">
                Mostrando <strong>{{ $expedientes->count() }}</strong> de <strong>{{ $expedientes->total() }}</strong> expedientes.
            </div>
            @if ($estadoFiltro !== 'firmadas')
                <div>
                    <x-boton type="submit" tipo="secundario" tam="sm" style="display:inline-flex; align-items:center; gap:0.5rem;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Descargar Certificados Seleccionados en ZIP
                    </x-boton>
                </div>
            @endif
        </div>

        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        @if ($estadoFiltro !== 'firmadas')
                            <th style="width: 40px; text-align: center;">
                                <input type="checkbox" id="check-todos" title="Seleccionar todos" onclick="toggleTodos(this)">
                            </th>
                        @endif
                        <th>Practicante</th>
                        <th>Región y Unidad</th>
                        <th>Calificación Definitiva</th>
                        <th>Estado Institucional</th>
                        <th>FirmaGob (FEA)</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($expedientes as $p)
                        @php
                            $post = $p->practicante?->postulante;
                            $concepto = \App\Support\Notas::concepto($p->nota_practica);
                            $reg = $p->consultorio?->region;
                            $esFirmada = $p->firmado_director_general_at !== null || $p->estado === 'cerrada_historial';
                        @endphp
                        <tr>
                            @if ($estadoFiltro !== 'firmadas')
                                <td style="text-align: center;">
                                    @if (! $esFirmada)
                                        <input type="checkbox" name="practicas[]" value="{{ $p->id }}" class="check-item">
                                    @else
                                        <span class="sr-only">Firmado</span>
                                    @endif
                                </td>
                            @endif
                            <td>
                                <div>
                                    <a href="{{ route('director_general.ver', $p->id) }}" style="font-weight:600; color:#1e3a8a;">
                                        {{ $post ? $post->nombre_completo : 'Sin datos' }}
                                    </a>
                                </div>
                                <div style="font-size:0.75rem; color:#64748b;">
                                    RUT: {{ $post?->rut ?? '—' }} &bull; {{ $post?->universidad ?? '—' }}
                                </div>
                            </td>
                            <td>
                                <div><strong>{{ $reg?->codigo }}</strong> &bull; {{ $p->consultorio?->nombre ?? '—' }}</div>
                                <div style="font-size:0.75rem; color:#64748b;">{{ $reg?->nombre ?? 'Sin región' }}</div>
                            </td>
                            <td>
                                <span class="badge" style="background:#e0f2fe; color:#0369a1; font-weight:600; padding:0.25rem 0.5rem; border-radius:4px;">
                                    {{ $concepto }}
                                </span>
                            </td>
                            <td>
                                @if ($p->estado === 'nivelada_biobio')
                                    <span class="badge" style="background:#fef3c7; color:#b45309; font-weight:600; padding:0.25rem 0.5rem; border-radius:4px;">
                                        Nivelada de Oficio
                                    </span>
                                @elseif ($p->estado === 'cerrada_historial')
                                    <span class="badge" style="background:#dcfce7; color:#15803d; font-weight:600; padding:0.25rem 0.5rem; border-radius:4px;">
                                        Cerrada e Historiada
                                    </span>
                                @else
                                    <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:600; padding:0.25rem 0.5rem; border-radius:4px;">
                                        Remitida a Concepción
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($esFirmada)
                                    <div>
                                        <span class="badge" style="background:#dcfce7; color:#15803d; font-weight:600; padding:0.25rem 0.5rem; border-radius:4px; font-size:0.75rem;">
                                            Suscrito con FirmaGob
                                        </span>
                                    </div>
                                    <div style="font-size:0.75rem; color:#64748b; margin-top:0.25rem;">
                                        {{ $p->firmado_director_general_at ? $p->firmado_director_general_at->format('d/m/Y') : 'Firmado' }}
                                    </div>
                                @else
                                    <span class="badge" style="background:#fee2e2; color:#b91c1c; font-weight:600; padding:0.25rem 0.5rem; border-radius:4px; font-size:0.75rem;">
                                        Pendiente FEA
                                    </span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <div style="display:inline-flex; gap:0.25rem;">
                                    <x-boton tipo="secundario" tam="sm" :href="route('director_general.certificado', $p->id)" target="_blank" title="Descargar Certificado Borrador">
                                        PDF
                                    </x-boton>
                                    <x-boton tipo="primario" tam="sm" :href="route('director_general.ver', $p->id)">
                                        {{ $esFirmada ? 'Ver Detalle' : 'Firmar / Subir' }}
                                    </x-boton>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </form>

    <div style="margin-top: 1.5rem;">
        {{ $expedientes->links() }}
    </div>
@endif

<script>
function toggleTodos(master) {
    document.querySelectorAll('.check-item').forEach(chk => {
        chk.checked = master.checked;
    });
}
</script>
@endsection
