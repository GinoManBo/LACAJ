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

## 2. Descentralización Regional, Jurídica Central (Biobío — Concepción) y Escala de Conceptos

### 2.1 Contexto y Motivación
Se estableció la separación funcional y jerárquica entre la **Secretaría Regional** (administración territorial local en las 6 regiones de la jurisdicción) y la **Secretaría Jurídica Central** ubicada en la sede de Biobío (Concepción):
1. **Delimitación a 6 meses (180 días):** Las secretarías regionales gestionan postulaciones, asignación y seguimiento durante los 6 meses de práctica. Cumplido este plazo y recibida la evaluación del tutor y la visación del Director Regional, la Secretaría Regional **cierra su competencia territorial y remite el expediente a Jurídica Central Biobío**.
2. **Pérdida de competencia territorial:** Una vez remitido (`remitida_biobio`), el expediente pasa a modo solo lectura para la región de origen, la cual no puede alterar ni resolver notas finales.
3. **Potestades exclusivas de Jurídica Central:**
   * **Recepción nacional:** Bandeja centralizada de expedientes de todas las regiones.
   * **Nivelación de notas de oficio (Regla ≥ 5.0 "Buena"):** Si una región remite calificación "Regular" (4.0 a 4.9) sin causales disciplinarias, Jurídica Central eleva la nota a 5.0 ("Buena") registrando justificación normativa en auditoría (`nivelada_biobio`). Si existen antecedentes sancionatorios graves fundados, se rechaza formalmente (`rechazada_sancion`).
   * **Despacho formal y archivo (`cerrada_historial`):** Notifica formalmente al postulante y archiva el expediente.
4. **Sistema institucional de conceptos:** Sustitución de la visión puramente numérica (1.0 a 7.0) por los conceptos oficiales reglamentarios:
   * **Sobresaliente** (6.5 – 7.0)
   * **Muy Bueno** (5.5 – 6.4)
   * **Buena** (5.0 – 5.4, o nivelada de oficio a 5.0)
   * **Regular** (4.0 – 4.9, aprobatoria pero sujeta a nivelación)
   * **Deficiente** (< 4.0, reprobatoria)

---

### 2.2 Componentes Afectados y Cambios Realizados

| Archivo | Comportamiento Anterior | Estado Actual |
| :--- | :--- | :--- |
| `CajBioBioPlataforma/app/Support/Notas.php` | Escala simple con concepto `SUFICIENTE` (4.0 a 4.4) y `BUENO` (4.5 a 5.4). | Incorpora conceptos oficiales (`BUENA`, `REGULAR`), método `esNivelable()` (4.0 a 4.9) y constante `NOTA_NIVELADA_OFICIO` (5.0). |
| `CajBioBioPlataforma/app/Models/Usuario.php` | Roles fijos sin distinción de Jurídica Central; `portalInicio()` limitado a secretaría regional. | Añade rol `juridica_central` a constantes y redirige automáticamente a `secretaria.juridica.index`. |
| `CajBioBioPlataforma/app/Support/Scope.php` | `regionId()` exigía región obligatoria para secretarías. | Otorga alcance nacional (`regionId() === null`) a usuarios con rol `juridica_central`. |
| `CajBioBioPlataforma/app/Models/Practica.php` | Estados tradicionales (`por_iniciar`, `activa`, `finalizada`, `cancelada`). | Incorpora trazabilidad para `remitida_at`, `nivelada_at`, `despachada_at`, relaciones de usuario y nuevos estados de flujo. |
| `CajBioBioPlataforma/app/Http/Controllers/Secretaria/HistorialController.php` | Solo listaba `finalizada` y `cancelada`. Sin opción de remisión. | Amplía estados (`remitida_biobio`, `nivelada_biobio`, etc.), añade modo solo lectura para la región y la acción `remitirBiobio()`. |
| `CajBioBioPlataforma/app/Http/Controllers/Secretaria/JuridicaBiobioController.php` | No existía. | Nuevo controlador exclusivo: recepción nacional, nivelación de oficio a 5.0, rechazo por sanción fundada y despacho final. |
| `CajBioBioPlataforma/routes/portales/secretaria.php` | Rutas estándar para secretarías regionales. | Incorpora rutas bajo prefijo `/secretaria/juridica` y endpoint de remisión regional `/historial/{id}/remitir-biobio`. |
| `CajBioBioPlataforma/database/seeders/UsuariosDemoSeeder.php` | Dotación incompleta con 2 secretarias en Biobío. | Estructura institucional completa: 2 en Biobío, 1 en cada región externa (NB, AR, LR, LL, AI) y 1 en Jurídica Central, documentando flexibilidad. |
| `CajBioBioPlataforma/resources/views/secretaria/historial/ver.blade.php` | Vista estática regional. | Agrega botón de remisión formal visada por Director Regional y alerta informativa de sólo lectura territorial. |
| `CajBioBioPlataforma/resources/views/secretaria/juridica/index.blade.php` | No existía. | Vista de bandeja de recepción nacional con filtros por región, estado y tarjetas de resumen. |
| `CajBioBioPlataforma/resources/views/secretaria/juridica/ver.blade.php` | No existía. | Ficha de expediente nacional con desglose de conceptos, alerta de nivelación y modales de acción exclusiva. |

