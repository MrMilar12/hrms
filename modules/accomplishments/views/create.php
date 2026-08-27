<?php
/** @var array $tasks */
/** @var array $employees */
/** @var bool $canCreateForOthers */
/** @var array|null $accomplishment */
/** @var array $attachments */
require MODULES_PATH . '/shared/views/header.php';
$isEditing = !empty($accomplishment);
?>
<div class="accomplishment-form-page">
<section class="accomplishment-form-hero glass-card">
    <a class="accomplishment-back" href="<?= BASE_URL ?>/accomplishments"><span aria-hidden="true">&larr;</span> Accomplishments</a>
    <div class="accomplishment-hero-main"><span class="accomplishment-hero-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3l2.6 5.3 5.9.8-4.3 4.2 1 5.9-5.2-2.8-5.2 2.8 1-5.9-4.3-4.2 5.9-.8L12 3Z"/></svg></span><div><span class="launcher-eyebrow"><?= $isEditing ? 'Revision workspace' : 'New submission' ?></span><h1><?= $isEditing ? 'Improve your accomplishment' : 'Record an accomplishment' ?></h1><p><?= $isEditing ? 'Update the requested details and supporting evidence before resubmitting.' : 'Capture the completed work, its outcome, and supporting photographs.' ?></p></div></div>
    <div class="accomplishment-hero-note"><strong>3 simple steps</strong><span>Details · Narrative · Evidence</span></div>
</section>

<section class="glass-card accomplishment-editor">
    <form id="accomplishment-form">
        <section class="accomplishment-form-section accomplishment-details-section">
        <header class="accomplishment-section-head"><span>1</span><div><h2>Accomplishment details</h2><p>Identify the activity and when it was completed.</p></div></header>
        <div class="accomplishment-section-body accomplishment-details-grid">
        <?php if ($canCreateForOthers): ?>
        <div class="form-group accomplishment-detail-control detail-control-wide">
            <label for="field-employee">Employee <b class="field-required" aria-label="required">*</b></label>
            <select name="employee_id" id="field-employee" required data-searchable-select data-search-placeholder="Search employee name..." data-search-empty="No employee found">
                <option value="">Select employee...</option>
                <?php foreach ($employees as $e): ?><option value="<?= (int) $e['id'] ?>"><?= htmlspecialchars($e['employee_name']) ?></option><?php endforeach; ?>
            </select>
            <small class="field-help">Select the employee who completed this accomplishment.</small>
        </div>
        <?php endif; ?>

        <div class="form-group accomplishment-detail-control detail-control-wide detail-title-control">
            <label for="field-title">Accomplishment title <b class="field-required" aria-label="required">*</b></label>
            <div class="accomplishment-title-input"><input name="title" id="field-title" value="<?= htmlspecialchars($accomplishment['title'] ?? '') ?>" placeholder="Example: Conducted a division training workshop" required maxlength="255"></div>
            <small class="field-help">Use a short, specific title. Add the full explanation in the Narrative section.</small>
        </div>

            <div class="form-group accomplishment-detail-control accomplishment-date-field">
                <label for="field-date">Date completed <b class="field-required" aria-label="required">*</b></label>
                <div class="accomplishment-date-wrap"><input type="date" name="accomplishment_date" id="field-date" value="<?= htmlspecialchars($accomplishment['accomplishment_date'] ?? date('Y-m-d')) ?>" required></div>
                <small class="field-help">Choose when the activity was completed.</small>
            </div>
            <div class="form-group accomplishment-detail-control accomplishment-task-field">
                <label for="field-task">Related task <em class="field-optional-inline">Optional</em></label>
                <select name="task_id" id="field-task">
                    <option value="">Select task...</option>
                    <?php foreach ($tasks as $t): ?><option value="<?= (int) $t['id'] ?>"<?= $canCreateForOthers ? ' data-employee-id="' . (int) $t['employee_id'] . '" hidden disabled' : '' ?>><?= htmlspecialchars($t['title']) ?><?= $canCreateForOthers ? ' - ' . htmlspecialchars($t['employee_number']) : '' ?></option><?php endforeach; ?>
                </select>
                <small class="field-help">Connect this accomplishment to an assigned task.</small>
            </div>
        </div>
        </section>

        <section class="accomplishment-form-section">
        <header class="accomplishment-section-head"><span>2</span><div><h2>Result and narrative</h2><p>Describe the work performed and the outcome achieved.</p></div></header>
        <div class="accomplishment-section-body">
        <div class="form-group">
            <label for="field-description">Description <span class="field-optional">Optional</span></label>
            <div class="accomplishment-description-wrap">
                <textarea name="description" id="field-description" rows="8" maxlength="2000"
                    placeholder="Describe what you accomplished, the activities performed, and the result or output."
                    ><?= htmlspecialchars($accomplishment['description'] ?? '') ?></textarea>
                <span id="char-counter" class="accomplishment-char-counter"><?= mb_strlen($accomplishment['description'] ?? '') ?> / 2000</span>
            </div>
        </div>
        </div>
        </section>

        <section class="accomplishment-form-section accomplishment-evidence-section">
        <header class="accomplishment-section-head"><span>3</span><div><h2>Photo evidence</h2><p>Add clear supporting photos. Each image is automatically optimized to 500 KB or less.</p></div></header>
        <div class="accomplishment-section-body">
        <div class="form-group">
            <div id="dropzone" class="accomplishment-dropzone glass-light">
                <span class="accomplishment-dropzone-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 16V4m0 0L7 9m5-5 5 5"/><path d="M5 14v5h14v-5"/></svg></span>
                <strong>Add supporting photos</strong>
                <p>Drag and drop images here or <span>browse files</span></p>
                <small>JPG, PNG, or WEBP · Up to 30 MB before automatic optimization</small>
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
        </div>
        </section>

        <div class="accomplishment-progress" id="submission-progress" hidden aria-live="polite">
            <div class="accomplishment-progress-head"><span><strong id="progress-title">Preparing submission</strong><small id="progress-detail">Saving accomplishment details...</small></span><b id="progress-percent">0%</b></div>
            <div class="accomplishment-progress-track" role="progressbar" aria-label="Submission progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span id="progress-fill"></span></div>
        </div>
        <div id="form-status" class="accomplishment-form-status"></div>

        <div class="accomplishment-form-actions">
            <span><strong><?= $isEditing ? 'Ready to update?' : 'Not ready yet?' ?></strong><small><?= $isEditing ? 'Save your changes or resubmit for approval.' : 'Save a draft and return at any time.' ?></small></span>
            <button type="button" id="btn-save-draft" class="btn btn-secondary"><?= $isEditing ? 'Save Changes' : 'Save Draft' ?></button>
            <button type="button" id="btn-submit" class="btn btn-primary"><?= $isEditing ? 'Resubmit for Review' : 'Submit' ?></button>
        </div>
    </form>
