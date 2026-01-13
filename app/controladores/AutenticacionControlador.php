<?php
declare(strict_types=1);

class AutenticacionControlador
{
  public function mostrarLogin(): void
  {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $error = $_SESSION['login_error'] ?? null;
    unset($_SESSION['login_error']);

    require __DIR__ . '/../vistas/auth/login.php';
  }

  public function login(): void
  {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
      header("Location: " . BASE_URL . "/login");
      exit;
    }

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $_SESSION['login_error'] = 'Sesión expirada o intento de CSRF bloqueado.';
        header("Location: " . BASE_URL . "/login");
        exit;
    }

    $usuario    = trim($_POST['usuario'] ?? '');
    $contrasenia = $_POST['contrasenia'] ?? '';

    $modelo = new CuentaModelo();
    $user  = $modelo->obtenerPorUsuario($usuario);

    if ($user && !empty($user['contrasenia']) && password_verify($contrasenia, $user['contrasenia'])) {
      session_regenerate_id(true);

      $_SESSION['usuario'] = $user['usuario'] ?? $usuario;
      $_SESSION['nombre']  = $user['nombre'] ?? $usuario;
      $_SESSION['id'] = $user['id'] ?? null;
      $_SESSION['rol'] = strtolower($user['rol'] ?? 'user');
      
      if (empty($_SESSION['csrf_token'])) {
          $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      }
      
      header("Location: " . BASE_URL . "/dashboard");
      exit;
    }

    $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
    header("Location: " . BASE_URL . "/login");
    exit;
  }

  public function logout(): void
  {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
      $p = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool)($p['secure'] ?? false), (bool)($p['httponly'] ?? true));
    }
    session_destroy();

    header("Location: " . BASE_URL . "/login");
    exit;
  }

  public function registrar(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();

    // Validación CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Error de seguridad: Token CSRF no válido.';
        header('Location: ' . BASE_URL . '/add_usuario');
        exit;
    }

    $usuario = trim($_POST['usuario'] ?? '');
    $contrasenia = trim($_POST['contrasenia'] ?? '');
    $rol = trim($_POST['rol'] ?? '');

    if ($usuario === '' || $contrasenia === '' || $rol === '') {
      $_SESSION['error'] = 'Todos los campos son obligatorios.';
      header('Location: ' . BASE_URL . '/add_usuario');
      exit;
    }

    $hash = password_hash($contrasenia, PASSWORD_ARGON2ID);

    $modelo = new CuentaModelo();
    $id = $modelo->registrarUsuario($usuario, $hash, $rol);

    if ($id) {
      header('Location: ' . BASE_URL . '/dashboard/');
      exit;
    }

    $_SESSION['error'] = 'Error al insertar el registro. Revisa logs.';
    header('Location: ' . BASE_URL . '/add_usuario');
    exit;
  }
}
