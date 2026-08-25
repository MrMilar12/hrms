<?php
/** @var array $users */
require MODULES_PATH . '/shared/views/header.php';
?>
<div class="glass-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h2 style="margin:0;">Manage Accounts</h2>
    </div>
    <table>
        <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Linked Employee</th><th>Status</th><th>Last Login</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['role_name']) ?></td>
                <td><?= htmlspecialchars($u['employee_number'] ?? '—') ?></td>
                <td>
                    <select class="user-status-select" data-user-id="<?= (int) $u['id'] ?>">
                        <?php foreach (['active', 'inactive', 'locked'] as $s): ?>
                            <option value="<?= $s ?>" <?= $s === $u['status'] ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><?= htmlspecialchars($u['last_login'] ?? 'Never') ?></td>
                <td></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$users): ?><tr><td colspan="7" style="text-align:center;color:var(--text-muted);">No users found.</td></tr><?php endif; ?>
        </tbody>
    </table>
    <p style="color:var(--text-muted); margin:1rem 0 0;">New accounts are created together with employees from the Employees page.</p>
</div>

<script>
document.querySelectorAll('.user-status-select').forEach((select) => {
    select.addEventListener('change', async () => {
        const formData = new FormData();
        formData.append('status', select.value);
        const result = await HRIS.postForm(`${window.BASE_URL}/admin/users/${select.dataset.userId}/status`, formData);
        HRIS.flash(result.message || (result.success ? 'Updated.' : 'Update failed.'), result.success ? 'success' : 'error');
    });
});
</script>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
