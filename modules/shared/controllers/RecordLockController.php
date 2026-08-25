<?php

class RecordLockController extends Controller
{
    public function unlock(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $scope = (string) ($_POST['scope'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if (!Auth::unlockRecord($scope, $password)) {
            $this->json(['success' => false, 'error' => 'The password is incorrect. Your data remains locked.'], 422);
        }
        $this->json(['success' => true, 'message' => 'Editing unlocked for 15 minutes.']);
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
