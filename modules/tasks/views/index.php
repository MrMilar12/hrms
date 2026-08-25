<?php
/** @var array $tasks */
/** @var bool $canCreate */
/** @var array $statuses */
require MODULES_PATH . '/shared/views/header.php';

$columns = [];
foreach ($statuses as $s) { $columns[$s] = []; }
foreach ($tasks as $task) { $columns[$task['status']][] = $task; }

$priorityClass = fn($p) => 'priority-' . strtolower($p);
?>
<div class="glass-card" style="display:flex; justify-content:space-between; align-items:center;">
    <h2 style="margin:0;">Tasks</h2>
    <div style="display:flex;gap:0.55rem;">
        <a class="btn btn-secondary" href="<?= BASE_URL ?>/tasks/calendar">&#128197; Calendar</a>
        <?php if ($canCreate): ?>
        <a class="btn btn-primary" href="<?= BASE_URL ?>/tasks/create">+ Create Task</a>
        <?php endif; ?>
    </div>
</div>

<div class="kanban-board">
    <?php foreach ($columns as $status => $items): ?>
        <div class="kanban-column glass-light">
            <div class="kanban-column-title">
                <span><?= htmlspecialchars($status) ?></span>
                <span class="kanban-count"><?= count($items) ?></span>
            </div>
            <?php foreach ($items as $task): ?>
                <a class="task-card" href="<?= BASE_URL ?>/tasks/<?= (int) $task['id'] ?>">
                    <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                    <div class="task-meta">
                        <span><span class="priority-dot <?= $priorityClass($task['priority']) ?>"></span><?= htmlspecialchars($task['priority']) ?></span>
                        <span><?= htmlspecialchars($task['due_date'] ?? 'No due date') ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
            <?php if (!$items): ?>
                <p style="color:var(--text-muted); font-size:0.8rem; text-align:center; padding:1rem 0;">No tasks</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
