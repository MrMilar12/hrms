<?php
/** @var array $position */
/** @var array $holders */
/** @var array $history */
/** @var array $vacancies */
$vacantCount = count(array_filter($vacancies, static fn(array $vacancy): bool => $vacancy['status'] === 'Vacant'));
require MODULES_PATH . '/shared/views/header.php';
?>
<main class="position-detail-page">
    <section class="glass-card position-detail-hero">
        <div class="position-detail-title"><span class="launcher-eyebrow">Plantilla position profile</span><h1><?= htmlspecialchars($position['title']) ?></h1><div><span><?= htmlspecialchars($position['salary_grade'] ?: 'Salary grade not specified') ?></span><span><?= $vacantCount > 0 ? $vacantCount . ' item' . ($vacantCount === 1 ? '' : 's') . ' available' : 'Fully occupied' ?></span></div></div>
        <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/positions">&larr; Back to positions</a>
    </section>
    <section class="position-detail-summary" aria-label="Position summary">
        <article class="glass-card"><div><span>Current holders</span><strong><?= count($holders) ?></strong><small>Active appointments</small></div></article>
        <article class="glass-card"><div><span>Service periods</span><strong><?= count($history) ?></strong><small>Past through present</small></div></article>
        <article class="glass-card <?= $vacantCount > 0 ? 'has-vacancy' : '' ?>"><div><span>Vacant items</span><strong><?= $vacantCount ?></strong><small><?= $vacantCount > 0 ? 'Ready for appointment' : 'No available items' ?></small></div></article>
    </section>
    <section class="glass-card position-detail-section">
        <div class="record-section-heading"><div><span class="launcher-eyebrow">Current assignment</span><h2>Employees holding this position</h2><p>Active holders and their time in this appointment.</p></div><span class="record-chip"><?= count($holders) ?> active</span></div>
        <div class="position-holder-grid">
            <?php foreach ($holders as $holder): $initial = strtoupper(substr((string) $holder['employee_name'], 0, 1)); ?>
                <a href="<?= BASE_URL ?>/employees/<?= UrlId::encode((int) $holder['id']) ?>"><span class="position-holder-avatar"><?= htmlspecialchars($initial) ?></span><div><strong><?= htmlspecialchars($holder['employee_name']) ?></strong><small><?= htmlspecialchars($holder['employee_number']) ?></small><em><?= htmlspecialchars($holder['department_name'] ?: 'Unassigned office') ?></em></div><span class="position-holder-tenure"><b><?= htmlspecialchars($holder['duration']) ?></b><small>Since <?= htmlspecialchars($holder['position_start'] ?: 'Not recorded') ?></small><i aria-hidden="true">&rarr;</i></span></a>
            <?php endforeach; ?>
            <?php if (!$holders): ?><p class="record-empty">No employee currently holds this position.</p><?php endif; ?>
        </div>
    </section>
    <section class="glass-card position-detail-section position-timeline-section">
        <div class="record-section-heading"><div><span class="launcher-eyebrow">Appointment timeline</span><h2>Past to present holders</h2><p>Chronological service history for this plantilla position.</p></div><span class="record-chip"><?= count($history) ?> period<?= count($history) === 1 ? '' : 's' ?></span></div>
        <div class="position-history-list">
            <?php foreach ($history as $index => $record): ?><article class="<?= !$record['end_date'] ? 'is-current' : '' ?>"><div class="position-history-marker"><span><?= $index + 1 ?></span></div><div class="position-history-person"><strong><?= htmlspecialchars($record['employee_name']) ?></strong><small><?= htmlspecialchars($record['employee_number']) ?></small><div><span><?= htmlspecialchars($record['item_number'] ?: 'No item number') ?></span><span><?= htmlspecialchars($record['source']) ?></span></div></div><div class="position-history-service"><span><?= !$record['end_date'] ? 'Current holder' : 'Completed service' ?></span><b><?= htmlspecialchars($record['duration']) ?></b><small><?= htmlspecialchars($record['start_date'] ?: 'Start date not recorded') ?> <i>&rarr;</i> <?= htmlspecialchars($record['end_date'] ?: 'Present') ?></small></div></article><?php endforeach; ?>
            <?php if (!$history): ?><p class="record-empty">No holder history is available yet. Add a previous appointment from the employee&rsquo;s Movement History tab.</p><?php endif; ?>
        </div>
    </section>
</main>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
