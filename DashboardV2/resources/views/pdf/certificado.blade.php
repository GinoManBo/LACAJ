<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Certificado Corte Suprema</title>
<style>
  @page { margin: 0; }
  body { font-family: 'Times New Roman', Times, serif; color: #111; font-size: 12.5pt; margin: 0; }
  .franja { height: 6mm; background: #0b2e6b; }
  .franja-roja { height: 1.6mm; background: #d52b1e; }
  .pagina { padding: 18mm 25mm 14mm; }
  .logo { width: 42mm; margin-bottom: 14mm; }
  .titulo { text-align: center; font-weight: bold; font-size: 15pt; letter-spacing: 6px; color: #0b2e6b; margin-bottom: 12mm; }
  p { text-align: justify; line-height: 2; text-indent: 12mm; margin: 0 0 6mm; }
  .firma { margin-top: 18mm; text-align: center; font-weight: bold; line-height: 1.45; }
  .firma img { height: 20mm; display: block; margin: 0 auto 2mm; }
  .cc { margin-top: 22mm; font-size: 11pt; line-height: 1.4; }
</style>
</head>
<body>
  <div class="franja"></div><div class="franja-roja"></div>
  <div class="pagina">
    <img class="logo" src="{{ $logo }}" alt="Corporación de Asistencia Judicial — Biobío">

    <div class="titulo">C E R T I F I C A D O</div>

    <p><b>{{ $inst['director_nombre'] }}</b>, {{ ucfirst(mb_strtolower($inst['director_cargo'])) }} de la Corporación de Asistencia Judicial de la Región del Biobío, CERTIFICA que {{ $femenino ? 'la postulante, doña' : 'el postulante, don' }} <b>{{ $nombreLegal }}, RUT N.º {{ $rut }}, HA APROBADO,</b> la práctica profesional reglamentaria para optar al Título de {{ $titulo }}.</p>

    <p>Este certificado se otorga para el único efecto de ser presentado ante la Excelentísima Corte Suprema de Justicia al momento de apertura de expediente de titulación, según Instructivo sobre Juramento de Abogados.</p>

    <p style="text-indent:0; margin-top:10mm;">{{ $inst['ciudad'] }}, {{ $hoy->day }} de {{ ucfirst($hoy->locale('es')->translatedFormat('F')) }} de {{ $hoy->year }}.</p>

    <div class="firma">
      @if ($firma)<img src="{{ $firma }}" alt="Firma">@endif
      {{ $inst['director_nombre'] }}<br>
      {{ $inst['director_cargo'] }}<br>
      {{ $inst['institucion'] }}
    </div>

    <div class="cc">c.c.-<br>-Dirección de Acceso a la Justicia.</div>
  </div>
</body>
</html>
