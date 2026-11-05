@extends('layouts.publico')
@section('titulo', 'Datos personales')

@php
    $d = $borrador['datos'] ?? [];
    $v = fn ($k) => $d[$k] ?? null;
    $regionSel = old('region_id', $v('region_id'));
    $discSel = old('discapacidad', $v('discapacidad') === null ? null : (int) $v('discapacidad'));
    $discSel = $discSel === null ? null : (string) (int) $discSel;
    $comunasRegion = $comunas->where('region_id', (int) $regionSel)->pluck('nombre', 'id');
    $errDisc = $errors->first('discapacidad');
@endphp

@section('contenido')
<x-postulacion.marco :n="$n" :pasos="$pasos" :permitido="$permitido" titulo="Datos personales" bajada="Los campos con asterisco rojo son obligatorios.">
    <form method="POST" action="{{ route('postulacion.guardar', 2) }}" data-paso-datos novalidate class="post-form">
        @csrf

        <fieldset class="bloque">
            <legend>Identificación</legend>
            <div class="rejilla-campos">
                <x-campo name="nombre" label="Nombres" :value="$v('nombre')" :obligatorio="true" autocomplete="given-name" />
                <x-campo name="apellido" label="Apellidos" :value="$v('apellido')" :obligatorio="true" autocomplete="family-name" />
                <x-campo name="rut" label="RUT" :value="$v('rut')" :obligatorio="true" placeholder="12.345.678-5" :ayuda="auth()->user()?->rol === 'postulante' ? 'Es el RUT de su ClaveÚnica y no se puede cambiar.' : 'Se da formato mientras escribe.'" inputmode="text" autocomplete="off" data-rut maxlength="12" :readonly="auth()->user()?->rol === 'postulante'" />
                <x-campo name="fecha_nacimiento" label="Fecha de nacimiento" type="date" :value="$v('fecha_nacimiento')" :obligatorio="true" :max="now()->subDay()->toDateString()" min="1900-01-02" autocomplete="bday" />
                <x-campo name="genero" label="Género" :value="$v('genero')" :opciones="$generos" :obligatorio="true" />
                <x-campo name="nacionalidad" label="Nacionalidad" :value="$v('nacionalidad')" :obligatorio="true" autocomplete="country-name" />
            </div>
        </fieldset>

        <fieldset class="bloque">
            <legend>Contacto</legend>
            <div class="rejilla-campos">
                <x-campo name="email" label="Correo electrónico" type="email" :value="$v('email')" :obligatorio="true" ayuda="Aquí le informaremos el resultado de su postulación." autocomplete="email" inputmode="email" />
                <x-campo name="telefono" label="Teléfono" type="tel" :value="$v('telefono')" :obligatorio="true" placeholder="+56 9 1234 5678" autocomplete="tel" inputmode="tel" />
            </div>
        </fieldset>

        @if (! auth()->check())
            <fieldset class="bloque">
                <legend>Cuenta de acceso a la plataforma</legend>
                <p class="texto-suave bloque-nota">Crea tu contraseña para ingresar a tu cuenta personal, dar seguimiento en vivo a tu postulación y subsanar antecedentes.</p>
                <div class="rejilla-campos">
                    <x-campo name="password" label="Contraseña" type="password" :obligatorio="empty($v('password_hash'))" :ayuda="! empty($v('password_hash')) ? 'Contraseña ya configurada. Déjela en blanco si no desea modificarla.' : 'Mínimo 8 caracteres.'" autocomplete="new-password" />
                    <x-campo name="password_confirmation" label="Confirmar contraseña" type="password" :obligatorio="empty($v('password_hash'))" :ayuda="! empty($v('password_hash')) ? 'Repita la contraseña solo si la está cambiando.' : 'Repita la misma contraseña.'" autocomplete="new-password" />
                </div>
            </fieldset>
        @endif

        <fieldset class="bloque">
            <legend>Domicilio</legend>
            <div class="rejilla-campos">
                <x-campo name="region_id" label="Región" as="select" :value="$regionSel" :obligatorio="true" data-region>
                    @foreach ($regiones as $r)<option value="{{ $r->id }}" @selected((string) $regionSel === (string) $r->id)>{{ $r->nombre }}</option>@endforeach
                </x-campo>
                <x-campo name="comuna_id" label="Comuna" :value="old('comuna_id', $v('comuna_id'))" :opciones="$comunasRegion" :obligatorio="true" data-comuna :placeholder="$regionSel ? 'Selecciona una comuna' : 'Primero elige una región'" :disabled="$regionSel ? false : true" />
                <div class="ancho-completo"><x-campo name="direccion" label="Dirección" :value="$v('direccion')" :obligatorio="true" placeholder="Calle, número, depto." autocomplete="street-address" /></div>
            </div>
            <script type="application/json" id="datos-comunas">@json($comunas->map(fn ($c) => ['id' => $c->id, 'r' => $c->region_id, 'n' => $c->nombre])->values())</script>
        </fieldset>

        <fieldset class="bloque">
            <legend>Salud y accesibilidad</legend>
            <div class="rejilla-campos">
                <fieldset class="campo ancho-completo {{ $errDisc ? 'tiene-error' : '' }}" data-grupo-obligatorio="Indique si tiene alguna discapacidad.">
                    <legend class="campo-etiqueta">¿Tiene alguna discapacidad?<span class="campo-obligatorio" aria-hidden="true">*</span><span class="sr-only"> (obligatorio)</span></legend>
                    <div class="opciones-grupo">
                        <label class="opcion"><input type="radio" name="discapacidad" value="1" @checked($discSel === '1') data-disc><span>Sí</span></label>
                        <label class="opcion"><input type="radio" name="discapacidad" value="0" @checked($discSel === '0') data-disc><span>No</span></label>
                    </div>
                    <p class="campo-error" role="alert">{{ $errDisc }}</p>
                </fieldset>
                <div class="ancho-completo" data-disc-detalle @if ($discSel !== '1') hidden @endif>
                    <x-campo name="discapacidad_descripcion" label="Describa su discapacidad" type="textarea" :value="$v('discapacidad_descripcion')" :obligatorio="true" ayuda="Indique el tipo, el grado y los apoyos que necesita." :disabled="$discSel === '1' ? false : true" />
                </div>
                <div class="ancho-completo">
                    <x-campo name="antecedentes_salud" label="Antecedentes de salud relevantes" type="textarea" :value="$v('antecedentes_salud')" :obligatorio="true" ayuda='Puede escribir "Ninguno".' />
                </div>
            </div>
        </fieldset>

        <fieldset class="bloque">
            <legend>Contacto de emergencia</legend>
            <div class="rejilla-campos">
                <x-campo name="emergencia_nombre" label="Nombre completo" :value="$v('emergencia_nombre')" :obligatorio="true" />
                <x-campo name="emergencia_vinculo" label="Vínculo" :value="$v('emergencia_vinculo')" :obligatorio="true" placeholder="Madre, pareja, hermano…" />
                <x-campo name="emergencia_telefono" label="Teléfono" type="tel" :value="$v('emergencia_telefono')" :obligatorio="true" inputmode="tel" />
                <x-campo name="emergencia_email" label="Correo electrónico (opcional)" type="email" :value="$v('emergencia_email')" inputmode="email" />
            </div>
        </fieldset>

        <div class="acciones-paso">
            <x-boton tipo="secundario" :href="route('postulacion.paso', 1)">Volver</x-boton>
            <x-boton type="submit">Continuar</x-boton>
        </div>
    </form>
</x-postulacion.marco>
@endsection
