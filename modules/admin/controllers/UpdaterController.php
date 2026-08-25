<?php

class UpdaterController extends Controller
{
    public function index(): void
    {
        Auth::requireDeveloper();
        try { $status = SystemUpdater::status(); $statusError = null; }
        catch (Throwable $e) { $status = null; $statusError = $e->getMessage(); }
        $this->view('admin', 'updater', [
            'pageTitle' => 'System Updater', 'updateStatus' => $status,
            'statusError' => $statusError, 'deployments' => SystemUpdater::history(),
        ]);
    }

    public function status(): void
    {
        Auth::requireDeveloper();
        try { $this->json(['success' => true, 'status' => SystemUpdater::status()]); }
        catch (Throwable $e) { $this->json(['success' => false, 'error' => $e->getMessage()], 502); }
    }

    public function apply(): void
    {
        Auth::requireDeveloper();
        $this->requireCsrf();
        set_time_limit(600);
        ignore_user_abort(true);
        try {
            $result = SystemUpdater::apply((int) Auth::userId());
            AuditLogger::log('system_update', 'system_deployments', null, null, $result);
            $this->json(['success' => true] + $result);
        } catch (Throwable $e) {
            error_log('System update failed: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
