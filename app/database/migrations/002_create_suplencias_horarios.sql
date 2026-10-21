-- ====================================================================
-- MIGRACIÓN 002: GESTIÓN Y APROBACIÓN DE SUPLENCIAS DE INSTRUCTORES
-- Base de Datos: sena_asistencia
-- Fecha de Registro: 2026-10-21
-- ====================================================================

USE `sena_asistencia`;

-- 1. Crear tabla para registrar las suplencias de horarios/fichas
CREATE TABLE IF NOT EXISTS `suplencias_horarios` (
  `id_suplencia` INT(11) NOT NULL AUTO_INCREMENT,
  `instructor_titular_id` INT(11) NOT NULL,
  `instructor_suplente_id` INT(11) NOT NULL,
  `fichas_id_ficha` INT(11) NOT NULL,
  `horarios_id_horario` INT(11) DEFAULT NULL COMMENT 'NULL indica que aplica para todos los bloques de la ficha en el rango de fechas',
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  `motivo` ENUM('Incapacidad Medica', 'Comision de Servicios', 'Permiso Personal', 'Capacitacion', 'Otro') NOT NULL DEFAULT 'Permiso Personal',
  `descripcion_motivo` TEXT DEFAULT NULL,
  `estado` ENUM('Pendiente', 'Aceptada', 'Rechazada', 'Cancelada', 'Finalizada') NOT NULL DEFAULT 'Pendiente',
  `fecha_solicitud` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `fecha_respuesta` DATETIME DEFAULT NULL,
  `observaciones_respuesta` TEXT DEFAULT NULL,
  PRIMARY KEY (`id_suplencia`),
  KEY `idx_suplencia_titular` (`instructor_titular_id`),
  KEY `idx_suplencia_suplente` (`instructor_suplente_id`),
  KEY `idx_suplencia_ficha` (`fichas_id_ficha`),
  KEY `idx_suplencia_fechas` (`fecha_inicio`, `fecha_fin`),
  KEY `idx_suplencia_estado` (`estado`),
  CONSTRAINT `fk_suplencia_titular` FOREIGN KEY (`instructor_titular_id`) REFERENCES `instructores` (`id_instructor`) ON DELETE CASCADE,
  CONSTRAINT `fk_suplencia_suplente` FOREIGN KEY (`instructor_suplente_id`) REFERENCES `instructores` (`id_instructor`) ON DELETE CASCADE,
  CONSTRAINT `fk_suplencia_ficha` FOREIGN KEY (`fichas_id_ficha`) REFERENCES `fichas` (`id_ficha`) ON DELETE CASCADE,
  CONSTRAINT `fk_suplencia_horario` FOREIGN KEY (`horarios_id_horario`) REFERENCES `horarios` (`id_horario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Añadir columna a la tabla de asistencia para trazar si fue tomada bajo suplencia
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'asistencia' 
      AND COLUMN_NAME = 'suplencias_id_suplencia'
);

SET @sql_alter = IF(@col_exists = 0, 
    'ALTER TABLE `asistencia` ADD COLUMN `suplencias_id_suplencia` INT(11) DEFAULT NULL AFTER `horarios_id_horario`, ADD CONSTRAINT `fk_asistencia_suplencia` FOREIGN KEY (`suplencias_id_suplencia`) REFERENCES `suplencias_horarios` (`id_suplencia`) ON DELETE SET NULL', 
    'SELECT 1'
);

PREPARE stmt FROM @sql_alter;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
