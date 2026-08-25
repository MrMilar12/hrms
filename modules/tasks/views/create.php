<?php
/** @var array $departments */
/** @var array $employees */
/** @var array $priorities */
require MODULES_PATH . '/shared/views/header.php';
?>
<div class="glass-card">
    <h2 style="margin-top:0;">New Task</h2>
    <form id="create-task-form">
        <div class="form-row">
            <div class="form-group" style="flex:2;"><label>Title</label><input name="title" required></div>
            <div class="form-group"><label>Priority</label>
                <select name="priority">
                    <?php foreach ($priorities as $p): ?><option value="<?= $p ?>" <?= $p === 'Medium' ? 'selected' : '' ?>><?= $p ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Due Date</label><input type="date" name="due_date"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Department</label>
                <select name="department_id">
                    <option value="">--</option>
                    <?php foreach ($departments as $d): ?><option value="<?= (int) $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group"><label>Description</label><textarea name="description" rows="4"></textarea></div>
        <div class="form-group">
            <label>Assign to</label>
            <select name="assignees[]" multiple size="6">
                <?php foreach ($employees as $e): ?><option value="<?= (int) $e['id'] ?>"><?= htmlspecialchars($e['employee_number']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Create Task</button>
    </form>
</div>
<script>
document.getElementById('create-task-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    formData.append('csrf_token', HRIS.getCsrfToken());
    const res = await fetch(`${window.BASE_URL}/tasks/store`, { method: 'POST', body: formData });
    const result = await res.json();
    if (result.success) {
        window.location.href = `${window.BASE_URL}/tasks/${result.task_id}`;
    } else {
        HRIS.flash(result.error || 'Failed to create task.', 'error');
    }
});
</script>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
