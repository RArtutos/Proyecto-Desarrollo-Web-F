<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="<?= BASE_URL ?>/styles/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <title>Lista de usuarios</title>
</head>

<body class="bg-dark text-white">

<header class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-secondary p-3">
  <div class="container-fluid d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
    <div id="head-logo">
      <h1 class="h3 mb-0 text-white">EuroTech</h1>
    </div>

    <div class="d-flex flex-wrap justify-content-center gap-2">
      <a class="btn btn-sm btn-success" href="<?= BASE_URL ?>/add_usuario">Nuevo Usuario</a>      
      <a href="<?= BASE_URL ?>/dashboard" class="btn btn-sm btn-outline-secondary">Regresar</a>
    </div>
  </div>
</header>

<main class="container-fluid py-4">

  <section class="mb-4">
    <h1 class="h3">Usuarios</h1>
    <p class="text-white-50 small">Gestión de acceso al sistema</p>

    <?php if (!empty($_SESSION['error_servidor'])): ?>
      <div class="alert alert-danger mt-3 small">
        <?= htmlspecialchars($_SESSION['error_servidor']) ?>
      </div>
      <?php unset($_SESSION['error_servidor']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['ok_servidor'])): ?>
      <div class="alert alert-success mt-3 small">
        <?= htmlspecialchars($_SESSION['ok_servidor']) ?>
      </div>
      <?php unset($_SESSION['ok_servidor']); ?>
    <?php endif; ?>
  </section>

  <section>
    <div class="table-responsive">
      <table class="table table-dark table-hover align-middle">
        <thead class="table-secondary">
          <tr>
            <th>Usuario</th>
            <th class="d-none d-sm-table-cell">Rol</th>
            <th class="d-none d-md-table-cell">Registrado</th>
            <th>Acciones</th>
          </tr>
        </thead>

        <tbody class="small">
        <?php if (empty($usuarios)): ?>
          <tr>
            <td colspan="4" class="text-center text-white-50 py-4">
              No hay usuarios registrados
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($usuarios as $u): ?>
            <tr>
              <td>
                <div class="fw-bold"><?= htmlspecialchars($u['usuario']) ?></div>
                <div class="d-sm-none mt-1">
                  <span class="badge bg-primary text-dark" style="font-size: 0.7rem;"><?= htmlspecialchars($u['rol']) ?></span>
                </div>
              </td>

              <td class="d-none d-sm-table-cell">
                <span class="badge bg-primary text-dark">
                  <?= htmlspecialchars($u['rol']) ?>
                </span>
              </td>

              <td class="d-none d-md-table-cell">
                <span class="text-white-50 small">
                  <?= date('d/m/Y', strtotime($u['fecha_registro'])) ?>
                </span>
              </td>

              <td class="text-nowrap">
                <div class="btn-group">
                  <button
                    class="btn btn-sm btn-outline-success px-2"
                    data-bs-toggle="modal"
                    data-bs-target="#modalEditarUsuario"
                    data-id="<?= (int)$u['id'] ?>"
                    data-usuario="<?= htmlspecialchars($u['usuario'], ENT_QUOTES) ?>"
                    data-rol="<?= htmlspecialchars($u['rol'], ENT_QUOTES) ?>"
                    title="Editar detalles del usuario"
                  >
                    <i class="bi bi-pencil"></i>
                  </button>

                  <form
                    method="POST"
                    action="<?= BASE_URL ?>/delete_usuario"
                    class="d-inline"
                    onsubmit="return confirm('¿Eliminar usuario?');"
                  >
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                    <button type="submit" class="btn btn-sm btn-outline-danger px-2" title="Eliminar usuario definitivamente">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" action="<?= BASE_URL ?>/edit_usuario" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Editar usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="id" id="edit-id">

        <div class="mb-3">
          <label class="form-label">Usuario</label>
          <input type="text" class="form-control" name="usuario" id="edit-usuario" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Rol</label>
          <select class="form-select" name="rol" id="edit-rol">
            <option value="admin">Admin</option>
            <option value="user">Usuario</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Nueva Contrase&ntilde;a</label>
          <input type="password" class="form-control" name="contrasenia" id="contrasenia">
          <small class="text-white-50">Dejar en blanco para no cambiar</small>
        </div>

        <div class="mb-3">
          <label class="form-label">Confirmar Contrase&ntilde;a</label>
          <input type="password" class="form-control" name="confirmar" id="confirmar">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success">Guardar cambios</button>
      </div>

    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('modalEditarUsuario');

  modal.addEventListener('show.bs.modal', event => {
    const btn = event.relatedTarget;

    document.getElementById('edit-id').value = btn.dataset.id;
    document.getElementById('edit-usuario').value = btn.dataset.usuario;
    document.getElementById('edit-rol').value = btn.dataset.rol;
  });
});
</script>

<script src="<?= BASE_URL ?>/JS/script.js"></script>

</body>
</html>