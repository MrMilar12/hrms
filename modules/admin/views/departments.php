<?php
/** @var array $departments */
/** @var array $allDepartments */
require MODULES_PATH . '/shared/views/header.php';
?>
<div class="glass-card">
    <h2 style="margin-top:0;">Manage Departments</h2>
    <table>
        <thead><tr><th>Name</th><th>Parent Department</th><th>Employees</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($departments as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d['name']) ?></td>
                <td><?= htmlspecialchars($d['parent_name'] ?? '—') ?></td>
                <td><?= (int) $d['employee_count'] ?></td>
                <td><button class="btn btn-sm btn-danger" onclick="deleteDepartment(<?= htmlspecialchars(json_encode(UrlId::encode((int) $d['id'])), ENT_QUOTES) ?>)">Delete</button></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$departments): ?><tr><td colspan="4" style="text-align:center;color:var(--text-muted);">No departments yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="glass-card">
    <h3 style="margin-top:0;">Add Department</h3>
    <form id="create-department-form">
        <div class="form-group">
            <label for="department-name">Department Name</label>
            <input id="department-name" name="name" required>
        </div>
        <div class="form-group">
            <label for="parent-department">Parent Department <small>(optional)</small></label>
            <select id="parent-department" name="parent_department_id">
                <option value="">No parent department</option>
                <?php foreach ($allDepartments as $d): ?>
                    <option value="<?= (int) $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Add Department</button>
    </form>
</div>

<script>
document.getElementById('create-department-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const result = await HRIS.postForm(`${window.BASE_URL}/admin/departments/store`, formData);
    if (result.success) { window.location.reload(); } else { HRIS.flash(result.error || 'Failed to add department.', 'error'); }
});

async function deleteDepartment(id) {
    if (!confirm('Delete this department?')) return;
    const formData = new FormData();
    const result = await HRIS.postForm(`${window.BASE_URL}/admin/departments/${id}/delete`, formData);
    if (result.success) { window.location.reload(); } else { HRIS.flash(result.error || 'Delete failed.', 'error'); }
}
</script>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
