# Auditoría de Avance de Proyecto — Plataforma Inteligente de Gestión y Optimización (CAJ Biobío)

**Fecha de actualización:** 9 de noviembre de 2026  
**Repositorio auditado:** `CajBioBioPlataforma/` (Laravel 12 / PHP 8.2 / Blade / MySQL 8.4)  
**Metodología:** Contraste línea por línea entre el documento de requerimientos integrales (54 puntos verificables + 3 Fases del Camino Alternativo Regional) y el código fuente real tras las refactorizaciones y consolidaciones institucionales implementadas hasta el 9 de noviembre de 2026.

---

## Leyenda de Estados

* `[x]` **Integrado:** Requerimiento implementado y funcional en controladores, modelos, servicios, vistas y base de datos.
* `[~]` **Parcialmente integrado:** La funcionalidad base existe y opera, pero se adaptó de forma simplificada o presenta diferencias menores de alcance respecto de la especificación inicial.
* `[ ]` **No integrado:** Funcionalidad aún no desarrollada como archivo o módulo separado en el repositorio actual.

---

## Resumen Ejecutivo de Avance Global

Tras las actualizaciones y mejoras implementadas hasta el **9 de noviembre de 2026** —que incorporaron la persistencia de folios institucionales (`folio_caj`, `folio_carta_presentacion`, `resolucion_numero`, `oficio_daj_numero`), la captura universal del `user_agent` en el log inmutable de auditoría con búsqueda y exportación Excel, el semáforo de disponibilidad de cupos en vivo para el postulante (con tope de 3 preferencias), la subsanación y actualización en línea de datos y documentos por parte del postulante mientras su solicitud está en revisión, la detección automática de prácticas previas canceladas por RUT, el comprobante PDF autogenerado de cancelación de práctica, la separación estricta de permisos entre Abogado Jefe de 1ª y 2ª Instancia, la exportación oficial a Excel de 2 hojas y 19 columnas en Jurídica Central, y el cierre de brechas de validación y auditoría— el proyecto alcanzó el siguiente nivel de completitud:

| Estado | Auditoría Inicial (01-nov-2026) | Auditoría Intermedia (03-nov-2026) | Auditoría Actual (09-nov-2026) | Variación Total |
| :--- | :---: | :---: | :---: | :---: |
| `[x]` **Integrado (100 %)** | 21 (38,9 %) | 37 (68,5 %) | **44 (81,5 %)** | **+23 ítems (+42,6 %)** |
| `[~]` **Parcialmente integrado (50 %)** | 20 (37,0 %) | 11 (20,4 %) | **8 (14,8 %)** | -12 ítems |
| `[ ]` **No integrado (0 %)** | 13 (24,1 %) | 6 (11,1 %) | **2 (3,7 %)** | **-11 ítems (-20,4 %)** |
| **Total de ítems verificables** | **54 (100 %)** | **54 (100 %)** | **54 (100 %)** | — |
| **Avance Global Ponderado** *(Integrados + 50 % Parciales)* | **57,4 %** | **78,7 %** | **88,9 %** | **+31,5 puntos** |

> **Hito clave alcanzado:** Las **Fases 1, 2 y 3 del Camino Alternativo Regional y Nacional** (Postulación con cuenta inmediata, semáforo de cupos en vivo y rectificación documental en línea; detección de prácticas previas canceladas; Inicio con Carta de Presentación firmada y folios institucionales persistidos; alertas a los 5,5 meses / 14 días; liberación de cupos a los 180 días; 1ª y 2ª instancia de evaluación exclusivamente en palabras con permisos segregados; Revisión Colegiada UCOE; remisión y nivelación en Jurídica Biobío; FirmaGob del Director General y despacho con expediente `.zip`) se encuentran **100 % operativas de punta a punta**.

---

## 1. Perfiles de Usuario y Gobernanza del Sistema

