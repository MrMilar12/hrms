/**
 * HRMS — app.js
 * Global JavaScript for the HRMS application.
 */

'use strict';

// ===================== Bootstrap tab persistence =====================
document.addEventListener('DOMContentLoaded', function () {
  // Restore last active PDS tab from sessionStorage
  const savedTab = sessionStorage.getItem('pdsActiveTab');
  if (savedTab) {
    const tabEl = document.querySelector('[data-bs-target="' + savedTab + '"]');
    if (tabEl) {
      const tab = new bootstrap.Tab(tabEl);
      tab.show();
    }
  }

  // Save active tab on change
  document.querySelectorAll('#pdsTabs [data-bs-toggle="tab"]').forEach(function (btn) {
    btn.addEventListener('shown.bs.tab', function (e) {
      sessionStorage.setItem('pdsActiveTab', e.target.getAttribute('data-bs-target'));
    });
  });

  // Auto-dismiss alerts after 5 seconds
  document.querySelectorAll('.alert-glass').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity 0.5s';
      el.style.opacity = '0';
      setTimeout(function () { el.remove(); }, 500);
    }, 5000);
  });

  // Confirm delete links
  document.querySelectorAll('a[data-confirm]').forEach(function (link) {
    link.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirm || 'Are you sure?')) {
        e.preventDefault();
      }
    });
  });

  // Password strength indicator on register
  const pwdInput = document.querySelector('input[name="password"]');
  if (pwdInput && document.querySelector('.auth-card')) {
    pwdInput.addEventListener('input', function () {
      let strength = 0;
      if (this.value.length >= 6)  strength++;
      if (this.value.length >= 10) strength++;
      if (/[A-Z]/.test(this.value)) strength++;
      if (/[0-9]/.test(this.value)) strength++;
      if (/[^A-Za-z0-9]/.test(this.value)) strength++;

      let bar = document.getElementById('pwd-strength-bar');
      if (!bar) {
        bar = document.createElement('div');
        bar.id = 'pwd-strength-bar';
        bar.style.cssText = 'height:3px;border-radius:2px;margin-top:6px;transition:all .3s;width:0;';
        this.parentNode.appendChild(bar);
      }
      const pct = (strength / 5 * 100) + '%';
      const colors = ['#ef4444','#f97316','#eab308','#22c55e','#10b981'];
      bar.style.width = pct;
      bar.style.background = colors[strength - 1] || '#ef4444';
    });
  }
});

// ===================== Password visibility toggle =====================
function togglePwd(fieldId, btn) {
  const f    = document.getElementById(fieldId);
  const icon = btn ? btn.querySelector('i') : null;
  if (!f) return;
  if (f.type === 'password') {
    f.type = 'text';
    if (icon) icon.className = 'fas fa-eye-slash';
  } else {
    f.type = 'password';
    if (icon) icon.className = 'fas fa-eye';
  }
}

// ===================== PDS — Permanent address toggle =====================
function togglePermanentAddr(cb) {
  const el = document.getElementById('permanentAddrFields');
  if (el) el.style.display = cb.checked ? 'none' : '';
}

// ===================== PDS — Question details toggle =====================
function toggleDetails(key, show) {
  const el = document.getElementById('details-' + key);
  if (el) el.style.display = show ? '' : 'none';
}

// ===================== Generic row removal =====================
function removeRow(id) {
  const el = document.getElementById(id);
  if (el) {
    el.style.transition = 'opacity 0.2s, transform 0.2s';
    el.style.opacity = '0';
    el.style.transform = 'translateY(-4px)';
    setTimeout(function () { el.remove(); }, 220);
  }
}

// ===================== Work experience — present toggle =====================
function togglePresent(cb, endId) {
  const endField = document.getElementById(endId);
  if (endField) {
    endField.disabled = cb.checked;
    if (cb.checked) endField.value = '';
  }
}

// ===================== Print shortcut =====================
document.addEventListener('keydown', function (e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
    // Let the browser handle native print
    return;
  }
});

// ===================== Table row highlight =====================
document.querySelectorAll('.table-glass tbody tr').forEach(function (row) {
  row.style.cursor = 'default';
});
