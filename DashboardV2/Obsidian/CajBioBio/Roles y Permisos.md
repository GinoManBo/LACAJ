---
tags: [roles, seguridad]
actualizado: 2026-10-06
---
# Roles y Permisos

La plataforma es **regional**: cada persona gestiona lo de su región. Seis roles; cada uno entra a su propio portal (`RequiereRol`, `rol:xxx`).

| Rol | Alcance | Portal |
|---|---|---|
| `postulante` | Solo su propia postulación (entra con [[ClaveÚnica]]) | [[Portal Público y Postulante]] |
| `secretaria` | **Solo su región** (`Scope::regionId`) | [[Portal Secretaría]] |
| `administrador` | Todas las regiones; corrige lo que las secretarias se equivocan; gestiona usuarios y cupos | [[Portal Administrador]] |
| `abogado` | Solo las prácticas que tiene asignadas (`Scope::abogadoId`) | [[Portal Abogado Tutor]] |
| `practicante` | Solo su propia práctica (`Scope::practicanteId`) | [[Portal Practicante]] |
| `gobernador` | Cola nacional de prácticas con evaluación aprobada | [[Portal Gobernador Regional]] |

## Reglas
- Toda consulta pasa por `app/Support/Scope.php`; el id que mande el cliente nunca decide el acceso.
- Secretaria sin región asignada → 403. Rol `secretaria` exige región al crearla (admin).
- Desactivar ≠ borrar: `activo=false`, historial intacto ([[Auditoría]]). Un admin no puede desactivarse a sí mismo.
- Admin puede restablecer contraseña: se genera una temporal que se muestra **una sola vez**.
- Notificaciones: cada usuario ve solo las suyas ([[Notificaciones]]).

## Conexiones
- [[Arquitectura]] · [[Índice]]
