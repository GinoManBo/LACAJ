@props(['valor', 'etiqueta', 'detalle' => null, 'critico' => false, 'href' => null])
@php $clase = 'indicador'.($critico ? ' indicador-critico' : ''); @endphp
@if ($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => $clase, 'style' => 'text-decoration:none;display:block']) }}>
@else
<div {{ $attributes->merge(['class' => $clase]) }}>
@endif
    <div class="indicador-valor">{{ $valor }}</div>
    <div class="indicador-etiqueta">{{ $etiqueta }}</div>
    @if ($detalle)<div class="indicador-detalle">{{ $detalle }}</div>@endif
@if ($href)</a>@else</div>@endif
