# CAJ Biobío — Sistema de Gestión de Practicantes

Aplicación PHP/Laravel + Blade + MySQL con portales por rol (postulación pública,
postulante, secretaría, administrador, gobernador regional, abogado tutor,
practicante). El proyecto Laravel vive en la raíz del repo. No hay React ni API
Node: el stack anterior fue eliminado.

## Estructura

- `app/Http/Controllers/<Portal>/` — un folder por rol.
- `app/Services/` — reglas de negocio (postulaciones, prácticas, evaluaciones,
  certificados, firma del gobernador, importación Excel, notificaciones, auditoría).
- `app/Support/` — `Scope` (autorización por fila), `Notas`, `Rut`, `Calendario`.
- `app/Http/Middleware/` — `RequiereRol` (`rol:xxx`), `ForzarCambioPassword`.
- `routes/web.php` — público, login y ClaveÚnica; carga un archivo por portal
  desde `routes/portales/`.
- `resources/views/` — Blade por portal, `components/` (`<x-boton>`, `<x-campo>`,
  `<x-tabla>`…) y `pdf/`. Tokens CSS en `resources/css/app.css`.
- `database/migrations`, `database/seeders` — schema y datos de referencia/demo.
- `tests/Feature/` — suite por portal; usa la base `caj_php_test` (`phpunit.xml`).
- `docs/vistas/` — especificación de variables y reglas por portal. `docs/` también
  guarda el manual de usuario y material de referencia.
- `docker-compose.yml`, `docker/`, `scripts/db-up.sh` — MySQL local en `:3307`.
- `Obsidian/CajBioBio/` — bóveda con la documentación funcional.

## Desarrollo

Ver README.md (`composer db:up` → `composer install` → `php artisan migrate --seed`
→ `npm run dev` + `php artisan serve`).
`php` y `composer` pueden no estar en el PATH: están en `~/.config/herd-lite/bin`.
Docker puede no estar disponible — `scripts/db-up.sh` cae automáticamente a
`podman run` si falta `docker compose`.

## Seguridad

- Toda autorización por región/rol/propietario se aplica en el servidor
  (`app/Support/Scope.php`), nunca confiando en un id que mande el cliente.
- Los usuarios se desactivan (`activo=false`), nunca se borran, para preservar
  trazabilidad (tabla `auditoria`).
- Ningún secreto con prefijo `VITE_`: Vite lo incluye en el bundle del navegador.
- La cédula se sube y la revisa una persona; no hay validación automática con IA.

## Estilo

- Vistas en Blade con JS vanilla; no introducir React.
- Formato PHP con Pint (`vendor/bin/pint`).
