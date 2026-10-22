-- =====================================================
-- Base de Datos: sena_asistencia
-- Versión 2.0 - Soporte de Bloques de Horarios (3h y 6h)
-- =====================================================

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE SCHEMA IF NOT EXISTS `sena_asistencia` DEFAULT CHARACTER SET utf8mb4 ;
USE `sena_asistencia` ;

-- -----------------------------------------------------
-- Table `sena_asistencia`.`roles`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`roles` (
  `id_rol` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` VARCHAR(30) NOT NULL,
  `descripcion` VARCHAR(150) NULL DEFAULT NULL,
  PRIMARY KEY (`id_rol`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `sena_asistencia`.`usuarios`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`usuarios` (
  `id_usuario` INT(11) NOT NULL AUTO_INCREMENT,
  `tipo_documento` ENUM('CC', 'TI', 'CE', 'Pasaporte') NOT NULL,
  `numero_documento` VARCHAR(20) NOT NULL,
  `nombres` VARCHAR(150) NOT NULL,
  `apellidos` VARCHAR(45) NOT NULL,
  `correo` VARCHAR(150) NOT NULL,
  `telefono` VARCHAR(45) NULL DEFAULT NULL,
  `contraseña` VARCHAR(255) NOT NULL,
  `estado` ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
  `fecha_creacion` DATETIME NULL DEFAULT CURRENT_TIMESTAMP(),
  `roles_id_rol` INT(11) NOT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE INDEX `uk_numero_documento` (`numero_documento` ASC),
  UNIQUE INDEX `uk_correo` (`correo` ASC),
  INDEX `fk_usuarios_roles` (`roles_id_rol` ASC),
  CONSTRAINT `fk_usuarios_roles`
    FOREIGN KEY (`roles_id_rol`)
    REFERENCES `sena_asistencia`.`roles` (`id_rol`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `sena_asistencia`.`instructores`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`instructores` (
  `id_instructor` INT(11) NOT NULL AUTO_INCREMENT,
  `area` ENUM('comercio', 'Tics') NOT NULL,
  `especialidad` VARCHAR(45) NULL DEFAULT NULL,
  `usuarios_id_usuario` INT(11) NOT NULL,
  PRIMARY KEY (`id_instructor`),
  UNIQUE INDEX `uk_instructor_usuario` (`usuarios_id_usuario` ASC),
  INDEX `fk_instructores_usuarios1_idx` (`usuarios_id_usuario` ASC),
  CONSTRAINT `fk_instructores_usuarios1`
    FOREIGN KEY (`usuarios_id_usuario`)
    REFERENCES `sena_asistencia`.`usuarios` (`id_usuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `sena_asistencia`.`fichas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`fichas` (
  `id_ficha` INT(11) NOT NULL AUTO_INCREMENT,
  `numero_ficha` VARCHAR(10) NOT NULL,
  `programa` VARCHAR(100) NOT NULL,
  `estado` ENUM('Activa', 'Inactiva', 'Finalizada') NOT NULL DEFAULT 'Activa',
  `area` ENUM('comercio', 'Tics') NOT NULL,
  `fecha_creacion` DATETIME NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id_ficha`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `sena_asistencia`.`horarios` (Bloques y franjas horarias por Ficha e Instructor)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`horarios` (
  `id_horario` INT(11) NOT NULL AUTO_INCREMENT,
  `fichas_id_ficha` INT(11) NOT NULL,
  `instructores_id_instructor` INT(11) NULL DEFAULT NULL,
  `fecha` DATE NULL DEFAULT NULL,
  `dias_semana` ENUM('Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo') NOT NULL,
  `hora_entrada` TIME NOT NULL,
  `hora_salida` TIME NOT NULL,
  `duracion_horas` TINYINT NOT NULL DEFAULT 3,
  `tipo_bloque` ENUM('3H_INDIVIDUAL', '6H_CONTINUO', '4H_NOCTURNA', '6H_PARTICIONADO') NOT NULL DEFAULT '3H_INDIVIDUAL',
  `ambiente` VARCHAR(100) NULL DEFAULT NULL,
  `competencia` VARCHAR(255) NULL DEFAULT NULL,
  `estado` ENUM('Activo', 'Inactivo') NOT NULL DEFAULT 'Activo',
  `fecha_creacion` DATETIME NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id_horario`),
  INDEX `fk_horarios_fichas_idx` (`fichas_id_ficha` ASC),
  INDEX `fk_horarios_instructores_idx` (`instructores_id_instructor` ASC),
  INDEX `idx_horarios_fecha` (`fecha` ASC),
  CONSTRAINT `fk_horarios_fichas`
    FOREIGN KEY (`fichas_id_ficha`)
    REFERENCES `sena_asistencia`.`fichas` (`id_ficha`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_horarios_instructores`
    FOREIGN KEY (`instructores_id_instructor`)
    REFERENCES `sena_asistencia`.`instructores` (`id_instructor`)
    ON DELETE SET NULL
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `sena_asistencia`.`aprendices`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`aprendices` (
  `id_aprendiz` INT(11) NOT NULL AUTO_INCREMENT,
  `codigo_rfid` VARCHAR(45) NOT NULL,
  `estado_matricula` ENUM('Matriculado', 'Retirado', 'Aplazado') NOT NULL DEFAULT 'Matriculado',
  `usuarios_id_usuario` INT(11) NOT NULL,
  `fichas_id_ficha` INT(11) NOT NULL,
  PRIMARY KEY (`id_aprendiz`),
  UNIQUE INDEX `uk_codigo_rfid` (`codigo_rfid` ASC),
  INDEX `fk_aprendices_usuarios` (`usuarios_id_usuario` ASC),
  INDEX `fk_aprendices_fichas` (`fichas_id_ficha` ASC),
  CONSTRAINT `fk_aprendices_fichas`
    FOREIGN KEY (`fichas_id_ficha`)
    REFERENCES `sena_asistencia`.`fichas` (`id_ficha`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_aprendices_usuarios`
    FOREIGN KEY (`usuarios_id_usuario`)
    REFERENCES `sena_asistencia`.`usuarios` (`id_usuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `sena_asistencia`.`instructores_has_fichas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`instructores_has_fichas` (
  `instructores_id_instructor` INT(11) NOT NULL,
  `fichas_id_ficha` INT(11) NOT NULL,
  `minutos_retardo` INT NOT NULL DEFAULT 0,
  `minutos_salida` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`instructores_id_instructor`, `fichas_id_ficha`),
  INDEX `fk_instructores_has_fichas_fichas1_idx` (`fichas_id_ficha` ASC),
  INDEX `fk_instructores_has_fichas_instructores1_idx` (`instructores_id_instructor` ASC),
  CONSTRAINT `fk_instructores_has_fichas_instructores1`
    FOREIGN KEY (`instructores_id_instructor`)
    REFERENCES `sena_asistencia`.`instructores` (`id_instructor`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_instructores_has_fichas_fichas1`
    FOREIGN KEY (`fichas_id_ficha`)
    REFERENCES `sena_asistencia`.`fichas` (`id_ficha`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `sena_asistencia`.`excusas_medicas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`excusas_medicas` (
  `id_excusa` INT(11) NOT NULL AUTO_INCREMENT,
  `motivo` ENUM('Enfermedad', 'Cita Medica', 'Calamidad', 'Otro') NOT NULL,
  `archivo_soporte` VARCHAR(255) NULL DEFAULT NULL,
  `observaciones` TEXT NULL DEFAULT NULL,
  `fecha_radicacion` DATETIME NULL DEFAULT CURRENT_TIMESTAMP(),
  `fecha_evaluacion` DATETIME NULL DEFAULT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  PRIMARY KEY (`id_excusa`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `sena_asistencia`.`detalle_excusas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`detalle_excusas` (
  `id_detalle` INT NOT NULL AUTO_INCREMENT,
  `estado` ENUM('Pendiente', 'Aprobado', 'Rechazado') NOT NULL,
  `fecha_evaluacion` DATETIME NULL,
  `observaciones` TEXT NULL,
  `excusas_medicas_id_excusa` INT(11) NOT NULL,
  `instructores_id_instructor` INT(11) NOT NULL,
  PRIMARY KEY (`id_detalle`),
  INDEX `fk_detalle_excusas_excusas_medicas1_idx` (`excusas_medicas_id_excusa` ASC),
  INDEX `fk_detalle_excusas_instructores1_idx` (`instructores_id_instructor` ASC),
  CONSTRAINT `fk_detalle_excusas_excusas_medicas1`
    FOREIGN KEY (`excusas_medicas_id_excusa`)
    REFERENCES `sena_asistencia`.`excusas_medicas` (`id_excusa`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_detalle_excusas_instructores1`
    FOREIGN KEY (`instructores_id_instructor`)
    REFERENCES `sena_asistencia`.`instructores` (`id_instructor`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `sena_asistencia`.`asistencia`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`asistencia` (
  `id_registro` INT(11) NOT NULL AUTO_INCREMENT,
  `fecha` DATE NOT NULL,
  `hora_entrada` TIME NULL DEFAULT NULL,
  `hora_salida` TIME NULL DEFAULT NULL,
  `estado` ENUM('Asistio', 'Retardo', 'Salida Temprana', 'Inasistencia', 'Justificada') NOT NULL,
  `metodo_registro` ENUM('RFID', 'MANUAL') NOT NULL DEFAULT 'RFID',
  `minutos_retardo` INT(11) NULL DEFAULT 0,
  `aprendices_id_aprendiz` INT(11) NOT NULL,
  `excusas_medicas_id_excusa` INT(11) NULL DEFAULT NULL,
  `instructores_id_instructor` INT(11) NOT NULL,
  `horarios_id_horario` INT(11) NULL DEFAULT NULL,
  `suplencias_id_suplencia` INT(11) NULL DEFAULT NULL,
  `observaciones` TEXT NULL DEFAULT NULL,
  PRIMARY KEY (`id_registro`),
  INDEX `fk_asistencia_aprendices` (`aprendices_id_aprendiz` ASC),
  INDEX `fk_asistencia_excusas_medicas1_idx` (`excusas_medicas_id_excusa` ASC),
  INDEX `fk_asistencia_instructores1_idx` (`instructores_id_instructor` ASC),
  INDEX `fk_asistencia_horarios_idx` (`horarios_id_horario` ASC),
  INDEX `fk_asistencia_suplencia_idx` (`suplencias_id_suplencia` ASC),
  CONSTRAINT `fk_asistencia_aprendices`
    FOREIGN KEY (`aprendices_id_aprendiz`)
    REFERENCES `sena_asistencia`.`aprendices` (`id_aprendiz`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_asistencia_excusas_medicas1`
    FOREIGN KEY (`excusas_medicas_id_excusa`)
    REFERENCES `sena_asistencia`.`excusas_medicas` (`id_excusa`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_asistencia_instructores1`
    FOREIGN KEY (`instructores_id_instructor`)
    REFERENCES `sena_asistencia`.`instructores` (`id_instructor`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_asistencia_horarios`
    FOREIGN KEY (`horarios_id_horario`)
    REFERENCES `sena_asistencia`.`horarios` (`id_horario`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_asistencia_suplencia`
    FOREIGN KEY (`suplencias_id_suplencia`)
    REFERENCES `sena_asistencia`.`suplencias_horarios` (`id_suplencia`)
    ON DELETE SET NULL
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `sena_asistencia`.`recuperacion_contrasenas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`recuperacion_contrasenas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` INT(11) NOT NULL,
  `codigo` VARCHAR(6) NOT NULL,
  `expiracion` DATETIME NOT NULL,
  `utilizado` TINYINT(1) DEFAULT 0,
  `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  INDEX `idx_recup_usuario` (`id_usuario`),
  INDEX `idx_recup_codigo` (`codigo`),
  CONSTRAINT `fk_recup_usuario`
    FOREIGN KEY (`id_usuario`)
    REFERENCES `sena_asistencia`.`usuarios` (`id_usuario`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8mb4;

-- -----------------------------------------------------
-- Table `sena_asistencia`.`suplencias_horarios`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`suplencias_horarios` (
  `id_suplencia` INT(11) NOT NULL AUTO_INCREMENT,
  `instructor_titular_id` INT(11) NOT NULL,
  `instructor_suplente_id` INT(11) NOT NULL,
  `fichas_id_ficha` INT(11) NOT NULL,
  `horarios_id_horario` INT(11) DEFAULT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  `motivo` ENUM('Incapacidad Medica', 'Comision de Servicios', 'Permiso Personal', 'Capacitacion', 'Otro') NOT NULL DEFAULT 'Permiso Personal',
  `descripcion_motivo` TEXT DEFAULT NULL,
  `estado` ENUM('Pendiente', 'Aceptada', 'Rechazada', 'Cancelada', 'Finalizada') NOT NULL DEFAULT 'Pendiente',
  `fecha_solicitud` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `fecha_respuesta` DATETIME DEFAULT NULL,
  `observaciones_respuesta` TEXT DEFAULT NULL,
  PRIMARY KEY (`id_suplencia`),
  INDEX `idx_suplencia_titular` (`instructor_titular_id` ASC),
  INDEX `idx_suplencia_suplente` (`instructor_suplente_id` ASC),
  INDEX `idx_suplencia_ficha` (`fichas_id_ficha` ASC),
  INDEX `idx_suplencia_fechas` (`fecha_inicio` ASC, `fecha_fin` ASC),
  INDEX `idx_suplencia_estado` (`estado` ASC),
  CONSTRAINT `fk_suplencia_titular`
    FOREIGN KEY (`instructor_titular_id`)
    REFERENCES `sena_asistencia`.`instructores` (`id_instructor`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_suplencia_suplente`
    FOREIGN KEY (`instructor_suplente_id`)
    REFERENCES `sena_asistencia`.`instructores` (`id_instructor`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_suplencia_ficha`
    FOREIGN KEY (`fichas_id_ficha`)
    REFERENCES `sena_asistencia`.`fichas` (`id_ficha`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_suplencia_horario`
    FOREIGN KEY (`horarios_id_horario`)
    REFERENCES `sena_asistencia`.`horarios` (`id_horario`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8mb4;

-- -----------------------------------------------------
-- Table `sena_asistencia`.`llamados_atencion`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sena_asistencia`.`llamados_atencion` (
  `id_llamado` INT(11) NOT NULL AUTO_INCREMENT,
  `numero_folio` VARCHAR(50) NOT NULL,
  `asistencia_id_registro` INT(11) NOT NULL,
  `aprendices_id_aprendiz` INT(11) NOT NULL,
  `instructores_id_instructor` INT(11) NOT NULL,
  `fichas_id_ficha` INT(11) NOT NULL,
  `numero_infraccion` INT(11) NOT NULL DEFAULT 1,
  `fecha_generacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
    REFERENCES `sena_asistencia`.`asistencia` (`id_registro`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_llamados_aprendices`
    FOREIGN KEY (`aprendices_id_aprendiz`)
    REFERENCES `sena_asistencia`.`aprendices` (`id_aprendiz`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_llamados_instructores`
    FOREIGN KEY (`instructores_id_instructor`)
    REFERENCES `sena_asistencia`.`instructores` (`id_instructor`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_llamados_fichas`
    FOREIGN KEY (`fichas_id_ficha`)
    REFERENCES `sena_asistencia`.`fichas` (`id_ficha`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8mb4;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;