<?php
// Single front-controller entry point.

require_once __DIR__ . '/../config/constants.php';

$maintenanceFile = STORAGE_PATH . '/cache/maintenance.json';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (is_file($maintenanceFile) && !str_starts_with($requestPath, BASE_URL . '/admin/updater')) {
    $maintenance = json_decode((string) file_get_contents($maintenanceFile), true) ?: [];
    http_response_code(503);
    header('Retry-After: 60');
    header('Content-Type: text/html; charset=UTF-8');
    $message = htmlspecialchars($maintenance['message'] ?? 'HRMS is being updated. Please try again shortly.');
    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>HRMS Update</title></head><body style="font-family:system-ui;display:grid;place-items:center;min-height:100vh;margin:0;background:#f4f7fb;color:#1e293b"><main style="max-width:520px;padding:2rem;text-align:center"><h1>System update in progress</h1><p>' . $message . '</p><p>Please refresh in a few minutes.</p></main></body></html>';
    exit;
}

if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

spl_autoload_register(function (string $class): void {
    $paths = [
        CORE_PATH . "/{$class}.php",
        MODULES_PATH . "/auth/controllers/{$class}.php",
        MODULES_PATH . "/employees/controllers/{$class}.php",
        MODULES_PATH . "/employees/models/{$class}.php",
        MODULES_PATH . "/tasks/controllers/{$class}.php",
        MODULES_PATH . "/tasks/models/{$class}.php",
        MODULES_PATH . "/dashboard/controllers/{$class}.php",
        MODULES_PATH . "/shared/controllers/{$class}.php",
        MODULES_PATH . "/admin/controllers/{$class}.php",
        MODULES_PATH . "/accomplishments/controllers/{$class}.php",
        MODULES_PATH . "/accomplishments/models/{$class}.php",
        MODULES_PATH . "/onboarding/controllers/{$class}.php",
    ];
    foreach ($paths as $path) {
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
});

require_once CONFIG_PATH . '/database.php';

$appConfig = require CONFIG_PATH . '/app.php';
date_default_timezone_set($appConfig['timezone']);
if ($appConfig['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

Auth::start();

// Authenticated and dynamic responses must never be stored in shared browser/proxy caches.
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

$router = new Router();

// ---- Auth ----
$router->get('/login', fn() => (new AuthController())->showLogin());
$router->post('/login', fn() => (new AuthController())->login());
$router->get('/login/verify-2fa', fn() => (new AuthController())->showTwoFactor());
$router->post('/login/verify-2fa', fn() => (new AuthController())->verifyTwoFactor());
$router->post('/logout', fn() => (new AuthController())->logout());

// ---- Dashboard ----
$router->get('/', fn() => (new DashboardController())->index());
$router->get('/dashboard', fn() => (new DashboardController())->index());
$router->get('/updates', fn() => (new UpdateController())->index());
$router->post('/updates/acknowledge', fn() => (new UpdateController())->acknowledge());

// ---- Onboarding ----
$router->get('/onboarding', fn() => (new OnboardingController())->index());
$router->get('/personnel-setup', fn() => (new OnboardingController())->personnelSetup());
$router->post('/personnel-setup', fn() => (new OnboardingController())->savePersonnelSetup());
$router->get('/personal-details-setup', fn() => (new OnboardingController())->personalDetailsSetup());
$router->post('/personal-details-setup', fn() => (new OnboardingController())->savePersonalDetailsSetup());

// ---- Employees (201 file) ----
$router->get('/profile', fn() => (new ProfileController())->show());
$router->post('/profile/update', fn() => (new ProfileController())->update());
$router->post('/profile/photo', fn() => (new ProfileController())->uploadPhoto());
$router->get('/profile/security', fn() => (new ProfileController())->security());
$router->post('/profile/security/2fa/enable', fn() => (new ProfileController())->enableTwoFactor());
$router->post('/profile/security/2fa/disable', fn() => (new ProfileController())->disableTwoFactor());
$router->get('/employees', fn() => (new EmployeeController())->index());
$router->get('/employees/create', fn() => (new EmployeeController())->create());
$router->post('/employees/store', fn() => (new EmployeeController())->store());
$router->get('/employees/{id}', fn($id) => (new EmployeeController())->show($id));
$router->post('/employees/{id}/photo', fn($id) => (new EmployeeController())->uploadPhoto($id));

// ---- PDS ----
$router->get('/pds', fn() => (new PdsController())->edit());
$router->post('/pds/save-section/{section}', fn($section) => (new PdsController())->saveSection($section));
$router->get('/pds/print/{id}', fn($id) => (new PdsController())->print($id));
$router->get('/reports/pds-completion', fn() => (new PdsController())->completionReport());

// ---- Tasks ----
$router->get('/tasks', fn() => (new TaskController())->index());
$router->get('/tasks/calendar', fn() => (new TaskController())->calendar());
$router->get('/tasks/create', fn() => (new TaskController())->create());
$router->post('/tasks/store', fn() => (new TaskController())->store());
$router->get('/tasks/{id}', fn($id) => (new TaskController())->show($id));
$router->post('/tasks/update-status/{id}', fn($id) => (new TaskController())->updateStatus($id));
$router->post('/tasks/add-comment/{id}', fn($id) => (new TaskController())->addComment($id));
$router->post('/tasks/upload-attachment/{id}', fn($id) => (new TaskController())->uploadAttachment($id));

// ---- Protected file streaming ----
$router->get('/photo/{id}', fn($id) => (new FileController())->photo($id));
$router->get('/files/task-attachment/{id}', fn($id) => (new FileController())->taskAttachment($id));
$router->get('/files/accomplishment-attachment/{id}', fn($id) => (new FileController())->accomplishmentAttachment($id));
$router->get('/search', fn() => (new SearchController())->index());
$router->post('/notifications/read', fn() => (new NotificationController())->markRead());

// ---- Accomplishments & Evidence ----
$router->get('/accomplishments', fn() => (new AccomplishmentController())->index());
$router->get('/accomplishments/create', fn() => (new AccomplishmentController())->create());
$router->get('/accomplishments/gallery', fn() => (new AccomplishmentController())->gallery());
$router->post('/accomplishments/store', fn() => (new AccomplishmentController())->store());
$router->get('/accomplishments/{id}/edit', fn($id) => (new AccomplishmentController())->edit($id));
$router->get('/accomplishments/{id}', fn($id) => (new AccomplishmentController())->show($id));
$router->get('/accomplishments/{id}/print', fn($id) => (new AccomplishmentController())->printView($id));
$router->post('/accomplishments/{id}/save-draft', fn($id) => (new AccomplishmentController())->saveDraft($id));
$router->post('/accomplishments/{id}/submit', fn($id) => (new AccomplishmentController())->submit($id));
$router->post('/accomplishments/{id}/review', fn($id) => (new AccomplishmentController())->review($id));
$router->post('/accomplishments/{id}/upload-attachment', fn($id) => (new AccomplishmentController())->uploadAttachment($id));
$router->post('/accomplishments/{aid}/attachments/{atid}/delete', fn($aid, $atid) => (new AccomplishmentController())->deleteAttachment($aid, $atid));

// ---- Administration ----
$router->get('/admin/dashboard', fn() => (new AdminController())->dashboard());
$router->get('/admin/activity', fn() => (new AdminController())->activity());
$router->get('/admin/users', fn() => (new AdminController())->users());
$router->post('/admin/users/{id}/status', fn($id) => (new AdminController())->updateUserStatus($id));
$router->post('/admin/users/{id}/update', fn($id) => (new AdminController())->updateUser($id));
$router->post('/admin/users/{id}/reset-password', fn($id) => (new AdminController())->resetUserPassword($id));
$router->post('/admin/users/{id}/reset-2fa', fn($id) => (new AdminController())->resetUserTwoFactor($id));
$router->post('/admin/users/{id}/delete', fn($id) => (new AdminController())->deleteUser($id));
$router->get('/admin/departments', fn() => (new AdminController())->departments());
$router->post('/admin/departments/store', fn() => (new AdminController())->storeDepartment());
$router->post('/admin/departments/{id}/delete', fn($id) => (new AdminController())->deleteDepartment($id));
$router->get('/admin/positions', fn() => (new AdminController())->positions());
$router->post('/admin/positions/store', fn() => (new AdminController())->storePosition());
$router->post('/admin/positions/{id}/delete', fn($id) => (new AdminController())->deletePosition($id));
$router->get('/admin/releases', fn() => (new AdminController())->releases());
$router->post('/admin/releases/store', fn() => (new AdminController())->storeRelease());
$router->post('/admin/releases/{id}/publish', fn($id) => (new AdminController())->publishRelease($id));
$router->post('/admin/releases/sync', fn() => (new AdminController())->syncGitHubReleases());
$router->get('/admin/updater', fn() => (new UpdaterController())->index());
$router->get('/admin/updater/status', fn() => (new UpdaterController())->status());
$router->get('/admin/updater/progress', fn() => (new UpdaterController())->progress());
$router->post('/admin/updater/apply', fn() => (new UpdaterController())->apply());

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
