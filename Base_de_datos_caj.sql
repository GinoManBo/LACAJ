-- Creación de la base de datos
CREATE DATABASE IF NOT EXISTS caj_practicas;
USE caj_practicas;

-- 1. TABLAS DE DOMINIO Y PARAMETRIZACIÓN
CREATE TABLE region (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

CREATE TABLE comuna (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    region_id INT NOT NULL,
    FOREIGN KEY (region_id) REFERENCES region(id)
);

CREATE TABLE universidad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL
);

CREATE TABLE especialidad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL
);

-- 2. TABLA DE USUARIOS DEL SISTEMA (Funcionarios CAJ)
CREATE TABLE usuario_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rut VARCHAR(12) UNIQUE NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    rol ENUM('Administrador General', 'Secretaria Regional', 'Abogado Tutor', 'Director Regional') NOT NULL,
    region_id INT, -- NULL para el Administrador General (Modo Dios)
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (region_id) REFERENCES region(id)
);

-- 3. TABLA DE CONSULTORIOS (Unidades de atención)
CREATE TABLE consultorio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    comuna_id INT NOT NULL,
    cupos_totales INT NOT NULL DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (comuna_id) REFERENCES comuna(id)
);

-- 4. TABLA POSTULANTE (Datos personales y académicos)
-- Se elimina Enseñanza Media, Magíster y contraseñas.
CREATE TABLE postulante (
    rut VARCHAR(12) PRIMARY KEY, -- Validado vía Clave Única
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    
    -- Domicilio
    comuna_id INT NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    
    -- Accesibilidad y Salud
    tiene_discapacidad BOOLEAN NOT NULL DEFAULT FALSE,
    detalle_discapacidad TEXT, -- Tipo, grado y apoyos requeridos
    antecedentes_salud TEXT,   -- Alergias, condiciones médicas
    
    -- Contacto de Emergencia
    emergencia_nombre VARCHAR(150) NOT NULL,
    emergencia_vinculo VARCHAR(50) NOT NULL,
    emergencia_telefono VARCHAR(20) NOT NULL, -- Obligatorio
    emergencia_email VARCHAR(150),
    
    -- Antecedentes Académicos
    universidad_id INT NOT NULL,
    situacion_academica ENUM('Egresado', 'Licenciado') NOT NULL,
    fecha_egreso DATE NOT NULL,
    especialidad_id INT NOT NULL,
    
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (comuna_id) REFERENCES comuna(id),
    FOREIGN KEY (universidad_id) REFERENCES universidad(id),
    FOREIGN KEY (especialidad_id) REFERENCES especialidad(id)
);

-- 5. TABLA POSTULACIÓN (Fase 1: Solicitud de ingreso)
CREATE TABLE postulacion (
    id INT AUTO_INCREMENT PRIMARY KEY, -- Código interno de postulación
    rut_postulante VARCHAR(12) NOT NULL,
    
    -- Preferencias de postulación
    region_preferencia_id INT NOT NULL,
    comuna_preferencia_id INT NOT NULL,
    consultorio_preferencia_1_id INT,
    consultorio_preferencia_2_id INT,
    
    fecha_disponibilidad DATE NOT NULL,
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Extrae el "Mes de Inscripción"
    estado ENUM('En Revisión', 'Lista de Espera', 'Aprobada', 'Rechazada', 'Cancelada') DEFAULT 'En Revisión',
    
    FOREIGN KEY (rut_postulante) REFERENCES postulante(rut),
    FOREIGN KEY (region_preferencia_id) REFERENCES region(id),
    FOREIGN KEY (comuna_preferencia_id) REFERENCES comuna(id),
    FOREIGN KEY (consultorio_preferencia_1_id) REFERENCES consultorio(id),
    FOREIGN KEY (consultorio_preferencia_2_id) REFERENCES consultorio(id)
);

-- 6. TABLA GESTIÓN DE DOCUMENTOS (Storage)
CREATE TABLE documento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rut_postulante VARCHAR(12) NOT NULL,
    postulacion_id INT,
    tipo_documento ENUM('Cedula Frontal', 'Cedula Reverso', 'Certificado Egreso', 'Compromiso Confidencialidad', 'Informe Practica SAJ', 'Resolucion Cierre') NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL, -- Solo se guarda la URL/Path físico, NO el blob
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    validado_con_reparos BOOLEAN DEFAULT FALSE,
    
    FOREIGN KEY (rut_postulante) REFERENCES postulante(rut),
    FOREIGN KEY (postulacion_id) REFERENCES postulacion(id)
);

-- 7. TABLA PRÁCTICA (Fase 2: Asignación y Desarrollo)
CREATE TABLE practica (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_registro_caj VARCHAR(50) UNIQUE, -- Folio de práctica oficial generado al aceptar
    postulacion_id INT NOT NULL UNIQUE,
    consultorio_asignado_id INT NOT NULL,
    abogado_tutor_id INT, -- Funcionario que evaluará
    
    fecha_inicio DATE NOT NULL,
    -- El Término Provisorio (6 meses) se calcula sumando 180 días a fecha_inicio en las consultas SQL
    
    estado ENUM('Vigente', 'Renuncia', 'Cancelada', 'Aprobada', 'Reprobada') DEFAULT 'Vigente',
    motivo_cancelacion TEXT,
    observaciones_seguimiento TEXT,
    
    fecha_cierre DATE, -- Fecha en que el Director emite la resolución
    
    FOREIGN KEY (postulacion_id) REFERENCES postulacion(id),
    FOREIGN KEY (consultorio_asignado_id) REFERENCES consultorio(id),
    FOREIGN KEY (abogado_tutor_id) REFERENCES usuario_sistema(id)
);

-- 8. TABLA EVALUACIÓN (Fase 3: Cierre y Notas - 7 Factores)
CREATE TABLE evaluacion_practica (
    id INT AUTO_INCREMENT PRIMARY KEY,
    practica_id INT NOT NULL UNIQUE,
    
    -- Notas de la rúbrica (Escala 1.0 a 7.0)
    nota_conocimiento DECIMAL(3,1) NOT NULL,
    nota_responsabilidad DECIMAL(3,1) NOT NULL,
    nota_iniciativa DECIMAL(3,1) NOT NULL,
    nota_criterio DECIMAL(3,1) NOT NULL,
    nota_relaciones_humanas DECIMAL(3,1) NOT NULL,
    nota_presentacion DECIMAL(3,1) NOT NULL,
    nota_asistencia DECIMAL(3,1) NOT NULL,
    
    observaciones_tutor TEXT,
    fecha_evaluacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (practica_id) REFERENCES practica(id)
);