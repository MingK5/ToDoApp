<?php
// tasks/task_add.php
require_once __DIR__ . '/../includes/db.php';
session_start();

// 1) only for logged-in users
if (empty($_SESSION['user_id'])) {
  header('Location: /ToDoApp/');
  exit;
}

// 2) no caching so Back-button always reloads
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 3) fetch + validate the date param
$due_date = $_GET['date'] ?? '';
if (!$due_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
  die("Invalid date");
}

// 4) pull categories from DB
$catStmt   = $pdo->query("SELECT id, name FROM categories");
$categories = $catStmt->fetchAll();

// 5) prepare form state & errors
$errors      = [];
$title       = '';
$description = '';
$priority    = 'Medium';
$category_id = '';

// 6) handle form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title       = trim($_POST['title'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $priority    = $_POST['priority']   ?? 'Medium';
  $category_id = $_POST['category_id']?? '';
  $due_date    = $_POST['due_date']   ?? '';

  // validations
  if ($title === '') {
    $errors[] = 'Title is required.';
  }
  if (!in_array($priority, ['Low','Medium','High'], true)) {
    $errors[] = 'Invalid priority.';
  }
  if (!ctype_digit($category_id)) {
    $errors[] = 'Please select a category.';
  }
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
    $errors[] = 'Invalid due date.';
  }

  // if ok, insert
  if (empty($errors)) {
    $stmt = $pdo->prepare("
      INSERT INTO tasks
        (user_id, category_id, title, description, due_date, priority, created_at, updated_at)
      VALUES
        (:uid, :cid, :title, :descr, :dd, :prio, NOW(), NOW())
    ");
    $stmt->execute([
      ':uid'   => $_SESSION['user_id'],
      ':cid'   => $category_id,
      ':title' => $title,
      ':descr' => $description,
      ':dd'    => $due_date,
      ':prio'  => $priority,
    ]);

    // after insert, close the popup and reload the calendar
    echo <<<JS
    <script>
      window.opener.location.reload();
      window.close();
    </script>
    JS;
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Task</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet">
</head>
<body class="p-4">
  <h4>Add a new task on <?= htmlspecialchars($due_date) ?></h4>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?>
        <div><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="due_date" value="<?= htmlspecialchars($due_date) ?>">

    <div class="mb-3">
      <label class="form-label">Category</label>
      <select name="category_id" class="form-select" required>
        <option value="">-- choose --</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>"
            <?= $c['id']==$category_id ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Title</label>
      <input name="title" type="text"
             class="form-control"
             value="<?= htmlspecialchars($title) ?>"
             required>
    </div>

    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" rows="3"
                class="form-control"><?= htmlspecialchars($description) ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Priority</label>
      <select name="priority" class="form-select">
        <?php foreach (['Low','Medium','High'] as $p): ?>
          <option <?= $p === $priority ? 'selected' : '' ?>>
            <?= $p ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="d-flex">
      <!-- Save -->
      <button type="submit" class="btn btn-primary">Save Task</button>
      <!-- Cancel -->
      <button type="button"
              class="btn btn-secondary ms-2"
              onclick="window.close();">
        Cancel
      </button>
    </div>
  </form>
</body>
</html>
