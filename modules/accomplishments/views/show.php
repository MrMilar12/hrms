<?php
/** @var array $accomplishment */
/** @var array $attachments */
/** @var array $reviews */
/** @var bool $isOwner */
/** @var bool $canReview */
require MODULES_PATH . '/shared/views/header.php';

$statusBadge = fn($s) => 'badge-' . strtolower(str_replace(' ', '-', $s === 'Approved' ? 'done' : ($s === 'Returned' ? 'cancelled' : $s)));
?>
<div class="glass-card">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem;">
        <div>
            <a href="<?= BASE_URL ?>/accomplishments" style="font-size:0.85rem; color:var(--text-muted);">&larr; Back</a>
            <h2 style="margin:0.4rem 0 0.15rem;"><?= htmlspecialchars($accomplishment['title']) ?></h2>
            <p style="margin:0; color:var(--text-muted); font-size:0.85rem;">
                &#128197; <?= htmlspecialchars($accomplishment['accomplishment_date']) ?>
                <?php if ($accomplishment['task_title']): ?> &middot; &#128203; <?= htmlspecialchars($accomplishment['task_title']) ?><?php endif; ?>
                &middot; &#128100; <?= htmlspecialchars($accomplishment['employee_name']) ?>
            </p>
        </div>
        <span class="badge <?= $statusBadge($accomplishment['status']) ?>" style="font-size:0.85rem;"><?= htmlspecialchars($accomplishment['status']) ?></span>
    </div>

    <div style="margin-top:1rem; display:flex; gap:0.6rem; flex-wrap:wrap;">
        <a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/accomplishments/<?= (int) $accomplishment['id'] ?>/print" target="_blank">&#11015; Download / Print</a>
        <?php if ($isOwner && in_array($accomplishment['status'], ['Draft', 'Returned'], true)): ?>
            <a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/accomplishments/create">Edit as new draft</a>
            <button class="btn btn-primary btn-sm" id="btn-submit-now">Submit for Review</button>
        <?php endif; ?>
    </div>
</div>

<div class="glass-card">
    <h3 style="margin-top:0;">Description</h3>
    <p style="color:var(--text-secondary); white-space:pre-wrap;"><?= htmlspecialchars($accomplishment['description'] ?? '—') ?></p>
</div>

<div class="glass-card">
    <h3 style="margin-top:0;">Accomplishment Photos</h3>
    <div class="attachment-grid">
        <?php foreach ($attachments as $att): ?>
            <div class="attachment-item">
                <a href="<?= BASE_URL ?>/files/accomplishment-attachment/<?= (int) $att['id'] ?>" target="_blank">
                    <img class="thumb" src="<?= BASE_URL ?>/files/accomplishment-attachment/<?= (int) $att['id'] ?>" alt="evidence">
                </a>
                <div><?= htmlspecialchars($att['caption'] ?? '') ?></div>
            </div>
        <?php endforeach; ?>
        <?php if (!$attachments): ?><p style="color:var(--text-muted);">No photos attached.</p><?php endif; ?>
    </div>
</div>

<?php if ($canReview && $accomplishment['status'] === 'For Review'): ?>
<div class="glass-card">
    <h3 style="margin-top:0;">Review</h3>
    <div class="form-group">
        <label>Review Comments</label>
        <textarea id="review-comments" rows="3" placeholder="Optional comments for the employee"></textarea>
    </div>
    <div style="display:flex; gap:0.6rem; justify-content:flex-end;">
        <button class="btn btn-secondary" id="btn-return">Return for Revision</button>
        <button class="btn btn-primary" id="btn-approve">&#10003; Approve</button>
    </div>
</div>
<?php endif; ?>

<?php if ($reviews): ?>
<div class="glass-card">
    <h3 style="margin-top:0;">Review History</h3>
    <div class="review-timeline">
        <?php foreach ($reviews as $r): ?>
            <?php $approved = $r['status'] === 'Approved'; ?>
            <div class="review-item">
                <span class="review-marker <?= $approved ? 'approved' : 'returned' ?>"><?= $approved ? '&#10003;' : '&#10007;' ?></span>
                <div class="review-content">
                    <div class="review-meta">
                        <span class="badge <?= $approved ? 'badge-done' : 'badge-cancelled' ?>"><?= htmlspecialchars($r['status']) ?></span>
                        <span class="review-by">by <strong><?= htmlspecialchars($r['reviewer_username']) ?></strong></span>
                        <span class="review-date"><?= date('M j, Y \a\t g:i A', strtotime($r['reviewed_at'])) ?></span>
                    </div>
                    <?php if ($r['comments']): ?>
                        <p class="review-comment">&ldquo;<?= nl2br(htmlspecialchars($r['comments'])) ?>&rdquo;</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
const accomplishmentId = <?= (int) $accomplishment['id'] ?>;

document.getElementById('btn-submit-now')?.addEventListener('click', async () => {
    const result = await HRIS.postJson(`${window.BASE_URL}/accomplishments/${accomplishmentId}/submit`, {});
    if (result.success) { window.location.reload(); } else { HRIS.flash(result.error || 'Submit failed.', 'error'); }
});

async function review(decision) {
    const formData = new FormData();
    formData.append('decision', decision);
    formData.append('comments', document.getElementById('review-comments').value);
    const result = await HRIS.postForm(`${window.BASE_URL}/accomplishments/${accomplishmentId}/review`, formData);
    if (result.success) { window.location.reload(); } else { HRIS.flash(result.error || 'Review failed.', 'error'); }
}
document.getElementById('btn-approve')?.addEventListener('click', () => review('Approved'));
document.getElementById('btn-return')?.addEventListener('click', () => review('Returned'));
</script>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
