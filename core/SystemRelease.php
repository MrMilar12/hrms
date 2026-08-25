<?php

class SystemRelease
{
    public static function unseenForUser(int $userId): array
    {
        $stmt = Database::getInstance()->prepare(
            'SELECT sr.id, sr.version, sr.title, sr.changes, sr.released_at
             FROM system_releases sr
             LEFT JOIN user_release_views urv
               ON urv.release_id = sr.id AND urv.user_id = ?
             WHERE sr.is_published = 1 AND urv.release_id IS NULL
             ORDER BY sr.released_at DESC, sr.id DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function published(): array
    {
        return Database::getInstance()->query(
            'SELECT id, version, title, changes, released_at, release_url
             FROM system_releases WHERE is_published = 1
             ORDER BY released_at DESC, id DESC'
        )->fetchAll();
    }

    public static function currentVersion(): string
    {
        $version = Database::getInstance()->query(
            'SELECT version FROM system_releases WHERE is_published = 1 ORDER BY released_at DESC, id DESC LIMIT 1'
        )->fetchColumn();
        return $version ?: APP_VERSION;
    }

    public static function acknowledge(int $userId, array $releaseIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $releaseIds))));
        if (!$ids) return;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo = Database::getInstance();
        $valid = $pdo->prepare("SELECT id FROM system_releases WHERE is_published = 1 AND id IN ({$placeholders})");
        $valid->execute($ids);
        $insert = $pdo->prepare('INSERT IGNORE INTO user_release_views (user_id, release_id) VALUES (?, ?)');
        foreach ($valid->fetchAll(PDO::FETCH_COLUMN) as $releaseId) {
            $insert->execute([$userId, (int) $releaseId]);
        }
    }
}
