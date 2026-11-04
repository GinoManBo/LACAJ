# Manual de Usuario y Procedimientos — Perfiles Administrativos y Directivos
## Sistema de Gestión de Prácticas Profesionales — Corporación de Asistencia Judicial

| | |
|---|---|
| **Versión de la aplicación** | 2.0 |
| **Fecha del documento** | 4 de noviembre de 2026 |
| **Alcance** | Admisión (Fase 1), inicio oficial, seguimiento y gestión de cupos (Fase 2), visación directiva, revisión colegiada, nivelación jurídica, firma electrónica avanzada (FirmaGob) y cierre de prácticas profesionales (Fase 3) |
| **Perfiles cubiertos** | Secretaría Regional, Secretaría Biobío (Dirección Jurídica Central), Director Regional, Jefatura y Revisores UCOE, Director General, Administrador del Sistema |

---

## Índice

1. [Introducción y Fases del Proceso](#1-introducción-y-fases-del-proceso)
   - 1.1. Propósito del sistema y las 3 fases operativas
   - 1.2. Perfiles administrativos, en qué fases actúan y qué hace cada uno
   - 1.3. Inicio de sesión y elementos comunes de la interfaz
   - 1.4. Estados de una práctica en la plataforma
   - 1.5. Disponibilidad de cupos por consultorio
2. [Perfil SECRETARÍA REGIONAL](#2-perfil-secretaría-regional)
   - 2.1. Panel General
   - 2.2. Procedimiento en Fase 1: Revisar, aceptar, rechazar, derivar o importar postulaciones
   - 2.3. Procedimiento en Fase 2: Emitir y cargar la Carta de Presentación firmada (Inicio oficial)
   - 2.4. Procedimiento en Fase 2: Seguimiento de prácticas activas y cancelación anticipada
   - 2.5. Procedimiento en Fase 3: Carga asistida de evaluaciones y remisión a Jurídica Central Biobío
   - 2.6. Consulta de Consultorios e Historial Regional
3. [Perfil SECRETARÍA BIOBÍO — DIRECCIÓN JURÍDICA CENTRAL (Unificado)](#3-perfil-secretaría-biobío--dirección-jurídica-central-unificado)
   - 3.1. Qué distingue a este perfil y en qué fases actúa
   - 3.2. Procedimiento en Fase 1: Supervisión nacional de postulaciones y admisión local
   - 3.3. Procedimiento en Fase 3: Recepción nacional y revisión de expedientes remitidos
   - 3.4. Procedimiento en Fase 3: Nivelación de calificaciones en palabras
   - 3.5. Procedimiento en Fase 3: Rechazo fundado por sanción disciplinaria
   - 3.6. Procedimiento en Fase 3: Autorización y despacho final de documentos al postulante
4. [Perfil DIRECTOR REGIONAL](#4-perfil-director-regional)
   - 4.1. Ámbito y fases en las que actúa
   - 4.2. Procedimiento en Fase 2: Seguimiento regional y consulta de cupos
   - 4.3. Procedimiento en Fase 3: Visar o modificar la calificación de una práctica (Individual y por lotes)
5. [Perfil JEFATURA Y REVISORES UCOE (Revisión Colegiada)](#5-perfil-jefatura-y-revisores-ucoe-revisión-colegiada)
   - 5.1. Ámbito y fases en las que actúa
   - 5.2. Procedimiento en Fase 3: Asignar revisores colegiados (Individual y por lotes)
   - 5.3. Procedimiento en Fase 3: Revisión y dictamen por parte del revisor colegiado
6. [Perfil DIRECTOR GENERAL](#6-perfil-director-general)
   - 6.1. Ámbito nacional y fases en las que actúa
   - 6.2. Procedimiento en Fase 2: Supervisión ejecutiva nacional y catálogo de sedes
   - 6.3. Procedimiento en Fase 3: Descarga de certificados y suscripción con FirmaGob
7. [Perfil ADMINISTRADOR DEL SISTEMA](#7-perfil-administrador-del-sistema)
   - 7.1. Ámbito institucional y fases en las que actúa
   - 7.2. Procedimiento: Gestión de usuarios, roles y contraseñas
   - 7.3. Procedimiento: Mantenedor de consultorios y ajuste de capacidad de cupos
   - 7.4. Procedimiento: Gestión de fichas de postulantes, corrección de datos, traslados y cancelación
   - 7.5. Procedimiento: Consulta de estadísticas institucionales y exportación de reportes
   - 7.6. Procedimiento: Consulta y exportación del registro de auditoría
8. [Anexos](#8-anexos)
   - 8.1. Escala institucional de calificación en palabras y criterios evaluados
   - 8.2. Documentos oficiales que emite y gestiona el sistema
   - 8.3. Flujo resumido entre perfiles administrativos por fase
   - 8.4. Preguntas frecuentes

---

## 1. Introducción y Fases del Proceso

### 1.1. Propósito del sistema y las 3 fases operativas

El sistema centraliza y controla de punta a punta el ciclo reglamentario de 180 días (6 meses) de la práctica profesional de los postulantes al título de abogado, dividiendo su tramitación en **tres fases secuenciales**:

1. **Fase 1 — Postulación, Admisión y Asignación (Pre-Práctica):**
   - El postulante ingresa su solicitud adjuntando sus documentos obligatorios y seleccionando sus consultorios de preferencia.
   - La **Secretaría Regional** revisa los antecedentes, acepta la postulación asignando consultorio y Abogado Jefe (o la deriva a otra región) y el sistema autogenera la **Carta de Presentación** a nombre del Director Regional o del Jefe de la UCOE.
2. **Fase 2 — Inicio Oficial, Desarrollo de la Práctica y Control de Cupos (180 días):**
   - La **Secretaría Regional** sube a la plataforma la **Carta de Presentación firmada**, acto que da el **inicio oficial** a la práctica y notifica inmediatamente al Abogado Jefe.
   - A los 5,5 meses (14 días antes del término), el sistema emite alertas automáticas al postulante y al Abogado Jefe para preparar el cierre.
   - Al cumplirse el día 180 (fecha de término), **el cupo del consultorio se libera automáticamente** para recibir a nuevos postulantes, aunque el expediente continúe su tramitación de cierre.
3. **Fase 3 — Evaluación, Visación Directiva, Nivelación Central, FirmaGob y Despacho Final (Post-Práctica):**
   - Una vez calificada la práctica en palabras por el Abogado Jefe (primera instancia en consultorio y segunda instancia en Corte de Apelaciones cuando corresponda), el expediente pasa a revisión de la **Jefatura UCOE** (revisión colegiada cuando aplica) y del **Director Regional**, quien visa o modifica fundadamente la calificación.
   - Con la visación directiva, la **Secretaría Regional** remite el expediente a la **Secretaría Jurídica Central en Biobío**, cerrando la competencia de la región de origen.
   - La **Secretaría Jurídica Central** revisa el expediente, nivela la calificación en palabras cuando procede (o rechaza por sanción disciplinaria), el **Director General** suscribe el certificado con **Firma Electrónica Avanzada (FirmaGob)** y finalmente Jurídica Central autoriza el **despacho final** de los documentos al portal del postulante.

### 1.2. Perfiles administrativos, en qué fases actúan y qué hace cada uno

| Perfil | Vistas en el sistema | Fases en que actúa | Qué hace en la plataforma |
|---|---|---|---|
| **Secretaría Regional** | Panel General, Postulaciones, Prácticas Activas, Historial, Consultorios | **Fase 1, Fase 2 y Fase 3** *(en su región)* | **Fase 1:** Revisa documentos, acepta, rechaza o deriva postulaciones, asigna consultorio y Abogado Jefe.<br>**Fase 2:** Descarga la Carta de Presentación, sube la carta firmada para activar la práctica, hace seguimiento y tramita cancelaciones.<br>**Fase 3:** Realiza carga asistida de evaluaciones y remite el expediente visado a Jurídica Central Biobío. |
| **Secretaría Biobío (Dirección Jurídica Central)** | Todas las vistas de Secretaría Regional + Supervisión Nacional de Postulaciones y Recepción Nacional de Expedientes | **Fase 1, Fase 2 y Fase 3** *(Regional en Biobío y Nacional en Fase 1 y 3)* | **Fase 1:** Gestiona las postulaciones de Biobío y supervisa en modo lectura las postulaciones de todo el país.<br>**Fase 2:** Gestiona las prácticas activas de la Región del Biobío.<br>**Fase 3:** Recibe los expedientes remitidos desde todas las regiones, nivela calificaciones en palabras, rechaza por sanción disciplinaria y ejecuta el despacho final de certificados tras la firma del Director General. |
| **Director Regional** | Validación de Evaluaciones, Seguimiento Regional, Consultorios | **Fase 1, Fase 2 y Fase 3** *(en su región)* | **Fase 1:** Su nombre y cargo encabezan la Carta de Presentación de su región.<br>**Fase 2:** Supervisa el avance de prácticas y la ocupación de consultorios regionales.<br>**Fase 3:** Revisa el informe final y las calificaciones, y visa o modifica en palabras la evaluación (individualmente o por lotes), habilitando el envío a Biobío. |
| **Jefatura y Revisores UCOE** | Validación de Evaluaciones, Revisión Colegiada, Seguimiento, Consultorios | **Fase 1, Fase 2 y Fase 3** *(en su jurisdicción)* | **Fase 1 y 2:** Encabeza la Carta de Presentación UCOE y supervisa el avance de prácticas.<br>**Fase 3:** Distribuye los expedientes evaluados entre los tres revisores colegiados (Luis Núñez, Verónica Cifuentes y Graciela Paredes) y emite la visación directiva. |
| **Director General** | Bandeja Nacional de Expedientes (FirmaGob), Seguimiento Nacional, Sedes Nacionales | **Fase 2 y Fase 3** *(en todo el país)* | **Fase 2:** Supervisa el estado de las prácticas y consultorios a nivel nacional.<br>**Fase 3:** Descarga los certificados aprobados (individualmente o en lote) y sube el Certificado oficial firmado con **FirmaGob**, habilitando el despacho final. |
| **Administrador del Sistema** | Panel de Indicadores, Postulantes, Consultorios, Capacidad, Usuarios, Estadísticas, Auditoría | **Transversal** *(Pre-Fase 1, Fase 2 y Fase 3 en todo el país)* | Crea y administra cuentas de usuarios y roles, mantiene el catálogo de 192 consultorios y sus cupos, corrige fichas de postulantes, ejecuta traslados excepcionales de sede y consulta estadísticas globales y el registro inmutable de auditoría. |

> **Importante:** Cada perfil ingresa con su propia cuenta institucional y solo ve las opciones autorizadas para su cargo. Las cuentas regionales (Secretaría Regional, Director Regional y Jefatura UCOE) ven únicamente la información de su propia región, mientras que Jurídica Central, el Director General y el Administrador tienen visibilidad nacional según sus atribuciones.

### 1.3. Inicio de sesión y elementos comunes de la interfaz

**Cómo ingresar al sistema:**
1. En la pantalla de bienvenida, ingrese su **RUT** (con o sin puntos y con guion) y su **contraseña institucional**, o bien pulse **Iniciar sesión con ClaveÚnica**.
2. Si ingresa por primera vez con una clave provisoria entregada por el Administrador, el sistema le pedirá definir una **nueva contraseña personal** antes de mostrar su panel de trabajo.

**Elementos comunes en pantalla:**
- **Menú lateral (izquierda):** Muestra las secciones disponibles para su perfil. Al pie indica su nombre, cargo y el botón para cerrar sesión.
- **Barra superior:** Muestra el título de la sección activa, la región o alcance de su cuenta y el icono de **campana de notificaciones** con contador rojo de avisos sin leer.
- **Notificaciones:** Al pulsar la campana puede revisar sus avisos pendientes (*Urgente*, *Aviso* o *Informativo*) y marcarlos como leídos individualmente o con el botón **Marcar todas como leídas**.

### 1.4. Estados de una práctica en la plataforma

| Estado visible | Fase | Significado operativo |
|---|---|---|
| **Por iniciar** | Fase 1 → 2 | Postulación aceptada y con consultorio asignado, pendiente de subir la Carta de Presentación firmada |
| **En curso** | Fase 2 | Carta de Presentación firmada cargada; la práctica se encuentra en desarrollo |
| **Por terminar** | Fase 2 | Práctica en curso a la que le restan 14 días o menos para cumplir los 180 días reglamentarios |
| **Finalizada** | Fase 3 | Cumplida y calificada por el Abogado Jefe; pendiente de visación directiva y remisión a Biobío |
| **Remitida a Biobío** | Fase 3 | Visada por la Dirección Regional y enviada a Jurídica Central; queda en solo lectura para la región de origen |
| **Nivelada en Biobío** | Fase 3 | Calificación revisada o nivelada en palabras por Jurídica Central; lista para firma del Director General |
| **Rechazada por sanción** | Fase 3 | Reprobada en Jurídica Central por causal disciplinaria fundada; bloquea la emisión de certificados |
| **Cerrada en Historial** | Fase 3 | Suscrita con FirmaGob y despachada por Jurídica Central; documentos disponibles para el postulante |
| **Cancelada** | Fase 2 / 3 | Interrumpida anticipadamente; libera el cupo de inmediato y queda registrada con su motivo |

### 1.5. Disponibilidad de cupos por consultorio

| Indicador | Condición |
|---|---|
| 🟢 **Muchos cupos** | La mitad o más de la capacidad del consultorio está libre |
| 🟠 **Pocos cupos** | Queda menos de la mitad de la capacidad disponible |
| 🔴 **Sin cupos** | No hay cupos libres en la unidad |

---

## 2. Perfil SECRETARÍA REGIONAL

**Quién lo usa:** La secretaria responsable de una región operativa.  
**Ámbito:** Ve y gestiona exclusivamente los consultorios, postulaciones, prácticas e historial de su región asignada.  
**En qué fases actúa:** Actúa en la **Fase 1** (admisión y asignación), **Fase 2** (inicio con carta firmada, seguimiento y cancelación) y **Fase 3** (carga asistida y remisión del expediente visado a Concepción).

### 2.1. Panel General

Vista de entrada al iniciar sesión. Muestra tres indicadores en tiempo real de su región:
- **Postulaciones pendientes** por revisar.
- **Prácticas activas** en curso.
- **Por terminar** en los próximos 14 días.

Debajo se listan las **Últimas postulaciones** recibidas y las **Prácticas por terminar**, cada una con acceso directo a la vista completa.

### 2.2. Procedimiento en Fase 1: Revisar, aceptar, rechazar, derivar o importar postulaciones

**Paso a paso para revisar y resolver una postulación:**

1. Abra **Postulaciones** en el menú lateral. Se listan las postulaciones pendientes dirigidas a su región, con buscador por nombre, RUT o universidad.
2. **Revisar antecedentes:** Haga clic sobre el postulante para abrir su ficha completa.
   - Verifique sus datos personales, domicilio, contacto de emergencia y orden de preferencia de consultorios.
   - Revise los archivos adjuntos, separados en **Cédula de identidad** (anverso y reverso) y **Documentos universitarios y legales** (Certificado de Egreso o Licenciatura, Certificado de Antecedentes para Fines Especiales y fotografía tamaño carnet), usando los botones **Ver** o **Descargar**.
   - Si falta alguna de las caras de la cédula, el sistema muestra una advertencia indicando que no es posible validar la identidad.
3. **Registrar comentarios internos (opcional):** Escriba una observación en el recuadro de notas y pulse **Agregar comentario**. La nota queda registrada a nombre de su secretaría y **acompaña al expediente aunque se derive a otra región**. Sus propios comentarios pueden borrarse con el botón de eliminar.
4. **Decidir sobre la postulación:**
   - **Para rechazar:** Pulse **Rechazar**, escriba el motivo del rechazo y confirme. La postulación sale del listado de pendientes.
   - **Para aceptar o derivar:** En la sección de asignación, seleccione el consultorio en el listado:
     - Primero aparece el consultorio elegido por el postulante (etiqueta *POSTULÓ AQUÍ*), luego los demás de su región y al final los de otras regiones (etiqueta *OTRA REGIÓN*), cada uno con su indicador de cupos disponibles.
     - Si asigna un consultorio de su propia región, seleccione opcionalmente al **Abogado Jefe** y pulse el botón verde **Confirmar ingreso**.
     - Si selecciona un consultorio de otra región, pulse el botón naranjo **Derivar a otra región**.

**Resultado:**
- Si confirmó el ingreso en su región, se crea la práctica en estado **Por iniciar** (calculando automáticamente los 180 días reglamentarios) y el sistema muestra en pantalla la contraseña temporal creada para el postulante.
- Si derivó a otra región, la postulación pasa a la bandeja de la secretaría de destino junto con sus comentarios.

> **Recomendación:** Antes de derivar a otra región, agregue siempre un comentario indicando el motivo (falta de cupos, cercanía al domicilio, cambio de ciudad). Es la información de contexto que verá la secretaría que recibe el caso.

**Procedimiento para importar nóminas de postulaciones desde Excel:**
1. En la vista **Postulaciones**, ubique el bloque de importación masiva.
2. Seleccione la planilla Excel desde su equipo y pulse **Importar**.
3. El sistema procesa las filas y muestra un mensaje con la cantidad de postulaciones creadas y aquellas omitidas por estar duplicadas.

### 2.3. Procedimiento en Fase 2: Emitir y cargar la Carta de Presentación firmada (Inicio oficial)

> **Importante:** Una práctica recién aceptada permanece en estado **Por iniciar** y no notifica al Abogado Jefe hasta que la Secretaría Regional sube la Carta de Presentación firmada.

1. Abra **Prácticas Activas** en el menú lateral y haga clic en el postulante que se encuentra en estado **Por iniciar**.
2. En la ficha de la práctica, pulse **Descargar Carta de Presentación**.
   - El sistema genera automáticamente el documento oficial en PDF dirigido al Abogado Jefe del consultorio asignado, con los datos del postulante y el pie de firma correspondiente al **Director Regional** (o al **Jefe de la UCOE** según la jurisdicción).
3. Gestione la firma física o electrónica del documento por parte del Director Regional o Jefe UCOE.
4. De vuelta en la misma ficha de la práctica, vaya al bloque **Subir Carta de Presentación Firmada**, seleccione el archivo PDF firmado (máximo 5 MB) y pulse **Subir carta firmada**.

**Resultado:** La práctica cambia automáticamente a estado **En curso**, el documento queda respaldado para consulta permanente con el botón **Ver carta firmada** y el sistema envía una notificación inmediata al **Abogado Jefe** informándole la incorporación oficial del postulante.

### 2.4. Procedimiento en Fase 2: Seguimiento de prácticas activas y cancelación anticipada

1. Abra **Prácticas Activas**. En la parte superior verá los **Cupos disponibles** y el **Total de cupos** de su región, junto con el botón **Exportar a Excel** para descargar la nómina completa con días transcurridos y porcentaje de avance.
2. En cada fila de la tabla dispone de las siguientes acciones:
   - **Ver detalle:** Abre la ficha completa de la práctica.
   - **Ver estadísticas:** Muestra el desglose de duración total (180 días), días transcurridos, días restantes y barra de progreso.
3. **Para cancelar anticipadamente una práctica:**
   1. Ingrese al detalle de la práctica o pulse la opción de gestión/cancelación.
   2. Seleccione obligatoriamente el **motivo de cancelación**: *Conducta del practicante*, *Decisión del practicante*, *Error de registro* u *Otro motivo*.
   3. Complete el detalle explicativo en el campo de texto y pulse **Cancelar práctica** (sin motivo seleccionado el botón permanece deshabilitado).

**Resultado:** La práctica pasa a estado **Cancelada**, **el cupo del consultorio se libera de inmediato** y el sistema habilita un enlace para descargar el **Comprobante oficial de cancelación** en PDF.

### 2.5. Procedimiento en Fase 3: Carga asistida de evaluaciones y remisión a Jurídica Central Biobío

**A. Cómo registrar una calificación de Segunda Instancia (Carga asistida):**
Cuando el postulante realiza alegatos ante la Corte de Apelaciones y la evaluación llega por oficio o soporte físico a la secretaría:
1. Abra la ficha de la práctica desde **Prácticas Activas**.
2. En la sección **Calificación de Segunda Instancia (Corte de Apelaciones)**, seleccione el concepto en palabras (**Sobresaliente**, **Buena**, **Regular** o **Mala**).
3. Escriba las observaciones de los alegatos (mínimo 10 caracteres) y pulse **Registrar calificación**.
4. El concepto queda guardado en el expediente como segunda instancia.

**B. Cómo cerrar la competencia regional y remitir el expediente a Jurídica Central Biobío:**
Una vez que el Abogado Jefe ha registrado la evaluación final de la práctica, esta pasa a la vista de **Historial** en estado **Finalizada**.

1. Abra **Historial** en el menú lateral y haga clic sobre el postulante.
2. Revise las pestañas de **Resumen**, **Documentos** y **Calificaciones** (donde se muestran en palabras los conceptos de los 7 criterios evaluados).
3. Verifique el estado de visación directiva:
   - Si el Director Regional o Jefe UCOE **aún no ha visado** la evaluación, verá un aviso indicando que falta la visación directiva y el botón de envío estará bloqueado.
   - Cuando el Director Regional visa la práctica, usted recibirá una notificación en su campana y el botón quedará habilitado.
4. Pulse **Remitir expediente a Jurídica Central (Biobío)** y confirme la operación.

**Resultado:** El estado cambia a **Remitida a Biobío**, se cierra la competencia regional (**el expediente queda en modo solo lectura para su región**) y la Secretaría Jurídica Central en Concepción recibe una notificación automática para continuar con la nivelación y el cierre.

### 2.6. Consulta de Consultorios e Historial Regional

- **Consultorios:** Muestra las tarjetas de todas las unidades de su región con su dirección, barra de ocupación y semáforo de disponibilidad. Al pulsar una tarjeta se despliega la lista de postulantes actualmente asignados a esa sede. *(La cantidad de cupos solo puede ser modificada por el Administrador).*
- **Historial:** Permite buscar prácticas cerradas o en trámite de cierre por nombre, RUT o año de inicio, y descargar el Certificado, la Resolución o el Expediente completo una vez que han sido autorizados.

---

## 3. Perfil SECRETARÍA BIOBÍO — DIRECCIÓN JURÍDICA CENTRAL (Unificado)

**Quién lo usa:** Las secretarias asignadas a la Región del Biobío y el personal de la Dirección Jurídica Central en Concepción.  
**Ámbito:** **Dual (Regional y Nacional)**. Ejerce todas las funciones territoriales de la Región del Biobío (sección 2) y, adicionalmente, posee las atribuciones exclusivas de **Dirección Jurídica Central** para todo el país.  
**En qué fases actúa:**
- **Fase 1:** Gestiona directamente las postulaciones de la Región del Biobío y **supervisa en modo lectura** las postulaciones de todas las demás regiones.
- **Fase 2:** Gestiona el inicio oficial y seguimiento de las prácticas de la Región del Biobío.
- **Fase 3:** Recibe los expedientes remitidos desde todas las regiones del país, **nivela las calificaciones en palabras**, resuelve rechazos por sanción disciplinaria y ejecuta el **despacho final de certificados** al postulante tras la firma del Director General.

### 3.1. Qué distingue a este perfil y en qué fases actúa

En su **Panel General**, además de los indicadores regionales del Biobío, este perfil visualiza un bloque destacado denominado **Atribuciones de Jurídica Central (Nacional)** con dos accesos exclusivos:
1. **Supervisión Nacional de Postulaciones** (Fase 1 — Antes de la práctica).
2. **Recepción Nacional de Expedientes** (Fase 3 — Después de la práctica).

### 3.2. Procedimiento en Fase 1: Supervisión nacional de postulaciones y admisión local

1. Ingrese a **Supervisión Nacional de Postulaciones**.
2. En la parte superior verá tres contadores nacionales: **Total pendientes**, **Locales (Biobío)** y **Externas (Otras regiones)**.
3. Utilice los filtros para ver todas las postulaciones del país, solo las locales de Biobío o las de una región externa específica, o pulse **Exportar a Excel** para descargar el reporte nacional.
4. **Regla de operación según el origen:**
   - **Si la postulación pertenece a Biobío:** Puede abrirla, revisar sus documentos y pulsar **Aceptar** (asignando consultorio) o **Rechazar**.
   - **Si la postulación pertenece a otra región:** Puede abrirla y consultar sus antecedentes en **modo solo lectura**, ya que la asignación de cupos corresponde exclusivamente a la Secretaría Regional de origen.

### 3.3. Procedimiento en Fase 3: Recepción nacional y revisión de expedientes remitidos

1. Ingrese a **Recepción Nacional de Expedientes** (Jurídica Central).
2. Verá los indicadores nacionales de control: **Recibidos**, **Niveladas**, **Cerradas en historial** y **Rechazadas por sanción**, además del desglose entre expedientes locales de Biobío y externos. Puede descargar la planilla completa con **Exportar a Excel**.
3. Filtre por estado, origen o región y haga clic sobre un postulante para abrir su **Ficha de Revisión Nacional**.
4. En la ficha revise:
   - El cumplimiento de los 180 días y los documentos del postulante.
   - Los conceptos en palabras de los **7 criterios evaluados en primera instancia** (consultorio), la calificación de **segunda instancia** (Corte de Apelaciones, pudiendo registrarla desde esta misma pantalla si aún no estaba cargada) y la visación del Director Regional.

### 3.4. Procedimiento en Fase 3: Nivelación de calificaciones en palabras

Cuando corresponde ajustar o nivelar de oficio la calificación de un expediente remitido (por ejemplo, nivelar un concepto **Regular** a **Buena** conforme a la normativa institucional, o ajustar criterios individuales):

1. Dentro de la **Ficha de Revisión Nacional** del expediente, ubique el formulario **Nivelación / Modificación de Calificación Institucional**.
2. Revise la preselección sugerida por el sistema (si el expediente viene con concepto **Regular**, el sistema sugiere automáticamente **Buena**).
3. Seleccione el **Concepto General Final** en palabras (**Sobresaliente**, **Buena**, **Regular** o **Mala**). Si lo requiere, también puede modificar en palabras cualquiera de los **7 criterios individuales**.
4. En el campo **Justificación institucional**, redacte obligatoriamente el fundamento de la modificación (mínimo 10 caracteres).
5. Pulse **Confirmar nivelación**.

**Resultado:** La calificación queda fijada en palabras como tercera instancia institucional, el expediente pasa a estado **Nivelada en Biobío**, se notifica al postulante y el caso queda disponible en la bandeja del **Director General** para la firma electrónica avanzada (**FirmaGob**).

### 3.5. Procedimiento en Fase 3: Rechazo fundado por sanción disciplinaria

Si el postulante registra antecedentes disciplinarios graves que impiden aprobar su práctica:

1. Dentro de la **Ficha de Revisión Nacional**, vaya a la sección **Rechazo por Causal Disciplinaria**.
2. Escriba los antecedentes y fundamentos de la sanción (mínimo 10 caracteres).
3. Pulse **Rechazar por sanción** y confirme.

**Resultado:** El expediente queda en estado **Rechazada por sanción**, el sistema bloquea de forma permanente la emisión de certificados de aprobación y envía una notificación urgente al postulante.

### 3.6. Procedimiento en Fase 3: Autorización y despacho final de documentos al postulante

> **Importante:** El botón de despacho final permanece bloqueado hasta que se cumplen dos requisitos: que la práctica tenga calificación aprobatoria en palabras y que el **Director General** haya cargado el Certificado suscrito con **FirmaGob**.

1. Cuando el Director General sube el certificado firmado con FirmaGob, usted recibirá una notificación en su campana: *"Certificado con FirmaGob listo para despacho"*.
2. Abra la **Ficha de Revisión Nacional** de ese postulante.
3. En el bloque de documentos oficiales, puede descargar y verificar:
   - **Descargar Certificado Corte Suprema** (incluye el documento suscrito con FirmaGob).
   - **Descargar Resolución de Aprobación** (con los conceptos en palabras de las 7 dimensiones).
   - **Descargar Expediente Completo** (paquete comprimido con todos los antecedentes y certificados para remisión a la Excelentísima Corte Suprema).
4. Pulse el botón **Autorizar y despachar al postulante**.

**Resultado:** El expediente pasa a su estado final **Cerrada en Historial**, los documentos quedan desbloqueados en el **Portal del Postulante** para descarga directa y el egresado recibe un aviso automático informándole que sus certificados están disponibles.

---

## 4. Perfil DIRECTOR REGIONAL

**Quién lo usa:** El Director Regional a cargo de una región operativa.  
**Ámbito:** Ve y supervisa exclusivamente los consultorios y postulantes de su región asignada.  
**En qué fases actúa:**
- **Fase 1:** Su nombre y cargo figuran oficialmente en la Carta de Presentación autogenerada de su región.
- **Fase 2:** Consulta el avance de las prácticas en curso y la ocupación de los consultorios regionales.
- **Fase 3:** Revisa el informe final y las evaluaciones de su región, y **visa o modifica en palabras la calificación final** (individualmente o por lotes), requisito obligatorio para que la Secretaría Regional pueda remitir el expediente a Concepción.

### 4.1. Ámbito y fases en las que actúa

Al ingresar a su portal, el Director Regional accede a tres secciones en el menú lateral:
- **Validación de Evaluaciones** (bandeja principal de Fase 3).
- **Seguimiento Regional** (monitoreo de Fase 2 y 3).
- **Consultorios** (ocupación de cupos de su región).

### 4.2. Procedimiento en Fase 2: Seguimiento regional y consulta de cupos

1. Abra **Seguimiento Regional**.
2. Filtre el listado de prácticas de su región por consultorio, por estado o buscando por nombre o RUT del postulante.
3. Pulse **Exportar a Excel** si desea descargar el reporte completo de su región con fechas de inicio, término, estado y si cuenta con visación directiva.
4. Para revisar la capacidad instalada, abra **Consultorios**: verá cada unidad operativa de su región con su dirección, abogados asignados, cupos ocupados y cupos libres según el semáforo de disponibilidad.

### 4.3. Procedimiento en Fase 3: Visar o modificar la calificación de una práctica (Individual y por lotes)

En la vista **Validación de Evaluaciones** se muestran tres contadores de su región: **Pendientes de visación**, **Validadas** y **Total activas**.

**Opción A — Procedimiento para revisar y visar (o modificar) un expediente individualmente:**

1. En el listado de **Pendientes**, haga clic sobre el postulante para abrir la **Ficha de Validación Directiva**.
2. Revise los antecedentes del caso:
   - Abra o descargue el **Informe Final de Práctica** subido por el postulante.
   - Revise los conceptos en palabras otorgados por el Abogado Jefe en cada uno de los **7 criterios de primera instancia** y, cuando corresponda, el concepto de **segunda instancia** (Corte de Apelaciones).
3. En la sección de resolución directiva, elija una de las dos opciones reglamentarias:
   - **Ratificar el concepto propuesto:** Deje seleccionado el mismo concepto en palabras que propuso el Abogado Jefe, agregue observaciones opcionales y pulse **Visar evaluación**.
   - **Modificar la calificación:** Seleccione el nuevo concepto final en palabras (**Sobresaliente**, **Buena**, **Regular** o **Mala**). Al cambiar el concepto, es **obligatorio** escribir el fundamento en el campo **Observaciones de la Dirección** (sin justificación el sistema no permite guardar el cambio). Luego pulse **Visar evaluación**.

**Opción B — Procedimiento para visar múltiples expedientes en lote:**

1. Si está conforme con las calificaciones propuestas de varios postulantes en la tabla principal de **Validación de Evaluaciones**, marque las casillas de selección a la izquierda de cada fila.
2. Pulse el botón **Validar seleccionados en lote**.

**Resultado (en ambas opciones):** El expediente queda formalmente visado con fecha y firma de la Dirección Regional, y el sistema envía una notificación automática a la **Secretaría Regional** informándole que ya puede remitir el expediente a Jurídica Central Biobío.

---

## 5. Perfil JEFATURA Y REVISORES UCOE (Revisión Colegiada)

**Quién lo usa:** El Jefe de la Unidad de Coordinación de Prácticas (UCOE, don Luis Núñez Oviedo) y las Revisoras Colegiadas designadas (doña Verónica Cifuentes y doña Graciela Paredes).  
**Ámbito:** Jurisdicción regional correspondiente a la UCOE.  
**En qué fases actúa:** Actúa en **Fase 1** (encabezando la Carta de Presentación emitida por UCOE), **Fase 2** (seguimiento de prácticas y cupos) y **Fase 3** (distribución de carga entre revisores colegiados y visación directiva de evaluaciones).

### 5.1. Ámbito y fases en las que actúa

Además de todas las funciones descritas para el Director Regional en la sección 4, este perfil cuenta en el menú lateral con el módulo exclusivo **Revisión Colegiada UCOE**, diseñado para distribuir la revisión de los expedientes evaluados entre los tres integrantes colegiados antes de emitir la visación formal.

### 5.2. Procedimiento en Fase 3: Asignar revisores colegiados (Individual y por lotes)

**Quién lo ejecuta:** La Jefatura de UCOE.

1. Abra **Revisión Colegiada UCOE** en el menú lateral.
2. Puede filtrar los expedientes evaluados por revisor asignado (*Todos*, *Sin asignar*, *Luis Núñez Oviedo*, *Verónica Cifuentes* o *Graciela Paredes*) o buscar por nombre o RUT del postulante.
3. **Para asignar un expediente individualmente:**
   - En la fila del postulante, despliegue el selector de revisor, elija a uno de los tres revisores colegiados y pulse **Asignar**.
4. **Para asignar varios expedientes en lote:**
   - Marque las casillas de selección de los expedientes que desea distribuir.
   - En la barra superior de asignación masiva, seleccione el nombre del revisor colegiado y pulse **Asignar lote**.

**Resultado:** Los expedientes quedan vinculados al revisor elegido y el sistema envía una notificación interna inmediata al revisor informándole los casos asignados a su bandeja.

### 5.3. Procedimiento en Fase 3: Revisión y dictamen por parte del revisor colegiado

1. Cuando ingresa al sistema una revisora colegiada (doña Verónica Cifuentes o doña Graciela Paredes) o el Jefe UCOE, al abrir **Revisión Colegiada UCOE** la bandeja filtra automáticamente los expedientes asignados a su nombre.
2. Haga clic en el expediente para abrir su ficha detallada.
3. Revise el **Informe Final de Práctica** y los conceptos en palabras de los 7 criterios evaluados por el Abogado Jefe.
4. Emita la validación ratificando el concepto en palabras o modificándolo con su debida observación fundamentada, habilitando así la remisión del expediente a Jurídica Central.

---

## 6. Perfil DIRECTOR GENERAL

**Quién lo usa:** El Director General de la Corporación de Asistencia Judicial.  
**Ámbito:** **Nacional** (ve los expedientes, prácticas y consultorios de todas las regiones).  
**En qué fases actúa:** Actúa en la **Fase 2** (supervisión ejecutiva nacional de prácticas y sedes) y en la **Fase 3** (descarga de borradores y suscripción del Certificado oficial con **Firma Electrónica Avanzada del Estado — FirmaGob**).

### 6.1. Ámbito nacional y fases en las que actúa

El portal del Director General cuenta con tres secciones principales:
- **Bandeja Nacional de Firma (FirmaGob)** (vista de entrada enfocada en el cierre de Fase 3).
- **Seguimiento Nacional** (supervisión de prácticas de las 6 regiones en Fase 2 y 3).
- **Catálogo Nacional de Sedes** (estado de ocupación de las 192 unidades operativas).

### 6.2. Procedimiento en Fase 2: Supervisión ejecutiva nacional y catálogo de sedes

1. Abra **Seguimiento Nacional**.
2. Puede acotar la consulta eligiendo una **Región**, un **Consultorio** o un **Estado** de práctica, o buscar directamente por nombre o RUT del postulante.
3. Pulse **Exportar seguimiento nacional a Excel** para obtener una planilla consolidada de todo el país con el estado de avance, visación regional y estado de firma de cada práctica.
4. En **Catálogo Nacional de Sedes** puede consultar por región todas las unidades operativas activas, sus abogados tutores y sus cupos libres.

### 6.3. Procedimiento en Fase 3: Descarga de certificados y suscripción con FirmaGob

En la **Bandeja Nacional de Firma** se muestran tres indicadores globales: **Pendientes de firma**, **Firmadas con FirmaGob** y **Total nacional**.

**Paso 1 — Descargar los certificados para firmarlos en FirmaGob (Individual o por lotes):**

- **Para descargar por lotes (recomendado para firma masiva):**
  1. En la tabla de expedientes pendientes de firma, marque las casillas de los postulantes que desea firmar.
  2. Pulse el botón **Descargar lote para FirmaGob**.
  3. Se descargará un paquete comprimido que contiene todos los Certificados para la Corte Suprema de los postulantes seleccionados, listos para ser subidos y firmados transversalmente en el portal estatal de FirmaGob.
- **Para descargar individualmente:**
  1. Haga clic sobre el postulante para abrir su **Ficha de Firma**.
  2. Pulse **Descargar Certificado** (y opcionalmente **Descargar Resolución** para consultar sus conceptos en palabras).

**Paso 2 — Subir el Certificado ya firmado con FirmaGob:**

1. Una vez firmado el documento con Firma Electrónica Avanzada, ingrese a la **Ficha de Firma** del postulante correspondiente.
2. En el bloque **Integración con FirmaGob**, seleccione el archivo PDF firmado desde su equipo (máximo 10 MB).
3. Opcionalmente, ingrese el **Folio de FirmaGob** y la **Dirección web de verificación** del documento.
4. Pulse **Subir Certificado FirmaGob**.

**Resultado:** El sistema verifica la integridad del documento, lo almacena como el Certificado oficial definitivo del postulante, marca el expediente como **Firmado con FirmaGob** y envía una notificación automática a la **Secretaría Jurídica Central en Biobío** para que realice la revisión final y el despacho al portal del postulante.

---

## 7. Perfil ADMINISTRADOR DEL SISTEMA

**Quién lo usa:** El encargado institucional de la administración técnica y estructural de la plataforma.  
**Ámbito:** **Nacional** (ve todas las regiones, sedes, usuarios y registros).  
**En qué fases actúa:** Actúa de forma **transversal** en todo el ciclo:
- **Antes de la Fase 1:** Configura las cuentas de usuario, roles, regiones asignadas y mantiene el catálogo oficial de los 192 consultorios y su capacidad de cupos.
- **Durante la Fase 2 y Fase 3:** Brinda soporte excepcional corrigiendo fichas de postulantes, ejecutando traslados de sede cuando se requiere reubicar a un postulante, consultando estadísticas globales y auditando la trazabilidad del sistema. *(Por regla de gobernanza, el Administrador no interviene en la visación jurídica ni recibe alertas diarias de término de práctica).*

### 7.1. Ámbito institucional y fases en las que actúa

En su vista de inicio, el Administrador observa los indicadores nacionales de prácticas activas, postulaciones pendientes, porcentaje de ocupación de cupos, usuarios activos y capacidad total de la red.

### 7.2. Procedimiento: Gestión de usuarios, roles y contraseñas

Los roles institucionales que puede crear y administrar son: **Administrador**, **Director General**, **Director Regional**, **Jefatura UCOE**, **Dirección Jurídica Central**, **Secretaría Regional** y **Abogado Jefe**.

**A. Crear un usuario nuevo:**
1. Abra **Usuarios y roles** y pulse **Nuevo usuario**.
2. Complete **Nombre**, **Apellido**, **RUT** (el sistema valida el dígito verificador y que no esté repetido), **Correo electrónico** y seleccione el **Rol**.
3. **Configuración según el rol elegido:**
   - Si el rol es **Secretaría Regional**, **Director Regional** o **Jefatura UCOE**, es obligatorio seleccionar la **Región** a su cargo (si asigna una Secretaría a la Región del Biobío, la cuenta adquiere automáticamente las atribuciones unificadas de Jurídica Central).
   - Si el rol es **Abogado Jefe**, puede seleccionar uno o más **Consultorios** donde ejerce como tutor y marcar la casilla **Es Segunda Instancia (Corte de Apelaciones)** si evalúa alegatos en Corte (en cuyo caso deberá indicar su región).
4. Pulse **Crear usuario**.

**Resultado:** El usuario se crea activo y el sistema muestra por única vez en un recuadro destacado su **contraseña temporal** para que se la entregue al funcionario.

**B. Editar un usuario existente:**
1. En el listado de usuarios, filtre por rol, estado o nombre y pulse **Editar** en la fila correspondiente.
2. Modifique los datos personales, cambie el rol, la región o los consultorios asociados y pulse **Guardar cambios**.

**C. Restablecer contraseña de un usuario:**
1. En la fila del usuario, pulse **Restablecer contraseña**.
2. El sistema genera una nueva clave temporal, la muestra en pantalla y obligará al funcionario a cambiarla en su próximo inicio de sesión.

**D. Desactivar o reactivar el acceso de un usuario:**
1. Pulse **Desactivar** en la fila del funcionario que cesa en sus funciones.
2. El usuario queda inactivo y no puede iniciar sesión, **conservando intacto todo su historial de evaluaciones, visaciones y auditoría**. Para restituir el acceso, pulse **Activar**.

> **Importante:** Por exigencia de trazabilidad institucional, no existe el borrado físico de usuarios en el sistema; la desactivación es el único mecanismo para retirar un acceso.

### 7.3. Procedimiento: Mantenedor de consultorios y ajuste de capacidad de cupos

El sistema cuenta con las **192 unidades operativas oficiales** de la Corporación, clasificadas en 11 tipos de unidad (Consultorio Jurídico, Oficina Jurídico Asistencial, Centro de Atención a Víctimas, Centro de Mediación, Programa Mi Abogado, Niñez y Adolescencia Defendida, Oficina de Defensa Laboral, Adulto Mayor, Segunda Instancia, Unidad Móvil y Dirección Regional/General).

**A. Crear o editar un consultorio:**
1. Abra **Consultorios** en el menú lateral. Puede filtrar por región, tipo de unidad, estado (activo o inactivo) o texto libre.
2. Para agregar una nueva unidad, pulse **Nuevo consultorio**; para modificar una existente, pulse **Editar** en su tarjeta o ficha.
3. Complete el nombre, tipo de unidad, región, comuna, dirección, teléfono, horario de atención, correo, capacidad de cupos y marque si se encuentra **Activo** y si **Acepta postulaciones** en el formulario público.
4. Pulse **Guardar**.

**B. Ampliar o reducir la capacidad de cupos de una sede:**
1. Abra **Capacidad institucional** (o entre al detalle de la sede desde **Consultorios**).
2. Haga clic sobre la sede que desea ajustar: verá sus cupos totales, ocupados, libres y la nómina de postulantes actualmente asignados.
3. Utilice los botones **+** y **−** (o ingrese el número directo) para aumentar o disminuir los cupos disponibles.
4. **Restricción de seguridad:** El sistema **no permite fijar una capacidad menor que la cantidad de postulantes que actualmente realizan su práctica en esa sede**. Si necesita reducir más cupos, primero debe trasladar a los postulantes activos a otra sede.

### 7.4. Procedimiento: Gestión de fichas de postulantes, corrección de datos, traslados y cancelación

1. Abra **Gestión de postulantes**. Puede buscar en todo el país por nombre, RUT, universidad, región, consultorio o estado, o pulsar **Exportar a Excel** para descargar el padrón nacional.
2. Haga clic sobre un postulante para abrir su **ficha editable**.
3. **Para corregir datos de la ficha:**
   - Modifique nombre, apellido, universidad, correo, teléfono, fechas de inicio o término, Abogado Jefe asignado, condición de discapacidad o estado de la práctica (por ejemplo, para reactivar una práctica cancelada por error de registro) y pulse **Guardar cambios**.
4. **Para trasladar al postulante a otra sede:**
   - En la sección **Trasladar de sede**, seleccione el **Consultorio de destino** (cada opción muestra su región y cupos libres), el nuevo **Abogado Jefe** y escriba el **motivo del traslado**.
   - Pulse **Confirmar traslado**. El sistema actualiza inmediatamente los cupos de ambas sedes y deja constancia en el historial de traslados de la ficha.
5. **Para cancelar la práctica:**
   - En la sección de cancelación, elija la causal (*Conducta del practicante*, *Decisión del practicante*, *Error de registro* u *Otro motivo*), indique el detalle si corresponde y pulse **Cancelar práctica**.

### 7.5. Procedimiento: Consulta de estadísticas institucionales y exportación de reportes

1. Abra **Estadísticas y reportes** en el menú lateral.
2. Revise los tres bloques de información gerencial:
   - **Ocupación territorial por región:** Sedes activas, capacidad instalada, cupos ocupados, cupos libres y porcentaje de ocupación por cada región.
   - **Tiempos promedio de asignación:** Cantidad de días promedio (además del mínimo y máximo) que transcurren entre el envío de la postulación y el inicio de la práctica, desglosado por año y por región.
   - **Historial anual de cancelaciones por causal:** Cantidad de prácticas interrumpidas por año, separadas por conducta, decisión personal, error de registro u otro motivo.
3. Pulse **Exportar informe estadístico a Excel** en la esquina superior derecha para descargar el reporte consolidado.

### 7.6. Procedimiento: Consulta y exportación del registro de auditoría

Todas las acciones ejecutadas en la plataforma quedan grabadas en un registro inmutable de auditoría.

1. Abra **Auditoría** en el menú lateral.
2. Filtre los eventos por:
   - Palabra clave, nombre, RUT, correo o dirección IP del funcionario.
   - **Acción ejecutada** (por ejemplo: carga de carta de presentación firmada, visación de director regional, asignación de revisor colegiado, nivelación de oficio, suscripción con FirmaGob, despacho final, traslado de sede, ajuste de cupos).
   - **Entidad afectada** (práctica, postulación, usuario, consultorio).
   - **Rango de fechas** (desde / hasta).
3. En cada fila de la tabla puede revisar la fecha y hora exacta, quién ejecutó la operación y el detalle de los datos modificados (valores anteriores y valores nuevos).
4. Pulse **Exportar auditoría a Excel** para descargar el registro filtrado para revisiones de control interno o fiscalización.

---

## 8. Anexos

### 8.1. Escala institucional de calificación en palabras y criterios evaluados

Por disposición reglamentaria de la Corporación, todas las evaluaciones, revisiones directivas, nivelaciones de Jurídica Central y resoluciones expresan las calificaciones **exclusivamente en conceptos en palabras**:

| Concepto Oficial en Palabras | Condición Reglamentaria |
|---|---|
| **Sobresaliente** | **Aprobada** |
| **Buena** *(incluye Muy Buena)* | **Aprobada** |
| **Regular** | **Aprobada** *(Susceptible de nivelación institucional en Jurídica Central)* |
| **Mala** | **Reprobada** *(Bloquea la emisión del Certificado para la Corte Suprema)* |

**Los 7 criterios reglamentarios evaluados en cada práctica son:**

| Criterio | Qué evalúa |
|---|---|
| **Conocimiento y criterio jurídico** | Aplicación correcta de los conceptos legales en los casos atendidos |
| **Responsabilidad** | Cumplimiento oportuno y completo de las labores encomendadas |
| **Iniciativa** | Disposición para proponer soluciones y actuar con proactividad |
| **Sentido social y de colaboración** | Compromiso con la atención de los usuarios y el trabajo en equipo |
| **Conducta** | Trato profesional, ético y respetuoso hacia usuarios y funcionarios |
| **Honorabilidad** | Resguardo de la confidencialidad, probidad e integridad institucional |
| **Asistencia y puntualidad** | Cumplimiento regular de la jornada y horarios establecidos |

### 8.2. Documentos oficiales que emite y gestiona el sistema

| Documento | Fase | Quién lo gestiona | Requisito en la plataforma | Contenido y finalidad |
|---|---|---|---|---|
| **Carta de Presentación** | Fase 1 → 2 | Secretaría Regional (firmada por Director Regional o Jefe UCOE) | Postulación aceptada con consultorio asignado | Dirigida al Abogado Jefe; al volver a subirse firmada activa oficialmente el inicio de la práctica |
| **Comprobante de Cancelación** | Fase 2 / 3 | Secretaría Regional o Administrador | Práctica cancelada con motivo seleccionado | Deja constancia formal de la interrupción anticipada de la práctica y su causal |
| **Certificado Corte Suprema** | Fase 3 | Suscrito por Director General (FirmaGob) y despachado por Jurídica Central | Práctica aprobada, firmada con FirmaGob y despachada | Certificación oficial de aprobación de la práctica reglamentaria para el expediente de titulación ante la Excelentísima Corte Suprema |
| **Resolución de Aprobación** | Fase 3 | Jurídica Central / Director General / Postulante | Práctica finalizada y aprobada | Documento formal con vistos, considerando y resuelvo que detalla en palabras los conceptos obtenidos en las 7 dimensiones |
| **Expediente Completo Comprimido** | Fase 3 | Secretaría Jurídica Central / Secretaría Regional | Práctica finalizada y aprobada | Reúne en un solo paquete descargable los documentos del postulante, informe final, resolución y certificado firmado |

### 8.3. Flujo resumido entre perfiles administrativos por fase

```
========================================================================================
FASE 1: POSTULACIÓN, ADMISIÓN Y ASIGNACIÓN (PRE-PRÁCTICA)
========================================================================================
POSTULANTE              SECRETARÍA REGIONAL                   SECRETARÍA BIOBÍO
   |                            |                             (JURÍDICA CENTRAL)
Envía postulación ────► Revisa ficha y documentos             Supervisa en modo lectura
(documentos y                   |                             todas las postulaciones
 preferencias)           ┌──────┴──────────────┐              del país (y tramita las
                      Rechaza           Acepta / Deriva       locales de Biobío)
                                        Asigna consultorio
                                        y Abogado Jefe
                                               |
                                     Estado: POR INICIAR

========================================================================================
FASE 2: INICIO OFICIAL, DESARROLLO (180 DÍAS) Y CONTROL DE CUPOS
========================================================================================
SECRETARÍA REGIONAL                ABOGADO JEFE               DIRECTOR REGIONAL /
   |                                    |                     DIRECTOR GENERAL
1. Descarga Carta de Presentación       |                            |
2. Sube Carta Firmada ───────────► Recibe aviso de            Monitorean avance y
   Estado pasa a: EN CURSO         inicio oficial             ocupación de cupos
   |                                    |                     regionales y nacionales
   ├── Día 166 (14 días antes): Alerta de término (POR TERMINAR)
   └── Día 180 (Fecha término): Se libera automáticamente el cupo en el consultorio

========================================================================================
FASE 3: EVALUACIÓN, VISACIÓN, NIVELACIÓN CENTRAL, FIRMAGOB Y DESPACHO FINAL
========================================================================================
ABOGADO JEFE         JEFATURA UCOE /         SECRETARÍA          SECRETARÍA BIOBÍO
(1ª y 2ª Inst.)      DIRECTOR REGIONAL       REGIONAL            (JURÍDICA CENTRAL)
   |                        |                    |                       |
Evalúa en palabras   Revisión Colegiada (UCOE)   |                       |
los 7 criterios ───► y Visación Directiva ─────► Remite expediente ────► Recibe expediente
(ESTADO: FINALIZADA) (Ratifica o modifica        a Concepción            (Solo lectura para
                      en palabras con            (ESTADO: REMITIDA        la región origen)
                      observación)                A BIOBÍO)                      |
                                                                      Nivela calificación
                                                                      en palabras (ESTADO:
                                                                      NIVELADA EN BIOBÍO)
                                                                      o rechaza por sanción
                                                                                 |
POSTULANTE              SECRETARÍA BIOBÍO             DIRECTOR GENERAL           |
   |                    (JURÍDICA CENTRAL)                   |                   |
Recibe aviso y                 |                      1. Descarga lote ◄─────────┘
descarga Certificado ◄── Autoriza y ejecuta ◄──────── 2. Sube Certificado
Corte Suprema y          el Despacho Final               firmado con FIRMAGOB
Resolución               (ESTADO: CERRADA
                          EN HISTORIAL)

========================================================================================
TRANSVERSAL — ADMINISTRADOR DEL SISTEMA:
Mantiene usuarios y roles, catálogo de 192 consultorios, ajuste de cupos, corrección de
fichas, traslados de sede, reportes estadísticos nacionales y registro de auditoría.
========================================================================================
```

### 8.4. Preguntas frecuentes

**Acepté una postulación y asigné consultorio, pero la práctica sigue apareciendo como «Por iniciar» y el Abogado Jefe no ha recibido el aviso.**  
Para que la práctica pase a estado **En curso** y notifique al Abogado Jefe, debe ingresar a la ficha de la práctica en **Prácticas Activas**, descargar la **Carta de Presentación**, hacerla firmar por el Director Regional (o Jefe UCOE) y **subir el documento firmado** en esa misma pantalla.

**Quiero remitir una práctica finalizada a Jurídica Central Biobío, pero el botón aparece bloqueado.**  
Ningún expediente puede remitirse a Concepción sin contar antes con la **visación del Director Regional o Jefe UCOE**. Verifique en la ficha del historial si aparece el aviso de falta de visación directiva; una vez que el Director vise la evaluación en su portal, el botón se habilitará automáticamente.

**Quise modificar la calificación de un postulante en el portal de Director Regional, pero el sistema no me deja guardar.**  
Cuando el Director Regional o Revisor UCOE cambia el concepto propuesto por el Abogado Jefe, es **obligatorio** escribir la justificación en el campo **Observaciones de la Dirección**. Complete ese recuadro y vuelva a pulsar **Visar evaluación**.

**Remití un expediente a Jurídica Central Biobío y ahora necesito corregir un dato desde mi Secretaría Regional.**  
Al remitir el expediente a Biobío se cierra la competencia regional y el caso queda en **modo solo lectura** para la región de origen. Cualquier ajuste posterior de calificación o despacho debe ser realizado por la **Secretaría Jurídica Central** en Concepción (o por el Administrador si se trata de un dato personal de la ficha).

**En Jurídica Central quiero despachar los certificados de una práctica aprobada, pero el botón «Autorizar y despachar» no está habilitado.**  
El despacho final requiere que el **Director General** haya subido previamente el Certificado suscrito con **FirmaGob**. Cuando el Director General cargue el documento firmado, usted recibirá una notificación y el botón quedará activo.

**No puedo reducir los cupos de un consultorio desde el Portal del Administrador.**  
Por regla de protección, la capacidad de un consultorio nunca puede ser inferior a la cantidad de postulantes que actualmente realizan su práctica en él. Si necesita rebajar los cupos por debajo de ese número, primero traslade a los postulantes activos a otra sede desde su ficha editable.

**Un consultorio tiene todas sus prácticas en trámite de evaluación o firma, pero aparece con cupos libres.**  
Es el comportamiento normal del sistema: al cumplirse el día 180 (fecha de término reglamentaria de la práctica), el cupo formativo se libera automáticamente para permitir el ingreso de nuevos postulantes, sin importar cuánto tiempo tome la tramitación documental de cierre en la Fase 3.
