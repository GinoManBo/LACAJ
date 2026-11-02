@extends('layouts.portal', ['migas' => [['Consultorios', route('secretaria.consultorios')], [$consultorio->nombre]]])
@section('titulo', $consultorio->nombre)
@section('contenido')
@php $c = $consultorio; @endphp
<div class="sec-cabecera">
    <div>
        <div class="sec-titulo-ficha"><h1 class="titulo-pagina">{{ $c->nombre }}</h1><x-badge :estado="$c->disponibilidad" /></div>
        <p class="sec-subtitulo">{{ $c->direccion }}@if ($c->comuna), {{ $c->comuna->nombre }}@endif</p>
    </div>
    <x-boton tipo="secundario" :href="route('secretaria.consultorios')">@include('secretaria._icono', ['n' => 'volver']) Volver a consultorios</x-boton>
</div>

<div class="rejilla-indicadores">
    <x-indicador :valor="$c->capacidad" etiqueta="Capacidad" />
    <x-indicador :valor="$c->ocupados" etiqueta="Ocupados" />
    <x-indicador :valor="$c->libres" etiqueta="Libres" :critico="$c->libres === 0" />
</div>
<div class="sec-barra sec-barra-grande es-{{ $c->disponibilidad }}" role="img" aria-label="Ocupación {{ $c->porcentaje_ocupacion }}%"><i style="--p:{{ $c->porcentaje_ocupacion }}%"></i></div>

<div style="margin: 1.5rem 0;">
    <x-tarjeta titulo="Información de Contacto y Atención">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:1rem">
            <div>
                <strong style="display:block;font-size:0.8rem;color:#6b7280;text-transform:uppercase">Tipo de Unidad</strong>
                <span>{{ $c->tipo_unidad_texto }}</span>
            </div>
            <div>
                <strong style="display:block;font-size:0.8rem;color:#6b7280;text-transform:uppercase">Teléfono(s)</strong>
                <span>{{ $c->telefono ?: 'Sin teléfono registrado' }}</span>
            </div>
            <div>
                <strong style="display:block;font-size:0.8rem;color:#6b7280;text-transform:uppercase">Correo Electrónico</strong>
                <span>@if ($c->email)<a href="mailto:{{ $c->email }}" style="color:#0284c7">{{ $c->email }}</a>@else Sin correo registrado @endif</span>
            </div>
            <div style="grid-column:1/-1">
                <strong style="display:block;font-size:0.8rem;color:#6b7280;text-transform:uppercase">Horario de Atención</strong>
                <p style="margin:0.25rem 0 0;white-space:pre-line;font-size:0.9rem;color:#374151">{{ $c->horario_atencion ?: 'Sin horario especificado' }}</p>
            </div>
        </div>
    </x-tarjeta>
</div>

<h2 style="margin:.5rem 0 0;font-size:1.25rem">Postulantes asignados</h2>
@if ($asignados->isEmpty())
    <x-estado-vacio titulo="Sin postulantes asignados" texto="Cuando acepte una postulación a este consultorio, aparecerá aquí." />
@else
    <x-tabla :columnas="['Postulante', 'Universidad', 'Abogado Jefe', 'Estado', 'Término']" descripcion="Postulantes asignados al consultorio">
        @foreach ($asignados as $p)
            @php $po = $p->practicante->postulante; $a = $p->abogado?->usuario; $est = $p->estado_visible; @endphp
            <tr data-href="{{ route('secretaria.practicas.ver', $p->id) }}">
                <td><a href="{{ route('secretaria.practicas.ver', $p->id) }}"><strong>{{ $po->nombre }} {{ $po->apellido }}</strong></a></td>
                <td>{{ $po->universidad }}</td>
                <td>{{ $a ? $a->nombre.' '.$a->apellido : 'Sin asignar' }}</td>
                <td><x-badge :estado="$est" :texto="$est === 'por_terminar' ? 'Por terminar' : null" /></td>
                <td class="sec-num">{{ $p->fecha_termino->format('d-m-Y') }}</td>
            </tr>
        @endforeach
    </x-tabla>
@endif
@endsection
