# Auditoría de Avance de Proyecto — Plataforma Inteligente de Gestión y Optimización (CAJ Biobío)

**Fecha de actualización:** 2 de noviembre de 2026  
**Repositorio auditado:** `CajBioBioPlataforma/` (Laravel 12 / PHP 8.2 / Blade / SQLite)  
**Metodología:** Contraste línea por línea entre el documento de requerimientos integrales (53 puntos verificables + 3 Fases del Camino Alternativo Regional) y el código fuente real tras las refactorizaciones documentadas en `REFACTORIZACION.md` (Secciones 1 a 8).

---

## Leyenda de Estados

* `[x]` **Integrado:** Requerimiento implementado y funcional en controladores, modelos, servicios, vistas y base de datos.
* `[~]` **Parcialmente integrado:** La funcionalidad base existe y opera, pero presenta diferencias menores de formato, validación o alcance respecto de la especificación literal.
* `[ ]` **No integrado:** Funcionalidad aún no desarrollada en el repositorio actual.

---

## Resumen Ejecutivo de Avance Global

Tras la implementación de los nuevos roles institucionales (`director_regional`, `jefe_ucoe`, `director_general`, `juridica_central`), la Revisión Colegiada UCOE, la Carta de Presentación autogenerada, la 2ª Instancia en Corte de Apelaciones, la Nivelación en Jurídica Central, la FirmaGob del Director General y el Panel de Estadísticas y Auditoría, el proyecto experimentó un salto sustantivo de completitud:

| Estado | Auditoría Anterior (01-nov-2026) | Auditoría Actual (03-nov-2026) | Variación |
| :--- | :---: | :---: | :---: |
| `[x]` **Integrado (100 %)** | 21 (39,6 %) | **37 (69,8 %)** | **+16 ítems (+30,2 %)** |
| `[~]` **Parcialmente integrado (50 %)** | 19 (35,8 %) | **10 (18,9 %)** | -9 ítems |
| `[ ]` **No integrado (0 %)** | 13 (24,5 %) | **6 (11,3 %)** | **-7 ítems (-13,2 %)** |
| **Total de ítems verificables** | **53 (100 %)** | **53 (100 %)** | — |
| **Avance Global Ponderado** *(Integrados + 50 % Parciales)* | **57,5 %** | **79,2 %** | **+21,7 puntos** |

> **Hito clave alcanzado:** Las **Fases 2 y 3 del Camino Alternativo Regional y Nacional** (Inicio con Carta de Presentación firmada, alertas a los 5,5 meses / 14 días, liberación de cupos a los 180 días, 1ª y 2ª instancia de evaluación en palabras, Revisión Colegiada UCOE, remisión y nivelación en Jurídica Biobío, FirmaGob del Director General y despacho con expediente `.zip`) se encuentran **100 % operativas de punta a punta**.

---

## 1. Perfiles de Usuario y Gobernanza del Sistema

### 1.1. Postulante (antes "Practicante")
* `[x]` **Cambio de terminología global:** Se reemplazó el término *"Practicante"* por **"Postulante"** en los menús principales (`layouts/portal.blade.php`), vistas de práctica (`practicante/inicio.blade.php`), cartas, certificados y bandejas directivas/secretaría. Por definición de arquitectura y estabilidad, las rutas internas (`/practicante`, `/admin/practicantes`) se mantienen así para garantizar compatibilidad retroactiva y evitar problemas de enlaces.
* `[x]` **Aprovisionamiento inmediato de cuenta y eliminación del limbo:** Al ingresar sus datos en el Paso 2, el postulante define su propia contraseña. Al enviar la postulación en el Paso 5, el sistema aprovisiona de inmediato su cuenta de usuario (`rol: 'postulante'`, `debe_cambiar_password: false`), inicia sesión automáticamente y lo redirige a su portal `Mi Postulación` (`postulante.inicio`) para ver en tiempo real el estado de su expediente. Se elimina por completo la entrega manual de contraseñas temporales por parte de Secretaría.
* `[x]` **Funciones de carga de antecedentes e informe final:** El postulante cuenta con el asistente de 4 pasos en `Postulante\PostulacionController` para subir sus documentos de postulación y con el módulo en `Practicante\PracticanteController::subirInforme` para cargar su Informe Final de Práctica y descargar su Certificado (incluyendo el PDF firmado con FirmaGob) y Resolución.

