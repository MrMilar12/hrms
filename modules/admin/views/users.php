<?php
/** @var array $users */
/** @var int $page */
/** @var int $totalPages */
/** @var array $roles */
/** @var int $currentUserId */
/** @var bool $viewerIsDeveloper */
require MODULES_PATH . '/shared/views/header.php';
?>
<div class="glass-card account-directory">
    <div class="account-directory-header">
        <div class="account-directory-title">
            <div class="account-title-mark" aria-hidden="true">&#128100;</div>
            <div><span class="launcher-eyebrow">Administration <span class="account-title-divider">/</span> Identity and access</span><h2>Manage Accounts</h2><p>Review employee access, update account status, and keep login security up to date.</p></div>
        </div>
        <div class="account-directory-tools">
            <span class="account-total"><?= number_format($total) ?> <small>accounts</small></span>
            <div class="glass-search account-search"><span aria-hidden="true">&#128269;</span><input type="search" id="account-search" placeholder="Search name, role, or email" aria-label="Search accounts"></div>
            <a class="btn btn-primary account-add-button" href="<?= BASE_URL ?>/employees/create">+ Add Account</a>
        </div>
    </div>
    <div class="account-directory-note"><span class="account-note-icon">&#9432;</span><span><strong>Account management</strong> New accounts are created together with employees from the Employees page. Use the controls below to manage existing access.</span></div>
    <div class="account-table-scroll" role="region" aria-label="Account directory" tabindex="0">
    <table>
        <thead><tr><th>Account Holder</th><th>Username</th><th>Email</th><th>Role</th><th>Employment</th><th>Status</th><th>Security</th><th>Account Activity</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <?php
            $nameParts = array_filter([$u['first_name'] ?? null, $u['middle_name'] ?? null, $u['surname'] ?? null, $u['name_extension'] ?? null], static fn($part) => $part !== null && trim((string) $part) !== '' && !in_array(strtoupper(trim((string) $part)), ['N/A', 'NA', 'NONE'], true));
            $accountName = trim(implode(' ', $nameParts)) ?: $u['username'];
            $initial = strtoupper(substr($u['first_name'] ?: ($u['surname'] ?: $u['username']), 0, 1));
            $isProtectedDeveloper = $u['role_name'] === ROLE_DEVELOPER && !$viewerIsDeveloper;
            ?>
            <tr>
                <td><div class="account-holder"><?php if (!empty($u['photo_id'])): ?><img src="<?= BASE_URL ?>/photo/<?= UrlId::encode((int) $u['photo_id']) ?>" alt=""><?php else: ?><span><?= htmlspecialchars($initial) ?></span><?php endif; ?><div><strong><?= htmlspecialchars($accountName) ?></strong><small><?= htmlspecialchars($u['employee_number'] ?? 'No linked employee') ?></small></div></div></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="record-chip account-role-chip"><?= htmlspecialchars($u['role_name']) ?></span></td>
                <td><div class="account-detail-stack"><strong><?= htmlspecialchars($u['position_title'] ?? 'Position not assigned') ?></strong><span><?= htmlspecialchars($u['department_name'] ?? 'Department not assigned') ?></span><small><?= htmlspecialchars($u['personnel_type'] ?? 'Unclassified') ?><?= !empty($u['current_school_station']) ? ' · ' . htmlspecialchars($u['current_school_station']) : '' ?></small></div></td>
                <td>
                    <select class="user-status-select" data-user-id="<?= UrlId::encode((int) $u['id']) ?>" <?= $isProtectedDeveloper ? 'disabled title="Only a Developer can manage this account"' : '' ?>>
                        <?php foreach (['active', 'inactive', 'locked'] as $s): ?>
                            <option value="<?= $s ?>" <?= $s === $u['status'] ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><div class="account-security-state <?= !empty($u['two_factor_enabled']) ? 'enabled' : '' ?>"><span><?= !empty($u['two_factor_enabled']) ? '2FA enabled' : '2FA not enabled' ?></span><small><?= $u['status'] === 'locked' ? 'Account locked' : 'Login protection' ?></small></div></td>
                <td><div class="account-detail-stack"><strong><?= $u['last_login'] ? htmlspecialchars(date('M j, Y · g:i A', strtotime($u['last_login']))) : 'Never logged in' ?></strong><span>Last login</span><small>Created <?= htmlspecialchars(date('M j, Y', strtotime($u['created_at']))) ?></small></div></td>
                <td>
                    <div class="account-actions">
                        <?php if (!$isProtectedDeveloper): ?>
                        <button type="button" class="btn btn-secondary btn-sm account-edit" data-id="<?= UrlId::encode((int) $u['id']) ?>" data-username="<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>" data-email="<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>" data-role-id="<?= (int) $u['role_id'] ?>">Edit</button>
                        <button type="button" class="btn btn-secondary btn-sm account-password" data-id="<?= UrlId::encode((int) $u['id']) ?>" data-username="<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>">Password</button>
                        <?php if (!empty($u['two_factor_enabled'])): ?><button type="button" class="btn btn-secondary btn-sm account-2fa" data-id="<?= UrlId::encode((int) $u['id']) ?>" data-username="<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>">Reset 2FA</button><?php endif; ?>
                        <?php if ((int) $u['id'] !== $currentUserId): ?><button type="button" class="btn btn-danger btn-sm account-delete" data-id="<?= UrlId::encode((int) $u['id']) ?>" data-username="<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>">Delete</button><?php endif; ?>
                        <?php else: ?><span class="record-chip">Protected account</span><?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$users): ?><tr><td colspan="9" style="text-align:center;color:var(--text-muted);">No users found.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Account pages" style="display:flex;justify-content:center;align-items:center;gap:.75rem;flex-wrap:wrap;margin-top:1.25rem;">
            <?php if ($page > 1): ?><a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/users?page=<?= $page - 1 ?>">Previous</a><?php endif; ?>
            <span style="color:var(--text-muted);">Page <?= $page ?> of <?= $totalPages ?> &middot; <?= number_format($total) ?> accounts</span>
            <?php if ($page < $totalPages): ?><a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/users?page=<?= $page + 1 ?>">Next</a><?php endif; ?>
        </nav>
    <?php endif; ?>
