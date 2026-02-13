<?php
$users = $users ?? [];
$adminUser = $adminUser ?? [];
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TeraCore - Διαχείριση Χρηστών</title>
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
        <a class="nav-link" href="/admin/dashboard"><i class="fa-solid fa-chart-line"></i> Πίνακας</a>
        <a class="nav-link" href="/admin/users"><i class="fa-solid fa-users"></i> Χρήστες</a>
        <a class="nav-link" href="/admin/modules"><i class="fa-solid fa-cubes"></i> Modules</a>
        <a class="nav-link" href="/admin/dashboard#admins"><i class="fa-solid fa-user-shield"></i> Διαχειριστές</a>
        <a class="nav-link" href="/admin/permissions"><i class="fa-solid fa-key"></i> Δικαιώματα</a>
        <a class="btn btn-ghost mt-3" href="/admin/logout"><i class="fa-solid fa-right-from-bracket me-2" title="Αποσύνδεση"></i>Αποσύνδεση</a>
      </nav>
    </aside>
    <main class="admin-content">
      <h1 class="page-title">Χρήστες</h1>
      <p class="subtitle">Διαχείριση χρηστών, tokens και πρόσβασης.</p>

      <section class="card fade-up">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h2 class="mb-1">Κατάλογος Χρηστών</h2>
            <p class="subtitle">Ανάκληση tokens, απενεργοποίηση ή διαγραφή χρήστη.</p>
          </div>
          <button class="btn btn-primary btn-icon" type="button" data-bs-toggle="modal" data-bs-target="#user-modal" title="Νέος χρήστης" aria-label="Νέος χρήστης">
            <i class="fa-solid fa-user-plus"></i>
          </button>
        </div>
        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Χρήστης</th>
                <th>Email</th>
                <th>Κατάσταση</th>
                <th>Tokens</th>
                <th>Ενέργειες</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user) : ?>
                <tr>
                  <td><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></td>
                  <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                  <td><span class="tag"><?php echo ((int)($user['is_active'] ?? 0)) === 1 ? 'ενεργός' : 'ανενεργός'; ?></span></td>
                  <td><?php echo empty($user['token_expires_at']) ? 'ανακλημένο' : 'ενεργό'; ?></td>
                  <td>
                    <form method="POST" action="/admin/users" class="d-inline">
                      <input type="hidden" name="action" value="revoke" />
                      <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>" />
                      <button class="btn btn-danger btn-sm btn-icon" type="submit" title="Ανάκληση token" aria-label="Ανάκληση token">
                        <i class="fa-solid fa-ban"></i>
                      </button>
                    </form>
                    <form method="POST" action="/admin/users" class="d-inline">
                      <input type="hidden" name="action" value="toggle" />
                      <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>" />
                      <button class="btn btn-ghost btn-sm btn-icon ms-2" type="submit" title="Εναλλαγή κατάστασης" aria-label="Εναλλαγή κατάστασης">
                        <i class="fa-solid fa-toggle-on"></i>
                      </button>
                    </form>
                    <button
                      class="btn btn-ghost btn-sm btn-icon ms-2 user-edit"
                      type="button"
                      title="Επεξεργασία"
                      aria-label="Επεξεργασία"
                      data-bs-toggle="modal"
                      data-bs-target="#user-modal"
                      data-user-id="<?php echo (int)$user['id']; ?>"
                      data-username="<?php echo htmlspecialchars($user['username'] ?? ''); ?>"
                      data-email="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                      data-first-name="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>"
                      data-last-name="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>"
                      data-is-active="<?php echo (int)($user['is_active'] ?? 0); ?>"
                    >
                      <i class="fa-solid fa-pen"></i>
                    </button>
                    <form method="POST" action="/admin/users" class="d-inline">
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>" />
                      <button class="btn btn-danger btn-sm btn-icon ms-2" type="submit" title="Διαγραφή χρήστη" aria-label="Διαγραφή χρήστη">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
      <div class="modal fade" id="user-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <form method="POST" action="/admin/users" id="user-form">
              <div class="modal-header">
                <h5 class="modal-title" id="user-modal-title">Νέος χρήστης</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Κλείσιμο"></button>
              </div>
              <div class="modal-body form-grid">
                <input type="hidden" name="action" id="user-form-action" value="create" />
                <input type="hidden" name="user_id" id="user-id" value="" />
                <div>
                  <label class="form-label" for="user-username">Username</label>
                  <input class="form-control" id="user-username" name="username" required />
                </div>
                <div>
                  <label class="form-label" for="user-email">Email</label>
                  <input class="form-control" id="user-email" name="email" type="email" required />
                </div>
                <div>
                  <label class="form-label" for="user-first">Όνομα</label>
                  <input class="form-control" id="user-first" name="first_name" />
                </div>
                <div>
                  <label class="form-label" for="user-last">Επώνυμο</label>
                  <input class="form-control" id="user-last" name="last_name" />
                </div>
                <div>
                  <label class="form-label" for="user-pass">Κωδικός</label>
                  <input class="form-control" id="user-pass" name="password" type="password" />
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="user-active" name="is_active" />
                  <label class="form-check-label" for="user-active">Ενεργός</label>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Άκυρο</button>
                <button type="submit" class="btn btn-primary">Αποθήκευση</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/admin/admin.js"></script>
</body>
</html>