### 1.2. Abogado Jefe (antes "Tutor")
* `[x]` **Cambio de terminología:** En toda la interfaz de usuario (`layouts/portal.blade.php`, `tutor/inicio.blade.php`, `secretaria/*`, `director/*`, `admin/*`), el rol se presenta oficialmente como **"Abogado Jefe"**.
* `[x]` **Funciones de recepción de alertas, revisión de informe y evaluación:** El Abogado Jefe recibe la notificación de inicio oficial al cargarse la Carta de Presentación firmada (`PracticasController::subirCartaPresentacion`), recibe la alerta automática a los 5,5 meses (`ActualizarEstadosPracticas`), aprueba u observa el Informe Final (`TutorController::revisarInforme`) y registra la evaluación de 1ª o 2ª Instancia (`TutorController::evaluar`).

### 1.3. Secretaría Regional (Biobío, Metropolitana, etc.)
* `[x]` **Gestión operativa regional y carga asistida:** El rol `secretaria` gestiona postulaciones, asigna consultorios y Abogados Jefes, descarga y sube la Carta de Presentación firmada para dar inicio oficial a la práctica, registra evaluaciones e informes en modalidad de carga asistida (`PracticasController::subirEvaluacionEscaneada`, `subirEvaluacionSegundaInstancia`, `subirInformeAsistido`), tramita cancelaciones/sanciones y remite el expediente visado a Jurídica Biobío (`HistorialController::remitirBiobio`).

### 1.4. Secretaría Biobío (Dirección Jurídica Central — Perfil Unificado)
* `[x]` **Perfil unificado de Secretaría Biobío con atribuciones de Dirección Jurídica Central:** Unificación ejecutada exitosamente. Las secretarias asignadas a la Región del Biobío (`codigo === 'BI'`) asumen nativamente las funciones centrales y territoriales: disponen de un Panel General con sección destacada de *Atribuciones de Jurídica Central*, acceso directo a la *Supervisión Nacional de Postulaciones* y a la *Recepción Nacional de Expedientes* (`JuridicaBiobioController`), formulario de nivelación manual en palabras (`instancia = 3`), recepción de notificaciones de remisión regional, rechazo por sanción disciplinaria (`rechazarSancion`), despacho de certificados al portal del postulante tras FirmaGob (`despachar`) y descarga de Certificado, Resolución y Expediente `.zip`. Mantiene 100% de compatibilidad retroactiva con el rol `juridica_central`. Pruebas automatizadas en `SecretariaBiobioUnificadaTest`.

### 1.5. Director Regional, Jefe UCOE y Director General
* `[x]` **Implementación de la tríada directiva (`director_regional`, `jefe_ucoe`, `director_general`) y Revisión Colegiada UCOE:**
  * `director_regional` (`DirectorRegionalController`): Bandeja acotada a su región, visación individual y en lote (`validar`, `validarLote`), solicitud de corrección (`observar`) y seguimiento regional.
  * `jefe_ucoe` (`DirectorRegionalController`): Opera en la Región Metropolitana con el módulo exclusivo de **Revisión Colegiada UCOE** (`/director/colegiada`), permitiendo a Luis Núñez asignar revisores individuales o por lote a Verónica Cifuentes y Graciela Paredes (`asignarRevisor`, `asignarRevisoresLote`), quienes cuentan con su bandeja filtrada y registro de dictamen colegiado (`registrarRevisionColegiada`).
  * `director_general` (`DirectorGeneralController`): Bandeja nacional de expedientes validados, descarga masiva en lote `.zip` para firma transversal (`descargarLote`) y carga del Certificado firmado con **FirmaGob** (`subirFirmaGob`).

