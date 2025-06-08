<?php
// includes/header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo isset($pageTitle) ? $pageTitle : 'ToDoApp'; ?></title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Your custom styles (if any) -->
  <link rel="stylesheet" href="/ToDoApp/assets/css/style.css">
</head>
<body class="bg-light">

<?php if (!empty($_SESSION['user_id'])): ?>
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
      <a class="navbar-brand" href="/ToDoApp/calendar">ToDoApp</a>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="/ToDoApp/calendar">Calendar</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/ToDoApp/schedule">Schedule</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/ToDoApp/status">Status</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/ToDoApp/archive">Archive</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/ToDoApp/logout">Logout</a>
        </li>
      </ul>
    </div>
  </nav>
<?php endif; ?>

<main class="py-4">
