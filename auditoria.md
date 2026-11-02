# Auditoría de Avance del Proyecto — Plataforma CAJ Biobío (`CajBioBioPlataforma`)[cite: 3]

**Fecha de auditoría:** 31 de octubre de 2026[cite: 3]  
**Directorio auditado:** [CajBioBioPlataforma](./) *(PHP 8.2+ / Laravel 12 / Blade / MySQL 8)*[cite: 3]  
**Versión declarada en sistema:** `2.0.1` ([config/caj.php](config/caj.php#L5))[cite: 3]  
**Documentación contrastada:** [Documentos_parte_1](../Documentos_parte_1) (Actas 1–7, Informe Final Etapa 1, Diseño de Solución) y [Documentos_parte_2](../Documentos_parte_2) (Minutas de Avance Semanas 1 a 8). *(Se ignoró `CajBioBioBeta` según lo solicitado).*[cite: 3]

---

## Resumen Ejecutivo

De un total de **53 puntos verificables** (desglosando ítems y sub-ítems del checklist en las 8 secciones)[cite: 3]:

| Estado | Simbología | Cantidad | Porcentaje | Descripción |
| :--- | :---: | :---: | :---: | :--- |
| **Integrado** | `[x]` | **21** | **39,6 %** | Funcionalidad implementada y operativa en el código según lo especificado.[cite: 3] |
| **Parcialmente integrado** | `[~]` | **19** | **35,8 %** | La base existe, pero presenta diferencias de regla de negocio, alcance incompleto o enfoque técnico distinto.[cite: 3] |
| **No integrado** | `[ ]` | **13** | **24,5 %** | Funcionalidad ausente en la base de datos, servicios, controladores y vistas.[cite: 3] |

> [!NOTE]
> **Contexto documental relevante (Minutas de Avance Semanas 6, 7 y 8):** Varias de las diferencias detectadas en el código responden a definiciones en curso registradas en las minutas más recientes (p. ej., la migración terminológica de «practicante» a «postulante» marcada *En progreso* en la Semana 8, la decisión de que Secretaría envíe correos manualmente para evitar bloqueos por spam, y la definición pendiente con Luis Núñez sobre el flujo de la UCOE / Jurídica Central)[cite: 3].

---

## 1. Acceso, Autenticación y Perfiles de Usuario

- `[~]` **Estandarización de terminología: La plataforma debe utilizar transversalmente el término «postulantes» en reemplazo de «practicantes», además de configurar el correo de contacto institucional oficial (`postulantes@cajbiobio.cl`).**[cite: 3]
  - **Correo oficial (`[x]`):** Configurado en [config/mail.php](config/mail.php#L27) (`'address' => env('MAIL_FROM_ADDRESS', 'postulantes@cajbiobio.cl')`) y visible en el pie de la portada pública [inicio.blade.php](resources/views/publico/inicio.blade.php#L46)[cite: 3].
  - **Terminología (`[~]`):** Aunque en la portada pública ([inicio.blade.php](resources/views/publico/inicio.blade.php#L9)) se incorporó *"postulantes al título de abogado"*, el término **«practicante» / «practicantes» sigue presente de forma transversal** en las vistas e interfaz: encabezado de [publico.blade.php](resources/views/layouts/publico.blade.php#L25) (*"Gestión de practicantes"*), [portal.blade.php](resources/views/layouts/portal.blade.php#L6) (*"Gestión de practicantes"*), carta de compromiso en [paso1.blade.php](resources/views/postulacion/paso1.blade.php#L18) (*"Los practicantes colaborarán..."*), menús de Tutor (*"Mis practicantes"*) y Administrador (*"Gestión de practicantes"*), además de las rutas (`/practicante`, `/admin/practicantes`) y el rol en base de datos (`practicante`)[cite: 3].

- `[x]` **Autenticación híbrida: Habilitar acceso temporal mediante RUT y clave asignada, manteniendo reservado el espacio de integración para la API de ClaveÚnica.**[cite: 3]
  - **Evidencia:** El inicio de sesión por RUT y contraseña está implementado en [`LoginController`](app/Http/Controllers/Auth/LoginController.php#L21-L72) y [login.blade.php](resources/views/auth/login.blade.php), incluyendo bloqueo tras 5 intentos fallidos y cambio obligatorio de clave temporal mediante el middleware [`ForzarCambioPassword`](app/Http/Middleware/ForzarCambioPassword.php#L12-L26)[cite: 3]. El cliente OIDC de ClaveÚnica está programado y reservado en [`ClaveUnica`](app/Services/ClaveUnica.php), [`ClaveUnicaController`](app/Http/Controllers/Auth/ClaveUnicaController.php) y [config/claveunica.php](config/claveunica.php) (controlado por `CLAVEUNICA_ENABLED=false`, mostrando el botón institucional con la etiqueta *"Disponible próximamente"*)[cite: 3].

- `[~]` **Perfil Postulante (Acceso Simplificado): Vista restringida exclusivamente para completar el formulario inicial, consultar el estado de postulación, aceptar/rechazar ofertas, cargar el Informe de Práctica en PDF al finalizar y descargar sus resoluciones. Se elimina completamente cualquier vista operativa de gestión de causas judiciales o audiencias (dado que operan en el SAJ institucional).**[cite: 3]
  - **Lo integrado:** Se eliminó por completo cualquier vista de causas judiciales o audiencias[cite: 3]. Existen vistas simplificadas para consultar estado de postulación ([postulante/inicio.blade.php](resources/views/postulante/inicio.blade.php)), subir el Informe de Práctica y descargar el Certificado de término y la Resolución ([practicante/inicio.blade.php](resources/views/practicante/inicio.blade.php), [`PracticanteController`](app/Http/Controllers/PracticanteController.php))[cite: 3].
  - **Lo faltante / diferencias:**
    1. **No existe funcionalidad para aceptar o rechazar ofertas** desde el portal del postulante (Secretaría asigna y crea la práctica directamente en [`Postulaciones::aceptar`](app/Services/Postulaciones.php#L44-L108))[cite: 3].
    2. El perfil está dividido en dos roles distintos (`postulante` en `/mi-postulacion` y `practicante` en `/practicante`), y quien postula sin ClaveÚnica no recibe credenciales sino hasta que la secretaría acepta su postulación[cite: 3].

- `[x]` **Perfil Secretaría Regional: Vista parametrizada para acceder y gestionar únicamente los consultorios, postulaciones y expedientes de su respectiva región.**[cite: 3]
  - **Evidencia:** La clase [`Scope::regionId`](app/Support/Scope.php#L14-L24) restringe obligatoriamente todas las consultas de [`PanelController`](app/Http/Controllers/Secretaria/PanelController.php), [`PostulacionesController`](app/Http/Controllers/Secretaria/PostulacionesController.php), [`PracticasController`](app/Http/Controllers/Secretaria/PracticasController.php), [`HistorialController`](app/Http/Controllers/Secretaria/HistorialController.php), [`ConsultoriosController`](app/Http/Controllers/Secretaria/ConsultoriosController.php) y [`DocumentoController`](app/Http/Controllers/DocumentoController.php#L56-L65) al `region_id` del usuario autenticado[cite: 3].

- `[ ]` **Perfil Secretaría Biobío (Jurídica Central): Módulo centralizado para recepcionar expedientes consolidados de todas las regiones, revisar consistencia de notas y gestionar los cierres institucionales.**[cite: 3]
  - **Evidencia:** No existe un rol, permiso ni bandeja diferenciada para Secretaría Central / Jurídica Biobío (o UCOE)[cite: 3]. Todas las cuentas con rol `secretaria` están acotadas a su propia región en [`Scope::regionId`](app/Support/Scope.php#L14-L24), y las evaluaciones de los tutores pasan directamente a estado `finalizada` y a la cola del Gobernador sin pasar por una bandeja de consolidación de notas[cite: 3].

- `[x]` **Perfil Abogado Jefe / Consultorio Jurídico: Acceso limitado a los postulantes de su unidad para consultar alertas de término, revisar el informe final y completar la rúbrica de evaluación.**[cite: 3]
  - **Evidencia:** Implementado bajo el rol `abogado` en [`TutorController`](app/Http/Controllers/TutorController.php) y [routes/portales/tutor.php](routes/portales/tutor.php)[cite: 3]. [`Scope::abogadoId`](app/Support/Scope.php#L29-L41) filtra exclusivamente las prácticas donde `abogado_id` corresponde al tutor autenticado[cite: 3]. Permite ver alertas de prácticas próximas a vencer ([tutor/inicio.blade.php](resources/views/tutor/inicio.blade.php)), descargar el informe ([`TutorController::descargarInforme`](app/Http/Controllers/TutorController.php#L75-L84)) y enviar la rúbrica ([`TutorController::evaluar`](app/Http/Controllers/TutorController.php#L56-L73))[cite: 3].

- `[~]` **Perfil Director Regional: Vista ejecutiva con capacidad de aprobación por lotes, validación de la propuesta de nota final y flujo de firma de resoluciones y oficios.**[cite: 3]
  - **Lo integrado:** Implementado bajo el rol `gobernador` (*"Gobernador Regional"* en la UI, aunque en [config/caj_documentos.php](config/caj_documentos.php#L10-L12) firma como `DIRECTOR GENERAL`)[cite: 3]. En [`ColaController`](app/Http/Controllers/Gobernador/ColaController.php) y [`GobernadorAutorizaciones`](app/Services/GobernadorAutorizaciones.php) permite autorizar individualmente, por selección múltiple, por lote de $N$ más antiguas y aprobar toda la cola, además de cargar su firma en [`FirmaController`](app/Http/Controllers/Gobernador/FirmaController.php)[cite: 3].
  - **Lo faltante / diferencias:**
    1. No está regionalizado (opera como cola nacional única)[cite: 3].
    2. Solo visualiza el promedio ya cerrado por el tutor y autoriza la emisión de certificados; no tiene opción para modificar/validar una propuesta de nota ni rechazar la evaluación[cite: 3].

- `[x]` **Perfil Administrador General ("Modo Dios"): Usuario único nacional, separado e independiente de las secretarías, con acceso total a base de datos para soporte, asignación de regiones a secretarías, gestión global de cupos y deshabilitación de cuentas (nunca eliminación, para preservar auditoría).**[cite: 3]
  - **Evidencia:** Rol `administrador` en [routes/portales/admin.php](routes/portales/admin.php)[cite: 3]. Tiene alcance nacional ([`Scope::regionId`](app/Support/Scope.php#L17) retorna `null`), permite editar estados/datos y trasladar practicantes de sede ([`PracticantesController`](app/Http/Controllers/Admin/PracticantesController.php)), gestionar cupos de todos los consultorios ([`CapacidadController`](app/Http/Controllers/Admin/CapacidadController.php)), asignar regiones a secretarías y activar/desactivar cuentas sin borrado físico ([`UsuariosController`](app/Http/Controllers/Admin/UsuariosController.php), [`UsuariosAdmin::cambiarActivo`](app/Services/UsuariosAdmin.php#L61-L82))[cite: 3].

---

## 2. Formulario de Postulación e Ingreso de Documentos

- `[~]` **Teléfono de emergencia obligatorio: Sustitución del correo de emergencia por un número de teléfono/celular de contacto obligatorio ante accidentes o urgencias.**[cite: 3]
  - **Lo integrado:** `emergencia_telefono` es obligatorio (`'required', 'string', 'min:8', 'max:30'` en [`PostulacionController::reglasPaso2`](app/Http/Controllers/PostulacionController.php#L210))[cite: 3].
  - **Lo faltante:** No se eliminó el campo de correo de emergencia (`emergencia_email`); sigue presente como campo opcional en el formulario ([paso2.blade.php](resources/views/postulacion/paso2.blade.php#L77-L79)), en la edición del postulante ([postulante/datos.blade.php](resources/views/postulante/datos.blade.php#L85)) y en la vista de secretaría ([_datos-postulante.blade.php](resources/views/secretaria/_datos-postulante.blade.php#L21))[cite: 3].

- `[~]` **Depuración de antecedentes académicos: Eliminación definitiva de los campos de Enseñanza Media, Magíster y Año de carrera actual. Se mantiene la formación universitaria y el Año de egreso con carácter obligatorio.**[cite: 3]
  - **Lo integrado:** Se eliminaron Enseñanza Media y Magíster, manteniendo `universidad` como obligatorio ([paso3.blade.php](resources/views/postulacion/paso3.blade.php#L15-L21))[cite: 3].
  - **Lo faltante:** El campo `anio_carrera` (*"Año de carrera"*) **sigue existiendo** con un selector `'quinto' => '5° año'` / `'egresado' => 'Egresado'` ([paso3.blade.php](resources/views/postulacion/paso3.blade.php#L23-L28), [`PostulacionController::reglasPaso3`](app/Http/Controllers/PostulacionController.php#L224)), y no existe un campo obligatorio de **Año de egreso**[cite: 3].

- `[x]` **Preguntas de accesibilidad e inclusión: Campo obligatorio para consultar si el postulante presenta alguna condición de discapacidad o requerimiento de accesibilidad.**[cite: 3]
  - **Evidencia:** Implementado en [paso2.blade.php](resources/views/postulacion/paso2.blade.php#L54-L64) (`discapacidad` obligatorio Sí/No y `discapacidad_descripcion` obligatorio condicional cuando selecciona Sí, validado en [`PostulacionController::reglasPaso2`](app/Http/Controllers/PostulacionController.php#L207-L208))[cite: 3].

- `[x]` **Validación visual de errores: Aviso visible al inicio indicando que el asterisco (*) representa campos obligatorios. Si falta información, el sistema debe bloquear el envío, hacer scroll automático al campo faltante y resaltarlo en rojo con un mensaje descriptivo.**[cite: 3]
  - **Evidencia:** Aviso en [paso2.blade.php](resources/views/postulacion/paso2.blade.php#L10) (*"Los campos con asterisco rojo son obligatorios"*), componente [campo.blade.php](resources/views/components/campo.blade.php) con clase `.tiene-error`, y funciones `cajValidar`, `marcarError` e `irAlPrimerError` (`scrollIntoView({ behavior: 'smooth', block: 'center' })` + bloqueo `e.preventDefault()`) en [resources/js/app.js](resources/js/app.js#L45-L96) y [resources/js/postulacion.js](resources/js/postulacion.js#L41-L65)[cite: 3].

- `[~]` **Carga documental obligatoria en PDF: Casillas para subir cédula de identidad por ambos lados y certificado de egreso.**[cite: 3]
  - **Lo integrado:** Existen las casillas para `cedula_frontal`, `cedula_reverso` y `certificado_egreso`[cite: 3].
  - **Lo faltante / diferencias:**
    1. **Cédula no acepta PDF:** En [`PostulacionController::reglaArchivo`](app/Http/Controllers/PostulacionController.php#L261-L265) y [cedula.blade.php](resources/views/postulacion/cedula.blade.php#L24-L38), la cédula frontal y reverso están restringidas exclusivamente a imágenes (`'image', 'mimes:jpg,jpeg,png'`), rechazando archivos `.pdf`[cite: 3].
    2. **Certificado de egreso es opcional:** En [`PostulacionController`](app/Http/Controllers/PostulacionController.php#L31-L33) (`TIPOS_OPCIONALES = ['alumno_regular', 'certificado_egreso']`) y en [paso3.blade.php](resources/views/postulacion/paso3.blade.php#L38) (*"Documentos universitarios (opcionales)"*), el certificado de egreso no es obligatorio para enviar la postulación[cite: 3].

- `[x]` **Privacidad sin IA: Validación documental realizada visualmente por el personal, descartando el análisis de contenido o lectura de cédulas mediante Inteligencia Artificial por cumplimiento de la Ley N° 19.628 de Protección de Datos Personales.**[cite: 3]
  - **Evidencia:** No existe ninguna dependencia, servicio ni llamada a APIs de IA/OCR en el código ([composer.json](composer.json), [`PostulacionController`](app/Http/Controllers/PostulacionController.php))[cite: 3]. La revisión se realiza visualmente por la secretaría en [_documentos.blade.php](resources/views/secretaria/_documentos.blade.php)[cite: 3].

- **Selección de disponibilidad y preferencias:**
  - `[~]` **Selector de Región y Comuna (el postulante no elige consultorios específicos a ciegas):** El postulante elige Región y Comuna de su domicilio en el Paso 2 ([paso2.blade.php](resources/views/postulacion/paso2.blade.php#L43-L52)), y en el Paso 4 ([`PostulacionController::datosPasoConsultorios`](app/Http/Controllers/PostulacionController.php#L362-L378)) el sistema ordena automáticamente los consultorios por cercanía geográfica (`hypot` sobre coordenadas lat/lng de la comuna) marcando los 3 más cercanos como `"Cercano"`, pero muestra el listado de todos los consultorios con un buscador de texto en vez de un selector de filtro por Región/Comuna en ese paso[cite: 3].
  - `[~]` **Selección de hasta 2 opciones de consultorios ordenados por prioridad de preferencia:** El sistema guarda el orden de prioridad (`1, 2, ...`), pero **no limita la selección a máximo 2 consultorios**[cite: 3]. En [`PostulacionController::reglasPaso4`](app/Http/Controllers/PostulacionController.php#L232) la regla es `'consultorios' => ['required', 'array', 'min:1']` (sin `'max:2'`) y en [paso4.blade.php](resources/views/postulacion/paso4.blade.php#L19) indica *"Puede elegir los que quiera"*[cite: 3].
  - `[~]` **Visualización del semáforo dinámico de cupos por unidad (Verde: disponible, Amarillo: 1 cupo, Rojo: sin cupos):**
    1. En la vista del postulante ([paso4.blade.php](resources/views/postulacion/paso4.blade.php)) **no se muestra el semáforo ni los cupos**; el semáforo solo está visible en los portales de Secretaría y Administración[cite: 3].
    2. La fórmula en [`Practicas::nivelDisponibilidad`](app/Services/Practicas.php#L65-L72) define Amarillo (`pocos`) cuando `libres < capacidad / 2` (menos del 50% de capacidad), en lugar de la regla exacta `Amarillo = 1 cupo`[cite: 3].
  - `[x]` **Calendario interactivo para marcar la fecha probable de inicio, bloqueando fines de semana (sábados y domingos):** Implementado en [paso4.blade.php](resources/views/postulacion/paso4.blade.php#L65-L92) y `iniciarCalendario` en [resources/js/postulacion.js](resources/js/postulacion.js#L168-L250), bloqueando sábados, domingos, feriados legales ([config/feriados.php](config/feriados.php)) y fechas pasadas, además de validación backend en [`Calendario::esHabil`](app/Support/Calendario.php#L15-L23)[cite: 3].

- `[ ]` **Identificador único: Generación automática de un código o folio único de postulación por registro.**[cite: 3]
  - **Evidencia:** La tabla `postulaciones` ([2026_10_05_000003_crea_postulantes_y_postulaciones.php](database/migrations/2026_10_05_000003_crea_postulantes_y_postulaciones.php#L36-L56)) solo posee `id` numérico interno[cite: 3]. No se genera un código/folio único de seguimiento ni se entrega al usuario en la pantalla de confirmación ([enviada.blade.php](resources/views/postulacion/enviada.blade.php))[cite: 3].

---

## 3. Flujo Operativo: Asignación, Confirmación e Inicio

- `[x]` **Bandeja de revisión de Secretaría Regional: Visualización de postulantes entrantes organizados por orden de llegada y filtrados por jurisdicción.**[cite: 3]
  - **Evidencia:** En [`Secretaria\PostulacionesController::index`](app/Http/Controllers/Secretaria/PostulacionesController.php#L21-L52), la consulta filtra por `where('region_id', $region)->where('estado', 'pendiente')` y ordena por fecha de llegada (`orderBy('fecha_postulacion')->orderBy('id')`)[cite: 3].

- `[ ]` **Módulo de subsanación: Casilla para requerir al postulante la resubida de archivos observados a través de su portal, impidiendo correcciones por correo externo.**[cite: 3]
  - **Evidencia:** En [secretaria/postulaciones/ver.blade.php](resources/views/secretaria/postulaciones/ver.blade.php), Secretaría solo dispone de acciones para **Aceptar**, **Rechazar** o agregar **Comentarios internos** (visibles solo para secretaría)[cite: 3]. Aunque el postulante con ClaveÚnica puede editar datos personales mientras su postulación está `pendiente` ([`PerfilController`](app/Http/Controllers/PerfilController.php)), no puede resubir documentos ni existe un estado/flujo de observación documental[cite: 3].

- **Gestión de cupos y alternativas:**
  - `[~]` **Camino principal: Pre-asignación del cupo si existe vacante en prioridad 1 o 2.** Secretaría visualiza las prioridades del postulante con la etiqueta `POSTULÓ AQUÍ` ([secretaria/postulaciones/ver.blade.php](resources/views/secretaria/postulaciones/ver.blade.php#L73)) y le asigna el consultorio con cupos, pero la acción realiza una **asignación definitiva inmediata** (creando la práctica activa/por iniciar), no una *pre-asignación* sujeta a confirmación del postulante[cite: 3].
  - `[~]` **Flujo alternativo: Opciones para contactar al postulante y reasignarlo a una unidad cercana o pasarlo a lista de espera si no hay vacantes. Si el postulante desiste, el sistema cancela la postulación y archiva en historial.** Secretaría puede asignarlo a cualquier consultorio con vacantes de la región o derivarlo a otra región ([`Postulaciones::asignar`](app/Services/Postulaciones.php#L113-L129))[cite: 3]. Sin embargo, **no existe estado de "Lista de espera"** ni flujo de desistimiento de postulación que se archive en el módulo de Historial (el Historial solo lista registros de la tabla `practicas`, no `postulaciones` rechazadas o desistidas)[cite: 3].

- `[ ]` **Aceptación formal del postulante: Interfaz para que el alumno acepte o rechace la vacante pre-asignada.**[cite: 3]
  - **Evidencia:** No existe paso de aceptación de oferta por parte del postulante[cite: 3]. En [`Postulaciones::aceptar`](app/Services/Postulaciones.php#L44-L108), la aprobación de secretaría crea directamente la práctica[cite: 3].

- `[~]` **Compromiso de Confidencialidad en línea: Suscripción digital del compromiso de confidencialidad y secreto profesional al momento exacto de aceptar la práctica (no al llenar el formulario inicial).**[cite: 3]
  - **Evidencia:** El compromiso de confidencialidad sí existe en línea, pero **está ubicado en el Paso 1 del formulario inicial de postulación** ([paso1.blade.php](resources/views/postulacion/paso1.blade.php)), contradiciendo la instrucción de suscribirlo al momento de aceptar la práctica[cite: 3].

- `[ ]` **Asignación de Folio CAJ: Generación del número secuencial oficial de registro de práctica institucional.**[cite: 3]
  - **Evidencia:** La tabla `practicas` ([2026_10_05_000004_crea_practicas_y_calificaciones.php](database/migrations/2026_10_05_000004_crea_practicas_y_calificaciones.php#L12-L38)) no tiene un campo de Folio CAJ de registro de práctica al inicio; solo se genera una secuencia anual (`resolucion_secuencia` / `resolucion_anio`) al final de la práctica cuando se emite la Resolución de término en [`CertificadosPractica::pdfResolucion`](app/Services/CertificadosPractica.php#L56-L78)[cite: 3].

- **Flujo documental de Carta de Presentación:**
  - `[ ]` **Autogeneración de la Carta de Presentación tipo con los datos del postulante y unidad:** No integrado[cite: 3]. En `resources/views/pdf/` solo existen plantillas para `certificado.blade.php`, `resolucion.blade.php` y `cancelacion.blade.php`[cite: 3].
  - `[ ]` **Opción para que Secretaría descargue la carta, gestione la firma correspondiente (en representación del Director) y la vuelva a subir firmada:** No integrado[cite: 3].

- `[ ]` **Inicio Manual Oficial: Botón para que Secretaría valide la fecha real de ingreso del postulante y dé inicio formal al cómputo de la práctica.**[cite: 3]
  - **Evidencia:** No existe botón de inicio manual[cite: 3]. La práctica inicia su cómputo automáticamente según `fecha_inicio_solicitada` al ser aceptada por secretaría ([`Postulaciones::aceptar`](app/Services/Postulaciones.php#L61-L81)) o mediante el comando automático diario [`ActualizarEstadosPracticas`](app/Console/Commands/ActualizarEstadosPracticas.php#L17-L23)[cite: 3].

- `[~]` **Notificación de bienvenida: Envío de correo al postulante adjuntando carta firmada, reglamento interno y copia de aviso al Abogado Jefe.**[cite: 3]
  - **Evidencia:** En [`Postulaciones::aceptar`](app/Services/Postulaciones.php#L93-L100) se crean **notificaciones internas en base de datos** (`Notificaciones::crear`) para el postulante y el Abogado Jefe, pero no se envían correos electrónicos (`Mail`) ni adjuntos[cite: 3].

---

## 4. Flujo de Desarrollo, Tiempos y Alertas

- `[x]` **Cómputo de plazo legal: Control del período reglamentario de 6 meses (180 días).**[cite: 3]
  - **Evidencia:** Parametrizado en [config/caj.php](config/caj.php#L7) (`'duracion_practica_meses' => (int) env('PRACTICA_DURACION_MESES', 6)`), asignado al crear la práctica (`$inicio->copy()->addMonths($meses)` en [`Postulaciones::aceptar`](app/Services/Postulaciones.php#L79)) y monitoreado con días transcurridos, restantes y porcentaje en [`Practicas::estadistica`](app/Services/Practicas.php#L40-L60)[cite: 3].

- `[~]` **Alertas automáticas de término: Notificación automatizada al cumplirse 5 meses y medio (2 semanas antes del término) dirigida exclusivamente al Postulante y al Abogado Jefe de la unidad (excluyendo expresamente al Administrador Central para no saturar su bandeja).**[cite: 3]
  - **Lo integrado:** Existe el comando programado [`ActualizarEstadosPracticas`](app/Console/Commands/ActualizarEstadosPracticas.php#L25-L42) en [routes/console.php](routes/console.php#L6) que excluye al Administrador Central y notifica al Abogado Jefe[cite: 3].
  - **Lo faltante / diferencias:**
    1. El umbral está configurado en **30 días antes del término** (`DIAS_POR_TERMINAR = 30` en [`Practicas`](app/Services/Practicas.php#L15)), no en 2 semanas (14 días)[cite: 3].
    2. El comando **solo notifica al Abogado Tutor** (`$p->abogado->usuario_id`), omitiendo enviar la alerta al usuario del Postulante/Practicante[cite: 3].

- `[ ]` **Liberación anticipada de cupos: La plataforma debe desvincular el cupo del consultorio automáticamente apenas el estudiante cumple los 6 meses de práctica, liberando la vacante para un nuevo postulante sin esperar los trámites burocráticos de revisión y firma.**[cite: 3]
  - **Evidencia:** En [`Consultorio::ocupados`](app/Models/Consultorio.php#L15-L19), los cupos ocupados se calculan como `$this->practicas()->whereIn('estado', ['por_iniciar', 'activa'])`[cite: 3]. Ni esa consulta filtra por `fecha_termino > today()`, ni el comando [`ActualizarEstadosPracticas`](app/Console/Commands/ActualizarEstadosPracticas.php) cambia el estado al cumplirse `fecha_termino`[cite: 3]. Por tanto, el cupo permanece ocupado hasta que el tutor envía la calificación final (que cambia el estado a `'finalizada'`)[cite: 3].

- `[~]` **Carga del Informe Final: Casilla en la sesión del postulante habilitada tras cumplir los 6 meses para cargar su informe en formato PDF (elaborado a partir de los antecedentes de causas del SAJ).**[cite: 3]
  - **Lo integrado:** El portal del practicante ([practicante/inicio.blade.php](resources/views/practicante/inicio.blade.php#L27-L42), [`InformesPractica::subir`](app/Services/InformesPractica.php#L18-L62)) cuenta con la casilla para subir el Informe de Práctica[cite: 3].
  - **Diferencias:**
    1. La casilla está habilitada durante toda la práctica (`in_array($p->estado, ['por_iniciar', 'activa'])`), sin restringir su apertura a haber cumplido los 6 meses (`fecha_termino`)[cite: 3].
    2. Acepta archivos Word además de PDF (`mimes:pdf,doc,docx` en [`PracticanteController::subirInforme`](app/Http/Controllers/PracticanteController.php#L36))[cite: 3].

---

## 5. Flujo de Evaluación, Calificaciones y Cierre

- `[x]` **Desbloqueo de calificación: El sistema debe condicionar la apertura de la evaluación a la carga efectiva del Informe de Práctica en PDF.**[cite: 3]
  - **Evidencia:** En [`EvaluacionesPractica::bloqueo`](app/Services/EvaluacionesPractica.php#L22-L24), si `! InformesPractica::vigente($p)` retorna el bloqueo *"El practicante aún no sube su informe de práctica; la encuesta se habilita al recibirlo."* y deshabilita el formulario en [tutor/practicante.blade.php](resources/views/tutor/practicante.blade.php#L15-L21)[cite: 3].

- `[x]` **Primera Calificación (Abogado Jefe): Formulario tipo encuesta/rúbrica con escala de notas de 1.0 a 7.0 (evaluando los 7 factores reglamentarios) y campo para observaciones cualitativas.**[cite: 3]
  - **Evidencia:** Los 7 criterios (*Conocimiento jurídico, Responsabilidad, Iniciativa, Sentido social, Conducta, Honorabilidad, Asistencia y puntualidad*) y su conversión a concepto cualitativo están definidos en [`Notas`](app/Support/Notas.php#L10-L43), validados entre `1.0` y `7.0` en [`TutorController::evaluar`](app/Http/Controllers/TutorController.php#L58-L65) junto con `observaciones`, y renderizados en [tutor/practicante.blade.php](resources/views/tutor/practicante.blade.php#L22-L42)[cite: 3].

- `[ ]` **Segunda Calificación condicional: Rombo de decisión y vista exclusiva para un segundo evaluador (ej. Abogado Jefe de Segunda Instancia / Corte de Apelaciones) que se habilita únicamente si aplica apelación o revisión.**[cite: 3]
  - **Evidencia:** Aunque la tabla `calificaciones` tiene una columna `instancia` y en [practicante/inicio.blade.php](resources/views/practicante/inicio.blade.php#L18) se muestran etiquetas estáticas para 1ª, 2ª y 3ª calificación, **no existe lógica, ruta, controlador ni vista para un segundo evaluador**[cite: 3]. En [`EvaluacionesPractica::evaluar`](app/Services/EvaluacionesPractica.php#L30-L69) siempre se inserta `instancia => 1` y se cierra inmediatamente la práctica en estado `'finalizada'`[cite: 3].

- `[ ]` **Consolidación en Jurídica Biobío: Bandeja para que la Secretaría Central valide promedios, revise coherencia técnica y ajuste notas según la escala institucional si procede.**[cite: 3]
  - **Evidencia:** No existe bandeja de consolidación intermedia entre la evaluación del Abogado Tutor y la bandeja de autorización del Gobernador/Director[cite: 3].

- `[x]` **Visto bueno de Dirección Regional: Módulo para que el Director Regional o jefatura valide la propuesta definitiva de cierre (individualmente o por lotes).**[cite: 3]
  - **Evidencia:** Implementado en [gobernador/inicio.blade.php](resources/views/gobernador/inicio.blade.php), [`ColaController`](app/Http/Controllers/Gobernador/ColaController.php) y [`GobernadorAutorizaciones`](app/Services/GobernadorAutorizaciones.php#L36-L109) con acciones unitarias, por selección de casillas, por lote configurable y total[cite: 3].

- **Autogeneración de los 4 documentos oficiales: Creación automática en PDF con los datos personales, fechas y calificaciones:**
  - `[x]` **Documento 1 (Postulante): Resolución de término de práctica:** Integrado en [`CertificadosPractica::pdfResolucion`](app/Services/CertificadosPractica.php#L56-L99) y plantilla [pdf/resolucion.blade.php](resources/views/pdf/resolucion.blade.php)[cite: 3].
  - `[x]` **Documento 2 (Postulante): Certificado de aprobación para la Corte Suprema:** Integrado en [`CertificadosPractica::pdfCertificado`](app/Services/CertificadosPractica.php#L25-L54) y plantilla [pdf/certificado.blade.php](resources/views/pdf/certificado.blade.php) (dirigido a la Excma. Corte Suprema con los 7 criterios evaluados)[cite: 3].
  - `[ ]` **Documento 3 (Interno): Oficio de traspaso a la Dirección Jurídica:** No integrado[cite: 3].
  - `[ ]` **Documento 4 (Interno): Resumen consolidado de evaluaciones y notas:** No integrado en PDF[cite: 3].

- `[~]` **Firma electrónica institucional: Módulo para descargar los documentos generados, estampar Firma Electrónica Avanzada (FirmaGob) por parte del Director y resubirlos firmados al repositorio del postulante.**[cite: 3]
  - **Evidencia:** Implementado con un enfoque técnico distinto: el Gobernador/Director sube una imagen PNG/JPG de su firma en [`GobernadorFirma`](app/Services/GobernadorFirma.php) ([gobernador/inicio.blade.php](resources/views/gobernador/inicio.blade.php#L15-L38)) y DomPDF incrusta esa imagen en base64 al generar el PDF al vuelo ([`CertificadosPractica::firmaBase64`](app/Services/CertificadosPractica.php#L120-L133))[cite: 3]. No existe flujo para descargar el PDF borrador, firmarlo con token/FirmaGob y volver a subir el PDF firmado al expediente del postulante[cite: 3].

- `[~]` **Despacho final y cierre: Envío de correo formal de cierre al postulante con sus documentos adjuntos y traspaso definitivo del expediente a la sección de Historial.**[cite: 3]
  - **Lo integrado:** La práctica pasa automáticamente a la sección de Historial (`estado = 'finalizada'`) y al autorizarse en [`GobernadorAutorizaciones::autorizarIds`](app/Services/GobernadorAutorizaciones.php#L95-L103) se habilita la descarga de PDFs en el portal del practicante y se envía una notificación interna en plataforma[cite: 3].
  - **Lo faltante:** No se despacha correo electrónico (`Mail`) con los documentos PDF adjuntos[cite: 3].

---

## 6. Módulo de Cancelaciones y Términos Anticipados

- `[x]` **Estado formal "Cancelada / Suspendida": Funcionalidad para interrumpir una práctica antes de su término legal por causales reglamentarias o deserción.**[cite: 3]
  - **Evidencia:** Implementado el estado `'cancelada'` tanto para Secretaría ([`Practicas::cancelar`](app/Services/Practicas.php#L77-L115), [`Secretaria\PracticasController::cancelar`](app/Http/Controllers/Secretaria/PracticasController.php#L52-L70)) como para el Administrador General ([`PracticasAdmin::cancelar`](app/Services/PracticasAdmin.php#L75-L108))[cite: 3].

- `[~]` **Canal de solicitud sin autoservicio: No habilitar botón de renuncia en la vista del alumno; el postulante debe tramitarla obligatoriamente por correo formal, lo cual debe constar en los términos de postulación.**[cite: 3]
  - **Lo integrado:** En la vista del postulante/practicante ([practicante/inicio.blade.php](resources/views/practicante/inicio.blade.php)) **no existe botón de renuncia ni cancelación**[cite: 3].
  - **Lo faltante:** En la carta de términos del Paso 1 ([paso1.blade.php](resources/views/postulacion/paso1.blade.php)) no se incluye una cláusula que indique expresamente el procedimiento de renuncia por correo formal[cite: 3].

- `[x]` **Registro de causal y fecha: Selector tipificado de motivos de cancelación (ej. irresponsabilidad, motivos laborales, personales o de salud) con registro automático de la fecha exacta.**[cite: 3]
  - **Evidencia:** Definido en [`Practicas::MOTIVOS_CANCELACION`](app/Services/Practicas.php#L17-L21) (`conducta`, `decision`, `otro`, más `error_registro` en Admin), modal [_modal-cancelar.blade.php](resources/views/secretaria/practicas/_modal-cancelar.blade.php) y guardado automático de `'cancelacion_fecha' => today()` y `'cancelacion_antes_de_iniciar'`[cite: 3].

- `[~]` **Respaldo con Oficio de Cancelación: Campo obligatorio para adjuntar el oficio formal de interrupción firmado por la Dirección.**[cite: 3]
  - **Evidencia:** El modal de cancelación ([_modal-cancelar.blade.php](resources/views/secretaria/practicas/_modal-cancelar.blade.php)) **no solicita adjuntar un archivo PDF firmado**[cite: 3]. En su lugar, el sistema autogenera un PDF de *Constancia de Cancelación de Práctica* ([pdf/cancelacion.blade.php](resources/views/pdf/cancelacion.blade.php), [`CertificadosPractica::pdfCancelacion`](app/Services/CertificadosPractica.php#L101-L118))[cite: 3].

- `[x]` **Liberación de cupo inmediata: La cancelación en curso debe restituir la vacante del consultorio de manera inmediata para destrabar la rotación.**[cite: 3]
  - **Evidencia:** Al actualizar `estado => 'cancelada'`, [`Consultorio::ocupados`](app/Models/Consultorio.php#L17) (que solo contabiliza `['por_iniciar', 'activa']`) libera el cupo en el acto[cite: 3].

- `[x]` **Constancia en expediente: El evento de término anticipado debe quedar registrado en la ficha del RUT dentro del historial.**[cite: 3]
  - **Evidencia:** Visible en [secretaria/historial/index.blade.php](resources/views/secretaria/historial/index.blade.php#L56-L65) y en la ficha detallada [secretaria/historial/ver.blade.php](resources/views/secretaria/historial/ver.blade.php#L20-L35) con fecha de cancelación, motivo tipificado, detalle, indicador de si ocurrió antes de iniciar (`NO INICIÓ`) y enlace al PDF de cancelación[cite: 3].

---

## 7. Vistas de Control, Reportes y Administración Central

- `[~]` **Módulo de Prácticas Activas: Vista operativa con filtros avanzados, buscador por RUT/nombre, enlace directo a los expedientes/documentos subidos y botón de exportación a Excel.**[cite: 3]
  - **Lo integrado:** Vista operativa en [secretaria/practicas/index.blade.php](resources/views/secretaria/practicas/index.blade.php) con filtros por consultorio y estado (`todas`, `activa`, `por_terminar`, `por_iniciar`), y exportación nativa a `.xlsx` (OpenSpout) en [`Secretaria\PracticasController::exportar`](app/Http/Controllers/Secretaria/PracticasController.php#L72-L99)[cite: 3].
  - **Lo faltante:**
    1. **El buscador no filtra por RUT:** En [`Secretaria\PracticasController::consulta`](app/Http/Controllers/Secretaria/PracticasController.php#L106-L109) solo busca por `nombre` y `apellido` (`where('nombre', 'like', $like)->orWhere('apellido', 'like', $like)`), y en [`Admin\PracticantesController::index`](app/Http/Controllers/Admin/PracticantesController.php#L23-L27) tampoco incluye `rut`[cite: 3].
    2. **Sin enlaces a documentos en la vista de Prácticas Activas:** Aunque [`Secretaria\PracticasController::ver`](app/Http/Controllers/Secretaria/PracticasController.php#L46) carga la relación `'documentos'`, la vista [secretaria/practicas/ver.blade.php](resources/views/secretaria/practicas/ver.blade.php) no renderiza la tabla de documentos subidos (solo se muestran en Postulaciones y en Historial)[cite: 3].

- `[x]` **Módulo de Historial Regionalizado: Repositorio histórico de prácticas terminadas y canceladas por región, con trazabilidad de fechas, notas finales y enlace de descarga de antecedentes.**[cite: 3]
  - **Evidencia:** Implementado en [`Secretaria\HistorialController`](app/Http/Controllers/Secretaria/HistorialController.php), [secretaria/historial/index.blade.php](resources/views/secretaria/historial/index.blade.php) y [secretaria/historial/ver.blade.php](resources/views/secretaria/historial/ver.blade.php) (incluye pestañas de *Calificaciones* y *Documentos* con botones para ver y descargar antecedentes)[cite: 3].

- **Gestión de cupos centralizada:**
  - `[x]` **Facilidad exclusiva del Administrador Central para aumentar o reducir las vacantes de cada unidad jurídica:** Implementado exclusivamente bajo `middleware('rol:administrador')` en [`Admin\CapacidadController`](app/Http/Controllers/Admin/CapacidadController.php) y [admin/capacidad/index.blade.php](resources/views/admin/capacidad/index.blade.php) (Secretaría solo tiene vista de lectura en Consultorios)[cite: 3].
  - `[x]` **Bloqueo de seguridad que impida reducir cupos totales por debajo de la cantidad de postulantes activos en dicha unidad:** En [`CapacidadController::ajustar`](app/Http/Controllers/Admin/CapacidadController.php#L47-L55) valida `if ($nueva < $ocupados)` devolviendo error y bloqueando el botón `−` en [admin/capacidad/index.blade.php](resources/views/admin/capacidad/index.blade.php#L67) cuando `$libres === 0`[cite: 3].

- `[x]` **Módulo de asignación territorial: Panel del Administrador General para vincular a cada funcionaria de secretaría con su región correspondiente.**[cite: 3]
  - **Evidencia:** En [`Admin\UsuariosController`](app/Http/Controllers/Admin/UsuariosController.php#L73-L84) y [admin/usuarios/form.blade.php](resources/views/admin/usuarios/form.blade.php#L40-L47), el Administrador General asigna o modifica la región (`region_id` obligatorio cuando `rol === 'secretaria'`)[cite: 3].

- `[~]` **Panel de Estadísticas y Reportes: Indicadores de tasas de ocupación por consultorio, prácticas activas vs. pendientes, tiempos de asignación y métricas de causales de cancelación anuales.**[cite: 3]
  - **Lo integrado:** Tasas de ocupación por consultorio y globales, conteo de prácticas activas, por iniciar, por vencer y postulaciones pendientes en [`Admin\InicioController`](app/Http/Controllers/Admin/InicioController.php), [`Secretaria\PanelController`](app/Http/Controllers/Secretaria/PanelController.php) y [`Secretaria\ConsultoriosController`](app/Http/Controllers/Secretaria/ConsultoriosController.php)[cite: 3].
  - **Lo faltante:** No están programados los indicadores de **tiempos de asignación** ni las **métricas de causales de cancelación anuales**[cite: 3].

---

## 8. Seguridad, Auditoría y Aspectos Técnicos

- `[x]` **Compatibilidad de servidores CAJ: Backend y base de datos desarrollados nativamente en PHP y MySQL/SQL para asegurar compatibilidad con la infraestructura de la institución.**[cite: 3]
  - **Evidencia:** Proyecto construido nativamente en PHP 8.2+ / Laravel 12 con vistas Blade renderizadas en servidor y MySQL 8 ([composer.json](composer.json), [docker-compose.yml](docker-compose.yml), [database/migrations](database/migrations))[cite: 3].

- `[~]` **Log inmutable de auditoría (Ley N° 19.628): Registro en base de datos que guarde automáticamente usuario, dirección IP, navegador, fecha/hora y detalle del cambio (con estructura JSON de `datos_anteriores` y `datos_nuevos`), especialmente en visualización de documentos sensibles, cambios de estados y modificaciones de notas.**[cite: 3]
  - **Lo integrado:** Tabla `auditoria` sin columna `updated_at` (`const UPDATED_AT = null;` en [`Models\Auditoria`](app/Models/Auditoria.php#L12)) y servicio [`Services\Auditoria::registrar`](app/Services/Auditoria.php#L14-L25) que almacena `usuario_id`, `accion`, `entidad`, `entidad_id`, `datos` (JSON), `ip` y `created_at` en inicios de sesión, postulaciones, aceptaciones, rechazos, derivaciones, cancelaciones, evaluaciones, autorizaciones, ajustes de cupos y cambios de usuarios[cite: 3].
  - **Lo faltante:**
    1. **No guarda el navegador (`user_agent`)** en la migración [2026_10_05_000005_crea_documentos_notificaciones_auditoria.php](database/migrations/2026_10_05_000005_crea_documentos_notificaciones_auditoria.php#L36-L46) ni en [`Services\Auditoria`](app/Services/Auditoria.php)[cite: 3].
    2. El campo JSON `datos` guarda únicamente un arreglo plano con los parámetros del evento (o en [`CapacidadController`](app/Http/Controllers/Admin/CapacidadController.php#L61) `['antes' => ..., 'despues' => ...]`), sin estandarizar la estructura `datos_anteriores` y `datos_nuevos`[cite: 3].
    3. **No registra eventos al visualizar o descargar documentos sensibles** (cédulas de identidad, certificados o informes) en [`DocumentoController::servir`](app/Http/Controllers/DocumentoController.php#L27-L44)[cite: 3].

- `[x]` **Gestión de bajas de personal: Deshabilitación o suspensión lógica de cuentas de usuarios administrativos en lugar de eliminación física en la base de datos.**[cite: 3]
  - **Evidencia:** En [`UsuariosAdmin::cambiarActivo`](app/Services/UsuariosAdmin.php#L61-L82) se cambia el booleano `activo` y se cierran las sesiones activas en la tabla `sessions`[cite: 3]. No existe ruta ni método `DELETE` para eliminar usuarios[cite: 3].

- `[x]` **Control de versión visible: Etiqueta visible del número de versión del sistema desplegado en la vista de administración.**[cite: 3]
  - **Evidencia:** Definido en [config/caj.php](config/caj.php#L5) (`'version' => '2.0.1'`) y desplegado en el panel de Administración General en [admin/inicio.blade.php](resources/views/admin/inicio.blade.php#L21)[cite: 3].