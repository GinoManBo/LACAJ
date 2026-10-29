@extends('layouts.publico')
@section('titulo', 'Mi postulación')

@php
    $nombre = trim($usuario->nombre.' '.$usuario->apellido);
    $estado = $enviada?->estado;
    $cerrada = in_array($estado, ['rechazada', 'retirada'], true);
    $mostrarAvance = ! $enviada || ($cerrada && $borrador);
    $completos = max(0, min(4, $siguiente - 1));
    $porcentaje = $completos * 25;
    $listaParaEnviar = $siguiente >= 5;
    $nombreSiguiente = $pasos[$siguiente] ?? 'Resumen';
    $mensajes = [
        'pendiente' => 'En revisión por la secretaría',
        'derivada' => 'En revisión: derivada a la secretaría de tu región',
        'aceptada' => 'Aceptada',
        'rechazada' => 'No aceptada',
        'retirada' => 'Retirada',
    ];
@endphp

@section('contenido')
<section class="mp">
    <div class="contenedor post-contenedor">

        <header class="mp-saludo">
            <div class="mp-saludo-texto">
                <p class="mp-etiqueta">Mi postulación</p>
                <h1>Hola, {{ $nombre }}</h1>
                <p class="mp-rut">RUT <span>{{ \App\Support\Rut::formatear($usuario->rut) }}</span></p>
            </div>
            <div class="mp-saludo-acciones">
                <a href="{{ route('inicio') }}" class="btn btn-fantasma btn-sm">Ir al inicio</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-boton tipo="secundario" tam="sm" type="submit">Cerrar sesión</x-boton>
                </form>
            </div>
        </header>

        @if (session('aviso'))
            <div class="alerta alerta-info" role="status">{{ session('aviso') }}</div>
        @endif

        @if ($enviada)
            <x-tarjeta :acento="true" class="mp-enviada" aria-labelledby="mp-enviada-titulo">
                <div class="mp-enviada-cab">
                    <h2 id="mp-enviada-titulo">
                        @if ($enRevision) Tu postulación fue enviada
                        @elseif ($estado === 'aceptada') Tu postulación fue aceptada
                        @elseif ($estado === 'rechazada') Tu postulación no fue aceptada
                        @else Tu postulación fue retirada @endif
                    </h2>
                    <x-badge :estado="$estado === 'retirada' ? 'finalizada' : $estado" :texto="$mensajes[$estado] ?? ucfirst($estado)" />
                </div>
                <dl class="mp-datos">
                    <div><dt>Fecha de envío</dt><dd>{{ $enviada->fecha_postulacion?->format('d-m-Y') }}</dd></div>
                    @if ($enviada->preferencias->isNotEmpty())
                        <div>
                            <dt>Consultorios elegidos, por orden de preferencia</dt>
                            <dd>
                                <ol class="mp-prefs">
                                    @foreach ($enviada->preferencias as $p)
                                        <li><span class="mp-pref-n" aria-hidden="true">{{ $loop->iteration }}</span><span class="mp-pref-nombre">{{ $p->consultorio?->nombre ?? 'Consultorio' }}</span></li>
                                    @endforeach
                                </ol>
                            </dd>
                        </div>
                    @endif
                </dl>
                @if ($enRevision)
                    <p class="texto-suave mp-nota">No necesitas hacer nada más por ahora. El resultado llegará a tu correo electrónico en un plazo máximo de 15 días hábiles.</p>
                    <div class="mp-editar">
                        <x-boton tipo="secundario" :href="route('postulante.datos')">Editar mis datos</x-boton>
                        <p class="texto-suave">Puedes corregirlos mientras la postulación esté en revisión.</p>
                    </div>
                @elseif ($estado === 'aceptada')
                    <p class="texto-suave mp-nota">Tu postulación fue aceptada. Recibirás tus accesos y las indicaciones para comenzar por correo electrónico.</p>
                @else
                    <p class="texto-suave mp-nota">
                        {{ $estado === 'rechazada' ? 'Esta postulación no fue aceptada.' : 'Esta postulación fue retirada.' }}
                        Puedes presentar una nueva cuando quieras.
                    </p>
                    @unless ($borrador)
                        <x-boton tipo="primario" :href="route('postulacion.paso', 1)">Postular nuevamente</x-boton>
                    @endunless
                @endif
            </x-tarjeta>
        @endif

        @if ($mostrarAvance)
            <x-tarjeta :acento="true" class="mp-avance" aria-labelledby="mp-avance-titulo">
                <div class="mp-avance-cab">
                    <h2 id="mp-avance-titulo">
                        @if ($cerrada) Postular nuevamente
                        @elseif ($listaParaEnviar) Tu postulación está lista para enviar
                        @elseif ($borrador) Continúa tu postulación
                        @else Comienza tu postulación @endif
                    </h2>
                    <p class="mp-progreso-texto"><strong>{{ $completos }} de 4</strong> pasos completos</p>
                </div>

                <div class="mp-barra" role="progressbar" aria-label="Avance de la postulación" aria-valuemin="0" aria-valuemax="4" aria-valuenow="{{ $completos }}" aria-valuetext="{{ $completos }} de 4 pasos completos">
                    <span style="width: {{ $porcentaje }}%"></span>
                </div>

                @if ($borrador)
                    <p class="mp-guardado texto-suave">
                        Último avance guardado: <time datetime="{{ $borrador->updated_at->toIso8601String() }}" title="{{ $borrador->updated_at->format('d-m-Y H:i') }}">{{ $borrador->updated_at->locale('es')->diffForHumans() }} ({{ $borrador->updated_at->format('d-m-Y H:i') }})</time>.
                        Lo que ya completaste se conserva.
                    </p>
                @else
                    <p class="mp-guardado texto-suave">Son cuatro pasos y un resumen final. Tu avance se guarda en tu perfil y puedes retomarlo cuando quieras.</p>
                @endif

                <nav aria-label="Pasos de la postulación">
                    <ol class="mp-pasos">
                        @foreach ($pasos as $n => $titulo)
                            @php
                                $hecho = $n < $siguiente;
                                $actual = $n === $siguiente;
                                $esResumen = $n === 5;
                            @endphp
                            <li class="mp-paso {{ $hecho ? 'es-hecho' : '' }} {{ $actual ? 'es-actual' : '' }}" @if ($actual) aria-current="step" @endif>
                                <span class="mp-paso-marca" aria-hidden="true">
                                    @if ($hecho)<svg viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>@else{{ $n }}@endif
                                </span>
                                <span class="mp-paso-nombre">
                                    {{ $esResumen ? 'Revisar y enviar' : $titulo }}
                                </span>
                                <span class="mp-paso-estado {{ $hecho ? 'ok' : '' }}">
                                    @if ($hecho) Completo
                                    @elseif ($esResumen) {{ $listaParaEnviar ? 'Falta enviarla' : 'Al final' }}
                                    @elseif ($actual) Pendiente · siguiente
                                    @else Pendiente @endif
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </nav>

                @if ($listaParaEnviar)
                    <div class="alerta alerta-info" role="status">
                        <strong>Último paso: envía tu postulación.</strong>
                        Ya completaste todos los datos. Revisa el resumen y confirma el envío; hasta que lo hagas, la secretaría no la recibe.
                    </div>
                @endif

                @unless ($listaParaEnviar)
                    <p class="mp-faltan texto-suave">
                        Te falta completar:
                        <strong>{{ collect($pasos)->only(range($siguiente, 4))->implode(', ') }}</strong>.
                    </p>
                @endunless

                <div class="mp-cta">
                    <x-boton :href="route('postulacion.paso', $siguiente)" class="post-cta">
                        @if ($listaParaEnviar) Revisar y enviar mi postulación
                        @elseif (! $borrador && $siguiente === 1) Comenzar con el paso 1: {{ $nombreSiguiente }}
                        @else Continuar con el paso {{ $siguiente }}: {{ $nombreSiguiente }} @endif
                    </x-boton>
                </div>
            </x-tarjeta>
        @endif

        <p class="mp-aviso texto-suave">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
            <span>Ingresaste con ClaveÚnica, por eso tu RUT ({{ \App\Support\Rut::formatear($usuario->rut) }}) es el de tu cuenta y no se puede cambiar en la postulación.</span>
        </p>
    </div>
    <div class="franja" aria-hidden="true"></div>
</section>
@endsection
