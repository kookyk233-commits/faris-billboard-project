<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/Response.php';

final class UserController
{
    /** GET /api/users — قائمة الأعضاء (بدون كلمات المرور) */
    public function index(): void
    {
        Response::success(User::all());
    }

    /**
     * POST /api/users — إضافة عضو جديد ومنحه صلاحية دخول
     * body: { "username", "password", "full_name", "role": "admin|employee", "administration_id" }
     */
    public function store(): void
    {
        $input = self::body();
        $username = trim((string) ($input['username'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $fullName = trim((string) ($input['full_name'] ?? ''));
        $role = (string) ($input['role'] ?? 'employee');
        $administrationId = isset($input['administration_id']) ? (int) $input['administration_id'] : null;

        if ($username === '' || $password === '' || $fullName === '') {
            Response::error('اسم المستخدم وكلمة المرور والاسم الكامل مطلوبة', 422);
        }

        if (strlen($password) < 8) {
            Response::error('كلمة المرور يجب ألا تقل عن 8 أحرف', 422);
        }

        if (!in_array($role, ['admin', 'employee'], true)) {
            Response::error('الصلاحية يجب أن تكون admin أو employee', 422);
        }

        if (User::usernameExists($username)) {
            Response::error('اسم المستخدم موجود بالفعل', 409);
        }

        $id = User::create($username, $password, $fullName, $role, $administrationId);
        Response::success(User::findById($id), 'تمت إضافة العضو ومنحه صلاحية الدخول', 201);
    }

    /**
     * PUT /api/users/{id}/permissions — تعديل صلاحية/تفعيل عضو
     * body: { "role": "admin|employee", "is_active": true }
     */
    public function updatePermissions(array $params): void
    {
        $id = (int) $params['id'];
        if (User::findById($id) === null) {
            Response::error('العضو غير موجود', 404);
        }

        $input = self::body();
        $role = (string) ($input['role'] ?? 'employee');
        $isActive = (bool) ($input['is_active'] ?? true);

        if (!in_array($role, ['admin', 'employee'], true)) {
            Response::error('الصلاحية يجب أن تكون admin أو employee', 422);
        }

        User::updatePermissions($id, $role, $isActive);
        Response::success(null, 'تم تحديث صلاحيات العضو');
    }

    /** DELETE /api/users/{id} */
    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        if (User::findById($id) === null) {
            Response::error('العضو غير موجود', 404);
        }

        User::delete($id);
        Response::success(null, 'تم حذف العضو');
    }

    private static function body(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
