<?php
/** @var string $recordLockScope */
/** @var bool $isUnlocked */
$recordLabel = $recordLockScope === 'pds' ? 'Personal Data Sheet' : 'profile';
?>
<section class="record-lock-banner glass-card <?= $isUnlocked ? 'is-unlocked' : '' ?>" data-record-lock-banner data-scope="<?= htmlspecialchars($recordLockScope) ?>">
    <div class="record-lock-icon" aria-hidden="true"><?= $isUnlocked ? '&#128275;' : '&#128274;' ?></div>
    <div class="record-lock-copy">
        <strong><?= $isUnlocked ? 'Editing is unlocked' : 'Your ' . $recordLabel . ' is locked' ?></strong>
        <p><?= $isUnlocked ? 'You may change this information for 15 minutes. Lock it again when you finish.' : 'Existing information is protected from changes. Enter your current password to edit it.' ?></p>
    </div>
    <?php if ($isUnlocked): ?>
        <button type="button" class="btn btn-secondary" data-record-lock-action>Lock now</button>
    <?php else: ?>
        <form class="record-unlock-form" data-record-unlock-form>
            <label><span class="sr-only">Current password</span><input type="password" name="password" autocomplete="current-password" placeholder="Current password" required></label>
            <button type="submit" class="btn btn-primary">Unlock editing</button>
        </form>
    <?php endif; ?>
</section>
