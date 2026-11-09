@props([
    'name' => 'universidad',
    'label' => 'Universidad',
    'value' => null,
    'obligatorio' => true,
    'placeholder' => 'Escriba para buscar o seleccionar su universidad...',
    'ayuda' => null,
])

@php
    $id = $attributes->get('id', 'campo-'.str_replace(['[', ']', '.'], '-', $name));
    $error = $errors->first(str_replace(['[', ']'], ['.', ''], $name));
    $valorRaw = old(str_replace(['[', ']'], ['.', ''], $name), $value);
    $universidades = \App\Support\Universidades::todas();

    $esOtra = false;
    $valorOtra = old('universidad_otra');
    $valorInput = $valorRaw;

    if ($valorRaw) {
        if ($valorRaw === 'Otra institución no listada') {
            $esOtra = true;
        } elseif (! in_array($valorRaw, $universidades, true)) {
            $esOtra = true;
            $valorOtra = $valorOtra ?: $valorRaw;
            $valorInput = 'Otra institución no listada';
        }
    }
@endphp

<div class="campo selector-universidad {{ $error ? 'tiene-error' : '' }}" data-selector-universidad>
    <label for="{{ $id }}" class="campo-etiqueta">
        {{ $label }}
        @if ($obligatorio)<span class="campo-obligatorio" aria-hidden="true">*</span><span class="sr-only"> (obligatorio)</span>@endif
    </label>

    <div class="selector-uni-caja">
        <div class="selector-uni-input-wrap">
            <input 
                type="text" 
                id="{{ $id }}"
                class="campo-control selector-uni-busqueda"
                placeholder="{{ $placeholder }}"
                value="{{ $valorInput }}"
                autocomplete="off"
                aria-autocomplete="list"
                aria-controls="{{ $id }}-lista"
                aria-expanded="false"
                role="combobox"
                @required($obligatorio)
                @if ($error) aria-invalid="true" @endif
            >
            <input type="hidden" name="{{ $name }}" class="selector-uni-valor" value="{{ $valorInput }}">

            <button type="button" class="selector-uni-toggle" tabindex="-1" aria-label="Desplegar lista de universidades">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
        </div>

        <div id="{{ $id }}-lista" class="selector-uni-desplegable" role="listbox">
            <div class="selector-uni-opciones">
                @foreach ($universidades as $u)
                    <div 
                        class="selector-uni-opcion {{ $valorInput === $u ? 'es-seleccionada' : '' }}" 
                        role="option" 
                        tabindex="-1"
                        data-valor="{{ $u }}"
                        aria-selected="{{ $valorInput === $u ? 'true' : 'false' }}"
                    >
                        {{ $u }}
                    </div>
                @endforeach
            </div>
            <div class="selector-uni-sin-resultados">
                No se encontraron universidades con ese término de búsqueda.
            </div>
        </div>
    </div>

    @if ($ayuda)
        <p class="campo-ayuda" style="margin-top:.25rem;font-size:.82rem;color:var(--texto-suave);">{{ $ayuda }}</p>
    @endif
    <p id="{{ $id }}-error" class="campo-error" role="alert">{{ $error }}</p>

    {{-- Subcampo para especificar otra universidad no incluida en el listado --}}
    <div class="selector-uni-otra" style="{{ $esOtra ? 'display:block;' : 'display:none;' }} margin-top:.75rem;">
        <label for="{{ $id }}-otra" class="campo-etiqueta" style="font-size:.85rem;font-weight:600;color:var(--azul-900);">
            Nombre de la institución o universidad no listada
            <span class="campo-obligatorio" aria-hidden="true">*</span>
        </label>
        <input 
            type="text" 
            id="{{ $id }}-otra" 
            name="universidad_otra" 
            class="campo-control selector-uni-input-otra"
            placeholder="Ingrese el nombre completo de la casa de estudios"
            value="{{ $valorOtra }}"
            maxlength="160"
            {{ $esOtra ? 'required' : '' }}
        >
        <p class="campo-ayuda" style="margin-top:.25rem;font-size:.8rem;color:var(--texto-suave);">Especifique el nombre oficial de su universidad o casa de estudios.</p>
    </div>
</div>
