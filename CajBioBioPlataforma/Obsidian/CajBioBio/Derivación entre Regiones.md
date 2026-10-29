---
tags: [regla, regiones]
actualizado: 2026-10-06
---
# Derivación entre Regiones

La plataforma es regional: cada secretaria ve solo su región. Pero un postulante puede haber postulado a un consultorio de otra región.

- Al asignar ([[Postulaciones]]), si el consultorio elegido pertenece a **otra región** → la postulación **se deriva**, no se acepta.
- La postulación sigue `pendiente`, cambia su `region_id` al destino y desaparece de la bandeja de origen; queda registro en `postulacion_derivaciones` (origen → destino, quién, nota) y un comentario si hubo nota.
- Las **secretarias activas de la región destino** reciben una [[Notificaciones|notificación]] "Postulación derivada".
- La ficha muestra el **historial de derivaciones**. En el selector de consultorios aparecen las etiquetas *POSTULÓ AQUÍ* y *OTRA REGIÓN*.
- Aceptar (misma región): exige cupo libre; abogado tutor opcional, debe pertenecer al consultorio.
- Regiones **operativas**: de Ñuble a Aysén (Ñuble, Biobío, Araucanía, Los Ríos, Los Lagos, Aysén). Son las únicas que existen en el seed; en bases antiguas el resto queda con `operativa=false` y sus consultorios desactivados.

## Conexiones
- [[Portal Secretaría]] · [[Roles y Permisos]] · [[Consultorio]]
