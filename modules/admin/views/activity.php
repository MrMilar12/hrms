<?php
/** @var array $logs */
/** @var array $filters */
/** @var array $actions */
/** @var array $summary */
require MODULES_PATH . '/shared/views/header.php';
$actionLabel = static fn(string $action): string => ucwords(str_replace('_', ' ', $action));
$actionIcon = static function (string $action): string {
    if (str_contains($action, 'login') || str_contains($action, '2fa') || str_contains($action, 'password')) return '&#128274;';
    if (str_contains($action, 'create') || str_contains($action, 'upload')) return '&#10010;';
    if (str_contains($action, 'delete')) return '&#128465;';
    if (str_contains($action, 'review') || str_contains($action, 'approve')) return '&#10003;';
    return '&#9998;';
};
$readableDetails = static function (?array $details): array {
    if (!$details) return [];
    $rows = [];
    foreach ($details as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $nestedKey => $nestedValue) {
                $label = $key === '_request' ? ($nestedKey === 'path' ? 'Page' : 'Request ' . $nestedKey) : $key . ' ' . $nestedKey;
                $rows[] = [ucwords(str_replace('_', ' ', $label)), $nestedValue];
            }
            continue;
        }
        $rows[] = [ucwords(str_replace('_', ' ', ltrim($key, '_'))), $value];
    }
    return array_map(static function (array $row): array {
        if (is_bool($row[1])) $row[1] = $row[1] ? 'Yes' : 'No';
        if ($row[1] === null || $row[1] === '') $row[1] = 'Not provided';
        return [$row[0], (string) $row[1]];
    }, $rows);
};
?>
<div class="activity-page">
    <section class="activity-hero glass-card">
        <div><span class="launcher-eyebrow">Accountability &amp; proof</span><h1>System activity logs</h1><p>A chronological, administrator-only record of important activity across HRMS.</p></div>
        <div class="activity-summary">
            <div><strong><?= (int) $summary['today'] ?></strong><span>Today</span></div>
            <div><strong><?= (int) $summary['week'] ?></strong><span>Last 7 days</span></div>
            <div><strong><?= (int) $summary['security'] ?></strong><span>Security events</span></div>
        </div>
    </section>

    <form class="activity-filters glass-card" method="get" action="<?= BASE_URL ?>/admin/activity">
        <div class="glass-search"><span>&#128269;</span><input name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Search actor, action, module, or IP address"></div>
        <select name="action" aria-label="Action"><option value="">All actions</option><?php foreach ($actions as $action): ?><option value="<?= htmlspecialchars($action) ?>" <?= $filters['action'] === $action ? 'selected' : '' ?>><?= htmlspecialchars($actionLabel($action)) ?></option><?php endforeach; ?></select>
        <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>" aria-label="From date">
        <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>" aria-label="To date">
        <button class="btn btn-primary" type="submit">Apply filters</button>
        <?php if (array_filter($filters)): ?><a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/activity">Clear</a><?php endif; ?>
    </form>

    <section class="glass-card activity-log-card">
        <div class="activity-card-heading"><div><h2>Audit trail</h2><p>Showing the latest <?= count($logs) ?> matching events.</p></div><span class="activity-proof-badge">&#128737; Admin only</span></div>
        <div class="activity-timeline">
            <?php foreach ($logs as $log):
                $old = $log['old_value'] ? json_decode($log['old_value'], true) : null;
                $new = $log['new_value'] ? json_decode($log['new_value'], true) : null;
                $oldDetails = $readableDetails($old);
                $newDetails = $readableDetails($new);
            ?>
            <article class="activity-event">
                <span class="activity-event-icon"><?= $actionIcon($log['action']) ?></span>
                <div class="activity-event-main">
                    <div class="activity-event-title"><strong><?= htmlspecialchars($actionLabel($log['action'])) ?></strong><span><?= htmlspecialchars($log['table_name']) ?><?= $log['record_id'] ? ' · Record #' . (int) $log['record_id'] : '' ?></span></div>
                    <div class="activity-event-meta"><span>&#128100; <?= htmlspecialchars($log['actor_name'] ?? 'System') ?></span><?php if ($log['role_name']): ?><span><?= htmlspecialchars($log['role_name']) ?></span><?php endif; ?><span>&#127760; <?= htmlspecialchars($log['ip_address'] ?: 'Unknown IP') ?></span><time datetime="<?= htmlspecialchars($log['created_at']) ?>"><?= date('M j, Y · g:i:s A', strtotime($log['created_at'])) ?></time></div>
                    <?php if ($oldDetails || $newDetails): ?><details class="activity-details"><summary>View recorded details</summary><div class="activity-detail-grid">
                        <?php if ($oldDetails): ?><div><span class="activity-detail-title">Previous information</span><dl><?php foreach ($oldDetails as [$label, $value]): ?><div><dt><?= htmlspecialchars($label) ?></dt><dd><?= htmlspecialchars($value) ?></dd></div><?php endforeach; ?></dl></div><?php endif; ?>
                        <?php if ($newDetails): ?><div><span class="activity-detail-title"><?= $oldDetails ? 'Updated information' : 'Activity information' ?></span><dl><?php foreach ($newDetails as [$label, $value]): ?><div><dt><?= htmlspecialchars($label) ?></dt><dd><?= htmlspecialchars($value) ?></dd></div><?php endforeach; ?></dl></div><?php endif; ?>
                    </div></details><?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
            <?php if (!$logs): ?><div class="activity-empty"><span>&#128220;</span><strong>No activity found</strong><p>Try clearing or changing the filters.</p></div><?php endif; ?>
        </div>
    </section>
</div>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
