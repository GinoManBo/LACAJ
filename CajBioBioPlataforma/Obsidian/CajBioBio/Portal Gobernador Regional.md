---
tags: [portal, gobernador]
actualizado: 2026-10-06
---
# Portal Gobernador Regional

Perfil agregado en el commit `e175e52`. Rol `gobernador`. Spec: `docs/vistas/gobernador.md`.

## Cola de autorizaciones (`gobernador.inicio`)
Prácticas **no canceladas, con evaluación aprobada y documentos aún no autorizados**, de la más antigua a la más reciente. Muestra practicante + RUT, consultorio/región, tutor, promedio con concepto y fecha de evaluación.

Acciones (todas auditadas):
- **Autorizar** una fila.
- **Autorizar seleccionadas** (checkboxes, "seleccionar todo").
- **Autorizar N** (lote de 1–1000, las más antiguas primero).
- **Autorizar todas** (con confirmación en modal).
Resultado: "Se autorizaron X de Y práctica(s)". Al autorizar, el practicante recibe una [[Notificaciones|notificación]] y puede descargar sus documentos.

## Firma electrónica (`gobernador.firma`)
Sube su firma (PNG/JPG ≤2 MB, ideal PNG transparente). Solo él ve la imagen. Si no hay firma, los PDF salen **sin firma** y se avisa en el inicio. Ver [[Documentos y Firma]].

## Conexiones
- [[Evaluación del Tutor]] (precondición) · [[Roles y Permisos]] · [[Auditoría]]
