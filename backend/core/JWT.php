<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Config.php';

/**
 * تطبيق مبسّط وآمن لـ JWT (HS256 فقط) — بديل خفيف عن firebase/php-jwt
 * لتفادي الاعتماد على Composer في بيئات الاستضافة المشتركة.
 */
final class JWT
{
    public static function encode(array $payload, int $ttlSeconds): string
    {
        $header = ['typ' => 'JWT', 'alg' => Config::JWT_ALGO];

        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $ttlSeconds;

        $segments = [
            self::base64UrlEncode(json_encode($header, JSON_UNESCAPED_UNICODE)),
            self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE)),
        ];

        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, Config::JWT_SECRET, true);
        $segments[] = self::base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * يُرجع الحمولة (payload) إذا كان التوقيع والصلاحية صحيحين، أو null إن لم يكن كذلك.
     */
    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $expectedSignature = hash_hmac(
            'sha256',
            $headerB64 . '.' . $payloadB64,
            Config::JWT_SECRET,
            true
        );

        $givenSignature = self::base64UrlDecode($signatureB64);

        // مقارنة زمنية ثابتة لمنع timing attacks
        if (!hash_equals($expectedSignature, $givenSignature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        if (!is_array($payload) || !isset($payload['exp'])) {
            return null;
        }

        if ($payload['exp'] < time()) {
            return null; // منتهي الصلاحية
        }

        return $payload;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $padded = str_pad($data, strlen($data) % 4 === 0 ? strlen($data) : strlen($data) + 4 - strlen($data) % 4, '=');
        return base64_decode(strtr($padded, '-_', '+/')) ?: '';
    }
}
