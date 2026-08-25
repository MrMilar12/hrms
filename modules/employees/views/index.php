<?php
/** @var array $employees */
/** @var bool $canManage */
require MODULES_PATH . '/shared/views/header.php';
?>
<div class="glass-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h2 style="margin:0;">Employees (201 File)</h2>
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
            <th>Employee #</th>
            <th>Department</th>
            <th>Position</th>
            <th>Status</th>
            <th>PDS Completion</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($employees as $emp): ?>
            <tr>
                <td><?= htmlspecialchars($emp['employee_number']) ?></td>
                <td><?= htmlspecialchars($emp['department_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars($emp['position_title'] ?? '—') ?></td>
                <td><span class="status-pill"><span class="dot"></span> <?= htmlspecialchars($emp['employment_status']) ?></span></td>
                <td>
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <div class="progress-bar" style="width:110px;">
                            <div class="progress-bar-fill" data-target="<?= (int) $emp['pds_percent'] ?>"></div>
                        </div>
                        <?= (int) $emp['pds_percent'] ?>%
                    </div>
                </td>
                <td><a href="<?= BASE_URL ?>/employees/<?= (int) $emp['id'] ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
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