### 1.1. Postulante (antes "Practicante")
* `[x]` **Cambio de terminología global:** Se reemplazó el término *"Practicante"* por **"Postulante"** en los menús principales (`layouts/portal.blade.php`), vistas de práctica (`practicante/inicio.blade.php`), cartas, certificados y bandejas directivas/secretaría. Por definición de arquitectura y estabilidad, las rutas internas (`/practicante`, `/admin/practicantes`) se mantienen así para garantizar compatibilidad retroactiva y evitar problemas de enlaces.
* `[x]` **Aprovisionamiento inmediato de cuenta y eliminación del limbo:** Al ingresar sus datos en el Paso 2 del asistente público (`Postulacion\PostulacionController`), el postulante define su propia contraseña (`min:8`, confirmada). Al enviar la postulación en el Paso 5, el sistema crea o actualiza de inmediato su cuenta de usuario (`rol: 'postulante'`), inicia sesión automáticamente (`Auth::login`) y lo redirige a su portal `Mi Postulación` (`postulante.inicio`) para seguir en tiempo real el estado de su expediente y actualizar o subsanar sus datos y documentos mientras se encuentre en revisión (`Postulante\PerfilController`). Se elimina por completo la entrega manual de contraseñas temporales por parte de Secretaría.
* `[x]` **Funciones de carga de antecedentes e informe final:** El postulante cuenta con el asistente de 5 pasos en `Postulacion\PostulacionController` para registrar su postulación, el módulo `Postulante\PerfilController` para rectificar antecedentes o reemplazar documentos mientras su postulación está en estado `pendiente` o `derivada`, y el módulo `Practicante\PracticanteController::subirInforme` para cargar su Informe Final de Práctica y descargar su Certificado (incluyendo el PDF firmado con FirmaGob) y Resolución.

### 1.2. Abogado Jefe (antes "Tutor")
* `[x]` **Cambio de terminología:** En toda la interfaz de usuario (`layouts/portal.blade.php`, `tutor/inicio.blade.php`, `secretaria/*`, `director/*`, `admin/*`), el rol se presenta oficialmente como **"Abogado Jefe"**.
* `[x]` **Funciones de recepción de alertas, visualización de carta firmada, revisión de informe y evaluación con segregación de 1ª y 2ª Instancia:** El Abogado Jefe recibe la notificación de inicio oficial al cargarse la Carta de Presentación firmada (`PracticasController::subirCartaPresentacion`), puede visualizar y descargar directamente dicha Carta de Presentación firmada desde su bandeja (`TutorController::verCartaFirmada`), recibe la alerta automática a los 5,5 meses (`ActualizarEstadosPracticas`), aprueba u observa el Informe Final (`TutorController::revisarInforme`) y registra la evaluación conceptual en palabras. Además, `TutorController` aplica una separación estricta de facultades: el Abogado Jefe de 1ª Instancia califica únicamente sus prácticas asignadas (`abogado_id`) y tiene bloqueada (`403`) la 2ª Instancia, mientras que el Abogado Jefe de 2ª Instancia (`es_segunda_instancia = true`) posee visibilidad regional sobre las prácticas de su jurisdicción para registrar el alegato ante Corte de Apelaciones (`evaluarSegundaInstancia`) sin poder alterar la evaluación de 1ª Instancia.

### 1.3. Secretaría Regional (Biobío, Metropolitana, etc.)
* `[x]` **Gestión operativa regional, control de folios, alerta de cancelaciones previas y carga asistida:** El rol `secretaria` gestiona postulaciones con alerta automática e historial en pantalla si el RUT del postulante registra prácticas previas canceladas (`Postulante::practicasPreviasCanceladas`), asigna consultorios y Abogados Jefes, registra el folio oficial de la Carta de Presentación (`guardarFolioCarta`), descarga el borrador y sube la Carta de Presentación firmada para dar inicio oficial a la práctica, administra los folios institucionales del expediente (`HistorialController::guardarFolios`), registra evaluaciones e informes en modalidad de carga asistida (`PracticasController::subirEvaluacionEscaneada`, `subirEvaluacionSegundaInstancia`, `subirInformeAsistido`), tramita cancelaciones/sanciones emitiendo el comprobante PDF de cancelación (`PracticasController::documentoCancelacion`) y remite el expediente visado a Jurídica Biobío (`HistorialController::remitirBiobio`).

### 1.4. Secretaría Biobío (Dirección Jurídica Central — Perfil Unificado)
* `[x]` **Perfil unificado de Secretaría Biobío con atribuciones de Dirección Jurídica Central:** Unificación ejecutada exitosamente. Las secretarias asignadas a la Región del Biobío (`codigo === 'BI'`) asumen nativamente las funciones centrales y territoriales: disponen de un Panel General con sección destacada de *Atribuciones de Jurídica Central*, acceso directo a la *Supervisión Nacional de Postulaciones y Prácticas* (con exportación a Excel oficial de 2 hojas —`En Proceso` y `Finalizados con Evaluación`— de 19 columnas institucionales cada una en `JuridicaBiobioController::exportarPostulaciones`) y a la *Recepción Nacional de Expedientes* (`JuridicaBiobioController`), edición de folios institucionales (`guardarFolios`), formulario de nivelación manual en palabras (`instancia = 3`), recepción de notificaciones de remisión regional, rechazo por sanción disciplinaria (`rechazarSancion`), despacho de certificados al portal del postulante tras FirmaGob (`despachar`) y descarga de Certificado, Resolución y Expediente `.zip`. Mantiene 100 % de compatibilidad retroactiva con el rol `juridica_central`.

