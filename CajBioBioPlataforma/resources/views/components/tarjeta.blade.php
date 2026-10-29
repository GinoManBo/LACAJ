@props(['titulo' => null, 'acento' => false, 'sinPadding' => false])
<section {{ $attributes->merge(['class' => 'tarjeta'.($acento ? ' tarjeta-acento' : '')]) }}>
    @if ($titulo || isset($acciones))
        <header class="tarjeta-cabecera">
            @if ($titulo)<h2>{{ $titulo }}</h2>@endif
            @isset($acciones)<div class="flex gap-2">{{ $acciones }}</div>@endisset
        </header>
    @endif
    <div @class(['tarjeta-cuerpo' => ! $sinPadding])>{{ $slot }}</div>
    @isset($pie)<footer class="tarjeta-pie">{{ $pie }}</footer>@endisset
</section>
