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
        <div class="form-group assignee-picker" id="assignee-picker">
            <div class="assignee-picker-heading">
                <div><label>Assign employees</label><small>Select one or more people for this task.</small></div>
                <span class="assignee-count" id="assignee-count">0 selected</span>
            </div>
            <div class="assignee-toolbar">
                <div class="glass-search"><span>&#128269;</span><input type="search" id="assignee-search" placeholder="Search name, number, or department..." autocomplete="off"></div>
                <button type="button" class="btn btn-secondary btn-sm" id="select-visible-assignees">Select all</button>
                <button type="button" class="filter-bar-clear" id="clear-assignees">Clear</button>
            </div>
            <div class="assignee-grid" id="assignee-grid">
                <?php foreach ($employees as $employee): ?>
                    <label class="assignee-option" data-search="<?= htmlspecialchars(strtolower($employee['employee_name'] . ' ' . $employee['employee_number'] . ' ' . ($employee['department_name'] ?? '')), ENT_QUOTES) ?>" data-department="<?= (int) ($employee['department_id'] ?? 0) ?>">
                        <input type="checkbox" name="assignees[]" value="<?= (int) $employee['id'] ?>">
                        <span class="assignee-check">&#10003;</span>
                        <span class="assignee-avatar"><?= htmlspecialchars(strtoupper(substr($employee['employee_name'], 0, 1))) ?></span>
                        <span class="assignee-copy"><strong><?= htmlspecialchars($employee['employee_name']) ?></strong><small><?= htmlspecialchars($employee['employee_number']) ?><?= $employee['department_name'] ? ' · ' . htmlspecialchars($employee['department_name']) : '' ?></small></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="assignee-empty" id="assignee-empty" hidden>No employees match your search.</p>
        </div>
        <button type="submit" class="btn btn-primary">Create Task</button>
    </form>
</div>
<script>
const assigneePicker = document.getElementById('assignee-picker');
const assigneeOptions = [...assigneePicker.querySelectorAll('.assignee-option')];
const assigneeSearch = document.getElementById('assignee-search');
const departmentSelect = document.querySelector('[name="department_id"]');
const updateAssigneePicker = () => {
    const query = assigneeSearch.value.trim().toLowerCase();
    const department = departmentSelect.value;
    let visible = 0;
    assigneeOptions.forEach(option => {
        const matchesSearch = !query || option.dataset.search.includes(query);
        const matchesDepartment = !department || option.dataset.department === department;
        option.hidden = !(matchesSearch && matchesDepartment);
        if (!option.hidden) visible++;
        option.classList.toggle('selected', option.querySelector('input').checked);
    });
    const selected = assigneeOptions.filter(option => option.querySelector('input').checked).length;
    document.getElementById('assignee-count').textContent = `${selected} selected`;
    document.getElementById('assignee-empty').hidden = visible !== 0;
};
assigneeSearch.addEventListener('input', updateAssigneePicker);
departmentSelect.addEventListener('change', updateAssigneePicker);
assigneePicker.addEventListener('change', updateAssigneePicker);
document.getElementById('select-visible-assignees').addEventListener('click', () => {
    assigneeOptions.filter(option => !option.hidden).forEach(option => { option.querySelector('input').checked = true; });
    updateAssigneePicker();
});
document.getElementById('clear-assignees').addEventListener('click', () => {
    assigneeOptions.forEach(option => { option.querySelector('input').checked = false; });
    updateAssigneePicker();
});
updateAssigneePicker();

document.getElementById('create-task-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    formData.append('csrf_token', HRIS.getCsrfToken());
    if (!formData.getAll('assignees[]').length) {
        HRIS.flash('Select at least one employee.', 'error');
        return;
    }
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
