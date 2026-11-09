@extends('layouts.portal', ['migas' => [
    auth()->user()->rol === 'juridica_central' ? ['Antes: Postulaciones', route('secretaria.juridica.postulaciones')] : ['Postulaciones', route('secretaria.postulaciones')],
    [$postulante->nombre.' '.$postulante->apellido]
]])
@section('titulo', 'Postulación de '.$postulante->nombre.' '.$postulante->apellido)
@section('contenido')
@php
    $esJuridica = auth()->user()->rol === 'juridica_central';
    $esExterno = $esJuridica && ($postulacion->region?->codigo ?? $postulante->region?->codigo) !== 'BI';
    $prefs = $postulacion->preferencias->sortBy('orden')->values();
    $reabrir = $errors->has('consultorio_id') || $errors->has('abogado_id');
    $elegido = old('consultorio_id');
@endphp

<div class="sec-titulo-ficha">
    <div>
        <div class="sec-titulo-ficha">
            <h1 class="titulo-pagina">{{ $postulante->nombre }} {{ $postulante->apellido }}</h1>
            <x-badge :estado="$postulacion->estado" />
            @if ($cancelacionesPrevias->isNotEmpty())
                <span class="sec-tag sec-tag-peligro" style="background:#fee2e2;color:#991b1b;border:1px solid #f87171;font-weight:700;display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .65rem;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    CANCELACIÓN PREVIA REGISTRADA
                </span>
            @endif
            @if ($esExterno)
                <span class="sec-tag" style="background:#f3e8ff;color:#6b21a8;font-weight:700;">EXTERNO ({{ $postulacion->region?->codigo ?? 'EXT' }})</span>
            @endif
        </div>
        <p class="sec-subtitulo">{{ $postulante->universidad }} · Postuló el {{ $postulacion->fecha_postulacion->format('d-m-Y') }}</p>
        @if ($postulante->datos_actualizados_at)
            <p class="sec-subtitulo"><span class="sec-tag sec-tag-otra">Datos actualizados</span> El postulante actualizó sus datos el {{ $postulante->datos_actualizados_at->format('d-m-Y H:i') }}</p>
        @endif
    </div>
</div>

