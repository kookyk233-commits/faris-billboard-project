<?php
declare(strict_types=1);

// عرض الأخطاء يُفعَّل فقط في بيئة التطوير — أبقِه مغلقًا في الإنتاج لمنع تسريب معلومات حساسة
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/AdController.php';
require_once __DIR__ . '/../controllers/AdministrationController.php';
require_once __DIR__ . '/../controllers/UserController.php';

// CORS — اسمح لتطبيق أندرويد وأي واجهة ويب بالاتصال بالـ API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

set_exception_handler(function (Throwable $e): void {
    error_log($e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
    Response::error('حدث خطأ غير متوقع في الخادم', 500);
});

$router = new Router();

// --- المصادقة ---
$router->post('/auth/login', fn() => (new AuthController())->login());
$router->post('/auth/device-token', fn() => (new AuthController())->registerDeviceToken());

// --- الإعلانات ---
$router->get('/ads', fn() => (new AdController())->index());
$router->get('/ads/{id}', fn($p) => (new AdController())->show($p));
$router->post('/ads', fn($p) => (new AdController())->store($p), [AuthMiddleware::requireAuth()]);
$router->put('/ads/{id}', fn($p) => (new AdController())->update($p), [AuthMiddleware::requireAuth()]);
$router->delete('/ads/{id}', fn($p) => (new AdController())->destroy($p), [
    AuthMiddleware::requireAuth(),
    AuthMiddleware::requireAdmin(),
]);

// --- الإدارات (Administrations) ---
$router->get('/administrations', fn() => (new AdministrationController())->index());
$router->post('/administrations', fn($p) => (new AdministrationController())->store($p), [
    AuthMiddleware::requireAuth(), AuthMiddleware::requireAdmin(),
]);
$router->put('/administrations/{id}', fn($p) => (new AdministrationController())->update($p), [
    AuthMiddleware::requireAuth(), AuthMiddleware::requireAdmin(),
]);
$router->delete('/administrations/{id}', fn($p) => (new AdministrationController())->destroy($p), [
    AuthMiddleware::requireAuth(), AuthMiddleware::requireAdmin(),
]);

// --- الأعضاء (Users) — إدارة الصلاحيات، للأدمن فقط ---
$router->get('/users', fn() => (new UserController())->index(), [
    AuthMiddleware::requireAuth(), AuthMiddleware::requireAdmin(),
]);
$router->post('/users', fn($p) => (new UserController())->store($p), [
    AuthMiddleware::requireAuth(), AuthMiddleware::requireAdmin(),
]);
$router->put('/users/{id}/permissions', fn($p) => (new UserController())->updatePermissions($p), [
    AuthMiddleware::requireAuth(), AuthMiddleware::requireAdmin(),
]);
$router->delete('/users/{id}', fn($p) => (new UserController())->destroy($p), [
    AuthMiddleware::requireAuth(), AuthMiddleware::requireAdmin(),
]);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
