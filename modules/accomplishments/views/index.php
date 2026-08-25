<?php
/** @var array $accomplishments */
/** @var bool $canReviewAll */
/** @var array $statuses */
/** @var string|null $activeStatus */
/** @var array $departments */
/** @var array $employees */
/** @var array $notSubmitted */
/** @var string $filterDepartment */
/** @var string $filterEmployee */
/** @var string $filterSearch */
require MODULES_PATH . '/shared/views/header.php';

$statusBadge = fn($s) => 'badge-' . strtolower(str_replace(' ', '-', $s === 'Approved' ? 'done' : ($s === 'Returned' ? 'cancelled' : $s)));
$statusIcon = ['Draft' => '&#9675;', 'Submitted' => '&#9681;', 'For Review' => '&#9682;', 'Approved' => '&#9679;', 'Returned' => '&#9888;'];

function qs(array $overrides = []) {
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, fn($v) => $v !== null && $v !== '');
    return $params ? '?' . http_build_query($params) : '';
}
?>
<div class="glass-card">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
            <h2 style="margin:0;">Accomplishments</h2>
            <p style="margin:0.25rem 0 0; color:var(--text-muted); font-size:0.85rem;">Document and showcase your completed activities.</p>
        </div>
        <div style="display:flex; gap:0.6rem;">
            <?php if (!$canReviewAll): ?>
                <a class="btn btn-secondary" href="<?= BASE_URL ?>/accomplishments/gallery">&#128247; My Gallery</a>
            <?php endif; ?>
            <a class="btn btn-primary" href="<?= BASE_URL ?>/accomplishments/create">+ New Accomplishment</a>
        </div>
    </div>

    <div class="tabs" style="margin-top:1.25rem; margin-bottom:<?= $canReviewAll ? '1rem' : '0' ?>;">
        <a class="<?= $activeStatus === null ? 'active' : '' ?>" href="<?= BASE_URL ?>/accomplishments<?= qs(['status' => null]) ?>">All</a>
        <?php foreach ($statuses as $s): ?>
            <a class="<?= $activeStatus === $s ? 'active' : '' ?>" href="<?= BASE_URL ?>/accomplishments<?= qs(['status' => $s]) ?>"><?= $s ?></a>
        <?php endforeach; ?>
    </div>

    <?php if ($canReviewAll): ?>
    <form method="get" class="filter-bar">
        <input type="hidden" name="status" value="<?= htmlspecialchars($activeStatus ?? '') ?>">
        <div class="glass-search">
            <span>&#128269;</span>
            <input type="text" name="q" value="<?= htmlspecialchars($filterSearch) ?>" placeholder="Search title or description...">
        </div>
        <label class="filter-pill">
            <span class="filter-icon">&#127970;</span>
            <select name="department_id" onchange="this.form.submit()">
                <option value="">All departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= (string) $filterDepartment === (string) $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="filter-pill">
            <span class="filter-icon">&#128100;</span>
            <select name="employee_id" onchange="this.form.submit()">
                <option value="">All employees</option>
                <?php foreach ($employees as $e): ?>
                    <option value="<?= (int) $e['id'] ?>" <?= (string) $filterEmployee === (string) $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['employee_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn btn-secondary btn-sm">Apply</button>
        <?php if ($filterDepartment || $filterEmployee || $filterSearch || $activeStatus): ?>
            <a class="filter-bar-clear" href="<?= BASE_URL ?>/accomplishments">Clear filters</a>
        <?php endif; ?>
    </form>
    <?php endif; ?>
</div>

<?php if ($canReviewAll && $notSubmitted): ?>
<div class="glass-card">
    <h3 style="margin-top:0; display:flex; align-items:center; gap:0.5rem;">&#9888; Personnel Without Submissions</h3>
    <p style="margin:0 0 0.85rem; color:var(--text-muted); font-size:0.85rem;">These employees have not submitted any accomplishment yet.</p>
    <div style="display:flex; flex-wrap:wrap; gap:0.6rem;">
        <?php foreach ($notSubmitted as $n): ?>
            <div class="glass-light" style="padding:0.6rem 0.9rem; border-radius:var(--radius-small); font-size:0.82rem;">
                <strong><?= htmlspecialchars($n['employee_name']) ?></strong>
                <div style="color:var(--text-muted); font-size:0.75rem;"><?= htmlspecialchars($n['department_name'] ?? '—') ?> &middot; <?= htmlspecialchars($n['position_title'] ?? '—') ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="accomplishment-grid">
    <?php foreach ($accomplishments as $a): ?>
        <a href="<?= BASE_URL ?>/accomplishments/<?= (int) $a['id'] ?>" class="accomplishment-card glass">
            <div class="accomplishment-cover">
                <?php if ($a['cover_attachment_id']): ?>
                    <img src="<?= BASE_URL ?>/files/accomplishment-attachment/<?= (int) $a['cover_attachment_id'] ?>" alt="cover">
                <?php else: ?>
                    <div class="accomplishment-cover-placeholder">&#128247;</div>
                <?php endif; ?>
                <span class="badge <?= $statusBadge($a['status']) ?> accomplishment-cover-badge"><?= $statusIcon[$a['status']] ?? '' ?> <?= htmlspecialchars($a['status']) ?></span>
            </div>
            <div class="accomplishment-body">
                <h3><?= htmlspecialchars($a['title']) ?></h3>
                <p><?= htmlspecialchars(mb_strimwidth($a['description'] ?? '', 0, 110, '…')) ?></p>
                <div class="accomplishment-meta">
                    <span>&#128197; <?= htmlspecialchars($a['accomplishment_date']) ?></span>
                    <?php if ($canReviewAll): ?><span>&#128100; <?= htmlspecialchars($a['employee_name']) ?></span><?php endif; ?>
                    <span>&#128206; <?= (int) $a['attachment_count'] ?></span>
                </div>
            </div>
        </a>
    <?php endforeach; ?>
</div>
<?php if (!$accomplishments): ?>
    <div class="glass-card" style="text-align:center; color:var(--text-muted);">No accomplishments found.</div>
<?php endif; ?>

<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
