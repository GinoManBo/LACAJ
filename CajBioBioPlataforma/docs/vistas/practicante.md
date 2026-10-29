# Vistas del portal Practicante

Layout: `layouts.portal`. Rol `practicante`. Rutas en `routes/portales/practicante.php`.

## `practicante.inicio` — GET `route('practicante.inicio')`
Una sola página con 4 bloques: detalle, calificaciones, informe, documentación.

Si `practica` es `null` (aún sin práctica asignada): solo estado vacío "Todavía no tiene una práctica asignada". Las demás variables no existen.

Variables (con práctica):
- `practica`: `fecha_inicio`, `fecha_termino`, `estado`, `consultorio.nombre/direccion`, `evaluacion` (null|obj)
- `estadoMostrado` (`por_iniciar|activa|por_terminar|finalizada|cancelada`) → badge arriba a la derecha
- `postulante.nombre_completo`; `tutor` Usuario|null (`nombre_completo`, `email`) — si null: "Abogado tutor por asignar"
- `calificaciones` colección de 3: `['instancia' => 1|2|3, 'obligatoria' => bool, 'nota' => float|null]`; `nota === null` ⇒ "Sin nota" (1ª: "Pendiente — obligatoria")
- `notaPractica` float|null, `notaPromedio` float|null (escala 1,0–7,0, un/dos decimales con coma)
- `conceptos` colección `['criterio','concepto']`|null — solo tras evaluación (7 filas)
- `informe` Documento|null (`nombre_original`, `created_at`), `puedeSubirInforme` bool
- `documentos` lista de 2: `['clave','titulo','detalle','ruta','bloqueo']`. `bloqueo` null ⇒ botón "Descargar" (enlace a `ruta`, descarga PDF); string ⇒ tarjeta **Bloqueado** mostrando ese texto exacto como motivo.

### Informe de práctica — POST `route('practicante.informe.subir')` (multipart)
Solo si `puedeSubirInforme`. Campo `informe`: archivo obligatorio PDF/DOC/DOCX, máx `config('caj.max_upload_mb')` MB. Errores en `$errors->first('informe')`: "Seleccione el archivo del informe.", "El informe debe ser PDF o Word (.doc, .docx).", "El archivo supera el tamaño máximo.", y "La práctica ya fue evaluada o cerrada…". Subir de nuevo reemplaza el anterior. Éxito: flash `ok`. Si hay `informe`: mostrar nombre, fecha y enlace `route('practicante.informe.descargar')`. Texto de apoyo: subir el informe habilita la evaluación de su tutor.

### Documentos
GET `practicante.certificado` y `practicante.resolucion` devuelven PDF (descarga). Si están bloqueados responden 403 (la UI no debe ofrecer el enlace).
