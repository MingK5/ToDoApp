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
    $stmt = $pdo->prepare("UPDATE tasks SET archived = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    header('Location: ' . $_SERVER['PHP_SELF'] . '?page=' . $page);
    exit;
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    header('Location: ' . $_SERVER['PHP_SELF'] . '?page=' . $page);
    exit;
}

// Pagination settings
$datesPerPage = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $datesPerPage;

// Get total number of distinct dates
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT DATE(due_date)) as total_dates FROM tasks WHERE user_id = ? AND archived = 0");
$stmt->execute([$_SESSION['user_id']]);
$totalDates = $stmt->fetchColumn();
$totalPages = ceil($totalDates / $datesPerPage);

// Get distinct dates with limit for pagination
$stmt = $pdo->prepare("
    SELECT DISTINCT DATE(due_date) as due_date
    FROM tasks
    WHERE user_id = ? AND archived = 0
    ORDER BY due_date ASC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindValue(2, $datesPerPage, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get tasks for the selected dates with category and dates
$tasksByDate = [];
if (!empty($dates)) {
    $placeholders = implode(',', array_fill(0, count($dates), '?'));
    $stmt = $pdo->prepare("
        SELECT t.id, t.title, t.description, t.due_date, t.status, t.priority, c.name as category_name, t.created_at, t.updated_at
        FROM tasks t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND DATE(t.due_date) IN ($placeholders) AND t.archived = 0
        ORDER BY t.due_date ASC
    ");
    $params = array_merge([$_SESSION['user_id']], $dates);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group tasks by date
    foreach ($tasks as $task) {
        $date = date('Y-m-d', strtotime($task['due_date']));
        $tasksByDate[$date][] = $task;
    }
}

$pageTitle = 'ToDoApp: Schedule';
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h4 class="text-center text-decoration-underline mb-4">Schedule: Task Monitoring</h4>

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
                                                        <?= $task['status'] === 'Ongoing' ? 'bg-warning' : 
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
                                              <a href="?action=archive&id=<?= $task['id'] ?>&page=<?= $page ?>" 
                                                class="btn btn-outline-danger btn-sm" 
                                                onclick="return confirm('Archive this task?')">
                                                Archive
                                              </a>
                                              <a href="?action=delete&id=<?= $task['id'] ?>&page=<?= $page ?>" 
                                                class="btn btn-danger btn-sm me-1" 
                                                onclick="return confirm('Are you sure you want to permanently delete this task?')">
                                                Delete
                                              </a>
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
                    <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                        <span aria-hidden="true">«</span>
                    </a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                        <span aria-hidden="true">»</span>
                    </a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>