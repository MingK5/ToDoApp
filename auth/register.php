<?php
// /ToDoApp/auth/register.php
require_once __DIR__ . '/../includes/db.php';
session_start();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = trim($_POST['username']        ?? '');
  $n = trim($_POST['full_name']       ?? '');
  $p =            $_POST['password']  ?? '';
  $c =            $_POST['password_confirm'] ?? '';

  if (!$u || !$n || !$p) {
    $errors[] = 'All fields are required.';
  }
  if ($p !== $c) {
    $errors[] = 'Passwords do not match.';
  }

  if (empty($errors)) {
    $hash = password_hash($p, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
      INSERT INTO users (username, password_hash, full_name)
      VALUES (:u, :h, :n)
    ");
    try {
      $stmt->execute([':u'=>$u, ':h'=>$hash, ':n'=>$n]);
      header('Location: /todoapp/?registered=1');
      exit;
    } catch (PDOException $e) {
      if ($e->errorInfo[1] === 1062) {
        $errors[] = 'Username already taken.';
      } else {
        throw $e;
      }
    }
  }
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Create Account – ToDoApp</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

  <div class="d-flex vh-100 justify-content-center align-items-center">
    <div class="card shadow-sm" style="max-width: 400px; width:100%;">
      <div class="card-body">
        <h3 class="card-title text-center mb-4">Create Account</h3>

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

          <div class="mb-3">
            <label for="n" class="form-label">Full Name</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
              <input id="n" name="full_name" type="text" class="form-control"
                placeholder="Full Name" required
                value="<?= htmlspecialchars($n ?? '') ?>">
            </div>
          </div>

          <div class="mb-3">
            <label for="p1" class="form-label">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input id="p1" name="password" type="password" class="form-control"
                placeholder="Password" required>
            </div>
          </div>

          <div class="mb-4">
            <label for="p2" class="form-label">Confirm Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
              <input id="p2" name="password_confirm" type="password" class="form-control"
                placeholder="Confirm Password" required>
            </div>
          </div>

          <button class="btn btn-primary w-100" type="submit">Register</button>
        </form>

        <div class="text-center mt-3">
          Already have an account? <a href="/todoapp/">Login</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
