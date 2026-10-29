# Vistas del portal Secretaría (para el agente creativo)

Layout: `layouts.portal` (`@extends`, sección `contenido`). Todas las rutas requieren rol `secretaria` y muestran **solo la región de la usuaria** (`auth()->user()->region`); no hay selector de región.
Mensajes flash disponibles: `session('ok')`, `session('credenciales')`, `$errors`. Todos los POST/DELETE llevan `@csrf` (DELETE con `@method('DELETE')`).
Componentes: usar los de `resources/views/components` (boton, campo, tarjeta, badge, modal, tabla, estado-vacio, indicador).
Estados de práctica para `<x-badge>`: `por_iniciar`, `activa`, `por_terminar` (derivado, naranja), `finalizada`, `cancelada`. Disponibilidad de cupos: `muchos` (verde), `pocos` (naranja), `sin` (rojo).
Fechas: son objetos Carbon → `->format('d-m-Y')`. Modelos Eloquent con relaciones ya cargadas (se indican).

## 1. `secretaria.panel` — GET `route('secretaria.panel')`
Variables: `$region` (Region: nombre), `$pendientes` int, `$activas` int, `$porTerminarCount` int,
`$ultimasPostulaciones` (≤5 Postulacion; `->postulante` nombre, apellido, universidad; `fecha_postulacion`),
`$practicasPorTerminar` (≤5 Practica; `->practicante->postulante`, `->consultorio`, `fecha_termino`).
UI: 3 indicadores; dos tarjetas con botón "Ver todas" → `secretaria.postulaciones` / `secretaria.practicas`. Estados vacíos: "Sin postulaciones pendientes", "Ninguna práctica por terminar".

## 2. `secretaria.postulaciones.index` — GET `route('secretaria.postulaciones')`
Variables: `$postulaciones` (Postulacion con `postulante`, `preferencias.consultorio`; campos postulante: nombre, apellido, rut, universidad, anio_carrera, email, telefono; `fecha_postulacion`), `$q` string.
Filtro GET `q` (nombre, apellido, RUT, universidad). Fila clicable → `route('secretaria.postulaciones.ver', $p->id)`. Columna "Postuló a": primer consultorio de `preferencias` (+N más). Vacío: "No hay postulaciones pendientes".
Además debe mostrar un aviso destacado si existe `session('credenciales')` = `['email','password']`: "Cuenta del practicante creada. Contraseña temporal: … (se muestra solo esta vez)".

## 3. `secretaria.postulaciones.ver` — GET `route('secretaria.postulaciones.ver', $id)`
Variables: `$postulacion` (con `postulante.comuna|region|especialidad`, `preferencias.consultorio.comuna`, `documentos`, `notas.autor`, `derivaciones.regionOrigen|regionDestino`), `$postulante`, `$documentosCedula`, `$documentosUniversitarios` (colecciones de Documento: `id`, `tipo`, `nombre_original`, `mime`), `$faltaCedula` bool, `$consultorios` (colección ordenada: primero `postulo_aqui`, luego región propia, luego `otra_region`; cada uno con `nombre`, `direccion`, `region->nombre`, `comuna->nombre`, `capacidad`, `ocupados`, `libres`, `disponibilidad` (muchos|pocos|sin), `postulo_aqui` bool, `otra_region` bool, `abogados` (cada `->id`, `->area`, `->usuario->nombre/apellido`)), `$regionPropiaId`.
Secciones: datos del postulante (contacto, domicilio, educación, accesibilidad/discapacidad, salud, contacto de emergencia: `emergencia_nombre|vinculo|telefono|email`), consultorios postulados en orden (`preferencias` con `orden`), `fecha_inicio_solicitada`, documentos con **Ver** (`route('documentos.ver', $d->id)`, nueva pestaña) y **Descargar** (`route('documentos.descargar', $d->id)`); si `$faltaCedula` advertir "Falta un lado de la cédula: no es posible validar identidad".
Historial de derivaciones (si hay): origen → destino, nota, fecha.
**Comentarios** (`$postulacion->notas`: `texto`, `created_at`, `autor->nombre/apellido`, `autor_usuario_id`): lista + formulario POST `route('secretaria.postulaciones.nota.crear', $postulacion->id)` campo `texto` (obligatorio, máx 2000; error "Escriba el comentario."). Botón eliminar (DELETE `route('secretaria.postulaciones.nota.eliminar', [$postulacion->id, $nota->id])`) solo si `$nota->autor_usuario_id === auth()->id()`.
**Rechazar**: modal con POST `route('secretaria.postulaciones.rechazar', $postulacion->id)`, campo opcional `motivo` (máx 2000). Botón peligro (rojo).
**Aceptar / asignar consultorio**: modal/panel con buscador cliente (nombre, dirección, región), tarjetas de `$consultorios` seleccionables (radio `consultorio_id`, obligatorio, error "Seleccione un consultorio."), etiquetas "POSTULÓ AQUÍ" / "OTRA REGIÓN", badge de disponibilidad. Si el consultorio elegido es de la propia región (`!otra_region`) mostrar select opcional `abogado_id` con los `abogados` de ese consultorio (opción "Sin asignar") y el botón verde/azul **Confirmar ingreso**; si es de otra región ocultar abogado y mostrar botón naranja **Derivar a otra región**. Campo opcional `nota` (máx 2000) "Comentario para la secretaría de destino / registro". Form: POST `route('secretaria.postulaciones.aceptar', $postulacion->id)`. Deshabilitar consultorios con `disponibilidad === 'sin'` de la propia región (el servidor igual valida: error en `consultorio_id`). Errores posibles en `$errors`: `consultorio_id`, `abogado_id`, `estado`.

