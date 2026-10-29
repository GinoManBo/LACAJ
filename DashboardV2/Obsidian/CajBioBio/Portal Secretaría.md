---
tags: [portal, secretaria]
actualizado: 2026-10-06
---
# Portal Secretaría

Rol `secretaria`; **solo ve su región**, sin selector de región. Spec: `docs/vistas/secretaria.md`. Rutas `secretaria.*`.

| Pantalla | Qué hace |
|---|---|
| [[Panel General]] | Indicadores (pendientes, activas, por terminar) + últimas postulaciones + prácticas por terminar |
| [[Postulaciones]] | Bandeja, búsqueda, **importar Excel** ([[Importación desde Excel]]), ficha con documentos, comentarios internos, rechazar, **aceptar/asignar consultorio** |
| [[Prácticas Activas]] | Por iniciar + activas, cupos, **exportar a Excel**, cancelar con motivo |
| [[Estadísticas de la Práctica]] | Total / transcurridos / restantes en días y % de avance |
| [[Historial]] | Finalizadas y canceladas, filtro por año; ficha con pestañas Resumen · Documentos · Calificaciones |
| [[Consultorios]] | Cupos por sede (solo lectura; los cupos los cambia el [[Portal Administrador]]) |

## Reglas clave
- **Asignar** = decisión de la secretaría (Jurídica asigna manualmente al consultorio). Si el consultorio elegido es de **otra región**, no se acepta: se **deriva** ([[Derivación entre Regiones]]). Si es de la propia, se acepta y se crea la [[Práctica]].
- Al aceptar se crea la cuenta del practicante con **contraseña temporal mostrada una sola vez** (flash `credenciales`); puede asignar abogado tutor de ese consultorio.
- Sin cupos libres no se puede aceptar.
- Postulaciones importadas sin RUT o fecha de inicio **no se pueden aceptar** hasta completarlas.
- **Comentarios** por postulación (autor + fecha); solo el autor puede borrar el suyo.
- **Cancelar** práctica (por_iniciar/activa): motivo (conducta, decisión del practicante, otro + detalle) → genera **documento de cancelación en PDF**, avisa al practicante y al tutor; marca `cancelacion_antes_de_iniciar` (etiqueta *NO INICIÓ* en historial).
- Estado visible `por_terminar` es **derivado** (activa y faltan ≤30 días), no se guarda.

## Conexiones
- [[Roles y Permisos]] · [[Flujo del proceso]] · [[Auditoría]]
