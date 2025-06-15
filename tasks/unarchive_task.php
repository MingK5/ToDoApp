<?php
// /ToDoApp/tasks/unarchive_task.php
session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');

// Redirect if user is not logged in
if (empty($_SESSION['user_id'])) {
    header('Location: /ToDoApp/');
    exit;
}

// Include database connection
include __DIR__ . '/../includes/db.php';

// Validate request method and CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: /ToDoApp/tasks/archive.php');
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Invalid CSRF token.';
    header('Location: /ToDoApp/tasks/archive.php');
    exit;
}

// Validate task_id
if (!isset($_POST['task_id']) || !filter_var($_POST['task_id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error'] = 'Invalid task ID.';
    header('Location: /ToDoApp/tasks/archive.php');
    exit;
}

$task_id = (int)$_POST['task_id'];
$user_id = $_SESSION['user_id'];

try {
    // Check if the task exists and belongs to the user
    $stmt = $pdo->prepare("SELECT id FROM tasks WHERE id = :task_id AND user_id = :user_id AND archived = 1");
    $stmt->execute([':task_id' => $task_id, ':user_id' => $user_id]);
    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = 'Task not found or not archived.';
        header('Location: /ToDoApp/tasks/archive.php');
        exit;
    }

    // Unarchive the task
    $stmt = $pdo->prepare("UPDATE tasks SET archived = 0, updated_at = NOW() WHERE id = :task_id");
    $stmt->execute([':task_id' => $task_id]);

    $_SESSION['success'] = 'Task unarchived successfully.';
    header('Location: /ToDoApp/tasks/archive.php');
    exit;
} catch (PDOException $e) {
    error_log("Unarchive failed: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred while unarchiving the task. Please try again later.';
    header('Location: /ToDoApp/tasks/archive.php');
    exit;
}
?>