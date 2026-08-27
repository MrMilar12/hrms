<?php
/** @var array $logs */
/** @var array $filters */
/** @var array $actions */
/** @var array $summary */
/** @var array $pagination */
/** @var array $activityTrend */
/** @var array $actionBreakdown */
/** @var array $reportMetrics */
require MODULES_PATH . '/shared/views/header.php';
$pageUrl = static function (int $page) use ($filters): string {
    $query = array_filter(array_merge($filters, ['page' => $page]), static fn($value) => $value !== '' && $value !== null);
    return BASE_URL . '/admin/activity?' . http_build_query($query);
};
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
$trendMax = max(1, ...array_column($activityTrend, 'total'));
$actionMax = max(1, ...array_map(static fn(array $row): int => (int) $row['total'], $actionBreakdown ?: [['total' => 0]]));
$busiestDay = $activityTrend ? array_reduce($activityTrend, static fn(?array $carry, array $day): array => !$carry || $day['total'] > $carry['total'] ? $day : $carry, null) : null;
$topAction = $actionBreakdown[0] ?? null;
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

    <section class="activity-report-grid">
        <article class="glass-card activity-trend-card">
            <div class="activity-card-heading"><div><span class="launcher-eyebrow">14-day trend</span><h2>System activity volume</h2><p>Daily audit events recorded by HRMS.</p></div><span class="analytics-chip">Live</span></div>
            <div class="activity-trend-chart" role="img" aria-label="System activity events during the last 14 days">
                <?php foreach ($activityTrend as $day): ?>
                    <div class="activity-trend-column" title="<?= htmlspecialchars($day['label']) ?>: <?= (int) $day['total'] ?> events">
                        <strong><?= (int) $day['total'] ?></strong><div><i style="height:<?= $day['total'] ? max(6, round(((int) $day['total'] / $trendMax) * 100)) : 0 ?>%"></i></div><small><?= htmlspecialchars($day['day']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="glass-card activity-action-card">
            <div class="activity-card-heading"><div><span class="launcher-eyebrow">Last 30 days</span><h2>Most frequent actions</h2><p>Top activity categories by volume.</p></div></div>
            <div class="activity-action-bars">
                <?php foreach ($actionBreakdown as $row): ?>
                    <div><span><?= htmlspecialchars($actionLabel($row['label'])) ?></span><div><i style="width:<?= round(((int) $row['total'] / $actionMax) * 100) ?>%"></i></div><strong><?= number_format((int) $row['total']) ?></strong></div>
                <?php endforeach; ?>
                <?php if (!$actionBreakdown): ?><div class="activity-report-empty">No activity recorded in the last 30 days.</div><?php endif; ?>
            </div>
        </article>

        <article class="glass-card activity-narrative-card">
            <div class="activity-narrative-icon" aria-hidden="true">&#128196;</div>
            <div><span class="launcher-eyebrow">Narrative report</span><h2>Administrative activity summary</h2>
                <p>During the last 30 days, HRMS recorded <strong><?= number_format($reportMetrics['thirtyDayTotal']) ?> audit event<?= $reportMetrics['thirtyDayTotal'] === 1 ? '' : 's' ?></strong> involving <strong><?= number_format($reportMetrics['uniqueActors']) ?> identified user<?= $reportMetrics['uniqueActors'] === 1 ? '' : 's' ?></strong>.</p>
                <p><?= $topAction ? 'The most frequent activity was <strong>' . htmlspecialchars($actionLabel($topAction['label'])) . '</strong>, with <strong>' . number_format((int) $topAction['total']) . ' recorded event' . ((int) $topAction['total'] === 1 ? '' : 's') . '</strong>.' : 'No dominant activity category is available for this reporting period.' ?> <?= $busiestDay && $busiestDay['total'] ? 'Within the 14-day trend, the busiest day was <strong>' . htmlspecialchars(date('F j, Y', strtotime($busiestDay['date']))) . '</strong> with <strong>' . number_format((int) $busiestDay['total']) . ' events</strong>.' : 'No activity was recorded during the last 14 days.' ?></p>
                <p class="<?= $reportMetrics['failedSecurity'] ? 'narrative-alert' : 'narrative-clear' ?>"><?= $reportMetrics['failedSecurity'] ? '<strong>' . number_format($reportMetrics['failedSecurity']) . ' failed or blocked security attempt' . ($reportMetrics['failedSecurity'] === 1 ? '' : 's') . '</strong> require administrative awareness.' : 'No failed or blocked security attempts were recorded during the last 30 days.' ?></p>
            </div>
        </article>
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
        <div class="activity-card-heading"><div><h2>Audit trail</h2><p>Showing <?= number_format($pagination['from']) ?>–<?= number_format($pagination['to']) ?> of <?= number_format($pagination['total']) ?> matching events.</p></div><span class="activity-proof-badge">&#128737; Admin only</span></div>
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
        <?php if ($pagination['totalPages'] > 1): ?>
            <?php
            $startPage = max(1, $pagination['page'] - 2);
            $endPage = min($pagination['totalPages'], $pagination['page'] + 2);
            ?>
            <nav class="activity-pagination" aria-label="Activity log pages">
                <a class="activity-page-control<?= $pagination['page'] <= 1 ? ' disabled' : '' ?>" href="<?= htmlspecialchars($pageUrl(max(1, $pagination['page'] - 1))) ?>" aria-label="Previous page" <?= $pagination['page'] <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>&larr;<span>Previous</span></a>
                <div class="activity-page-numbers">
                    <?php if ($startPage > 1): ?><a href="<?= htmlspecialchars($pageUrl(1)) ?>">1</a><?php if ($startPage > 2): ?><span>&hellip;</span><?php endif; ?><?php endif; ?>
                    <?php for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++): ?><a class="<?= $pageNumber === $pagination['page'] ? 'active' : '' ?>" href="<?= htmlspecialchars($pageUrl($pageNumber)) ?>" <?= $pageNumber === $pagination['page'] ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a><?php endfor; ?>
                    <?php if ($endPage < $pagination['totalPages']): ?><?php if ($endPage < $pagination['totalPages'] - 1): ?><span>&hellip;</span><?php endif; ?><a href="<?= htmlspecialchars($pageUrl($pagination['totalPages'])) ?>"><?= $pagination['totalPages'] ?></a><?php endif; ?>
                </div>
                <a class="activity-page-control<?= $pagination['page'] >= $pagination['totalPages'] ? ' disabled' : '' ?>" href="<?= htmlspecialchars($pageUrl(min($pagination['totalPages'], $pagination['page'] + 1))) ?>" aria-label="Next page" <?= $pagination['page'] >= $pagination['totalPages'] ? 'aria-disabled="true" tabindex="-1"' : '' ?>><span>Next</span>&rarr;</a>
            </nav>
        <?php endif; ?>
    </section>
</div>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
