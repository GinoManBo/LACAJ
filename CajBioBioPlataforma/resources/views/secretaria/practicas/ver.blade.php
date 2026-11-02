@extends('layouts.portal', ['migas' => [['Prácticas activas', route('secretaria.practicas')], ['Detalle']]])
@section('titulo', 'Práctica de '.$practica->practicante->postulante->nombre)
@section('contenido')
@php
    $postulante = $practica->practicante->postulante; $nom = $postulante->nombre.' '.$postulante->apellido;
    $est = $practica->estado_visible; $c = $practica->consultorio; $a = $practica->abogado?->usuario; $e = $practica->estadistica;
    $anio = ['quinto' => 'Quinto año', 'egresado' => 'Egresado/a'][$postulante->anio_carrera] ?? $postulante->anio_carrera;
@endphp
<div class="sec-cabecera">
    <div>
        <div class="sec-titulo-ficha"><h1 class="titulo-pagina">{{ $nom }}</h1><x-badge :estado="$est" :texto="$est === 'por_terminar' ? 'Por terminar' : null" /></div>
        <p class="sec-subtitulo">{{ $postulante->universidad }} · {{ $c->nombre }}</p>
    </div>
    <div class="sec-busqueda">
        <x-boton tipo="secundario" :href="route('secretaria.practicas.estadistica', $practica->id)">@include('secretaria._icono', ['n' => 'grafico']) Ver estadísticas</x-boton>
        <x-boton tipo="peligro" data-modal-abrir="modal-cancelar">Cancelar práctica</x-boton>
    </div>
</div>

<div class="sec-dos">
    <x-tarjeta titulo="Postulante">
        <dl class="datos">
            <div><dt>RUT</dt><dd>{{ $postulante->rut }}</dd></div>
            <div><dt>Año de carrera</dt><dd>{{ $anio }}</dd></div>
            <div><dt>Correo</dt><dd><a href="mailto:{{ $postulante->email }}">{{ $postulante->email }}</a></dd></div>
            <div><dt>Teléfono</dt><dd>{{ $postulante->telefono }}</dd></div>
            <div class="ancho-completo"><dt>Domicilio</dt><dd>{{ $postulante->direccion }}, {{ $postulante->comuna?->nombre }}</dd></div>
            <div class="ancho-completo"><dt>Discapacidad</dt><dd>{{ $postulante->discapacidad ? 'Sí' : 'No' }}@if ($postulante->discapacidad && $postulante->discapacidad_descripcion) — {{ $postulante->discapacidad_descripcion }}@endif</dd></div>
        </dl>
    </x-tarjeta>
    <x-tarjeta titulo="Práctica">
        <dl class="datos">
            <div class="ancho-completo"><dt>Consultorio</dt><dd>{{ $c->nombre }}<span class="sec-menor">{{ $c->direccion }}@if ($c->comuna), {{ $c->comuna->nombre }}@endif</span></dd></div>
            <div class="ancho-completo"><dt>Abogado Jefe Consultorio</dt><dd>{{ $a ? $a->nombre.' '.$a->apellido : 'Sin asignar' }}</dd></div>
            <div><dt>Inicio</dt><dd>{{ $practica->fecha_inicio->format('d-m-Y') }}</dd></div>
            <div><dt>Término</dt><dd>{{ $practica->fecha_termino->format('d-m-Y') }}</dd></div>
        </dl>
        <div class="sec-avance" style="margin-top:1.25rem;font-size:.88rem">
            <div class="sec-barra" role="img" aria-label="Avance {{ $e['porcentaje'] }}%"><i style="--p:{{ $e['porcentaje'] }}%"></i></div>
            <span>{{ $e['porcentaje'] }}% · {{ $e['transcurridos'] }} de {{ $e['total'] }} días · <a href="{{ route('secretaria.practicas.estadistica', $practica->id) }}">Ver estadísticas</a></span>
        </div>
    </x-tarjeta>
</div>

