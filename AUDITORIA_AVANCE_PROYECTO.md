# Auditoría de Avance del Proyecto — Plataforma CAJ Biobío (`CajBioBioPlataforma`)

**Fecha de auditoría:** 1 de noviembre de 2026  
**Directorio auditado:** `CajBioBioPlataforma` *(PHP 8.2+ / Laravel 12 / Blade / MySQL 8)*  
**Versión declarada en sistema:** `2.0.1` (`CajBioBioPlataforma/config/caj.php#L5`)  
**Documentación contrastada:** `Documentos_parte_1` (Actas 1–7, Informe Final Etapa 1, Diseño de Solución), `Documentos_parte_2` (Minutas de Avance Semanas 1 a 8) y **Especificación de Caminos Alternativos para Regiones Externas (Fases 1, 2 y 3)**. *(Se ignoró `CajBioBioBeta` según lo solicitado).*

---

## Resumen Ejecutivo

De un total de **53 puntos verificables** del checklist principal más las **3 fases operativas del Camino Alternativo para Regiones Externas**:

| Estado | Simbología | Cantidad | Porcentaje | Descripción |
| :--- | :---: | :---: | :---: | :--- |
| **Integrado** | `[x]` | **21** | **39,6 %** | Funcionalidad implementada y operativa en el código según lo especificado. |
| **Parcialmente integrado** | `[~]` | **19** | **35,8 %** | La base existe, pero presenta diferencias de regla de negocio, alcance incompleto o enfoque técnico distinto. |
| **No integrado** | `[ ]` | **13** | **24,5 %** | Funcionalidad ausente en la base de datos, servicios, controladores y vistas. |

> [!IMPORTANT]
> **Hallazgos críticos al contrastar con el Camino Alternativo de Regiones Externas (Fases 1, 2 y 3):**
> 1. **Fase 1 (Reasignación asistida y 3 bifurcaciones):** El sistema actual asigna directamente creando la práctica (`Postulaciones::aceptar`); no tiene estados `PreAsignada`, `Confirmada`, `ListaEspera` ni `Desistida` (archivada en historial sin sanción). Además, en `UsuariosDemoSeeder.php` solo se crearon las 2 secretarias de Biobío, faltando las cuentas de secretaría (1 por región) para las 5 regiones externas (`NB`, `AR`, `LR`, `LL`, `AI`).
> 2. **Fase 2 (Emisor diferenciado de Carta de Presentación e Inicio Manual):** No existe autogeneración ni subida de Carta de Presentación firmada diferenciando al **Director Regional** (en regiones externas) vs. **Jefe de UCOE** (en Biobío), ni botón de **Inicio Manual Oficial**. En las cancelaciones, se cumple cancelar sin nota y permitir nueva postulación desde cero, pero falta exigir la subida del **Oficio de Término Anticipado firmado**.
> 3. **Fase 3 (2ª Instancia, Traspaso a Jurídica Biobío, Nivelación de Nota $\ge 5.0$ y FirmaGob):**
>    - En `CajBioBioPlataforma/app/Support/Notas.php#L7`, la constante actual es `MINIMA_APROBACION = 4.0` y el concepto entre `4.0` y `4.4` es `'SUFICIENTE'`, contradiciendo la regla institucional de que **la calificación no puede ser "regular" ni inferior a 5.0** (*debe situarse en "buena" $\ge 5.0$, "muy buena" o "sobresaliente"*).
>    - No existe vista ni flujo para el **Abogado Jefe de Segunda Instancia** (Corte de Apelaciones), ni cierre de competencia regional con traspaso a la **Secretaría Jurídica de Biobío (Concepción)** para subir la nota de oficio a "buena" ($5.0$) o rechazar por sanción disciplinaria fundada.
>    - No se autogeneran los 2 documentos internos (Oficio de traspaso a Dirección Jurídica y Resumen consolidado de evaluaciones) ni existe el flujo de carga de PDFs con **FirmaGob** del Director General y despacho por correo desde Jurídica Biobío.

---

## 1. Acceso, Autenticación y Perfiles de Usuario

