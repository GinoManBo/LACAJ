---
tags: [entidad]
---
# Práctica

Instancia de práctica profesional. Nace cuando se acepta una postulación en [[Postulaciones]] (tabla `practicas`).

## Campos
- [[Practicante]], [[Consultorio]], [[Abogado Tutor]] (opcional al inicio), fecha de inicio y término (por defecto inicio + 6 meses; reglamento 6–12).
- **Estado guardado**: `por_iniciar` → `activa` → `finalizada` | `cancelada`.
- **Estado visible**: `por_terminar` es derivado (activa y ≤30 días para el término).
- `nota_practica`, `documentos_autorizados` (+ quién/cuándo, ver [[Portal Gobernador Regional]]).
- Cancelación: fecha, motivo, detalle, quién, y si fue antes de iniciar.
- Traslados entre sedes (`practica_traslados`) con historial.

## Ciclo
- `por_iniciar` → `activa`: tarea diaria `practicas:actualizar-estados`.
- `activa` → `finalizada`: al enviarse la [[Evaluación del Tutor]].
- `por_iniciar`/`activa` → `cancelada`: [[Portal Secretaría]] o [[Portal Administrador]].
- "Ocupa cupo" mientras esté `por_iniciar` o `activa`.

## Conexiones
- [[Prácticas Activas]] · [[Estadísticas de la Práctica]] · [[Historial]] · [[Certificado de Práctica]] · [[Flujo del proceso]]
