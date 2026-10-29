@props(['columnas' => [], 'descripcion' => null])
<div class="tabla-envoltura">
    <table {{ $attributes->merge(['class' => 'tabla']) }}>
        @if ($descripcion)<caption class="sr-only">{{ $descripcion }}</caption>@endif
        @if ($columnas)
            <thead><tr>@foreach ($columnas as $c)<th scope="col">{{ $c }}</th>@endforeach</tr></thead>
        @elseif (isset($cabecera))
            <thead>{{ $cabecera }}</thead>
        @endif
        <tbody>{{ $slot }}</tbody>
    </table>
</div>
