<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Resolución</title>
<style>
  @page { margin: 0; }
  body { font-family: 'Times New Roman', Times, serif; color: #111; font-size: 11.5pt; margin: 0; }
  .franja { height: 6mm; background: #0b2e6b; }
  .franja-roja { height: 1.6mm; background: #d52b1e; }
  .pagina { padding: 14mm 25mm 12mm; }
  .salto { page-break-before: always; }
  .logo { width: 36mm; margin-bottom: 4mm; }
  .encabezado { margin-left: 78mm; font-weight: bold; line-height: 1.8; }
  .num { margin-bottom: 4mm; color: #0b2e6b; }
  .materia { text-align: justify; }
  .lugar { margin: 6mm 0 6mm 78mm; font-weight: bold; }
  .rotulo { font-weight: bold; margin-left: 18mm; margin-bottom: 3mm; color: #0b2e6b; }
  p { text-align: justify; line-height: 1.7; text-indent: 22mm; margin: 0 0 4mm; }
  table.conceptos { margin: 8mm 0 10mm; border-collapse: collapse; }
  table.conceptos td { line-height: 2.1; padding: 0; vertical-align: top; }
  .cierre { text-align: center; font-weight: bold; margin: 10mm 0; }
  .firma { margin-top: 16mm; text-align: center; font-weight: bold; line-height: 1.45; }
  .firma img { height: 20mm; display: block; margin: 0 auto 2mm; }
  .dist { margin-top: 16mm; font-size: 11pt; line-height: 1.5; }
</style>
</head>
<body>
  @php
    $de = $femenino ? 'doña' : 'don';
    $fechaRes = $hoy->day.' de '.$hoy->locale('es')->translatedFormat('F').' '.$hoy->year;
    $comuna = $practica->consultorio->comuna->nombre;
  @endphp
  <div class="franja"></div><div class="franja-roja"></div>
  <div class="pagina">
    <img class="logo" src="{{ $logo }}" alt="Corporación de Asistencia Judicial — Biobío">

    <div class="encabezado">
      <div class="num">RESOLUCIÓN N.° {{ $certificado->numero_resolucion }}/{{ $certificado->anio }}</div>
      <div class="materia">APRUEBA PRÁCTICA PROFESIONAL DE POSTULANTE AL TÍTULO DE {{ mb_strtoupper($titulo) }} DE {{ mb_strtoupper($de) }} {{ $nombreLegal }}.</div>
    </div>

    <div class="lugar">{{ $inst['ciudad'] }}, {{ $fechaRes }}.</div>

    <div class="rotulo">VISTOS:</div>
    <p>Lo dispuesto en la Ley N°19.880, que establece las Bases de los Procedimientos Administrativos que rigen los actos de los órganos de la Administración del Estado; en el numeral 5° del artículo 523 del Código Orgánico de Tribunales; en el artículo 2 de la Ley 17.995, que Concede Personalidad Jurídica a los Servicios de Asistencia Jurídica que se indican en las Regiones que se señalan; en la letra i) del artículo 19 de los Estatutos de la Corporación de Asistencia Judicial de la Región del Biobío, aprobados en el Decreto con fuerza de Ley N° 994 del año 1981; en los artículos 24 al 26 del Reglamento de Práctica Profesional de Postulantes al Título de Abogado contenido en el Decreto Supremo N° 265, del Ministerio de Justicia, de 1985; en el Protocolo que regula las condiciones generales para la realización de la Práctica Profesional de los Postulantes al título de Abogado suscrito entre el Ministerio de Justicia y las Corporaciones de Asistencia Judicial el 10 de agosto de 2009; en el Instructivo para la tramitación de expedientes de Juramento de Abogadas y Abogados contenido en el Auto Acordado de la Corte Suprema N°192-2015; el certificado de licenciatura en Ciencias Jurídicas de {{ $de }} <b>{{ $nombreLegal }},</b> emitido por la {{ $postulante->universidad }}.</p>

    <div class="rotulo">CONSIDERANDO:</div>
    <p>Oficio del Director de Acceso a la Justicia de la Institución, de fecha {{ $fechaRes }}, mediante el cual informa que {{ $de }} <b>{{ $nombreLegal }},</b> {{ $femenino ? 'licenciada' : 'licenciado' }} en Ciencias Jurídicas de la {{ $postulante->universidad }}, ha cumplido satisfactoriamente con la práctica profesional dispuesta en el numeral 5° del artículo 523 del Código Orgánico de Tribunales, realizada en la Oficina Jurídica de Atención de la comuna de {{ $comuna }}, entre los días {{ $practica->fecha_inicio->format('d/m/Y') }} al {{ $practica->fecha_termino->format('d/m/Y') }}.</p>
  </div>

  <div class="pagina salto">
    <div class="rotulo">RESUELVO:</div>
    <p><b>1° APRUÉBASE</b> práctica profesional de {{ $de }} <b>{{ $nombreLegal }},</b> cédula de identidad <b>RUT N.º {{ $rut }},</b> con los siguientes conceptos:</p>

    <table class="conceptos">
      @foreach ($criterios as $clave => $etiqueta)
        <tr>
          <td style="width:14mm;">{{ chr(97 + $loop->index) }})</td>
          <td style="width:86mm;">{{ mb_strtoupper($etiqueta) }}</td>
          <td>: {{ \App\Support\Notas::concepto((float) $evaluacion->$clave) }}</td>
        </tr>
      @endforeach
    </table>

    <p><b>2° EXPÍDASE</b> el informe de calificación de la práctica profesional aludida y el certificado de aprobación de la misma, en conformidad a las disposiciones legales y administrativas vigentes.</p>

    <div class="cierre">ANÓTESE, NOTIFÍQUESE Y ARCHÍVESE.</div>

    <div class="firma">
      @if ($firma)<img src="{{ $firma }}" alt="Firma">@endif
      {{ $inst['director_nombre'] }}<br>
      {{ $inst['director_cargo'] }}<br>
      {{ $inst['institucion'] }}
    </div>

    <div class="dist">
      <b style="text-decoration: underline;">DISTRIBUCIÓN:</b><br>
      - Interesado/a.<br>
      - Director acceso a la Justicia.<br>
      - Encargado de sistemas informáticos.<br>
      - Encargada de transparencia activa.<br>
      - Archivo Dirección General.
    </div>
  </div>
</body>
</html>
