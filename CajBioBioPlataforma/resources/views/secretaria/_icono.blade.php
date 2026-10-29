@php
    $trazos = [
        'ojo' => 'M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12zM12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6z',
        'grafico' => 'M4 20V10M10 20V4M16 20v-7M22 20H2',
        'cancelar' => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM5.6 5.6l12.8 12.8',
        'descargar' => 'M12 3v12m0 0l-4-4m4 4l4-4M4 20h16',
        'copiar' => 'M9 9h10a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V10a1 1 0 0 1 1-1zM5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1',
        'buscar' => 'M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14zM21 21l-4.3-4.3',
        'excel' => 'M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8zM14 3v5h5M9 13l4 5M13 13l-4 5',
        'volver' => 'M19 12H5m0 0l6-6m-6 6l6 6',
        'alerta' => 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z',
        'check' => 'M5 12l5 5L20 7',
    ];
@endphp
<svg class="ico" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $trazos[$n] ?? '' }}"/></svg>
