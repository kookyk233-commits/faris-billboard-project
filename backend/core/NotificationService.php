<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../config/Database.php';

/**
 * إرسال إشعار فوري لكل الأجهزة المسجّلة فور نشر إعلان جديد
 * (بدون أي نظام موافقة/انتظار — الإشعار يُرسل مباشرة بعد الحفظ في قاعدة البيانات).
 *
 * ملاحظة: يستخدم FCM HTTP v1 (الحالي المدعوم من Google)، ويحتاج ملف
 * حساب الخدمة (service account JSON) من Firebase Console لتوليد access token،
 * أو يمكن استبدال getAccessToken() بمكتبة google/apiclient لو توفّر Composer
 * على الاستضافة. الدالة أدناه توضح نقطة التكامل بشكل عملي وقابل للتوسعة.
 */
final class NotificationService
{
    public static function notifyNewAd(int $adId, string $title, string $body): void
    {
        if (!Config::FCM_ENABLED) {
            return; // الإشعارات غير مفعّلة بعد — راجع Config::FCM_ENABLED
        }

        $tokens = self::allDeviceTokens();
        if (empty($tokens)) {
            return;
        }

        foreach ($tokens as $token) {
            self::sendToToken($token, $title, $body, $adId);
        }
    }

    private static function allDeviceTokens(): array
    {
        $stmt = Database::connection()->query('SELECT fcm_token FROM device_tokens');
        return array_column($stmt->fetchAll(), 'fcm_token');
    }

    private static function sendToToken(string $token, string $title, string $body, int $adId): void
    {
        // FCM Legacy HTTP API — أبسط للتطبيق البحثي، ويحتاج فقط Server Key من Firebase.
        // لمشروع إنتاجي حقيقي يُفضّل الانتقال إلى HTTP v1 + OAuth2.
        $payload = [
            'to'    => $token,
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
            'data' => [
                'ad_id' => $adId,
                'type'  => 'new_ad',
            ],
        ];

        $ch = curl_init('https://fcm.googleapis.com/fcm/send');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: key=' . Config::FCM_SERVER_KEY,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT    => 5,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    public static function registerDeviceToken(?int $userId, string $fcmToken): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO device_tokens (user_id, fcm_token) VALUES (:user_id, :fcm_token)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)'
        );
        $stmt->execute(['user_id' => $userId, 'fcm_token' => $fcmToken]);
    }
}
