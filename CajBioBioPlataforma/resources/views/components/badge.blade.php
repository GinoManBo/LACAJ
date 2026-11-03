@props(['estado', 'texto' => null])
@php
    $etiquetas = [
        'pendiente' => 'Pendiente', 'aceptada' => 'Aceptada', 'rechazada' => 'Rechazada', 'derivada' => 'Derivada',
        'activa' => 'En curso', 'por_iniciar' => 'Por iniciar', 'finalizada' => 'Finalizada', 'cancelada' => 'Cancelada',
        'por_terminar' => 'Por terminar',
        'remitida_biobio' => 'Remitida a Biobío', 'nivelada_biobio' => 'Nivelada en Biobío',
        'rechazada_sancion' => 'Rechazada por Sanción', 'cerrada_historial' => 'Cerrada / Despachada',
        'urgente' => 'Urgente', 'aviso' => 'Aviso', 'informativo' => 'Informativo',
        'muchos' => 'Muchos cupos', 'pocos' => 'Pocos cupos', 'sin' => 'Sin cupos',
        'alta' => 'Muchos cupos', 'media' => 'Cupos medios', 'baja' => 'Pocos cupos',
    ];
    $clave = strtolower((string) $estado);
@endphp
<span {{ $attributes->merge(['class' => 'badge badge-'.$clave]) }}>{{ $slot->isNotEmpty() ? $slot : ($texto ?? ($etiquetas[$clave] ?? ucfirst($clave))) }}</span>
