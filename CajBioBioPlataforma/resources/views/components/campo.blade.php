@props(['name', 'label', 'type' => 'text', 'value' => null, 'obligatorio' => false, 'ayuda' => null, 'opciones' => null, 'placeholder' => null, 'as' => null])
@php
    $id = $attributes->get('id', 'campo-'.str_replace(['[', ']', '.'], '-', $name));
    $error = $errors->first(str_replace(['[', ']'], ['.', ''], $name));
    $valor = old(str_replace(['[', ']'], ['.', ''], $name), $value);
    $tag = $as ?? ($opciones !== null ? 'select' : ($type === 'textarea' ? 'textarea' : 'input'));
    $descritoPor = trim(($ayuda ? "$id-ayuda " : '')."$id-error");
    $comunes = ['id' => $id, 'name' => $name, 'class' => 'campo-control', 'aria-describedby' => $descritoPor];
@endphp
<div class="campo {{ $error ? 'tiene-error' : '' }}">
    <label for="{{ $id }}" class="campo-etiqueta">{{ $label }}@if ($obligatorio)<span class="campo-obligatorio" aria-hidden="true">*</span><span class="sr-only"> (obligatorio)</span>@endif</label>
    @if ($tag === 'select')
        <select {{ $attributes->except('id')->merge($comunes) }} @required($obligatorio) @if ($error) aria-invalid="true" @endif>
            @if ($placeholder !== false)<option value="">{{ $placeholder ?? 'Selecciona una opción' }}</option>@endif
            @foreach ($opciones ?? [] as $v => $texto)
                <option value="{{ $v }}" @selected((string) $valor === (string) $v)>{{ $texto }}</option>
            @endforeach
            {{ $slot }}
        </select>
    @elseif ($tag === 'textarea')
        <textarea {{ $attributes->except('id')->merge($comunes) }} placeholder="{{ $placeholder }}" @required($obligatorio) @if ($error) aria-invalid="true" @endif>{{ $valor }}</textarea>
    @else
        <input type="{{ $type }}" {{ $attributes->except('id')->merge($comunes) }} @if ($type !== 'password' && $type !== 'file') value="{{ $valor }}" @endif placeholder="{{ $placeholder }}" @required($obligatorio) @if ($error) aria-invalid="true" @endif>
    @endif
    @if ($ayuda)<p id="{{ $id }}-ayuda" class="campo-ayuda">{{ $ayuda }}</p>@endif
    <p id="{{ $id }}-error" class="campo-error" role="alert">{{ $error }}</p>
</div>