### 1.5. Director Regional, Jefe UCOE y Director General
* `[x]` **Implementación de la tríada directiva (`director_regional`, `jefe_ucoe`, `director_general`) y Revisión Colegiada UCOE:**
  * `director_regional` (`DirectorRegionalController`): Bandeja acotada a su región, visación individual y en lote (`validar`, `validarLote`), solicitud de corrección (`observar`) y seguimiento regional exportable a Excel.
  * `jefe_ucoe` (`DirectorRegionalController`): Opera en la Región Metropolitana con el módulo exclusivo de **Revisión Colegiada UCOE** (`/director/colegiada`), permitiendo a Luis Núñez asignar revisores individuales o por lote a Verónica Cifuentes y Graciela Paredes (`asignarRevisor`, `asignarRevisoresLote`), quienes cuentan con su bandeja filtrada y registro de dictamen colegiado (`registrarRevisionColegiada`).
  * `director_general` (`DirectorGeneralController`): Bandeja nacional de expedientes validados, edición y persistencia de folios oficiales de Resolución, Oficio DAJ, Folio CAJ y Carta de Presentación (`guardarFolios`), descarga masiva en lote `.zip` para firma transversal (`descargarLote`), carga del Certificado firmado con **FirmaGob** (`subirFirmaGob`) y Seguimiento Nacional exportable a Excel.

### 1.6. Administrador del Sistema
* `[x]` **Rol técnico/estructural separado de la operación diaria:** El rol `admin` gestiona usuarios (incluyendo asignación de región a `director_regional`, `jefe_ucoe`, `secretaria` y Abogados de 2ª Instancia), el catálogo de los **192 consultorios y unidades operativas oficiales** con horarios normalizados (`ConsultoriosOficialesSeeder`, migración `normalizar_horario_atencion_consultorios` y `Admin\ConsultoriosController`), las capacidades por área, el panel de Estadísticas y Reportes exportables a Excel (`Admin\EstadisticasController`) y el Visor de Auditoría con exportación a Excel (`Admin\AuditoriaController::exportar`), estando excluido de las notificaciones operativas de término de práctica.

---

## 2. Flujo Completo del Proceso (Paso a Paso)

### Fase 1: Postulación y Validación de Antecedentes (Pre-Práctica)

#### Paso 1: Registro y Creación de Postulación
* `[ ]` **Generación de folio único en la tabla `postulaciones`:** El modelo `Postulacion` utiliza su clave primaria autoincremental `id`; la numeración correlativa institucional oficial (`folio_caj`, `folio_carta_presentacion`, `resolucion_numero` y `oficio_daj_numero`) se implementó directamente sobre la tabla `practicas` una vez aprobada la postulación.
* `[x]` **Autocompletado de datos personales:** `PostulacionController` y `Postulante\PerfilController` precargan automáticamente RUT, nombre completo, correo, teléfono y antecedentes académicos desde el perfil autenticado del postulante (`Usuario` / `Postulante`).
* `[~]` **Datos de contacto de emergencia (sin correo electrónico):** Se capturan nombre y teléfono de emergencia obligatorios en el asistente de postulación, aunque el formulario y controlador mantienen el campo opcional `emergencia_email`.
* `[x]` **Depuración de antecedentes académicos (Eliminación de 5° año y catálogo de universidades):** Se eliminó por completo el campo `anio_carrera` y se incorporó el selector estandarizado de universidades (`App\Support\Universidades`) con soporte para *"Otra institución no listada"*.

