# Registro de Refactorización y Transición de Arquitectura

Este documento centraliza los cambios estructurales aplicados a la plataforma, registrando el comportamiento anterior, las capas de compatibilidad temporal vigentes y el checklist necesario para depurar código heredado en fases posteriores.

---

## 1. Unificación de Roles: `practicante` → `postulante`

### 1.1 Contexto y Motivación
Históricamente la plataforma manejaba a la misma persona física con dos roles distintos: nacía como `postulante` y mutaba a `practicante` al ser aceptada por la Secretaría Regional. 
Se unificó la identidad bajo el rol **`postulante`**, pasando a gobernar los accesos, vistas y permisos según su **etapa en el ciclo de vida** (`$usuario->etapa()`), sin requerir mutaciones de rol en la tabla `usuarios`.

---

### 1.2 Comportamiento y Código Anterior (Heredado)

A continuación se detallan los puntos que existían antes de la unificación:

| Archivo | Comportamiento Anterior | Estado Actual |
| :--- | :--- | :--- |
| `CajBioBioPlataforma/app/Services/Postulaciones.php` | Asignaba explícitamente `'rol' => 'practicante'` al momento de aceptar la postulación. | Se fija `'rol' => 'postulante'`. El avance lo determina la vinculación en `practicantes` y `practicas`. |
| `CajBioBioPlataforma/app/Models/Usuario.php` | Redirección estática en `portalInicio()` según coincidencia exacta de rol (`practicante` → `/practicante`, `postulante` → `/mi-postulacion`). | Se añade `portalInicioPostulante()` y `etapa()` para resolver dinámicamente según la fase real. |
| `CajBioBioPlataforma/app/Support/Scope.php` | `Scope::practicanteId()` validaba únicamente `$u->rol === 'practicante'`. | Soporta tanto `'practicante'` como `'postulante'`. |
| `CajBioBioPlataforma/app/Http/Middleware/RequiereRol.php` | Comprobación rígida de roles sin admitir equivalencias. | Incorpora compatibilidad bidireccional entre `postulante` y `practicante`. |
| `CajBioBioPlataforma/app/Http/Controllers/DocumentoController.php` | Solo los usuarios con rol `'practicante'` podían consultar los documentos de la práctica. | Método `esDelPracticanteOPostulante()` que atiende ambos roles y permite al postulante ver sus documentos de postulación. |
| `CajBioBioPlataforma/routes/portales/practicante.php` | Rutas protegidas únicamente con `middleware('rol:practicante')`. | Protegidas con `middleware('rol:practicante,postulante')`. |
| `CajBioBioPlataforma/routes/portales/postulante.php` | Rutas protegidas únicamente con `middleware('rol:postulante')`. | Protegidas con `middleware('rol:postulante,practicante')`. |
| `CajBioBioPlataforma/resources/views/layouts/portal.blade.php` | Menú lateral asignado por valor directo de `$u->rol`. | Menú condicionado dinámicamente si el postulante cuenta con práctica asignada. |

---

### 1.3 Capa de Compatibilidad Temporal Vigente

Para no romper instalaciones existentes, datos de prueba ni vistas administrativas mientras se consolida la refactorización:

1. **Migración de unificación:**
   * Archivo: `CajBioBioPlataforma/database/migrations/2026_11_01_000001_unifica_rol_practicante_en_postulante.php`
   * Sentencia: `UPDATE usuarios SET rol = 'postulante' WHERE rol = 'practicante';` (idempotente y segura ante 0 coincidencias).
   * Reversibilidad: Método `down()` que reasigna `'practicante'` a quienes tengan registro en la tabla `practicantes`.
2. **Tolerancia en Middleware:**
   * `CajBioBioPlataforma/app/Http/Middleware/RequiereRol.php` trata `'practicante'` y `'postulante'` como equivalentes para evitar bloqueos 403.
3. **Preservación en constantes:**
   * `Usuario::ROLES` mantiene `'practicante'` temporalmente para permitir lecturas históricas.

---

### 1.4 Checklist para la Limpieza Definitiva (Fase Futura)

Cuando se determine eliminar por completo el rol `'practicante'` del código base:

- [ ] **`CajBioBioPlataforma/app/Models/Usuario.php`:**
  - Retirar `'practicante'` de la constante `Usuario::ROLES`.
  - Retirar la opción `'practicante'` del `match` en `Usuario::portalInicio()`.
- [ ] **`CajBioBioPlataforma/app/Http/Middleware/RequiereRol.php`:**
  - Retirar la lógica de equivalencia bidireccional, validando únicamente `$usuario->rol` contra `$roles`.
- [ ] **`CajBioBioPlataforma/app/Support/Scope.php`:**
  - Simplificar `Scope::practicanteId()` validando exclusivamente `$u->rol === 'postulante'`.
- [ ] **`CajBioBioPlataforma/app/Http/Controllers/DocumentoController.php`:**
  - Limpiar `'practicante'` del `match` en `puedeVer()`.
- [ ] **`CajBioBioPlataforma/routes/portales/practicante.php`:**
  - Reemplazar `middleware('rol:practicante,postulante')` por `middleware('rol:postulante')`.
- [ ] **`CajBioBioPlataforma/routes/portales/postulante.php`:**
  - Reemplazar `middleware('rol:postulante,practicante')` por `middleware('rol:postulante')`.
- [ ] **Vistas Administrativas:**
  - En `CajBioBioPlataforma/resources/views/admin/usuarios/form.blade.php` y `CajBioBioPlataforma/resources/views/admin/usuarios/index.blade.php`, retirar la clave `'practicante'` de los arreglos `$etRol`.
- [ ] **Tests:**
  - Retirar referencias a `usuario('practicante')` en `CajBioBioPlataforma/tests/Concerns/DatosPracticaB.php`.

---

## 2. Próximas Refactorizaciones (Pendientes)

*(Espacio reservado para documentar las siguientes fases de refactorización según se vayan abordando).*
