@extends('layouts.publico')
@section('titulo', 'Consultorio y fecha')

@php
    $sel = collect(old('consultorios', $seleccionados))->map(fn ($x) => (int) $x)->values();
    $fechaSel = old('fecha_inicio', $borrador['fecha_inicio'] ?? '');
    $errCons = $errors->first('consultorios') ?: $errors->first('consultorios.*');
    $errFecha = $errors->first('fecha_inicio');
@endphp

@section('contenido')
<x-postulacion.marco :n="$n" :pasos="$pasos" :permitido="$permitido" titulo="Consultorio y fecha de inicio" bajada="Elija los consultorios en el orden en que le gustaría practicar y la fecha en que podría comenzar.">
    <form method="POST" action="{{ route('postulacion.guardar', 4) }}" data-paso-consultorios novalidate class="post-form">
        @csrf

        <fieldset class="bloque">
            <legend>Consultorios por orden de preferencia</legend>
            <div class="campo {{ $errCons ? 'tiene-error' : '' }}" data-grupo-obligatorio="Seleccione al menos un consultorio.">
                <p class="bloque-nota texto-suave" id="cons-ayuda">Toque un consultorio para agregarlo (máximo 3 consultorios): el primero que marque será su primera preferencia. Para cambiar el orden, quite uno y vuelva a agregarlo.</p>

                <div class="cons-orden" data-orden aria-live="polite">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
                        <span style="font-weight:600;font-size:.9rem;color:var(--tinta-fuerte);">Consultorios seleccionados:</span>
                        <span class="sec-tag" data-contador-cons style="font-weight:700;font-size:.8rem;">0 de 3 elegidos</span>
                    </div>
                    <p class="cons-orden-vacio" data-orden-vacio>Aún no ha elegido ningún consultorio.</p>
                    <ol class="cons-orden-lista" data-orden-lista></ol>
                </div>

                <div class="cons-aviso-max" data-aviso-max style="display:none;background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #ef4444;color:#991b1b;padding:.75rem 1rem;border-radius:.375rem;font-size:.88rem;margin-bottom:.75rem;" role="alert">
                    <strong>Límite máximo alcanzado:</strong> Puede elegir como máximo 3 consultorios por orden de preferencia. Para agregar este consultorio, primero quite uno de los ya seleccionados en la lista de arriba.
                </div>

                <div class="cons-buscar">
                    <label for="cons-buscar" class="sr-only">Buscar consultorio por nombre o comuna</label>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/></svg>
                    <input type="search" id="cons-buscar" class="campo-control" placeholder="Buscar por nombre o comuna" autocomplete="off" data-buscar>
                </div>

                <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;margin-bottom:.75rem;font-size:.82rem;color:var(--tinta-suave);" aria-label="Leyenda de disponibilidad de cupos">
                    <span>Disponibilidad de cupos:</span>
                    <x-badge estado="muchos" />
                    <x-badge estado="pocos" />
                    <x-badge estado="sin" />
                </div>

                <ul class="cons-lista" data-lista aria-describedby="cons-ayuda">
                    @foreach ($consultorios as $c)
                        <li data-item data-buscable="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($c->nombre.' '.$c->comuna?->nombre.' '.$c->direccion)) }}">
                            <button type="button" class="cons-item" data-id="{{ $c->id }}" aria-pressed="false">
                                <span class="cons-marca" aria-hidden="true"></span>
                                <span class="cons-datos">
                                    <strong data-nombre>{{ $c->nombre }}</strong>
                                    <span>{{ $c->comuna?->nombre }}@if ($c->direccion) · {{ $c->direccion }}@endif</span>
                                    <span class="cons-cupos" style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-top:.2rem;">
                                        <span><strong>{{ $c->ocupados }}</strong> de {{ $c->capacidad }} cupos ocupados ({{ $c->libres }} {{ $c->libres === 1 ? 'libre' : 'libres' }})</span>
                                        <x-badge :estado="$c->disponibilidad" />
                                    </span>
                                    @if ($c->id === $masCercanoId)<x-badge estado="informativo" texto="MÁS CERCANO A SU DOMICILIO" />@endif
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ul>
                <p class="cons-sin-resultados" data-sin-resultados hidden>Ningún consultorio coincide con la búsqueda.</p>

                <div data-hidden-cons>
                    @foreach ($sel as $id)<input type="hidden" name="consultorios[]" value="{{ $id }}">@endforeach
                </div>
                <p class="campo-error" role="alert">{{ $errCons }}</p>
            </div>
        </fieldset>

        <fieldset class="bloque">
            <legend>Fecha de inicio</legend>
            <div class="campo {{ $errFecha ? 'tiene-error' : '' }}" data-grupo-obligatorio="Seleccione la fecha de inicio.">
                <p class="bloque-nota texto-suave">Solo se puede comenzar en días hábiles. Los fines de semana, los feriados y las fechas pasadas no están disponibles.</p>
                <div class="calendario" data-calendario data-hoy="{{ $hoy }}" data-valor="{{ $fechaSel }}">
                    <div class="cal-nav">
                        <button type="button" class="cal-flecha" data-cal-prev aria-label="Mes anterior"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg></button>
                        <h3 class="cal-mes" data-cal-titulo aria-live="polite"></h3>
                        <button type="button" class="cal-flecha" data-cal-next aria-label="Mes siguiente"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></button>
                    </div>
                    <div class="cal-semana" aria-hidden="true"><span>lun</span><span>mar</span><span>mié</span><span>jue</span><span>vie</span><span>sáb</span><span>dom</span></div>
                    <div class="cal-dias" data-cal-dias role="grid"></div>
                    <p class="cal-leyenda"><span class="cal-muestra-feriado">12</span> Feriado, no disponible</p>
                    <script type="application/json" data-cal-feriados>@json(array_values($feriados))</script>
                </div>
                <p class="cal-elegida" data-cal-elegida role="status">@if ($fechaSel) @else Aún no ha elegido una fecha. @endif</p>
                <input type="hidden" name="fecha_inicio" value="{{ $fechaSel }}" data-cal-valor>
                <p class="campo-error" role="alert">{{ $errFecha }}</p>
            </div>
        </fieldset>

        <div class="acciones-paso">
            <x-boton tipo="secundario" :href="route('postulacion.paso', 3)">Volver</x-boton>
            <x-boton type="submit">Revisar postulación</x-boton>
        </div>
    </form>
</x-postulacion.marco>
@endsection
