<?php
// /todoapp/tasks/calendar.php
require_once __DIR__ . '/../includes/db.php';
session_start();

// redirect if not logged in
if (empty($_SESSION['user_id'])) {
  header('Location: /ToDoApp/');
  exit;
}

// force no-cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// determine year/month from query or default to today
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

// build DateTime for the first of this month
$dt = new DateTimeImmutable("{$year}-{$month}-01");

// prev/next month links
$prev = $dt->modify('-1 month');
$next = $dt->modify('+1 month');

// calculate start day (Monday=0..Sunday=6)
$startDow = ((int)$dt->format('w') + 6) % 7;
// total days in month
$daysInMonth = (int)$dt->format('t');

// fetch all this month’s tasks for the user
$startDate = $dt->format('Y-m-01');
$endDate   = $dt->format('Y-m-t');
$stmt = $pdo->prepare("
  SELECT id, title, due_date, status 
  FROM tasks 
  WHERE user_id = :uid
    AND archived = 0
    AND due_date BETWEEN :start AND :end
");
$stmt->execute([
  ':uid'   => $_SESSION['user_id'],
  ':start' => $startDate,
  ':end'   => $endDate
]);
$rows = $stmt->fetchAll();

// group tasks by date
$tasksByDay = [];
foreach ($rows as $r) {
  $tasksByDay[$r['due_date']][] = $r;
}

// build calendar grid (array of weeks → array of 7 cells; null = blank)
$weeks = [];
$dayNum = 1 - $startDow;
while ($dayNum <= $daysInMonth) {
  $week = [];
  for ($dow = 0; $dow < 7; $dow++, $dayNum++) {
    if ($dayNum < 1 || $dayNum > $daysInMonth) {
      $week[] = null;
    } else {
      $week[] = $dt->format('Y-m-') . str_pad($dayNum, 2, '0', STR_PAD_LEFT);
    }
  }
  $weeks[] = $week;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a href="?year=<?= $prev->format('Y') ?>&month=<?= $prev->format('n') ?>"
       class="btn btn-outline-primary">&laquo; Prev</a>
    <h4 class="m-0"><?= $dt->format('F Y') ?></h4>
    <a href="?year=<?= $next->format('Y') ?>&month=<?= $next->format('n') ?>"
       class="btn btn-outline-primary">Next &raquo;</a>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <table class="table table-bordered mb-0 text-center">
        <thead class="table-light">
          <tr>
            <th>Mon</th><th>Tue</th><th>Wed</th>
            <th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($weeks as $week): ?>
          <tr>
            <?php foreach ($week as $date): ?>
              <?php if (!$date): ?>
                <td class="bg-light" style="height:100px;"></td>
              <?php else: ?>
                <?php 
                  $isToday = $date === date('Y-m-d');
                  $badgeClass = $isToday ? 'bg-info text-white' : '';
                ?>
                <td style="vertical-align: top; height:100px;">
                  <div class="d-flex justify-content-between">
                    <a href="/todoapp/day/<?= $date ?>" class="fw-bold <?= $badgeClass ?>">
                      <?= (int)substr($date, 8, 2) ?>
                    </a>
                    <a href="/todoapp/tasks/task_add.php?date=<?= $date ?>" 
                       class="text-success" title="Add Task">
                      <i class="bi bi-plus-circle"></i>
                    </a>
                  </div>

                  <?php if (!empty($tasksByDay[$date])): ?>
                    <ul class="list-unstyled small mt-1">
                      <?php foreach ($tasksByDay[$date] as $t): 
                        // map status to badge color
                        $statusColor = match($t['status']) {
                          'Completed' => 'success',
                          'On-going'  => 'warning',
                          default     => 'secondary'
                        };
                      ?>
                        <li>
                          <a href="/todoapp/tasks/task_edit.php?id=<?= $t['id'] ?>"
                             class="badge bg-<?= $statusColor ?> text-decoration-none">
                            <?= htmlspecialchars($t['title']) ?>
                          </a>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                </td>
              <?php endif; ?>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
