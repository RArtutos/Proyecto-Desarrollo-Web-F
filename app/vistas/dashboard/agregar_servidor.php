<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuario = $_SESSION['usuario'] ?? null;
$id = $_SESSION['id'] ?? null;
if (!$usuario) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

require_once __DIR__ . '/../../modelos/CuentaModelo.php'; 
$model = new CuentaModelo();

if (!$model->esAdminPorId((int)$id)) {
    header('Location: ' . BASE_URL . '/');
    exit;
}
$mensajeOk = $_SESSION['ok_servidor'] ?? null;
$mensajeError = $_SESSION['error_servidor'] ?? null;
$tokenCreado = $_SESSION['token_servidor_creado'] ?? null;

unset($_SESSION['ok_servidor'], $_SESSION['error_servidor'], $_SESSION['token_servidor_creado']);
?>
<!doctype html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agregar servidor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-dark text-white d-flex flex-column" style="min-height: 100vh;">

    <header class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center">
        <p class="mb-0 text-white-50">Por favor llene los siguientes campos con la información del servidor</p>
        <a href="<?= BASE_URL ?>/dashboard" class="btn btn-outline-secondary" tabindex="5" aria-label="Regresa a la pagina principal">Regresar</a>
    </header>

    <main class="container py-5 flex-grow-1">

        <h1 class="mb-4 text-white">
            Nuevo servidor
        </h1>

        <?php if ($mensajeError): ?>
            <div class="alert alert-danger border border-danger-subtle" role="alert">
                <?= htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($mensajeOk): ?>
            <div class="alert alert-success border border-success-subtle" role="alert">
                <?= htmlspecialchars($mensajeOk, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($tokenCreado): ?>
            <?php 
                $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                           || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
                $protocol = $isHttps ? "https://" : "http://";
                $host = $_SERVER['HTTP_HOST'];
                $apiUrl = $protocol . $host . BASE_URL;
                $aliasCreado = $_SESSION['alias_servidor_creado'] ?? 'servidor_alias';
            ?>
            <div class="alert alert-warning border border-warning-subtle" role="alert">
                <div class="fw-bold mb-3">Datos para el Instalador del Demonio:</div>
                
                <div class="mb-3">
                    <label class="small text-uppercase fw-bold">1. URL del Servidor Central:</label>
                    <div class="d-flex gap-2">
                        <code class="p-2 border border-secondary rounded text-bg-dark flex-grow-1"><?= htmlspecialchars($apiUrl) ?></code>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="small text-uppercase fw-bold">2. Alias del Servidor:</label>
                    <div class="d-flex gap-2">
                        <code class="p-2 border border-secondary rounded text-bg-dark flex-grow-1"><?= htmlspecialchars($aliasCreado) ?></code>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="small text-uppercase fw-bold">3. Token del Servidor:</label>
                    <div class="d-flex gap-2">
                        <code class="p-2 border border-secondary rounded text-bg-dark flex-grow-1"><?= htmlspecialchars($tokenCreado) ?></code>
                    </div>
                </div>

                <hr class="border-secondary">

                <div class="mt-2">
                    <p class="fw-bold">Opción A: Instalación Automática (Recomendada)</p>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control form-control-sm text-bg-dark border-secondary" readonly 
                               value="curl -sSf <?= $apiUrl ?>/instaladorDemonio.sh | sudo bash -s -- '<?= $aliasCreado ?>' '<?= $tokenCreado ?>' '<?= $apiUrl ?>'">
                        <button class="btn btn-outline-secondary btn-sm" onclick="navigator.clipboard.writeText(this.previousElementSibling.value)">Copiar</button>
                    </div>

                    <p class="fw-bold">Opción B: Instalación Manual</p>
                    <p class="small text-white-50">Descarga el archivo <code>instaladorDemonio.sh</code>, dale permisos de ejecución y sigue las instrucciones en pantalla.</p>
                </div>
            </div>
            <?php unset($_SESSION['alias_servidor_creado']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-6">

            <form action="<?= BASE_URL ?>/add_servidor" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="mb-4">
                    <label for="alias" class="form-label">Alias</label>
                    <input type="text" id="alias" name="alias"
                        class="form-control form-control-lg text-bg-dark border-secondary"
                        maxlength="20" required tabindex="1">
                </div>

                <div class="mb-4">
                    <label for="ip" class="form-label">Dirección IP</label>
                    <input type="text" id="ip" name="ip"
                        class="form-control form-control-lg text-bg-dark border-secondary"
                        maxlength="15" required tabindex="2">
                </div>

                <div class="mb-5">
                    <label for="dominio" class="form-label">Dominio (opcional)</label>
                    <input type="text" id="dominio" name="dominio"
                        class="form-control form-control-lg text-bg-dark border-secondary"
                        maxlength="50" tabindex="3">
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100" tabindex="4" aria-label="Guarda los datos del servidor">Guardar</button>
            </form>

            </div>
        </div>
    </main>

    <footer class="text-center py-3 border-top border-secondary">
        <p class="m-0 text-white-50">Copyright © <?php echo date('Y'); ?>, diseñado por EuroTech</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
