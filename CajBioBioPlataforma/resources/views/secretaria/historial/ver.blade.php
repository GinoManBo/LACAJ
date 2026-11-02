@extends('layouts.portal', ['migas' => [['Historial', route('secretaria.historial')], ['Detalle']]])
@section('titulo', 'Historial de '.$postulante->nombre.' '.$postulante->apellido)
@section('contenido')
@php
    $c = $practica->consultorio; $a = $practica->abogado?->usuario;
    $cancelada = $practica->estado === 'cancelada'; $noInicio = $cancelada && $practica->cancelacion_antes_de_iniciar;
    $anio = ['quinto' => 'Quinto año', 'egresado' => 'Egresado/a'][$postulante->anio_carrera] ?? $postulante->anio_carrera;
@endphp
<div class="sec-cabecera">
    <div>
        <div class="sec-titulo-ficha"><h1 class="titulo-pagina">{{ $postulante->nombre }} {{ $postulante->apellido }}</h1><x-badge :estado="$practica->estado" />@if ($noInicio)<span class="sec-tag sec-tag-no-inicio">NO INICIÓ</span>@endif</div>
        <p class="sec-subtitulo">{{ $postulante->universidad }} · {{ $c->nombre }}</p>
    </div>
    <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
        @if ($puedeRemitir)
            <form action="{{ route('secretaria.historial.remitir_biobio', $practica->id) }}" method="POST" onsubmit="return confirm('¿Cerrar la competencia regional y remitir el expediente visado por el Director Regional a Jurídica Central de Biobío (Concepción)? Una vez remitido, la región ya no podrá modificarlo y quedará en modo solo lectura.');">
                @csrf
                <x-boton tipo="primario">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.4rem;"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                    Cerrar Competencia Regional y Remitir a Jurídica Biobío
                </x-boton>
            </form>
        @endif
        <x-boton tipo="secundario" :href="route('secretaria.historial')">@include('secretaria._icono', ['n' => 'volver']) Volver al historial</x-boton>
    </div>
</div>

@if ($esSoloLectura)
    <div class="alerta alerta-info" role="note" style="margin:1rem 0;background:var(--azul-50);border:1px solid var(--azul-200);border-radius:.5rem;padding:.75rem 1rem;display:flex;gap:.75rem;align-items:flex-start;">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--azul-700);flex-shrink:0;margin-top:.15rem;"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        <div>
            <strong style="color:var(--azul-900)">Competencia territorial regional cerrada (Modo Solo Lectura)</strong>
            <div style="font-size:.88rem;color:var(--azul-800);margin-top:.2rem;">Este expediente fue remitido a la Secretaría Jurídica Central de Biobío (Concepción){{ $practica->remitida_at ? ' el '.$practica->remitida_at->format('d-m-Y H:i') : '' }}. La Secretaría Regional ya no tiene competencia para alterar notas ni resoluciones finales.</div>
            @if ($practica->estado === 'nivelada_biobio')
                <div style="margin-top:.35rem;font-size:.85rem;color:var(--verde-800);font-weight:600;">Nota nivelada de oficio a Buena (5.0) en Jurídica Biobío. Justificación: {{ $practica->justificacion_nivelacion }}</div>
            @elseif ($practica->estado === 'rechazada_sancion')
                <div style="margin-top:.35rem;font-size:.85rem;color:var(--rojo-800);font-weight:600;">Rechazada por Jurídica Biobío por causal disciplinaria fundada: {{ $practica->causal_rechazo_sancion }}</div>
            @elseif ($practica->estado === 'cerrada_historial')
                <div style="margin-top:.35rem;font-size:.85rem;color:var(--azul-900);font-weight:600;">Expediente cerrado y despachado formalmente al postulante por Jurídica Biobío{{ $practica->despachada_at ? ' el '.$practica->despachada_at->format('d-m-Y') : '' }}.</div>
            @endif
        </div>
    </div>
@endif

<div class="sec-pestanas" role="tablist" aria-label="Secciones de la ficha">
    @foreach (['resumen' => 'Resumen', 'documentos' => 'Documentos', 'calificaciones' => 'Calificaciones'] as $k => $t)
        <button type="button" role="tab" class="sec-pestana" id="tab-{{ $k }}" aria-controls="panel-{{ $k }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? 0 : -1 }}">{{ $t }}</button>
    @endforeach
</div>

