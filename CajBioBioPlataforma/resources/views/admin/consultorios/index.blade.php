@extends('layouts.portal', ['migas' => [['Administración', route('admin.inicio')], ['Consultorios y Sedes']]])
@section('titulo', 'Consultorios y Sedes Operativas')

@section('contenido')
@include('admin._nav')

<div class="cabecera-ficha" style="margin-bottom:1.5rem">
    <div>
        <h1 class="titulo-pagina">Consultorios y Sedes Operativas</h1>
        <p class="texto-suave" style="margin:.25rem 0 0">
            Administración centralizada de las unidades y sedes operativas de la CAJ Biobío en las 6 regiones de la jurisdicción.
        </p>
    </div>
    <div>
        <x-boton tipo="primario" :href="route('admin.consultorios.crear')">+ Registrar Nueva Sede</x-boton>
    </div>
</div>

<div class="rejilla-indicadores" style="margin-bottom:1.5rem">
    <x-indicador :valor="$totales['sedes']" etiqueta="Sedes registradas" detalle="Total en catálogo institucional" />
    <x-indicador :valor="$totales['activas']" etiqueta="Sedes activas" detalle="Operativas en plataforma" />
    <x-indicador :valor="$totales['capacidad']" etiqueta="Cupos institucionales" detalle="Capacidad total de formación" />
</div>

<x-tarjeta titulo="Filtros de búsqueda" style="margin-bottom:1.5rem">
    <form method="GET" action="{{ route('admin.consultorios.index') }}" class="tp-filtros" role="search" data-sin-validar style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:1rem;align-items:end">
        <div>
            <label for="q" class="campo-etiqueta" style="font-size:0.85rem">Buscar</label>
            <input id="q" name="q" type="search" value="{{ $filtros['q'] }}" placeholder="Nombre, dirección, teléfono..." class="campo-control" style="width:100%">
        </div>
        <div>
            <label for="region_id" class="campo-etiqueta" style="font-size:0.85rem">Región</label>
            <select id="region_id" name="region_id" class="campo-control" style="width:100%">
                <option value="">Todas las regiones</option>
                @foreach ($regiones as $reg)
                    <option value="{{ $reg->id }}" @selected((string)$filtros['region_id'] === (string)$reg->id)>
                        {{ $reg->nombre }} ({{ $reg->codigo }})
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="tipo_unidad" class="campo-etiqueta" style="font-size:0.85rem">Tipo de Unidad</label>
            <select id="tipo_unidad" name="tipo_unidad" class="campo-control" style="width:100%">
                <option value="">Todos los tipos</option>
                @foreach ($tipos as $clave => $etiqueta)
                    <option value="{{ $clave }}" @selected($filtros['tipo_unidad'] === $clave)>
                        {{ $etiqueta }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="activo" class="campo-etiqueta" style="font-size:0.85rem">Estado</label>
            <select id="activo" name="activo" class="campo-control" style="width:100%">
                <option value="">Todos los estados</option>
                <option value="1" @selected($filtros['activo'] === '1')>Solo activos</option>
                <option value="0" @selected($filtros['activo'] === '0')>Inactivos</option>
            </select>
        </div>
        <div style="display:flex;gap:0.5rem">
            <x-boton type="submit" tipo="primario" style="flex:1">Filtrar</x-boton>
            @if ($filtros['q'] || $filtros['region_id'] || $filtros['tipo_unidad'] || $filtros['activo'] !== null)
                <x-boton tipo="secundario" :href="route('admin.consultorios.index')">Limpiar</x-boton>
            @endif
        </div>
    </form>
</x-tarjeta>

<x-tarjeta :titulo="'Listado de sedes ('.$consultorios->count().')'" sinPadding>
    @if ($consultorios->isEmpty())
        <div style="padding:2rem">
            <x-estado-vacio titulo="No se encontraron sedes" texto="Pruebe cambiando los criterios de búsqueda o registre una nueva sede." />
        </div>
    @else
        <div style="overflow-x:auto">
            <table class="tabla" style="width:100%">
                <thead>
                    <tr>
                        <th>Unidad / Tipo</th>
                        <th>Comuna y Región</th>
                        <th>Dirección</th>
                        <th>Contacto</th>
                        <th>Cupos</th>
                        <th>Estado</th>
                        <th style="text-align:right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($consultorios as $c)
                        <tr>
                            <td>
                                <a href="{{ route('admin.consultorios.ver', $c) }}" style="font-weight:600;color:#0b3d91">
                                    {{ $c->nombre }}
                                </a>
                                @if ($c->tipo_unidad)
                                    <span class="badge badge-neutro" style="font-size:0.75rem;margin-top:0.25rem;display:inline-block">
                                        {{ $c->tipo_unidad }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $c->comuna?->nombre ?? '—' }}</strong>
                                <span class="dato-menor texto-suave" style="display:block">{{ $c->region?->nombre ?? '—' }}</span>
                            </td>
                            <td style="max-width:200px">
                                <span style="font-size:0.85rem;color:#374151">{{ $c->direccion }}</span>
                            </td>
                            <td style="font-size:0.85rem">
                                @if ($c->telefono)
                                    <div>📞 <span style="color:#4b5563">{{ $c->telefono }}</span></div>
                                @endif
                                @if ($c->email)
                                    <div>✉️ <a href="mailto:{{ $c->email }}" style="color:#0284c7">{{ $c->email }}</a></div>
                                @endif
                                @if (! $c->telefono && ! $c->email)
                                    <span class="texto-suave">Sin contacto</span>
                                @endif
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $c->ocupados_n }}</strong> / {{ $c->capacidad }}
                                    <x-badge :estado="$c->disponibilidad" />
                                </div>
                                <span class="dato-menor texto-suave">{{ $c->libres_n }} libres</span>
                            </td>
                            <td>
                                @if ($c->activo)
                                    <span class="badge badge-verde" style="font-size:0.78rem">Activo</span>
                                @else
                                    <span class="badge badge-gris" style="font-size:0.78rem">Inactivo</span>
                                @endif
                                @if ($c->acepta_postulaciones)
                                    <span class="badge" style="background:#e0f2fe;color:#0369a1;font-size:0.75rem;margin-top:0.2rem;display:inline-block">Postulaciones</span>
                                @endif
                            </td>
                            <td style="text-align:right">
                                <div style="display:inline-flex;gap:0.35rem">
                                    <a href="{{ route('admin.consultorios.ver', $c) }}" class="btn btn-sm btn-secundario" title="Ver detalle">
                                        Ver
                                    </a>
                                    <a href="{{ route('admin.consultorios.editar', $c) }}" class="btn btn-sm btn-primario" title="Editar información">
                                        Editar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-tarjeta>
@endsection
