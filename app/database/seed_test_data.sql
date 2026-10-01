-- ==========================================================================
-- SCRIPT DE DATOS BÁSICO / LIVIANO - SISTEMA DE CONTROL DE ASISTENCIA SENA
-- ==========================================================================
-- Incluye:
--  * 4 Roles
--  * 2 Fichas: 1 de TICs (2694521 - ADSO) y 1 de Comercio (2718932 - Contabilidad)
--  * 2 Administradores: 1 de TICs y 1 de Comercio
--  * 2 Instructores: 1 para TICs y 1 para Comercio
--  * 4 Aprendices: 2 en la Ficha TICs y 2 en la Ficha Comercio
--  * Horarios de prueba con bloques de 3h y 6h
--  * Asistencias de prueba vinculadas a sus respectivos bloques
--  * CONTRASEÑA UNIVERSAL PARA TODOS: 123456
-- ==========================================================================

USE `sena_asistencia`;

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `asistencia`;
TRUNCATE TABLE `detalle_excusas`;
TRUNCATE TABLE `excusas_medicas`;
TRUNCATE TABLE `instructores_has_fichas`;
TRUNCATE TABLE `aprendices`;
TRUNCATE TABLE `horarios`;
TRUNCATE TABLE `instructores`;
TRUNCATE TABLE `usuarios`;
TRUNCATE TABLE `fichas`;
TRUNCATE TABLE `roles`;

