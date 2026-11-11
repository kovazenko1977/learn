<?php
/**
 * Email notification sender for WP Event Booking.
 */

if (!defined('ABSPATH') && !defined('WPEB_STANDALONE')) {
    exit;
}

class WPEB_Mailer {
    public static function send_booking_email($bookingData) {
        $db = WPEB_Database::instance();
        $enabled = $db->get_setting('email_enabled', '1');
        if ($enabled !== '1' && $enabled !== 1 && $enabled !== true) {
            return false;
        }

        $adminEmail = $db->get_setting('email_to', get_option('admin_email', 'admin@example.com'));
        $fromName = $db->get_setting('email_from_name', get_bloginfo('name', 'WP Event Booking'));
        $subject = $db->get_setting('email_subject', 'Подтверждение записи на мероприятие #{code}');
        $template = $db->get_setting('email_template', '');

        $subject = self::render_placeholders($subject, $bookingData);
        $body = self::render_placeholders($template, $bookingData);

        // Send confirmation to client
        $toClient = $bookingData['client_email'];
        self::mail($toClient, $subject, $body, $fromName);

        // Send alert to admin if different
        if (!empty($adminEmail) && $adminEmail !== $toClient) {
            $adminSubject = "Новая бронь: " . ($bookingData['event_title'] ?? '') . " [#" . ($bookingData['booking_code'] ?? '') . "]";
            $adminBody = "Поступила новая запись на мероприятие:\n\n" . $body;
            self::mail($adminEmail, $adminSubject, $adminBody, $fromName);
        }

        return true;
    }

    public static function render_placeholders($template, $data) {
        $customText = '';
        if (!empty($data['custom_fields'])) {
            $cf = is_array($data['custom_fields']) ? $data['custom_fields'] : json_decode($data['custom_fields'], true);
            if (is_array($cf)) {
                $lines = [];
                foreach ($cf as $k => $v) {
                    if (is_array($v)) $v = implode(', ', $v);
                    $lines[] = "$k: $v";
                }
                $customText = implode("\n", $lines);
            }
        }

        $replacements = [
            '{name}' => $data['client_name'] ?? '',
            '{email}' => $data['client_email'] ?? '',
            '{phone}' => $data['client_phone'] ?? '',
            '{event_title}' => $data['event_title'] ?? '',
            '{date}' => $data['slot_date'] ?? '',
            '{time}' => $data['slot_time'] ?? '',
            '{tickets}' => $data['ticket_count'] ?? 1,
            '{code}' => $data['booking_code'] ?? '',
            '{price}' => $data['price'] ?? '',
            '{location}' => $data['location'] ?? '',
            '{custom_fields}' => $customText
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private static function mail($to, $subject, $body, $fromName = '') {
        $headers = [];
        if (!empty($fromName)) {
            $headers[] = 'From: ' . $fromName . ' <noreply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . '>';
        }

        if (function_exists('wp_mail')) {
            return wp_mail($to, $subject, $body, $headers);
        } else {
            return @mail($to, $subject, $body, implode("\r\n", $headers));
        }
    }
}
