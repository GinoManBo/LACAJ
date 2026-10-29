@props(['tipo' => 'primario', 'href' => null, 'type' => 'button', 'tam' => null, 'bloque' => false])
@php
    $clases = 'btn btn-'.$tipo.($tam === 'sm' ? ' btn-sm' : '').($bloque ? ' btn-bloque' : '');
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $clases]) }}>{{ $slot }}</button>
@endif
