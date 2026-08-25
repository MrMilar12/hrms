<?php
// Single front-controller entry point.

require_once __DIR__ . '/../config/constants.php';

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

$router = new Router();

// ---- Auth ----
$router->get('/login', fn() => (new AuthController())->showLogin());
$router->post('/login', fn() => (new AuthController())->login());
$router->get('/login/verify-2fa', fn() => (new AuthController())->showTwoFactor());
$router->post('/login/verify-2fa', fn() => (new AuthController())->verifyTwoFactor());
$router->get('/logout', fn() => (new AuthController())->logout());

// ---- Dashboard ----
$router->get('/', fn() => (new DashboardController())->index());
$router->get('/dashboard', fn() => (new DashboardController())->index());

// ---- Onboarding ----
$router->get('/onboarding', fn() => (new OnboardingController())->index());

// ---- Employees (201 file) ----
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

// ---- Accomplishments & Evidence ----
$router->get('/accomplishments', fn() => (new AccomplishmentController())->index());
$router->get('/accomplishments/create', fn() => (new AccomplishmentController())->create());
$router->get('/accomplishments/gallery', fn() => (new AccomplishmentController())->gallery());
$router->post('/accomplishments/store', fn() => (new AccomplishmentController())->store());
$router->get('/accomplishments/{id}', fn($id) => (new AccomplishmentController())->show($id));
$router->get('/accomplishments/{id}/print', fn($id) => (new AccomplishmentController())->printView($id));
$router->post('/accomplishments/{id}/save-draft', fn($id) => (new AccomplishmentController())->saveDraft($id));
$router->post('/accomplishments/{id}/submit', fn($id) => (new AccomplishmentController())->submit($id));
$router->post('/accomplishments/{id}/review', fn($id) => (new AccomplishmentController())->review($id));
$router->post('/accomplishments/{id}/upload-attachment', fn($id) => (new AccomplishmentController())->uploadAttachment($id));
$router->post('/accomplishments/{aid}/attachments/{atid}/delete', fn($aid, $atid) => (new AccomplishmentController())->deleteAttachment($aid, $atid));

// ---- Administration ----
$router->get('/admin/users', fn() => (new AdminController())->users());
$router->post('/admin/users/{id}/status', fn($id) => (new AdminController())->updateUserStatus($id));
$router->get('/admin/departments', fn() => (new AdminController())->departments());
$router->post('/admin/departments/store', fn() => (new AdminController())->storeDepartment());
$router->post('/admin/departments/{id}/delete', fn($id) => (new AdminController())->deleteDepartment($id));
$router->get('/admin/positions', fn() => (new AdminController())->positions());
$router->post('/admin/positions/store', fn() => (new AdminController())->storePosition());
$router->post('/admin/positions/{id}/delete', fn($id) => (new AdminController())->deletePosition($id));

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
