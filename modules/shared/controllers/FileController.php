<?php
// Streams protected uploaded files (photos, task attachments) after a permission check.
// All uploads are served through here so permission checks are centralized rather than
// relying on web-server config alone.

class FileController extends Controller
{
    public function photo(string $id): void
    {
        Auth::requireLogin();

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM employee_photos WHERE id = ?');
        $stmt->execute([(int) $id]);
        $photo = $stmt->fetch();

        if (!$photo) {
            http_response_code(404);
            echo 'Not found.';
            return;
        }

        if ((int) $photo['employee_id'] !== Auth::employeeId() && !Auth::can('employee.view')) {
            http_response_code(403);
            echo 'Forbidden.';
            return;
        }

        $this->stream($photo['file_path']);
    }

    public function taskAttachment(string $id): void
    {
        Auth::requirePermission('task.view');

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT att.*
             FROM task_attachments att
             WHERE att.id = ?'
        );
        $stmt->execute([(int) $id]);
        $attachment = $stmt->fetch();

        if (!$attachment) {
            http_response_code(404);
            echo 'Not found.';
            return;
        }

        if (!Auth::can('task.create')) {
            $access = $pdo->prepare(
                'SELECT 1 FROM task_assignments WHERE task_id = ? AND employee_id = ? LIMIT 1'
            );
            $access->execute([(int) $attachment['task_id'], (int) Auth::employeeId()]);
            if (!$access->fetchColumn()) {
                http_response_code(403);
                echo 'Forbidden.';
                return;
            }
        }

        $this->stream($attachment['file_path']);
    }

    public function accomplishmentAttachment(string $id): void
    {
        Auth::requirePermission('accomplishment.create');

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT att.*, a.employee_id FROM accomplishment_attachments att
             JOIN accomplishments a ON a.id = att.accomplishment_id
             WHERE att.id = ?'
        );
        $stmt->execute([(int) $id]);
        $attachment = $stmt->fetch();

        if (!$attachment) {
            http_response_code(404);
            echo 'Not found.';
            return;
        }

        if ((int) $attachment['employee_id'] !== Auth::employeeId() && !Auth::can('accomplishment.view_all')) {
            http_response_code(403);
            echo 'Forbidden.';
            return;
        }

        $this->stream($attachment['file_path']);
    }

    private function stream(string $path): void
    {
        if (!is_file($path)) {
            http_response_code(404);
            echo 'File missing.';
            return;
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="attachment"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=3600');
        readfile($path);
        exit;
    }
}
