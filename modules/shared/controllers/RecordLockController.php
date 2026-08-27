<?php

class RecordLockController extends Controller
{
    public function unlock(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $scope = (string) ($_POST['scope'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if (!in_array($scope, ['profile', 'pds'], true) || $password === '' || !Auth::userId()) {
            $this->json(['success' => false, 'error' => 'Enter your current account password.'], 422);
        }

        // Verify exactly the account attached to the active session. Keeping the
        // status comparison in PHP avoids SQL-mode differences around quoted strings.
        $stmt = Database::getInstance()->prepare('SELECT password_hash, status FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([Auth::userId()]);
        $account = $stmt->fetch();
        if (!$account || $account['status'] !== 'active' || !password_verify($password, $account['password_hash'])) {
            AuditLogger::log('record_unlock_failed', $scope, Auth::employeeId(), null, ['scope' => $scope]);
            $this->json(['success' => false, 'error' => 'The password is incorrect. Your data remains locked.'], 422);
        }

        $_SESSION['record_unlocks'][$scope] = time() + (3 * 60 * 60);
        AuditLogger::log('record_unlocked', $scope, Auth::employeeId(), null, ['scope' => $scope]);
        $this->json(['success' => true, 'message' => 'Editing unlocked for 3 hours.']);
    }

    public function lock(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $scope = (string) ($_POST['scope'] ?? '');
        Auth::lockRecord($scope);
        $this->json(['success' => true, 'message' => 'Your data is locked.']);
    }
}