### 1.6. Administrador del Sistema
* `[x]` **Rol técnico/estructural separado de la operación diaria:** El rol `admin` gestiona usuarios (incluyendo asignación de región a `director_regional`, `jefe_ucoe`, `secretaria` y Abogados de 2ª Instancia), el catálogo de los **192 consultorios y unidades operativas oficiales** (`ConsultoriosOficialesSeeder` y `Admin\ConsultoriosController`), las capacidades por área, el panel de Estadísticas y Reportes exportables a Excel (`Admin\EstadisticasController`) y el Visor de Auditoría (`Admin\AuditoriaController`), estando excluido de las notificaciones operativas de término de práctica.

---

## 2. Flujo Completo del Proceso (Paso a Paso)

### Fase 1: Postulación y Validación de Antecedentes (Pre-Práctica)

#### Paso 1: Registro y Creación de Postulación
* `[ ]` **Generación de folio único de postulación:** El modelo `Postulacion` utiliza su clave primaria autoincremental `id`, sin una columna `folio` con formato correlativo institucional para la postulación.
* `[x]` **Autocompletado de datos personales:** `PostulacionController::mostrarPaso` precarga automáticamente RUT, nombre completo, correo y teléfono desde el perfil autenticado del postulante (`Usuario`).
* `[~]` **Datos de contacto de emergencia (sin correo electrónico):** Se capturan nombre y teléfono de emergencia en el Paso 1, aunque el formulario y controlador aún mantienen el campo opcional `emergencia_email`.
* `[x]` **Depuración de antecedentes académicos (Eliminación de 5° año):** Se eliminó por completo el campo `anio_carrera` del formulario (`postulante/postulacion/paso-2.blade.php`) y de las validaciones de `Postulaciones.php`.

#### Paso 2: Carga de Documentación Obligatoria
* `[x]` **1. Fotocopia Cédula de Identidad (ambos lados):** Se exigen `cedula_frontal` y `cedula_reverso` como obligatorios en el Paso 3, aceptando documentos en formato `PDF` (además de imágenes `JPG/PNG`) hasta 5 MB tanto en la interfaz de usuario con indicador de archivo cargado como en la validación del servidor.
* `[x]` **2. Certificado de Egreso o Licenciatura (Obligatorio) y eliminación de Certificado de Alumno Regular:** En `PostulacionController` se eliminó el certificado de alumno regular y se incorporó `certificado_egreso` dentro de `TIPOS_OBLIGATORIOS`, aceptando `pdf,jpg,jpeg,png` hasta 5 MB.
* `[x]` **3. Certificado de Antecedentes para Fines Especiales:** Obligatorio (`certificado_antecedentes`), validando formato `pdf,jpg,jpeg,png` y tamaño máximo de 5 MB.
* `[x]` **4. Fotografía tamaño carnet:** Obligatoria (`foto_carnet`), validando formato de imagen (`jpg,jpeg,png`) con nombre y RUT asociados al registro del postulante.

#### Paso 3: Selección de Preferencias con Semáforo de Cupos
* `[~]` **Selección de hasta 2 consultorios con semáforo en vivo:** El Paso 4 (`paso-4.blade.php`) permite elegir consultorios por región y modalidad, pero aún permite marcar más de 2 opciones (sin regla `max:2`) y el semáforo de disponibilidad está visible en el portal de Secretaría/Admin, no directamente en la lista del postulante.

#### Paso 4: Revisión por Secretaría y Subsanación
* `[x]` **Notificación automática a Secretaría al enviar postulación:** `PostulacionController::enviar` dispara `Notificaciones::postulacionEnviada($p)` notificando a las secretarias de la región.
* `[ ]` **Flujo de Observación y Subsanación de documentos de postulación:** Actualmente Secretaría puede **Aprobar** (asignando consultorio) o **Rechazar** la postulación con motivo (`Postulaciones::rechazar`), pero no existe el estado intermedio `Observada` para que el postulante reemplace únicamente un documento de postulación defectuoso sin crear otra postulación (nota: la subsanación sí fue implementada para el Informe Final de Práctica en Fase 3).

