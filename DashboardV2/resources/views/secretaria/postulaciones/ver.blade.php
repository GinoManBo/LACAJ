@extends('layouts.portal', ['migas' => [['Postulaciones', route('secretaria.postulaciones')], [$postulante->nombre.' '.$postulante->apellido]]])
@section('titulo', 'Postulación de '.$postulante->nombre.' '.$postulante->apellido)
@section('contenido')
@php
    $prefs = $postulacion->preferencias->sortBy('orden')->values();
    $reabrir = $errors->has('consultorio_id') || $errors->has('abogado_id');
    $elegido = old('consultorio_id');
@endphp

<div class="sec-titulo-ficha">
    <div>
        <div class="sec-titulo-ficha"><h1 class="titulo-pagina">{{ $postulante->nombre }} {{ $postulante->apellido }}</h1><x-badge :estado="$postulacion->estado" /></div>
        <p class="sec-subtitulo">{{ $postulante->universidad }} · Postuló el {{ $postulacion->fecha_postulacion->format('d-m-Y') }}</p>
        @if ($postulante->datos_actualizados_at)
            <p class="sec-subtitulo"><span class="sec-tag sec-tag-otra">Datos actualizados</span> El postulante actualizó sus datos el {{ $postulante->datos_actualizados_at->format('d-m-Y H:i') }}</p>
        @endif
    </div>
</div>

<div class="sec-ficha con-lateral">
    <div class="sec-columna">
        <x-tarjeta titulo="Datos del postulante">@include('secretaria._datos-postulante')</x-tarjeta>

        <x-tarjeta titulo="Consultorios a los que postuló">
            @if ($prefs->isEmpty())
                <p class="sec-ausente">El postulante no indicó preferencias.</p>
            @else
                <ol class="sec-orden">
                    @foreach ($prefs as $pf)
                        <li><span class="resumen-num">{{ $pf->orden }}</span><div><strong>{{ $pf->consultorio->nombre }}</strong><span class="sec-menor">{{ $pf->consultorio->comuna?->nombre }}@if ($pf->consultorio->direccion) · {{ $pf->consultorio->direccion }}@endif</span></div></li>
                    @endforeach
                </ol>
            @endif
            <p class="resumen-fecha" style="margin-top:1rem"><span class="texto-suave">Fecha de inicio solicitada</span><strong style="text-transform:none">{{ $postulacion->fecha_inicio_solicitada?->format('d-m-Y') ?? 'Por completar' }}</strong></p>
        </x-tarjeta>

        <x-tarjeta titulo="Documentos">@include('secretaria._documentos', ['documentosCedula' => $documentosCedula, 'documentosUniversitarios' => $documentosUniversitarios, 'faltaCedula' => $faltaCedula])</x-tarjeta>

        @if ($postulacion->derivaciones->isNotEmpty())
            <x-tarjeta titulo="Historial de derivaciones">
                @foreach ($postulacion->derivaciones as $d)
                    <div class="sec-deriva">
                        <strong>{{ $d->regionOrigen->nombre }} → {{ $d->regionDestino->nombre }}</strong>
                        <span class="texto-suave sec-num">{{ \Illuminate\Support\Carbon::parse($d->created_at)->format('d-m-Y') }}</span>
                        @if ($d->nota)<span style="flex-basis:100%;white-space:pre-line">{{ $d->nota }}</span>@endif
                    </div>
                @endforeach
            </x-tarjeta>
        @endif

        <x-tarjeta titulo="Comentarios">
            <p class="texto-suave" style="margin:0 0 1rem;font-size:.92rem">Los comentarios viajan con la postulación: si se deriva, la secretaría de destino los verá.</p>
            @forelse ($postulacion->notas as $n)
                <article class="sec-nota">
                    <p>{{ $n->texto }}</p>
                    <footer>
                        <span>{{ $n->autor?->nombre }} {{ $n->autor?->apellido }} · {{ $n->created_at->format('d-m-Y H:i') }}</span>
                        @if ($n->autor_usuario_id === auth()->id())
                            <form method="POST" action="{{ route('secretaria.postulaciones.nota.eliminar', [$postulacion->id, $n->id]) }}" onsubmit="return confirm('¿Eliminar este comentario?')">
                                @csrf @method('DELETE')
                                <x-boton type="submit" tipo="fantasma" tam="sm">Eliminar</x-boton>
                            </form>
                        @endif
                    </footer>
                </article>
            @empty
                <p class="sec-ausente">Aún no hay comentarios.</p>
            @endforelse
            <form method="POST" action="{{ route('secretaria.postulaciones.nota.crear', $postulacion->id) }}" class="sec-nota-nueva">
                @csrf
                <x-campo name="texto" label="Nuevo comentario" type="textarea" :obligatorio="true" data-mensaje="Escriba el comentario." maxlength="2000" />
                <div><x-boton type="submit" tipo="secundario">Agregar comentario</x-boton></div>
            </form>
        </x-tarjeta>
    </div>

    <aside class="sec-lateral">
        <x-tarjeta titulo="Resolver postulación" acento>
            <div class="sec-decidir">
                <dl>
                    <div><dt>Región que la gestiona</dt><dd>{{ $postulante->region?->nombre }}</dd></div>
                    <div><dt>Inicio solicitado</dt><dd>{{ $postulacion->fecha_inicio_solicitada?->format('d-m-Y') ?? 'Por completar' }}</dd></div>
                </dl>
                <p>Asigne un consultorio para aceptar, o rechace la postulación indicando el motivo.</p>
                <x-boton data-modal-abrir="modal-aceptar">Aceptar y asignar consultorio</x-boton>
                <x-boton tipo="peligro" data-modal-abrir="modal-rechazar">Rechazar postulación</x-boton>
            </div>
        </x-tarjeta>
    </aside>
