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

    function updatePdsCompletion(value) {
        const percent = Math.max(0, Math.min(100, Number.parseInt(value, 10) || 0));
        const bar = document.getElementById('pds-completion-bar');
        const text = document.getElementById('pds-completion-text');
        if (bar) {
            bar.dataset.target = String(percent);
            bar.style.width = `${percent}%`;
            bar.closest('[role="progressbar"]')?.setAttribute('aria-valuenow', String(percent));
        }
        if (text) text.textContent = `${percent}% Complete`;
    }

    return { postJson, postForm, flash, getCsrfToken, updatePdsCompletion };
})();

document.addEventListener('DOMContentLoaded', () => {
    // ---- Password-protected Profile and PDS editing ----
    document.querySelectorAll('[data-record-protected]').forEach(region => {
        if (region.dataset.recordUnlocked === 'true') return;
        region.classList.add('record-is-locked');
        region.querySelectorAll('input, select, textarea, button').forEach(control => { control.disabled = true; });
    });
    document.querySelectorAll('[data-record-unlock-form]').forEach(form => form.addEventListener('submit', async event => {
        event.preventDefault();
        const banner = form.closest('[data-record-lock-banner]');
        const button = form.querySelector('[type="submit"]');
        button.disabled = true;
        const formData = new FormData(form);
        formData.append('scope', banner.dataset.scope);
        try {
            const result = await HRIS.postForm(`${window.BASE_URL}/records/unlock`, formData);
            HRIS.flash(result.message || result.error, result.success ? 'success' : 'error');
            if (result.success) window.setTimeout(() => window.location.reload(), 350);
        } catch (_) {
            HRIS.flash('Unable to verify your password.', 'error');
        } finally { button.disabled = false; }
    }));
    document.querySelectorAll('[data-record-lock-action]').forEach(button => button.addEventListener('click', async () => {
        const banner = button.closest('[data-record-lock-banner]');
        button.disabled = true;
        try {
            const formData = new FormData();
            formData.append('scope', banner.dataset.scope);
            const result = await HRIS.postForm(`${window.BASE_URL}/records/lock`, formData);
            HRIS.flash(result.message || result.error, result.success ? 'success' : 'error');
            if (result.success) window.setTimeout(() => window.location.reload(), 300);
        } catch (_) {
            HRIS.flash('Unable to lock the record.', 'error');
            button.disabled = false;
        }
    }));

    // ---- Appearance settings ----
    const appearanceRoot = document.querySelector('[data-appearance-settings]');
    if (appearanceRoot) {
        const storageKey = 'hrms-appearance';
        const defaults = { mode: 'system', palette: 'ocean', primary: '#3b6fe0', secondary: '#8b5cf6' };
        const palettes = {};
        appearanceRoot.querySelectorAll('[data-palette-choice]').forEach(button => {
            palettes[button.dataset.paletteChoice] = {
                primary: button.dataset.primary,
                secondary: button.dataset.secondary,
            };
        });
        const validColor = value => /^#[0-9a-f]{6}$/i.test(String(value || ''));
        const readSettings = () => {
            try {
                const saved = { ...defaults, ...JSON.parse(localStorage.getItem(storageKey) || '{}') };
                return {
                    mode: ['system', 'light', 'dark'].includes(saved.mode) ? saved.mode : defaults.mode,
                    palette: (palettes[saved.palette] || saved.palette === 'custom') ? saved.palette : defaults.palette,
                    primary: validColor(saved.primary) ? saved.primary : defaults.primary,
                    secondary: validColor(saved.secondary) ? saved.secondary : defaults.secondary,
                };
            }
            catch (_) { return { ...defaults }; }
        };
        let settings = readSettings();
        const status = appearanceRoot.querySelector('[data-settings-status]');
        const primaryInput = appearanceRoot.querySelector('[data-custom-primary]');
        const secondaryInput = appearanceRoot.querySelector('[data-custom-secondary]');
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');

        const applySettings = (announce = false) => {
            const dark = settings.mode === 'dark' || (settings.mode === 'system' && systemTheme.matches);
            document.documentElement.dataset.theme = dark ? 'dark' : 'light';
            document.documentElement.style.setProperty('--accent-blue', settings.primary);
            document.documentElement.style.setProperty('--accent-violet', settings.secondary);
            document.documentElement.style.setProperty('--accent-primary', settings.primary);
            appearanceRoot.querySelectorAll('[data-theme-choice]').forEach(button => {
                const selected = button.dataset.themeChoice === settings.mode;
                button.classList.toggle('selected', selected);
                button.setAttribute('aria-checked', String(selected));
            });
            appearanceRoot.querySelectorAll('[data-palette-choice]').forEach(button => {
                const selected = button.dataset.paletteChoice === settings.palette;
                button.classList.toggle('selected', selected);
                button.setAttribute('aria-checked', String(selected));
            });
            primaryInput.value = settings.primary;
            secondaryInput.value = settings.secondary;
            appearanceRoot.dataset.activeMode = settings.mode;
            appearanceRoot.dataset.activePalette = settings.palette;
            if (announce && status) {
                status.textContent = 'Saved';
                status.classList.add('is-saved');
                window.setTimeout(() => { status.textContent = 'Changes save automatically'; status.classList.remove('is-saved'); }, 1600);
            }
        };
        const save = () => {
            try {
                localStorage.setItem(storageKey, JSON.stringify(settings));
                applySettings(true);
            } catch (_) {
                applySettings(false);
                if (status) { status.textContent = 'Could not save on this browser'; status.classList.add('is-error'); }
            }
        };

        appearanceRoot.querySelectorAll('[data-theme-choice]').forEach(button => button.addEventListener('click', () => {
            settings.mode = button.dataset.themeChoice;
            save();
        }));
        appearanceRoot.querySelectorAll('[data-palette-choice]').forEach(button => button.addEventListener('click', () => {
            settings = { ...settings, palette: button.dataset.paletteChoice, ...palettes[button.dataset.paletteChoice] };
            save();
        }));
        const advancedToggle = appearanceRoot.querySelector('[data-advanced-toggle]');
        const advancedPanel = appearanceRoot.querySelector('[data-advanced-panel]');
        advancedToggle.addEventListener('click', () => {
            const open = advancedToggle.getAttribute('aria-expanded') !== 'true';
            advancedToggle.setAttribute('aria-expanded', String(open));
            advancedPanel.hidden = !open;
        });
        appearanceRoot.querySelector('[data-apply-custom]').addEventListener('click', () => {
            settings = { ...settings, palette: 'custom', primary: primaryInput.value, secondary: secondaryInput.value };
            save();
        });
        [primaryInput, secondaryInput].forEach(input => input.addEventListener('input', () => {
            const property = input === primaryInput ? '--accent-blue' : '--accent-violet';
            document.documentElement.style.setProperty(property, input.value);
            if (input === primaryInput) document.documentElement.style.setProperty('--accent-primary', input.value);
        }));
        appearanceRoot.querySelector('[data-reset-appearance]').addEventListener('click', () => {
            settings = { ...defaults };
            advancedToggle.setAttribute('aria-expanded', 'false');
            advancedPanel.hidden = true;
            save();
        });
        systemTheme.addEventListener?.('change', () => { if (settings.mode === 'system') applySettings(); });
        applySettings();
    }

    // ---- Universal table list/card view ----
    document.querySelectorAll('table:not([data-view-toggle="off"])').forEach((table, tableIndex) => {
        if (!table.tHead || !table.tBodies.length || table.dataset.viewReady === '1') return;
        table.dataset.viewReady = '1';
        const headings = [...table.tHead.querySelectorAll('th')].map(th => th.textContent.trim() || 'Details');
        const labelRows = () => {
            table.querySelectorAll('tbody tr').forEach(row => {
                [...row.children].forEach((cell, index) => {
                    if (cell.tagName === 'TD') cell.dataset.label = headings[index] || 'Details';
                });
            });
        };
        labelRows();

        const key = `hrms-table-view:${window.location.pathname}:${tableIndex}`;
        const toolbar = document.createElement('div');
        toolbar.className = 'table-view-toolbar';
        toolbar.innerHTML = `
            <span>View</span>
            <div class="table-view-switch" role="group" aria-label="Choose table view">
                <button type="button" data-view="list" aria-label="List view"><span aria-hidden="true">&#9776;</span> List</button>
                <button type="button" data-view="card" aria-label="Card view"><span aria-hidden="true">&#9638;</span> Cards</button>
            </div>`;
        table.parentNode.insertBefore(toolbar, table);

        const setView = (view) => {
            const cardView = view === 'card';
            table.classList.toggle('table-card-view', cardView);
            toolbar.querySelectorAll('[data-view]').forEach(button => {
                const active = button.dataset.view === view;
                button.classList.toggle('active', active);
                button.setAttribute('aria-pressed', String(active));
            });
            try { localStorage.setItem(key, view); } catch (_) {}
            labelRows();
        };
        toolbar.querySelectorAll('[data-view]').forEach(button => button.addEventListener('click', () => setView(button.dataset.view)));
        let preferredView = 'list';
        try { preferredView = localStorage.getItem(key) || 'list'; } catch (_) {}
        setView(preferredView === 'card' ? 'card' : 'list');

        new MutationObserver(labelRows).observe(table.tBodies[0], {childList: true, subtree: true});
    });

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

    // A notification becomes read only when the user opens it, then leaves unread areas.
    document.querySelectorAll('.notification-link[data-notification-id]').forEach((link) => {
        link.addEventListener('click', async (event) => {
            event.preventDefault();
            const destination = link.href;
            const formData = new FormData();
            formData.append('notification_id', link.dataset.notificationId);
            try {
                const result = await HRIS.postForm(`${window.BASE_URL}/notifications/read`, formData);
                if (result.success) {
                    document.querySelectorAll(`.notification-link[data-notification-id="${link.dataset.notificationId}"]`).forEach(item => item.remove());
                    const remaining = Math.max(0, Number(notifToggle?.dataset.unread || 0) - 1);
                    if (notifToggle) {
                        notifToggle.dataset.unread = String(remaining);
                        const unreadLabel = document.getElementById('notification-unread-label');
                        if (unreadLabel) unreadLabel.textContent = `${remaining} unread`;
                        const badge = notifToggle.querySelector('.dot-badge b');
                        if (badge) badge.textContent = remaining > 9 ? '9+' : String(remaining);
                        if (!remaining) {
                            notifToggle.classList.remove('pulse');
                            notifToggle.querySelector('.dot-badge')?.remove();
                        }
                    }
                    const notificationList = document.getElementById('notif-list');
                    if (notificationList && !notificationList.querySelector('.notification-link')) {
                        notificationList.innerHTML = '<div class="notification-empty">You\'re all caught up.<small>No unread notifications.</small></div>';
                    }
                }
            } finally {
                if (destination && destination !== '#') window.location.assign(destination);
            }
        });
    });

    // ---- Permission-aware global header search ----
    const globalSearch = document.getElementById('global-search');
    const searchResults = document.getElementById('global-search-results');
    const searchSpinner = document.getElementById('search-spinner');
    const searchClear = document.getElementById('search-clear');
    if (globalSearch && searchResults) {
        let searchTimer;
        let searchRequest;
        let closeTimer;
        let activeResult = -1;
        const closeSearch = () => {
            clearTimeout(closeTimer);
            searchResults.classList.remove('is-open');
            globalSearch.setAttribute('aria-expanded', 'false');
            activeResult = -1;
            closeTimer = setTimeout(() => { searchResults.hidden = true; }, 180);
        };
        const openSearch = () => {
            clearTimeout(closeTimer);
            searchResults.hidden = false;
            requestAnimationFrame(() => searchResults.classList.add('is-open'));
            globalSearch.setAttribute('aria-expanded', 'true');
        };
        const syncSearchControls = () => {
            if (searchClear) searchClear.hidden = globalSearch.value.length === 0;
        };
        const selectResult = (index) => {
            const items = [...searchResults.querySelectorAll('a')];
            if (!items.length) return;
            activeResult = (index + items.length) % items.length;
            items.forEach((item, itemIndex) => item.classList.toggle('active', itemIndex === activeResult));
            items[activeResult].scrollIntoView({block: 'nearest'});
        };
        const renderSearch = (results, query) => {
            searchResults.replaceChildren();
            if (!results.length) {
                const empty = document.createElement('div');
                empty.className = 'global-search-empty';
                empty.innerHTML = '<span aria-hidden="true">⌕</span><strong>No matching results</strong><small></small>';
                empty.querySelector('small').textContent = `Try another keyword for “${query}”`;
                searchResults.appendChild(empty);
            } else {
                results.forEach((result, index) => {
                    const link = document.createElement('a');
                    link.href = result.url;
                    link.className = 'global-search-item';
                    link.setAttribute('role', 'option');
                    link.style.setProperty('--search-item-index', index);
                    const type = document.createElement('span');
                    type.className = `global-search-type search-type-${result.type.toLowerCase()}`;
                    type.textContent = result.type.charAt(0);
                    const copy = document.createElement('span');
                    const title = document.createElement('strong');
                    title.textContent = result.title;
                    const subtitle = document.createElement('small');
                    subtitle.textContent = `${result.type} · ${result.subtitle}`;
                    copy.append(title, subtitle);
                    link.append(type, copy);
                    searchResults.appendChild(link);
                });
            }
            openSearch();
            activeResult = -1;
        };
        globalSearch.addEventListener('input', () => {
            syncSearchControls();
            clearTimeout(searchTimer);
            searchRequest?.abort();
            const query = globalSearch.value.trim();
            if (query.length < 2) { closeSearch(); return; }
            searchTimer = setTimeout(async () => {
                const currentRequest = new AbortController();
                searchRequest = currentRequest;
                searchSpinner.hidden = false;
                try {
                    const response = await fetch(`${window.BASE_URL}/search?q=${encodeURIComponent(query)}`, {signal: currentRequest.signal});
                    if (!response.ok) throw new Error(`Search failed with HTTP ${response.status}`);
                    const result = await response.json();
                    if (globalSearch.value.trim() === query) renderSearch(result.results || [], query);
                } catch (error) {
                    if (error.name !== 'AbortError') HRIS.flash('Search is temporarily unavailable.', 'error');
                } finally {
                    if (searchRequest === currentRequest) {
                        searchSpinner.hidden = true;
                        searchRequest = null;
                    }
                }
            }, 220);
        });
        globalSearch.addEventListener('focus', () => {
            if (globalSearch.value.trim().length >= 2 && searchResults.hasChildNodes()) openSearch();
        });
        searchClear?.addEventListener('click', () => {
            globalSearch.value = '';
            searchRequest?.abort();
            clearTimeout(searchTimer);
            syncSearchControls();
            closeSearch();
            globalSearch.focus();
        });
        document.addEventListener('keydown', event => {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                globalSearch.focus();
                globalSearch.select();
            }
        });
        globalSearch.addEventListener('keydown', event => {
            const items = searchResults.querySelectorAll('a');
            if (event.key === 'ArrowDown' && items.length) { event.preventDefault(); selectResult(activeResult + 1); }
            else if (event.key === 'ArrowUp' && items.length) { event.preventDefault(); selectResult(activeResult - 1); }
            else if (event.key === 'Enter' && activeResult >= 0) { event.preventDefault(); items[activeResult].click(); }
            else if (event.key === 'Escape') closeSearch();
        });
        document.addEventListener('click', event => {
            if (!event.target.closest('.header-search-wrap')) closeSearch();
        });
    }

    // ---- Searchable select / combobox ----
    document.querySelectorAll('select[data-searchable-select]').forEach((select, selectIndex) => {
        if (select.dataset.searchReady === 'true') return;
        select.dataset.searchReady = 'true';

        const options = [...select.options].map(option => ({
            value: option.value,
            label: option.textContent.trim(),
            disabled: option.disabled,
        }));
        const wrapper = document.createElement('div');
        wrapper.className = 'searchable-select';
        const input = document.createElement('input');
        const list = document.createElement('div');
        const listId = `searchable-select-${selectIndex}-${Math.random().toString(36).slice(2, 7)}`;
        input.type = 'search';
        input.className = 'searchable-select-input';
        input.placeholder = select.dataset.searchPlaceholder || 'Type to search...';
        input.autocomplete = 'off';
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-controls', listId);
        list.className = 'searchable-select-list';
        list.id = listId;
        list.setAttribute('role', 'listbox');
        list.hidden = true;

        const selectedOption = select.options[select.selectedIndex];
        input.value = selectedOption?.value ? selectedOption.textContent.trim() : '';
        select.parentNode.insertBefore(wrapper, select);
        wrapper.append(input, list, select);
        select.classList.add('searchable-select-native');

        let visibleOptions = [];
        let activeIndex = -1;
        const close = () => {
            list.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            activeIndex = -1;
        };
        const choose = item => {
            select.value = item.value;
            input.value = item.label;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            close();
        };
        const setActive = index => {
            const items = [...list.querySelectorAll('[role="option"]')];
            if (!items.length) return;
            activeIndex = (index + items.length) % items.length;
            items.forEach((item, i) => item.classList.toggle('active', i === activeIndex));
            items[activeIndex].scrollIntoView({ block: 'nearest' });
            input.setAttribute('aria-activedescendant', items[activeIndex].id);
        };
        const render = () => {
            const query = input.value.trim().toLowerCase();
            visibleOptions = options.filter(option => option.value && !option.disabled && (!query || option.label.toLowerCase().includes(query))).slice(0, 60);
            list.replaceChildren();
            if (!visibleOptions.length) {
                const empty = document.createElement('div');
                empty.className = 'searchable-select-empty';
                empty.textContent = 'No matching position found';
                list.appendChild(empty);
            } else {
                visibleOptions.forEach((option, index) => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.id = `${listId}-option-${index}`;
                    item.className = 'searchable-select-option';
                    item.setAttribute('role', 'option');
                    item.setAttribute('aria-selected', String(select.value === option.value));
                    item.textContent = option.label;
                    item.addEventListener('mousedown', event => event.preventDefault());
                    item.addEventListener('click', () => choose(option));
                    list.appendChild(item);
                });
            }
            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            activeIndex = -1;
        };

        input.addEventListener('focus', render);
        input.addEventListener('input', () => {
            if (select.options[select.selectedIndex]?.textContent.trim() !== input.value.trim()) select.value = '';
            render();
        });
        input.addEventListener('keydown', event => {
            if (event.key === 'ArrowDown') { event.preventDefault(); if (list.hidden) render(); setActive(activeIndex + 1); }
            else if (event.key === 'ArrowUp') { event.preventDefault(); if (list.hidden) render(); setActive(activeIndex - 1); }
            else if (event.key === 'Enter' && activeIndex >= 0) { event.preventDefault(); choose(visibleOptions[activeIndex]); }
            else if (event.key === 'Escape') close();
        });
        input.addEventListener('blur', () => {
            window.setTimeout(() => {
                close();
                const current = select.options[select.selectedIndex];
                input.value = current?.value ? current.textContent.trim() : '';
            }, 100);
        });
    });

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
                HRIS.updatePdsCompletion(result.completionPercent);
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
                employee_id: select.dataset.employeeId || null,
            });
            HRIS.flash(result.message || (result.success ? 'Status updated.' : 'Update failed.'), result.success ? 'success' : 'error');
            if (result.success) window.setTimeout(() => window.location.reload(), 450);
        });
    });
});
