<?php
require_once __DIR__ . '/../core/conexion.php';


class CuentaModelo {
  public function obtenerPorUsuario($usuario) {
    global $conexion;

    $sql = "SELECT * FROM cuenta WHERE usuario = ? LIMIT 1";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) return false;

    mysqli_stmt_bind_param($stmt, "s", $usuario);
    mysqli_stmt_execute($stmt);

    $res = mysqli_stmt_get_result($stmt);
    $fila = $res ? mysqli_fetch_assoc($res) : false;

    mysqli_stmt_close($stmt);
    return $fila ?: false;
  }

  public function listarUsuarios() {
    global $conexion;

    $sql = "SELECT id, usuario, rol, fecha_registro FROM cuenta";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) return false;

    mysqli_stmt_execute($stmt);

    $res = mysqli_stmt_get_result($stmt);
    if (!$res) return false;

    $usuarios = mysqli_fetch_all($res, MYSQLI_ASSOC);

    mysqli_stmt_close($stmt);
    return $usuarios;
  }


  public function registrarUsuario(string $usuario, string $hash, string $rol) {
    global $conexion;

    mysqli_begin_transaction($conexion);

        try {
            $sql = "INSERT INTO cuenta (usuario, contrasenia, rol) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conexion, $sql);
            if (!$stmt) {
                throw new Exception("PREPARE: " . mysqli_error($conexion));
            }

            mysqli_stmt_bind_param($stmt, "sss", $usuario, $hash, $rol);

            if (!mysqli_stmt_execute($stmt)) {
                $errorMsg = mysqli_stmt_error($stmt);
                $errno = mysqli_stmt_errno($stmt);
                
                // 1062 es el código estándar de MySQL para entrada duplicada
                if ($errno === 1062 || stripos($errorMsg, 'Duplicate entry') !== false) {
                    throw new Exception("El nombre de usuario '{$usuario}' ya está en uso. Por favor elige otro.");
                }
                throw new Exception("Error al registrar: " . $errorMsg);
            }

            $id = mysqli_insert_id($conexion);

            mysqli_stmt_close($stmt);
            mysqli_commit($conexion);

            return $id; 
        } catch (Exception $e) {
            mysqli_rollback($conexion);

            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error_servidor'] = $e->getMessage();

            return false;
        }
    }


    public function esAdminPorId(int $id): bool {
      global $conexion;
    
      $sql = "SELECT rol FROM cuenta WHERE id = ? LIMIT 1";
      $stmt = mysqli_prepare($conexion, $sql);
      if (!$stmt) return false;
    
      mysqli_stmt_bind_param($stmt, "i", $id);
      mysqli_stmt_execute($stmt);
    
      $res = mysqli_stmt_get_result($stmt);
      $row = $res ? mysqli_fetch_assoc($res) : null;
    
      mysqli_stmt_close($stmt);
    
      return $row && strtolower($row['rol']) === 'admin';
    }


    public function editarUsuario(int $id, string $usuario, string $rol, ?string $contrasenia): bool {
      global $conexion;
  
      if ($contrasenia !== null) {
          $sql = "UPDATE cuenta 
                  SET usuario = ?, rol = ?, contrasenia = ?
                  WHERE id = ?";
          $stmt = mysqli_prepare($conexion, $sql);
          if (!$stmt) return false;
          mysqli_stmt_bind_param($stmt, "sssi", $usuario, $rol, $contrasenia, $id);
      } else {
          $sql = "UPDATE cuenta 
                  SET usuario = ?, rol = ?
                  WHERE id = ?";
          $stmt = mysqli_prepare($conexion, $sql);
          if (!$stmt) return false;
          mysqli_stmt_bind_param($stmt, "ssi", $usuario, $rol, $id);
      }
  
      $ok = mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
      return $ok;
  }

  public function eliminarUsuario(int $id): bool {
      global $conexion;

      $sql = "DELETE FROM cuenta WHERE id = ?";
      $stmt = mysqli_prepare($conexion, $sql);
      if (!$stmt) return false;

      mysqli_stmt_bind_param($stmt, "i", $id);
      $ok = mysqli_stmt_execute($stmt);

      mysqli_stmt_close($stmt);
      return $ok;
  }



  public function buscarUsuarios(string $q, int $limit = 10): array {
    global $conexion;

    $q = trim($q);
    if ($q === '') return [];

    $sql = "SELECT id, usuario 
            FROM cuenta
            WHERE usuario LIKE ?
            ORDER BY usuario
            LIMIT ?";

    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) return [];

    $like = "%{$q}%";
    mysqli_stmt_bind_param($stmt, "si", $like, $limit);
    mysqli_stmt_execute($stmt);

    $res = mysqli_stmt_get_result($stmt);
    $rows = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];

    mysqli_stmt_close($stmt);
    return $rows;
  }

  public function ListarTodosUsuarios() {
    global $conexion;
  
    $sql = "SELECT id, usuario FROM cuenta ORDER BY usuario ASC";
    $stmt = mysqli_prepare($conexion, $sql);
    if (!$stmt) return false;
  
    mysqli_stmt_execute($stmt);
  
    $res = mysqli_stmt_get_result($stmt);
    if (!$res) {
      mysqli_stmt_close($stmt);
      return false;
    }
  
    $rows = mysqli_fetch_all($res, MYSQLI_ASSOC);
  
    mysqli_stmt_close($stmt);
    return $rows;
  } 
}