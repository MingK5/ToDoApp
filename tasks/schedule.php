<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: /ToDoApp/');
    exit;
}

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Handle archive action
if (isset($_GET['action']) && $_GET['action'] === 'archive' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE tasks SET archived = 1 WHERE id = ? AND user_id = ?"); // Fixed to include user_id
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    header('Location: ' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&category=' . urlencode($_GET['category'] ?? '') . '&priority=' . urlencode($_GET['priority'] ?? '') . '&sort=' . ($_GET['sort'] ?? 'asc'));
    exit;
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?"); // Fixed to include user_id
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    header('Location: ' . $_SERVER['PHP_SELF'] . '?page=' . $page . '&category=' . urlencode($_GET['category'] ?? '') . '&priority=' . urlencode($_GET['priority'] ?? '') . '&sort=' . ($_GET['sort'] ?? 'asc'));
    exit;
}

// Pagination settings
$datesPerPage = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $datesPerPage;

// Filter and sort parameters
$categoryFilter = isset($_GET['category']) ? urldecode($_GET['category']) : '';
$priorityFilter = isset($_GET['priority']) ? urldecode($_GET['priority']) : '';
$sortOrder = isset($_GET['sort']) ? $_GET['sort'] : 'asc';

// Get total number of distinct dates with filters
$sql = "SELECT COUNT(DISTINCT DATE(due_date)) as total_dates FROM tasks WHERE user_id = ? AND archived = 0";
$params = [$_SESSION['user_id']];
if ($categoryFilter) {
    $sql .= " AND category_id = (SELECT id FROM categories WHERE name = ?)";
    $params[] = $categoryFilter;
}
if ($priorityFilter) {
    $sql .= " AND priority = ?";
    $params[] = $priorityFilter;
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$totalDates = $stmt->fetchColumn();
$totalPages = ceil($totalDates / $datesPerPage);

// Get distinct dates with limit for pagination and filters
$sql = "
    SELECT DISTINCT DATE(due_date) as due_date
    FROM tasks
    WHERE user_id = ? AND archived = 0";
$params = [$_SESSION['user_id']];
if ($categoryFilter) {
    $sql .= " AND category_id = (SELECT id FROM categories WHERE name = ?)";
    $params[] = $categoryFilter;
}
if ($priorityFilter) {
    $sql .= " AND priority = ?";
    $params[] = $priorityFilter;
}
$sql .= " ORDER BY due_date " . ($sortOrder === 'desc' ? 'DESC' : 'ASC') . " LIMIT ? OFFSET ?";
$params[] = $datesPerPage;
$params[] = $offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get tasks for the selected dates with category, dates, and filters
$tasksByDate = [];
if (!empty($dates)) {
    $placeholders = implode(',', array_fill(0, count($dates), '?'));
    $sql = "
        SELECT t.id, t.title, t.description, t.due_date, t.status, t.priority, c.name as category_name, t.created_at, t.updated_at
        FROM tasks t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?";
    $params = [$_SESSION['user_id']];
    if ($categoryFilter) {
        $sql .= " AND t.category_id = (SELECT id FROM categories WHERE name = ?)";
        $params[] = $categoryFilter;
    }
    if ($priorityFilter) {
        $sql .= " AND t.priority = ?";
        $params[] = $priorityFilter;
    }
    $sql .= " AND DATE(t.due_date) IN ($placeholders) AND t.archived = 0 ORDER BY t.due_date " . ($sortOrder === 'desc' ? 'DESC' : 'ASC');
    $stmt = $pdo->prepare($sql);
    $params = array_merge($params, $dates);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group tasks by date
    foreach ($tasks as $task) {
        $date = date('Y-m-d', strtotime($task['due_date']));
        $tasksByDate[$date][] = $task;
    }
}

// Hardcoded priorities
$priorities = ['Low', 'Medium', 'High'];

// Fetch unique categories for the filter
$stmt = $pdo->prepare("SELECT DISTINCT name FROM categories");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
$categories = array_map('strval', $categories);

$pageTitle = 'ToDoApp: Schedule';
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h4 class="text-center text-decoration-underline mb-4">Schedule: Task Monitoring</h4>

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-md-4">
            <select class="form-select" name="category" onchange="this.form.submit()" form="filterForm">
                <option value="">All Categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= htmlspecialchars($category) ?>" <?= $categoryFilter === $category ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <select class="form-select" name="priority" onchange="this.form.submit()" form="filterForm">
                <option value="">All Priorities</option>
                <?php foreach ($priorities as $priority): ?>
                    <option value="<?= htmlspecialchars($priority) ?>" <?= $priorityFilter === $priority ? 'selected' : '' ?>>
                        <?= htmlspecialchars($priority) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <div class="btn-group">
                <a href="?page=<?= $page ?>&category=<?= urlencode($categoryFilter) ?>&priority=<?= urlencode($priorityFilter) ?>&sort=asc" 
                   class="btn btn-outline-secondary <?= $sortOrder === 'asc' ? 'active' : '' ?>">Sort Asc</a>
                <a href="?page=<?= $page ?>&category=<?= urlencode($categoryFilter) ?>&priority=<?= urlencode($priorityFilter) ?>&sort=desc" 
                   class="btn btn-outline-secondary <?= $sortOrder === 'desc' ? 'active' : '' ?>">Sort Desc</a>
            </div>
        </div>
    </div>
    <form id="filterForm" method="get" style="display: none;">
        <input type="hidden" name="page" value="<?= $page ?>">
    </form>

    <?php if (empty($tasksByDate)): ?>
        <p class="text-center">No tasks found.</p>
    <?php else: ?>
        <div class="accordion" id="tasksAccordion">
            <?php foreach ($tasksByDate as $date => $tasks): ?>
                <div class="accordion-item" style="padding-bottom: 0.5em;">
                    <h2 class="accordion-header" id="heading<?= str_replace('-', '', $date) ?>">
                        <button class="accordion-button" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#collapse<?= str_replace('-', '', $date) ?>" 
                                aria-expanded="true" aria-controls="collapse<?= str_replace('-', '', $date) ?>" 
                                style="background-color: #2F4F4F; color: white;">
                            <?= date('F j, Y', strtotime($date)) ?>
                        </button>
                    </h2>
                    <div id="collapse<?= str_replace('-', '', $date) ?>" 
                         class="accordion-collapse collapse show" 
                         aria-labelledby="heading<?= str_replace('-', '', $date) ?>">
                        <div class="accordion-body">
                            <ul class="list-group">
                                <?php foreach ($tasks as $task): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?= htmlspecialchars($task['title']) ?>
                                                    <span class="badge 
                                                        <?= $task['status'] === 'On-going' ? 'bg-warning' : 
                                                            ($task['status'] === 'Completed' ? 'bg-success' : 'bg-secondary') ?> ms-2">
                                                        <?= ucfirst($task['status']) ?>
                                                    </span>
                                                </h6>
                                                <small class="text-primary mb-1" style="font-style:italic">
                                                    Category: <?= htmlspecialchars($task['category_name'] ?? 'Uncategorized') ?>
                                                </small><br>
                                                <small class="mb-1">Description: <?= htmlspecialchars($task['description']) ?></small><br>
                                                <small class="mb-1">Priority: <?= htmlspecialchars($task['priority']) ?></small><br>
                                                <small class="mb-1">Created: <?= date('Y-m-d H:i', strtotime($task['created_at'])) ?></small><br>
                                                <small class="mb-1">Updated: <?= date('Y-m-d H:i', strtotime($task['updated_at'])) ?></small>
                                            </div>
                                            <div>
                                              <a href="?action=archive&id=<?= $task['id'] ?>&page=<?= $page ?>&category=<?= urlencode($categoryFilter) ?>&priority=<?= urlencode($priorityFilter) ?>&sort=<?= $sortOrder ?>" 
                                                class="btn btn-outline-danger btn-sm" 
                                                onclick="return confirm('Archive this task?')">
                                                Archive
                                              </a>
                                              <a href="?action=delete&id=<?= $task['id'] ?>&page=<?= $page ?>&category=<?= urlencode($categoryFilter) ?>&priority=<?= urlencode($priorityFilter) ?>&sort=<?= $sortOrder ?>" 
                                                class="btn btn-danger btn-sm me-1" 
                                                onclick="return confirm('Are you sure you want to permanently delete this task?')">
                                                Delete
                                              </a>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation" style="padding-top:1em">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&category=<?= urlencode($categoryFilter) ?>&priority=<?= urlencode($priorityFilter) ?>&sort=<?= $sortOrder ?>" aria-label="Previous">
                        <span aria-hidden="true">«</span>
                    </a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&category=<?= urlencode($categoryFilter) ?>&priority=<?= urlencode($priorityFilter) ?>&sort=<?= $sortOrder ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&category=<?= urlencode($categoryFilter) ?>&priority=<?= urlencode($priorityFilter) ?>&sort=<?= $sortOrder ?>" aria-label="Next">
                        <span aria-hidden="true">»</span>
                    </a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>