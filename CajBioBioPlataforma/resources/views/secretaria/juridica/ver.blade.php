@extends('layouts.portal', ['migas' => [['Jurídica Central', route('secretaria.juridica.index')], ['Expediente']]])
@section('titulo', 'Expediente de '.$postulante->nombre.' '.$postulante->apellido)
@section('contenido')
@php
    $c = $practica->consultorio;
    $reg = $c->region;
    $a = $practica->abogado?->usuario;
    $esCerrada = $practica->estado === 'cerrada_historial';
@endphp
<div class="sec-cabecera">
    <div>
        <div class="sec-titulo-ficha">
            <h1 class="titulo-pagina">{{ $postulante->nombre }} {{ $postulante->apellido }}</h1>
            <x-badge :estado="$practica->estado" />
        </div>
        <p class="sec-subtitulo">Región de Origen: <strong>{{ $reg->nombre }} ({{ $reg->codigo }})</strong> · Sede: {{ $c->nombre }} · RUT: {{ $postulante->rut }}</p>
    </div>
    <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
        <x-boton tipo="secundario" :href="route('secretaria.juridica.index')">@include('secretaria._icono', ['n' => 'volver']) Volver a recepción</x-boton>
    </div>
</div>

{{-- Bloque de alertas de estado y nivelación --}}
@if ($esNivelable && $practica->estado === 'remitida_biobio')
    <div class="alerta alerta-aviso" style="margin:1rem 0;background:var(--amarillo-50);border:1px solid var(--amarillo-300);border-radius:.5rem;padding:1rem;display:flex;gap:.75rem;">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--amarillo-800);flex-shrink:0;"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
        <div>
            <strong style="color:var(--amarillo-900);font-size:1rem;">Calificación en concepto «Regular» — Sujeto a Revisión y Nivelación Institucional</strong>
            <p style="margin:.25rem 0 0;font-size:.88rem;color:var(--amarillo-950);">Conforme al reglamento institucional de la CAJ Biobío, las calificaciones regulares de regiones externas deben revisarse en sede central para su elevación manual al concepto <strong>Buena</strong> (u otro concepto meritorio), salvo antecedentes sancionatorios fundados.</p>
        </div>
    </div>
@elseif ($practica->estado === 'nivelada_biobio')
    <div class="alerta alerta-exito" style="margin:1rem 0;background:var(--verde-50);border:1px solid var(--verde-200);border-radius:.5rem;padding:1rem;display:flex;gap:.75rem;">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--verde-800);flex-shrink:0;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <div>
            <strong style="color:var(--verde-900);font-size:1rem;">Calificación Determinada en Sede Central: {{ $conceptoFinal }}</strong>
            <p style="margin:.25rem 0 0;font-size:.88rem;color:var(--verde-950);">Fijada por Secretaría Jurídica Central{{ $practica->nivelada_at ? ' el '.$practica->nivelada_at->format('d-m-Y H:i') : '' }}. Justificación normativa: <em>{{ $practica->justificacion_nivelacion }}</em></p>
        </div>
    </div>
@elseif ($practica->estado === 'rechazada_sancion')
    <div class="alerta alerta-error" style="margin:1rem 0;background:var(--rojo-50);border:1px solid var(--rojo-200);border-radius:.5rem;padding:1rem;display:flex;gap:.75rem;">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--rojo-800);flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <div>
            <strong style="color:var(--rojo-900);font-size:1rem;">Expediente Rechazado por Causal Disciplinaria Fundada</strong>
            <p style="margin:.25rem 0 0;font-size:.88rem;color:var(--rojo-950);">Fundamento: {{ $practica->causal_rechazo_sancion }}</p>
        </div>
    </div>
@elseif ($practica->estado === 'cerrada_historial')
    <div class="alerta alerta-info" style="margin:1rem 0;background:var(--azul-50);border:1px solid var(--azul-200);border-radius:.5rem;padding:1rem;display:flex;gap:.75rem;">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--azul-800);flex-shrink:0;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <div>
            <strong style="color:var(--azul-900);font-size:1rem;">Expediente Cerrado y Despachado Formalmente</strong>
            <p style="margin:.25rem 0 0;font-size:.88rem;color:var(--azul-950);">Certificados despachados por correo al postulante{{ $practica->despachada_at ? ' el '.$practica->despachada_at->format('d-m-Y') : '' }}. El postulante ya tiene acceso de descarga en su portal.</p>
        </div>
    </div>
@endif

