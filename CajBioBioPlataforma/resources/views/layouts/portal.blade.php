@php
    $u = Auth::user();
    $noLeidas = \App\Models\Notificacion::where('usuario_destino_id', $u->id)->whereNull('leida_at')->count();
    $notificacionesRecientes = \App\Models\Notificacion::where('usuario_destino_id', $u->id)->orderByDesc('created_at')->orderByDesc('id')->take(5)->get();
    $urlLeerTodas = Route::has('notificaciones.leer-todas') ? route('notificaciones.leer-todas') : '';
    $urlNotificaciones = Route::has('notificaciones.index') ? route('notificaciones.index') : '#';
    $etiquetaAbogado = ($u->rol === 'abogado' && $u->abogado?->es_segunda_instancia)
        ? 'Abogado Jefe 2ª Instancia'
        : 'Abogado Jefe Consultorio';
    $etiquetasRol = [
        'administrador' => 'Administrador',
        'secretaria' => ($u->region && $u->region->codigo === 'BI') ? 'Secretaría Biobío (Central)' : 'Secretaría regional',
        'juridica_central' => 'Secretaría Biobío (Jurídica Central)',
        'director_regional' => 'Director Regional',
        'jefe_ucoe' => 'Jefe de UCOE Biobío',
        'director_general' => 'Director General',
        'abogado' => $etiquetaAbogado,
        'practicante' => 'Postulante',
        'postulante' => 'Postulante',
        'gobernador' => 'Gobernador regional',
    ];
    $iconos = [
        'inicio' => 'M3 11l9-8 9 8v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z',
        'lista' => 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01',
        'maletin' => 'M4 8h16a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1zM9 8V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3',
        'reloj' => 'M12 7v5l3 2M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z',
        'edificio' => 'M4 21V5a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v16M14 10h5a1 1 0 0 1 1 1v10M3 21h18M8 8h2M8 12h2M8 16h2',
    ];
    $menu = match ($u->rol) {
        'administrador' => [
            ['admin.inicio', 'Panel de Control', 'inicio'],
            ['admin.practicantes.index', 'Postulantes', 'lista'],
            ['admin.consultorios.index', 'Consultorios y Cupos', 'edificio'],
            ['admin.usuarios.index', 'Usuarios y Roles', 'maletin'],
            ['admin.estadisticas.index', 'Métricas Globales', 'reloj'],
            ['admin.auditoria.index', 'Log de Auditoría', 'lista'],
        ],
        'secretaria' => array_merge([
            ['secretaria.panel', 'Panel general', 'inicio'],
            ['secretaria.postulaciones', ($u->region && $u->region->codigo === 'BI') ? 'Postulaciones Biobío' : 'Postulaciones', 'lista'],
            ['secretaria.practicas', 'Prácticas activas', 'maletin'],
            ['secretaria.historial', 'Historial', 'reloj'],
            ['secretaria.consultorios', 'Consultorios', 'edificio'],
        ], ($u->region && $u->region->codigo === 'BI') ? [
            ['secretaria.juridica.postulaciones', 'Supervisión Nacional', 'lista'],
            ['secretaria.juridica.index', 'Jurídica Central', 'maletin'],
        ] : []),
        'juridica_central' => [
            ['secretaria.panel', 'Panel general', 'inicio'],
            ['secretaria.postulaciones', 'Postulaciones Biobío', 'lista'],
            ['secretaria.practicas', 'Prácticas activas', 'maletin'],
            ['secretaria.juridica.postulaciones', 'Supervisión Nacional', 'lista'],
            ['secretaria.juridica.index', 'Jurídica Central', 'maletin'],
            ['secretaria.historial', 'Historial', 'reloj'],
            ['secretaria.consultorios', 'Consultorios', 'edificio'],
        ],
        'abogado' => [['tutor.inicio', ($u->abogado?->es_segunda_instancia ? 'Segunda Instancia (Corte)' : 'Mis postulantes'), 'inicio']],
        'practicante', 'postulante' => (function () use ($u) {
            $tienePractica = $u->practicante?->practicas()->exists();
            $practicaActiva = $u->practicante?->practicas()->latest('id')->first();
            $esCancelada = $practicaActiva && $practicaActiva->estado === 'cancelada';
            $tienePostulacion = \App\Models\Postulacion::whereHas('postulante', fn ($q) => $q->where('rut', $u->rut))->exists()
                || \App\Models\PostulacionBorrador::where('usuario_id', $u->id)->exists();

            $links = [];
            if ($tienePostulacion || ! $tienePractica || $esCancelada) {
                $links[] = ['postulante.inicio', 'Mi postulación', 'lista'];
            }
            if ($tienePractica) {
                $links[] = ['practicante.inicio', 'Mi práctica', 'maletin'];
            }

            return $links ?: [['postulante.inicio', 'Mi postulación', 'inicio']];
        })(),
        'director_regional' => [
            ['director.inicio', 'Validación de Evaluaciones', 'inicio'],
            ['director.seguimiento', 'Seguimiento de Prácticas', 'maletin'],
            ['director.consultorios', 'Consultorios y Cupos', 'edificio'],
        ],
        'jefe_ucoe' => [
            ['director.inicio', 'Validación de Evaluaciones', 'inicio'],
            ['director.seguimiento', 'Seguimiento de Prácticas', 'maletin'],
            ['director.colegiada', 'Revisión Colegiada UCOE', 'lista'],
            ['director.consultorios', 'Consultorios y Cupos', 'edificio'],
        ],
        'director_general' => [
            ['director_general.inicio', 'Bandeja Firma Final (FirmaGob)', 'inicio'],
            ['director_general.seguimiento', 'Seguimiento Nacional', 'maletin'],
            ['director_general.consultorios', 'Sedes Nacionales', 'edificio'],
        ],
        'gobernador' => [['gobernador.inicio', 'Resumen regional', 'inicio']],
        default => [],
    };
    $menu = array_values(array_filter($menu, fn ($i) => Route::has($i[0])));
    $migas = $migas ?? [];
    try { $inicioUrl = $u->portalInicio(); } catch (\Throwable) { $inicioUrl = url('/'); }
