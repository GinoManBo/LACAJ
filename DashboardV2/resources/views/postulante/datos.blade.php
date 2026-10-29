@extends('layouts.publico')
@section('titulo', 'Mis datos')
@push('head')@vite('resources/js/postulacion.js')@endpush

@php
    $p = $postulante;
    $regionSel = old('region_id', $p->region_id);
    $discSel = old('discapacidad', $p->discapacidad === null ? null : (int) $p->discapacidad);
    $discSel = $discSel === null ? null : (string) (int) $discSel;
    $comunasRegion = $comunas->where('region_id', (int) $regionSel)->pluck('nombre', 'id');
    $errDisc = $errors->first('discapacidad');
@endphp

@section('contenido')
<div class="post-marco">
    <div class="contenedor post-contenedor post-cuerpo">
        <header class="post-titulo">
            <p class="post-contador">Mi postulación</p>
            <h1>Mis datos</h1>
            <p class="texto-suave">Corrige tus datos mientras tu postulación esté en revisión.</p>
        </header>

        <section class="dt-lectura" aria-labelledby="dt-lectura-t">
            <h2 id="dt-lectura-t" class="dt-lectura-titulo">Datos de tu ClaveÚnica</h2>
            <dl class="dt-lectura-lista">
                <div><dt>Nombre completo</dt><dd>{{ trim($p->nombre.' '.$p->apellido) }}</dd></div>
                <div><dt>RUT</dt><dd class="sec-num">{{ \App\Support\Rut::formatear($usuario->rut) }}</dd></div>
                <div><dt>Fecha de nacimiento</dt><dd class="sec-num">{{ $p->fecha_nacimiento?->format('d-m-Y') ?? 'No registrada' }}</dd></div>
            </dl>
            <p class="texto-suave dt-lectura-nota">Estos datos provienen de tu ClaveÚnica y no se pueden cambiar aquí.</p>
        </section>

        @if ($errors->any())
            <div class="alerta alerta-error" role="alert"><div><strong>Revisa los campos marcados.</strong> No se guardó ningún cambio.</div></div>
        @endif

        <form method="POST" action="{{ route('postulante.datos.guardar') }}" data-paso-datos novalidate class="post-form">
            @csrf

            <fieldset class="bloque">
                <legend>Contacto</legend>
                <div class="rejilla-campos">
                    <x-campo name="email" label="Correo electrónico" type="email" :value="$p->email" :obligatorio="true" ayuda="Aquí te informaremos el resultado de tu postulación." autocomplete="email" inputmode="email" />
                    <x-campo name="telefono" label="Teléfono" type="tel" :value="$p->telefono" :obligatorio="true" placeholder="+56 9 1234 5678" autocomplete="tel" inputmode="tel" />
                </div>
            </fieldset>

            <fieldset class="bloque">
                <legend>Datos personales</legend>
                <div class="rejilla-campos">
                    <x-campo name="genero" label="Género" :value="$p->genero" :opciones="$generos" :obligatorio="true" />
                    <x-campo name="nacionalidad" label="Nacionalidad" :value="$p->nacionalidad" :obligatorio="true" autocomplete="country-name" />
                    <fieldset class="campo ancho-completo {{ $errDisc ? 'tiene-error' : '' }}" data-grupo-obligatorio="Indica si tienes alguna discapacidad.">
                        <legend class="campo-etiqueta">¿Tienes alguna discapacidad?<span class="campo-obligatorio" aria-hidden="true">*</span><span class="sr-only"> (obligatorio)</span></legend>
                        <div class="opciones-grupo">
                            <label class="opcion"><input type="radio" name="discapacidad" value="1" @checked($discSel === '1') data-disc><span>Sí</span></label>
                            <label class="opcion"><input type="radio" name="discapacidad" value="0" @checked($discSel === '0') data-disc><span>No</span></label>
                        </div>
                        <p class="campo-error" role="alert">{{ $errDisc }}</p>
                    </fieldset>
                    <div class="ancho-completo" data-disc-detalle @if ($discSel !== '1') hidden @endif>
                        <x-campo name="discapacidad_descripcion" label="Describe tu discapacidad" type="textarea" :value="$p->discapacidad_descripcion" :obligatorio="true" ayuda="Indica el tipo, el grado y los apoyos que necesitas." :disabled="$discSel === '1' ? false : true" />
                    </div>
                </div>
            </fieldset>

            <fieldset class="bloque">
                <legend>Domicilio</legend>
                <div class="rejilla-campos">
                    <x-campo name="region_id" label="Región" as="select" :value="$regionSel" :obligatorio="true" data-region>
                        @foreach ($regiones as $r)<option value="{{ $r->id }}" @selected((string) $regionSel === (string) $r->id)>{{ $r->nombre }}</option>@endforeach
                    </x-campo>
                    <x-campo name="comuna_id" label="Comuna" :value="$p->comuna_id" :opciones="$comunasRegion" :obligatorio="true" data-comuna :placeholder="$regionSel ? 'Selecciona una comuna' : 'Primero elige una región'" :disabled="$regionSel ? false : true" />
                    <div class="ancho-completo"><x-campo name="direccion" label="Dirección" :value="$p->direccion" :obligatorio="true" placeholder="Calle, número, depto." autocomplete="street-address" /></div>
                </div>
                <script type="application/json" id="datos-comunas">@json($comunas->map(fn ($c) => ['id' => $c->id, 'r' => $c->region_id, 'n' => $c->nombre])->values())</script>
            </fieldset>

            <fieldset class="bloque">
                <legend>Contacto de emergencia</legend>
                <div class="rejilla-campos">
                    <x-campo name="emergencia_nombre" label="Nombre completo" :value="$p->emergencia_nombre" :obligatorio="true" />
                    <x-campo name="emergencia_vinculo" label="Vínculo" :value="$p->emergencia_vinculo" :obligatorio="true" placeholder="Madre, pareja, hermano…" />
                    <x-campo name="emergencia_telefono" label="Teléfono" type="tel" :value="$p->emergencia_telefono" :obligatorio="true" inputmode="tel" />
                    <x-campo name="emergencia_email" label="Correo electrónico (opcional)" type="email" :value="$p->emergencia_email" inputmode="email" />
                </div>
            </fieldset>

            <fieldset class="bloque">
                <legend>Salud</legend>
                <x-campo name="antecedentes_salud" label="Antecedentes de salud relevantes" type="textarea" :value="$p->antecedentes_salud" :obligatorio="true" ayuda='Puedes escribir "Ninguno".' />
            </fieldset>

            <fieldset class="bloque">
                <legend>Estudios</legend>
                <div class="rejilla-campos">
                    <div class="ancho-completo"><x-campo name="universidad" label="Universidad" :value="$p->universidad" :obligatorio="true" /></div>
                    <x-campo name="anio_carrera" label="Año de carrera" :value="$p->anio_carrera" :opciones="['quinto' => '5° año', 'egresado' => 'Egresado']" :obligatorio="true" />
                    <x-campo name="especialidad_id" label="Especialidad de interés" :value="$p->especialidad_id" :opciones="$especialidades->pluck('nombre', 'id')" :obligatorio="true" />
                </div>
            </fieldset>

            <p class="texto-suave dt-nota">La secretaría verá los cambios de inmediato. Para cambiar los consultorios o la fecha de inicio, comunícate con la secretaría.</p>

            <div class="acciones-paso">
                <x-boton tipo="secundario" :href="route('postulante.inicio')">Cancelar</x-boton>
                <x-boton type="submit">Guardar cambios</x-boton>
            </div>
        </form>
    </div>
</div>
@endsection
