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

## 3. Arquitectura de Abogados: Consolidación bajo el perfil de «Abogado Jefe», Instancias Judiciales (1ª y 2ª Instancia), Carta de Presentación Oficial y Liberación de Cupos

### 3.1 Contexto y Motivación
La gestión judicial en los consultorios y tribunales de la jurisdicción requería formalizar la figura del **Abogado Jefe**, descartando la nomenclatura de «Abogado Tutor» en los portales principales debido a la alta rotación y la estructura orgánica real de la institución:
1. **Diferenciación de Instancias Evaluadoras:**
   * **Abogado Jefe de 1ª Instancia (Consultorio Jurídico):** Asociado a una unidad operativa local (ej. Consultorio San Pedro, Coronel). Supervisa y evalúa los 6 meses continuos de práctica sobre los 7 factores reglamentarios. Su visibilidad está limitada exclusivamente a los postulantes de su unidad.
   * **Abogado Jefe de 2ª Instancia (Corte de Apelaciones):** Asociado a la Oficina de Segunda Instancia Regional. Cuenta con visibilidad transversal para toda su región (`region_id`), accediendo a los postulantes de cualquier consultorio que deban rendir o hayan rendido alegatos ante el Tribunal de Alzada.
   * **Cruces de permisos estrictos:** El Abogado Jefe de consultorio no puede calificar ni modificar la 2ª instancia. El Abogado Jefe de Corte de Apelaciones ve la 1ª instancia como sólo lectura y no puede alterar las notas del consultorio.
2. **Carta de Presentación Oficial y Flujo Manual de Firma:**
   * La plataforma genera en PDF la Carta de Presentación oficial a nombre de la Dirección de Acceso a la Justicia, dirigida al Abogado Jefe del Consultorio y con la advertencia reglamentaria sobre la comparecencia obligatoria ante la Corte de Apelaciones.
   * La Secretaría Regional descarga el documento oficial tipo, tramita su firma manual o electrónica autorizada institucional, y carga el PDF escaneado/firmado en el sistema.
   * Al cargar la carta firmada, la práctica adquiere el estado `activa` («Inicio Manual Oficial»), se notifica automáticamente al Abogado Jefe del consultorio y se instruye el despacho formal del documento.
3. **Carga Asistida de Segunda Instancia:**
   * Si el Abogado Jefe de 2ª Instancia remite la calificación de los alegatos mediante oficio o correo electrónico a la Secretaría, tanto la Secretaría Regional como la Secretaría Jurídica Central disponen de un módulo de carga asistida para transcribir el concepto cualitativo y las observaciones reglamentarias.
4. **Ponderación Asimétrica de Instancias:**
   * La 1ª instancia es el elemento determinante principal del desempeño (evaluación integral de 6 meses).
   * La 2ª instancia es complementaria y condicional a los alegatos ante la Corte.
   * Ambas calificaciones se consolidan como propuesta cualitativa que es visada por el Director Regional antes de la remisión a Jurídica Central Biobío.
5. **Término Provisional y Liberación Automática de Vacantes (180 Días):**
   * Al cumplirse los 6 meses reglamentarios (`fecha_termino <= today()`), el cupo del consultorio se libera automáticamente en el cálculo de vacantes de la institución (`Consultorio::ocupados()`), permitiendo recibir nuevos postulantes sin depender del cierre administrativo o burocrático de la evaluación.
   * **Alerta de término (Día 166 / 5,5 meses):** A dos semanas del término legal, el sistema activa alertas visuales para solicitar el informe final y prevenir retrasos.
   * **Subsanación de Informes:** El Abogado Jefe puede formular observaciones formales al informe final cargado por el postulante antes de emitir la calificación, enviando una notificación urgente para su corrección.
6. **Sistema de Calificación Exclusivamente en Palabras (Escala Institucional de 4 Niveles):**
   * Toda la captura y visualización de notas en la plataforma (tanto para 1ª como para 2ª instancia) se efectúa en conceptos reglamentarios en palabras: `Sobresaliente`, `Buena`, `Regular` y `Mala`, suprimiendo la exposición de números decimales a los usuarios.

---

### 3.2 Componentes Afectados y Cambios Realizados

