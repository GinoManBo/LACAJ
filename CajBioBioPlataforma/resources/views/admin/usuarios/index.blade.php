@extends('layouts.portal', ['migas' => [['Administración', route('admin.inicio')], ['Usuarios']]])
@section('titulo', 'Usuarios')
@php
    $etRol = [
        'administrador' => 'Administrador',
        'secretaria' => 'Secretaría regional',
        'juridica_central' => 'Jurídica Central',
        'director_regional' => 'Director/a Regional',
        'jefe_ucoe' => 'Jefe/a UCOE',
        'director_general' => 'Director/a General',
        'abogado' => 'Abogado tutor',
        'gobernador' => 'Gobernador',
        'practicante' => 'Postulante',
        'postulante' => 'Postulante',
    ];
    $f = $filtros ?? [];
    $url = fn (array $extra) => route('admin.usuarios.index', array_filter(array_merge($f, $extra), fn ($v) => $v !== null && $v !== ''));
    $temporal = session('password_temporal');
@endphp
@section('contenido')
@include('admin._nav')
<div class="cabecera-ficha">
    <div>
        <h1 class="titulo-pagina">Usuarios</h1>
        <p class="texto-suave" style="margin:.25rem 0 0">{{ $usuarios->total() }} {{ $usuarios->total() === 1 ? 'usuario' : 'usuarios' }}. Las cuentas no se borran: se desactivan y conservan su historial.</p>
    </div>
    <x-boton href="{{ route('admin.usuarios.crear') }}">Nuevo usuario</x-boton>
</div>

@if ($temporal)
    <section class="clave-temporal" role="alert" aria-labelledby="ct-titulo">
        <div>
            <h2 id="ct-titulo">Contraseña temporal de {{ $temporal['email'] }}</h2>
            <p class="clave-aviso"><strong>No se volverá a mostrar.</strong> Cópiala ahora y entrégala por un canal seguro; se pedirá cambiarla al primer ingreso.</p>
        </div>
        <div class="clave-fila">
            <code id="clave-valor">{{ $temporal['password'] }}</code>
            <x-boton type="button" id="clave-copiar" tipo="secundario">Copiar</x-boton>
        </div>
        <p id="clave-estado" class="sr-only" aria-live="polite"></p>
    </section>
@endif

<form method="GET" action="{{ route('admin.usuarios.index') }}" class="filtros" role="search">
    <div class="chips" role="group" aria-label="Filtrar por rol">
        <a href="{{ $url(['rol' => null]) }}" class="chip" @if (empty($f['rol'])) aria-current="true" @endif>Todos</a>
        @foreach ($roles as $r)
            <a href="{{ $url(['rol' => $r]) }}" class="chip" @if (($f['rol'] ?? null) === $r) aria-current="true" @endif>{{ $etRol[$r] ?? $r }}</a>
        @endforeach
    </div>
    <div class="filtros-fila">
        @if (! empty($f['rol']))<input type="hidden" name="rol" value="{{ $f['rol'] }}">@endif
        <div class="campo"><label for="estado" class="sr-only">Estado</label>
            <select id="estado" name="estado" class="campo-control" onchange="this.form.submit()">
                <option value="">Cualquier estado</option>
                <option value="activo" @selected(($f['estado'] ?? '') === 'activo')>Activos</option>
                <option value="inactivo" @selected(($f['estado'] ?? '') === 'inactivo')>Inactivos</option>
            </select></div>
        <div class="campo" style="flex:1"><label for="q" class="sr-only">Buscar</label>
            <input id="q" name="q" type="search" value="{{ $f['q'] ?? '' }}" class="campo-control" placeholder="Nombre, apellido o correo"></div>
        <x-boton type="submit">Buscar</x-boton>
        @if ($f)<x-boton tipo="fantasma" href="{{ route('admin.usuarios.index') }}">Limpiar</x-boton>@endif
    </div>
</form>

@if ($usuarios->isEmpty())
    <x-estado-vacio titulo="No hay usuarios con esos filtros" texto="Cambia el rol, el estado o la búsqueda.">
        <x-boton tipo="secundario" href="{{ route('admin.usuarios.index') }}">Quitar filtros</x-boton>
    </x-estado-vacio>