{{-- Panel de Acciones Exclusivas de Jurídica Central --}}
<div class="tarjeta" style="margin-bottom:1.5rem;padding:1.25rem;background:var(--blanco);border:1px solid var(--borde);border-radius:.5rem;">
    <h3 style="margin:0 0 .75rem;font-size:1.1rem;color:var(--azul-900);">Acciones Exclusivas de Secretaría Jurídica Central</h3>
    <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;">
        @if ($puedeAjustarNota)
            <x-boton tipo="primario" data-modal-abrir="modal-ajustar-nota">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.4rem;"><polyline points="18 15 12 9 6 15"/></svg>
                Determinar Calificación Institucional
            </x-boton>
        @endif

        @if ($puedeRechazar)
            <x-boton tipo="peligro" data-modal-abrir="modal-rechazar-sancion">
                Rechazar por causal disciplinaria fundada
            </x-boton>
        @endif

        @if ($puedeDespachar && ! $esCerrada)
            <form action="{{ route('secretaria.juridica.despachar', $practica->id) }}" method="POST" onsubmit="return confirm('¿Confirmar el despacho formal de los certificados al postulante y archivar el expediente en CerradaHistorial?');">
                @csrf
                <x-boton tipo="exito">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.4rem;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    Despachar Certificados por Correo y Cerrar Expediente
                </x-boton>
            </form>
        @endif
    </div>
</div>

