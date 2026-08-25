<?php

class PortableUpdater
{
    private const BRANCH = 'main';
    private const PRESERVE = [
        'config/app.php', '.env', 'uploads/', '.git/',
        'storage/logs/', 'storage/cache/', 'storage/backups/',
        'storage/app.key', 'storage/installed.lock',
    ];

    public static function status(): array
    {
        self::ensureStateTable();
        $commit = self::githubJson('/commits/' . self::BRANCH);
        $remoteSha = (string) ($commit['sha'] ?? '');
        if (!preg_match('/^[a-f0-9]{40}$/', $remoteSha)) throw new RuntimeException('GitHub returned an invalid commit identifier.');
        $state = Database::getInstance()->query('SELECT deployed_commit, deployed_version FROM system_update_state WHERE id = 1')->fetch();
        $localSha = (string) ($state['deployed_commit'] ?? '');
        if ($localSha === '') {
            $localSha = (string) (Database::getInstance()->query('SELECT source_commit FROM system_releases WHERE source_commit IS NOT NULL ORDER BY released_at DESC, id DESC LIMIT 1')->fetchColumn() ?: $remoteSha);
            self::saveState($localSha, $state['deployed_version'] ?: self::version());
        }
        $release = Database::getInstance()->prepare('SELECT version FROM system_releases WHERE source_commit = ? AND is_published = 1 ORDER BY id DESC LIMIT 1');
        $release->execute([$remoteSha]);
        $newVersion = $release->fetchColumn() ?: null;
        $currentVersion = $state['deployed_version'] ?: self::version();
        return [
            'current_version' => $currentVersion,
            'new_version' => $newVersion,
            'version_ready' => $newVersion !== null && version_compare($newVersion, $currentVersion, '>'),
            'local_sha' => $localSha,
            'remote_sha' => $remoteSha,
            'remote_message' => trim((string) ($commit['commit']['message'] ?? '')),
            'update_available' => $localSha === '' || !hash_equals($localSha, $remoteSha),
            'working_tree_clean' => true,
            'deployment_writable' => self::canWriteApplication(),
            'branch' => self::BRANCH,
            'repository' => GITHUB_REPOSITORY,
        ];
    }

