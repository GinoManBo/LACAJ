<x-modal id="modal-cancelar" titulo="Cancelar práctica">
    <form id="form-cancelar" method="POST" action="{{ $accion ?? '#' }}" class="sec-asignar" data-sin-validar-extra>
        @csrf
        <div class="alerta alerta-aviso" role="note">@include('secretaria._icono', ['n' => 'alerta'])<div>Va a cancelar la práctica de <strong data-cancelar-nombre>{{ $nombre ?? 'este postulante' }}</strong>. Se libera el cupo y se genera un documento de cancelación.</div></div>
        <div class="campo {{ $errors->has('motivo') ? 'tiene-error' : '' }}" data-grupo-obligatorio="Seleccione el motivo de cancelación.">
            <fieldset class="sec-motivos">
                <legend>Motivo de cancelación<span class="campo-obligatorio" aria-hidden="true">*</span></legend>
                @foreach ($motivos as $clave => $etiqueta)
                    <label class="sec-motivo"><input type="radio" name="motivo" value="{{ $clave }}" @checked(old('motivo') === (string) $clave)><span>{{ $etiqueta }}</span></label>
                @endforeach
            </fieldset>
            <p class="campo-error" role="alert">{{ $errors->first('motivo') }}</p>
        </div>
        <div class="sec-extra" data-detalle @if (old('motivo') !== 'otro') hidden @endif>
            <x-campo name="detalle" label="Detalle del motivo" type="textarea" maxlength="2000" ayuda="Explique brevemente por qué se cancela." />
        </div>
    </form>
    <x-slot:pie><x-boton type="submit" tipo="peligro" form="form-cancelar" data-cancelar-enviar disabled>Cancelar práctica</x-boton></x-slot:pie>
</x-modal>
<script>
(() => {
    const form = document.getElementById('form-cancelar'), modal = document.getElementById('modal-cancelar');
    const enviar = document.querySelector('[data-cancelar-enviar]'), detalle = form.querySelector('[data-detalle]');
    const sync = () => {
        const m = form.querySelector('input[name=motivo]:checked');
        enviar.disabled = !m; detalle.hidden = !m || m.value !== 'otro';
    };
    form.querySelectorAll('input[name=motivo]').forEach((r) => r.addEventListener('change', sync));
    document.querySelectorAll('[data-cancelar-url]').forEach((b) => b.addEventListener('click', () => {
        form.action = b.dataset.cancelarUrl;
        form.querySelector('[data-cancelar-nombre]').textContent = b.dataset.cancelarNombre;
    }));
    sync();
})();
</script>
