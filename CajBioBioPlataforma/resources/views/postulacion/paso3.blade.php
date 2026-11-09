@extends('layouts.publico')
@section('titulo', 'Antecedentes educativos')

@php
    $e = $borrador['educ'] ?? [];
    $ev = fn ($k) => $e[$k] ?? null;
    $etiquetas = [
        'certificado_egreso' => 'Certificado de egreso',
    ];
    $maxMb = config('caj.max_upload_mb', 8);
@endphp

@section('contenido')
<x-postulacion.marco :n="$n" :pasos="$pasos" :permitido="$permitido" titulo="Antecedentes educativos y archivos" bajada="Indique su universidad y adjunte su cédula de identidad y su certificado de egreso obligatorio.">
    <form method="POST" action="{{ route('postulacion.guardar', 3) }}" enctype="multipart/form-data" data-paso-archivos novalidate class="post-form">
        @csrf

        <fieldset class="bloque">
            <legend>Estudios</legend>
            <div class="rejilla-campos">
                <div class="ancho-completo"><x-selector-universidad name="universidad" label="Universidad" :value="$ev('universidad')" :obligatorio="true" placeholder="Escriba para buscar o seleccionar su universidad..." ayuda="Seleccione de la lista oficial o indique su institución si no figura en el catálogo." /></div>
                <div class="ancho-completo"><x-campo name="especialidad_id" label="Especialidad de interés" :value="$ev('especialidad_id')" :opciones="$especialidades->pluck('nombre', 'id')" :obligatorio="true" /></div>
            </div>
        </fieldset>

        <fieldset class="bloque">
            <legend>Cédula de identidad</legend>
            <p class="texto-suave bloque-nota">Necesitamos una imagen clara de cada lado. Desde el celular, «Escanear con cámara» abre la cámara trasera.</p>
            <div class="rejilla-campos">
                <x-postulacion.cedula name="cedula_frontal" titulo="Cédula, lado frontal" :archivo="$archivos['cedula_frontal'] ?? null" />
                <x-postulacion.cedula name="cedula_reverso" titulo="Cédula, lado reverso" :archivo="$archivos['cedula_reverso'] ?? null" />
            </div>
        </fieldset>

        <fieldset class="bloque">
            <legend>Certificado de egreso (obligatorio)</legend>
            <p class="texto-suave bloque-nota">Acredita su condición de egresado/a de la carrera de Derecho, requisito indispensable para realizar la práctica. PDF, JPG o PNG, hasta {{ $maxMb }} MB.</p>
            <div class="rejilla-campos">
                @php $aEgreso = $archivos['certificado_egreso'] ?? null; @endphp
                <div class="ancho-completo">
                    <x-campo name="certificado_egreso" label="Certificado de egreso" type="file" accept=".pdf,image/png,image/jpeg" :obligatorio="true" :ayuda="$aEgreso ? 'Archivo cargado: '.$aEgreso->nombre_original.'. Elija otro para reemplazarlo.' : 'Adjunte el certificado de egreso emitido por su universidad.'" data-max-mb="{{ $maxMb }}" />
                </div>
            </div>
        </fieldset>

        @if (! empty($tiposOpcionales))
            <fieldset class="bloque">
                <legend>Documentos universitarios (opcionales)</legend>
                <p class="texto-suave bloque-nota">Adjuntarlos agiliza la revisión. PDF, JPG o PNG, hasta {{ $maxMb }} MB cada uno.</p>
                <div class="rejilla-campos">
                    @foreach ($tiposOpcionales as $t)
                        @php $a = $archivos[$t] ?? null; @endphp
                        <x-campo :name="$t" :label="($etiquetas[$t] ?? $t).' (opcional)'" type="file" accept=".pdf,image/png,image/jpeg" :ayuda="$a ? 'Archivo cargado: '.$a->nombre_original.'. Elija otro para reemplazarlo.' : null" data-max-mb="{{ $maxMb }}" />
                    @endforeach
                </div>
            </fieldset>
        @endif

        <x-modal id="modal-camara" titulo="Escanear cédula" class="modal-camara">
            <div class="camara" data-camara-caja>
                <video playsinline muted autoplay data-camara-video></video>
                <div class="camara-guia" aria-hidden="true"></div>
                <p class="camara-estado" data-camara-estado role="status"></p>
            </div>
            <p class="texto-suave" style="margin:.75rem 0 0;font-size:.88rem">Encuadre la cédula dentro del marco, con buena luz y sin reflejos.</p>
            <x-slot:pie><button type="button" class="btn btn-primario" data-camara-capturar>Tomar foto</button></x-slot:pie>
        </x-modal>

        <div class="acciones-paso">
            <x-boton tipo="secundario" :href="route('postulacion.paso', 2)">Volver</x-boton>
            <x-boton type="submit">Continuar</x-boton>
        </div>
    </form>
</x-postulacion.marco>
@endsection
