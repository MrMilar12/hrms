<?php
/** @var array $tasks */
/** @var array $employees */
/** @var bool $canCreateForOthers */
require MODULES_PATH . '/shared/views/header.php';
?>
<div class="glass-card">
    <a href="<?= BASE_URL ?>/accomplishments" style="font-size:0.85rem; color:var(--text-muted);">&larr; Back</a>
    <h2 style="margin:0.5rem 0 0.15rem;">Create Accomplishment</h2>
    <p style="margin:0; color:var(--text-muted); font-size:0.85rem;">Document a completed activity.</p>
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
            <input name="title" id="field-title" placeholder="Enter accomplishment title..." required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Date of Accomplishment</label>
                <input type="date" name="accomplishment_date" id="field-date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label>Related Task (optional)</label>
                <select name="task_id" id="field-task">
                    <option value="">Select task...</option>
                    <?php foreach ($tasks as $t): ?><option value="<?= (int) $t['id'] ?>"><?= htmlspecialchars($t['title']) ?><?= $canCreateForOthers ? ' — ' . htmlspecialchars($t['employee_number']) : '' ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Description</label>
            <div style="position:relative;">
                <textarea name="description" id="field-description" rows="8" maxlength="2000"
                    placeholder="Describe what you accomplished, the activities performed, and the result or output."
                    style="min-height:220px; resize:vertical;"></textarea>
                <span id="char-counter" style="position:absolute; right:0.75rem; bottom:0.6rem; font-size:0.75rem; color:var(--text-muted);">0 / 2000</span>
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

            <div id="photo-grid" class="attachment-grid" style="margin-top:1rem;"></div>
        </div>

        <div id="form-status" style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.75rem;"></div>

        <div style="display:flex; gap:0.6rem; justify-content:flex-end;">
            <button type="button" id="btn-save-draft" class="btn btn-secondary">Save Draft</button>
            <button type="button" id="btn-submit" class="btn btn-primary">Submit</button>
        </div>
    </form>
</div>

<script>
const stagedPhotos = []; // { file, caption, previewUrl }
let accomplishmentId = null;
let autosaveTimer = null;

const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('file-input');
const photoGrid = document.getElementById('photo-grid');
const descriptionField = document.getElementById('field-description');
const charCounter = document.getElementById('char-counter');
const formStatus = document.getElementById('form-status');

dropzone.addEventListener('click', () => fileInput.click());
['dragover', 'dragleave', 'drop'].forEach((evt) => {
    dropzone.addEventListener(evt, (e) => {
        e.preventDefault();
        dropzone.style.borderColor = evt === 'dragover' ? 'var(--accent-blue)' : 'var(--glass-border-hover)';
    });
});
dropzone.addEventListener('drop', (e) => addFiles(e.dataTransfer.files));
fileInput.addEventListener('change', () => addFiles(fileInput.files));

function addFiles(fileList) {
    [...fileList].forEach((file) => {
        stagedPhotos.push({ file, caption: '', previewUrl: URL.createObjectURL(file) });
    });
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
    stagedPhotos.splice(idx, 1);
    renderPhotoGrid();
}

descriptionField.addEventListener('input', () => {
    charCounter.textContent = `${descriptionField.value.length} / 2000`;
    descriptionField.style.height = 'auto';
    descriptionField.style.height = Math.min(600, Math.max(220, descriptionField.scrollHeight)) + 'px';
});

function formPayload() {
    const fd = new FormData();
    const employeeField = document.getElementById('field-employee');
    if (employeeField) fd.append('employee_id', employeeField.value);
    fd.append('title', document.getElementById('field-title').value);
    fd.append('accomplishment_date', document.getElementById('field-date').value);
    fd.append('task_id', document.getElementById('field-task').value);
    fd.append('description', descriptionField.value);
    return fd;
}

async function ensureAccomplishmentSaved() {
    const fd = formPayload();
    if (accomplishmentId) {
        const result = await HRIS.postForm(`${window.BASE_URL}/accomplishments/${accomplishmentId}/save-draft`, fd);
        return result.success;
    }
    const result = await HRIS.postForm(`${window.BASE_URL}/accomplishments/store`, fd);
    if (result.success) {
        accomplishmentId = result.accomplishment_id;
        return true;
    }
    HRIS.flash(result.error || 'Failed to save.', 'error');
    return false;
}

async function uploadStagedPhotos() {
    for (const photo of stagedPhotos) {
        if (photo.uploaded) continue;
        const fd = new FormData();
        fd.append('file', photo.file);
        fd.append('caption', photo.caption);
        const result = await HRIS.postForm(`${window.BASE_URL}/accomplishments/${accomplishmentId}/upload-attachment`, fd);
        if (result.success) photo.uploaded = true;
    }
}

document.getElementById('btn-save-draft').addEventListener('click', async () => {
    formStatus.textContent = 'Saving…';
    if (!document.getElementById('field-title').value.trim()) {
        HRIS.flash('Title is required.', 'error');
        formStatus.textContent = '';
        return;
    }
    if (await ensureAccomplishmentSaved()) {
        await uploadStagedPhotos();
        formStatus.textContent = 'Draft saved.';
        HRIS.flash('Draft saved.', 'success');
    } else {
        formStatus.textContent = '';
    }
});

document.getElementById('btn-submit').addEventListener('click', async () => {
    if (!document.getElementById('field-title').value.trim()) {
        HRIS.flash('Title is required.', 'error');
        return;
    }
    formStatus.textContent = 'Submitting…';
    if (await ensureAccomplishmentSaved()) {
        await uploadStagedPhotos();
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
});

// Lightweight autosave of the draft while typing (title/description/date/task).
['field-title', 'field-date', 'field-task', 'field-description', 'field-employee'].forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', () => {
        clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(async () => {
            if (!document.getElementById('field-title').value.trim()) return;
            if (await ensureAccomplishmentSaved()) {
                formStatus.textContent = 'Draft autosaved.';
            }
        }, 1500);
    });
});
</script>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
