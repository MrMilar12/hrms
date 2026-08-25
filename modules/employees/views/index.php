<?php
/** @var array $employees */
/** @var bool $canManage */
/** @var int $page */
/** @var int $totalPages */
require MODULES_PATH . '/shared/views/header.php';
?>
<div class="glass-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <div><span class="launcher-eyebrow">People directory</span><h2 style="margin:.15rem 0 0;">Employees (201 File)</h2></div>
        <div style="display:flex; align-items:center; gap:0.75rem;">
            <div class="glass-search" style="max-width:260px;">
                <span>&#128269;</span>
                <input type="text" id="employee-search" placeholder="Search employees..." aria-label="Search employees">
            </div>
            <?php if ($canManage): ?>
                <a class="btn btn-primary" href="<?= BASE_URL ?>/employees/create">+ Add Employee</a>
            <?php endif; ?>
        </div>
    </div>
    <table id="employee-table">
        <thead>
        <tr>
            <th>Employee</th>
            <th>Department</th>
            <th>Position</th>
            <th>Personnel Type</th>
            <th>Work Station</th>
            <th>Status</th>
            <th>PDS Completion</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($employees as $emp): ?>
            <?php
            $employeeNameParts = array_filter([
                $emp['first_name'] ?? null, $emp['middle_name'] ?? null,
                $emp['surname'] ?? null, $emp['name_extension'] ?? null,
            ], static fn($part) => $part !== null && trim((string) $part) !== '' && !in_array(strtoupper(trim((string) $part)), ['N/A', 'NA', 'NONE'], true));
            $employeeName = trim(implode(' ', $employeeNameParts)) ?: $emp['employee_number'];
            $initial = strtoupper(substr($emp['first_name'] ?: ($emp['surname'] ?: $emp['employee_number']), 0, 1));
            $workStation = $emp['current_school_station'] ?: ($emp['district'] ?: 'Not assigned');
            ?>
            <tr>
                <td><div class="employee-directory-person"><?php if (!empty($emp['photo_id'])): ?><img src="<?= BASE_URL ?>/photo/<?= UrlId::encode((int) $emp['photo_id']) ?>" alt=""><?php else: ?><span><?= htmlspecialchars($initial) ?></span><?php endif; ?><div><strong><?= htmlspecialchars($employeeName) ?></strong><small><?= htmlspecialchars($emp['employee_number']) ?></small></div></div></td>
                <td><?= htmlspecialchars($emp['department_name'] ?? 'Not assigned') ?></td>
                <td><?= htmlspecialchars($emp['position_title'] ?? 'Not assigned') ?></td>
                <td><span class="record-chip"><?= htmlspecialchars($emp['personnel_type'] ?? 'Unclassified') ?></span></td>
                <td><?= htmlspecialchars($workStation) ?></td>
                <td><span class="status-pill"><span class="dot"></span> <?= htmlspecialchars($emp['employment_status']) ?></span></td>
                <td>
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <div class="progress-bar" style="width:110px;">
                            <div class="progress-bar-fill" data-target="<?= (int) $emp['pds_percent'] ?>"></div>
                        </div>
                        <?= (int) $emp['pds_percent'] ?>%
                    </div>
                </td>
                <td><a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/employees/<?= UrlId::encode((int) $emp['id']) ?>">View profile</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Employee pages" style="display:flex;justify-content:center;align-items:center;gap:.75rem;flex-wrap:wrap;margin-top:1.25rem;">
            <?php if ($page > 1): ?><a class="btn btn-secondary" href="<?= BASE_URL ?>/employees?page=<?= $page - 1 ?>">Previous</a><?php endif; ?>
            <span style="color:var(--text-muted);">Page <?= $page ?> of <?= $totalPages ?> &middot; <?= number_format($total) ?> employees</span>
            <?php if ($page < $totalPages): ?><a class="btn btn-secondary" href="<?= BASE_URL ?>/employees?page=<?= $page + 1 ?>">Next</a><?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
<script>
document.getElementById('employee-search')?.addEventListener('input', (e) => {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('#employee-table tbody tr').forEach((row) => {
        row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
});
</script>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
