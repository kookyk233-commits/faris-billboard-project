<?php
declare(strict_types=1);

/**
 * الإعدادات العامة للمشروع.
 * عند الرفع على الاستضافة: عدّل القيم هنا فقط (أو حوّلها إلى متغيرات بيئة .env).
 * لا تضع بيانات اتصال قاعدة البيانات الحقيقية في مستودع Git عام.
 */
final class Config
{
    // --- قاعدة البيانات ---
    public const DB_HOST = 'faris2018.infinityfreeapp.com';
    public const DB_NAME = 'faris_billboard';
    public const DB_USER = 'if0_43016477';
    public const DB_PASS = 'Moon2604';
    public const DB_CHARSET = 'utf8mb4';

    // --- JWT ---
    // غيّر هذا المفتاح إلى قيمة عشوائية طويلة وسرّية قبل النشر (32 بايت على الأقل)
    public const JWT_SECRET = 'CHANGE_THIS_TO_A_LONG_RANDOM_SECRET_BEFORE_DEPLOY';
    public const JWT_ALGO = 'HS256';
    public const ACCESS_TOKEN_TTL = 3600;        // ساعة واحدة
    public const REFRESH_TOKEN_TTL = 60 * 60 * 24 * 30; // 30 يوم

    // --- رفع الصور ---
    public const UPLOAD_DIR = __DIR__ . '/../public/uploads/';
    public const UPLOAD_URL_BASE = '/uploads/'; // يُضاف إليه دومين الاستضافة في الواجهة
    public const MAX_UPLOAD_BYTES = 5 * 1024 * 1024; // 5MB
    public const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    // --- إشعارات Firebase Cloud Messaging (اختياري) ---
    // احصل عليه من Firebase Console -> Project Settings -> Cloud Messaging -> Server key (Legacy)
    public const FCM_SERVER_KEY = 'FCM_SERVER_KEY_HERE';
    public const FCM_ENABLED = false; // فعّلها بعد ضبط FCM_SERVER_KEY

    // --- عام ---
    public const APP_ENV = 'production'; // production | development
}