| Archivo | Comportamiento Anterior | Estado Actual |
| :--- | :--- | :--- |
| `CajBioBioPlataforma/database/migrations/2026_11_02_000001_agrega_segunda_instancia_a_abogados.php` | No existía. | Añade `es_segunda_instancia` y `region_id` (`unsignedSmallInteger` para compatibilidad estricta con `regiones.id` en MySQL) en `abogados`; `carta_presentacion_firmada`, `carta_presentacion_subida_at` y `abogado_segunda_instancia_id` en `practicas`. |
| `CajBioBioPlataforma/app/Models/Abogado.php` | Atributos estándar de abogado tutor sin distinción de instancia ni región. | Incorpora `es_segunda_instancia` a casts, relación `region()`, relación `practicasSegundaInstancia()` y método `esSegundaInstancia()`. |
| `CajBioBioPlataforma/app/Models/Practica.php` | Sin relaciones directas a calificaciones por instancia ni carta firmada. | Añade cast `carta_presentacion_subida_at`, relación `abogadoSegundaInstancia()`, y métodos auxiliares `calificacionPrimeraInstancia()` y `calificacionSegundaInstancia()`. |
| `CajBioBioPlataforma/app/Models/Consultorio.php` | `ocupados()` contaba todas las prácticas activas sin considerar el vencimiento de los 6 meses. | Filtra únicamente las prácticas cuya `fecha_termino > today()`, liberando la vacante inmediatamente al cumplirse los 180 días. |
| `CajBioBioPlataforma/app/Support/Notas.php` | Métodos numéricos estándar. | Añade `claveConcepto()`, `valorDesdeConcepto()` y constante estructurada `OPCIONES` para operar con calificaciones en palabras. |
| `CajBioBioPlataforma/app/Services/CartaPresentacionService.php` | No existía. | Servicio que genera el PDF formal con membrete institucional, datos del postulante, registro de práctica y advertencia de alegatos en Corte. |
| `CajBioBioPlataforma/resources/views/pdf/carta_presentacion.blade.php` | No existía. | Plantilla oficial de Carta de Presentación idéntica al formato institucional de la Corporación. |
| `CajBioBioPlataforma/app/Services/InformesPractica.php` | Solo permitía subida y consulta de informes. | Incorpora `observar()` para permitir al Abogado Jefe registrar observaciones de subsanación y emitir notificaciones urgentes. |
| `CajBioBioPlataforma/app/Http/Controllers/Tutor/TutorController.php` | Diseñado para tutor único de consultorio, con notas numéricas del 1 al 7. | Discrimina entre 1ª y 2ª instancia según `es_segunda_instancia`, implementa `evaluarSegundaInstancia()`, `observarInforme()`, visibilidad transversal regional y evaluación en palabras. |
| `CajBioBioPlataforma/app/Http/Controllers/Secretaria/PracticasController.php` | Sin gestión de carta de presentación ni carga asistida de 2ª instancia. | Métodos `descargarCartaPresentacion()`, `subirCartaPresentacion()`, `verCartaPresentacionFirmada()` y `calificarSegundaInstancia()`. |
| `CajBioBioPlataforma/routes/portales/tutor.php` | Rutas básicas de tutor. | Agrega rutas para `evaluacion.segunda_instancia` y `informe.observar`. |
| `CajBioBioPlataforma/routes/portales/secretaria.php` | Rutas estándar. | Agrega rutas de descarga, subida y visualización de Carta de Presentación, y carga asistida de 2ª instancia. |
| `CajBioBioPlataforma/resources/views/tutor/inicio.blade.php` | Vista rígida de «Mis postulantes». | Soporta doble vista: bandeja de consultorio para 1ª instancia o bandeja de Corte de Apelaciones con métricas regionales para 2ª instancia. |
| `CajBioBioPlataforma/resources/views/tutor/practicante.blade.php` | Formulario numérico con promedio decimal. | Rediseño integral: muestra calificaciones sólo en palabras, formularios independientes según instancia, cruce de permisos en solo lectura y subsanación de informe. |
| `CajBioBioPlataforma/resources/views/secretaria/practicas/ver.blade.php` | Ficha básica de práctica activa. | Tarjetas para descarga y subida de Carta de Presentación firmada, e interfaz para carga asistida de calificaciones de 2ª instancia. |
| `CajBioBioPlataforma/resources/views/layouts/portal.blade.php` | Rol genérico «Abogado tutor». | Badge y menú dinámicos: «Abogado Jefe Consultorio» vs «Abogado Jefe 2ª Instancia» (`Segunda Instancia (Corte)`). |
| `CajBioBioPlataforma/database/seeders/UsuariosDemoSeeder.php` | Solo abogados de consultorio. | Añade cuenta de demostración para Corte de Apelaciones (`corte_biobio@cajbiobio.cl`) con rol de 2ª instancia regional. |

---

## 4. Catálogo Oficial de Consultorios y Unidades Operativas (192 Sedes), Atributos de Contacto y Módulo de Gestión Administrativa

### 4.1 Contexto y Motivación
Se incorporó el catálogo oficial completo de las 192 unidades y sedes operativas reales de la Corporación de Asistencia Judicial del Biobío en las 6 regiones de su jurisdicción (Ñuble, Biobío, La Araucanía, Los Ríos, Los Lagos y Aysén), asegurando la preservación íntegra de toda su información institucional y operativa:
1. **Nuevos Atributos de Información Operativa:**
   * **Tipo de Unidad (`tipo_unidad`):** Clasificación institucional reglamentaria (`CJ` Consultorio Jurídico tradicional, `OJA` Oficina Jurídico Asistencial, `CAVI` Centro de Atención a Víctimas, `CMED` Centro de Mediación, `PMA` Programa Mi Abogado, `NAD` Niñez y Adolescencia Defendida, `ODL` Oficina de Defensa Laboral, `ODAM` Oficina de Defensa Adulto Mayor, `2DA INSTANCIA` Oficina de Segunda Instancia, `MOVIL` Unidad Móvil y `DG/DR` Dirección Regional/General).
   * **Teléfonos de contacto (`telefono`):** Registro de líneas fijas, anexos, celulares institucionales y notas de operatividad telefónica.
   * **Horario de atención al público (`horario_atencion`):** Detalle multilínea de los días y bloques horarios de atención matutina, vespertina y citaciones especiales.
   * **Correo electrónico institucional (`email`):** Casilla formal de cada unidad para comunicaciones operativas.
2. **Capacidad de Gestión para Administradores:**
   * La información de los consultorios no es estática: los administradores de la plataforma cuentan ahora con un módulo integral de administración (`admin.consultorios`) para:
     - Consultar y filtrar las 192 sedes por nombre, dirección, región, tipo de unidad institucional o estado de activación.
     - Dar de alta nuevas unidades y sedes operativas.
     - Modificar y actualizar en cualquier momento los datos de contacto, dirección, teléfonos, horarios de atención, correo electrónico, capacidad de cupos formativos y estados de habilitación.
     - Ajustar la capacidad máxima de postulantes de manera exacta o mediante controles incrementales rápidos.