- `[~]` **Estandarización de terminología: La plataforma debe utilizar transversalmente el término «postulantes» en reemplazo de «practicantes», además de configurar el correo de contacto institucional oficial (`postulantes@cajbiobio.cl`).**
  - **Correo oficial (`[x]`):** Configurado en `CajBioBioPlataforma/config/mail.php#L27` (`'address' => env('MAIL_FROM_ADDRESS', 'postulantes@cajbiobio.cl')`) y visible en `CajBioBioPlataforma/resources/views/publico/inicio.blade.php#L46`.
  - **Terminología (`[~]`):** Aunque en `CajBioBioPlataforma/resources/views/publico/inicio.blade.php#L9` se menciona *"postulantes al título de abogado"* y en `CajBioBioPlataforma/app/Services/Postulaciones.php#L78` se unificó el rol a `'postulante'`, el término **«practicante» / «practicantes» sigue presente de forma transversal** en la interfaz: `CajBioBioPlataforma/resources/views/layouts/publico.blade.php#L25` (*"Gestión de practicantes"*), `CajBioBioPlataforma/resources/views/layouts/portal.blade.php#L6`, `CajBioBioPlataforma/resources/views/postulacion/paso1.blade.php#L18`, menús de Tutor (*"Mis practicantes"*) y Admin (*"Gestión de practicantes"*).

- `[x]` **Autenticación híbrida: Habilitar acceso temporal mediante RUT y clave asignada, manteniendo reservado el espacio de integración para la API de ClaveÚnica.**
  - **Evidencia:** Login por RUT y clave en `CajBioBioPlataforma/app/Http/Controllers/Auth/LoginController.php#L21-L72` y `CajBioBioPlataforma/resources/views/auth/login.blade.php`, con bloqueo tras 5 intentos fallidos y cambio obligatorio de clave temporal en `CajBioBioPlataforma/app/Http/Middleware/ForzarCambioPassword.php#L12-L26`. Integración OIDC de ClaveÚnica reservada en `CajBioBioPlataforma/app/Services/ClaveUnica.php`, `CajBioBioPlataforma/app/Http/Controllers/Auth/ClaveUnicaController.php` y `CajBioBioPlataforma/config/claveunica.php`.

- `[~]` **Perfil Postulante (Acceso Simplificado): Vista restringida exclusivamente para completar el formulario inicial, consultar el estado de postulación, aceptar/rechazar ofertas, cargar el Informe de Práctica en PDF al finalizar y descargar sus resoluciones. Se elimina completamente cualquier vista operativa de gestión de causas judiciales o audiencias (dado que operan en el SAJ institucional).**
  - **Lo integrado:** Se eliminó por completo cualquier vista de causas judiciales o audiencias. Existen vistas para consultar postulación (`CajBioBioPlataforma/resources/views/postulante/inicio.blade.php`), subir el Informe de Práctica y descargar Certificado y Resolución (`CajBioBioPlataforma/resources/views/practicante/inicio.blade.php`, `CajBioBioPlataforma/app/Http/Controllers/Practicante/PracticanteController.php`).
  - **Lo faltante / diferencias:**
    1. **No existe interfaz para que el postulante acepte o rechace la vacante pre-asignada (`PreAsignada` -> `Confirmada` o `Desistida`)** ni suscriba en ese acto el Compromiso de Confidencialidad.
    2. Quien postula por el formulario sin ClaveÚnica no recibe usuario temporal al postular, sino recién cuando secretaría acepta su postulación (`CajBioBioPlataforma/app/Services/Postulaciones.php#L55-L84`).

- `[x]` **Perfil Secretaría Regional: Vista parametrizada para acceder y gestionar únicamente los consultorios, postulaciones y expedientes de su respectiva región.**
  - **Evidencia:** `CajBioBioPlataforma/app/Support/Scope.php#L14-L24` restringe todas las consultas de `Secretaria\*Controller` y `DocumentoController` al `region_id` asignado a la funcionaria. *(Observación: en `CajBioBioPlataforma/database/seeders/UsuariosDemoSeeder.php#L18-L19` están creadas las 2 secretarias de Biobío, pero falta poblar la funcionaria de secretaría de cada una de las 5 regiones externas `NB`, `AR`, `LR`, `LL`, `AI`).*

- `[ ]` **Perfil Secretaría Biobío (Jurídica Central): Módulo centralizado para recepcionar expedientes consolidados de todas las regiones, revisar consistencia de notas y gestionar los cierres institucionales.**
  - **Evidencia:** No existe un módulo centralizado en Concepción (Secretaría Jurídica de Biobío) donde las regiones externas remitan sus expedientes al concluir su competencia regional (Fase 3.3), ni interfaz para nivelar/subir de oficio una nota "regular" a "buena" ($\ge 5.0$) o rechazar por sanción disciplinaria fundada (Fase 3.4), ni despacho final de certificados firmados (Fase 3.5).

