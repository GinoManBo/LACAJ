# Notificaciones (todos los roles)

Rutas (usuario autenticado; cada uno solo ve las suyas): ver `routes/portales/tutor.php`.

## `notificaciones.index` — GET `route('notificaciones.index')` (`?solo=no_leidas` opcional)
Variables: `notificaciones` (paginador, 20 por página; cada una: `id`, `tipo` urgente|aviso|informativo, `titulo`, `mensaje`, `leida_at` null|Carbon, `created_at`, `entidad_tipo`, `entidad_id`), `noLeidas` int, `solo` ?string.

- Contador rojo con `noLeidas`. Filtro Todas / Sin leer (`?solo=no_leidas`).
- Badge por `tipo` (`<x-badge>`): urgente = práctica próxima a finalizar; aviso = encuesta/informe/documentos; informativo = nuevo practicante asignado.
- No leída: destacada; botón "Marcar como leída" → POST `route('notificaciones.leer', $n->id)` con `@csrf` (responde `back()`).
- Botón "Marcar todas como leídas" → POST `route('notificaciones.leer-todas')` con `@csrf`; éxito: flash `ok`.
- Estado vacío: "No tiene notificaciones" / "No tiene notificaciones sin leer".
- La campana del layout puede contar con `Notificacion::where('usuario_destino_id', auth()->id())->whereNull('leida_at')->count()`.
