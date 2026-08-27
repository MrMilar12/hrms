<?php
/** @var string $recordLockScope */
/** @var bool $isUnlocked */
$recordLabel = $recordLockScope === 'pds' ? 'Personal Data Sheet' : 'profile';
?>
<section class="record-lock-banner glass-card <?= $isUnlocked ? 'is-unlocked' : '' ?>" data-record-lock-banner data-scope="<?= htmlspecialchars($recordLockScope) ?>" data-record-label="<?= htmlspecialchars($recordLabel) ?>">
    <div class="record-lock-icon" aria-hidden="true">
        <?php if ($isUnlocked): ?><svg viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="3"/><path d="M8 10V7a4 4 0 0 1 7.5-2"/></svg>
        <?php else: ?><svg viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><?php endif; ?>
    </div>
    <div class="record-lock-copy">
        <strong><?= $isUnlocked ? 'Editing is unlocked' : 'Your ' . $recordLabel . ' is locked' ?></strong>
        <p><?= $isUnlocked ? 'You may change this information for 3 hours. Lock it again when you finish.' : 'Existing information is protected from changes. Enter your current password to edit it.' ?></p>
    </div>
    <?php if ($isUnlocked): ?>
        <button type="button" class="btn btn-secondary record-lock-button" data-record-lock-action>Lock now</button>
    <?php else: ?>
        <button type="button" class="btn btn-primary record-lock-button" data-record-unlock-action><span>Unlock editing</span><span aria-hidden="true">&rarr;</span></button>
    <?php endif; ?>
</section>
