<?php
class Notifier {
    private static $logFile = __DIR__ . '/../data/notifications.log';

    public static function notify($userId, $message, $type = 'info') {
        $users = Storage::read('users');
        $targetUser = null;
        foreach ($users as $u) {
            if ($u['id'] === $userId) {
                $targetUser = $u;
                break;
            }
        }

        if (!$targetUser) return;

        $entry = sprintf(
            "[%s] To: %s (%s) | Type: %s | Message: %s\n",
            date('Y-m-d H:i:s'),
            $targetUser['name'],
            $targetUser['username'],
            $type,
            $message
        );

        if (!is_dir(dirname(self::$logFile))) {
            mkdir(dirname(self::$logFile), 0777, true);
        }

        file_put_contents(self::$logFile, $entry, FILE_APPEND);
    }
}
