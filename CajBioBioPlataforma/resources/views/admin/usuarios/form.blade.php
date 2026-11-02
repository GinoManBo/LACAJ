@php
    $editando = $usuario->exists;
    $etRol = [
        'administrador' => 'Administrador',
        'secretaria' => 'Secretaría regional',
        'juridica_central' => 'Jurídica Central Biobío',
        'director_regional' => 'Director/a Regional',
        'jefe_ucoe' => 'Jefe/a de UCOE Biobío',
        'director_general' => 'Director/a General',
        'abogado' => 'Abogado tutor',
        'practicante' => 'Postulante',
        'postulante' => 'Postulante',
        'gobernador' => 'Gobernador regional',
    ];
    $etArea = ['civil' => 'Civil', 'penal' => 'Penal', 'laboral' => 'Laboral', 'familia' => 'Familia'];
    $rolActual = old('rol', $usuario->rol ?: 'secretaria');
    $propio = $editando && $usuario->id === Auth::id();
    $seleccion = collect(old('consultorios', $editando ? ($usuario->abogado?->consultorios->pluck('id')->all() ?? []) : []))->map(fn ($i) => (int) $i)->all();
    $titulo = $editando ? 'Editar usuario' : 'Nuevo usuario';
@endphp
@extends('layouts.portal', ['migas' => [['Administración', route('admin.inicio')], ['Usuarios', route('admin.usuarios.index')], [$titulo]]])
@section('titulo', $titulo)
@section('contenido')
@include('admin._nav')
<div>
    <h1 class="titulo-pagina">{{ $titulo }}</h1>
    <p class="texto-suave" style="margin:.25rem 0 0">
        @if ($editando) {{ $usuario->email }}. La contraseña no se cambia aquí; usa «Restablecer contraseña» en el listado.
        @else Se generará una contraseña temporal que verás una sola vez al guardar. @endif
    </p>
</div>

<form method="POST" action="{{ $editando ? route('admin.usuarios.actualizar', $usuario) : route('admin.usuarios.guardar') }}" class="formulario formulario-ancho" novalidate>
    @csrf
    @if ($editando) @method('PUT') @endif

    <x-tarjeta titulo="Datos de la persona">
        <div class="rejilla-campos">
            <x-campo name="nombre" label="Nombre" :value="$usuario->nombre" obligatorio autocomplete="off" />
            <x-campo name="apellido" label="Apellido" :value="$usuario->apellido" obligatorio autocomplete="off" />
            <x-campo name="email" type="email" label="Correo" :value="$usuario->email" obligatorio autocomplete="off" />
            <x-campo name="rut" label="RUT" :value="$usuario->rut" placeholder="12.345.678-5" ayuda="Es el usuario para ingresar al sistema y con ClaveÚnica." data-rut obligatorio autocomplete="off" />
        </div>
    </x-tarjeta>

    <x-tarjeta titulo="Rol y asignación">
        <div class="rejilla-campos">
            <div>
                <x-campo name="rol" label="Rol" :opciones="collect($roles)->mapWithKeys(fn ($r) => [$r => $etRol[$r] ?? $r])->all()" :value="$rolActual" obligatorio placeholder="Selecciona un rol" ayuda="{{ $propio ? 'No puedes cambiar tu propio rol.' : '' }}" />
            </div>
            <div data-solo-rol="secretaria" @if ($rolActual !== 'secretaria') hidden @endif>
                <x-campo name="region_id" label="Región" :opciones="$regiones->pluck('nombre', 'id')->all()" :value="$usuario->region_id" placeholder="Selecciona una región" ayuda="Obligatoria para secretaría: solo verá esta región." />
            </div>
            <div data-solo-rol="abogado" @if ($rolActual !== 'abogado') hidden @endif>
                <x-campo name="area" label="Área" :opciones="$etArea" :value="$usuario->abogado?->area" placeholder="Selecciona un área" ayuda="Obligatoria para abogado tutor." />
            </div>
        </div>

        <fieldset class="consultorios" data-solo-rol="abogado" @if ($rolActual !== 'abogado') hidden @endif>
            <legend class="campo-etiqueta">Consultorios donde atiende</legend>
            <p class="campo-ayuda" style="margin:0 0 .5rem">Opcional. Puedes asignarlo después.</p>
            <div class="checks">
                @foreach ($consultorios as $c)
                    <label class="check"><input type="checkbox" name="consultorios[]" value="{{ $c->id }}" @checked(in_array($c->id, $seleccion, true))><span>{{ $c->nombre }}</span></label>
                @endforeach
            </div>
            @error('consultorios')<p class="campo-error" role="alert">{{ $message }}</p>@enderror
        </fieldset>
    </x-tarjeta>

    <div class="acciones-form">
        <x-boton tipo="fantasma" href="{{ route('admin.usuarios.index') }}">Cancelar</x-boton>
        <x-boton type="submit">{{ $editando ? 'Guardar cambios' : 'Crear usuario' }}</x-boton>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const rol = document.getElementById('campo-rol');
    const aplicar = () => {
        document.querySelectorAll('[data-solo-rol]').forEach((b) => {
            const visible = b.dataset.soloRol === rol.value;
            b.hidden = !visible;
            b.querySelectorAll('select, input').forEach((s) => { s.disabled = !visible; if (s.tagName === 'SELECT') s.required = visible; });
        });
    };
    rol.addEventListener('change', aplicar); aplicar();
});
</script>
@endsection
