---
tags: [regla, evaluacion]
actualizado: 2026-10-06
---
# Evaluación del Tutor

**La calificación es única, no mensual** (`cambios.txt`). Código: `Services/EvaluacionesPractica`, `Support/Notas`.

## Precondiciones (si falla, la encuesta se muestra deshabilitada con el motivo exacto)
1. La práctica está `activa` (solo se evalúan prácticas en curso).
2. **El practicante subió su informe de práctica** ([[Portal Practicante]]) — esto desbloquea la encuesta.
3. No fue evaluada antes (una sola evaluación por práctica).

## Encuesta: 7 criterios, nota 1,0–7,0 (todos obligatorios)
Conocimiento y criterio jurídico · Responsabilidad · Iniciativa · Sentido social y de colaboración · Conducta · Honorabilidad · Asistencia y puntualidad. Más observaciones opcionales (máx. 3000).

- **Promedio** = media de los 7 criterios (2 decimales).
- **Aprobada** si promedio ≥ **4,0**.
- **Concepto**: ≥6,5 SOBRESALIENTE · ≥5,5 MUY BUENO · ≥4,5 BUENO · ≥4,0 SUFICIENTE · <4,0 DEFICIENTE.

## Efecto al enviar (en una transacción, con bloqueo contra doble evaluación)
- Crea la `evaluacion` y la [[Calificaciones|calificación]] de instancia 1 (nota = promedio a 1 decimal).
- La práctica pasa a **`finalizada`** y guarda `nota_practica`.
- [[Auditoría]] `practica.evaluar` + [[Notificaciones|notificación]] al practicante.
- Si aprobó, entra a la cola del [[Portal Gobernador Regional]] para autorizar los documentos ([[Documentos y Firma]]).

## Conexiones
- [[Portal Abogado Tutor]] · [[Práctica]]
