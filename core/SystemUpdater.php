<?php

class SystemUpdater
{
    private const BRANCH = 'main';
    private const REMOTE = 'origin';

    public static function status(): array
    {
        $localSha = trim(self::run(['git', 'rev-parse', 'HEAD'])['output']);
        $remoteResult = self::run(['git', 'ls-remote', self::REMOTE, 'refs/heads/' . self::BRANCH], false, 20);
        if (!$remoteResult['success']) throw new RuntimeException('Unable to check GitHub: ' . $remoteResult['output']);
        $remoteSha = preg_split('/\s+/', trim($remoteResult['output']))[0] ?? '';
        if (!preg_match('/^[a-f0-9]{40}$/', $remoteSha)) throw new RuntimeException('GitHub returned an invalid commit identifier.');

        $dirty = trim(self::run(['git', 'status', '--porcelain'])['output']) !== '';
        return [
            'current_version' => self::localVersion(),
            'local_sha' => $localSha,
            'remote_sha' => $remoteSha,
            'update_available' => !hash_equals($localSha, $remoteSha),
            'working_tree_clean' => !$dirty,
            'deployment_writable' => is_writable(BASE_PATH) && is_writable(BASE_PATH . '/.git'),
            'branch' => self::BRANCH,
            'repository' => GITHUB_REPOSITORY,
        ];
    }

    public static function apply(int $developerId): array
    {
        self::ensureStorage();
        $lockHandle = fopen(STORAGE_PATH . '/cache/system-update.lock', 'c+');
        if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) throw new RuntimeException('Another system update is already running.');

        $oldSha = trim(self::run(['git', 'rev-parse', 'HEAD'])['output']);
        $oldVersion = self::localVersion();
        $backup = null;
        try {
            if (!is_writable(BASE_PATH) || !is_writable(BASE_PATH . '/.git')) {
                throw new RuntimeException('Update cancelled: the web server cannot write the application or Git repository. Configure a privileged deployment worker instead of making the web application writable.');
            }
            if (trim(self::run(['git', 'status', '--porcelain'])['output']) !== '') {
                throw new RuntimeException('Update cancelled: the server contains uncommitted files. Commit and push them first.');
            }

            self::ensureMigrationTableAndBaseline();
            $backup = self::backup($oldSha, $oldVersion);
            self::setMaintenance(true, 'Installing a new HRMS version. Please try again shortly.');

            self::mustRun(['git', 'fetch', '--prune', self::REMOTE, self::BRANCH], 120);
            $remoteSha = trim(self::run(['git', 'rev-parse', self::REMOTE . '/' . self::BRANCH])['output']);
            if (hash_equals($oldSha, $remoteSha)) {
                self::setMaintenance(false);
                return ['updated' => false, 'message' => 'The system is already up to date.'];
            }
            $ancestor = self::run(['git', 'merge-base', '--is-ancestor', $oldSha, $remoteSha], false);
            if (!$ancestor['success']) throw new RuntimeException('Update is not a fast-forward. Manual deployment review is required.');

            $notes = trim(self::run(['git', 'log', '--pretty=format:%s', $oldSha . '..' . $remoteSha])['output']);
            self::mustRun(['git', 'merge', '--ff-only', $remoteSha], 120);

            $newVersion = self::localVersion();
            if ($newVersion === $oldVersion) throw new RuntimeException('The incoming update did not change the VERSION file.');
            if (!preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $newVersion)) {
                throw new RuntimeException('The incoming VERSION file is not a valid semantic version.');
            }