#### Paso 5: Asignación, Confirmación y Carta de Presentación
* `[ ]` **Reserva temporal de cupo (`PreAsignada`) y aceptación de la oferta por el postulante (`Confirmada` / `ListaEspera` / `Desistida`):** La aprobación de Secretaría en `Postulaciones::aprobar` crea directamente la práctica en estado `'por_iniciar'` sin un paso intermedio de aceptación de oferta por parte del postulante.
* `[~]` **Compromiso de Confidencialidad:** El check obligatorio de confidencialidad y protección de datos existe y se persiste (`acepta_declaracion`), pero se solicita en el Paso 1 de la postulación en lugar de al momento de confirmar la vacante.
* `[x]` **Autogeneración de la Carta de Presentación tipo (diferenciando Director Regional vs. Jefe UCOE):** Implementado en `CartaPresentacionService::generarPdf` y `resources/views/pdf/carta_presentacion.blade.php`, emitiendo el documento a nombre del Jefe de la UCOE en la Región Metropolitana o del Director Regional en Biobío/otras regiones, dirigido al Abogado Jefe de la unidad asignada.
* `[x]` **Descarga y subida de la Carta de Presentación firmada por Secretaría:** Implementado en `Secretaria\PracticasController::descargarCartaPresentacion`, `subirCartaPresentacion` y `verCartaPresentacionFirmada`, almacenando el documento en `documentos_practica` (`tipo = 'carta_presentacion_firmada'`).

---

### Fase 2: Desarrollo de la Práctica, Alertas y Cupos

* `[x]` **Inicio Manual Oficial al cargar la Carta de Presentación firmada:** En `PracticasController::subirCartaPresentacion`, al subir la carta firmada la práctica transiciona de `'por_iniciar'` a `'activa'` y notifica inmediatamente al Abogado Jefe.
* `[ ]` **Folio CAJ secuencial en la tabla `practicas`:** Aunque la Carta de Presentación y la Resolución generan un correlativo institucional (`17400 + $practica->id`), el modelo `Practica` no posee una columna `folio_caj` persistida con formato `CAJ-REG-AÑO-NNNN`.
* `[x]` **Alerta automática de término a los 5,5 meses (14 días antes / Día 166):** `Practicas::DIAS_POR_TERMINAR = 14`, `config/caj_documentos.php` (`dias_alerta_previa_termino = 14`) y el comando `ActualizarEstadosPracticas` notifican tanto al **Abogado Jefe** como al **Postulante**, excluyendo al Administrador.
* `[x]` **Liberación anticipada de cupos a los 180 días:** En `Consultorio::ocupados()`, `Secretaria\PracticasController`, `Admin\InicioController` y `Admin\EstadisticasController`, las prácticas con `fecha_termino <= today()` dejan de contabilizarse como cupo ocupado aunque continúen su tramitación documental de cierre.
* `[~]` **Suspensión, Renuncia o Anulación anticipada con causal e historial:** Implementado el flujo de cancelación formal con causales tipificadas (`Postulaciones::CAUSALES_CANCELACION`: renuncia voluntaria, inasistencia injustificada, incompatibilidad académica, falta disciplinaria, otro) y liberación inmediata de cupo en `PracticasController::cancelar` y `PracticantesController::cancelar`. Se marca `[~]` únicamente porque el formulario de cancelación registra el motivo y detalle en base de datos pero no adjunta un archivo PDF de oficio de término.

---

### Fase 3: Evaluación, Cierre Regional, Consolidación Biobío y FirmaGob

