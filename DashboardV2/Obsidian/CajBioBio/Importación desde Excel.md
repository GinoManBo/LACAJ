---
tags: [regla, importacion]
actualizado: 2026-10-06
---
# Importación desde Excel

`Services/ImportadorPostulaciones` (OpenSpout). La secretaria importa postulaciones pendientes desde un `.xlsx` en la bandeja de [[Postulaciones]].

- Columnas fijas por posición: `NombrePostulante | Universidad | NumeroTelefono | fechaPostulacion | Correo | NumeroTelefono` (la 2ª columna de teléfono queda como alterno).
- Máx. **500 filas**; valida nombre, universidad y correo; omite filas inválidas o con correo repetido y devuelve el detalle (`fila`, `motivo`).
- Crea postulante + postulación pendiente **en la región de quien importa**.
- Quedan con **datos parciales** (sin RUT, comuna, fecha de inicio…): no se pueden aceptar ni derivar hasta completarlos.

Inverso: **Exportar a Excel** de [[Prácticas Activas]] (`secretaria.practicas.exportar`).

## Conexiones
- [[Portal Secretaría]] · [[Postulante]]
