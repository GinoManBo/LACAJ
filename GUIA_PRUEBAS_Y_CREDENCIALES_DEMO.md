# Guía Operativa de Demostración y Credenciales — CAJ Bío-Bío

Esta guía detalla los usuarios de prueba, credenciales y los 7 escenarios de demostración sembrados en la base de datos de desarrollo (`caj_practicas`). Permiten validar y certificar de punta a punta cada rol y etapa del ciclo de vida de una práctica profesional.

---

## 1. Matriz Resumen de Credenciales de Acceso

> [!IMPORTANT]
> **El formulario de inicio de sesión (`/ingresar`) exige RUT y Contraseña.**
> - **Contraseña universal para todas las cuentas:** `123456`
> - Puedes ingresar el RUT con o sin puntos (ej: `10000030-8` o `10.000.030-8`).

| Rol Funcional | Nombre | **RUT de Acceso** | Correo Institucional | Portal | Caso / Estado de Prueba |
| :--- | :--- | :---: | :--- | :--- | :--- |
| **Postulante (Pre-práctica)** | Camilo Echeverría Solís | `10000030-8` | `postulante@cajbiobio.cl` | `/mi-postulacion` | Postulación enviada (pendiente de revisión) |
| **Practicante (En Curso)** | Valentina Morales Riquelme | `10000031-6` | `practicante.activo@cajbiobio.cl` | `/practicante` | Práctica activa (~66% de avance, 4 meses) |
| **Practicante (Por Terminar)** | Matías Salgado Vega | `10000032-4` | `practicante.termino@cajbiobio.cl` | `/practicante` | Faltan < 14 días. Informe final cargado |
| **Practicante (Segunda Instancia)** | Sofía Henríquez Alarcón | `10000033-2` | `postulante.corte@cajbiobio.cl` | `/practicante` | Práctica finalizada, remitida a Corte |
| **Practicante (Región Externa)** | Gabriel Navarro Sepúlveda | `10000034-0` | `postulante.nuble@cajbiobio.cl` | `/practicante` | Expediente remitido a Biobío (Nota 4.6) |
| **Practicante (Para FirmaGob)** | Javiera Cárdenas Pino | `10000035-9` | `postulante.firmagob@cajbiobio.cl` | `/practicante` | Nivelada a 5.5, lista para FirmaGob |
| **Practicante (Titulado)** | Rodrigo Sepúlveda Castro | `10000036-7` | `postulante.titulado@cajbiobio.cl` | `/practicante` | Expediente cerrado con certificados listos |
| **Abogado Jefe (1ª Instancia)** | Carlos Muñoz Sepúlveda | `10000004-0` | `abogado1@cajbiobio.cl` | `/tutor` | Tutor de prácticas activas y por evaluar |
| **Abogado Jefe (2ª Instancia)** | Mauricio Vergara Toledo | `10000016-4` | `corte_biobio@cajbiobio.cl` | `/tutor` | Evaluación de alegatos en Corte Concepción |
| **Secretaría Biobío (Unificada)** | Paulina Herrera Núñez | `10000002-4` | `secretaria1@cajbiobio.cl` | `/secretaria` | Panel unificado territorial y nacional |
| **Secretaría Biobío (Alterna)** | Lorena Sanhueza Carrasco | `10000010-5` | `juridica@cajbiobio.cl` | `/secretaria` | Panel unificado territorial y nacional |
| **Jurídica Central (Específico)** | Verónica Sanhueza | `10000037-5` | `juridica.central@cajbiobio.cl` | `/secretaria/juridica` | Rol nativo `juridica_central` |
| **Secretaría Regional (Ñuble)** | Loreto Morales Bastías | `10000011-3` | `secretaria_nuble@cajbiobio.cl` | `/secretaria` | Gestión territorial externa |
| **Director Regional (Ñuble)** | Ricardo Salazar Valenzuela | `10000022-9` | `director_nuble@cajbiobio.cl` | `/director` | Visación y remisión a Biobío |
| **Jefatura UCOE (RM)** | Luis Núñez Oviedo | `10000021-0` | `ucoe_biobio@cajbiobio.cl` | `/director` | Revisión Colegiada UCOE |
| **Director General** | Mauricio Decap Fernández | `10000020-2` | `director_general@cajbiobio.cl` | `/director-general` | Visación central y FirmaGob transversal |
| **Administrador del Sistema** | Mauricio Decap Fernández | `10000001-6` | `admin@cajbiobio.cl` | `/admin` | Configuración, consultorios y auditoría |

---

## 2. Guía de Pruebas por Escenario Operativo

