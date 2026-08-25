<?php
/** @var array $employee */
/** @var array|null $photo */
/** @var int $pdsPercent */
require MODULES_PATH . '/shared/views/header.php';
?>
<div class="glass-card" style="padding:2rem;">
    <div style="display:flex; gap:1.75rem; align-items:center; flex-wrap:wrap;">
        <div>
            <?php if ($photo): ?>
                <img class="thumb" style="width:140px;height:140px;border-radius:var(--radius-medium);" src="<?= BASE_URL ?>/photo/<?= (int) $photo['id'] ?>" alt="2x2 photo">
            <?php else: ?>
                <div class="thumb glass-light" style="width:140px;height:140px;border-radius:var(--radius-medium);display:flex;align-items:center;justify-content:center;color:var(--text-muted);">No Photo</div>
            <?php endif; ?>
        </div>
        <div style="flex:1; min-width:240px;">
            <h2 style="margin:0 0 0.15rem;"><?= htmlspecialchars($employee['employee_number']) ?></h2>
            <p style="color:var(--text-secondary); margin:0 0 1rem;"><?= htmlspecialchars($employee['position_title'] ?? '—') ?> &middot; <?= htmlspecialchars($employee['department_name'] ?? '—') ?></p>

            <div style="display:flex; gap:2rem; flex-wrap:wrap; margin-bottom:1rem;">
                <div>
                    <div class="stat-title">Employee No.</div>
                    <div style="font-weight:600;"><?= htmlspecialchars($employee['employee_number']) ?></div>
                </div>
                <div>
                    <div class="stat-title">Department</div>
                    <div style="font-weight:600;"><?= htmlspecialchars($employee['department_name'] ?? '—') ?></div>
                </div>
                <div>
                    <div class="stat-title">Status</div>
                    <div><span class="status-pill"><span class="dot"></span> <?= htmlspecialchars($employee['employment_status']) ?></span></div>
                </div>
                <div>
                    <div class="stat-title">Date Hired</div>
                    <div style="font-weight:600;"><?= htmlspecialchars($employee['date_hired'] ?? '—') ?></div>
                </div>
            </div>

            <div style="max-width:280px; margin-bottom:1rem;">
                <div class="stat-title">PDS Completion</div>
                <div class="progress-bar"><div class="progress-bar-fill" data-target="<?= $pdsPercent ?>"></div></div>
                <span style="font-size:0.8rem; color:var(--text-muted);"><?= $pdsPercent ?>% complete</span>
            </div>

            <div style="display:flex; gap:0.6rem;">
                <a class="btn btn-primary" href="<?= BASE_URL ?>/pds?employee_id=<?= (int) $employee['id'] ?>">Edit PDS</a>
                <a class="btn btn-secondary" href="<?= BASE_URL ?>/pds/print/<?= (int) $employee['id'] ?>" target="_blank">Print PDS</a>
            </div>
        </div>
    </div>
</div>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
