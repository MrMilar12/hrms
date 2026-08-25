<?php
/** @var array $releases */
require MODULES_PATH . '/shared/views/header.php';
?>
<section class="release-history-page">
    <div class="glass-card release-history-hero">
        <div><span class="launcher-eyebrow">Release history</span><h1>What's New in HRMS</h1><p>Features, improvements, and fixes included in each system version.</p></div>
        <span class="release-version">Current version <?= htmlspecialchars(SystemRelease::currentVersion()) ?></span>
    </div>
    <div class="release-history-list">
        <?php foreach ($releases as $release): ?>
            <article class="glass-card release-history-item">
                <div class="release-history-marker"><span>v<?= htmlspecialchars($release['version']) ?></span><time><?= htmlspecialchars(date('F j, Y', strtotime($release['released_at']))) ?></time></div>
                <div><h2><?= htmlspecialchars($release['title']) ?></h2><div class="release-changes"><?= nl2br(htmlspecialchars($release['changes'])) ?></div><?php if (!empty($release['release_url'])): ?><a class="release-source-link" href="<?= htmlspecialchars($release['release_url']) ?>" target="_blank" rel="noopener noreferrer">View on GitHub &rarr;</a><?php endif; ?></div>
            </article>
        <?php endforeach; ?>
        <?php if (!$releases): ?><div class="glass-card release-empty">No published system updates yet.</div><?php endif; ?>
    </div>
</section>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
