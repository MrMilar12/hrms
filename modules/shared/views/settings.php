<?php require MODULES_PATH . '/shared/views/header.php'; ?>
<div class="settings-page" data-appearance-settings>
    <section class="settings-hero glass-card">
        <div class="settings-hero-icon" aria-hidden="true">&#10024;</div>
        <div>
            <span class="launcher-eyebrow">Personalization</span>
            <h1>Make HRMS feel like yours</h1>
            <p>Choose a display mode and color style. Your preferences are saved on this device.</p>
        </div>
        <span class="settings-saved" data-settings-status aria-live="polite">Changes save automatically</span>
    </section>

    <section class="glass-card settings-section settings-display-section">
        <div class="settings-section-heading">
            <div><h2>Display mode</h2><p>Use your device setting automatically, or keep HRMS in light or dark mode.</p></div>
        </div>
        <div class="theme-choice-grid" role="radiogroup" aria-label="Display mode">
            <button type="button" class="theme-choice" data-theme-choice="system" role="radio">
                <span class="theme-preview theme-preview-system"><i></i><i></i></span><strong>System</strong><small>Match this device</small><b aria-hidden="true">&#10003;</b>
            </button>
            <button type="button" class="theme-choice" data-theme-choice="light" role="radio">
                <span class="theme-preview theme-preview-light"><i></i></span><strong>Light</strong><small>Bright and clear</small><b aria-hidden="true">&#10003;</b>
            </button>
            <button type="button" class="theme-choice" data-theme-choice="dark" role="radio">
                <span class="theme-preview theme-preview-dark"><i></i></span><strong>Dark</strong><small>Easy on the eyes</small><b aria-hidden="true">&#10003;</b>
            </button>
        </div>
    </section>

    <section class="glass-card settings-section settings-color-section">
        <div class="settings-section-heading">
            <div><h2>Color style</h2><p>Pick an accent palette for buttons, highlights, icons, and ambient color.</p></div>
        </div>
        <div class="palette-grid" role="radiogroup" aria-label="Accent color">
            <?php foreach ([
                'ocean' => ['Ocean', '#3b6fe0', '#8b5cf6'],
                'violet' => ['Violet', '#7c3aed', '#ec4899'],
                'emerald' => ['Emerald', '#059669', '#22c55e'],
                'sunset' => ['Sunset', '#ea580c', '#e11d48'],
                'rose' => ['Rose', '#e11d48', '#a855f7'],
                'teal' => ['Teal', '#0891b2', '#14b8a6'],
            ] as $key => [$name, $primary, $secondary]): ?>
                <button type="button" class="palette-choice" data-palette-choice="<?= $key ?>" data-primary="<?= $primary ?>" data-secondary="<?= $secondary ?>" role="radio">
                    <span style="--swatch-a:<?= $primary ?>;--swatch-b:<?= $secondary ?>"></span><strong><?= $name ?></strong><b aria-hidden="true">&#10003;</b>
                </button>
            <?php endforeach; ?>
        </div>

        <button type="button" class="advanced-toggle" data-advanced-toggle aria-expanded="false">
            <span><strong>Advanced colors</strong><small>Create a custom two-color theme</small></span><b aria-hidden="true">&#8964;</b>
        </button>
        <div class="advanced-color-panel" data-advanced-panel hidden>
            <label><span>Primary color</span><input type="color" value="#3b6fe0" data-custom-primary></label>
            <label><span>Secondary color</span><input type="color" value="#8b5cf6" data-custom-secondary></label>
            <div class="custom-color-preview"><span></span><strong>Live preview</strong><small>Your custom gradient is applied instantly.</small></div>
            <button type="button" class="btn btn-primary" data-apply-custom>Use custom colors</button>
        </div>
    </section>

    <div class="settings-actions"><button type="button" class="btn btn-secondary" data-reset-appearance>Reset to defaults</button></div>
</div>
<?php require MODULES_PATH . '/shared/views/footer.php'; ?>