## 4. `secretaria.practicas.index` — GET `route('secretaria.practicas')`
Variables: `$practicas` (Practica por_iniciar/activa; `->practicante->postulante`, `->consultorio`, `->abogado->usuario` (puede ser null), `fecha_inicio`, `fecha_termino`, `estado_visible`, `estadistica` = `['total','transcurridos','restantes','porcentaje']`), `$q`, `$cuposTotales`, `$cuposDisponibles`, `$motivos` (clave=>etiqueta).
UI: indicadores cupos; búsqueda GET `q`; botón **Exportar a Excel** → `route('secretaria.practicas.exportar', ['q' => $q])`; tabla con acciones por fila: 👁 detalle `route('secretaria.practicas.ver', $p->id)`, 📊 estadísticas `route('secretaria.practicas.estadistica', $p->id)`, ✏️ gestionar → modal de cancelación.
Modal cancelar: POST `route('secretaria.practicas.cancelar', $p->id)`; `motivo` (radio/select obligatorio, valores de `$motivos`; botón deshabilitado hasta elegir; error "Seleccione el motivo de cancelación."), `detalle` opcional (visible si motivo = otro, máx 2000). Tras cancelar llega `session('documento_cancelacion')` (URL del PDF): mostrar enlace "Descargar documento de cancelación".

## 5. `secretaria.practicas.ver` — GET `route('secretaria.practicas.ver', $id)`
Variables: `$practica` (con `practicante.postulante.comuna`, `consultorio.comuna`, `abogado.usuario`, `documentos`; `estadistica`, `estado_visible`), `$motivos`. Ficha: practicante (contacto, discapacidad), consultorio, tutor, periodo, estado, enlace a estadísticas, botón cancelar (mismo modal).

## 6. `secretaria.practicas.estadistica` — GET `route('secretaria.practicas.estadistica', $id)`
Variables: `$practica` (practicante.postulante, consultorio, abogado.usuario), `$estadistica` (`total` días, `transcurridos`, `restantes`, `porcentaje` 0-100). UI: barra de avance, tres cifras, botón "Volver a Prácticas Activas" → `secretaria.practicas`.

## 7. `secretaria.historial.index` — GET `route('secretaria.historial')`
Variables: `$practicas` (finalizadas y canceladas; `estado`, `cancelacion_antes_de_iniciar`, `nota_practica`, `fecha_inicio`, `fecha_termino`, `practicante.postulante`, `consultorio`, `abogado.usuario`), `$q`, `$anio` (string|null), `$anios` (colección de años). Filtros GET `q`, `anio` (select con "Todos"). Etiqueta **NO INICIÓ** si `estado==='cancelada' && cancelacion_antes_de_iniciar`. Fila → `route('secretaria.historial.ver', $p->id)`. Vacío: "No hay prácticas cerradas".

## 8. `secretaria.historial.ver` — GET `route('secretaria.historial.ver', $id)`
Variables: `$practica` (con `practicante.postulante.comuna`, `consultorio`, `abogado.usuario`, `evaluacion`, `calificaciones`, `postulacion.documentos`, `canceladaPor`), `$postulante`, `$estadistica`, `$motivoCancelacion` (string|null), `$evaluacion` (Evaluacion|null: `promedio`, `aprobado`, `observaciones`), `$criterios` (array de `['etiqueta','nota','concepto']`, 7 elementos, vacío si no hay evaluación), `$conceptoFinal` (string|null: SOBRESALIENTE|MUY BUENO|BUENO|SUFICIENTE|DEFICIENTE), `$documentosCedula`, `$documentosUniversitarios`.
Tres pestañas: **Resumen** (contacto, periodo, duración `estadistica['total']` días, consultorio, tutor; si cancelada, aviso rojo con fecha `cancelacion_fecha`, `$motivoCancelacion`, `cancelacion_detalle` y si fue antes o durante; enlace a PDF `route('secretaria.cancelacion.documento', $practica->id)` solo si cancelada), **Documentos** (Ver/Descargar como en postulaciones), **Calificaciones** (`nota_practica` + `$conceptoFinal`, lista `$criterios`, `$evaluacion->aprobado` HA APROBADO / NO HA APROBADO, `observaciones`; si no hay evaluación: "La práctica no se completó: no hay evaluación registrada").

## 9. `secretaria.consultorios.index` — GET `route('secretaria.consultorios')`
Variables: `$consultorios` (Consultorio con `comuna`; añadidos `ocupados`, `libres`, `porcentaje_ocupacion`, `disponibilidad`, además de `nombre`, `direccion`, `capacidad`). Tarjetas con barra de ocupación, badge de disponibilidad y leyenda (muchos/pocos/sin); clic → `route('secretaria.consultorios.ver', $c->id)`. Nota: los cupos se modifican desde el portal Administrador.

## 10. `secretaria.consultorios.ver` — GET `route('secretaria.consultorios.ver', $id)`
Variables: `$consultorio` (mismos campos), `$asignados` (Practica por_iniciar/activa con `practicante.postulante`, `abogado.usuario`, `fecha_termino`, `estado_visible`). Tabla de practicantes asignados (universidad, tutor, estado, fecha de término). Vacío: "Sin practicantes asignados".

## PDF (ya hecho): `resources/views/pdf/cancelacion.blade.php`
GET `route('secretaria.cancelacion.documento', $id)` descarga el PDF de una práctica cancelada.

## Documentos (todos los roles)
`route('documentos.ver', $id)` (inline) y `route('documentos.descargar', $id)`; autorización por fila en servidor (404 si no corresponde).
