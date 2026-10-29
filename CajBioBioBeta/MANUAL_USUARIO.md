# Manual de Usuario y Procedimientos
## Sistema de Gestión de Practicantes — Corporación de Asistencia Judicial

| | |
|---|---|
| **Versión de la aplicación** | 1.9.2 |
| **Fecha del documento** | 20 de agosto de 2026 |
| **Alcance** | Postulación, ingreso, seguimiento, evaluación y cierre de prácticas profesionales de postulantes al título de abogado |
| **Perfiles cubiertos** | Postulante, Secretaría regional, Abogado tutor, Practicante, Administrador |

---

## 1. Introducción

### 1.1 Propósito del sistema

El sistema centraliza el ciclo completo de la práctica profesional reglamentaria:

1. El **postulante** envía su postulación desde un formulario público.
2. La **secretaría regional** revisa la postulación, la acepta y asigna consultorio (o la deriva a otra región).
3. El **abogado tutor** hace seguimiento del practicante y registra la evaluación final de desempeño.
4. El **practicante** consulta su ficha, sus calificaciones y descarga sus documentos oficiales.
5. El **administrador** mantiene usuarios, roles, fichas de practicantes y la capacidad de la red de consultorios.

### 1.2 Perfiles de usuario y qué ve cada uno

| Perfil | Vista en el sistema | Qué puede hacer |
|---|---|---|
| **Postulante** (externo) | Formulario de Postulación | Postular en 5 pasos, escanear su cédula, elegir consultorios por preferencia |
| **Secretaría regional** | Panel General, Postulaciones, Prácticas Activas, Historial, Consultorios | Aceptar/rechazar/derivar postulaciones, asignar consultorio, cancelar prácticas, consultar historial y cupos |
| **Abogado tutor** | Portal del Abogado Tutor | Ver sus practicantes, recibir notificaciones, completar la encuesta de desempeño |
| **Practicante** | Portal del Practicante | Ver su práctica, sus calificaciones y descargar certificado y resolución |
| **Administrador** | Portal del Administrador | Crear/editar/desactivar usuarios, corregir fichas de practicantes, trasladar de sede, ampliar cupos |

> **Importante:** cada vista corresponde a un usuario distinto. En esta versión de demostración las vistas se alcanzan desde el menú lateral sin pedir credenciales; en producción cada perfil ingresa con su propia cuenta y solo ve su portal.

### 1.3 Elementos comunes de la interfaz

- **Menú lateral (izquierda):** sección `PRINCIPAL` con las vistas de secretaría, y sección `ACCESO RÁPIDO` con los portales de Administrador, Abogado y Practicante. Al pie se muestra la versión de la aplicación.
- **Barra superior:** título del portal activo, **selector de región**, campana de notificaciones y usuario conectado.
- **Selector de región:** solo aparece en las vistas de secretaría. Define qué consultorios, postulaciones, prácticas e historial se ven. Cada región tiene su secretaría responsable.
- **Migas de pan:** ubicación dentro del sistema (por ejemplo `Inicio / Región del Biobío / Postulaciones pendientes`).
- **Fichas modales:** al hacer clic en una fila o tarjeta se abre una ficha superpuesta. Se cierra con la **X**, con el botón **Cerrar** o haciendo clic fuera de ella.

### 1.4 Estados de una práctica

| Estado | Se muestra como | Significado |
|---|---|---|
| Activa | **En curso** | La práctica está en desarrollo |
| Por iniciar | Por iniciar | Asignada, aún no comienza |
| Por terminar | Por terminar | Finaliza en los próximos 30 días |
| Finalizada | Finalizada | Cumplida y evaluada |
| Cancelada | Cancelada | Interrumpida; queda registrada con su motivo |

### 1.5 Disponibilidad de cupos por consultorio

| Indicador | Condición |
|---|---|
| 🟢 **Muchos cupos** | La mitad o más de la capacidad está libre |
| 🟠 **Pocos cupos** | Queda menos de la mitad de la capacidad |
| 🔴 **Sin cupos** | No hay cupos libres |

