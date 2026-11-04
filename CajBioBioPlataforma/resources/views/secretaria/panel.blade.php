@extends('layouts.portal', ['migas' => [['Panel general']]])
@section('titulo', 'Panel general')
@section('contenido')
<div class="sec-cabecera">
    <div>
        <h1 class="titulo-pagina">{{ $esBiobioCentral ? 'Panel de Secretaría · Sede Central Biobío' : 'Panel de secretaría' }}</h1>
        <p class="texto-suave">
            @if ($esBiobioCentral)
                Gestión Operativa Regional (Biobío) y Supervisión Central de Jurídica (Alcance Nacional - Concepción)
            @else
                Región de {{ $region?->nombre ?? 'su jurisdicción' }}
            @endif
        </p>
    </div>
</div>

<h2 style="font-size:1.05rem;font-weight:700;color:var(--azul-900);margin:1.25rem 0 .5rem 0;display:flex;align-items:center;gap:.4rem;">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
    Operación Regional ({{ $region?->nombre ?? 'Biobío' }})
</h2>
<div class="rejilla-indicadores">
    <x-indicador :valor="$pendientes" etiqueta="Postulaciones pendientes" detalle="Esperan su revisión local" :href="route('secretaria.postulaciones')" :critico="$pendientes > 0" />
    <x-indicador :valor="$activas" etiqueta="Prácticas activas" detalle="En curso en consultorios" :href="route('secretaria.practicas')" />
    <x-indicador :valor="$porTerminarCount" etiqueta="Por terminar" detalle="Cierran en los próximos 14 días" :href="route('secretaria.practicas')" />
</div>

@if (!empty($esBiobioCentral) && !empty($centralTotales))
<div style="margin-top:2rem;padding:1.25rem;background:linear-gradient(135deg,#f8fafc 0%,#eff6ff 100%);border:1px solid #bfdbfe;border-radius:.75rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;">
        <div>
            <h2 style="font-size:1.1rem;font-weight:700;color:var(--azul-900);margin:0;display:flex;align-items:center;gap:.5rem;">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                Atribuciones de Jurídica Central (Nacional)
            </h2>
            <p style="font-size:.85rem;color:var(--texto-suave);margin:.2rem 0 0 0;">
                Consolidación de expedientes de las 6 regiones, nivelación institucional de notas y despacho oficial tras FirmaGob.
            </p>
        </div>
        <div style="display:flex;gap:.5rem;">
            <x-boton tipo="secundario" tam="sm" :href="route('secretaria.juridica.postulaciones')">
                Supervisión Nacional
            </x-boton>
            <x-boton tipo="primario" tam="sm" :href="route('secretaria.juridica.index')">
                Bandeja Nacional de Expedientes
            </x-boton>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.75rem;">
        <a href="{{ route('secretaria.juridica.index', ['estado' => 'remitida_biobio']) }}" style="text-decoration:none;background:var(--blanco);padding:.85rem;border-radius:.5rem;border:1px solid #e2e8f0;display:block;">
            <div style="font-size:.78rem;font-weight:600;color:var(--texto-suave);text-transform:uppercase;">Recibidos de Regiones</div>
            <div style="font-size:1.6rem;font-weight:700;color:var(--azul-800);margin-top:.2rem;">{{ $centralTotales['recibidos'] }}</div>
            <div style="font-size:.75rem;color:var(--texto-suave);margin-top:.2rem;">Pendientes de nivelación</div>
        </a>
        <a href="{{ route('secretaria.juridica.index', ['estado' => 'nivelada_biobio']) }}" style="text-decoration:none;background:var(--blanco);padding:.85rem;border-radius:.5rem;border:1px solid #e2e8f0;display:block;">
            <div style="font-size:.78rem;font-weight:600;color:var(--texto-suave);text-transform:uppercase;">Listos para Despacho</div>
            <div style="font-size:1.6rem;font-weight:700;color:var(--verde-800);margin-top:.2rem;">{{ $centralTotales['por_despachar'] }}</div>
            <div style="font-size:.75rem;color:var(--texto-suave);margin-top:.2rem;">Con FirmaGob del Director General</div>
        </a>
        <a href="{{ route('secretaria.juridica.postulaciones') }}" style="text-decoration:none;background:var(--blanco);padding:.85rem;border-radius:.5rem;border:1px solid #e2e8f0;display:block;">
            <div style="font-size:.78rem;font-weight:600;color:var(--texto-suave);text-transform:uppercase;">Postulaciones Nacionales</div>
            <div style="font-size:1.6rem;font-weight:700;color:#6b21a8;margin-top:.2rem;">{{ $centralTotales['postulaciones_nacionales'] }}</div>
            <div style="font-size:.75rem;color:var(--texto-suave);margin-top:.2rem;">Supervisión de las 6 regiones</div>
        </a>
    </div>
</div>
@endif

<div class="sec-dos" style="margin-top:1.5rem;">
    <x-tarjeta titulo="Postulaciones pendientes ({{ $region?->nombre ?? 'Biobío' }})">
        <x-slot:acciones><x-boton tipo="secundario" tam="sm" :href="route('secretaria.postulaciones')">Ver todas</x-boton></x-slot:acciones>
        @if ($ultimasPostulaciones->isEmpty())
            <x-estado-vacio titulo="Sin postulaciones pendientes" texto="Cuando llegue una nueva postulación a su región aparecerá aquí." />
        @else
            <ul class="sec-lista">
                @foreach ($ultimasPostulaciones as $p)
                    <li>
                        <div><a href="{{ route('secretaria.postulaciones.ver', $p->id) }}">{{ $p->postulante->nombre }} {{ $p->postulante->apellido }}</a><span class="sec-menor">{{ $p->postulante->universidad }}</span></div>
                        <span class="sec-menor sec-num">{{ $p->fecha_postulacion->format('d-m-Y') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-tarjeta>

    <x-tarjeta titulo="Prácticas por terminar ({{ $region?->nombre ?? 'Biobío' }})">
        <x-slot:acciones><x-boton tipo="secundario" tam="sm" :href="route('secretaria.practicas')">Ver todas</x-boton></x-slot:acciones>
        @if ($practicasPorTerminar->isEmpty())
            <x-estado-vacio titulo="Ninguna práctica por terminar" texto="Las prácticas próximas a cerrar se listarán en este espacio." />
        @else
            <ul class="sec-lista">
                @foreach ($practicasPorTerminar as $p)
                    <li>
                        <div><a href="{{ route('secretaria.practicas.ver', $p->id) }}">{{ $p->practicante->postulante->nombre }} {{ $p->practicante->postulante->apellido }}</a><span class="sec-menor">{{ $p->consultorio->nombre }}</span></div>
                        <span class="sec-menor sec-num">Termina {{ $p->fecha_termino->format('d-m-Y') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-tarjeta>
</div>
@endsection