@endphp
<!DOCTYPE html>
<html lang="es-CL">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Portal') · CAJ Biobío</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
<a href="#contenido" class="saltar">Saltar al contenido</a>
<div class="portal">
    <div id="velo" class="velo" aria-hidden="true"></div>
    <aside id="sidebar" class="sidebar" aria-label="Navegación del portal">
        <div class="franja" aria-hidden="true"></div>
        <div class="sidebar-marca">
            <a href="{{ $inicioUrl }}" class="marca">
                <img src="{{ asset('img/logo-caj.png') }}" alt="Corporación de Asistencia Judicial del Biobío" width="48" height="44" class="marca-logo marca-logo-portal">
                <span class="marca-texto" aria-hidden="true"><strong>CAJ Biobío</strong><span>Gestión de postulantes</span></span>
            </a>
        </div>
        <nav class="nav">
            <p class="nav-titulo">{{ $etiquetasRol[$u->rol] ?? 'Portal' }}</p>
            @foreach ($menu as [$ruta, $texto, $icono])
                <a href="{{ route($ruta) }}" @if (request()->routeIs($ruta)) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $iconos[$icono] }}"/></svg>
                    {{ $texto }}
                </a>
            @endforeach
        </nav>
        <div class="sidebar-pie">Corporación de Asistencia Judicial</div>
    </aside>

    <div class="portal-principal">
        <header class="topbar">
            <button id="menu-toggle" type="button" class="topbar-menu" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="topbar-usuario">
                <div class="campana-contenedor" id="contenedor-notificaciones">
                    <button type="button" class="campana" id="btn-campana" aria-label="Notificaciones{{ $noLeidas ? ': '.$noLeidas.' sin leer' : '' }}" aria-expanded="false" aria-haspopup="true" aria-controls="dropdown-notificaciones" data-url-leer="{{ $urlLeerTodas }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.9 1.9 0 0 0 3.4 0"/></svg>
                        @if ($noLeidas > 0)<span class="campana-contador" id="campana-contador" aria-hidden="true">{{ $noLeidas > 99 ? '99+' : $noLeidas }}</span>@endif
                    </button>
                    <div class="campana-dropdown" id="dropdown-notificaciones" role="menu" aria-label="Notificaciones recientes" hidden>
                        <div class="campana-dropdown-cabecera">
                            <div class="campana-dropdown-titulo">
                                <strong>Notificaciones</strong>
                                @if ($noLeidas > 0)
                                    <span class="campana-dropdown-badge" id="dropdown-badge-noleidas">{{ $noLeidas }} {{ $noLeidas === 1 ? 'nueva' : 'nuevas' }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="campana-dropdown-cuerpo">
                            @forelse ($notificacionesRecientes as $n)
                                @php $nueva = is_null($n->leida_at); @endphp
                                <div class="campana-dropdown-item {{ $nueva ? 'es-nueva' : '' }}">
                                    <div class="campana-dropdown-item-header">
                                        <span class="campana-dropdown-punto" aria-hidden="true"></span>
                                        <span class="campana-dropdown-item-titulo">{{ $n->titulo }}</span>
                                        <span class="campana-dropdown-fecha">{{ $n->created_at?->diffForHumans(null, true) ?? '' }}</span>
                                    </div>
                                    <p class="campana-dropdown-item-msg">{{ \Illuminate\Support\Str::limit($n->mensaje, 90) }}</p>
                                </div>
                            @empty
                                <div class="campana-dropdown-vacio">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.9 1.9 0 0 0 3.4 0"/></svg>
                                    <p>No tienes notificaciones recientes</p>
                                </div>
                            @endforelse
                        </div>
                        <div class="campana-dropdown-pie">
                            <a href="{{ $urlNotificaciones }}" class="campana-dropdown-ver-mas">
                                <span>Ver más</span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="usuario-datos">
                    <strong>{{ $u->nombre_completo }}</strong>
                    <span>{{ $etiquetasRol[$u->rol] ?? $u->rol }}@if ($u->region) · {{ $u->region->nombre }}@endif</span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-boton type="submit" tipo="secundario" tam="sm">Salir</x-boton>
                </form>
            </div>
        </header>
        <div class="franja" aria-hidden="true"></div>

        @if ($migas)
            <nav class="migas" aria-label="Ubicación">
                <ol>
                    <li><a href="{{ $inicioUrl }}">Inicio</a></li>
                    @foreach ($migas as $i => $m)
                        @php [$texto, $url] = is_array($m) ? [$m[0], $m[1] ?? null] : [$m, null]; @endphp
                        <li>@if ($url && ! $loop->last)<a href="{{ $url }}">{{ $texto }}</a>@else<span aria-current="page">{{ $texto }}</span>@endif</li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <main id="contenido" class="portal-contenido">
            @if (session('ok'))<div class="alerta alerta-ok" role="status">{{ session('ok') }}</div>@endif
            @if (session('error'))<div class="alerta alerta-error" role="alert">{{ session('error') }}</div>@endif
            @if ($errors->any())
                <div class="alerta alerta-error" role="alert"><div><strong>Revisa lo siguiente para continuar.</strong><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div></div>
            @endif
            @yield('contenido')
        </main>
    </div>
</div>
</body>
</html>
