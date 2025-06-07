<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: /ToDoApp/');
  exit;
}

// force no-cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