- `[x]` **Perfil Abogado Jefe / Consultorio Jurídico: Acceso limitado a los postulantes de su unidad para consultar alertas de término, revisar el informe final y completar la rúbrica de evaluación.**
  - **Evidencia:** Implementado bajo el rol `abogado` en `CajBioBioPlataforma/app/Http/Controllers/Tutor/TutorController.php` y `CajBioBioPlataforma/routes/portales/tutor.php`, restringido vía `Scope::abogadoId`.

- `[~]` **Perfil Director Regional: Vista ejecutiva con capacidad de aprobación por lotes, validación de la propuesta de nota final y flujo de firma de resoluciones y oficios.**
  - **Lo integrado:** Implementado bajo el rol `gobernador` en `CajBioBioPlataforma/app/Http/Controllers/Gobernador/ColaController.php` y `CajBioBioPlataforma/app/Services/GobernadorAutorizaciones.php`, con aprobación unitaria, por selección, por lote de $N$ más antiguas y total.
  - **Lo faltante / diferencias (Fase 2 y Fase 3.5):**
    1. No distingue entre **Director Regional** (quien valida la propuesta definitiva de notas de su zona externa y a cuyo nombre se emite la Carta de Presentación en regiones externas) y el **Director General de la Corporación** en Concepción (quien firma exclusivamente mediante **FirmaGob** el Certificado oficial para la Corte Suprema).
    2. Usa imagen PNG/JPG en base64 en lugar de descarga/resubida de PDF firmado con FirmaGob.

- `[x]` **Perfil Administrador General ("Modo Dios"): Usuario único nacional, separado e independiente de las secretarías, con acceso total a base de datos para soporte, asignación de regiones a secretarías, gestión global de cupos y deshabilitación de cuentas (nunca eliminación, para preservar auditoría).**
  - **Evidencia:** Implementado en `CajBioBioPlataforma/routes/portales/admin.php`, `CajBioBioPlataforma/app/Http/Controllers/Admin/PracticantesController.php`, `CajBioBioPlataforma/app/Http/Controllers/Admin/CapacidadController.php`, `CajBioBioPlataforma/app/Http/Controllers/Admin/UsuariosController.php` y `CajBioBioPlataforma/app/Services/UsuariosAdmin.php#L61-L82`.

---

## 2. Formulario de Postulación e Ingreso de Documentos

- `[~]` **Teléfono de emergencia obligatorio: Sustitución del correo de emergencia por un número de teléfono/celular de contacto obligatorio ante accidentes o urgencias.**
  - **Lo integrado:** `emergencia_telefono` es obligatorio en `CajBioBioPlataforma/app/Http/Controllers/Postulacion/PostulacionController.php#L214`.
  - **Lo faltante:** El campo `emergencia_email` sigue presente como opcional en `CajBioBioPlataforma/resources/views/postulacion/paso2.blade.php#L77-L79`, `CajBioBioPlataforma/resources/views/postulante/datos.blade.php#L85` y `CajBioBioPlataforma/resources/views/secretaria/_datos-postulante.blade.php#L21`.

- `[~]` **Depuración de antecedentes académicos: Eliminación definitiva de los campos de Enseñanza Media, Magíster y Año de carrera actual. Se mantiene la formación universitaria y el Año de egreso con carácter obligatorio.**
  - **Lo integrado:** Se eliminaron Enseñanza Media y Magíster; `universidad` es obligatorio (`CajBioBioPlataforma/resources/views/postulacion/paso3.blade.php#L15-L21`).
  - **Lo faltante:** Sigue existiendo el selector `anio_carrera` (*"Año de carrera"*: `5° año` / `Egresado`) en `CajBioBioPlataforma/resources/views/postulacion/paso3.blade.php#L23-L28` y `CajBioBioPlataforma/app/Http/Controllers/Postulacion/PostulacionController.php#L224`, en lugar de eliminarlo y pedir el **Año de egreso** obligatorio.

- `[x]` **Preguntas de accesibilidad e inclusión: Campo obligatorio para consultar si el postulante presenta alguna condición de discapacidad o requerimiento de accesibilidad.**
  - **Evidencia:** Implementado en `CajBioBioPlataforma/resources/views/postulacion/paso2.blade.php#L54-L64` y `CajBioBioPlataforma/app/Http/Controllers/Postulacion/PostulacionController.php#L207-L208`.

- `[x]` **Validación visual de errores: Aviso visible al inicio indicando que el asterisco (*) representa campos obligatorios. Si falta información, el sistema debe bloquear el envío, hacer scroll automático al campo faltante y resaltarlo en rojo con un mensaje descriptivo.**
  - **Evidencia:** Aviso en `CajBioBioPlataforma/resources/views/postulacion/paso2.blade.php#L10`, componente `CajBioBioPlataforma/resources/views/components/campo.blade.php` y funciones `cajValidar`, `marcarError` e `irAlPrimerError` en `CajBioBioPlataforma/resources/js/app.js#L45-L96`.

