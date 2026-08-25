<?php
/** @var array $positions */
require MODULES_PATH . '/shared/views/header.php';
?>
<div class="glass-card">
    <h2 style="margin-top:0;">Manage Positions</h2>
    <table>
        <thead><tr><th>Title</th><th>Salary Grade</th><th>Employees</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($positions as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['title']) ?></td>
                <td><?= htmlspecialchars($p['salary_grade'] ?? '—') ?></td>
                <td><?= (int) $p['employee_count'] ?></td>
                <td><button class="btn btn-sm btn-danger" onclick="deletePosition(<?= (int) $p['id'] ?>)">Delete</button></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$positions): ?><tr><td colspan="4" style="text-align:center;color:var(--text-muted);">No positions yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="glass-card">
    <h3 style="margin-top:0;">Add Position</h3>
    <form id="create-position-form">
        <div class="form-row">
            <div class="form-group"><label>Title</label><input name="title" required></div>
            <div class="form-group"><label>Salary Grade</label><input name="salary_grade" placeholder="e.g. SG-15"></div>
        </div>
        <button type="submit" class="btn btn-primary">Add Position</button>
    </form>
</div>

<script>
document.getElementById('create-position-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const result = await HRIS.postForm(`${window.BASE_URL}/admin/positions/store`, formData);
    if (result.success) { window.location.reload(); } else { HRIS.flash(result.error || 'Failed to add position.', 'error'); }
});

async function deletePosition(id) {
    if (!confirm('Delete this position?')) return;
    const formData = new FormData();
    const result = await HRIS.postForm(`${window.BASE_URL}/admin/positions/${id}/delete`, formData);
    if (result.success) { window.location.reload(); } else { HRIS.flash(result.error || 'Delete failed.', 'error'); }
}
</script>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
