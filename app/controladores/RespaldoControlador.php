<?php
declare(strict_types=1);

class RespaldoControlador
{

    private function generarSQL(): string
    {
        require_once __DIR__ . '/../core/conexion.php';
        global $conexion;

        $sql = "-- Respaldo de Base de Datos Monitor\n";
        $sql .= "-- Generado el: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tablas = [];
        $result = mysqli_query($conexion, "SHOW TABLES");
        while ($row = mysqli_fetch_row($result)) {
            $tablas[] = $row[0];
        }

        foreach ($tablas as $tabla) {
            $res = mysqli_query($conexion, "SHOW CREATE TABLE $tabla");
            $row = mysqli_fetch_row($res);
            $sql .= "\n-- Estructura de tabla: $tabla\n";
            $sql .= "DROP TABLE IF EXISTS `$tabla`;\n";
            $sql .= $row[1] . ";\n\n";

            // Datos de la tabla
            $res = mysqli_query($conexion, "SELECT * FROM $tabla");
            while ($row = mysqli_fetch_assoc($res)) {
                $columnas = array_keys($row);
                $valores = array_map(function($v) use ($conexion) {
                    if ($v === null) return 'NULL';
                    return "'" . mysqli_real_escape_string($conexion, (string)$v) . "'";
                }, array_values($row));

                $sql .= "INSERT INTO `$tabla` (`" . implode("`, `", $columnas) . "`) VALUES (" . implode(", ", $valores) . ");\n";
            }
            $sql .= "\n";
        }

        $sql .= "\nSET FOREIGN_KEY_CHECKS=1;";
        return $sql;
    }

    public function enviarTelegram(): void
    {
        if (php_sapi_name() !== 'cli') {
            if (session_status() === PHP_SESSION_NONE) session_start();

            if (empty($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
                header('Location: ' . BASE_URL . '/dashboard');
                exit;
            }
        }

        $sql = $this->generarSQL();
        $fecha = date('d-m-Y_H-i-s');
        $nombreArchivo = "respaldo_{$fecha}.sql";
        $rutaTemporal = sys_get_temp_dir() . '/' . $nombreArchivo;

        file_put_contents($rutaTemporal, $sql);

        // Envío a Telegram
        $token = TELEGRAM_BOT_TOKEN;
        $chatId = TELEGRAM_CHAT_ID;
        $url = "https://api.telegram.org/bot{$token}/sendDocument";

        $postFields = [
            'chat_id'  => $chatId,
            'caption'  => "📦 Respaldo automático de base de datos - Monitor\n📅 Fecha: " . date('d/m/Y H:i:s'),
            'document' => new CURLFile($rutaTemporal)
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        
        $result = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        unlink($rutaTemporal);

        if ($result === false) {
            throw new Exception("Error de CURL: " . $err);
        }

        if ($httpCode !== 200) {
            throw new Exception("Error de Telegram API (HTTP $httpCode): " . $result);
        }

        if (php_sapi_name() !== 'cli') {
            $_SESSION['ok_servidor'] = "Respaldo enviado a Telegram correctamente.";
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }


    public function descargar(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (empty($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $sql = $this->generarSQL();
        $fecha = date('Y-m-d_H-i-s');
        $nombreArchivo = "respaldo_monitor_{$fecha}.sql";

        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"{$nombreArchivo}\"");

        echo $sql;
        exit;
    }
}