<div class="sec-dos" style="margin-top: 1.5rem;">
    <x-tarjeta titulo="Carta de Presentación Oficial">
        <p style="font-size: 0.9rem; color: #4b5563; margin-bottom: 1rem;">
            Documento formal emitido a nombre de la Dirección de Acceso a la Justicia para presentación del postulante ante el Consultorio Jurídico y aviso reglamentario de alegatos ante la Corte de Apelaciones.
        </p>

        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
            <x-boton tipo="secundario" :href="route('secretaria.practicas.carta_presentacion', $practica->id)" target="_blank">
                @include('secretaria._icono', ['n' => 'documento']) Descargar Carta Tipo (PDF)
            </x-boton>
            @if ($practica->carta_presentacion_firmada)
                <x-boton tipo="primario" :href="route('secretaria.practicas.ver_carta_firmada', $practica->id)" target="_blank">
                    @include('secretaria._icono', ['n' => 'documento']) Ver Carta Firmada en Sistema
                </x-boton>
            @endif
        </div>

        @if ($practica->carta_presentacion_firmada)
            <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 0.85rem; margin-bottom: 1rem;">
                <p style="margin: 0; font-size: 0.88rem; color: #166534; font-weight: 600;">
                    ✓ Carta firmada registrada el {{ $practica->carta_presentacion_subida_at?->format('d-m-Y H:i') ?? 'N/A' }}
                </p>
                <p style="margin: 0.35rem 0 0 0; font-size: 0.82rem; color: #15803d;">
                    Inicio manual oficial completado. Recuerde remitir formalmente este documento al Abogado Jefe del consultorio.
                </p>
            </div>
        @else
            <div style="background-color: #fffbeb; border: 1px solid #fef3c7; border-radius: 6px; padding: 0.85rem; margin-bottom: 1rem;">
                <p style="margin: 0; font-size: 0.85rem; color: #92400e;">
                    <strong>Paso pendiente:</strong> Descargue la carta tipo, recabe la firma autorizada institucional, y cargue el documento escaneado/firmado para oficializar el inicio y notificar al Abogado Jefe.
                </p>
            </div>
        @endif

        <form action="{{ route('secretaria.practicas.subir_carta_firmada', $practica->id) }}" method="POST" enctype="multipart/form-data" style="margin-top: 0.75rem;">
            @csrf
            <label for="carta_firmada" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem; color: #374151;">
                {{ $practica->carta_presentacion_firmada ? 'Reemplazar Carta Firmada (PDF)' : 'Cargar Carta Firmada (PDF)' }}
            </label>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="file" name="carta_firmada" id="carta_firmada" accept=".pdf" required style="font-size: 0.85rem; flex: 1; padding: 0.35rem; border: 1px solid #d1d5db; border-radius: 4px;">
                <button type="submit" class="btn btn-primario" style="font-size: 0.85rem; padding: 0.45rem 1rem;">
                    Subir y Oficializar
                </button>
            </div>
            @error('carta_firmada')
                <p style="color: #dc2626; font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p>
            @enderror
        </form>
    </x-tarjeta>

    <x-tarjeta titulo="Instancias Judiciales y Calificaciones">
        <!-- 1ª Instancia -->
        <div style="border-bottom: 1px solid #e5e7eb; padding-bottom: 1rem; margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <strong style="font-size: 0.95rem; color: #1f2937;">1ª Instancia: Consultorio Jurídico</strong>
                @if ($calif1)
                    <span class="badge" style="background:#e0f2fe;color:#0369a1;padding:0.25rem 0.6rem;border-radius:9999px;font-weight:600;font-size:0.82rem;">{{ \App\Support\Notas::concepto($calif1->nota) }}</span>
                @else
                    <span class="badge" style="background:#fef3c7;color:#92400e;padding:0.25rem 0.6rem;border-radius:9999px;font-weight:600;font-size:0.82rem;">En desarrollo / Pendiente</span>
                @endif
            </div>
            <p style="font-size: 0.82rem; color: #6b7280; margin: 0.35rem 0 0 0;">
                Calificación reglamentaria de los 6 meses continuos de práctica por el Abogado Jefe de Consultorio.
            </p>
            @if ($calif1)
                <p style="font-size: 0.82rem; color: #374151; margin-top: 0.5rem;">
                    <strong>Observaciones:</strong> {{ $calif1->observaciones ?: 'Sin observaciones registradas.' }}
                </p>
            @endif
        </div>

        <!-- 2ª Instancia -->
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <strong style="font-size: 0.95rem; color: #1f2937;">2ª Instancia: Corte de Apelaciones</strong>
                @if ($calif2)
                    <span class="badge" style="background:#e0f2fe;color:#0369a1;padding:0.25rem 0.6rem;border-radius:9999px;font-weight:600;font-size:0.82rem;">{{ \App\Support\Notas::concepto($calif2->nota) }}</span>
                @else
                    <span class="badge" style="background:#f3f4f6;color:#6b7280;padding:0.25rem 0.6rem;border-radius:9999px;font-weight:600;font-size:0.82rem;">Sin registro</span>
                @endif
            </div>
            <p style="font-size: 0.82rem; color: #6b7280; margin: 0.35rem 0 0 0;">
                Evaluación cualitativa complementaria de alegatos ante el Tribunal de Alzada.
            </p>
            @if ($calif2)
                <p style="font-size: 0.82rem; color: #374151; margin-top: 0.5rem;">
                    <strong>Observaciones:</strong> {{ $calif2->observaciones ?: 'Sin observaciones registradas.' }}
                </p>
            @endif

            <details style="margin-top: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 0.75rem;">
                <summary style="font-size: 0.85rem; font-weight: 600; color: #374151; cursor: pointer;">
                    Carga Asistida de 2ª Instancia (Oficio / Correo de Corte)
                </summary>
                <form action="{{ route('secretaria.practicas.segunda_instancia', $practica->id) }}" method="POST" style="margin-top: 0.75rem;">
                    @csrf
                    <div style="margin-bottom: 0.75rem;">
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 0.25rem;">
                            Calificación cualitativa (en palabras):
                        </label>
                        <select name="concepto" required style="width: 100%; font-size: 0.85rem; padding: 0.4rem; border: 1px solid #d1d5db; border-radius: 4px;">
                            <option value="">-- Seleccione concepto cualitativo --</option>
                            @foreach ($opcionesNotas as $clave => $opc)
                                <option value="{{ $clave }}" @selected(old('concepto', $calif2 ? \App\Support\Notas::claveConcepto($calif2->nota) : '') === $clave)>
                                    {{ $opc['nombre'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 0.25rem;">
                            Observaciones de alegatos y desempeño en Corte:
                        </label>
                        <textarea name="observaciones" rows="3" required placeholder="Detalle antecedentes de los alegatos en Corte de Apelaciones..." style="width: 100%; font-size: 0.85rem; padding: 0.4rem; border: 1px solid #d1d5db; border-radius: 4px;">{{ old('observaciones', $calif2?->observaciones) }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-secundario" style="font-size: 0.82rem; width: 100%; padding: 0.45rem;">
                        Guardar Calificación de Segunda Instancia
                    </button>
                </form>
            </details>
        </div>
    </x-tarjeta>
</div>
@include('secretaria.practicas._modal-cancelar', ['accion' => route('secretaria.practicas.cancelar', $practica->id), 'nombre' => $nom])
@endsection