@if ($cancelacionesPrevias->isNotEmpty())
    <div class="alerta alerta-peligro" role="alert" style="margin-bottom: 1.5rem; background: #fff1f2; border: 2px solid #fecdd3; border-left: 6px solid #e11d48; padding: 1.25rem 1.5rem; border-radius: .75rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <div style="display:flex; align-items:flex-start; gap: 1rem;">
            <div style="flex-shrink:0; width:2.75rem; height:2.75rem; border-radius:50%; background:#fee2e2; display:flex; align-items:center; justify-content:center; color:#be123c;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div style="flex:1;">
                <div style="display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; margin-bottom:.35rem;">
                    <h2 style="margin:0; font-size:1.15rem; font-weight:700; color:#9f1239;">
                        Antecedente Institucional: Este postulante cuenta con una cancelación previa
                    </h2>
                    <span class="sec-tag" style="background:#be123c; color:#ffffff; font-weight:700; font-size:.75rem; padding:.2rem .6rem;">
                        {{ $cancelacionesPrevias->count() === 1 ? '1 cancelación histórica' : $cancelacionesPrevias->count().' cancelaciones históricas' }}
                    </span>
                </div>
                <p style="margin:0 0 1rem; color:#881337; font-size:.92rem; line-height:1.5;">
                    El postulante registra antecedentes de una práctica anterior formalmente cancelada en la plataforma. Al momento de asignarle consultorio, debe considerar las causales y antecedentes que motivaron la resolución anterior:
                </p>

                <div style="display:flex; flex-direction:column; gap:.75rem;">
                    @foreach ($cancelacionesPrevias as $prAnt)
                        @php
                            $motivoTexto = \App\Services\Practicas::textoMotivoCancelacion($prAnt->cancelacion_motivo);
                        @endphp
                        <div style="background:#ffffff; border:1px solid #fecdd3; border-radius:.5rem; padding:1rem; color:#1e293b;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap; margin-bottom:.6rem; padding-bottom:.5rem; border-bottom:1px solid #ffe4e6;">
                                <div>
                                    <span style="font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; color:#9f1239; font-weight:700; display:block;">Causal reglamentaria de cancelación</span>
                                    <strong style="font-size:1.1rem; color:#be123c;">{{ $motivoTexto }}</strong>
                                </div>
                                <div style="text-align:right;">
                                    <span style="font-size:.78rem; color:#64748b; display:block;">Fecha de cancelación</span>
                                    <strong class="sec-num" style="color:#334155;">{{ $prAnt->cancelacion_fecha ? $prAnt->cancelacion_fecha->format('d-m-Y') : 'Sin fecha registrada' }}</strong>
                                    @if ($prAnt->cancelacion_fecha)
                                        <span class="texto-suave" style="font-size:.8rem; display:block;">({{ $prAnt->cancelacion_fecha->diffForHumans() }})</span>
                                    @endif
                                </div>
                            </div>

                            <dl class="sec-dl-compacto" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:.75rem; margin:0 0 .5rem; font-size:.88rem;">
                                <div>
                                    <dt style="color:#64748b; font-size:.78rem; text-transform:uppercase; font-weight:600;">Sede / Consultorio anterior</dt>
                                    <dd style="margin:0; font-weight:600;">{{ $prAnt->consultorio?->nombre ?? 'No asignado' }} @if($prAnt->consultorio?->region) <span class="texto-suave">({{ $prAnt->consultorio->region->nombre }})</span>@endif</dd>
                                </div>
                                @if ($prAnt->abogado?->usuario)
                                    <div>
                                        <dt style="color:#64748b; font-size:.78rem; text-transform:uppercase; font-weight:600;">Abogado tutor asignado</dt>
                                        <dd style="margin:0;">{{ $prAnt->abogado->usuario->nombre_completo }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt style="color:#64748b; font-size:.78rem; text-transform:uppercase; font-weight:600;">Momento de la cancelación</dt>
                                    <dd style="margin:0;">{{ $prAnt->cancelacion_antes_de_iniciar ? 'Antes de iniciar funciones' : 'Durante el transcurso de la práctica' }}</dd>
                                </div>
                                @if ($prAnt->canceladaPor)
                                    <div>
                                        <dt style="color:#64748b; font-size:.78rem; text-transform:uppercase; font-weight:600;">Cancelada por</dt>
                                        <dd style="margin:0;">{{ $prAnt->canceladaPor->nombre_completo }} <span class="texto-suave">({{ ucfirst($prAnt->canceladaPor->rol) }})</span></dd>
                                    </div>
                                @endif
                            </dl>

                            @if ($prAnt->cancelacion_detalle)
                                <div style="background:#fff1f2; border-left:3px solid #f43f5e; padding:.65rem .85rem; border-radius:.35rem; margin-top:.5rem; font-size:.88rem;">
                                    <strong style="color:#9f1239; display:block; font-size:.78rem; text-transform:uppercase; margin-bottom:.15rem;">Detalle / Justificación de la cancelación:</strong>
                                    <span style="color:#4c0519; white-space:pre-line;">{{ $prAnt->cancelacion_detalle }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif

<div class="sec-ficha con-lateral">
    <div class="sec-columna">
        <x-tarjeta titulo="Datos del postulante">@include('secretaria._datos-postulante')</x-tarjeta>

        <x-tarjeta titulo="Consultorios a los que postuló">
            @if ($prefs->isEmpty())
                <p class="sec-ausente">El postulante no indicó preferencias.</p>
            @else
                <ol class="sec-orden">
                    @foreach ($prefs as $pf)
                        <li><span class="resumen-num">{{ $pf->orden }}</span><div><strong>{{ $pf->consultorio->nombre }}</strong><span class="sec-menor">{{ $pf->consultorio->comuna?->nombre }}@if ($pf->consultorio->direccion) · {{ $pf->consultorio->direccion }}@endif</span></div></li>
                    @endforeach
                </ol>
            @endif
            <p class="resumen-fecha" style="margin-top:1rem"><span class="texto-suave">Fecha de inicio solicitada</span><strong style="text-transform:none">{{ $postulacion->fecha_inicio_solicitada?->format('d-m-Y') ?? 'Por completar' }}</strong></p>
        </x-tarjeta>

        <x-tarjeta titulo="Documentos">@include('secretaria._documentos', ['documentosCedula' => $documentosCedula, 'documentosUniversitarios' => $documentosUniversitarios, 'faltaCedula' => $faltaCedula])</x-tarjeta>

        @if ($postulacion->derivaciones->isNotEmpty())
            <x-tarjeta titulo="Historial de derivaciones">
                @foreach ($postulacion->derivaciones as $d)
                    <div class="sec-deriva">
                        <strong>{{ $d->regionOrigen->nombre }} → {{ $d->regionDestino->nombre }}</strong>
                        <span class="texto-suave sec-num">{{ \Illuminate\Support\Carbon::parse($d->created_at)->format('d-m-Y') }}</span>
                        @if ($d->nota)<span style="flex-basis:100%;white-space:pre-line">{{ $d->nota }}</span>@endif
                    </div>
                @endforeach
            </x-tarjeta>
        @endif

        <x-tarjeta titulo="Comentarios">
            <p class="texto-suave" style="margin:0 0 1rem;font-size:.92rem">Los comentarios viajan con la postulación: si se deriva, la secretaría de destino los verá.</p>
            @forelse ($postulacion->notas as $n)
                <article class="sec-nota">
                    <p>{{ $n->texto }}</p>
                    <footer>
                        <span>{{ $n->autor?->nombre }} {{ $n->autor?->apellido }} · {{ $n->created_at->format('d-m-Y H:i') }}</span>
                        @if ($n->autor_usuario_id === auth()->id())
                            <form method="POST" action="{{ route('secretaria.postulaciones.nota.eliminar', [$postulacion->id, $n->id]) }}" onsubmit="return confirm('¿Eliminar este comentario?')">
                                @csrf @method('DELETE')
                                <x-boton type="submit" tipo="fantasma" tam="sm">Eliminar</x-boton>
                            </form>
                        @endif
                    </footer>
                </article>
            @empty
                <p class="sec-ausente">Aún no hay comentarios.</p>
            @endforelse
            <form method="POST" action="{{ route('secretaria.postulaciones.nota.crear', $postulacion->id) }}" class="sec-nota-nueva">
                @csrf
                <x-campo name="texto" label="Nuevo comentario" type="textarea" :obligatorio="true" data-mensaje="Escriba el comentario." maxlength="2000" />
                <div><x-boton type="submit" tipo="secundario">Agregar comentario</x-boton></div>
            </form>
        </x-tarjeta>
    </div>

    <aside class="sec-lateral">
        @if ($esExterno)
            <x-tarjeta titulo="Competencia territorial" acento>
                <div class="sec-decidir">
                    <dl>
                        <div><dt>Región de origen</dt><dd><strong>{{ $postulacion->region?->nombre ?? $postulante->region?->nombre }}</strong></dd></div>
                        <div><dt>Inicio solicitado</dt><dd>{{ $postulacion->fecha_inicio_solicitada?->format('d-m-Y') ?? 'Por completar' }}</dd></div>
                    </dl>
                    <div style="background:#f3e8ff; border:1px solid #d8b4fe; color:#581c87; padding:1rem; border-radius:.5rem; font-size:.88rem; line-height:1.45; margin-top:.5rem;">
                        <div style="font-weight:700; margin-bottom:.35rem; display:flex; align-items:center; gap:.5rem;">
                            <span class="sec-tag" style="background:#6b21a8;color:#ffffff;font-size:.7rem;">SOLO LECTURA</span>
                            <span>Postulación Regional</span>
                        </div>
                        <p style="margin:0;">Como Secretaría Jurídica Central (Biobío), su acceso en esta etapa previa es únicamente de supervisión. La asignación de consultorio, aceptación o rechazo es <strong>competencia exclusiva de la Secretaría Regional de origen</strong>.</p>
                    </div>
                </div>
            </x-tarjeta>
        @elseif ($postulacion->estado !== 'pendiente')
            <x-tarjeta titulo="Estado de resolución" acento>
                <div class="sec-decidir">
                    <dl>
                        <div><dt>Estado de la postulación</dt><dd><x-badge :estado="$postulacion->estado" /></dd></div>
                        <div><dt>Región gestora</dt><dd><strong>{{ $postulacion->region?->nombre ?? $postulante->region?->nombre }}</strong></dd></div>
                        @if ($postulacion->resuelta_at)
                            <div><dt>Fecha de resolución</dt><dd>{{ $postulacion->resuelta_at->format('d-m-Y H:i') }}</dd></div>
                        @endif
                        @if ($postulacion->revisadaPor)
                            <div><dt>Revisada por</dt><dd>{{ $postulacion->revisadaPor->nombre }} {{ $postulacion->revisadaPor->apellido }}</dd></div>
                        @endif
                        @if ($postulacion->consultorioAsignado)
                            <div><dt>Consultorio asignado</dt><dd><strong>{{ $postulacion->consultorioAsignado->nombre }}</strong></dd></div>
                        @endif
                    </dl>

                    @if ($postulacion->estado === 'aceptada')
                        <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; padding:.85rem; border-radius:.5rem; font-size:.88rem; line-height:1.45; margin-top:.5rem;">
                            <strong>Postulación Tramitada y Aceptada</strong>
                            <p style="margin:.3rem 0 0;">Esta postulación ya concluyó su etapa de admisión. Se encuentra formalizada como práctica reglamentaria.</p>
                        </div>
                        @if ($postulacion->practica)
                            <div style="margin-top:.75rem;">
                                @php
                                    $urlPractica = in_array($postulacion->practica->estado, ['remitida_biobio', 'nivelada_biobio', 'rechazada_sancion', 'cerrada_historial'], true)
                                        ? route('secretaria.juridica.ver', $postulacion->practica->id)
                                        : route('secretaria.practicas.ver', $postulacion->practica->id);
                                @endphp
                                <x-boton :href="$urlPractica" tam="sm">Ver Ficha de la Práctica</x-boton>
                            </div>
                        @endif
                    @elseif ($postulacion->estado === 'rechazada')
                        <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:.85rem; border-radius:.5rem; font-size:.88rem; line-height:1.45; margin-top:.5rem;">
                            <strong>Postulación Rechazada</strong>
                            @if ($postulacion->motivo_rechazo)
                                <p style="margin:.3rem 0 0;"><em>Motivo:</em> {{ $postulacion->motivo_rechazo }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </x-tarjeta>
        @else
            <x-tarjeta titulo="Resolver postulación" acento>
                <div class="sec-decidir">
                    <dl>
                        <div><dt>Región que la gestiona</dt><dd>{{ $postulante->region?->nombre }}</dd></div>
                        <div><dt>Inicio solicitado</dt><dd>{{ $postulacion->fecha_inicio_solicitada?->format('d-m-Y') ?? 'Por completar' }}</dd></div>
                    </dl>
                    @if ($cancelacionesPrevias->isNotEmpty())
                        @php $prUltima = $cancelacionesPrevias->first(); @endphp
                        <div style="background:#fff1f2; border:1px solid #fecdd3; border-left:4px solid #e11d48; color:#9f1239; padding:.75rem .9rem; border-radius:.5rem; font-size:.85rem; line-height:1.45; margin-bottom:.85rem;">
                            <strong style="display:flex; align-items:center; gap:.35rem; margin-bottom:.2rem;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                Cancelación previa:
                            </strong>
                            <span>{{ \App\Services\Practicas::textoMotivoCancelacion($prUltima->cancelacion_motivo) }}</span>
                            @if ($prUltima->cancelacion_fecha)
                                <div class="texto-suave" style="font-size:.78rem; color:#be123c; margin-top:.2rem;">Fecha: {{ $prUltima->cancelacion_fecha->format('d-m-Y') }}</div>
                            @endif
                        </div>
                    @endif
                    <p>Asigne un consultorio para aceptar, o rechace la postulación indicando el motivo.</p>
                    <x-boton data-modal-abrir="modal-aceptar">Aceptar y asignar consultorio</x-boton>
                    <x-boton tipo="peligro" data-modal-abrir="modal-rechazar">Rechazar postulación</x-boton>
                </div>
            </x-tarjeta>
        @endif
    </aside>
</div>

@if (! $esExterno && $postulacion->estado === 'pendiente')
<x-modal id="modal-rechazar" titulo="Rechazar postulación">
    <form id="form-rechazar" method="POST" action="{{ route('secretaria.postulaciones.rechazar', $postulacion->id) }}" class="sec-asignar">
        @csrf
        <p style="margin:0">Se rechazará la postulación de <strong>{{ $postulante->nombre }} {{ $postulante->apellido }}</strong>. Esta acción no se puede deshacer.</p>
        <x-campo name="motivo" label="Motivo (opcional)" type="textarea" ayuda="Queda registrado en la postulación." maxlength="2000" />
    </form>
    <x-slot:pie><x-boton type="submit" tipo="peligro" form="form-rechazar">Rechazar postulación</x-boton></x-slot:pie>
</x-modal>

<x-modal id="modal-aceptar" titulo="Asignar consultorio" :grande="true">
    <form id="form-aceptar" method="POST" action="{{ route('secretaria.postulaciones.aceptar', $postulacion->id) }}" class="sec-asignar" data-nombre="{{ $postulante->nombre }} {{ $postulante->apellido }}">
        @csrf
        @if ($cancelacionesPrevias->isNotEmpty())
            @php $prUltima = $cancelacionesPrevias->first(); @endphp
            <div style="background:#fff1f2; border:1px solid #fecdd3; border-left:4px solid #e11d48; color:#9f1239; padding:.75rem 1rem; border-radius:.5rem; margin-bottom:1rem; font-size:.88rem; display:flex; align-items:flex-start; gap:.65rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; margin-top:.15rem;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <div>
                    <strong>Postulante con Cancelación Previa Registrada</strong>
                    <div style="margin-top:.2rem; color:#881337;">
                        Causal: <strong>{{ \App\Services\Practicas::textoMotivoCancelacion($prUltima->cancelacion_motivo) }}</strong>
                        @if ($prUltima->cancelacion_detalle) · {{ $prUltima->cancelacion_detalle }}@endif
                        @if ($prUltima->cancelacion_fecha) (Fecha: {{ $prUltima->cancelacion_fecha->format('d-m-Y') }})@endif
                    </div>
                    <div class="texto-suave" style="font-size:.8rem; margin-top:.25rem; color:#9f1239;">
                        Considere este antecedente al seleccionar la sede y confirmar la asignación del consultorio.
                    </div>
                </div>
            </div>
        @endif
        <div class="cons-buscar">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14zM21 21l-4.3-4.3"/></svg>
            <label for="buscar-cons" class="sr-only">Buscar consultorio</label>
            <input id="buscar-cons" type="search" class="campo-control" placeholder="Buscar por nombre, dirección o región" autocomplete="off">
        </div>

        <div class="campo {{ $errors->has('consultorio_id') ? 'tiene-error' : '' }}" data-grupo-obligatorio="Seleccione un consultorio.">
            <span class="campo-etiqueta" id="etq-cons">Consultorio de destino<span class="campo-obligatorio" aria-hidden="true">*</span></span>
            <div class="sec-cons-lista" role="radiogroup" aria-labelledby="etq-cons">
                @foreach ($consultorios as $c)
                    @php $bloqueado = ! $c->otra_region && $c->disponibilidad === 'sin'; @endphp
                    <label class="sec-cons" data-buscar="{{ \Illuminate\Support\Str::lower($c->nombre.' '.$c->direccion.' '.$c->region->nombre.' '.$c->comuna?->nombre) }}">
                        <input type="radio" name="consultorio_id" value="{{ $c->id }}" data-otra="{{ $c->otra_region ? 1 : 0 }}" data-region="{{ $c->region->nombre }}" data-nombre="{{ $c->nombre }}" @checked((string) $elegido === (string) $c->id) @disabled($bloqueado)>
                        <span class="sec-cons-cuerpo">
                            <strong>{{ $c->nombre }}</strong>
                            <x-badge :estado="$c->disponibilidad" />
                            <span class="sec-cons-sub">{{ $c->direccion }}@if ($c->comuna), {{ $c->comuna->nombre }}@endif · {{ $c->region->nombre }}</span>
                            <span class="sec-cons-sub">{{ $c->ocupados }} de {{ $c->capacidad }} cupos ocupados · {{ $c->libres }} {{ $c->libres === 1 ? 'libre' : 'libres' }}@if ($bloqueado) · No se puede asignar sin cupos @endif</span>
                            @if ($c->postulo_aqui || $c->otra_region)
                                <span class="sec-etiquetas">
                                    @if ($c->postulo_aqui)<span class="sec-tag sec-tag-aqui">POSTULÓ AQUÍ</span>@endif
                                    @if ($c->otra_region)<span class="sec-tag sec-tag-otra">OTRA REGIÓN</span>@endif
                                </span>
                            @endif
                        </span>
                    </label>
                @endforeach
                <p class="sec-cons-vacio" data-sin-resultados hidden>Ningún consultorio coincide con la búsqueda.</p>
            </div>
            <p class="campo-error" role="alert">{{ $errors->first('consultorio_id') }}</p>
        </div>

        <div class="sec-extra" data-bloque-abogado hidden>
            @foreach ($consultorios->where('otra_region', false) as $c)
                <div class="campo" data-abogados-de="{{ $c->id }}" hidden>
                    <label for="abogado-{{ $c->id }}" class="campo-etiqueta">Abogado tutor (opcional)</label>
                    <select id="abogado-{{ $c->id }}" name="abogado_id" class="campo-control" disabled>
                        <option value="">Sin asignar</option>
                        @foreach ($c->abogados as $a)
                            <option value="{{ $a->id }}" @selected((string) old('abogado_id') === (string) $a->id)>{{ $a->usuario->nombre }} {{ $a->usuario->apellido }}@if ($a->area) — {{ $a->area }}@endif</option>
                        @endforeach
                    </select>
                    <p class="campo-ayuda">Puede asignarlo ahora o más tarde.</p>
                    @if ($errors->has('abogado_id'))<p class="campo-error" role="alert">{{ $errors->first('abogado_id') }}</p>@endif
                </div>
            @endforeach
        </div>

        <x-campo name="nota" label="Comentario para la secretaría de destino / registro (opcional)" type="textarea" maxlength="2000" />

        <div class="sec-resultado" data-resultado data-modo="" aria-live="polite">
            <p><strong data-resultado-titulo>Elija un consultorio</strong><span data-resultado-texto>Aquí verá qué ocurrirá al confirmar.</span></p>
        </div>
    </form>
    <x-slot:pie>
        <x-boton type="submit" tipo="ok" form="form-aceptar" data-confirmar disabled>Confirmar ingreso</x-boton>
        <x-boton type="submit" tipo="aviso" form="form-aceptar" data-derivar hidden>Derivar a otra región</x-boton>
    </x-slot:pie>
</x-modal>

<script>
(() => {
    const form = document.getElementById('form-aceptar');
    if (!form) return;
    const modal = document.getElementById('modal-aceptar');
    const radios = [...form.querySelectorAll('input[name=consultorio_id]')];
    const btnOk = document.querySelector('[data-confirmar]'), btnDer = document.querySelector('[data-derivar]');
    const res = form.querySelector('[data-resultado]');
    const bloque = form.querySelector('[data-bloque-abogado]');
    const nombre = form.dataset.nombre;

    function actualizar() {
        const r = radios.find((x) => x.checked);
        form.querySelectorAll('[data-abogados-de]').forEach((d) => { d.hidden = true; d.querySelector('select').disabled = true; });
        bloque.hidden = true;
        btnOk.disabled = !r; btnOk.hidden = false; btnDer.hidden = true;
        res.dataset.modo = '';
        if (!r) return;
        const otra = r.dataset.otra === '1';
        if (otra) {
            btnOk.hidden = true; btnDer.hidden = false;
            res.dataset.modo = 'otra';
            res.querySelector('[data-resultado-titulo]').textContent = 'Se derivará a ' + r.dataset.region;
            res.querySelector('[data-resultado-texto]').textContent = nombre + ' pasará a la secretaría de ' + r.dataset.region + ' para que decida su ingreso. Dejará de aparecer en su lista y el comentario viajará con la postulación.';
        } else {
            const d = form.querySelector('[data-abogados-de="' + r.value + '"]');
            if (d) { d.hidden = false; d.querySelector('select').disabled = false; bloque.hidden = false; }
            res.dataset.modo = 'propia';
            res.querySelector('[data-resultado-titulo]').textContent = 'Ingresará a ' + r.dataset.nombre;
            res.querySelector('[data-resultado-texto]').textContent = 'Se acepta la postulación y se crea la práctica.';
        }
    }
    radios.forEach((r) => r.addEventListener('change', actualizar));

    const buscador = document.getElementById('buscar-cons');
    buscador.addEventListener('input', () => {
        const t = buscador.value.trim().toLowerCase();
        let vistos = 0;
        form.querySelectorAll('.sec-cons').forEach((l) => { const ok = !t || l.dataset.buscar.includes(t); l.hidden = !ok; if (ok) vistos++; });
        form.querySelector('[data-sin-resultados]').hidden = vistos > 0;
    });
    buscador.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });
    actualizar();
    @if ($reabrir) modal.showModal(); @endif
})();
</script>
@endif
@endsection