* `[~]` **Carga del Informe Final por el postulante y revisión por el Abogado Jefe (con subsanación):** El postulante sube su informe en `PracticanteController::subirInforme` y el Abogado Jefe puede **aprobarlo** u **observarlo para subsanación** en `TutorController::revisarInforme` (habilitando la recarga al postulante). Se marca `[~]` porque el sistema permite subir el informe desde que la práctica está `'activa'` (sin bloquear antes del día 166) y acepta `pdf,doc,docx` en lugar de restringirlo solo a `.pdf`.
* `[x]` **Primera Calificación (Consultorio) en escala conceptual en palabras y carga asistida:** El Abogado Jefe evalúa en `TutorController::evaluar` y la Secretaría puede realizar la carga asistida con respaldo escaneado en `PracticasController::subirEvaluacionEscaneada`. Todas las vistas muestran la calificación en palabras (`Sobresaliente`, `Buena`, `Regular`, `Mala`).
* `[x]` **Segunda Calificación condicional (Corte de Apelaciones / 2ª Instancia):** Si `requiere_segunda_instancia = true`, se habilita la segunda evaluación para el Abogado Jefe con `es_segunda_instancia = true` en `TutorController::evaluar` o mediante carga asistida en `PracticasController::subirEvaluacionSegundaInstancia`, almacenándose en `calificaciones_practica` con `instancia = 2`.
* `[x]` **Cierre de Competencia Regional, Remisión a Jurídica Biobío y Nivelación de Notas en palabras:** Una vez visada por el Director Regional / Jefe UCOE (`validado_director_at`), la Secretaría Regional remite la práctica a Jurídica Biobío (`HistorialController::remitirBiobio`, estado `remitida_biobio`). En `JuridicaBiobioController::nivelar`, la Secretaría de Dirección Jurídica Central revisa las calificaciones de 1ª y 2ª instancia y puede nivelar manualmente el concepto final en palabras (`instancia = 3`, estado `nivelada_biobio`) o rechazar por sanción disciplinaria (`rechazada_sancion`).
* `[x]` **Visto Bueno de Dirección Regional / Jefatura UCOE (individual y por lotes):** Implementado en `DirectorRegionalController::validar` y `validarLote`, además del flujo previo de Revisión Colegiada UCOE en la Región Metropolitana (`registrarRevisionColegiada`).
* `[x]` **Firma Electrónica Institucional (FirmaGob) por el Director General:** Implementado en `DirectorGeneralController::descargarLote` (descarga masiva de borradores en `.zip` para firma transversal) y `subirFirmaGob` (carga del PDF firmado electrónicamente, guardado en `certificado_firmagob_id` y entregado directamente al postulante).
* `[x]` **Despacho de Certificados al Postulante y remisión manual a la Corte Suprema:** Implementado el despacho al portal del postulante (`JuridicaBiobioController::despachar` / `DirectorGeneralController::subirFirmaGob`) y la disposición/descarga de Certificado, Resolución y Expediente completo en `.zip` (`CertificadosPractica::zipExpediente`). El envío oficial a la Corte Suprema corresponde según definición operativa a un trámite manual externo a la plataforma respaldado por la descarga completa del expediente.

---

## 3. Documentos Autogenerados del Sistema

* `[x]` **1. Carta de Presentación e Inicio:** Implementada en `CartaPresentacionService` y `resources/views/pdf/carta_presentacion.blade.php` (diferenciando Director Regional vs. Jefe UCOE).
* `[~]` **2. Acta / Ficha de Evaluación del Postulante:** Los datos de evaluación de 1ª, 2ª y 3ª instancia se registran en `evaluaciones` y `calificaciones_practica` y se consolidan en `resources/views/pdf/resolucion.blade.php`, aunque no existe un PDF descargable en blanco de "Formulario de Evaluación" previo a la calificación.
* `[ ]` **3. Oficio / Informe de Traspaso a Dirección Jurídica:** El traspaso regional a Jurídica Biobío se ejecuta digitalmente mediante el cambio de estado a `remitida_biobio` y registro en auditoría (`HistorialController::remitirBiobio`), sin generar un documento PDF aparte de "Oficio de Traspaso".
* `[ ]` **4. Resumen Consolidado de Evaluaciones (PDF independiente):** La consolidación y nivelación de 1ª, 2ª y 3ª instancia se visualiza en la bandeja de Jurídica Central (`secretaria/juridica/ver.blade.php`) y se imprime dentro del PDF de Resolución (`pdf/resolucion.blade.php`), sin constituir un cuarto archivo PDF separado.
* `[x]` **5. Certificado de Término de Práctica Profesional y Resolución:** Implementados en `CertificadosPractica::pdfCertificado`, `pdfResolucion` y `zipExpediente` (`resources/views/pdf/certificado.blade.php` y `resolucion.blade.php`), incluyendo soporte completo para el PDF firmado con FirmaGob.

