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
  <title>TeraCore - Πίνακας Διαχείρισης</title>
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
        <a class="nav-link" href="/admin/dashboard#admins"><i class="fa-solid fa-user-shield"></i> Διαχειριστές</a>
        <a class="nav-link" href="/admin/dashboard#permissions"><i class="fa-solid fa-key"></i> Δικαιώματα</a>
        <a class="btn btn-ghost mt-3" href="/admin/logout"><i class="fa-solid fa-right-from-bracket me-2" title="Αποσύνδεση"></i>Αποσύνδεση</a>
      </nav>
    </aside>
    <main class="admin-content">
      <h1 class="page-title">Πίνακας Διαχείρισης</h1>
      <p class="subtitle">Καλώς ήρθες, <?php echo htmlspecialchars($adminUser['username'] ?? 'admin'); ?>.</p>

      <section class="section-grid">
        <div class="metric">
          <h3>Ενεργοί Χρήστες</h3>
          <span><?php echo (int)$activeUsers; ?> χρήστες</span>
        </div>
        <div class="metric">
          <h3>Ενεργοί Διαχειριστές</h3>
          <span><?php echo (int)$activeAdmins; ?> διαχειριστές</span>
        </div>
        <div class="metric">
          <h3>Ανακλημένα Tokens</h3>
          <span><?php echo (int)$revokedTokens; ?> ανακλήσεις</span>
        </div>
      </section>

      <section id="admins" class="card mt-4 fade-up stagger-1">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h2 class="mb-1">Διαχείριση Admins</h2>
            <p class="subtitle">Power admin δεν μπορεί να διαγραφεί.</p>
          </div>
          <button class="btn btn-primary btn-icon" data-bs-toggle="collapse" data-bs-target="#admin-create" title="Προσθήκη διαχειριστή" aria-label="Προσθήκη διαχειριστή">
            <i class="fa-solid fa-user-plus"></i>
          </button>
        </div>

        <div id="admin-create" class="collapse mb-3">
          <form class="form-grid" method="POST" action="/admin/dashboard">
            <input type="hidden" name="action" value="create" />
            <div>
              <label class="form-label">Όνομα διαχειριστή</label>
              <input class="form-control" name="admin_name" required />
            </div>
            <div>
              <label class="form-label">Κατάσταση</label>
              <select class="form-control" name="status">
                <option value="active">ενεργός</option>
                <option value="inactive">ανενεργός</option>
                <option value="suspended">σε αναστολή</option>
              </select>
            </div>
            <div>
              <label class="form-label">Περιγραφή</label>
              <textarea class="form-control" name="description" rows="2"></textarea>
            </div>
            <div>
              <label class="form-label">Κωδικός</label>
              <input class="form-control" type="password" name="password" placeholder="Κωδικός διαχειριστή" />
            </div>
            <button class="btn btn-primary btn-icon" type="submit" title="Δημιουργία" aria-label="Δημιουργία">
              <i class="fa-solid fa-plus"></i>
            </button>
          </form>
        </div>

        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Admin</th>
                <th>Κατάσταση</th>
                <th>Περιγραφή</th>
                <th>Ενέργειες</th>
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
                      <input type="password" name="password" class="form-control d-inline w-auto" placeholder="Νέος κωδικός" />
                      <select name="status" class="form-control d-inline w-auto">
                        <option value="active" <?php echo ($admin['status'] ?? '') === 'active' ? 'selected' : ''; ?>>ενεργός</option>
                        <option value="inactive" <?php echo ($admin['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>ανενεργός</option>
                        <option value="suspended" <?php echo ($admin['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>σε αναστολή</option>
                      </select>
                      <button class="btn btn-ghost btn-sm btn-icon ms-2" type="submit" title="Ενημέρωση" aria-label="Ενημέρωση">
                        <i class="fa-solid fa-pen"></i>
                      </button>
                    </form>
                    <form method="POST" action="/admin/dashboard" class="d-inline">
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="admin_id" value="<?php echo (int)$admin['id']; ?>" />
                      <button class="btn btn-danger btn-sm btn-icon ms-2" type="submit" <?php echo $isPower ? 'disabled' : ''; ?> title="Διαγραφή" aria-label="Διαγραφή">
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

      <section id="permissions" class="card mt-4 fade-up stagger-2">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h2 class="mb-1">Χάρτης Δικαιωμάτων</h2>
            <p class="subtitle">Διαχείριση δικαιωμάτων ανά ρόλο.</p>
          </div>
          <button class="btn btn-ghost" type="button" disabled>Σύντομα</button>
        </div>
        <p class="subtitle">Ο χάρτης δικαιωμάτων θα συνδεθεί σε πραγματικά δεδομένα μόλις ολοκληρωθούν οι ρόλοι.</p>
      </section>
    </main>
  </div>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/admin/admin.js"></script>
</body>
</html>
