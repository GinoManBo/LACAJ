@extends('layouts.portal', ['migas' => [['Postulaciones']]])
@section('titulo', 'Postulaciones')
@section('contenido')
@if (session('credenciales'))
    @php $cred = session('credenciales'); @endphp
    <section class="sec-credenciales" role="alert" aria-labelledby="cred-titulo">
        <h2 id="cred-titulo">Cuenta del postulante creada</h2>
        <p>Entregue estos datos al postulante. La contraseña temporal se muestra solo esta vez y se le pedirá cambiarla al ingresar.</p>
        <div class="sec-cred-fila"><span class="sec-cred-correo">{{ $cred['email'] }}</span></div>
        <div class="sec-cred-fila">
            <code class="sec-cred-valor" id="cred-pass">{{ $cred['password'] }}</code>
            <x-boton tipo="secundario" tam="sm" id="cred-copiar" data-copiar="{{ $cred['email'] }}&#10;{{ $cred['password'] }}">@include('secretaria._icono', ['n' => 'copiar']) <span>Copiar contraseña</span></x-boton>
        </div>
    </section>
    <script>
        document.getElementById('cred-copiar')?.addEventListener('click', async (e) => {
            const b = e.currentTarget, txt = document.getElementById('cred-pass').textContent.trim();
            try { await navigator.clipboard.writeText(txt); }
            catch { const r = document.createRange(); r.selectNodeContents(document.getElementById('cred-pass')); const s = getSelection(); s.removeAllRanges(); s.addRange(r); document.execCommand('copy'); }
            b.querySelector('span').textContent = 'Copiada';
            setTimeout(() => (b.querySelector('span').textContent = 'Copiar contraseña'), 2500);
        });
    </script>
@endif

@if (session('practica_creada_id'))
    <div class="alerta alerta-info" role="status" style="margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
        <div>
            <strong>Siguiente paso administrativo:</strong>
            Descargue la Carta de Presentación tipo, recabe la firma autorizada institucional y cárguela firmada en la ficha de la práctica para oficializar el inicio y notificar al Abogado Jefe.
        </div>
        <x-boton tipo="primario" tam="sm" :href="route('secretaria.practicas.ver', session('practica_creada_id'))">
            Tramitar Carta de Presentación
        </x-boton>
    </div>
@endif

<div class="sec-cabecera">
    <div>
        <h1 class="titulo-pagina">Postulaciones pendientes</h1>
        <p class="texto-suave">{{ $postulaciones->count() }} {{ $postulaciones->count() === 1 ? 'postulación' : 'postulaciones' }}{{ $q !== '' ? ' para «'.$q.'»' : ' esperan su revisión' }}.</p>
    </div>
    <x-boton tipo="secundario" data-modal-abrir="modal-importar">@include('secretaria._icono', ['n' => 'excel']) Importar Excel</x-boton>
</div>

@php $omitidas = collect(session('importacion_omitidas', [])); @endphp
@if (session('importacion_vacia'))
    <div class="alerta alerta-info imp-aviso" role="status"><span>{{ session('importacion_vacia') }}</span></div>
