<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/JWT.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/NotificationService.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../config/Config.php';

final class AuthController
{
    /**
     * POST /api/auth/login
     * body: { "username": "...", "password": "..." }
     */
    public function login(): void
    {
        $input = self::body();
        $username = trim((string) ($input['username'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if ($username === '' || $password === '') {
            Response::error('اسم المستخدم وكلمة المرور مطلوبان', 422);
        }

        $user = User::findByUsername($username);

        // رسالة موحّدة سواء كان المستخدم غير موجود أو كلمة المرور خاطئة (لمنع تخمين أسماء المستخدمين)
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            Response::error('اسم المستخدم أو كلمة المرور غير صحيحة', 401);
        }

        if ((int) $user['is_active'] !== 1) {
            Response::error('لا تمتلك الصلاحية للدخول لهذه الصفحة', 403);
        }

        $accessToken = JWT::encode([
            'sub'  => $user['id'],
            'role' => $user['role'],
        ], Config::ACCESS_TOKEN_TTL);

        Response::success([
            'access_token' => $accessToken,
            'expires_in'   => Config::ACCESS_TOKEN_TTL,
            'user'         => [
                'id'                => (int) $user['id'],
                'username'          => $user['username'],
                'full_name'         => $user['full_name'],
                'role'              => $user['role'],
                'administration_id' => $user['administration_id'] !== null ? (int) $user['administration_id'] : null,
            ],
        ], 'تم تسجيل الدخول بنجاح');
    }

    /**
     * POST /api/auth/device-token  (Authorization اختياري)
     * body: { "fcm_token": "..." }
     */
    public function registerDeviceToken(): void
    {
        $input = self::body();
        $token = trim((string) ($input['fcm_token'] ?? ''));
        if ($token === '') {
            Response::error('fcm_token مطلوب', 422);
        }

        $userId = AuthMiddleware::$currentUser['sub'] ?? null;
        NotificationService::registerDeviceToken($userId !== null ? (int) $userId : null, $token);

        Response::success(null, 'تم تسجيل جهاز الإشعارات');
    }

    private static function body(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
