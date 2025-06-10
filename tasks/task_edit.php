<?php
// /ToDoApp/tasks/task_edit.php
require_once __DIR__ . '/../includes/db.php';
session_start();

// 1) Only for logged-in users
if (empty($_SESSION['user_id'])) {
  header('Location: /ToDoApp/');
  exit;
}

// 2) No caching so Back-button always reloads
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 3) Fetch and validate the task ID
$task_id = $_GET['id'] ?? '';
if (!$task_id || !ctype_digit($task_id)) {
  die("Invalid task ID");
}

// 4) Fetch existing task data
$stmt = $pdo->prepare("
  SELECT title, description, due_date, priority, category_id
  FROM tasks
  WHERE id = ? AND user_id = ? AND archived = 0
");
$stmt->execute([$task_id, $_SESSION['user_id']]);
$task = $stmt->fetch();
if (!$task) {
  die("Task not found or access denied");
}

// 5) Pull categories from DB
$catStmt = $pdo->query("SELECT id, name FROM categories");
$categories = $catStmt->fetchAll();

// 6) Handle form POST for editing
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $priority = $_POST['priority'] ?? 'Medium';
  $category_id = $_POST['category_id'] ?? '';
  $due_date = $_POST['due_date'] ?? '';

  // Validations
  if ($title === '') {
    $errors[] = 'Title is required.';
  }
  if (!in_array($priority, ['Low', 'Medium', 'High'], true)) {
    $errors[] = 'Invalid priority.';
  }
  if (!ctype_digit($category_id)) {
    $errors[] = 'Please select a category.';
  }
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
    $errors[] = 'Invalid due date.';
  }

  // If ok, update
  if (empty($errors)) {
    $stmt = $pdo->prepare("
      UPDATE tasks
      SET title = ?, description = ?, priority = ?, category_id = ?, due_date = ?, updated_at = NOW()
      WHERE id = ? AND user_id = ? AND archived = 0
    ");
    $stmt->execute([
      $title, $description, $priority, $category_id, $due_date, $task_id, $_SESSION['user_id']
    ]);

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
  <title>Edit Task</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <h4>Edit Task</h4>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?>
        <div><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="edit_task_id" value="<?= htmlspecialchars($task_id) ?>">

    <div class="mb-3">
      <label class="form-label">Category</label>
      <select name="category_id" class="form-select" required>
        <option value="">-- choose --</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $c['id'] == $task['category_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Title</label>
      <input name="title" type="text" class="form-control" value="<?= htmlspecialchars($task['title']) ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Description</label>
      <textarea name="description" rows="3" class="form-control"><?= htmlspecialchars($task['description']) ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Priority</label>
      <select name="priority" class="form-select">
        <?php foreach (['Low', 'Medium', 'High'] as $p): ?>
          <option <?= $p === $task['priority'] ? 'selected' : '' ?>>
            <?= $p ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Due Date</label>
      <input name="due_date" type="date" class="form-control" value="<?= htmlspecialchars($task['due_date']) ?>" required>
    </div>

    <div class="d-flex">
      <button type="submit" class="btn btn-primary">Save Changes</button>
      <button type="button" class="btn btn-secondary ms-2" onclick="window.close();">
        Cancel
      </button>
    </div>
  </form>
</body>
</html>