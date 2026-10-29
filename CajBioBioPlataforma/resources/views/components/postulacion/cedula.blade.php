@props(['name', 'titulo', 'archivo' => null])
@php
    $id = 'campo-'.$name;
    $error = $errors->first($name);
@endphp
<div class="campo cedula {{ $error ? 'tiene-error' : '' }}" data-cedula data-camara-titulo="{{ $titulo }}">
    <span class="campo-etiqueta" id="{{ $id }}-etq">{{ $titulo }}<span class="campo-obligatorio" aria-hidden="true">*</span><span class="sr-only"> (obligatorio)</span></span>
    <div class="cedula-zona" data-zona>
        <img class="cedula-vista" alt="" hidden data-vista>
        <div class="cedula-marcador" data-marcador @if ($archivo) hidden @endif>
            <svg viewBox="0 0 64 40" aria-hidden="true"><rect x="1.5" y="1.5" width="61" height="37" rx="4"/><circle cx="19" cy="16" r="5"/><path d="M10 31c1.5-5 16.5-5 18 0M36 13h20M36 20h20M36 27h12"/></svg>
            <span>{{ $name === 'cedula_frontal' ? 'Frente de la cédula' : 'Reverso de la cédula' }}</span>
        </div>
        <div class="cedula-cargada" data-cargada @if (! $archivo) hidden @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
            <span>Archivo cargado: <strong data-nombre>{{ $archivo?->nombre_original }}</strong></span>
        </div>
    </div>
    <div class="cedula-acciones">
        <button type="button" class="btn btn-secundario btn-sm" data-camara>
            <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h3l1.5-2h7L17 8h3v11H4z"/><circle cx="12" cy="13.5" r="3.2"/></svg>
            Escanear con cámara
        </button>
        <button type="button" class="btn btn-secundario btn-sm" data-subir>
            <svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V5M7.5 9.5L12 5l4.5 4.5M5 19h14"/></svg>
            Subir archivo
        </button>
    </div>
    <input type="file" id="{{ $id }}" name="{{ $name }}" class="campo-control cedula-input" accept="image/png,image/jpeg" @required(! $archivo) aria-labelledby="{{ $id }}-etq" aria-describedby="{{ $id }}-ayuda {{ $id }}-error" @if ($error) aria-invalid="true" @endif>
    <input type="file" class="sr-only" accept="image/*" capture="environment" tabindex="-1" aria-hidden="true" data-nativa>
    <p id="{{ $id }}-ayuda" class="campo-ayuda">Imagen JPG o PNG, hasta 5 MB. Que se lea bien y no tenga reflejos.</p>
    <p id="{{ $id }}-error" class="campo-error" role="alert">{{ $error }}</p>
</div>
