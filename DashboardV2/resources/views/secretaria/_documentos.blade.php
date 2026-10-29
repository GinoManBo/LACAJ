@php
    $nombres = [
        'cedula_frontal' => 'Cédula de identidad, frente', 'cedula_reverso' => 'Cédula de identidad, reverso',
        'alumno_regular' => 'Certificado de alumno regular', 'concentracion_notas' => 'Concentración de notas',
        'certificado_egreso' => 'Certificado de egreso', 'curriculum' => 'Currículum vitae', 'otro' => 'Otro documento',
    ];
    $tiposCedula = collect($documentosCedula)->pluck('tipo');
    $lados = [];
    if (! $tiposCedula->contains('cedula_frontal')) $lados[] = 'el frente';
    if (! $tiposCedula->contains('cedula_reverso')) $lados[] = 'el reverso';
@endphp
<div class="sec-docs">
    @if ($faltaCedula)
        <div class="alerta alerta-aviso" role="alert">
            @include('secretaria._icono', ['n' => 'alerta'])
            <div><strong>Falta un lado de la cédula: no es posible validar identidad.</strong>
                @if ($lados)<div>No se recibió {{ implode(' ni ', $lados) }}.</div>@endif</div>
        </div>
    @endif
    @foreach ([['Cédula de identidad', $documentosCedula, 'No hay documentos de cédula.'], ['Documentos universitarios', $documentosUniversitarios, 'No hay documentos universitarios.']] as [$titulo, $lista, $vacio])
        <div>
            <h3>{{ $titulo }}</h3>
            @forelse ($lista as $d)
                <div class="sec-doc">
                    <div class="sec-doc-nombre"><strong>{{ $nombres[$d->tipo] ?? ucfirst(str_replace('_', ' ', $d->tipo)) }}</strong><span>{{ $d->nombre_original }}</span></div>
                    <div class="sec-doc-botones">
                        <x-boton tipo="secundario" tam="sm" :href="route('documentos.ver', $d->id)" target="_blank" rel="noopener">@include('secretaria._icono', ['n' => 'ojo']) Ver</x-boton>
                        <x-boton tipo="fantasma" tam="sm" :href="route('documentos.descargar', $d->id)">@include('secretaria._icono', ['n' => 'descargar']) Descargar</x-boton>
                    </div>
                </div>
            @empty
                <p class="sec-ausente">{{ $vacio }}</p>
            @endforelse
        </div>
    @endforeach
</div>
