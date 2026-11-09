@extends('layouts.portal', ['migas' => [
    ['Validación de evaluaciones', route('director.inicio')],
    ['Expediente: ' . $postulante->nombre_completo]
]])

@section('titulo', 'Expediente Evaluativo — ' . $postulante->nombre_completo)

@section('contenido')
<div class="sec-cabecera">
    <div>
        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
            <a href="{{ route('director.inicio') }}" class="texto-suave" style="display:inline-flex; align-items:center; gap:0.25rem; font-size:0.875rem;">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver a la bandeja
            </a>
        </div>
        <h1 class="titulo-pagina">{{ $postulante->nombre_completo }}</h1>
        <p class="texto-suave">
            RUT {{ $postulante->rut }} &bull; Consultorio {{ $consultorio->nombre }} ({{ $consultorio->region?->nombre }})
        </p>
    </div>
    <div class="sec-acciones">
        @if ($practica->validada_director_at)
            <span class="badge" style="background:#dcfce7; color:#15803d; font-weight:600; padding:0.5rem 0.75rem; border-radius:6px; font-size:0.875rem;">
                Expediente Visado por Dirección
            </span>
        @else
            <span class="badge" style="background:#fef3c7; color:#b45309; font-weight:600; padding:0.5rem 0.75rem; border-radius:6px; font-size:0.875rem;">
                Pendiente de Visación Formal
            </span>
        @endif
    </div>
</div>

<div class="sec-dos" style="margin-bottom: 1.5rem;">
    <!-- Antecedentes Generales -->
    <x-tarjeta titulo="Antecedentes del Postulante y de la Práctica">
        <dl class="datos">
            <div><dt>RUT</dt><dd>{{ $postulante->rut }}</dd></div>
            <div><dt>Universidad</dt><dd>{{ $postulante->universidad }}</dd></div>
            <div><dt>Correo electrónico</dt><dd>{{ $postulante->email }}</dd></div>
            <div><dt>Teléfono</dt><dd>{{ $postulante->telefono ?? '—' }}</dd></div>
            <div class="ancho-completo"><dt>Unidad operativa / Sede</dt><dd>{{ $consultorio->nombre }}</dd></div>
            <div class="ancho-completo"><dt>Abogado Evaluador</dt><dd>{{ $practica->abogado?->usuario?->nombre_completo ?? 'Sin asignar' }}</dd></div>
            <div><dt>Fecha Inicio</dt><dd>{{ $practica->fecha_inicio ? $practica->fecha_inicio->format('d-m-Y') : '—' }}</dd></div>
            <div><dt>Fecha Término</dt><dd>{{ $practica->fecha_termino ? $practica->fecha_termino->format('d-m-Y') : '—' }}</dd></div>
        </dl>
    </x-tarjeta>

    <!-- Informe de Práctica del SAJ -->
    <x-tarjeta titulo="Informe de Práctica Institucional (SAJ)">
        @if ($informeSaj)
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:1.25rem; display:flex; flex-direction:column; gap:0.75rem;">
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <div style="background:#fee2e2; color:#ef4444; padding:0.5rem; border-radius:6px;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <div style="font-weight:600; color:#1e293b;">{{ $informeSaj->nombre_original }}</div>
                        <div style="font-size:0.75rem; color:#64748b;">
                            Cargado el {{ $informeSaj->created_at->format('d/m/Y H:i') }} &bull; {{ number_format($informeSaj->tamano_bytes / 1024, 1) }} KB
                        </div>
                    </div>
                </div>
                <div>
                    <x-boton tipo="secundario" tam="sm" :href="route('documentos.descargar', $informeSaj->id)" target="_blank">
                        Descargar Informe de Práctica PDF
                    </x-boton>
                </div>
            </div>
        @else
            <div class="alerta alerta-aviso" style="margin:0;">
                No se encuentra adjunto un informe de práctica en formato PDF proveniente del SAJ para este expediente.
            </div>
        @endif
    </x-tarjeta>
</div>

