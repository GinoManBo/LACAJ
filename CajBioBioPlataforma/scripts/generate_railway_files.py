#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Generador de scripts SQL para Railway:
1. caj_railway_inserts.sql: Archivo exclusivo de sentencias INSERT INTO organizadas lógicamente.
2. caj_railway_completo.sql: Esquema DDL completo (CREATE TABLE IF NOT EXISTS) + INSERTs.
"""

import re
import os
import shutil

def main():
    dump_path = 'dump_raw.sql'
    if not os.path.exists(dump_path):
        raise FileNotFoundError(f"No se encontró {dump_path}")

    with open(dump_path, 'r', encoding='utf-8') as f:
        text = f.read()

    # Extraer bloques de tablas
    pattern = re.compile(r'--\s*\n-- Table structure for table `(\w+)`\s*\n--(.*?)(?=(?:--\s*\n-- Table structure for table `|\Z))', re.DOTALL)
    matches = pattern.findall(text)

    table_schemas = {}
    table_inserts = {}

    for tname, block in matches:
        m_create = re.search(r'(CREATE TABLE `.+?ENGINE=InnoDB.*?;)', block, re.DOTALL)
        if m_create:
            create_sql = m_create.group(1).strip()
            # Convertir a CREATE TABLE IF NOT EXISTS
            create_sql = re.sub(r'CREATE TABLE `', 'CREATE TABLE IF NOT EXISTS `', create_sql)
            table_schemas[tname] = create_sql

        m_insert = re.search(r'(INSERT INTO `.+?;)', block, re.DOTALL)
        if m_insert:
            table_inserts[tname] = m_insert.group(1).strip()

    print(f"Esquemas encontrados: {len(table_schemas)}")
    print(f"Inserts encontrados: {len(table_inserts)}")

    # Tablas a incluir en el archivo de inserts y su orden lógico
    TABLES_DATA_ORDER = [
        ('regiones', '1. Regiones operativas oficiales (Ñuble, Biobío, Araucanía, Los Ríos, Los Lagos, Aysén)'),
        ('comunas', '2. Comunas oficiales de las 6 regiones de la jurisdicción (138 comunas)'),
        ('especialidades', '3. Especialidades jurídicas institucionales (5 especialidades)'),
        ('consultorios', '4. Catálogo oficial completo de consultorios y unidades de la CAJ Biobío (202 sedes con dirección, teléfono, horario, email y capacidad)'),
        ('usuarios', '5. Usuarios del sistema con credenciales de prueba universales (31 funcionarios y postulantes, clave: 123456)'),
        ('abogados', '6. Abogados Tutores y Jefes de 1ª y 2ª Instancia (Corte de Apelaciones)'),
        ('abogado_consultorio', '7. Asignación oficial de Abogados Tutores a Consultorios'),
        ('postulantes', '8. Fichas de antecedentes personales y académicos de Postulantes de prueba'),
        ('postulaciones', '9. Postulaciones a práctica profesional en sus diversas etapas'),
        ('postulacion_preferencias', '10. Sedes y consultorios preferidos seleccionados por los postulantes'),
        ('postulacion_notas', '11. Observaciones y notas de seguimiento interno de postulaciones'),
        ('practicantes', '12. Expedientes de practicantes asignados y activos'),
        ('practicas', '13. Prácticas profesionales con los 7 escenarios de demostración completos'),
        ('evaluaciones', '14. Evaluaciones de desempeño de tutores (Rúbrica oficial de 7 criterios)'),
        ('calificaciones', '15. Calificaciones de 1ª y 2ª instancia y nivelaciones de oficio'),
        ('documentos', '16. Metadatos de expedientes y documentos cargados en el sistema'),
        ('certificados', '17. Certificados oficiales emitidos para la Excma. Corte Suprema y Resoluciones'),
        ('resolucion_secuencia', '18. Correlativo numérico institucional de resoluciones'),
        ('notificaciones', '19. Notificaciones y alertas operativas para los usuarios'),
        ('auditoria', '20. Bitácora de auditoría institucional'),
        ('migrations', '21. Registro de migraciones ejecutadas de Laravel (19 migraciones)'),
    ]

    # Todas las tablas para el archivo completo de DDL
    ALL_TABLES_ORDER = [
        'regiones',
        'comunas',
        'especialidades',
        'consultorios',
        'usuarios',
        'password_reset_tokens',
        'sessions',
        'abogados',
        'abogado_consultorio',
        'postulantes',
        'postulaciones',
        'postulacion_preferencias',
        'postulacion_derivaciones',
        'postulacion_notas',
        'postulacion_borradores',
        'practicantes',
        'practicas',
        'practica_traslados',
        'evaluaciones',
        'calificaciones',
        'documentos',
        'archivos_staging',
        'certificados',
        'resolucion_secuencia',
        'notificaciones',
        'auditoria',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'migrations'
    ]

    header_common = """-- =============================================================================
