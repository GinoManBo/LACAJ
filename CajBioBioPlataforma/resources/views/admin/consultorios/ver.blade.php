@extends('layouts.portal', ['migas' => [
    ['Administración', route('admin.inicio')],
    ['Consultorios y Sedes', route('admin.consultorios.index')],
    [$sede->nombre]
]])
@section('titulo', $sede->nombre.' · Sede')

@section('contenido')
@include('admin._nav')

<div class="cabecera-ficha" style="margin-bottom:1.5rem">
    <div>
        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap">
            <h1 class="titulo-pagina" style="margin:0">{{ $sede->nombre }}</h1>
            @if ($sede->tipo_unidad)
                <span class="badge" style="background:#e0f2fe;color:#0369a1;font-weight:600;font-size:0.85rem">
                    {{ $sede->tipo_unidad_texto }}
                </span>
            @endif
            @if ($sede->activo)
                <span class="badge badge-verde">Activo</span>
            @else
                <span class="badge badge-gris">Inactivo</span>
            @endif
        </div>
        <p class="texto-suave" style="margin:.35rem 0 0">
            {{ $sede->region?->nombre }} · Comuna de {{ $sede->comuna?->nombre ?? 'Sin comuna' }}
        </p>
    </div>
    <div style="display:flex;gap:0.5rem">
        <x-boton tipo="primario" :href="route('admin.consultorios.editar', $sede)">Editar Información</x-boton>
        <x-boton tipo="secundario" :href="route('admin.consultorios.index')">← Volver</x-boton>
    </div>
</div>

<div class="rejilla-indicadores" style="margin-bottom:1.5rem">
    <x-indicador :valor="$sede->capacidad" etiqueta="Capacidad total" detalle="Cupos formativos máximos" />
    <x-indicador :valor="$ocupados" etiqueta="Postulantes asignados" detalle="En curso o por iniciar" />
    <x-indicador :valor="$libres" etiqueta="Cupos disponibles" :critico="$libres === 0" detalle="{{ $porcentaje }}% de ocupación" />
</div>

<div class="tp-dos" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:1.5rem;margin-bottom:1.5rem">
    <x-tarjeta titulo="Información de Contacto y Atención">
        <dl class="datos" style="display:grid;grid-template-columns:1fr;gap:0.75rem">
            <div>
                <dt style="font-weight:600;font-size:0.8rem;color:#6b7280;text-transform:uppercase">Dirección</dt>
                <dd style="margin:0;font-size:0.95rem;color:#1f2937">{{ $sede->direccion ?: 'Sin dirección registrada' }}</dd>
            </div>
            <div>
                <dt style="font-weight:600;font-size:0.8rem;color:#6b7280;text-transform:uppercase">Teléfono(s)</dt>
                <dd style="margin:0;font-size:0.95rem;color:#1f2937">
                    @if ($sede->telefono)
                        📞 {{ $sede->telefono }}
                    @else
                        <span class="texto-suave">Sin teléfono registrado</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt style="font-weight:600;font-size:0.8rem;color:#6b7280;text-transform:uppercase">Correo Electrónico</dt>
                <dd style="margin:0;font-size:0.95rem;color:#1f2937">
                    @if ($sede->email)
                        ✉️ <a href="mailto:{{ $sede->email }}" style="color:#0284c7">{{ $sede->email }}</a>
                    @else
                        <span class="texto-suave">Sin correo registrado</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt style="font-weight:600;font-size:0.8rem;color:#6b7280;text-transform:uppercase">Horario de Atención</dt>
                <dd style="margin:0;font-size:0.9rem;color:#374151;white-space:pre-line;background:#f8fafc;padding:0.6rem;border-radius:4px;border:1px solid #e2e8f0">{{ $sede->horario_atencion ?: 'Sin horario especificado' }}</dd>
            </div>
            <div>
                <dt style="font-weight:600;font-size:0.8rem;color:#6b7280;text-transform:uppercase">Habilitación de Postulaciones</dt>
                <dd style="margin:0;font-size:0.95rem">
                    {{ $sede->acepta_postulaciones ? '✓ Acepta asignación de postulantes' : '✗ Asignación deshabilitada temporalmente' }}
                </dd>
            </div>
        </dl>
    </x-tarjeta>

    <x-tarjeta titulo="Gestión de Capacidad y Cupos">
        <div class="ajuste" style="display:flex;align-items:center;justify-content:center;gap:1.5rem;margin:1rem 0">
            <form method="POST" action="{{ route('admin.consultorios.capacidad', $sede) }}">
                @csrf <input type="hidden" name="accion" value="menos">
                <button type="submit" class="ajuste-boton" aria-label="Retirar un cupo" @disabled($libres === 0) data-permitir-repetir style="font-size:1.5rem;width:3rem;height:3rem">&minus;</button>
            </form>
            <div class="ajuste-valor" aria-live="polite" style="text-align:center">
                <span style="font-size:2.2rem;font-weight:700;display:block">{{ $sede->capacidad }}</span>
                <small class="texto-suave">cupos totales</small>
            </div>
            <form method="POST" action="{{ route('admin.consultorios.capacidad', $sede) }}">
                @csrf <input type="hidden" name="accion" value="mas">
                <button type="submit" class="ajuste-boton" aria-label="Agregar un cupo" data-permitir-repetir style="font-size:1.5rem;width:3rem;height:3rem">+</button>
            </form>
        </div>

        <div class="barra barra-grande" role="meter" aria-valuemin="0" aria-valuemax="{{ $sede->capacidad }}" aria-valuenow="{{ $ocupados }}" style="margin:1rem 0">
            <span class="barra-relleno barra-{{ $disponibilidad }}" style="--ancho: {{ $porcentaje }}%"></span>
        </div>

        <div style="display:flex;justify-content:space-between;font-size:0.9rem;margin-bottom:1rem">
            <span><strong>{{ $ocupados }}</strong> ocupados</span>
            <span><strong>{{ $libres }}</strong> libres</span>
        </div>

        <form method="POST" action="{{ route('admin.consultorios.capacidad', $sede) }}" style="border-top:1px solid #e5e7eb;padding-top:1rem">
            @csrf
            <label for="cap-directa" class="campo-etiqueta" style="font-size:0.85rem">Fijar capacidad exacta</label>
            <div style="display:flex;gap:0.5rem;align-items:center">
                <input id="cap-directa" name="capacidad" type="number" min="{{ $ocupados }}" max="500" value="{{ $sede->capacidad }}" class="campo-control" style="max-width:8rem">
                <x-boton type="submit" tipo="secundario">Aplicar</x-boton>
            </div>
        </form>
    </x-tarjeta>