3. **Flexibilización de Unicidad Territorial:**
   * Se ajustó el índice único de la tabla `consultorios` a `unique(['nombre', 'comuna_id'])`, permitiendo la coexistencia de unidades institucionales homónimas en comunas y regiones distintas (como la «Oficina de Segunda Instancia» presente en Chillán, Concepción, Temuco, Valdivia y Puerto Montt).

---

### 4.2 Componentes Afectados y Cambios Realizados

| Archivo | Comportamiento Anterior | Estado Actual |
| :--- | :--- | :--- |
| `CajBioBioPlataforma/database/migrations/2026_11_02_000002_agrega_datos_de_contacto_a_consultorios.php` | No existía. | Incorpora columnas `tipo_unidad`, `telefono`, `horario_atencion`, `email` y flexibiliza el índice único a `nombre + comuna_id`. |
| `CajBioBioPlataforma/database/seeders/ConsultoriosOficialesSeeder.php` | No existía. | Catálogo institucional con las 192 sedes reales, vinculadas a sus respectivas comunas y regiones. |
| `CajBioBioPlataforma/database/seeders/ReferenciasSeeder.php` | Solo poblaba 10 consultorios de prueba. | Invoca `ConsultoriosOficialesSeeder` para poblar el 100% de las unidades oficiales. |
| `CajBioBioPlataforma/app/Models/Consultorio.php` | Sin atributos de contacto ni métodos de tipo de unidad. | Añade casts numéricos, accesor `tipo_unidad_texto` y accesor `nombre_completo` para desambiguación en selectores. |
| `CajBioBioPlataforma/app/Http/Controllers/Admin/ConsultoriosController.php` | No existía (solo ajuste numérico de capacidad en `CapacidadController`). | Controlador completo para listar, filtrar, crear, ver detalle, editar datos de contacto y ajustar capacidad de sedes. |
| `CajBioBioPlataforma/routes/portales/admin.php` | Rutas restringidas a `/admin/capacidad`. | Registra grupo `/admin/consultorios` con rutas RESTful completas de administración. |
| `CajBioBioPlataforma/resources/views/admin/consultorios/index.blade.php` | No existía. | Vista de catálogo con buscador de texto, filtros por región, tipo de unidad y estado, tabla responsiva y métricas. |
| `CajBioBioPlataforma/resources/views/admin/consultorios/form.blade.php` | No existía. | Formulario para registrar y editar todos los campos de la sede con filtrado dinámico de comunas por región. |
| `CajBioBioPlataforma/resources/views/admin/consultorios/ver.blade.php` | No existía. | Ficha detallada de sede con datos de contacto, horario, cupos, postulantes asignados y abogados vinculados. |
| `CajBioBioPlataforma/resources/views/admin/_nav.blade.php` | Pestaña «Capacidad». | Pestaña «Consultorios y Sedes» con enlace directo a la gestión de sedes. |
| `CajBioBioPlataforma/resources/views/layouts/portal.blade.php` | Menú lateral básico de administrador. | Menú con acceso directo a «Consultorios y Sedes». |
| `CajBioBioPlataforma/resources/views/secretaria/consultorios/ver.blade.php` | Solo mostraba nombre y dirección. | Tarjeta con tipo de unidad, teléfonos, correo institucional y horario de atención al público. |
| `CajBioBioPlataforma/resources/views/secretaria/consultorios/index.blade.php` | Tarjetas básicas sin datos de contacto. | Incorpora badge de tipo de unidad y datos de contacto en las tarjetas regionales. |

---

## 5. Depuración de Postulación: Supresión de Condición de Alumno / Año de Carrera y Exigencia Obligatoria de Certificado de Egreso

### 5.1 Contexto y Motivación
Para iniciar la práctica profesional judicial en la Corporación de Asistencia Judicial es requisito reglamentario indispensable haber completado y aprobado el plan curricular de la carrera de Derecho (calidad de egresado/a). En consecuencia:
1. **Eliminación de "5° año" y selector de año de carrera:** La disyuntiva entre *"5° año"* y *"Egresado"* en el formulario era innecesaria y confusa, puesto que un estudiante en curso no puede iniciar la práctica. Se eliminó el campo y selector `anio_carrera` tanto del paso 3 del formulario de postulación como de la pantalla de corrección de datos del postulante (`postulante.datos`).
2. **Eliminación del Certificado de Alumno Regular:** Se suprimió la opción de cargar `alumno_regular` de las opciones de subida de archivos en toda la plataforma de postulación.
3. **Obligatoriedad del Certificado de Egreso:** El documento `certificado_egreso` se trasladó a `TIPOS_OBLIGATORIOS`. La plataforma valida su existencia en el paso 3 y bloquea el avance al paso 4 y al envío final si no ha sido adjuntado. Admite formatos PDF, JPG y PNG de hasta el límite configurado (`config('caj.max_upload_mb')`).
4. **Persistencia y Compatibilidad:** Al enviar la postulación, `PostulacionController::enviar()` fija automáticamente `'anio_carrera' => 'egresado'`, manteniendo la consistencia de la base de datos y garantizando que las vistas de secretaría y seguimiento muestren la condición reglamentaria *"Egresado/a"*.

---

### 5.2 Componentes Afectados y Cambios Realizados

