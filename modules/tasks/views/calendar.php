<?php
/** @var DateTimeImmutable $month */
/** @var array $tasksByDate */
/** @var bool $canCreate */
require MODULES_PATH . '/shared/views/header.php';

$firstDay = $month->modify('first day of this month');
$gridStart = $firstDay->modify('monday this week');
$lastDay = $month->modify('last day of this month');
$gridEnd = $lastDay->modify('sunday this week');
$previousMonth = $month->modify('-1 month')->format('Y-m');
$nextMonth = $month->modify('+1 month')->format('Y-m');
$today = date('Y-m-d');
$priorityClass = fn(string $priority) => 'priority-' . strtolower($priority);
?>
<section class="task-calendar-page">
    <div class="calendar-toolbar glass-card">
        <div><span class="launcher-eyebrow">Task management</span><h1><?= htmlspecialchars($month->format('F Y')) ?></h1><p>Review assignments by due date.</p></div>
        <div class="calendar-toolbar-actions">
            <a class="btn btn-secondary" href="<?= BASE_URL ?>/tasks">&#9638; Board</a>
            <?php if ($canCreate): ?><a class="btn btn-primary" href="<?= BASE_URL ?>/tasks/create">+ Create Task</a><?php endif; ?>
            <div class="calendar-month-nav"><a href="?month=<?= $previousMonth ?>" aria-label="Previous month">&larr;</a><a href="?month=<?= date('Y-m') ?>">Today</a><a href="?month=<?= $nextMonth ?>" aria-label="Next month">&rarr;</a></div>
        </div>
    </div>

    <div class="task-calendar glass">
        <div class="calendar-weekdays"><?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day): ?><span><?= $day ?></span><?php endforeach; ?></div>
        <div class="calendar-grid">
            <?php for ($date = $gridStart; $date <= $gridEnd; $date = $date->modify('+1 day')): ?>
                <?php $key = $date->format('Y-m-d'); $dayTasks = $tasksByDate[$key] ?? []; ?>
                <article class="calendar-day <?= $date->format('m') !== $month->format('m') ? 'outside-month' : '' ?> <?= $key === $today ? 'today' : '' ?>">
                    <header><span><?= $date->format('j') ?></span><?php if ($dayTasks): ?><small><?= count($dayTasks) ?></small><?php endif; ?></header>
                    <div class="calendar-day-tasks">
                        <?php foreach ($dayTasks as $task): ?>
                            <a class="calendar-task" href="<?= BASE_URL ?>/tasks/<?= UrlId::encode((int) $task['id']) ?>" title="<?= htmlspecialchars($task['title']) ?>">
                                <span class="priority-dot <?= $priorityClass($task['priority']) ?>"></span>
                                <span><strong><?= htmlspecialchars($task['title']) ?></strong><small><?= htmlspecialchars($task['status']) ?><?= $task['department_name'] ? ' · ' . htmlspecialchars($task['department_name']) : '' ?></small></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endfor; ?>
        </div>
    </div>
</section>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
