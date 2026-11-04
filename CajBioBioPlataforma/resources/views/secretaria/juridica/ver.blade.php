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
            <strong style="color:var(--azul-900);font-size:1rem;">Expediente Cerrado y Despachado al Portal</strong>
            <p style="margin:.25rem 0 0;font-size:.88rem;color:var(--azul-950);">Documentos oficiales autorizados y disponibles en el portal del postulante{{ $practica->despachada_at ? ' desde el '.$practica->despachada_at->format('d-m-Y') : '' }}. La secretaría puede descargar el expediente completo a continuación en caso de requerir remitirlo personalmente por correo.</p>
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
                Modificar calificación institucional
            </x-boton>
        @endif

        @if ($puedeRechazar)
            <x-boton tipo="peligro" data-modal-abrir="modal-rechazar-sancion">
                Rechazar por causal disciplinaria fundada
            </x-boton>
        @endif

        @if ($puedeDespachar && ! $esCerrada)
            <form action="{{ route('secretaria.juridica.despachar', $practica->id) }}" method="POST" onsubmit="return confirm('¿Confirmar la autorización y el despacho formal de los certificados al portal del postulante, archivando el expediente en Historial?');">
                @csrf
                <x-boton tipo="exito">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.4rem;"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Autorizar y despachar al portal del postulante
                </x-boton>
            </form>
        @endif

        @php
            $esAprobatoriaJur = $practica->nota_practica !== null
                ? \App\Support\Notas::esAprobatoria($practica->nota_practica)
                : ($evaluacion && $evaluacion->aprobado);
            $puedeDescargarJur = $evaluacion && $esAprobatoriaJur
                && ! in_array($practica->estado, ['cancelada', 'rechazada_sancion'], true)
                && ($practica->documentos_autorizados || $practica->estado === 'cerrada_historial' || $practica->firmado_director_general_at);
        @endphp

        @if ($puedeDescargarJur)
            <x-boton tipo="secundario" :href="route('secretaria.juridica.certificado', $practica->id)" target="_blank">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.3rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Descargar Certificado
            </x-boton>
            <x-boton tipo="secundario" :href="route('secretaria.juridica.resolucion', $practica->id)" target="_blank">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.3rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Descargar Resolución
            </x-boton>
            <x-boton tipo="primario" :href="route('secretaria.juridica.expediente', $practica->id)">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.3rem;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Descargar Expediente (ZIP)
            </x-boton>
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