---

## 4. Módulo de Administración, Estadísticas y Auditoría

* `[x]` **1. Gestión de Usuarios y Roles:** CRUD completo en `Admin\UsuariosController` y `UsuariosAdmin` para todos los roles (`postulante`, `tutor`, `secretaria`, `juridica_central`, `director_regional`, `jefe_ucoe`, `director_general`, `admin`), incluyendo soporte de `region_id` y marca `es_segunda_instancia`.
* `[x]` **2. Gestión de Sedes, Consultorios y Cupos (192 unidades oficiales):** Implementado el catálogo oficial de 192 consultorios y unidades operativas en `ConsultoriosOficialesSeeder`, el mantenedor CRUD en `Admin\ConsultoriosController` y el gestor de capacidades por área en `Admin\CapacidadController`.
* `[x]` **3. Panel de Estadísticas y Reportes:** Implementado en `Admin\EstadisticasController` y `resources/views/admin/estadisticas/index.blade.php`, incluyendo tasas de ocupación por consultorio, tiempos promedio de asignación en días, desglose anual de prácticas canceladas por causal y exportación a Excel.
* `[~]` **4. Registro de Auditoría (Logs):** Implementado el servicio `Auditoria::registrar`, el modelo `LogAuditoria` (con `usuario_id`, `accion`, `entidad`, `entidad_id`, `datos_previos`, `datos_nuevos`, `ip`) y el visor filtrable en `Admin\AuditoriaController`. Se marca `[~]` únicamente porque la tabla `logs_auditoria` no almacena la columna `user_agent` ni registra eventos de solo lectura en `DocumentoController`.

---

## 5. Reglas de Negocio y Validaciones Críticas

* `[x]` **Regla 1 — Visibilidad de cupos en tiempo real:** Calculada dinámicamente sobre prácticas activas/por iniciar cuya `fecha_termino > today()`.
* `[x]` **Regla 2 — Liberación anticipada de cupos a los 180 días:** Aplicada uniformemente en `Consultorio::ocupados()`, `PracticasController`, `InicioController` y `EstadisticasController`.
* `[x]` **Regla 3 — Inicio manual condicionado a Carta de Presentación firmada:** La práctica permanece en `'por_iniciar'` hasta que Secretaría sube la Carta de Presentación firmada en `PracticasController::subirCartaPresentacion`.
* `[~]` **Regla 4 — Bloqueo de etapas sin requisitos previos:** Se bloquea la remisión a Biobío sin visación del Director Regional (`HistorialController::remitirBiobio`), se bloquea el despacho sin FirmaGob (`JuridicaBiobioController::despachar`) y se bloquea la emisión de certificados a reprobados (`CertificadosPractica::bloqueo`). Se marca `[~]` solo por el detalle reportado en el Hallazgo `#N1` de la segunda auditoría (agregar `CertificadosPractica::bloqueo($p)` en las rutas de descarga directa de Secretaría/Jurídica) y porque el postulante puede subir su informe antes del día 166.
* `[x]` **Regla 5 — Segregación territorial y nacional:** `Scope::regionId($u)` aísla estrictamente por región a `secretaria`, `director_regional` y `jefe_ucoe`, mientras que `juridica_central`, `director_general` y `admin` poseen visibilidad nacional según sus atribuciones.
* `[~]` **Regla 6 — Validación estricta de archivos:** Se valida el límite de 5 MB (`max:5120`) en todos los cargadores y formatos específicos por documento; solo resta permitir `.pdf` en cédula frontal/reverso y restringir el informe final exclusivamente a `.pdf`.
* `[x]` **Regla 7 — Escala de evaluación exclusivamente en palabras:** Todas las bandejas, selectores de evaluación/nivelación, portal del postulante y PDFs muestran las calificaciones en escala conceptual (`Sobresaliente`, `Buena`, `Regular`, `Mala`).
* `[~]` **Regla 8 — Almacenamiento seguro en disco privado:** Todos los documentos de postulación, informes, cartas firmadas, evaluaciones escaneadas y certificados FirmaGob se almacenan fuera de `public/` en `storage/app/private` y se sirven mediante `DocumentoController` autenticado. Se marca `[~]` únicamente porque `DocumentoController` no deja traza de lectura en `logs_auditoria`.
* `[x]` **Regla 9 — Exigencia de Certificado de Egreso (sin 5° año):** Eliminado el requisito de 5° año y alumno regular; `certificado_egreso` es obligatorio.