---

## 2. Perfil POSTULANTE — Formulario de Postulación

**Quién lo usa:** estudiante de Derecho o egresado que postula a la práctica.
**Cómo se accede:** enlace público del formulario (en la demo, desde el panel de secretaría).

El formulario avanza por 5 pasos, con barra de progreso en la parte superior. Se puede volver a cualquier paso con **Volver** o desde el resumen con **Editar**.

### Procedimiento

**Paso 0 — Inicio**
Pulse **Ingresar postulación**.

**Paso 1 — Carta de presentación y términos**
1. Lea la carta institucional y los términos (requisitos, duración, compromisos, tratamiento de datos según Ley N° 19.628, proceso de selección y beneficios).
2. Marque la casilla de aceptación. Sin ella el botón **Continuar** permanece bloqueado.

**Paso 2 — Datos personales**
Los campos con asterisco rojo son obligatorios.
1. **Identificación:** nombre, apellido, RUT, fecha de nacimiento, correo, teléfono, género, nacionalidad, universidad.
2. **Información de accesibilidad:** indique si presenta alguna discapacidad. Si responde afirmativamente, describa el tipo, grado y apoyos que requiere.
3. **Domicilio:** región, dirección y comuna. La comuna se habilita después de elegir región.
4. **Contacto de emergencia:** nombre, vínculo, teléfono y correo.
5. **Antecedentes de salud:** alergias o condiciones relevantes. Puede escribir "Ninguno".

**Paso 3 — Antecedentes educativos y cédula**
1. Seleccione universidad, año de carrera (5° año o Egresado) y especialidad.
2. **Cédula de identidad (obligatoria):** capture el **lado frontal** y el **lado reverso**.
   - **Escanear con cámara:** se abre la cámara del dispositivo y captura la imagen.
   - **Subir archivo:** JPG o PNG, máximo 5 MB por imagen.
3. **Validación automática:** con ambas caras cargadas, pulse el botón de análisis. El sistema revisa:
   - nitidez y legibilidad de ambas imágenes,
   - que ambas caras correspondan a la misma cédula,
   - que el lado frontal y el reverso sean los correctos,
   - coincidencia del número de documento entre caras,
   - dígito verificador del RUN,
   - vigencia del documento.

   El resultado es uno de tres veredictos: **Cédula validada**, **Validada con reparos** o **No se pudo validar**. Si cambia alguna imagen, la validación anterior se descarta y debe repetirse.

**Paso 4 — Consultorio y fecha de inicio**
1. Seleccione **uno o más consultorios**. El orden en que los marca es su **orden de preferencia** (se numera 1, 2, 3…). Puede quitar cualquiera desde la lista de preferencias.
2. Si indicó comuna de domicilio, los consultorios se ordenan por cercanía y el más próximo se marca con la etiqueta *MÁS CERCANO A SU DOMICILIO*. También puede buscarlos por comuna o nombre.
3. Elija la **fecha en que puede comenzar**. No se permiten fines de semana ni feriados legales.
4. Con al menos un consultorio y una fecha, pulse **Revisar postulación**.

**Paso 5 — Resumen y envío**
1. Revise cada bloque (datos personales, accesibilidad, domicilio, contacto de emergencia, salud, antecedentes educativos, cédula, consultorios y fecha).
2. Corrija con **Editar** en el bloque que corresponda.
3. Pulse **Confirmar y enviar**.

**Resultado:** mensaje de postulación enviada. La notificación del resultado llega por correo electrónico en un plazo máximo de 15 días hábiles.

---

## 3. Perfil SECRETARÍA REGIONAL

**Quién lo usa:** la secretaria responsable de una región.
**Ámbito:** solo ve consultorios, postulaciones, prácticas e historial **de la región seleccionada** en la barra superior. Cambiar de región cambia todo el contenido de estas cinco vistas.

### 3.1 Panel General

Vista de entrada. Muestra tres indicadores de la región activa:

- **Postulaciones pendientes** por revisar.
- **Prácticas activas** en curso.
- **Por terminar** en los próximos 30 días.