<div class="sec-dos">
    {{-- Columna 1: Evaluación y Conceptos en Palabras --}}
    <div class="sec-pila">
        <x-tarjeta acento titulo="Calificación Institucional">
            <div class="sec-final" style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <div style="font:700 2rem var(--fuente-titulo);color:var(--azul-900);">{{ $conceptoFinal }}</div>
                    <div class="indicador-etiqueta" style="color:var(--texto-suave);">Concepto Reglamentario</div>
                </div>
                <div style="text-align:right;">
                    <div style="margin-top:.4rem;"><x-badge :estado="$evaluacion && $evaluacion->aprobado ? 'activa' : 'cancelada'" :texto="$evaluacion && $evaluacion->aprobado ? 'APROBADO' : 'NO APROBADO'" /></div>
                </div>
            </div>

            @if (count($criterios))
                <div style="margin-top:1.25rem;">
                    <h4 style="font-size:.95rem;margin:0 0 .5rem;color:var(--texto);">Desglose por Criterios</h4>
                    <ul class="sec-criterios" style="list-style:none;padding:0;margin:0;">
                        @foreach ($criterios as $cr)
                            <li style="display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid var(--borde-suave);font-size:.9rem;">
                                <span>{{ $cr['etiqueta'] }}</span>
                                <span style="font-weight:700;color:var(--azul-900);">{{ $cr['concepto'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($evaluacion && $evaluacion->observaciones)
                <div style="margin-top:1rem;padding-top:.75rem;border-top:1px solid var(--borde-suave);">
                    <div style="font-size:.85rem;font-weight:600;color:var(--texto-suave);">Observaciones del Abogado Tutor:</div>
                    <p style="margin:.25rem 0 0;font-size:.9rem;white-space:pre-line;">{{ $evaluacion->observaciones }}</p>
                </div>
            @endif
        </x-tarjeta>
    </div>

    {{-- Columna 2: Datos de Postulante y Trazabilidad --}}
    <div class="sec-pila">
        <x-tarjeta titulo="Ficha del Postulante">
            <dl class="datos">
                <div><dt>Nombre Completo</dt><dd>{{ $postulante->nombre }} {{ $postulante->apellido }}</dd></div>
                <div><dt>RUT</dt><dd>{{ $postulante->rut }}</dd></div>
                <div><dt>Correo Electrónico</dt><dd><a href="mailto:{{ $postulante->email }}">{{ $postulante->email }}</a></dd></div>
                <div><dt>Teléfono</dt><dd>{{ $postulante->telefono }}</dd></div>
                <div class="ancho-completo"><dt>Universidad</dt><dd>{{ $postulante->universidad }}</dd></div>
                <div class="ancho-completo"><dt>Domicilio</dt><dd>{{ $postulante->direccion }}, {{ $postulante->comuna?->nombre }} ({{ $reg->nombre }})</dd></div>
            </dl>
        </x-tarjeta>

        <x-tarjeta titulo="Trazabilidad y Competencia">
            <dl class="datos">
                <div><dt>Región de Origen</dt><dd>{{ $reg->nombre }} ({{ $reg->codigo }})</dd></div>
                <div><dt>Sede Consultorio</dt><dd>{{ $c->nombre }}</dd></div>
                <div><dt>Abogado Tutor</dt><dd>{{ $a ? $a->nombre.' '.$a->apellido : 'Sin asignar' }}</dd></div>
                <div><dt>Periodo</dt><dd>{{ $practica->fecha_inicio->format('d-m-Y') }} al {{ $practica->fecha_termino->format('d-m-Y') }}</dd></div>
                @if ($practica->remitida_at)
                    <div class="ancho-completo"><dt>Remitido a Biobío</dt><dd>{{ $practica->remitida_at->format('d-m-Y H:i') }} (Cierre de competencia regional)</dd></div>
                @endif
                @if ($practica->despachada_at)
                    <div class="ancho-completo"><dt>Despachado</dt><dd>{{ $practica->despachada_at->format('d-m-Y H:i') }} (Archivado en CerradaHistorial)</dd></div>
                @endif
            </dl>
        </x-tarjeta>
    </div>
</div>

{{-- Modal para determinar manualmente la calificación institucional con todas las opciones en palabras --}}
@if ($puedeAjustarNota)
    <div id="modal-ajustar-nota" class="modal" hidden role="dialog" aria-modal="true" aria-labelledby="modal-ajustar-titulo">
        <div class="modal-contenido">
            <div class="modal-cabecera">
                <h2 id="modal-ajustar-titulo" class="modal-titulo">Determinar Calificación Institucional</h2>
                <button type="button" class="modal-cerrar" data-modal-cerrar aria-label="Cerrar modal">&times;</button>
            </div>
            <form action="{{ route('secretaria.juridica.nivelar', $practica->id) }}" method="POST">
                @csrf
                <div class="modal-cuerpo">
                    <p style="margin:0 0 .75rem;font-size:.9rem;color:var(--texto);">
                        Calificación actual registrada: <strong>{{ $conceptoFinal }}</strong>.
                        Seleccione el concepto reglamentario final en palabras y fundamente la decisión institucional:
                    </p>
                    <div class="campo" style="margin-bottom:1rem;">
                        <label class="campo-etiqueta" for="concepto">Calificación en palabras <span class="texto-rojo">*</span></label>
                        <select id="concepto" name="concepto" class="campo-control" required>
                            @foreach ($opcionesNotas as $clave => $op)
                                <option value="{{ $clave }}" @selected($clave === $claveSugerida)>
                                    {{ $op['nombre'] }} {{ $op['aprobatorio'] ? '(Aprobatoria)' : '(Reprobatoria)' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="sec-menor" style="margin-top:.35rem;">Por reglamento institucional, las calificaciones en concepto «Regular» sin faltas disciplinarias se elevan a «Buena».</p>
                    </div>
                    <div class="campo">
                        <label for="justificacion" class="campo-etiqueta">Justificación normativa y antecedentes <span class="texto-rojo">*</span></label>
                        <textarea id="justificacion" name="justificacion" rows="4" class="campo-control" required placeholder="Indique la justificación normativa reglamentaria (ej: cumplimiento íntegro del plan de práctica institucional sin medidas disciplinarias)..."></textarea>
                    </div>
                </div>
                <div class="modal-pie">
                    <x-boton type="button" tipo="secundario" data-modal-cerrar>Cancelar</x-boton>
                    <x-boton type="submit" tipo="primario">Guardar Calificación Institucional</x-boton>
                </div>
            </form>
        </div>
    </div>
@endif

@if ($puedeRechazar)
    <div id="modal-rechazar-sancion" class="modal" hidden role="dialog" aria-modal="true" aria-labelledby="modal-rechazar-titulo">
        <div class="modal-contenido">
            <div class="modal-cabecera">
                <h2 id="modal-rechazar-titulo" class="modal-titulo">Rechazar por Causal Disciplinaria Fundada</h2>
                <button type="button" class="modal-cerrar" data-modal-cerrar aria-label="Cerrar modal">&times;</button>
            </div>
            <form action="{{ route('secretaria.juridica.rechazar_sancion', $practica->id) }}" method="POST">
                @csrf
                <div class="modal-cuerpo">
                    <p style="margin:0 0 .75rem;font-size:.9rem;color:var(--rojo-800);">Se registrará el rechazo de la práctica por antecedentes disciplinarios graves, impidiendo la aprobación y nivelación.</p>
                    <div class="campo">
                        <label for="motivo" class="campo-etiqueta">Fundamento y antecedentes sancionatorios <span class="texto-rojo">*</span></label>
                        <textarea id="motivo" name="motivo" rows="4" class="campo-control" required placeholder="Indique los antecedentes sumarios o resoluciones disciplinarias fundadas..."></textarea>
                    </div>
                </div>
                <div class="modal-pie">
                    <x-boton type="button" tipo="secundario" data-modal-cerrar>Cancelar</x-boton>
                    <x-boton type="submit" tipo="peligro">Confirmar Rechazo por Sanción</x-boton>
                </div>
            </form>
        </div>
    </div>
@endif

@endsection