| Archivo | Comportamiento Anterior | Estado Actual |
| :--- | :--- | :--- |
| `CajBioBioPlataforma/app/Http/Controllers/Postulacion/PostulacionController.php` | `TIPOS_OBLIGATORIOS` solo incluía cédula (frente y reverso); `TIPOS_OPCIONALES` incluía `alumno_regular` y `certificado_egreso`. Selector de `anio_carrera` obligatorio en `reglasPaso3()` y `CAMPOS_EDITABLES`. | `certificado_egreso` pasa a `TIPOS_OBLIGATORIOS` con validación de documento (PDF/JPG/PNG). Se elimina `alumno_regular` y la validación de `anio_carrera`. En `enviar()` se registra `'anio_carrera' => 'egresado'`. |
| `CajBioBioPlataforma/resources/views/postulacion/paso3.blade.php` | Pedía selector de "Año de carrera" (5° año / Egresado) y ofrecía subir certificado de alumno regular en documentos opcionales. | Se retira el campo de año de carrera. Se incorpora bloque destacado y obligatorio para adjuntar el Certificado de Egreso. Se elimina la subida de certificado de alumno regular. |
| `CajBioBioPlataforma/resources/views/postulacion/paso5.blade.php` | Mostraba en el resumen "Año de carrera: 5° año / Egresado" y listaba certificado de alumno regular en archivos. | Muestra "Condición académica: Egresado/a" y retira la etiqueta de alumno regular del desglose de adjuntos. |
| `CajBioBioPlataforma/resources/views/postulante/datos.blade.php` | Permitía al postulante editar su "Año de carrera" entre 5° año y Egresado. | Se elimina el selector `anio_carrera` del formulario de actualización de datos. |
| `CajBioBioPlataforma/resources/views/secretaria/_datos-postulante.blade.php` | Mostraba etiqueta "Año de carrera". | Actualizado a "Condición académica", mostrando por defecto "Egresado/a". |
| `CajBioBioPlataforma/resources/views/secretaria/historial/ver.blade.php` | Mostraba etiqueta "Año de carrera". | Actualizado a "Condición académica", con valor predeterminado "Egresado/a". |
| `CajBioBioPlataforma/resources/views/secretaria/practicas/ver.blade.php` | Mostraba etiqueta "Año de carrera". | Actualizado a "Condición académica", con valor predeterminado "Egresado/a". |
| `CajBioBioPlataforma/resources/views/secretaria/postulaciones/index.blade.php` | Columna universidad con subetiqueta dependiente de `anio_carrera`. | Utiliza valor predeterminado "Egresado/a" si el campo está vacío. |
| `CajBioBioPlataforma/tests/Feature/PostulacionTest.php` | Sin pruebas para el paso 3. | Incorpora pruebas que comprueban que el paso 3 exige obligatoriamente el Certificado de Egreso, no exige `anio_carrera` y avanza a paso 4. |
| `CajBioBioPlataforma/tests/Feature/EditarDatosPostulanteTest.php` | Pasaba `'anio_carrera'` en la carga de datos del postulante. | Actualizado para excluir `anio_carrera` del formulario de edición. |
| `CajBioBioPlataforma/docs/vistas/postulacion.md` | Documentaba `anio_carrera` y `alumno_regular` como opcional. | Refleja `certificado_egreso` como obligatorio y supresión de `alumno_regular` y `anio_carrera`. |

---

## 6. Arquitectura de Directores y Jefaturas: División de Facultades (Secretaría vs. Directores), Visación Regional (Fase 3), Revisión Colegiada UCOE (Biobío), Suscripción FirmaGob (Director General) y Despacho de Documentos Oficiales

### 6.1 Contexto y Motivación
Se implementó la estructura de facultades, perfiles y flujos jerárquicos de toma de decisión para las Jefaturas y Directores de la Corporación de Asistencia Judicial del Biobío:

1. **División de Facultades: Secretaría vs. Directores / Jefaturas:**
   * **Facultad Material y Operativa (Secretaría):** Corresponde a la Secretaría (Regional o Jurídica Central de Biobío), encargada de la operativa material del sistema: descargar borradores, gestionar firmas manuales o estampadas, cargar PDFs autorizados, registrar calificaciones asistidas y realizar la remisión electrónica de los legajos.
   * **Facultad Jerárquica y Legal de Fondo (Directores / Jefes):** Corresponde a las autoridades ejecutivas (Director Regional, Jefe de UCOE y Director General), quienes ejercen la representación institucional, validan formalmente las notas, auditan las evaluaciones y suscriben con Firma Electrónica Avanzada (FEA - FirmaGob) el cierre de la práctica.

