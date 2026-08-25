<?php
/** @var array $tasks */
/** @var array $employees */
/** @var bool $canCreateForOthers */
/** @var array|null $accomplishment */
/** @var array $attachments */
require MODULES_PATH . '/shared/views/header.php';
$isEditing = !empty($accomplishment);
?>
<div class="glass-card">
    <a href="<?= BASE_URL ?>/accomplishments" style="font-size:0.85rem; color:var(--text-muted);">&larr; Back</a>
    <h2 style="margin:0.5rem 0 0.15rem;"><?= $isEditing ? 'Edit Returned Accomplishment' : 'Create Accomplishment' ?></h2>
    <p style="margin:0; color:var(--text-muted); font-size:0.85rem;"><?= $isEditing ? 'Update the details and evidence requested by the approving authority.' : 'Document a completed activity.' ?></p>
</div>

<div class="glass-card">
    <form id="accomplishment-form">
        <?php if ($canCreateForOthers): ?>
        <div class="form-group">
            <label>Employee</label>
            <select name="employee_id" id="field-employee" required>
                <option value="">Select employee...</option>
                <?php foreach ($employees as $e): ?><option value="<?= (int) $e['id'] ?>"><?= htmlspecialchars($e['employee_name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label>Accomplishment Title</label>
            <input name="title" id="field-title" value="<?= htmlspecialchars($accomplishment['title'] ?? '') ?>" placeholder="Enter accomplishment title..." required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Date of Accomplishment</label>
                <input type="date" name="accomplishment_date" id="field-date" value="<?= htmlspecialchars($accomplishment['accomplishment_date'] ?? date('Y-m-d')) ?>" required>
            </div>
            <div class="form-group">
                <label>Related Task (optional)</label>
                <select name="task_id" id="field-task">
                    <option value="">Select task...</option>
                    <?php foreach ($tasks as $t): ?><option value="<?= (int) $t['id'] ?>"<?= $canCreateForOthers ? ' data-employee-id="' . (int) $t['employee_id'] . '" hidden disabled' : '' ?>><?= htmlspecialchars($t['title']) ?><?= $canCreateForOthers ? ' - ' . htmlspecialchars($t['employee_number']) : '' ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Description</label>
            <div style="position:relative;">
                <textarea name="description" id="field-description" rows="8" maxlength="2000"
                    placeholder="Describe what you accomplished, the activities performed, and the result or output."
                    style="min-height:220px; resize:vertical;"><?= htmlspecialchars($accomplishment['description'] ?? '') ?></textarea>
                <span id="char-counter" style="position:absolute; right:0.75rem; bottom:0.6rem; font-size:0.75rem; color:var(--text-muted);"><?= mb_strlen($accomplishment['description'] ?? '') ?> / 2000</span>
            </div>
        </div>

        <div class="form-group">
            <label>Photos</label>
            <div id="dropzone" class="glass-light" style="border:2px dashed var(--glass-border-hover); border-radius:var(--radius-medium); padding:2rem; text-align:center; cursor:pointer; transition:border-color var(--transition), background var(--transition);">
                <div style="font-size:1.8rem; margin-bottom:0.4rem;">&#10133;</div>
                <div style="font-weight:600; margin-bottom:0.2rem;">Add accomplishment photos</div>
                <div style="font-size:0.85rem; color:var(--text-muted);">Drag &amp; drop images here, or <span style="color:var(--accent-blue); font-weight:600;">Browse Files</span></div>
                <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.5rem;">JPG &middot; JPEG &middot; PNG &middot; WEBP &middot; Maximum 5 MB</div>
                <input type="file" id="file-input" accept=".jpg,.jpeg,.png,.webp" multiple hidden>
            </div>

            <div id="existing-photo-grid" class="attachment-grid" style="margin-top:1rem;">
                <?php foreach ($attachments as $attachment): ?>
                <?php $attachmentToken = UrlId::encode((int) $attachment['id']); ?>
                <div class="attachment-item" id="existing-photo-<?= htmlspecialchars($attachmentToken) ?>">
                    <img class="thumb" src="<?= BASE_URL ?>/files/accomplishment-attachment/<?= htmlspecialchars($attachmentToken) ?>" alt="Accomplishment evidence">
                    <?php if (!empty($attachment['caption'])): ?><div><?= htmlspecialchars($attachment['caption']) ?></div><?php endif; ?>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeExistingPhoto(<?= htmlspecialchars(json_encode($attachmentToken), ENT_QUOTES) ?>)">Remove</button>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="photo-grid" class="attachment-grid" style="margin-top:1rem;"></div>
        </div>

        <div id="form-status" style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.75rem;"></div>

        <div style="display:flex; gap:0.6rem; justify-content:flex-end;">
            <button type="button" id="btn-save-draft" class="btn btn-secondary"><?= $isEditing ? 'Save Changes' : 'Save Draft' ?></button>
            <button type="button" id="btn-submit" class="btn btn-primary"><?= $isEditing ? 'Resubmit for Review' : 'Submit' ?></button>
        </div>
    </form>
</div>

<script>
const stagedPhotos = []; // { file, caption, previewUrl, uploaded }
let accomplishmentId = <?= $isEditing ? json_encode(UrlId::encode((int) $accomplishment['id'])) : 'null' ?>;
let autosaveTimer = null;
let savePromise = null;

const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('file-input');
const photoGrid = document.getElementById('photo-grid');
const descriptionField = document.getElementById('field-description');
const charCounter = document.getElementById('char-counter');
const formStatus = document.getElementById('form-status');
const employeeField = document.getElementById('field-employee');
const taskField = document.getElementById('field-task');
const saveButton = document.getElementById('btn-save-draft');
const submitButton = document.getElementById('btn-submit');
<?php if ($isEditing && !empty($accomplishment['task_id'])): ?>
document.getElementById('field-task').value = <?= json_encode((string) $accomplishment['task_id']) ?>;
<?php endif; ?>

dropzone.addEventListener('click', () => fileInput.click());
['dragover', 'dragleave', 'drop'].forEach((evt) => {
    dropzone.addEventListener(evt, (e) => {
        e.preventDefault();
        dropzone.style.borderColor = evt === 'dragover' ? 'var(--accent-blue)' : 'var(--glass-border-hover)';
    });
});
dropzone.addEventListener('drop', (e) => addFiles(e.dataTransfer.files));
fileInput.addEventListener('change', () => addFiles(fileInput.files));

function filterTasksForEmployee() {
    if (!employeeField) return;
    const employeeId = employeeField.value;
    taskField.value = '';
    [...taskField.options].slice(1).forEach((option) => {
        const show = employeeId !== '' && option.dataset.employeeId === employeeId;
        option.hidden = !show;
        option.disabled = !show;
    });
    taskField.disabled = employeeId === '';
}
employeeField?.addEventListener('change', filterTasksForEmployee);
filterTasksForEmployee();

function addFiles(fileList) {
    [...fileList].forEach((file) => {
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            HRIS.flash(`${file.name} is not a supported image.`, 'error');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            HRIS.flash(`${file.name} exceeds the 5 MB limit.`, 'error');
            return;
        }
        stagedPhotos.push({ file, caption: '', previewUrl: URL.createObjectURL(file) });
    });
    fileInput.value = '';
    renderPhotoGrid();
}

function renderPhotoGrid() {
    photoGrid.innerHTML = stagedPhotos.map((p, idx) => `
        <div class="attachment-item">
            <img class="thumb" src="${p.previewUrl}" alt="preview">
            <input type="text" placeholder="Caption" value="${p.caption.replace(/"/g, '&quot;')}"
                   oninput="stagedPhotos[${idx}].caption = this.value" style="margin-top:0.4rem; font-size:0.78rem; padding:0.35rem 0.5rem;">
            <button type="button" class="btn btn-sm btn-danger" style="margin-top:0.3rem;" onclick="removePhoto(${idx})">Remove</button>
        </div>
    `).join('');
}

function removePhoto(idx) {
    URL.revokeObjectURL(stagedPhotos[idx].previewUrl);
    stagedPhotos.splice(idx, 1);
    renderPhotoGrid();
}

async function removeExistingPhoto(attachmentId) {
    if (!confirm('Remove this photo?')) return;
    const result = await HRIS.postJson(`${window.BASE_URL}/accomplishments/${accomplishmentId}/attachments/${attachmentId}/delete`, {});
    if (result.success) {
        document.getElementById(`existing-photo-${attachmentId}`)?.remove();
        HRIS.flash('Photo removed.', 'success');
    } else {
        HRIS.flash(result.error || 'Could not remove photo.', 'error');
    }
}

descriptionField.addEventListener('input', () => {
    charCounter.textContent = `${descriptionField.value.length} / 2000`;
    descriptionField.style.height = 'auto';
    descriptionField.style.height = Math.min(600, Math.max(220, descriptionField.scrollHeight)) + 'px';
});

function formPayload() {
    const fd = new FormData();
    if (employeeField) fd.append('employee_id', employeeField.value);
    fd.append('title', document.getElementById('field-title').value);
    fd.append('accomplishment_date', document.getElementById('field-date').value);
    fd.append('task_id', document.getElementById('field-task').value);
    fd.append('description', descriptionField.value);
    return fd;
}

async function ensureAccomplishmentSaved() {
    if (savePromise) return savePromise;
    savePromise = (async () => {
        const fd = formPayload();
        const endpoint = accomplishmentId
            ? `${window.BASE_URL}/accomplishments/${accomplishmentId}/save-draft`
            : `${window.BASE_URL}/accomplishments/store`;
        const result = await HRIS.postForm(endpoint, fd);
        if (result.success) {
            if (!accomplishmentId) accomplishmentId = result.accomplishment_token;
            if (employeeField) employeeField.disabled = true;
            return true;
        }
        const validationError = result.error || Object.values(result.errors || {})[0] || 'Failed to save.';
        HRIS.flash(validationError, 'error');
        return false;
    })();
    try {
        return await savePromise;
    } finally {
        savePromise = null;
    }
}

async function uploadStagedPhotos() {
    for (const photo of stagedPhotos) {
        if (photo.uploaded) continue;
        const fd = new FormData();
        fd.append('file', photo.file);
        fd.append('caption', photo.caption);
        const result = await HRIS.postForm(`${window.BASE_URL}/accomplishments/${accomplishmentId}/upload-attachment`, fd);
        if (result.success) {
            photo.uploaded = true;
        } else {
            HRIS.flash(result.error || `Failed to upload ${photo.file.name}.`, 'error');
            return false;
        }
    }
    return true;
}

function validateRequiredFields() {
    if (employeeField && !employeeField.value) return 'Please select an employee.';
    if (!document.getElementById('field-title').value.trim()) return 'Title is required.';
    if (!document.getElementById('field-date').value) return 'Accomplishment date is required.';
    return '';
}

function setWorking(isWorking) {
    saveButton.disabled = isWorking;
    submitButton.disabled = isWorking;
}

document.getElementById('btn-save-draft').addEventListener('click', async () => {
    formStatus.textContent = 'Saving…';
    const error = validateRequiredFields();
    if (error) {
        HRIS.flash(error, 'error');
        formStatus.textContent = '';
        return;
    }
    setWorking(true);
    if (await ensureAccomplishmentSaved() && await uploadStagedPhotos()) {
        formStatus.textContent = 'Draft saved.';
        HRIS.flash('Draft saved.', 'success');
    } else {
        formStatus.textContent = '';
    }
    setWorking(false);
});

document.getElementById('btn-submit').addEventListener('click', async () => {
    const error = validateRequiredFields();
    if (error) {
        HRIS.flash(error, 'error');
        return;
    }
    setWorking(true);
    formStatus.textContent = 'Submitting…';
    if (await ensureAccomplishmentSaved()) {
        if (!await uploadStagedPhotos()) {
            formStatus.textContent = 'Photo upload failed. Your draft is saved.';
            setWorking(false);
            return;
        }
        const result = await HRIS.postJson(`${window.BASE_URL}/accomplishments/${accomplishmentId}/submit`, {});
        if (result.success) {
            window.location.href = `${window.BASE_URL}/accomplishments/${accomplishmentId}`;
        } else {
            HRIS.flash(result.error || 'Failed to submit.', 'error');
            formStatus.textContent = '';
        }
    } else {
        formStatus.textContent = '';
    }
    setWorking(false);
});

// Lightweight autosave of the draft while typing (title/description/date/task).
['field-title', 'field-date', 'field-task', 'field-description', 'field-employee'].forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', () => {
        clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(async () => {
            if (validateRequiredFields()) return;
            if (await ensureAccomplishmentSaved()) {
                formStatus.textContent = 'Draft autosaved.';
            }
        }, 1500);
    });
});
</script>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