<!-- Rúbrica y Evaluación de Primera Instancia -->
<div class="tarjeta" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <h3 style="font-size:1.1rem; font-weight:700; color:#1e3a8a; margin-bottom:1rem;">
        Evaluación de Primera Instancia (Abogado Jefe de Consultorio)
    </h3>

    <div class="tabla-contenedor" style="margin-bottom: 1rem;">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Criterio Evaluado</th>
                    <th style="text-align: right;">Concepto Evaluativo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($criterios as $c)
                    <tr>
                        <td style="font-weight:500;">{{ $c['etiqueta'] }}</td>
                        <td style="text-align: right;">
                            <span class="badge" style="background:#f1f5f9; color:#334155; font-weight:600; padding:0.25rem 0.5rem; border-radius:4px;">
                                {{ $c['concepto'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc; font-size:1rem;">
                    <th style="font-weight:700; color:#0f172a;">Propuesta de Calificación Final</th>
                    <th style="text-align: right;">
                        <span class="badge" style="background:#dbeafe; color:#1e40af; font-size:1rem; padding:0.35rem 0.75rem; border-radius:6px; font-weight:700;">
                            {{ $conceptoFinal }}
                        </span>
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>

    @if ($evaluacion->observaciones)
        <div style="background:#f8fafc; border-left:4px solid #3b82f6; padding:0.75rem 1rem; border-radius:0 6px 6px 0;">
            <div style="font-weight:600; font-size:0.85rem; color:#1e40af; margin-bottom:0.25rem;">Observaciones del Abogado Evaluador:</div>
            <div style="font-size:0.9rem; color:#334155; white-space:pre-line;">{{ $evaluacion->observaciones }}</div>
        </div>
    @endif
</div>

<!-- Segunda Instancia (si existiera) -->
@if ($califSegunda)
    <div class="tarjeta" style="margin-bottom: 1.5rem; padding: 1.25rem; border-left:4px solid #0284c7;">
        <h3 style="font-size:1.1rem; font-weight:700; color:#0369a1; margin-bottom:0.75rem;">
            Resolución de Segunda Instancia (Corte de Apelaciones)
        </h3>
        <p style="font-size:0.9rem; color:#475569; margin-bottom:0.75rem;">
            Este expediente fue sometido a evaluación de segunda instancia por el Abogado Jefe de Corte.
        </p>
        <dl class="datos">
            <div><dt>Concepto 2ª Instancia</dt><dd><strong>{{ \App\Support\Notas::concepto($califSegunda->nota) }}</strong></dd></div>
            <div><dt>Fecha Resolución</dt><dd>{{ $califSegunda->created_at->format('d/m/Y') }}</dd></div>
            <div class="ancho-completo"><dt>Fundamentación</dt><dd>{{ $califSegunda->observaciones ?? 'Sin observaciones adicionales.' }}</dd></div>
        </dl>
    </div>
@endif

<!-- Panel de Revisión, Calificación y Visación Directiva -->
<div class="tarjeta" style="padding: 1.5rem; border: 2px solid {{ $practica->validada_director_at ? '#86efac' : '#3b82f6' }};">
    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem;">
        <div style="background:{{ $practica->validada_director_at ? '#dcfce7' : '#dbeafe' }}; color:{{ $practica->validada_director_at ? '#15803d' : '#1d4ed8' }}; padding:0.6rem; border-radius:8px;">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        </div>
        <div>
            <h3 style="font-size:1.15rem; font-weight:700; color:#0f172a; margin:0;">
                Revisión, Calificación y Visación Directiva Formal
            </h3>
            <p style="font-size:0.85rem; color:#64748b; margin:0;">
                {{ $esUcoe ? 'Jefatura y Revisores de la Unidad de Consultorios y Oficinas Especializadas (UCOE)' : 'Dirección Regional' }}
                &bull; Potestad reglamentaria de revisar, ratificar o modificar la calificación final y autorizar la remisión a Jurídica Central en Concepción.
            </p>
        </div>
    </div>

    @if ($practica->validada_director_at)
        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:1rem; margin-bottom:1.25rem;">
            <div style="display:flex; align-items:center; gap:0.5rem; color:#166534; font-weight:700; font-size:0.95rem; margin-bottom:0.25rem;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Expediente actualmente visado con calificación «{{ $conceptoFinal }}»
            </div>
            <p style="font-size:0.85rem; color:#14532d; margin:0;">
                Visado por <strong>{{ $practica->validadaDirectorPor?->nombre_completo ?? 'Dirección Regional' }}</strong> el {{ $practica->validada_director_at->format('d/m/Y \a \l\a\s H:i') }} hrs.
                @if ($puedeModificar)
                    <span style="color:#047857;">(Puede modificar la calificación o actualizar observaciones mientras el expediente no haya sido remitido a Concepción).</span>
                @else
                    <span style="color:#64748b;">(Expediente remitido a Jurídica Central en modo solo lectura).</span>
                @endif
            </p>
            @if ($practica->observaciones_director)
                <div style="font-size:0.85rem; color:#166534; background:#dcfce7; padding:0.4rem 0.65rem; border-radius:4px; margin-top:0.5rem;">
                    <strong>Observaciones registradas:</strong> {{ $practica->observaciones_director }}
                </div>
            @endif
        </div>
    @endif

    @if ($puedeModificar)
        <form method="POST" action="{{ route('director.validar', $practica->id) }}" id="form-visacion-ucoe" onsubmit="return confirm('¿Confirma la resolución y calificación directiva para este expediente?');">
            @csrf
            
            <!-- Bloque de Edición y Ajuste por Criterio / Factor -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:1.25rem; margin-bottom:1.25rem;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:0.5rem; margin-bottom:0.85rem;">
                    <div>
                        <label class="campo-etiqueta" style="font-weight:700; font-size:0.95rem; margin:0; color:#1e293b; display:block;">
                            Revisión y Calificación por Criterio (7 Factores Reglamentarios):
                        </label>
                        <p style="font-size:0.8rem; color:#64748b; margin:0.15rem 0 0;">
                            {{ $esUcoe ? 'UCOE' : 'Dirección Regional' }} posee la atribución reglamentaria de revisar, ratificar o ajustar individualmente cada factor evaluativo.
                        </p>
                    </div>
                    <div id="aviso-criterio-modificado" style="background:#fef3c7; border:1px solid #fde68a; color:#92400e; padding:0.25rem 0.6rem; border-radius:6px; font-size:0.75rem; font-weight:600;" hidden>
                        ⚠️ Criterios modificados respecto a la propuesta original
                    </div>
                </div>

                <div class="tabla-contenedor" style="background:#ffffff; border-radius:6px; border:1px solid #e2e8f0; margin-bottom:0.75rem;">
                    <table class="tabla" style="margin:0;">
                        <thead>
                            <tr style="background:#f8fafc;">
                                <th style="width:40%;">Factor / Criterio Reglamentario</th>
                                <th style="width:24%; text-align:center;">Propuesta 1ª Instancia</th>
                                <th style="width:36%;">Calificación {{ $esUcoe ? 'UCOE' : 'Dirección' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($criterios as $c)
                                @php
                                    $cCampo = $c['campo'] ?? '';
                                    $valOriginal = $c['clave'] ?? 'BUENA';
                                    $valSel = old('criterios.'.$cCampo, $valOriginal);
                                @endphp
                                <tr data-fila-criterio="{{ $cCampo }}">
                                    <td style="font-weight:600; font-size:0.88rem; color:#1e293b;">
                                        {{ $c['etiqueta'] }}
                                    </td>
                                    <td style="text-align:center;">
                                        <span class="badge" style="background:#f1f5f9; color:#475569; font-weight:600; font-size:0.8rem;">
                                            {{ $c['concepto'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <select name="criterios[{{ $cCampo }}]" class="campo-control select-criterio-ucoe" style="width:100%; padding:0.35rem 0.5rem; font-size:0.86rem;" data-criterio-campo="{{ $cCampo }}" data-valor-original="{{ $valOriginal }}">
                                            @foreach ($opcionesNotas as $cClave => $opc)
                                                <option value="{{ $cClave }}" @selected($valSel === $cClave) data-valor="{{ $opc['valor'] }}">
                                                    {{ $opc['nombre'] }} ({{ $opc['aprobatorio'] ? 'Aprobatorio' : 'Reprobatorio' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background:#f1f5f9; font-weight:700;">
                                <td colspan="2" style="font-size:0.9rem; color:#0f172a;">
                                    Promedio Calculado de Factores Reglamentarios:
                                </td>
                                <td style="font-size:0.9rem;">
                                    <span id="promedio-criterio-concepto" class="badge" style="background:#dbeafe; color:#1e40af; font-size:0.95rem; font-weight:700; padding:0.25rem 0.6rem; border-radius:6px;">
                                        {{ $conceptoFinal }}
                                    </span>
                                    <span id="promedio-criterio-valor" style="color:#64748b; font-size:0.8rem; font-weight:500; margin-left:0.35rem;"></span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Bloque de Calificación Final en Palabras -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:1.25rem; margin-bottom:1.25rem;">
                <label class="campo-etiqueta" style="font-weight:700; font-size:0.95rem; margin-bottom:0.35rem; display:block; color:#1e293b;">
                    Calificación Final Directiva (Escala Institucional en Palabras):
                </label>
                <p style="font-size:0.8rem; color:#64748b; margin:0 0 0.75rem;">
                    Se actualiza automáticamente según el promedio ponderado de los 7 criterios. Puede seleccionar una calificación directiva en palabras si corresponde.
                </p>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:0.75rem;">
                    @foreach ($opcionesNotas as $clave => $opc)
                        @php
                            $estaSeleccionada = old('concepto', $claveActual) === $clave;
                            $colorTexto = match ($clave) {
                                'SOBRESALIENTE' => '#1e40af',
                                'BUENA' => '#15803d',
                                'REGULAR' => '#b45309',
                                default => '#b91c1c',
                            };
                        @endphp
                        <label style="border:2px solid #e2e8f0; background:#ffffff; border-radius:8px; padding:0.75rem 1rem; cursor:pointer; display:flex; align-items:center; gap:0.5rem;">
                            <input type="radio" name="concepto" value="{{ $clave }}" {{ $estaSeleccionada ? 'checked' : '' }} style="accent-color:{{ $colorTexto }};">
                            <div>
                                <div style="font-weight:700; font-size:0.95rem; color:{{ $colorTexto }};">
                                    {{ $opc['nombre'] }}
                                </div>
                                <div style="font-size:0.75rem; color:#64748b;">
                                    {{ $opc['aprobatorio'] ? 'Aprobatorio' : 'Reprobatorio' }}
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label for="observaciones_director" class="campo-etiqueta" style="font-weight:600; font-size:0.875rem; margin-bottom:0.35rem; display:block;">
                    Observaciones o Justificación de la Dirección / UCOE:
                </label>
                <textarea id="observaciones_director" name="observaciones_director" rows="3" class="campo-control" style="width:100%;" placeholder="Ingrese comentarios institucionales, fundamentación en caso de modificar criterios o la nota propuesta, o constancia de revisión...">{{ old('observaciones_director', $practica->observaciones_director) }}</textarea>
                <span style="font-size:0.75rem; color:#64748b;">Obligatorio si modifica los criterios o la calificación respecto a la propuesta original del Abogado Jefe. Toda modificación quedará asentada en el log inmutable de auditoría.</span>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                <p style="font-size:0.85rem; color:#64748b; margin:0; max-width:600px;">
                    Al guardar, las calificaciones por criterio y la nota final quedarán visadas institucionalmente y registradas en auditoría. El expediente quedará habilitado para su remisión formal a Jurídica Central Concepción.
                </p>
                <x-boton type="submit" tipo="primario" style="background:#16a34a; border-color:#16a34a;">
                    {{ $practica->validada_director_at ? 'Actualizar Calificación, Criterios y Visación' : 'Validar / Fijar Calificación y Visar' }}
                </x-boton>
            </div>
        </form>
    @else
        <div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:1rem; color:#475569; font-size:0.9rem;">
            <strong>Modo solo lectura:</strong> Este expediente ya ha sido remitido formalmente a Jurídica Central en Concepción o archivado definitivamente, por lo que la competencia regional se encuentra cerrada y no admite modificaciones en esta instancia.
        </div>
    @endif
</div>

<!-- Historial Inmutable de Auditoría -->
@if (isset($historialAuditoria) && $historialAuditoria->isNotEmpty())
    <div class="tarjeta" style="margin-top: 1.5rem; padding: 1.25rem; border-top: 3px solid #64748b;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.75rem;">
            <h3 style="font-size:1.05rem; font-weight:700; color:#1e293b; margin:0; display:flex; align-items:center; gap:0.5rem;">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Historial de Auditoría y Modificaciones de Notas
            </h3>
            <span class="badge" style="background:#f1f5f9; color:#475569; font-size:0.8rem;">
                {{ $historialAuditoria->count() }} {{ $historialAuditoria->count() === 1 ? 'registro' : 'registros' }}
            </span>
        </div>

        <div class="tabla-contenedor">
            <table class="tabla" style="font-size:0.86rem;">
                <thead>
                    <tr>
                        <th style="width:140px;">Fecha / Hora</th>
                        <th>Operador Responsable</th>
                        <th>Acción</th>
                        <th>Detalle de Cambios en Criterios y Notas</th>
                        <th>Justificación / Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($historialAuditoria as $log)
                        @php
                            $u = $log->usuario;
                            $datos = $log->datos ?? [];
                            $modificados = $datos['criterios_modificados'] ?? [];
                        @endphp
                        <tr>
                            <td style="white-space:nowrap; color:#64748b;">
                                {{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '—' }}
                            </td>
                            <td>
                                <strong>{{ $u ? $u->nombre_completo : 'Sistema' }}</strong>
                                <div style="font-size:0.75rem; color:#64748b;">{{ $datos['usuario_rol'] ?? ($u?->rol ?? '—') }} &bull; {{ $log->ip }}</div>
                            </td>
                            <td>
                                <span class="badge" style="background:{{ str_contains($log->accion, 'modificar') ? '#fef3c7; color:#b45309;' : '#dcfce7; color:#15803d;' }}; font-weight:600;">
                                    {{ $log->accion }}
                                </span>
                            </td>
                            <td>
                                @if (!empty($modificados))
                                    <div style="display:flex; flex-direction:column; gap:0.25rem;">
                                        <strong style="color:#b45309; font-size:0.8rem;">Criterios Modificados ({{ count($modificados) }}):</strong>
                                        <ul style="margin:0; padding-left:1.1rem; font-size:0.8rem;">
                                            @foreach ($modificados as $critKey => $cambio)
                                                <li>
                                                    <strong>{{ $cambio['criterio'] ?? $critKey }}:</strong>
                                                    <span style="color:#64748b; text-decoration:line-through;">{{ $cambio['concepto_anterior'] ?? $cambio['nota_anterior'] }}</span>
                                                    &rarr;
                                                    <strong style="color:#0f766e;">{{ $cambio['concepto_nuevo'] ?? $cambio['nota_nueva'] }}</strong>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                @if (!empty($datos['nota_fijada']) || !empty($datos['concepto_fijado']))
                                    <div style="margin-top:0.25rem; font-size:0.8rem; color:#334155;">
                                        Calificación final: 
                                        @if (!empty($datos['concepto_anterior']) && $datos['concepto_anterior'] !== $datos['concepto_fijado'])
                                            <span style="color:#64748b; text-decoration:line-through;">{{ $datos['concepto_anterior'] }}</span> &rarr;
                                        @endif
                                        <strong>{{ $datos['concepto_fijado'] ?? $datos['nota_fijada'] }}</strong>
                                    </div>
                                @endif
                            </td>
                            <td style="color:#334155; font-size:0.82rem; white-space:pre-line;">
                                {{ $datos['observaciones'] ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selects = document.querySelectorAll('.select-criterio-ucoe');
    const promedioConcepto = document.getElementById('promedio-criterio-concepto');
    const promedioValor = document.getElementById('promedio-criterio-valor');
    const radioConceptos = document.querySelectorAll('input[name="concepto"]');
    const avisoModificacion = document.getElementById('aviso-criterio-modificado');

    if (!selects.length) return;

    const valores = {
        'SOBRESALIENTE': 7.0,
        'BUENA': 5.0,
        'REGULAR': 4.0,
        'MALA': 3.0
    };

    function calcular() {
        let suma = 0;
        let count = 0;
        let huboCambio = false;

        selects.forEach(function (sel) {
            const valClave = sel.value;
            const valNum = valores[valClave] || 5.0;
            suma += valNum;
            count++;

            const valOriginal = sel.getAttribute('data-valor-original');
            const fila = sel.closest('tr');
            if (valOriginal && valClave !== valOriginal) {
                huboCambio = true;
                if (fila) fila.style.backgroundColor = '#fefce8';
            } else {
                if (fila) fila.style.backgroundColor = '';
            }
        });

        if (count === 0) return;
        const promedio = Math.round((suma / count) * 100) / 100;
        let concepto = 'Mala';
        let conceptoClave = 'MALA';

        if (promedio >= 6.0) {
            concepto = 'Sobresaliente';
            conceptoClave = 'SOBRESALIENTE';
        } else if (promedio >= 5.0) {
            concepto = 'Buena';
            conceptoClave = 'BUENA';
        } else if (promedio >= 4.0) {
            concepto = 'Regular';
            conceptoClave = 'REGULAR';
        }

        if (promedioConcepto) promedioConcepto.textContent = concepto;
        if (promedioValor) promedioValor.textContent = 'Promedio: ' + promedio.toFixed(2);

        if (avisoModificacion) {
            avisoModificacion.hidden = !huboCambio;
        }

        radioConceptos.forEach(function (r) {
            if (r.value === conceptoClave) {
                r.checked = true;
            }
        });
    }

    selects.forEach(function (sel) {
        sel.addEventListener('change', calcular);
    });

    calcular();
});
</script>
@endsection