2. **Diferenciación de los Tres Perfiles Directivos:**
   * **Director Regional (`director_regional` - Regiones Externas: NB, AR, LR, LL, AI):**
     - **Alcance territorial estricto:** Supervisa exclusivamente su región geográfica (`region_id`).
     - **Bandeja de Validación de Evaluaciones (Fase 3 Regional):** Examina los expedientes evaluados por los Abogados Jefes de consultorio y Corte de Apelaciones. Cuenta con acceso directo al Informe de Práctica del SAJ en PDF y visualización de las rúbricas y calificaciones en palabras (`Sobresaliente`, `Buena`, `Regular`, `Mala`).
     - **Potestad de Revisión, Modificación y Visación Directiva:** Posee la facultad legal expresa de ratificar la propuesta del Abogado Jefe o modificar la calificación final antes de remitir el expediente a Concepción. Al modificar la nota, el sistema actualiza la calificación, exige fundamentación obligatoria y deja trazabilidad completa en auditoría. La visación formal directiva (`validada_director_at`) es el requisito indispensable que habilita a la Secretaría Regional a remitir el expediente a Concepción.
     - **Seguimiento Regional y Exportación:** Monitoreo integral de la dotación y estado de los practicantes en su jurisdicción, con exportación de nóminas a Excel mediante OpenSpout.
     - **Directorio de Consultorios y Cupos:** Visualización de la capacidad, ocupación y disponibilidad formativa de sus sedes locales.
   * **Jefe de UCOE (`jefe_ucoe` - Región del Biobío — don Luis Núñez Oviedo):**
     - Ejerce funcionalmente las atribuciones de Director Regional para la Octava Región (Biobío).
     - **Identidad Documental de Jefatura:** Los documentos emitidos en su jurisdicción reflejan su cargo oficial: «Jefe de la Unidad de Consultorios y Oficinas Especializadas (UCOE)».
     - **Submódulo Exclusivo de Revisión Colegiada UCOE:** Facilita la distribución y reasignación equitativa (individual y masiva en lote) de expedientes evaluados entre los 3 revisores designados en la Región del Biobío: don Luis Núñez Oviedo, doña Verónica Cifuentes y doña Graciela Paredes, quienes cuentan con la potestad de examinar, validar o modificar las calificaciones de los expedientes asignados.
   * **Director General (`director_general` - Sede Central Concepción — don Mauricio Decap Fernández):**
     - **Alcance Nacional Corporativo:** Supervisa transversalmente las 6 regiones de la Corporación.
     - **Bandeja Nacional de Firma Final (FirmaGob):** Visualización centralizada de expedientes consolidados y auditados de todo el país.
     - **Emisión y Descarga Oficial de Documentos:** Descarga individual de Certificados Oficiales para la Corte Suprema y Resoluciones de Aprobación en PDF, así como descarga masiva en archivo ZIP para firma externa por lotes.
     - **Integración con FirmaGob (FEA) y Gatillador de Cierre:** El Director General sube el Certificado PDF ya firmado con Firma Electrónica Avanzada, registrando el folio y la URL de verificación pública de la firma.
     - **Efectos Vinculantes del Cierre Definitivo:** Al cargar el certificado suscrito con FirmaGob, el expediente cambia automáticamente al estado final `cerrada_historial`, se autoriza de inmediato la visualización y descarga de los certificados para el postulante en su portal (`documentos_autorizados = true`), y se envían las notificaciones institucionales de término formal de práctica.
     - **Supervisión Nacional y Exportación Consolidada:** Módulo de monitoreo de prácticas en las 6 regiones con exportación ejecutiva a Excel.

3. **Homologación con Documentos Oficiales Tipo y Escala en Palabras:**
   * Se ajustaron los generadores de documentos (`CertificadosPractica` y `CartaPresentacionService`) para reflejar dinámicamente las firmas, cargos y autoridades según la región de origen y la sede central, estipulando formalmente el cargo «Jefe de la Unidad de Consultorios y Oficinas Especializadas (UCOE)».
   * En todas las interfaces directivas, las calificaciones y rúbricas se exhiben exclusivamente en conceptos cualitativos reglamentarios en palabras: `Sobresaliente`, `Buena`, `Regular` y `Mala`.

---

### 6.2 Componentes Afectados y Cambios Realizados

