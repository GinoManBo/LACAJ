-- =============================================================================
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


-- =============================================================================
-- SECCIÓN 1: CREACIÓN DE ESTRUCTURA DE TABLAS (CREATE TABLE IF NOT EXISTS)
-- =============================================================================
-- Si las tablas ya existen (ej. por ejecución previa de 'php artisan migrate'),
-- estas sentencias se ignoran limpiamente sin provocar errores.


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `regiones`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `regiones` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden` tinyint NOT NULL,
  `operativa` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `regiones_codigo_unique` (`codigo`),
  UNIQUE KEY `regiones_nombre_unique` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `comunas`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `comunas` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `region_id` smallint unsigned NOT NULL,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lat` decimal(8,5) DEFAULT NULL,
  `lng` decimal(8,5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `comunas_region_id_nombre_unique` (`region_id`,`nombre`),
  CONSTRAINT `comunas_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regiones` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=139 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `especialidades`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `especialidades` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `orden` smallint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `especialidades_nombre_unique` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `consultorios`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `consultorios` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(140) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_unidad` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `horario_atencion` text COLLATE utf8mb4_unicode_ci,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comuna_id` smallint unsigned NOT NULL,
  `region_id` smallint unsigned NOT NULL,
  `capacidad` smallint unsigned NOT NULL DEFAULT '0',
  `color` char(7) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#0b3d91',
  `acepta_postulaciones` tinyint(1) NOT NULL DEFAULT '1',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `consultorios_nombre_comuna_unique` (`nombre`,`comuna_id`),
  KEY `consultorios_region_id_index` (`region_id`),
  KEY `consultorios_comuna_id_foreign` (`comuna_id`),
  CONSTRAINT `consultorios_comuna_id_foreign` FOREIGN KEY (`comuna_id`) REFERENCES `comunas` (`id`),
  CONSTRAINT `consultorios_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regiones` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=203 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `usuarios`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rut` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rol` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `region_id` smallint unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `debe_cambiar_password` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_acceso` datetime DEFAULT NULL,
  `intentos_fallidos` tinyint unsigned NOT NULL DEFAULT '0',
  `bloqueado_hasta` datetime DEFAULT NULL,
  `creado_por` int unsigned DEFAULT NULL,
  `firma_nombre_almacenado` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firma_mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firma_actualizada_at` datetime DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuarios_email_unique` (`email`),
  UNIQUE KEY `usuarios_rut_unique` (`rut`),
  KEY `usuarios_rol_activo_index` (`rol`,`activo`),
  KEY `usuarios_region_id_foreign` (`region_id`),
  KEY `usuarios_creado_por_foreign` (`creado_por`),
  CONSTRAINT `usuarios_creado_por_foreign` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `usuarios_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regiones` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `password_reset_tokens`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `sessions`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `abogados`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `abogados` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned NOT NULL,
  `area` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `es_segunda_instancia` tinyint(1) NOT NULL DEFAULT '0',
  `region_id` smallint unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `abogados_usuario_id_unique` (`usuario_id`),
  KEY `abogados_region_id_foreign` (`region_id`),
  CONSTRAINT `abogados_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regiones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `abogados_usuario_id_foreign` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `abogado_consultorio`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `abogado_consultorio` (
  `abogado_id` int unsigned NOT NULL,
  `consultorio_id` int unsigned NOT NULL,
  PRIMARY KEY (`abogado_id`,`consultorio_id`),
  KEY `abogado_consultorio_consultorio_id_foreign` (`consultorio_id`),
  CONSTRAINT `abogado_consultorio_abogado_id_foreign` FOREIGN KEY (`abogado_id`) REFERENCES `abogados` (`id`),
  CONSTRAINT `abogado_consultorio_consultorio_id_foreign` FOREIGN KEY (`consultorio_id`) REFERENCES `consultorios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `postulantes`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `postulantes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `rut` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `email` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `genero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nacionalidad` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discapacidad` tinyint(1) NOT NULL DEFAULT '0',
  `discapacidad_descripcion` text COLLATE utf8mb4_unicode_ci,
  `region_id` smallint unsigned NOT NULL,
  `comuna_id` smallint unsigned DEFAULT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `universidad` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `anio_carrera` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `especialidad_id` smallint unsigned DEFAULT NULL,
  `antecedentes_salud` text COLLATE utf8mb4_unicode_ci,
  `emergencia_nombre` varchar(140) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergencia_vinculo` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergencia_telefono` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergencia_email` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `datos_actualizados_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `postulantes_rut_unique` (`rut`),
  KEY `postulantes_email_index` (`email`),
  KEY `postulantes_apellido_nombre_index` (`apellido`,`nombre`),
  KEY `postulantes_region_id_foreign` (`region_id`),
  KEY `postulantes_comuna_id_foreign` (`comuna_id`),
  KEY `postulantes_especialidad_id_foreign` (`especialidad_id`),
  CONSTRAINT `postulantes_comuna_id_foreign` FOREIGN KEY (`comuna_id`) REFERENCES `comunas` (`id`),
  CONSTRAINT `postulantes_especialidad_id_foreign` FOREIGN KEY (`especialidad_id`) REFERENCES `especialidades` (`id`),
  CONSTRAINT `postulantes_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regiones` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `postulaciones`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `postulaciones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `postulante_id` int unsigned NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `origen` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'formulario',
  `region_id` smallint unsigned NOT NULL,
  `fecha_postulacion` datetime NOT NULL,
  `fecha_inicio_solicitada` date DEFAULT NULL,
  `revisada_por` int unsigned DEFAULT NULL,
  `resuelta_at` datetime DEFAULT NULL,
  `motivo_rechazo` text COLLATE utf8mb4_unicode_ci,
  `consultorio_asignado_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `postulaciones_estado_region_id_index` (`estado`,`region_id`),
  KEY `postulaciones_region_id_fecha_postulacion_index` (`region_id`,`fecha_postulacion`),
  KEY `postulaciones_postulante_id_foreign` (`postulante_id`),
  KEY `postulaciones_revisada_por_foreign` (`revisada_por`),
  KEY `postulaciones_consultorio_asignado_id_foreign` (`consultorio_asignado_id`),
  CONSTRAINT `postulaciones_consultorio_asignado_id_foreign` FOREIGN KEY (`consultorio_asignado_id`) REFERENCES `consultorios` (`id`),
  CONSTRAINT `postulaciones_postulante_id_foreign` FOREIGN KEY (`postulante_id`) REFERENCES `postulantes` (`id`),
  CONSTRAINT `postulaciones_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regiones` (`id`),
  CONSTRAINT `postulaciones_revisada_por_foreign` FOREIGN KEY (`revisada_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `postulacion_preferencias`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `postulacion_preferencias` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `postulacion_id` int unsigned NOT NULL,
  `consultorio_id` int unsigned NOT NULL,
  `orden` tinyint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `postulacion_preferencias_postulacion_id_orden_unique` (`postulacion_id`,`orden`),
  UNIQUE KEY `postulacion_preferencias_postulacion_id_consultorio_id_unique` (`postulacion_id`,`consultorio_id`),
  KEY `postulacion_preferencias_consultorio_id_foreign` (`consultorio_id`),
  CONSTRAINT `postulacion_preferencias_consultorio_id_foreign` FOREIGN KEY (`consultorio_id`) REFERENCES `consultorios` (`id`),
  CONSTRAINT `postulacion_preferencias_postulacion_id_foreign` FOREIGN KEY (`postulacion_id`) REFERENCES `postulaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `postulacion_derivaciones`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `postulacion_derivaciones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `postulacion_id` int unsigned NOT NULL,
  `region_origen_id` smallint unsigned NOT NULL,
  `region_destino_id` smallint unsigned NOT NULL,
  `usuario_id` int unsigned NOT NULL,
  `nota` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `postulacion_derivaciones_postulacion_id_foreign` (`postulacion_id`),
  KEY `postulacion_derivaciones_region_origen_id_foreign` (`region_origen_id`),
  KEY `postulacion_derivaciones_region_destino_id_foreign` (`region_destino_id`),
  KEY `postulacion_derivaciones_usuario_id_foreign` (`usuario_id`),
  CONSTRAINT `postulacion_derivaciones_postulacion_id_foreign` FOREIGN KEY (`postulacion_id`) REFERENCES `postulaciones` (`id`),
  CONSTRAINT `postulacion_derivaciones_region_destino_id_foreign` FOREIGN KEY (`region_destino_id`) REFERENCES `regiones` (`id`),
  CONSTRAINT `postulacion_derivaciones_region_origen_id_foreign` FOREIGN KEY (`region_origen_id`) REFERENCES `regiones` (`id`),
  CONSTRAINT `postulacion_derivaciones_usuario_id_foreign` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `postulacion_notas`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `postulacion_notas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `postulacion_id` int unsigned NOT NULL,
  `autor_usuario_id` int unsigned NOT NULL,
  `texto` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `postulacion_notas_postulacion_id_created_at_index` (`postulacion_id`,`created_at`),
  KEY `postulacion_notas_autor_usuario_id_foreign` (`autor_usuario_id`),
  CONSTRAINT `postulacion_notas_autor_usuario_id_foreign` FOREIGN KEY (`autor_usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `postulacion_notas_postulacion_id_foreign` FOREIGN KEY (`postulacion_id`) REFERENCES `postulaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `postulacion_borradores`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `postulacion_borradores` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned NOT NULL,
  `datos` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `postulacion_borradores_usuario_id_unique` (`usuario_id`),
  CONSTRAINT `postulacion_borradores_usuario_id_foreign` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `practicantes`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `practicantes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `postulante_id` int unsigned NOT NULL,
  `usuario_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `practicantes_postulante_id_unique` (`postulante_id`),
  UNIQUE KEY `practicantes_usuario_id_unique` (`usuario_id`),
  CONSTRAINT `practicantes_postulante_id_foreign` FOREIGN KEY (`postulante_id`) REFERENCES `postulantes` (`id`),
  CONSTRAINT `practicantes_usuario_id_foreign` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `practicas`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `practicas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `postulacion_id` int unsigned NOT NULL,
  `practicante_id` int unsigned NOT NULL,
  `consultorio_id` int unsigned NOT NULL,
  `abogado_id` int unsigned DEFAULT NULL,
  `abogado_segunda_instancia_id` int unsigned DEFAULT NULL,
  `carta_presentacion_firmada` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `folio_carta_presentacion` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `carta_presentacion_subida_at` datetime DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_termino` date NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `folio_caj` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nota_practica` decimal(2,1) DEFAULT NULL,
  `resolucion_numero` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resolucion_anio` smallint unsigned DEFAULT NULL,
  `oficio_daj_numero` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `oficio_daj_fecha` date DEFAULT NULL,
  `finalizada_at` datetime DEFAULT NULL,
  `validada_director_at` datetime DEFAULT NULL,
  `validada_director_por` int unsigned DEFAULT NULL,
  `observaciones_director` text COLLATE utf8mb4_unicode_ci,
  `revisor_colegiado_id` int unsigned DEFAULT NULL,
  `revisor_colegiado_nombre` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remitida_at` datetime DEFAULT NULL,
  `remitida_por` int unsigned DEFAULT NULL,
  `nivelada_at` datetime DEFAULT NULL,
  `nivelada_por` int unsigned DEFAULT NULL,
  `justificacion_nivelacion` text COLLATE utf8mb4_unicode_ci,
  `causal_rechazo_sancion` text COLLATE utf8mb4_unicode_ci,
  `cancelacion_motivo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelacion_detalle` text COLLATE utf8mb4_unicode_ci,
  `cancelacion_fecha` date DEFAULT NULL,
  `cancelacion_antes_de_iniciar` tinyint(1) DEFAULT NULL,
  `cancelada_por` int unsigned DEFAULT NULL,
  `documentos_autorizados` tinyint(1) NOT NULL DEFAULT '0',
  `documentos_autorizados_por` int unsigned DEFAULT NULL,
  `documentos_autorizados_at` datetime DEFAULT NULL,
  `despachada_at` datetime DEFAULT NULL,
  `firmado_director_general_at` datetime DEFAULT NULL,
  `firmado_director_general_por` int unsigned DEFAULT NULL,
  `firmagob_folio` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firmagob_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificado_firmagob_id` int unsigned DEFAULT NULL,
  `despachada_por` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `practicas_postulacion_id_unique` (`postulacion_id`),
  KEY `practicas_estado_consultorio_id_index` (`estado`,`consultorio_id`),
  KEY `practicas_practicante_id_index` (`practicante_id`),
  KEY `practicas_fecha_termino_index` (`fecha_termino`),
  KEY `practicas_documentos_autorizados_index` (`documentos_autorizados`),
  KEY `practicas_consultorio_id_foreign` (`consultorio_id`),
  KEY `practicas_abogado_id_foreign` (`abogado_id`),
  KEY `practicas_cancelada_por_foreign` (`cancelada_por`),
  KEY `practicas_documentos_autorizados_por_foreign` (`documentos_autorizados_por`),
  KEY `practicas_remitida_por_foreign` (`remitida_por`),
  KEY `practicas_nivelada_por_foreign` (`nivelada_por`),
  KEY `practicas_despachada_por_foreign` (`despachada_por`),
  KEY `practicas_abogado_segunda_instancia_id_foreign` (`abogado_segunda_instancia_id`),
  KEY `practicas_validada_director_por_foreign` (`validada_director_por`),
  KEY `practicas_revisor_colegiado_id_foreign` (`revisor_colegiado_id`),
  KEY `practicas_firmado_director_general_por_foreign` (`firmado_director_general_por`),
  KEY `practicas_certificado_firmagob_id_foreign` (`certificado_firmagob_id`),
  CONSTRAINT `practicas_abogado_id_foreign` FOREIGN KEY (`abogado_id`) REFERENCES `abogados` (`id`),
  CONSTRAINT `practicas_abogado_segunda_instancia_id_foreign` FOREIGN KEY (`abogado_segunda_instancia_id`) REFERENCES `abogados` (`id`) ON DELETE SET NULL,
  CONSTRAINT `practicas_cancelada_por_foreign` FOREIGN KEY (`cancelada_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `practicas_certificado_firmagob_id_foreign` FOREIGN KEY (`certificado_firmagob_id`) REFERENCES `documentos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `practicas_consultorio_id_foreign` FOREIGN KEY (`consultorio_id`) REFERENCES `consultorios` (`id`),
  CONSTRAINT `practicas_despachada_por_foreign` FOREIGN KEY (`despachada_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `practicas_documentos_autorizados_por_foreign` FOREIGN KEY (`documentos_autorizados_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `practicas_firmado_director_general_por_foreign` FOREIGN KEY (`firmado_director_general_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `practicas_nivelada_por_foreign` FOREIGN KEY (`nivelada_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `practicas_postulacion_id_foreign` FOREIGN KEY (`postulacion_id`) REFERENCES `postulaciones` (`id`),
  CONSTRAINT `practicas_practicante_id_foreign` FOREIGN KEY (`practicante_id`) REFERENCES `practicantes` (`id`),
  CONSTRAINT `practicas_remitida_por_foreign` FOREIGN KEY (`remitida_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `practicas_revisor_colegiado_id_foreign` FOREIGN KEY (`revisor_colegiado_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `practicas_validada_director_por_foreign` FOREIGN KEY (`validada_director_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `practica_traslados`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `practica_traslados` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `practica_id` int unsigned NOT NULL,
  `consultorio_origen_id` int unsigned NOT NULL,
  `consultorio_destino_id` int unsigned NOT NULL,
  `abogado_origen_id` int unsigned DEFAULT NULL,
  `abogado_destino_id` int unsigned DEFAULT NULL,
  `fecha` date NOT NULL,
  `motivo` text COLLATE utf8mb4_unicode_ci,
  `usuario_id` int unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `practica_traslados_practica_id_foreign` (`practica_id`),
  KEY `practica_traslados_consultorio_origen_id_foreign` (`consultorio_origen_id`),
  KEY `practica_traslados_consultorio_destino_id_foreign` (`consultorio_destino_id`),
  KEY `practica_traslados_usuario_id_foreign` (`usuario_id`),
  CONSTRAINT `practica_traslados_consultorio_destino_id_foreign` FOREIGN KEY (`consultorio_destino_id`) REFERENCES `consultorios` (`id`),
  CONSTRAINT `practica_traslados_consultorio_origen_id_foreign` FOREIGN KEY (`consultorio_origen_id`) REFERENCES `consultorios` (`id`),
  CONSTRAINT `practica_traslados_practica_id_foreign` FOREIGN KEY (`practica_id`) REFERENCES `practicas` (`id`),
  CONSTRAINT `practica_traslados_usuario_id_foreign` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `evaluaciones`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `evaluaciones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `practica_id` int unsigned NOT NULL,
  `abogado_id` int unsigned NOT NULL,
  `conocimiento_juridico` decimal(2,1) NOT NULL,
  `responsabilidad` decimal(2,1) NOT NULL,
  `iniciativa` decimal(2,1) NOT NULL,
  `sentido_social` decimal(2,1) NOT NULL,
  `conducta` decimal(2,1) NOT NULL,
  `honorabilidad` decimal(2,1) NOT NULL,
  `asistencia_puntualidad` decimal(2,1) NOT NULL,
  `promedio` decimal(3,2) NOT NULL,
  `aprobado` tinyint(1) NOT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `evaluaciones_practica_id_unique` (`practica_id`),
  KEY `evaluaciones_abogado_id_foreign` (`abogado_id`),
  CONSTRAINT `evaluaciones_abogado_id_foreign` FOREIGN KEY (`abogado_id`) REFERENCES `abogados` (`id`),
  CONSTRAINT `evaluaciones_practica_id_foreign` FOREIGN KEY (`practica_id`) REFERENCES `practicas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `calificaciones`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `calificaciones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `practica_id` int unsigned NOT NULL,
  `instancia` tinyint unsigned NOT NULL,
  `nota` decimal(2,1) NOT NULL,
  `fecha` date NOT NULL,
  `registrada_por` int unsigned NOT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `calificaciones_practica_id_instancia_unique` (`practica_id`,`instancia`),
  KEY `calificaciones_registrada_por_foreign` (`registrada_por`),
  CONSTRAINT `calificaciones_practica_id_foreign` FOREIGN KEY (`practica_id`) REFERENCES `practicas` (`id`),
  CONSTRAINT `calificaciones_registrada_por_foreign` FOREIGN KEY (`registrada_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `documentos`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `documentos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tipo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `postulacion_id` int unsigned DEFAULT NULL,
  `practica_id` int unsigned DEFAULT NULL,
  `nombre_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_almacenado` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamano_bytes` int unsigned NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subido_por` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `documentos_nombre_almacenado_unique` (`nombre_almacenado`),
  KEY `documentos_postulacion_id_tipo_index` (`postulacion_id`,`tipo`),
  KEY `documentos_practica_id_tipo_index` (`practica_id`,`tipo`),
  CONSTRAINT `documentos_postulacion_id_foreign` FOREIGN KEY (`postulacion_id`) REFERENCES `postulaciones` (`id`),
  CONSTRAINT `documentos_practica_id_foreign` FOREIGN KEY (`practica_id`) REFERENCES `practicas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `archivos_staging`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `archivos_staging` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_almacenado` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamano_bytes` int unsigned NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  `consumed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `certificados`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `certificados` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `practica_id` int unsigned NOT NULL,
  `tipo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_resolucion` smallint unsigned DEFAULT NULL,
  `anio` smallint unsigned DEFAULT NULL,
  `fecha_emision` date NOT NULL,
  `documento_id` int unsigned DEFAULT NULL,
  `emitido_por` int unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificados_practica_id_tipo_unique` (`practica_id`,`tipo`),
  UNIQUE KEY `certificados_tipo_anio_numero_resolucion_unique` (`tipo`,`anio`,`numero_resolucion`),
  UNIQUE KEY `certificados_documento_id_unique` (`documento_id`),
  KEY `certificados_emitido_por_foreign` (`emitido_por`),
  CONSTRAINT `certificados_documento_id_foreign` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`),
  CONSTRAINT `certificados_emitido_por_foreign` FOREIGN KEY (`emitido_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `certificados_practica_id_foreign` FOREIGN KEY (`practica_id`) REFERENCES `practicas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `resolucion_secuencia`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `resolucion_secuencia` (
  `anio` smallint unsigned NOT NULL,
  `ultimo_numero` smallint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`anio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `notificaciones`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `usuario_destino_id` int unsigned NOT NULL,
  `tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad_tipo` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entidad_id` int unsigned DEFAULT NULL,
  `creada_por` int unsigned DEFAULT NULL,
  `leida_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `notificaciones_usuario_destino_id_leida_at_index` (`usuario_destino_id`,`leida_at`),
  KEY `notificaciones_creada_por_foreign` (`creada_por`),
  CONSTRAINT `notificaciones_creada_por_foreign` FOREIGN KEY (`creada_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `notificaciones_usuario_destino_id_foreign` FOREIGN KEY (`usuario_destino_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `auditoria`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `auditoria` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned DEFAULT NULL,
  `accion` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad_id` int unsigned DEFAULT NULL,
  `datos` json DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `auditoria_entidad_entidad_id_index` (`entidad`,`entidad_id`),
  KEY `auditoria_usuario_id_created_at_index` (`usuario_id`,`created_at`),
  CONSTRAINT `auditoria_usuario_id_foreign` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `cache`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `cache_locks`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `jobs`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `job_batches`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `failed_jobs`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Estructura de tabla: `migrations`
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================================
-- SECCIÓN 2: CARGA DE DATOS OFICIALES Y DEMOSTRATIVOS
-- =============================================================================


-- -----------------------------------------------------------------------------
-- Datos para `regiones`: 1. Regiones operativas oficiales (Ñuble, Biobío, Araucanía, Los Ríos, Los Lagos, Aysén)
-- -----------------------------------------------------------------------------
INSERT INTO `regiones` (`id`, `codigo`, `nombre`, `orden`, `operativa`) VALUES (1,'NB','Región de Ñuble',1,1),(2,'BI','Región del Biobío',2,1),(3,'AR','Región de La Araucanía',3,1),(4,'LR','Región de Los Ríos',4,1),(5,'LL','Región de Los Lagos',5,1),(6,'AI','Región de Aysén',6,1);


-- -----------------------------------------------------------------------------
-- Datos para `comunas`: 2. Comunas oficiales de las 6 regiones de la jurisdicción (138 comunas)
-- -----------------------------------------------------------------------------
INSERT INTO `comunas` (`id`, `region_id`, `nombre`, `lat`, `lng`) VALUES (1,1,'Chillán',-36.60630,-72.10340),(2,1,'Bulnes',NULL,NULL),(3,1,'Chillán Viejo',NULL,NULL),(4,1,'El Carmen',NULL,NULL),(5,1,'Pemuco',NULL,NULL),(6,1,'Pinto',NULL,NULL),(7,1,'Quillón',NULL,NULL),(8,1,'San Ignacio',NULL,NULL),(9,1,'Yungay',NULL,NULL),(10,1,'Quirihue',NULL,NULL),(11,1,'Cobquecura',NULL,NULL),(12,1,'Coelemu',NULL,NULL),(13,1,'Ninhue',NULL,NULL),(14,1,'Portezuelo',NULL,NULL),(15,1,'Ránquil',NULL,NULL),(16,1,'Treguaco',NULL,NULL),(17,1,'San Carlos',NULL,NULL),(18,1,'Coihueco',NULL,NULL),(19,1,'Ñiquén',NULL,NULL),(20,1,'San Fabián',NULL,NULL),(21,1,'San Nicolás',NULL,NULL),(22,2,'Concepción',-36.82700,-73.05030),(23,2,'Coronel',-37.02760,-73.13790),(24,2,'Chiguayante',NULL,NULL),(25,2,'Florida',NULL,NULL),(26,2,'Hualqui',NULL,NULL),(27,2,'Lota',NULL,NULL),(28,2,'Penco',NULL,NULL),(29,2,'San Pedro de la Paz',-36.83860,-73.10660),(30,2,'Santa Juana',NULL,NULL),(31,2,'Talcahuano',-36.72490,-73.11690),(32,2,'Tomé',NULL,NULL),(33,2,'Hualpén',NULL,NULL),(34,2,'Lebu',NULL,NULL),(35,2,'Arauco',NULL,NULL),(36,2,'Cañete',NULL,NULL),(37,2,'Contulmo',NULL,NULL),(38,2,'Curanilahue',NULL,NULL),(39,2,'Los Álamos',NULL,NULL),(40,2,'Tirúa',NULL,NULL),(41,2,'Los Ángeles',-37.46970,-72.35370),(42,2,'Antuco',NULL,NULL),(43,2,'Cabrero',NULL,NULL),(44,2,'Laja',NULL,NULL),(45,2,'Mulchén',NULL,NULL),(46,2,'Nacimiento',NULL,NULL),(47,2,'Negrete',NULL,NULL),(48,2,'Quilaco',NULL,NULL),(49,2,'Quilleco',NULL,NULL),(50,2,'San Rosendo',NULL,NULL),(51,2,'Santa Bárbara',NULL,NULL),(52,2,'Tucapel',NULL,NULL),(53,2,'Yumbel',NULL,NULL),(54,2,'Alto Biobío',NULL,NULL),(55,3,'Temuco',-38.73590,-72.59040),(56,3,'Carahue',NULL,NULL),(57,3,'Cunco',NULL,NULL),(58,3,'Curarrehue',NULL,NULL),(59,3,'Freire',NULL,NULL),(60,3,'Galvarino',NULL,NULL),(61,3,'Gorbea',NULL,NULL),(62,3,'Lautaro',NULL,NULL),(63,3,'Loncoche',NULL,NULL),(64,3,'Melipeuco',NULL,NULL),(65,3,'Nueva Imperial',NULL,NULL),(66,3,'Padre Las Casas',NULL,NULL),(67,3,'Perquenco',NULL,NULL),(68,3,'Pitrufquén',NULL,NULL),(69,3,'Pucón',NULL,NULL),(70,3,'Saavedra',NULL,NULL),(71,3,'Teodoro Schmidt',NULL,NULL),(72,3,'Toltén',NULL,NULL),(73,3,'Vilcún',NULL,NULL),(74,3,'Villarrica',NULL,NULL),(75,3,'Cholchol',NULL,NULL),(76,3,'Angol',NULL,NULL),(77,3,'Collipulli',NULL,NULL),(78,3,'Curacautín',NULL,NULL),(79,3,'Ercilla',NULL,NULL),(80,3,'Lonquimay',NULL,NULL),(81,3,'Los Sauces',NULL,NULL),(82,3,'Lumaco',NULL,NULL),(83,3,'Purén',NULL,NULL),(84,3,'Renaico',NULL,NULL),(85,3,'Traiguén',NULL,NULL),(86,3,'Victoria',NULL,NULL),(87,4,'Valdivia',-39.81420,-73.24590),(88,4,'Corral',NULL,NULL),(89,4,'Lanco',NULL,NULL),(90,4,'Los Lagos',NULL,NULL),(91,4,'Máfil',NULL,NULL),(92,4,'Mariquina',NULL,NULL),(93,4,'Paillaco',NULL,NULL),(94,4,'Panguipulli',NULL,NULL),(95,4,'La Unión',NULL,NULL),(96,4,'Futrono',NULL,NULL),(97,4,'Lago Ranco',NULL,NULL),(98,4,'Río Bueno',NULL,NULL),(99,5,'Puerto Montt',-41.46930,-72.94240),(100,5,'Calbuco',NULL,NULL),(101,5,'Cochamó',NULL,NULL),(102,5,'Fresia',NULL,NULL),(103,5,'Frutillar',NULL,NULL),(104,5,'Los Muermos',NULL,NULL),(105,5,'Llanquihue',NULL,NULL),(106,5,'Maullín',NULL,NULL),(107,5,'Puerto Varas',NULL,NULL),(108,5,'Castro',NULL,NULL),(109,5,'Ancud',NULL,NULL),(110,5,'Chonchi',NULL,NULL),(111,5,'Curaco de Vélez',NULL,NULL),(112,5,'Dalcahue',NULL,NULL),(113,5,'Puqueldón',NULL,NULL),(114,5,'Queilén',NULL,NULL),(115,5,'Quellón',NULL,NULL),(116,5,'Quemchi',NULL,NULL),(117,5,'Quinchao',NULL,NULL),(118,5,'Osorno',NULL,NULL),(119,5,'Puerto Octay',NULL,NULL),(120,5,'Purranque',NULL,NULL),(121,5,'Puyehue',NULL,NULL),(122,5,'Río Negro',NULL,NULL),(123,5,'San Juan de la Costa',NULL,NULL),(124,5,'San Pablo',NULL,NULL),(125,5,'Chaitén',NULL,NULL),(126,5,'Futaleufú',NULL,NULL),(127,5,'Hualaihué',NULL,NULL),(128,5,'Palena',NULL,NULL),(129,6,'Coyhaique',-45.57120,-72.06850),(130,6,'Lago Verde',NULL,NULL),(131,6,'Aysén',NULL,NULL),(132,6,'Cisnes',NULL,NULL),(133,6,'Guaitecas',NULL,NULL),(134,6,'Cochrane',NULL,NULL),(135,6,'O\'Higgins',NULL,NULL),(136,6,'Tortel',NULL,NULL),(137,6,'Chile Chico',NULL,NULL),(138,6,'Río Ibáñez',NULL,NULL);


-- -----------------------------------------------------------------------------
-- Datos para `especialidades`: 3. Especialidades jurídicas institucionales (5 especialidades)
-- -----------------------------------------------------------------------------
INSERT INTO `especialidades` (`id`, `nombre`, `activo`, `orden`) VALUES (1,'Derecho Civil',1,1),(2,'Derecho Penal',1,2),(3,'Derecho Laboral',1,3),(4,'Derecho de Familia',1,4),(5,'Otra',1,99);


-- -----------------------------------------------------------------------------
-- Datos para `consultorios`: 4. Catálogo oficial completo de consultorios y unidades de la CAJ Biobío (202 sedes con dirección, teléfono, horario, email y capacidad)
-- -----------------------------------------------------------------------------
INSERT INTO `consultorios` (`id`, `nombre`, `tipo_unidad`, `direccion`, `telefono`, `horario_atencion`, `email`, `comuna_id`, `region_id`, `capacidad`, `color`, `acepta_postulaciones`, `activo`, `created_at`, `updated_at`) VALUES (1,'Consultorio Centro — Concepción',NULL,'O\'Higgins 440, piso 3, Concepción',NULL,NULL,NULL,22,2,6,'#0b3d91',1,1,'2026-11-05 11:06:59','2026-11-05 11:06:59'),(2,'Consultorio Talcahuano',NULL,'Colón 1262, Talcahuano',NULL,NULL,NULL,31,2,4,'#0b3d91',1,1,'2026-11-05 11:06:59','2026-11-05 11:06:59'),(3,'Consultorio San Pedro de la Paz',NULL,'Av. Gran Bretaña 3035, San Pedro',NULL,NULL,NULL,29,2,5,'#0b3d91',1,1,'2026-11-05 11:06:59','2026-11-05 11:06:59'),(4,'Consultorio Los Ángeles',NULL,'Caupolicán 380, Los Ángeles',NULL,NULL,NULL,41,2,3,'#0b3d91',1,1,'2026-11-05 11:06:59','2026-11-05 11:06:59'),(5,'Consultorio Chillán',NULL,'Arauco 584, Chillán',NULL,NULL,NULL,1,1,3,'#0b3d91',1,1,'2026-11-05 11:06:59','2026-11-05 11:06:59'),(6,'Consultorio Coronel',NULL,'Manuel Rodríguez 481, Coronel',NULL,NULL,NULL,23,2,2,'#0b3d91',1,1,'2026-11-05 11:06:59','2026-11-05 11:06:59'),(7,'Consultorio Temuco',NULL,'Manuel Montt 855, Temuco',NULL,NULL,NULL,55,3,4,'#0b3d91',1,1,'2026-11-05 11:06:59','2026-11-05 11:06:59'),(8,'Consultorio Valdivia',NULL,'Arauco 371, Valdivia',NULL,NULL,NULL,87,4,4,'#0b3d91',1,1,'2026-11-05 11:06:59','2026-11-05 11:06:59'),(9,'Consultorio Puerto Montt',NULL,'Benavente 405, Puerto Montt',NULL,NULL,NULL,99,5,4,'#0b3d91',1,1,'2026-11-05 11:06:59','2026-11-05 11:06:59'),(10,'Consultorio Coyhaique',NULL,'Baquedano 310, Coyhaique',NULL,NULL,NULL,129,6,2,'#0b3d91',1,1,'2026-11-05 11:06:59','2026-11-05 11:06:59'),(11,'CAVI CHILLÁN','CAVI','Gamero n°533, Chillán','(42) 2229417','Lunes a Jueves de 09:00 a 16:30\nViernes de 09:00 a 13:00','caivchillan@cajbiobio.cl',1,1,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(12,'CAJ BULNES','CJ','Arturo Prat Nº 495 Bulnes','981214631','Lunes a Miércoles de 09:00 a 17:30\nJueves y Viernes de 09:00 a 13:00','cbulnes@cajbiobio.cl',2,1,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(13,'CAJ CHILLAN CIVIL','CJ','Constitución Nº 492 4º piso, Chillán','965957797','Lunes a Viernes de 09:00 a 17:30','cchillan@cajbiobio.cl',1,1,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(14,'CAJ CHILLAN FAMILIA','CJ','Constitución Nº 492 4º piso, Chillán','965957797','Lunes a Viernes de 09:00 a 17:30','cchillan@cajbiobio.cl',1,1,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(15,'CAJ CHILLAN VIEJO','OJA','Antonio Varas Nº 979 Chillán Viejo','971007730','Lunes, Miércoles, Jueves y Viernes de 09:00 a 17:30\nMartes de 09:00 a 12:00','cchillanviejo@cajbiobio.cl',3,1,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(16,'CAJ COBQUECURA','OJA','Independencia Nº300, Municipalidad Cobquecura','(44) 3579694','Lunes, Martes y Viernes de 09:00 a 13:00\nJueves de 09:00 a 18:00','ccobquecura@cajbiobio.cl',11,1,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(17,'CAJ COELEMU','OJA','Samuel Bambach 586, Coelemu','971026186','Lunes, Miércoles y Viernes de 09:00 a 17:30\nMartes de 9:00 a 16:30 Jueves de 09:00 a 14:00','ccoelemu@cajbiobio.cl',12,1,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(18,'CAJ COIHUECO','OJA','Luis Hermosilla N°1387','(42) 2859004 anexo 273','Martes y Jueves de 09:00 a 17:30','ccoihueco@cajbiobio.cl',18,1,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(19,'CAJ EL CARMEN','OJA','Balmaceda N°211, El Carmen','(42) 2270271','Lunes y Martes de 09:00 a 13:00\nMiércoles de 09:00 a 17:30\nJueves de 14:00 a 17:30','celcarmen@cajbiobio.cl',4,1,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(20,'CAJ NINHUE','OJA','N-48, Municipalidad Ninhue','969182009','Viernes de 14:00 a 18:00','cninhue@cajbiobio.cl',13,1,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(21,'CAJ PEMUCO','OJA','Claudio Carrasco Nº 596 (interior), Edif. Públicos Pemuco','971005521','Lunes, Martes, Jueves y Viernes de 9:00 a 12:00\nMiercoles de 9:00 a 17:30','cpemuco@cajbiobio.cl',5,1,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(22,'CAJ PINTO','OJA','Manuel Balmaceda 160 D, Galería- El Huemul 2 Pinto','981214718','Lunes de 09:00 a 17:30\nMartes, Jueves y Viernes de 09:00 a 13:00','cpinto@cajbiobio.cl',6,1,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(23,'CAJ PORTEZUELO','OJA','Catedral 586','969192840','Lunes, Martes y Jueves de 9:00 a 13:00\nMiercoles de 9:00 a 17:30','cportezuelo@cajbiobio.cl',14,1,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(24,'CAJ QUILLON','OJA','El Roble nº 299, Municipalidad Quillón','956882645','Lunes de 14:30 a 18:30\nMartes y Jueves de 09:00 a 13:00\nMiércoles de 09:00 a 17:30','cquillon@cajbiobio.cl',7,1,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(25,'CAJ QUIRIHUE','OJA','Independencia Nº121 Quirihue','998858661','Lunes y Miercoles 9:00 a 17:00\nMartes y Jueves de 14:00 a 17:00\nViernes de 9:00 a 14:00','cquirihue@cajbiobio.cl',10,1,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(26,'CAJ RANQUIL','OJA','Nicolás León Nº521 Municipalidad Ranquil','958895195','Martes de 09:00 a 17:30','cranquil@cajbiobio.cl',15,1,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(27,'CAJ SAN CARLOS','CJ','Maipú Nº 874. San Carlos','975889016','Lunes y Miércoles de 09:00 a 14:00\nMartes, Jueves y Viernes de 09:00 a 17:00','csancarlos@cajbiobio.cl',17,1,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(28,'CAJ SAN GREGORIO/ÑIQUEN','OJA','Estado Nº 188 esq. Ernesto Zuñiga Ñiquen','969193552','Martes a Viernes, de 9:00 a 13:00 hrs, y Miercoles horario extendido (14:00 a 18:00 hrs)','csangregorio@cajbiobio.cl',19,1,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(29,'CAJ SAN IGNACIO','OJA','Ángel Custodio Hernández S/N, Esq. Los Carreras. Edificios Públicos San Ignacio','969189826','Lunes a Miércoles de 09:00 a 18:00\nJueves y Viernes de 09:00 a 14:00','csanignacio@cajbiobio.cl',8,1,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(30,'CAJ SAN NICOLAS','OJA','Esmeralda S/N esquina Balmaceda San Nicolás','969197645','Lunes, Miercoles, Jueves y Viernes de 9:00 a 13:00\nMartes de 14:00 a 18:00','csannicolas@cajbiobio.cl',21,1,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(31,'CAJ TREHUACO','OJA','Calle G. Orejola Nº460 Municipalidad Trehuaco','969198963','Lunes de 14:00 a 17:00','ctrehuaco@cajbiobio.cl',16,1,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(32,'CAJ YUNGAY','CJ','Prat Nº 357 2° Piso','998858709','Lunes a Viernes 8:30 a 14:00 y 15:00 a 17:00','cyungay@cajbiobio.cl',9,1,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(33,'CAJ HUEPIL/TUCAPEL','OJA','Independencia N° 362 interior (a un costado del municipio)','969192882','Lunes a Jueves de 8:30 a 17:00\nviernes de 8:30 a 13:30','ctucapel@cajbiobio.cl',52,2,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(34,'DIRECCIÓN REGIONAL ÑUBLE','DG/DR','Constitución Nº 492 4º piso, Chillán','956473901','Lunes a Viernes 09:00 a 17:30','dirnuble@cajbiobio.cl',1,1,1,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(35,'OFICINA MEDIACION CHILLAN','CMED','Constitución Nº 492 4º piso, Chillán','971008779','Lunes y Martes de 9:00 a 17:30\nMiércoles de 9:00 a 13:00','mediacionchillan@cajbiobio.cl',1,1,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(36,'PROGRAMA MI ABOGADO CHILLAN','PMA','Avda. Ohiggins 563, Chillán','(42) 2282617','Lunes a Jueves de 08:30 a 17:30\nViernes de 08:30 a 16:30','miabogadochillan@cajbiobio.cl',1,1,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(37,'NAD CHILLÁN','NAD','Arauco N°308, Chillán','(44) 3 67 6903','Lunes a Viernes de 08:00 a 17:00','nadnuble@cajbiobio.cl',1,1,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(38,'NAD YUNGAY','NAD','Tacna N°70, Yungay','S/N','Lunes a Miércoles de 08:00 a 16:00','nadnuble@cajbiobio.cl',10,1,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(39,'NAD SAN CARLOS','NAD','Maipú 680 1 er piso San Carlos','S/N','Lunes a Jueves 07:30 a 16:00','nadnuble@cajbiobio.cl',17,1,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(40,'ODL CHILLAN','ODL','Claudio Arrau N° 784 Chillán','958894064','Lunes a Viernes 08:30 a 18:00','odlchillan@cajbiobio.cl',1,1,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(41,'ODAM CHILLÁN','ODAM','CONSTITUCIÓN N° 492 PISO 4','963171790 / 963171172','Lunes a Viernes de 09:00 a 17:30','pamchillan@cajbiobio.cl',1,1,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(42,'OFICINA SEGUNDA INSTANCIA','2DA INSTANCIA','Constitución Nº 492 4º piso, Chillán','981214718','Lunes a Viernes de 09:00 a 17:30','segundainstanciachillan@cajbiobio.cl',1,1,4,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(43,'CAJ ALTO BIO BIO','OJA','Dependencias de Municipalidad de Alto Bio Bio.','971029053','Lunes: 09:00-14:00 y 15:00-18:00\nMartes: 09:00-14:00 y 15:00-18:00\nMiercoles: 09:00-14:00 y 15:00-18:00','caltobiobio@cajbiobio.cl',54,2,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(44,'CAJ ANTUCO','OJA','Calle Calixto Padilla Nº155 Antuco','(43) 2633219 / 969195826','Lunes: 09:00-13:00\nMartes: 09:00 - 17:00\nMiercoles: 09:00-13:00\nJueves: 09:00-13:00','cantuco@cajbiobio.cl',42,2,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(45,'CAJ ARAUCO','CJ','Caupolicán 271 Arauco','(41) 2106234 / 971022095','Lunes: 09:00-13:30 y 15:00-16:00\nMartes: 09:00-13:30 y 15:00-16:00\nMiercoles: 09:00-13:30 y 15:00-16:00\nJueves: 09:00-13:30 y 15:00-16:00\nViernes: 09:00-13:30 y 15:00-16:00','carauco@cajbiobio.cl',35,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(46,'CAJ BARRIO NORTE','CJ','Manuel Gutiérrez Nº 1804','(41) 2237987 / 981214748','Lunes: 09:00-13:30 y 15:00-17:30\nMartes: 09:00-13:30 y 15:00-17:30\nMiercoles: 09:00-13:30 y 15:00-17:30\nJueves: 09:00-13:30 y 15:00-17:30\nViernes: 09:00-12:00','cbarrionorte@cajbiobio.cl',22,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(47,'CAJ CABRERO','OJA','Calle Rio Claro N° 249, Cabrero','971024724','Lunes: 09:00-13:30\nMartes: 09:00-13:30\nMiercoles: 09:00-13:30\nJueves: 09:00-13:30\nViernes: 09:00-13:30','ccabrero@cajbiobio.cl',43,2,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(48,'CAJ CAÑETE','CJ','Esmeralda Nº 367. Cañete','(41) 2613671 / 971023487','Lunes: 09:00-13:30 y 14:30-18:00\nMartes: 09:00-13:30 y 14:30-18:00\nMiercoles: 09:00-13:30 y 14:30-18:00\nJueves: 09:00-13:30 y 14:30-18:00\nViernes: 09:00-13:30 y 14:30-18:00','ccanete@cajbiobio.cl',36,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(49,'CAJ CHIGUAYANTE','CJ','Pedro Medina Nº 540. Chiguayante','(41) 2354165','Lunes: 09:00-13:30 y 15:00-17:30\nMartes: 09:00-13:30 y 15:00-17:30\nMiercoles: 09:00-13:30 y 15:00-17:30\nJueves: 09:00-13:30 y 15:00-17:30\nViernes: 09:00-13:30 y 15:00-17:30','cchiguayante@cajbiobio.cl',24,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-10 12:47:51'),(50,'CAJ CIVIL LOS ANGELES','CJ','Los Carreras Nº 395 - 399 (esquina Lynch) Los Ángeles','(43) 2344417 / 969183594','LUNES A VIERNES DE 09 A 13:30.- 14:00 A 16:30\nLunes: 09:00-13:30 y 14:00-16:30\nMartes: 09:00-13:30 y 14:00-16:30\nMiercoles: 09:00-13:30 y 14:00-16:30\nJueves: 09:00-13:30 y 14:00-16:30\nViernes: 09:00-13:30 y 14:00-16:30','closangeles@cajbiobio.cl',41,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(51,'CAJ CONCEPCION CIVIL','CJ','Lincoyán N°344, Concepción','(41) 2735955 - 2735956 - 2735957','Lunes: 08:30-13:00 y 15:00-17:30\nMartes: 08:30-13:00 y 15:00-17:30\nMiercoles: 08:30-13:00 y 15:00-17:30\nJueves: 08:30-13:00 y 15:00-17:30\nViernes: 08:30-13:00 y 15:00-17:30','cconcepcion@cajbiobio.cl',22,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:57'),(52,'CAJ CONCEPCION FAMILIA','CJ','Lincoyán N°344, Concepción','41 2736570 / 41 2736586 / 981214541','Lunes: 09:00-13:00 y 15:00-17:30\nMartes: 09:00-13:00 y 15:00-17:30\nMiercoles: 09:00-13:00 y 15:00-17:30\nJueves: 09:00-13:00 y 15:00-17:30\nViernes: 09:00-13:00 y 15:00-17:30','familiaconcepcion@cajbiobio.cl',22,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(53,'CAJ CONCEPCION PENAL INFRACCIONAL','CJ','Lincoyán N°344, Concepción','971028943','Lunes: 09:00-13:00 y 15:00-17:30\nMartes: 09:00-13:00 y 15:00-17:30\nMiercoles: 09:00-13:00 y 15:00-17:30\nJueves: 09:00-13:00 y 15:00-17:30\nViernes: 09:00-13:00 y 15:00-17:30','penalinfraccional@cajbiobio.cl',22,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(54,'CAJ CORONEL','CJ','Remigio Castro Nº 221 Esquina Sotomayor. Coronel.','(41) 2770958 / (41) 2714678 / 981214599','Lunes: 09:00-13:30\nMartes: 09:00-13:30\nMiercoles: 09:00-13:30\nJueves: 09:00-13:30 y 14:30-17:30\nViernes: 09:00-13:30','ccoronel@cajbiobio.cl',23,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(55,'CAJ CURANILAHUE','CJ','Caupolican N° 736 Locales C y D','9 71023623','Lunes: 09:00-13:00 y 15:00-17:30\nMartes: 09:00-13:00 y 15:00-17:30\nMiercoles: 09:00-13:00 y 15:00-17:30\nJueves: 09:00-13:00 y 15:00-17:30\nViernes: 09:00-13:00 y 15:00-17:30','ccuranilahue@cajbiobio.cl',38,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(56,'CAJ FAM LOS ANGELES','CJ','Los Carreras Nº 395 - 399 (esquina Lynch) Los Ángeles','(43)2630072 / (43)2630073','Lunes: 09:00-13:00 y 14:30-16:30\nMartes: 09:00-13:00 y 14:30-16:30\nMiercoles: 09:00-13:00 y 14:30-16:30\nJueves: 09:00-13:00 y 14:30-16:30\nViernes: 09:00-13:00 y 14:30-16:30','familialosangeles@cajbiobio.cl',41,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(57,'CAJ FLORIDA','OJA','Ignacio Serrano N° 500. Florida','(44) 3208126 / 998858626','Miercoles: 09:00-13:00 y 14:00-17:30\nJueves: 09:00-13:00 y 14:00-17:30\nViernes: 09:00-13:00','cflorida@cajbiobio.cl',25,2,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(58,'CAJ HUALPEN','CJ','Holanda Nº 1224 Hualpén','(41) 2410845 / 981214661','Lunes: 09:00-13:00 y 14:30-17:15\nMartes: 09:00-13:00 y 14:30-17:15\nMiercoles: 09:00-13:00 y 14:30-17:15\nJueves: 09:00-13:00 y 14:30-17:15\nViernes: 09:00-13:00','chualpen@cajbiobio.cl',33,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(59,'CAJ HUALQUI','OJA','Freire 448-A, Hualqui.','(41) 2780209 / 981214717','Martes: 09:00-13:00\nMiercoles: 09:00-17:30\nViernes: 09:00-17:30','chualqui@cajbiobio.cl',26,2,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(60,'CAJ LAJA','OJA','O\'Higgins 137, Laja','(43) 2535167 / 971017315','Lunes: 09:00-13:00 y 14:00-16:00\nMartes: 09:00-13:00 y 14:00-16:00\nMiercoles: 09:00-13:00 y 14:00-16:00\nJueves: 09:00-13:00 y 14:00-16:00\nViernes: 09:00-13:00 y 14:00-16:00','claja@cajbiobio.cl',44,2,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(61,'CAJ LEBU','CJ','Juan Antonio Ríos N.º 30, Lebu','(41) 2512577 / 995879216','Lunes: 09:00-13:30 y 14:30-17:30\nMartes: 09:00-13:30 y 14:30-17:30\nMiercoles: 09:00-14:00 y 14:30-17:30\nJueves: 09:00-14:00 y 14:30-17:30\nViernes: 09:00-14:00 y 14:30-17:30','clebu@cajbiobio.cl',34,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(62,'CAJ LOS ALAMOS','CJ','Colón Nº 103','(44) 2963522 / 971018586','Lunes: 09:00-13:30 y 14:30-17:00\nMartes: 09:00-13:30 y 14:30-17:00\nMiercoles: 09:00-13:30 y 14:30-17:00\nJueves: 09:00-13:30 y 14:30-17:00\nViernes: 09:00- 13:30 y 14:30-17:00','closalamos@cajbiobio.cl',39,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(63,'CAJ LOTA','CJ','Pedro Aguirre Cerda Nº 170, Lota.','958904940','Lunes: 09:00-13:00 y 15:00-16:30\nMartes: 09:00-13:00 y 15:00-16:30\nMiercoles: 09:00-13:00 y 15:00-16:30\nJueves: 09:00-13:00 y 15:00-16:30\nViernes: 09:00-13:00 y 15:00-16:30','clota@cajbiobio.cl',27,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(64,'CAJ MULCHEN','OJA','Villagran Nº 335, Mulchén','971024795','Lunes: 09:00-13:30 y 14:30-17:00\nMartes: 09:00-13:30 y 14:30-17:00\nMiercoles: 09:00-13:30 y 14:30-17:00\nJueves: 09:00-13:30 y 14:30-17:00\nViernes: 09:00- 13:30 y 14:30-17:00','cmulchen@cajbiobio.cl',45,2,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(65,'CAJ NACIMIENTO','CJ','Freire Nº299','(43) 2533504 / 973861429','Lunes: 09:00-13:30 y 14:30-16:00\nMartes: 09:00-13:30 y 14:30-17:00\nMiercoles: 09:00-13:30\nJueves: 09:00-13:30\nViernes: 09:00- 13:30','cnacimiento@cajbiobio.cl',46,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(66,'CAJ NEGRETE','OJA','Ignacio Carrera Pinto S/N. Negrete.','(43) 2551739 / 969188287','Lunes: 13:30 a 17:30\nMartes: 13:30-17:30\nMiercoles: 13:30-17:30\nJueves: 13:30-17:30\nViernes: 09:30-13:30','cnegrete@cajbiobio.cl',47,2,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(67,'CAJ PENCO','CJ','Las Heras Nº 516, Penco','(41) 2452114','Lunes: 08:30-13:00 y 15:00-17:30\nMartes: 08:30-13:00 y 15:00-17:30\nMiercoles: 08:30-13:00 y 15:00-17:30\nJueves: 08:30-13:00 y 15:00-17:30\nViernes: 08:30-13:00 y 15:00-17:30','cpenco@cajbiobio.cl',28,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(68,'CAJ QUILACO','OJA','Córdova Nº 46 Municipalidad de Quilaco','(43) 2589142 / 971026504','Miercoles: 10:00-11:30','cquilaco@cajbiobio.cl',48,2,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(69,'CAJ QUILLECO','OJA','Biblioteca Municipal, Calle Caupolicán S/n, Quilleco','(43) 2633400 / 971022055','Lunes: 08:00 - 17:00','cquilleco@cajbiobio.cl',49,2,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(70,'CAJ SAN PEDRO DE LA PAZ','CJ','Los Aromos N°1480, San Pedro de la Paz','(44) 3067694','Lunes: 09:00-13:00 y 15:00-17:30\nMartes: 09:00-13:00 y 15:00-17:30\nMiercoles: 09:00-13:00 y 15:00-17:30\nJueves: 09:00-13:00 y 15:00-17:30\nViernes: 09:00-13:00 y 15:00-17:30','csanpedro@cajbiobio.cl',29,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(71,'CAJ SANTA BARBARA','OJA','Las Rosas Nº 160, Esquina Carreras, Santa Bárbara','998858636','Lunes A VIERNES: 09:00-13:00 LUNES A JUEVES  15:00-17:30\nLunes: 09:00-13:00 y 15:30-17:30\nMartes: 09:00-13:00 y 15:30-17:30\nMiercoles: 09:00-13:00 y 15:30-17:30\nJueves: 09:00-13:00 y 15:30-17:30\nViernes: 09:00- 13:00','csantabarbara@cajbiobio.cl',51,2,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(72,'CAJ SANTA JUANA','OJA','Yungay S/N Edificios Públicos Santa Juana','41-2779588 / 958890760','Lunes: 09:00-14:00 y 15:00-17:30\nMartes: 09:00-14:00 y 15:00-17:30\nMiercoles: 09:00-11:15\nJueves: 09:00-14:00 y 15:00-17:30','csantajuana@cajbiobio.cl',30,2,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(73,'CAJ TALCAHUANO','CJ','Anibal Pinto Nº 311. 2do Piso, Talcahuano','Actualmente con problemas de servicio, preferir llamar a teléfono celular / 981214582','Lunes: 09:00-13:30 y 15:00-17:30\nMartes: 09:00-13:30 y 15:00-17:30\nMiercoles: 09:00-13:30 y 15:00-17:30\nJueves: 09:00-13:30 y 15:00-17:30\nViernes: 09:00-13:30 y 15:00-17:30','ctalcahuano@cajbiobio.cl',31,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(74,'CAJ TALCAHUANO FAMILIA','CJ','Anibal Pinto Nº 311, 1er Piso. Talcahuano.','Actualmente con problemas de servicio, preferir llamar a teléfono celular / 981214556','Lunes: 09:00-13:30\nMartes: 09:00-13:30\nMiercoles: 09:00-13:30\nJueves: 09:00-13:30\nViernes: 09:00-13:30\nEn las tardes solo previa citación por profesional.','familiatalcahuano@cajbiobio.cl',31,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(75,'CAJ TOME','CJ','Nogueira Nº 1231 Tomé','979882554','Lunes: 09:00-13:00 y 15:00-17:30\nMartes: 09:00-13:00 y 15:00-17:30\nMiercoles: 09:00-13:00 y 15:00-17:30\nJueves: 09:00-13:00 y 15:00-17:30\nViernes: 09:00-13:00 y 15:00-17:30','ctome@cajbiobio.cl',32,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(76,'CAJ YUMBEL','CJ','O\'Higgins Nº 898, 2°Piso, Edificios Públicos. Yumbel','(44) 3027551 / 981214557','Lunes: 09:00-13:30 y 14:00-17:00\nMartes: 09:00-13:30 y 14:00-17:00\nMiercoles: 09:00-13:30 y 14:00-17:00\nJueves: 09:00-13:30 y 14:00-17:00\nViernes: 09:00-13:30','cyumbel@cajbiobio.cl',53,2,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(77,'CAVI CONCEPCION','CAVI','Ainavillo Nº 633, Concepción','(41) 2215670 / 971025171','Lunes: 09:00-13:00 y 14:00-17:30\nMartes: 09:00-13:00 y 14:00-17:30\nMiercoles: 09:00-13:00 y 14:00-17:30\nJueves: 09:00-13:00 y 14:00-17:30\nViernes: 09:00-13:00 y 14:00-17:30','caviprovinciaconcepcion@cajbiobio.cl',22,2,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(78,'CENTRO MEDIACION CONCEPCION','CMED','Chacabuco N° 550  Of 44 Concepción','969187267 / 225 24 55 69','Lunes: 09:00-13:00 y 15:00-17:00\nMartes: 09:00-13:00 y 15:00-17:00\nMiercoles: 09:00-13:00 y 15:00-17:00\nJueves: 09:00-13:00 y 15:00-17:00\nViernes: 09:00-13:00 y 15:00-17:00','mediacion@cajbiobio.cl',22,2,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(79,'NAD CONCEPCIÓN','NAD','Lincoyan 282, Piso 7','41-3440352','Lunes: 08:30-14:00 y 15:00-17:30\nMartes: 08:30-14:00 y 15:00-17:30\nMiercoles: 08:30-14:00 y 15:00-17:30\nJueves: 08:30-14:00 y 15:00-17:30\nViernes: 08:30-14:00 y 15:00-17:30','curadoresbiobio@cajbiobio.cl',22,2,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(80,'NAD CURANILAHUE','NAD','Arturo Prat 775, dpto. 101','44-3570502','Lunes: 08:30-14:00 y 15:00-17:30\nMartes: 08:30-14:00 y 15:00-17:30\nMiercoles: 08:30-14:00 y 15:00-17:30\nJueves: 08:30-14:00 y 15:00-17:30\nViernes: 08:30-14:00 y 15:00-17:30','curadoresbiobio@cajbiobio.cl',38,2,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(81,'NAD LOS ANGELES','NAD','LAUTARO N°385 OF. 321','44-3702454','Lunes: 08:30-14:00 y 15:00-17:30\nMartes: 08:30-14:00 y 15:00-17:30\nMiercoles: 08:30-14:00 y 15:00-17:30\nJueves: 08:30-14:00 y 15:00-17:30\nViernes: 08:30-14:00 y 15:00-17:30','curadoresbiobio@cajbiobio.cl',41,2,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(82,'ODL CONCEPCIÓN','ODL','San Martín 457 Concepción','979882549 / Actualmente no contamos con  telefonía fija.','Lunes: 09:00-13:00 y 14:30-17:00\nMartes: 09:00-13:00 y 14:30-17:00\nMiércoles: 09:00-13:00 y 14:30-17:00\nJueves: 09:00-13:00 y 14:30-17:00\nViernes: 09:00-13:00 y 14:30-17:00','odlconcepcion@cajbiobio.cl',22,2,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(83,'ODL LOS ANGELES','ODL','Los Carreras Nº 395 - 399 (esquina Lynch) Los Ángeles','Teléfono fijo (43) 2525441 / Celular: 971026373','Lunes: 09:00-13:30 y 14:30-16:00\nMartes: 09:00-13:30 y 14:30-16:00.\nMiercoles: 09:00-13:30 y 14:30-16:00\nJueves: 09:00-13:30 y 14:30-16:00.\nViernes: 09:00-13:30 y 14:30-16:00. Jornada de la mañana atención general. Jornada de la tarde solo atenciones con agenda previa.','odllosangeles@cajbiobio.cl',41,2,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(84,'OFICINA SEGUNDA INSTANCIA','2DA INSTANCIA','Lincoyán N°344, Concepción','(41) 2735955 / 971024418','Lunes: 09:00-13:30 y 15:00-17:30\nMartes: 09:00-13:30 y 15:00-17:30\nMiércoles: 09:00-13:30 y 15:00-17:30\nJueves: 09:00-13:30 y 15:00-17:30\nViernes: 09:00-13:30 y 15:00-17:30','samuel.osorio@cajbiobio.cl',22,2,4,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(85,'ODAM CONCEPCION','ODAM','Barros Arana #1098, Oficina 804, Concepción','963171258 (A. Social) / 963171652 (Abogada)','Lunes: 09:00-13:30\nMartes: 09:00-13:30\nMiercoles: 09:00-13:30\nJueves: 09:00-13:30\nViernes: 09:00-13:30','pamconcepcion@cajbiobio.cl',22,2,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(86,'PROGRAMA MI ABOGADO CONCEPCIÓN','PMA','Caupolicán N° 567, oficina 301-302, Edificio La Hechicera, Concepción.','412224894','Lunes: 08:30-13:30 y 14:30-17:30\nMartes: 08:30-13:30 y 14:30-17:30\nMiércoles: 08:30-13:30 y 14:30-16:30\nJueves: 08:30-13:30 y 14:30-16:30\nViernes: 08:30-13:30 y 14:30-16:30','miabogadobiobio@cajbiobio.cl',22,2,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(87,'PROGRAMA MI ABOGADO LOS ÁNGELES','PMA','Lautaro N° 385, Of. 304-309, Los Ángeles.','432351529','Lunes: 08:30-13:30 y 14:30-17:30\nMartes: 08:30-13:30 y 14:30-17:30\nMiércoles: 08:30-13:30 y 14:30-16:30\nJueves: 08:30-13:30 y 14:30-16:30\nViernes: 08:30-13:30 y 14:30-16:30','miabogadobiobio@cajbiobio.cl',41,2,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(88,'UNIDAD MÓVIL CONCEPCION','MOVIL','Lincoyán N°344, Concepción','(41) 2735955','Lunes: 09:00-13:30 y 15:00-17:30\nMartes: 09:00-13:30 y 15:00-17:30\nMiercoles: 09:00-13:30 y 15:00-17:30\nJueves: 09:00-13:30 y 15:00-17:30\nViernes: 09:00-13:30 y 15:00-17:30','movilconcepcion@cajbiobio.cl',22,2,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(89,'UNIDAD MÓVIL CONTULMO TIRÚA','MOVIL','ESMERALDA N° 367','+569 9885 8664','Lunes: 09:00-17:30\nMartes: 09:00-17:30\nMiercoles: 09:00-17:30\nJueves: 09:00-17:30\nViernes: 09:00-17:30','movilcontulmotirua@cajbiobio.cl',36,2,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(90,'CAVI TEMUCO','CAVI','P. Lynch 723 Temuco','225651588 / 971044765','Lunes: 09:00-13:30 y 14:00-17:00\nMartes: 09:00-13:30 y 14:00-17:00\nMiercoles: 09:00-13:30 y 14:00-17:00\nJueves: 09:00-13:30 y 14:00-17:00\nViernes: 09:00-13:30 y 14:00-17:00','caivtemuco@cajbiobio.cl',55,3,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(91,'CAJ ANGOL','CJ','Jarpa Nº 335 Angol','(45) 2715028','Lunes: 09:00-13:30 y 14:30-16:00\nMartes: 09:00-13:30 y 14:30-16:00\nMiercoles: 09:00-13:30 y 14:30-16:00\nJueves: 09:00-13:30 y 14:30-16:00\nViernes: 09:00-13:30 y 14:30-16:00','cangol@cajbiobio.cl',76,3,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(92,'CAJ CARAHUE','OJA','Las Heras Nº 182 Carahue','(45) 2651982','Lunes: 09:00-13:30 y 14:00-16:30\nMartes: 09:00-13:30 y 14:00-17:00\nMiercoles: 09:00-13:30\nJueves: 09:00-13:30\nViernes: 09:00-13:00','ccarahue@cajbiobio.cl',56,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(93,'CAJ CHOLCHOL','OJA','Vicuña Mackenna S/N, Municipalidad Cholchol','(45) 2734263','Martes: 09:00-13:00','ccholchol@cajbiobio.cl',75,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(94,'CAJ COLLIPULLI','OJA','O Carrol 707','(45) 2812592 /971042956','Lunes: 10:30-13:30 y 14:00-17:30\nMartes: 09:00-13:30 y 14:00-17:00\nMiercoles: 09:00-13:30\nJueves: 09:00-13:30 y 14:00-17:00\nViernes: 10:30-13:30','ccollipulli@cajbiobio.cl',77,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(95,'CAJ CUNCO','OJA','Pedro Aguirre Cerda Nº 600 Cunco','(45) 2573028 / 971045212','Lunes: 14:30-18:30\nMiercoles: 09:00-13:30 y 14:00-17:00\nViernes: 08:30-13:30 y 14:00-17:00','ccunco@cajbiobio.cl',57,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(96,'CAJ CURACAUTIN','OJA','Yungay Nº 265 Oficina Nº7 Edificios Públicos Curacautín','(45) 2881910 / 971045209','Lunes: 09:00-13:30 y 14:30-16:00\nMartes: 09:00-13:30 y 14:30-16:00\nMiercoles: 09:00-13:30 y 14:30-16:00\nJueves: 09:00-13:30 y 14:30-16:00\nViernes: 09:00-13:30 y 14:30-16:00','ccuracautin@cajbiobio.cl',78,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(97,'CAJ CURARREHUE','OJA','Av. Ohiggins nro. 638 Curarrehue.','971042961','Martes: 09:00-13:30 y 14:00-16:30','ccurarrehue@cajbiobio.cl',58,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(98,'CAJ ERCILLA','OJA','Ongolmo s/n, Ercilla','(45) 2812592 /971042956','Miercoles: 14:30-17:30\nViernes: 15:00-17:00','cercilla@cajbiobio.cl',79,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(99,'CAJ FREIRE','OJA','Camalez Nº 113 Municipalidad Freire','971044137','Lunes: 09:00-13:00\nMartes: 09:00-13:30 y 14:00-17:00\nJueves: 09:00-13:30 y 14:00-17:00','cfreire@cajbiobio.cl',59,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(100,'CAJ GALVARINO','OJA','Independencia Nº 90 Municipalidad Galvarino','(45) 2513003 / 971041297','Jueves: 09:30-14:00','cgalvarino@cajbiobio.cl',60,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(101,'CAJ GORBEA','OJA','Lord Cochrane Nº 402 Gorbea','(45) 2972091 / 971045267','Miercoles: 09:00-13:00','cgorbea@cajbiobio.cl',61,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(102,'CAJ LAUTARO','CJ','José Miguel Carrera Nº 680 Lautaro','(45) 2531535','Lunes: 09:00-13:30 y 14:00-17:00\nMartes: 09:00-13:30 y 14:00-17:00\nMiercoles: 09:00-13:30 y 14:00-17:00\nJueves: 09:00-13:00\nViernes: 09:00-14:00','clautaro@cajbiobio.cl',62,3,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(103,'CAJ LONCOCHE','CJ','Balmaceda 369 Loncoche','(45) 2472422','Lunes: 09:00-13:00 y 14:00-17:00\nMartes: 09:00-13:00 y 14:00-17:00\nMiercoles: 09:00-13:00 y 14:00-17:00\nJueves: 09:00-13:00 y 14:00-17:00\nViernes: 09:00-13:00','cloncoche@cajbiobio.cl',63,3,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(104,'CAJ LONQUIMAY','OJA','Ignacio Carrera Pinto 559','(45) 2656080 / 971045209','Viernes: 09:00-13:00','clonquimay@cajbiobio.cl',80,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(105,'CAJ LOS SAUCES','OJA','Ercilla Nº 275 Esquina Balmaceda Los Sauces','971042582','Lunes: 14:00-18:00\nMartes: 09:00-13:00\nMiercoles: 09:00-13:00\nJueves: 09:00-13:00\nViernes: 09:00-13:00','clossauces@cajbiobio.cl',81,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(106,'CAJ LUMACO','OJA','Prat Nº506 Municipalidad Lumaco','(45) 2861084','Jueves: 14:00-17:30','clumaco@cajbiobio.cl',82,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(107,'CAJ MELIPEUCO','OJA','Pedro Aguirre Cerda Nº 140 Oficina 208 Municipalidad Melipeuco','971043615','Martes: 09:00-13:00','cmelipeuco@cajbiobio.cl',64,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(108,'CAJ NUEVA IMPERIAL','CJ','Baquedano N° 580 Nueva Imperial','(45) 2611585 / 971043299','Lunes: 09:00-13:30 y 14:00-17:00\nMartes: 09:00-13:30 y 14:00-17:00\nMiercoles: 09:00-13:30 y 14:00-17:00\nJueves: 09:00-13:30 y 14:00-17:00\nViernes: 09:00-13:00','cnvaimperial@cajbiobio.cl',65,3,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(109,'CAJ PADRE LAS CASAS','OJA','Pasaje Manuel Ladino Curiqueo, N° 1350','9-71040685','Lunes: 09:00-13:30 y 14:00-16:00\nMiercoles: 09:00-13:30 y 14:00-16:00\nViernes: 09:00-12:30','cpadrelascasas@cajbiobio.cl',66,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(110,'CAJ PERQUENCO','OJA','21 de Mayo 448 Perquenco','971041297','Miercoles: 09:00-13:00','cperquenco@cajbiobio.cl',67,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(111,'CAJ PITRUFQUEN','CJ','Francisco Bilbao Nº 330, Interior Galeria Torres, Pitrufquén','(45) 2392413 / 971040097','Lunes: 09:00-13:00 y 15:00-17:30\nMartes: 09:00-13:00 y 15:00-17:30\nMiercoles: 09:00-13:00 y 15:00-17:30\nJueves: 09:00-13:00 y 15:00-17:30\nViernes: 09:00-12:00','cpitrufquen@cajbiobio.cl',68,3,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(112,'CAJ PUERTO SAAVEDRA','OJA','Calle Patricio Lynch esquina 21 de Mayo. Puerto Saavedra.','971044896','Lunes: 09:00-13:00\nMartes: 09:00-13:00\nMiercoles: 09:00-13:00\nJueves: 09:00-13:00','cptosaavedra@cajbiobio.cl',70,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(113,'CAJ PUCÓN','OJA','Avda. B. O\'Higgins 774, Oficina 1210, piso 2° Centro comercial Los Álamos Pucón','(45) 2210981 / 979899516','Lunes: 09:00-13:30 y 14:00-17:00\nMartes: 09:00-13:30 y 14:00-17:00\nMiercoles: 09:00-13:30 y 14:00-17:00\nJueves: 09:00-13:30 y 14:00-17:00\nViernes: 09:00-13:00','cpucon@cajbiobio.cl',69,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(114,'CAJ PUREN','OJA','Calle Quiroga 310 Purén','(45) 2793914 / 971044739','Lunes: 09:00-13:00\nMartes: 09:00-13:00\nMiercoles: 09:00-13:30 y 14:00-17:00\nJueves: 09:00-13:00','cpuren@cajbiobio.cl',83,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(115,'CAJ RENAICO','OJA','Calle Progreso S/N Municipalidad Renaico','(45) 2774014 / 971042956','Martes: 13:30-17:30\nJueves: 13:30-17:30','crenaico@cajbiobio.cl',84,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(116,'CAJ TEMUCO CIVIL','CJ','Claro Solar Nº 871, Subterraneo - Temuco','(45) 2213175','Lunes: 09:00-13:30 y 14:30-16:00\nMartes: 09:00-13:30 y 14:30-16:00\nMiercoles: 09:00-13:30 y 14:30-16:00\nJueves: 09:00-13:30 y 14:30-16:00\nViernes: 09:00-13:30','ctemuco@cajbiobio.cl',55,3,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(117,'CAJ TOLTÉN','OJA','Alonso Del Pozo Nº 575 Toltén','(45) 2924516 / 971045855','Lunes: 09:00-13:00\nMartes: 09:00-13:00\nMiercoles: 09:00-13:00\nJueves: 09:00-13:00\nViernes: 09:00-13:00','ctolten@cajbiobio.cl',72,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(118,'CAJ TRAIGUEN','OJA','Coronel Urrutia Nº 280 Traiguén','(45) 2861084','Lunes: 09:00-13:30 y 14:00-17:00\nMartes: 09:00-13:30 y 14:00-17:00\nMiercoles: 09:00-13:30 y 14:00-17:00\nJueves: 09:00-13:30\nViernes: 09:00-13:30 y 14:00-17:00','ctraiguen@cajbiobio.cl',85,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(119,'CAJ TEODORO SCHMIDT','OJA','Manuel Balmaceda N° 410 Teodoro Schmidt','971046166','Miercoles: 09:00-13:00','ctschmidt@cajbiobio.cl',71,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-05 11:07:00'),(120,'CAJ VICTORIA','OJA','Baquedano Nº 510 Victoria','(45) 2844314 / 971045117','Lunes: 09:00-13:30 y 14:00-17:00\nMartes: 09:00-13:30 y 14:00-17:00\nMiercoles: 09:00-13:00\nJueves: 09:00-13:30 y 14:00-17:00\nViernes: 09:00-13:00','cvictoria@cajbiobio.cl',86,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(121,'CAJ VILCUN','OJA','Calle Prat S/N°, Vilcún','971043615','Lunes: 09:00-13:00\nMartes: 09:00-13:00\nMiercoles: 09:00-13:00\nJueves: 09:00-13:00\nViernes: 09:00-13:00','cvilcun@cajbiobio.cl',73,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(122,'CAJ VILLARRICA','CJ','Pedro de Valdivia Nº 0320 Villarica','971043852','Lunes: 09:00-13:30 y 14:00-17:00\nMartes: 09:00-13:30 y 14:00-17:00\nMiercoles: 09:00-13:30 y 14:00-17:00\nJueves: 09:00-13:30 y 14:00-17:00\nViernes: 09:00-13:30 y 14:00-17:00','cvillarrica@cajbiobio.cl',74,3,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(123,'DIRECCIÓN REGIONAL ARAUCANÍA','DR/DG','Vicuña Mackenna Nº 952 Oficina 2, 2° piso Edificio Recabarren Temuco','452234443','Lunes: 09:00-13:30 y 14:30-16:00\nMartes: 09:00-13:30 y 14:30-16:00\nMiercoles: 09:00-13:30 y 14:30-16:00\nJueves: 09:00-13:30 y 14:30-16:00\nViernes: 09:00-13:30','dirnovena@cajbiobio.cl',55,3,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(124,'CAJ TEMUCO FAMILIA','CJ','San Martin Nº 856, Temuco','222270812','Lunes: 09:00-13:30 y 14:30-16:00\nMartes: 09:00-13:30 y 14:30-16:00\nMiercoles: 09:00-13:30 y 14:30-16:00\nJueves: 09:00-13:30 y 14:30-16:00\nViernes: 09:00-13:30','familiatemuco@cajbiobio.cl',55,3,5,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(125,'CENTRO MEDIACION TEMUCO','CMED','San Martin Nº 698 (Esquina V. Mackenna)- Temuco','(45) 2276873','Lunes: 09:00-13:30 y 14:30-16:00\nMartes: 09:00-13:30 y 14:30-16:00\nMiercoles: 09:00-13:30 y 14:30-16:00\nJueves: 09:00-13:30 y 14:30-16:00\nViernes: 09:00-13:30','mediaciontemuco@cajbiobio.cl',55,3,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(126,'PROGRAMA MI ABOGADO TEMUCO','PMA','O´Higgins 866 Temuco','(45) 2818830 / 953384991','Lunes: 09:00-13:30 y 14:30-17:00\nMartes: 09:00-13:30 y 14:30-17:00\nMiercoles: 09:00-13:30 y 14:30-17:00\nJueves: 09:00-13:30 y 14:30-17:00\nViernes: 09:00-13:30 y 14:30-17:00','miabogadolaaraucania@cajbiobio.cl',55,3,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(127,'UNIDAD MÓVIL CAUTÍN','MOVIL','Villarrica, Pucón, Curarrehue','(45) 2210981 / 971045707','Lunes: 09:00-13:30 y 14:00-17:30\nMiercoles: 09:00-13:30 y 14:00-17:30\nViernes: 09:00-13:30 y 14:00-17:30','movilcautin@cajbiobio.cl',69,3,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(128,'UNIDAD MÓVIL COSTA','MOVIL','Claro Solar Nº 871 Temuco','(45) 2213175 / 971063822','Lunes: 09:00-13:30 y 14:00-17:30\nMiercoles: 09:00-13:30 y 14:00-17:30\nViernes: 09:00-13:30 y 14:00-17:30','movilcosta@cajbiobio.cl',55,3,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(129,'UNIDAD MÓVIL MALLECO','MOVIL','Curacautín, Lonquimay, Victoria','(45) 2844314 / 971041182','Lunes: 09:00-13:30 y 14:00-17:30\nMiercoles: 09:00-13:30 y 14:00-17:30\nViernes: 09:00-13:30 y 14:00-17:30','movilmalleco@cajbiobio.cl',86,3,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(130,'NAD ANGOL','NAD','Calle Manuel Bunster Nº273','(44) 335897','Lunes: 08:30-13:00 y 14:30-17:30\nMartes: 08:30-13:00 y 14:30-17:30\nMiercoles: 08:30-13:00 y 14:30-17:30\nJueves: 08:30-13:00 y 14:30-17:30\nViernes: 08:30-13:00 y 14:30-17:30','nadaraucania@cajbiobio.cl',76,3,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(131,'NAD TEMUCO','NAD','Antonio Varas 979, Oficina 901, Temuco','443579765 / 443579766','Lunes: 08:30-13:00 y 14:30-17:00\nMartes: 08:30-13:00 y 14:30-17:00\nMiercoles: 08:30-13:00 y 14:30-17:00\nJueves: 08:30-13:00 y 14:30-17:00\nViernes: 08:30-13:00','nadaraucania@cajbiobio.cl',55,3,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(132,'NAD VILLARRICA','NAD','Manuel Antonio Matta N° 370, Villarrica','(45) 2529391','Lunes: 08:30-13:00 y 14:30-17:30\nMartes: 08:30-13:00 y 14:30-17:30\nMiercoles: 08:30-13:00 y 14:30-17:30\nJueves: 08:30-13:00 y 14:30-17:30\nViernes: 08:30-13:00 y 14:30-17:30','nadaraucania@cajbiobio.cl',74,3,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(133,'ODL ANGOL','ODL','Prat Nº501, Angol','(45) 2711768','Lunes: 09:00-13:30 y 14:30-16:30\nMartes: 09:00-13:30 y 14:30-16:30\nMiercoles: 09:00-13:30 y 14:30-16:30\nJueves: 09:00-13:30 y 14:30-16:30\nViernes: 09:00-13:30 y 14:30-16:30','odlangol@cajbiobio.cl',76,3,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(134,'ODL TEMUCO','ODL','San Martín Nº 698 (Esquina V. Mackenna)- Temuco','(45) 2230443 / 971045533','Lunes: 09:00-13:30\nMartes: 09:00-13:30\nMiercoles: 09:00-13:30\nJueves: 09:00-13:30\nViernes: 09:00-13:30','odltemuco@cajbiobio.cl',55,3,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(135,'ODAM ARAUCANÍA','ODAM','P. LYNCH 723 TEMUCO','(45) 2213175 / 963171950 / 963171379','Lunes: 09:00-13:30 y 14:00-17:00\nMartes: 09:00-13:30 y 14:00-17:00\nMiercoles: 09:00-13:30 y 14:00-17:00\nJueves: 09:00-13:30 y 14:00-17:00\nViernes: 09:00-13:30 y 14:00-17:00','pamaraucania@cajbiobio.cl',55,3,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(136,'OFICINA SEGUNDA INSTANCIA','2DA INSTANCIA','Vicuña Mackenna Nº 952 Oficina 2, 2° piso Edificio Recabarren Temuco','(45) 2234443 / 971046147','Lunes: 09:00-13:00\nMartes: 09:00-13:00\nMiercoles: 09:00-13:00\nJueves: 09:00-13:00\nViernes: 09:00-13:30 y 14:00-17:30','segundainstanciatemuco@cajbiobio.cl',55,3,4,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(137,'CAVI VALDIVIA','CAVI','Calle Simpson 1458, Valdivia','(63) 2246057 / 971067785','Lunes: 09:00-13:00 y 14:00-16:30\nMartes: 09:00-13:00 y 14:00-16:30\nMiercoles: 09:00-13:00 y 14:00-16:30\nJueves: 09:00-13:00 y 14:00-16:30\nViernes: 09:00-13:00 y 14:00-16:00','cavivaldivia@cajbiobio.cl',87,4,2,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(138,'CAJ FUTRONO','OJA','Balmaceda 320, Interior. Futrono','(63) 2482671 / 971068206','Lunes: 09:00-14:00 y 15:00-17:30\nMartes: 09:00-14:00 y 15:00-17:30\nMiercoles: 09:00-14:00 y 15:00-17:30\nJueves: 09:00-14:00 y 15:00-17:30\nViernes: 09:00-13:00','cfutrono@cajbiobio.cl',96,4,3,'#0b3d91',1,1,'2026-11-05 11:07:00','2026-11-08 17:44:58'),(139,'CAJ LAGO RANCO','OJA','Santiago Rosas 407, 1er piso, Lago Ranco','971067996','Lunes: 09:00-13:00\nMartes: 09:00-13:00\nMiercoles: 09:00-13:00\nJueves: 09:00-13:00\nViernes: 09:00-13:00','clagoranco@cajbiobio.cl',97,4,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(140,'CAJ LANCO','OJA','Yungay esquina Unión s/n. Lanco','63-2251070','Lunes: 09:00-13:00\nMartes: 09:00-13:00\nMiercoles: 09:00-13:00\nJueves: 09:00-13:00\nViernes: 09:00-13:00','clanco@cajbiobio.cl',89,4,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(141,'CAJ LA UNIÓN','OJA','Letelier S/N , Piso 1, Ed. Municipales La Unión','(64) 2472266 / 952117549','Lunes: 09:00-13:30 y 14:30-16:00\nMartes: 09:00-13:30 y 14:30-16:00\nMiercoles: 09:00-13:30 y 14:30-16:00\nJueves: 09:00-13:30 y 14:30-16:00\nViernes: 09:00-13:30 y 14:30-16:00','claunion@cajbiobio.cl',95,4,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(142,'CAJ LOS LAGOS','OJA','San Martín Nº 01. Los Lagos','(63) 2461919','Lunes: 09:00-13:00 y 14:00-16:30\nMartes: 09:00-13:00 y 14:00-16:30\nMiercoles: 09:00-13:00 y 14:00-16:30\nJueves: 09:00-13:00 y 14:00-16:30\nViernes: 09:00-13:00 y 14:00-16:30','closlagos@cajbiobio.cl',90,4,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(143,'CAJ MÁFIL','OJA','Libertador Bernardo O\'Higgins S/N Municipalidad Mafil','No tiene','Lunes: 14:00-18:00\nMartes: 09:00-13:00\nMiercoles: 09:00-13:00\nJueves: 09:00-13:00\nViernes: 09:00-13:00','cmafil@cajbiobio.cl',91,4,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(144,'CAJ PAILLACO','OJA','Calle 18 de Octubre S/N, sector estación, Paillaco.','(63) 2422894','Lunes: 09:00-13.30 y 14:00 a 16:30\nMartes: 09:00-13:30 y 14:00-16:30\nMiercoles: 09:00-13:30 y 14:00-16:30\nJueves: 09:00-13:30 y 14:00-16:30\nViernes: 09:00-13:30 y 14:00-15:30','cpaillaco@cajbiobio.cl',93,4,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(145,'CAJ CORRAL','OJA','Calle Miraflores S/n edificio Municipal 2','No tiene','Viernes: 09:00-13:00','ccorral@cajbiobio.cl',88,4,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(146,'CAJ PANGUIPULLI','OJA','Carlos Acharan Arce Nº 296, Panguipulli.','971067949','Lunes: 09:00-13:00 y 14:30-18:00\nMartes: 09:00-12:00\nMiercoles: 09:00-13:00 y 14:30-18:00\nJueves: 09:00-13:00 y 14:30-18:00\nViernes: 09:00-12:00','cpanguipulli@cajbiobio.cl',94,4,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(147,'CAJ RÍO BUENO','OJA','San Martín N° 1141, Río Bueno','(64) 2341016','Lunes: 09:00-13:30 y 14:30-16:00\nMartes: 09:00-13:30 y 14:30-16:00\nMiercoles: 09:00-13:30 y 14:30-16:00\nJueves: 09:00-13:30 y 14:30-16:00\nViernes: 09:00-13:30 y 14:30-16:00','criobueno@cajbiobio.cl',98,4,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(148,'CAJ SAN JOSÉ DE LA MARIQUINA','OJA','Calle Mariquina 56, Interior','(63)2451044-971071698','Lunes: 09:00-13:00 y 14:00-16:00\nMartes: 09:00-13:00 y 14:00-16:00\nMiercoles: 09:00-13:00 y 14:00-16:00\nJueves: 09:00-13:00 y 14:00-16:00\nViernes: 09:00-13:00 y 14:00-16:00','csjmariquina@cajbiobio.cl',92,4,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(149,'CAJ VALDIVIA','CJ','Camilo Henriquez Nº 670 Valdivia','(63) 2213341','Lunes: 09:00-13:30 y 14:30-16:30\nMartes: 09:00-13:30 y 14:30-16:30\nMiercoles: 09:00-13:30 y 14:30-16:30\nJueves: 09:00-13:30 y 14:30-16:30\nViernes: 09:00-13:30 y 14:30-16:30','cvaldivia@cajbiobio.cl',87,4,5,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(150,'DIRECCIÓN REGIONAL LOS RIOS','DR/DG','Arauco N°371-373, 2°Piso Interior, Valdivia','(63) 223 29 88','Lunes: 09:00-13:30 y 14:30-17:30\nMartes: 09:00-13:30 y 14:30-17:30\nMiercoles: 09:00-13:30 y 14:30-17:30\nJueves: 09:00-13:30 y 14:30-17:30\nViernes: 09:00-13:30 y 14:30-17:30','dirdecimacuarta@cajbiobio.cl',87,4,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(151,'PROGRAMA MI ABOGADO VALDIVIA','PMA','Pedro de Valdivia N° 403','+569 85575770','Lunes: 09:00-13:30 y 14:30-17:30\nMartes: 09:00-13:30 y 14:30-17:30\nMiercoles: 09:00-13:30 y 14:30-16:30\nJueves: 09:00-13:30 y 14:30-16:30\nViernes: 09:00-13:30 y 14:30-16:30','miabogadolosrios@cajbiobio.cl',87,4,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(152,'NAD VALDIVIA','NAD','Calle Arauco N° 934','(63) 2639030 / +569 71067162','Lunes: 08:30-13:30 y 14:30-17:30\nMartes: 08:30-13:30 y 14:30-17:30\nMiercoles: 08:30-13:30 y 14:30-17:30\nJueves: 08:30-13:30 y 14:30-17:30\nViernes: 08:30-13:30 y 14:30-17:30','nadlosrios@cajbiobio.cl',87,4,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(153,'ODL VALDIVIA','ODL','Caupolicán Nº 175 Valdivia','971067128','Lunes: 09:00-13:30 y 14:30-16:30\nMartes: 09:00-13:30 y 14:30-16:30\nMiercoles: 09:00-13:30 y 14:30-16:30\nJueves: 09:00-13:30 y 14:30-16:30\nViernes: 09:00-13:30','odlvaldivia@cajbiobio.cl',87,4,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(154,'ODAM LOS RÍOS','ODAM','Carampangue N°420, Valdivia.','63-2385310','Lunes: 14:30-16:30\nMiercoles: 14:30-16:30\nViernes: 14:30-16:30','pamlosrios@cajbiobio.cl',87,4,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(155,'OFICINA SEGUNDA INSTANCIA','2DA INSTANCIA','Arauco Nº 371 Piso 2 Valdivia','(63) 2232988','Lunes: 14:30-19:00\nMartes: 14:30-19:00\nMiercoles: 14:30-19:00\nJueves: 14:30-19:00\nViernes: 14:30-19:00','segundainstanciavaldivia@cajbiobio.cl',87,4,4,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(156,'CAVI PUERTO MONTT','CAVI','Av. Presidente Ibañez N° 600 - Edificio Sector Justicia, Primer Piso Sur','(65) 2435877 / (65) 2570501','Lunes a Viernes 08:30 a 13:30 y 14:30 a 17:00 hrs.','caivptomontt@cajbiobio.cl',99,5,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(157,'CAJ ANCUD','CJ','Aníbal Pinto Nº 313, Segundo Piso, Ancud','(65) 2622124 / 974424983','Lunes: 08:30-13:30 y 14:30-17:30\nMartes: 08:30-13:30 y 14:30-17:30\nMiercoles: 08:30-13:30 y 14:30-17:30\nJueves: 08:30-13:30 y 14:30-17:30\nViernes: 08:30-13:30 y 14:30-17:30','cancud@cajbiobio.cl',109,5,5,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(158,'CAJ CALBUCO','OJA','Ernesto Riquelme Nº 28 Calbuco','(65) 2461010','Lunes: 09:00-13:30 y 14:30-17:30\nMartes: 09:00-13:30 y 14:30-17:30\nMiercoles: 09:00-13:30 y 14:30-17:30\nJueves: 09:00-13:30 y 14:30-17:30\nViernes: 09:00-13:00','ccalbuco@cajbiobio.cl',100,5,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(159,'CAJ CASTRO','CJ','San Martin Nº 612, Tercer Piso, Castro','(65)2638788 / 971098734','Lunes: 09:00-13:00 y 14:30-16:30\nMartes: 09:00-13:00 y 14:30-16:30\nMiercoles: 09:00-13:00 y 14:30-16:30\nJueves: 09:00-13:00 y 14:30-16:30\nViernes: 09:00-13:00 y 14:30-16:30','ccastro@cajbiobio.cl',108,5,5,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(160,'CAJ FRESIA','OJA','Sin domicilio en espera de Convenio Municipal','S/N','-','cfresia@cajbiobio.cl',102,5,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(161,'CAJ CHAITEN','OJA','Independencia Esquina Diego Portales.','(65) 2731604','Lunes: 08:30-13:30 y 14:30-17:30\nMartes: 08:30-13:30 y 14:30-17:30\nMiercoles: 08:30-13:30 y 14:30-17:30\nJueves: 08:30-13:30 y 14:30-17:30\nViernes: 08:30-13:30 y 14:30-17:30','cchaiten@cajbiobio.cl',125,5,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(162,'CAJ FRUTILLAR','OJA','Carlos Richter Nº 200, Ed. Brisas de Los Lagos, piso 2, Of. 24, Frutillar','(65) 2422380 / 974424429','Lunes: 09:00-13:30\nMartes: 09:00-13:30\nMiercoles: 09:00-13:30\nJueves: 09:00-13:30\nViernes: 09:00-13:30','cfrutillar@cajbiobio.cl',103,5,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(163,'CAJ FUTALEUFU','OJA','Balmaceda N° 646','(65) 2721202 / 940057703','Lunes: 08:30-13:30 y 14:30-17:30\nMartes: 08:30-13:30 y 14:30-17:30\nMiercoles: 08:30-13:30 y 14:30-17:30\nJueves: 08:30-13:30 y 14:30-17:30\nViernes: 08:30-13:30 y 14:30-17:30','cfutaleufu@cajbiobio.cl',126,5,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(164,'CAJ LOS MUERMOS','OJA','Bernardo Ohiggins S/N','(65) 2211812 / 974424943','Lunes a Viernes 09:00 a 13:00 hrs','closmuermos@cajbiobio.cl',104,5,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(165,'CAJ MAULLIN','OJA','Almeyda 251, Maullín','(65) 2451840 / 940185372','Lunes a Jueves 09:00 a 13:00 y 14:30 a 16:30 Viernes 09:00 a 13:00 hrs.','cmaullin@cajbiobio.cl',106,5,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(166,'CAJ OSORNO','CJ','Av. Juan Mackenna N° 825, 5 piso, Oficina 502, Osorno','(64) 2202270 / (64) 2202152 / (64)','Lunes a Viernes 09:00 a 13:00 y 14:00 a 18:00 hrs.','cosorno@cajbiobio.cl',118,5,5,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(167,'CAJ PALENA','OJA','Luis Risopatron N° 695 interior','(65) 2731604 /  940057703','Viernes: 09:00-17:00','cpalena@cajbiobio.cl',128,5,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(168,'CAJ CIVIL PUERTO MONTT','CJ','Av. Presidente Ibañez N° 600 - Edificio Sector Justicia, Tercer Piso Centro','(65) 2250497 / (65) 2267675','Lunes: 09:00-13:00 y 15:00-17:30\nMartes: 09:00-13:00 y 15:00-17:30\nMiercoles: 09:00-13:00 y 15:00-17:30\nJueves: 09:00-13:00 y 15:00-17:30\nViernes: 09:00-13:00 y 15:00-17:30','cptomontt@cajbiobio.cl',99,5,5,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(169,'CAJ HUALAIHUE','OJA','Gabriela Mistral S/N, Hauilahue','(65) 2217436 / 956378208','Martes: 09:00-13:30 y 14:00-17:30\nMiercoles: 08:30-12:30','cptomontt@cajbiobio.cl',127,5,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(170,'CAJ PUERTO VARAS','CJ','Santa Rosa 560, Local 15 B Puerto Varas','(65)2234728','Lunes a Viernes 09:00 a 13:00 y 14:00 a 16:30 hrs','cpuertovaras@cajbiobio.cl',107,5,5,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(171,'CAJ PURRANQUE','OJA','Manuel Bulnes N° 210, Purranque','(64) 2350180 / 971093981','Martes y Jueves de 09:00 - 18:00','cpurranque@cajbiobio.cl',120,5,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(172,'CAJ QUELLON','CJ','Avenida La Paz 470, Quellón','(65) 2682190 / 974424990','Lunes a Viernes 09:00 a 13:30 y 15:00 a 17:00 hrs.','cquellon@cajbiobio.cl',115,5,5,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(173,'CAJ RIO NEGRO','OJA','Mac Iver Nº 143, lado escuela Andrew Jackson. Río Negro','(64) 2361707 / 971093981','Lunes, Miercoles y Viernes 09:00 a 13:00 y 14:00 a 18:00 hrs.','crionegro@cajbiobio.cl',122,5,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(174,'DIRECCIÓN REGIONAL LOS LAGOS','DG/DR','Av. Presidente Ibañez N°600, Ed. Sector Justicia, Piso 3 Sur, Pto. Montt','(65) 2266900 / (65) 2287804 / (65) 2439830','Lunes a Viernes 09:00 a 13:00 y 15:00 a 17:00','dirdecima@cajbiobio.cl',99,5,1,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(175,'CAJ FAMILIA PUERTO MONTT','CJ','Av. Presidente Ibañez N° 600 - Edificio Sector Justicia, Segundo Piso Norte','(65) 2255011 / (65) 2266910 / 971093827 / 971092724','Lunes: 09:00-12:00 y 14:40-17:00\nMartes: 09:00-12:00 y 14:40-17:00\nMiercoles: 09:00-12:00 y 14:40-17:00\nJueves: 09:00-12:00 y 14:40-17:00\nViernes: 09:00-12:00 y 14:40-17:00','familiapuertomontt@cajbiobio.cl',99,5,5,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(176,'CENTRO MEDIACION PUERTO MONTT','CMED','Av. Presidente Ibañez N° 600 - Edificio Sector Justicia, Primer Piso Norte','(65) 2435878','Lunes a Viernes 08:30 a 13:30 y 14:30 a 17:30 hrs.','mediacionpmontt@cajbiobio.cl',99,5,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(177,'PROGRAMA MI ABOGADO PUERTO MONTT','PMA','Av. Juan Soler Manfredini 41, torre costanera, piso 15 oficina 1501, Puerto Montt.','(65) 2202294 / (65) 2202295','Lunes: 08:30-13:30 y 14:30-17:30\nMartes: 08:30-13:30 y 14:30-17:30\nMiercoles: 08:30-13:30 y 14:30-17:30\nJueves: 08:30-13:30 y 14:30-17:30\nViernes: 08:30-13:30 y 14:30-16:30','miabogadoloslagos@cajbiobio.cl',99,5,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(178,'UNIDAD MÓVIL CASTRO','MOVIL','San Martin Nº 612, Tercer Piso, Castro ( atención en Chonchi)','(65)2634431 / 9 71200022','Miercoles: 08:30-13:30','movilcastro@cajbiobio.cl',108,5,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(179,'UNIDAD MÓVIL OSORNO','MOVIL','Av. Juan Mackenna N° 825, 5 piso, Oficina 502, Osorno ( atención en San Pablo)','(64) 2202152 (64) 2202270 / 974424988','Martes: 15:00-18:00','movosorno@cajbiobio.cl',118,5,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(180,'UNIDAD MÓVIL QUINCHAO','MOVIL','San Martin Nº 612, Tercer Piso, Castro ( atención en Dalcahue)','(65) 2634431 / (65) 2638788 / 971093908','Jueves: 08:30-13:30','robinson.manzanares@cajbiobio.cl',118,5,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(181,'NAD CASTRO','NAD','O´higgins Nº494 edificio Leoman, piso 3, OF 5','(65) 2226196','Lunes a Viernes 08:30 a 17:30 hrs','nadloslagos@cajbiobio.cl',108,5,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(182,'NAD OSORNO','NAD','Manuel Antonio Matta N° 549 of. 903,Edificio Germania, Osorno','(64) 2346290','Lunes: 08:30-17:30\nMartes: 08:30-17:30\nMiercoles: 08:30-17:30\nJueves: 08:30-17:30\nViernes: 08:30-17:30','nadloslagos@cajbiobio.cl',118,5,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(183,'NAD PALENA','NAD','Luis Risopatron N° 695 interior','(65) 2226523','Lunes: 08:30-13:30 y 14:30-17:30\nMartes: 08:30-13:30 y 14:30-17:30\nMiercoles: 08:30-13:30 y 14:30-17:30\nJueves: 08:30-13:30 y 14:30-17:30\nViernes: 08:30-13:30 y 14:30-17:30','nadloslagos@cajbiobio.cl',128,5,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(184,'NAD PUERTO MONTT','NAD','Rancagua esquina Urmeneta, Puerto Montt','(65) 2226044','Lunes: 08:30-13:30 y 15:00-17:30\nMartes: 08:30-13:30 y 15:00-17:30\nMiercoles: 08:30-13:30 y 15:00-17:30\nJueves: 08:30-13:30 y 15:00-17:30\nViernes: 08:30-13:30 y 15:00-17:30','nadloslagos@cajbiobio.cl',99,5,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(185,'ODL CASTRO','ODL','O*higgins 494, esq. Sotomayor, Castro','(65)2367909 / 971094466','Lunes a Viernes 8:30 a 13:30 hrs y 14:30 17:30 hrs.','odlcastro@cajbiobio.cl',108,5,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(186,'ODL OSORNO','ODL','Av. Juan Mackenna N° 825, 5 piso, Oficina 501. Osorno','(64)2540021 / (64)2540022 / 971094600','Lunes a Viernes 09:00 a 13:00 y 15:00 a 17:30 Hrs.','odlosorno@cajbiobio.cl',118,5,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(187,'ODL PUERTO MONTT','ODL','Av. Presidente Ibañez N° 600 - Edificio Sector Justicia, Tercer Piso Norte','(65) 2350036 / (65) 2350037 / 971094967','Lunes a Viernes 09:00 a 13:00 y DE 15:00 a 17:30 hrs.','odlpmontt@cajbiobio.cl',99,5,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-05 11:07:01'),(188,'ODAM LOS LAGOS','ODAM','Rancagua esquina Urmeneta, Puerto Montt','(65) 2202296 / 963171458 / 963171458','Lunes: 09:30-13:30\nMiercoles: 09:30-13:30\nViernes: 09:30-13:30','pamloslagos@cajbiobio.cl',99,5,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(189,'OFICINA SEGUNDA INSTANCIA','2DA INSTANCIA','Av. Presidente Ibáñez N° 600, piso 2, lado norte','(65) 2439826','Lunes: 08:30-13:30\nMartes: 08:30-13:30\nMiercoles: 08:30-13:30\nJueves: 08:30-13:30\nViernes: 08:30-13:30','segundainstanciapmontt@cajbiobio.cl',99,5,4,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(190,'CAVI COYHAIQUE','CAVI','Moraleda N°52','(67) 2214640 / 968980089','Lunes: 09:00-13:30 y 14:30-16:30\nMartes: 09:00-13:30 y 14:30-16:30\nMiercoles: 09:00-13:30 y 14:30-16:30\nJueves: 09:00-13:30 y 14:30-16:30\nViernes: 09:00-13:30 y 14:30-15:30','caivcoyhaique@cajbiobio.cl',129,6,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(191,'CAJ PUERTO AYSEN','CJ','Carrera N° 1413, Puerto Aysen','(67) 2336815 / 968980088','Lunes: 09:00-13:30 y 14:30-16:30\nMartes: 09:00-13:30 y 14:30-16:30\nMiercoles: 09:00-13:30 y 14:30-16:30\nJueves: 09:00-13:30 y 14:30-16:30\nViernes: 09:00-13:30 y 14:30-15:30','caysen@cajbiobio.cl',131,6,5,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(192,'CAJ CHILE CHICO','CJ','Bernardo O\'higgins esquina Balmaceda, Chile Chico','958914689','Lunes: 08:00-13:00\nMartes: 08:00-13:00\nMiercoles: 08:00-13:00\nJueves: 08:00-13:00\nViernes: 08:00-13:00','cchilechico@cajbiobio.cl',137,6,5,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(193,'CAJ COCHRANE','CJ','Esmeralda N° 199, Cochrane','(67) 2321256 / (9) 62054417','Lunes: 09:00-14:00\nMartes: 09:00-14:00\nMiercoles: 09:00-14:00\nJueves: 09:00-14:00\nViernes: 09:00-14:00','ccochrane@cajbiobio.cl',134,6,5,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(194,'CAJ COYHAIQUE','CJ','Arturo Prat Nº 202. Coyhaique.','(67) 2214649 / 968980090','Lunes: 09:00-13:30 y 14:30-16:30\nMartes: 09:00-13:30 y 14:30-16:30\nMiercoles: 09:00-13:30 y 14:30-16:30\nJueves: 09:00-13:30 y 14:30-16:30\nViernes: 09:00-13:30 y 14:30-16:30','ccoyhaique@cajbiobio.cl',129,6,5,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(195,'CAJ PUERTO CISNES','OJA','10 de julio N°157-B, Puerto Cisnes.','931892223','Lunes: 09:00-13:30 y 14:30-16:30\nMartes: 09:00-13:30 y 14:30-16:30\nMiercoles: 09:00-13:30 y 14:30-16:30\nJueves: 09:00-13:30 y 14:30-16:30\nViernes: 09:00-13:30 y 14:30-15:30','cpuertocisnes@cajbiobio.cl',132,6,3,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(196,'DIRECCIÓN REGIONAL AYSÉN','DG/DR','Prat N°202, Piso 2, Coyhaique','(67) 2573267','Lunes: 09:00-13:30 y 14:30-16:30\nMartes: 09:00-13:30 y 14:30-16:30\nMiercoles: 09:00-13:30 y 14:30-16:30\nJueves: 09:00-13:30 y 14:30-16:30\nViernes: 09:00-13:30 y 14:30-15:30','dirundecima@cajbiobio.cl',129,6,1,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(197,'NAD AYSÉN','NAD','Eusebio Ibar N°1345 - A','(67) 2 332504','Lunes: 09:00-13:30 y 14:30-16:30\nMartes: 09:00-13:30 y 14:30-16:30\nMiercoles: 09:00-13:30 y 14:30-16:30\nJueves: 09:00-13:30 y 14:30-16:30\nViernes: 09:00-13:30 y 14:30-15:30','curadoresaysen@cajbiobio.cl',131,6,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(198,'NAD COYHAIQUE','NAD','Moraleda Nº36','(67) 2-528328 / (67) 2-528329','Lunes: 09:00-13:30 y 14:30-16:30\nMartes: 09:00-13:30 y 14:30-16:30\nMiercoles: 09:00-13:30 y 14:30-16:30\nJueves: 09:00-13:30 y 14:30-16:30\nViernes: 09:00-13:30 y 14:30-15:30','curadoresaysen@cajbiobio.cl',129,6,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(199,'ODL AYSÉN','ODL','Carrera N° 1413, Puerto Aysen','(67) 2321275 / 920446385','Lunes: 09:00-13:30 y 14:30-16:30\nMartes: 09:00-13:30 y 14:30-16:30\nMiercoles: 09:00-13:30 y 14:30-16:30\nJueves: 09:00-13:30 y 14:30-16:30\nViernes: 09:00-13:30 y 14:30-15:30','odlaysen@cajbiobio.cl',131,6,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(200,'ODL COYHAIQUE','ODL','Prat Nº 202, Piso 2 Coyhaique.','(67) 2573266 / 950293530','Lunes: 09:00-13:30 y 14:30-16:30\nMartes: 09:00-13:30 y 14:30-16:30\nMiercoles: 09:00-13:30 y 14:30-16:30\nJueves: 09:00-13:30 y 14:30-16:30\nViernes: 09:00-13:30 y 14:30-15:30','odlcoyhaique@cajbiobio.cl',129,6,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(201,'PMA COYHAIQUE','PMA','Los Coigues Nº 324','939596105','Lunes: 09:00-13:30 y 14:00-17:00\nMartes: 09:00-13:30 y 14:00-17:00\nMiercoles: 09:00-13:30 y 14:00-16:00\nJueves: 09:00-13:30 y 14:00-16:00\nViernes: 09:00-13:30 y 14:00-16:00','miabogadoaysen@cajbiobio.cl',129,6,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58'),(202,'ODAM AYSÉN','ODAM','Los Coigues N° 324, Coyhaique','947958824 / 947958970','Lunes: 09:00-13:30 y 14:30-16:00\nMartes: 09:00-13:30 y 14:30-16:00\nMiercoles: 09:00-13:30 y 14:30-16:00\nJueves: 09:00-13:30 y 14:30-16:00\nViernes: 09:00-13:30 y 14:30-16:00','pamaysen@cajbiobio.cl',131,6,2,'#0b3d91',1,1,'2026-11-05 11:07:01','2026-11-08 17:44:58');


-- -----------------------------------------------------------------------------
-- Datos para `usuarios`: 5. Usuarios del sistema con credenciales de prueba universales (31 funcionarios y postulantes, clave: 123456)
-- -----------------------------------------------------------------------------
INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `email`, `rut`, `password`, `rol`, `region_id`, `activo`, `debe_cambiar_password`, `ultimo_acceso`, `intentos_fallidos`, `bloqueado_hasta`, `creado_por`, `firma_nombre_almacenado`, `firma_mime`, `firma_actualizada_at`, `remember_token`, `created_at`, `updated_at`) VALUES (1,'Mauricio','Decap Fernández','admin@cajbiobio.cl','10000001-6','$2y$12$I48f2SLQdklPEB29B04eweEXIWMvYIY4gjeqEJQPhgVzqcwwc33By','administrador',NULL,1,0,'2026-11-05 12:17:33',0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:01','2026-11-10 12:47:52'),(2,'Paulina','Herrera Núñez','secretaria1@cajbiobio.cl','10000002-4','$2y$12$DXoEP1zb824cmrw5EBkCQe/XWQfkbwCv43NLUoSdWU8ePPln57lIC','secretaria',2,1,0,'2026-11-08 18:20:01',0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:01','2026-11-10 12:47:52'),(3,'Soledad','Vergara Pino','secretaria2@cajbiobio.cl','10000003-2','$2y$12$SB2yRrr7UI5ll1AzYn.boelt7ksgrxdqm88Wyho0NOb0TGqX5Ti9O','secretaria',2,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:02','2026-11-10 12:47:52'),(4,'Lorena','Sanhueza Carrasco','juridica@cajbiobio.cl','10000010-5','$2y$12$bz00X02Anv4jnEvlSavpie/vEoh2upCw5pQ4M7socItz3rVZiIRi6','secretaria',2,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:02','2026-11-10 12:47:52'),(5,'Loreto','Morales Bastías','secretaria_nuble@cajbiobio.cl','10000011-3','$2y$12$K/PwKcYeAp2UOPSayLVDtuzkqcc6IRQJ3XM8s84ES.zRO7iCcZ1RK','secretaria',1,1,0,'2026-11-08 18:03:48',0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:02','2026-11-10 12:47:52'),(6,'Claudia','Baeza Morales','secretaria_araucania@cajbiobio.cl','10000012-1','$2y$12$HSCFCQn.3yUSWFrEitJvNeI6DXo7KbI6rYNdksRhucUX3xMCzTEdK','secretaria',3,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:02','2026-11-10 12:47:53'),(7,'Marcela','Valenzuela Ríos','secretaria_losrios@cajbiobio.cl','10000013-K','$2y$12$kUvu72x61ud1Pco.RLAQeubN2eRnly7.Qk6ajbjgLvxAxMWowYwj.','secretaria',4,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:03','2026-11-10 12:47:53'),(8,'Patricia','Cárdenas Soto','secretaria_loslagos@cajbiobio.cl','10000014-8','$2y$12$u1SF1mCptFh.iZSP7.B8/..mjoN4eRRtxnP7bNhcbQl0a60D.ixFS','secretaria',5,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:03','2026-11-10 12:47:53'),(9,'Pamela','Fuentes Almonacid','secretaria_aysen@cajbiobio.cl','10000015-6','$2y$12$vae0KzBjXmQdMq/oaUaJMeS3U.BnScqtD9XTTW.O4I4t4kOuxJ6R.','secretaria',6,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:03','2026-11-10 12:47:53'),(10,'Carlos','Muñoz Sepúlveda','abogado1@cajbiobio.cl','10000004-0','$2y$12$XJxzoE50Cw33Im7a4Edf3.4r4e9aym4oatSJsycyZXvy0A1DNBfU2','abogado',NULL,1,0,'2026-11-05 12:11:49',0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:03','2026-11-10 12:47:54'),(11,'Andrea','Rojas Fuentes','abogado2@cajbiobio.cl','10000005-9','$2y$12$.vjyWGUTEiavWLP.ShgoWOEzLpQ.EpLLubxI6VSHLQXJPIydK2DIu','abogado',NULL,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:04','2026-11-10 12:47:54'),(12,'Mauricio','Vergara Toledo','corte_biobio@cajbiobio.cl','10000016-4','$2y$12$y71hQpU67BzFow1pcwXLzO3lsp58uUnie9bzlnW.o.52HdgbPBWLW','abogado',2,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:04','2026-11-10 12:47:54'),(13,'Rodrigo','Díaz Wörner','gobernador@cajbiobio.cl','10000006-7','$2y$12$DHMAQgV3JzMHKjJ1SnCZ0.cac00HqosDCv6PhTXN5601ACr8kFlTC','gobernador',NULL,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:04','2026-11-10 12:47:54'),(14,'Mauricio','Decap Fernández','director_general@cajbiobio.cl','10000020-2','$2y$12$GRdhQDgI8N/d5E/8/8IwsO7S3bkiFQYbgouERMmxhruFIHe4tq3du','director_general',NULL,1,0,'2026-11-08 21:00:45',0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:04','2026-11-10 12:47:54'),(15,'Luis','Núñez Oviedo','ucoe_biobio@cajbiobio.cl','10000021-0','$2y$12$vWF.T9hFfmoISqw0RovqOOUEYgdg0W8yBO7oLAHvk2d/gmR87w/2q','jefe_ucoe',2,1,0,'2026-11-05 12:03:29',0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:04','2026-11-10 12:47:55'),(16,'Verónica','Cifuentes Matus','revisora_veronica@cajbiobio.cl','10000027-K','$2y$12$MCk0f6CXMh7cVcON6xwlSuTDhTyVNfFZQ8lZ85Z4vsJSfds1L4SjW','jefe_ucoe',2,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:05','2026-11-10 12:47:55'),(17,'Graciela','Paredes Saavedra','revisora_graciela@cajbiobio.cl','10000028-8','$2y$12$SSOIhS1yY.VhQnrJa9VVuu99p/wuus8vxUk70bOq4F3cPpkXcAXWe','jefe_ucoe',2,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:05','2026-11-10 12:47:55'),(18,'Ricardo','Salazar Valenzuela','director_nuble@cajbiobio.cl','10000022-9','$2y$12$aQOJz/eS2g9KBWc.DDZvVuXcQ6WWgICfO2WLo7GGu9q30zwH02QGi','director_regional',1,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:05','2026-11-10 12:47:55'),(19,'Alejandro','Viñuela Hiriart','director_araucania@cajbiobio.cl','10000023-7','$2y$12$ebqBJRqOxaXfyU9yjSJJxubRlP9PSy9v2r83t6c46QF5nLpkKyJtu','director_regional',3,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:05','2026-11-10 12:47:56'),(20,'María Cristina','Valdés Silva','director_losrios@cajbiobio.cl','10000024-5','$2y$12$EFxMO.tJJFBqV4et1pGweOJULXZkDlBdSI/oqbs1Qvw2PAonEJagO','director_regional',4,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:06','2026-11-10 12:47:56'),(21,'Consuelo','Bellido Almonacid','director_loslagos@cajbiobio.cl','10000025-3','$2y$12$LSsgRmakPnmJsRMowKwspO92ui27hdxbXLp5UuqbfSHHqhe4dpmre','director_regional',5,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:06','2026-11-10 12:47:56'),(22,'Rodrigo','Morales Henríquez','director_aysen@cajbiobio.cl','10000026-1','$2y$12$8QgKUgrqbroPVqStsxkWhuwQPGregKwqcV7RYlY.xkpNqvuBup3OG','director_regional',6,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:06','2026-11-10 12:47:56'),(23,'Verónica','Sanhueza (Jurídica)','juridica.central@cajbiobio.cl','10000037-7','$2y$12$rraSxeCGRh3Vc.7wBoI1N.BbBTgXsWKAl/zKuqoNNIZ.XUaTIEFMm','juridica_central',2,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:06','2026-11-10 12:47:57'),(24,'Camilo','Echeverría Solís','postulante@cajbiobio.cl','10000030-K','$2y$12$Fo3lik.h9I/m.RXvThGF6.59FDMKaHtadSDX30U5pBIyF.7HIfvO.','postulante',2,1,0,'2026-11-08 17:27:39',0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:07','2026-11-10 12:47:57'),(25,'Valentina','Morales Riquelme','practicante.activo@cajbiobio.cl','10000031-8','$2y$12$JS89ssngEyAxO4Ru4tlnp.3IyDzeXekVrhXG5NJEeXWwsBfRe/wIa','postulante',2,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:07','2026-11-10 12:47:59'),(26,'Matías','Salgado Vega','practicante.termino@cajbiobio.cl','10000032-6','$2y$12$Ks7Lfh3Bp/5mUtVYEkrnsuC9/46hh59yTI5Gu3RkiSHnRmsQEGrPm','postulante',2,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:07','2026-11-10 12:47:59'),(27,'Sofía','Henríquez Alarcón','postulante.corte@cajbiobio.cl','10000033-4','$2y$12$8kcnCbna0KVJQ3cdUl1lqOhceIjbjhmTYOk4ItQvNyqqkMgv1Ea/a','postulante',2,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:08','2026-11-10 12:47:59'),(28,'Gabriel','Navarro Sepúlveda','postulante.nuble@cajbiobio.cl','10000034-2','$2y$12$83tdrh1eRsk6nd/0Ve.m..3A/e3sJavQXNztTNv9cBRNWVchI4/tK','postulante',1,1,0,'2026-11-08 18:01:27',0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:08','2026-11-10 12:47:59'),(29,'Javiera','Cárdenas Pino','postulante.firmagob@cajbiobio.cl','10000035-0','$2y$12$zL6bw4k.OjMgbzdh6sGWF.BXRQw8AVPd91l97IPYgskUIBeSrkLWq','postulante',2,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:08','2026-11-10 12:48:00'),(30,'Rodrigo','Sepúlveda Castro','postulante.titulado@cajbiobio.cl','10000036-9','$2y$12$TOwRPEhyFbNRJxLX5VMK8uT99GvxwVZK3s/YpN1SscA1I4C8FwUZW','postulante',2,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:08','2026-11-10 12:48:00'),(31,'Cristóbal','Montoya Toledo','montoyacristobal@gmail.com','21922971-2','$2y$12$Miaw6oYve8TfDu33gN0p4OjCdxnMV/I5tU.f2afxPMTL5AgUvtpn.','postulante',NULL,1,0,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:38:17','2026-11-05 11:38:17');


-- -----------------------------------------------------------------------------
-- Datos para `abogados`: 6. Abogados Tutores y Jefes de 1ª y 2ª Instancia (Corte de Apelaciones)
-- -----------------------------------------------------------------------------
INSERT INTO `abogados` (`id`, `usuario_id`, `area`, `es_segunda_instancia`, `region_id`, `activo`) VALUES (1,10,NULL,0,NULL,1),(2,11,NULL,0,NULL,1),(3,12,NULL,1,2,1);


-- -----------------------------------------------------------------------------
-- Datos para `abogado_consultorio`: 7. Asignación oficial de Abogados Tutores a Consultorios
-- -----------------------------------------------------------------------------
INSERT INTO `abogado_consultorio` (`abogado_id`, `consultorio_id`) VALUES (1,1),(2,1),(1,2);


-- -----------------------------------------------------------------------------
-- Datos para `postulantes`: 8. Fichas de antecedentes personales y académicos de Postulantes de prueba
-- -----------------------------------------------------------------------------
INSERT INTO `postulantes` (`id`, `rut`, `nombre`, `apellido`, `fecha_nacimiento`, `email`, `telefono`, `genero`, `nacionalidad`, `discapacidad`, `discapacidad_descripcion`, `region_id`, `comuna_id`, `direccion`, `universidad`, `anio_carrera`, `especialidad_id`, `antecedentes_salud`, `emergencia_nombre`, `emergencia_vinculo`, `emergencia_telefono`, `emergencia_email`, `created_at`, `updated_at`, `datos_actualizados_at`) VALUES (1,'10000030-K','Camilo','Echeverría Solís','1998-03-12','postulante@cajbiobio.cl','+56987654321','masculino','Chilena',0,NULL,2,22,'Calle Los Aromos 123','Universidad de Concepción','egresado',2,'ujghf','Contacto de Emergencia','Familiar','+56911223344',NULL,'2026-11-05 11:07:07','2026-11-08 17:06:05','2026-11-08 17:06:05'),(2,'10000031-8','Valentina','Morales Riquelme','1997-11-20','practicante.activo@cajbiobio.cl','+56976543210','femenino','Chilena',0,NULL,2,22,'Calle Los Aromos 123','Universidad del Bío-Bío','egresado',NULL,NULL,'Contacto de Emergencia','Familiar','+56911223344',NULL,'2026-11-05 11:07:07','2026-11-05 11:07:07',NULL),(3,'10000032-6','Matías','Salgado Vega','1998-07-04','practicante.termino@cajbiobio.cl','+56965432109','masculino','Chilena',0,NULL,2,22,'Calle Los Aromos 123','Universidad Católica de la Santísima Concepción','egresado',NULL,NULL,'Contacto de Emergencia','Familiar','+56911223344',NULL,'2026-11-05 11:07:07','2026-11-05 11:07:07',NULL),(4,'10000033-4','Sofía','Henríquez Alarcón','1999-01-18','postulante.corte@cajbiobio.cl','+56954321098','femenino','Chilena',0,NULL,2,22,'Calle Los Aromos 123','Universidad de Concepción','egresado',NULL,NULL,'Contacto de Emergencia','Familiar','+56911223344',NULL,'2026-11-05 11:07:08','2026-11-05 11:07:08',NULL),(5,'10000034-2','Gabriel','Navarro Sepúlveda','1997-09-09','postulante.nuble@cajbiobio.cl','+56943210987','masculino','Chilena',0,NULL,1,1,'Calle Los Aromos 123','Universidad de Concepción (Campus Chillán)','egresado',NULL,NULL,'Contacto de Emergencia','Familiar','+56911223344',NULL,'2026-11-05 11:07:08','2026-11-05 11:07:08',NULL),(6,'10000035-0','Javiera','Cárdenas Pino','1998-12-03','postulante.firmagob@cajbiobio.cl','+56932109876','femenino','Chilena',0,NULL,2,22,'Calle Los Aromos 123','Universidad del Desarrollo','egresado',NULL,NULL,'Contacto de Emergencia','Familiar','+56911223344',NULL,'2026-11-05 11:07:08','2026-11-05 11:07:08',NULL),(7,'10000036-9','Rodrigo','Sepúlveda Castro','1996-04-25','postulante.titulado@cajbiobio.cl','+56921098765','masculino','Chilena',0,NULL,2,22,'Calle Los Aromos 123','Universidad de Concepción','egresado',NULL,NULL,'Contacto de Emergencia','Familiar','+56911223344',NULL,'2026-11-05 11:07:08','2026-11-05 11:07:08',NULL),(8,'21922971-2','Cristóbal','Montoya Toledo','2005-09-04','montoyacristobal@gmail.com','+56991522997','masculino','Chile',0,NULL,2,24,'lautaro 1983 15','Universidad del Desarrollo','egresado',3,'Ninguno','Alejandra','madre','+569565656',NULL,'2026-11-05 11:38:17','2026-11-05 11:38:17',NULL);


-- -----------------------------------------------------------------------------
-- Datos para `postulaciones`: 9. Postulaciones a práctica profesional en sus diversas etapas
-- -----------------------------------------------------------------------------
INSERT INTO `postulaciones` (`id`, `postulante_id`, `estado`, `origen`, `region_id`, `fecha_postulacion`, `fecha_inicio_solicitada`, `revisada_por`, `resuelta_at`, `motivo_rechazo`, `consultorio_asignado_id`, `created_at`, `updated_at`) VALUES (1,1,'pendiente','formulario',2,'2026-11-07 12:47:57','2026-11-25',NULL,NULL,NULL,NULL,'2026-11-05 11:07:07','2026-11-10 12:47:57'),(2,2,'aceptada','formulario',2,'2026-06-10 12:47:59','2026-07-10',NULL,'2026-07-10 12:47:59',NULL,1,'2026-11-05 11:07:07','2026-11-10 12:47:59'),(3,3,'aceptada','formulario',2,'2026-04-10 12:47:59',NULL,NULL,NULL,NULL,1,'2026-11-05 11:07:07','2026-11-10 12:47:59'),(4,4,'aceptada','formulario',2,'2026-03-10 12:47:59','2026-04-10',NULL,NULL,NULL,2,'2026-11-05 11:07:08','2026-11-10 12:47:59'),(5,5,'aceptada','formulario',1,'2026-03-10 12:47:59','2026-04-10',NULL,NULL,NULL,5,'2026-11-05 11:07:08','2026-11-10 12:47:59'),(6,6,'aceptada','formulario',2,'2026-03-10 12:48:00','2026-04-10',NULL,NULL,NULL,2,'2026-11-05 11:07:08','2026-11-10 12:48:00'),(7,7,'aceptada','formulario',2,'2026-03-10 12:48:00','2026-04-10',NULL,NULL,NULL,2,'2026-11-05 11:07:08','2026-11-10 12:48:00'),(8,8,'aceptada','formulario',2,'2026-11-05 11:38:17','2026-11-09',2,'2026-11-05 11:46:44',NULL,49,'2026-11-05 11:38:17','2026-11-05 11:46:44');


-- -----------------------------------------------------------------------------
-- Datos para `postulacion_preferencias`: 10. Sedes y consultorios preferidos seleccionados por los postulantes
-- -----------------------------------------------------------------------------
INSERT INTO `postulacion_preferencias` (`id`, `postulacion_id`, `consultorio_id`, `orden`) VALUES (1,1,1,1),(2,1,2,2),(3,8,49,1),(4,8,51,2);


-- -----------------------------------------------------------------------------
-- Datos para `postulacion_notas`: 11. Observaciones y notas de seguimiento interno de postulaciones
-- -----------------------------------------------------------------------------
INSERT INTO `postulacion_notas` (`id`, `postulacion_id`, `autor_usuario_id`, `texto`, `created_at`) VALUES (1,8,2,'jnfjdafdsfasdf','2026-11-05 11:46:44');


-- -----------------------------------------------------------------------------
-- Datos para `practicantes`: 12. Expedientes de practicantes asignados y activos
-- -----------------------------------------------------------------------------
INSERT INTO `practicantes` (`id`, `postulante_id`, `usuario_id`) VALUES (1,2,25),(2,3,26),(3,4,27),(4,5,28),(5,6,29),(6,7,30),(7,8,31);


-- -----------------------------------------------------------------------------
-- Datos para `practicas`: 13. Prácticas profesionales con los 7 escenarios de demostración completos
-- -----------------------------------------------------------------------------
INSERT INTO `practicas` (`id`, `postulacion_id`, `practicante_id`, `consultorio_id`, `abogado_id`, `abogado_segunda_instancia_id`, `carta_presentacion_firmada`, `folio_carta_presentacion`, `carta_presentacion_subida_at`, `fecha_inicio`, `fecha_termino`, `estado`, `folio_caj`, `nota_practica`, `resolucion_numero`, `resolucion_anio`, `oficio_daj_numero`, `oficio_daj_fecha`, `finalizada_at`, `validada_director_at`, `validada_director_por`, `observaciones_director`, `revisor_colegiado_id`, `revisor_colegiado_nombre`, `remitida_at`, `remitida_por`, `nivelada_at`, `nivelada_por`, `justificacion_nivelacion`, `causal_rechazo_sancion`, `cancelacion_motivo`, `cancelacion_detalle`, `cancelacion_fecha`, `cancelacion_antes_de_iniciar`, `cancelada_por`, `documentos_autorizados`, `documentos_autorizados_por`, `documentos_autorizados_at`, `despachada_at`, `firmado_director_general_at`, `firmado_director_general_por`, `firmagob_folio`, `firmagob_url`, `certificado_firmagob_id`, `despachada_por`, `created_at`, `updated_at`) VALUES (1,2,1,1,1,NULL,'fefa1d21-e456-4757-ad63-c221b873a071',NULL,'2026-07-10 12:47:59','2026-07-10','2027-01-10','activa',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:07','2026-11-10 12:47:59'),(2,3,2,1,1,NULL,NULL,NULL,NULL,'2026-05-15','2026-11-15','activa',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:07','2026-11-10 12:47:59'),(3,4,3,2,1,3,NULL,NULL,NULL,'2026-04-10','2026-10-31','finalizada',NULL,5.2,NULL,NULL,NULL,NULL,NULL,'2026-11-05 12:06:51',15,'esta persona si pasa',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'2026-11-05 12:10:16',14,'12345','http://localhost:8000/director-general/expedientes/3/certificado',17,NULL,'2026-11-05 11:07:08','2026-11-10 12:47:59'),(4,5,4,5,1,NULL,NULL,NULL,NULL,'2026-04-10','2026-10-21','remitida_biobio',NULL,4.6,NULL,NULL,NULL,NULL,NULL,'2026-11-05 12:47:59',NULL,NULL,NULL,NULL,'2026-11-08 12:47:59',2,'2026-11-05 12:00:00',2,'Calificación fijada en «Buena»: fasdfddddddd',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:08','2026-11-10 12:47:59'),(5,6,5,1,1,NULL,NULL,NULL,NULL,'2026-04-10','2026-10-26','nivelada_biobio',NULL,5.5,NULL,NULL,NULL,NULL,NULL,'2026-11-06 12:48:00',NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-08 12:48:00',2,'Calificación institucional fijada en «Muy Buena» conforme al rendimiento destacado.',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:07:08','2026-11-10 12:48:00'),(6,7,6,1,1,NULL,NULL,NULL,NULL,'2026-03-10','2026-09-10','cerrada_historial',NULL,6.2,NULL,NULL,NULL,NULL,NULL,'2026-10-10 12:48:00',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-16 12:48:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'2026-11-05 12:48:00','2026-11-05 12:48:00','2026-10-31 12:48:00',14,'FG-2026-BIOBIO-0042','https://validador.firmagob.gob.cl/validar?folio=FG-2026-BIOBIO-0042',19,2,'2026-11-05 11:07:08','2026-11-10 12:48:00'),(7,8,7,49,NULL,NULL,NULL,NULL,NULL,'2026-11-09','2027-05-09','por_iniciar',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-05 11:46:44','2026-11-05 11:46:44');


-- -----------------------------------------------------------------------------
-- Datos para `evaluaciones`: 14. Evaluaciones de desempeño de tutores (Rúbrica oficial de 7 criterios)
-- -----------------------------------------------------------------------------
INSERT INTO `evaluaciones` (`id`, `practica_id`, `abogado_id`, `conocimiento_juridico`, `responsabilidad`, `iniciativa`, `sentido_social`, `conducta`, `honorabilidad`, `asistencia_puntualidad`, `promedio`, `aprobado`, `observaciones`, `created_at`) VALUES (1,3,1,5.2,5.2,5.2,5.2,5.2,5.2,5.2,5.20,1,'Desempeño adecuado en consultorio. Requiere evaluación complementaria de alegatos en Corte.','2026-11-05 11:07:08'),(2,4,1,4.6,4.6,4.6,4.6,4.6,4.6,4.6,4.60,0,'Desempeño regular sin faltas graves.','2026-11-05 11:07:08'),(3,5,1,5.5,5.5,5.5,5.5,5.5,5.5,5.5,5.50,1,NULL,'2026-11-05 11:07:08'),(4,6,1,6.2,6.2,6.2,6.2,6.2,6.2,6.2,6.20,1,NULL,'2026-11-05 11:07:08');


-- -----------------------------------------------------------------------------
-- Datos para `calificaciones`: 15. Calificaciones de 1ª y 2ª instancia y nivelaciones de oficio
-- -----------------------------------------------------------------------------
INSERT INTO `calificaciones` (`id`, `practica_id`, `instancia`, `nota`, `fecha`, `registrada_por`, `observaciones`, `created_at`) VALUES (1,3,1,5.2,'2026-10-31',10,'Primera instancia en Consultorio Talcahuano.','2026-11-05 11:07:08'),(2,4,1,4.6,'2026-10-21',10,'Evaluación de 1ª instancia en Consultorio Chillán.','2026-11-05 11:07:08'),(3,5,3,5.5,'2026-11-08',2,'Fijación formal por Secretaría Jurídica Central.','2026-11-05 11:07:08'),(4,4,3,5.0,'2026-11-05',2,'Modificación institucional de calificación a «Buena» por Secretaría Jurídica Central: fasdfddddddd','2026-11-05 12:00:00');


-- -----------------------------------------------------------------------------
-- Datos para `documentos`: 16. Metadatos de expedientes y documentos cargados en el sistema
-- -----------------------------------------------------------------------------
INSERT INTO `documentos` (`id`, `tipo`, `postulacion_id`, `practica_id`, `nombre_original`, `nombre_almacenado`, `mime`, `tamano_bytes`, `sha256`, `subido_por`, `created_at`) VALUES (1,'cedula_frontal',1,NULL,'cedula_frontal.pdf','4795767f-29a8-4aea-90c0-dae78d746727','application/pdf',328,'62e730b1b469c20af1817001b3fd6bd5ccdac0079245b32ca039218f9d2b70e4',24,'2026-11-05 11:07:07'),(2,'cedula_reverso',1,NULL,'cedula_reverso.pdf','89113c62-5a95-44c7-8012-0f6419f784ea','application/pdf',328,'62e730b1b469c20af1817001b3fd6bd5ccdac0079245b32ca039218f9d2b70e4',24,'2026-11-05 11:07:07'),(3,'certificado_egreso',1,NULL,'certificado_egreso.pdf','c2394130-35d6-4f07-acc9-de9d4874ece1','application/pdf',328,'62e730b1b469c20af1817001b3fd6bd5ccdac0079245b32ca039218f9d2b70e4',24,'2026-11-05 11:07:07'),(4,'certificado_antecedentes',1,NULL,'certificado_antecedentes.pdf','78b158e2-51cf-41e7-a21d-4d5201a13a50','application/pdf',328,'62e730b1b469c20af1817001b3fd6bd5ccdac0079245b32ca039218f9d2b70e4',24,'2026-11-05 11:07:07'),(5,'carta_presentacion_firmada',2,1,'carta_presentacion_firmada.pdf','303ee1ee-731b-4893-88c3-a2d4899f05b6','application/pdf',328,'62e730b1b469c20af1817001b3fd6bd5ccdac0079245b32ca039218f9d2b70e4',10,'2026-11-05 11:07:07'),(6,'informe_final',3,2,'Informe_Final_Practica_Matias_Salgado.pdf','1c02e6d5-7f64-4e07-a6f6-3b5f357d90f0','application/pdf',328,'62e730b1b469c20af1817001b3fd6bd5ccdac0079245b32ca039218f9d2b70e4',26,'2026-11-05 11:07:07'),(7,'informe_final',4,3,'Informe_Sofia_Henriquez.pdf','38179c9b-e39a-4bbd-abb8-a858e4d1fdf1','application/pdf',328,'62e730b1b469c20af1817001b3fd6bd5ccdac0079245b32ca039218f9d2b70e4',27,'2026-11-05 11:07:08'),(8,'informe_final',5,4,'Informe_Gabriel_Navarro.pdf','be3546d1-1af2-4416-a67e-a010b4b2bb71','application/pdf',328,'62e730b1b469c20af1817001b3fd6bd5ccdac0079245b32ca039218f9d2b70e4',28,'2026-11-05 11:07:08'),(9,'informe_final',6,5,'Informe_Javiera_Cardenas.pdf','eb2650eb-c1e7-4ee0-820b-d59bf536aa53','application/pdf',328,'62e730b1b469c20af1817001b3fd6bd5ccdac0079245b32ca039218f9d2b70e4',29,'2026-11-05 11:07:08'),(10,'certificado_firmagob',7,6,'Certificado_Corte_Suprema_Firmado.pdf','b752beb5-bc1f-4685-8b9b-1dafef77ee87','application/pdf',328,'62e730b1b469c20af1817001b3fd6bd5ccdac0079245b32ca039218f9d2b70e4',14,'2026-11-05 11:07:08'),(11,'informe_final',7,6,'Informe_Rodrigo_Sepulveda.pdf','02205e43-e92f-4423-8636-e9e24e7eb255','application/pdf',328,'62e730b1b469c20af1817001b3fd6bd5ccdac0079245b32ca039218f9d2b70e4',30,'2026-11-05 11:07:08'),(12,'carta_presentacion_firmada',2,1,'carta_presentacion_firmada.pdf','5864e440-5487-44db-b7be-ff0b9f5af5e0','application/pdf',328,'62e730b1b469c20af1817001b3fd6bd5ccdac0079245b32ca039218f9d2b70e4',10,'2026-11-05 11:07:31'),(13,'certificado_firmagob',7,6,'Certificado_Corte_Suprema_Firmado.pdf','8a87588d-07ab-4b51-a121-14ff3cdff359','application/pdf',328,'62e730b1b469c20af1817001b3fd6bd5ccdac0079245b32ca039218f9d2b70e4',14,'2026-11-05 11:07:32'),(14,'cedula_frontal',8,NULL,'avance2_v1.1.pdf','997fc8f0-b1b4-4e0a-94f2-0862c75d3e38','application/pdf',1281355,'e99f64ba9abee50baa25a05575a7c72f978221e7f9be81c7247cf59dcf363f36',NULL,'2026-11-05 11:38:17'),(15,'cedula_reverso',8,NULL,'avance2_v1.1.pdf','ee9f88c4-dbf4-4a70-97c8-0feec1e8eab4','application/pdf',1281355,'e99f64ba9abee50baa25a05575a7c72f978221e7f9be81c7247cf59dcf363f36',NULL,'2026-11-05 11:38:17'),(16,'certificado_egreso',8,NULL,'Presentación Sistema de Gestión de Postulantes CAJ Biobío.pptx.pdf','eedb5042-06bd-4bec-8dc1-8f1f4dfdebb8','application/pdf',1568021,'9a54630b39bc5f98bf41a024b12ce30e70f02f294913e18354406cd707279f16',NULL,'2026-11-05 11:38:17'),(17,'certificado_corte_suprema',NULL,3,'carta-presentacion-practica-7.pdf','dcc5bf37-439f-42f3-92e1-1ab7b636cd07','application/pdf',111450,'76d31a996006b9650eb579d9f073d52d11be35154fabd86fba65584b2fe5cb87',14,'2026-11-05 12:10:16'),(18,'carta_presentacion_firmada',2,1,'carta_presentacion_firmada.pdf','fefa1d21-e456-4757-ad63-c221b873a071','application/pdf',328,'62e730b1b469c20af1817001b3fd6bd5ccdac0079245b32ca039218f9d2b70e4',10,'2026-11-10 12:47:59'),(19,'certificado_firmagob',7,6,'Certificado_Corte_Suprema_Firmado.pdf','22376775-73b9-4eb4-aa25-5f111b4a0c59','application/pdf',328,'62e730b1b469c20af1817001b3fd6bd5ccdac0079245b32ca039218f9d2b70e4',14,'2026-11-10 12:48:00');


-- -----------------------------------------------------------------------------
-- Datos para `certificados`: 17. Certificados oficiales emitidos para la Excma. Corte Suprema y Resoluciones
-- -----------------------------------------------------------------------------
INSERT INTO `certificados` (`id`, `practica_id`, `tipo`, `numero_resolucion`, `anio`, `fecha_emision`, `documento_id`, `emitido_por`, `created_at`) VALUES (1,6,'certificado_corte_suprema',101,2026,'2026-10-31',10,14,'2026-11-05 11:07:08'),(2,6,'resolucion',101,2026,'2026-10-31',NULL,14,'2026-11-05 11:07:08'),(3,3,'certificado_corte_suprema',NULL,NULL,'2026-11-05',17,14,'2026-11-05 12:07:33'),(4,3,'resolucion',596,2026,'2026-11-05',NULL,14,'2026-11-05 12:07:37'),(7,4,'certificado_corte_suprema',NULL,NULL,'2026-11-08',NULL,14,'2026-11-08 21:01:35'),(8,5,'certificado_corte_suprema',NULL,NULL,'2026-11-08',NULL,14,'2026-11-08 21:01:36');


-- -----------------------------------------------------------------------------
-- Datos para `resolucion_secuencia`: 18. Correlativo numérico institucional de resoluciones
-- -----------------------------------------------------------------------------
INSERT INTO `resolucion_secuencia` (`anio`, `ultimo_numero`) VALUES (2026,1);


-- -----------------------------------------------------------------------------
-- Datos para `notificaciones`: 19. Notificaciones y alertas operativas para los usuarios
-- -----------------------------------------------------------------------------
INSERT INTO `notificaciones` (`id`, `usuario_destino_id`, `tipo`, `titulo`, `mensaje`, `entidad_tipo`, `entidad_id`, `creada_por`, `leida_at`, `created_at`) VALUES (1,2,'aviso','Nueva postulación','Cristóbal Montoya Toledo envió una postulación.','postulacion',8,31,'2026-11-08 18:33:57','2026-11-05 11:38:17'),(2,3,'aviso','Nueva postulación','Cristóbal Montoya Toledo envió una postulación.','postulacion',8,31,NULL,'2026-11-05 11:38:17'),(3,4,'aviso','Nueva postulación','Cristóbal Montoya Toledo envió una postulación.','postulacion',8,31,NULL,'2026-11-05 11:38:17'),(4,31,'aviso','Postulación aceptada','Su práctica fue asignada a CAJ CHIGUAYANTE desde el 09-11-2026.','practica',7,2,NULL,'2026-11-05 11:46:44'),(5,28,'aviso','Calificación fijada a Buena','La Secretaría Jurídica Central de Biobío ha determinado formalmente su calificación final en concepto «Buena» conforme a la normativa institucional.','practica',4,2,NULL,'2026-11-05 12:00:00'),(6,2,'aviso','Calificación modificada por Dirección Regional','La calificación de Sofía Henríquez Alarcón fue revisada y modificada a «Sobresaliente» por la Dirección Regional. Expediente habilitado para remisión a Concepción.','practica',3,15,'2026-11-08 18:33:57','2026-11-05 12:06:51'),(7,3,'aviso','Calificación modificada por Dirección Regional','La calificación de Sofía Henríquez Alarcón fue revisada y modificada a «Sobresaliente» por la Dirección Regional. Expediente habilitado para remisión a Concepción.','practica',3,15,NULL,'2026-11-05 12:06:51'),(8,4,'aviso','Calificación modificada por Dirección Regional','La calificación de Sofía Henríquez Alarcón fue revisada y modificada a «Sobresaliente» por la Dirección Regional. Expediente habilitado para remisión a Concepción.','practica',3,15,NULL,'2026-11-05 12:06:51'),(9,2,'informativo','Postulante actualizó sus datos','Camilo Echeverría Solís corrigió sus datos de postulación.','postulacion',1,24,'2026-11-08 18:33:57','2026-11-08 17:06:05'),(10,3,'informativo','Postulante actualizó sus datos','Camilo Echeverría Solís corrigió sus datos de postulación.','postulacion',1,24,NULL,'2026-11-08 17:06:05'),(11,4,'informativo','Postulante actualizó sus datos','Camilo Echeverría Solís corrigió sus datos de postulación.','postulacion',1,24,NULL,'2026-11-08 17:06:05');


-- -----------------------------------------------------------------------------
-- Datos para `auditoria`: 20. Bitácora de auditoría institucional
-- -----------------------------------------------------------------------------
INSERT INTO `auditoria` (`id`, `usuario_id`, `accion`, `entidad`, `entidad_id`, `datos`, `ip`, `user_agent`, `created_at`) VALUES (1,10,'auth.login','usuario',10,NULL,'127.0.0.1',NULL,'2026-11-05 11:08:48'),(2,10,'auth.logout','usuario',10,NULL,'127.0.0.1',NULL,'2026-11-05 11:11:27'),(3,31,'postulacion.crear','postulacion',8,'{\"rut\": \"21922971-2\"}','127.0.0.1',NULL,'2026-11-05 11:38:17'),(4,31,'auth.logout','usuario',31,NULL,'127.0.0.1',NULL,'2026-11-05 11:41:45'),(5,2,'auth.login','usuario',2,NULL,'127.0.0.1',NULL,'2026-11-05 11:41:52'),(6,2,'postulacion.aceptar','postulacion',8,'{\"practica_id\": 7, \"consultorio_id\": 49}','127.0.0.1',NULL,'2026-11-05 11:46:44'),(7,2,'practica.nivelar_oficio','practica',4,'{\"justificacion\": \"fasdfddddddd\", \"concepto_nuevo\": \"Buena\", \"concepto_anterior\": \"Regular\"}','127.0.0.1',NULL,'2026-11-05 12:00:00'),(8,2,'auth.logout','usuario',2,NULL,'127.0.0.1',NULL,'2026-11-05 12:00:42'),(9,14,'auth.login','usuario',14,NULL,'127.0.0.1',NULL,'2026-11-05 12:00:54'),(10,14,'auth.logout','usuario',14,NULL,'127.0.0.1',NULL,'2026-11-05 12:03:20'),(11,15,'auth.login','usuario',15,NULL,'127.0.0.1',NULL,'2026-11-05 12:03:29'),(12,15,'director.modificar_evaluacion','practica',3,'{\"director_id\": 15, \"nota_fijada\": 7, \"director_rol\": \"jefe_ucoe\", \"nota_anterior\": 5.2, \"observaciones\": \"esta persona si pasa\", \"fue_modificada\": true, \"concepto_fijado\": \"Sobresaliente\", \"concepto_anterior\": \"Buena\"}','127.0.0.1',NULL,'2026-11-05 12:06:51'),(13,15,'auth.logout','usuario',15,NULL,'127.0.0.1',NULL,'2026-11-05 12:06:57'),(14,14,'auth.login','usuario',14,NULL,'127.0.0.1',NULL,'2026-11-05 12:07:12'),(15,14,'director_general.firma_gob','practica',3,'{\"folio\": \"12345\", \"sha256\": \"76d31a996006b9650eb579d9f073d52d11be35154fabd86fba65584b2fe5cb87\", \"director_id\": 14, \"documento_id\": 17, \"url_verificacion\": \"http://localhost:8000/director-general/expedientes/3/certificado\"}','127.0.0.1',NULL,'2026-11-05 12:10:16'),(16,14,'auth.logout','usuario',14,NULL,'127.0.0.1',NULL,'2026-11-05 12:11:44'),(17,10,'auth.login','usuario',10,NULL,'127.0.0.1',NULL,'2026-11-05 12:11:49'),(18,10,'auth.logout','usuario',10,NULL,'127.0.0.1',NULL,'2026-11-05 12:17:19'),(19,1,'auth.login','usuario',1,NULL,'127.0.0.1',NULL,'2026-11-05 12:17:33'),(20,1,'consultorio.capacidad','consultorio',49,'{\"a\": 4, \"de\": 5}','127.0.0.1',NULL,'2026-11-05 12:18:40'),(21,1,'consultorio.capacidad','consultorio',49,'{\"a\": 3, \"de\": 4}','127.0.0.1',NULL,'2026-11-05 12:18:40'),(22,1,'consultorio.capacidad','consultorio',49,'{\"a\": 3, \"de\": 3}','127.0.0.1',NULL,'2026-11-05 12:18:59'),(23,1,'consultorio.capacidad','consultorio',49,'{\"a\": 3, \"de\": 3}','127.0.0.1',NULL,'2026-11-05 12:19:02'),(24,2,'auth.login','usuario',2,NULL,'127.0.0.1',NULL,'2026-11-08 16:39:21'),(25,24,'auth.login','usuario',24,NULL,'127.0.0.1',NULL,'2026-11-08 17:05:44'),(26,24,'postulante.actualizar_datos','postulante',1,'{\"campos\": [\"especialidad_id\", \"antecedentes_salud\"]}','127.0.0.1',NULL,'2026-11-08 17:06:05'),(27,24,'auth.logout','usuario',24,NULL,'127.0.0.1',NULL,'2026-11-08 17:06:41'),(28,24,'auth.login','usuario',24,NULL,'127.0.0.1',NULL,'2026-11-08 17:27:39'),(29,2,'auth.logout','usuario',2,NULL,'127.0.0.1',NULL,'2026-11-08 18:01:10'),(30,28,'auth.login','usuario',28,NULL,'127.0.0.1',NULL,'2026-11-08 18:01:27'),(31,28,'auth.logout','usuario',28,NULL,'127.0.0.1',NULL,'2026-11-08 18:03:35'),(32,5,'auth.login','usuario',5,NULL,'127.0.0.1',NULL,'2026-11-08 18:03:48'),(33,5,'auth.logout','usuario',5,NULL,'127.0.0.1',NULL,'2026-11-08 18:19:50'),(34,2,'auth.login','usuario',2,NULL,'127.0.0.1',NULL,'2026-11-08 18:20:01'),(35,2,'auth.logout','usuario',2,NULL,'127.0.0.1',NULL,'2026-11-08 21:00:34'),(36,14,'auth.login','usuario',14,NULL,'127.0.0.1',NULL,'2026-11-08 21:00:45');


-- -----------------------------------------------------------------------------
-- Datos para `migrations`: 21. Registro de migraciones ejecutadas de Laravel (19 migraciones)
-- -----------------------------------------------------------------------------
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000001_create_cache_table',1),(2,'0001_01_01_000002_create_jobs_table',1),(3,'2026_10_05_000001_crea_referencias',1),(4,'2026_10_05_000002_crea_usuarios',1),(5,'2026_10_05_000003_crea_postulaciones_practicas',1),(6,'2026_10_05_000004_crea_evaluacion_documentos',1),(7,'2026_10_06_000001_postulaciones_importadas_datos_parciales',1),(8,'2026_10_06_000002_borrador_postulacion_y_rol_postulante',1),(9,'2026_10_06_000003_postulantes_datos_actualizados',1),(10,'2026_10_14_000001_regiones_operativas_nuble_a_aysen',1),(11,'2026_11_01_000001_unifica_rol_practicante_en_postulante',1),(12,'2026_11_01_000002_agrega_flujo_juridica_central_a_practicas',1),(13,'2026_11_02_000001_agrega_segunda_instancia_a_abogados',1),(14,'2026_11_02_000002_agrega_datos_de_contacto_a_consultorios',1),(15,'2026_11_02_000003_agrega_directores_y_firmagob_a_practicas',1),(16,'2026_11_02_000004_hace_nullable_area_en_abogados',1),(17,'2026_11_08_000001_normalizar_horario_atencion_consultorios',2),(18,'2026_11_08_000002_agrega_folios_institucionales_a_practicas',2),(19,'2026_11_09_000001_agrega_user_agent_a_auditoria',3);


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
