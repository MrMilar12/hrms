<?php

class Notification
{
    public static function user(int $userId, string $message, ?string $link = null): void
    {
        if ($userId <= 0 || $userId === (int) Auth::userId()) return;
        $stmt = Database::getInstance()->prepare('INSERT INTO notifications (user_id, message, link, is_read, created_at) VALUES (?, ?, ?, 0, NOW())');
        $stmt->execute([$userId, mb_substr($message, 0, 255), $link]);
    }

    public static function employees(array $employeeIds, string $message, ?string $link = null): void
    {
        $employeeIds = array_values(array_unique(array_filter(array_map('intval', $employeeIds))));
        if (!$employeeIds) return;
        $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
        $stmt = Database::getInstance()->prepare("SELECT id FROM users WHERE status = 'active' AND employee_id IN ($placeholders)");
        $stmt->execute($employeeIds);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $userId) self::user((int) $userId, $message, $link);
    }

    public static function permission(string $permission, string $message, ?string $link = null): void
    {
        $stmt = Database::getInstance()->prepare(
            "SELECT DISTINCT u.id FROM users u
             JOIN role_permissions rp ON rp.role_id = u.role_id
             JOIN permissions p ON p.id = rp.permission_id
             WHERE u.status = 'active' AND p.code = ?"
        );
        $stmt->execute([$permission]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $userId) self::user((int) $userId, $message, $link);
    }
}