| Archivo | Comportamiento Anterior | Estado Actual |
| :--- | :--- | :--- |
| `CajBioBioPlataforma/database/migrations/2026_11_02_000003_agrega_directores_y_firmagob_a_practicas.php` | No existía. | Añade a `practicas` las columnas `validada_director_at`, `validada_director_por`, `observaciones_director`, `revisor_colegiado_id`, `revisor_colegiado_nombre`, `firmado_director_general_at`, `firmado_director_general_por`, `firmagob_folio`, `firmagob_url`, `certificado_firmagob_id` con índices foráneos seguros. |
| `CajBioBioPlataforma/app/Models/Usuario.php` | No incluía roles directivos específicos. | Añade `director_regional`, `jefe_ucoe`, `director_general` a `ROLES` y `ROLES_GESTIONABLES`; define redirecciones de `portalInicio()` a `director.inicio` y `director_general.inicio`. |
| `CajBioBioPlataforma/app/Models/Practica.php` | Sin atributos directivos de visación ni firma digital. | Añade casts datetime y relaciones `validadaDirectorPor`, `revisorColegiado`, `firmadoDirectorGeneralPor` y `certificadoFirmagob`. |
| `CajBioBioPlataforma/app/Support/Scope.php` | No definía alcance para directores. | Fija alcance nacional (`null`) para `director_general` y alcance territorial regional para `director_regional` y `jefe_ucoe`. |
| `CajBioBioPlataforma/app/Http/Controllers/DocumentoController.php` | Permisos de visualización sin contemplar directores. | Permite descarga y visualización de documentos oficiales para `director_general`, `director_regional` y `jefe_ucoe`. |
| `CajBioBioPlataforma/app/Support/Notas.php` | Escala previa de 5 niveles numéricos/conceptuales. | Actualiza escala institucional a 4 conceptos en palabras: `Sobresaliente` (≥6.0), `Buena` (≥5.0), `Regular` (≥4.0) y `Mala` (<4.0). |
| `CajBioBioPlataforma/app/Http/Controllers/Director/DirectorRegionalController.php` | No existía. | Controlador directivo regional para revisión, validación y modificación de notas, seguimiento con Excel, capacidad de consultorios y distribución colegiada UCOE. |
| `CajBioBioPlataforma/app/Http/Controllers/Director/DirectorGeneralController.php` | No existía. | Controlador de Dirección General para bandeja nacional FirmaGob, descarga individual y en ZIP de certificados, registro de FEA y gatillador de cierre a `cerrada_historial`, supervisión nacional y catálogo de sedes. |
| `CajBioBioPlataforma/routes/portales/director.php` | No existía. | Rutas para el portal de Dirección Regional y Jefatura de UCOE (`director.*`). |
| `CajBioBioPlataforma/routes/portales/director_general.php` | No existía. | Rutas para el portal de Dirección General (`director_general.*`). |
| `CajBioBioPlataforma/routes/web.php` | No cargaba portales directivos. | Registra las rutas de `director.php` y `director_general.php`. |
| `CajBioBioPlataforma/resources/views/layouts/portal.blade.php` | Sin menús para directores. | Configura sidebar, títulos, badges y navegación exclusiva para `director_regional`, `jefe_ucoe` y `director_general`. |
| `CajBioBioPlataforma/resources/views/director/inicio.blade.php` | No existía. | Bandeja regional de validación con métricas, filtros, selección y visación masiva en lote, y calificaciones en palabras. |
| `CajBioBioPlataforma/resources/views/director/ver.blade.php` | No existía. | Ficha evaluativa con visor/enlace al Informe SAJ en PDF, rúbricas de 1ª y 2ª instancia, panel interactivo para validar o modificar la calificación final en la escala de 4 palabras (Sobresaliente, Buena, Regular, Mala), fundamentación obligatoria y trazabilidad. |
| `CajBioBioPlataforma/resources/views/director/seguimiento.blade.php` | No existía. | Grilla de seguimiento de prácticas regionales con filtros avanzados y botón de descarga en Excel. |
| `CajBioBioPlataforma/resources/views/director/consultorios.blade.php` | No existía. | Directorio regional de consultorios con indicadores de dotación, capacidad y disponibilidad de vacantes. |
| `CajBioBioPlataforma/resources/views/director/colegiada.blade.php` | No existía. | Panel exclusivo UCOE Biobío para distribución y reasignación de expedientes entre don Luis Núñez, doña Verónica Cifuentes y doña Graciela Paredes. |
| `CajBioBioPlataforma/resources/views/director_general/index.blade.php` | No existía. | Bandeja nacional de firma FirmaGob con indicadores, filtros por región, descarga de certificados en ZIP y enlaces de suscripción. |
| `CajBioBioPlataforma/resources/views/director_general/ver.blade.php` | No existía. | Ficha nacional con rúbrica, descarga de documentos oficiales y formulario de subida de FirmaGob con gatillador de cierre y autorización de certificados. |
| `CajBioBioPlataforma/resources/views/director_general/seguimiento.blade.php` | No existía. | Panel de supervisión de prácticas en las 6 regiones con exportación consolidada a Excel. |
| `CajBioBioPlataforma/resources/views/director_general/consultorios.blade.php` | No existía. | Catálogo nacional de las 192 sedes con métricas corporativas de ocupación y cupos. |
| `CajBioBioPlataforma/app/Services/CartaPresentacionService.php` | Generaba emisor estático. | Asigna dinámicamente como firmante a la Jefatura de UCOE («Jefe de la Unidad de Consultorios y Oficinas Especializadas (UCOE)») en Biobío y al Director Regional en las regiones externas. |
| `CajBioBioPlataforma/app/Services/CertificadosPractica.php` | Resolvía firmas estáticas. | Resuelve cargos y nombres reales del Director General y directores regionales; respeta el desbloqueo por `documentos_autorizados`. |
| `CajBioBioPlataforma/app/Http/Controllers/Secretaria/HistorialController.php` & `ver.blade.php` | Permitía remisión sin visación previa. | Bloquea el botón de remisión a Jurídica Central hasta que el expediente cuente con visación formal directiva (`validada_director_at`). |
| `CajBioBioPlataforma/database/seeders/UsuariosDemoSeeder.php` | Sin usuarios directivos. | Incorpora cuentas demo para Director General (`director_general@cajbiobio.cl`), Jefe de UCOE (`ucoe_biobio@cajbiobio.cl`), revisores colegiados y los 5 Directores Regionales externos. |

---

---

## 7. Arquitectura de Administración Central: Gobernanza Territorial, Gestión Exclusiva de Cupos, Rol Corrector Integral, Visor de Auditoría Inmutable, Métricas Globales y Exclusión de Alertas Masivas

### 7.1 Contexto y Motivación
El perfil de **Administrador General** corresponde al usuario central superior e independiente de las secretarías regionales y directores, concebido para brindar soporte técnico, corrección operativa y control macroinstitucional:

1. **Gestión Exclusiva de Cupos a Nivel Nacional:**
   * Es el único rol facultado en el sistema para aumentar o disminuir las vacantes de toda la red de consultorios jurídicos del país (192 sedes).
   * Atiende las solicitudes formales de ampliación o reducción enviadas externamente por los abogados jefes de consultorio.
   * **Bloqueo estricto de consistencia:** El sistema valida y bloquea cualquier intento de reducir la capacidad de una unidad por debajo de los postulantes que se encuentren activos en ella (`nueva_capacidad >= ocupados()`).

2. **Rol Corrector Integral sobre Expedientes y Prácticas:**
   * Actúa como supervisor técnico transversal para subsanar errores operativos de postulación o asignación cometidos en secretaría.
   * Accede a todos los registros del país sin restricciones territoriales de `Scope`.
   * Permite la edición directa de perfiles, correos, datos personales, universidad, fechas formativas, abogado tutor y sede asignada.
   * Cuenta con la atribución de realizar traslados inter-sedes y cancelaciones administrativas directas con registro de motivo y fundamentación.
   * Exporta nóminas completas de prácticas activas e históricas a Excel (.xlsx) con filtros avanzados por texto, región, consultorio y estado.

3. **Administración de Usuarios, Roles y Gobernanza Territorial:**
   * Crea y gestiona los accesos de las funcionarias de secretaría, abogados jefes, directores regionales y personal institucional.
   * Vincula territorialmente a cada secretaria con su respectiva región operativa.
   * **Prohibición de eliminación física (Ley N° 19.628):** Las cuentas de personal saliente o desvinculado se suspenden lógicamente (`activo = false`), resguardando de forma perpetua el historial institucional y la trazabilidad de auditoría.

