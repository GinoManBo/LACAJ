@extends('layouts.publico')
@section('titulo', 'Postular a la práctica')

@section('contenido')
@php $hayBorrador = session()->has('postulacion'); @endphp
<section class="post-inicio">
    <div class="contenedor post-contenedor">
        <h1>Postula a tu práctica profesional en CAJ Biobío.</h1>
        <p class="post-inicio-bajada">Son cinco pasos y toma unos 10 minutos. Puedes interrumpirlo y volver: tu avance se guarda mientras tengas esta pestaña abierta.</p>

        <ul class="post-requisitos" aria-label="Qué necesitas tener a mano">
            <li>
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M14 10h4M14 14h4M6.5 16c.6-1.4 4.4-1.4 5 0"/></svg>
                <div><strong>Tu cédula de identidad</strong><span>Una foto del frente y otra del reverso. Puedes sacarlas con la cámara del celular.</span></div>
            </li>
            <li>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.8-4 3.5-6 7-6s6.2 2 7 6"/></svg>
                <div><strong>Tus datos y un contacto de emergencia</strong><span>Domicilio, teléfono, universidad y el año que cursas.</span></div>
            </li>
            <li>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-6.5-5.7-6.5-11a6.5 6.5 0 0 1 13 0c0 5.3-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/></svg>
                <div><strong>Los consultorios que prefieres</strong><span>Los ordenas por preferencia y eliges desde qué día laboral quieres comenzar.</span></div>
            </li>
        </ul>

        <div class="post-inicio-acciones">
            @if ($hayBorrador)
                <x-boton :href="route('postulacion.paso', 1)" class="post-cta">Continuar postulación</x-boton>
                <p class="texto-suave">Encontramos una postulación sin terminar en este navegador.</p>
            @else
                <x-boton :href="route('postulacion.paso', 1)" class="post-cta">Ingresar postulación</x-boton>
            @endif
        </div>

        @php $esPostulante = auth()->check() && auth()->user()->rol === 'postulante'; @endphp
        @if ($esPostulante)
            <aside class="post-guardar post-guardar-ingresado" aria-label="Tu avance">
                <p>Ingresaste como <strong>{{ trim(auth()->user()->nombre.' '.auth()->user()->apellido) }}</strong>. Tu avance se guarda automáticamente.</p>
                <p><a href="{{ route('postulante.inicio') }}">Ver mi postulación</a></p>
            </aside>
        @elseif (! auth()->check())
            <aside class="post-guardar" aria-labelledby="guardar-titulo">
                <h2 id="guardar-titulo">Guarda tu avance con ClaveÚnica</h2>
                <p>Si ingresas con ClaveÚnica, tu avance queda guardado en tu perfil y puedes continuar después desde cualquier dispositivo. Es opcional: también puedes postular sin ingresar.</p>
                <div class="cu-centro">
                    @if (\App\Services\ClaveUnica::habilitada())
                        <a href="{{ route('claveunica.redirigir') }}" class="cu-oficial" aria-label="Ingresar con ClaveÚnica para guardar mi avance">
                            <img src="{{ asset('img/claveunica.svg') }}" alt="" width="24" height="24">
                            <span>ClaveÚnica</span>
                        </a>
                    @else
                        <button type="button" disabled aria-disabled="true" aria-describedby="cu-proximamente" class="cu-oficial cu-oficial-inactivo">
                            <img src="{{ asset('img/claveunica.svg') }}" alt="" width="24" height="24">
                            <span>ClaveÚnica</span>
                        </button>
                        <p id="cu-proximamente" class="cu-nota">Disponible próximamente</p>
                    @endif
                </div>
            </aside>
        @endif
    </div>
    <div class="franja" aria-hidden="true"></div>
</section>
@endsection
