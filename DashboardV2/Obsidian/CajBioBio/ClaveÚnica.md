---
tags: [sistema, auth]
actualizado: 2026-10-06
---
# ClaveÚnica

Integración OpenID Connect (`ClaveUnicaController`, `Services/ClaveUnica`) **lista pero apagada**: `CLAVEUNICA_ENABLED=false` → las rutas responden 404 y el botón no aparece.

Para activarla: solicitar la app en claveunica.gob.cl, registrar `https://<dominio>/claveunica/callback` y completar `CLAVEUNICA_ENABLED`, `CLAVEUNICA_CLIENT_ID`, `CLAVEUNICA_CLIENT_SECRET`, `CLAVEUNICA_REDIRECT_URI`.

- El ingreso **vincula por RUN**: el administrador debe haber creado al usuario con ese RUT; quien postula entra con rol `postulante` ([[Portal Público y Postulante]]).
- Ingreso normal: **RUT + contraseña** (y correo), con bloqueo por intentos fallidos.

## Conexiones
- [[Roles y Permisos]] · [[Portal Administrador]]
