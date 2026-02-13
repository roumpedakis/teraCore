<?php
$adminUser = $adminUser ?? [];
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TeraCore - Permissions</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&family=IBM+Plex+Mono:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
  <link href="/admin/admin.css" rel="stylesheet" />
</head>
<body>
  <div class="admin-shell">
    <aside class="admin-sidebar">
      <div class="brand">
        <i class="fa-solid fa-shield-halved"></i>
        <span>TeraCore</span>
      </div>
      <nav class="nav-list">
        <a class="nav-link" href="/admin/dashboard"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
        <a class="nav-link" href="/admin/users"><i class="fa-solid fa-users"></i> Users</a>
        <a class="nav-link" href="/admin/modules"><i class="fa-solid fa-cubes"></i> Modules</a>
        <a class="nav-link" href="/admin/permissions"><i class="fa-solid fa-key"></i> Permissions</a>
        <a class="btn btn-ghost mt-3" id="logout-button" href="/admin/logout"><i class="fa-solid fa-right-from-bracket me-2" title="Logout"></i>Logout</a>
      </nav>
    </aside>
    <main class="admin-content">
      <h1 class="page-title">Permissions</h1>
      <p class="subtitle">Roles and user permissions for modules.</p>

      <section class="card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h2 class="mb-1">Roles</h2>
            <p class="subtitle">Create and update roles.</p>
          </div>
        </div>
        <form id="role-form" class="form-grid">
          <input type="hidden" id="role-id" name="role_id" />
          <div>
            <label class="form-label" for="role-name">Role name</label>
            <input class="form-control" id="role-name" name="name" required />
          </div>
          <div>
            <label class="form-label" for="role-description">Description</label>
            <input class="form-control" id="role-description" name="description" />
          </div>
          <div class="d-flex align-items-end gap-2">
            <button class="btn btn-primary" id="role-save" type="submit">Save</button>
            <button class="btn btn-ghost" id="role-reset" type="button">Reset</button>
          </div>
        </form>

        <div class="table-wrap mt-3">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Role</th>
                <th>Description</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="roles-table-body"></tbody>
          </table>
        </div>
      </section>

      <section class="card mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h2 class="mb-1">User Permissions</h2>
            <p class="subtitle">Set module permissions per user.</p>
          </div>
        </div>

        <div class="user-picker">
          <label class="form-label" for="userSearch">Search user</label>
          <input id="userSearch" class="form-control" type="text" placeholder="Type name or email" />
          <div class="user-listbox" id="userList" role="listbox" aria-label="Users"></div>
        </div>

        <div id="userPermissionsContainer" style="display: none;">
          <p class="subtitle">Selected user: <strong id="selectedUsername">-</strong></p>
          <div class="user-modules-list" id="userPermissionsList"></div>
          <button class="btn btn-primary" id="savePermissionsBtn" type="button">Save Permissions</button>
        </div>
      </section>
    </main>
  </div>

  <div class="modal fade" id="admin-feedback-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="admin-feedback-title">Notice</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="admin-feedback-body"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/admin/admin.js"></script>
  <script src="/admin/permissions.js"></script>
</body>
</html>
