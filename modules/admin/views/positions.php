<?php
/** @var array $positions */
/** @var int $page */
/** @var int $total */
/** @var int $totalPages */
/** @var string $search */
/** @var int $totalVacancies */
/** @var string $vacancyFilter */
/** @var string $salaryFilter */
/** @var array $salaryGrades */
require MODULES_PATH . '/shared/views/header.php';
?>
<div class="glass-card position-directory">
    <div class="position-directory-header">
        <div><span class="launcher-eyebrow">Plantilla catalogue</span><h2>Manage Positions</h2><p>Browse and maintain government position titles and salary grades.</p></div>
        <div class="position-directory-counts"><span class="position-count"><strong><?= number_format($total) ?></strong><small><?= $search !== '' ? 'matches' : 'positions' ?></small></span><a class="position-vacancy-count" href="<?= BASE_URL ?>/vacant-positions"><strong><?= number_format($totalVacancies) ?></strong><small>vacant item<?= $totalVacancies === 1 ? '' : 's' ?></small></a></div>
    </div>
    <form class="position-search" method="get" action="<?= BASE_URL ?>/admin/positions" role="search">
        <div class="glass-search"><span aria-hidden="true">&#128269;</span><input type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search title or salary grade..." aria-label="Search positions"></div>
        <select name="vacancy" aria-label="Filter by vacancy"><option value="">All occupancy</option><option value="available" <?= $vacancyFilter === 'available' ? 'selected' : '' ?>>With vacancies</option><option value="occupied" <?= $vacancyFilter === 'occupied' ? 'selected' : '' ?>>Fully occupied</option></select>
        <select name="salary_grade" aria-label="Filter by salary grade"><option value="">All salary grades</option><?php foreach ($salaryGrades as $grade): ?><option value="<?= htmlspecialchars($grade) ?>" <?= $salaryFilter === $grade ? 'selected' : '' ?>><?= htmlspecialchars($grade) ?></option><?php endforeach; ?></select>
        <button class="btn btn-primary" type="submit">Apply filters</button>
        <?php if ($search !== '' || $vacancyFilter !== '' || $salaryFilter !== ''): ?><a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/positions">Clear</a><?php endif; ?>
        <div class="position-view-toggle" role="group" aria-label="Position display"><button type="button" class="active" data-position-view="list">List</button><button type="button" data-position-view="cards">Cards</button></div>
    </form>
    <?php if ($search !== ''): ?><p class="position-search-summary">Showing results for <strong>&ldquo;<?= htmlspecialchars($search) ?>&rdquo;</strong></p><?php endif; ?>
    <div class="position-table-wrap" data-position-panel="list">
        <table data-view-toggle="off">
            <thead><tr><th>Title</th><th>Salary Grade</th><th>Employees</th><th>Vacant items</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($positions as $p): ?>
                <tr><td><strong><?= htmlspecialchars($p['title']) ?></strong></td><td><span class="record-chip"><?= htmlspecialchars($p['salary_grade'] ?? '—') ?></span></td><td><span class="position-occupied-count"><?= number_format((int) $p['employee_count']) ?></span></td><td><?php if ((int)$p['vacant_count'] > 0): ?><a class="position-vacancy-cell" href="<?= BASE_URL ?>/vacant-positions"><span><?= number_format((int)$p['vacant_count']) ?> available</span><small><?= htmlspecialchars($p['vacant_items']) ?></small></a><?php else: ?><span class="position-no-vacancy">Fully occupied</span><?php endif; ?></td><td><div class="position-row-actions"><a class="btn btn-sm btn-secondary" href="<?= BASE_URL ?>/admin/positions/<?= UrlId::encode((int)$p['id']) ?>">View</a><?php if ((int)$p['vacant_count'] > 0): ?><a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>/vacant-positions">Fill</a><?php endif; ?><button class="btn btn-sm btn-danger" type="button" onclick="deletePosition(<?= htmlspecialchars(json_encode(UrlId::encode((int) $p['id'])), ENT_QUOTES) ?>)">Delete</button></div></td></tr>
            <?php endforeach; ?>
            <?php if (!$positions): ?><tr><td colspan="5" class="position-empty">No matching positions found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="position-card-view" data-position-panel="cards" hidden>
        <?php foreach ($positions as $p): ?><article class="position-management-card <?= (int)$p['vacant_count'] > 0 ? 'has-vacancy' : '' ?>"><header><span class="record-chip"><?= htmlspecialchars($p['salary_grade'] ?? 'No SG') ?></span><span><?= (int)$p['vacant_count'] > 0 ? (int)$p['vacant_count'].' available' : 'Fully occupied' ?></span></header><h3><?= htmlspecialchars($p['title']) ?></h3><div class="position-card-stats"><span><strong><?= (int)$p['employee_count'] ?></strong><small>Employees</small></span><span><strong><?= (int)$p['vacant_count'] ?></strong><small>Vacant items</small></span></div><?php if ($p['vacant_items']): ?><p><?= htmlspecialchars($p['vacant_items']) ?></p><?php endif; ?><footer><a class="btn btn-sm btn-secondary" href="<?= BASE_URL ?>/admin/positions/<?= UrlId::encode((int)$p['id']) ?>">View history</a><?php if ((int)$p['vacant_count'] > 0): ?><a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>/vacant-positions">Fill</a><?php endif; ?><button class="btn btn-sm btn-danger" type="button" onclick="deletePosition(<?= htmlspecialchars(json_encode(UrlId::encode((int)$p['id'])),ENT_QUOTES) ?>)">Delete</button></footer></article><?php endforeach; ?>
        <?php if (!$positions): ?><p class="position-empty">No matching positions found.</p><?php endif; ?>
    </div>
    <?php if ($totalPages > 1): ?>
        <?php $query = ($search !== '' ? '&q=' . rawurlencode($search) : '') . ($vacancyFilter !== '' ? '&vacancy=' . rawurlencode($vacancyFilter) : '') . ($salaryFilter !== '' ? '&salary_grade=' . rawurlencode($salaryFilter) : ''); ?>
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
    const confirmation = await Swal.fire({title:'Confirm new position',text:'Enter your password to add this position.',input:'password',inputLabel:'Current password',inputAttributes:{autocomplete:'current-password'},showCancelButton:true,confirmButtonText:'Add position',inputValidator:value=>!value?'Password is required.':undefined});
    if (!confirmation.isConfirmed) return;
    const data = new FormData(event.target); data.append('confirmation_password',confirmation.value);
    const result = await HRIS.postForm(`${window.BASE_URL}/admin/positions/store`, data);
    if (result.success) window.location.reload(); else HRIS.flash(result.error || 'Failed to add position.', 'error');
});
async function deletePosition(id) {
    const confirmation = await Swal.fire({title:'Delete position?',text:'Enter your password to confirm this permanent action.',icon:'warning',input:'password',inputLabel:'Current password',inputAttributes:{autocomplete:'current-password'},showCancelButton:true,confirmButtonText:'Delete position',confirmButtonColor:'#dc2626',inputValidator:value=>!value?'Password is required.':undefined});
    if (!confirmation.isConfirmed) return;
    const data = new FormData(); data.append('confirmation_password',confirmation.value);
    const result = await HRIS.postForm(`${window.BASE_URL}/admin/positions/${id}/delete`, data);
    if (result.success) window.location.reload(); else HRIS.flash(result.error || 'Delete failed.', 'error');
}
const viewButtons=[...document.querySelectorAll('[data-position-view]')];const viewPanels=[...document.querySelectorAll('[data-position-panel]')];const setPositionView=view=>{viewButtons.forEach(button=>button.classList.toggle('active',button.dataset.positionView===view));viewPanels.forEach(panel=>panel.hidden=panel.dataset.positionPanel!==view);localStorage.setItem('hrms-position-view',view);};viewButtons.forEach(button=>button.addEventListener('click',()=>setPositionView(button.dataset.positionView)));setPositionView(localStorage.getItem('hrms-position-view')==='cards'?'cards':'list');
</script>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
