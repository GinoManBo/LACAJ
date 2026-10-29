@php
    $items = [
        ['admin.inicio', 'Resumen', 'admin.inicio'],
        ['admin.practicantes.index', 'Practicantes', 'admin.practicantes.*'],
        ['admin.capacidad.index', 'Capacidad', 'admin.capacidad.*'],
        ['admin.usuarios.index', 'Usuarios', 'admin.usuarios.*'],
    ];
@endphp
<nav class="subnav" aria-label="Secciones de administración">
    @foreach ($items as [$ruta, $texto, $patron])
        <a href="{{ route($ruta) }}" @if (request()->routeIs($patron)) aria-current="page" @endif>{{ $texto }}</a>
    @endforeach
</nav>
