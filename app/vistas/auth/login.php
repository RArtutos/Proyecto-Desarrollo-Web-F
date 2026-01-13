<?php $sid = session_id(); ?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Pantalla de inicio de sesión</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
      crossorigin="anonymous"
    />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"
    />
  </head>

  <body class="bg-dark text-white d-flex flex-column" style="min-height: 100vh">
    <header class="p-3"></header>

    <main class="d-flex align-items-center justify-content-center flex-grow-1">
      <div class="card bg-secondary p-4 shadow-lg" style="width: 350px">
        <div class="card-body text-center">

          <img
            src="<?= BASE_URL ?>/img/logo.png"
            alt="logo de EuroTech"
            width="64"
            height="64"
            class="mb-3"
          />

          <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2">
              <?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="<?= BASE_URL ?>/login">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <div class="mb-3">
              <label for="usuario" class="form-label d-block text-start">Usuario</label>
              <input
                type="text"
                id="usuario"
                name="usuario"
                placeholder="Usuario"
                class="form-control text-bg-dark border-secondary"
                tabindex="1"
                required
              />
            </div>

            <div class="mb-4">
              <label for="contrasena" class="form-label d-block text-start">Contrase&ntilde;a</label>
              <div class="input-group">
                <input
                  type="password"
                  id="contrasenia"
                  name="contrasenia"
                  class="form-control text-bg-dark border-secondary"
                  tabindex="2"
                  required
                />
                <button class="btn btn-outline-light" type="button" id="togglePass" tabindex="3">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>

            <button type="submit" class="btn btn-primary w-100" tabindex="4" aria-label="Boton para ingresar al dashboard">Entrar</button>
          </form>
        </div>
      </div>
    </main>

    <footer class="text-center py-3 border-top border-secondary mt-auto">
      <p class="m-0 text-white-50">Copyright © <?php echo date('Y'); ?>, diseñado por EuroTech</p>
    </footer>
    <script src="<?= BASE_URL ?>/JS/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
  </body>
</html>
