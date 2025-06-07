<?php
// /ToDoApp/index.php
require_once __DIR__ . '/includes/db.php';
session_start();

// 1. Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
  header('Location: /ToDoApp/calendar');
  exit;
}

// 2. Handle POST
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = trim($_POST['username'] ?? '');
  $p = $_POST['password']   ?? '';

  if ($u && $p) {
    $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = :u");
    $stmt->execute([':u' => $u]);
    $user = $stmt->fetch();
    if ($user && password_verify($p, $user['password_hash'])) {
      $_SESSION['user_id'] = $user['id'];
      header('Location: /ToDoApp/calendar');
      exit;
    }
  }
  $errors[] = 'Invalid username or password.';
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Member Login – ToDoApp</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

  <div class="d-flex vh-100 justify-content-center align-items-center">
    <div class="card shadow-sm" style="max-width: 400px; width:100%;">
      <div class="card-body">
        <h3 class="card-title text-center mb-4">Member Login</h3>

        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <?php foreach($errors as $e): ?>
              <div><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" novalidate>
          <div class="mb-3">
            <label for="u" class="form-label">Username</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-person"></i></span>
              <input id="u" name="username" type="text" class="form-control"
                placeholder="Username" required
                value="<?= htmlspecialchars($u ?? '') ?>">
            </div>
          </div>

          <div class="mb-4">
            <label for="p" class="form-label">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input id="p" name="password" type="password" class="form-control"
                placeholder="Password" required>
            </div>
          </div>

          <button class="btn btn-primary w-100" type="submit">Login</button>
        </form>

        <div class="text-center mt-3">
          Don't have an account? <a href="/ToDoApp/register">Register</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS Bundle (optional, for components) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
