<?php
/** @var array $employees */
/** @var bool $canManage */
/** @var int $page */
/** @var int $totalPages */
$filterQuery = array_filter($filters, static fn($value): bool => $value !== '' && $value !== 0);
require MODULES_PATH . '/shared/views/header.php';
?>
<div class="glass-card employee-directory-page">
    <div class="employee-directory-header">
        <div class="employee-directory-heading"><span class="launcher-eyebrow">People directory</span><h2>Employees</h2><p>Search and manage personnel 201 files.</p></div>
        <div class="employee-directory-actions">
            <?php if ($canManage): ?>
                <a class="btn btn-secondary" href="<?= BASE_URL ?>/vacant-positions">Vacant Positions</a>
                <a class="btn btn-primary" href="<?= BASE_URL ?>/employees/create"><span aria-hidden="true">+</span> Add Employee</a>
            <?php endif; ?>
        </div>
    </div>
    <form class="employee-filter-panel" method="get" action="<?= BASE_URL ?>/employees">
        <div class="employee-filter-topline"><div><strong>Find an employee</strong><span><?= number_format($total) ?> result<?= $total===1?'':'s' ?></span></div><?php if($filterQuery): ?><a href="<?= BASE_URL ?>/employees">Reset all</a><?php endif; ?></div>
        <label class="employee-filter-search"><span>Search</span><div class="glass-search"><i aria-hidden="true">&#128269;</i><input type="search" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Search name, employee number, position, or station"></div></label>
        <div class="employee-filter-grid">
            <label><span>Personnel type</span><select name="personnel_type"><option value="">All personnel</option><option value="Teaching" <?= $filters['personnel_type']==='Teaching'?'selected':'' ?>>Teaching</option><option value="Non-Teaching" <?= $filters['personnel_type']==='Non-Teaching'?'selected':'' ?>>Non-Teaching</option></select></label>
            <label><span>Employment status</span><select name="employment_status"><option value="">All statuses</option><?php foreach($statuses as $status): ?><option value="<?= htmlspecialchars($status) ?>" <?= $filters['employment_status']===$status?'selected':'' ?>><?= htmlspecialchars($status) ?></option><?php endforeach; ?></select></label>
            <label><span>Department</span><select name="department_id"><option value="">All departments</option><?php foreach($departments as $department): ?><option value="<?= (int)$department['id'] ?>" <?= (int)$filters['department_id']===(int)$department['id']?'selected':'' ?>><?= htmlspecialchars($department['name']) ?></option><?php endforeach; ?></select></label>
            <label><span>Position</span><select name="position_id" data-searchable-select data-search-placeholder="Search positions..."><option value="">All positions</option><?php foreach($positions as $position): ?><option value="<?= (int)$position['id'] ?>" <?= (int)$filters['position_id']===(int)$position['id']?'selected':'' ?>><?= htmlspecialchars($position['title']) ?></option><?php endforeach; ?></select></label>
            <label><span>District</span><select name="district"><option value="">All districts</option><?php foreach($districts as $district): ?><option value="<?= htmlspecialchars($district) ?>" <?= $filters['district']===$district?'selected':'' ?>><?= htmlspecialchars($district) ?></option><?php endforeach; ?></select></label>
            <div class="employee-filter-actions"><button class="btn btn-primary" type="submit">Show results</button></div>
        </div>
    </form>
    <div class="employee-filter-result"><span>Directory results</span><strong><?= number_format($total) ?> employee<?= $total===1?'':'s' ?></strong></div>
    <div class="table-responsive">
    <table id="employee-table">
        <thead>
        <tr>
            <th>Employee</th>
            <th>Department</th>
            <th>Position</th>
            <th>Personnel Type</th>
            <th>Work Station</th>
            <th>Status</th>
            <th>PDS Completion</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($employees as $emp): ?>
            <?php
            $employeeNameParts = array_filter([
                $emp['first_name'] ?? null, $emp['middle_name'] ?? null,
                $emp['surname'] ?? null, $emp['name_extension'] ?? null,
            ], static fn($part) => $part !== null && trim((string) $part) !== '' && !in_array(strtoupper(trim((string) $part)), ['N/A', 'NA', 'NONE'], true));
            $employeeName = trim(implode(' ', $employeeNameParts)) ?: $emp['employee_number'];
            $initial = strtoupper(substr($emp['first_name'] ?: ($emp['surname'] ?: $emp['employee_number']), 0, 1));
            $workStation = $emp['current_school_station'] ?: ($emp['district'] ?: 'Not assigned');
            ?>
            <tr>
                <td><div class="employee-directory-person"><?php if (!empty($emp['photo_id'])): ?><img src="<?= BASE_URL ?>/photo/<?= UrlId::encode((int) $emp['photo_id']) ?>" alt=""><?php else: ?><span><?= htmlspecialchars($initial) ?></span><?php endif; ?><div><strong><?= htmlspecialchars($employeeName) ?></strong><small><?= htmlspecialchars($emp['employee_number']) ?></small></div></div></td>
                <td><?= htmlspecialchars($emp['department_name'] ?? 'Not assigned') ?></td>
                <td><?= htmlspecialchars($emp['position_title'] ?? 'Not assigned') ?></td>
                <td><span class="record-chip"><?= htmlspecialchars($emp['personnel_type'] ?? 'Unclassified') ?></span></td>
                <td><?= htmlspecialchars($workStation) ?></td>
                <td><span class="status-pill"><span class="dot"></span> <?= htmlspecialchars($emp['employment_status']) ?></span></td>
                <td>
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <div class="progress-bar" style="width:110px;">
                            <div class="progress-bar-fill" data-target="<?= (int) $emp['pds_percent'] ?>"></div>
                        </div>
                        <?= (int) $emp['pds_percent'] ?>%
                    </div>
                </td>
                <td><a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/employees/<?= UrlId::encode((int) $emp['id']) ?>">View profile</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$employees): ?><tr><td colspan="8"><div class="position-empty"><strong>No employees matched these filters.</strong><span>Clear a filter or use a broader search term.</span></div></td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Employee pages" style="display:flex;justify-content:center;align-items:center;gap:.75rem;flex-wrap:wrap;margin-top:1.25rem;">
            <?php if ($page > 1): ?><a class="btn btn-secondary" href="<?= BASE_URL ?>/employees?<?= htmlspecialchars(http_build_query(array_merge($filterQuery,['page'=>$page-1]))) ?>">Previous</a><?php endif; ?>
            <span style="color:var(--text-muted);">Page <?= $page ?> of <?= $totalPages ?> &middot; <?= number_format($total) ?> employees</span>
            <?php if ($page < $totalPages): ?><a class="btn btn-secondary" href="<?= BASE_URL ?>/employees?<?= htmlspecialchars(http_build_query(array_merge($filterQuery,['page'=>$page+1]))) ?>">Next</a><?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
