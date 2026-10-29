<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Cancelación de práctica profesional</title>
<style>
    @page { margin: 0; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11.5px; color: #1b2433; margin: 0; }
    .franja { height: 10px; background: #0b2e6b; }
    .franja-roja { height: 4px; background: #d52b1e; }
    .cuerpo { padding: 34px 48px; }
    .inst { color: #0b2e6b; font-size: 10px; letter-spacing: 1.2px; text-transform: uppercase; font-weight: bold; }
    h1 { color: #0b2e6b; font-size: 19px; margin: 6px 0 2px; }
    .folio { color: #6b7385; font-size: 10px; }
    h2 { color: #0b2e6b; font-size: 12px; border-bottom: 2px solid #0b2e6b; padding-bottom: 3px; margin: 22px 0 8px; text-transform: uppercase; letter-spacing: .6px; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 5px 0; vertical-align: top; border-bottom: 1px solid #e3e7ef; }
    td.k { width: 32%; color: #6b7385; }
    .aviso { border-left: 4px solid #d52b1e; background: #fdf1f0; padding: 10px 14px; margin-top: 18px; }
    .firma { margin-top: 70px; width: 45%; border-top: 1px solid #1b2433; padding-top: 5px; text-align: center; }
    .pie { position: fixed; bottom: 0; left: 0; right: 0; }
    .pie p { text-align: center; color: #6b7385; font-size: 9px; margin: 0 0 8px; }
</style>
</head>
<body>
<div class="franja"></div><div class="franja-roja"></div>
<div class="cuerpo">
    <div class="inst">Corporación de Asistencia Judicial · Región del Biobío</div>
    <h1>Constancia de cancelación de práctica profesional</h1>
    <div class="folio">Práctica N° {{ $practica->id }} · Emitido el {{ now()->format('d-m-Y H:i') }}</div>

    <h2>Practicante</h2>
    <table>
        <tr><td class="k">Nombre</td><td>{{ $postulante->nombre }} {{ $postulante->apellido }}</td></tr>
        <tr><td class="k">RUT</td><td>{{ $postulante->rut }}</td></tr>
        <tr><td class="k">Universidad</td><td>{{ $postulante->universidad }}</td></tr>
        <tr><td class="k">Correo</td><td>{{ $postulante->email }}</td></tr>
    </table>

    <h2>Práctica</h2>
    <table>
        <tr><td class="k">Consultorio</td><td>{{ $practica->consultorio->nombre }} ({{ $practica->consultorio->region->nombre }})</td></tr>
        <tr><td class="k">Abogado tutor</td><td>{{ $practica->abogado ? $practica->abogado->usuario->nombre.' '.$practica->abogado->usuario->apellido : 'Sin asignar' }}</td></tr>
        <tr><td class="k">Periodo programado</td><td>{{ $practica->fecha_inicio->format('d-m-Y') }} al {{ $practica->fecha_termino->format('d-m-Y') }}</td></tr>
    </table>

    <h2>Cancelación</h2>
    <table>
        <tr><td class="k">Fecha</td><td>{{ $practica->cancelacion_fecha?->format('d-m-Y') }}</td></tr>
        <tr><td class="k">Motivo</td><td>{{ $motivo }}</td></tr>
        <tr><td class="k">Momento</td><td>{{ $practica->cancelacion_antes_de_iniciar ? 'Antes de iniciar la práctica' : 'Durante el desarrollo de la práctica' }}</td></tr>
        <tr><td class="k">Registrada por</td><td>{{ $practica->canceladaPor?->nombre }} {{ $practica->canceladaPor?->apellido }}</td></tr>
    </table>

    @if ($practica->cancelacion_detalle)
        <div class="aviso"><strong>Detalle:</strong> {{ $practica->cancelacion_detalle }}</div>
    @endif

    <div class="firma">{{ $emitidoPor->nombre }} {{ $emitidoPor->apellido }}<br>Secretaría regional</div>
</div>
<div class="pie"><p>Documento generado por el Sistema de Gestión de Practicantes — CAJ Biobío</p><div class="franja-roja"></div><div class="franja"></div></div>
</body>
</html>