4. **Mantenimiento Técnico y Configuración de Sistema:**
   * Supervisa los parámetros globales de la plataforma y exhibe prominentemente la etiqueta visible de la versión desplegada del software (`config('caj.version')`).

5. **Monitoreo Global, Estadísticas y Reportes:**
   * **Tasa de ocupación en tiempo real:** Supervisa la capacidad total, cupos ocupados, libres y porcentajes tanto a nivel consolidado nacional como desglosado por cada una de las 6 regiones.
   * **Métricas anuales de tiempos promedio de asignación:** Calcula la cantidad de días transcurridos desde el ingreso de la postulación hasta el inicio formal de la práctica, desglosado por año y por región.
   * **Métricas anuales de causales de cancelación:** Clasifica anualmente las cancelaciones por causales reglamentarias (`conducta`, `decision`, `error_registro`, `otro`).
   * Exportación consolidada de métricas e indicadores a archivo Excel (.xlsx).

6. **Visor del Log Inmutable de Auditoría:**
   * Módulo especializado (`admin.auditoria`) para consultar el historial inmodificable de eventos registrados en la tabla `auditoria`.
   * Permite filtrar por texto, tipo de acción, entidad afectada, usuario operador y rango de fechas.
   * Muestra dirección IP y despliega el contenido íntegro del payload JSON en un visor interactivo, con exportación a Excel.

7. **Delimitación Operativa de Alertas:**
   * A diferencia de los postulantes y los abogados jefes, el Administrador Central está **expresamente excluido** de la recepción de alertas automáticas masivas de término de prácticas (notificaciones del día 166 o fin de los 6 meses), evitando el colapso de su bandeja de entrada ante los cientos de expedientes activos.

---

### 7.2 Componentes Afectados y Cambios Realizados

| Archivo | Comportamiento Anterior | Estado Actual |
| :--- | :--- | :--- |
| `CajBioBioPlataforma/app/Models/Auditoria.php` | Modelo básico sin relación de usuario. | Añade relación `usuario()` a `Usuario`, cast datetime para `created_at` y soporte de consulta. |
| `CajBioBioPlataforma/app/Http/Controllers/Admin/AuditoriaController.php` | No existía. | Controlador del Visor de Auditoría Inmutable con filtros multicriterio, paginación y exportación a Excel con OpenSpout. |
| `CajBioBioPlataforma/app/Http/Controllers/Admin/EstadisticasController.php` | No existía. | Controlador de Métricas Globales: ocupación territorial, tiempos anuales de asignación, cancelaciones por causal y reporte Excel. |
| `CajBioBioPlataforma/app/Http/Controllers/Admin/PracticantesController.php` | Sin filtros regionales ni exportación a Excel. | Añade filtros por región, consultorio y estado, y método `exportar()` a Excel (.xlsx). |
| `CajBioBioPlataforma/app/Console/Commands/ActualizarEstadosPracticas.php` | Solo notificaba al tutor. | Incorpora alerta de día 166 al postulante y garantiza la exclusión expresa del Administrador de alertas masivas. |
| `CajBioBioPlataforma/routes/portales/admin.php` | Sin rutas de auditoría ni estadísticas. | Registra rutas para `/admin/auditoria`, `/admin/estadisticas` y exportación de postulantes. |
| `CajBioBioPlataforma/resources/views/admin/_nav.blade.php` | 4 pestañas de navegación. | Añade pestañas para «Métricas Globales» y «Log de Auditoría». |
| `CajBioBioPlataforma/resources/views/layouts/portal.blade.php` | Menú lateral acotado. | Incorpora «Métricas Globales» y «Log de Auditoría» en la navegación del Administrador. |
| `CajBioBioPlataforma/resources/views/admin/inicio.blade.php` | Resumen básico sin enlaces directos a métricas. | Tarjeta con versión de software, indicadores consolidados y accesos rápidos a estadísticas y auditoría. |
| `CajBioBioPlataforma/resources/views/admin/auditoria/index.blade.php` | No existía. | Visor del log inmutable con indicadores, filtros, badges por tipo de acción, visualizador de payload JSON y exportación. |
| `CajBioBioPlataforma/resources/views/admin/estadisticas/index.blade.php` | No existía. | Panel macroinstitucional con tablas de ocupación por región, tiempos de asignación y desglose de causales de cancelación. |
| `CajBioBioPlataforma/resources/views/admin/practicantes/index.blade.php` | Búsqueda por texto simple. | Barra de filtros avanzados (región, consultorio, estado), visualización de notas en palabras y botón de exportación a Excel. |

---

## 8. Seguridad, Búsqueda Avanzada y Modelo de Despacho al Portal (Hallazgos #18 al #21)

