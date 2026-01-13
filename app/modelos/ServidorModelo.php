<?php
require_once __DIR__ . '/../core/conexion.php';

class ServidorModelo
{
    public static function agregarServidor(
        string $alias,
        string $ip,
        ?string $dominio,
        int $id,
        string $tokenHash
    ): bool {
        global $conexion;

        $sqlCheck = "SELECT COUNT(*) as total FROM servidor WHERE alias = ?";
        $stmtCheck = mysqli_prepare($conexion, $sqlCheck);
        mysqli_stmt_bind_param($stmtCheck, "s", $alias);
        mysqli_stmt_execute($stmtCheck);
        $resCheck = mysqli_stmt_get_result($stmtCheck);
        $rowCheck = mysqli_fetch_assoc($resCheck);
        mysqli_stmt_close($stmtCheck);

        if ($rowCheck['total'] > 0) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error_servidor'] = "El alias '$alias' ya está en uso. Elige otro.";
            return false;
        }

        mysqli_begin_transaction($conexion);

        try {
            $sql1 = "INSERT INTO servidor (alias, ip, dominio, dueno_id, token)
                     VALUES (?, ?, ?, ?, ?)";
            $stmt1 = mysqli_prepare($conexion, $sql1);
            if (!$stmt1) {
                throw new Exception(mysqli_error($conexion));
            }

            mysqli_stmt_bind_param($stmt1, "sssis", $alias, $ip, $dominio, $id, $tokenHash);

            if (!mysqli_stmt_execute($stmt1)) {
                throw new Exception(mysqli_stmt_error($stmt1));
            }

            $id_servidor = mysqli_insert_id($conexion);
            mysqli_stmt_close($stmt1);

            $rol = 'admin';
            $sql2 = "INSERT INTO usuarios_servidor (id_servidor, id_usuario, rol)
                     VALUES (?, ?, ?)";
            $stmt2 = mysqli_prepare($conexion, $sql2);
            if (!$stmt2) {
                throw new Exception(mysqli_error($conexion));
            }

            mysqli_stmt_bind_param($stmt2, "iis", $id_servidor, $id, $rol);

            if (!mysqli_stmt_execute($stmt2)) {
                throw new Exception(mysqli_stmt_error($stmt2));
            }

            mysqli_stmt_close($stmt2);

            mysqli_commit($conexion);
            return true;

        } catch (Exception $e) {
            mysqli_rollback($conexion);
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error_servidor'] = $e->getMessage();
            return false;
        }
    }

    public static function listarPorUsuario(int $id_usuario): array
    {
        global $conexion;

        $sql = "
            SELECT
                s.id,
                s.alias,
                s.ip,
                s.dominio,
                s.estado,
                s.dueno_id,
                us.rol AS rol_usuario
            FROM usuarios_servidor us
            INNER JOIN servidor s ON s.id = us.id_servidor
            WHERE us.id_usuario = ?
            ORDER BY s.alias ASC
        ";

        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) return [];

        mysqli_stmt_bind_param($stmt, "i", $id_usuario);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

        mysqli_stmt_close($stmt);

        return $rows ?: [];
    }

    public static function validarTokenPorIp(string $ipRequest, string $tokenPlano): bool
    {
        global $conexion;

        $sql = "SELECT token FROM servidor WHERE ip = ? LIMIT 1";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) return false;

        mysqli_stmt_bind_param($stmt, "s", $ipRequest);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$row) return false;

        $hash = $row['token'] ?? '';
        if ($hash === '') return false;

        return password_verify($tokenPlano, $hash);
    }

    public static function validarTokenPorAlias(string $alias, string $tokenPlano, string $ipRequest): bool
    {
        global $conexion;

        $sql = "SELECT token, ip FROM servidor WHERE alias = ? LIMIT 1";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) return false;

        mysqli_stmt_bind_param($stmt, "s", $alias);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$row) return false;

        $hash = $row['token'] ?? '';
        $ipRegistrada = $row['ip'] ?? '';

        return password_verify($tokenPlano, $hash) && ($ipRegistrada === $ipRequest);
    }

    public static function marcarActivoPorIp(string $ipRequest): bool
    {
        global $conexion;

        $sql = "UPDATE servidor SET estado = 'ENCENDIDO', ultima_senal = NOW() WHERE ip = ? LIMIT 1";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) return false;

        mysqli_stmt_bind_param($stmt, "s", $ipRequest);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return (bool)$ok;
    }

    public static function marcarActivoPorAlias(string $alias): bool
    {
        global $conexion;

        $sql = "UPDATE servidor SET estado = 'ENCENDIDO', ultima_senal = NOW() WHERE alias = ? LIMIT 1";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) return false;

        mysqli_stmt_bind_param($stmt, "s", $alias);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return (bool)$ok;
    }

    public static function marcarApagadoPorIp(string $ipRequest): bool
    {
        global $conexion;

        $sql = "UPDATE servidor SET estado = 'APAGADO', ultima_senal = NOW() WHERE ip = ? LIMIT 1";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) return false;

        mysqli_stmt_bind_param($stmt, "s", $ipRequest);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return (bool)$ok;
    }

    public static function marcarApagadoPorAlias(string $alias): bool
    {
        global $conexion;

        $sql = "UPDATE servidor SET estado = 'APAGADO', ultima_senal = NOW() WHERE alias = ? LIMIT 1";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) return false;

        mysqli_stmt_bind_param($stmt, "s", $alias);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return (bool)$ok;
    }

    public static function marcarIndeterminadoPorUsuario(int $id_usuario, int $segundos): void
    {
        global $conexion;

        $sql = "
            UPDATE servidor s
            INNER JOIN usuarios_servidor us ON us.id_servidor = s.id
            SET s.estado = 'INDETERMINADO'
            WHERE us.id_usuario = ?
            AND s.estado = 'ENCENDIDO'
            AND (s.ultima_senal IS NULL OR TIMESTAMPDIFF(SECOND, s.ultima_senal, NOW()) > ?)
        ";

        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) return;

        mysqli_stmt_bind_param($stmt, "ii", $id_usuario, $segundos);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    public static function actualizarServidor(int $id, string $alias, string $ip, ?string $dominio, ?string $tokenHash = null): bool
    {
        global $conexion;

        // 1. Obtener los datos actuales del servidor para comparar si el alias cambió
        $sqlCurrent = "SELECT alias FROM servidor WHERE id = ? LIMIT 1";
        $stmtCurrent = mysqli_prepare($conexion, $sqlCurrent);
        mysqli_stmt_bind_param($stmtCurrent, "i", $id);
        mysqli_stmt_execute($stmtCurrent);
        $resCurrent = mysqli_stmt_get_result($stmtCurrent);
        $current = mysqli_fetch_assoc($resCurrent);
        mysqli_stmt_close($stmtCurrent);

        if (!$current) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error_servidor'] = "No se encontró el servidor con ID $id.";
            return false;
        }

        // 2. Si el alias es diferente al actual, verificar que el nuevo no esté duplicado
        if ($current['alias'] !== $alias) {
            $sqlCheck = "SELECT COUNT(*) as total FROM servidor WHERE alias = ? AND id != ?";
            $stmtCheck = mysqli_prepare($conexion, $sqlCheck);
            mysqli_stmt_bind_param($stmtCheck, "si", $alias, $id);
            mysqli_stmt_execute($stmtCheck);
            $resCheck = mysqli_stmt_get_result($stmtCheck);
            $rowCheck = mysqli_fetch_assoc($resCheck);
            mysqli_stmt_close($stmtCheck);

            if ($rowCheck['total'] > 0) {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['error_servidor'] = "El alias '$alias' ya está en uso por otro servidor. Elige otro.";
                return false;
            }
        }

        // 3. Proceder con la actualización
        if ($tokenHash) {
            $sql = "UPDATE servidor SET alias = ?, ip = ?, dominio = ?, token = ? WHERE id = ?";
            $stmt = mysqli_prepare($conexion, $sql);
            if (!$stmt) {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['error_servidor'] = "Error en la consulta: " . mysqli_error($conexion);
                return false;
            }
            mysqli_stmt_bind_param($stmt, "ssssi", $alias, $ip, $dominio, $tokenHash, $id);
        } else {
            $sql = "UPDATE servidor SET alias = ?, ip = ?, dominio = ? WHERE id = ?";
            $stmt = mysqli_prepare($conexion, $sql);
            if (!$stmt) {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['error_servidor'] = "Error en la consulta: " . mysqli_error($conexion);
                return false;
            }
            mysqli_stmt_bind_param($stmt, "sssi", $alias, $ip, $dominio, $id);
        }

        $ok = mysqli_stmt_execute($stmt);
        if (!$ok) {
            $error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error_servidor'] = "Error al actualizar: " . $error;
            return false;
        }

        mysqli_stmt_close($stmt);

        return $ok;
    }

    public static function eliminarServidor(int $idServidor): bool
    {
        global $conexion;

        $sql = "DELETE FROM servidor WHERE id = ?";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, "i", $idServidor);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public static function listarUsuariosDelServidor(int $idServidor): array
    {
        global $conexion;
        $sql = "SELECT c.id, c.usuario, us.rol 
                FROM usuarios_servidor us 
                INNER JOIN cuenta c ON us.id_usuario = c.id 
                WHERE us.id_servidor = ?";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, "i", $idServidor);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_all($res, MYSQLI_ASSOC) ?: [];
    }

    public static function esAdminDelServidor(int $idUsuario, int $idServidor): bool
    {
        global $conexion;
        $sql = "SELECT 1 FROM usuarios_servidor WHERE id_usuario = ? AND id_servidor = ? AND rol = 'admin' LIMIT 1";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $idUsuario, $idServidor);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $esAdmin = mysqli_num_rows($res) > 0;
        mysqli_stmt_close($stmt);
        return $esAdmin;
    }

    public static function esDuenoDelServidor(int $idUsuario, int $idServidor): bool
    {
        global $conexion;
        $sql = "SELECT 1 FROM servidor WHERE id = ? AND dueno_id = ? LIMIT 1";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $idServidor, $idUsuario);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $esDueno = mysqli_num_rows($res) > 0;
        mysqli_stmt_close($stmt);
        return $esDueno;
    }

    public static function agregarUsuarioAServidor(int $idServidor, int $idUsuario, string $rol = 'user'): bool
    {
        global $conexion;
        $sql = "INSERT IGNORE INTO usuarios_servidor (id_servidor, id_usuario, rol) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, "iis", $idServidor, $idUsuario, $rol);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public static function promoverUsuarioAServidor(int $idServidor, int $idUsuario): bool
    {
        global $conexion;
        $sql = "UPDATE usuarios_servidor SET rol = 'admin' WHERE id_servidor = ? AND id_usuario = ?";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $idServidor, $idUsuario);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public static function eliminarUsuarioDelServidor(int $idServidor, int $idUsuario): bool
    {
        global $conexion;
        
        $sqlDueño = "SELECT 1 FROM servidor WHERE id = ? AND dueno_id = ? LIMIT 1";
        $stmtVal = mysqli_prepare($conexion, $sqlDueño);
        mysqli_stmt_bind_param($stmtVal, "ii", $idServidor, $idUsuario);
        mysqli_stmt_execute($stmtVal);
        $resDueño = mysqli_stmt_get_result($stmtVal);
        if (mysqli_num_rows($resDueño) > 0) {
            mysqli_stmt_close($stmtVal);
            return false; 
        }
        mysqli_stmt_close($stmtVal);


        $sql = "DELETE FROM usuarios_servidor WHERE id_servidor = ? AND id_usuario = ?";
        
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $idServidor, $idUsuario);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    public static function degradarUsuarioAServidor(int $idServidor, int $idUsuario): bool
    {
        global $conexion;

        $sqlDueño = "SELECT 1 FROM servidor WHERE id = ? AND dueno_id = ? LIMIT 1";
        $stmtVal = mysqli_prepare($conexion, $sqlDueño);
        mysqli_stmt_bind_param($stmtVal, "ii", $idServidor, $idUsuario);
        mysqli_stmt_execute($stmtVal);
        $res = mysqli_stmt_get_result($stmtVal);
        if (mysqli_num_rows($res) > 0) {
            mysqli_stmt_close($stmtVal);
            return false;
        }
        mysqli_stmt_close($stmtVal);

        $sql = "UPDATE usuarios_servidor SET rol = 'user' WHERE id_servidor = ? AND id_usuario = ?";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $idServidor, $idUsuario);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }
}
