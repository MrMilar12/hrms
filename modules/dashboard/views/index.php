<?php
/** @var int $pdsPercent */
/** @var array $completion */
/** @var array $taskCounts */
/** @var int $totalEmployees */
/** @var int $totalTasks */
/** @var int $pendingReview */
/** @var array $notifications */
/** @var array $myAccomplishmentCounts */
/** @var array|null $accomplishmentCounts */
require MODULES_PATH . '/shared/views/header.php';

$doneSections = count(array_filter($completion));
$totalSections = count($completion) ?: 1;
?>
<div class="stat-grid">
    <div class="stat-card glass">
        <div class="stat-icon">&#128101;</div>
        <div class="stat-title">Employees</div>
        <div class="stat-value"><?= number_format($totalEmployees) ?></div>
        <div class="stat-sub">Across all departments</div>
    </div>
    <div class="stat-card glass">
        <div class="stat-icon">&#128203;</div>
        <div class="stat-title">PDS Completion</div>
        <div class="stat-value"><?= $pdsPercent ?>%</div>
        <div class="stat-sub"><?= $doneSections ?>/<?= $totalSections ?> sections done</div>
    </div>
    <div class="stat-card glass">
        <div class="stat-icon">&#10003;</div>
        <div class="stat-title">Tasks</div>
        <div class="stat-value"><?= number_format($totalTasks) ?></div>
        <div class="stat-sub">Organization-wide</div>
    </div>
    <div class="stat-card glass">
        <div class="stat-icon">&#8987;</div>
        <div class="stat-title">Pending Reviews</div>
        <div class="stat-value"><?= number_format($pendingReview) ?></div>
        <div class="stat-sub">Awaiting sign-off</div>
    </div>
    <?php if ($accomplishmentCounts !== null): ?>
        <div class="stat-card glass">
            <div class="stat-icon">&#128247;</div>
            <div class="stat-title">Accomplishment Monitoring</div>
            <div class="stat-value"><?= number_format(array_sum($accomplishmentCounts)) ?></div>
            <div class="stat-sub"><?= $accomplishmentCounts['Approved'] ?> Approved &middot; <?= $accomplishmentCounts['For Review'] ?> For Review &middot; <?= $accomplishmentCounts['Returned'] ?> Returned</div>
        </div>
    <?php else: ?>
        <div class="stat-card glass">
            <div class="stat-icon">&#128247;</div>
            <div class="stat-title">My Accomplishments</div>
            <div class="stat-value"><?= number_format(array_sum($myAccomplishmentCounts)) ?></div>
            <div class="stat-sub"><?= $myAccomplishmentCounts['Approved'] ?? 0 ?> Approved &middot; <?= $myAccomplishmentCounts['For Review'] ?? 0 ?> For Review &middot; <?= $myAccomplishmentCounts['Draft'] ?? 0 ?> Draft</div>
        </div>
    <?php endif; ?>
</div>

<div class="glass-card">
    <h3 style="margin-top:0;">PDS Completion</h3>
    <div class="progress-bar"><div class="progress-bar-fill" data-target="<?= $pdsPercent ?>"></div></div>
    <p style="color:var(--text-muted); font-size:0.85rem;"><?= $pdsPercent ?>% complete &mdash; <a href="<?= BASE_URL ?>/pds">Continue editing my PDS</a></p>
    <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
        <?php foreach ($completion as $section => $done): ?>
            <span class="badge <?= $done ? 'badge-done' : 'badge-open' ?>">
                <?= $done ? '✓' : '○' ?> <?= htmlspecialchars(ucwords(str_replace('_', ' ', $section))) ?>
            </span>
        <?php endforeach; ?>
    </div>
</div>

<div class="glass-card">
    <h3 style="margin-top:0;">My Tasks</h3>
    <table>
        <thead><tr><th>Status</th><th>Count</th></tr></thead>
        <tbody>
        <?php foreach (['Open','In Progress','For Review','Done','Cancelled'] as $status): ?>
            <tr><td><span class="badge badge-<?= strtolower(str_replace(' ', '-', $status)) ?>"><?= $status ?></span></td><td><?= (int) ($taskCounts[$status] ?? 0) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p><a href="<?= BASE_URL ?>/tasks">View all my tasks &rarr;</a></p>
</div>

<div class="glass-card">
    <h3 style="margin-top:0;">My Accomplishments</h3>
    <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
        <?php foreach ($myAccomplishmentCounts as $status => $count): ?>
            <span class="badge badge-<?= strtolower(str_replace(' ', '-', $status === 'Approved' ? 'done' : ($status === 'Returned' ? 'cancelled' : $status))) ?>"><?= htmlspecialchars($status) ?>: <?= $count ?></span>
        <?php endforeach; ?>
    </div>
    <p style="margin-top:0.75rem;"><a href="<?= BASE_URL ?>/accomplishments">View all &rarr;</a></p>
</div>

<div class="glass-card">
    <h3 style="margin-top:0;">Notifications</h3>
    <?php foreach ($notifications as $n): ?>
        <div class="dropdown-item <?= $n['is_read'] ? 'read' : '' ?>">
            <span class="status-dot"></span>
            <span><?= htmlspecialchars($n['message']) ?> <small style="color:var(--text-muted);"><?= htmlspecialchars($n['created_at']) ?></small></span>
        </div>
    <?php endforeach; ?>
    <?php if (!$notifications): ?><p style="color:var(--text-muted);">No notifications.</p><?php endif; ?>
</div>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
