@extends('layouts.portal', ['migas' => [[$esSegundaInstancia ? 'Segunda Instancia (Corte)' : 'Mis postulantes', route('tutor.inicio')], [$postulante->nombre_completo]]])
@section('titulo', $postulante->nombre_completo)

@section('contenido')
@php
    $etiquetasEstado = ['por_iniciar' => 'Por iniciar', 'activa' => 'En curso', 'por_terminar' => 'Por terminar', 'finalizada' => 'Finalizada', 'cancelada' => 'Cancelada'];
    $fmt = fn ($f) => $f?->locale('es')->translatedFormat('j \d\e F \d\e Y');
    $kb = fn ($b) => $b >= 1048576 ? number_format($b / 1048576, 1, ',', '').' MB' : max(1, round($b / 1024)).' KB';
@endphp

<header class="tp-cabecera tp-entra">
    <div>
        <h1 class="titulo-pagina">{{ $postulante->nombre_completo }}</h1>
        <p class="texto-suave">
            {{ $postulante->universidad }} · {{ $practica->consultorio->nombre ?? 'Sin consultorio' }}
            @if ($practica->consultorio?->region) · Región {{ $practica->consultorio->region->nombre }} @endif
        </p>
    </div>
    <div class="tp-meta">
        <x-badge :estado="$estadoMostrado" :texto="$etiquetasEstado[$estadoMostrado] ?? null" />
        <x-boton tipo="secundario" :href="route('tutor.inicio')">Volver al listado</x-boton>
    </div>
</header>

@if ($porTerminar && ! $esSegundaInstancia)
    <div class="alerta alerta-aviso tp-entra" role="status" style="--i:1">
        <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4M12 17h.01M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
        <div>
            <strong>Alerta de término reglamentario (Día 166 / 5,5 meses)</strong>
            <div>{{ $diasRestantes === 0 ? 'La práctica cumple sus 6 meses hoy.' : 'Quedan '.$diasRestantes.($diasRestantes === 1 ? ' día' : ' días').' para cumplir el período legal.' }} Recuerde solicitar el informe de práctica y completar la evaluación de 1ª instancia.</div>
        </div>
    </div>
@elseif ($practica->estado === 'activa' && $diasRestantes < 0 && ! $esSegundaInstancia)
    <div class="alerta alerta-error tp-entra" role="status" style="--i:1">
        <div><strong>Período de 6 meses cumplido.</strong> La práctica ha alcanzado el término provisional; el cupo del consultorio se libera y corresponde emitir la evaluación.</div>
    </div>
@endif

