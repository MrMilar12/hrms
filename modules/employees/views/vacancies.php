<?php /** @var array $vacancies */ /** @var array $departments */ /** @var array $filters */ $openVacancies = count(array_filter($vacancies, static fn(array $vacancy): bool => $vacancy['status'] === 'Vacant')); require MODULES_PATH . '/shared/views/header.php'; ?>
<main class="vacancy-page">
    <section class="glass-card vacancy-hero"><div><span class="launcher-eyebrow">Plantilla monitoring</span><h1>Vacant positions</h1><p>Assign available items while preserving every previous appointment.</p></div><div class="vacancy-hero-summary"><span><strong><?= $openVacancies ?></strong> available</span><a class="btn btn-secondary" href="<?= BASE_URL ?>/employees">Employee directory</a></div></section>
    <form class="glass-card vacancy-filter-bar" method="get" action="<?= BASE_URL ?>/vacant-positions" role="search">
        <label class="vacancy-search"><span aria-hidden="true">&#128269;</span><input type="search" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Search position, item number, previous holder, or station..." aria-label="Search vacant positions"></label>
        <select name="status" aria-label="Filter by status"><option value="">All statuses</option><?php foreach(['Vacant','Filled','Cancelled'] as $status): ?><option value="<?= $status ?>" <?= $filters['status']===$status?'selected':'' ?>><?= $status ?></option><?php endforeach; ?></select>
        <select name="department_id" aria-label="Filter by office"><option value="">All offices</option><?php foreach($departments as $department): ?><option value="<?= (int)$department['id'] ?>" <?= $filters['department_id']==(int)$department['id']?'selected':'' ?>><?= htmlspecialchars($department['name']) ?></option><?php endforeach; ?></select>
        <button class="btn btn-primary" type="submit">Apply</button><?php if($filters['q']!==''||$filters['status']!==''||$filters['department_id']>0): ?><a class="btn btn-secondary" href="<?= BASE_URL ?>/vacant-positions">Clear</a><?php endif; ?>
        <div class="vacancy-view-toggle" role="group" aria-label="Vacancy display"><button class="active" type="button" data-vacancy-view="cards">Cards</button><button type="button" data-vacancy-view="list">List</button></div>
    </form>
    <section class="glass-card vacancy-list-card">
        <div class="vacancy-list-heading"><div><span class="launcher-eyebrow">Position registry</span><h2><?= count($vacancies) ?> recorded position<?= count($vacancies) === 1 ? '' : 's' ?></h2></div><p>When the selected employee leaves an occupied item, that previous item is automatically added here as a new vacancy.</p></div>
        <div class="vacancy-card-grid" data-vacancy-panel="cards">
        <?php foreach ($vacancies as $vacancy): ?><article class="vacancy-card <?= $vacancy['status'] === 'Vacant' ? 'is-open' : 'is-filled' ?>">
            <header><span class="vacancy-status"><i></i><?= htmlspecialchars($vacancy['status']) ?></span><time><?= htmlspecialchars(date('M j, Y', strtotime($vacancy['vacated_on']))) ?></time></header>
            <div class="vacancy-position"><span class="vacancy-position-icon" aria-hidden="true">&#128188;</span><div><h3><?= htmlspecialchars($vacancy['position_title'] ?: 'Unspecified position') ?></h3><p><?= htmlspecialchars($vacancy['item_number'] ?: 'No item number') ?><?= $vacancy['salary_grade'] ? ' · ' . htmlspecialchars($vacancy['salary_grade']) : '' ?></p></div></div>
            <dl><div><dt>Previous holder</dt><dd><?= htmlspecialchars($vacancy['former_employee_name']) ?><small><?= htmlspecialchars($vacancy['former_employee_number'] ?: 'No employee number') ?></small></dd></div><div><dt>Office or station</dt><dd><?= htmlspecialchars($vacancy['department_name'] ?: ($vacancy['station'] ?: 'Not assigned')) ?></dd></div><div><dt>Reason vacated</dt><dd><?= htmlspecialchars($vacancy['reason']) ?></dd></div></dl>
            <footer><?php if ($vacancy['status'] === 'Vacant'): ?><div><strong>Ready for appointment</strong><small>Select who will hold this item.</small></div><button class="btn btn-primary fill-vacancy" type="button" data-id="<?= UrlId::encode((int)$vacancy['id']) ?>" data-position="<?= htmlspecialchars($vacancy['position_title'] ?: 'this position',ENT_QUOTES) ?>">Fill position</button><?php else: ?><div class="vacancy-current-holder"><span aria-hidden="true">&#10003;</span><div><small>Current holder</small><strong><?= htmlspecialchars($vacancy['holder_name'] ?: 'Filled') ?></strong><b><?= htmlspecialchars($vacancy['holder_employee_number'] ?: '') ?></b></div></div><?php endif; ?></footer>
        </article><?php endforeach; ?>
        <?php if (!$vacancies): ?><p class="record-empty vacancy-empty">No vacant positions have been recorded.</p><?php endif; ?>
        </div>
        <div class="vacancy-list-view" data-vacancy-panel="list" hidden><div class="table-responsive"><table data-view-toggle="off"><thead><tr><th>Status</th><th>Position and item</th><th>Previous holder</th><th>Office or station</th><th>Date vacated</th><th>Current holder / action</th></tr></thead><tbody><?php foreach($vacancies as $vacancy): ?><tr><td><span class="record-chip"><?= htmlspecialchars($vacancy['status']) ?></span></td><td><strong><?= htmlspecialchars($vacancy['position_title']?:'Unspecified position') ?></strong><small><?= htmlspecialchars($vacancy['item_number']?:'No item number') ?><?= $vacancy['salary_grade']?' · '.htmlspecialchars($vacancy['salary_grade']):'' ?></small></td><td><strong><?= htmlspecialchars($vacancy['former_employee_name']) ?></strong><small><?= htmlspecialchars($vacancy['former_employee_number']?:'No employee number') ?></small></td><td><?= htmlspecialchars($vacancy['department_name']?:($vacancy['station']?:'Not assigned')) ?></td><td><?= htmlspecialchars($vacancy['vacated_on']) ?></td><td><?php if($vacancy['status']==='Vacant'): ?><button class="btn btn-primary btn-sm fill-vacancy" type="button" data-id="<?= UrlId::encode((int)$vacancy['id']) ?>" data-position="<?= htmlspecialchars($vacancy['position_title']?:'this position',ENT_QUOTES) ?>">Fill position</button><?php else: ?><strong><?= htmlspecialchars($vacancy['holder_name']?:'Filled') ?></strong><small><?= htmlspecialchars($vacancy['holder_employee_number']?:'') ?></small><?php endif; ?></td></tr><?php endforeach; ?><?php if(!$vacancies): ?><tr><td colspan="6" class="position-empty">No matching vacancy records.</td></tr><?php endif; ?></tbody></table></div></div>
    </section>
