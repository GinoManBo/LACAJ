@props(['pasos' => [], 'actual' => 1, 'permitido' => null, 'ruta' => 'postulacion.paso'])
<nav aria-label="Progreso de la postulación">
    <ol class="pasos">
        @foreach (array_values($pasos) as $i => $nombre)
            @php
                $n = $i + 1; $esActual = $n === (int) $actual;
                $cls = $n < $actual ? 'paso-hecho' : ($esActual ? 'paso-actual' : '');
                $enlace = $permitido !== null && $n <= (int) $permitido && ! $esActual;
                if ($enlace) $cls .= ' paso-enlace';
            @endphp
            <li class="paso {{ $cls }}" @if ($esActual) aria-current="step" @endif>
                @if ($enlace)<a href="{{ route($ruta, $n) }}" class="paso-vinculo">@endif
                <span class="paso-num">{{ $n < $actual ? '✓' : $n }}</span>
                <span class="paso-nombre">{{ $nombre }}</span>
                @if ($enlace)</a>@endif
            </li>
        @endforeach
    </ol>
</nav>
