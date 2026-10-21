-- =====================================================
-- Migración: IDEA 2 - Control de Marcación Manual y Llamados de Atención
-- Base de Datos: sena_asistencia
-- =====================================================

USE `sena_asistencia`;

-- 1. Asegurar columna metodo_registro en la tabla asistencia
SET @dbname = DATABASE();
SET @tablename = 'asistencia';
SET @columnname = 'metodo_registro';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  'ALTER TABLE asistencia ADD COLUMN metodo_registro ENUM(\'RFID\', \'MANUAL\') NOT NULL DEFAULT \'RFID\' AFTER estado'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2. Asegurar columna observaciones en la tabla asistencia si no existiera
SET @columnname = 'observaciones';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  'ALTER TABLE asistencia ADD COLUMN observaciones TEXT NULL DEFAULT NULL AFTER horarios_id_horario'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 3. Crear tabla de llamados de atención disciplinarios individuales
CREATE TABLE IF NOT EXISTS `llamados_atencion` (
  `id_llamado` INT(11) NOT NULL AUTO_INCREMENT,
  `numero_folio` VARCHAR(50) NOT NULL,
  `asistencia_id_registro` INT(11) NOT NULL,
  `aprendices_id_aprendiz` INT(11) NOT NULL,
  `instructores_id_instructor` INT(11) NOT NULL,
  `fichas_id_ficha` INT(11) NOT NULL,
  `numero_infraccion` INT(11) NOT NULL DEFAULT 3,
  `fecha_generacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `motivo` VARCHAR(255) NOT NULL DEFAULT 'No portar carné o llavero RFID institucional en formación',
  `estado` ENUM('Generado', 'Notificado', 'Firmado', 'Anulado') NOT NULL DEFAULT 'Generado',
  `notificado_correo` TINYINT(1) NOT NULL DEFAULT 0,
  `observaciones` TEXT NULL DEFAULT NULL,
  PRIMARY KEY (`id_llamado`),
  UNIQUE INDEX `uk_folio` (`numero_folio` ASC),
  INDEX `fk_llamados_asistencia_idx` (`asistencia_id_registro` ASC),
  INDEX `fk_llamados_aprendices_idx` (`aprendices_id_aprendiz` ASC),
  INDEX `fk_llamados_instructores_idx` (`instructores_id_instructor` ASC),
  INDEX `fk_llamados_fichas_idx` (`fichas_id_ficha` ASC),
  CONSTRAINT `fk_llamados_asistencia`
    FOREIGN KEY (`asistencia_id_registro`)
    REFERENCES `asistencia` (`id_registro`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_llamados_aprendices`
    FOREIGN KEY (`aprendices_id_aprendiz`)
    REFERENCES `aprendices` (`id_aprendiz`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_llamados_instructores`
    FOREIGN KEY (`instructores_id_instructor`)
    REFERENCES `instructores` (`id_instructor`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_llamados_fichas`
    FOREIGN KEY (`fichas_id_ficha`)
    REFERENCES `fichas` (`id_ficha`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8mb4;
