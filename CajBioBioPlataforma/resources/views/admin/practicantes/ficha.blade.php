@extends('layouts.portal', ['migas' => [['Administración', route('admin.inicio')], ['Postulantes', route('admin.practicantes.index')], [$practica->practicante->postulante->nombre.' '.$practica->practicante->postulante->apellido]]])
@section('titulo', 'Ficha de postulante')
@php
    $po = $practica->practicante->postulante;
    $accionable = in_array($practica->estado, ['por_iniciar', 'activa'], true);
    $etiquetasEstado = [
        'por_iniciar' => 'Por iniciar',
        'activa' => 'En curso',
        'finalizada' => 'Finalizada',
        'remitida_biobio' => 'Remitida a Jurídica Central',
        'nivelada_biobio' => 'Nivelada en Jurídica',
        'rechazada_sancion' => 'Rechazada por sanción',
        'cerrada_historial' => 'Cerrada con FirmaGob',
        'cancelada' => 'Cancelada',
    ];
    $etiquetasMotivo = ['conducta' => 'Conducta del postulante', 'decision' => 'Decisión del postulante', 'error_registro' => 'Error de registro', 'otro' => 'Otro motivo'];
    $opcAbogados = $abogados->mapWithKeys(fn ($a) => [$a->id => $a->usuario->nombre.' '.$a->usuario->apellido.' · '.ucfirst($a->area)])->all();
    $opcEstados = collect($estados)->mapWithKeys(fn ($e) => [$e => $etiquetasEstado[$e] ?? $e])->all();
    $discapacidadActual = old('discapacidad', $po->discapacidad ? '1' : '0');
    $fi = old('fecha_inicio', $practica->fecha_inicio->format('Y-m-d'));
    $ft = old('fecha_termino', $practica->fecha_termino->format('Y-m-d'));
@endphp
@section('contenido')
@include('admin._nav')
<div class="cabecera-ficha">
    <div>
        <h1 class="titulo-pagina">{{ $po->nombre }} {{ $po->apellido }}</h1>
        <p class="texto-suave" style="margin:.25rem 0 0">{{ $po->universidad }} · {{ $practica->consultorio->nombre }}, {{ $practica->consultorio->region->nombre }}</p>
    </div>
    <x-badge :estado="$practica->estado" />
</div>

@if ($practica->estado === 'cancelada')
    <div class="alerta alerta-error" role="note"><div>
        <strong>Práctica cancelada el {{ $practica->cancelacion_fecha?->format('d/m/Y') }}</strong>
        @if ($practica->cancelacion_antes_de_iniciar)<span class="sello">NO INICIÓ</span>@endif
        <div>Motivo: {{ $etiquetasMotivo[$practica->cancelacion_motivo] ?? $practica->cancelacion_motivo }}.@if ($practica->cancelacion_detalle) {{ $practica->cancelacion_detalle }}@endif</div>
        <div class="dato-menor">Para reactivarla, cambia el estado a «En curso» o «Por iniciar» y guarda (requiere cupo en la sede).</div>
    </div></div>
@endif

