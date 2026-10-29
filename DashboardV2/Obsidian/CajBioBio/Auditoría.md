---
tags: [sistema, auditoria]
actualizado: 2026-10-06
---
# Auditoría

Tabla `auditoria` (`Services/Auditoria::registrar`): usuario, acción, entidad, id, datos JSON e IP. Es la razón de que los usuarios **no se borren**.

Acciones registradas (entre otras): `postulacion.derivar`, `postulacion.rechazar`, `practica.cancelar`, `practica.evaluar`, `practica.informe_subido`, `gobernador.autorizar_documentos`, y cambios del [[Portal Administrador]] (traslados, usuarios, capacidad).

## Conexiones
- [[Roles y Permisos]] · [[Arquitectura]]
