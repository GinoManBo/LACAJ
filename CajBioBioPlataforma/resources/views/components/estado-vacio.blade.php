@props(['titulo', 'texto' => null])
<div {{ $attributes->merge(['class' => 'vacio']) }}>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
    <h3>{{ $titulo }}</h3>
    @if ($texto)<p>{{ $texto }}</p>@endif
    {{ $slot }}
</div>