<div class="rejilla-ficha">
    <div class="columna">
        <x-tarjeta titulo="Datos del postulante">
            <form method="POST" action="{{ route('admin.practicantes.actualizar', $practica) }}" class="formulario" novalidate>
                @csrf @method('PUT')
                <div class="rejilla-campos">
                    <x-campo name="nombre" label="Nombre" :value="$po->nombre" obligatorio />
                    <x-campo name="apellido" label="Apellido" :value="$po->apellido" obligatorio />
                    <x-campo name="universidad" label="Universidad" :value="$po->universidad" obligatorio />
                    <x-campo name="email" type="email" label="Correo" :value="$practica->practicante->usuario->email ?? $po->email" obligatorio />
                    <x-campo name="telefono" type="tel" label="Teléfono" :value="$po->telefono" obligatorio ayuda="Formato +56 9 1234 5678" />
                    <x-campo name="abogado_id" label="Abogado tutor" :opciones="$opcAbogados" :value="$practica->abogado_id" placeholder="Sin tutor" />
                    <x-campo name="fecha_inicio" type="date" label="Inicio" :value="$fi" obligatorio />
                    <x-campo name="fecha_termino" type="date" label="Término" :value="$ft" obligatorio />
                    <x-campo name="estado" label="Estado" :opciones="$opcEstados" :value="$practica->estado" obligatorio placeholder="Selecciona un estado" ayuda="Para cancelar usa el bloque «Cancelar práctica». Pasar de cancelada a otro estado la reactiva." />
                    <x-campo name="discapacidad" label="Discapacidad" :opciones="['0' => 'No', '1' => 'Sí']" :value="$discapacidadActual" obligatorio placeholder="Selecciona" id="discapacidad" />
                </div>
                <div id="bloque-discapacidad" @if ($discapacidadActual !== '1') hidden @endif>
                    <x-campo name="discapacidad_descripcion" type="textarea" label="Descripción de la discapacidad" :value="$po->discapacidad_descripcion" />
                </div>
                <div class="acciones-form"><x-boton type="submit">Guardar cambios</x-boton></div>
            </form>
        </x-tarjeta>

        <x-tarjeta titulo="Trasladar de sede" id="traslado">
            @if (! $accionable)
                <p class="texto-suave" style="margin:0">Solo se puede trasladar una práctica por iniciar o en curso. Esta práctica está <strong>{{ strtolower($etiquetasEstado[$practica->estado] ?? $practica->estado) }}</strong>.</p>
            @else
                <form method="POST" action="{{ route('admin.practicantes.trasladar', $practica) }}" class="formulario" id="form-traslado" novalidate>
                    @csrf
                    <div class="campo @error('consultorio_id') tiene-error @enderror" data-grupo-obligatorio="Elige la sede de destino.">
                        <span class="campo-etiqueta" id="et-sedes">Sede de destino<span class="campo-obligatorio" aria-hidden="true">*</span></span>
                        <input type="search" id="buscar-sede" class="campo-control" placeholder="Buscar sede o región" aria-label="Buscar sede o región" data-sin-validar>
                        <div class="sedes-lista" role="radiogroup" aria-labelledby="et-sedes">
                            @foreach ($sedes as $s)
                                @php
                                    $esActual = $s->id === $practica->consultorio_id;
                                    $libres = (int) $s->libres;
                                    $disp = $libres === 0 ? 'sin' : ($libres >= $s->capacidad / 2 ? 'muchos' : 'pocos');
                                    $bloqueada = $esActual || $libres === 0;
                                @endphp
                                <label class="sede-opcion" data-texto="{{ mb_strtolower($s->nombre.' '.$s->region->nombre.' '.$s->direccion) }}" data-nombre="{{ $s->nombre }}">
                                    <input type="radio" name="consultorio_id" value="{{ $s->id }}" @checked((string) old('consultorio_id') === (string) $s->id) @disabled($bloqueada)>
                                    <span class="sede-cuerpo">
                                        <strong>{{ $s->nombre }}</strong>
                                        <span class="dato-menor texto-suave">{{ $s->region->nombre }} · {{ $s->direccion }}</span>
                                        <span class="sede-meta">
                                            @if ($esActual)<x-badge estado="informativo" texto="Sede actual" />@else<x-badge :estado="$disp" />@endif
                                            <span class="dato-menor">{{ $libres }} de {{ $s->capacidad }} cupos libres</span>
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="campo-error" role="alert">{{ $errors->first('consultorio_id') }}</p>
                    </div>
                    <p id="sin-resultados-sede" class="texto-suave" hidden>Ninguna sede coincide con la búsqueda.</p>
                    <x-campo name="abogado_id" label="Tutor en la nueva sede" :opciones="$opcAbogados" placeholder="Sin tutor" id="traslado-abogado" ayuda="Opcional. Si no eliges, queda sin tutor." />
                    <div class="campo">
                        <label for="traslado-motivo" class="campo-etiqueta">Motivo del traslado</label>
                        <textarea id="traslado-motivo" name="motivo" class="campo-control" aria-describedby="traslado-motivo-ayuda"></textarea>
                        <p id="traslado-motivo-ayuda" class="campo-ayuda">Opcional. Queda en el historial.</p>
                    </div>
                    <div class="acciones-form">
                        <p class="texto-suave dato-menor" id="resumen-traslado" aria-live="polite" style="margin:0;margin-right:auto">Elige una sede de destino.</p>
                        <x-boton type="submit" tipo="secundario">Trasladar</x-boton>
                    </div>
                </form>
            @endif
        </x-tarjeta>

        <x-tarjeta titulo="Cancelar práctica" class="tarjeta-critica">
            @if (! $accionable)
                <p class="texto-suave" style="margin:0">Solo se puede cancelar una práctica por iniciar o en curso.</p>
            @else
                <form method="POST" action="{{ route('admin.practicantes.cancelar', $practica) }}" id="form-cancelar" class="formulario" novalidate>
                    @csrf
                    <p class="texto-suave" style="margin:0">Libera el cupo en la sede y deja constancia en el historial. Puede reactivarse después desde «Datos del postulante».</p>
                    <div class="campo @error('motivo') tiene-error @enderror" data-grupo-obligatorio="Elige un motivo.">
                        <span class="campo-etiqueta" id="et-motivo">Motivo<span class="campo-obligatorio" aria-hidden="true">*</span></span>
                        <div class="radios" role="radiogroup" aria-labelledby="et-motivo">
                            @foreach ($motivosCancelacion as $m)
                                <label class="radio"><input type="radio" name="motivo" value="{{ $m }}" @checked(old('motivo') === $m)><span>{{ $etiquetasMotivo[$m] ?? $m }}</span></label>
                            @endforeach
                        </div>
                        <p class="campo-error" role="alert">{{ $errors->first('motivo') }}</p>
                    </div>
                    <x-campo name="detalle" type="textarea" label="Detalle" id="cancelar-detalle" ayuda="Obligatorio si el motivo es «Otro motivo»." />
                    <div class="acciones-form">
                        <x-boton type="button" tipo="peligro" id="btn-cancelar" disabled data-modal-abrir="modal-cancelar">Cancelar práctica…</x-boton>
                    </div>
                </form>
            @endif
        </x-tarjeta>
    </div>

    <div class="columna">
        <x-tarjeta titulo="Datos de la práctica">
            <dl class="lista-datos">
                <div><dt>RUT</dt><dd>{{ $po->rut }}</dd></div>
                <div><dt>Región</dt><dd>{{ $practica->consultorio->region->nombre }}</dd></div>
                <div><dt>Sede</dt><dd>{{ $practica->consultorio->nombre }}</dd></div>
                <div><dt>Inicio</dt><dd>{{ $practica->fecha_inicio->format('d/m/Y') }}</dd></div>
                <div><dt>Término</dt><dd>{{ $practica->fecha_termino->format('d/m/Y') }}</dd></div>
                <div><dt>Tutor</dt><dd>{{ $practica->abogado?->usuario ? $practica->abogado->usuario->nombre.' '.$practica->abogado->usuario->apellido : 'Sin tutor' }}</dd></div>
            </dl>
        </x-tarjeta>

        <x-tarjeta titulo="Historial de traslados">
            @forelse ($practica->traslados as $t)
                @if ($loop->first)<ol class="linea-tiempo">@endif
                <li>
                    <div class="dato-menor texto-suave">{{ \Illuminate\Support\Carbon::parse($t->fecha)->format('d/m/Y') }}</div>
                    <div><strong>{{ $t->consultorioOrigen?->nombre ?? '—' }}</strong> &rarr; <strong>{{ $t->consultorioDestino?->nombre ?? '—' }}</strong></div>
                    @if ($t->motivo)<div class="dato-menor texto-suave">{{ $t->motivo }}</div>@endif
                </li>
                @if ($loop->last)</ol>@endif
            @empty
                <p class="texto-suave" style="margin:0">Sin traslados registrados.</p>
            @endforelse
        </x-tarjeta>
    </div>