</div>

<x-modal id="modal-rechazar" titulo="Rechazar postulación">
    <form id="form-rechazar" method="POST" action="{{ route('secretaria.postulaciones.rechazar', $postulacion->id) }}" class="sec-asignar">
        @csrf
        <p style="margin:0">Se rechazará la postulación de <strong>{{ $postulante->nombre }} {{ $postulante->apellido }}</strong>. Esta acción no se puede deshacer.</p>
        <x-campo name="motivo" label="Motivo (opcional)" type="textarea" ayuda="Queda registrado en la postulación." maxlength="2000" />
    </form>
    <x-slot:pie><x-boton type="submit" tipo="peligro" form="form-rechazar">Rechazar postulación</x-boton></x-slot:pie>
</x-modal>

<x-modal id="modal-aceptar" titulo="Asignar consultorio" :grande="true">
    <form id="form-aceptar" method="POST" action="{{ route('secretaria.postulaciones.aceptar', $postulacion->id) }}" class="sec-asignar" data-nombre="{{ $postulante->nombre }} {{ $postulante->apellido }}">
        @csrf
        <div class="cons-buscar">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14zM21 21l-4.3-4.3"/></svg>
            <label for="buscar-cons" class="sr-only">Buscar consultorio</label>
            <input id="buscar-cons" type="search" class="campo-control" placeholder="Buscar por nombre, dirección o región" autocomplete="off">
        </div>

        <div class="campo {{ $errors->has('consultorio_id') ? 'tiene-error' : '' }}" data-grupo-obligatorio="Seleccione un consultorio.">
            <span class="campo-etiqueta" id="etq-cons">Consultorio de destino<span class="campo-obligatorio" aria-hidden="true">*</span></span>
            <div class="sec-cons-lista" role="radiogroup" aria-labelledby="etq-cons">
                @foreach ($consultorios as $c)
                    @php $bloqueado = ! $c->otra_region && $c->disponibilidad === 'sin'; @endphp
                    <label class="sec-cons" data-buscar="{{ \Illuminate\Support\Str::lower($c->nombre.' '.$c->direccion.' '.$c->region->nombre.' '.$c->comuna?->nombre) }}">
                        <input type="radio" name="consultorio_id" value="{{ $c->id }}" data-otra="{{ $c->otra_region ? 1 : 0 }}" data-region="{{ $c->region->nombre }}" data-nombre="{{ $c->nombre }}" @checked((string) $elegido === (string) $c->id) @disabled($bloqueado)>
                        <span class="sec-cons-cuerpo">
                            <strong>{{ $c->nombre }}</strong>
                            <x-badge :estado="$c->disponibilidad" />
                            <span class="sec-cons-sub">{{ $c->direccion }}@if ($c->comuna), {{ $c->comuna->nombre }}@endif · {{ $c->region->nombre }}</span>
                            <span class="sec-cons-sub">{{ $c->ocupados }} de {{ $c->capacidad }} cupos ocupados · {{ $c->libres }} {{ $c->libres === 1 ? 'libre' : 'libres' }}@if ($bloqueado) · No se puede asignar sin cupos @endif</span>
                            @if ($c->postulo_aqui || $c->otra_region)
                                <span class="sec-etiquetas">
                                    @if ($c->postulo_aqui)<span class="sec-tag sec-tag-aqui">POSTULÓ AQUÍ</span>@endif
                                    @if ($c->otra_region)<span class="sec-tag sec-tag-otra">OTRA REGIÓN</span>@endif
                                </span>
                            @endif
                        </span>
                    </label>
                @endforeach
                <p class="sec-cons-vacio" data-sin-resultados hidden>Ningún consultorio coincide con la búsqueda.</p>
            </div>
            <p class="campo-error" role="alert">{{ $errors->first('consultorio_id') }}</p>
        </div>

        <div class="sec-extra" data-bloque-abogado hidden>
            @foreach ($consultorios->where('otra_region', false) as $c)
                <div class="campo" data-abogados-de="{{ $c->id }}" hidden>
                    <label for="abogado-{{ $c->id }}" class="campo-etiqueta">Abogado tutor (opcional)</label>
                    <select id="abogado-{{ $c->id }}" name="abogado_id" class="campo-control" disabled>
                        <option value="">Sin asignar</option>
                        @foreach ($c->abogados as $a)
                            <option value="{{ $a->id }}" @selected((string) old('abogado_id') === (string) $a->id)>{{ $a->usuario->nombre }} {{ $a->usuario->apellido }}@if ($a->area) — {{ $a->area }}@endif</option>
                        @endforeach
                    </select>
                    <p class="campo-ayuda">Puede asignarlo ahora o más tarde.</p>
                    @if ($errors->has('abogado_id'))<p class="campo-error" role="alert">{{ $errors->first('abogado_id') }}</p>@endif
                </div>
            @endforeach
        </div>

        <x-campo name="nota" label="Comentario para la secretaría de destino / registro (opcional)" type="textarea" maxlength="2000" />

        <div class="sec-resultado" data-resultado data-modo="" aria-live="polite">
            <p><strong data-resultado-titulo>Elija un consultorio</strong><span data-resultado-texto>Aquí verá qué ocurrirá al confirmar.</span></p>
        </div>
    </form>
    <x-slot:pie>
        <x-boton type="submit" tipo="ok" form="form-aceptar" data-confirmar disabled>Confirmar ingreso</x-boton>
        <x-boton type="submit" tipo="aviso" form="form-aceptar" data-derivar hidden>Derivar a otra región</x-boton>
    </x-slot:pie>
