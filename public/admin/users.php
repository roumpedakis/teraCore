<?php
$users = $users ?? [];
$adminUser = $adminUser ?? [];
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TeraCore Admin Users</title>
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
      <h1 class="page-title">Users</h1>
      <p class="subtitle">Διαχείριση χρηστών, tokens και πρόσβασης.</p>

      <section class="card fade-up">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h2 class="mb-1">User Directory</h2>
            <p class="subtitle">Revoke tokens, deactivate ή delete user.</p>
          </div>
        </div>
        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>User</th>
                <th>Email</th>
                <th>Status</th>
                <th>Tokens</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user) : ?>
                <tr>
                  <td><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></td>
                  <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                  <td><span class="tag"><?php echo ((int)($user['is_active'] ?? 0)) === 1 ? 'active' : 'inactive'; ?></span></td>
                  <td><?php echo empty($user['token_expires_at']) ? 'revoked' : 'active'; ?></td>
                  <td>
                    <form method="POST" action="/admin/users" class="d-inline">
                      <input type="hidden" name="action" value="revoke" />
                      <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>" />
                      <button class="btn btn-danger btn-sm" type="submit">Revoke</button>
                    </form>
                    <form method="POST" action="/admin/users" class="d-inline">
                      <input type="hidden" name="action" value="toggle" />
                      <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>" />
                      <button class="btn btn-ghost btn-sm ms-2" type="submit">Toggle</button>
                    </form>
                    <form method="POST" action="/admin/users" class="d-inline">
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>" />
                      <button class="btn btn-danger btn-sm ms-2" type="submit">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
