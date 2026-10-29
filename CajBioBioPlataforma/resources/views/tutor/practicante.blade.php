@extends('layouts.portal', ['migas' => [['Mis practicantes', route('tutor.inicio')], [$postulante->nombre_completo]]])
@section('titulo', $postulante->nombre_completo)

@section('contenido')
@php
    $etiquetasEstado = ['por_iniciar' => 'Por iniciar', 'activa' => 'En curso', 'por_terminar' => 'Por terminar', 'finalizada' => 'Finalizada', 'cancelada' => 'Cancelada'];
    $fmt = fn ($f) => $f?->locale('es')->translatedFormat('j \d\e F \d\e Y');
    $nota = fn ($n) => number_format((float) $n, 1, ',', '');
    $kb = fn ($b) => $b >= 1048576 ? number_format($b / 1048576, 1, ',', '').' MB' : max(1, round($b / 1024)).' KB';
@endphp

<header class="tp-cabecera tp-entra">
    <div>
        <h1 class="titulo-pagina">{{ $postulante->nombre_completo }}</h1>
        <p class="texto-suave">{{ $postulante->universidad }} · {{ $practica->consultorio->nombre ?? 'Sin consultorio' }}</p>
    </div>
    <div class="tp-meta">
        <x-badge :estado="$estadoMostrado" :texto="$etiquetasEstado[$estadoMostrado] ?? null" />
        <x-boton tipo="secundario" :href="route('tutor.inicio')">Volver al listado</x-boton>
    </div>
</header>

@if ($porTerminar)
    <div class="alerta alerta-aviso tp-entra" role="status" style="--i:1">
        <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4M12 17h.01M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
        <div>
            <strong>Proximidad al cierre de práctica</strong>
            <div>{{ $diasRestantes === 0 ? 'La práctica termina hoy.' : 'Quedan '.$diasRestantes.($diasRestantes === 1 ? ' día' : ' días').' para el término.' }} Recuerde pedir el informe de cierre y completar la encuesta final de desempeño.</div>
        </div>
    </div>
@elseif ($practica->estado === 'activa' && $diasRestantes < 0)
    <div class="alerta alerta-error tp-entra" role="status" style="--i:1">
        <div><strong>La fecha de término ya pasó.</strong> Complete la encuesta de desempeño para cerrar la práctica.</div>
    </div>
@endif