Debajo, **Últimas postulaciones** y **Prácticas por terminar**, cada una con el botón **Ver todas** que lleva a la vista completa.

### 3.2 Postulaciones — revisar, aceptar, rechazar o derivar

**Procedimiento estándar:**

1. Abra **Postulaciones**. Se listan las postulaciones dirigidas a consultorios de su región, con nombre, RUT, universidad, año, correo, teléfono y fecha de recepción.
2. **Revisar antecedentes:** haga clic sobre la fila para abrir la ficha del postulante. Contiene sus datos de contacto, el consultorio al que postuló y los archivos que adjuntó, separados en *Documentos universitarios* y *Cédula de identidad*, con opción **Ver** y **Descargar**. Si falta un lado de la cédula, el sistema lo advierte: no es posible validar identidad.
3. Desde la ficha o desde la fila, elija **Aceptar** o **Rechazar**.
   - **Rechazar** retira la postulación del listado.
   - **Aceptar** abre la ventana de asignación (paso 4).
4. **Asignar consultorio:**
   - Se muestran los datos del postulante y las **notas** que otras secretarías hayan dejado sobre él.
   - La lista de consultorios aparece ordenada: primero aquel al que postuló (etiqueta *POSTULÓ AQUÍ*), luego los de su región y al final los de otras regiones (etiqueta *OTRA REGIÓN*). Cada tarjeta indica dirección y disponibilidad de cupos. Puede buscar por nombre, dirección o región.
   - **Comentarios:** escriba una anotación y pulse **Agregar comentario**. La nota queda guardada a nombre de su secretaría y **acompaña al postulante aunque se derive a otra región**. Sus propias notas pueden eliminarse con la X.
5. **Confirmar:** el pie de la ventana indica siempre qué ocurrirá.
   - Consultorio de su región → botón verde **Confirmar ingreso**. El postulante ingresa y sale del listado de pendientes.
   - Consultorio de otra región → botón naranjo **Derivar a otra región**. La postulación queda a cargo de la secretaría de esa región, desaparece de su listado y se muestra un aviso de confirmación de la derivación.

> **Recomendación:** antes de derivar, deje siempre un comentario con el motivo (falta de cupos, cercanía al domicilio, disponibilidad horaria). Es la única información de contexto que recibe la secretaría de destino.

### 3.3 Prácticas Activas — seguimiento y cancelación

1. La vista muestra **Cupos disponibles** y **Total de cupos** de la región, y la tabla de prácticas con practicante, universidad, fechas de inicio y término, abogado tutor, consultorio y estado.
2. **Buscar practicante:** campo de búsqueda por nombre.
3. Botones de acción por fila:
   - 👁 **Ver detalle** de la práctica.
   - 📊 **Ver estadísticas** — abre la vista de estadísticas de esa práctica (duración total, días transcurridos y porcentaje de avance). Se regresa con **Volver a Prácticas Activas**.
   - ✏️ **Gestionar práctica** — abre la ventana de cancelación.
4. **Cancelar una práctica:**
   1. Pulse **Gestionar práctica**.
   2. Seleccione el **motivo**: *Conducta del practicante*, *Decisión del practicante* u *Otro motivo*. Si elige "Otro motivo" puede detallarlo en el campo de texto.
   3. Pulse **Cancelar práctica**. Sin motivo seleccionado el botón permanece deshabilitado.
   4. La práctica queda en estado **Cancelada**. Una práctica ya cancelada no se puede volver a cancelar.

### 3.4 Historial

Registro de prácticas cerradas de la región (finalizadas y canceladas).

1. Busque por nombre o filtre por año.
2. Haga clic en cualquier fila para abrir el **resumen del practicante**, con tres pestañas:
   - **Resumen:** datos de contacto, periodo, duración en días, consultorio y abogado tutor. Si la práctica fue cancelada, se muestra un aviso con la fecha y el motivo, distinguiendo si se canceló *antes de iniciar* (etiqueta *NO INICIÓ* en la tabla) o *durante su desarrollo*.
   - **Documentos:** archivos universitarios y cédula, con **Ver** y **Descargar**.
   - **Calificaciones:** nota final y conceptos de las 7 dimensiones evaluadas por el tutor. Si la práctica no se completó, no hay evaluación registrada y el sistema lo indica.