### Escenario A: Revisión y Aprobación de Postulación
1. **Acceso como Postulante:** Iniciar sesión con RUT **`10000030-8`** (`postulante@cajbiobio.cl`) y clave `123456`.
   - Navegar a `/mi-postulacion`.
   - Verificar la vista de estado `'pendiente'`, antecedentes cargados y las preferencias de consultorios registradas (1º Consultorio Centro Concepción, 2º Talcahuano).
2. **Acceso como Secretaría:** Iniciar sesión con RUT **`10000002-4`** (`secretaria1@cajbiobio.cl`) y clave `123456`.
   - Ir a `/secretaria/postulaciones`.
   - Abrir el expediente de **Camilo Echeverría Solís**.
   - Descargar/ver sus 4 documentos cargados (cédula frontal/reverso, certificado de egreso, certificado de antecedentes).
   - Aprobar la postulación asignando el consultorio deseado para crear la práctica oficial.

---

### Escenario B: Práctica Activa e Informe Final con Alerta de Término
1. **Acceso como Practicante en Curso:** Iniciar sesión con RUT **`10000031-6`** (`practicante.activo@cajbiobio.cl`) y clave `123456`.
   - Ir a `/practicante`.
   - Observar la barra de progreso al 66% (4 meses transcurridos de los 6 meses reglamentarios).
2. **Acceso como Practicante por Terminar:** Iniciar sesión con RUT **`10000032-4`** (`practicante.termino@cajbiobio.cl`) y clave `123456`.
   - Ir a `/practicante`.
   - Comprobar que faltan menos de 14 días para la fecha de término y que el informe final ya se encuentra subido.
3. **Evaluación del Tutor (Abogado Jefe):** Iniciar sesión con RUT **`10000004-0`** (`abogado1@cajbiobio.cl`) y clave `123456`.
   - Ir a `/tutor`.
   - En la sección **"Prácticas por Evaluar / Alerta de Término"**, pulsar sobre **Matías Salgado Vega**.
   - Ingresar a la rúbrica oficial de 7 criterios (conocimientos, puntualidad, redacción, iniciativa, etc.) y emitir la calificación.

---

### Escenario C: Segunda Instancia Judicial (Corte de Apelaciones)
1. Iniciar sesión como Abogado Jefe de 2ª Instancia: RUT **`10000016-4`** (`corte_biobio@cajbiobio.cl`) y clave `123456`.
2. Ir a `/tutor`.
3. Ver el expediente de **Sofía Henríquez Alarcón** (RUT `10000033-2`), cuya 1ª instancia en el Consultorio de Talcahuano obtuvo nota 5.2.
4. Evaluar el desempeño en sala y alegatos para promediar la nota definitiva conforme al reglamento institucional.

---

### Escenario D: Nivelación Institucional de Región Externa (Jurídica Central Unificada)
1. Iniciar sesión como Secretaría de Bío-Bío: RUT **`10000002-4`** (`secretaria1@cajbiobio.cl`) o RUT **`10000010-5`** (`juridica@cajbiobio.cl`) con clave `123456`.
2. Notar el encabezado institucional **"Secretaría Biobío (Central)"** y la tarjeta destacada **"Atribuciones de Jurídica Central (Nacional)"** con acceso directo.
3. Ir a `/secretaria/juridica` (o pulsar el acceso directo).
4. En la bandeja de **"Expedientes Remitidos desde Regiones"**, ubicar a **Gabriel Navarro Sepúlveda** (Región de Ñuble, calificado con nota 4.6 "Regular").
5. Hacer clic en **Ver Expediente**, ingresar una nueva nota institucional (ej: 5.0 "Buena") con la justificación jurídica pertinente y confirmar la nivelación.

---

### Escenario E: FirmaGob por el Director General
1. Iniciar sesión como Director General: RUT **`10000020-2`** (`director_general@cajbiobio.cl`) y clave `123456`.
2. Navegar a `/director-general`.
3. En la bandeja nacional de expedientes nivelados y validados, revisar a **Javiera Cárdenas Pino** (RUT `10000035-9`, nota nivelada a 5.5 "Muy Buena").
4. Descargar el expediente o paquete `.zip` para firma transversal.
5. Utilizar el botón de **"Subir Certificado FirmaGob"** para cargar el documento firmado con firma electrónica avanzada del Estado.

---

### Escenario F: Descarga de Certificados Oficiales por el Egresado
1. Iniciar sesión como Postulante Titulado: RUT **`10000036-7`** (`postulante.titulado@cajbiobio.cl`) y clave `123456`.
2. Navegar a `/practicante`.
3. El estado de la práctica se encuentra en `'cerrada_historial'` con `documentos_autorizados = true`.
4. Hacer clic en los botones de descarga directa:
   - **Descargar Resolución de Término de Práctica**.
   - **Descargar Certificado Oficial para la Excma. Corte Suprema**.
5. Ambos documentos se descargan exitosamente gracias a los archivos PDF persistidos en el almacenamiento privado.
