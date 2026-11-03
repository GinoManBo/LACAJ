@extends('layouts.portal', ['migas' => [['Seguimiento regional']]])
@section('titulo', 'Seguimiento de Prácticas — ' . ($esUcoe ? 'UCOE Biobío' : 'Región ' . $region->nombre))

@section('contenido')
<div class="sec-cabecera">
    <div>
        <h1 class="titulo-pagina">Seguimiento Regional de Prácticas</h1>
        <p class="texto-suave">
            {{ $esUcoe ? 'Jefatura de UCOE — Región del Biobío' : 'Dirección Regional de ' . $region->nombre }}
            &bull; Monitoreo integral de practicantes en formación y estado de expedientes.
        </p>
    </div>
    <div class="sec-acciones">
        <x-boton tipo="secundario" :href="route('director.seguimiento.exportar', request()->query())">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right:0.25rem;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Exportar a Excel
        </x-boton>
    </div>
</div>

<div class="tarjeta" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form method="GET" action="{{ route('director.seguimiento') }}" class="filtros-grilla" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
        <div style="flex: 1; min-width: 220px;">
            <label for="q" class="campo-etiqueta" style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block;">Buscar practicante</label>
            <input type="text" id="q" name="q" value="{{ $q }}" placeholder="Nombre, apellido o RUT..." class="campo-control" style="width:100%;">
        </div>
        <div style="min-width: 200px;">
            <label for="consultorio_id" class="campo-etiqueta" style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block;">Consultorio / Unidad</label>
            <select id="consultorio_id" name="consultorio_id" class="campo-control" style="width:100%;">
                <option value="">Todos los consultorios</option>
                @foreach ($consultorios as $c)
                    <option value="{{ $c->id }}" {{ (string)$consultorioId === (string)$c->id ? 'selected' : '' }}>
                        {{ $c->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div style="min-width: 180px;">
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
            @if ($q !== '' || $consultorioId || $estado)
                <x-boton tipo="fantasma" :href="route('director.seguimiento')">Limpiar</x-boton>
            @endif
        </div>
    </form>
</div>

@if ($practicas->isEmpty())
    <x-estado-vacio titulo="No hay prácticas que coincidan" :texto="$q !== '' || $consultorioId || $estado ? 'Intente ajustar los filtros de búsqueda.' : 'No hay prácticas registradas en la región.'" />
@else
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Practicante</th>
                    <th>Consultorio / Unidad</th>
                    <th>Abogado Tutor</th>
                    <th>Periodo Formativo</th>
                    <th>Estado</th>
                    <th>Visación Dirección</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($practicas as $p)
                    @php
                        $post = $p->practicante?->postulante;
                        $estVisible = \App\Services\Practicas::estadoVisible($p);
                    @endphp
                    <tr>
                        <td>
                            <div style="font-weight:600; color:#1e293b;">
                                {{ $post ? $post->nombre_completo : '—' }}
                            </div>
                            <div style="font-size:0.75rem; color:#64748b;">
                                RUT: {{ $post?->rut ?? '—' }} &bull; {{ $post?->universidad ?? '—' }}
                            </div>
                        </td>
                        <td>{{ $p->consultorio?->nombre ?? '—' }}</td>
                        <td>{{ $p->abogado?->usuario?->nombre_completo ?? 'Sin asignar' }}</td>
                        <td style="font-size:0.85rem;">
                            {{ $p->fecha_inicio ? $p->fecha_inicio->format('d/m/Y') : '—' }}
                            <span style="color:#64748b;">al</span>
                            {{ $p->fecha_termino ? $p->fecha_termino->format('d/m/Y') : '—' }}
                        </td>
                        <td>
                            <x-badge :estado="$estVisible" />
                        </td>
                        <td>
                            @if ($p->validada_director_at)
                                <span class="badge" style="background:#dcfce7; color:#15803d; font-weight:600; padding:0.25rem 0.5rem; border-radius:4px; font-size:0.75rem;">
                                    Visada ({{ $p->validada_director_at->format('d/m/Y') }})
                                </span>
                            @else
                                <span class="badge" style="background:#f1f5f9; color:#64748b; font-weight:500; padding:0.25rem 0.5rem; border-radius:4px; font-size:0.75rem;">
                                    No visada
                                </span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            @if ($p->evaluacion)
                                <x-boton tipo="secundario" tam="sm" :href="route('director.ver', $p->id)">
                                    Ver Legajo
                                </x-boton>
                            @else
                                <span style="font-size:0.75rem; color:#94a3b8;">En curso</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $practicas->links() }}
    </div>
@endif
@endsection
