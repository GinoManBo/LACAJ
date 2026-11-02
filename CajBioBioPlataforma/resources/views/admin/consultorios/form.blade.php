@extends('layouts.portal', ['migas' => [
    ['Administración', route('admin.inicio')],
    ['Consultorios y Sedes', route('admin.consultorios.index')],
    [$esEdicion ? 'Editar '.$consultorio->nombre : 'Nueva Sede']
]])
@section('titulo', ($esEdicion ? 'Editar Sede · '.$consultorio->nombre : 'Registrar Nueva Sede'))

@section('contenido')
@include('admin._nav')

<div class="cabecera-ficha" style="margin-bottom:1.5rem">
    <div>
        <h1 class="titulo-pagina">{{ $esEdicion ? 'Editar Información de Sede' : 'Registrar Nueva Sede / Consultorio' }}</h1>
        <p class="texto-suave" style="margin:.25rem 0 0">
            {{ $esEdicion ? 'Modifique los datos institucionales, de contacto o capacidad de la sede.' : 'Ingrese los datos requeridos para dar de alta una nueva unidad operativa.' }}
        </p>
    </div>
    <x-boton tipo="secundario" :href="route('admin.consultorios.index')">← Volver al listado</x-boton>
</div>

<x-tarjeta :titulo="$esEdicion ? 'Datos de '.$consultorio->nombre : 'Formulario de registro'">
    <form method="POST" action="{{ $esEdicion ? route('admin.consultorios.actualizar', $consultorio) : route('admin.consultorios.guardar') }}" novalidate>
        @csrf
        @if ($esEdicion)
            @method('PUT')
        @endif

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:1.25rem;margin-bottom:1.25rem">
            <div>
                <label for="nombre" class="campo-etiqueta">Nombre oficial de la Unidad *</label>
                <input id="nombre" name="nombre" type="text" required maxlength="140" value="{{ old('nombre', $consultorio->nombre) }}" class="campo-control" style="width:100%" placeholder="Ej: CAJ CHILLAN CIVIL">
                @error('nombre')<p class="campo-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="tipo_unidad" class="campo-etiqueta">Tipo de Unidad Operativa</label>
                <select id="tipo_unidad" name="tipo_unidad" class="campo-control" style="width:100%">
                    <option value="">-- Seleccione tipo institucional --</option>
                    @foreach ($tipos as $clave => $etiqueta)
                        <option value="{{ $clave }}" @selected(old('tipo_unidad', $consultorio->tipo_unidad) === $clave)>
                            {{ $etiqueta }}
                        </option>
                    @endforeach
                </select>
                @error('tipo_unidad')<p class="campo-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="region_id" class="campo-etiqueta">Región *</label>
                <select id="region_id" name="region_id" required class="campo-control" style="width:100%">
                    <option value="">-- Seleccione región --</option>
                    @foreach ($regiones as $reg)
                        <option value="{{ $reg->id }}" @selected(old('region_id', $consultorio->region_id) == $reg->id)>
                            {{ $reg->nombre }} ({{ $reg->codigo }})
                        </option>
                    @endforeach
                </select>
                @error('region_id')<p class="campo-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="comuna_id" class="campo-etiqueta">Comuna *</label>
                <select id="comuna_id" name="comuna_id" required class="campo-control" style="width:100%">
                    <option value="">-- Seleccione comuna --</option>
                    @foreach ($comunas as $com)
                        <option value="{{ $com->id }}" data-region="{{ $com->region_id }}" @selected(old('comuna_id', $consultorio->comuna_id) == $com->id)>
                            {{ $com->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('comuna_id')<p class="campo-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:1.25rem;margin-bottom:1.25rem">
            <div style="grid-column:1/-1">
                <label for="direccion" class="campo-etiqueta">Dirección física *</label>
                <input id="direccion" name="direccion" type="text" required maxlength="200" value="{{ old('direccion', $consultorio->direccion) }}" class="campo-control" style="width:100%" placeholder="Calle, número, piso u oficina...">
                @error('direccion')<p class="campo-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="telefono" class="campo-etiqueta">Teléfono(s) de contacto</label>
                <input id="telefono" name="telefono" type="text" maxlength="255" value="{{ old('telefono', $consultorio->telefono) }}" class="campo-control" style="width:100%" placeholder="Ej: (42) 2229417 / 981214631">
                @error('telefono')<p class="campo-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="campo-etiqueta">Correo electrónico institucional</label>
                <input id="email" name="email" type="email" maxlength="150" value="{{ old('email', $consultorio->email) }}" class="campo-control" style="width:100%" placeholder="Ej: unidad@cajbiobio.cl">
                @error('email')<p class="campo-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="capacidad" class="campo-etiqueta">Capacidad de cupos formativos *</label>
                <input id="capacidad" name="capacidad" type="number" min="0" max="500" required value="{{ old('capacidad', $consultorio->capacidad) }}" class="campo-control" style="width:100%">
                <small class="texto-suave">Cupos simultáneos máximos para postulantes en práctica.</small>
                @error('capacidad')<p class="campo-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div style="margin-bottom:1.25rem">
            <label for="horario_atencion" class="campo-etiqueta">Horario de atención al público</label>
            <textarea id="horario_atencion" name="horario_atencion" rows="4" class="campo-control" style="width:100%" placeholder="Ej: Lunes a Jueves de 09:00 a 16:30&#10;Viernes de 09:00 a 13:00">{{ old('horario_atencion', $consultorio->horario_atencion) }}</textarea>
            @error('horario_atencion')<p class="campo-error">{{ $message }}</p>@enderror
        </div>

        <div style="display:flex;gap:2rem;margin-bottom:1.5rem;flex-wrap:wrap">
            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                <input type="checkbox" name="activo" value="1" @checked(old('activo', $consultorio->activo))>
                <span style="font-weight:600;font-size:0.9rem">Sede activa en plataforma</span>
            </label>

            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                <input type="checkbox" name="acepta_postulaciones" value="1" @checked(old('acepta_postulaciones', $consultorio->acepta_postulaciones))>
                <span style="font-weight:600;font-size:0.9rem">Habilitada para asignar postulantes</span>
            </label>
        </div>

        <div style="display:flex;gap:0.75rem;justify-content:flex-end">
            <x-boton tipo="secundario" :href="route('admin.consultorios.index')">Cancelar</x-boton>
            <x-boton type="submit" tipo="primario">
                {{ $esEdicion ? 'Guardar Cambios' : 'Registrar Sede' }}
            </x-boton>
        </div>
    </form>
</x-tarjeta>

<script>
(function() {
    const regSel = document.getElementById('region_id');
    const comSel = document.getElementById('comuna_id');
    if (!regSel || !comSel) return;

    function filtrarComunas() {
        const regId = regSel.value;
        const opts = [...comSel.options];
        opts.forEach(opt => {
            if (!opt.value) return;
            const r = opt.dataset.region;
            if (!regId || r === regId) {
                opt.hidden = false;
            } else {
                opt.hidden = true;
            }
        });
    }

    regSel.addEventListener('change', filtrarComunas);
    filtrarComunas();
})();
</script>
@endsection
