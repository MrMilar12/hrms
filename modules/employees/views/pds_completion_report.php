<?php
/** @var array $rows */
require MODULES_PATH . '/shared/views/header.php';
?>
<div class="glass-card">
    <h2 style="margin-top:0;">PDS Completion by Department</h2>
    <table>
        <thead><tr><th>Department</th><th>Total Employees</th><th>Fully Completed</th><th>% Complete</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <?php
                $total = (int) $row['total_employees'];
                $complete = (int) $row['complete_employees'];
                $pct = $total > 0 ? round(($complete / $total) * 100) : 0;
            ?>
            <tr>
                <td><?= htmlspecialchars($row['department_name'] ?? 'Unassigned') ?></td>
                <td><?= $total ?></td>
                <td><?= $complete ?></td>
                <td>
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <div class="progress-bar" style="width:120px;"><div class="progress-bar-fill" data-target="<?= $pct ?>"></div></div>
                        <?= $pct ?>%
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
