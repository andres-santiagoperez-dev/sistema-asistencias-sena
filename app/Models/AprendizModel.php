<?php
/**
 * Modelo de Aprendices (AprendizModel)
 * 
 * Responsabilidad:
 * 1. Consultar aprendices matriculados con su información de usuario y ficha asignada.
 * 2. Filtrar por área técnica ('comercio' o 'Tics') y por ficha de formación.
 * 3. Consultar fichas disponibles para el filtro y la asignación en el modal.
 * 4. Registrar nuevos aprendices (creación en `usuarios` + registro en `aprendices`) vía transacción.
 * 5. Actualizar el estado de matrícula de un aprendiz.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

class AprendizModel
{
    /**
     * Lista los aprendices con sus datos personales, ficha y estado.
     * Permite filtrar opcionalmente por área técnica y/o por ficha específica.
     */
    public function listarAprendices(?string $area = null, ?int $idFicha = null): array
    {
        $sql = "SELECT 
                    a.id_aprendiz,
                    a.codigo_rfid,
                    a.estado_matricula,
                    u.id_usuario,
                    u.tipo_documento,
                    u.numero_documento AS documento,
                    CONCAT(u.nombres, ' ', u.apellidos) AS nombre,
                    u.nombres,
                    u.apellidos,
                    u.correo,
                    u.telefono,
                    u.estado AS estado_usuario,
                    f.id_ficha,
                    f.numero_ficha,
                    f.programa,
                    f.area,
                    CONCAT(f.numero_ficha, ' - ', f.programa) AS ficha_completa
                FROM aprendices a
                INNER JOIN usuarios u ON a.usuarios_id_usuario = u.id_usuario
                INNER JOIN fichas f   ON a.fichas_id_ficha = f.id_ficha";

        $condiciones = [];
        $params      = [];

        if ($area !== null && $area !== '' && $area !== 'todas') {
            $condiciones[] = "f.area = :area";
            $params[':area'] = $area;
        }

        if ($idFicha !== null && $idFicha > 0) {
            $condiciones[] = "f.id_ficha = :id_ficha";
            $params[':id_ficha'] = $idFicha;
        }

        if (!empty($condiciones)) {
            $sql .= " WHERE " . implode(' AND ', $condiciones);
        }

        $sql .= " ORDER BY u.apellidos ASC, u.nombres ASC";

        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Obtiene el listado de fichas disponibles para los selectores de filtro y asignación.
     */
    public function obtenerFichasParaSelect(?string $area = null): array
    {
        $sql = "SELECT id_ficha, numero_ficha, programa, area,
                       CONCAT(numero_ficha, ' - ', programa) AS etiqueta
                FROM fichas";
        
        $params = [];
        if ($area !== null && $area !== '' && $area !== 'todas') {
            $sql .= " WHERE area = :area";
            $params[':area'] = $area;
        }

        $sql .= " ORDER BY numero_ficha ASC";

        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Busca la información de un aprendiz por su ID.
     */
    public function buscarPorId(int $idAprendiz): array|false
    {
        $sql = "SELECT 
                    a.id_aprendiz,
                    a.codigo_rfid,
                    a.estado_matricula,
                    u.id_usuario,
                    u.tipo_documento,
                    u.numero_documento AS documento,
                    u.nombres,
                    u.apellidos,
                    u.correo,
                    u.telefono,
                    f.id_ficha,
                    f.numero_ficha,
                    f.programa,
                    f.area
                FROM aprendices a
                INNER JOIN usuarios u ON a.usuarios_id_usuario = u.id_usuario
                INNER JOIN fichas f   ON a.fichas_id_ficha = f.id_ficha
                WHERE a.id_aprendiz = :id_aprendiz
                LIMIT 1";

        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute([':id_aprendiz' => $idAprendiz]);

        return $stmt->fetch();
    }

    /**
     * Registra un nuevo aprendiz mediante transacción atómica (Usuario + Registro de Aprendiz).
     */
    public function crearAprendiz(array $datosUsuario, int $idFicha, string $codigoRfid, string $estadoMatricula = 'Matriculado'): int
    {
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();

        try {
            // 1. Obtener ID del rol 'Aprendiz'
            $stmtRol = $pdo->prepare("SELECT id_rol FROM roles WHERE nombre_rol LIKE '%aprendiz%' LIMIT 1");
            $stmtRol->execute();
            $rol = $stmtRol->fetch();
            $idRol = $rol ? (int)$rol['id_rol'] : 3;

            // 2. Hash de contraseña por defecto (o suministrada)
            $passwordPlana = $datosUsuario['password'] ?? '123456';
            $passwordHash  = password_hash($passwordPlana, PASSWORD_BCRYPT);

            // 3. Insertar usuario base
            $sqlUsuario = "INSERT INTO usuarios (tipo_documento, numero_documento, nombres, apellidos, correo, telefono, contraseña, estado, roles_id_rol)
                           VALUES (:tipo_doc, :doc, :nombres, :apellidos, :correo, :telefono, :pass, 'Activo', :rol)";
            $stmtUser = $pdo->prepare($sqlUsuario);
            $stmtUser->execute([
                ':tipo_doc'  => $datosUsuario['tipo_documento'] ?? 'CC',
                ':doc'       => $datosUsuario['documento'],
                ':nombres'   => $datosUsuario['nombres'],
                ':apellidos' => $datosUsuario['apellidos'],
                ':correo'    => $datosUsuario['correo'],
                ':telefono'  => $datosUsuario['telefono'] ?? null,
                ':pass'      => $passwordHash,
                ':rol'       => $idRol
            ]);

            $idUsuario = (int)$pdo->lastInsertId();

            // 4. Insertar registro técnico de aprendiz vinculado a la ficha
            $sqlAprendiz = "INSERT INTO aprendices (codigo_rfid, estado_matricula, usuarios_id_usuario, fichas_id_ficha)
                            VALUES (:rfid, :estado, :id_usuario, :id_ficha)";
            $stmtApr = $pdo->prepare($sqlAprendiz);
            $stmtApr->execute([
                ':rfid'       => $codigoRfid,
                ':estado'     => $estadoMatricula,
                ':id_usuario' => $idUsuario,
                ':id_ficha'   => $idFicha
            ]);

            $idAprendiz = (int)$pdo->lastInsertId();

            $pdo->commit();
            return $idAprendiz;

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza el estado de matrícula de un aprendiz (Matriculado, Retirado, Aplazado).
     */
    public function actualizarEstadoMatricula(int $idAprendiz, string $nuevoEstado): bool
    {
        $sql = "UPDATE aprendices 
                SET estado_matricula = :estado
                WHERE id_aprendiz = :id_aprendiz";

        $stmt = Conexion::obtener()->prepare($sql);
        return $stmt->execute([
            ':estado'      => $nuevoEstado,
            ':id_aprendiz' => $idAprendiz
        ]);
    }
}