    public static function apply(int $developerId): array
    {
        self::ensureDirectories();
        $lock = fopen(STORAGE_PATH . '/cache/system-update.lock', 'c+');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) throw new RuntimeException('Another update is already running.');
        $work = STORAGE_PATH . '/cache/update_' . bin2hex(random_bytes(6));
        $backup = null; $installed = [];
        $status = self::status();
        $oldVersion = $status['current_version'];
        try {
            if (!$status['update_available']) return ['updated' => false, 'message' => 'The system is already up to date.'];
            if (!$status['version_ready']) throw new RuntimeException('Publish a higher version notification for this GitHub commit before installing it.');
            if (!$status['deployment_writable']) throw new RuntimeException('PHP cannot write the application directory on this hosting account.');
            if (!class_exists('ZipArchive')) throw new RuntimeException('The PHP ZIP extension is required.');
            self::baselineCurrentMigrations();
            mkdir($work, 0750, true);
            $archive = $work . '/github-update.zip';
            self::download('/zipball/' . $status['remote_sha'], $archive);
            $source = self::extractAndLocateRoot($archive, $work . '/source');
            $newVersion = $status['new_version'];

            $backup = STORAGE_PATH . '/backups/' . date('Ymd_His') . '_before_v' . $newVersion . '.zip';
            self::setMaintenance(true);
            $installed = self::installArchive($source, $backup);
            file_put_contents(BASE_PATH . '/VERSION', $newVersion . PHP_EOL, LOCK_EX);
            $migrations = self::runNewMigrations();
            self::healthCheck();
            self::saveState($status['remote_sha'], $newVersion);
            self::publish($developerId, $newVersion, $status['remote_message'], $status['remote_sha']);
            self::logDeployment($developerId, $oldVersion, $newVersion, $status['local_sha'], $status['remote_sha'], 'success', $status['remote_message'], $backup);
            self::setMaintenance(false);
            self::removeTree($work);
            return ['updated' => true, 'version' => $newVersion, 'sha' => $status['remote_sha'], 'migrations' => $migrations,
                'message' => "HRMS {$newVersion} was installed successfully."];
        } catch (Throwable $e) {
            if ($backup && is_file($backup)) self::restoreFiles($backup, $installed);
            self::logDeployment($developerId, $oldVersion, null, $status['local_sha'] ?? null, $status['remote_sha'] ?? null, 'failed', $e->getMessage(), $backup);
            self::setMaintenance(false); self::removeTree($work); throw $e;
        } finally {
            flock($lock, LOCK_UN); fclose($lock);
        }
    }

    private static function githubJson(string $path): array
    {
        $temp = tempnam(sys_get_temp_dir(), 'hrms-gh-');
        self::download($path, $temp);
        try { return json_decode((string) file_get_contents($temp), true, 512, JSON_THROW_ON_ERROR); }
        finally { @unlink($temp); }
    }

    private static function download(string $path, string $destination): void
    {
        if (!extension_loaded('curl')) throw new RuntimeException('PHP cURL is required.');
        $handle = fopen($destination, 'wb'); if (!$handle) throw new RuntimeException('Unable to create update download.');
        $headers = ['Accept: application/vnd.github+json', 'X-GitHub-Api-Version: 2022-11-28'];
        $token = trim((string) getenv('HRMS_GITHUB_TOKEN')); if ($token !== '') $headers[] = 'Authorization: Bearer ' . $token;
        $curl = curl_init('https://api.github.com/repos/' . GITHUB_REPOSITORY . $path);
        curl_setopt_array($curl, [CURLOPT_FILE => $handle, CURLOPT_FOLLOWLOCATION => true, CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 180, CURLOPT_HTTPHEADER => $headers, CURLOPT_USERAGENT => 'HRMS-Portable-Updater/1.0']);
        $ok = curl_exec($curl); $code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE); $error = curl_error($curl);
        curl_close($curl); fclose($handle);
        if (!$ok || $code < 200 || $code >= 300) { @unlink($destination); throw new RuntimeException($error ?: "GitHub download failed with HTTP {$code}."); }
    }

    private static function extractAndLocateRoot(string $archive, string $destination): string
    {
        $zip = new ZipArchive(); if ($zip->open($archive) !== true) throw new RuntimeException('The GitHub archive is invalid.');
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false || str_contains($name, '../') || str_starts_with($name, '/')) { $zip->close(); throw new RuntimeException('Unsafe path found in update archive.'); }
        }
        if (!mkdir($destination, 0750, true) || !$zip->extractTo($destination)) { $zip->close(); throw new RuntimeException('Unable to extract the update.'); }
        $zip->close(); $roots = array_values(array_filter(glob($destination . '/*') ?: [], 'is_dir'));
        if (count($roots) !== 1) throw new RuntimeException('Unexpected GitHub archive structure.');
        return $roots[0];
    }

    private static function installArchive(string $source, string $backupPath): array
    {
        $backup = new ZipArchive(); if ($backup->open($backupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Unable to create file backup.');
        $installed = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($source) + 1));
            if (self::preserved($relative)) continue;
            $target = BASE_PATH . '/' . $relative;
            if ($item->isDir()) { if (!is_dir($target)) mkdir($target, 0755, true); continue; }
            if (is_file($target)) $backup->addFile($target, $relative); else $installed[] = $relative;
            $directory = dirname($target); if (!is_dir($directory)) mkdir($directory, 0755, true);
            $temporary = $target . '.update-' . bin2hex(random_bytes(3));
            if (!copy($item->getPathname(), $temporary) || !rename($temporary, $target)) throw new RuntimeException("Unable to install {$relative}.");
        }
        $backup->addFromString('.new-files.json', json_encode($installed)); $backup->close(); return $installed;
    }

    private static function restoreFiles(string $backupPath, array $installed): void
    {
        foreach ($installed as $relative) { $path = BASE_PATH . '/' . $relative; if (is_file($path)) @unlink($path); }
        $zip = new ZipArchive(); if ($zip->open($backupPath) !== true) return;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i); if ($name === '.new-files.json') continue;
            $target = BASE_PATH . '/' . $name; if (!is_dir(dirname($target))) mkdir(dirname($target), 0755, true);
            copy('zip://' . $backupPath . '#' . $name, $target);
        }
        $zip->close();
    }

    private static function runNewMigrations(): array
    {
        $pdo = Database::getInstance(); self::ensureStateTable();
        $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (migration VARCHAR(190) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
        $applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN); $ran = [];
        $files = glob(BASE_PATH . '/database/migrations/*.sql') ?: []; sort($files, SORT_NATURAL);
        $insert = $pdo->prepare('INSERT IGNORE INTO schema_migrations (migration) VALUES (?)');
        foreach ($files as $file) {
            $name = basename($file); if (in_array($name, $applied, true)) continue;
            $sql = trim((string) file_get_contents($file));
            if ($sql !== '') $pdo->exec($sql);
            $insert->execute([$name]); $ran[] = $name;
        }
        return $ran;
    }

    private static function baselineCurrentMigrations(): void
    {
        $pdo = Database::getInstance();
        $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (migration VARCHAR(190) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
        $insert = $pdo->prepare('INSERT IGNORE INTO schema_migrations (migration) VALUES (?)');
        foreach (glob(BASE_PATH . '/database/migrations/*.sql') ?: [] as $file) $insert->execute([basename($file)]);
    }

    private static function publish(int $developerId, string $version, string $notes, string $sha): void
    {
        $title = 'HRMS Version ' . $version; $notes = trim(strtok($notes, "\n")) ?: 'System improvements and maintenance updates.';
        $url = 'https://github.com/' . GITHUB_REPOSITORY . '/commit/' . $sha;
        $stmt = Database::getInstance()->prepare(
            'INSERT INTO system_releases (version,title,changes,released_at,is_published,created_by,release_url,source_commit)
             VALUES (?,?,?,NOW(),1,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title),changes=VALUES(changes),released_at=NOW(),is_published=1,created_by=VALUES(created_by),release_url=VALUES(release_url),source_commit=VALUES(source_commit)'
        );
        $stmt->execute([$version, $title, $notes, $developerId, $url, $sha]);
    }

    private static function logDeployment(int $developerId, ?string $from, ?string $to, ?string $fromSha, ?string $toSha, string $status, string $details, ?string $backup): void
    {
        try { $stmt = Database::getInstance()->prepare('INSERT INTO system_deployments (developer_id,from_version,to_version,from_commit,to_commit,status,details,backup_files) VALUES (?,?,?,?,?,?,?,?)'); $stmt->execute([$developerId,$from,$to,$fromSha,$toSha,$status,$details,$backup]); }
        catch (Throwable $ignored) { error_log('Deployment logging failed: ' . $ignored->getMessage()); }
    }

    private static function saveState(string $sha, string $version): void
    { Database::getInstance()->prepare('UPDATE system_update_state SET deployed_commit=?, deployed_version=? WHERE id=1')->execute([$sha,$version]); }
    private static function ensureStateTable(): void
    { Database::getInstance()->exec("CREATE TABLE IF NOT EXISTS system_update_state (id TINYINT UNSIGNED PRIMARY KEY,deployed_commit CHAR(40) NULL,deployed_version VARCHAR(30) NULL,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB"); Database::getInstance()->exec("INSERT IGNORE INTO system_update_state (id,deployed_version) VALUES (1,'" . addslashes(self::version()) . "')"); }
    private static function healthCheck(): void
    { foreach (['public/index.php','config/constants.php','VERSION'] as $file) if (!is_file(BASE_PATH . '/' . $file)) throw new RuntimeException("Health check failed: {$file} is missing."); }
    private static function canWriteApplication(): bool
    { return is_writable(BASE_PATH) && is_writable(BASE_PATH . '/public') && is_writable(BASE_PATH . '/core'); }
    private static function version(): string
    { return is_file(BASE_PATH . '/VERSION') ? trim((string) file_get_contents(BASE_PATH . '/VERSION')) : APP_VERSION; }
    private static function preserved(string $path): bool
    { foreach (self::PRESERVE as $preserve) if ($path === rtrim($preserve, '/') || str_starts_with($path, $preserve)) return true; return false; }
    private static function setMaintenance(bool $on): void
    { $file = STORAGE_PATH . '/cache/maintenance.json'; if (!$on) { @unlink($file); return; } file_put_contents($file, json_encode(['message'=>'Installing a new HRMS version.','started_at'=>date(DATE_ATOM)]), LOCK_EX); }
    private static function ensureDirectories(): void
    { foreach ([STORAGE_PATH . '/cache', STORAGE_PATH . '/backups'] as $dir) if (!is_dir($dir)) mkdir($dir,0750,true); }
    private static function removeTree(string $dir): void
    { if (!is_dir($dir)) return; $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST); foreach($it as $item) $item->isDir()?@rmdir($item->getPathname()):@unlink($item->getPathname()); @rmdir($dir); }
}
