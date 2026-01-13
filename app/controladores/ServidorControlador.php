<?php
require_once __DIR__ . '/../modelos/ServidorModelo.php';
require_once __DIR__ . '/../modelos/CuentaModelo.php';

class ServidorControlador
{
    public function guardar()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Validación CSRF
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error_servidor'] = 'Error de seguridad: Token CSRF no válido.';
                header('Location: ' . BASE_URL . '/add_servidor');
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $alias   = trim($_POST['alias'] ?? '');
        $ip      = trim($_POST['ip'] ?? '');
        $dominio = trim($_POST['dominio'] ?? '');

        $id = (int)($_SESSION['id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['error_servidor'] = 'No hay sesión válida (id de usuario no encontrado).';
            header('Location: ' . BASE_URL . '/add_servidor');
            exit;
        }

        $modelCuenta = new CuentaModelo();
        
        if (!$modelCuenta->esAdminPorId((int)$id)) {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        if ($alias === '' || $ip === '') {
            $_SESSION['error_servidor'] = 'Alias e IP son obligatorios.';
            header('Location: ' . BASE_URL . '/add_servidor');
            exit;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $_SESSION['error_servidor'] = 'La IP no es válida (solo IPv4).';
            header('Location: ' . BASE_URL . '/add_servidor');
            exit;
        }

        $dominio = ($dominio === '') ? null : $dominio;

        $tokenPlano = bin2hex(random_bytes(32));
        $tokenHash  = password_hash($tokenPlano, PASSWORD_ARGON2ID);

        $ok = ServidorModelo::agregarServidor($alias, $ip, $dominio, $id, $tokenHash);

        if (!$ok) {
            header('Location: ' . BASE_URL . '/add_servidor');
            exit;
        }

        $_SESSION['ok_servidor'] = 'Servidor agregado. Copia el token y guárdalo en el demonio del servidor.';
        $_SESSION['token_servidor_creado'] = $tokenPlano;
        $_SESSION['alias_servidor_creado'] = $alias; // Guardar el alias para mostrarlo en la vista

        header('Location: ' . BASE_URL . '/add_servidor');
        exit;
    }

    public function editar()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Validación CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['error_dashboard'] = 'Error de seguridad: Token CSRF no válido.';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $idAdmin = (int)($_SESSION['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $idServidor = (int)($_POST['id'] ?? 0);
        $alias      = trim($_POST['alias'] ?? '');
        $ip         = trim($_POST['ip'] ?? '');
        $dominio    = trim($_POST['dominio'] ?? '');

        if ($idServidor <= 0 || $alias === '' || $ip === '') {
            $_SESSION['error_dashboard'] = 'Datos incompletos para editar.';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        if (!ServidorModelo::esAdminDelServidor($idAdmin, $idServidor)) {
            $_SESSION['error_dashboard'] = 'No tienes permisos para editar este servidor.';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $_SESSION['error_dashboard'] = 'La IP no es válida.';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }


        global $conexion;
        $sqlActual = "SELECT alias, ip FROM servidor WHERE id = ? LIMIT 1";
        $stmtActual = mysqli_prepare($conexion, $sqlActual);
        mysqli_stmt_bind_param($stmtActual, "i", $idServidor);
        mysqli_stmt_execute($stmtActual);
        $resActual = mysqli_stmt_get_result($stmtActual);
        $actual = mysqli_fetch_assoc($resActual);
        mysqli_stmt_close($stmtActual);

        $cambioCritico = false;
        $tokenHash = null;
        $tokenPlano = null;

        if ($actual) {
            // Si el alias o la IP han cambiado, sí es necesario re-instalar (cambio crítico)
            if ($actual['alias'] !== $alias || $actual['ip'] !== $ip) {
                $cambioCritico = true;
            }
        }

        if ($cambioCritico) {
            // Solo generamos nuevo token si hubo cambio de IP o Alias
            $tokenPlano = bin2hex(random_bytes(32));
            $tokenHash  = password_hash($tokenPlano, PASSWORD_ARGON2ID);
        }

        $ok = ServidorModelo::actualizarServidor($idServidor, $alias, $ip, $dominio === '' ? null : $dominio, $tokenHash);

        if ($ok) {
            $_SESSION['ok_dashboard'] = 'Servidor actualizado correctamente.';
            
            if ($cambioCritico) {
                // Solo activamos el recuadro de instalación si se generó un nuevo token
                $_SESSION['token_servidor_creado'] = $tokenPlano;
                $_SESSION['alias_servidor_creado'] = $alias;
            }
        } else {
            // Usar el mensaje específico del modelo si está disponible
            $_SESSION['error_dashboard'] = $_SESSION['error_servidor'] ?? 'Error al actualizar el servidor.';
            unset($_SESSION['error_servidor']);
        }

        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }

    public function eliminar()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Validación CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['error_dashboard'] = 'Error de seguridad: Token CSRF no válido.';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $idAdmin = (int)($_SESSION['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $idServidor = (int)($_POST['id'] ?? 0);

        if ($idServidor <= 0) {
            $_SESSION['error_dashboard'] = 'ID de servidor no válido.';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        // VALIDACIÓN DE DUEÑO: Solo el creador original puede borrarlo
        if (!ServidorModelo::esDuenoDelServidor($idAdmin, $idServidor)) {
            $_SESSION['error_dashboard'] = 'No tienes permisos suficientes. Solo el dueño del servidor puede eliminarlo.';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $ok = ServidorModelo::eliminarServidor($idServidor);

        if ($ok) {
            $_SESSION['ok_dashboard'] = 'Servidor eliminado correctamente.';
        } else {
            $_SESSION['error_dashboard'] = 'Error al eliminar el servidor.';
        }

        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }

    public function dashboard()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $idUsuario = (int)($_SESSION['id'] ?? 0);

        if ($idUsuario <= 0) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        ServidorModelo::marcarIndeterminadoPorUsuario($idUsuario, 60);

        $servidores = ServidorModelo::listarPorUsuario($idUsuario);

        require APP_PATH . '/vistas/dashboard/dashboard.php';
        exit;
    }

    public function apiServidores(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $idUsuario = (int)($_SESSION['id'] ?? 0);

        header('Content-Type: application/json; charset=utf-8');
        if ($idUsuario <= 0) {
            echo json_encode(['ok' => false, 'error' => 'Sesión no válida']);
            exit;
        }

        $servidores = ServidorModelo::listarPorUsuario($idUsuario);
        echo json_encode(['ok' => true, 'servidores' => $servidores]);
        exit;
    }

    public function apiListarUsuariosServidor(int $idServidor): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $idAdmin = (int)($_SESSION['id'] ?? 0);

        header('Content-Type: application/json; charset=utf-8');
        if (!ServidorModelo::esAdminDelServidor($idAdmin, $idServidor)) {
            echo json_encode(['ok' => false, 'error' => 'No tienes permisos']);
            exit;
        }

        $usuarios = ServidorModelo::listarUsuariosDelServidor($idServidor);
        echo json_encode(['ok' => true, 'usuarios' => $usuarios]);
        exit;
    }

    public function apiAgregarUsuarioServidor(int $idServidor): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $idAdmin = (int)($_SESSION['id'] ?? 0);

        header('Content-Type: application/json; charset=utf-8');
        if (!ServidorModelo::esAdminDelServidor($idAdmin, $idServidor)) {
            echo json_encode(['ok' => false, 'error' => 'No tienes permisos']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $idUsuario = (int)($data['idUsuario'] ?? 0);

        if ($idUsuario <= 0) {
            echo json_encode(['ok' => false, 'error' => 'ID de usuario no válido']);
            exit;
        }

        $ok = ServidorModelo::agregarUsuarioAServidor($idServidor, $idUsuario);
        echo json_encode(['ok' => $ok]);
        exit;
    }

    public function apiEliminarUsuarioServidor(int $idServidor): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $idAdmin = (int)($_SESSION['id'] ?? 0);

        header('Content-Type: application/json; charset=utf-8');
        if (!ServidorModelo::esAdminDelServidor($idAdmin, $idServidor)) {
            echo json_encode(['ok' => false, 'error' => 'No tienes permisos']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $idUsuario = (int)($data['idUsuario'] ?? 0);

        if ($idUsuario <= 0) {
            echo json_encode(['ok' => false, 'error' => 'ID de usuario no válido']);
            exit;
        }

        $ok = ServidorModelo::eliminarUsuarioDelServidor($idServidor, $idUsuario);
        echo json_encode(['ok' => $ok]);
        exit;
    }

    public function apiPromoverUsuarioServidor(int $idServidor): void
    {
        if (empty($_SESSION['id'])) {
            $this->json(401, ['ok' => false, 'error' => 'No autorizado']);
        }

        if (!ServidorModelo::esAdminDelServidor((int)$_SESSION['id'], $idServidor)) {
            $this->json(403, ['ok' => false, 'error' => 'No tienes permisos de administrador en este servidor']);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $idUsuario = (int)($input['idUsuario'] ?? 0);

        if ($idUsuario <= 0) {
            $this->json(400, ['ok' => false, 'error' => 'ID de usuario no válido']);
        }

        if (ServidorModelo::promoverUsuarioAServidor($idServidor, $idUsuario)) {
            $this->json(200, ['ok' => true]);
        } else {
            $this->json(500, ['ok' => false, 'error' => 'No se pudo promover al usuario']);
        }
    }

    public function apiDegradarUsuarioServidor(int $idServidor): void
    {
        if (empty($_SESSION['id'])) {
            $this->json(401, ['ok' => false, 'error' => 'No autorizado']);
        }

        if (!ServidorModelo::esAdminDelServidor((int)$_SESSION['id'], $idServidor)) {
            $this->json(403, ['ok' => false, 'error' => 'No tienes permisos de administrador en este servidor']);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $idUsuario = (int)($input['idUsuario'] ?? 0);

        if ($idUsuario <= 0) {
            $this->json(400, ['ok' => false, 'error' => 'ID de usuario no válido']);
        }

        if (ServidorModelo::degradarUsuarioAServidor($idServidor, $idUsuario)) {
            $this->json(200, ['ok' => true]);
        } else {
            $this->json(500, ['ok' => false, 'error' => 'No se pudo degradar al usuario (puede que sea el dueño)']);
        }
    }

    /**
     * Envía una respuesta JSON y finaliza la ejecución.
     */
    private function json(int $status, array $data): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
