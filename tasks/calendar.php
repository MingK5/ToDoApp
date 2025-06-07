<?php
// /ToDoApp/tasks/calendar.php
require_once __DIR__ . '/../includes/db.php';
session_start();

// 1) redirect if not logged in
if (empty($_SESSION['user_id'])) {
  header('Location: /ToDoApp/');
  exit;
}

// 2) prevent browser caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 3) determine year/month
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$dt    = new DateTimeImmutable("{$year}-{$month}-01");

// 4) prev/next
$prev = $dt->modify('-1 month');
$next = $dt->modify('+1 month');

// 5) fetch tasks in this month
$start = $dt->format('Y-m-01');
$end   = $dt->format('Y-m-t');
$stmt = $pdo->prepare("
  SELECT id, title, due_date, status
  FROM tasks
  WHERE user_id = :uid
    AND archived = 0
    AND due_date BETWEEN :start AND :end
");
$stmt->execute([
  ':uid'   => $_SESSION['user_id'],
  ':start' => $start,
  ':end'   => $end,
]);
$rows = $stmt->fetchAll();

// group by date
$tasksByDay = [];
foreach ($rows as $r) {
  $tasksByDay[$r['due_date']][] = $r;
}

// 6) build calendar grid
$startDow    = ((int)$dt->format('w') + 6) % 7; // Mon=0…Sun=6
$daysInMonth = (int)$dt->format('t');
$weeks = [];
$day = 1 - $startDow;
while ($day <= $daysInMonth) {
  $week = [];
  for ($i = 0; $i < 7; $i++, $day++) {
    if ($day < 1 || $day > $daysInMonth) {
      $week[] = null;
    } else {
      $week[] = $dt->format('Y-m-') . str_pad($day,2,'0',STR_PAD_LEFT);
    }
  }
  $weeks[] = $week;
}

$pageTitle = 'ToDoApp: Calendar';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <h4 class="text-center text-decoration-underline">Calendar: Task Recording</h4>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a href="?year=<?= $prev->format('Y') ?>&month=<?= $prev->format('n') ?>"
       class="btn btn-outline-primary">&laquo; Prev</a>
    <h4 class="m-0"><?= $dt->format('F Y') ?></h4>
    <a href="?year=<?= $next->format('Y') ?>&month=<?= $next->format('n') ?>"
       class="btn btn-outline-primary">Next &raquo;</a>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <table class="table table-bordered mb-0 text-center" style="table-layout: fixed;">
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
            <?php else: 
              date_default_timezone_set('Asia/Kuala_Lumpur');
              $isToday    = $date === date('Y-m-d');
              $badgeClass = $isToday ? 'bg-info text-white' : '';

              // slice out up to 3 tasks
              $all  = $tasksByDay[$date] ?? [];
              $slice = array_slice($all, 0, 3);
              $extra = count($all) - count($slice);
            ?>
              <td style="vertical-align: top; height:100px; padding:4px;">
                <div class="d-flex justify-content-between">
                  <span class="fw-bold <?= $badgeClass ?>">
                    <?= (int)substr($date,8,2) ?>
                  </span>
                  <a href="/ToDoApp/tasks/task_add.php?date=<?= $date ?>"
                     onclick="window.open(this.href,'AddTask','width=600,height=600');return false;"
                     class="text-success" title="Add Task">
                    <i class="bi bi-plus-circle"></i>
                  </a>
                </div>

                <?php if ($slice): ?>
                  <ul class="list-unstyled small mt-1"
                      style="
                        max-height: calc(1.4em * 3);
                        overflow: hidden;
                        margin:0;
                        padding:0;
                      ">
                    <?php foreach ($slice as $t):
                      $col = match($t['status']) {
                        'Completed' => 'success',
                        'On-going'  => 'warning',
                        default     => 'secondary'
                      };
                    ?>
                      <li style="margin-bottom:2px;">
                        <span class="badge bg-<?= $col ?> truncate-1">
                          <?= htmlspecialchars($t['title']) ?>
                        </span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                  <?php if ($extra > 0): ?>
                    <div class="small text-muted">+<?= $extra ?> more</div>
                  <?php endif; ?>
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
