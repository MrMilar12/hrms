<?php

class GitHubReleaseSync
{
    public static function sync(int $userId): array
    {
        if (!extension_loaded('curl')) throw new RuntimeException('PHP cURL is required to connect to GitHub.');

        $url = 'https://api.github.com/repos/' . GITHUB_REPOSITORY . '/releases?per_page=30';
        $headers = ['Accept: application/vnd.github+json', 'X-GitHub-Api-Version: 2022-11-28'];
        $token = trim((string) getenv('HRMS_GITHUB_TOKEN'));
        if ($token !== '') $headers[] = 'Authorization: Bearer ' . $token;
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers, CURLOPT_USERAGENT => 'HRMS-Release-Sync/1.0',
        ]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($body === false || $status !== 200) {
            $message = $error ?: "GitHub returned HTTP {$status}.";
            if ($status === 403) $message .= ' API rate limit reached; configure HRMS_GITHUB_TOKEN.';
            throw new RuntimeException($message);
        }
        $releases = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($releases)) throw new RuntimeException('GitHub returned an invalid release list.');

        $stmt = Database::getInstance()->prepare(
            'INSERT INTO system_releases
                (version, title, changes, released_at, is_published, created_by, github_release_id, release_url)
             VALUES (?, ?, ?, ?, 1, ?, ?, ?)
             ON DUPLICATE KEY UPDATE title = VALUES(title), changes = VALUES(changes),
                released_at = VALUES(released_at), is_published = 1,
                github_release_id = VALUES(github_release_id), release_url = VALUES(release_url)'
        );
        $imported = 0; $skipped = 0;
        foreach ($releases as $release) {
            if (!empty($release['draft']) || empty($release['id']) || empty($release['tag_name'])) { $skipped++; continue; }
            $version = preg_replace('/^[vV]/', '', trim((string) $release['tag_name']));
            if (!preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version)) { $skipped++; continue; }
            $title = trim((string) ($release['name'] ?? '')) ?: 'Version ' . $version;
            $changes = trim((string) ($release['body'] ?? '')) ?: 'Maintenance and system improvements.';
            $releasedAt = $release['published_at'] ?? $release['created_at'] ?? null;
            $date = $releasedAt ? date('Y-m-d H:i:s', strtotime((string) $releasedAt)) : date('Y-m-d H:i:s');
            $stmt->execute([$version, mb_substr($title, 0, 150), $changes, $date, $userId,
                (int) $release['id'], mb_substr((string) ($release['html_url'] ?? ''), 0, 500) ?: null]);
            $imported++;
        }
        return ['imported' => $imported, 'skipped' => $skipped];
    }
}
