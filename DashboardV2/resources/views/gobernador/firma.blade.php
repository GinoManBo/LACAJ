@extends('layouts.portal', ['migas' => [['Cola de autorizaciones', route('gobernador.inicio')], ['Firma electrónica']]])
@section('titulo', 'Firma electrónica')
@section('contenido')
@include('gobernador._nav')
<div>
    <h1 class="titulo-pagina">Firma electrónica</h1>
    <p class="texto-suave" style="margin:.25rem 0 0">Se estampa en los certificados y resoluciones que usted autoriza.</p>
</div>

<div class="rejilla-dos">
    <x-tarjeta titulo="Firma vigente">
        @if ($tieneFirma)
            <div class="firma-papel"><img src="{{ route('gobernador.firma.imagen') }}?v={{ $actualizadaAt?->timestamp }}" alt="Su firma electrónica actual"></div>
            <p class="dato-menor texto-suave" style="margin:.75rem 0 0">Actualizada el {{ $actualizadaAt?->format('d/m/Y \a \l\a\s H:i') ?? 'fecha desconocida' }}.</p>
        @else
            <x-estado-vacio titulo="Aún no hay firma cargada" texto="Sube una imagen para que los documentos salgan firmados." />
        @endif
    </x-tarjeta>

    <x-tarjeta titulo="{{ $tieneFirma ? 'Reemplazar firma' : 'Subir firma' }}">
        <form method="POST" action="{{ route('gobernador.firma.subir') }}" enctype="multipart/form-data" class="formulario" novalidate>
            @csrf
            <div class="campo @error('firma') tiene-error @enderror">
                <label for="firma" class="campo-etiqueta">Imagen de la firma<span class="campo-obligatorio" aria-hidden="true">*</span></label>
                <input id="firma" name="firma" type="file" accept="image/png,image/jpeg" class="campo-control campo-archivo" required data-mensaje="Seleccione la imagen de su firma." aria-describedby="firma-ayuda firma-error" @error('firma') aria-invalid="true" @enderror>
                <p id="firma-ayuda" class="campo-ayuda">PNG o JPG, hasta 2 MB. Recomendado: PNG con fondo transparente, recortado a la firma.</p>
                <p id="firma-error" class="campo-error" role="alert">{{ $errors->first('firma') }}</p>
            </div>
            <div id="previa" hidden>
                <p class="campo-etiqueta" style="margin:0 0 .35rem">Vista previa</p>
                <div class="firma-papel"><img id="previa-img" alt="Vista previa de la firma seleccionada"></div>
            </div>
            <div class="acciones-form"><x-boton type="submit">{{ $tieneFirma ? 'Reemplazar firma' : 'Guardar firma' }}</x-boton></div>
        </form>
    </x-tarjeta>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const inp = document.getElementById('firma'), previa = document.getElementById('previa'), img = document.getElementById('previa-img');
    let url = null;
    inp.addEventListener('change', () => {
        if (url) URL.revokeObjectURL(url);
        const f = inp.files[0];
        if (!f || !/^image\/(png|jpeg)$/.test(f.type)) { previa.hidden = true; return; }
        url = URL.createObjectURL(f); img.src = url; previa.hidden = false;
    });
});
</script>
@endsection
