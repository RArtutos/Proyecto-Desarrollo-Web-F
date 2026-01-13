<!doctype html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Agregar usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-dark text-white d-flex flex-column" style="min-height: 100vh;">

    <header class="p-3 border-bottom border-secondary d-flex justify-content-end">
        <a href="<?= BASE_URL ?>/list_usuarios" class="btn btn-outline-secondary" tabindex="6" aria-label="Regresa a la pagina principal">Regresar</a>
    </header>

    <main class="container py-5 flex-grow-1">

        <h1 class="mb-5 text-white">Nuevo usuario</h1>

        <?php if (!empty($_SESSION['error_servidor'])): ?>
            <div class="alert alert-danger mb-4">
                <?= htmlspecialchars($_SESSION['error_servidor']) ?>
            </div>
            <?php unset($_SESSION['error_servidor']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['ok_servidor'])): ?>
            <div class="alert alert-success mb-4">
                <?= htmlspecialchars($_SESSION['ok_servidor']) ?>
            </div>
            <?php unset($_SESSION['ok_servidor']); ?>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/add_usuario" method="post">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="usuario" class="form-label">Nombre de usuario</label>
                    <input type="text" id="usuario" name="usuario"
                           class="form-control form-control-lg text-bg-dark border-secondary"
                           required
                           tabindex="1">
                </div>
                <div class="col-md-6">
                    <label for="password" class="form-label">Contrase&ntilde;a</label>
                    <input type="password" id="contrasenia" name="contrasenia"
                           class="form-control form-control-lg text-bg-dark border-secondary"
                           required
                           tabindex="2">
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6">
                    <label for="rol" class="form-label">Rol</label>
                    <select id="rol" name="rol"
                            class="form-select form-select-lg text-bg-dark border-secondary"
                            required
                            tabindex="3">
                        <option value="user">Usuario</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="confirmar" class="form-label">Confirmar contrase&ntilde;a</label>
                    <input type="password" id="confirmar" name="confirmar"
                           class="form-control form-control-lg text-bg-dark border-secondary"
                           required
                           tabindex="4">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-50" tabindex="5" aria-label="Da de alta el usuario">Crear usuario</button>

        </form>

    </main>

    <footer class="text-center py-3 border-top border-secondary">
        <p class="m-0 text-white-50">Copyright © <?php echo date('Y'); ?>, diseñado por EuroTech</p>
    </footer>
    <script src="<?= BASE_URL ?>/JS/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