<div class="tp-dos">
    <div class="tp-pila">
        <x-tarjeta titulo="Ficha del postulante" class="tp-entra" style="--i:2">
            <dl class="tp-ficha">
                <div><dt>RUT</dt><dd>{{ $postulante->rut }}</dd></div>
                <div><dt>Teléfono</dt><dd>{{ $postulante->telefono ?: '—' }}</dd></div>
                <div class="ancho-completo" style="grid-column:1/-1"><dt>Correo</dt><dd><a href="mailto:{{ $postulante->email }}">{{ $postulante->email }}</a></dd></div>
                <div><dt>Universidad</dt><dd>{{ $postulante->universidad }}</dd></div>
                <div><dt>Consultorio</dt><dd>{{ $practica->consultorio->nombre ?? '—' }}@if ($practica->consultorio?->direccion)<br><span class="texto-suave">{{ $practica->consultorio->direccion }}</span>@endif</dd></div>
                <div><dt>Inicio oficial</dt><dd>{{ $fmt($practica->fecha_inicio) }}</dd></div>
                <div><dt>Término legal</dt><dd>{{ $fmt($practica->fecha_termino) }}</dd></div>
                @if ($postulante->discapacidad)
                    <div style="grid-column:1/-1"><dt>Discapacidad</dt><dd>{{ $postulante->discapacidad_descripcion ?: 'Declarada, sin detalle.' }}</dd></div>
                @endif
            </dl>
        </x-tarjeta>

        <x-tarjeta titulo="Informe final de práctica" class="tp-entra" style="--i:3">
            @if ($informe)
                <div class="tp-archivo">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8zM14 3v5h5M9 13h6M9 17h6"/></svg>
                    <div>
                        <strong>{{ $informe->nombre_original }}</strong>
                        <span>{{ $kb($informe->tamano_bytes) }} · recibido el {{ $fmt($informe->created_at) }}</span>
                    </div>
                    <x-boton tipo="secundario" tam="sm" :href="route('tutor.informe.descargar', $practica->id)">Descargar informe</x-boton>
                </div>

                @if (! $evaluacion && ! $esSegundaInstancia)
                    <details style="margin-top: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 0.75rem;">
                        <summary style="font-size: 0.85rem; font-weight: 600; color: #374151; cursor: pointer;">
                            ¿Formular observaciones para subsanación del informe?
                        </summary>
                        <form action="{{ route('tutor.informe.observar', $practica->id) }}" method="POST" style="margin-top: 0.75rem;">
                            @csrf
                            <label style="display: block; font-size: 0.82rem; margin-bottom: 0.25rem;">
                                Indique las inconsistencias u observaciones que el postulante debe corregir:
                            </label>
                            <textarea name="observaciones" rows="3" required placeholder="Escriba las correcciones requeridas antes de evaluar..." style="width: 100%; font-size: 0.85rem; padding: 0.4rem; border: 1px solid #d1d5db; border-radius: 4px;"></textarea>
                            <button type="submit" class="btn btn-secundario" style="font-size: 0.82rem; margin-top: 0.5rem; width: 100%;">
                                Enviar observaciones al postulante
                            </button>
                        </form>
                    </details>
                @endif
            @else
                <div class="tp-bloqueo">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8zM14 3v5h5"/></svg>
                    <div><strong>Informe pendiente</strong>El postulante aún no carga su informe final en plataforma. La encuesta de 1ª instancia se habilitará al recibirlo.</div>
                </div>
            @endif
        </x-tarjeta>

        <!-- Tarjeta de la otra instancia (para garantizar cruces de permisos claros) -->
        @if ($esSegundaInstancia)
            <!-- El abogado de 2ª instancia ve 1ª instancia como solo lectura -->
            <x-tarjeta titulo="1ª Instancia: Consultorio Jurídico (Solo lectura)" class="tp-entra" style="--i:4">
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:0.85rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <strong>Evaluación de 6 meses en Consultorio</strong>
                        @if ($concepto1)
                            <span class="badge" style="background:#e0f2fe;color:#0369a1;padding:0.25rem 0.6rem;border-radius:9999px;font-weight:600;">
                                {{ $concepto1 }}
                            </span>
                        @else
                            <span class="badge" style="background:#fef3c7;color:#92400e;padding:0.25rem 0.6rem;border-radius:9999px;">Pendiente en consultorio</span>
                        @endif
                    </div>
                    @if ($calif1 && $calif1->observaciones)
                        <p style="font-size:0.85rem; color:#475569; margin:0.5rem 0 0;">
                            <strong>Observaciones del Abogado Jefe de Consultorio:</strong><br>{{ $calif1->observaciones }}
                        </p>
                    @endif
                    <p style="font-size:0.8rem; color:#64748b; margin:0.75rem 0 0;">
                        🔒 <em>Bloqueado: El Abogado Jefe de 2ª Instancia no tiene atribución para alterar la evaluación de 1ª instancia.</em>
                    </p>
                </div>
            </x-tarjeta>
        @else
            <!-- El abogado de 1ª instancia ve 2ª instancia como solo lectura -->
            <x-tarjeta titulo="2ª Instancia: Corte de Apelaciones (Solo lectura)" class="tp-entra" style="--i:4">
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:0.85rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <strong>Alegatos ante Tribunal de Alzada</strong>
                        @if ($concepto2)
                            <span class="badge" style="background:#f0fdf4;color:#166534;padding:0.25rem 0.6rem;border-radius:9999px;font-weight:600;">
                                {{ $concepto2 }}
                            </span>
                        @else
                            <span class="badge" style="background:#f3f4f6;color:#6b7280;padding:0.25rem 0.6rem;border-radius:9999px;">Sin registro / No aplica aún</span>
                        @endif
                    </div>
                    @if ($calif2 && $calif2->observaciones)
                        <p style="font-size:0.85rem; color:#475569; margin:0.5rem 0 0;">
                            <strong>Observaciones de Corte de Apelaciones:</strong><br>{{ $calif2->observaciones }}
                        </p>
                    @endif
                    <p style="font-size:0.8rem; color:#64748b; margin:0.75rem 0 0;">
                        🔒 <em>Bloqueado: El Abogado Jefe de Consultorio no puede calificar ni modificar la 2ª instancia.</em>
                    </p>
                </div>
            </x-tarjeta>
        @endif
    </div>

    <!-- Módulo Principal de Evaluación según el rol del Abogado Jefe -->
    @if ($esSegundaInstancia)
        <!-- Formulario o Vista de Evaluación de 2ª Instancia (Corte de Apelaciones) -->
        <x-tarjeta titulo="Evaluación de Segunda Instancia (Corte de Apelaciones)" class="tp-entra" style="--i:3" id="evaluacion-corte">
            @if ($calif2)
                <div class="tp-lectura">
                    <div class="tp-sello tp-sello-si">
                        <div>
                            <strong>CALIFICACIÓN EN CORTE DE APELACIONES</strong>
                            <span style="font-size:1.1rem; font-weight:700;">{{ $concepto2 }}</span>
                        </div>
                    </div>
                    @if ($calif2->observaciones)
                        <div style="margin-top:1rem;">
                            <strong>Observaciones y antecedentes de alegatos:</strong>
                            <p class="texto-suave" style="margin:.25rem 0 0; white-space:pre-line;">{{ $calif2->observaciones }}</p>
                        </div>
                    @endif
                    <p class="texto-suave" style="margin:1rem 0 0; font-size:.85rem;">
                        Registrada formalmente el {{ $fmt($calif2->created_at ?? now()) }}. La evaluación complementaria queda incorporada al expediente regional.
                    </p>
                </div>
            @else
                <form method="POST" action="{{ route('tutor.evaluacion.segunda_instancia', $practica->id) }}" id="form-segunda-instancia">
                    @csrf
                    <p class="texto-suave" style="margin:0 0 1rem;">
                        Evalúe el desempeño del postulante en los alegatos ante la Corte de Apelaciones. La calificación institucional se expresa únicamente en conceptos cualitativos normativos.
                    </p>

                    @error('concepto')<div class="alerta alerta-error" style="margin-bottom:.75rem;">{{ $message }}</div>@enderror
                    @error('observaciones')<div class="alerta alerta-error" style="margin-bottom:.75rem;">{{ $message }}</div>@enderror

                    <div style="margin-bottom:1.25rem;">
                        <label style="display:block; font-weight:600; font-size:0.9rem; margin-bottom:0.5rem; color:#1f2937;">
                            Calificación de alegatos (en palabras) *
                        </label>
                        <select name="concepto" required style="width:100%; padding:0.6rem; font-size:0.95rem; border:1px solid #d1d5db; border-radius:6px;">
                            <option value="">-- Seleccione calificación cualitativa --</option>
                            @foreach ($opcionesNotas as $clave => $opc)
                                <option value="{{ $clave }}" @selected(old('concepto') === $clave)>
                                    {{ $opc['nombre'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="margin-bottom:1.25rem;">
                        <label style="display:block; font-weight:600; font-size:0.9rem; margin-bottom:0.5rem; color:#1f2937;">
                            Observaciones de los alegatos y preparación jurídica *
                        </label>
                        <textarea name="observaciones" rows="5" required minlength="10" maxlength="3000" placeholder="Detalle antecedentes de los alegatos en Corte, solvencia argumentativa, conocimiento de las causas y cumplimiento de instrucciones..." style="width:100%; padding:0.6rem; font-size:0.9rem; border:1px solid #d1d5db; border-radius:6px;">{{ old('observaciones') }}</textarea>
                        <small class="texto-suave">Mínimo 10 caracteres. Máximo 3000 caracteres.</small>
                    </div>

                    <x-boton type="submit" style="width:100%;">
                        Emitir Calificación de Segunda Instancia (Corte de Apelaciones)
                    </x-boton>
                </form>
            @endif
        </x-tarjeta>
    @else
        <!-- Formulario o Vista de Evaluación de 1ª Instancia (Consultorio Jurídico) -->
        <x-tarjeta titulo="Encuesta de desempeño de 1ª Instancia (Consultorio)" class="tp-entra" style="--i:3" id="encuesta">
            @if ($evaluacion)
                @php $ok = $evaluacion->aprobado; @endphp
                <div class="tp-lectura">
                    <div class="tp-sello {{ $ok ? 'tp-sello-si' : 'tp-sello-no' }}">
                        <div>
                            <strong>{{ $ok ? 'HA APROBADO' : 'NO HA APROBADO' }}</strong>
                            <span style="font-size:1.15rem; font-weight:700;">Calificación: {{ $concepto1 }}</span>
                        </div>
                    </div>
                    <div>
                        @foreach ($criterios as $clave => $etiqueta)
                            <div class="tp-lectura-fila">
                                <span>{{ $etiqueta }}</span>
                                <b style="font-weight:600; color:#1f2937;">{{ \App\Support\Notas::concepto($evaluacion->$clave) }}</b>
                            </div>
                        @endforeach
                    </div>
                    @if ($evaluacion->observaciones)
                        <div>
                            <strong>Comentarios generales del Abogado Jefe</strong>
                            <p class="texto-suave" style="margin:.25rem 0 0; white-space:pre-line;">{{ $evaluacion->observaciones }}</p>
                        </div>
                    @endif
                    <p class="texto-suave" style="margin:0; font-size:.85rem;">
                        La evaluación de 1ª instancia es definitiva y consolida los 6 meses reglamentarios en el consultorio.
                    </p>
                </div>
            @elseif ($bloqueoEvaluacion)
                <div class="tp-bloqueo" role="status">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                    <div><strong>Encuesta bloqueada</strong>{{ $bloqueoEvaluacion }}</div>
                </div>
                <fieldset disabled style="border:0;padding:0;margin:1rem 0 0;opacity:.5" aria-hidden="true">
                    <ul class="tp-criterios">
                        @foreach ($criterios as $clave => $etiqueta)
                            <li class="tp-criterio">
                                <span>{{ $etiqueta }}</span>
                                <input class="campo-control" disabled placeholder="Sobresaliente, Buena, Regular..." tabindex="-1">
                            </li>
                        @endforeach
                    </ul>
                </fieldset>
            @else
                <form method="POST" action="{{ route('tutor.evaluacion.guardar', $practica->id) }}" id="form-encuesta" novalidate data-sin-validar>
                    @csrf
                    <p class="texto-suave" style="margin:0 0 .75rem">
                        Califique en palabras cada uno de los 7 factores reglamentarios de desempeño durante los 6 meses de práctica continua. La evaluación es única y no se puede modificar tras enviarla.
                    </p>
                    @error('evaluacion')<div class="alerta alerta-error" role="alert" style="margin-bottom:.75rem">{{ $message }}</div>@enderror
                    
                    <ul class="tp-criterios">
                        @foreach ($criterios as $clave => $etiqueta)
                            <li class="tp-criterio" style="display:flex; flex-direction:column; gap:0.25rem; margin-bottom:0.75rem;">
                                <label style="font-weight:600; font-size:0.85rem; color:#374151;">{{ $etiqueta }} *</label>
                                <select name="{{ $clave }}" required style="width:100%; padding:0.45rem; font-size:0.9rem; border:1px solid #d1d5db; border-radius:4px;" data-criterio>
                                    <option value="">-- Seleccionar calificación en palabras --</option>
                                    @foreach ($opcionesNotas as $cClave => $opc)
                                        <option value="{{ $cClave }}" @selected(old($clave) === $cClave)>
                                            {{ $opc['nombre'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </li>
                        @endforeach
                    </ul>

                    <div style="margin-top:1rem">
                        <x-campo name="observaciones" label="Comentarios generales y fundamentación" type="textarea" maxlength="3000" ayuda="Opcional. Máximo 3000 caracteres." />
                    </div>

                    <div class="tp-resultado" aria-live="polite" style="margin-top:1.25rem;">
                        <div class="tp-resultado-fila">
                            <div class="tp-promedio">
                                <span data-concepto-general style="font-size:1.15rem; font-weight:700;">—</span>
                                <small data-concepto-ayuda>Complete los 7 criterios</small>
                            </div>
                            <span class="badge badge-neutro tp-veredicto" data-veredicto>Sin resultado</span>
                        </div>
                        <div style="margin-top:1rem;">
                            <x-boton type="submit" data-enviar disabled style="width:100%;">
                                Emitir Evaluación de Primera Instancia
                            </x-boton>
                        </div>
                    </div>
                </form>

                <script>
                (function () {
                    const form = document.getElementById('form-encuesta');
                    if (!form) return;
                    const campos = [...form.querySelectorAll('[data-criterio]')];
                    const el = (s) => form.querySelector(s);

                    const escala = {
                        'SOBRESALIENTE': { valor: 7.0, nombre: 'Sobresaliente' },
                        'BUENA': { valor: 5.0, nombre: 'Buena' },
                        'REGULAR': { valor: 4.0, nombre: 'Regular' },
                        'MALA': { valor: 3.0, nombre: 'Mala' }
                    };

                    function conceptoTexto(prom) {
                        if (prom >= 6.0) return 'Sobresaliente';
                        if (prom >= 5.0) return 'Buena';
                        if (prom >= 4.0) return 'Regular';
                        return 'Mala';
                    }

                    function actualizar() {
                        const vals = campos.map(c => escala[c.value] ? escala[c.value].valor : null);
                        const llenos = vals.filter(v => v !== null);
                        const completo = llenos.length === campos.length;

                        const prom = llenos.length ? (llenos.reduce((a, b) => a + b, 0) / llenos.length) : null;
                        const conc = prom !== null ? conceptoTexto(prom) : '—';

                        el('[data-concepto-general]').textContent = conc;
                        el('[data-concepto-ayuda]').textContent = completo 
                            ? 'Concepto institucional propuesto: ' + conc 
                            : (llenos.length ? 'Faltan ' + (campos.length - llenos.length) + ' criterios' : 'Complete los 7 criterios');

                        const v = el('[data-veredicto]');
                        if (completo) {
                            const ok = prom >= 4.0;
                            v.textContent = ok ? 'HA APROBADO' : 'NO HA APROBADO';
                            v.className = 'badge tp-veredicto ' + (ok ? 'badge-aprobado' : 'badge-reprobado');
                        } else {
                            v.textContent = 'Sin resultado';
                            v.className = 'badge badge-neutro tp-veredicto';
                        }
                        el('[data-enviar]').disabled = !completo;
                    }

                    form.addEventListener('change', actualizar);
                    actualizar();
                })();
                </script>
            @endif
        </x-tarjeta>
    @endif
</div>
@endsection
