@extends('layouts.portal', ['migas' => [
    ['Validación de evaluaciones', route('director.inicio')],
    ['Revisión colegiada UCOE']
]])

@section('titulo', 'Revisión Colegiada — Jefatura de UCOE Biobío')

@section('contenido')
<div class="sec-cabecera">
    <div>
        <h1 class="titulo-pagina">Distribución de Revisión Colegiada</h1>
        <p class="texto-suave">
            Jefatura de UCOE &bull; Región del Biobío.
            Asignación equitativa y control de expedientes entre el equipo revisor colegiado de UCOE.
        </p>
    </div>
    <div class="sec-acciones">
        <x-boton tipo="secundario" :href="route('director.inicio')">
            Volver a Bandeja General
        </x-boton>
    </div>
</div>

<!-- Filtros de Búsqueda -->
<div class="tarjeta" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form method="GET" action="{{ route('director.colegiada') }}" class="filtros-grilla" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
        <div style="flex: 1; min-width: 240px;">
            <label for="q" class="campo-etiqueta" style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block;">Buscar postulante</label>
            <input type="text" id="q" name="q" value="{{ $q }}" placeholder="Nombre, apellido o RUT..." class="campo-control" style="width:100%;">
        </div>
        <div style="min-width: 240px;">
            <label for="revisor" class="campo-etiqueta" style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block;">Filtrar por Revisor</label>
            <select id="revisor" name="revisor" class="campo-control" style="width:100%;">
                <option value="todos" {{ $revisorFiltro === 'todos' || ! $revisorFiltro ? 'selected' : '' }}>Todos los revisores</option>
                <option value="sin_asignar" {{ $revisorFiltro === 'sin_asignar' ? 'selected' : '' }}>Sin revisor asignado</option>
                @foreach ($revisores as $k => $v)
                    <option value="{{ $k }}" {{ $revisorFiltro === $k || str_contains((string)$revisorFiltro, $k) ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <x-boton type="submit">Filtrar</x-boton>
            @if ($q !== '' || ($revisorFiltro && $revisorFiltro !== 'todos'))
                <x-boton tipo="fantasma" :href="route('director.colegiada')">Limpiar</x-boton>
            @endif
        </div>
    </form>
</div>

@if ($expedientes->isEmpty())
    <x-estado-vacio titulo="No hay expedientes para mostrar" texto="No se encontraron expedientes con los criterios de búsqueda seleccionados." />
@else
    <!-- Formulario de Asignación Masiva por Lote -->
    <form method="POST" action="{{ route('director.colegiada.lote') }}" id="form-colegiada-lote" onsubmit="return confirm('¿Confirma la asignación del lote de expedientes al revisor seleccionado?');">
        @csrf
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1rem; background:#f8fafc; padding:0.75rem 1rem; border-radius:8px; border:1px solid #e2e8f0;">
            <div style="font-size:0.875rem; color:#475569;">
                Mostrando <strong>{{ $expedientes->count() }}</strong> de <strong>{{ $expedientes->total() }}</strong> expedientes.
            </div>
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <label for="revisor-lote" style="font-size:0.85rem; font-weight:600; color:#334155; margin:0;">Asignar selección a:</label>
                <select name="revisor" id="revisor-lote" class="campo-control" style="font-size:0.85rem; padding:0.35rem 0.65rem;" required>
                    <option value="">Seleccione revisor...</option>
                    @foreach ($revisores as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                    @endforeach
                </select>
                <x-boton type="submit" tipo="primario" tam="sm">
                    Asignar en Lote
                </x-boton>
            </div>
        </div>

        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">
                            <input type="checkbox" id="check-todos" title="Seleccionar todos" onclick="toggleTodos(this)">
                        </th>
                        <th>Practicante</th>
                        <th>Consultorio</th>
                        <th>Propuesta Evaluativa</th>
                        <th>Revisor Asignado</th>
                        <th>Reasignar Individual</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($expedientes as $p)
                        @php
                            $post = $p->practicante?->postulante;
                            $concepto = \App\Support\Notas::concepto($p->nota_practica);
                            $revisorActual = $p->revisor_colegiado_nombre;
                        @endphp
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" name="practicas[]" value="{{ $p->id }}" class="check-item">
                            </td>
                            <td>
                                <div>
                                    <a href="{{ route('director.ver', $p->id) }}" style="font-weight:600; color:#1e3a8a;">
                                        {{ $post ? $post->nombre_completo : 'Sin datos' }}
                                    </a>
                                </div>
                                <div style="font-size:0.75rem; color:#64748b;">
                                    RUT: {{ $post?->rut ?? '—' }} &bull; {{ $post?->universidad ?? '—' }}
                                </div>
                            </td>
                            <td>{{ $p->consultorio?->nombre ?? '—' }}</td>
                            <td>
                                <span class="badge" style="background:#e0f2fe; color:#0369a1; font-weight:600; padding:0.25rem 0.5rem; border-radius:4px;">
                                    {{ $concepto }}
                                </span>
                            </td>
                            <td>
                                @if ($revisorActual)
                                    <span class="badge" style="background:#e0e7ff; color:#3730a3; font-weight:600; padding:0.25rem 0.5rem; border-radius:4px;">
                                        {{ $revisorActual }}
                                    </span>
                                @else
                                    <span class="badge" style="background:#fee2e2; color:#b91c1c; font-weight:600; padding:0.25rem 0.5rem; border-radius:4px;">
                                        Sin Asignar
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div style="display:inline-flex; align-items:center; gap:0.25rem;">
                                    <select onchange="asignarIndividual({{ $p->id }}, this.value)" class="campo-control" style="font-size:0.8rem; padding:0.2rem 0.5rem;">
                                        <option value="">Cambiar a...</option>
                                        @foreach ($revisores as $k => $v)
                                            <option value="{{ $k }}" {{ $revisorActual === $k ? 'selected' : '' }}>
                                                {{ $k }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </td>
                            <td style="text-align: right;">
                                <x-boton tipo="secundario" tam="sm" :href="route('director.ver', $p->id)">
                                    Examinar Legajo
                                </x-boton>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </form>

    <!-- Form oculto para asignaciones individuales vía JavaScript -->
    <form id="form-individual" method="POST" action="" style="display:none;">
        @csrf
        <input type="hidden" name="revisor" id="input-individual-revisor">
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

function asignarIndividual(practicaId, revisor) {
    if (!revisor) return;
    const form = document.getElementById('form-individual');
    form.action = "{{ url('director/colegiada') }}/" + practicaId + "/asignar";
    document.getElementById('input-individual-revisor').value = revisor;
    form.submit();
}
</script>
@endsection
