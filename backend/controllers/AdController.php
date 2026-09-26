<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Ad.php';
require_once __DIR__ . '/../models/Administration.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/NotificationService.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../config/Config.php';

final class AdController
{
    /** GET /api/ads?page=1&per_page=20 */
    public function index(): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = (int) ($_GET['per_page'] ?? 20);

        $ads = Ad::paginate($page, $perPage);

        Response::success(array_map([self::class, 'transform'], $ads));
    }

    /** GET /api/ads/{id} */
    public function show(array $params): void
    {
        $ad = Ad::find((int) $params['id']);
        if ($ad === null) {
            Response::error('الإعلان غير موجود', 404);
        }
        Response::success(self::transform($ad));
    }

    /**
     * POST /api/ads  (multipart/form-data)
     * fields: title, content, source, administration_id, image (اختياري)
     * يتطلب مصادقة — المستخدم الحالي يُسجَّل كصاحب الإعلان تلقائيًا.
     */
    public function store(): void
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $source = trim((string) ($_POST['source'] ?? '')) ?: null;
        $administrationId = (int) ($_POST['administration_id'] ?? 0);

        if ($title === '' || $content === '' || $administrationId <= 0) {
            Response::error('العنوان والمحتوى والإدارة حقول مطلوبة', 422);
        }

        if (Administration::find($administrationId) === null) {
            Response::error('الإدارة المحددة غير موجودة', 422);
        }

        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imagePath = self::storeUploadedImage($_FILES['image']);
        }

        $userId = (int) AuthMiddleware::$currentUser['sub'];

        $adId = Ad::create($title, $content, $source, $imagePath, $administrationId, $userId);

        // إشعار فوري لكل الطلاب/المستخدمين فور نشر الإعلان — بدون أي موافقة مسبقة
        NotificationService::notifyNewAd($adId, $title, mb_substr($content, 0, 100));

        $ad = Ad::find($adId);
        Response::success(self::transform(self::withJoins($ad)), 'تم نشر الإعلان بنجاح', 201);
    }

    /** PUT /api/ads/{id} */
    public function update(array $params): void
    {
        $id = (int) $params['id'];
        if (Ad::find($id) === null) {
            Response::error('الإعلان غير موجود', 404);
        }

        $input = self::jsonBody();
        $title = trim((string) ($input['title'] ?? ''));
        $content = trim((string) ($input['content'] ?? ''));
        $source = isset($input['source']) ? trim((string) $input['source']) : null;

        if ($title === '' || $content === '') {
            Response::error('العنوان والمحتوى مطلوبان', 422);
        }

        Ad::update($id, $title, $content, $source);
        Response::success(null, 'تم تحديث الإعلان');
    }

    /** DELETE /api/ads/{id} */
    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        if (Ad::find($id) === null) {
            Response::error('الإعلان غير موجود', 404);
        }

        Ad::delete($id);
        Response::success(null, 'تم حذف الإعلان');
    }

    private static function storeUploadedImage(array $file): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            Response::error('فشل رفع الصورة', 422);
        }

        if ($file['size'] > Config::MAX_UPLOAD_BYTES) {
            Response::error('حجم الصورة أكبر من الحد المسموح (5MB)', 422);
        }

        $mime = mime_content_type($file['tmp_name']) ?: '';
        if (!in_array($mime, Config::ALLOWED_IMAGE_TYPES, true)) {
            Response::error('صيغة الصورة غير مدعومة (jpeg, png, webp فقط)', 422);
        }

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'bin',
        };

        // اسم عشوائي آمن لمنع تخمين الملفات أو الكتابة فوق ملف موجود
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = Config::UPLOAD_DIR . $filename;

        if (!is_dir(Config::UPLOAD_DIR)) {
            mkdir(Config::UPLOAD_DIR, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            Response::error('تعذر حفظ الصورة على الخادم', 500);
        }

        return Config::UPLOAD_URL_BASE . $filename;
    }

    private static function withJoins(array $ad): array
    {
        $administration = Administration::find((int) $ad['administration_id']);
        $ad['administration_name'] = $administration['name'] ?? null;
        return $ad;
    }

    private static function transform(array $ad): array
    {
        return [
            'id'                  => (int) $ad['id'],
            'title'               => $ad['title'],
            'content'             => $ad['content'],
            'source'              => $ad['source'],
            'image_url'           => $ad['image_path'] ?? null,
            'administration_id'   => (int) $ad['administration_id'],
            'administration_name' => $ad['administration_name'] ?? null,
            'published_at'        => $ad['published_at'],
        ];
    }

    private static function jsonBody(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
