<?php


define('APP_PATH', __DIR__ . '/app');
if (!defined('BASE_URL')) define('BASE_URL', '');

// Habilitar errores para depuración
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Cargar configuración y conexión de forma global (como lo hace index.php)
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/conexion.php';
require_once __DIR__ . '/app/controladores/RespaldoControlador.php';

if (!defined('TELEGRAM_BOT_TOKEN') || !defined('TELEGRAM_CHAT_ID')) {
    die("[" . date('Y-m-d H:i:s') . "] ERROR: No se han configurado las constantes de Telegram en config.php\n");
}

try {
    echo "[" . date('Y-m-d H:i:s') . "] Iniciando respaldo...\n";
    $respaldo = new RespaldoControlador();
    $respaldo->enviarTelegram();
    echo "[" . date('Y-m-d H:i:s') . "] Respaldo enviado exitosamente.\n";
} catch (Throwable $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . " en línea " . $e->getLine() . "\n";
}

