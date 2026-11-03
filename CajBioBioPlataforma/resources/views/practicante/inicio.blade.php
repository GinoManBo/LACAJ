@extends('layouts.portal', ['migas' => [['Mi práctica']]])
@section('titulo', 'Mi práctica')

@section('contenido')
@if (! $practica)
    <header class="tp-cabecera tp-entra"><h1 class="titulo-pagina">Mi práctica</h1></header>
    <x-estado-vacio class="tp-entra" style="--i:1" titulo="Todavía no tiene una práctica asignada"
        texto="Cuando la secretaría regional confirme su práctica, aquí verá su consultorio, abogado tutor, calificaciones y documentos." />
@else
@php
    $etiquetasEstado = [
        'por_iniciar' => 'Por iniciar',
        'activa' => 'En curso',
        'por_terminar' => 'Por terminar',
        'finalizada' => 'Finalizada',
        'cancelada' => 'Cancelada',
        'remitida_biobio' => 'Remitida a Jurídica Central',
        'nivelada_biobio' => 'Nivelada por Jurídica',
        'rechazada_sancion' => 'Rechazada por sanción',
        'cerrada_historial' => 'Expediente cerrado / Historial',
    ];
    $fmt = fn ($f) => $f?->locale('es')->translatedFormat('j \d\e F \d\e Y');
    $total = max(1, $practica->fecha_inicio->diffInDays($practica->fecha_termino));
    $avance = max(0, min(100, round(now()->startOfDay()->diffInDays($practica->fecha_inicio->copy()->startOfDay(), false) * -100 / $total)));
    if (in_array($practica->estado, ['finalizada', 'remitida_biobio', 'nivelada_biobio', 'cerrada_historial'], true)) {
        $avance = 100;
    }
    $ordinal = [1 => '1ª calificación', 2 => '2ª calificación', 3 => '3ª calificación'];
@endphp

<section class="tp-hero tarjeta tarjeta-acento tp-entra" style="padding:1.25rem 1.5rem" aria-labelledby="titulo-practica">
    <div class="tp-cabecera">
        <div>
            <h1 id="titulo-practica" class="titulo-pagina">Hola, {{ explode(' ', $postulante->nombre_completo)[0] }}</h1>
            <p class="texto-suave">{{ $practica->consultorio->nombre ?? 'Consultorio por definir' }}@if ($practica->consultorio?->direccion) · {{ $practica->consultorio->direccion }}@endif</p>
        </div>
        <x-badge :estado="$estadoMostrado" :texto="$etiquetasEstado[$estadoMostrado] ?? null" />
    </div>
    <dl class="tp-ficha" style="grid-template-columns:repeat(auto-fit,minmax(14rem,1fr))">
        <div><dt>Abogado tutor</dt><dd>@if ($tutor)<strong>{{ $tutor->nombre_completo }}</strong><br><a href="mailto:{{ $tutor->email }}">{{ $tutor->email }}</a>@else<span class="texto-suave">Abogado tutor por asignar</span>@endif</dd></div>
        <div><dt>Inicio</dt><dd>{{ $fmt($practica->fecha_inicio) }}</dd></div>
        <div><dt>Término</dt><dd>{{ $fmt($practica->fecha_termino) }}</dd></div>
    </dl>
    <div>
        <div class="tp-linea" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $avance }}" aria-label="Avance del período de práctica"><span style="transform:scaleX({{ $avance / 100 }})"></span></div>
        <div class="tp-linea-fechas" aria-hidden="true"><span>{{ $practica->fecha_inicio->format('d-m-Y') }}</span><span>{{ $avance }}% del período</span><span>{{ $practica->fecha_termino->format('d-m-Y') }}</span></div>
    </div>
</section>

