@props(['estado', 'texto' => null])
@php
    $etiquetas = [
        'pendiente' => 'Pendiente', 'aceptada' => 'Aceptada', 'rechazada' => 'Rechazada', 'derivada' => 'Derivada',
        'activa' => 'En curso', 'por_iniciar' => 'Por iniciar', 'finalizada' => 'Finalizada', 'cancelada' => 'Cancelada',
        'urgente' => 'Urgente', 'aviso' => 'Aviso', 'informativo' => 'Informativo',
        'muchos' => 'Muchos cupos', 'pocos' => 'Pocos cupos', 'sin' => 'Sin cupos',
    ];
    $clave = strtolower((string) $estado);
@endphp
<span {{ $attributes->merge(['class' => 'badge badge-'.$clave]) }}>{{ $slot->isNotEmpty() ? $slot : ($texto ?? ($etiquetas[$clave] ?? ucfirst($clave))) }}</span>