<div class="tp-dos">
    <div class="tp-pila">
        <x-tarjeta titulo="Ficha del practicante" class="tp-entra" style="--i:2">
            <dl class="tp-ficha">
                <div><dt>RUT</dt><dd>{{ $postulante->rut }}</dd></div>
                <div><dt>Teléfono</dt><dd>{{ $postulante->telefono ?: '—' }}</dd></div>
                <div class="ancho-completo" style="grid-column:1/-1"><dt>Correo</dt><dd><a href="mailto:{{ $postulante->email }}">{{ $postulante->email }}</a></dd></div>
                <div><dt>Universidad</dt><dd>{{ $postulante->universidad }}</dd></div>
                <div><dt>Consultorio</dt><dd>{{ $practica->consultorio->nombre ?? '—' }}@if ($practica->consultorio?->direccion)<br><span class="texto-suave">{{ $practica->consultorio->direccion }}</span>@endif</dd></div>
                <div><dt>Inicio</dt><dd>{{ $fmt($practica->fecha_inicio) }}</dd></div>
                <div><dt>Término</dt><dd>{{ $fmt($practica->fecha_termino) }}</dd></div>
                @if ($postulante->discapacidad)
                    <div style="grid-column:1/-1"><dt>Discapacidad</dt><dd>{{ $postulante->discapacidad_descripcion ?: 'Declarada, sin detalle.' }}</dd></div>
                @endif
            </dl>
        </x-tarjeta>

        <x-tarjeta titulo="Informe de práctica" class="tp-entra" style="--i:3">
            @if ($informe)
                <div class="tp-archivo">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8zM14 3v5h5M9 13h6M9 17h6"/></svg>
                    <div>
                        <strong>{{ $informe->nombre_original }}</strong>
                        <span>{{ $kb($informe->tamano_bytes) }} · recibido el {{ $fmt($informe->created_at) }}</span>
                    </div>
                    <x-boton tipo="secundario" tam="sm" :href="route('tutor.informe.descargar', $practica->id)">Ver informe</x-boton>
                </div>
            @else
                <div class="tp-bloqueo">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8zM14 3v5h5"/></svg>
                    <div><strong>Informe pendiente</strong>El practicante todavía no sube su informe de práctica. Le avisaremos cuando llegue.</div>
                </div>
            @endif
        </x-tarjeta>
    </div>

    <x-tarjeta titulo="Encuesta de desempeño" class="tp-entra" style="--i:3" id="encuesta">
        @if ($evaluacion)
            @php $ok = $evaluacion->aprobado; @endphp
            <div class="tp-lectura">
                <div class="tp-sello {{ $ok ? 'tp-sello-si' : 'tp-sello-no' }}">
                    <div>
                        <strong>{{ $ok ? 'HA APROBADO' : 'NO HA APROBADO' }}</strong>
                        <span>Promedio {{ number_format($evaluacion->promedio, 2, ',', '') }}{{ $concepto ? ' · '.$concepto : '' }}</span>
                    </div>
                </div>
                <div>
                    @foreach ($criterios as $clave => $etiqueta)
                        <div class="tp-lectura-fila"><span>{{ $etiqueta }}</span><b>{{ $nota($evaluacion->$clave) }}</b></div>
                    @endforeach
                </div>
                @if ($evaluacion->observaciones)
                    <div><strong>Comentarios generales</strong><p class="texto-suave" style="margin:.25rem 0 0;white-space:pre-line">{{ $evaluacion->observaciones }}</p></div>
                @endif
                <p class="texto-suave" style="margin:0;font-size:.85rem">La evaluación es única y ya no puede modificarse.</p>
            </div>
        @elseif ($bloqueoEvaluacion)
            <div class="tp-bloqueo" role="status">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                <div><strong>Encuesta bloqueada</strong>{{ $bloqueoEvaluacion }}</div>
            </div>
            <fieldset disabled style="border:0;padding:0;margin:1rem 0 0;opacity:.5" aria-hidden="true">
                <ul class="tp-criterios">
                    @foreach ($criterios as $clave => $etiqueta)
                        <li class="tp-criterio"><span>{{ $etiqueta }}</span><input class="campo-control" disabled placeholder="1,0 a 7,0" tabindex="-1"></li>
                    @endforeach
                </ul>
            </fieldset>
        @else
            <form method="POST" action="{{ route('tutor.evaluacion.guardar', $practica->id) }}" id="form-encuesta" novalidate data-sin-validar>
                @csrf
                <p class="texto-suave" style="margin:0 0 .75rem">Califique cada criterio de 1,0 a 7,0 (con un decimal). La aprobación es con promedio 4,0 o más. La evaluación es única y no se puede modificar después de enviarla.</p>
                @error('evaluacion')<div class="alerta alerta-error" role="alert" style="margin-bottom:.75rem">{{ $message }}</div>@enderror
                <ul class="tp-criterios">
                    @foreach ($criterios as $clave => $etiqueta)
                        <li class="tp-criterio">
                            <x-campo :name="$clave" :label="$etiqueta" type="number" obligatorio step="0.1" min="1" max="7" inputmode="decimal" placeholder="1,0 - 7,0" data-criterio />
                        </li>
                    @endforeach
                </ul>
                <div style="margin-top:1rem">
                    <x-campo name="observaciones" label="Comentarios generales" type="textarea" maxlength="3000" ayuda="Opcional. Máximo 3000 caracteres." />
                </div>

                <div class="tp-resultado" aria-live="polite">
                    <div class="tp-resultado-fila">
                        <div class="tp-promedio"><span data-promedio>—</span><small data-concepto>Complete los 7 criterios</small></div>
                        <span class="badge badge-neutro tp-veredicto" data-veredicto>Sin resultado</span>
                    </div>
                    <div class="tp-regla" data-regla role="img" aria-label="Promedio sobre escala de 1,0 a 7,0; línea de aprobación en 4,0">
                        <div class="tp-regla-relleno" data-relleno></div>
                        <div class="tp-regla-corte" style="left:50%" title="4,0"></div>
                    </div>
                    <div class="tp-regla-escala" aria-hidden="true"><span>1,0</span><span>4,0 aprueba</span><span>7,0</span></div>
                    <x-boton type="submit" data-enviar disabled>Enviar evaluación</x-boton>
                </div>
            </form>
            <script>
            (function () {
                const form = document.getElementById('form-encuesta');
                if (!form) return;
                const campos = [...form.querySelectorAll('[data-criterio]')];
                const el = (s) => form.querySelector(s);
                const fmt = (n) => n.toFixed(2).replace('.', ',');
                const concepto = (n) => n >= 6.5 ? 'Sobresaliente' : n >= 5.5 ? 'Muy bueno' : n >= 4.5 ? 'Bueno' : n >= 4 ? 'Suficiente' : 'Deficiente';
                const valor = (c) => { const v = parseFloat(String(c.value).replace(',', '.')); return Number.isFinite(v) && v >= 1 && v <= 7 && Math.abs(Math.round(v * 10) / 10 - v) < 1e-9 ? v : null; };
                function actualizar() {
                    const vals = campos.map(valor);
                    campos.forEach((c, i) => c.closest('.tp-criterio').classList.toggle('tiene-nota', vals[i] !== null));
                    const llenos = vals.filter((v) => v !== null);
                    const completo = llenos.length === campos.length;
                    const prom = llenos.length ? Math.round((llenos.reduce((a, b) => a + b, 0) / llenos.length) * 100) / 100 : null;
                    el('[data-promedio]').textContent = prom === null ? '—' : fmt(prom);
                    el('[data-concepto]').textContent = completo ? concepto(prom) : (llenos.length ? 'Faltan ' + (campos.length - llenos.length) + ' criterios' : 'Complete los 7 criterios');
                    const v = el('[data-veredicto]'); const regla = el('[data-regla]');
                    if (completo) {
                        const ok = prom >= 4;
                        v.textContent = ok ? 'HA APROBADO' : 'NO HA APROBADO';
                        v.className = 'badge tp-veredicto ' + (ok ? 'badge-aprobado' : 'badge-reprobado');
                        regla.dataset.estado = ok ? 'si' : 'no';
                    } else {
                        v.textContent = 'Sin resultado'; v.className = 'badge badge-neutro tp-veredicto'; delete regla.dataset.estado;
                    }
                    el('[data-relleno]').style.transform = 'scaleX(' + (prom === null ? 0 : (prom - 1) / 6) + ')';
                    el('[data-enviar]').disabled = !completo;
                }
                form.addEventListener('input', actualizar);
                form.addEventListener('change', actualizar);
                actualizar();
            })();
            </script>
        @endif
    </x-tarjeta>
</div>
@endsection