            self::installDependenciesIfChanged($oldSha, $remoteSha);
            $migrations = self::runPendingMigrations();
            self::healthCheck();
            self::publishDeployment($developerId, $newVersion, $notes, $remoteSha);
            self::recordDeployment($developerId, $oldVersion, $newVersion, $oldSha, $remoteSha, 'success', $notes, $backup);
            self::setMaintenance(false);
            return ['updated' => true, 'version' => $newVersion, 'sha' => $remoteSha, 'migrations' => $migrations,
                'message' => "HRMS {$newVersion} was installed successfully."];
        } catch (Throwable $e) {
            $failedSha = trim(self::run(['git', 'rev-parse', 'HEAD'], false)['output']);
            if ($failedSha !== '' && $failedSha !== $oldSha) self::run(['git', 'reset', '--hard', $oldSha], false, 120);
            if ($backup && is_file($backup['database'])) self::restoreDatabase($backup['database']);
            self::recordDeployment($developerId, $oldVersion, null, $oldSha, $failedSha ?: null, 'failed', $e->getMessage(), $backup);
            self::setMaintenance(false);
            throw $e;
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    public static function history(): array
    {
        self::ensureDeploymentTable();
        return Database::getInstance()->query(
            'SELECT d.*, u.username FROM system_deployments d LEFT JOIN users u ON u.id = d.developer_id
             ORDER BY d.started_at DESC, d.id DESC LIMIT 50'
        )->fetchAll();
    }

    private static function localVersion(): string
    {
        $path = BASE_PATH . '/VERSION';
        return is_file($path) ? trim((string) file_get_contents($path)) : APP_VERSION;
    }

    private static function ensureStorage(): void
    {
        foreach ([STORAGE_PATH . '/cache', STORAGE_PATH . '/backups'] as $directory) {
            if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
                throw new RuntimeException('Unable to create updater storage.');
            }
        }
    }

    private static function setMaintenance(bool $enabled, string $message = ''): void
    {
        $path = STORAGE_PATH . '/cache/maintenance.json';
        if (!$enabled) { if (is_file($path)) unlink($path); return; }
        file_put_contents($path, json_encode(['message' => $message, 'started_at' => date(DATE_ATOM)], JSON_PRETTY_PRINT), LOCK_EX);
    }

    private static function backup(string $sha, string $version): array
    {
        $stamp = date('Ymd_His');
        $base = STORAGE_PATH . '/backups/' . $stamp . '_v' . preg_replace('/[^0-9A-Za-z._-]/', '_', $version);
        $archive = $base . '_files.tar';
        self::mustRun(['git', 'archive', '--format=tar', '--output=' . $archive, $sha], 120);

        $config = require CONFIG_PATH . '/app.php';
        $db = $config['db'];
        $dump = $base . '_database.sql';
        $command = [dirname(PHP_BINARY) . '/mysqldump', '--host=' . $db['host'], '--port=' . $db['port'], '--user=' . $db['user'],
            '--single-transaction', '--routines', '--events', '--triggers', '--result-file=' . $dump, $db['name']];
        if ($db['pass'] !== '') array_splice($command, 4, 0, ['--password=' . $db['pass']]);
        self::mustRun($command, 180);
        return ['files' => $archive, 'database' => $dump];
    }

    private static function restoreDatabase(string $dump): void
    {
        $config = require CONFIG_PATH . '/app.php'; $db = $config['db'];
        $command = [dirname(PHP_BINARY) . '/mysql', '--host=' . $db['host'], '--port=' . $db['port'], '--user=' . $db['user'], $db['name']];
        if ($db['pass'] !== '') array_splice($command, 4, 0, ['--password=' . $db['pass']]);
        self::run($command, false, 180, $dump);
    }

    private static function ensureMigrationTableAndBaseline(): void
    {
        $pdo = Database::getInstance(); self::ensureDeploymentTable();
        $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (migration VARCHAR(190) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
        $insert = $pdo->prepare('INSERT IGNORE INTO schema_migrations (migration) VALUES (?)');
        foreach (glob(BASE_PATH . '/database/migrations/*.sql') ?: [] as $file) $insert->execute([basename($file)]);
    }

    private static function runPendingMigrations(): array
    {
        $pdo = Database::getInstance();
        $applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
        $ran = []; $insert = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');
        $files = glob(BASE_PATH . '/database/migrations/*.sql') ?: []; sort($files, SORT_NATURAL);
        foreach ($files as $file) {
            $name = basename($file); if (in_array($name, $applied, true)) continue;
            $sql = trim((string) file_get_contents($file));
            if ($sql !== '') self::runMysqlFile($file);
            $insert->execute([$name]); $ran[] = $name;
        }
        return $ran;
    }

    private static function installDependenciesIfChanged(string $oldSha, string $newSha): void
    {
        $changed = self::run(['git', 'diff', '--name-only', $oldSha, $newSha, '--', 'composer.lock'])['output'];
        if (trim($changed) === '') return;
        self::mustRun(['/usr/bin/env', 'composer', 'install', '--no-dev', '--no-interaction', '--prefer-dist', '--optimize-autoloader'], 300);
    }

    private static function runMysqlFile(string $file): void
    {
        $config = require CONFIG_PATH . '/app.php'; $db = $config['db'];
        $command = [dirname(PHP_BINARY) . '/mysql', '--host=' . $db['host'], '--port=' . $db['port'], '--user=' . $db['user'], $db['name']];
        if ($db['pass'] !== '') array_splice($command, 4, 0, ['--password=' . $db['pass']]);
        $result = self::run($command, false, 180, $file);
        if (!$result['success']) throw new RuntimeException('Migration ' . basename($file) . ' failed: ' . $result['output']);
    }

    private static function healthCheck(): void
    {
        $pdo = Database::getInstance();
        foreach (['users', 'roles', 'system_releases', 'system_deployments', 'schema_migrations'] as $table) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
            $stmt->execute([$table]); if (!(int) $stmt->fetchColumn()) throw new RuntimeException("Health check failed: {$table} is missing.");
        }
        self::mustRun([PHP_BINARY, '-l', BASE_PATH . '/public/index.php']);
    }

    private static function publishDeployment(int $developerId, string $version, string $notes, string $sha): void
    {
        $notes = $notes !== '' ? $notes : 'System improvements and maintenance updates.';
        $url = 'https://github.com/' . GITHUB_REPOSITORY . '/commit/' . $sha;
        $stmt = Database::getInstance()->prepare(
            'INSERT INTO system_releases (version, title, changes, released_at, is_published, created_by, release_url)
             VALUES (?, ?, ?, NOW(), 1, ?, ?)
             ON DUPLICATE KEY UPDATE title = VALUES(title), changes = VALUES(changes), released_at = NOW(),
                is_published = 1, created_by = VALUES(created_by), release_url = VALUES(release_url)'
        );
        $stmt->execute([$version, 'HRMS Version ' . $version, $notes, $developerId, $url]);
    }

    private static function ensureDeploymentTable(): void
    {
        Database::getInstance()->exec(
            "CREATE TABLE IF NOT EXISTS system_deployments (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, developer_id INT UNSIGNED NULL,
                from_version VARCHAR(30) NULL, to_version VARCHAR(30) NULL,
                from_commit CHAR(40) NULL, to_commit CHAR(40) NULL,
                status ENUM('success','failed') NOT NULL, details TEXT NULL,
                backup_files VARCHAR(500) NULL, backup_database VARCHAR(500) NULL,
                started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (developer_id) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_system_deployments_started (started_at)
            ) ENGINE=InnoDB"
        );
    }

    private static function recordDeployment(int $developerId, ?string $fromVersion, ?string $toVersion, ?string $fromSha,
        ?string $toSha, string $status, string $details, ?array $backup): void
    {
        try {
            self::ensureDeploymentTable();
            $stmt = Database::getInstance()->prepare(
                'INSERT INTO system_deployments (developer_id, from_version, to_version, from_commit, to_commit, status, details, backup_files, backup_database)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$developerId, $fromVersion, $toVersion, $fromSha, $toSha, $status, $details,
                $backup['files'] ?? null, $backup['database'] ?? null]);
        } catch (Throwable $ignored) { error_log('Unable to record system deployment: ' . $ignored->getMessage()); }
    }

    private static function mustRun(array $command, int $timeout = 60): array
    {
        $result = self::run($command, false, $timeout);
        if (!$result['success']) throw new RuntimeException(implode(' ', array_slice($command, 0, 2)) . ' failed: ' . $result['output']);
        return $result;
    }

    private static function run(array $command, bool $throw = true, int $timeout = 60, ?string $stdinFile = null): array
    {
        // Apache runs as `daemon` while this checkout belongs to the deployment
        // owner. Trust only this repository for this one Git process instead of
        // weakening Git's global safe.directory protection.
        if (($command[0] ?? null) === 'git') {
            array_splice($command, 1, 0, ['-c', 'safe.directory=' . BASE_PATH]);
        }
        $descriptors = [0 => $stdinFile ? ['file', $stdinFile, 'r'] : ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open($command, $descriptors, $pipes, BASE_PATH);
        if (!is_resource($process)) {
            if ($throw) throw new RuntimeException('Unable to start deployment command.');
            return ['success' => false, 'output' => 'Unable to start command.'];
        }
        if (!$stdinFile) fclose($pipes[0]);
        stream_set_blocking($pipes[1], false); stream_set_blocking($pipes[2], false);
        $output = ''; $started = time();
        do {
            $output .= stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
            $state = proc_get_status($process);
            if (!$state['running']) break;
            if (time() - $started > $timeout) { proc_terminate($process, 9); $output .= "\nCommand timed out."; break; }
            usleep(100000);
        } while (true);
        $output .= stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $exit = proc_close($process);
        if ($exit === -1 && isset($state['exitcode']) && $state['exitcode'] >= 0) $exit = $state['exitcode'];
        $result = ['success' => $exit === 0, 'output' => trim($output), 'exit_code' => $exit];
        if ($throw && !$result['success']) throw new RuntimeException($result['output'] ?: 'Deployment command failed.');
        return $result;
    }
}