### 3.5 Consultorios

Tarjetas de los consultorios de la región, con dirección, barra de ocupación y cupos disponibles, según la leyenda de disponibilidad (sección 1.5).

Al hacer clic en una tarjeta se despliega el detalle: practicantes activos, cupos disponibles y la lista de **practicantes asignados** con su universidad, tutor, estado y fecha de término.

> Los cupos se **modifican desde el Portal del Administrador** (sección 6.3). Esta vista es de consulta.

---

## 4. Perfil ABOGADO TUTOR — Portal del Abogado Tutor

**Quién lo usa:** el abogado a cargo de uno o más practicantes.

### 4.1 Encabezado

Muestra el nombre del tutor, su área de especialidad, la cantidad de practicantes activos y su consultorio.

### 4.2 Notificaciones

1. Pulse **Notificaciones**. El contador rojo indica los avisos sin leer.
2. Tipos de aviso:
   - **Urgente** — práctica próxima a finalizar.
   - **Aviso** — encuesta de desempeño pendiente.
   - **Informativo** — nuevo practicante asignado.
3. Al hacer clic sobre un aviso queda marcado como leído. **Marcar todas como leídas** limpia el contador.

### 4.3 Mis practicantes

1. La columna izquierda lista los practicantes a su cargo. Búsquelos por nombre, universidad o estado; la X limpia la búsqueda.
2. Seleccione uno para ver su ficha a la derecha: periodo de práctica, teléfono, correo, consultorio asignado e información de discapacidad.
3. Si el practicante está **Por terminar**, aparece la alerta *Proximidad al cierre de práctica*, recordando completar el informe de cierre y la encuesta final.

### 4.4 Procedimiento: Encuesta de desempeño (evaluación final)

1. Seleccione al practicante que corresponda.
2. En **Encuesta de desempeño**, califique de **1,0 a 7,0** cada uno de los 7 criterios:

   | Criterio | Qué evalúa |
   |---|---|
   | Conocimiento y criterio jurídico | Aplica correctamente los conceptos legales |
   | Responsabilidad | Entrega trabajos a tiempo y completos |
   | Iniciativa | Propone soluciones y actúa sin esperar instrucciones |
   | Sentido social y de colaboración | Compromiso con los usuarios y el equipo |
   | Conducta | Trato profesional y respetuoso |
   | Honorabilidad | Confidencialidad e integridad |
   | Asistencia y puntualidad | Cumple horarios y asiste regularmente |

3. Escriba los **comentarios generales** (logros destacados, áreas de mejora).
4. Pulse **Enviar evaluación**. El botón se habilita solo cuando **los 7 criterios** tienen nota.
5. El sistema calcula el promedio y muestra el resultado: **HA APROBADO** (promedio 4,0 o superior) o **NO HA APROBADO**.
6. **Nueva encuesta** limpia el formulario si debe rehacerse.

> La evaluación enviada es la base de los conceptos que aparecen en la resolución que aprueba la práctica y de las calificaciones que ve el practicante.

---

## 5. Perfil PRACTICANTE — Portal del Practicante

**Quién lo usa:** el postulante ya aceptado, durante y después de su práctica.

### 5.1 Detalle de la práctica

Consultorio asignado, abogado tutor con su correo y teléfono, y fechas de inicio y término. El estado de la práctica se muestra en la esquina superior derecha.

### 5.2 Calificaciones

- **Primera instancia (obligatoria):** se califica al término de la práctica.
- **Segunda y tercera instancia (opcionales):** solo aplican si la práctica las contempla; mientras no existan, aparecen como *Sin nota*.
- **Nota de la práctica:** evaluación global del desempeño.
- **Nota promedio:** promedio de las instancias efectivamente calificadas.

Todas las notas están en escala de 1,0 a 7,0.

