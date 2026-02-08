<?php
$activeUsers = $activeUsers ?? 0;
$activeAdmins = $activeAdmins ?? 0;
$revokedTokens = $revokedTokens ?? 0;
$admins = $admins ?? [];
$adminUser = $adminUser ?? [];
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TeraCore Admin Dashboard</title>
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
        <a class="nav-link" href="/admin/dashboard#admins"><i class="fa-solid fa-user-shield"></i> Admins</a>
        <a class="nav-link" href="/admin/dashboard#permissions"><i class="fa-solid fa-key"></i> Permissions</a>
        <a class="btn btn-ghost mt-3" href="/admin/logout">Logout</a>
      </nav>
    </aside>
    <main class="admin-content">
      <h1 class="page-title">Admin Dashboard</h1>
      <p class="subtitle">Kalws hrthes, <?php echo htmlspecialchars($adminUser['username'] ?? 'admin'); ?>.</p>

      <section class="section-grid">
        <div class="metric">
          <h3>Active Users</h3>
          <span><?php echo (int)$activeUsers; ?> users</span>
        </div>
        <div class="metric">
          <h3>Active Admins</h3>
          <span><?php echo (int)$activeAdmins; ?> admins</span>
        </div>
        <div class="metric">
          <h3>Revoked Tokens</h3>
          <span><?php echo (int)$revokedTokens; ?> revoked</span>
        </div>
      </section>

      <section id="admins" class="card mt-4 fade-up stagger-1">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h2 class="mb-1">Διαχείριση Admins</h2>
            <p class="subtitle">Power admin δεν μπορεί να διαγραφεί.</p>
          </div>
          <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#admin-create">Add Admin</button>
        </div>

        <div id="admin-create" class="collapse mb-3">
          <form class="form-grid" method="POST" action="/admin/dashboard">
            <input type="hidden" name="action" value="create" />
            <div>
              <label class="form-label">Admin username (must match user)</label>
              <input class="form-control" name="admin_name" required />
            </div>
            <div>
              <label class="form-label">Status</label>
              <select class="form-control" name="status">
                <option value="active">active</option>
                <option value="inactive">inactive</option>
                <option value="suspended">suspended</option>
              </select>
            </div>
            <div>
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" rows="2"></textarea>
            </div>
            <div>
              <label class="form-label">Password</label>
              <input class="form-control" type="password" name="password" placeholder="Admin password" />
            </div>
            <button class="btn btn-primary" type="submit">Create Admin</button>
          </form>
        </div>

        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Admin</th>
                <th>Status</th>
                <th>Description</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($admins as $admin) : ?>
                <?php $isPower = strtolower((string)($admin['name'] ?? '')) === 'power admin'; ?>
                <tr>
                  <td><?php echo htmlspecialchars($admin['name'] ?? ''); ?></td>
                  <td><span class="tag"><?php echo htmlspecialchars($admin['status'] ?? ''); ?></span></td>
                  <td><?php echo htmlspecialchars($admin['description'] ?? ''); ?></td>
                  <td>
                    <form method="POST" action="/admin/dashboard" class="d-inline">
                      <input type="hidden" name="action" value="update" />
                      <input type="hidden" name="admin_id" value="<?php echo (int)$admin['id']; ?>" />
                      <input type="hidden" name="admin_name" value="<?php echo htmlspecialchars($admin['name'] ?? ''); ?>" />
                      <input type="hidden" name="description" value="<?php echo htmlspecialchars($admin['description'] ?? ''); ?>" />
                      <input type="password" name="password" class="form-control d-inline w-auto" placeholder="New pass" />
                      <select name="status" class="form-control d-inline w-auto">
                        <option value="active" <?php echo ($admin['status'] ?? '') === 'active' ? 'selected' : ''; ?>>active</option>
                        <option value="inactive" <?php echo ($admin['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>inactive</option>
                        <option value="suspended" <?php echo ($admin['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>suspended</option>
                      </select>
                      <button class="btn btn-ghost btn-sm ms-2" type="submit">Update</button>
                    </form>
                    <form method="POST" action="/admin/dashboard" class="d-inline">
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="admin_id" value="<?php echo (int)$admin['id']; ?>" />
                      <button class="btn btn-danger btn-sm ms-2" type="submit" <?php echo $isPower ? 'disabled' : ''; ?>>Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <section id="permissions" class="card mt-4 fade-up stagger-2">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h2 class="mb-1">Permissions Matrix</h2>
            <p class="subtitle">Διαχείριση δικαιωμάτων ανά role.</p>
          </div>
          <button class="btn btn-ghost" type="button" disabled>Coming Soon</button>
        </div>
        <p class="subtitle">Το permissions matrix θα συνδεθεί σε πραγματικά δεδομένα μόλις ολοκληρωθούν οι ρόλοι.</p>
      </section>
    </main>
  </div>
</body>
</html>
