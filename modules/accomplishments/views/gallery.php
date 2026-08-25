<?php
/** @var array $items */
/** @var array $statuses */
/** @var string $activeStatus */
require MODULES_PATH . '/shared/views/header.php';
?>
<div class="glass-card">
    <a href="<?= BASE_URL ?>/accomplishments" style="font-size:0.85rem; color:var(--text-muted);">&larr; Back to Accomplishments</a>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-top:0.5rem;">
        <div>
            <h2 style="margin:0;">My Accomplishment Gallery</h2>
            <p style="margin:0.25rem 0 0; color:var(--text-muted); font-size:0.85rem;">View and download your accomplishment records, alongside your evidence photos.</p>
        </div>
    </div>
    <div class="tabs" style="margin-top:1.25rem; margin-bottom:0;">
        <a class="<?= $activeStatus === 'All' ? 'active' : '' ?>" href="<?= BASE_URL ?>/accomplishments/gallery?status=All">All</a>
        <?php foreach ($statuses as $s): ?>
            <a class="<?= $activeStatus === $s ? 'active' : '' ?>" href="<?= BASE_URL ?>/accomplishments/gallery?status=<?= urlencode($s) ?>"><?= $s ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="gallery-grid">
    <?php foreach ($items as $item): ?>
        <div class="gallery-tile glass-light">
            <?php if ($item['cover_attachment_id']): ?>
                <img src="<?= BASE_URL ?>/files/accomplishment-attachment/<?= UrlId::encode((int) $item['cover_attachment_id']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
            <?php else: ?>
                <div class="accomplishment-cover-placeholder" style="height:100%;">&#128247;</div>
            <?php endif; ?>
            <div class="gallery-tile-actions">
                <a href="<?= BASE_URL ?>/accomplishments/<?= UrlId::encode((int) $item['id']) ?>" title="View">&#128065;</a>
                <?php if ($item['status'] === 'Approved'): ?><a href="<?= BASE_URL ?>/accomplishments/<?= UrlId::encode((int) $item['id']) ?>/print" target="_blank" title="Download / Print">&#11015;</a><?php endif; ?>
            </div>
            <div class="gallery-tile-overlay">
                <strong><?= htmlspecialchars($item['title']) ?></strong>
                <span><?= htmlspecialchars($item['accomplishment_date']) ?> &middot; <?= htmlspecialchars($item['status']) ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php if (!$items): ?>
    <div class="glass-card" style="text-align:center; color:var(--text-muted);">No accomplishments in this category yet.</div>
<?php endif; ?>

<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
