<?php
declare(strict_types=1);

class LoginControlador
{
  public function mostrarLogin(): void
  {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    // Si ya existe sesión activa, redirigir al Dashboard directamente
    if (!empty($_SESSION['id'])) {
        header("Location: " . BASE_URL . "/dashboard");
        exit;
    }

    // Generar token CSRF para el formulario de login si no existe
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

    // Validación CSRF
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
      
      header("Location: " . BASE_URL . "/dashboard");
      exit;
    }

    $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
    header("Location: " . BASE_URL . "/login");
    exit;
  }

  public function logout(): void
  {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
      $p = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool)($p['secure'] ?? false), (bool)($p['httponly'] ?? true));
    }
    session_destroy();

    header("Location: " . BASE_URL . "/login");
    exit;
  }
}