</div>

<div class="tp-dos" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:1.5rem">
    <x-tarjeta :titulo="'Postulantes asignados ('.$asignados->count().')'" sinPadding>
        @if ($asignados->isEmpty())
            <div style="padding:1.5rem">
                <x-estado-vacio titulo="Sin postulantes asignados" texto="Todos los cupos de esta sede están actualmente disponibles." />
            </div>
        @else
            <ul class="lista-filas">
                @foreach ($asignados as $p)
                    @php $po = $p->practicante->postulante; @endphp
                    <li style="padding:0.75rem 1rem;border-bottom:1px solid #f3f4f6">
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <div>
                                <a href="{{ route('admin.practicantes.ver', $p) }}" style="font-weight:600;color:#1f2937">
                                    {{ $po->nombre }} {{ $po->apellido }}
                                </a>
                                <span class="dato-menor texto-suave" style="display:block">
                                    {{ $p->abogado?->usuario ? 'Abogado Jefe: '.$p->abogado->usuario->nombre_completo : 'Sin abogado asignado' }} · Término: {{ $p->fecha_termino->format('d/m/Y') }}
                                </span>
                            </div>
                            <x-badge :estado="$p->estado_visible" />
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-tarjeta>

    <x-tarjeta :titulo="'Abogados vinculados ('.$sede->abogados->count().')'" sinPadding>
        @if ($sede->abogados->isEmpty())
            <div style="padding:1.5rem">
                <x-estado-vacio titulo="Sin abogados vinculados" texto="Vincule abogados a este consultorio desde la sección de Usuarios." />
            </div>
        @else
            <ul class="lista-filas">
                @foreach ($sede->abogados as $abg)
                    @php $u = $abg->usuario; @endphp
                    <li style="padding:0.75rem 1rem;border-bottom:1px solid #f3f4f6">
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <div>
                                <a href="{{ route('admin.usuarios.editar', $u) }}" style="font-weight:600;color:#1f2937">
                                    {{ $u->nombre_completo }}
                                </a>
                                <span class="dato-menor texto-suave" style="display:block">
                                    {{ $u->email }} · {{ $abg->es_segunda_instancia ? 'Segunda Instancia (Corte)' : 'Primera Instancia (Consultorio)' }}
                                </span>
                            </div>
                            <span class="badge badge-neutro">{{ ucfirst($abg->area ?: 'General') }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-tarjeta>
</div>
@endsection
