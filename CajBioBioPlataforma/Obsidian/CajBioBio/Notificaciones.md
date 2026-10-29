---
tags: [sistema, notificaciones]
actualizado: 2026-10-06
---
# Notificaciones

Campana + página `notificaciones.index` para todos los roles; cada usuario ve solo las suyas. Filtro Todas / Sin leer, marcar una o todas como leídas. Tres tipos: **urgente**, **aviso**, **informativo**.

| Evento | Destinatario | Tipo |
|---|---|---|
| Nueva postulación enviada | Secretarias de la región | aviso |
| Postulante actualizó sus datos | Secretarias | informativo |
| Postulación derivada | Secretarias región destino | aviso |
| Postulación aceptada | Practicante | aviso |
| Nuevo practicante asignado | Abogado tutor | informativo |
| Práctica próxima a finalizar (tarea diaria `practicas:actualizar-estados`) | Abogado tutor | **urgente** |
| Informe de práctica recibido | Abogado tutor | aviso |
| Práctica evaluada | Practicante | aviso |
| Documentos autorizados | Practicante | aviso |
| Práctica cancelada | Practicante y tutor | aviso |

## Conexiones
- [[Roles y Permisos]] · [[Auditoría]]