- `[~]` **Carga documental obligatoria en PDF: Casillas para subir cédula de identidad por ambos lados y certificado de egreso.**
  - **Lo faltante / diferencias:**
    1. En `CajBioBioPlataforma/app/Http/Controllers/Postulacion/PostulacionController.php#L261-L265` y `CajBioBioPlataforma/resources/views/postulacion/cedula.blade.php#L24-L38`, `cedula_frontal` y `cedula_reverso` solo aceptan imágenes (`jpg,jpeg,png`), rechazando archivos `.pdf`.
    2. En `CajBioBioPlataforma/app/Http/Controllers/Postulacion/PostulacionController.php#L31-L33`, `certificado_egreso` está clasificado como **opcional** (`TIPOS_OPCIONALES`), no obligatorio.

- `[x]` **Privacidad sin IA: Validación documental realizada visualmente por el personal, descartando el análisis de contenido o lectura de cédulas mediante Inteligencia Artificial por cumplimiento de la Ley N° 19.628 de Protección de Datos Personales.**
  - **Evidencia:** Sin análisis de IA/OCR en `CajBioBioPlataforma/composer.json` ni en `PostulacionController.php`; revisión visual por secretaría en `CajBioBioPlataforma/resources/views/secretaria/_documentos.blade.php`.

- **Selección de disponibilidad y preferencias:**
  - `[~]` **Selector de Región y Comuna (el postulante no elige consultorios específicos a ciegas):** En el Paso 4 (`CajBioBioPlataforma/resources/views/postulacion/paso4.blade.php`, `CajBioBioPlataforma/app/Http/Controllers/Postulacion/PostulacionController.php#L362-L378`) se listan todos los consultorios ordenados por cercanía geográfica a la comuna del domicilio con un buscador de texto, pero sin selectores de Región y Comuna en ese paso.
  - `[~]` **Selección de hasta 2 opciones de consultorios ordenados por prioridad de preferencia:** Guarda el orden de prioridad, pero no restringe a máximo 2 opciones (`'consultorios' => ['required', 'array', 'min:1']` sin `'max:2'` en `CajBioBioPlataforma/app/Http/Controllers/Postulacion/PostulacionController.php#L232`).
  - `[~]` **Visualización del semáforo dinámico de cupos por unidad (Verde: disponible, Amarillo: 1 cupo, Rojo: sin cupos):** No se muestra el semáforo al postulante en `CajBioBioPlataforma/resources/views/postulacion/paso4.blade.php`, y en `CajBioBioPlataforma/app/Services/Practicas.php#L65-L72` el color amarillo se calcula como `libres < capacidad / 2` en lugar de `libres === 1`.
  - `[x]` **Calendario interactivo para marcar la fecha probable de inicio, bloqueando fines de semana (sábados y domingos):** Implementado en `CajBioBioPlataforma/resources/js/postulacion.js#L168-L250` y `CajBioBioPlataforma/app/Support/Calendario.php#L15-L23`.

- `[ ]` **Identificador único: Generación automática de un código o folio único de postulación por registro.**
  - **Evidencia:** La tabla `postulaciones` (`CajBioBioPlataforma/database/migrations/2026_10_05_000003_crea_postulaciones_practicas.php#L41-L59`) solo tiene `id` autoincremental; no genera un código/folio único ni lo muestra en `CajBioBioPlataforma/resources/views/postulacion/enviada.blade.php`.

---

## 3. Flujo Operativo: Asignación, Confirmación e Inicio (y Fase 1 / Fase 2 Regional)

- `[x]` **Bandeja de revisión de Secretaría Regional: Visualización de postulantes entrantes organizados por orden de llegada y filtrados por jurisdicción.**
  - **Evidencia:** `CajBioBioPlataforma/app/Http/Controllers/Secretaria/PostulacionesController.php#L21-L52` filtra por `region_id` y ordena por `orderBy('fecha_postulacion')->orderBy('id')`.

- `[ ]` **Módulo de subsanación: Casilla para requerir al postulante la resubida de archivos observados a través de su portal, impidiendo correcciones por correo externo.**
  - **Evidencia:** No existe estado `observada` ni formulario en el portal del postulante para resubir documentos observados por la secretaría.

