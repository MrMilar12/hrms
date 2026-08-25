<?php
/** @var array $summary */
/** @var array $gender */
/** @var array $departmentStats */
/** @var array $positionStats */
/** @var array $employmentStats */
/** @var array $taskStats */
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
?>
<section class="admin-analytics">
    <div class="analytics-hero">
        <div><span class="launcher-eyebrow">Workforce analytics</span><h1>Workforce intelligence</h1><p>A clear view of your people, submissions, and operational progress.</p></div>
        <div class="analytics-period"><span>Reporting as of</span><strong><?= htmlspecialchars(date('F j, Y')) ?></strong></div>
    </div>

    <div class="analytics-kpis">
        <article class="analytics-kpi analytics-kpi-blue"><span class="analytics-kpi-icon">&#128101;</span><div><small>Total employees</small><strong><?= number_format($summary['employees']) ?></strong><span>Across <?= number_format($summary['departments']) ?> departments</span></div></article>
        <article class="analytics-kpi analytics-kpi-green"><span class="analytics-kpi-icon">&#127891;</span><div><small>Teaching personnel</small><strong><?= number_format($summary['teaching']) ?></strong><span><?= $employeeTotal ? round(($summary['teaching'] / $employeeTotal) * 100, 1) : 0 ?>% of employees</span></div></article>
        <article class="analytics-kpi analytics-kpi-violet"><span class="analytics-kpi-icon">&#128188;</span><div><small>Non-Teaching personnel</small><strong><?= number_format($summary['nonTeaching']) ?></strong><span><?= $employeeTotal ? round(($summary['nonTeaching'] / $employeeTotal) * 100, 1) : 0 ?>% of employees<?= $summary['unclassifiedPersonnel'] ? ' · ' . number_format($summary['unclassifiedPersonnel']) . ' unclassified' : '' ?></span></div></article>
        <article class="analytics-kpi analytics-kpi-violet"><span class="analytics-kpi-icon">&#128273;</span><div><small>Active accounts</small><strong><?= number_format($summary['activeUsers']) ?></strong><span>Enabled system users</span></div></article>
        <article class="analytics-kpi analytics-kpi-orange"><span class="analytics-kpi-icon">&#9203;</span><div><small>Open assignments</small><strong><?= number_format($summary['openTasks']) ?></strong><span>Individual work items</span></div></article>
        <article class="analytics-kpi analytics-kpi-green"><span class="analytics-kpi-icon">&#128196;</span><div><small>Average PDS</small><strong><?= $summary['pdsAverage'] ?>%</strong><span>Organization completion</span></div></article>
        <article class="analytics-kpi analytics-kpi-pink"><span class="analytics-kpi-icon">&#10022;</span><div><small>Awaiting review</small><strong><?= number_format($summary['pendingAccomplishments']) ?></strong><span>Accomplishment submissions</span></div></article>
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
</section>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
