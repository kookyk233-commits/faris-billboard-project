<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/JWT.php';
require_once __DIR__ . '/../core/Response.php';

final class AuthMiddleware
{
    /** الحمولة الحالية بعد التحقق، متاحة لبقية الطلب */
    public static ?array $currentUser = null;

    /**
     * يتحقق من وجود Authorization: Bearer <token> صالح.
     * يوقف الطلب فورًا برد 401 إن لم يكن صالحًا.
     */
    public static function requireAuth(): callable
    {
        return function (): void {
            $token = self::extractBearerToken();
            if ($token === null) {
                Response::error('التوكن مفقود', 401);
            }

            $payload = JWT::decode($token);
            if ($payload === null) {
                Response::error('التوكن غير صالح أو منتهي الصلاحية', 401);
            }

            self::$currentUser = $payload;
        };
    }

    /**
     * يجب استخدامها بعد requireAuth(). تسمح فقط للأدمن بالمتابعة.
     */
    public static function requireAdmin(): callable
    {
        return function (): void {
            if (self::$currentUser === null || (self::$currentUser['role'] ?? '') !== 'admin') {
                Response::error('لا تملك صلاحية الوصول لهذا الإجراء', 403);
            }
        };
    }

    private static function extractBearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? null;

        if ($header === null || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }
}