</div>

@if ($accionable)
<x-modal id="modal-cancelar" titulo="¿Cancelar esta práctica?">
    <p style="margin-top:0">Se cancelará la práctica de <strong>{{ $po->nombre }} {{ $po->apellido }}</strong> y se liberará su cupo en {{ $practica->consultorio->nombre }}.</p>
    <p class="texto-suave" style="margin-bottom:0">Motivo: <strong id="resumen-motivo">—</strong></p>
    <x-slot:pie><x-boton type="submit" tipo="peligro" form="form-cancelar">Sí, cancelar práctica</x-boton></x-slot:pie>
</x-modal>
@endif

<script>
document.addEventListener('DOMContentLoaded', () => {
    const disc = document.getElementById('discapacidad'), bd = document.getElementById('bloque-discapacidad');
    disc?.addEventListener('change', () => { bd.hidden = disc.value !== '1'; const t = bd.querySelector('textarea'); t.required = disc.value === '1'; });
    if (disc && disc.value === '1') bd.querySelector('textarea').required = true;

    const buscar = document.getElementById('buscar-sede');
    const opciones = [...document.querySelectorAll('.sede-opcion')];
    buscar?.addEventListener('input', () => {
        const q = buscar.value.trim().toLowerCase(); let n = 0;
        opciones.forEach((o) => { const ok = !q || o.dataset.texto.includes(q); o.hidden = !ok; if (ok) n++; });
        document.getElementById('sin-resultados-sede').hidden = n > 0;
    });
    const resumen = document.getElementById('resumen-traslado');
    document.querySelectorAll('input[name=consultorio_id]').forEach((r) => r.addEventListener('change', () => {
        resumen.innerHTML = 'Se trasladará a la sede: <strong></strong>';
        resumen.querySelector('strong').textContent = r.closest('.sede-opcion').dataset.nombre;
    }));
    const marcada = document.querySelector('input[name=consultorio_id]:checked');
    if (marcada) marcada.dispatchEvent(new Event('change'));

    const form = document.getElementById('form-cancelar');
    if (form) {
        const btn = document.getElementById('btn-cancelar'), det = document.getElementById('cancelar-detalle');
        const sync = () => {
            const m = form.querySelector('input[name=motivo]:checked');
            btn.disabled = !m;
            det.required = m?.value === 'otro';
            document.getElementById('resumen-motivo').textContent = m ? m.nextElementSibling.textContent : '—';
        };
        form.addEventListener('change', sync); sync();
    }
});
</script>
@endsection
