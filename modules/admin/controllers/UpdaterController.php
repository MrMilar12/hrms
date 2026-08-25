<?php

class UpdaterController extends Controller
{
    public function index(): void
    {
        Auth::requireDeveloper();
        $this->redirect('/admin/releases');
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
        $developerId = (int) Auth::userId();
        // Release PHP's session file lock so progress polling from this same
        // logged-in browser is not blocked until the deployment finishes.
        if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
        try {
            $result = SystemUpdater::apply($developerId);
            AuditLogger::log('system_update', 'system_deployments', null, null, $result);
            $this->json(['success' => true] + $result);
        } catch (Throwable $e) {
            error_log('System update failed: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function progress(): void
    {
        Auth::requireDeveloper();
        $this->json(['success' => true, 'progress' => PortableUpdater::progress()]);
    }
}
