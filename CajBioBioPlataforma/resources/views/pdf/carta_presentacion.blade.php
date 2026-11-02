<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Carta de Presentación — {{ $postulante_nombre_completo }}</title>
<style>
  @page { margin: 15mm 20mm; }
  body { font-family: 'Times New Roman', Times, serif; color: #111; font-size: 11pt; line-height: 1.45; }
  .cabecera-logo { width: 44mm; margin-bottom: 2mm; }
  .direccion-titulo { font-weight: bold; font-size: 10pt; letter-spacing: 1px; color: #111; margin-bottom: 5mm; }
  .fecha-lugar { text-align: right; font-weight: bold; margin-bottom: 6mm; }
  .bloque-encabezado { margin-bottom: 6mm; font-size: 10.5pt; line-height: 1.35; }
  .bloque-fila { display: table; width: 100%; margin-bottom: 3.5mm; }
  .etiqueta-de-a { display: table-cell; width: 14mm; font-weight: bold; vertical-align: top; }
  .cuerpo-de-a { display: table-cell; vertical-align: top; font-weight: bold; }
  .cuerpo-de-a div { font-weight: bold; }
  .cuerpo-de-a .institucion { font-weight: bold; color: #222; }
  p { text-align: justify; text-indent: 10mm; margin: 0 0 4mm; line-height: 1.5; }
  p.registro { text-indent: 14mm; font-weight: normal; margin: 4mm 0; }
  .firma { margin-top: 14mm; text-align: center; font-weight: bold; line-height: 1.35; }
  .firma img { height: 18mm; display: block; margin: 0 auto 2mm; }
  .cc { margin-top: 12mm; font-size: 9.5pt; line-height: 1.4; color: #333; }
</style>
</head>
<body>
  @if ($logo)
    <img class="cabecera-logo" src="{{ $logo }}" alt="Corporación de Asistencia Judicial — Biobío">
  @endif

  <div class="direccion-titulo">DIRECCIÓN DE ACCESO A LA JUSTICIA</div>

  <div class="fecha-lugar">{{ $ciudad }}, {{ $fecha }}.-</div>

  <div class="bloque-encabezado">
    <div class="bloque-fila">
      <div class="etiqueta-de-a">DE :</div>
      <div class="cuerpo-de-a">
        <div>{{ mb_strtoupper($emisor_nombre) }}</div>
        <div>{{ mb_strtoupper($emisor_cargo) }}</div>
        <div class="institucion">CORPORACIÓN DE ASISTENCIA JUDICIAL REGIÓN DEL BIOBÍO</div>
      </div>
    </div>

    <div class="bloque-fila">
      <div class="etiqueta-de-a">A :</div>
      <div class="cuerpo-de-a">
        <div>{{ mb_strtoupper($abogado_jefe_nombre) }}</div>
        <div>ABOGADO JEFE CONSULTORIO JURÍDICO {{ mb_strtoupper($consultorio_nombre) }}</div>
        <div class="institucion">CORPORACIÓN DE ASISTENCIA JUDICIAL REGIÓN DEL BIOBÍO</div>
      </div>
    </div>
  </div>

  <p>Junto con saludarle, vengo en informar a Ud. que {{ $postulante_tratamiento }}, <b>{{ mb_strtoupper($postulante_nombre_completo) }}</b>, CI <b>{{ $postulante_rut }}</b>, Licenciado/a de la <b>{{ $postulante_universidad }}</b>, correo electrónico: <b>{{ $postulante_email }}</b> celular: <b>{{ $postulante_telefono ?: 'Sin registrar' }}</b>, con Domicilio en <b>{{ $postulante_domicilio }}</b>, inició su práctica profesional en el Consultorio jurídico de <b>{{ mb_strtoupper($consultorio_nombre) }}</b>, con fecha <b>{{ $fecha_inicio }}</b>.-</p>

  <p class="registro">El número de Registro en nuestros Archivos es el <b>N.º {{ $numero_registro }}</b>.-</p>

  <p>Por lo anterior, solicito se le informe el funcionamiento administrativo del mismo y se acuerde el horario en que deberá asistir, así como toda la información necesaria de la práctica profesional a realizar y dar cumplimiento a las obligaciones señaladas en el Reglamento de Práctica Profesional, Título IV, artículos 9 y siguientes.</p>

  <p>Además, conforme al Reglamento de Práctica Profesional el/la postulante debe efectuar un alegato ante la I. Corte de Apelaciones y para tal objeto será citado oportunamente por la Oficina de Segunda Instancia. Por lo anterior, resulta fundamental que el correo electrónico y el teléfono aquí registrados sean válidos, estar atento/a la citación e informar cualquier cambio, ya que de no tener respuesta de su parte es muy posible que esta práctica profesional se alargue para cumplir este requisito.</p>

  <p style="text-indent: 0; margin-top: 6mm;">Saluda atentamente a Ud.</p>

  <div class="firma">
    @if ($firma)
      <img src="{{ $firma }}" alt="Firma">
    @endif
    <div>{{ mb_strtoupper($emisor_nombre) }}</div>
    <div>{{ mb_strtoupper($emisor_cargo) }}</div>
    <div>CAJ REGIÓN DEL BIOBÍO</div>
  </div>

  <div class="cc">
    CC. - Abogado Jefe Provincial.<br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Abogado Jefe Segunda Instancia {{ $ciudad_segunda_instancia }}.
  </div>
</body>
</html>
