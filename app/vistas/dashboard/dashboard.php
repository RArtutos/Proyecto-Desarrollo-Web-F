<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/styles/style.css" />
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
    crossorigin="anonymous"
  />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
  <script>const BASE_URL = '<?= BASE_URL ?>';</script>
  <title>Lista de servidores</title>
</head>

<body class="bg-dark text-white">
  <header class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-secondary p-3">
    <div class="container-fluid d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
      <div id="head-logo">
        <h1 class="h3 mb-0 text-white">EuroTech</h1>
      </div>

      <div class="d-flex flex-column flex-sm-row align-items-center gap-3 w-100 w-sm-auto justify-content-center justify-content-sm-end">
        <h2 class="h6 mb-0 text-white-50 text-center text-sm-start">
          Hola, <?= htmlspecialchars($_SESSION['usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        </h2>

        <div class="d-flex flex-wrap justify-content-center gap-2">
          <?php if (($_SESSION['rol'] ?? 'user') === 'admin'): ?>
            <a href="<?= BASE_URL ?>/respaldo" class="btn btn-sm btn-warning" title="Enviar respaldo SQL a Telegram">
              <i class="bi bi-telegram"></i> <span class="d-none d-md-inline">Backup Telegram</span>
            </a>
            <a href="<?= BASE_URL ?>/list_usuarios" class="btn btn-sm btn-success">Usuarios</a>
            <a href="<?= BASE_URL ?>/add_servidor" class="btn btn-sm btn-success">Nuevo Servidor</a>
          <?php endif; ?>
          <a href="<?= BASE_URL ?>/logout" class="btn btn-sm btn-danger">Salir</a>
        </div>
      </div>
    </div>
  </header>

  <main class="container-fluid py-4">
    <section class="mb-4">
      <h1 class="h3">Servidores</h1>
      <p class="text-white-50 small">Gestión de monitoreo</p>

      <?php if (!empty($_SESSION['error_dashboard'])): ?>
        <div class="alert alert-danger mt-3">
          <?= htmlspecialchars($_SESSION['error_dashboard'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['error_dashboard']); ?>
      <?php endif; ?>

      <?php if (!empty($_SESSION['ok_dashboard'])): ?>
        <div class="alert alert-success mt-3">
          <?= htmlspecialchars($_SESSION['ok_dashboard'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['ok_dashboard']); ?>
      <?php endif; ?>

      <?php if (!empty($_SESSION['token_servidor_creado'])): ?>
        <?php 
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                       || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            $protocol = $isHttps ? "https://" : "http://";
            $apiUrl = $protocol . $_SERVER['HTTP_HOST'] . BASE_URL;
            $token = $_SESSION['token_servidor_creado'];
            $alias = $_SESSION['alias_servidor_creado'] ?? 'servidor';
        ?>
        <div class="alert alert-warning border border-warning-subtle mb-4" role="alert">
            <h5 class="alert-heading fw-bold small text-uppercase">¡Configuración del Servidor!</h5>
            <p class="small mb-3">Ejecuta este comando como root en tu servidor:</p>
            
            <div class="input-group input-group-sm mb-3">
                <input type="text" class="form-control text-bg-dark border-secondary font-monospace" readonly id="shell-cmd"
                       value="curl -sSf <?= $apiUrl ?>/instaladorDemonio.sh | sudo bash -s -- '<?= $alias ?>' '<?= $token ?>' '<?= $apiUrl ?>'" style="font-size: 0.75rem;">
                <button class="btn btn-warning" onclick="navigator.clipboard.writeText(document.getElementById('shell-cmd').value)">
                  <i class="bi bi-copy"></i>
                </button>
            </div>

            <div class="bg-dark bg-opacity-25 p-2 rounded">
                <div class="row g-2 text-center text-md-start">
                    <div class="col-6 col-md-4">
                        <span class="d-block text-muted small">Alias:</span>
                        <code class="small"><?= htmlspecialchars($alias) ?></code>
                    </div>
                    <div class="col-6 col-md-4">
                        <span class="d-block text-muted small">Token:</span>
                        <code class="small">********</code>
                    </div>
                    <div class="col-12 col-md-4">
                        <span class="d-block text-muted small">API URL:</span>
                        <code class="small"><?= $protocol . $_SERVER['HTTP_HOST'] ?></code>
                    </div>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['token_servidor_creado'], $_SESSION['alias_servidor_creado']); ?>
      <?php endif; ?>
    </section>

    <section id="server-list">
      <div class="table-responsive">
        <table class="table table-dark table-hover align-middle">
          <thead class="table-secondary">
            <tr>
              <th>Servidor</th>
              <th>IP/Dominio</th>
              <th class="d-none d-md-table-cell">Estado</th>
              <th class="d-none d-sm-table-cell">Rol</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="servers-tbody" class="small">
            <!-- Renderizado dinámico vía JS -->
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <div class="modal fade" id="modalUsuariosServidor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">

        <div class="modal-header">
          <h5 class="modal-title" id="mus-title">Usuarios del servidor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" id="mus-servidor-id">
          <input type="hidden" id="mus-dueno-id">

          <div class="mb-3">
            <h6 class="mb-2">Todos los usuarios</h6>

            <div class="table-responsive">
              <table class="table table-dark table-hover align-middle mb-2">
                <thead class="table-secondary">
                  <tr>
                    <th>Usuario</th>
                    <th style="width: 1%"></th>
                  </tr>
                </thead>
                <tbody id="mus-all-tbody">
                  <tr><td colspan="2" class="text-white-50">Cargando…</td></tr>
                </tbody>
              </table>
            </div>

            <div class="d-flex justify-content-between align-items-center">
              <button class="btn btn-sm btn-secondary" id="mus-page-prev">Anterior</button>
              <div class="text-white-50 small">
                Página <span id="mus-page-now">1</span> / <span id="mus-page-total">1</span>
              </div>
              <button class="btn btn-sm btn-secondary" id="mus-page-next">Siguiente</button>
            </div>
          </div>

          <hr>

          <h6 class="mb-2">Usuarios asignados</h6>
          <div id="mus-alert"></div>

          <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
              <thead class="table-secondary">
                <tr>
                  <th>Usuario</th>
                  <th>Rol</th>
                  <th style="width: 1%"></th>
                </tr>
              </thead>
              <tbody id="mus-tbody">
                <tr><td colspan="3" class="text-white-50">Cargando…</td></tr>
              </tbody>
            </table>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>

      </div>
    </div>
  </div>

  <!-- Modal Editar Servidor -->
  <div class="modal fade" id="modalEditarServidor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form action="<?= BASE_URL ?>/edit_servidor" method="POST" class="modal-content">
        <div class="modal-header text-white">
          <h5 class="modal-title text-white">Editar Servidor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
          <input type="hidden" name="id" id="edit-id">
          
          <div class="mb-3">
            <label class="form-label">Alias</label>
            <input type="text" name="alias" id="edit-alias" class="form-control text-bg-dark border-secondary" required maxlength="20">
          </div>
          <div class="mb-3">
            <label class="form-label">Dirección IP</label>
            <input type="text" name="ip" id="edit-ip" class="form-control text-bg-dark border-secondary" required maxlength="15">
          </div>
          <div class="mb-3">
            <label class="form-label">Dominio (opcional)</label>
            <input type="text" name="dominio" id="edit-dominio" class="form-control text-bg-dark border-secondary" maxlength="50">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const idSesion = <?= json_encode($_SESSION['id'] ?? 0) ?>;
    const csrfToken = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
    const tbody = document.getElementById('servers-tbody');

    function estadoUI(estadoRaw) {
      const estado = String(estadoRaw || 'INACTIVO').toUpperCase();
      if (estado === 'ENCENDIDO' || estado === 'ACTIVO') return { badge: 'bg-success', text: estado };
      if (estado === 'APAGADO') return { badge: 'bg-danger', text: estado };
      if (estado === 'INDETERMINADO') return { badge: 'bg-warning', text: estado };
      return { badge: 'bg-secondary', text: estado };
    }

    function escapeHtml(s) {
      return String(s ?? '')
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'",'&#039;');
    }

    function renderRows(servidores) {
      if (!Array.isArray(servidores) || servidores.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="6" class="text-center text-white-50 py-4">
              No tienes servidores asociados en usuarios_servidor.
            </td>
          </tr>
        `;
        return;
      }

      tbody.innerHTML = '';
      servidores.forEach(s => {
        const est = estadoUI(s.estado);
        const idServidor = s.id;
        const aliasServidor = s.alias || '';
        const ipServidor = s.ip || '';
        const dominioServidor = s.dominio || '';
        const rolUsuario = String(s.rol_usuario || '').toUpperCase();
        const duenoId = Number(s.dueno_id || 0);
        const esDueno = (Number(s.dueno_id) === Number(idSesion));

        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>
            <div class="fw-bold">${escapeHtml(aliasServidor)}</div>
            <div class="d-md-none mt-1">
              <span class="badge ${est.badge} text-dark p-1" style="font-size: 0.7rem;">${escapeHtml(est.text)}</span>
            </div>
          </td>
          <td>
            <code class="text-white d-block" style="font-size: 0.8rem;">${escapeHtml(ipServidor)}</code>
            <small class="text-white-50">${dominioServidor ? escapeHtml(dominioServidor) : ''}</small>
          </td>
          <td class="d-none d-md-table-cell">
            <span class="badge ${est.badge} text-dark">${escapeHtml(est.text)}</span>
          </td>
          <td class="d-none d-sm-table-cell">
            <span class="badge bg-info text-dark" style="font-size: 0.75rem;">${escapeHtml(s.rol_usuario || '')}</span>
          </td>
          <td class="text-nowrap">
            <div class="btn-group">
            ${rolUsuario === 'ADMIN' ? `
              <button type="button" class="btn btn-sm btn-outline-primary" 
                data-bs-toggle="modal" data-bs-target="#modalEditarServidor"
                title="Editar Servidor"
                data-id="${idServidor}" data-alias="${escapeHtml(aliasServidor)}"
                data-ip="${escapeHtml(ipServidor)}" data-dominio="${escapeHtml(dominioServidor)}">
                <i class="bi bi-pencil"></i>
              </button>
              <button type="button" class="btn btn-sm btn-outline-success btn-usuarios-servidor px-2"
                data-bs-toggle="modal" data-bs-target="#modalUsuariosServidor"
                title="Gestionar Usuarios"
                data-id="${idServidor}" data-servidor-id="${idServidor}" data-servidor-alias="${escapeHtml(aliasServidor)}"
                data-dueno-id="${duenoId}">
                <i class="bi bi-people"></i>
              </button>
            ` : ''}
            ${esDueno ? `
              <form action="${BASE_URL}/delete_servidor" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar servidor?');">
                <input type="hidden" name="csrf_token" value="${csrfToken}">
                <input type="hidden" name="id" value="${idServidor}">
                <button type="submit" class="btn btn-sm btn-outline-danger px-2" title="Eliminar Servidor">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            ` : ''}
            </div>
          </td>
        `;
        tbody.appendChild(tr);
      });
    }

    let inFlight = false;

    async function refreshServers() {
      if (inFlight) return;
      inFlight = true;
      try {
        const res = await fetch(`${BASE_URL}/api/servidores`, { credentials: 'same-origin' });
        const data = await res.json().catch(() => null);
        if (!res.ok || !data || data.ok !== true) return;
        renderRows(data.servidores);
      } catch (e) {
      } finally {
        inFlight = false;
      }
    }

    const musModal = document.getElementById('modalUsuariosServidor');
    const musTitle = document.getElementById('mus-title');
    const musServidorId = document.getElementById('mus-servidor-id');
    const musDuenoId = document.getElementById('mus-dueno-id');
    const musTbody = document.getElementById('mus-tbody');
    const musAlert = document.getElementById('mus-alert');

    const musAllTbody = document.getElementById('mus-all-tbody');
    const musPrev = document.getElementById('mus-page-prev');
    const musNext = document.getElementById('mus-page-next');
    const musPageNow = document.getElementById('mus-page-now');
    const musPageTotal = document.getElementById('mus-page-total');

    // Manejo del modal de edición
    const editModal = document.getElementById('modalEditarServidor');
    if (editModal) {
      editModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('edit-id').value = btn.dataset.id;
        document.getElementById('edit-alias').value = btn.dataset.alias;
        document.getElementById('edit-ip').value = btn.dataset.ip;
        document.getElementById('edit-dominio').value = btn.dataset.dominio || '';
      });
    }

    const PER_PAGE = 10;
    let allUsers = [];
    let allPage = 1;
    let assignedIds = new Set();

    function esc(s) {
      return String(s ?? '')
        .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
        .replaceAll('"','&quot;').replaceAll("'",'&#039;');
    }

    function showAlert(type, msg) {
      musAlert.innerHTML = `<div class="alert alert-${type} py-2">${esc(msg)}</div>`;
      setTimeout(() => { musAlert.innerHTML = ''; }, 2500);
    }

    function totalPages() {
      return Math.max(1, Math.ceil((allUsers?.length || 0) / PER_PAGE));
    }

    function clampPage() {
      const tp = totalPages();
      if (allPage < 1) allPage = 1;
      if (allPage > tp) allPage = tp;
    }

    function renderAllUsers() {
      clampPage();
      const tp = totalPages();

      musPageNow.textContent = String(allPage);
      musPageTotal.textContent = String(tp);
      musPrev.disabled = allPage <= 1;
      musNext.disabled = allPage >= tp;

      const start = (allPage - 1) * PER_PAGE;
      const slice = (allUsers || []).slice(start, start + PER_PAGE);

      if (!slice.length) {
        musAllTbody.innerHTML = `<tr><td colspan="2" class="text-white-50">Sin usuarios.</td></tr>`;
        return;
      }

      musAllTbody.innerHTML = slice.map(u => {
        const idU = Number(u.id) || 0;
        const name = esc(u.usuario ?? u.usuarios ?? '');
        const ya = assignedIds.has(idU);

        return `
          <tr>
            <td>${name}</td>
            <td class="text-end">
              ${ya
                ? `<button class="btn btn-sm btn-secondary" disabled>Asignado</button>`
                : `<button class="btn btn-sm btn-success mus-add" data-id="${idU}" aria-label="Agrega el usuario al servidor">Agregar</button>`
              }
            </td>
          </tr>
        `;
      }).join('');
    }

    async function cargarTodosUsuarios() {
      musAllTbody.innerHTML = `<tr><td colspan="2" class="text-white-50">Cargando…</td></tr>`;
      const res = await fetch(`${BASE_URL}/api/usuarios`, { credentials: 'same-origin' });
      const data = await res.json().catch(() => null);

      if (!res.ok || !data || data.ok !== true) {
        allUsers = [];
        allPage = 1;
        renderAllUsers();
        return;
      }

      const usuarios = Array.isArray(data.usuarios) ? data.usuarios : [];
      allUsers = usuarios.map(u => ({
        id: Number(u.id) || 0,
        usuario: String(u.usuario ?? u.usuarios ?? '')
      }));

      allPage = 1;
      renderAllUsers();
    }

    async function cargarUsuariosServidor(idServidor, idDueno) {
      musTbody.innerHTML = `<tr><td colspan="3" class="text-white-50">Cargando…</td></tr>`;

      const res = await fetch(`${BASE_URL}/api/servidor/${idServidor}/usuarios`, { credentials: 'same-origin' });
      const data = await res.json().catch(() => null);

      if (!res.ok || !data || data.ok !== true) {
        assignedIds = new Set();
        musTbody.innerHTML = `<tr><td colspan="3" class="text-danger">No se pudo cargar.</td></tr>`;
        return;
      }

      assignedIds = new Set((data.usuarios || []).map(u => Number(u.id)));

      if (!data.usuarios || data.usuarios.length === 0) {
        musTbody.innerHTML = `<tr><td colspan="3" class="text-white-50">Sin usuarios asignados.</td></tr>`;
        return;
      }

      musTbody.innerHTML = data.usuarios.map(u => {
        const idU = Number(u.id);
        const roles = String(u.rol).toLowerCase();
        const esAdmin = roles === 'admin';
        const esElDueno = (idU === Number(idDueno));

        let actions = '';
        if (!esElDueno) {
          if (esAdmin) {
            actions += `<button class="btn btn-warning mus-degradar" title="Degradar a Usuario" data-id="${idU}" arial-label="Cambia de rol dentro del servidor a usuario">Degradar</button>`;
          } else {
            actions += `<button class="btn btn-warning mus-promover" title="Promover a Admin" data-id="${idU}" aria-label="Cambia de rol dentro del servidor a administrador">Promover</button>`;
          }
          actions += `<button class="btn btn-danger mus-del" title="Eliminar del servidor" data-id="${idU}" aria-label="Elimina al usuario del servidor">Eliminar</button>`;
        } else {
          actions = '<span class="badge bg-secondary">Dueño</span>';
        }

        return `
          <tr>
            <td>${esc(u.usuario)}</td>
            <td><span class="badge bg-info text-dark">${esc(u.rol ?? '')}</span></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                ${actions}
              </div>
            </td>
          </tr>
        `;
      }).join('');
    }

    async function postJson(url, body) {
      const token = document.querySelector('meta[name="csrf-token"]')?.content;
      const res = await fetch(url, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': token
        },
        credentials: 'same-origin',
        body: JSON.stringify(body)
      });
      const data = await res.json().catch(() => null);
      return { res, data };
    }

    musModal.addEventListener('show.bs.modal', async (event) => {
      const btn = event.relatedTarget;
      const idServidor = btn?.dataset?.servidorId;
      const idDueno = btn?.dataset?.duenoId;
      const alias = btn?.dataset?.servidorAlias || '';

      musServidorId.value = idServidor || '';
      musDuenoId.value = idDueno || '';
      musTitle.textContent = alias ? `Usuarios del servidor • ${alias}` : 'Usuarios del servidor';

      musAlert.innerHTML = '';

      allUsers = [];
      allPage = 1;
      assignedIds = new Set();

      musAllTbody.innerHTML = `<tr><td colspan="2" class="text-white-50">Cargando…</td></tr>`;
      musPageNow.textContent = '1';
      musPageTotal.textContent = '1';
      musPrev.disabled = true;
      musNext.disabled = true;

      if (idServidor) {
        await cargarUsuariosServidor(idServidor, idDueno);
        await cargarTodosUsuarios();
        renderAllUsers();
      }
    });

    musPrev.addEventListener('click', () => {
      if (allPage > 1) {
        allPage -= 1;
        renderAllUsers();
      }
    });

    musNext.addEventListener('click', () => {
      if (allPage < totalPages()) {
        allPage += 1;
        renderAllUsers();
      }
    });

    document.addEventListener('click', async (e) => {
      const idServidor = musServidorId.value;
      const idDueno = musDuenoId.value;
      if (!idServidor) return;

      const delBtn = e.target.closest('.mus-del');
      if (delBtn) {
        const idUsuario = Number(delBtn.dataset.id || 0);
        const { res, data } = await postJson(`${BASE_URL}/api/servidor/${idServidor}/usuarios/eliminar`, { idUsuario });
        if (!res.ok || !data || data.ok !== true) return showAlert('danger', 'No se pudo eliminar.');
        showAlert('success', 'Usuario eliminado.');
        await cargarUsuariosServidor(idServidor, idDueno);
        renderAllUsers();
        return;
      }

      const promBtn = e.target.closest('.mus-promover');
      if (promBtn) {
        const idUsuario = Number(promBtn.dataset.id || 0);
        const { res, data } = await postJson(`${BASE_URL}/api/servidor/${idServidor}/usuarios/promover`, { idUsuario });
        if (!res.ok || !data || data.ok !== true) return showAlert('danger', data?.error || 'No se pudo promover.');
        showAlert('success', 'Usuario promovido a admin.');
        await cargarUsuariosServidor(idServidor, idDueno);
        renderAllUsers();
        return;
      }

      const degBtn = e.target.closest('.mus-degradar');
      if (degBtn) {
        const idUsuario = Number(degBtn.dataset.id || 0);
        const { res, data } = await postJson(`${BASE_URL}/api/servidor/${idServidor}/usuarios/degradar`, { idUsuario });
        if (!res.ok || !data || data.ok !== true) return showAlert('danger', data?.error || 'No se pudo degradar.');
        showAlert('success', 'Usuario degradado a usuario normal.');
        await cargarUsuariosServidor(idServidor, idDueno);
        renderAllUsers();
        return;
      }

      const addBtn = e.target.closest('.mus-add');
      if (addBtn) {
        const idUsuario = Number(addBtn.dataset.id || 0);
        const { res, data } = await postJson(`${BASE_URL}/api/servidor/${idServidor}/usuarios/agregar`, { idUsuario });
        if (!res.ok || !data || data.ok !== true) return showAlert('danger', 'No se pudo agregar.');
        showAlert('success', 'Usuario agregado.');
        await cargarUsuariosServidor(idServidor, idDueno);
        renderAllUsers();
        return;
      }
    });

    refreshServers();
    setInterval(refreshServers, 5000);
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= BASE_URL ?>/JS/script.js"></script>
</body>
</html>