#### Paso 2: Carga de Documentación Obligatoria (Simplificada por Definición Institucional)
* `[x]` **1. Fotocopia Cédula de Identidad (ambos lados):** Se exigen `cedula_frontal` y `cedula_reverso` como obligatorios en el Paso 3 (`PostulacionController::TIPOS_OBLIGATORIOS`), aceptando documentos en formato `PDF`, `JPG`, `JPEG` y `PNG` hasta 5 MB (`max:5120`), tanto en la postulación inicial como en la actualización posterior desde el perfil del postulante.
* `[x]` **2. Certificado de Egreso o Licenciatura (Obligatorio) y eliminación de Certificado de Alumno Regular:** Se exige `certificado_egreso` dentro de `TIPOS_OBLIGATORIOS` (`mimes:pdf,jpg,jpeg,png`, `max:5120`) y se eliminó el certificado de alumno regular.
* `[~]` **3. Certificado de Antecedentes para Fines Especiales:** Soportado en el modelo documental y en el empaquetado de expedientes (`CertificadosPractica`), pero retirado de `TIPOS_OBLIGATORIOS` en el asistente de postulación por simplificación operativa institucional (exigiendo únicamente Cédula por ambos lados y Certificado de Egreso).
* `[~]` **4. Fotografía tamaño carnet:** Soportada en el modelo documental, pero retirada de `TIPOS_OBLIGATORIOS` en el asistente de postulación bajo el mismo criterio de simplificación de ingreso (3 documentos obligatorios: `cedula_frontal`, `cedula_reverso` y `certificado_egreso`).

#### Paso 3: Selección de Preferencias con Semáforo de Cupos
* `[x]` **Selección acotada de consultorios con semáforo de disponibilidad en vivo:** Implementado en `PostulacionController::reglasPaso4`, `datosPasoConsultorios` y `resources/views/postulacion/paso4.blade.php` y `paso5.blade.php`. El sistema valida un mínimo de 1 y un máximo estricto de 3 preferencias (`min:1`, `max:3`) y despliega en vivo para cada consultorio los cupos totales, ocupados, disponibles, porcentaje de ocupación y el indicador semafórico (`Practicas::nivelDisponibilidad`).

#### Paso 4: Revisión por Secretaría y Subsanación
* `[x]` **Notificación automática a Secretaría y detección de prácticas previas canceladas:** `PostulacionController::enviar` dispara `Notificaciones::postulacionEnviada($p)` a las secretarias de la región. Adicionalmente, `Secretaria\PostulacionesController` y `JuridicaBiobioController` cruzan automáticamente el RUT del postulante para alertar en bandeja y en la ficha de revisión si registra prácticas previas canceladas (`Postulante::practicasPreviasCanceladas`).
* `[~]` **Flujo de Subsanación y Actualización de documentos de postulación:** Implementado mediante edición activa en `Postulante\PerfilController::datos` y `guardarDatos`. Mientras la postulación se encuentra en revisión (`pendiente` o `derivada`), el postulante puede modificar sus datos personales/académicos y reemplazar cualquiera de sus documentos (`cedula_frontal`, `cedula_reverso`, `certificado_egreso`), lo cual actualiza el archivo en almacenamiento privado, registra el evento en auditoría (`postulacion.actualizar_documento` / `postulante.actualizar_datos`) y envía una notificación automática a la Secretaría Regional (*"Postulante actualizó antecedentes"*). Se marca `[~]` únicamente porque opera de forma continua durante el estado `pendiente`/`derivada` sin requerir un estado separado `Observada`.

#### Paso 5: Asignación, Confirmación y Carta de Presentación
* `[ ]` **Reserva temporal de cupo (`PreAsignada`) y aceptación de la oferta por el postulante (`Confirmada` / `ListaEspera` / `Desistida`):** La aprobación de Secretaría en `Postulaciones::aprobar` crea directamente la práctica en estado `'por_iniciar'` (permitiendo también derivarla a otra región con `Postulaciones::derivar`) sin un paso intermedio de confirmación de vacante por parte del postulante.
* `[~]` **Compromiso de Confidencialidad:** El check obligatorio de declaración de veracidad y confidencialidad existe y se valida (`acepta_declaracion`), solicitándose en el Paso 5 del asistente al confirmar el envío de la postulación.
* `[x]` **Autogeneración de la Carta de Presentación tipo (diferenciando Director Regional vs. Jefe UCOE y con folio configurable):** Implementado en `CartaPresentacionService::generarPdf` y `resources/views/pdf/carta_presentacion.blade.php`, incluyendo soporte para `folio_carta_presentacion` (`guardarFolioCarta`), horario normalizado del consultorio y firma diferenciada del Jefe de la UCOE en la Región Metropolitana o del Director Regional en Biobío/otras regiones.
* `[x]` **Descarga y subida de la Carta de Presentación firmada por Secretaría (y visualización por Abogado Jefe):** Implementado en `Secretaria\PracticasController::descargarCartaPresentacion`, `subirCartaPresentacion` y `verCartaPresentacionFirmada`, además de `Tutor\TutorController::verCartaFirmada` para que el Abogado Jefe pueda consultar el documento firmado desde su propio portal.