</main>
<script>
const vacancyViewButtons=[...document.querySelectorAll('[data-vacancy-view]')];const vacancyViewPanels=[...document.querySelectorAll('[data-vacancy-panel]')];const setVacancyView=view=>{vacancyViewButtons.forEach(button=>button.classList.toggle('active',button.dataset.vacancyView===view));vacancyViewPanels.forEach(panel=>panel.hidden=panel.dataset.vacancyPanel!==view);localStorage.setItem('hrms-vacancy-view',view);};vacancyViewButtons.forEach(button=>button.addEventListener('click',()=>setVacancyView(button.dataset.vacancyView)));setVacancyView(localStorage.getItem('hrms-vacancy-view')==='list'?'list':'cards');
document.querySelectorAll('.fill-vacancy').forEach(button => button.addEventListener('click', async () => {
    const safe = value => String(value ?? '').replace(/[&<>"']/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[character]));
    let searchTimer;
    const dialog = `
        <div class="vacancy-assign-dialog">
            <div class="vacancy-target-card">
                <span>Position to fill</span>
                <strong>${safe(button.dataset.position)}</strong>
                <small>The selected employee will become the current holder.</small>
            </div>
            <section class="vacancy-dialog-section">
                <div class="vacancy-dialog-section-head"><b>1</b><div><strong>Select employee</strong><small>Search by name or employee number.</small></div></div>
                <label class="swal-field"><span>Employee search</span><input id="vacancy-employee-search" type="search" autocomplete="off" placeholder="Start typing at least 2 characters"></label>
                <label class="swal-field"><span>Matching employees</span><select id="vacancy-employee" disabled><option value="">Search results will appear here</option></select></label>
            </section>
            <section class="vacancy-dialog-section">
                <div class="vacancy-dialog-section-head"><b>2</b><div><strong>Confirm appointment</strong><small>Set the effective date and authorize this change.</small></div></div>
                <div class="vacancy-confirm-grid">
                    <label class="swal-field"><span>Effective date</span><input id="vacancy-date" type="date" value="<?= date('Y-m-d') ?>"></label>
                    <label class="swal-field"><span>Current password</span><input id="vacancy-password" type="password" autocomplete="current-password" placeholder="Enter your password"></label>
                </div>
            </section>
            <div class="vacancy-chain-note"><span aria-hidden="true">i</span><div><strong>Position history will be preserved</strong><small>If the employee already holds another item, that item will be recorded as vacant.</small></div></div>
        </div>`;
    const result = await Swal.fire({
        title:'Assign position', html:dialog, showCancelButton:true, confirmButtonText:'Confirm assignment', cancelButtonText:'Cancel', focusConfirm:false,
        customClass:{popup:'vacancy-assign-popup',title:'vacancy-assign-title',htmlContainer:'vacancy-assign-content',actions:'vacancy-assign-actions',confirmButton:'vacancy-confirm-button',cancelButton:'vacancy-cancel-button'},
        didOpen:()=>{
            const search=document.getElementById('vacancy-employee-search');
            const select=document.getElementById('vacancy-employee');
            search.focus();
            search.addEventListener('input',()=>{
                clearTimeout(searchTimer);const query=search.value.trim();
                if(query.length<2){select.disabled=true;select.innerHTML='<option value="">Search results will appear here</option>';return;}
                select.disabled=true;select.innerHTML='<option value="">Searching employees...</option>';
                searchTimer=setTimeout(async()=>{try{
                    const response=await fetch(`${window.BASE_URL}/vacant-positions/employee-search?q=${encodeURIComponent(query)}`);
                    const payload=await response.json();const employees=payload.employees||[];
                    select.innerHTML=employees.length?'<option value="">Choose an employee</option>'+employees.map(employee=>`<option value="${Number(employee.id)}">${safe(employee.employee_name)} · ${safe(employee.employee_number)} · ${safe(employee.position_title)}</option>`).join(''):'<option value="">No matching employees found</option>';
                    select.disabled=!employees.length;
                }catch(error){select.innerHTML='<option value="">Search unavailable. Try again.</option>';select.disabled=true;}},300);
            });
        },
        preConfirm:()=>{const employee_id=document.getElementById('vacancy-employee').value;const effective_date=document.getElementById('vacancy-date').value;const confirmation_password=document.getElementById('vacancy-password').value;if(!employee_id||!effective_date||!confirmation_password){Swal.showValidationMessage('Complete the employee, effective date, and password fields.');return false;}return{employee_id,effective_date,confirmation_password};}
    });
    if (!result.isConfirmed) return;
    const data=new FormData();data.append('csrf_token',HRIS.getCsrfToken());data.append('employee_id',result.value.employee_id);data.append('effective_date',result.value.effective_date);data.append('confirmation_password',result.value.confirmation_password);
    try { const response=await fetch(`${window.BASE_URL}/vacant-positions/${button.dataset.id}/fill`,{method:'POST',body:data});const payload=await response.json();if(!response.ok||!payload.success)throw new Error(payload.error||'Unable to fill position.');await Swal.fire({icon:'success',title:'Position assigned',text:payload.message});location.reload(); } catch(error){Swal.fire({icon:'error',title:'Assignment failed',text:error.message});}
}));
</script>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
