---
tags: [desarrollo, arquitectura]
actualizado: 2026-10-06
---
# Arquitectura

Historia: mockup de un solo `App.tsx` → monorepo Node/React con MySQL (commit `8055374`) → **migración a PHP/Laravel** (commit `85e7d1a`, "PHP migrado"). Luego se eliminó el stack Node/React y la app Laravel pasó a la raíz del repo.

## Estructura del repo
| Carpeta | Qué es |
|---|---|
| `app/`, `routes/`, `resources/`, `database/`, `config/`, `public/`, `tests/` | Laravel + Blade + MySQL. Portales por rol. |
| `docs/` | Especificación de vistas, manuales y material de referencia |
| `docker-compose.yml`, `scripts/db-up.sh` | MySQL (puerto 3307) vía docker o podman |
| `Obsidian/CajBioBio/` | Esta bóveda |

## Dentro de la app
- `routes/web.php` — público, login, ClaveÚnica, y carga un archivo por portal desde `routes/portales/` (admin, secretaria, tutor, practicante, gobernador, postulante, postulacion).
- `app/Http/Controllers/<Portal>/` — un folder por rol.
- `app/Services/` — reglas de negocio: `Postulaciones`, `Practicas`, `PracticasAdmin`, `UsuariosAdmin`, `EvaluacionesPractica`, `InformesPractica`, `GobernadorAutorizaciones`, `GobernadorFirma`, `CertificadosPractica`, `ImportadorPostulaciones`, `ClaveUnica`, `Notificaciones`, `Auditoria`, `Almacenamiento`, `PracticaVista`.
- `app/Support/` — `Scope` (autorización por fila), `Notas` (escala y conceptos), `Rut`, `Calendario` (feriados).
- `app/Http/Middleware/` — `RequiereRol` (`rol:xxx`), `ForzarCambioPassword`.
- `resources/views/` — Blade por portal + `components/` (`<x-boton>`, `<x-campo>`, `<x-tabla>`, `<x-badge>`, `<x-modal>`, `<x-indicador>`, `<x-tarjeta>`…) + `pdf/` (certificado, resolución, cancelación).
- `docs/vistas/*.md` — especificación de variables y reglas por portal (fuente de verdad de cada vista).
- Migraciones: referencias → usuarios → postulaciones/prácticas → evaluación/documentos → ajustes (importadas parciales, borrador de postulación, rol postulante, datos actualizados).

## Seguridad (reglas innegociables)
- Autorización por región/rol/propietario **solo en servidor** (`Scope.php`), nunca por un id que mande el cliente. Ver [[Roles y Permisos]].
- Usuarios se **desactivan** (`activo=false`), nunca se borran → trazabilidad en [[Auditoría]].
- Login con bloqueo por intentos fallidos + `throttle` en el POST de ingreso; cambio de contraseña forzado (`debe_cambiar_password`).
- Se **eliminó la validación automática de cédula con IA** (Gemini) del producto; la cédula se sube y la revisa una persona.
- Documentos de postulación se guardan en staging (`archivos_staging`) y se adoptan al enviar; descarga con autorización por fila (`DocumentoController`).

## Conexiones
- [[Entorno de Desarrollo]] · [[Índice]]
