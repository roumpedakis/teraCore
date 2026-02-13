<?php
$adminUser = $adminUser ?? [];
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TeraCore - Διαχείριση Modules</title>
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
        <a class="btn btn-ghost mt-3" id="logout-button" href="/admin/logout"><i class="fa-solid fa-right-from-bracket me-2" title="Αποσύνδεση"></i>Αποσύνδεση</a>
      </nav>
    </aside>
    <main class="admin-content">
      <h1 class="page-title">Modules</h1>
      <p class="subtitle">Διαχείριση διαθέσιμων modules και δικαιωμάτων ανά χρήστη.</p>

      <section class="module-stats">
        <div class="stat-card">
          <span class="stat-value" id="totalModules">0</span>
          <span class="stat-label">Σύνολο</span>
        </div>
        <div class="stat-card">
          <span class="stat-value" id="coreModules">0</span>
          <span class="stat-label">Core</span>
        </div>
        <div class="stat-card">
          <span class="stat-value" id="paidModules">0</span>
          <span class="stat-label">Paid</span>
        </div>
      </section>

      <section class="card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h2 class="mb-1">Διαθέσιμα Modules</h2>
            <p class="subtitle">Επισκόπηση pricing και εξαρτήσεων.</p>
          </div>
        </div>
        <div class="modules-grid" id="modulesGrid"></div>
      </section>

      <section class="user-modules-section">
        <h2>Δικαιώματα χρήστη</h2>
        <p class="subtitle">Διάλεξε χρήστη και όρισε module permissions.</p>

        <div class="user-picker">
          <label class="form-label" for="userSearch">Αναζήτηση χρήστη</label>
          <input id="userSearch" class="form-control" type="text" placeholder="Γράψε όνομα ή email" />
          <div class="user-listbox" id="userList" role="listbox" aria-label="Χρήστες"></div>
        </div>

        <div id="userModulesContainer" style="display: none;">
          <p class="subtitle">Επιλεγμένος χρήστης: <strong id="selectedUsername">-</strong></p>

          <div class="billing-info">
            <h4>Σύνοψη χρέωσης</h4>
            <div class="billing-details">
              <span>Μηνιαίο σύνολο: <strong id="monthlyTotal">€0.00</strong></span>
              <span>Ενεργά modules: <strong id="activeModulesCount">0</strong></span>
              <span>Paid modules: <strong id="paidModulesCount">0</strong></span>
            </div>
          </div>

          <div class="user-modules-list" id="userModulesList"></div>

          <button class="btn btn-primary" id="saveModulesBtn" type="button">Αποθήκευση</button>
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
  <script src="/admin/modules.js"></script>
</body>
</html>
