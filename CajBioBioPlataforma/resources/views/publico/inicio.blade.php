@extends('layouts.publico')
@section('titulo', 'Práctica profesional en asistencia judicial')

@section('contenido')
@php $postula = Route::has('postulacion.inicio'); @endphp

<section class="portada">
    <div class="contenedor portada-grid">
        <div class="portada-texto">
            <h1>Práctica profesional para postular al título de abogado en la Corporación de Asistencia Judicial del Biobío</h1>
            <p class="portada-bajada">Postula en línea para realizar tu práctica en nuestras oficinas de atención y sigue el estado de tu proceso desde un solo lugar.</p>
            <div class="portada-acciones">
                @if ($postula)<x-boton :href="route('postulacion.inicio')" class="post-cta">Postular a la práctica</x-boton>@endif
                <x-boton tipo="secundario" :href="route('login')" class="post-cta">Ingresar</x-boton>
            </div>
            @if (config('claveunica.enabled'))
                <div class="portada-cu"><x-boton :href="route('claveunica.redirigir')" class="btn-claveunica">Ingresar con ClaveÚnica</x-boton></div>
            @endif
        </div>
        <div class="portada-ilustracion" aria-hidden="true">
            <x-ilustracion-tribunal />
        </div>
    </div>
</section>

<section class="seccion seccion-gris" id="institucion">
    <div class="contenedor institucion">
        <div>
            <h2>Quiénes somos</h2>
            <p class="lectura">Somos un servicio público autónomo relacionado con el Ministerio de Justicia y Derechos Humanos, que garantiza protección legal a personas en situación de vulnerabilidad. Nuestro equipo reúne abogados, trabajadores sociales, psicólogos, técnicos jurídicos y personal administrativo.</p>
            <p class="lectura"><strong>Misión:</strong> materializar el acceso a la justicia para personas con necesidades jurídicas, especialmente en condiciones de vulnerabilidad.</p>
        </div>
        <div>
            <h3 class="subtitulo">Lo que hacemos</h3>
            <ul class="servicios">
                <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v15M7 19h10M5 8l7-2 7 2M5 8l-2.5 5a2.8 2.8 0 0 0 5 0L5 8zM19 8l-2.5 5a2.8 2.8 0 0 0 5 0L19 8z"/></svg>Asesoría jurídica, social y judicial</li>
                <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l4 4v14H7zM14 3v4h4M10 12h5M10 16h5"/></svg>Atención en materias de familia, laboral, civil y penal</li>
                <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10l9-6 9 6M5 10v8M9.5 10v8M14.5 10v8M19 10v8M3 20h18"/></svg>Orientación jurídica comunitaria</li>
                <li><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20c.7-3.5 3-5 6-5s5.3 1.5 6 5M16 5.5a3 3 0 0 1 0 5M18 15c1.8.600 2.8 2 3 5"/></svg>Unidad de migrantes y reinserción social (convenio con Gendarmería)</li>
            </ul>
        </div>
    </div>
</section>

<section class="seccion" id="proceso" lang="es">
    <div class="contenedor seccion-centrada">
        <h2>Cómo funciona la postulación</h2>
        <p class="lectura texto-suave">El proceso es en línea y se completa en cinco pasos. La secretaría de tu región revisa cada postulación.</p>
        @php
            $detalle = [
                1 => 'Lee la carta de compromiso y acepta los términos de la práctica.',
                2 => 'Ingresa tu RUT, tus datos de contacto y un contacto de emergencia.',
                3 => 'Indica tu universidad y especialidad de interés, y adjunta tu cédula de identidad por ambos lados.',
                4 => 'Ordena por preferencia los consultorios donde quieres practicar y elige la fecha de inicio.',
                5 => 'Revisa lo ingresado y envía la postulación. La secretaría te responderá.',
            ];
        @endphp
        <ol class="ruta" data-ruta>
            @foreach ($pasos as $n => $nombre)
                <li class="ruta-paso" style="--i: {{ $loop->index }}">
                    <div class="ruta-card">
                        <span class="ruta-num" aria-hidden="true">{{ $n }}</span>
                        <h3><span class="sr-only">Paso {{ $n }}: </span>{{ $nombre }}</h3>
                        <p>{{ $detalle[$n] ?? '' }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
        <div class="ruta-cta">
            @if ($postula)<x-boton :href="route('postulacion.inicio')">Comenzar postulación</x-boton>@endif
            <a href="https://cajbiobio.cl/practica_profesional/" rel="noopener">Guía de práctica profesional: requisitos, plazos y condiciones</a>
        </div>
    </div>
</section>

<section class="seccion seccion-gris" id="consultorios">
    <div class="contenedor seccion-centrada">
        <h2>Consultorios y oficinas de práctica</h2>
        <p class="lectura texto-suave">{{ $totalConsultorios }} {{ $totalConsultorios === 1 ? 'oficina registrada' : 'oficinas registradas' }} en el sistema. En la postulación eliges cuáles prefieres, por orden.</p>
        <div class="regiones">
            @forelse ($regiones as $region)
                @php $lista = $consultorios[$region->id]; @endphp
                <details class="region" @if ($loop->first) open @endif>
                    <summary><span>{{ $region->nombre }}</span><span class="region-cuenta">{{ $lista->count() }}</span></summary>
                    <ul class="oficinas">
                        @foreach ($lista as $c)
                            <li>
                                <strong>{{ $c->nombre }}</strong>
                                <span>{{ $c->direccion }}@if ($c->comuna), {{ $c->comuna->nombre }}@endif</span>
                            </li>
                        @endforeach
                    </ul>
                </details>
            @empty
                <p class="texto-suave">Pronto publicaremos las oficinas disponibles.</p>
            @endforelse
        </div>
    </div>
</section>

<section class="seccion-azul" id="contacto">
    <div class="contenedor contacto">
        <div>
            <h2>Contacto</h2>
            <dl class="contacto-datos">
                <div><dt>Dirección</dt><dd>Freire N° 1220, Concepción</dd></div>
                <div><dt>Teléfonos</dt><dd><a href="tel:+56412735950">(41) 273 5950</a> / <a href="tel:+56412735951">(41) 273 5951</a></dd></div>
                <div><dt>Correo</dt><dd><a href="mailto:{{ config('caj.correo_contacto', 'postulantes@cajbiobio.cl') }}">{{ config('caj.correo_contacto', 'postulantes@cajbiobio.cl') }}</a></dd></div>
            </dl>
        </div>
        <div class="contacto-ingreso">
            <h3>¿Ya postulaste o estás realizando tu práctica?</h3>
            <p>Ingresa para revisar el estado de tu postulación, tu práctica y tus documentos.</p>
            <x-boton tipo="secundario" :href="route('login')" class="btn-sobre-azul">Ingresar al sistema</x-boton>
        </div>
    </div>
</section>
@endsection
