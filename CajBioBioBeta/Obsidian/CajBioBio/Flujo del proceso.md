---
tags: [flujo, conexiones]
---
# Flujo del proceso

Describe las **conexiones** entre las entidades del sistema.

1. Un [[Postulante]] completa el [[Formulario de Postulación]] y elige uno o más [[Consultorio|consultorios]] por **orden de preferencia** más una fecha de inicio.
2. La postulación llega a [[Postulaciones]] (panel). Al **aceptar** se revisan sus datos y se le **asigna un [[Consultorio]]** (se puede redirigir a otro distinto al postulado).
3. Al aceptarse, la postulación se convierte en una [[Práctica]], que conecta:
   - un [[Practicante]] (el ex postulante),
   - un [[Abogado Tutor]],
   - un [[Consultorio]],
   - fechas de inicio y término, y un **estado**.
4. Las prácticas vigentes se ven en [[Prácticas Activas]], donde se pueden abrir sus [[Estadísticas de la Práctica]] o **cancelarlas**.
5. Cuando una práctica termina o se cancela pasa al [[Historial]] (estados Finalizada / Cancelada).
6. Cada [[Consultorio]] tiene una disponibilidad de cupos que se refleja en [[Consultorios]].
7. El [[Practicante]] revisa su [[Vista Practicante]] con sus [[Calificaciones]] y, al finalizar, descarga su [[Certificado de Práctica]].
8. El [[Abogado Tutor]] gestiona a sus practicantes desde la [[Vista Abogado]].

```
Postulante → Formulario → Postulaciones (aceptar/asignar) → Práctica
Práctica → Practicante + Abogado Tutor + Consultorio
Práctica → Prácticas Activas → (Estadísticas | Cancelar) → Historial
```
