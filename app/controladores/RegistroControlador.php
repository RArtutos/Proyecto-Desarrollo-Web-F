<?php
declare(strict_types=1);

require_once __DIR__ . '/../modelos/CuentaModelo.php';

class RegistroControlador
{
    public function registrar(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Validación CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['error_servidor'] = 'Error de seguridad: Token CSRF no válido.';
            header('Location: ' . BASE_URL . '/add_usuario');
            exit;
        }

        $usuario = trim($_POST['usuario'] ?? '');
        $contrasenia = trim($_POST['contrasenia'] ?? '');
        $rol = trim($_POST['rol'] ?? 'usuario');

        if ($usuario === '' || $contrasenia === '' || $rol === '') {
            $_SESSION['error_servidor'] = 'Todos los campos son obligatorios.';
            header('Location: ' . BASE_URL . '/add_usuario');
            exit;
        }

        $hash = password_hash($contrasenia, PASSWORD_ARGON2ID);

        $modelo = new CuentaModelo();
        $id = $modelo->registrarUsuario($usuario, $hash, $rol);

        if ($id) {
            $_SESSION['ok_servidor'] = "Usuario registrado correctamente.";
            header('Location: ' . BASE_URL . '/list_usuarios');
            exit;
        }

        // Si falló y no hay mensaje específico del modelo, ponemos uno genérico
        if (!isset($_SESSION['error_servidor'])) {
            $_SESSION['error_servidor'] = 'Error al insertar el registro.';
        }
        
        header('Location: ' . BASE_URL . '/add_usuario');
        exit;
    }
}
