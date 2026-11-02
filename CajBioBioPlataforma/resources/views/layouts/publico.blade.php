<!DOCTYPE html>
<html lang="es-CL">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Gestión de postulantes') · CAJ Biobío</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('img/logo-caj.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="@yield('clase-body')">
    <a href="#contenido" class="saltar">Saltar al contenido</a>
    <div class="franja-gob"><div class="contenedor">Servicio público relacionado con el Ministerio de Justicia y Derechos Humanos</div></div>
    <header class="cabecera-publica">
        <div class="contenedor">
            <a href="{{ route('inicio') }}" class="marca" aria-label="Corporación de Asistencia Judicial del Biobío, inicio">
                <img src="{{ asset('img/logo-caj.png') }}" alt="Corporación de Asistencia Judicial del Biobío" width="53" height="48" class="marca-logo">
                <span class="marca-texto" aria-hidden="true"><strong>CAJ Biobío</strong><span>Corporación de Asistencia Judicial</span></span>
            </a>
            @if (request()->routeIs('inicio'))
            <nav class="menu-publico" aria-label="Secciones">
                <a href="#proceso">Cómo postular</a><a href="#consultorios">Consultorios</a><a href="#contacto">Contacto</a>
            </nav>
            @endif
            <nav class="flex items-center gap-2" aria-label="Principal">
                @if (Route::has('postulacion.inicio'))
                    <x-boton tipo="fantasma" :href="route('postulacion.inicio')" class="hidden sm:inline-flex">Postular</x-boton>
                @endif
                @auth
                    <x-boton :href="Auth::user()->portalInicio()">Mi portal</x-boton>
                @else
                    <x-boton :href="route('login')">Ingresar</x-boton>
                @endauth
            </nav>
        </div>
    </header>

    <main id="contenido">
        @if (session('ok'))
            <div class="contenedor" style="padding-top:1rem">
                @if (session('ok'))<div class="alerta alerta-ok" role="status">{{ session('ok') }}</div>@endif
            </div>
        @endif
        @yield('contenido')
    </main>

    @hasSection('pie')
        @yield('pie')
    @else
    <footer class="pie-publico">
        <div class="contenedor">
            <div class="pie-identidad">
                <a href="{{ route('inicio') }}" class="pie-sello"><img src="{{ asset('img/logo-caj.png') }}" alt="Corporación de Asistencia Judicial del Biobío" width="44" height="40" class="marca-logo marca-logo-pie"></a>
                <div class="pie-identidad-texto">
                    <a href="{{ route('inicio') }}" class="pie-titulo">Corporación de Asistencia Judicial del Biobío</a>
                    <p>Plataforma de gestión de prácticas profesionales de postulantes al título de abogado.<br><span class="no-corta">Sitio institucional: <a href="https://cajbiobio.cl" rel="noopener">cajbiobio.cl</a>.</span></p>
                </div>
            </div>
            <nav aria-label="Enlaces del pie">
                <p style="color:var(--azul-900);font-weight:600;margin:0 0 .5rem">Accesos</p>
                <ul style="list-style:none;margin:0;padding:0;display:grid;gap:.4rem">
                    @if (Route::has('postulacion.inicio'))<li><a href="{{ route('postulacion.inicio') }}">Postular a la práctica</a></li>@endif
                    <li><a href="{{ route('login') }}">Ingresar al sistema</a></li>
                </ul>
            </nav>
            <p style="font-size:.85rem;margin:0">© {{ date('Y') }} Corporación de Asistencia Judicial del Biobío.</p>
        </div>
    </footer>
    @endif
</body>
</html>
