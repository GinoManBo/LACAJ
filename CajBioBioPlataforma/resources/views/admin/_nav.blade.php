@php
    $items = [
        ['admin.inicio', 'Resumen', ['admin.inicio']],
        ['admin.practicantes.index', 'Postulantes', ['admin.practicantes.*', 'admin.postulantes.*']],
        ['admin.consultorios.index', 'Consultorios y Sedes', ['admin.consultorios.*', 'admin.capacidad.*']],
        ['admin.usuarios.index', 'Usuarios', ['admin.usuarios.*']],
    ];
@endphp
<nav class="subnav" aria-label="Secciones de administración">
    @foreach ($items as [$ruta, $texto, $patrones])
        @php
            $activo = false;
            foreach ((array)$patrones as $p) {
                if (request()->routeIs($p)) { $activo = true; break; }
            }
        @endphp
        <a href="{{ route($ruta) }}" @if ($activo) aria-current="page" @endif>{{ $texto }}</a>
    @endforeach
</nav>
