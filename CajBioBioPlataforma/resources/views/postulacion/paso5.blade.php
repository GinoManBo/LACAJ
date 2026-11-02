@extends('layouts.publico')
@section('titulo', 'Resumen y envío')

@php
    $generos = ['femenino' => 'Femenino', 'masculino' => 'Masculino', 'otro' => 'Otro', 'prefiere_no_decir' => 'Prefiero no decirlo'];
    $etiquetasArchivo = [
        'cedula_frontal' => 'Cédula, lado frontal', 'cedula_reverso' => 'Cédula, lado reverso',
        'certificado_egreso' => 'Certificado de egreso',
    ];
    $fmtFecha = fn ($f) => $f ? \Illuminate\Support\Carbon::parse($f)->translatedFormat('l j \d\e F \d\e Y') : '—';
    $fila = fn ($k, $val) => ['k' => $k, 'v' => ($val === null || $val === '') ? '—' : $val];
@endphp

@section('contenido')
<x-postulacion.marco :n="$n" :pasos="$pasos" :permitido="$permitido" titulo="Revise su postulación" bajada="Si algo no está bien, use Editar en la sección correspondiente. Al confirmar, la postulación llega a la secretaría de la región.">
    <div class="resumen">
        <x-tarjeta titulo="Datos personales">
            <x-slot:acciones><a class="btn btn-fantasma btn-sm" href="{{ route('postulacion.paso', 2) }}">Editar<span class="sr-only"> datos personales</span></a></x-slot:acciones>
            <dl class="datos">
                @foreach ([
                    $fila('Nombre', trim(($datos['nombre'] ?? '').' '.($datos['apellido'] ?? ''))),
                    $fila('RUT', $datos['rut'] ?? null),
                    $fila('Fecha de nacimiento', isset($datos['fecha_nacimiento']) ? \Illuminate\Support\Carbon::parse($datos['fecha_nacimiento'])->translatedFormat('j \d\e F \d\e Y') : null),
                    $fila('Género', $generos[$datos['genero'] ?? ''] ?? null),
                    $fila('Nacionalidad', $datos['nacionalidad'] ?? null),
                    $fila('Correo', $datos['email'] ?? null),
                    $fila('Teléfono', $datos['telefono'] ?? null),
                ] as $f)
                    <div><dt>{{ $f['k'] }}</dt><dd>{{ $f['v'] }}</dd></div>
                @endforeach
            </dl>
        </x-tarjeta>

        <x-tarjeta titulo="Accesibilidad y salud">
            <x-slot:acciones><a class="btn btn-fantasma btn-sm" href="{{ route('postulacion.paso', 2) }}">Editar<span class="sr-only"> accesibilidad y salud</span></a></x-slot:acciones>
            <dl class="datos">
                <div><dt>Discapacidad</dt><dd>{{ ! empty($datos['discapacidad']) ? 'Sí' : 'No' }}</dd></div>
                @if (! empty($datos['discapacidad']))<div class="ancho-completo"><dt>Descripción</dt><dd>{{ $datos['discapacidad_descripcion'] ?? '—' }}</dd></div>@endif
                <div class="ancho-completo"><dt>Antecedentes de salud</dt><dd>{{ $datos['antecedentes_salud'] ?? '—' }}</dd></div>
            </dl>
        </x-tarjeta>

        <x-tarjeta titulo="Domicilio">
            <x-slot:acciones><a class="btn btn-fantasma btn-sm" href="{{ route('postulacion.paso', 2) }}">Editar<span class="sr-only"> domicilio</span></a></x-slot:acciones>
            <dl class="datos">
                <div><dt>Región</dt><dd>{{ $region?->nombre ?? '—' }}</dd></div>
                <div><dt>Comuna</dt><dd>{{ $comuna?->nombre ?? '—' }}</dd></div>
                <div class="ancho-completo"><dt>Dirección</dt><dd>{{ $datos['direccion'] ?? '—' }}</dd></div>
            </dl>
        </x-tarjeta>

        <x-tarjeta titulo="Contacto de emergencia">
            <x-slot:acciones><a class="btn btn-fantasma btn-sm" href="{{ route('postulacion.paso', 2) }}">Editar<span class="sr-only"> contacto de emergencia</span></a></x-slot:acciones>
            <dl class="datos">
                <div><dt>Nombre</dt><dd>{{ $datos['emergencia_nombre'] ?? '—' }}</dd></div>
                <div><dt>Vínculo</dt><dd>{{ $datos['emergencia_vinculo'] ?? '—' }}</dd></div>
                <div><dt>Teléfono</dt><dd>{{ $datos['emergencia_telefono'] ?? '—' }}</dd></div>
                <div><dt>Correo</dt><dd>{{ ($datos['emergencia_email'] ?? '') ?: '—' }}</dd></div>
            </dl>
        </x-tarjeta>

        <x-tarjeta titulo="Antecedentes educativos">
            <x-slot:acciones><a class="btn btn-fantasma btn-sm" href="{{ route('postulacion.paso', 3) }}">Editar<span class="sr-only"> antecedentes educativos</span></a></x-slot:acciones>
            <dl class="datos">
                <div class="ancho-completo"><dt>Universidad</dt><dd>{{ $educ['universidad'] ?? '—' }}</dd></div>
                <div><dt>Condición académica</dt><dd>Egresado/a</dd></div>
                <div><dt>Especialidad</dt><dd>{{ $especialidad?->nombre ?? '—' }}</dd></div>
            </dl>
        </x-tarjeta>

        <x-tarjeta titulo="Archivos adjuntos">
            <x-slot:acciones><a class="btn btn-fantasma btn-sm" href="{{ route('postulacion.paso', 3) }}">Editar<span class="sr-only"> archivos adjuntos</span></a></x-slot:acciones>
            <ul class="archivos">
                @foreach ($etiquetasArchivo as $tipo => $etq)
                    @if ($a = $archivos[$tipo] ?? null)
                        <li><span>{{ $etq }}</span><strong>{{ $a->nombre_original }}</strong></li>
                    @endif
                @endforeach
            </ul>
        </x-tarjeta>

        <x-tarjeta titulo="Consultorios y fecha de inicio">
            <x-slot:acciones><a class="btn btn-fantasma btn-sm" href="{{ route('postulacion.paso', 4) }}">Editar<span class="sr-only"> consultorios y fecha</span></a></x-slot:acciones>
            <ol class="resumen-cons">
                @foreach ($consultoriosElegidos as $c)
                    <li><span class="resumen-num" aria-hidden="true">{{ $loop->iteration }}</span><span><strong>{{ $c->nombre }}</strong><span class="texto-suave"> {{ $c->comuna?->nombre }}</span></span></li>
                @endforeach
            </ol>
            <p class="resumen-fecha"><span class="texto-suave">Fecha de inicio</span> <strong>{{ $fmtFecha($fechaInicio) }}</strong></p>
        </x-tarjeta>

        <form method="POST" action="{{ route('postulacion.enviar') }}" class="envio">
            @csrf
            <p class="texto-suave">El resultado llegará a <strong>{{ $datos['email'] ?? 'su correo' }}</strong> en un plazo máximo de 15 días hábiles.</p>
            <div class="acciones-paso">
                <x-boton tipo="secundario" :href="route('postulacion.paso', 4)">Volver</x-boton>
                <x-boton type="submit">Confirmar y enviar</x-boton>
            </div>
        </form>
    </div>
</x-postulacion.marco>
@endsection
