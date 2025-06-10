<?php
// /ToDoApp/status.php
require_once __DIR__ . '/../includes/db.php';
session_start();

// 1) Redirect if not logged in
if (empty($_SESSION['user_id'])) {
  header('Location: /ToDoApp/');
  exit;
}

// 2) Prevent browser caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 3) Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'], $_POST['status'])) {
  $task_id = (int)$_POST['task_id'];
  $status = $_POST['status'];
  $valid_statuses = ['Pending', 'On-going', 'Completed'];
  
  if (in_array($status, $valid_statuses)) {
    $stmt = $pdo->prepare("
      UPDATE tasks 
      SET status = ?, updated_at = NOW()
      WHERE id = ? AND user_id = ? AND archived = 0
    ");
    $stmt->execute([$status, $task_id, $_SESSION['user_id']]);
  }
  header('Location: ' . $_SERVER['PHP_SELF'] . '?status=' . urlencode($_GET['status'] ?? ''));
  exit;
}

// 4) Get status filter
$status_filter = isset($_GET['status']) ? urldecode($_GET['status']) : 'All';
$valid_filters = ['All', 'Pending', 'On-going', 'Completed'];
if (!in_array($status_filter, $valid_filters)) {
  $status_filter = 'All';
}

// 5) Fetch tasks based on status filter
$sql = "
  SELECT t.id, t.title, t.description, t.due_date, t.status, t.priority, c.name as category_name, t.created_at, t.updated_at
  FROM tasks t
  LEFT JOIN categories c ON t.category_id = c.id
  WHERE t.user_id = ? AND t.archived = 0
";
$params = [$_SESSION['user_id']];
if ($status_filter !== 'All') {
  $sql .= " AND t.status = ?";
  $params[] = $status_filter;
}
$sql .= " ORDER BY t.due_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6) Set page title
$pageTitle = 'ToDoApp: Status';
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
  <h4 class="text-center text-decoration-underline mb-4">Task Status Management</h4>

  <!-- Status Filter -->
  <div class="mb-3">
    <label for="statusFilter" class="form-label">Filter by Status</label>
    <select id="statusFilter" class="form-select" onchange="window.location.href='?status=' + encodeURIComponent(this.value)">
      <?php foreach ($valid_filters as $filter): ?>
        <option value="<?= htmlspecialchars($filter) ?>" <?= $status_filter === $filter ? 'selected' : '' ?>>
          <?= htmlspecialchars($filter) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <?php if (empty($tasks)): ?>
    <p class="text-center">No tasks found.</p>
  <?php else: ?>
    <div class="card">
      <div class="card-body">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Title</th>
              <th>Category</th>
              <th>Due Date</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tasks as $task): ?>
              <tr>
                <td><?= htmlspecialchars($task['title']) ?></td>
                <td><?= htmlspecialchars($task['category_name'] ?? 'Uncategorized') ?></td>
                <td><?= date('Y-m-d', strtotime($task['due_date'])) ?></td>
                <td>
                  <span class="badge 
                    <?= $task['priority'] === 'High' ? 'bg-danger' : 
                        ($task['priority'] === 'Medium' ? 'bg-warning' : 'bg-success') ?>">
                    <?= htmlspecialchars($task['priority']) ?>
                  </span>
                </td>
                <td>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                    <select name="status" class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
                      <?php foreach (['Pending', 'On-going', 'Completed'] as $status): ?>
                        <option value="<?= $status ?>" <?= $task['status'] === $status ? 'selected' : '' ?>>
                          <?= $status ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </form>
                </td>
                <td>
                  <a href="/ToDoApp/tasks/task_edit.php?id=<?= $task['id'] ?>" 
                     class="btn btn-outline-primary btn-sm" 
                     onclick="window.open(this.href,'EditTask','width=600,height=600');return false;">
                    Edit
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>