- **Gestión de cupos y alternativas (Camino Principal y Camino Alternativo Fase 1 en Regiones Externas):**
  - `[~]` **Camino principal: Pre-asignación del cupo si existe vacante en prioridad 1 o 2.** En `CajBioBioPlataforma/app/Services/Postulaciones.php#L33-L116`, secretaría asigna el consultorio pero el sistema crea de inmediato la práctica (`estado = 'aceptada'`), omitiendo el estado intermedio `PreAsignada`.
  - `[~]` **Flujo alternativo (Fase 1 Regiones Externas — Reasignación asistida y 3 bifurcaciones):**
    - Secretaría sí visualiza la ficha del candidato (nombre, especialidad de interés y teléfono obligatorio) en `CajBioBioPlataforma/resources/views/secretaria/postulaciones/ver.blade.php` y puede seleccionar cualquier consultorio cercano con cupo de su misma región.
    - **Brechas frente a las 3 bifurcaciones de Fase 1:**
      1. **Opción A (`PreAsignada` -> `Confirmada`):** No existe el paso donde secretaría deja la postulación en `PreAsignada` con el nuevo consultorio acordado para que el postulante confirme su vacante y firme digitalmente el Compromiso de Confidencialidad (pasando a `Confirmada`).
      2. **Opción B (`ListaEspera` con orden de prelación original):** No existe el estado `lista_espera` en `postulaciones` para mantener al postulante esperando que venza una práctica vigente respetando su `fecha_postulacion` original.
      3. **Opción C (`Desistida` archivada en historial sin sanción):** No existe el estado `desistida` (distinto de `rechazada`), ni aparece archivado en el módulo de Historial (`CajBioBioPlataforma/app/Http/Controllers/Secretaria/HistorialController.php` solo consulta la tabla `practicas`, no `postulaciones` desistidas). *(Nota positiva: en `PostulacionController.php#L121-L122` solo se bloquea re-postular si tiene una postulación `pendiente` o `derivada`, por lo que al quedar en `desistida` sí permite volver a postular en igualdad de condiciones).*

- `[ ]` **Aceptación formal del postulante: Interfaz para que el alumno acepte o rechace la vacante pre-asignada.**
  - **Evidencia:** Ausente en `CajBioBioPlataforma/resources/views/postulante/inicio.blade.php`.

- `[~]` **Compromiso de Confidencialidad en línea: Suscripción digital del compromiso de confidencialidad y secreto profesional al momento exacto de aceptar la práctica (no al llenar el formulario inicial).**
  - **Evidencia:** Actualmente está puesto en el Paso 1 del formulario inicial (`CajBioBioPlataforma/resources/views/postulacion/paso1.blade.php`), en lugar de firmarse al confirmar la vacante (`PreAsignada` -> `Confirmada`).

- `[ ]` **Asignación de Folio CAJ: Generación del número secuencial oficial de registro de práctica institucional.**
  - **Evidencia:** La tabla `practicas` no genera un Folio CAJ secuencial al confirmarse la práctica.

- **Flujo documental de Carta de Presentación (y Fase 2 Regional):**
  - `[ ]` **Autogeneración de la Carta de Presentación tipo con los datos del postulante y unidad (diferenciando Director Regional en regiones externas vs. Jefe de UCOE en Biobío):** No existe plantilla en `resources/views/pdf/` ni lógica territorial en `CajBioBioPlataforma/app/Services/CertificadosPractica.php`.
  - `[ ]` **Opción para que Secretaría descargue la carta, gestione la firma correspondiente (en representación del Director) y la vuelva a subir firmada:** No integrado.

- `[ ]` **Inicio Manual Oficial: Botón para que Secretaría valide la fecha real de ingreso del postulante y dé inicio formal al cómputo de la práctica.**
  - **Evidencia:** No existe botón de Inicio Manual Oficial condicionado a la subida de la Carta de Presentación firmada.

- `[~]` **Notificación de bienvenida: Envío de correo al postulante adjuntando carta firmada, reglamento interno y copia de aviso al Abogado Jefe.**
  - **Evidencia:** Solo crea notificaciones internas en base de datos (`CajBioBioPlataforma/app/Services/Postulaciones.php#L105-L112`), sin envío de correo con adjuntos al pulsar Inicio Manual.

---

## 4. Flujo de Desarrollo, Tiempos y Alertas

- `[x]` **Cómputo de plazo legal: Control del período reglamentario de 6 meses (180 días).**
  - **Evidencia:** Implementado en `CajBioBioPlataforma/config/caj.php#L7` y `CajBioBioPlataforma/app/Services/Practicas.php#L40-L60`.

