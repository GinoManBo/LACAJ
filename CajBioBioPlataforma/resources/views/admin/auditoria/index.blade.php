@extends('layouts.portal', ['migas' => [['Administración', route('admin.inicio')], ['Log de Auditoría']]])
@section('titulo', 'Log Inmutable de Auditoría — Administración')

@section('contenido')
@include('admin._nav')

<div class="sec-cabecera" style="margin-top:1rem;">
    <div>
        <h1 class="titulo-pagina">Log Inmutable de Auditoría</h1>
        <p class="texto-suave">
            Registro cronológico inmodificable con trazabilidad completa de operaciones, modificaciones de calificaciones, cambios de cupos y accesos (Ley N° 19.628).
        </p>
    </div>
    <div class="sec-acciones">
        <x-boton tipo="secundario" :href="route('admin.auditoria.exportar', request()->query())">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right:0.25rem;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Exportar Auditoría a Excel
        </x-boton>
    </div>
</div>

<div class="rejilla-indicadores">
    <x-indicador :valor="number_format($totales['total'], 0, ',', '.')" etiqueta="Total de Eventos Auditados" detalle="Historial completo preservado" />
    <x-indicador :valor="number_format($totales['ultimos_30d'], 0, ',', '.')" etiqueta="Operaciones en Últimos 30 Días" detalle="Actividad reciente del sistema" />
    <x-indicador :valor="$totales['usuarios_activos']" etiqueta="Usuarios Registrados en Log" detalle="Operadores con trazabilidad" />
</div>

<div class="tarjeta" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form method="GET" action="{{ route('admin.auditoria.index') }}" class="filtros-grilla" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
        <div style="flex: 1; min-width: 220px;">
            <label for="q" class="campo-etiqueta" style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block;">Búsqueda general</label>
            <input type="text" id="q" name="q" value="{{ $q }}" placeholder="Acción, usuario, RUT, IP..." class="campo-control" style="width:100%;">
        </div>
        <div style="min-width: 180px;">
            <label for="accion" class="campo-etiqueta" style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block;">Acción ejecutada</label>
            <select id="accion" name="accion" class="campo-control" style="width:100%;">
                <option value="">Todas las acciones</option>
                @foreach ($acciones as $acc)
                    <option value="{{ $acc }}" {{ $accionSeleccionada === $acc ? 'selected' : '' }}>
                        {{ $acc }}
                    </option>
                @endforeach
            </select>
        </div>
        <div style="min-width: 150px;">
            <label for="entidad" class="campo-etiqueta" style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block;">Entidad</label>
            <select id="entidad" name="entidad" class="campo-control" style="width:100%;">
                <option value="">Todas las entidades</option>
                @foreach ($entidades as $ent)
                    <option value="{{ $ent }}" {{ $entidadSeleccionada === $ent ? 'selected' : '' }}>
                        {{ $ent }}
                    </option>
                @endforeach
            </select>
        </div>
        <div style="min-width: 140px;">
            <label for="desde" class="campo-etiqueta" style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block;">Desde</label>
            <input type="date" id="desde" name="desde" value="{{ $desde }}" class="campo-control" style="width:100%;">
        </div>
        <div style="min-width: 140px;">
            <label for="hasta" class="campo-etiqueta" style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block;">Hasta</label>
            <input type="date" id="hasta" name="hasta" value="{{ $hasta }}" class="campo-control" style="width:100%;">
        </div>
        <div style="display:flex; gap:0.5rem;">
            <x-boton type="submit">Filtrar</x-boton>
            @if ($q !== '' || $accionSeleccionada || $entidadSeleccionada || $desde || $hasta)
                <x-boton tipo="fantasma" :href="route('admin.auditoria.index')">Limpiar</x-boton>
            @endif
        </div>
    </form>
</div>

@if ($registros->isEmpty())
    <x-estado-vacio titulo="No hay eventos de auditoría coincidentes" texto="Intente flexibilizar los filtros de búsqueda o el rango de fechas." />
@else
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th style="width: 130px;">Fecha / Hora</th>
                    <th>Usuario Responsable</th>
                    <th>Acción</th>
                    <th>Entidad / ID</th>
                    <th>IP</th>
                    <th>Detalle / Payload JSON</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($registros as $r)
                    @php
                        $u = $r->usuario;
                        $colorBadge = match (true) {
                            str_contains($r->accion, 'cancelar') => '#fee2e2; color:#b91c1c;',
                            str_contains($r->accion, 'modificar') => '#fef3c7; color:#b45309;',
                            str_contains($r->accion, 'validar') || str_contains($r->accion, 'firma') => '#dcfce7; color:#15803d;',
                            str_contains($r->accion, 'crear') || str_contains($r->accion, 'creado') => '#e0e7ff; color:#3730a3;',
                            default => '#f1f5f9; color:#475569;',
                        };
                    @endphp
                    <tr>
                        <td style="font-size:0.8rem; white-space:nowrap;">
                            <strong>{{ $r->created_at ? $r->created_at->format('d/m/Y') : '—' }}</strong>
                            <div style="color:#64748b;">{{ $r->created_at ? $r->created_at->format('H:i:s') : '' }}</div>
                        </td>
                        <td>
                            @if ($u)
                                <div style="font-weight:600; color:#1e293b;">{{ $u->nombre_completo }}</div>
                                <div style="font-size:0.75rem; color:#64748b;">
                                    {{ $u->rol }} &bull; RUT: {{ $u->rut ?? '—' }}
                                </div>
                            @else
                                <span style="color:#94a3b8; font-style:italic;">Sistema / Anónimo</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge" style="background:{{ $colorBadge }} font-weight:600; padding:0.25rem 0.5rem; border-radius:4px; font-size:0.8rem;">
                                {{ $r->accion }}
                            </span>
                        </td>
                        <td>
                            <span style="font-weight:600;">{{ $r->entidad }}</span>
                            @if ($r->entidad_id)
                                <span style="font-size:0.8rem; color:#64748b;">#{{ $r->entidad_id }}</span>
                            @endif
                        </td>
                        <td style="font-size:0.8rem; font-family:monospace; color:#64748b;">
                            {{ $r->ip ?? '—' }}
                        </td>
                        <td style="font-size:0.8rem;">
                            @if ($r->datos && count($r->datos) > 0)
                                <details style="cursor:pointer;">
                                    <summary style="color:#0284c7; font-weight:600; outline:none;">
                                        Ver payload ({{ count($r->datos) }} {{ count($r->datos) === 1 ? 'campo' : 'campos' }})
                                    </summary>
                                    <pre style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:4px; padding:0.5rem; margin-top:0.35rem; font-size:0.75rem; max-width:400px; max-height:200px; overflow:auto; white-space:pre-wrap; word-break:break-all;">{{ json_encode($r->datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            @else
                                <span style="color:#cbd5e1;">Sin datos adicionales</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $registros->links() }}
    </div>
@endif
@endsection