<div class="tp-dos tp-dos-igual">
    <x-tarjeta titulo="Calificaciones" class="tp-entra" style="--i:1">
        <ul class="tp-notas">
            @foreach ($calificaciones as $c)
                <li class="tp-nota">
                    <div>{{ $ordinal[$c['instancia']] }}
                        <small>{{ $c['obligatoria'] ? 'Obligatoria' : 'Opcional' }}</small></div>
                    @if ($c['nota'] !== null)
                        <span class="tp-nota-valor">{{ \App\Support\Notas::concepto($c['nota']) }}</span>
                    @else
                        <span class="tp-nota-vacia">{{ $c['obligatoria'] ? 'Pendiente — obligatoria' : 'Sin nota' }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="tp-totales">
            <x-indicador :valor="$notaPractica !== null ? \App\Support\Notas::concepto($notaPractica) : '—'" etiqueta="Calificación institucional" :detalle="$notaPractica === null ? 'Se calcula cuando su tutor evalúa' : null" />
            <x-indicador :valor="$notaPromedio !== null ? \App\Support\Notas::concepto($notaPromedio) : '—'" etiqueta="Promedio de instancias" :detalle="$notaPromedio === null ? 'Aún sin calificaciones' : null" />
        </div>
        @if ($conceptos)
            <h3 style="margin:1.5rem 0 0;font-size:1.05rem">Conceptos de la evaluación del tutor</h3>
            <ul class="tp-conceptos">
                @foreach ($conceptos as $c)
                    <li><span>{{ $c['criterio'] }}</span><b>{{ $c['concepto'] }}</b></li>
                @endforeach
            </ul>
        @endif
    </x-tarjeta>

    <x-tarjeta titulo="Informe de práctica" class="tp-entra" style="--i:2">
        <p class="texto-suave" style="margin:0 0 1rem">Al subir su informe se habilita la evaluación de su abogado tutor. Sin informe, la encuesta de desempeño permanece bloqueada.</p>
        @if ($informe)
            <div class="tp-archivo" style="margin-bottom:1rem">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8zM14 3v5h5M9 13h6M9 17h6"/></svg>
                <div><strong>{{ $informe->nombre_original }}</strong><span>Enviado el {{ $fmt($informe->created_at) }}</span></div>
                <x-boton tipo="secundario" tam="sm" :href="route('practicante.informe.descargar')">Descargar</x-boton>
            </div>
        @endif
        @if ($puedeSubirInforme)
            <form method="POST" action="{{ route('practicante.informe.subir') }}" enctype="multipart/form-data" style="display:grid;gap:1rem">
                @csrf
                <x-campo name="informe" type="file" :label="$informe ? 'Reemplazar informe' : 'Archivo del informe'" obligatorio accept=".pdf,.doc,.docx"
                    :ayuda="'PDF o Word (.doc, .docx). Máximo '.config('caj.max_upload_mb').' MB.'" data-mensaje="Seleccione el archivo del informe." />
                <div><x-boton type="submit">{{ $informe ? 'Reemplazar informe' : 'Subir informe' }}</x-boton></div>
            </form>
        @else
            <div class="tp-bloqueo"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                <div>El informe ya no puede modificarse porque la práctica fue evaluada o cerrada.</div></div>
        @endif
    </x-tarjeta>
</div>

<x-tarjeta titulo="Documentación" class="tp-entra" style="--i:3">
    <div class="tp-docs">
        @foreach ($documentos as $d)
            @if ($d['bloqueo'])
                <article class="tp-doc tp-doc-bloqueado" aria-label="{{ $d['titulo'] }}: bloqueado">
                    <div class="tp-meta"><h3>{{ $d['titulo'] }}</h3><x-badge estado="neutro" texto="Bloqueado" /></div>
                    <p>{{ $d['detalle'] }}</p>
                    <div class="tp-bloqueo"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg><div>{{ $d['bloqueo'] }}</div></div>
                </article>
            @else
                <article class="tp-doc" aria-label="{{ $d['titulo'] }}: disponible">
                    <div class="tp-meta"><h3>{{ $d['titulo'] }}</h3><x-badge estado="aprobado" texto="Disponible" /></div>
                    <p>{{ $d['detalle'] }}</p>
                    <x-boton :href="$d['ruta']">Descargar PDF</x-boton>
                </article>
            @endif
        @endforeach
    </div>
</x-tarjeta>
@endif
@endsection