-- CORPORACIÓN DE ASISTENCIA JUDICIAL DE LA REGIÓN DEL BIOBÍO
-- Plataforma Inteligente de Gestión y Optimización de Prácticas Profesionales
-- =============================================================================
-- Archivo generado para despliegue y siembra directa en RAILWAY / Cloud MySQL
-- Compatible con MySQL 8.0, 8.4 y MariaDB 10.5+
--
-- CARACTERÍSTICAS PRINCIPALES:
--  - Codificación UTF-8 Multibyte (utf8mb4) para preservación exacta de tildes y caracteres en español.
--  - Desactivación temporal de restricciones foráneas para carga masiva sin bloqueos.
--  - Sin comandos dependientes de entorno ('CREATE DATABASE' ni 'USE'), lo que permite
--    importar directamente en la base de datos asignada por Railway (ej: 'railway').
--  - Sin comandos de bloqueo ('LOCK TABLES' / 'UNLOCK TABLES') para evitar errores de permisos.
--  - Incluye los 202 consultorios reales (192 sedes oficiales del seeder + unidades base).
--  - Incluye los 31 usuarios institucionales y de demostración con contraseña universal '123456'.
--  - Incluye los 7 escenarios demostrativos del ciclo de vida de práctica profesional.
--  - Incluye las 19 migraciones registradas para sincronía perfecta con Laravel.
-- =============================================================================

SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT;
SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS;
SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION;
SET NAMES utf8mb4;
SET @OLD_TIME_ZONE=@@TIME_ZONE;
SET TIME_ZONE='+00:00';
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
"""

    footer_common = """
-- =============================================================================
-- RESTAURACIÓN DE CONFIGURACIÓN Y RESTRICCIONES
-- =============================================================================
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT;
SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS;
SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION;
SET TIME_ZONE=@OLD_TIME_ZONE;
SET SQL_MODE=@OLD_SQL_MODE;

-- Fin del archivo de carga para Railway.
"""

    # --------------------------------------------------------------------------
    # 1. Generar caj_railway_inserts.sql (Solo sentencias de inserción de datos)
    # --------------------------------------------------------------------------
    inserts_sql_lines = [header_common]
    inserts_sql_lines.append("""
-- =============================================================================
-- SECCIÓN DE INSERTS DE DATOS REALES Y DE DEMOSTRACIÓN
-- =============================================================================
""")

    for tname, desc in TABLES_DATA_ORDER:
        if tname in table_inserts:
            inserts_sql_lines.append(f"\n-- -----------------------------------------------------------------------------")
            inserts_sql_lines.append(f"-- Tabla `{tname}`: {desc}")
            inserts_sql_lines.append(f"-- -----------------------------------------------------------------------------")
            inserts_sql_lines.append(table_inserts[tname] + "\n")

    inserts_sql_lines.append(footer_common)
    inserts_content = "\n".join(inserts_sql_lines)

    # --------------------------------------------------------------------------
    # 2. Generar caj_railway_completo.sql (Esquema DDL completo + INSERTS)
    # --------------------------------------------------------------------------
    completo_sql_lines = [header_common]
    completo_sql_lines.append("""
-- =============================================================================
-- SECCIÓN 1: CREACIÓN DE ESTRUCTURA DE TABLAS (CREATE TABLE IF NOT EXISTS)
-- =============================================================================
-- Si las tablas ya existen (ej. por ejecución previa de 'php artisan migrate'),
-- estas sentencias se ignoran limpiamente sin provocar errores.
""")

    for tname in ALL_TABLES_ORDER:
        if tname in table_schemas:
            completo_sql_lines.append(f"\n-- -----------------------------------------------------------------------------")
            completo_sql_lines.append(f"-- Estructura de tabla: `{tname}`")
            completo_sql_lines.append(f"-- -----------------------------------------------------------------------------")
            completo_sql_lines.append(table_schemas[tname] + "\n")

    completo_sql_lines.append("""
-- =============================================================================
-- SECCIÓN 2: CARGA DE DATOS OFICIALES Y DEMOSTRATIVOS
-- =============================================================================
""")

    for tname, desc in TABLES_DATA_ORDER:
        if tname in table_inserts:
            completo_sql_lines.append(f"\n-- -----------------------------------------------------------------------------")
            completo_sql_lines.append(f"-- Datos para `{tname}`: {desc}")
            completo_sql_lines.append(f"-- -----------------------------------------------------------------------------")
            completo_sql_lines.append(table_inserts[tname] + "\n")

    completo_sql_lines.append(footer_common)
    completo_content = "\n".join(completo_sql_lines)

    # Escribir archivos en la raíz del repositorio
    file_inserts_root = 'caj_railway_inserts.sql'
    file_completo_root = 'caj_railway_completo.sql'

    with open(file_inserts_root, 'w', encoding='utf-8') as f:
        f.write(inserts_content)
    print(f"Generado exitosamente: {file_inserts_root} ({os.path.getsize(file_inserts_root):,} bytes)")

    with open(file_completo_root, 'w', encoding='utf-8') as f:
        f.write(completo_content)
    print(f"Generado exitosamente: {file_completo_root} ({os.path.getsize(file_completo_root):,} bytes)")

    # Copiar también en CajBioBioPlataforma/database/
    target_dir = os.path.join('CajBioBioPlataforma', 'database')
    if os.path.isdir(target_dir):
        shutil.copy2(file_inserts_root, os.path.join(target_dir, file_inserts_root))
        shutil.copy2(file_completo_root, os.path.join(target_dir, file_completo_root))
        print(f"Archivos copiados a {target_dir}")

if __name__ == '__main__':
    main()
