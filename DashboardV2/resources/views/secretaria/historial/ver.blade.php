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
    <x-boton tipo="secundario" :href="route('secretaria.historial')">@include('secretaria._icono', ['n' => 'volver']) Volver al historial</x-boton>
</div>

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
            <x-tarjeta titulo="Practicante">
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
                    <div><div class="indicador-valor">{{ $practica->nota_practica !== null ? number_format($practica->nota_practica, 1, ',', '') : '—' }}</div><div class="indicador-etiqueta">Nota de la práctica</div></div>
                    <div>
                        @if ($conceptoFinal)<strong style="font:700 1.4rem var(--fuente-titulo);color:var(--azul-900)">{{ $conceptoFinal }}</strong>@endif
                        <div style="margin-top:.4rem"><x-badge :estado="$evaluacion->aprobado ? 'activa' : 'cancelada'" :texto="$evaluacion->aprobado ? 'HA APROBADO' : 'NO HA APROBADO'" /></div>
                    </div>
                </div>
            </x-tarjeta>
            @if (count($criterios))
                <x-tarjeta titulo="Criterios evaluados">
                    <ul class="sec-criterios">
                        @foreach ($criterios as $cr)
                            <li><span>{{ $cr['etiqueta'] }}@if (! empty($cr['concepto']))<span class="sec-menor">{{ $cr['concepto'] }}</span>@endif</span><span class="sec-nota-valor">{{ is_numeric($cr['nota']) ? number_format($cr['nota'], 1, ',', '') : $cr['nota'] }}</span></li>
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
