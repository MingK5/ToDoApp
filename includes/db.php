<?php
// includes/db.php

// 1) Credentials
$dbHost = 'localhost';
$dbName = 'todo_app';
$dbUser = 'todo_user';
$dbPass = 'todoapp123';
$charset = 'utf8mb4';

// 2) Data Source Name
$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=$charset";

// 3) PDO options
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
  $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (\PDOException $e) {
  // In production, log this and show a generic error page instead
  throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