-- -----------------------------------------------------
-- 1. ROLES
-- -----------------------------------------------------
INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion`) VALUES
(1, 'Administrador Comercio', 'Gestiona fichas, instructores y reportes de Comercio'),
(2, 'Instructor', 'Pasa lista, configura retardos y evalúa excusas'),
(3, 'Aprendiz', 'Consulta horario, asistencias y radica excusas'),
(4, 'Administrador TICs', 'Gestiona fichas, instructores y reportes de TICs');

-- -----------------------------------------------------
-- 2. FICHAS DE FORMACIÓN
-- -----------------------------------------------------
INSERT INTO `fichas` (`id_ficha`, `numero_ficha`, `programa`, `estado`, `area`, `fecha_creacion`) VALUES
(1, '2694521', 'Análisis y Desarrollo de Software', 'Activa', 'Tics', NOW()),
(2, '2718932', 'Gestión Contable y Financiera', 'Activa', 'comercio', NOW());

-- -----------------------------------------------------
-- 3. USUARIOS (2 Admins, 2 Instructores, 4 Aprendices)
-- Contraseña universal: 123456
-- -----------------------------------------------------
INSERT INTO `usuarios` (`id_usuario`, `tipo_documento`, `numero_documento`, `nombres`, `apellidos`, `correo`, `telefono`, `contraseña`, `estado`, `roles_id_rol`) VALUES
-- Administradores
(1, 'CC', '1000000001', 'Admin', 'Comercio', 'admin.comercio@sena.edu.co', '3001112233', '$2y$10$ULejeZHULlzeH2tn8w2GNeAMuAXuNBN5XcLueb8UPX2UcBkMU.KVm', 'Activo', 1),
(2, 'CC', '1000000002', 'Admin', 'TICs', 'admin.tics@sena.edu.co', '3002223344', '$2y$10$ULejeZHULlzeH2tn8w2GNeAMuAXuNBN5XcLueb8UPX2UcBkMU.KVm', 'Activo', 4),

-- Instructores
(3, 'CC', '1000000010', 'Carlos Alberto', 'Gómez Restrepo', 'carlos.gomez@sena.edu.co', '3104567890', '$2y$10$ULejeZHULlzeH2tn8w2GNeAMuAXuNBN5XcLueb8UPX2UcBkMU.KVm', 'Activo', 2),
(4, 'CC', '1000000017', 'Gloria Stella', 'Henao Duque', 'gloria.henao@sena.edu.co', '3171234567', '$2y$10$ULejeZHULlzeH2tn8w2GNeAMuAXuNBN5XcLueb8UPX2UcBkMU.KVm', 'Activo', 2),

-- Aprendices Ficha TICs (ADSO)
(5, 'TI', '1010000000', 'Felipe', 'Ortiz Álvarez', 'felipe.ortiz@soy.sena.edu.co', '3100234555', '$2y$10$ULejeZHULlzeH2tn8w2GNeAMuAXuNBN5XcLueb8UPX2UcBkMU.KVm', 'Activo', 3),
(6, 'CC', '1010000001', 'Andrea', 'Álvarez Cardona', 'andrea.alvarez@soy.sena.edu.co', '3100246900', '$2y$10$ULejeZHULlzeH2tn8w2GNeAMuAXuNBN5XcLueb8UPX2UcBkMU.KVm', 'Activo', 3),

-- Aprendices Ficha Comercio (Contabilidad)
(7, 'CC', '1010000004', 'Santiago', 'Restrepo Ríos', 'santiago.restrepo@soy.sena.edu.co', '3100283935', '$2y$10$ULejeZHULlzeH2tn8w2GNeAMuAXuNBN5XcLueb8UPX2UcBkMU.KVm', 'Activo', 3),
(8, 'CC', '1010000005', 'Catalina', 'Giraldo Acosta', 'catalina.giraldo@soy.sena.edu.co', '3100296280', '$2y$10$ULejeZHULlzeH2tn8w2GNeAMuAXuNBN5XcLueb8UPX2UcBkMU.KVm', 'Activo', 3);

-- -----------------------------------------------------
-- 4. INSTRUCTORES
-- -----------------------------------------------------
INSERT INTO `instructores` (`id_instructor`, `area`, `especialidad`, `usuarios_id_usuario`) VALUES
(1, 'Tics', 'Desarrollo Web Full Stack', 3),
(2, 'comercio', 'Contabilidad y NIIF', 4);

-- -----------------------------------------------------
-- 5. ASIGNACIÓN INSTRUCTORES <-> FICHAS (Políticas de Retardo)
-- -----------------------------------------------------
INSERT INTO `instructores_has_fichas` (`instructores_id_instructor`, `fichas_id_ficha`, `minutos_retardo`, `minutos_salida`) VALUES
(1, 1, 15, 0), -- Carlos Gómez en Ficha 1 (TICs) con 15 min de tolerancia
(2, 2, 10, 0); -- Gloria Henao en Ficha 2 (Comercio) con 10 min de tolerancia

-- -----------------------------------------------------
-- 6. APRENDICES (2 para TICs y 2 para Comercio con RFID)
-- -----------------------------------------------------
INSERT INTO `aprendices` (`id_aprendiz`, `codigo_rfid`, `estado_matricula`, `usuarios_id_usuario`, `fichas_id_ficha`) VALUES
(1, 'RFID-TIC-01', 'Matriculado', 5, 1),
(2, 'RFID-TIC-02', 'Matriculado', 6, 1),
(3, 'RFID-COM-01', 'Matriculado', 7, 2),
(4, 'RFID-COM-02', 'Matriculado', 8, 2);

-- -----------------------------------------------------
-- 7. HORARIOS (Bloques de 3h y 6h)
-- -----------------------------------------------------
INSERT INTO `horarios` (`id_horario`, `fichas_id_ficha`, `instructores_id_instructor`, `dias_semana`, `hora_entrada`, `hora_salida`, `duracion_horas`, `tipo_bloque`, `ambiente`, `competencia`, `estado`) VALUES
-- FICHA 1 (TICs - Jornada Mañana)
(1, 1, 1, 'Lunes', '06:00:00', '09:00:00', 3, '3H_INDIVIDUAL', 'Ambiente 301', '220501096 - Desarrollo Web Backend', 'Activo'),
(2, 1, 1, 'Lunes', '09:00:00', '12:00:00', 3, '3H_INDIVIDUAL', 'Ambiente 301', '220501096 - Taller de Frontend', 'Activo'),
(3, 1, 1, 'Martes', '06:00:00', '12:00:00', 6, '6H_CONTINUO', 'Laboratorio TIC 1', '220501095 - Bases de Datos y SQL', 'Activo'),

-- FICHA 2 (Comercio - Jornada Tarde)
(4, 2, 2, 'Lunes', '12:00:00', '15:00:00', 3, '3H_INDIVIDUAL', 'Aula 201', '210303022 - Contabilidad General y NIIF', 'Activo'),
(5, 2, 2, 'Lunes', '15:00:00', '18:00:00', 3, '3H_INDIVIDUAL', 'Aula 201', '210303023 - Costos y Presupuestos', 'Activo'),
(6, 2, 2, 'Martes', '12:00:00', '18:00:00', 6, '6H_CONTINUO', 'Laboratorio Comercio 1', '210303024 - Taller Práctico Siigo', 'Activo');

-- -----------------------------------------------------
-- 8. ASISTENCIAS DE PRUEBA (Vinculadas a sus bloques)
-- -----------------------------------------------------
INSERT INTO `asistencia` (`id_registro`, `fecha`, `hora_entrada`, `hora_salida`, `estado`, `minutos_retardo`, `aprendices_id_aprendiz`, `excusas_medicas_id_excusa`, `instructores_id_instructor`, `horarios_id_horario`) VALUES
-- Aprendiz TIC 1: Asistió puntual al Bloque 1 y al Bloque 2
(1, CURDATE(), '05:55:00', '09:00:00', 'Asistio', 0, 1, NULL, 1, 1),
(2, CURDATE(), '09:02:00', '12:00:00', 'Asistio', 0, 1, NULL, 1, 2),

-- Aprendiz TIC 2: Retardo en Bloque 1, puntual en Bloque 2
(3, CURDATE(), '06:18:00', '09:00:00', 'Retardo', 18, 2, NULL, 1, 1),
(4, CURDATE(), '09:00:00', '12:00:00', 'Asistio', 0, 2, NULL, 1, 2),

-- Aprendiz Comercio 1: Asistió puntual a sus dos bloques
(5, CURDATE(), '11:58:00', '15:00:00', 'Asistio', 0, 3, NULL, 2, 4),
(6, CURDATE(), '15:01:00', '18:00:00', 'Asistio', 0, 3, NULL, 2, 5),

-- Aprendiz Comercio 2: Asistió a Bloque 4, pero inasistió a Bloque 5
(7, CURDATE(), '11:55:00', '15:00:00', 'Asistio', 0, 4, NULL, 2, 4),
(8, CURDATE(), NULL, NULL, 'Inasistencia', 0, 4, NULL, 2, 5);

SET FOREIGN_KEY_CHECKS = 1;
