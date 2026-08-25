<?php

// Fixed-window rate limiter stored in MySQL so limits are shared by all app servers.
// Redis should replace this storage layer at very high throughput without changing callers.
class RateLimiter
{
    public static function allow(string $bucket, int $limit, int $windowSeconds): bool
    {
        $now = time();
        $windowTimestamp = intdiv($now, $windowSeconds) * $windowSeconds;
        $windowStart = date('Y-m-d H:i:s', $windowTimestamp);
        $expiresAt = date('Y-m-d H:i:s', $windowTimestamp + $windowSeconds);
        $bucketHash = hash('sha256', $bucket);
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare(
            'INSERT INTO rate_limits (bucket_hash, window_start, hits, expires_at)
             VALUES (?, ?, 1, ?)
             ON DUPLICATE KEY UPDATE hits = hits + 1'
        );
        $stmt->execute([$bucketHash, $windowStart, $expiresAt]);
        $read = $pdo->prepare('SELECT hits FROM rate_limits WHERE bucket_hash = ? AND window_start = ?');
        $read->execute([$bucketHash, $windowStart]);
        $hits = (int) $read->fetchColumn();

        header('X-RateLimit-Limit: ' . $limit);
        header('X-RateLimit-Remaining: ' . max(0, $limit - $hits));
        if ($hits > $limit) {
            header('Retry-After: ' . max(1, ($windowTimestamp + $windowSeconds) - $now));
            return false;
        }

        // Low-frequency opportunistic cleanup keeps the table bounded.
        if (random_int(1, 1000) === 1) {
            $pdo->exec('DELETE FROM rate_limits WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
        }
        return true;
    }
}
