@php
    $nac = $postulante->fecha_nacimiento?->format('d-m-Y');
    $anio = ['quinto' => 'Quinto año', 'egresado' => 'Egresado/a'][$postulante->anio_carrera] ?? ($postulante->anio_carrera ?: 'Egresado/a');
@endphp
<div class="sec-columna" style="gap:1.25rem">
    <dl class="datos">
        <div><dt>RUT</dt><dd>{{ $postulante->rut }}</dd></div>
        <div><dt>Fecha de nacimiento</dt><dd>{{ $nac ?? 'No informada' }}</dd></div>
        <div><dt>Correo</dt><dd><a href="mailto:{{ $postulante->email }}">{{ $postulante->email }}</a></dd></div>
        <div><dt>Teléfono</dt><dd>{{ $postulante->telefono }}</dd></div>
        <div class="ancho-completo"><dt>Domicilio</dt><dd>{{ $postulante->direccion }}, {{ $postulante->comuna?->nombre }}@if ($postulante->region), {{ $postulante->region->nombre }}@endif</dd></div>
        <div><dt>Universidad</dt><dd>{{ $postulante->universidad }}</dd></div>
        <div><dt>Condición académica</dt><dd>{{ $anio }}</dd></div>
        @if ($postulante->especialidad)<div><dt>Especialidad</dt><dd>{{ $postulante->especialidad->nombre }}</dd></div>@endif
        <div><dt>Discapacidad</dt><dd>{{ $postulante->discapacidad ? 'Sí' : 'No' }}@if ($postulante->discapacidad && $postulante->discapacidad_descripcion) — {{ $postulante->discapacidad_descripcion }}@endif</dd></div>
        <div class="ancho-completo"><dt>Antecedentes de salud</dt><dd>{{ $postulante->antecedentes_salud ?: 'Sin antecedentes informados' }}</dd></div>
    </dl>
    <dl class="datos" style="padding-top:1rem;border-top:1px solid var(--linea)">
        <div class="ancho-completo"><dt>Contacto de emergencia</dt><dd>{{ $postulante->emergencia_nombre }} ({{ $postulante->emergencia_vinculo }})</dd></div>
        <div><dt>Teléfono</dt><dd>{{ $postulante->emergencia_telefono }}</dd></div>
        <div><dt>Correo</dt><dd>{{ $postulante->emergencia_email ?: 'No informado' }}</dd></div>
    </dl>
</div>