---

### 2.3 Delimitación Territorial, Calificación Manual en Palabras y Pantallas Separadas

A fin de garantizar la separación de atribuciones entre la sede central y las sedes territoriales:

1. **Gestión Manual de Calificaciones y Expresión en Palabras:**
   * **Procedimiento 100% manual:** La nivelación o ajuste de calificaciones en Jurídica Central nunca es automática. La secretaria examina manualmente los antecedentes disciplinarios y el expediente remitido, abriendo el modal de decisión.
   * **Opciones completas en palabras:** El formulario de ajuste ofrece el abanico institucional completo en palabras:
     - `Sobresaliente`
     - `Muy Buena`
     - `Buena` (preseleccionada por defecto ante calificaciones de origen en concepto Regular sin antecedentes sancionatorios)
     - `Regular`
     - `Deficiente`
   * **Ausencia de números en la interfaz:** En todas las pantallas de visualización, tablas y exportables se eliminaron los valores decimales numéricos (ej. 5.0, 4.8), exponiendo exclusivamente el concepto institucional en palabras. Internamente, la base de datos conserva anclas decimales (`Notas::OPCIONES`) para mantener la integridad de los campos `decimal(2,1)` en `practicas` y `calificaciones`.

2. **Pantalla Antes de la Práctica (Supervisión Nacional de Postulaciones):**
   * **Ruta:** `CajBioBioPlataforma/routes/portales/secretaria.php` (`/secretaria/juridica/postulaciones` → `JuridicaBiobioController@postulaciones`).
   * **Alcance:** Visibilidad completa de todos los postulantes del país pendientes de ingreso.
   * **Protección de Competencia Territorial:**
     * **Postulantes de Biobío (`BI`):** Botón «Tramitar y Asignar» con plenas facultades para aceptar, asignar consultorio o rechazar.
     * **Postulantes de Regiones Externas (NB, AR, LR, LL, AI):** Botón «Ver (Solo lectura)». Se deshabilitan los formularios y modales en `CajBioBioPlataforma/resources/views/secretaria/postulaciones/ver.blade.php`. Si se intenta enviar una asignación o rechazo vía POST, los métodos `aceptar()` y `rechazar()` en `CajBioBioPlataforma/app/Http/Controllers/Secretaria/PostulacionesController.php` abortan con **HTTP 403 Forbidden**.
   * **Descarga Excel:** `JuridicaBiobioController@exportarPostulaciones` con columna explícita «Origen Territorial» (`Local (Biobío)` vs `Externo ([Región])`).

3. **Pantalla Después de la Práctica (Recepción Nacional de Expedientes):**
   * **Ruta:** `CajBioBioPlataforma/routes/portales/secretaria.php` (`/secretaria/juridica` → `JuridicaBiobioController@index`).
   * **Alcance:** Bandeja de expedientes remitidos por las regiones tras finalizar los 6 meses de práctica.
   * **Acciones Exclusivas:** Determinar calificación institucional manualmente seleccionando entre todas las opciones en palabras, rechazo fundado por sanción disciplinaria y despacho formal de certificados para la Corte Suprema con archivo en `cerrada_historial`.
   * **Descarga Excel:** `JuridicaBiobioController@exportarExpedientes` con columna de calificación exclusivamente en palabras («Calificación Institucional»), estado de despacho y origen territorial.

4. **Filtros Compuestos en Pantalla y Descargas Excel:**
   * Badges diferenciados en todas las tablas y fichas: `LOCAL (Biobío)` en azul celeste vs `EXTERNO ([Código])` en púrpura.
   * Filtro desplegable de origen en ambas pantallas: `Todos los orígenes`, `Solo Biobío (Local/Gestión directa)` y `Solo Regiones Externas`.
   * Filtro por región específica y búsqueda por texto.
   * **Soporte estricto de filtros en Excel:** Tanto `exportarPostulaciones` como `exportarExpedientes` reciben la totalidad de los parámetros de consulta activos (`q`, `origen`, `region_id`, `estado`) vía `request()->query()`, asegurando que el archivo descargado contenga con exactitud los mismos registros filtrados en pantalla.
   * Menú lateral en `CajBioBioPlataforma/resources/views/layouts/portal.blade.php` con accesos directos independientes:
     * *Antes: Postulaciones* (`secretaria.juridica.postulaciones`)
     * *Después: Expedientes* (`secretaria.juridica.index`)
     * *Historial general* (`secretaria.historial`)
     * *Consultorios* (`secretaria.consultorios`)

---

### 2.4 Dotación Regional en Seeder vs. Reglas de Negocio
Se documentó explícitamente en `CajBioBioPlataforma/database/seeders/UsuariosDemoSeeder.php` que la asignación inicial (2 secretarias en Biobío, 1 en cada una de las 5 regiones externas y 1 en Jurídica Central) es un escenario demostrativo y referencial. La plataforma no impone un límite de concurrencia o dotación máxima por región en sus modelos ni validaciones.

---

## 3. Próximas Refactorizaciones (Pendientes)

*(Espacio reservado para documentar las siguientes fases de refactorización según se vayan abordando).*
