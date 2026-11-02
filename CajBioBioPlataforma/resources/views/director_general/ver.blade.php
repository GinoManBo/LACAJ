@extends('layouts.portal', ['migas' => [
    ['Bandeja nacional de firma', route('director_general.index')],
    ['Expediente: ' . $postulante->nombre_completo]
]])

@section('titulo', 'Firma y Cierre Definitivo — ' . $postulante->nombre_completo)

@section('contenido')
<div class="sec-cabecera">
    <div>
        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
            <a href="{{ route('director_general.index') }}" class="texto-suave" style="display:inline-flex; align-items:center; gap:0.25rem; font-size:0.875rem;">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver a la bandeja nacional
            </a>
        </div>
        <h1 class="titulo-pagina">{{ $postulante->nombre_completo }}</h1>
        <p class="texto-suave">
            RUT {{ $postulante->rut }} &bull; Región de {{ $region?->nombre }} ({{ $region?->codigo }}) &bull; Consultorio {{ $consultorio?->nombre }}
        </p>
    </div>
    <div class="sec-acciones">
        @if ($esCerrada)
            <span class="badge" style="background:#dcfce7; color:#15803d; font-weight:700; padding:0.5rem 0.75rem; border-radius:6px; font-size:0.875rem;">
                Expediente Cerrado e Historiado
            </span>
        @else
            <span class="badge" style="background:#fee2e2; color:#b91c1c; font-weight:700; padding:0.5rem 0.75rem; border-radius:6px; font-size:0.875rem;">
                Pendiente de Firma FirmaGob
            </span>
        @endif
    </div>
</div>

<div class="sec-dos" style="margin-bottom: 1.5rem;">
    <!-- Antecedentes del Postulante -->
    <x-tarjeta titulo="Antecedentes Institucionales">
        <dl class="datos">
            <div><dt>RUT</dt><dd>{{ $postulante->rut }}</dd></div>
            <div><dt>Universidad</dt><dd>{{ $postulante->universidad }}</dd></div>
            <div><dt>Correo electrónico</dt><dd>{{ $postulante->email }}</dd></div>
            <div><dt>Región de origen</dt><dd><strong>{{ $region?->nombre }} ({{ $region?->codigo }})</strong></dd></div>
            <div class="ancho-completo"><dt>Consultorio / Unidad</dt><dd>{{ $consultorio?->nombre }}</dd></div>
            <div class="ancho-completo"><dt>Abogado Evaluador</dt><dd>{{ $practica->abogado?->usuario?->nombre_completo ?? 'Sin asignar' }}</dd></div>
            <div><dt>Visación Regional</dt><dd>{{ $practica->validadaDirectorPor?->nombre_completo ?? 'Visada por Dirección Regional' }}</dd></div>
            <div><dt>Fecha Visación Regional</dt><dd>{{ $practica->validada_director_at ? $practica->validada_director_at->format('d/m/Y') : '—' }}</dd></div>
        </dl>
    </x-tarjeta>

    <!-- Documentos Oficiales Generados -->
    <x-tarjeta titulo="Documentos Oficiales Generados">
        <p style="font-size:0.85rem; color:#64748b; margin-bottom:1rem;">
            Descargue los borradores oficiales pre-generados para firmar mediante FirmaGob (Firma Electrónica Avanzada del Estado).
        </p>

        <div style="display:flex; flex-direction:column; gap:0.75rem;">
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:0.75rem 1rem; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-weight:600; color:#1e293b; font-size:0.9rem;">Certificado Oficial Corte Suprema</div>
                    <div style="font-size:0.75rem; color:#64748b;">Acredita aprobación para juramento de abogado</div>
                </div>
                <x-boton tipo="secundario" tam="sm" :href="route('director_general.certificado', $practica->id)" target="_blank">
                    Descargar PDF
                </x-boton>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:0.75rem 1rem; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-weight:600; color:#1e293b; font-size:0.9rem;">Resolución de Aprobación</div>
                    <div style="font-size:0.75rem; color:#64748b;">Resolución exenta de calificación</div>
                </div>
                <x-boton tipo="secundario" tam="sm" :href="route('director_general.resolucion', $practica->id)" target="_blank">
                    Descargar PDF
                </x-boton>
            </div>

            @if ($informeSaj)
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:0.75rem 1rem; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <div style="font-weight:600; color:#1e293b; font-size:0.9rem;">Informe de Práctica SAJ</div>
                        <div style="font-size:0.75rem; color:#64748b;">Emitido por el postulante en el sistema SAJ</div>
                    </div>
                    <x-boton tipo="secundario" tam="sm" :href="route('documentos.descargar', $informeSaj->id)" target="_blank">
                        Ver Informe
                    </x-boton>
                </div>
            @endif
        </div>
    </x-tarjeta>