### 5.3 Procedimiento: descargar documentos oficiales

1. Vaya a **Documentación**. Se generan dos documentos, con el nombre del practicante y la fecha en el nombre del archivo:
   - **Certificado Corte Suprema** — certificado de aprobación para presentar ante la Excelentísima Corte Suprema al abrir el expediente de titulación.
   - **Resolución** — resolución que aprueba la práctica, con los conceptos de calificación.
2. Ambos permanecen **Bloqueados** mientras la práctica no esté finalizada y calificada. En ese caso el sistema indica el estado actual de la práctica.
3. Cuando el estado es **Finalizada**, los documentos pasan a **Disponible**: pulse **Descargar**. El documento se abre en una ventana nueva lista para imprimir o guardar como PDF.

> Si su navegador bloquea las ventanas emergentes, la descarga no se abrirá. Permita las ventanas emergentes para este sitio y repita la operación.

---

## 6. Perfil ADMINISTRADOR — Portal del Administrador

**Quién lo usa:** el encargado institucional del sistema. A diferencia de la secretaría, **ve todas las regiones**.

### 6.1 Indicadores

Prácticas activas, postulaciones pendientes, porcentaje de ocupación de cupos y practicantes registrados.

### 6.2 Procedimiento: Gestión de practicantes (corregir ficha, trasladar de sede, cancelar)

1. En **Gestión de practicantes**, busque por nombre, universidad, sede o abogado tutor.
2. Haga clic en la tarjeta del practicante para abrir su **ficha editable**.
3. **Corregir datos:** nombre, universidad, correo, teléfono, fechas de inicio y término, abogado tutor, información de discapacidad y estado de la práctica.
4. **Trasladar de sede:** en *SEDE ASIGNADA*, busque y seleccione la nueva sede. Cada tarjeta indica dirección y disponibilidad. El pie de la ficha avisa: *Se trasladará a la sede: …*
5. **Cancelar la práctica:** seleccione el motivo (*Conducta del practicante*, *Decisión del practicante*, *Error de registro* u *Otro motivo*) y pulse **Cancelar práctica**. Una práctica cancelada puede reactivarse cambiando su **estado** en los datos del practicante.
6. Pulse **Guardar cambios**. Los cambios solo se aplican al guardar; el sistema confirma con *Cambios guardados correctamente*.

### 6.3 Procedimiento: Capacidad institucional (ampliar o reducir cupos)

1. En **Capacidad institucional** se lista cada sede con su ocupación (ocupados/capacidad).
2. Haga clic en una sede para abrir su detalle: cupos totales, ocupados, disponibles y nivel de disponibilidad.
3. Use **+** y **−** para ajustar la capacidad. El sistema informa cuántos cupos se agregaron o retiraron.
4. **Restricción:** no es posible dejar menos cupos que practicantes ya asignados; el botón **−** se deshabilita al alcanzar ese límite.
5. El detalle lista además los practicantes actualmente asignados a esa sede.

### 6.4 Procedimiento: Usuarios y roles

Los roles disponibles son **Administrador**, **Secretaria** y **Abogado tutor**. Use los filtros superiores para acotar la lista por rol.

**Crear un usuario nuevo**

1. Pulse **Nuevo usuario** (esquina superior derecha de la tarjeta *Usuarios y roles*).
2. Complete **nombre completo**, **correo electrónico** y **rol**.
3. Pulse **Crear usuario**.
4. Validaciones: nombre y correo son obligatorios, el correo debe tener formato válido y no puede repetirse otro usuario con el mismo correo. Cualquier error se muestra bajo el formulario.
5. El usuario se crea **activo**, con sus iniciales como avatar y último acceso *Sin ingresos*. Si estaba filtrando por otro rol, la lista salta automáticamente al rol del usuario creado para que quede visible.

**Editar un usuario**
Pulse el botón ✏️ de su fila, modifique nombre, correo o rol y pulse **Guardar cambios**.