- `[~]` **Alertas automáticas de término: Notificación automatizada al cumplirse 5 meses y medio (2 semanas antes del término) dirigida exclusivamente al Postulante y al Abogado Jefe de la unidad (excluyendo expresamente al Administrador Central para no saturar su bandeja).**
  - **Lo faltante / diferencias:** En `CajBioBioPlataforma/app/Services/Practicas.php#L15` la alerta está en `DIAS_POR_TERMINAR = 30` (no 14 días / 2 semanas), y en `CajBioBioPlataforma/app/Console/Commands/ActualizarEstadosPracticas.php#L25-L42` solo notifica al Abogado Jefe omitiendo al Postulante.

- `[ ]` **Liberación anticipada de cupos: La plataforma debe desvincular el cupo del consultorio automáticamente apenas el estudiante cumple los 6 meses de práctica, liberando la vacante para un nuevo postulante sin esperar los trámites burocráticos de revisión y firma.**
  - **Evidencia:** En `CajBioBioPlataforma/app/Models/Consultorio.php#L15-L19`, `ocupados()` cuenta toda práctica en estado `'por_iniciar'` o `'activa'` sin liberar el cupo cuando `fecha_termino <= today()`.

- `[~]` **Carga del Informe Final: Casilla en la sesión del postulante habilitada tras cumplir los 6 meses para cargar su informe en formato PDF (elaborado a partir de los antecedentes de causas del SAJ).**
  - **Diferencias:** En `CajBioBioPlataforma/app/Http/Controllers/Practicante/PracticanteController.php#L36` y `CajBioBioPlataforma/app/Services/InformesPractica.php#L18-L62`, la casilla está abierta desde el día 1 de la práctica y acepta `.doc/.docx` además de `.pdf`.

---

## 5. Flujo de Evaluación, Calificaciones y Cierre (y Fase 3 Regional / Traspaso a Biobío)

- `[x]` **Desbloqueo de calificación: El sistema debe condicionar la apertura de la evaluación a la carga efectiva del Informe de Práctica en PDF.**
  - **Evidencia:** Implementado en `CajBioBioPlataforma/app/Services/EvaluacionesPractica.php#L22-L24`.

- `[x]` **Primera Calificación (Abogado Jefe): Formulario tipo encuesta/rúbrica con escala de notas de 1.0 a 7.0 (evaluando los 7 factores reglamentarios) y campo para observaciones cualitativas.**
  - **Evidencia:** Implementado en `CajBioBioPlataforma/app/Support/Notas.php#L9-L17` y `CajBioBioPlataforma/app/Http/Controllers/Tutor/TutorController.php#L56-L73`.

- `[ ]` **Segunda Calificación condicional (Fase 3.2 Regional): Rombo de decisión y vista exclusiva para un segundo evaluador (Abogado Jefe de Segunda Instancia / Corte de Apelaciones, homólogo territorial a don Samuel Osorio en Biobío) que se habilita únicamente si tramitó causas/alegatos en Corte de Apelaciones o solicita revisión.**
  - **Evidencia:** Aunque existe la tabla `calificaciones` con columna `instancia`, no hay interfaz ni lógica para designar o habilitar al **Abogado Jefe de Segunda Instancia** ni para que este emita su calificación y observaciones complementando la nota de primera instancia.

- `[ ]` **Cierre de Competencia Regional, Consolidación y Nivelación de Notas de Oficio en Secretaría Jurídica Biobío (Fase 3.3 y 3.4):**
  - **Evidencia:**
    1. **Sin cierre de competencia regional ni envío a Concepción:** En `CajBioBioPlataforma/app/Services/EvaluacionesPractica.php#L30-L69`, apenas el Abogado Jefe evalúa, la práctica pasa directamente a `'finalizada'`, sin paso de empaquetado/envío de la Secretaría Regional hacia la Secretaría Jurídica de Biobío.
    2. **Escala de aprobación incorrecta en código (`4.0` en vez de `5.0` "Buena"):** En `CajBioBioPlataforma/app/Support/Notas.php#L7-L28`, el sistema define `MINIMA_APROBACION = 4.0` y etiqueta `4.0–4.4` como `'SUFICIENTE'` y `4.5–5.4` como `'BUENO'`. Sin embargo, la regla institucional establece que **para aprobar formalmente la calificación no puede ser "regular" ($< 5.0$) ni inferior a 5.0** (debe ser "buena" $\ge 5.0$, "muy buena" o "sobresaliente").
    3. **Sin atribución de subir nota de oficio a "Buena" ($5.0$) en Jurídica Biobío:** No existe la bandeja para que la Secretaría Jurídica de Biobío suba de oficio una nota "regular" a "buena" ($5.0$) cuando no hay causales disciplinarias graves, o tramite el rechazo formal si existen antecedentes sancionatorios fundados.

