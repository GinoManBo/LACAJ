@extends('layouts.portal', ['migas' => [['Cola de autorizaciones']]])
@section('titulo', 'Cola de autorizaciones')
@php
    $total = $pendientes->count();
    $concepto = fn ($n) => $n >= 6.5 ? 'Sobresaliente' : ($n >= 5.5 ? 'Muy bueno' : ($n >= 4.5 ? 'Bueno' : ($n >= 4.0 ? 'Suficiente' : 'Insuficiente')));
    $clasesConcepto = fn ($n) => $n >= 5.5 ? 'badge-muchos' : ($n >= 4.0 ? 'badge-informativo' : 'badge-sin');
@endphp
@section('contenido')
@include('gobernador._nav')
<div>
    <h1 class="titulo-pagina">Cola de autorizaciones</h1>
    <p class="texto-suave" style="margin:.25rem 0 0">Prácticas evaluadas que esperan su autorización para emitir certificado y resolución.</p>
</div>

@unless ($tieneFirma)
    <div class="alerta alerta-aviso" role="note"><div><strong>Aún no sube su firma electrónica.</strong> Los PDF saldrán sin firma. <a href="{{ route('gobernador.firma') }}">Subir mi firma</a></div></div>
@endunless

<div class="rejilla-indicadores">
    <x-indicador :valor="$total" etiqueta="Pendientes de autorización" :critico="$total > 0" />
</div>

@if ($total === 0)
    <x-estado-vacio titulo="No hay prácticas pendientes de autorización" texto="Cuando una secretaría cierre una evaluación, aparecerá aquí." />
@else
    <x-tarjeta titulo="Autorización en lote">
        <div class="lote">
            <form method="POST" action="{{ route('gobernador.autorizar.lote') }}" class="lote-n" novalidate>
                @csrf
                <x-campo name="cantidad" type="number" label="Autorizar las N más antiguas" min="1" max="1000" :value="min($total, 10)" obligatorio inputmode="numeric" data-mensaje="Indique cuántas prácticas autorizar." />
                <x-boton type="submit" tipo="secundario">Autorizar N</x-boton>
            </form>
            <div class="lote-todo">
                <p class="dato-menor texto-suave" style="margin:0">Autoriza toda la cola de una vez.</p>
                <x-boton type="button" data-modal-abrir="modal-todo">Autorizar todas ({{ $total }})</x-boton>
            </div>
        </div>
    </x-tarjeta>

    <form method="POST" action="{{ route('gobernador.autorizar.seleccion') }}" id="form-seleccion" novalidate>
        @csrf
        <x-tabla :columnas="['', 'Postulante', 'Sede', 'Abogado tutor', 'Evaluación', 'Fecha', '']" descripcion="Prácticas pendientes, de la más antigua a la más reciente" class="tabla-cola">
            <x-slot:cabecera>
                <tr>
                    <th scope="col" class="col-check"><label class="check-solo"><input type="checkbox" id="sel-todo" aria-label="Seleccionar todas las prácticas"></label></th>
                    <th scope="col">Postulante</th><th scope="col">Sede</th><th scope="col">Abogado tutor</th><th scope="col">Evaluación</th><th scope="col">Fecha</th><th scope="col"><span class="sr-only">Acción</span></th>
                </tr>
            </x-slot:cabecera>
            @foreach ($pendientes as $p)
                @php $po = $p->practicante->postulante; $prom = (float) ($p->evaluacion->promedio ?? 0); @endphp
                <tr>
                    <td class="col-check"><label class="check-solo"><input type="checkbox" name="practicas[]" value="{{ $p->id }}" class="sel-fila" aria-label="Seleccionar a {{ $po->nombre }} {{ $po->apellido }}"></label></td>
                    <td><strong>{{ $po->nombre }} {{ $po->apellido }}</strong><div class="dato-menor texto-suave">{{ $po->rut }}</div></td>
                    <td>{{ $p->consultorio->nombre }}<div class="dato-menor texto-suave">{{ $p->consultorio->region->nombre }}</div></td>
                    <td>{{ $p->abogado?->usuario ? $p->abogado->usuario->nombre.' '.$p->abogado->usuario->apellido : 'Sin tutor' }}</td>
                    <td class="nowrap"><strong class="nota">{{ number_format($prom, 1, ',', '') }}</strong> <span class="badge {{ $clasesConcepto($prom) }}">{{ $concepto($prom) }}</span></td>
                    <td class="nowrap">{{ $p->evaluacion?->created_at?->format('d/m/Y') ?? '—' }}</td>
                    <td><button type="submit" class="btn btn-secundario btn-sm" formaction="{{ route('gobernador.autorizar', $p->id) }}" formnovalidate data-permitir-repetir>Autorizar</button></td>
                </tr>
            @endforeach
        </x-tabla>

        <div class="barra-seleccion" id="barra-seleccion" hidden role="region" aria-label="Acciones sobre la selección">
            <span><strong id="sel-n">0</strong> <span id="sel-txt">seleccionadas</span></span>
            <x-boton type="button" tipo="fantasma" id="sel-limpiar">Quitar selección</x-boton>
            <x-boton type="button" data-modal-abrir="modal-seleccion">Autorizar selección</x-boton>
        </div>
    </form>

    <x-modal id="modal-todo" titulo="Autorizar toda la cola">
        <p style="margin-top:0">Se autorizarán <strong>{{ $total }}</strong> {{ $total === 1 ? 'práctica' : 'prácticas' }} y se emitirán sus certificados y resoluciones{{ $tieneFirma ? ' con su firma' : ' sin firma, porque aún no la sube' }}.</p>
        <p class="texto-suave" style="margin-bottom:0">Esta acción no se puede deshacer.</p>
        <x-slot:pie>
            <form method="POST" action="{{ route('gobernador.autorizar.todo') }}">@csrf<x-boton type="submit" tipo="peligro">Autorizar {{ $total }}</x-boton></form>
        </x-slot:pie>
    </x-modal>

    <x-modal id="modal-seleccion" titulo="Autorizar selección">
        <p style="margin-top:0">Se autorizarán <strong id="modal-sel-n">0</strong> prácticas y se emitirán sus documentos{{ $tieneFirma ? ' con su firma' : ' sin firma' }}.</p>
        <p class="texto-suave" style="margin-bottom:0">Esta acción no se puede deshacer.</p>
        <x-slot:pie><x-boton type="submit" tipo="peligro" form="form-seleccion">Autorizar selección</x-boton></x-slot:pie>
    </x-modal>

    <x-modal id="modal-lote" titulo="Autorizar en lote">
        <p style="margin-top:0">Se autorizarán las <strong id="modal-lote-n">0</strong> prácticas más antiguas de la cola.</p>
        <p class="texto-suave" style="margin-bottom:0">Esta acción no se puede deshacer.</p>
        <x-slot:pie><x-boton type="button" tipo="peligro" id="lote-confirmar">Autorizar</x-boton></x-slot:pie>
    </x-modal>