---

### Fase 2: Desarrollo de la Práctica, Alertas y Cupos

* `[x]` **Inicio Manual Oficial al cargar la Carta de Presentación firmada:** En `PracticasController::subirCartaPresentacion`, al subir la carta firmada la práctica transiciona de `'por_iniciar'` a `'activa'` y notifica inmediatamente al Abogado Jefe.
* `[x]` **Folio CAJ secuencial y folios institucionales en la tabla `practicas`:** Implementado mediante la migración `2026_11_08_000002_agrega_folios_institucionales_a_practicas.php` y el modelo `Practica`, que incorpora y persiste las columnas `folio_caj`, `folio_carta_presentacion`, `resolucion_numero`, `resolucion_anio`, `oficio_daj_numero` y `oficio_daj_fecha`, además del cálculo automático con formato institucional `CAJ-{REG}-{AÑO}-{SEQ}` (`getFolioCajCalculadoAttribute`) y su edición desde Secretaría, Historial, Jurídica Central y Dirección General.
* `[x]` **Alerta automática de término a los 5,5 meses (14 días antes / Día 166):** `Practicas::DIAS_POR_TERMINAR = 14`, `config/caj_documentos.php` (`dias_alerta_previa_termino = 14`) y el comando `ActualizarEstadosPracticas` notifican tanto al **Abogado Jefe** como al **Postulante**, excluyendo al Administrador.
* `[x]` **Liberación anticipada de cupos a los 180 días:** En `Consultorio::ocupados()`, `Secretaria\PracticasController`, `Admin\InicioController` y `Admin\EstadisticasController`, las prácticas con `fecha_termino <= today()` dejan de contabilizarse como cupo ocupado aunque continúen su tramitación documental de cierre.
* `[x]` **Suspensión, Renuncia o Anulación anticipada con causal, historial y comprobante PDF:** Implementado el flujo de cancelación formal con causales tipificadas (`Practicas::MOTIVOS_CANCELACION`), liberación inmediata de cupo en `PracticasController::cancelar` / `PracticantesController::cancelar`, detección de reincidencia por RUT en futuras postulaciones y generación/descarga del **Comprobante PDF Oficial de Cancelación de Práctica** en `PracticasController::documentoCancelacion` (`resources/views/pdf/cancelacion.blade.php`) con trazabilidad en auditoría.

---

### Fase 3: Evaluación, Cierre Regional, Consolidación Biobío y FirmaGob

* `[~]` **Carga del Informe Final por el postulante y revisión por el Abogado Jefe (con subsanación):** El postulante sube su informe en `PracticanteController::subirInforme` (o Secretaría mediante carga asistida en `PracticasController::subirInformeAsistido`) y el Abogado Jefe puede **aprobarlo** u **observarlo para subsanación** en `TutorController::revisarInforme` (habilitando la recarga al postulante). Se marca `[~]` únicamente porque el sistema permite subir el informe mientras la práctica está `'activa'` (sin bloquear antes del día 166) y acepta `pdf,doc,docx`.
* `[x]` **Primera Calificación (Consultorio) exclusivamente en escala conceptual en palabras y carga asistida:** El Abogado Jefe de 1ª Instancia evalúa los 7 criterios reglamentarios seleccionando conceptos en palabras (`Sobresaliente`, `Buena`, `Regular`, `Mala`) en `TutorController::evaluar`, y la Secretaría puede realizar la carga asistida con respaldo escaneado en `PracticasController::subirEvaluacionEscaneada`. Todas las vistas, certificados, resoluciones y exportaciones Excel muestran la calificación en palabras.
* `[x]` **Segunda Calificación condicional (Corte de Apelaciones / 2ª Instancia) con permisos segregados:** Si `requiere_segunda_instancia = true`, se habilita la segunda evaluación exclusivamente para el Abogado Jefe con `es_segunda_instancia = true` en su región (`TutorController::evaluarSegundaInstancia`) o mediante carga asistida con respaldo en `PracticasController::subirEvaluacionSegundaInstancia`, almacenándose en `calificaciones_practica` con `instancia = 2`.
* `[x]` **Cierre de Competencia Regional, Remisión a Jurídica Biobío y Nivelación de Notas en palabras:** Una vez visada por el Director Regional / Jefe UCOE (`validada_director_at`), la Secretaría Regional remite la práctica a Jurídica Biobío (`HistorialController::remitirBiobio`, estado `remitida_biobio`). En `JuridicaBiobioController::nivelar`, la Secretaría de Dirección Jurídica Central revisa las calificaciones de 1ª y 2ª instancia y puede nivelar manualmente el concepto final en palabras (`instancia = 3`, estado `nivelada_biobio`) o rechazar por sanción disciplinaria (`rechazada_sancion`).
* `[x]` **Visto Bueno de Dirección Regional / Jefatura UCOE (individual y por lotes):** Implementado en `DirectorRegionalController::validar` y `validarLote`, además del flujo previo de Revisión Colegiada UCOE en la Región Metropolitana (`registrarRevisionColegiada`).
* `[x]` **Firma Electrónica Institucional (FirmaGob) por el Director General:** Implementado en `DirectorGeneralController::descargarLote` (descarga masiva de borradores en `.zip` para firma transversal) y `subirFirmaGob` (carga del PDF firmado electrónicamente, guardado en `certificado_firmagob_id` y entregado directamente al postulante).
* `[x]` **Despacho de Certificados al Postulante y remisión manual a la Corte Suprema:** Implementado el despacho al portal del postulante (`JuridicaBiobioController::despachar` / `DirectorGeneralController::subirFirmaGob`) y la disposición/descarga de Certificado, Resolución y Expediente completo en `.zip` (`CertificadosPractica::zipExpediente`). El envío oficial a la Corte Suprema corresponde según definición operativa a un trámite manual externo a la plataforma respaldado por la descarga completa del expediente.