- `[~]` **Visto bueno de Dirección Regional: Módulo para que el Director Regional o jefatura valide la propuesta definitiva de cierre (individualmente o por lotes).**
  - **Evidencia:** Existe la cola de autorización en `CajBioBioPlataforma/app/Http/Controllers/Gobernador/ColaController.php`, pero hoy recibe las prácticas directamente desde el Abogado Tutor sin haber pasado por la consolidación/nivelación de Jurídica Biobío.

- **Autogeneración de los 4 documentos oficiales: Creación automática en PDF con los datos personales, fechas y calificaciones:**
  - `[x]` **Documento 1 (Postulante): Resolución de término de práctica** (`CajBioBioPlataforma/resources/views/pdf/resolucion.blade.php`).
  - `[x]` **Documento 2 (Postulante): Certificado de aprobación para la Corte Suprema** (`CajBioBioPlataforma/resources/views/pdf/certificado.blade.php`).
  - `[ ]` **Documento 3 (Interno): Oficio de traspaso a la Dirección Jurídica** (No integrado).
  - `[ ]` **Documento 4 (Interno): Resumen consolidado de evaluaciones y notas** (No integrado).

- `[~]` **Firma electrónica institucional (Fase 3.5): Módulo para descargar los documentos generados, estampar Firma Electrónica Avanzada (FirmaGob) exclusivamente por el Director General de la Corporación en Concepción y resubirlos firmados al repositorio del postulante.**
  - **Evidencia:** Actualmente solo incrusta una imagen PNG/JPG en base64 (`CajBioBioPlataforma/app/Services/GobernadorFirma.php`); no permite descargar los PDFs y resubirlos firmados con FirmaGob por el Director General.

- `[~]` **Despacho final y cierre (Fase 3.5): Envío de correo formal de cierre al postulante por parte de la Secretaría Jurídica de Biobío con sus documentos adjuntos y traspaso definitivo del expediente al estado `CerradaHistorial`.**
  - **Evidencia:** No existe el paso de despacho digital por correo desde la Secretaría Jurídica de Biobío ni el estado explícito `CerradaHistorial` tras el envío de los certificados firmados.

---

## 6. Módulo de Cancelaciones y Términos Anticipados (y Fase 2 Regional)

- `[x]` **Estado formal "Cancelada / Suspendida" (Interrupción anticipada sin evaluación ni reprobación, obligando a realizar los 6 meses desde cero si reingresa):**
  - **Evidencia:** En `CajBioBioPlataforma/app/Services/Practicas.php#L77-L115`, la práctica pasa a `'cancelada'` con `nota_practica = null` (no es reprobado con nota mala). Además, el esquema de base de datos (`practicas.practicante_id` no único) y el validador de postulación permiten que el RUT vuelva a postular en el futuro para cursar los 6 meses desde cero.

- `[~]` **Canal de solicitud sin autoservicio: No habilitar botón de renuncia en la vista del alumno; el postulante debe tramitarla obligatoriamente por correo formal, lo cual debe constar en los términos de postulación.**
  - **Evidencia:** No hay botón de renuncia en la vista del alumno (cumple), pero falta incluir la cláusula explícita en `CajBioBioPlataforma/resources/views/postulacion/paso1.blade.php`.

- `[x]` **Registro de causal y fecha: Selector tipificado de motivos de cancelación (ej. irresponsabilidad/mala práctica, motivos laborales, familiares/personales, suspensión temporal o salud) con registro automático de la fecha exacta.**
  - **Evidencia:** Implementado en `CajBioBioPlataforma/app/Services/Practicas.php#L17-L21` con `cancelacion_fecha => today()`.

- `[~]` **Respaldo con Oficio de Cancelación (Fase 2 Regional): Campo obligatorio para adjuntar el Oficio de Término Anticipado firmado por la Dirección Regional.**
  - **Evidencia:** El modal `CajBioBioPlataforma/resources/views/secretaria/practicas/_modal-cancelar.blade.php` no exige subir el PDF del oficio firmado por la Dirección Regional (solo autogenera un PDF simple en `resources/views/pdf/cancelacion.blade.php`).

- `[x]` **Liberación de cupo inmediata: La cancelación en curso debe restituir la vacante del consultorio de manera inmediata para destrabar la rotación.**
  - **Evidencia:** `CajBioBioPlataforma/app/Models/Consultorio.php#L17` libera el cupo en el acto al dejar el estado `'activa'`/`'por_iniciar'`.

