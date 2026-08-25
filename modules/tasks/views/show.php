<?php
/** @var array $task */
/** @var array $assignees */
/** @var array $attachments */
/** @var array $comments */
/** @var array $history */
/** @var array $statuses */
/** @var bool $canUpdateStatus */
/** @var int|null $currentEmployeeId */
/** @var bool $canManageAssignments */
require MODULES_PATH . '/shared/views/header.php';
$badgeClass = 'badge-' . strtolower(str_replace(' ', '-', $task['status']));
?>
<div class="glass-card">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
        <div>
            <h2 style="margin:0 0 0.4rem;"><?= htmlspecialchars($task['title']) ?></h2>
            <p style="color:var(--text-secondary);"><?= nl2br(htmlspecialchars($task['description'] ?? '')) ?></p>
            <p style="font-size:0.85rem; color:var(--text-muted);"><strong>Department:</strong> <?= htmlspecialchars($task['department_name'] ?? '—') ?> &middot; <strong>Priority:</strong> <?= htmlspecialchars($task['priority']) ?> &middot; <strong>Due:</strong> <?= htmlspecialchars($task['due_date'] ?? '—') ?></p>
            <p style="font-size:0.85rem; color:var(--text-muted);"><strong>Assignees:</strong> <?= htmlspecialchars(implode(', ', array_column($assignees, 'employee_number')) ?: '—') ?></p>
        </div>
        <div style="text-align:right;">
            <small style="display:block;color:var(--text-muted);margin-bottom:0.3rem;">Overall status</small>
            <span class="badge <?= $badgeClass ?>" id="current-status-badge"><?= htmlspecialchars($task['status']) ?></span>
        </div>
    </div>
</div>

<div class="glass-card">
    <div class="task-assignment-heading"><div><span class="launcher-eyebrow">Individual progress</span><h3>Employee submissions</h3></div><span><?= count($assignees) ?> assignee<?= count($assignees) === 1 ? '' : 's' ?></span></div>
    <p class="task-assignment-note">Each employee must update and submit this task separately. One employee's submission does not submit it for the others.</p>
    <div class="task-assignment-grid">
        <?php foreach ($assignees as $assignee): ?>
            <?php $canChangeThis = $canUpdateStatus && ($canManageAssignments || (int) $assignee['id'] === (int) $currentEmployeeId); ?>
            <article class="task-assignee-card">
                <span class="assignee-avatar"><?= htmlspecialchars(strtoupper(substr($assignee['employee_name'], 0, 1))) ?></span>
                <div class="task-assignee-copy"><strong><?= htmlspecialchars($assignee['employee_name']) ?></strong><small><?= htmlspecialchars($assignee['employee_number']) ?><?= $assignee['submitted_at'] ? ' · Submitted ' . htmlspecialchars($assignee['submitted_at']) : '' ?></small></div>
                <?php if ($canChangeThis): ?>
                    <select class="task-status-select" data-task-id="<?= UrlId::encode((int) $task['id']) ?>" data-employee-id="<?= (int) $assignee['id'] ?>" aria-label="Status for <?= htmlspecialchars($assignee['employee_name']) ?>">
                        <?php foreach ($statuses as $status): ?><option value="<?= $status ?>" <?= $status === $assignee['status'] ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <span class="badge badge-<?= strtolower(str_replace(' ', '-', $assignee['status'])) ?>"><?= htmlspecialchars($assignee['status']) ?></span>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</div>

<div class="glass-card">
    <h3 style="margin-top:0;">Attachments</h3>
    <form id="attachment-form" enctype="multipart/form-data">
        <div class="form-row">
            <div class="form-group"><label>Photo</label><input type="file" id="attachment-file" name="file" accept=".jpg,.jpeg,.png,.webp" required></div>
            <div class="form-group"><label>Caption</label><input type="text" name="caption" maxlength="255"></div>
        </div>
        <div id="attachment-preview" style="margin-bottom:0.75rem;"></div>
        <button type="submit" class="btn btn-primary">Upload</button>
    </form>

    <div class="attachment-grid" style="margin-top:1rem;">
        <?php foreach ($attachments as $att): ?>
            <div class="attachment-item">
                <a href="<?= BASE_URL ?>/files/task-attachment/<?= UrlId::encode((int) $att['id']) ?>" target="_blank">
                    <img class="thumb" src="<?= BASE_URL ?>/files/task-attachment/<?= UrlId::encode((int) $att['id']) ?>" alt="attachment">
                </a>
                <div><?= htmlspecialchars($att['caption'] ?? '') ?></div>
            </div>
        <?php endforeach; ?>
        <?php if (!$attachments): ?><p style="color:var(--text-muted);">No attachments yet.</p><?php endif; ?>
    </div>
</div>

<div class="glass-card">
    <h3 style="margin-top:0;">Comments</h3>
    <div id="comment-list">
        <?php foreach ($comments as $c): ?>
            <div class="dropdown-item">
                <span class="status-dot"></span>
                <span><strong><?= htmlspecialchars($c['username']) ?>:</strong> <?= htmlspecialchars($c['comment']) ?> <small style="color:var(--text-muted);"><?= htmlspecialchars($c['created_at']) ?></small></span>
            </div>
        <?php endforeach; ?>
    </div>
    <form id="comment-form">
        <div class="form-group"><textarea name="comment" rows="2" required></textarea></div>
        <button type="submit" class="btn btn-primary">Add Comment</button>
    </form>
</div>

<div class="glass-card">
    <h3 style="margin-top:0;">Status History</h3>
    <table>
        <thead><tr><th>Employee submission</th><th>From</th><th>To</th><th>Changed By</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($history as $h): ?>
            <tr>
                <td><?= htmlspecialchars($h['employee_name'] ?? 'Legacy task update') ?></td>
                <td><?= htmlspecialchars($h['old_status'] ?? '—') ?></td>
                <td><?= htmlspecialchars($h['new_status']) ?></td>
                <td><?= htmlspecialchars($h['changed_by_username'] ?? '—') ?></td>
                <td><?= htmlspecialchars($h['changed_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
const taskId = <?= json_encode(UrlId::encode((int) $task['id'])) ?>;

document.getElementById('attachment-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const result = await HRIS.postForm(`${window.BASE_URL}/tasks/upload-attachment/${taskId}`, formData);
    if (result.success) { window.location.reload(); } else { HRIS.flash(result.error || 'Upload failed.', 'error'); }
});

document.getElementById('comment-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const result = await HRIS.postForm(`${window.BASE_URL}/tasks/add-comment/${taskId}`, formData);
    if (result.success) { window.location.reload(); } else { HRIS.flash(result.error || 'Failed to comment.', 'error'); }
});
</script>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
