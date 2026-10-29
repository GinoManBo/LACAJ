---
tags: [flujo, conexiones]
actualizado: 2026-10-06
---
# Flujo del proceso

Describe las **conexiones** entre las entidades y los roles del sistema.

1. Un [[Postulante]] completa el [[Formulario de Postulación]] (público, o continuando su borrador tras ingresar con [[ClaveÚnica]]) y elige uno o más [[Consultorio|consultorios]] por **orden de preferencia** más una fecha de inicio. La secretaría también puede cargar postulaciones por [[Importación desde Excel]].
2. La postulación llega a [[Postulaciones]] de la secretaría **de su región** ([[Portal Secretaría]]), que revisa datos y documentos, comenta, y **asigna manualmente** un consultorio.
   - Consultorio de otra región → **se deriva** a esa región ([[Derivación entre Regiones]]).
   - Consultorio propio con cupo → **se acepta**: nace la [[Práctica]], se crea la cuenta del [[Practicante]] (contraseña temporal) y se puede asignar [[Abogado Tutor]].
   - O se **rechaza** con motivo.
3. La práctica (estado `por_iniciar`) pasa a `activa` al llegar su fecha de inicio (tarea diaria). Se ve en [[Prácticas Activas]] con sus [[Estadísticas de la Práctica]]; puede **cancelarse** (genera PDF de cancelación) o ser corregida/trasladada por el [[Portal Administrador]].
4. Cerca del término (≤30 días) el tutor recibe una alerta *urgente* ([[Notificaciones]]).
5. El practicante sube su **informe de práctica** ([[Portal Practicante]]) → esto **desbloquea** la encuesta del tutor.
6. El [[Abogado Tutor]] completa la [[Evaluación del Tutor]] (7 criterios, escala 1–7). La práctica pasa a `finalizada` y entra al [[Historial]]; si aprobó (≥4,0), queda pendiente de autorización.
7. El [[Portal Gobernador Regional|Gobernador Regional]] **autoriza** (individual, selección, lote o todas) y su firma queda en los PDF ([[Documentos y Firma]]).
8. El practicante descarga su [[Certificado de Práctica]] y la Resolución.
9. Cada [[Consultorio]] tiene cupos (capacidad − prácticas por iniciar/activas) visibles en [[Consultorios]]; el administrador ajusta la capacidad.

```
Postulante → Formulario → Postulaciones (asignar) ─┬→ Derivada a otra región
                                                   ├→ Rechazada
                                                   └→ Práctica (por_iniciar → activa)
Práctica → Informe del practicante → Evaluación del tutor → Finalizada
Finalizada (aprobada) → Autorización del Gobernador → Certificado + Resolución
Práctica → Cancelar → Historial (+ PDF de cancelación)
```
