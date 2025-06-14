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
  header('Location: ' . $_SERVER['PHP_SELF'] . '?status=' . urlencode($_GET['status'] ?? 'All Status') . '&category=' . urlencode($_GET['category'] ?? '') . '&priority=' . urlencode($_GET['priority'] ?? '') . '&sort=' . ($_GET['sort'] ?? 'asc') . '&page=' . ($_GET['page'] ?? '1'));
  exit;
}

// 4) Handle edit form submission (placeholder - to be handled in task_edit.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_task_id'])) {
  // Edit functionality will be handled in task_edit.php
  header('Location: ' . $_SERVER['PHP_SELF'] . '?status=' . urlencode($_GET['status'] ?? 'All Status') . '&category=' . urlencode($_GET['category'] ?? '') . '&priority=' . urlencode($_GET['priority'] ?? '') . '&sort=' . ($_GET['sort'] ?? 'asc') . '&page=' . ($_GET['page'] ?? '1'));
  exit;
}

// 5) Filter and sort parameters
$categoryFilter = isset($_GET['category']) ? urldecode($_GET['category']) : '';
$priorityFilter = isset($_GET['priority']) ? urldecode($_GET['priority']) : '';
$sortOrder = isset($_GET['sort']) ? $_GET['sort'] : 'asc';
$status_filter = isset($_GET['status']) ? urldecode($_GET['status']) : 'All Status';

// 6) Pagination settings
$itemsPerPage = 10; // Changed to items per page instead of days
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

// 7) Get total number of tasks with filters
$sql = "SELECT COUNT(*) as total_tasks FROM tasks WHERE user_id = ? AND archived = 0";
$params = [$_SESSION['user_id']];
if ($categoryFilter) {
    $sql .= " AND category_id = (SELECT id FROM categories WHERE name = ?)";
    $params[] = $categoryFilter;
}
if ($priorityFilter) {
    $sql .= " AND priority = ?";
    $params[] = $priorityFilter;
}
if ($status_filter !== 'All Status') {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$totalTasks = $stmt->fetchColumn();
$totalPages = ceil($totalTasks / $itemsPerPage);

// 8) Get tasks with filters and pagination
$sql = "
    SELECT t.id, t.title, t.due_date, t.status, t.priority, c.name as category_name, t.created_at, t.updated_at
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = ? AND t.archived = 0
";
$params = [$_SESSION['user_id']];
if ($categoryFilter) {
    $sql .= " AND t.category_id = (SELECT id FROM categories WHERE name = ?)";
    $params[] = $categoryFilter;
}
if ($priorityFilter) {
    $sql .= " AND t.priority = ?";
    $params[] = $priorityFilter;
}
if ($status_filter !== 'All Status') {
    $sql .= " AND t.status = ?";
    $params[] = $status_filter;
}
$sql .= " ORDER BY t.due_date " . ($sortOrder === 'desc' ? 'DESC' : 'ASC') . " LIMIT ? OFFSET ?";
$params[] = $itemsPerPage;
$params[] = $offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 10) Fetch unique categories and priorities
$catStmt = $pdo->prepare("SELECT DISTINCT name FROM categories");
$catStmt->execute();
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
$categories = array_map('strval', $categories);

$priorities = ['Low', 'Medium', 'High'];
$valid_statuses = ['All Status', 'Pending', 'On-going', 'Completed'];

// 11) Set page title
$pageTitle = 'ToDoApp: Status';
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
  <h4 class="text-center text-decoration-underline mb-4">Task Status Management</h4>

  <!-- Filters on the same line -->
  <div class="row mb-3 align-items-center">
    <div class="col-md-3">
      <select class="form-select" name="category" onchange="this.form.submit()" form="filterForm">
        <option value="">All Categories</option>
        <?php foreach ($categories as $category): ?>
          <option value="<?= htmlspecialchars($category) ?>" <?= $categoryFilter === $category ? 'selected' : '' ?>>
            <?= htmlspecialchars($category) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-select" name="priority" onchange="this.form.submit()" form="filterForm">
        <option value="">All Priorities</option>
        <?php foreach ($priorities as $priority): ?>
          <option value="<?= htmlspecialchars($priority) ?>" <?= $priorityFilter === $priority ? 'selected' : '' ?>>
            <?= htmlspecialchars($priority) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-select" name="status" onchange="this.form.submit()" form="filterForm">
        <option value="All Status" <?= $status_filter === 'All Status' ? 'selected' : '' ?>>All Status</option>
        <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
        <option value="On-going" <?= $status_filter === 'On-going' ? 'selected' : '' ?>>On-going</option>
        <option value="Completed" <?= $status_filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
      </select>
    </div>
    <div class="col-md-3">
      <div class="btn-group">
        <a href="?page=<?= $page ?>&category=<?= urlencode($categoryFilter) ?>&priority=<?= urlencode($priorityFilter) ?>&status=<?= urlencode($status_filter) ?>&sort=asc" 
           class="btn btn-outline-secondary <?= $sortOrder === 'asc' ? 'active' : '' ?>">Sort Asc</a>
        <a href="?page=<?= $page ?>&category=<?= urlencode($categoryFilter) ?>&priority=<?= urlencode($priorityFilter) ?>&status=<?= urlencode($status_filter) ?>&sort=desc" 
           class="btn btn-outline-secondary <?= $sortOrder === 'desc' ? 'active' : '' ?>">Sort Desc</a>
      </div>
    </div>
  </div>
  <form id="filterForm" method="get" style="display: none;">
    <input type="hidden" name="page" value="<?= $page ?>">
  </form>

  <?php if (empty($tasks)): ?>
    <p class="text-center">No tasks found.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead class="table-light">
          <tr>
            <th>Due Date</th>
            <th>Title</th>
            <th>Category</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tasks as $task): ?>
            <tr>
              <td><?= date('Y-m-d', strtotime($task['due_date'])) ?></td>
              <td><?= htmlspecialchars($task['title']) ?></td>
              <td><?= htmlspecialchars($task['category_name'] ?? 'Uncategorized') ?></td>
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

    <!-- Pagination -->
    <nav aria-label="Page navigation" style="padding-top:1em">
      <ul class="pagination justify-content-center">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
          <a class="page-link" href="?page=<?= $page - 1 ?>&category=<?= urlencode($categoryFilter) ?>&priority=<?= urlencode($priorityFilter) ?>&status=<?= urlencode($status_filter) ?>&sort=<?= $sortOrder ?>" aria-label="Previous">
            <span aria-hidden="true">«</span>
          </a>
        </li>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>&category=<?= urlencode($categoryFilter) ?>&priority=<?= urlencode($priorityFilter) ?>&status=<?= urlencode($status_filter) ?>&sort=<?= $sortOrder ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
          <a class="page-link" href="?page=<?= $page + 1 ?>&category=<?= urlencode($categoryFilter) ?>&priority=<?= urlencode($priorityFilter) ?>&status=<?= urlencode($status_filter) ?>&sort=<?= $sortOrder ?>" aria-label="Next">
            <span aria-hidden="true">»</span>
          </a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>