@endif
@if ($omitidas->isNotEmpty())
    <details class="imp-omitidas">
        <summary>{{ $omitidas->count() }} {{ $omitidas->count() === 1 ? 'fila no se importó' : 'filas no se importaron' }}</summary>
        <div class="imp-omitidas-tabla">
            <table>
                <caption class="sr-only">Filas del Excel que no se importaron y su motivo</caption>
                <thead><tr><th scope="col">Fila</th><th scope="col">Motivo</th></tr></thead>
                <tbody>
                    @foreach ($omitidas as $o)
                        <tr><td class="sec-num">{{ $o['fila'] }}</td><td>{{ $o['motivo'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
@endif

<form method="GET" action="{{ route('secretaria.postulaciones') }}" class="sec-busqueda" role="search">
    <div class="campo"><label for="q" class="sr-only">Buscar postulación</label>
        <input id="q" name="q" type="search" value="{{ $q }}" class="campo-control" placeholder="Nombre, apellido, RUT o universidad"></div>
    <x-boton type="submit">@include('secretaria._icono', ['n' => 'buscar']) Buscar</x-boton>
    @if ($q !== '')<x-boton tipo="fantasma" :href="route('secretaria.postulaciones')">Limpiar</x-boton>@endif
</form>

@if ($postulaciones->isEmpty())
    <x-estado-vacio titulo="No hay postulaciones pendientes" :texto="$q !== '' ? 'Ninguna coincide con su búsqueda.' : 'Las nuevas postulaciones de su región aparecerán aquí.'" />
@else
    <x-tabla :columnas="['Postulante', 'Universidad', 'Postuló a', 'Contacto', 'Fecha']" descripcion="Postulaciones pendientes de la región">
        @foreach ($postulaciones as $p)
            @php
                $po = $p->postulante;
                $pref = $p->preferencias->sortBy('orden')->values();
                $cancList = ($cancelacionesPorRut ?? collect())->get($po?->rut);
                $cancPr = $cancList && $cancList->isNotEmpty() ? $cancList->first() : null;
            @endphp
            <tr data-href="{{ route('secretaria.postulaciones.ver', $p->id) }}">
                <td>
                    <a href="{{ route('secretaria.postulaciones.ver', $p->id) }}"><strong>{{ $po->nombre }} {{ $po->apellido }}</strong></a>
                    @if ($po->rut)<span class="sec-menor">{{ $po->rut }}</span>@elseif ($p->origen === 'excel')<span class="sec-tag sec-tag-nuevo imp-tag">Datos por completar</span>@endif
                    @if ($po->datos_actualizados_at)<span class="sec-tag sec-tag-otra">Datos actualizados</span>@endif
                    @if ($cancPr)
                        <span class="sec-tag sec-tag-peligro" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;font-weight:700;display:inline-flex;align-items:center;gap:.25rem;margin-top:.25rem;" title="Cancelada el {{ $cancPr->cancelacion_fecha?->format('d-m-Y') }} · Motivo: {{ \App\Services\Practicas::textoMotivoCancelacion($cancPr->cancelacion_motivo) }}">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            Cancelación previa: {{ \App\Services\Practicas::textoMotivoCancelacion($cancPr->cancelacion_motivo) }}
                        </span>
                    @endif
                </td>
                <td>{{ $po->universidad }}<span class="sec-menor">{{ ['quinto' => 'Quinto año', 'egresado' => 'Egresado/a'][$po->anio_carrera] ?? ($po->anio_carrera ?: 'Egresado/a') }}</span></td>
                <td>@if ($pref->isNotEmpty()){{ $pref->first()->consultorio->nombre }}@if ($pref->count() > 1)<span class="sec-menor">+{{ $pref->count() - 1 }} más</span>@endif @else <span class="sec-menor">Sin preferencias</span>@endif</td>
                <td>{{ $po->email }}<span class="sec-menor">{{ $po->telefono }}</span></td>
                <td class="sec-num">{{ $p->fecha_postulacion->format('d-m-Y') }}</td>
            </tr>
        @endforeach
    </x-tabla>
@endif

@php $errImp = $errors->importar->first('archivo'); @endphp
<x-modal id="modal-importar" titulo="Importar postulaciones desde Excel">
    <div class="imp-nota" role="note">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8h.01M11 12h1v5h1M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z"/></svg>
        <p><strong>Para que la importación funcione, el archivo debe seguir el formato de columnas indicado, en este orden:</strong> NombrePostulante | Universidad | NumeroTelefono | fechaPostulacion | Correo | NumeroTelefono. Con estos datos se crean postulaciones pendientes en su región y el resto de la información se completa después.</p>
    </div>

    <div class="imp-ejemplo" tabindex="0" role="region" aria-label="Ejemplo de encabezado y fila">
        <table>
            <caption class="sr-only">Ejemplo de columnas del archivo</caption>
            <thead><tr><th scope="col">NombrePostulante</th><th scope="col">Universidad</th><th scope="col">NumeroTelefono</th><th scope="col">fechaPostulacion</th><th scope="col">Correo</th><th scope="col">NumeroTelefono</th></tr></thead>
            <tbody><tr><td>Ana Rojas Soto</td><td>Universidad de Concepción</td><td>+56911112222</td><td>03-10-2026</td><td>ana@correo.cl</td><td>+56933334444</td></tr></tbody>
        </table>
    </div>

    <ul class="imp-reglas">
        <li>Archivo en formato .xlsx, de hasta 2 MB y 500 filas.</li>
        <li>La primera fila debe contener los encabezados.</li>
        <li>La fecha va como dd-mm-aaaa. Si queda vacía, se usa la fecha de hoy.</li>
        <li>El segundo NumeroTelefono se guarda como teléfono alternativo.</li>
        <li>Las filas con datos inválidos o con un correo que ya tiene una postulación pendiente se omiten, y se le informa cuáles.</li>
    </ul>

    <form id="form-importar" method="POST" enctype="multipart/form-data" action="{{ route('secretaria.postulaciones.importar') }}" class="imp-form" data-sin-validar>
        @csrf
        <div class="campo {{ $errImp ? 'tiene-error' : '' }}">
            <label for="imp-archivo" class="campo-etiqueta">Archivo Excel (.xlsx)<span class="campo-obligatorio" aria-hidden="true">*</span><span class="sr-only"> (obligatorio)</span></label>
            <input id="imp-archivo" name="archivo" type="file" accept=".xlsx" required class="campo-control" aria-describedby="imp-archivo-error"@if ($errImp) aria-invalid="true"@endif>
            <p id="imp-archivo-error" class="campo-error" role="alert">{{ $errImp }}</p>
        </div>
    </form>
    <x-slot:pie><x-boton type="submit" form="form-importar" id="imp-enviar">Importar</x-boton></x-slot:pie>
</x-modal>
<script>
(() => {
    const form = document.getElementById('form-importar'), modal = document.getElementById('modal-importar');
    if (!form) return;
    const btn = document.getElementById('imp-enviar'), campo = document.getElementById('imp-archivo');
    form.addEventListener('submit', (e) => {
        if (!campo.files.length) { e.preventDefault(); campo.setAttribute('aria-invalid', 'true'); document.getElementById('imp-archivo-error').textContent = 'Seleccione un archivo .xlsx.'; campo.closest('.campo').classList.add('tiene-error'); campo.focus(); return; }
        btn.disabled = true; btn.textContent = 'Importando…';
    });
    window.addEventListener('pageshow', (e) => { if (e.persisted) { btn.disabled = false; btn.textContent = 'Importar'; } });
    @if ($errImp) modal.showModal(); campo.focus(); @endif
})();
</script>
@endsection
