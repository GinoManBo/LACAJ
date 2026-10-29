# Vistas del portal Abogado Tutor

Layout: `layouts.portal`. Rol `abogado`. Rutas en `routes/portales/tutor.php`. Estados de práctica con `<x-badge>`: `por_iniciar`, `activa`, `por_terminar` (derivado: activa y faltan ≤30 días), `finalizada`, `cancelada`.

## `tutor.inicio` — GET `route('tutor.inicio')`
Encabezado + "Mis practicantes".

Variables:
- `usuario` (nombre, apellido, email), `abogado` (`area`: civil|penal|laboral|familia), `consultorios` (colección: `nombre`, `direccion`)
- `activos` int — practicantes con práctica activa
- `noLeidas` int — notificaciones sin leer (enlace a `route('notificaciones.index')`)
- `practicas` colección de Practica: `id`, `fecha_inicio`, `fecha_termino` (Carbon), `estado`, `estado_mostrado` (usar este para el badge), `practicante.postulante` (`nombre_completo`, `universidad`, `email`, `telefono`), `consultorio.nombre`, `evaluacion` (null si no evaluada)
- `filtros` `['q' => ?string, 'estado' => ?string]`, `estadosFiltro` lista de valores válidos

Formulario de búsqueda: GET `route('tutor.inicio')`, campos `q` (texto: nombre/universidad, máx 100) y `estado` (select con `estadosFiltro`, opcional); botón X que limpia (enlace sin query).
Cada fila enlaza a `route('tutor.practicantes.ver', $practica->id)`. Alerta visible en filas con `estado_mostrado == 'por_terminar'`.
Estado vacío: "Aún no tiene practicantes asignados" (sin filtros) / "Sin resultados para su búsqueda" (con filtros).

## `tutor.practicante` — GET `route('tutor.practicantes.ver', $practica)`
Ficha + informe + encuesta de desempeño. 404 si la práctica no es del tutor.

Variables:
- `practica` (`id`, `fecha_inicio`, `fecha_termino`, `estado`, `nota_practica`, `consultorio.nombre/direccion`, `calificaciones`)
- `postulante` (`nombre_completo`, `rut`, `email`, `telefono`, `universidad`, `discapacidad` bool, `discapacidad_descripcion`)
- `estadoMostrado` string, `porTerminar` bool, `diasRestantes` int (puede ser negativo)
- `informe` Documento|null (`nombre_original`, `tamano_bytes`, `created_at`) → botón "Ver informe" `route('tutor.informe.descargar', $practica->id)`
- `bloqueoEvaluacion` string|null — si no es null, la encuesta se muestra deshabilitada con ese texto exacto (sin informe: explica que se habilita al recibirlo)
- `evaluacion` Evaluacion|null (7 criterios, `promedio`, `aprobado`, `observaciones`), `concepto` string|null (SOBRESALIENTE|MUY BUENO|BUENO|SUFICIENTE|DEFICIENTE)
- `criterios` `['conocimiento_juridico' => 'Conocimiento y criterio jurídico', ...]` (clave => etiqueta, 7 ítems)

Alerta "Proximidad al cierre de práctica" cuando `porTerminar`: recordar informe de cierre y encuesta final.

### Formulario Encuesta — POST `route('tutor.evaluacion.guardar', $practica->id)` (solo si `bloqueoEvaluacion === null`)
- Un campo por cada clave de `criterios`, nombre = clave; numérico 1,0–7,0 paso 0,1 (`<input type="number" step="0.1" min="1" max="7">`; el servidor también acepta coma). **Todos obligatorios**; el botón "Enviar evaluación" se habilita solo con los 7 completos.
- `observaciones` textarea opcional, máx 3000 ("comentarios generales").
- Errores (`$errors->first(clave)`): "Califique este criterio.", "La nota debe estar entre 1,0 y 7,0.", "Use un decimal (por ejemplo 5,5)."; error general `$errors->first('evaluacion')` (informe faltante, ya evaluada).
- Éxito: redirige a la misma vista con flash `ok` ("Evaluación enviada: HA APROBADO." / "NO HA APROBADO.").
- Con `evaluacion` presente: mostrar resultado de solo lectura (promedio, concepto, badge HA APROBADO / NO HA APROBADO con verde/rojo), sin formulario. Una sola evaluación por práctica.