</div>

<!-- Rúbrica y Calificación Definitiva en Palabras -->
<div class="tarjeta" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <h3 style="font-size:1.1rem; font-weight:700; color:#1e3a8a; margin-bottom:1rem;">
        Calificación y Rúbrica Auditada
    </h3>

    <div class="tabla-contenedor" style="margin-bottom: 1rem;">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Criterio Reglamentario</th>
                    <th style="text-align: right;">Concepto Evaluativo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($criterios as $c)
                    <tr>
                        <td style="font-weight:500;">{{ $c['etiqueta'] }}</td>
                        <td style="text-align: right;">
                            <span class="badge" style="background:#f1f5f9; color:#334155; font-weight:600; padding:0.25rem 0.5rem; border-radius:4px;">
                                {{ $c['concepto'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc; font-size:1rem;">
                    <th style="font-weight:700; color:#0f172a;">Calificación Oficial Final</th>
                    <th style="text-align: right;">
                        <span class="badge" style="background:#dbeafe; color:#1e40af; font-size:1.05rem; padding:0.4rem 0.85rem; border-radius:6px; font-weight:700;">
                            {{ $conceptoFinal }}
                        </span>
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>

    @if ($practica->evaluacion?->observaciones)
        <div style="background:#f8fafc; border-left:4px solid #3b82f6; padding:0.75rem 1rem; border-radius:0 6px 6px 0;">
            <div style="font-weight:600; font-size:0.85rem; color:#1e40af; margin-bottom:0.25rem;">Observaciones del Abogado Evaluador:</div>
            <div style="font-size:0.9rem; color:#334155; white-space:pre-line;">{{ $practica->evaluacion->observaciones }}</div>
        </div>
    @endif
</div>

<!-- Integración FirmaGob: Suscripción y Despacho Final -->
<div class="tarjeta" style="padding: 1.5rem; border: 2px solid {{ $esCerrada ? '#86efac' : '#3b82f6' }};">
    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1.25rem;">
        <div style="background:{{ $esCerrada ? '#dcfce7' : '#dbeafe' }}; color:{{ $esCerrada ? '#15803d' : '#1d4ed8' }}; padding:0.6rem; border-radius:8px;">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <h3 style="font-size:1.2rem; font-weight:700; color:#0f172a; margin:0;">
                Suscripción con FirmaGob (Firma Electrónica Avanzada)
            </h3>
            <p style="font-size:0.85rem; color:#64748b; margin:0;">
                Gatillador vinculante de Cierre Definitivo de Práctica y despacho oficial de antecedentes para la Corte Suprema.
            </p>
        </div>
    </div>

    @if ($puedeFirmarCierre && ! $esCerrada)
        <form method="POST" action="{{ route('director_general.firmagob', $practica->id) }}" enctype="multipart/form-data" onsubmit="return confirm('¿Confirma la carga del Certificado firmado con FirmaGob? Esta acción cerrará definitivamente el expediente, habilitará los documentos para el postulante y enviará la notificación formal.');">
            @csrf
            <div class="sec-dos" style="margin-bottom: 1rem;">
                <div>
                    <label for="archivo_firmado" class="campo-etiqueta" style="font-weight:600; font-size:0.875rem; margin-bottom:0.35rem; display:block;">
                        Certificado Oficial firmado con FirmaGob (.pdf) <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="file" id="archivo_firmado" name="archivo_firmado" accept="application/pdf" class="campo-control" style="width:100%;" required>
                    <span style="font-size:0.75rem; color:#64748b;">Suba el PDF firmado digitalmente con FEA por el Director General.</span>
                </div>
                <div>
                    <label for="folio" class="campo-etiqueta" style="font-weight:600; font-size:0.875rem; margin-bottom:0.35rem; display:block;">
                        Folio o Número de Documento FirmaGob
                    </label>
                    <input type="text" id="folio" name="folio" placeholder="Ej. 17975" class="campo-control" style="width:100%;">
                    <span style="font-size:0.75rem; color:#64748b;">Identificador único estampado en la firma digital.</span>
                </div>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label for="url_verificacion" class="campo-etiqueta" style="font-weight:600; font-size:0.875rem; margin-bottom:0.35rem; display:block;">
                    URL de Verificación de FirmaGob (Opcional)
                </label>
                <input type="url" id="url_verificacion" name="url_verificacion" placeholder="https://cajbiobio.cl/valida_firma/d/17975" class="campo-control" style="width:100%;">
                <span style="font-size:0.75rem; color:#64748b;">Enlace del validador público de firma electrónica.</span>
            </div>

            <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px; padding:0.75rem 1rem; margin-bottom:1.25rem;">
                <div style="font-weight:600; color:#1e40af; font-size:0.85rem; margin-bottom:0.25rem;">Efectos reglamentarios del registro:</div>
                <ul style="font-size:0.85rem; color:#1e3a8a; margin:0; padding-left:1.25rem;">
                    <li>El estado del expediente pasa a <strong>CerradaHistorial</strong> de forma definitiva e inalterable.</li>
                    <li>Se autoriza automáticamente la descarga de los certificados para el egresado en su portal.</li>
                    <li>Se emite la notificación formal de término de práctica para el inicio del expediente de título.</li>
                </ul>
            </div>

            <div style="display:flex; justify-content:flex-end;">
                <x-boton type="submit" tipo="primario" style="background:#1e40af; padding:0.6rem 1.25rem;">
                    Registrar Certificado FirmaGob y Despachar Cierre Definitivo
                </x-boton>
            </div>
        </form>
    @else
        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:1.25rem;">
            <div style="display:flex; align-items:center; gap:0.5rem; color:#166534; font-weight:700; font-size:1.05rem; margin-bottom:0.75rem;">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Certificado suscrito formalmente con FirmaGob y despachado
            </div>
            <dl class="datos" style="margin-bottom:1rem;">
                <div><dt>Firmado por</dt><dd><strong>{{ $practica->firmadoDirectorGeneralPor?->nombre_completo ?? 'Mauricio Decap Fernández (Director General)' }}</strong></dd></div>
                <div><dt>Fecha de Suscripción</dt><dd>{{ $practica->firmado_director_general_at ? $practica->firmado_director_general_at->format('d/m/Y H:i') : '—' }} hrs.</dd></div>
                <div><dt>Folio FirmaGob</dt><dd>{{ $practica->firmagob_folio ?? '—' }}</dd></div>
                <div><dt>Verificación FirmaGob</dt><dd>
                    @if ($practica->firmagob_url)
                        <a href="{{ $practica->firmagob_url }}" target="_blank" style="color:#0284c7; text-decoration:underline;">
                            Validar en sitio web
                        </a>
                    @else
                        —
                    @endif
                </dd></div>
            </dl>

            @if ($practica->certificadoFirmagob)
                <div style="display:flex; align-items:center; gap:0.75rem; background:#ffffff; border:1px solid #dcfce7; padding:0.75rem 1rem; border-radius:6px;">
                    <div style="color:#15803d;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div style="flex:1;">
                        <div style="font-weight:600; color:#1e293b; font-size:0.9rem;">{{ $practica->certificadoFirmagob->nombre_original }}</div>
                        <div style="font-size:0.75rem; color:#64748b;">
                            Certificado FEA registrado &bull; {{ number_format($practica->certificadoFirmagob->tamano_bytes / 1024, 1) }} KB
                        </div>
                    </div>
                    <x-boton tipo="secundario" tam="sm" :href="route('documentos.descargar', $practica->certificadoFirmagob->id)" target="_blank">
                        Descargar Certificado Firmado
                    </x-boton>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
