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

11. **Normalización y Formateo Multilínea de Horarios de Consultorios (Hallazgo #N7):**
   * En el catálogo de sedes y consultorios (`consultorios.horario_atencion`), los horarios contenían secuencias de escape de texto literales `\n` o bloques de espaciado tabular producto de la carga inicial en comillas simples, provocando que en las vistas de Secretaría Regional y Administrador (`secretaria/consultorios/ver.blade.php`, `admin/consultorios/ver.blade.php`, `admin/consultorios/form.blade.php`) se mostraran los caracteres `\n` en pantalla en lugar de saltos de línea.
   * Se incorporó en el modelo `App\Models\Consultorio` un accessor `getHorarioAtencionAttribute` y un mutator `setHorarioAtencionAttribute` que normalizan automáticamente secuencias literales `\n`, saltos Windows/Mac y espaciados tabulares a saltos de línea reales `\n`.
   * Se creó la migración `2026_11_08_000001_normalizar_horario_atencion_consultorios.php` para sanear de forma no destructiva los registros existentes en base de datos.
   * En combinación con el estilo `white-space: pre-line` existente en las vistas, los horarios ahora se presentan estructurados por jornada y día de forma legible e impecable.

12. **Visualización de Disponibilidad y Cupos Ocupados/Totales en Postulación (Hallazgo #N8):**
   * En el flujo de postulación institucional (`PostulacionController::datosPasoConsultorios` y `datosResumen`), los postulantes no tenían visibilidad de la ocupación real ni de la capacidad de los consultorios antes de elegirlos como preferencias.
   * Se integró el cálculo eficiente y agregado de vacantes activas vía `Practica::query()->whereIn('estado', ['por_iniciar', 'activa'])->where('fecha_termino', '>', today())->groupBy('consultorio_id')->selectRaw('consultorio_id, count(*) as total')`, determinando para cada consultorio sus cupos ocupados, libres y nivel de disponibilidad institucional (`Practicas::nivelDisponibilidad`).
   * En el Paso 4 (`postulacion/paso4.blade.php`), se incorporó una leyenda informativa y se muestra en cada tarjeta de consultorio la cifra exacta (ej. `3 de 5 cupos ocupados (2 libres)`) junto con el badge institucional (`Muchos cupos`, `Pocos cupos` o `Sin cupos`).
   * En el Paso 5 (`postulacion/paso5.blade.php`) y en el portal del postulante (`postulante/inicio.blade.php`), el resumen de consultorios seleccionados mantiene la visibilidad de cupos ocupados y totales para máxima transparencia y orientación del postulante.

13. **Modificación de Criterios/Secciones de Nota por UCOE y Log de Auditoría Inmutable:**
   * Anteriormente, la Jefatura / Revisores de UCOE y Direcciones Regionales solo contaban con un selector para modificar el concepto final global (`concepto`), sin poder ajustar individualmente los 7 factores reglamentarios de desempeño de la rúbrica institucional.
   * Se modificó `DirectorRegionalController::validar` y la vista `resources/views/director/ver.blade.php` para incorporar una tabla interactiva con selectores individuales para cada uno de los 7 criterios reglamentarios (`conocimiento_juridico`, `responsabilidad`, `iniciativa`, `sentido_social`, `conducta`, `honorabilidad`, `asistencia_puntualidad`), sincronizada dinámicamente con el promedio ponderado y la calificación final en palabras.
   * Al guardar una modificación, se actualizan individualmente las columnas de criterios en el registro `evaluaciones`, así como el promedio, el veredicto de aprobación y la nota en `practicas` y `calificaciones`.
   * Toda modificación de criterios o nota final genera un registro estricto en el log inmutable de auditoría institucional (`Auditoria::registrar`), guardando bajo `ucoe.modificar_evaluacion` o `director.modificar_evaluacion` el detalle antes/después de cada criterio modificado, la nota anterior/nueva, el usuario responsable, la justificación obligatoria y la dirección IP.
   * Se incorporó en `director/ver.blade.php` una sección visible de historial de auditoría que detalla cronológicamente las revisiones y ajustes realizados sobre el expediente.

14. **Supervisión Nacional de Postulantes en Todos sus Estados y Exportación Oficial en Excel Multiejecutiva:**
   * Anteriormente, la pantalla de Supervisión Nacional de Postulaciones (`/secretaria/juridica/postulaciones`) estaba rígidamente limitada a postulaciones en estado `'pendiente'` (`where('estado', 'pendiente')`), impidiendo a la Secretaría Jurídica Central supervisar el ciclo de vida completo de los postulantes del país (activos en curso, finalizados, nivelados o cerrados con certificado).
   * Se refactorizó `JuridicaBiobioController::postulaciones` y `exportarPostulaciones` mediante la consulta transversal `queryPostulaciones(Request $request)` que carga impacientemente a los postulantes, sus consultorios, regiones, prácticas, rúbricas de evaluación y calificaciones, eliminando el filtro duro de pendientes.
   * Se incorporó un filtro multifuncional de estados en la interfaz (`resources/views/secretaria/juridica/postulaciones.blade.php`):
     * Agrupaciones macro: «En Proceso (Pendientes y En Curso)» y «Terminados / Evaluados».
     * Estados específicos: `pendiente`, `por_iniciar`, `activa`, `finalizada`, `remitida_biobio`, `nivelada_biobio`, `cerrada_historial`, `rechazada`, `cancelada` y `rechazada_sancion`.
   * Se rediseñó el panel de indicadores KPI superiores para reflejar el volumen nacional consolidado: Postulantes Totales, Pendientes en Espera, En Curso Activo, Terminadas / Evaluadas, Gestión Directa Biobío y Supervisión Regional.
   * Se actualizó la tabla institucional incorporando la columna de estado procesal con badges institucionales dinámicos (`<x-badge :estado="$estadoItem" />`) y el concepto de calificación reglamentario si ya fue evaluado, manteniendo enlaces contextuales al expediente (`secretaria.juridica.ver`), seguimiento de práctica o tramitación de postulación.
   * **Exportación Oficial en Excel (.xlsx) con Estructura Institucional de Dos Hojas:**
     * **Hoja 1 («En Proceso»):** Genera las 19 columnas reglamentarias para postulantes que aún no culminan su práctica: `Nº`, `NOMBRE`, `RUT`, `FECHA DE NACIMIENTO`, `DIRECCION`, `TELEFONO`, `E- MAIL`, `CONSULTORIO`, `REGIÓN`, `MES DE INSCRIPCION`, `FECHA DE INICIO`, `Nº REGISTRO`, `UNIVERSIDAD`, `FECHA DE CERTIFICADO`, `SITUACIÓN ACADEMICA`, `APROBADA`, `FECHA APROBACION`, `TÉRMINO PROVISORIO` y `OBSERVACIONES`.
     * **Hoja 2 («Finalizados con Evaluación»):** Genera las 19 columnas oficiales para egresados con práctica concluida, desglosando los 7 factores reglamentarios de la rúbrica institucional convertidos a conceptos en palabras (`Sobresaliente`, `Buena`, `Regular`, `Mala`): `NOMBRE`, `RUT`, `FECHA DE NACIMIENTO`, `TELEFONO`, `E- MAIL`, `CONSULTORIO`, `REGIÓN`, `MES`, `FECHA`, `N°REG`, `UNIVERSIDAD`, `CONOCIMIENTO Y CRITERIO`, `RESPONSABILIDAD`, `INICIATIVA`, `SENTIDO SOCIAL Y DE COLABORACION`, `CONDUCTA`, `HONORABILIDAD`, `ASISTENCIA Y PUNTUALIDAD` y `FECHA DE APROBACIÓN`.
     * Se habilitó tanto la descarga integrada del libro completo con ambas pestañas como la descarga selectiva de cada hoja individual (`hoja=proceso` y `hoja=terminados`), conservando de manera fidedigna los filtros activos en pantalla (`q`, `origen`, `region_id`, `estado`).
   * **Blindaje Estricto de Competencia Territorial y Etapa de Revisión:**
     * Se blindó la ficha de postulación (`resources/views/secretaria/postulaciones/ver.blade.php`), de modo que la secretaría solo puede tramitar y modificar admisiones si se cumplen copulativamente dos condiciones: (1) **Territorial:** Que el postulante pertenezca a su jurisdicción (Biobío para Jurídica Central o región propia para secretaría regional; las demás se bloquean en modo «Solo Lectura - Postulación Regional»), y (2) **Etapa procesal:** Que la postulación se encuentre estrictamente en etapa de revisión previa (`estado === 'pendiente'`). Si ya fue resuelta (`aceptada` o `rechazada`), se retiran los botones de acción y modales de aceptación/rechazo, desplegando la tarjeta informativa de «Estado de resolución» con fecha, revisor y enlace a la práctica en curso.

15. **Catálogo Oficial de Universidades y Selector Searchable con Filtrado Instantáneo:**
   * Anteriormente, la universidad del postulante en el Paso 3 del formulario de postulación (`resources/views/postulacion/paso3.blade.php`) y en la edición de datos personales (`resources/views/postulante/datos.blade.php`) era un campo de texto libre plano (`<input type="text">`), lo que generaba inconsistencias de nombres, abreviaciones heterogéneas y erratas en los reportes institucionales.
   * Se creó la clase de soporte `App\Support\Universidades` conteniendo las 54 opciones reglamentarias oficiales, abarcando todas las casas de estudios superiores de Chile (tradicionales, privadas, extintas con egresados vigentes y opción de contingencia «Otra institución no listada»).
   * Se diseñó el componente reutilizable `<x-selector-universidad>` con un combobox accesible y responsivo.
   * En `resources/js/postulacion.js` se implementó `iniciarSelectorUniversidad()` con filtrado en tiempo real (`input`), normalización de caracteres/tildes y soporte para búsqueda tanto por nombre completo como por abreviatura/sigla (ej. `UdeC`, `PUCV`, `UC`, `UCSC`, `UBB`, `USS`, `UACH`, `UAH`, etc.).
   * Se incorporó soporte integral de teclado (flechas arriba/abajo para navegar, Enter para seleccionar, Escape/Tab para cerrar) y cierre por clic exterior.
   * Ante la selección de «Otra institución no listada» o cuando el postulante cuente con una institución especial preexistente, se despliega automáticamente un campo secundario validado para registrar el nombre de la institución, asegurando trazabilidad fidedigna tanto en `PostulacionController` como en `PerfilController`.


16. **Re-postulación de Postulantes Cancelados o Rechazados sin Sobreescritura Histórica (Arquitectura No Destructiva):**
    * Se verificó y reforzó la arquitectura de datos respecto al ciclo de vida de los postulantes y practicantes:
      * En la base de datos, la relación entre un postulante (RUT) y sus postulaciones es de 1 a N (`Postulante::hasMany(Postulacion)`), al igual que entre un practicante y sus prácticas (`Practicante::hasMany(Practica)`).
      * Cada nueva postulación enviada a través de `PostulacionController::enviar` ejecuta un `Postulacion::create(...)`, originando un nuevo registro independiente en la tabla `postulaciones` con su propio ID y fecha de ingreso, sin sobreescribir ni mutar jamás las postulaciones previas.
      * Cuando una postulación es aceptada, se crea una nueva fila en `practicas` (`Practica::create(...)`) vinculada a la nueva postulación.
      * Si una práctica anterior fue cancelada por la secretaría o el tutor institucional (por conducta, renuncia/decisión del postulante o error administrativo), dicho expediente cancelado permanece inalterado e indexado de forma permanente en la base de datos, conservando su causal (`cancelacion_motivo`), fecha de cancelación (`cancelacion_fecha`), detalle (`cancelacion_detalle`), usuario responsable (`cancelada_por`), trazabilidad en el log de auditoría (`Auditoria::registrar('practica.cancelar', ...)`) y en el historial de prácticas de la secretaría.
    * En `PostulacionController::enviar`, se implementó la validación para impedir postulaciones concurrentes si el postulante ya cuenta con una práctica asignada o en curso (`por_iniciar`, `activa`, `remitida_biobio`, `nivelada_biobio`), permitiendo explícitamente re-postular a aquellos cuya práctica haya sido cancelada (`cancelada`) o cuya postulación previa haya sido rechazada (`rechazada`) o retirada (`retirada`).
    * En `PerfilController::inicio` y `resources/views/postulante/inicio.blade.php`:
      * Se integró la visualización clara del estado de cancelación previa: si la práctica asociada fue cancelada, el portal del postulante despliega una tarjeta explicativa con badge «Práctica Cancelada», fecha de cancelación, causal reglamentaria y sede anterior.
      * Se aclara explícitamente al postulante que sus antecedentes previos permanecen archivados y resguardados institucionalmente, habilitando el botón destacado «Postular nuevamente» (`route('postulacion.paso', 1)`) para reiniciar su proceso con una postulación limpia y con sus datos básicos pre-cargados.


17. **Detección y Visualización de Antecedentes de Cancelación Previa al Asignar Postulantes:**
    * **Objetivo:** Cuando un postulante que haya tenido una práctica previa cancelada (por cualquier motivo reglamentario: conducta, renuncia/decisión, error de registro u otro) vuelva a postular, la secretaría encargada de tramitar su admisión debe ser alertada de forma ostensible antes y durante el proceso de asignación de sede, conociendo con total claridad la causal y antecedentes de la cancelación.
    * **Cálculo y Modelado de Antecedentes:**
      * En `Postulante` y `Postulacion`, se incorporaron los métodos `practicasPreviasCanceladas()` y `tieneCancelacionPrevia()` para recuperar de manera retroactiva el historial de cancelaciones por RUT o relación postulante/practicante.
      * En `Practicas::MOTIVOS_CANCELACION` y `Practicas::textoMotivoCancelacion()`, se estandarizó la resolución textual de todas las causales reglamentarias (`conducta` -> «Conducta del postulante», `decision` -> «Decisión del postulante», `error_registro` -> «Error de registro administrativo», `otro` -> «Otro motivo»).
    * **Alertas en la Ficha de Tramitación y Asignación (`secretaria.postulaciones.ver`):**
      * **Cabecera:** Se añade el badge de alerta institucional `⚠️ CANCELACIÓN PREVIA REGISTRADA`.
      * **Panel Destacado de Antecedente Institucional:** Despliega una tarjeta roja/rosácea de advertencia destacando:
        * Causal reglamentaria en tipografía sobresaliente.
        * Fecha oficial de cancelación y tiempo transcurrido.
        * Sede / consultorio y región anterior asignada.
        * Abogado tutor institucional a cargo en dicha práctica.
        * Funcionario o autoridad que registró la cancelación.
        * Momento procesal (antes de iniciar vs. en el transcurso).
        * Detalle y justificación textual registrada al momento de cancelar.
      * **Tarjeta Lateral de Resolución:** Alerta resumida antes de presionar los botones de acción.
      * **Modal de Asignación de Consultorio (`modal-aceptar`):** Se insertó un banner de advertencia directamente dentro del modal donde se selecciona el consultorio, garantizando que la secretaría tenga presente el antecedente al confirmar el nuevo destino.
    * **Alertas en Listados de Postulaciones:**
      * En `resources/views/secretaria/postulaciones/index.blade.php` (Secretaría Regional) y `resources/views/secretaria/juridica/postulaciones.blade.php` (Supervisión Nacional Jurídica Central), se pre-consultan las cancelaciones por lote (`$cancelacionesPorRut`) para evitar consultas N+1 y se muestra un badge explicativo junto al nombre del postulante: `⚠️ Cancelación previa: [Motivo]` con tooltip informativo.


18. **Límite Máximo de 3 Consultorios en el Formulario de Postulación:**
    * **Objetivo:** Permitir que los postulantes elijan entre 1 y un máximo de 3 consultorios por orden de preferencia durante el Paso 4 del formulario de postulación.
    * **Validación Backend (`PostulacionController`):**
      * En `reglasPaso4()`, se actualizó la regla de validación de `consultorios` a `['required', 'array', 'min:1', 'max:3']`.
      * En `mensajes()`, se incorporó el mensaje reglamentario: `'consultorios.max' => 'Puede seleccionar como máximo 3 consultorios por orden de preferencia.'`.
      * En `datosPasoConsultorios()`, se acotó la carga de consultorios seleccionados a los primeros 3 (`array_slice(..., 0, 3)`).
    * **Interfaz de Usuario e Interactividad (`paso4.blade.php` y `postulacion.js`):**
      * En la vista `resources/views/postulacion/paso4.blade.php`, se actualizó el texto de ayuda explicativo y se incorporó un contador dinámico institucional de opciones elegidas (`X de 3 elegidos` / `3 de 3 (Máximo alcanzado)`).
      * Se diseñó un banner de advertencia visual (`cons-aviso-max`) que se despliega si el postulante intenta añadir un 4° consultorio, indicándole claramente que debe quitar uno de los ya seleccionados para agregar otro.
      * En `resources/js/postulacion.js`, la función `iniciarConsultorios()` controla activamente el límite (`MAX_CONSULTORIOS = 3`), impidiendo la inserción de más de 3 sedes, actualizando los badges de orden y ocultando automáticamente el aviso al deseleccionar una sede.


19. **Re-subida y Actualización de Cédula de Identidad y Certificado de Egreso en Edición de Postulación:**
    * **Objetivo:** Permitir que el postulante, mientras su postulación se encuentre en etapa de revisión (`pendiente` o `derivada`), pueda no solo editar sus datos personales y académicos, sino también volver a subir o actualizar sus documentos reglamentarios fundamentales: la cédula de identidad (lado frontal y lado reverso) y el certificado de egreso.
    * **Controlador y Lógica de Almacenamiento (`PerfilController`):**
      * En `PerfilController::datos`, se cargan y pasan a la vista los documentos adjuntos de la postulación indexados por tipo (`$postulacion->documentos->keyBy('tipo')`).
      * En `PerfilController::guardarDatos`, se implementó la validación multipart de archivos:
        * Cédula frontal y reverso: formatos `pdf,jpg,jpeg,png` hasta 5 MB.
        * Certificado de egreso: formatos `pdf,jpg,jpeg,png` hasta 8 MB (`config('caj.max_upload_mb')`).
      * Si el postulante adjunta un nuevo archivo para cualquiera de estos tipos, se almacena en el disco local (`Almacenamiento::guardar`), se actualiza la tupla correspondiente en la tabla `documentos` (o se crea si no existía), se elimina de forma limpia el archivo físico anterior para no dejar huérfanos, y se deja registro de auditoría institucional (`Auditoria::registrar('postulacion.actualizar_documento', ...)`).
      * Si se actualizan campos del perfil o archivos, se refresca la marca temporal `datos_actualizados_at = now()` en `postulantes` y se notifica a la secretaría correspondiente.
    * **Interfaz de Edición (`resources/views/postulante/datos.blade.php`):**
      * Se habilitó `enctype="multipart/form-data"` y `data-paso-archivos` en el formulario principal.
      * Se incorporaron dos nuevas secciones visibles:
        1. **Cédula de identidad:** Bloques para cédula frontal y cédula reverso mediante el componente reutilizable `<x-postulacion.cedula>`, mostrando el archivo cargado actualmente, enlace directo para visualizarlo en nueva pestaña, botones para subir un nuevo archivo o capturarlo directamente mediante cámara (integrado con el modal `<x-modal id="modal-camara">`).
        2. **Certificado de egreso:** Tarjeta informativa con el nombre del certificado de egreso actual, tamaño en KB, fecha de subida previa y enlace para verlo, junto con el selector de archivo `<x-campo type="file">` para reemplazarlo de forma opcional.


20. **Simplificación del Mensaje de Asignación de Consultorio en Secretaría:**
    * **Objetivo:** En el modal de asignación de consultorio (`resources/views/secretaria/postulaciones/ver.blade.php`), eliminar el texto redundante que indicaba que el postulante ya contaba con acceso activo con la contraseña creada al postular, manteniendo limpia y directa la explicación institucional de la acción.
    * **Cambio:** En la función interactiva `actualizar()`, el texto resultante para sedes propias quedó conciso: `Se acepta la postulación y se crea la práctica.`, omitiendo la mención accesoria de credenciales previas.


21. **Persistencia y Parametrización Manual de Folios Institucionales CAJ (Carta de Presentación y Resolución de Término):**
    * **Contexto y Problemática:**
      * El sistema calculaba los números de folio exclusivamente mediante un correlativo automático estimado (`17400 + $practica->id`), sin persistir ni permitir ingresar los folios oficiales reales de los archivos físicos e institucionales de la CAJ.
      * La Carta de Presentación oficial emitida por la Dirección de Acceso a la Justicia requiere el número de registro físico: *«El número de Registro en nuestros Archivos es el N. º 17.478.-»*.
      * El Certificado para la Corte Suprema de Justicia no posee número de folio en el texto reglamentario.
      * La Resolución de Término oficial que aprueba la práctica requiere ingresar y persistir manualmente:
        1. El número y año de la resolución exenta (ej. *«RESOLUCIÓN N.° 596/2026»*).
        2. El número y fecha del Oficio DAJ que fundamenta el considerando (ej. *«Oficio N.º 607/2026 del Director de Acceso a la Justicia de la Institución, de fecha 28 de julio de 2026»*).
    * **Modelo y Base de Datos:**
      * Se creó y ejecutó la migración `2026_11_08_000002_agrega_folios_institucionales_a_practicas.php`, añadiendo a la tabla `practicas`:
        * `folio_carta_presentacion` (`string`, 50, nullable): Número de registro en archivos para la Carta de Presentación.
        * `resolucion_numero` (`string`, 50, nullable): Número de la resolución de término.
        * `resolucion_anio` (`unsignedSmallInteger`, nullable): Año oficial de la resolución.
        * `oficio_daj_numero` (`string`, 50, nullable): Número de oficio de la Dirección de Acceso a la Justicia.
        * `oficio_daj_fecha` (`date`, nullable): Fecha del oficio DAJ.
        * `folio_caj` (`string`, 100, nullable): Folio estandarizado institucional en formato `CAJ-REG-AÑO-NNNN`.
      * En `app/Models/Practica.php`, se configuraron los casts de fecha y año (`oficio_daj_fecha => 'date'`, `resolucion_anio => 'integer'`), y se definieron los accessors:
        * `numero_registro_carta`: Devuelve el folio manual si está registrado; de lo contrario, aplica como fallback institucional seguro `number_format(17400 + $this->id, 0, '', '.')`.
        * `numero_resolucion_completo`: Resuelve la nomenclatura oficial `numero/anio`.
        * `folio_caj_calculado`: Resuelve dinámicamente el estándar `CAJ-{REGION}-{AÑO}-{ID_SECUENCIA}`.
    * **Generación de Documentos Oficiales en PDF:**
      * En `CartaPresentacionService.php`, se vinculó `$numeroRegistro = $p->numero_registro_carta`, asegurando que la carta tipo impresa refleje de inmediato el número manual guardado.
      * En `CertificadosPractica::pdfResolucion`, se sincroniza el número y año manual con la entidad `Certificado` (`numero_resolucion`, `anio`).
      * En `resources/views/pdf/resolucion.blade.php`:
        * Se parametrizó el encabezado: `RESOLUCIÓN N.° {{ $practica->resolucion_numero ? $practica->resolucion_numero . '/' . ($practica->resolucion_anio ?? $certificado->anio) : $certificado->numero_resolucion . '/' . $certificado->anio }}`.
        * En el `CONSIDERANDO`, se adaptó para estampar dinámicamente el memo DAJ: `Oficio @if($practica->oficio_daj_numero)N.º {{ $practica->oficio_daj_numero }}/{{ $practica->resolucion_anio ?? $certificado->anio }} @endif del Director de Acceso a la Justicia de la Institución, de fecha {{ $practica->oficio_daj_fecha ? $practica->oficio_daj_fecha->locale('es')->translatedFormat('d \d\e F \d\e Y') : $fechaRes }}...`.
    * **Módulos de Gestión e Interfaz de Usuario:**
      * **Secretaría Regional (`resources/views/secretaria/practicas/ver.blade.php`):** Se incorporó un bloque con formulario para registrar/actualizar el N.º de Registro en Archivos (`folio_carta_presentacion`) con botón «Guardar Folio», y se permitió actualizarlo también al momento de subir la carta firmada (`subirCartaPresentacion`).
      * **Secretaría Jurídica Central (`resources/views/secretaria/juridica/ver.blade.php`):** Se añadió el botón «Folios y Correlativos Institucionales», una tarjeta resumen de trazabilidad de folios oficiales y el modal interactivo `modal-editar-folios` para configurar N.º de resolución, año, N.º de oficio DAJ, fecha de oficio y registro de carta.
      * **Dirección General (`resources/views/director_general/ver.blade.php`):** Se desplegó en la tarjeta de documentos generados el N.° de resolución y oficio vigentes, integrando el modal `modal-folios-dg` para ajustar dichos correlativos antes de suscribir la resolución oficial con FirmaGob.
      * **Historial Regional (`app/Http/Controllers/Secretaria/HistorialController.php`):** Se habilitó el endpoint `guardarFolios` para permitir corregir o ingresar folios a expedientes históricos.
      * **Exportación Nacional a Excel (`JuridicaBiobioController.php`):** En las dos hojas del reporte («En Proceso» y «Finalizados con Evaluación»), las columnas `Nº REGISTRO` y `N°REG` leen y priorizan los folios reales persistidos (`resolucion_numero`, `folio_carta_presentacion` y `numero_registro_carta`).


22. **Disponibilidad de la Carta de Presentación Oficial Firmada en el Portal del Abogado Tutor:**
    * **Objetivo:** Una vez que la Secretaría Regional carga la Carta de Presentación firmada en el sistema para dar inicio oficial a la práctica, dicho documento debe quedar inmediatamente disponible para el Abogado Tutor / Abogado Jefe asignado en la sección del postulante correspondiente.
    * **Rutas y Controlador (`routes/portales/tutor.php` y `app/Http/Controllers/Tutor/TutorController.php`):**
      * Se crearon las rutas protegidas `GET /tutor/practicantes/{practica}/carta-firmada.pdf` y `GET /tutor/postulantes/{practica}/carta-firmada.pdf` (`route('tutor.practicantes.carta_firmada', $practica)`).
      * En `TutorController::verCartaFirmada`, se implementó la entrega del PDF (`response()->file(...)`) con validación de seguridad de alcance: solo el tutor formalmente asignado al consultorio del practicante (o tutor regional de 2ª instancia) puede acceder al documento.
    * **Interfaz de Usuario del Abogado Tutor:**
      * **Cabecera de la ficha del practicante (`resources/views/tutor/practicante.blade.php`):** Se incorporó el botón destacado `Ver Carta Firmada (PDF)` cuando `$practica->carta_presentacion_firmada` está presente.
      * **Ficha del postulante (`resources/views/tutor/practicante.blade.php`):** Se agregó un bloque informativo dentro de la tarjeta de datos personales indicando el estado oficial, la fecha y hora de subida por secretaría, el número de folio en archivos institucionales y un botón directo de descarga/visualización.
      * **Bandeja de postulantes (`resources/views/tutor/inicio.blade.php`):** En la tabla de postulantes, la columna de estado incorpora el badge interactivo `✓ Carta firmada` con enlace directo al documento PDF.


23. **Remediación de Seguridad Crítica: Prevención de Sobrescritura de Cuentas, Degradación de Rol y Enumeración de Cuentas Institucionales:**
    * **Vulnerabilidad Previa:**
      * En `PostulacionController::enviar`, cuando un usuario no autenticado completaba el formulario público `/postular`, el sistema buscaba si existía un usuario con el RUT ingresado (`Usuario::where('rut', $rut)->first()`). Si existía, actualizaba sin autenticación previa sus credenciales (`email`, `password`), forzaba `'rol' => 'postulante'` e iniciaba sesión de inmediato con `Auth::login($usuario)`.
      * Esto posibilitaba el secuestro de cuentas de postulantes previos y el sabotaje o bloqueo de cuentas de funcionarios institucionales (Administrador, Directores, Secretarias, Abogados), degradando sus privilegios y privándolos de acceso a sus paneles.
    * **Corrección Aplicada:**
      1. **Validación Preventiva en Paso 2 (`reglasPaso2`):**
         * Si el usuario no está autenticado (`! auth()->check()`), se valida que el RUT normalizado no exista previamente en la tabla `usuarios`.
         * Se valida que el correo electrónico no exista en la tabla `usuarios` (`Rule::unique('usuarios', 'email')`).
         * Si el usuario está autenticado (postulante logueado realizando una repostulación legítima), se omite la restricción de RUT (el cual proviene inmutable de su sesión activa) e ignora su propio ID en la verificación de correo (`Rule::unique('usuarios', 'email')->ignore(auth()->id())`).
      2. **Neutralización de Divulgación de Información y Enumeración (Information Disclosure / Anti-Enumeration):**
         * Bajo ninguna circunstancia se emiten mensajes que distingan roles institucionales (ej. *«El RUT ingresado corresponde a un usuario institucional...»*).
         * Se estandarizó un mensaje de error estrictamente uniforme y genérico para cualquier cuenta preexistente: *«Ya existe una cuenta registrada con este RUT. Si ya tiene cuenta en la plataforma, por favor inicie sesión.»* y *«Ya existe una cuenta registrada con este correo electrónico. Por favor inicie sesión o utilice otra dirección de correo.»*. De este modo, un atacante no puede inferir si un RUT o correo corresponde a un funcionario administrativo o a un postulante.
      3. **Blindaje Defensivo en Transacción de Envío (`enviar`):**
         * Se eliminó definitivamente el bloque `$usuario->update([... 'rol' => 'postulante'])`.
         * Para solicitudes no autenticadas, se verifica defensivamente antes de la transacción que ni el RUT ni el correo existan en `usuarios`.
         * Solo se realiza `Usuario::create(...)` para nuevos postulantes genuinos. Si el postulante ya está autenticado, se utiliza `$usuario = $request->user()` preservando íntegramente sus credenciales, perfil y privilegios.


24. **Remediación de Confianza Irrestricta en Proxies (`trustProxies`) y Prevención de Suplantación de IP:**
    * **Vulnerabilidad Previa:**
      * En `bootstrap/app.php`, la instrucción `$middleware->trustProxies(at: "*")` instruía a Laravel a confiar ciegamente en cabeceras `X-Forwarded-For` de cualquier origen, permitiendo a clientes externos falsificar su IP de origen en la tabla inmutable de auditoría (`Auditoria::registrar()`) y eludir las restricciones de límite de peticiones (`throttle:10,1` en `/ingresar` y `/postular/enviar`) rotando direcciones ficticias.
    * **Corrección Aplicada:**
      * Se eliminó el comodín irrestricto `*` en `bootstrap/app.php`.
      * Se parametrizó la lista de proxies de confianza mediante la variable de entorno `TRUSTED_PROXIES`, aplicando un fallback seguro a direcciones locales (`['127.0.0.1', '::1']`) si no se define.
      * Se especificaron las cabeceras exactas de reenvío admitidas (`HEADER_X_FORWARDED_FOR`, `HOST`, `PORT`, `PROTO`, `AWS_ELB`).
      * Se documentó y agregó `TRUSTED_PROXIES` en `.env.example` y `.env` con guía para entornos con Nginx, Docker o balanceadores externos.
      * La corrección es completamente transparente para los usuarios finales y no altera ni restringe el acceso de ningún postulante ni funcionario desde internet o redes externas.


25. **Remediación de Autorización Basada en Texto Plano y Eliminación de Rol Secretaria en Portal Directivo:**
    * **Vulnerabilidad Previa:**
      * En `DirectorRegionalController::autorizar` y `routes/portales/director.php`, se permitía el acceso al portal directivo a cualquier usuario con rol `secretaria` de la Región del Biobío cuyo nombre o apellido contuviera `'cifuentes'` o `'paredes'` mediante `str_contains()`.
      * Esto provocaba escalada de privilegios indebida (cualquier secretaria con esos apellidos adquiría facultades de Director Regional para visar y modificar calificaciones) y al mismo tiempo bloqueaba a cualquier nueva funcionaria revisora de UCOE con apellido diferente.
    * **Corrección Aplicada:**
      * En `routes/portales/director.php`, se eliminó el rol `'secretaria'` del middleware de rutas directivas, quedando estrictamente limitado a `middleware('rol:director_regional,jefe_ucoe,administrador')`.
      * En `DirectorRegionalController::autorizar`, se eliminó la comprobación de texto plano sobre nombres/apellidos, gobernando el acceso de forma pura mediante RBAC (`rolesPermitidos = ['director_regional', 'jefe_ucoe', 'administrador']`).
      * En `DirectorRegionalController::colegiada`, se eliminó la condición de secretaria y se dinamizó la lista de revisores consultando los usuarios activos con rol `jefe_ucoe` en la base de datos (`Usuario::where('rol', 'jefe_ucoe')`), eliminando el hardcodeo de nombres en controladores, validaciones y vistas.
      * En `buscarRevisorUsuario`, `asignarRevisor` y `asignarRevisoresLote`, la resolución y validación de revisores se realiza contra la entidad `Usuario` con rol `jefe_ucoe`/`administrador`, permitiendo que cualquier nuevo/a funcionario/a asignado a UCOE desde el panel administrativo quede automáticamente operativo/a sin tocar código.
      * En `resources/views/director/colegiada.blade.php`, se dinamizó el selector de filtrado y el texto descriptivo para reflejar al equipo colegiado sin atarse a apellidos específicos.


26. **Consistencia de Permisos en DocumentoController para Secretaría Biobío (Jurídica Central):**
    * **Problema Previo:**
      * En `DocumentoController::puedeVer`, para el rol `'secretaria'` se exigía estrictamente que la región del documento coincidiera con la región del usuario (`$regionDoc === $u->region_id`).
      * Sin embargo, las secretarias de la Región del Biobío (Sede Central Concepción) ejercen el rol unificado de Secretaría Jurídica Central (`Scope::esBiobioCentral($u)`). Al revisar expedientes remitidos desde otras regiones de la macrozona (Ñuble, Araucanía, Los Ríos, Los Lagos o Aysén) en `/secretaria/juridica` o `/secretaria/historial`, el sistema arrojaba un error 404 al intentar abrir o descargar los documentos adjuntos (cédulas de identidad, certificados de egreso, etc.) por pertenecer a otra región.
    * **Corrección Aplicada:**
      * En `DocumentoController::puedeVer`, se integró `Scope::esBiobioCentral($u)` para el rol `'secretaria'`, permitiendo a las secretarias de la Sede Central Biobío visualizar y descargar documentos de expedientes remitidos desde cualquier región.
      * Se preservó la restricción territorial para las secretarias de regiones externas (Ñuble, Araucanía, Los Ríos, Los Lagos, Aysén), quienes continúan limitadas estrictamente a los documentos pertenecientes a su propia jurisdicción regional.
      * En `Scope::esBiobioCentral`, se robusteció la comprobación para evaluar tanto la relación cargada `$u->region` como el identificador directo `$u->region_id` contra la Región del Biobío (`BI`).


27. **Remediación de Enumeración de Usuarios, Bloqueo Inducido (DoS) y Registro de Auditoría en Autenticación:**
    * **Vulnerabilidad Previa:**
      * En `LoginController::entrar`, el sistema entregaba mensajes diferenciados ante credenciales erróneas, cuentas desactivadas o cuentas bloqueadas, permitiendo enumerar si un RUT existía y en qué estado se encontraba.
      * No existía trazabilidad en la tabla `auditoria` para los intentos fallidos de autenticación ni para los eventos de bloqueo.
      * El bloqueo por 5 intentos fallidos operaba de forma global sobre la columna `usuarios.bloqueado_hasta` sin discriminar la IP de origen, permitiendo a un atacante conociendo el RUT público de un funcionario (Director, Administrador, etc.) enviar 5 peticiones erróneas cada 15 minutos para mantener permanentemente denegado el acceso al usuario legítimo (Account Lockout DoS).
    * **Corrección Aplicada:**
      * **Prevención de Enumeración (Anti-Enumeration):** Se unificó el mensaje de error ante cualquier fallo de credenciales: `'RUT o contraseña incorrectos.'`. El estado de cuenta desactivada solo se revela si el usuario proporciona la contraseña correcta, imposibilitando inferir la existencia del usuario mediante ataques de diccionario o fuerza bruta.
      * **Protección contra DoS por Bloqueo Inducido:** Se implementó control de tasa mediante `RateLimiter` acoplado al par `RUT + IP`. Cuando una IP particular supera 5 fallos para un RUT determinado, esa IP es bloqueada temporalmente durante 15 minutos. Si el funcionario legítimo se conecta desde otra dirección de red e ingresa su contraseña válida, el sistema autentica exitosamente al usuario y limpia cualquier contador de fallos provocado por el atacante.
      * **Auditoría Completa de Seguridad:** Se añadieron registros inmediatos e inmutables en `auditoria`:
        * `auth.login_fallido`: Registra cada fallo de ingreso con el RUT, motivo interno (`usuario_no_encontrado`, `password_incorrecta`, `cuenta_desactivada`), IP, User-Agent y número de intentos.
        * `auth.bloqueo`: Registra el momento en que se activa el bloqueo temporal por límite de intentos.
        * `auth.login_bloqueado`: Registra intentos subsecuentes provenientes de IPs restringidas.


28. **Cifrado de Sesiones en Base de Datos e Inyección Global de Cabeceras HTTP de Seguridad:**
    * **Vulnerabilidad Previa:**
      * En `.env` y `config/session.php`, la directiva `SESSION_ENCRYPT=false` provocaba que la carga útil (`payload`) de las sesiones en la tabla `sessions` —que almacena borradores temporales con datos sensibles como RUT, dirección, antecedentes de salud y contactos de emergencia— se guardara en Base64 sin encriptar, vulnerable ante accesos no autorizados a la base de datos o volcados de respaldo.
      * No existía un middleware que inyectara cabeceras de endurecimiento HTTP en vistas HTML y rutas web, exponiendo la plataforma a ataques de clickjacking, robo de sesión en iframes y sniffing de tipos MIME.
    * **Corrección Aplicada:**
      * **Cifrado Fuerte de Sesiones:** Se activó `SESSION_ENCRYPT=true` en `.env`, `.env.example` y por defecto en `config/session.php`. Laravel cifra y descifra automáticamente todo el contenido de la tabla `sessions` con AES-256-CBC utilizando la clave `APP_KEY`.
      * **Middleware Global de Seguridad (`CabecerasSeguridad`):** Se creó `app/Http/Middleware/CabecerasSeguridad.php` y se registró globalmente mediante `$middleware->append(CabecerasSeguridad::class)` en `bootstrap/app.php`.
      * **Cabeceras HTTP Inyectadas en Toda Respuesta:**
        * `X-Frame-Options: SAMEORIGIN`: Bloquea la incrustación en iframes de terceros para prevenir Clickjacking (CWE-1021).
        * `X-Content-Type-Options: nosniff`: Fuerza al navegador a respetar estrictamente los tipos MIME y previene ataques de MIME-sniffing (CWE-79).
        * `Referrer-Policy: strict-origin-when-cross-origin`: Impide la filtración de URLs internas en enlaces externos salientes.
        * `Permissions-Policy: camera=(), microphone=(), geolocation=()`: Restringe APIs sensibles del navegador.