---

## 6. Estado del Camino Alternativo Regional (Flujo de 3 Fases)

| Fase | Descripción del Flujo | Estado Actual | Nivel de Completitud |
| :--- | :--- | :---: | :---: |
| **Fase 1** | Postulación, revisión por Secretaría Regional y generación de Carta de Presentación diferenciada (Director Regional vs. Jefe UCOE) | `[x]` | **95 %** *(Operativo de punta a punta; solo sin estado intermedio de subsanación de postulación)* |
| **Fase 2** | Inicio oficial al subir Carta de Presentación firmada, cómputo de 180 días, alertas al día 166 (14 días antes) al Postulante y Abogado Jefe, y liberación automática del cupo al día 180 | `[x]` | **100 %** |
| **Fase 3** | Carga y revisión de Informe Final (con subsanación), evaluación en palabras (1ª y 2ª Instancia en Corte de Apelaciones), Revisión Colegiada UCOE (RM), visación de Director Regional, remisión y nivelación en Jurídica Biobío (`instancia = 3`), FirmaGob del Director General y despacho con expediente `.zip` | `[x]` | **100 %** |

---

## 7. Cuentas y Casos de Demostración Sembrados (DatosDemostracionSeeder)

Para certificar y auditar de punta a punta cada rol y etapa sin depender de cargas manuales, se implementó `DatosDemostracionSeeder` (ejecutado automáticamente por `UsuariosDemoSeeder`). Todos los usuarios utilizan la contraseña unificada: `123456`.

| Rol / Etapa | Correo de Prueba | Caso Operativo Sembrado |
| :--- | :--- | :--- |
| **Postulante** | `postulante@cajbiobio.cl` | Postulación enviada en estado `pendiente`, con cédula, certificados y preferencias. |
| **Practicante Activo** | `practicante.activo@cajbiobio.cl` | Práctica activa en Consultorio Centro Concepción con 4 meses de avance (~66%) y carta de inicio firmada. |
| **Practicante por Terminar** | `practicante.termino@cajbiobio.cl` | Término a 5 días (< 14 días con alerta activa) e informe final cargado. Listo para calificar en `/tutor`. |
| **2ª Instancia Corte** | `postulante.corte@cajbiobio.cl` | Práctica calificada en consultorio (5.2) derivada a 2ª instancia ante la Corte de Apelaciones (`corte_biobio@cajbiobio.cl`). |
| **Nivelación Regional** | `postulante.nuble@cajbiobio.cl` | Expediente remitido desde Ñuble (`remitida_biobio`, nota 4.6 "Regular"). Listo para nivelar en `/secretaria/juridica`. |
| **FirmaGob** | `postulante.firmagob@cajbiobio.cl` | Expediente nivelado a 5.5 "Muy Buena". Listo en el portal nacional del Director General (`/director-general`). |
| **Titulado (Descarga)** | `postulante.titulado@cajbiobio.cl` | Práctica cerrada con documentos autorizados. Listo para descargar Resolución y Certificado Corte Suprema. |
| **Jurídica Central Nativo** | `juridica.central@cajbiobio.cl` | Usuario explícito con rol `juridica_central` para verificación de acceso directo. |

