<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Administration.php';
require_once __DIR__ . '/../core/Response.php';

final class AdministrationController
{
    /** GET /api/administrations */
    public function index(): void
    {
        Response::success(Administration::all());
    }

    /** POST /api/administrations  body: { "name": "..." } */
    public function store(): void
    {
        $input = self::body();
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            Response::error('اسم الإدارة مطلوب', 422);
        }

        $id = Administration::create($name);
        Response::success(Administration::find($id), 'تمت إضافة الإدارة', 201);
    }

    /** PUT /api/administrations/{id} */
    public function update(array $params): void
    {
        $id = (int) $params['id'];
        if (Administration::find($id) === null) {
            Response::error('الإدارة غير موجودة', 404);
        }

        $input = self::body();
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            Response::error('اسم الإدارة مطلوب', 422);
        }

        Administration::update($id, $name);
        Response::success(null, 'تم تحديث الإدارة');
    }

    /** DELETE /api/administrations/{id} */
    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        if (Administration::find($id) === null) {
            Response::error('الإدارة غير موجودة', 404);
        }

        Administration::delete($id);
        Response::success(null, 'تم حذف الإدارة');
    }

    private static function body(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
