<?php
/** @var array $positions */
/** @var int $page */
/** @var int $total */
/** @var int $totalPages */
/** @var string $search */
require MODULES_PATH . '/shared/views/header.php';
?>
<div class="glass-card position-directory">
    <div class="position-directory-header">
        <div><span class="launcher-eyebrow">Plantilla catalogue</span><h2>Manage Positions</h2><p>Browse and maintain government position titles and salary grades.</p></div>
        <span class="position-count"><strong><?= number_format($total) ?></strong><small><?= $search !== '' ? 'matches' : 'positions' ?></small></span>
    </div>
    <form class="position-search" method="get" action="<?= BASE_URL ?>/admin/positions" role="search">
        <div class="glass-search"><span aria-hidden="true">&#128269;</span><input type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search title or salary grade..." aria-label="Search positions"></div>
        <button class="btn btn-primary" type="submit">Search</button>
        <?php if ($search !== ''): ?><a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/positions">Clear</a><?php endif; ?>
    </form>
    <?php if ($search !== ''): ?><p class="position-search-summary">Showing results for <strong>&ldquo;<?= htmlspecialchars($search) ?>&rdquo;</strong></p><?php endif; ?>
    <div class="position-table-wrap">
        <table>
            <thead><tr><th>Title</th><th>Salary Grade</th><th>Employees</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($positions as $p): ?>
                <tr><td><strong><?= htmlspecialchars($p['title']) ?></strong></td><td><span class="record-chip"><?= htmlspecialchars($p['salary_grade'] ?? '—') ?></span></td><td><?= number_format((int) $p['employee_count']) ?></td><td><button class="btn btn-sm btn-danger" type="button" onclick="deletePosition(<?= htmlspecialchars(json_encode(UrlId::encode((int) $p['id'])), ENT_QUOTES) ?>)">Delete</button></td></tr>
            <?php endforeach; ?>
            <?php if (!$positions): ?><tr><td colspan="4" class="position-empty">No matching positions found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
        <?php $query = $search !== '' ? '&q=' . rawurlencode($search) : ''; ?>
        <nav class="position-pagination" aria-label="Position pages">
            <a class="btn btn-secondary <?= $page <= 1 ? 'disabled' : '' ?>" <?= $page > 1 ? 'href="' . BASE_URL . '/admin/positions?page=' . ($page - 1) . $query . '"' : 'aria-disabled="true"' ?>>Previous</a>
            <span>Page <strong><?= $page ?></strong> of <?= $totalPages ?> <small>&middot; 20 per page</small></span>
            <a class="btn btn-secondary <?= $page >= $totalPages ? 'disabled' : '' ?>" <?= $page < $totalPages ? 'href="' . BASE_URL . '/admin/positions?page=' . ($page + 1) . $query . '"' : 'aria-disabled="true"' ?>>Next</a>
        </nav>
    <?php endif; ?>
</div>
<div class="glass-card">
    <h3 style="margin-top:0;">Add Position</h3>
    <form id="create-position-form"><div class="form-row"><div class="form-group"><label>Title</label><input name="title" required></div><div class="form-group"><label>Salary Grade</label><input name="salary_grade" placeholder="e.g. SG-15"></div></div><button type="submit" class="btn btn-primary">Add Position</button></form>
</div>
<script>
document.getElementById('create-position-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const result = await HRIS.postForm(`${window.BASE_URL}/admin/positions/store`, new FormData(event.target));
    if (result.success) window.location.reload(); else HRIS.flash(result.error || 'Failed to add position.', 'error');
});
async function deletePosition(id) {
    if (!confirm('Delete this position?')) return;
    const result = await HRIS.postForm(`${window.BASE_URL}/admin/positions/${id}/delete`, new FormData());
    if (result.success) window.location.reload(); else HRIS.flash(result.error || 'Delete failed.', 'error');
}
</script>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