**Suspender o reactivar el acceso**
Pulse **Desactivar** en la fila del usuario. Queda atenuado en la lista y deja de contarse en *Usuarios activos*, **sin borrar su historial**. **Activar** revierte la operación.

> No existe eliminación de usuarios: la desactivación es el mecanismo definido para retirar un acceso conservando la trazabilidad.

### 6.5 Información del sistema

Versión de la aplicación, usuarios registrados, usuarios activos, consultorios en la red y cupos totales. Úsela como referencia al reportar una incidencia.

---

## 7. Anexos

### 7.1 Escala institucional de calificación

La nota de 1,0 a 7,0 se expresa como concepto en la resolución:

| Nota | Concepto |
|---|---|
| 6,5 – 7,0 | SOBRESALIENTE |
| 5,5 – 6,4 | MUY BUENO |
| 4,5 – 5,4 | BUENO |
| 4,0 – 4,4 | SUFICIENTE |
| Menor a 4,0 | DEFICIENTE |

Se aprueba la práctica con promedio igual o superior a 4,0.

### 7.2 Documentos que emite el sistema

| Documento | Lo genera | Requisito | Contenido |
|---|---|---|---|
| Certificado Corte Suprema | Portal del Practicante | Práctica finalizada y calificada | Certificación de aprobación de la práctica reglamentaria, para el expediente de titulación |
| Resolución de aprobación | Portal del Practicante | Práctica finalizada y calificada | Vistos, considerando y resuelvo, con los conceptos de las 7 dimensiones evaluadas |

### 7.3 Flujo resumido entre perfiles

```
POSTULANTE            SECRETARÍA               ABOGADO TUTOR        PRACTICANTE
   |                      |                         |                    |
Formulario  ──────►  Postulaciones                  |                    |
(5 pasos)            revisa ficha                   |                    |
                     y archivos                     |                    |
                          |                         |                    |
                   ┌──────┴───────┐                 |                    |
                Rechaza      Acepta / Deriva        |                    |
                             asigna consultorio     |                    |
                                  |                 |                    |
                          Prácticas Activas ──► seguimiento              |
                          (seguimiento, cupos,      |                    |
                           cancelación)        encuesta final            |
                                  |             (7 criterios)            |
                                  |                 |                    |
                              Historial  ◄──── práctica evaluada ──► calificaciones
                                                                    y descarga de
                                                                    documentos

                    ADMINISTRADOR: usuarios y roles, fichas de practicantes,
                    traslados de sede y capacidad de toda la red (todas las regiones)
```

### 7.4 Preguntas frecuentes

**No veo una postulación que sé que ingresó.**
Verifique la **región** seleccionada en la barra superior: solo se ven las postulaciones dirigidas a consultorios de esa región. Si fue derivada a otra región, ya no aparece en su listado.

**Derivé una postulación por error.**
La postulación queda a cargo de la secretaría de destino. Contáctela para que la devuelva; el comentario asociado sigue viajando con el postulante.

**El botón de cancelar práctica está deshabilitado.**
Falta seleccionar el motivo de cancelación.

**El botón de enviar evaluación está deshabilitado.**
Faltan notas: los 7 criterios deben estar calificados.

**Los documentos del practicante aparecen bloqueados.**
La práctica no está finalizada o no tiene la calificación final registrada por el tutor.

**No puedo reducir los cupos de una sede.**
La capacidad no puede ser menor que el número de practicantes ya asignados. Traslade primero a los practicantes desde su ficha.

**La validación de cédula no se ejecuta.**
Requiere ambas caras cargadas y la clave `VITE_GEMINI_API_KEY` configurada en el entorno. Sin ella el sistema informa que falta la configuración.

### 7.5 Consideraciones de esta versión

- Es una versión de **demostración**: los datos se mantienen en memoria del navegador y **se reinician al recargar la página**.
- Las vistas no exigen inicio de sesión; el cambio de perfil se hace desde el menú lateral.
- Los datos de personas, consultorios y prácticas son de ejemplo.
- La validación automática de cédula envía las imágenes a un servicio externo de análisis; considérelo al tratar datos personales reales (Ley N° 19.628).
