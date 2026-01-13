<?php
require_once __DIR__ . '/../modelos/CuentaModelo.php';

class UsuarioControlador {

  public function listarUsuarios(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $idUsuario = (int)($_SESSION['id'] ?? 0);

        if ($idUsuario <= 0) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $modelCuenta = new CuentaModelo();
        
        if (!$modelCuenta->esAdminPorId((int)$idUsuario)) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        
        $usuarios = $modelCuenta->listarUsuarios();
        require APP_PATH . '/vistas/dashboard/listar_usuarios.php';

    }

    public function editarUsuario(): void {
        $id         = (int)($_POST['id'] ?? 0);
        $usuario    = trim($_POST['usuario'] ?? '');
        $rol        = $_POST['rol'] ?? 'usuario';
        $contrasenia= $_POST['contrasenia'] ?? '';

        if ($id <= 0 || $usuario === '') {
            $_SESSION['error_servidor'] = 'Datos inválidos';
            header('Location: ' . BASE_URL . '/list_usuarios');
            exit;
        }

        $hash = !empty($contrasenia) ? password_hash($contrasenia, PASSWORD_ARGON2ID) : null;

        $model = new CuentaModelo();
        $ok = $model->editarUsuario($id, $usuario, $rol, $hash);

        $_SESSION[$ok ? 'ok_servidor' : 'error_servidor']
            = $ok ? 'Usuario actualizado correctamente' : 'Error al actualizar';

        header('Location: ' . BASE_URL . '/list_usuarios');
        exit;
    }

    public function eliminarUsuario(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (
            empty($_SESSION['csrf_token']) ||
            empty($_POST['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            $_SESSION['error_servidor'] = 'Token CSRF inválido';
            header('Location: ' . BASE_URL . '/list_usuarios');
            exit;
        }

        if (empty($_SESSION['id']) || $_SESSION['rol'] !== 'admin') {
            $_SESSION['error_servidor'] = 'No autorizado';
            header('Location: ' . BASE_URL . '/list_usuarios');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['error_servidor'] = 'ID inválido';
            header('Location: ' . BASE_URL . '/list_usuarios');
            exit;
        }

        if ($id === (int)$_SESSION['id']) {
            $_SESSION['error_servidor'] = 'No puedes eliminar tu propio usuario';
            header('Location: ' . BASE_URL . '/list_usuarios');
            exit;
        }

        $model = new CuentaModelo();
        $ok = $model->eliminarUsuario($id);

        $_SESSION[$ok ? 'ok_servidor' : 'error_servidor'] =
            $ok ? 'Usuario eliminado correctamente' : 'Error al eliminar usuario';

        header('Location: ' . BASE_URL . '/list_usuarios');
        exit;
    }

    public function usuariosActivos() {
        $model = new CuentaModelo();
        echo json_encode($model->listarUsuariosActivos());
        exit;
    }
    
    public function toggleUsuario() {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)$data['id'];
        $accion = $data['accion'];
    
        $model = new CuentaModelo();
    
        if ($accion === 'add') {
            $model->activarUsuario($id);
        } else {
            $model->desactivarUsuario($id);
        }
    
        echo json_encode(['ok' => true]);
        exit;
    }



    public function buscarUsuario(string $usuario): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $idUsuario = (int)($_SESSION['id'] ?? 0);

        if ($idUsuario <= 0) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $modelCuenta = new CuentaModelo();
        
        if (!$modelCuenta->esAdminPorId((int)$idUsuario)) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        
        $usuarios = $modelCuenta->listarUsuarios();
        require APP_PATH . '/vistas/dashboard/listar_usuarios.php';

    }


    public function apiListarUsuarios() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['usuario'])) {
          http_response_code(401);
          header('Content-Type: application/json; charset=utf-8');
          echo json_encode(['ok' => false, 'error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
          exit;
        }
        $modelCuenta = new CuentaModelo();
        $usuarios = $modelCuenta->ListarTodosUsuarios();
        if ($usuarios === false) $usuarios = [];
      
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'usuarios' => $usuarios], JSON_UNESCAPED_UNICODE);
        exit;
      }
      
}