</div>

<script>
const accountRoles = <?= json_encode($roles, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

document.getElementById('account-search')?.addEventListener('input', event => {
    const term = event.target.value.trim().toLowerCase();
    document.querySelectorAll('table tbody tr').forEach(row => row.hidden = term !== '' && !row.textContent.toLowerCase().includes(term));
});

document.querySelectorAll('.user-status-select').forEach((select) => {
    select.dataset.previous = select.value;
    select.addEventListener('change', async () => {
        const formData = new FormData();
        formData.append('status', select.value);
        const result = await HRIS.postForm(`${window.BASE_URL}/admin/users/${select.dataset.userId}/status`, formData);
        if (result.success) select.dataset.previous = select.value;
        else select.value = select.dataset.previous;
        HRIS.flash(result.message || result.error || (result.success ? 'Updated.' : 'Update failed.'), result.success ? 'success' : 'error');
    });
});

document.querySelectorAll('.account-edit').forEach((button) => button.addEventListener('click', async () => {
    const roleOptions = accountRoles.map(role => `<option value="${Number(role.id)}" ${String(role.id) === button.dataset.roleId ? 'selected' : ''}>${escapeHtml(role.name)}</option>`).join('');
    const modal = await Swal.fire({
        title: 'Edit account',
        html: `<div class="swal-account-form"><label>Username<input id="account-username" class="swal2-input" value="${escapeHtml(button.dataset.username)}"></label><label>Email<input id="account-email" type="email" class="swal2-input" value="${escapeHtml(button.dataset.email)}"></label><label>Role<select id="account-role" class="swal2-select">${roleOptions}</select></label></div>`,
        showCancelButton: true,
        confirmButtonText: 'Save changes',
        focusConfirm: false,
        preConfirm: () => ({ username: document.getElementById('account-username').value.trim(), email: document.getElementById('account-email').value.trim(), role_id: document.getElementById('account-role').value })
    });
    if (!modal.isConfirmed) return;
    const fd = new FormData(); Object.entries(modal.value).forEach(([key, value]) => fd.append(key, value));
    const result = await HRIS.postForm(`${window.BASE_URL}/admin/users/${button.dataset.id}/update`, fd);
    if (result.success) window.location.reload();
    else HRIS.flash(result.error || Object.values(result.errors || {})[0] || 'Update failed.', 'error');
}));

document.querySelectorAll('.account-password').forEach((button) => button.addEventListener('click', async () => {
    const modal = await Swal.fire({ title: `Reset ${button.dataset.username}'s password`, input: 'password', inputLabel: 'Temporary password', inputPlaceholder: 'At least 8 characters', inputAttributes: { minlength: 8, autocomplete: 'new-password' }, showCancelButton: true, confirmButtonText: 'Reset password', inputValidator: value => value.length < 8 ? 'Enter at least 8 characters.' : undefined });
    if (!modal.isConfirmed) return;
    const fd = new FormData(); fd.append('password', modal.value);
    const result = await HRIS.postForm(`${window.BASE_URL}/admin/users/${button.dataset.id}/reset-password`, fd);
    HRIS.flash(result.message || result.error || 'Password reset failed.', result.success ? 'success' : 'error');
}));

document.querySelectorAll('.account-2fa').forEach((button) => button.addEventListener('click', async () => {
    const modal = await Swal.fire({ icon: 'warning', title: 'Reset two-factor authentication?', text: `${button.dataset.username} will need to configure 2FA again.`, showCancelButton: true, confirmButtonText: 'Reset 2FA', confirmButtonColor: '#d97706' });
    if (!modal.isConfirmed) return;
    const result = await HRIS.postJson(`${window.BASE_URL}/admin/users/${button.dataset.id}/reset-2fa`, {});
    if (result.success) window.location.reload(); else HRIS.flash(result.error || '2FA reset failed.', 'error');
}));

document.querySelectorAll('.account-delete').forEach((button) => button.addEventListener('click', async () => {
    const modal = await Swal.fire({ icon: 'warning', title: 'Delete this account?', html: `<strong>${escapeHtml(button.dataset.username)}</strong><br>The employee 201 file will be retained. Accounts with activity history cannot be deleted.`, showCancelButton: true, confirmButtonText: 'Delete account', confirmButtonColor: '#dc2626' });
    if (!modal.isConfirmed) return;
    const result = await HRIS.postJson(`${window.BASE_URL}/admin/users/${button.dataset.id}/delete`, {});
    if (result.success) window.location.reload(); else HRIS.flash(result.error || 'Delete failed.', 'error');
}));

function escapeHtml(value) {
    const node = document.createElement('div'); node.textContent = value; return node.innerHTML;
}
</script>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