<section class="sec-panel" role="tabpanel" id="panel-resumen" aria-labelledby="tab-resumen">
    <div class="sec-columna">
        @if ($cancelada)
            <div class="alerta alerta-error" role="note">
                @include('secretaria._icono', ['n' => 'alerta'])
                <div>
                    <strong>Práctica cancelada{{ $practica->cancelacion_fecha ? ' el '.$practica->cancelacion_fecha->format('d-m-Y') : '' }}, {{ $noInicio ? 'antes de iniciar' : 'durante la práctica' }}.</strong>
                    @if ($motivoCancelacion)<div>Motivo: {{ $motivoCancelacion }}</div>@endif
                    @if ($practica->cancelacion_detalle)<div style="white-space:pre-line">{{ $practica->cancelacion_detalle }}</div>@endif
                    <div style="margin-top:.5rem"><x-boton tipo="secundario" tam="sm" :href="route('secretaria.cancelacion.documento', $practica->id)">@include('secretaria._icono', ['n' => 'descargar']) Descargar documento de cancelación</x-boton></div>
                </div>
            </div>
        @endif
        <div class="sec-dos">
            <x-tarjeta titulo="Postulante">
                <dl class="datos">
                    <div><dt>RUT</dt><dd>{{ $postulante->rut }}</dd></div>
                    <div><dt>Año de carrera</dt><dd>{{ $anio }}</dd></div>
                    <div><dt>Correo</dt><dd>{{ $postulante->email }}</dd></div>
                    <div><dt>Teléfono</dt><dd>{{ $postulante->telefono }}</dd></div>
                    <div class="ancho-completo"><dt>Domicilio</dt><dd>{{ $postulante->direccion }}, {{ $postulante->comuna?->nombre }}</dd></div>
                </dl>
            </x-tarjeta>
            <x-tarjeta titulo="Práctica">
                <dl class="datos">
                    <div class="ancho-completo"><dt>Consultorio</dt><dd>{{ $c->nombre }}</dd></div>
                    <div class="ancho-completo"><dt>Abogado tutor</dt><dd>{{ $a ? $a->nombre.' '.$a->apellido : 'Sin asignar' }}</dd></div>
                    <div><dt>Inicio</dt><dd>{{ $practica->fecha_inicio->format('d-m-Y') }}</dd></div>
                    <div><dt>Término</dt><dd>{{ $practica->fecha_termino->format('d-m-Y') }}</dd></div>
                    <div><dt>Duración</dt><dd>{{ $estadistica['total'] }} días</dd></div>
                </dl>
            </x-tarjeta>
        </div>
    </div>
</section>

<section class="sec-panel" role="tabpanel" id="panel-documentos" aria-labelledby="tab-documentos" hidden>
    <x-tarjeta titulo="Documentos">@include('secretaria._documentos', ['documentosCedula' => $documentosCedula, 'documentosUniversitarios' => $documentosUniversitarios, 'faltaCedula' => false])</x-tarjeta>
</section>

<section class="sec-panel" role="tabpanel" id="panel-calificaciones" aria-labelledby="tab-calificaciones" hidden>
    @if (! $evaluacion)
        <x-estado-vacio titulo="La práctica no se completó: no hay evaluación registrada" texto="Las calificaciones se registran al finalizar la práctica." />
    @else
        <div class="sec-columna">
            <x-tarjeta acento>
                <div class="sec-final">
                    <div>
                        @if ($conceptoFinal)<strong style="font:700 1.8rem var(--fuente-titulo);color:var(--azul-900)">{{ $conceptoFinal }}</strong>@endif
                        <div class="indicador-etiqueta">Calificación Institucional</div>
                    </div>
                    <div>
                        <div style="margin-top:.4rem"><x-badge :estado="$evaluacion->aprobado ? 'activa' : 'cancelada'" :texto="$evaluacion->aprobado ? 'HA APROBADO' : 'NO HA APROBADO'" /></div>
                    </div>
                </div>
            </x-tarjeta>
            @if (count($criterios))
                <x-tarjeta titulo="Criterios evaluados">
                    <ul class="sec-criterios">
                        @foreach ($criterios as $cr)
                            <li><span>{{ $cr['etiqueta'] }}</span><span class="sec-nota-valor" style="font-weight:700;color:var(--azul-900)">{{ $cr['concepto'] }}</span></li>
                        @endforeach
                    </ul>
                </x-tarjeta>
            @endif
            @if ($evaluacion->observaciones)
                <x-tarjeta titulo="Observaciones del tutor"><p style="margin:0;white-space:pre-line">{{ $evaluacion->observaciones }}</p></x-tarjeta>
            @endif
        </div>
    @endif
</section>

<script>
(() => {
    const tabs = [...document.querySelectorAll('[role=tab]')];
    const abrir = (t, foco) => {
        tabs.forEach((x) => {
            const on = x === t;
            x.setAttribute('aria-selected', on); x.tabIndex = on ? 0 : -1;
            document.getElementById(x.getAttribute('aria-controls')).hidden = !on;
        });
        if (foco) t.focus();
        history.replaceState(null, '', '#' + t.id.replace('tab-', ''));
    };
    tabs.forEach((t, i) => {
        t.addEventListener('click', () => abrir(t));
        t.addEventListener('keydown', (e) => {
            const d = { ArrowRight: 1, ArrowLeft: -1 }[e.key];
            if (d) { e.preventDefault(); abrir(tabs[(i + d + tabs.length) % tabs.length], true); }
        });
    });
    const inicial = tabs.find((t) => '#' + t.id.replace('tab-', '') === location.hash);
    if (inicial) abrir(inicial);
})();
</script>
@endsection