</x-modal>

<script>
(() => {
    const form = document.getElementById('form-aceptar');
    if (!form) return;
    const modal = document.getElementById('modal-aceptar');
    const radios = [...form.querySelectorAll('input[name=consultorio_id]')];
    const btnOk = document.querySelector('[data-confirmar]'), btnDer = document.querySelector('[data-derivar]');
    const res = form.querySelector('[data-resultado]');
    const bloque = form.querySelector('[data-bloque-abogado]');
    const nombre = form.dataset.nombre;

    function actualizar() {
        const r = radios.find((x) => x.checked);
        form.querySelectorAll('[data-abogados-de]').forEach((d) => { d.hidden = true; d.querySelector('select').disabled = true; });
        bloque.hidden = true;
        btnOk.disabled = !r; btnOk.hidden = false; btnDer.hidden = true;
        res.dataset.modo = '';
        if (!r) return;
        const otra = r.dataset.otra === '1';
        if (otra) {
            btnOk.hidden = true; btnDer.hidden = false;
            res.dataset.modo = 'otra';
            res.querySelector('[data-resultado-titulo]').textContent = 'Se derivará a ' + r.dataset.region;
            res.querySelector('[data-resultado-texto]').textContent = nombre + ' pasará a la secretaría de ' + r.dataset.region + ' para que decida su ingreso. Dejará de aparecer en su lista y el comentario viajará con la postulación.';
        } else {
            const d = form.querySelector('[data-abogados-de="' + r.value + '"]');
            if (d) { d.hidden = false; d.querySelector('select').disabled = false; bloque.hidden = false; }
            res.dataset.modo = 'propia';
            res.querySelector('[data-resultado-titulo]').textContent = 'Ingresará a ' + r.dataset.nombre;
            res.querySelector('[data-resultado-texto]').textContent = 'Se acepta la postulación y se crea la práctica. Si ' + nombre + ' aún no tiene cuenta, sus credenciales temporales se mostrarán una sola vez.';
        }
    }
    radios.forEach((r) => r.addEventListener('change', actualizar));

    const buscador = document.getElementById('buscar-cons');
    buscador.addEventListener('input', () => {
        const t = buscador.value.trim().toLowerCase();
        let vistos = 0;
        form.querySelectorAll('.sec-cons').forEach((l) => { const ok = !t || l.dataset.buscar.includes(t); l.hidden = !ok; if (ok) vistos++; });
        form.querySelector('[data-sin-resultados]').hidden = vistos > 0;
    });
    buscador.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });
    actualizar();
    @if ($reabrir) modal.showModal(); @endif
})();
</script>
@endsection
