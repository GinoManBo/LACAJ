# Vistas del formulario público de postulación

Controlador: `App\Http\Controllers\Postulacion\PostulacionController`. Layout: `layouts.publico`.
Rutas (`routes/portales/postulacion.php`): `postulacion.inicio` GET `/postular`, `postulacion.paso` GET `/postular/paso/{n}`,
`postulacion.guardar` POST `/postular/paso/{n}` (n=1..4), `postulacion.enviar` POST `/postular/enviar`, `postulacion.enviada` GET `/postular/enviada`.

## Reglas de producto (el servidor ya las impone; la vista debe reflejarlas)
- **No se puede saltar pasos**: abrir el paso N sin completar los anteriores redirige al primero incompleto con flash `aviso`.
  El stepper debe mostrar como clicables solo los pasos ≤ `$permitido`.
- Solo los campos obligatorios bloquean; los opcionales quedan libres. Al fallar validación: scroll al primer campo con error y contorno rojo (componente `campo` + JS ya definidos), mensajes bajo cada campo (`$errors`).
- Los valores previos se rellenan desde `$borrador` (`old()` tiene prioridad).
- Asterisco rojo en obligatorios.

## `postulacion.inicio`
CTA "Ingresar postulación" → `route('postulacion.paso', 1)`. Explica en 3-4 líneas qué se necesita (cédula por ambos lados, datos, consultorios). Si hay borrador en sesión, "Continuar postulación".

## `postulacion.paso1` — Carta y términos
Variables: `$n=1, $pasos, $borrador, $permitido`. Texto institucional: carta + 6 términos. Fecha: `now()->translatedFormat('j \d\e F \d\e Y')`, "Concepción".
Form POST `postulacion.guardar` 1: checkbox `acepta` (value 1, obligatorio; error: «Debe aceptar los términos y condiciones para continuar.»). Botón Continuar (deshabilitado visualmente hasta marcar, pero el servidor valida igual).

## `postulacion.paso2` — Datos personales
Variables extra: `$regiones` (id, nombre), `$comunas` (id, region_id, nombre — filtrar el `<select>` de comuna por región en JS; deshabilitado hasta elegir región), `$generos` (clave→etiqueta).
Campos (POST paso 2) — `*` obligatorio: `nombre*`, `apellido*`, `rut*` (formatear al escribir; el servidor valida dígito verificador), `fecha_nacimiento*` (date), `email*`, `telefono*`, `genero*`, `nacionalidad*`,
`discapacidad*` (radio 1/0) y `discapacidad_descripcion` (visible y obligatorio solo si discapacidad=1; tipo, grado y apoyos),
Domicilio: `region_id*`, `comuna_id*`, `direccion*`;
Contacto de emergencia: `emergencia_nombre*`, `emergencia_vinculo*`, `emergencia_telefono*`, `emergencia_email` (opcional);
`antecedentes_salud*` (textarea; ayuda: «Puede escribir "Ninguno"»).
Botones: Volver (paso 1) / Continuar.

## `postulacion.paso3` — Antecedentes educativos y archivos
Variables extra: `$especialidades` (id, nombre), `$archivos` (colección keyBy tipo de `ArchivoStaging`: nombre_original, tamano_bytes; vacía si no hay), `$tiposObligatorios` = [cedula_frontal, cedula_reverso, certificado_egreso], `$tiposOpcionales` = [].
Form `enctype="multipart/form-data"`. Campos: `universidad*`, `especialidad_id*`.
Cédula: `cedula_frontal*` y `cedula_reverso*` — imagen JPG/PNG ≤ 5 MB; dos botones por lado: «Escanear con cámara» (input `accept="image/*" capture="environment"` o getUserMedia con vista previa y captura a File vía DataTransfer) y «Subir archivo». Vista previa de la imagen elegida. Si ya hay `$archivos['cedula_frontal']` mostrar «Archivo cargado: nombre» y NO exigir de nuevo (no lleva `required` en HTML).
Certificado de egreso: `certificado_egreso*` — obligatorio (PDF/JPG/PNG ≤ `config('caj.max_upload_mb')` MB). Acredita la condición de egresado/a de Derecho (se eliminó la opción de alumno regular y el selector de año de carrera).
**No hay validación automática de cédula con IA** (se eliminó del producto).

## `postulacion.paso4` — Consultorio y fecha
Variables extra: `$consultorios` (Collection ordenada por cercanía al domicilio; cada uno: id, nombre, direccion, comuna->nombre, `distancia`), `$masCercanoId` (etiqueta «MÁS CERCANO A SU DOMICILIO»), `$seleccionados` (ids en orden de preferencia), `$feriados` (array 'Y-m-d'), `$hoy`.
Campos: `consultorios[]*` (≥1; el ORDEN en que se marcan es la preferencia 1,2,3… — numerar, permitir quitar; enviar inputs hidden `consultorios[]` en ese orden), buscador por comuna/nombre (JS), `fecha_inicio*` (calendario propio: no permite fines de semana ni `$feriados` ni fechas < `$hoy`; feriados tachados en rojo; valor `Y-m-d`).
Botón «Revisar postulación».

## `postulacion.paso5` — Resumen y envío
Variables: `$datos, $educ, $region, $comuna, $especialidad, $consultoriosElegidos` (Collection de Consultorio en orden), `$fechaInicio`, `$archivos` (keyBy tipo).
Bloques con botón «Editar» (enlace al paso): datos personales, accesibilidad, domicilio, contacto de emergencia, salud, antecedentes educativos, archivos adjuntos (nombres), consultorios (numerados), fecha. Form POST `postulacion.enviar` (solo @csrf) con «Confirmar y enviar». Muestra `$errors` (p. ej. RUT ya con postulación en revisión).

## `postulacion.enviada`
Confirmación: «Postulación enviada. El resultado llegará por correo electrónico en un plazo máximo de 15 días hábiles.» Enlace a inicio.