@else
    <x-tabla :columnas="['Usuario', 'Rol', 'Región / área', 'Último acceso', 'Estado', 'Acciones']" descripcion="Usuarios del sistema">
        @foreach ($usuarios as $u)
            <tr @class(['fila-inactiva' => ! $u->activo])>
                <td>
                    <div class="persona">
                        <span class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($u->nombre, 0, 1).mb_substr($u->apellido, 0, 1)) }}</span>
                        <span><strong>{{ $u->nombre }} {{ $u->apellido }}</strong><span class="dato-menor texto-suave" style="display:block">{{ $u->email }}@if ($u->rut) · {{ $u->rut }}@endif</span></span>
                    </div>
                </td>
                <td>{{ $etRol[$u->rol] ?? $u->rol }}</td>
                <td>{{ $u->region?->nombre ?? ($u->abogado?->area ? ucfirst($u->abogado->area) : '—') }}</td>
                <td class="nowrap">{{ $u->ultimo_acceso ? \Illuminate\Support\Carbon::parse($u->ultimo_acceso)->format('d/m/Y H:i') : 'Sin ingresos' }}</td>
                <td>@if ($u->activo)<x-badge estado="activa" texto="Activo" />@else<x-badge estado="finalizada" texto="Inactivo" />@endif</td>
                <td>
                    <div class="acciones-fila">
                        <x-boton tipo="secundario" tam="sm" href="{{ route('admin.usuarios.editar', $u) }}" aria-label="Editar a {{ $u->nombre }} {{ $u->apellido }}">Editar</x-boton>
                        <x-boton type="button" tipo="fantasma" tam="sm" data-modal-abrir="modal-clave" data-accion="{{ route('admin.usuarios.resetear', $u) }}" data-nombre="{{ $u->nombre }} {{ $u->apellido }}">Restablecer contraseña</x-boton>
                        @if ($u->activo)
                            <x-boton type="button" tipo="fantasma" tam="sm" class="texto-rojo" data-modal-abrir="modal-estado" data-accion="{{ route('admin.usuarios.desactivar', $u) }}" data-nombre="{{ $u->nombre }} {{ $u->apellido }}" data-activar="0">Desactivar</x-boton>
                        @else
                            <x-boton type="button" tipo="fantasma" tam="sm" data-modal-abrir="modal-estado" data-accion="{{ route('admin.usuarios.activar', $u) }}" data-nombre="{{ $u->nombre }} {{ $u->apellido }}" data-activar="1">Activar</x-boton>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </x-tabla>
    <div class="paginacion">{{ $usuarios->links() }}</div>
@endif

<x-modal id="modal-estado" titulo="Cambiar acceso">
    <form method="POST" id="form-estado" action="#">
        @csrf
        <p id="estado-texto" style="margin-top:0"></p>
        <p class="texto-suave" id="estado-nota" style="margin-bottom:0"></p>
    </form>
    <x-slot:pie><x-boton type="submit" id="estado-confirmar" tipo="peligro" form="form-estado" data-permitir-repetir>Confirmar</x-boton></x-slot:pie>
</x-modal>
<x-modal id="modal-clave" titulo="Restablecer contraseña">
    <form method="POST" id="form-clave" action="#">
        @csrf
        <p style="margin-top:0">Se generará una contraseña temporal para <strong id="clave-nombre"></strong> y se cerrarán sus sesiones activas. La verás una sola vez.</p>
    </form>
    <x-slot:pie><x-boton type="submit" form="form-clave">Generar contraseña</x-boton></x-slot:pie>
</x-modal>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-modal-abrir="modal-estado"]').forEach((b) => b.addEventListener('click', () => {
        const act = b.dataset.activar === '1';
        document.getElementById('form-estado').action = b.dataset.accion;
        const t = document.getElementById('estado-texto'); t.textContent = '';
        t.append(act ? 'Vas a activar a ' : 'Vas a desactivar a ', Object.assign(document.createElement('strong'), { textContent: b.dataset.nombre }), '.');
        document.getElementById('estado-nota').textContent = act ? 'Recupera el acceso al sistema con su misma contraseña.' : 'Pierde el acceso de inmediato. Su historial se conserva y puedes reactivarlo cuando quieras.';
        const c = document.getElementById('estado-confirmar');
        c.textContent = act ? 'Activar usuario' : 'Desactivar usuario';
        c.classList.toggle('btn-peligro', !act); c.classList.toggle('btn-primario', act);
    }));
    document.querySelectorAll('[data-modal-abrir="modal-clave"]').forEach((b) => b.addEventListener('click', () => {
        document.getElementById('form-clave').action = b.dataset.accion;
        document.getElementById('clave-nombre').textContent = b.dataset.nombre;
    }));
    const copiar = document.getElementById('clave-copiar');
    copiar?.addEventListener('click', async () => {
        const texto = document.getElementById('clave-valor').textContent;
        try { await navigator.clipboard.writeText(texto); }
        catch { const r = document.createRange(); r.selectNodeContents(document.getElementById('clave-valor')); const s = getSelection(); s.removeAllRanges(); s.addRange(r); document.execCommand('copy'); }
        copiar.textContent = 'Copiada';
        document.getElementById('clave-estado').textContent = 'Contraseña copiada al portapapeles';
        setTimeout(() => (copiar.textContent = 'Copiar'), 2200);
    });
});
</script>
@endsection
