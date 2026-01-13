<?php
declare(strict_types=1);

require_once __DIR__ . '/../modelos/ServidorModelo.php';

class ServidorApiControlador
{
    private function json(int $status, array $data): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    private function obtenerIpCliente(): string
    {
        $xReal = trim($_SERVER['HTTP_X_REAL_IP'] ?? '');
        if ($xReal !== '') return $xReal;

        $xff = trim($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($xff !== '') {
            $parts = array_map('trim', explode(',', $xff));
            if (!empty($parts[0])) return $parts[0];
        }

        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    public function keepalive(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->json(405, ['ok' => false, 'error' => 'Método no permitido']);
        }

        $token = trim($_POST['token'] ?? '');
        $alias = trim($_POST['alias'] ?? '');
        $ip = $this->obtenerIpCliente();

        if ($token === '' || $alias === '') {
            $this->json(400, ['ok' => false, 'error' => 'token y alias son obligatorios']);
        }

        if (!ServidorModelo::validarTokenPorAlias($alias, $token, $ip)) {
            $this->json(401, ['ok' => false, 'error' => 'No autorizado']);
        }

        if (!ServidorModelo::marcarActivoPorAlias($alias)) {
            $this->json(500, ['ok' => false, 'error' => 'No se pudo actualizar el estado']);
        }

        $this->json(200, ['ok' => true, 'estado' => 'ENCENDIDO']);
    }

    public function shutdown(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->json(405, ['ok' => false, 'error' => 'Método no permitido']);
        }

        $token = trim($_POST['token'] ?? '');
        $alias = trim($_POST['alias'] ?? '');
        $ip = $this->obtenerIpCliente();

        if ($token === '' || $alias === '') {
            $this->json(400, ['ok' => false, 'error' => 'token y alias son obligatorios']);
        }

        if (!ServidorModelo::validarTokenPorAlias($alias, $token, $ip)) {
            $this->json(401, ['ok' => false, 'error' => 'No autorizado']);
        }

        if (!ServidorModelo::marcarApagadoPorAlias($alias)) {
            $this->json(500, ['ok' => false, 'error' => 'No se pudo actualizar el estado']);
        }

        $this->json(200, ['ok' => true, 'estado' => 'APAGADO']);
    }
}