@endif

<script>
document.addEventListener('DOMContentLoaded', () => {
    const filas = [...document.querySelectorAll('.sel-fila')];
    const todo = document.getElementById('sel-todo'), barra = document.getElementById('barra-seleccion');
    const sync = () => {
        const n = filas.filter((f) => f.checked).length;
        if (!barra) return;
        barra.hidden = n === 0;
        document.getElementById('sel-n').textContent = n;
        document.getElementById('sel-txt').textContent = n === 1 ? 'seleccionada' : 'seleccionadas';
        document.getElementById('modal-sel-n').textContent = n;
        todo.checked = n === filas.length; todo.indeterminate = n > 0 && n < filas.length;
        filas.forEach((f) => f.closest('tr').classList.toggle('fila-sel', f.checked));
    };
    todo?.addEventListener('change', () => { filas.forEach((f) => (f.checked = todo.checked)); sync(); });
    filas.forEach((f) => f.addEventListener('change', sync));
    document.getElementById('sel-limpiar')?.addEventListener('click', () => { filas.forEach((f) => (f.checked = false)); sync(); });

    const formLote = document.querySelector('.lote-n');
    formLote?.addEventListener('submit', (e) => {
        if (formLote.dataset.confirmado === '1') return;
        e.preventDefault(); e.stopImmediatePropagation();
        if (!window.cajValidar(formLote)) return;
        const n = Math.min(parseInt(formLote.querySelector('input[name=cantidad]').value, 10), {{ $total }});
        document.getElementById('modal-lote-n').textContent = n;
        document.getElementById('modal-lote').showModal();
    }, true);
    document.getElementById('lote-confirmar')?.addEventListener('click', () => { formLote.dataset.confirmado = '1'; formLote.requestSubmit(); });
});
</script>
@endsection
