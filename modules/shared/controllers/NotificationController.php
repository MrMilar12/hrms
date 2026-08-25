<?php

class NotificationController extends Controller
{
    public function markRead(): void
    {
        Auth::requireLogin();
        $this->requireCsrf();
        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        if ($notificationId < 1) {
            $this->json(['success' => false, 'error' => 'Invalid notification.'], 422);
            return;
        }
        $stmt = Database::getInstance()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ? AND is_read = 0');
        $stmt->execute([$notificationId, Auth::userId()]);
        $this->json(['success' => true, 'updated' => $stmt->rowCount()]);
    }
}
