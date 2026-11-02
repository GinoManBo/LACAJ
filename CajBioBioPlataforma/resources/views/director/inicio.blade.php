@extends('layouts.portal', ['migas' => [['Validación de evaluaciones']]])
@section('titulo', 'Validación de Evaluaciones — ' . ($esUcoe ? 'Jefatura UCOE' : 'Dirección Regional ' . $region->nombre))

@section('contenido')
<div class="sec-cabecera">
    <div>
        <h1 class="titulo-pagina">Validación de Evaluaciones Regionales</h1>
        <p class="texto-suave">
            {{ $esUcoe ? 'Jefatura de UCOE — Región del Biobío' : 'Dirección Regional de ' . $region->nombre }}
            &bull; Visación formal de propuestas evaluativas de 1ª y 2ª instancia antes de remitir a Jurídica Central.
        </p>
    </div>
    @if ($esUcoe)
        <div class="sec-acciones">
            <x-boton tipo="secundario" :href="route('director.colegiada')">
                Revisión Colegiada UCOE
            </x-boton>
        </div>
    @endif
</div>

<div class="rejilla-indicadores">
    <x-indicador :valor="$totales['pendientes']" etiqueta="Pendientes de Visación" detalle="Requieren autorización directiva" :critico="$totales['pendientes'] > 0" />
    <x-indicador :valor="$totales['validadas']" etiqueta="Visadas Formalmente" detalle="Habilitadas para remisión a Concepción" />
    <x-indicador :valor="$totales['activas_total']" etiqueta="Prácticas en Curso" detalle="Dotación regional activa" />
</div>

<div class="tarjeta" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
    <form method="GET" action="{{ route('director.inicio') }}" class="filtros-grilla" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
        <div style="flex: 1; min-width: 240px;">
            <label for="q" class="campo-etiqueta" style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block;">Buscar postulante</label>
            <input type="text" id="q" name="q" value="{{ $q }}" placeholder="Nombre, apellido o RUT..." class="campo-control" style="width:100%;">
        </div>
        <div style="min-width: 200px;">
            <label for="estado_validacion" class="campo-etiqueta" style="font-size:0.85rem; font-weight:600; margin-bottom:0.25rem; display:block;">Estado de visación</label>
            <select id="estado_validacion" name="estado_validacion" class="campo-control" style="width:100%;">
                <option value="pendientes" {{ $estadoValidacion === 'pendientes' ? 'selected' : '' }}>Pendientes de visación</option>
                <option value="validadas" {{ $estadoValidacion === 'validadas' ? 'selected' : '' }}>Visadas por Dirección</option>
                <option value="todas" {{ $estadoValidacion === 'todas' ? 'selected' : '' }}>Todas las evaluadas</option>
            </select>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <x-boton type="submit">Filtrar</x-boton>
            @if ($q !== '' || $estadoValidacion !== 'pendientes')
                <x-boton tipo="fantasma" :href="route('director.inicio')">Limpiar</x-boton>
            @endif
        </div>
    </form>
</div>

@if ($practicas->isEmpty())
    <x-estado-vacio titulo="No hay expedientes en esta bandeja" :texto="$q !== '' ? 'No se encontraron expedientes con los filtros aplicados.' : 'No existen prácticas evaluadas pendientes en la región.'" />
@else
    <form method="POST" action="{{ route('director.validar_lote') }}" id="form-lote" onsubmit="return confirm('¿Confirma la visación formal directiva para los expedientes seleccionados?');">
        @csrf
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
            <div style="font-size:0.875rem; color:#475569;">
                Mostrando <strong>{{ $practicas->count() }}</strong> de <strong>{{ $practicas->total() }}</strong> expedientes evaluados.
            </div>
            @if ($estadoValidacion !== 'validadas')
                <div>
                    <x-boton type="submit" tipo="primario" tam="sm" id="btn-validar-lote" style="display:inline-flex; align-items:center; gap:0.5rem;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Validar Seleccionados en Lote
                    </x-boton>
                </div>
            @endif
        </div>

        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        @if ($estadoValidacion !== 'validadas')
                            <th style="width: 40px; text-align: center;">
                                <input type="checkbox" id="check-todos" title="Seleccionar todos" onclick="toggleTodos(this)">
                            </th>
                        @endif
                        <th>Practicante</th>
                        <th>Consultorio / Unidad</th>
                        <th>Abogado Evaluador</th>
                        <th>Propuesta Evaluativa</th>
                        <th>Estado Visación</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($practicas as $p)
                        @php
                            $post = $p->practicante?->postulante;
                            $concepto = \App\Support\Notas::concepto($p->nota_practica);
                            $esVisada = $p->validada_director_at !== null;
                        @endphp
                        <tr>
                            @if ($estadoValidacion !== 'validadas')
                                <td style="text-align: center;">
                                    @if (! $esVisada)
                                        <input type="checkbox" name="practicas[]" value="{{ $p->id }}" class="check-item">
                                    @else
                                        <span class="sr-only">Visada</span>
                                    @endif
                                </td>
                            @endif
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
                            <td>
                                <span style="font-weight:500;">{{ $p->consultorio?->nombre ?? '—' }}</span>
                            </td>
                            <td>
                                <div>{{ $p->abogado?->usuario?->nombre_completo ?? 'Sin asignar' }}</div>
                                @if ($p->calificacionSegundaInstancia)
                                    <div style="font-size:0.75rem; color:#0284c7; font-weight:600;">Con 2ª Instancia (Corte)</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge" style="background:#e0f2fe; color:#0369a1; font-weight:600; padding:0.25rem 0.5rem; border-radius:4px;">
                                    {{ $concepto }}
                                </span>
                            </td>
                            <td>
                                @if ($esVisada)
                                    <span class="badge" style="background:#dcfce7; color:#15803d; font-weight:600; padding:0.25rem 0.5rem; border-radius:4px;">
                                        Visada el {{ $p->validada_director_at->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="badge" style="background:#fef3c7; color:#b45309; font-weight:600; padding:0.25rem 0.5rem; border-radius:4px;">
                                        Pendiente de visación
                                    </span>
                                @endif
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

    <div style="margin-top: 1.5rem;">
        {{ $practicas->links() }}
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