</section>
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
const progressBox = document.getElementById('submission-progress');
const progressTitle = document.getElementById('progress-title');
const progressDetail = document.getElementById('progress-detail');
const progressPercent = document.getElementById('progress-percent');
const progressFill = document.getElementById('progress-fill');
<?php if ($isEditing && !empty($accomplishment['task_id'])): ?>
document.getElementById('field-task').value = <?= json_encode((string) $accomplishment['task_id']) ?>;
<?php endif; ?>

dropzone.addEventListener('click', () => fileInput.click());
['dragover', 'dragleave', 'drop'].forEach((evt) => {
    dropzone.addEventListener(evt, (e) => {
        e.preventDefault();
        dropzone.classList.toggle('is-dragging', evt === 'dragover');
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

const formatBytes = bytes => bytes < 1024 ? `${bytes} B` : bytes < 1024 * 1024 ? `${(bytes / 1024).toFixed(0)} KB` : `${(bytes / 1024 / 1024).toFixed(2)} MB`;
const escapeHtml = value => String(value || '').replace(/[&<>'"]/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[character]);
const canvasBlob = (canvas, quality) => new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', quality));
const loadBrowserImage = file => new Promise((resolve, reject) => {
    const image = new Image();
    const url = URL.createObjectURL(file);
    image.onload = () => { URL.revokeObjectURL(url); resolve(image); };
    image.onerror = () => { URL.revokeObjectURL(url); reject(new Error('Could not read image')); };
    image.src = url;
});
async function compressPhoto(file) {
    const image = await loadBrowserImage(file);
    const initialScale = Math.min(1, 2200 / Math.max(image.naturalWidth, image.naturalHeight));
    let latest = file;
    for (let attempt = 0; attempt < 18; attempt++) {
        const scale = initialScale * Math.pow(.86, Math.floor(attempt / 3));
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
        canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
        const context = canvas.getContext('2d', { alpha: false });
        context.fillStyle = '#fff'; context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        const blob = await canvasBlob(canvas, [.84, .7, .56][attempt % 3]);
        if (!blob) throw new Error('Image compression failed');
        latest = new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg', lastModified: Date.now() });
        if (latest.size <= 500 * 1024) break;
    }
    return latest;
}

async function addFiles(fileList) {
    const files = [...fileList];
    fileInput.value = '';
    for (const file of files) {
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            HRIS.flash(`${file.name} is not a supported image.`, 'error');
            continue;
        }
        if (file.size > 30 * 1024 * 1024) {
            HRIS.flash(`${file.name} exceeds the 30 MB source-image limit.`, 'error');
            continue;
        }
        formStatus.textContent = `Optimizing ${file.name} (${formatBytes(file.size)})...`;
        try {
            const optimized = await compressPhoto(file);
            stagedPhotos.push({ file: optimized, originalName: file.name, originalSize: file.size, compressedSize: optimized.size, caption: '', previewUrl: URL.createObjectURL(optimized), uploaded: false });
            renderPhotoGrid();
        } catch (_) {
            HRIS.flash(`${file.name} could not be optimized. Please try another image.`, 'error');
        }
    }
    formStatus.textContent = files.length ? `${stagedPhotos.filter(photo => !photo.uploaded).length} optimized photo(s) ready to upload.` : '';
}

function renderPhotoGrid() {
    photoGrid.innerHTML = stagedPhotos.map((p, idx) => `
        <div class="attachment-item">
            <img class="thumb" src="${p.previewUrl}" alt="preview">
            <div class="photo-size-row"><span>${escapeHtml(p.originalName)}</span><b>${formatBytes(p.compressedSize)}</b></div>
            <div class="photo-compression-note">Original ${formatBytes(p.originalSize)} &rarr; optimized ${formatBytes(p.compressedSize)}</div>
            <input type="text" placeholder="Caption" value="${escapeHtml(p.caption)}"
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
    const pending = stagedPhotos.filter(photo => !photo.uploaded);
    let completed = 0;
    for (const photo of pending) {
        if (photo.uploaded) continue;
        const fd = new FormData();
        fd.append('file', photo.file);
        fd.append('caption', photo.caption);
        progressTitle.textContent = `Uploading photo ${completed + 1} of ${pending.length}`;
        progressDetail.textContent = `${photo.originalName} • ${formatBytes(photo.file.size)}`;
        const result = await postFormWithProgress(`${window.BASE_URL}/accomplishments/${accomplishmentId}/upload-attachment`, fd, fraction => {
            setProgress(15 + Math.round(((completed + fraction) / Math.max(1, pending.length)) * 70));
        });
        if (result.success) {
            photo.uploaded = true;
            photo.compressedSize = result.file_size || photo.compressedSize;
            completed++;
            renderPhotoGrid();
        } else {
            HRIS.flash(result.error || `Failed to upload ${photo.file.name}.`, 'error');
            return false;
        }
    }
    return true;
}

function setProgress(value, title = '', detail = '') {
    const percent = Math.max(0, Math.min(100, Math.round(value)));
    progressBox.hidden = false;
    progressFill.style.width = `${percent}%`;
    progressPercent.textContent = `${percent}%`;
    progressBox.querySelector('[role="progressbar"]').setAttribute('aria-valuenow', String(percent));
    if (title) progressTitle.textContent = title;
    if (detail) progressDetail.textContent = detail;
}

function postFormWithProgress(url, formData, onProgress) {
    return new Promise(resolve => {
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url);
        xhr.upload.addEventListener('progress', event => { if (event.lengthComputable) onProgress(event.loaded / event.total); });
        xhr.addEventListener('load', () => { try { resolve(JSON.parse(xhr.responseText)); } catch (_) { resolve({ success: false, error: 'The server response could not be read.' }); } });
        xhr.addEventListener('error', () => resolve({ success: false, error: 'Upload interrupted. Check your connection and try again.' }));
        xhr.send(formData);
    });
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
    setProgress(5, 'Saving draft', 'Saving accomplishment details...');
    if (await ensureAccomplishmentSaved() && await uploadStagedPhotos()) {
        setProgress(100, 'Draft saved', 'All details and photos were saved successfully.');
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
    setProgress(5, 'Preparing submission', 'Saving accomplishment details...');
    formStatus.textContent = 'Submitting…';
    if (await ensureAccomplishmentSaved()) {
        if (!await uploadStagedPhotos()) {
            formStatus.textContent = 'Photo upload failed. Your draft is saved.';
            setWorking(false);
            return;
        }
        const result = await HRIS.postJson(`${window.BASE_URL}/accomplishments/${accomplishmentId}/submit`, {});
        if (result.success) {
            setProgress(100, 'Submission complete', 'Opening your submitted accomplishment...');
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