{{-- Modal para modificar manualmente la calificación institucional con los 7 criterios individualmente --}}
@if ($puedeAjustarNota)
    <x-modal id="modal-ajustar-nota" titulo="Modificar calificación institucional" :grande="true">
        <form id="form-ajustar-nota" action="{{ route('secretaria.juridica.nivelar', $practica->id) }}" method="POST">
            @csrf
            <p style="margin:0 0 1rem;font-size:.9rem;color:var(--texto);">
                Calificación actual registrada: <strong>{{ $conceptoFinal }}</strong>.
                Modifique individualmente la calificación de cada uno de los 7 criterios reglamentarios en palabras:
            </p>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:.85rem;margin-bottom:1.25rem;background:var(--papel);padding:1rem;border-radius:.5rem;border:1px solid var(--borde-suave);">
                @foreach ($criteriosList as $clave => $etiqueta)
                    @php
                        $valActual = $evaluacion && isset($evaluacion->{$clave}) 
                            ? \App\Support\Notas::claveConcepto($evaluacion->{$clave}) 
                            : $claveSugerida;
                    @endphp
                    <div class="campo" style="margin:0;">
                        <label class="campo-etiqueta" for="crit_{{ $clave }}" style="font-weight:600;font-size:.85rem;">
                            {{ $etiqueta }} <span class="texto-rojo">*</span>
                        </label>
                        <select id="crit_{{ $clave }}" name="{{ $clave }}" class="campo-control" data-criterio-modal required style="font-size:.9rem;padding:.45rem .6rem;">
                            @foreach ($opcionesNotas as $cClave => $op)
                                <option value="{{ $cClave }}" @selected($cClave === $valActual)>
                                    {{ $op['nombre'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding:.75rem 1rem;background:var(--azul-50);border:1px solid var(--azul-200);border-radius:.4rem;margin-bottom:1rem;flex-wrap:wrap;gap:.75rem;">
                <div>
                    <span style="font-size:.85rem;color:var(--azul-800);font-weight:600;">Calificación Final Resultante:</span>
                    <div id="resultado-concepto-nombre" style="font-size:1.25rem;font-weight:700;color:var(--azul-900);">{{ $conceptoFinal }}</div>
                </div>
                <div style="min-width:220px;">
                    <label class="campo-etiqueta" for="concepto" style="font-size:.8rem;color:var(--azul-800);">Concepto formal a ratificar:</label>
                    <select id="concepto" name="concepto" class="campo-control" style="font-size:.85rem;padding:.35rem .5rem;">
                        @foreach ($opcionesNotas as $cClave => $op)
                            <option value="{{ $cClave }}" @selected($cClave === $claveSugerida)>
                                {{ $op['nombre'] }} {{ $op['aprobatorio'] ? '(Aprobatoria)' : '(Reprobatoria)' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="campo">
                <label for="justificacion" class="campo-etiqueta">Justificación normativa y antecedentes <span class="texto-rojo">*</span></label>
                <textarea id="justificacion" name="justificacion" rows="3" class="campo-control" required placeholder="Indique la justificación normativa institucional (ej: cumplimiento íntegro del plan de práctica institucional sin medidas disciplinarias)..."></textarea>
            </div>
        </form>
        <x-slot:pie>
            <x-boton type="submit" tipo="primario" form="form-ajustar-nota">Guardar modificación</x-boton>
        </x-slot:pie>
    </x-modal>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectsCriterios = document.querySelectorAll('[data-criterio-modal]');
            const elemResultado = document.getElementById('resultado-concepto-nombre');
            const selectConcepto = document.getElementById('concepto');
            
            const valoresConceptos = {
                'SOBRESALIENTE': 7.0,
                'BUENA': 5.0,
                'REGULAR': 4.0,
                'MALA': 3.0
            };

            function actualizarConceptoResultante() {
                if (!selectsCriterios.length) return;
                let suma = 0;
                let count = 0;
                selectsCriterios.forEach(function (s) {
                    if (valoresConceptos[s.value] !== undefined) {
                        suma += valoresConceptos[s.value];
                        count++;
                    }
                });
                if (count === 0) return;
                const prom = suma / count;
                let concepto = 'Mala';
                let clave = 'MALA';
                if (prom >= 6.0) {
                    concepto = 'Sobresaliente';
                    clave = 'SOBRESALIENTE';
                } else if (prom >= 5.0) {
                    concepto = 'Buena';
                    clave = 'BUENA';
                } else if (prom >= 4.0) {
                    concepto = 'Regular';
                    clave = 'REGULAR';
                }
                if (elemResultado) elemResultado.textContent = concepto;
                if (selectConcepto) selectConcepto.value = clave;
            }

            selectsCriterios.forEach(function (s) {
                s.addEventListener('change', actualizarConceptoResultante);
            });
        });
    </script>
@endif

@if ($puedeRechazar)
    <x-modal id="modal-rechazar-sancion" titulo="Rechazar por Causal Disciplinaria Fundada">
        <form id="form-rechazar-sancion" action="{{ route('secretaria.juridica.rechazar_sancion', $practica->id) }}" method="POST">
            @csrf
            <p style="margin:0 0 .75rem;font-size:.9rem;color:var(--rojo-800);">Se registrará el rechazo de la práctica por antecedentes disciplinarios graves, impidiendo la aprobación y nivelación.</p>
            <div class="campo">
                <label for="motivo" class="campo-etiqueta">Fundamento y antecedentes sancionatorios <span class="texto-rojo">*</span></label>
                <textarea id="motivo" name="motivo" rows="4" class="campo-control" required placeholder="Indique los antecedentes sumarios o resoluciones disciplinarias fundadas..."></textarea>
            </div>
        </form>
        <x-slot:pie>
            <x-boton type="submit" tipo="peligro" form="form-rechazar-sancion">Confirmar Rechazo por Sanción</x-boton>
        </x-slot:pie>
    </x-modal>
@endif

@endsection
