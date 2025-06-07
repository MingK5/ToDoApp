<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: /ToDoApp/');
  exit;
}

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$pageTitle = 'ToDoApp: Schedule';
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <h4 class="text-center text-decoration-underline">Schedule: Task Monitoring</h4>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>