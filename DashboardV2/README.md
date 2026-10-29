# CAJ Biobío — Gestión de Practicantes (Laravel)

Portales por rol (postulación pública, secretaría, administrador, gobernador, abogado tutor, practicante) en PHP/Laravel + Blade. MySQL.

## Puesta en marcha

```bash
composer db:up                 # MySQL en :3307 + Adminer en :8081 (docker compose o podman)
cp .env.example .env           # ya apunta al MySQL local (caj_practicas en :3307)
composer install && npm install
php artisan key:generate
php artisan migrate --seed     # referencias + usuarios demo
npm run dev                    # Vite (CSS/JS)
php artisan serve              # http://localhost:8000
```

## Tests

Usan la base `caj_php_test` (ver `phpunit.xml`):

```bash
php artisan test
```

## Tareas programadas

`php artisan practicas:actualizar-estados` activa prácticas que ya comenzaron y avisa al tutor de las que están por terminar. Programarlo a diario.

## Diseño

Especificación por portal en `docs/vistas/`. Manual de usuario y material de referencia (logo, certificados de muestra) en `docs/`. Tokens CSS y componentes Blade (`<x-boton>`, `<x-campo>`, `<x-tabla>`…) en `resources/css/app.css` y `resources/views/components/`.

## Reglas

- Autorización por región/rol/propietario siempre en servidor (`app/Support/Scope.php`).
- Usuarios se desactivan (`activo=false`), nunca se borran: trazabilidad en `auditoria`.

## ClaveÚnica

Integración OpenID Connect lista pero **apagada** (`CLAVEUNICA_ENABLED=false`; rutas responden 404 y el botón no aparece).
Para activarla: solicitar la app en https://claveunica.gob.cl, registrar el redirect `https://<dominio>/claveunica/callback` y completar en `.env`
`CLAVEUNICA_ENABLED=true`, `CLAVEUNICA_CLIENT_ID`, `CLAVEUNICA_CLIENT_SECRET`, `CLAVEUNICA_REDIRECT_URI`.
El ingreso vincula por RUN: el administrador debe haber creado el usuario con ese RUT (`ClaveUnicaController`, `app/Services/ClaveUnica.php`).