- `[x]` **Constancia en expediente: El evento de término anticipado debe quedar registrado en la ficha del RUT dentro del historial.**
  - **Evidencia:** Registrado y visible en `CajBioBioPlataforma/resources/views/secretaria/historial/ver.blade.php#L20-L35`.

---

## 7. Vistas de Control, Reportes y Administración Central

- `[~]` **Módulo de Prácticas Activas: Vista operativa con filtros avanzados, buscador por RUT/nombre, enlace directo a los expedientes/documentos subidos y botón de exportación a Excel.**
  - **Lo faltante:** En `CajBioBioPlataforma/app/Http/Controllers/Secretaria/PracticasController.php#L106-L109` y `CajBioBioPlataforma/app/Http/Controllers/Admin/PracticantesController.php#L23-L27` el buscador no filtra por `rut`, y en `CajBioBioPlataforma/resources/views/secretaria/practicas/ver.blade.php` no se renderiza el listado de documentos del expediente.

- `[x]` **Módulo de Historial Regionalizado: Repositorio histórico de prácticas terminadas y canceladas por región, con trazabilidad de fechas, notas finales y enlace de descarga de antecedentes.**
  - **Evidencia:** Implementado en `CajBioBioPlataforma/app/Http/Controllers/Secretaria/HistorialController.php`. *(Se complementará para mostrar también postulaciones en estado `Desistida` de la Fase 1 Opción C).*

- **Gestión de cupos centralizada:**
  - `[x]` **Facilidad exclusiva del Administrador Central para aumentar o reducir las vacantes de cada unidad jurídica:** `CajBioBioPlataforma/app/Http/Controllers/Admin/CapacidadController.php`.
  - `[x]` **Bloqueo de seguridad que impida reducir cupos totales por debajo de la cantidad de postulantes activos en dicha unidad:** `CajBioBioPlataforma/app/Http/Controllers/Admin/CapacidadController.php#L47-L55`.

- `[x]` **Módulo de asignación territorial: Panel del Administrador General para vincular a cada funcionaria de secretaría con su región correspondiente.**
  - **Evidencia:** `CajBioBioPlataforma/app/Http/Controllers/Admin/UsuariosController.php#L73-L84`.

- `[~]` **Panel de Estadísticas y Reportes: Indicadores de tasas de ocupación por consultorio, prácticas activas vs. pendientes, tiempos de asignación y métricas de causales de cancelación anuales.**
  - **Lo faltante:** Faltan las métricas de **tiempos de asignación** y **causales de cancelación anuales** en `CajBioBioPlataforma/app/Http/Controllers/Admin/InicioController.php` y `CajBioBioPlataforma/app/Http/Controllers/Secretaria/PanelController.php`.

---

## 8. Seguridad, Auditoría y Aspectos Técnicos

- `[x]` **Compatibilidad de servidores CAJ: Backend y base de datos desarrollados nativamente en PHP y MySQL/SQL para asegurar compatibilidad con la infraestructura de la institución.**
  - **Evidencia:** `CajBioBioPlataforma/composer.json` y `CajBioBioPlataforma/docker-compose.yml`.

- `[~]` **Log inmutable de auditoría (Ley N° 19.628): Registro en base de datos que guarde automáticamente usuario, dirección IP, navegador, fecha/hora y detalle del cambio (con estructura JSON de `datos_anteriores` y `datos_nuevos`), especialmente en visualización de documentos sensibles, cambios de estados y modificaciones de notas.**
  - **Lo faltante:** En `CajBioBioPlataforma/app/Services/Auditoria.php` y `CajBioBioPlataforma/database/migrations/2026_10_05_000004_crea_evaluacion_documentos.php#L108-L120` falta almacenar `user_agent` (navegador), estandarizar las claves `datos_anteriores` y `datos_nuevos`, y auditar la visualización/descarga de documentos en `CajBioBioPlataforma/app/Http/Controllers/DocumentoController.php#L27-L44`.

- `[x]` **Gestión de bajas de personal: Deshabilitación o suspensión lógica de cuentas de usuarios administrativos en lugar de eliminación física en la base de datos.**
  - **Evidencia:** `CajBioBioPlataforma/app/Services/UsuariosAdmin.php#L61-L82`.

- `[x]` **Control de versión visible: Etiqueta visible del número de versión del sistema desplegado en la vista de administración.**
  - **Evidencia:** `CajBioBioPlataforma/resources/views/admin/inicio.blade.php#L21`.
