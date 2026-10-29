# Vistas — Portal Administrador (`resources/views/admin/…`)

Layout: `layouts.portal` (menú ya apunta a `admin.inicio`; agregar enlaces a `admin.practicantes.index`, `admin.capacidad.index`, `admin.usuarios.index`).
Todas las rutas exigen rol administrador; el admin ve **todas las regiones**. Flash disponibles: `ok` (éxito), errores de validación en `$errors`, y `password_temporal` = `['email' => str, 'password' => str]` (mostrar UNA vez, bien destacado, con botón copiar y aviso "No se volverá a mostrar").
Campos con error: usar `<x-campo>` (scroll + contorno rojo). Estados con `<x-badge>`.

## admin/inicio.blade.php — `admin.inicio` (GET /admin)
- `$indicadores`: `practicas_activas`, `postulaciones_pendientes`, `ocupacion_pct` (0-100), `practicantes_registrados` → 4 `<x-indicador>`.
- `$sistema`: `version`, `usuarios`, `usuarios_activos`, `consultorios`, `cupos_totales` → tarjeta "Información del sistema".
- Accesos rápidos a Gestión de practicantes, Capacidad, Usuarios.

## admin/practicantes/index.blade.php — `admin.practicantes.index` (GET /admin/practicantes?q=)
- `$practicas`: paginador de `Practica` con relaciones: `practicante.postulante` (nombre, apellido, universidad, email), `consultorio` (nombre, `region.nombre`), `abogado.usuario` (nombre, apellido; puede ser null), `estado`, `fecha_inicio`, `fecha_termino` (Carbon). `$q`: búsqueda actual.
- Form GET `q` (nombre, universidad, sede o abogado). Tarjeta/fila por práctica → enlace a `admin.practicantes.ver`. Badge de estado. `{{ $practicas->links() }}`.
- Vacío: "No hay practicantes que coincidan".

## admin/practicantes/ficha.blade.php — `admin.practicantes.ver` (GET /admin/practicantes/{practica})
Variables: `$practica` (+`practicante.postulante`, `practicante.usuario`, `consultorio.region`, `abogado.usuario`, `traslados.consultorioOrigen/consultorioDestino`), `$abogados` (colección `Abogado` con `usuario`; `id`, `area`), `$sedes` (`Consultorio` con `region`, atributo `libres`, `capacidad`, `direccion`), `$estados` (`por_iniciar|activa|finalizada|cancelada`), `$motivosCancelacion` (`conducta|decision|error_registro|otro` → etiquetas: Conducta del practicante / Decisión del practicante / Error de registro / Otro motivo).
Tres bloques (el pie de la ficha indica "Se trasladará a la sede: …" al elegir sede):
1. **Guardar cambios** — `PUT admin.practicantes.actualizar` (@method('PUT')). Campos: `nombre`*, `apellido`*, `universidad`*, `email`* (único entre usuarios), `telefono`* (regex +56…), `fecha_inicio`*, `fecha_termino`* (≥ inicio, mensaje "La fecha de término no puede ser anterior al inicio."), `abogado_id` (select, opcional, vacío = sin tutor), `discapacidad`* (0/1) y `discapacidad_descripcion` (requerida si discapacidad=1), `estado`* (select de `$estados`). Nota UI: elegir "cancelada" aquí da error — se cancela con el bloque 3; cambiar una cancelada a activa/por_iniciar la **reactiva** (falla si la sede no tiene cupos: error en `estado`). Éxito: flash `ok` "Cambios guardados correctamente."
2. **Trasladar de sede** — `POST admin.practicantes.trasladar`. Campos: `consultorio_id`* (buscador + tarjetas con dirección, región y disponibilidad de cupos: libres/capacidad → badge muchos/pocos/sin), `abogado_id` (tutor en la nueva sede, opcional), `motivo` (texto, opcional). Errores en `consultorio_id` ("La sede de destino no tiene cupos libres."). Solo si estado ∈ por_iniciar|activa (si no, deshabilitar y explicar).
3. **Cancelar práctica** — `POST admin.practicantes.cancelar`. `motivo`* (radio de `$motivosCancelacion`), `detalle` (obligatorio si motivo=otro). Botón peligro (rojo) deshabilitado hasta elegir motivo; confirmar en `<x-modal>`. Solo si estado ∈ por_iniciar|activa.
Además: tarjeta de datos de solo lectura (RUT en `practicante.postulante.rut`, región, fechas) y **historial de traslados** (`$practica->traslados`: `fecha`, `consultorioOrigen.nombre` → `consultorioDestino.nombre`, `motivo`). Si cancelada: aviso con `cancelacion_fecha`, `cancelacion_motivo`, `cancelacion_detalle`, y etiqueta NO INICIÓ si `cancelacion_antes_de_iniciar`.

