<?php
/**
 * NotificationManager - Симуляция отправки уведомлений
 */
class NotificationManager {
    /**
     * Симуляция отправки Email
     */
    public static function sendEmail($to, $subject, $message) {
        // В реальном приложении здесь был бы вызов mail() или внешнего API (SendGrid, Mailgun)
        $logEntry = "[" . date('Y-m-d H:i:s') . "] EMAIL TO: $to | SUBJECT: $subject\n";
        file_put_contents(__DIR__ . '/../data/notifications_log.txt', $logEntry, FILE_APPEND);
        return true;
    }

    /**
     * Симуляция отправки SMS
     */
    public static function sendSMS($phone, $text) {
        $logEntry = "[" . date('Y-m-d H:i:s') . "] SMS TO: $phone | TEXT: $text\n";
        file_put_contents(__DIR__ . '/../data/notifications_log.txt', $logEntry, FILE_APPEND);
        return true;
    }

    /**
     * Отправить подтверждение бронирования гостю
     */
    public static function notifyBookingConfirmed($booking) {
        $msg = "Здравствуйте, {$booking['guest']['name']}! Ваше бронирование #{$booking['id']} подтверждено.";
        self::sendEmail($booking['guest']['email'], "Бронирование подтверждено", $msg);
        self::sendSMS($booking['guest']['phone'], $msg);
    }
}