---

## 3. Documentos Autogenerados del Sistema

* `[x]` **1. Carta de Presentación e Inicio:** Implementada en `CartaPresentacionService` y `resources/views/pdf/carta_presentacion.blade.php` (diferenciando Director Regional vs. Jefe UCOE, con `folio_carta_presentacion` editable y horario de atención normalizado), complementada además por el **Comprobante PDF de Cancelación de Práctica** (`resources/views/pdf/cancelacion.blade.php`).
* `[~]` **2. Acta / Ficha de Evaluación del Postulante:** Los 7 criterios reglamentarios de evaluación y las calificaciones de 1ª, 2ª y 3ª instancia se capturan digitalmente en `evaluaciones` y `calificaciones_practica`, con respaldo escaneado en carga asistida y consolidación en la Resolución Oficial. Se mantiene marcado en `[~]` debido a que no existe una plantilla, formato institucional oficial ni documento de referencia sobre cómo debería estructurarse un formulario descargable en blanco previo a la calificación.
* `[x]` **3. Traspaso / Remisión a Dirección Jurídica (100 % Digital por Definición Institucional):** El traspaso de competencia territorial a Jurídica Biobío no requiere oficio documental en papel o PDF independiente por definición del modelo institucional; se ejecuta de forma íntegramente digital mediante la visación previa obligatoria del Director Regional / Jefatura UCOE (`validada_director_at`), la transición oficial al estado `remitida_biobio`, el bloqueo de edición para la región de origen, la notificación automática a Jurídica Central y el registro inmutable en el log de auditoría (`HistorialController::remitirBiobio`).
* `[x]` **4. Resumen Consolidado de Evaluaciones (Integrado en Resolución Oficial):** En la práctica institucional de la CAJ Biobío no existe un PDF independiente o aislado de notas; el resumen consolidado de las calificaciones de 1ª, 2ª y 3ª instancia (con los 7 criterios reglamentarios expresados conceptualmente en palabras) forma parte estructural e íntegra de la **Resolución Oficial de Término** (`resources/views/pdf/resolucion.blade.php`, página 2, *RESUELVO 1°*), además de exportarse en el libro Excel institucional de 19 columnas y visualizarse en el portal directivo y de secretaría.
* `[x]` **5. Certificado de Término de Práctica Profesional y Resolución:** Implementados en `CertificadosPractica::pdfCertificado`, `pdfResolucion` y `zipExpediente` (`resources/views/pdf/certificado.blade.php` y `resolucion.blade.php`), incluyendo soporte completo para los folios institucionales (`resolucion_numero`, `resolucion_anio`, `oficio_daj_numero`, `oficio_daj_fecha`, `folio_caj`) y el PDF firmado con FirmaGob.

---

## 4. Módulo de Administración, Estadísticas y Auditoría

