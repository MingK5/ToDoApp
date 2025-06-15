<?php
// /ToDoApp/tasks/archive.php
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');

// Redirect if user is not logged in
if (empty($_SESSION['user_id'])) {
    header('Location: /ToDoApp/');
    exit;
}

// Prevent browser caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Include database connection
include __DIR__ . '/../includes/db.php';

// Generate CSRF token for form security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

// Handle tasks per page (limit) from query parameter
$validLimits = [3, 5, 10];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $validLimits) ? (int)$_GET['limit'] : 5;

// Handle sort option from query parameter
$validSorts = [
    'title' => 't.title ASC',
    'priority' => 't.priority DESC',
    'updated_time' => 't.updated_at DESC'
];
$sort = isset($_GET['sort']) && array_key_exists($_GET['sort'], $validSorts) ? $_GET['sort'] : 'updated_time';
$sortSql = $validSorts[$sort];

// Handle pagination
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;

// Get total archived tasks
try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM tasks t WHERE t.archived = 1 AND t.user_id = :uid");
    $countStmt->execute([':uid' => $_SESSION['user_id']]);
    $totalTasks = $countStmt->fetchColumn();
    $totalPages = ceil($totalTasks / $limit);
} catch (PDOException $e) {
    error_log("Count query failed: " . $e->getMessage());
    die("An error occurred while fetching task count. Please try again later.");
}

$page = min($page, $totalPages ? $totalPages : 1);
$start = ($page - 1) * $limit;

// Get archived tasks with category name
try {
    $query = "
        SELECT t.id, t.title, t.description, t.priority, t.updated_at, t.status, c.name as category
        FROM tasks t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.archived = 1 AND t.user_id = :uid
        ORDER BY $sortSql
        LIMIT :start, :limit
    ";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':uid', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Query failed: " . $e->getMessage());
    die("An error occurred while fetching tasks. Please try again later.");
}

$pageTitle = 'ToDoApp: Archive';
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <!-- Notification banners -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Page header -->
    <h4 class="text-center text-decoration-underline">Task Archiving</h4>
    <h2>📁 Archived Tasks</h2>

    <!-- Sort controls -->
    <div class="d-flex justify-content-end mb-3">
        <div class="dropdown">
            <button class="btn btn-outline-primary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                Sort by: <?= ucfirst(str_replace('_', ' ', $sort)) ?>
            </button>
            <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                <li><a class="dropdown-item <?= $sort === 'updated_time' ? 'active' : '' ?>" href="?page=1&limit=<?= $limit ?>&sort=updated_time">Updated Time</a></li>
                <li><a class="dropdown-item <?= $sort === 'title' ? 'active' : '' ?>" href="?page=1&limit=<?= $limit ?>&sort=title">Title</a></li>
                <li><a class="dropdown-item <?= $sort === 'priority' ? 'active' : '' ?>" href="?page=1&limit=<?= $limit ?>&sort=priority">Priority</a></li>
            </ul>
        </div>
    </div>

    <!-- Task list -->
    <?php if (empty($tasks)): ?>
        <p class="text-muted">No archived tasks found.</p>
    <?php else: ?>
        <?php foreach ($tasks as $task): ?>
            <div class="card mb-2">
                <div class="card-body">
                    <strong><?= htmlspecialchars($task['title']) ?></strong>
                    <span class="badge bg-<?= $task['status'] === 'Completed' ? 'success' : 'warning' ?>">
                        <?= htmlspecialchars($task['status']) ?>
                    </span><br>
                    <em>Category: <?= htmlspecialchars($task['category'] ?: 'None') ?></em><br>
                    <span>Description: <?= htmlspecialchars($task['description'] ?: 'No description') ?></span><br>
                    <span>Priority: <?= htmlspecialchars($task['priority']) ?></span><br>
                    <span>Updated: <?= htmlspecialchars($task['updated_at']) ?></span><br>
                    <!-- Delete and unarchive buttons -->
                    <button class="btn btn-danger btn-sm mt-2 me-2" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $task['id'] ?>">Delete</button>
                    <button class="btn btn-success btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#unarchiveModal<?= $task['id'] ?>">Unarchive</button>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal fade" id="deleteModal<?= $task['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $task['id'] ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteModalLabel<?= $task['id'] ?>">Confirm Delete</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to delete the task "<strong><?= htmlspecialchars($task['title']) ?></strong>"? This action cannot be undone.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <form method="post" action="/ToDoApp/tasks/delete_task.php" class="d-inline">
                                <input type="hidden" name="task_id" value="<?= htmlspecialchars($task['id']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unarchive Confirmation Modal -->
            <div class="modal fade" id="unarchiveModal<?= $task['id'] ?>" tabindex="-1" aria-labelledby="unarchiveModalLabel<?= $task['id'] ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="unarchiveModalLabel<?= $task['id'] ?>">Confirm Unarchive</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to unarchive the task "<strong><?= htmlspecialchars($task['title']) ?></strong>"? It will be moved back to your active tasks.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <form method="post" action="/ToDoApp/tasks/unarchive_task.php" class="d-inline">
                                <input type="hidden" name="task_id" value="<?= htmlspecialchars($task['id']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <button type="submit" class="btn btn-success">Unarchive</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Pagination and limit controls -->
    <?php if ($totalTasks > 0): ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&limit=<?= $limit ?>&sort=<?= urlencode($sort) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <form class="d-flex align-items-center">
                <label for="limitSelect" class="me-2">Tasks per page:</label>
                <select id="limitSelect" name="limit" class="form-select" style="width: auto;" onchange="this.form.submit()">
                    <?php foreach ($validLimits as $opt): ?>
                        <option value="<?= $opt ?>" <?= $limit === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="page" value="1">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>