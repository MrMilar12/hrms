<?php
/** @var array $departments */
/** @var array $positions */
/** @var array $roles */
/** @var array $statuses */
require MODULES_PATH . '/shared/views/header.php';
?>
<div class="glass-card">
    <h2 style="margin-top:0;">Add Employee</h2>
    <p style="color:var(--text-muted); margin-top:-0.5rem;">Create the employee record and their login account in one step.</p>
    <form id="create-employee-form">
        <div class="form-row">
            <div class="form-group"><label>Employee Number</label><input name="employee_number" required placeholder="e.g. EMP-0002"></div>
            <div class="form-group"><label>Date Hired</label><input type="date" name="date_hired"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Department</label>
                <select name="department_id">
                    <option value="">--</option>
                    <?php foreach ($departments as $d): ?><option value="<?= (int) $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Position</label>
                <select name="position_id" data-searchable-select data-search-placeholder="Search government positions...">
                    <option value="">Select position</option>
                    <?php foreach ($positions as $p): ?><option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Employment Status</label>
                <select name="employment_status">
                    <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $s === 'Probationary' ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <h3 style="margin:1.5rem 0 0.75rem;">Login Account</h3>
        <div class="form-row">
            <div class="form-group"><label>Username</label><input name="username" required maxlength="60" autocomplete="username"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" required autocomplete="email"></div>
            <div class="form-group"><label>Temporary Password</label><input type="password" name="password" required minlength="8" autocomplete="new-password"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Role</label>
                <select name="role_id" required>
                    <?php foreach ($roles as $role): ?><option value="<?= (int) $role['id'] ?>" <?= $role['name'] === 'Employee' ? 'selected' : '' ?>><?= htmlspecialchars($role['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Create Employee &amp; Account</button>
    </form>
</div>
<script>
document.getElementById('create-employee-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('csrf_token', HRIS.getCsrfToken());
    const res = await fetch(`${window.BASE_URL}/employees/store`, { method: 'POST', body: formData });
    const result = await res.json();
    if (result.success) {
        window.location.href = `${window.BASE_URL}/employees/${result.employee_token}`;
    } else {
        HRIS.flash(result.error || 'Failed to add employee.', 'error');
    }
});
</script>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
