// HRMS — shared front-end helpers (vanilla JS, no framework).

const HRIS = (() => {
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    async function postJson(url, data) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCsrfToken(),
            },
            body: JSON.stringify(data),
        });
        return res.json();
    }

    async function postForm(url, formData) {
        formData.append('csrf_token', getCsrfToken());
        const res = await fetch(url, { method: 'POST', body: formData });
        return res.json();
    }

    function flash(message, type = 'success') {
        const el = document.getElementById('flash-message');
        if (!el) return;
        el.textContent = message;
        el.className = `alert alert-${type}`;
        el.style.display = 'block';
        setTimeout(() => { el.style.display = 'none'; }, 4000);
    }

    return { postJson, postForm, flash, getCsrfToken };
})();

document.addEventListener('DOMContentLoaded', () => {
    // ---- Application drawer ----
    const drawer = document.getElementById('app-drawer');
    const drawerBackdrop = document.getElementById('app-drawer-backdrop');
    const menuToggle = document.getElementById('menu-toggle');
    const drawerClose = document.getElementById('drawer-close');
    const setDrawerOpen = (isOpen) => {
        if (!drawer) return;
        drawer.classList.toggle('open', isOpen);
        drawerBackdrop?.classList.toggle('open', isOpen);
        drawer.setAttribute('aria-hidden', String(!isOpen));
        menuToggle?.setAttribute('aria-expanded', String(isOpen));
    };
    if (menuToggle && drawer) {
        menuToggle.addEventListener('click', () => setDrawerOpen(!drawer.classList.contains('open')));
        drawerClose?.addEventListener('click', () => setDrawerOpen(false));
        drawerBackdrop?.addEventListener('click', () => setDrawerOpen(false));
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && drawer.classList.contains('open')) setDrawerOpen(false);
        });
        if (window.OPEN_APP_DRAWER) setDrawerOpen(true);
    }

    // ---- Return to the previous app page, or the dashboard for direct visits ----
    const backButton = document.getElementById('app-back-button');
    backButton?.addEventListener('click', () => {
        const cameFromThisApp = document.referrer.startsWith(window.location.origin + window.BASE_URL);
        if (cameFromThisApp && window.history.length > 1) {
            window.history.back();
        } else {
            window.location.assign(`${window.BASE_URL}/dashboard`);
        }
    });

    // ---- Notification dropdown ----
    const notifToggle = document.getElementById('notif-toggle');
    const notifDropdown = document.getElementById('notif-dropdown');
    if (notifToggle && notifDropdown) {
        notifToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('open');
        });
        document.addEventListener('click', (e) => {
            if (!notifDropdown.contains(e.target) && e.target !== notifToggle) {
                notifDropdown.classList.remove('open');
            }
        });
    }

    // ---- Animate progress bars in on load ----
    document.querySelectorAll('.progress-bar-fill[data-target]').forEach((bar) => {
        const target = bar.dataset.target;
        requestAnimationFrame(() => { bar.style.width = `${target}%`; });
    });

    // ---- PDS tabbed form navigation ----
    const tabButtons = document.querySelectorAll('.tabs [data-tab]');
    const panels = document.querySelectorAll('.tab-panel');

    tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            tabButtons.forEach((b) => b.classList.remove('active'));
            panels.forEach((p) => (p.style.display = 'none'));
            btn.classList.add('active');
            const target = document.getElementById(btn.dataset.tab);
            if (target) target.style.display = 'block';
        });
    });

    // Generic AJAX section-save forms: <form class="ajax-section-form" data-endpoint="...">
    document.querySelectorAll('form.ajax-section-form').forEach((form) => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const result = await HRIS.postForm(form.dataset.endpoint, formData);
            if (result.success) {
                HRIS.flash(result.message || 'Saved.', 'success');
            } else {
                HRIS.flash(result.error || 'Save failed.', 'error');
            }
        });
    });

    // Task attachment client-side preview before upload.
    const fileInput = document.getElementById('attachment-file');
    const previewBox = document.getElementById('attachment-preview');
    if (fileInput && previewBox) {
        fileInput.addEventListener('change', () => {
            previewBox.innerHTML = '';
            const file = fileInput.files[0];
            if (!file) return;
            const img = document.createElement('img');
            img.className = 'thumb';
            img.src = URL.createObjectURL(file);
            previewBox.appendChild(img);
        });
    }

    // Task status update via AJAX select.
    document.querySelectorAll('select.task-status-select').forEach((select) => {
        select.addEventListener('change', async () => {
            const taskId = select.dataset.taskId;
            const result = await HRIS.postJson(`${window.BASE_URL}/tasks/update-status/${taskId}`, {
                status: select.value,
            });
            HRIS.flash(result.message || (result.success ? 'Status updated.' : 'Update failed.'), result.success ? 'success' : 'error');
        });
    });
});