1. **Depuración de Parámetros Estadísticos (Hallazgo #18):**
   * Se eliminó la captura huérfana de `$anioFiltro` en `EstadisticasController::index`, consolidando el panel macroinstitucional en un visor histórico multianual completo.

2. **Exhaustividad en Filtros de Seguimiento Institucional (Hallazgo #19):**
   * Se incorporaron los estados `nivelada_biobio` y `rechazada_sancion` en los selectores visuales y en las listas blancas `in_array` de los controladores de seguimiento: `Admin\PracticantesController`, `DirectorRegionalController` y `DirectorGeneralController`.

3. **Búsqueda Multicriterio con Soporte de RUT (Hallazgo #20):**
   * En `Secretaria\PracticasController::consulta` y `Secretaria\HistorialController::index`, se habilitó la búsqueda por RUT tanto con formato institucional (`XX.XXX.XXX-X`) como por dígitos limpios, subsanando el desfase con el placeholder de la interfaz.

4. **Despacho Seguro al Portal y Descarga de Expediente Oficial (Hallazgo #21):**
   * **Modelo de Notificación en Portal Autenticado:** Para prevenir que los correos automáticos institucionales con documentos adjuntos sean clasificados como SPAM o rebotados por políticas DMARC/SPF en casillas externas de los postulantes, el cierre formal se ejecuta como **«Autorizar y despachar al portal del postulante»**.
   * **Descarga Institucional para la Secretaría:** Tanto la Secretaría Jurídica Central como las Secretarías Regionales disponen ahora de botones dedicados para descargar individualmente el Certificado Corte Suprema y la Resolución de Término, así como la descarga directa del **Expediente Completo en ZIP (`.zip`)**, permitiendo su remisión manual personalizada por correo electrónico si se requiere.

5. **Protección y Validación de Aprobación en Descargas de Secretaría (Hallazgo #N1):**
   * Se blindaron los 6 endpoints de descarga en `HistorialController` y `JuridicaBiobioController` (`descargarCertificado`, `descargarResolucion`, `descargarExpediente`), exigiendo la validación estricta de `CertificadosPractica::bloqueo($p)` previa a cualquier generación o entrega documental.
   * Se impidió la descarga o emisión indebida de certificados en expedientes cancelados, reprobados o rechazados por sanción disciplinaria, previniendo emisiones fraudulentas con la leyenda «HA APROBADO» y errores 500 por falta de evaluación.
   * En las vistas de secretaría regional y jurídica central, la botonera de descarga se condicionó exclusivamente a prácticas aprobatorias autorizadas.

6. **Consistencia Visual en Disponibilidad y Capacidad de Consultorios (Hallazgo #N2):**
   * Se ajustó `Practicas::nivelDisponibilidad` preservando compatibilidad completa con `'muchos'`, `'pocos'` y `'sin'`, incorporando además en `components/badge.blade.php` y `resources/css/app.css` las etiquetas y clases CSS para `'alta'`, `'media'`, `'baja'`, `.sec-barra.es-*` y `.barra-*`.
   * Se eliminó el riesgo de badges y barras desprovistas de color CSS o con etiquetas genéricas en las vistas de Consultorios y Asignación de Postulaciones.

7. **Gobernanza Institucional: Suscripción FirmaGob y Despacho Final por Jurídica (Hallazgo #N3):**
   * Se corrigió la descoordinación en `DirectorGeneralController::subirFirmaGob`, asegurando que la carga del documento firmado con FirmaGob estampe la firma FEA, actualice `firmado_director_general_at` y notifique a la Secretaría Jurídica Central sin mutar prematuramente el estado a `cerrada_historial`.
   * Se mantuvo como paso final vinculante el rol de la Secretaría Jurídica Central, permitiendo a sus evaluadores revisar el certificado suscrito y presionar el botón «Autorizar y despachar al portal del postulante» para culminar el ciclo reglamentario.

8. **Normalización de Etiquetas en Bandejas de Seguimiento Directivo (Hallazgo #N4):**
   * En `resources/views/director/seguimiento.blade.php` y `resources/views/director_general/seguimiento.blade.php`, se reemplazó `:texto="$estVisible"` por `:estado="$estVisible"`.
   * Se aseguró que `<x-badge>` resuelva las etiquetas institucionalmente amigables («Por terminar», «Remitida a Biobío», «Nivelada en Biobío», «Cerrada / Despachada») en lugar de slugs de base de datos sin formato.

9. **Desacoplamiento Estructural de Áreas y Especialidad en Consultorios (Hallazgo #N5):**
   * Se eliminó la rigidez del atributo `area` en la ficha del Abogado Jefe (`abogados.area`), trasladando conceptualmente la materia y especialidad a los Consultorios y Unidades Operativas (las 192 sedes ya tipificadas con `tipo_unidad` como `CJ`, `ODL`, `CAVI`, `PMA`, `2DA INSTANCIA`, `OJA`).
   * En `UsuariosController`, `UsuariosAdmin` y `resources/views/admin/usuarios/form.blade.php`, se retiró el selector forzado de materia y la validación obligatoria para abogados.
   * La Segunda Instancia queda desacoplada de cualquier área ficticia, gobernándose exclusivamente por el indicador institucional `es_segunda_instancia` y su `region_id`.
   * En el portal del tutor (`tutor/inicio.blade.php`) y en el listado de usuarios (`admin/usuarios/index.blade.php`), la cabecera y el detalle reflejan dinámicamente los consultorios reales asignados al profesional o su competencia ante la Corte de Apelaciones.
   * Se retiró el rol heredado `'gobernador'` de `Usuario::ROLES_GESTIONABLES`.

10. **Resolución de Relación Faltante `registradaPor` en Modelo `Calificacion` (Hallazgo #N6):**
   * En `DirectorRegionalController::ver` (utilizado por Directores Regionales y por la Jefatura / Revisores de UCOE Biobío al presionar «Ver legajo») y en `DirectorGeneralController::ver`, la carga impaciente incluye `'calificaciones.registradaPor'`.
   * El modelo `App\Models\Calificacion` no tenía definida la relación Eloquent hacia el usuario que registró la calificación, a pesar de existir la columna de clave foránea `registrada_por` en la tabla `calificaciones`.
   * Se definió la relación `public function registradaPor() { return $this->belongsTo(Usuario::class, 'registrada_por'); }` en `App\Models\Calificacion`, eliminando la excepción `RelationNotFoundException` y habilitando la apertura y visación del legajo en UCOE y Direcciones Regionales.
