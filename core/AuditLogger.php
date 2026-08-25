<?php
// Writes an audit_logs row for every create/update/delete on sensitive tables.

class AuditLogger
{
    public static function log(string $action, string $tableName, ?int $recordId, ?array $oldValue = null, ?array $newValue = null): void
    {
        $requestContext = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'path' => isset($_SERVER['REQUEST_URI']) ? (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/') : null,
        ];
        $newValue = $newValue ?? [];
        $newValue['_request'] = $requestContext;
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, table_name, record_id, old_value, new_value, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            Auth::userId(),
            $action,
            $tableName,
            $recordId,
            $oldValue !== null ? json_encode($oldValue) : null,
            json_encode($newValue, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
