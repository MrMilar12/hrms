<?php

class PortableUpdater
{
    private const BRANCH = 'main';
    private const PRESERVE = [
        'config/app.php', '.env', 'uploads/', 'output/', 'tmp/', '.git/',
        'storage/logs/', 'storage/cache/', 'storage/backups/',
        'storage/app.key', 'storage/installed.lock', 'storage/.htaccess', 'storage/.gitkeep',
    ];

    public static function status(): array
    {
        self::ensureStateTable();
        $commit = self::githubJson('/commits/' . self::BRANCH);
        $remoteSha = (string) ($commit['sha'] ?? '');
        if (!preg_match('/^[a-f0-9]{40}$/', $remoteSha)) throw new RuntimeException('GitHub returned an invalid commit identifier.');
        $workingCopySha = self::gitCommit('HEAD');
        $trackedRemoteSha = self::gitCommit('refs/remotes/origin/' . self::BRANCH);
        if ($workingCopySha !== null && $trackedRemoteSha !== null && hash_equals($workingCopySha, $trackedRemoteSha) && !hash_equals($remoteSha, $trackedRemoteSha)) {
            $remoteSha = $trackedRemoteSha;
            $commit = ['sha' => $remoteSha, 'commit' => ['message' => 'Current Git deployment synchronized with the updater.']];
        }
        $state = Database::getInstance()->query('SELECT deployed_commit, deployed_version FROM system_update_state WHERE id = 1')->fetch();
        $localSha = (string) ($state['deployed_commit'] ?? '');
        // A developer may deploy this checkout with Git instead of the web
        // updater. In that case the files are already current, so synchronize
        // the updater state instead of offering to install the same commit.
        if ($workingCopySha !== null && hash_equals($workingCopySha, $remoteSha) && !hash_equals($localSha, $remoteSha)) {
            $localSha = $remoteSha;
            self::saveState($localSha, (string) ($state['deployed_version'] ?: self::version()));
        }
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
        self::writeProgress(2, 'Checking the available update...');
        $lock = fopen(STORAGE_PATH . '/cache/system-update.lock', 'c+');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) throw new RuntimeException('Another update is already running.');
        $work = STORAGE_PATH . '/cache/update_' . bin2hex(random_bytes(6));
        $backup = null; $installed = [];
        $status = self::status();
        $oldVersion = $status['current_version'];
        try {
            if (!$status['update_available']) {
                self::writeProgress(100, 'The system is already up to date.');
                return ['updated' => false, 'message' => 'The system is already up to date.'];
            }
            if (!$status['version_ready']) throw new RuntimeException('Publish a higher version notification for this GitHub commit before installing it.');
            if (!$status['deployment_writable']) throw new RuntimeException('PHP cannot write the application directory on this hosting account.');
            if (!class_exists('ZipArchive')) throw new RuntimeException('The PHP ZIP extension is required.');
            self::baselineCurrentMigrations();
            self::writeProgress(12, 'Preparing the update workspace...');
            mkdir($work, 0750, true);
            $archive = $work . '/github-update.zip';
            self::writeProgress(25, 'Downloading the update securely...');
            self::download('/zipball/' . $status['remote_sha'], $archive);
            self::writeProgress(42, 'Verifying and extracting the update...');
            $source = self::extractAndLocateRoot($archive, $work . '/source');
            $newVersion = $status['new_version'];

            self::writeProgress(48, 'Checking every installation destination...');
            self::assertInstallable($source);

            $backup = STORAGE_PATH . '/backups/' . date('Ymd_His') . '_before_v' . $newVersion . '.zip';
            self::writeProgress(55, 'Backing up the current application...');
            self::setMaintenance(true);
            $installed = self::backupFiles($source, $backup);
            self::writeProgress(68, 'Installing the new application files...');
            self::installArchive($source);
            file_put_contents(BASE_PATH . '/VERSION', $newVersion . PHP_EOL, LOCK_EX);
            self::writeProgress(82, 'Applying database migrations...');
            $migrations = self::runNewMigrations();
            self::writeProgress(92, 'Running the system health check...');
            self::healthCheck();
            self::saveState($status['remote_sha'], $newVersion);
            self::publish($developerId, $newVersion, $status['remote_message'], $status['remote_sha']);
            self::logDeployment($developerId, $oldVersion, $newVersion, $status['local_sha'], $status['remote_sha'], 'success', $status['remote_message'], $backup);
            self::setMaintenance(false);
            self::removeTree($work);
            self::writeProgress(100, "HRMS {$newVersion} was installed successfully.");
            return ['updated' => true, 'version' => $newVersion, 'sha' => $status['remote_sha'], 'migrations' => $migrations,
                'message' => "HRMS {$newVersion} was installed successfully."];
        } catch (Throwable $e) {
            self::writeProgress(100, 'Update failed. Restoring the previous application files.');
            if ($backup && is_file($backup)) self::restoreFiles($backup, $installed);
            self::logDeployment($developerId, $oldVersion, null, $status['local_sha'] ?? null, $status['remote_sha'] ?? null, 'failed', $e->getMessage(), $backup);
            self::setMaintenance(false); self::removeTree($work); throw $e;
        } finally {
            flock($lock, LOCK_UN); fclose($lock);
        }
    }

    private static function githubJson(string $path): array
    {
        self::ensureDirectories();
        $cache = STORAGE_PATH . '/cache/github-' . hash('sha256', $path) . '.json';
        if (is_file($cache) && filemtime($cache) >= time() - 300) {
            $cached = json_decode((string) file_get_contents($cache), true);
            if (is_array($cached)) return $cached;
        }
        $temp = tempnam(STORAGE_PATH . '/cache', 'hrms-gh-');
        if ($temp === false || $temp === '') {
            throw new RuntimeException('Unable to create a temporary GitHub response file.');
        }
        try {
            self::download($path, $temp);
            $contents = file_get_contents($temp);
            if ($contents === false || $contents === '') throw new RuntimeException('GitHub returned an empty response.');
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            file_put_contents($cache, $contents, LOCK_EX);
            return $decoded;
        }
        catch (Throwable $error) {
            // A stale successful response is safer than repeatedly exhausting
            // the GitHub quota while rendering Developer navigation.
            if (is_file($cache)) {
                $cached = json_decode((string) file_get_contents($cache), true);
                if (is_array($cached)) return $cached;
            }
            if ($path === '/commits/' . self::BRANCH && ($fallback = self::gitRemoteCommit())) {
                file_put_contents($cache, json_encode($fallback, JSON_UNESCAPED_SLASHES), LOCK_EX);
                return $fallback;
            }
            throw $error;
        } finally { @unlink($temp); }
    }

    private static function download(string $path, string $destination): void
    {
        if (!extension_loaded('curl')) throw new RuntimeException('PHP cURL is required.');
        if ($destination === '') throw new RuntimeException('The update download destination is invalid.');
        $directory = dirname($destination);
        if (!is_dir($directory) || !is_writable($directory)) throw new RuntimeException('The updater cache directory is not writable.');
        $handle = fopen($destination, 'wb'); if (!$handle) throw new RuntimeException('Unable to create update download.');
        $headers = ['Accept: application/vnd.github+json', 'X-GitHub-Api-Version: 2022-11-28'];
        $token = trim((string) getenv('HRMS_GITHUB_TOKEN')); if ($token !== '') $headers[] = 'Authorization: Bearer ' . $token;
        $url = 'https://api.github.com/repos/' . GITHUB_REPOSITORY . $path;
        if ($token === '' && str_starts_with($path, '/zipball/')) {
            $url = 'https://codeload.github.com/' . GITHUB_REPOSITORY . '/zip/' . rawurlencode(substr($path, 9));
            $headers = [];
        }
        $responseHeaders = [];
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_FILE => $handle, CURLOPT_FOLLOWLOCATION => true, CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 180, CURLOPT_HTTPHEADER => $headers, CURLOPT_USERAGENT => 'HRMS-Portable-Updater/1.1',
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders): int {
                if (str_contains($line, ':')) { [$name, $value] = explode(':', $line, 2); $responseHeaders[strtolower(trim($name))] = trim($value); }
                return strlen($line);
            }]);
        $ok = curl_exec($curl); $code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE); $error = curl_error($curl);
        curl_close($curl); fclose($handle);
        if (!$ok || $code < 200 || $code >= 300) {
            @unlink($destination);
            if ($code === 403 && isset($responseHeaders['x-ratelimit-reset'])) {
                $reset = date('g:i A', (int) $responseHeaders['x-ratelimit-reset']);
                throw new RuntimeException("GitHub API limit reached. Try again after {$reset}, or configure HRMS_GITHUB_TOKEN.");
            }
            throw new RuntimeException($error ?: "GitHub download failed with HTTP {$code}.");
        }
    }

    public static function progress(): array
    {
        self::ensureDirectories();
        $file = STORAGE_PATH . '/cache/system-update-progress.json';
        if (!is_file($file)) return ['percent' => 0, 'message' => 'Waiting for the update to start...'];
        $progress = json_decode((string) file_get_contents($file), true);
        return is_array($progress) ? $progress : ['percent' => 0, 'message' => 'Preparing the update...'];
    }

    private static function gitRemoteCommit(): ?array
    {
        $sha = self::gitCommit('refs/remotes/origin/' . self::BRANCH);
        return $sha === null ? null : ['sha' => $sha, 'commit' => ['message' => 'Cached remote branch status retrieved through Git.']];
    }

    private static function gitCommit(string $revision): ?string
    {
        if (!is_dir(BASE_PATH . '/.git') || !function_exists('proc_open')) return null;
        $command = ['git', '-c', 'safe.directory=' . BASE_PATH, 'rev-parse', $revision];
        $process = @proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
        if (!is_resource($process)) return null;
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        if (proc_close($process) !== 0 || !preg_match('/^([a-f0-9]{40})(?:\s|$)/', trim((string) $output), $match)) return null;
        return $match[1];
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

    private static function backupFiles(string $source, string $backupPath): array
    {
        $backup = new ZipArchive(); if ($backup->open($backupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Unable to create file backup.');
        $installed = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($source) + 1));
            if ($item->isDir() || self::preserved($relative) || self::legacyUpdater($relative, $item->getPathname())) continue;
            $target = BASE_PATH . '/' . $relative;
            if (is_file($target)) $backup->addFile($target, $relative); else $installed[] = $relative;
        }
        $backup->addFromString('.new-files.json', json_encode($installed)); $backup->close(); return $installed;
    }

    private static function installArchive(string $source): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($source) + 1));
            if (self::preserved($relative) || self::legacyUpdater($relative, $item->getPathname())) continue;
            $target = BASE_PATH . '/' . $relative;
            if ($item->isDir()) { if (!is_dir($target) && !mkdir($target, 0755, true)) throw new RuntimeException("Unable to create {$relative}."); continue; }
            $directory = dirname($target); if (!is_dir($directory) && !mkdir($directory, 0755, true)) throw new RuntimeException("Unable to create the directory for {$relative}.");
            $temporary = $target . '.update-' . bin2hex(random_bytes(3));
            if (!copy($item->getPathname(), $temporary)) throw new RuntimeException("Unable to prepare {$relative}.");
            if (is_file($target) && is_writable($target)) {
                $installedOk = copy($temporary, $target); @unlink($temporary);
            } elseif (is_writable($directory)) {
                $installedOk = rename($temporary, $target);
            } else {
                @unlink($temporary); $installedOk = false;
            }
            if (!$installedOk) throw new RuntimeException("Unable to install {$relative}.");
        }
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

    private static function assertInstallable(string $source): void
    {
        $blocked = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($iterator as $item) {
            if (!$item->isFile()) continue;
            $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($source) + 1));
            if (self::preserved($relative) || self::legacyUpdater($relative, $item->getPathname())) continue;
            $target = BASE_PATH . '/' . $relative;
            $parent = dirname($target);
            while (!is_dir($parent) && $parent !== BASE_PATH) $parent = dirname($parent);
            if (is_file($target)) {
                if (!is_writable($target) && !is_writable(dirname($target))) $blocked[] = $relative;
            } elseif (!is_writable($parent)) {
                $blocked[] = $relative;
            }
            if (count($blocked) >= 8) break;
        }
        if ($blocked) throw new RuntimeException('Installation permission check failed for: ' . implode(', ', $blocked));
    }

    private static function legacyUpdater(string $relative, string $sourcePath): bool
    {
        if ($relative !== 'core/PortableUpdater.php') return false;
        $incoming = (string) @file_get_contents($sourcePath);
        // Prevent an older GitHub package from overwriting the hardened
        // installer and recreating a permanent update-failure loop.
        return !str_contains($incoming, 'private static function assertInstallable');
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
    private static function writeProgress(int $percent, string $message): void
    { self::ensureDirectories(); file_put_contents(STORAGE_PATH . '/cache/system-update-progress.json', json_encode(['percent'=>$percent,'message'=>$message,'updated_at'=>date(DATE_ATOM)]), LOCK_EX); }
    private static function removeTree(string $dir): void
    { if (!is_dir($dir)) return; $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST); foreach($it as $item) $item->isDir()?@rmdir($item->getPathname()):@unlink($item->getPathname()); @rmdir($dir); }
}
