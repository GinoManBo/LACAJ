# CAJ Biobío — Plataforma de Gestión de Prácticas

Sistema institucional para la gestión y optimización de prácticas judiciales en la Corporación de Asistencia Judicial del Biobío (portales por rol: Postulación pública, Secretaría Regional, Jurídica Central Concepción, Abogado Jefe / Corte de Apelaciones, Gobernador y Administrador) en PHP/Laravel + Blade y MySQL.

## Requisitos previos

- **PHP** >= 8.2 (extensiones requeridas: `pdo_mysql`, `mbstring`, `gd`, `zip`, `xml`)
- **Composer** >= 2.x
- **Node.js** >= 20.x y **npm**
- **Docker** o **Podman** (para el contenedor MySQL local) o un servidor **MySQL 8.0+** propio

## Cómo correr el proyecto

```bash
# 1. Posicionarse en la carpeta de la plataforma
cd CajBioBioPlataforma

# 2. Instalar dependencias
composer install
npm install

# 3. Archivo de configuración y clave de cifrado
cp .env.example .env
php artisan key:generate

# 4. Levantar base de datos MySQL en :3307 y Adminer en :8081
docker compose up -d
# (o mediante: composer db:up)

# 5. Ejecutar migraciones y cargar datos semilla (192 consultorios oficiales y usuarios demo)
php artisan migrate --seed

# 6. Iniciar servidores de desarrollo
# Terminal 1 - Backend Laravel:
php artisan serve
# Terminal 2 - Frontend / Vite:
npm run dev
```

Accesos locales por defecto:
- **Plataforma Web:** [http://localhost:8000](http://localhost:8000)
- **Gestor BD (Adminer):** [http://localhost:8081](http://localhost:8081)

## Tests

Las pruebas automatizadas utilizan la base `caj_php_test` (configurada en `phpunit.xml`):

```bash
php artisan test
```

## Tareas programadas

`php artisan practicas:actualizar-estados` activa prácticas que ya comenzaron y avisa al tutor de las que están por terminar. Programarlo a diario.

## Documentación y Diseño

- Especificación de vistas por portal en `docs/vistas/`.
- Registro de arquitectura y cambios en `docs/REFACTORIZACION.md`.
- Manuales y anexos institucionales en `docs/`.
- Componentes Blade (`<x-boton>`, `<x-campo>`, `<x-tabla>`…) en `resources/views/components/` y estilos en `resources/css/app.css`.

## Reglas institucionales

- Autorización por región/rol/propietario siempre en servidor (`app/Support/Scope.php`).
- Usuarios se desactivan (`activo=false`), nunca se borran: trazabilidad en `auditoria`.

## ClaveÚnica

Integración OpenID Connect lista pero **apagada** (`CLAVEUNICA_ENABLED=false`; rutas responden 404 y el botón no aparece).
Para activarla: solicitar la app en https://claveunica.gob.cl, registrar el redirect `https://<dominio>/claveunica/callback` y completar en `.env`
`CLAVEUNICA_ENABLED=true`, `CLAVEUNICA_CLIENT_ID`, `CLAVEUNICA_CLIENT_SECRET`, `CLAVEUNICA_REDIRECT_URI`.
El ingreso vincula por RUN: el administrador debe haber creado el usuario con ese RUT (`ClaveUnicaController`, `app/Services/ClaveUnica.php`).
