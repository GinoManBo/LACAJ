---
tags: [desarrollo, setup]
actualizado: 2026-10-06
---
# Entorno de Desarrollo

La app Laravel vive en la raíz del repo (ver [[Arquitectura]]).

## Usuarios de prueba (sembrados con `migrate --seed`)

Contraseña única en desarrollo: **`123456`**. El ingreso es **con RUT** (o correo) — ver `LoginRutTest`.

| Rol | Nombre | Correo | RUT | Notas |
|---|---|---|---|---|
| Administrador | Mauricio Decap Fernández | admin@cajbiobio.cl | 10000001-6 | Todas las regiones |
| Secretaria | Paulina Herrera Núñez | secretaria1@cajbiobio.cl | 10000002-4 | Región Biobío |
| Secretaria | Soledad Vergara Pino | secretaria2@cajbiobio.cl | 10000003-2 | Región Biobío (dos por región, como en la operación real) |
| Abogado tutor | Carlos Muñoz Sepúlveda | abogado1@cajbiobio.cl | 10000004-0 | Área Civil |
| Abogado tutor | Andrea Rojas Fuentes | abogado2@cajbiobio.cl | 10000005-9 | Área Penal |
| Gobernador Regional | Rodrigo Díaz Wörner | gobernador@cajbiobio.cl | 10000006-7 | Autoriza y firma documentos |

Practicantes **no** se siembran: su cuenta se crea sola al aceptar una postulación y la contraseña temporal se muestra una vez a la secretaria (ver [[Portal Secretaría]]).

Datos de referencia sembrados: 6 regiones de Ñuble a Aysén (NB, BI, AR, LR, LL, AI), 138 comunas, 10 consultorios, especialidades (Civil, Penal, Laboral, Familia, Otra) — ver [[Datos — Consultorios]].

## Cómo correr todo

Requiere PHP, Composer, Node ≥20 y Docker (o Podman: `scripts/db-up.sh` cae solo a `podman run`).

```bash
composer db:up                 # MySQL en :3307 (+ Adminer)
cp .env.example .env           # ya apunta al MySQL local (caj_practicas en :3307)
composer install && npm install
php artisan key:generate
php artisan migrate --seed     # referencias + usuarios demo
npm run dev                    # Vite (CSS/JS)
php artisan serve              # http://localhost:8000
```

## Tests
```bash
php artisan test               # usa la base caj_php_test (phpunit.xml)
```
Cubren: portal admin, secretaría, gobernador, evaluación del tutor, documentos del practicante, postulación, borrador, edición de datos del postulante, importación Excel y login por RUT.

## Tarea programada
`php artisan practicas:actualizar-estados` — activa prácticas que ya comenzaron y avisa al tutor de las que están por terminar. **Programarlo a diario** (cron).

## Variables de entorno relevantes
- `PRACTICA_DURACION_MESES` (6 por defecto; reglamento: mín. 6, máx. 12), `MAX_UPLOAD_MB` (8).
- `CLAVEUNICA_*` — ver [[ClaveÚnica]] (apagada por defecto).

## Conexiones
- [[Índice]] · [[Arquitectura]]
