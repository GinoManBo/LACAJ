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
6. **Sistema de Calificación Exclusivamente en Palabras:**
   * Toda la captura y visualización de notas en la plataforma (tanto para 1ª como para 2ª instancia) se efectúa en conceptos reglamentarios en palabras: `Sobresaliente`, `Muy Buena`, `Buena`, `Regular` y `Deficiente`, suprimiendo la exposición de números decimales a los usuarios.

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

## 6. Próximas Refactorizaciones (Pendientes)

*(Espacio reservado para documentar las siguientes fases de refactorización según se vayan abordando).*