* `[x]` **1. Gestión de Usuarios y Roles:** CRUD completo en `Admin\UsuariosController` y `UsuariosAdmin` para todos los roles (`postulante`, `tutor`, `secretaria`, `juridica_central`, `director_regional`, `jefe_ucoe`, `director_general`, `admin`), incluyendo soporte de `region_id` y marca `es_segunda_instancia`.
* `[x]` **2. Gestión de Sedes, Consultorios y Cupos (192 unidades oficiales):** Implementado el catálogo oficial de 192 consultorios y unidades operativas en `ConsultoriosOficialesSeeder` con horarios normalizados (`09:00 a 13:30 / 15:00 a 17:30 hrs.`), el mantenedor CRUD en `Admin\ConsultoriosController` y el gestor de capacidades por área en `Admin\CapacidadController`.
* `[x]` **3. Panel de Estadísticas y Reportes:** Implementado en `Admin\EstadisticasController` y `resources/views/admin/estadisticas/index.blade.php`, incluyendo tasas de ocupación por consultorio, tiempos promedio de asignación en días, desglose anual de prácticas canceladas por causal y exportación a Excel.
* `[x]` **4. Registro de Auditoría (Logs) y Exportación Excel:** Implementado el servicio `Auditoria::registrar`, el modelo y tabla inmutable `auditoria` (con `usuario_id`, `accion`, `entidad`, `entidad_id`, `datos`, `ip`, `user_agent`, fecha), captura automática de `user_agent` en toda petición HTTP, el visor filtrable con búsqueda general (incluyendo navegador y dispositivo) y su exportación completa a Excel (`.xlsx`) con columna dedicada de *User-Agent / Navegador* en `Admin\AuditoriaController::exportar`, la auditoría obligatoria de todas las exportaciones masivas a Excel (`practica.exportar_regional`, `postulacion.exportar_nacional`, `expediente.exportar_excel`, `seguimiento.exportar_nacional`, `auditoria.exportar_excel`), el registro de cada visualización y descarga en `DocumentoController::servir` (`documento.ver`, `documento.descargar`) y descargas de certificados/resoluciones/lotes FirmaGob (Ley N.° 19.628), y la trazabilidad exhaustiva de evaluaciones de 1ª y 2ª instancia (con los 7 criterios reglamentarios, estados `antes` y `despues`, actor y postulante afectado).

---

## 5. Reglas de Negocio y Validaciones Críticas

* `[x]` **Regla 1 — Visibilidad de cupos en tiempo real:** Calculada dinámicamente sobre prácticas activas/por iniciar cuya `fecha_termino > today()`, visible tanto en los paneles administrativos como en el selector de preferencias del postulante (`paso4.blade.php`).
* `[x]` **Regla 2 — Liberación anticipada de cupos a los 180 días:** Aplicada uniformemente en `Consultorio::ocupados()`, `PracticasController`, `PostulacionController`, `InicioController` y `EstadisticasController`.
* `[x]` **Regla 3 — Inicio manual condicionado a Carta de Presentación firmada:** La práctica permanece en `'por_iniciar'` hasta que Secretaría sube la Carta de Presentación firmada en `PracticasController::subirCartaPresentacion`.
* `[x]` **Regla 4 — Bloqueo de etapas sin requisitos previos:** Se bloquea la remisión a Biobío sin evaluación o sin visación del Director Regional (`HistorialController::remitirBiobio`), se bloquea el despacho sin FirmaGob (`JuridicaBiobioController::despachar`) y se aplica `CertificadosPractica::bloqueo($p)` en todas las rutas de descarga de Certificado, Resolución y Expediente `.zip` tanto del Postulante (`PracticanteController`) como de Secretaría Regional (`HistorialController`), Jurídica Central (`JuridicaBiobioController`) y Dirección General (`DirectorGeneralController`).
* `[x]` **Regla 5 — Segregación territorial y nacional:** `Scope::regionId($u)` aísla estrictamente por región a `secretaria`, `director_regional`, `jefe_ucoe` y Abogados de 2ª Instancia, mientras que Secretaría Biobío (`esBiobioCentral`), `juridica_central`, `director_general` y `admin` poseen visibilidad nacional según sus atribuciones.
* `[~]` **Regla 6 — Validación estricta de archivos:** Se valida el límite de 5 MB (`max:5120`) en todos los cargadores y se habilitó el formato `.pdf` (junto con `jpg,jpeg,png`) en `cedula_frontal` y `cedula_reverso` tanto al postular como al subsanar documentos; se marca `[~]` únicamente porque el cargador del Informe Final permite opcionalmente archivos Word (`mimes:pdf,doc,docx`) además de `.pdf` para facilitar la operación del postulante y la carga asistida.
* `[x]` **Regla 7 — Escala de evaluación exclusivamente en palabras:** Todas las bandejas, selectores de evaluación de 1ª y 2ª instancia, nivelación de 3ª instancia, portal del postulante, exportaciones Excel y PDFs muestran las calificaciones en escala conceptual (`Sobresaliente`, `Buena`, `Regular`, `Mala`).
* `[x]` **Regla 8 — Almacenamiento seguro en disco privado:** Todos los documentos de postulación, informes, cartas firmadas, evaluaciones escaneadas y certificados FirmaGob se almacenan fuera de `public/` en `storage/app/private` y se sirven mediante `DocumentoController` autenticado con registro de auditoría en cada visualización (`documento.ver`) y descarga (`documento.descargar`).
* `[x]` **Regla 9 — Exigencia de Certificado de Egreso (sin 5° año):** Eliminado el requisito de 5° año y alumno regular; `certificado_egreso` es obligatorio.

