# Vistas — Portal Gobernador Regional (`resources/views/gobernador/…`)

Layout `layouts.portal`; menú: `gobernador.inicio` (Cola de autorizaciones) y `gobernador.firma` (Firma electrónica). Flash `ok`; errores en `$errors` (clave `practica`, `cantidad`, `practicas`, `firma`).

## gobernador/inicio.blade.php — `gobernador.inicio` (GET /gobernador)
Variables: `$pendientes` (colección de `Practica`; relaciones `practicante.postulante` (nombre, apellido, rut), `practicante.usuario.email`, `consultorio.nombre` + `consultorio.region.nombre`, `abogado.usuario` (nombre, apellido; puede ser null), `evaluacion` (`promedio`, `aprobado`, `created_at`), `fecha_inicio`, `fecha_termino`, `estado`), `$tieneFirma` (bool), `$firmaActualizadaAt` (Carbon|null).
Contenido:
- Aviso si `!$tieneFirma`: "Aún no sube su firma electrónica" con enlace a `gobernador.firma` (los PDF saldrán sin firma).
- Indicador "Pendientes de autorización" = `$pendientes->count()`.
- **Acciones masivas** (tres formularios POST):
  - Autorizar N: `POST gobernador.autorizar.lote`, campo `cantidad`* (entero 1–1000; errores "Indique cuántas…", "Ingrese al menos 1.", "El máximo por lote es 1000.").
  - Autorizar todas: `POST gobernador.autorizar.todo` (sin campos) — confirmar en `<x-modal>` mostrando el total.
  - Autorizar seleccionadas: `POST gobernador.autorizar.seleccion` con `practicas[]` (ids de las filas marcadas con checkbox; "Seleccionar todo" en cabecera). Error `practicas`: "Seleccione al menos una práctica."
- **Tabla/cola** (ordenada de más antigua a más reciente): checkbox, practicante (nombre + RUT), consultorio/región, abogado tutor, promedio de la evaluación con concepto (≥6,5 SOBRESALIENTE, ≥5,5 MUY BUENO, ≥4,5 BUENO, ≥4,0 SUFICIENTE), fecha de evaluación, botón **Autorizar** por fila (`POST gobernador.autorizar` con el id de práctica en la URL: `route('gobernador.autorizar', $p->id)`).
- Resultado de acciones masivas llega en flash `ok`: "Se autorizaron X de Y práctica(s)." Errores por fila en `$errors->first('practica')`.
- Vacío: "No hay prácticas pendientes de autorización."

## gobernador/firma.blade.php — `gobernador.firma` (GET /gobernador/firma)
Variables: `$tieneFirma` (bool), `$actualizadaAt` (Carbon|null).
- Vista previa si `$tieneFirma`: `<img src="{{ route('gobernador.firma.imagen') }}">` (la imagen solo la sirve al propio gobernador; sobre fondo blanco/cuadriculado) + "Actualizada el …". Si no hay, estado vacío.
- Form `POST gobernador.firma.subir` con `enctype="multipart/form-data"`, campo `firma`* (archivo PNG o JPG, máx. 2 MB; recomendar fondo transparente PNG). Errores: "Seleccione la imagen de su firma.", "El archivo debe ser una imagen PNG o JPG.", "Formato no permitido: use PNG o JPG.", "La imagen no puede superar los 2 MB." Previsualización cliente opcional antes de enviar. Éxito: flash `ok` "Firma actualizada."
