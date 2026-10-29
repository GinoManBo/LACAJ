@props(['id', 'titulo', 'grande' => false])
<dialog id="{{ $id }}" aria-labelledby="{{ $id }}-titulo" {{ $attributes->merge(['class' => 'modal'.($grande ? ' modal-grande' : '')]) }}>
    <header class="modal-cabecera">
        <h2 id="{{ $id }}-titulo">{{ $titulo }}</h2>
        <button type="button" class="modal-cerrar" data-modal-cerrar aria-label="Cerrar">&times;</button>
    </header>
    <div class="modal-cuerpo">{{ $slot }}</div>
    <footer class="modal-pie">
        @isset($pie){{ $pie }}@endisset
        <button type="button" class="btn btn-secundario" data-modal-cerrar>Cerrar</button>
    </footer>
</dialog>
