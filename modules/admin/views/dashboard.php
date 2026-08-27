<?php
/** @var array $summary */
/** @var array $gender */
/** @var array $departmentStats */
/** @var array $positionStats */
/** @var array $employmentStats */
/** @var array $taskStats */
/** @var array $ageStats */
/** @var array $retirementEmployees */
/** @var array $tenureStats */
/** @var array $accomplishmentStats */
/** @var array $monthlySubmissions */
require MODULES_PATH . '/shared/views/header.php';

$employeeTotal = max(1, $summary['employees']);
$malePercent = round(($gender['Male'] / $employeeTotal) * 100, 1);
$femalePercent = round(($gender['Female'] / $employeeTotal) * 100, 1);
$otherPercent = max(0, 100 - $malePercent - $femalePercent);
$teachingPercent = $summary['employees'] > 0 ? round(($summary['teaching'] / $summary['employees']) * 100, 1) : 0;
$nonTeachingPercent = $summary['employees'] > 0 ? round(($summary['nonTeaching'] / $summary['employees']) * 100, 1) : 0;
$unclassifiedPersonnelPercent = $summary['employees'] > 0 ? max(0, round(100 - $teachingPercent - $nonTeachingPercent, 1)) : 0;
$submissionMax = max(1, ...array_column($monthlySubmissions, 'total'));
$departmentMax = max(1, ...array_map(fn($row) => (int) $row['total'], $departmentStats ?: [['total' => 0]]));
$positionMax = max(1, ...array_map(fn($row) => (int) $row['total'], $positionStats ?: [['total' => 0]]));
$taskTotal = max(1, array_sum(array_map(fn($row) => (int) $row['total'], $taskStats)));
$taskColors = ['Open' => '#60a5fa', 'In Progress' => '#8b5cf6', 'For Review' => '#f59e0b', 'Done' => '#22c55e', 'Cancelled' => '#94a3b8'];
$ageMax = max(1, ...array_values($ageStats));
$tenureMax = max(1, ...array_values($tenureStats));
$accomplishmentTotal = array_sum($accomplishmentStats);
$accomplishmentChartTotal = max(1, $accomplishmentTotal);
$accomplishmentColors = ['Draft' => '#94a3b8', 'For Review' => '#f59e0b', 'Approved' => '#22c55e', 'Returned' => '#f43f5e'];
$accomplishmentStops = [];
$accomplishmentCursor = 0;
foreach ($accomplishmentStats as $label => $total) {
    $next = $accomplishmentCursor + (((int) $total / $accomplishmentChartTotal) * 100);
    $color = $accomplishmentColors[$label];
    $accomplishmentStops[] = $color . ' ' . $accomplishmentCursor . '% ' . $next . '%';
    $accomplishmentCursor = $next;
}
$accomplishmentGradient = $accomplishmentTotal ? implode(',', $accomplishmentStops) : 'var(--glass-border-soft) 0 100%';
?>
<section class="admin-analytics">
    <div class="analytics-hero">
        <div><span class="launcher-eyebrow">Workforce analytics</span><h1>Workforce intelligence</h1><p>A clear view of your people, submissions, and operational progress.</p></div>
        <div class="analytics-period"><span>Reporting as of</span><strong><?= htmlspecialchars(date('F j, Y')) ?></strong></div>
    </div>

    <div class="analytics-kpis">
        <article class="analytics-kpi analytics-kpi-blue" data-analytics-detail="employees"><span class="analytics-kpi-icon">&#128101;</span><div><small>Total employees</small><strong><?= number_format($summary['employees']) ?></strong><span>Across <?= number_format($summary['departments']) ?> departments</span></div></article>
        <article class="analytics-kpi analytics-kpi-green" data-analytics-detail="teaching"><span class="analytics-kpi-icon">&#127891;</span><div><small>Teaching personnel</small><strong><?= number_format($summary['teaching']) ?></strong><span><?= $employeeTotal ? round(($summary['teaching'] / $employeeTotal) * 100, 1) : 0 ?>% of employees</span></div></article>
        <article class="analytics-kpi analytics-kpi-violet" data-analytics-detail="non_teaching"><span class="analytics-kpi-icon">&#128188;</span><div><small>Non-Teaching personnel</small><strong><?= number_format($summary['nonTeaching']) ?></strong><span><?= $employeeTotal ? round(($summary['nonTeaching'] / $employeeTotal) * 100, 1) : 0 ?>% of employees<?= $summary['unclassifiedPersonnel'] ? ' · ' . number_format($summary['unclassifiedPersonnel']) . ' unclassified' : '' ?></span></div></article>
        <article class="analytics-kpi analytics-kpi-violet" data-analytics-detail="active_users"><span class="analytics-kpi-icon">&#128273;</span><div><small>Active accounts</small><strong><?= number_format($summary['activeUsers']) ?></strong><span>Enabled system users</span></div></article>
        <article class="analytics-kpi analytics-kpi-orange" data-analytics-detail="open_tasks"><span class="analytics-kpi-icon">&#9203;</span><div><small>Open assignments</small><strong><?= number_format($summary['openTasks']) ?></strong><span>Individual work items</span></div></article>
        <article class="analytics-kpi analytics-kpi-green" data-analytics-detail="pds"><span class="analytics-kpi-icon">&#128196;</span><div><small>Average PDS</small><strong><?= $summary['pdsAverage'] ?>%</strong><span>Organization completion</span></div></article>
        <article class="analytics-kpi analytics-kpi-pink" data-analytics-detail="review"><span class="analytics-kpi-icon">&#10022;</span><div><small>Awaiting review</small><strong><?= number_format($summary['pendingAccomplishments']) ?></strong><span>Accomplishment submissions</span></div></article>
        <article class="analytics-kpi analytics-kpi-retirement" data-analytics-detail="retirement"><span class="analytics-kpi-icon" aria-hidden="true">&#9203;</span><div><small>Retirement bracket</small><strong><?= number_format($summary['retirementAge']) ?></strong><span>Employees aged 60–65</span></div></article>
    </div>

    <div class="analytics-grid analytics-grid-primary">
        <article class="analytics-card glass analytics-submissions">
            <div class="analytics-card-head"><div><span class="launcher-eyebrow">12-month activity</span><h2>Accomplishment submissions</h2></div><span class="analytics-chip">Monthly</span></div>
            <div class="analytics-bars" role="img" aria-label="Monthly accomplishment submission chart">
                <?php foreach ($monthlySubmissions as $month): ?>
                    <div class="analytics-bar-column" title="<?= htmlspecialchars($month['label'] . ' ' . $month['year']) ?>: <?= (int) $month['total'] ?> submissions">
                        <span class="analytics-bar-value"><?= (int) $month['total'] ?></span>
                        <div class="analytics-bar-track"><span style="height:<?= max(3, round(((int) $month['total'] / $submissionMax) * 100)) ?>%"></span></div>
                        <small><?= htmlspecialchars($month['label']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="analytics-card glass">
            <div class="analytics-card-head"><div><span class="launcher-eyebrow">Workforce</span><h2>Male and female</h2></div></div>
            <div class="gender-chart-wrap">
                <div class="gender-donut" style="--male:<?= $malePercent ?>%;--female:<?= $malePercent + $femalePercent ?>%;"><div><strong><?= number_format($summary['employees']) ?></strong><span>Employees</span></div></div>
                <div class="analytics-legend">
                    <div><span class="legend-dot legend-male"></span><span>Male</span><strong><?= number_format($gender['Male']) ?></strong><small><?= $malePercent ?>%</small></div>
                    <div><span class="legend-dot legend-female"></span><span>Female</span><strong><?= number_format($gender['Female']) ?></strong><small><?= $femalePercent ?>%</small></div>
                    <div><span class="legend-dot legend-other"></span><span>Not specified</span><strong><?= number_format($gender['Not specified']) ?></strong><small><?= $otherPercent ?>%</small></div>
                </div>
            </div>
        </article>
    </div>

    <article class="analytics-card glass analytics-positions">
        <div class="analytics-card-head">
            <div><span class="launcher-eyebrow">Plantilla occupancy</span><h2>Occupied plantilla positions</h2><p>Number of employees currently assigned to each plantilla position.</p></div>
            <div class="analytics-position-summary">
                <span><strong><?= number_format($summary['occupiedPositions']) ?></strong> occupied titles</span>
                <span><strong><?= number_format($summary['positionAssignedEmployees']) ?></strong> assigned employees</span>
                <?php if ($summary['positionUnassignedEmployees'] > 0): ?><span><strong><?= number_format($summary['positionUnassignedEmployees']) ?></strong> unassigned</span><?php endif; ?>
            </div>
        </div>
        <?php if ($positionStats): ?>
            <div class="plantilla-graph" role="img" aria-label="Horizontal bar graph showing employee count per occupied plantilla position">
                <div class="plantilla-graph-axis"><span>Plantilla position</span><span>Occupied employees</span></div>
                <?php foreach ($positionStats as $row): ?>
                    <?php $positionPercent = round(((int) $row['total'] / $positionMax) * 100, 1); ?>
                    <div class="plantilla-graph-row" title="<?= htmlspecialchars($row['label']) ?>: <?= (int) $row['total'] ?> occupied">
                        <div class="plantilla-graph-label"><strong><?= htmlspecialchars($row['label']) ?></strong><small><?= htmlspecialchars($row['salary_grade'] ?: 'No salary grade') ?></small></div>
                        <div class="plantilla-graph-track"><span style="width:<?= $positionPercent ?>%"></span></div>
                        <output><?= number_format((int) $row['total']) ?></output>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="analytics-empty">No employees have been assigned to a plantilla position yet.</div>
        <?php endif; ?>
    </article>

    <div class="analytics-grid analytics-grid-secondary">
        <article class="analytics-card glass">
            <div class="analytics-card-head"><div><span class="launcher-eyebrow">Organization</span><h2>Employees by department</h2></div></div>
            <div class="horizontal-chart">
                <?php foreach ($departmentStats as $row): ?>
                    <div class="horizontal-chart-row"><span><?= htmlspecialchars($row['label']) ?></span><div><i style="width:<?= round(((int) $row['total'] / $departmentMax) * 100) ?>%"></i></div><strong><?= (int) $row['total'] ?></strong></div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="analytics-card glass">
            <div class="analytics-card-head"><div><span class="launcher-eyebrow">Assignments</span><h2>Task progress</h2></div></div>
            <div class="task-progress-stack">
                <?php foreach ($taskStats as $row): ?><span style="width:<?= ((int) $row['total'] / $taskTotal) * 100 ?>%;background:<?= $taskColors[$row['label']] ?? '#64748b' ?>" title="<?= htmlspecialchars($row['label']) ?>: <?= (int) $row['total'] ?>"></span><?php endforeach; ?>
            </div>
            <div class="task-progress-list">
                <?php foreach ($taskStats as $row): ?><div><span class="legend-dot" style="background:<?= $taskColors[$row['label']] ?? '#64748b' ?>"></span><span><?= htmlspecialchars($row['label']) ?></span><strong><?= (int) $row['total'] ?></strong><small><?= round(((int) $row['total'] / $taskTotal) * 100) ?>%</small></div><?php endforeach; ?>
            </div>
        </article>

        <article class="analytics-card glass">
            <div class="analytics-card-head"><div><span class="launcher-eyebrow">Employment</span><h2>Workforce status</h2></div></div>
            <div class="employment-list">
                <?php foreach ($employmentStats as $row): ?>
                    <div><span><?= htmlspecialchars($row['label']) ?></span><strong><?= (int) $row['total'] ?></strong><div><i style="width:<?= round(((int) $row['total'] / $employeeTotal) * 100) ?>%"></i></div></div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="analytics-card glass">
            <div class="analytics-card-head"><div><span class="launcher-eyebrow">Classification</span><h2>Teaching and Non-Teaching</h2></div></div>
            <div class="gender-chart-wrap">
                <div class="personnel-donut" style="--teaching:<?= $teachingPercent ?>%;--non-teaching:<?= $teachingPercent + $nonTeachingPercent ?>%;" role="img" aria-label="Teaching <?= $teachingPercent ?> percent, Non-Teaching <?= $nonTeachingPercent ?> percent, Unclassified <?= $unclassifiedPersonnelPercent ?> percent"><div><strong><?= number_format($summary['employees']) ?></strong><span>Employees</span></div></div>
                <div class="analytics-legend">
                    <div><span class="legend-dot legend-teaching"></span><span>Teaching</span><strong><?= number_format($summary['teaching']) ?></strong><small><?= $teachingPercent ?>%</small></div>
                    <div><span class="legend-dot legend-non-teaching"></span><span>Non-Teaching</span><strong><?= number_format($summary['nonTeaching']) ?></strong><small><?= $nonTeachingPercent ?>%</small></div>
                    <div><span class="legend-dot legend-other"></span><span>Unclassified</span><strong><?= number_format($summary['unclassifiedPersonnel']) ?></strong><small><?= $unclassifiedPersonnelPercent ?>%</small></div>
                </div>
            </div>
        </article>
    </div>

    <div class="analytics-card-head analytics-section-heading">
        <div><span class="launcher-eyebrow">People insights</span><h2>Workforce composition and outcomes</h2><p>Additional indicators for planning, retention, and submission review.</p></div>
        <span class="analytics-chip">Live data</span>
    </div>
    <div class="analytics-grid analytics-grid-insights">
        <article class="analytics-card glass">
            <div class="analytics-card-head"><div><span class="launcher-eyebrow">Demographics</span><h2>Employee age distribution</h2><p>The 60–65 retirement bracket is shown separately.</p></div></div>
            <div class="distribution-columns" role="img" aria-label="Employee count by age group">
                <?php foreach ($ageStats as $label => $total): ?>
                    <div class="distribution-column<?= $label === '60–65' ? ' is-retirement' : '' ?>" title="<?= htmlspecialchars($label) ?>: <?= (int) $total ?> employees">
                        <strong><?= number_format((int) $total) ?></strong>
                        <div><i style="height:<?= (int) $total > 0 ? max(8, round(((int) $total / $ageMax) * 100)) : 0 ?>%"></i></div>
                        <span><?= htmlspecialchars($label) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="analytics-card glass">
            <div class="analytics-card-head"><div><span class="launcher-eyebrow">Retention</span><h2>Length of service</h2><p>Computed only from the employee&rsquo;s saved date hired.</p></div></div>
            <div class="insight-bars" role="img" aria-label="Employee count by length of service">
                <?php foreach ($tenureStats as $label => $total): ?>
                    <div class="insight-bar-row" title="<?= htmlspecialchars($label) ?>: <?= (int) $total ?> employees">
                        <span><?= htmlspecialchars($label) ?></span>
                        <div><i style="width:<?= (int) $total > 0 ? max(3, round(((int) $total / $tenureMax) * 100)) : 0 ?>%"></i></div>
                        <strong><?= number_format((int) $total) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="analytics-card glass">
            <div class="analytics-card-head"><div><span class="launcher-eyebrow">Quality workflow</span><h2>Accomplishment outcomes</h2></div></div>
            <div class="outcome-chart-wrap">
                <div class="outcome-donut" style="background:conic-gradient(<?= htmlspecialchars($accomplishmentGradient) ?>)" role="img" aria-label="Accomplishment records grouped by review status"><div><strong><?= number_format($accomplishmentTotal) ?></strong><span>Records</span></div></div>
                <div class="analytics-legend outcome-legend">
                    <?php foreach ($accomplishmentStats as $label => $total): ?>
                        <div><span class="legend-dot" style="background:<?= $accomplishmentColors[$label] ?>"></span><span><?= htmlspecialchars($label) ?></span><strong><?= number_format((int) $total) ?></strong><small><?= round(((int) $total / $accomplishmentChartTotal) * 100) ?>%</small></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>
    </div>

    <article class="analytics-card glass retirement-roster">
        <div class="analytics-card-head">
            <div><span class="launcher-eyebrow">Retirement monitoring</span><h2>Employees aged 60–65</h2><p>Employees are included automatically based on the birth date saved in their PDS.</p></div>
            <span class="retirement-count"><strong><?= number_format(count($retirementEmployees)) ?></strong> employee<?= count($retirementEmployees) === 1 ? '' : 's' ?></span>
        </div>
        <?php if ($retirementEmployees): ?>
            <div class="retirement-list">
                <?php foreach ($retirementEmployees as $employee): ?>
                    <?php
                    $fullName = trim(implode(' ', array_filter([
                        $employee['first_name'], $employee['middle_name'], $employee['surname'], $employee['name_extension'],
                    ])));
                    ?>
                    <a class="retirement-person" href="<?= BASE_URL ?>/employees/<?= UrlId::encode((int) $employee['id']) ?>">
                        <span class="retirement-avatar" aria-hidden="true"><?= htmlspecialchars(strtoupper(substr($employee['first_name'] ?: $employee['surname'] ?: '?', 0, 1))) ?></span>
                        <span class="retirement-person-main"><strong><?= htmlspecialchars($fullName ?: 'Unnamed employee') ?></strong><small><?= htmlspecialchars($employee['employee_number']) ?> · <?= htmlspecialchars($employee['position_title']) ?></small></span>
                        <span class="retirement-person-detail"><small>Department</small><strong><?= htmlspecialchars($employee['department_name']) ?></strong></span>
                        <span class="retirement-person-detail"><small>Birth date</small><strong><?= htmlspecialchars(date('M j, Y', strtotime($employee['birth_date']))) ?></strong></span>
                        <span class="retirement-age"><strong><?= (int) $employee['age'] ?></strong><small>years old</small></span>
                        <span class="retirement-open" aria-hidden="true">&rarr;</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="retirement-empty"><span aria-hidden="true">&#10003;</span><div><strong>No employees are currently in the 60–65 bracket.</strong><p>The roster will update automatically when a saved PDS birth date falls within this range.</p></div></div>
        <?php endif; ?>
    </article>
</section>

<div class="analytics-modal-backdrop" id="analytics-detail-modal" hidden>
    <section class="analytics-modal glass-strong" role="dialog" aria-modal="true" aria-labelledby="analytics-modal-title">
        <header class="analytics-modal-head">
            <div><span class="launcher-eyebrow">Analytics details</span><h2 id="analytics-modal-title">Data list</h2><p id="analytics-modal-subtitle">Live values represented by this card.</p></div>
            <button type="button" class="analytics-modal-close" aria-label="Close analytics details">&times;</button>
        </header>
        <div class="analytics-modal-toolbar">
            <label class="analytics-modal-search"><span aria-hidden="true">&#128269;</span><input type="search" id="analytics-modal-search" placeholder="Search this data list…" autocomplete="off"></label>
            <span class="analytics-modal-count" id="analytics-modal-count">0 records</span>
        </div>
        <div class="analytics-modal-list" id="analytics-modal-list"></div>
    </section>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    const cards = [...document.querySelectorAll('.admin-analytics .analytics-kpi, .admin-analytics .analytics-card')];
    const backdrop = document.getElementById('analytics-detail-modal');
    const modal = backdrop?.querySelector('.analytics-modal');
    const title = document.getElementById('analytics-modal-title');
    const subtitle = document.getElementById('analytics-modal-subtitle');
    const list = document.getElementById('analytics-modal-list');
    const search = document.getElementById('analytics-modal-search');
    const count = document.getElementById('analytics-modal-count');
    const detailTypes = ['employees','teaching','non_teaching','active_users','open_tasks','pds','review','retirement','submissions','gender','positions','departments','tasks','employment','personnel','age','tenure','accomplishments','retirement'];
    let rows = [];

    const normalize = value => String(value || '').replace(/\s+/g, ' ').trim();
    const escapeHtml = value => normalize(value).replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char]));
    const cardTitle = card => normalize(card.querySelector('h2')?.textContent || card.querySelector('small')?.textContent || 'Analytics data');

    const collectRows = card => {
        if (card.classList.contains('analytics-kpi')) {
            return [{ label: normalize(card.querySelector('small')?.textContent), value: normalize(card.querySelector('strong')?.textContent), detail: normalize(card.querySelector('div > span')?.textContent) }];
        }
        const selectors = ['.retirement-person', '.plantilla-graph-row', '.horizontal-chart-row', '.task-progress-list > div', '.employment-list > div', '.analytics-legend > div', '.distribution-column', '.insight-bar-row', '.analytics-bar-column'];
        const elements = [...card.querySelectorAll(selectors.join(','))].filter((element, index, all) => !all.some((other, otherIndex) => otherIndex !== index && other.contains(element)));
        const values = elements.map(element => {
            const titled = normalize(element.getAttribute('title'));
            const text = normalize(element.textContent);
            const parts = (titled || text).split(':');
            return { label: normalize(parts.shift()), value: normalize(parts.join(':')), detail: titled && text !== titled ? text : '' };
        }).filter(row => row.label || row.value);
        if (values.length) return values;
        const description = normalize(card.querySelector('.analytics-card-head p')?.textContent);
        return [{ label: cardTitle(card), value: normalize(card.querySelector('.retirement-count, .analytics-position-summary')?.textContent), detail: description || 'No detailed records are available.' }];
    };

    const render = () => {
        const term = normalize(search.value).toLocaleLowerCase();
        const filtered = rows.filter(row => normalize(`${row.label} ${row.value} ${row.detail}`).toLocaleLowerCase().includes(term));
        count.textContent = `${filtered.length} ${filtered.length === 1 ? 'record' : 'records'}`;
        list.innerHTML = filtered.length ? filtered.map((row, index) => `<article class="analytics-modal-row"><span class="analytics-modal-index">${index + 1}</span><div><strong>${escapeHtml(row.label || 'Record')}</strong>${row.detail ? `<small>${escapeHtml(row.detail)}</small>` : ''}</div><output>${escapeHtml(row.value)}</output></article>`).join('') : '<div class="analytics-modal-empty"><span>⌕</span><strong>No matching data</strong><small>Try a different search term.</small></div>';
    };

    const open = async card => {
        rows = [];
        title.textContent = cardTitle(card);
        subtitle.textContent = normalize(card.querySelector('.analytics-card-head p')?.textContent || card.querySelector('div > span')?.textContent || 'Live values represented by this analytics card.');
        search.value = '';
        list.innerHTML = '<div class="analytics-modal-loading"><span></span><strong>Loading live records…</strong><small>Please wait while the data list is prepared.</small></div>';
        count.textContent = 'Loading…';
        backdrop.hidden = false;
        document.body.classList.add('analytics-modal-open');
        requestAnimationFrame(() => backdrop.classList.add('open'));
        try {
            const response = await fetch(`${window.BASE_URL}/admin/analytics/details?type=${encodeURIComponent(card.dataset.analyticsDetail)}`, { headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.error || 'Unable to load analytics data.');
            rows = Array.isArray(result.rows) ? result.rows : [];
            subtitle.textContent = result.limited ? 'Showing the first 1,000 live records. Use search to narrow the list.' : 'Live records represented by this analytics card.';
            render();
            window.setTimeout(() => search.focus(), 80);
        } catch (error) {
            rows = collectRows(card);
            render();
            subtitle.textContent = `${error.message} Displaying the visible chart data instead.`;
        }
    };
    const close = () => {
        backdrop.classList.remove('open');
        document.body.classList.remove('analytics-modal-open');
        window.setTimeout(() => { backdrop.hidden = true; }, 180);
    };

    cards.forEach((card, index) => {
        card.dataset.analyticsDetail = card.dataset.analyticsDetail || detailTypes[index] || 'employees';
        card.classList.add('analytics-clickable');
        const action = document.createElement('button');
        action.type = 'button';
        action.className = 'analytics-card-action';
        action.title = 'View data list';
        action.setAttribute('aria-label', `View ${cardTitle(card)} data list`);
        action.innerHTML = '<span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></span><strong>View data list</strong><b aria-hidden="true">&rarr;</b>';
        action.addEventListener('click', () => open(card));
        card.appendChild(action);
        card.addEventListener('click', event => { if (!event.target.closest('a, button, input, select')) open(card); });
    });
    search?.addEventListener('input', render);
    backdrop?.querySelector('.analytics-modal-close')?.addEventListener('click', close);
    backdrop?.addEventListener('click', event => { if (event.target === backdrop) close(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && !backdrop.hidden) close(); });
});
</script>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
