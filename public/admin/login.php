<?php
$loginError = $loginError ?? '';
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TeraCore - Είσοδος Διαχειριστή</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&family=IBM+Plex+Mono:wght@400;600&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
  <link href="/admin/admin.css" rel="stylesheet" />
</head>
<body>
  <main class="login-shell">
    <section class="login-card fade-up">
      <div class="brand mb-3">
        <i class="fa-solid fa-shield-halved"></i>
        <span>TeraCore Διαχείριση</span>
      </div>
      <h1 class="page-title mb-2">Είσοδος</h1>
      <p class="subtitle mb-4">Πρόσβαση στον πίνακα διαχείρισης.</p>
      <form id="admin-login-form" class="form-grid" method="POST" action="/admin/login">
        <div>
          <label class="form-label" for="admin-username">Όνομα χρήστη</label>
          <input class="form-control" id="admin-username" name="username" placeholder="admin" required />
        </div>
        <div>
          <label class="form-label" for="admin-password">Κωδικός</label>
          <input class="form-control" id="admin-password" name="password" type="password" placeholder="••••••" required />
        </div>
        <?php if (!empty($loginError)) : ?>
          <p id="login-error" class="text-danger"><?php echo htmlspecialchars($loginError); ?></p>
        <?php endif; ?>
        <button class="btn btn-primary" type="submit">Σύνδεση</button>
      </form>
      <p class="subtitle mt-3">Ο διαχειριστής χρησιμοποιεί ξεχωριστό κωδικό.</p>
    </section>
  </main>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/admin/admin.js"></script>
</body>
</html>
