---
tags: [regla, documentos, pdf]
actualizado: 2026-10-06
---
# Documentos y Firma

PDF generados con DomPDF desde `resources/views/pdf/`. Servicio: `CertificadosPractica`.

## Documentos del practicante (certificado + resolución)
- **Certificado Corte Suprema** y **Resolución de aprobación** (con número correlativo).
- Se desbloquean solo cuando se cumple **todo** (`CertificadosPractica::bloqueo`): práctica no cancelada → `finalizada` → con [[Evaluación del Tutor]] → evaluación **aprobada** → **autorizada por el Gobernador Regional**. Si falta algo, el portal muestra el motivo exacto y la descarga responde 403.
- **Nº de resolución**: se asigna en la primera emisión y se conserva; secuencia **por año, sin saltos** (tabla `resolucion_secuencia`; si el PDF falla, el número no se consume).
- Texto con género (Abogado/Abogada) según el postulante; RUT formateado; logo CAJ embebido.
- Texto institucional parametrizado en `config/caj_documentos.php`.

## Firma electrónica
El gobernador sube su firma en su portal ([[Portal Gobernador Regional]]); se incrusta en base64 en los PDF. Sin firma, los PDF salen sin firma.

## Documento de cancelación
Al cancelar una práctica ([[Portal Secretaría]]) se genera un PDF de cancelación (`secretaria.cancelacion.documento`). Era un pendiente de `cambios.txt` ("generar documento por la cancelación").

## Archivos subidos
Postulación (cédula, universitarios), informe de práctica y firma pasan por `Services/Almacenamiento`; la descarga/visualización pasa por `DocumentoController` con autorización por fila.

## Conexiones
- [[Certificado de Práctica]] · [[Auditoría]]