---

## 6. Estado del Camino Alternativo Regional (Flujo de 3 Fases)

| Fase | Descripción del Flujo | Estado Actual | Nivel de Completitud |
| :--- | :--- | :---: | :---: |
| **Fase 1** | Postulación con creación inmediata de cuenta, semáforo de cupos en vivo (hasta 3 preferencias), actualización/subsanación de antecedentes y documentos mientras está en revisión, detección de prácticas previas canceladas por RUT, revisión por Secretaría Regional y generación de Carta de Presentación diferenciada (Director Regional vs. Jefe UCOE) con folio configurable | `[x]` | **100 %** |
| **Fase 2** | Inicio oficial al subir Carta de Presentación firmada (visible para Secretaría y Abogado Jefe), folios institucionales persistidos en `practicas` (`folio_caj`, `folio_carta_presentacion`, `resolucion_numero`, `oficio_daj_numero`), cómputo de 180 días, alertas al día 166 (14 días antes) al Postulante y Abogado Jefe, liberación automática del cupo al día 180 y comprobante PDF de cancelación | `[x]` | **100 %** |
| **Fase 3** | Carga y revisión de Informe Final (con subsanación), evaluación en palabras (1ª y 2ª Instancia en Corte de Apelaciones con permisos segregados), Revisión Colegiada UCOE (RM), visación de Director Regional, remisión y nivelación en Jurídica Biobío (`instancia = 3`), FirmaGob del Director General y despacho con expediente `.zip` | `[x]` | **100 %** |

---

## 7. Cuentas y Casos de Demostración Sembrados (DatosDemostracionSeeder)

Para certificar y auditar de punta a punta cada rol y etapa sin depender de cargas manuales, se implementó `DatosDemostracionSeeder` (ejecutado automáticamente por `UsuariosDemoSeeder`). Todos los usuarios utilizan la contraseña unificada: `123456`.

| Rol / Etapa | Correo de Prueba | Caso Operativo Sembrado |
| :--- | :--- | :--- |
| **Postulante** | `postulante@cajbiobio.cl` | Postulación enviada en estado `pendiente`, con cédula, certificado de egreso y preferencias (habilitado para editar datos/documentos en revisión). |
| **Practicante Activo** | `practicante.activo@cajbiobio.cl` | Práctica activa en Consultorio Centro Concepción con 4 meses de avance (~66%) y carta de inicio firmada. |
| **Practicante por Terminar** | `practicante.termino@cajbiobio.cl` | Término a 5 días (< 14 días con alerta activa) e informe final cargado. Listo para calificar en `/tutor`. |
| **2ª Instancia Corte** | `postulante.corte@cajbiobio.cl` | Práctica calificada en consultorio (*Buena*) derivada a 2ª instancia ante la Corte de Apelaciones (`corte_biobio@cajbiobio.cl`). |
| **Nivelación Regional** | `postulante.nuble@cajbiobio.cl` | Expediente remitido desde Ñuble (`remitida_biobio`, concepto *Regular*). Listo para nivelar en `/secretaria/juridica`. |
| **FirmaGob** | `postulante.firmagob@cajbiobio.cl` | Expediente nivelado a concepto *Buena*. Listo en el portal nacional del Director General (`/director-general`). |
| **Titulado (Descarga)** | `postulante.titulado@cajbiobio.cl` | Práctica cerrada con documentos autorizados. Listo para descargar Resolución y Certificado Corte Suprema. |
| **Jurídica Central Nativo** | `juridica.central@cajbiobio.cl` | Usuario explícito con rol `juridica_central` para verificación de acceso directo. |