## admin/capacidad/index.blade.php — `admin.capacidad.index` (GET /admin/capacidad)
- `$sedes`: `Consultorio` (con `region.nombre`) + atributos `ocupados_n`, `libres_n`, `capacidad`, `direccion`. Tarjeta por sede: barra de ocupación (ocupados/capacidad), badge de disponibilidad (libres ≥ mitad = muchos; < mitad = pocos; 0 = sin), enlace a `admin.capacidad.ver`.

## admin/capacidad/detalle.blade.php — `admin.capacidad.ver` (GET /admin/capacidad/{consultorio})
- `$sede` (`region`, `comuna`, `capacidad`), `$ocupados`, `$libres`, `$asignados` (Practica con `practicante.postulante`, `abogado.usuario`, `estado`, `fecha_termino`).
- Controles **+** / **−**: dos `<form method=POST action=admin.capacidad.ajustar>` con `accion=mas` / `accion=menos` (también acepta `capacidad=N`). Deshabilitar "−" cuando `$libres == 0` (capacidad = ocupados). Error en `capacidad`: "La capacidad no puede ser menor que los practicantes asignados (N). Traslade primero a los practicantes." Éxito: `ok` "Se agregaron N cupo(s)." / "Se retiraron N cupo(s)."
- Lista de asignados (vacío: "Sin practicantes asignados").

## admin/usuarios/index.blade.php — `admin.usuarios.index` (GET /admin/usuarios?rol=&estado=&q=)
- `$usuarios` paginador de `Usuario` (`nombre`, `apellido`, `email`, `rut` nullable, `rol`, `activo`, `ultimo_acceso` nullable → "Sin ingresos", `region.nombre`, `abogado.area`), `$filtros` (array con rol/estado/q), `$roles` (`administrador|secretaria|abogado|gobernador`).
- Filtros GET: chips por rol, select `estado` (`activo|inactivo`), búsqueda `q`. Botón "Nuevo usuario" → `admin.usuarios.crear`. Avatar con iniciales; fila atenuada si inactivo.
- Por fila: ✏️ editar (`admin.usuarios.editar`), **Desactivar** (`POST admin.usuarios.desactivar`) / **Activar** (`POST admin.usuarios.activar`) — confirmar en modal ("Pierde acceso; su historial se conserva"); **Restablecer contraseña** (`POST admin.usuarios.resetear`). No existe borrar. Los errores de desactivar propio llegan en `$errors->first('activo')`.
- Mostrar el flash `password_temporal` en un recuadro destacado.

## admin/usuarios/form.blade.php — crear y editar
Rutas: crear → GET `admin.usuarios.crear`, POST `admin.usuarios.guardar`; editar → GET `admin.usuarios.editar` (`$usuario`), `PUT admin.usuarios.actualizar` (@method('PUT')).
Variables: `$usuario` (`Usuario`, vacío al crear, `rol` por defecto secretaria; al editar trae `abogado.consultorios`), `$roles`, `$areas` (`civil|penal|laboral|familia`), `$regiones` (`id`, `nombre`; solo operativas), `$consultorios` (`id`, `nombre`).
Campos: `nombre`*, `apellido`*, `email`* (único; "Ya existe un usuario con ese correo."), `rut` (opcional; RUT chileno válido y único; ayuda: "Es la llave de ingreso con ClaveÚnica"; errores "El RUT no es válido…" / "Ya existe un usuario con ese RUT."), `rol`* (select), `region_id` (visible y obligatorio solo si rol=secretaria: "La región es obligatoria para el rol Secretaria."), `area` (visible y obligatoria solo si rol=abogado: "El área es obligatoria para el rol Abogado tutor.") y `consultorios[]` (checkboxes, solo abogado, opcional). Mostrar/ocultar según rol con JS vanilla pero el servidor valida igual. Al crear se genera contraseña temporal (se muestra una vez tras guardar, en el listado). Al editar no se cambia la contraseña aquí; si el usuario edita su propio registro, el rol queda bloqueado (error en `rol` si lo intenta).
