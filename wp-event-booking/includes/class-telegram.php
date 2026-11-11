<?php
/**
 * Telegram notification sender for WP Event Booking.
 */

if (!defined('ABSPATH') && !defined('WPEB_STANDALONE')) {
    exit;
}

class WPEB_Telegram {
    public static function send_booking_notification($bookingData) {
        $db = WPEB_Database::instance();
        $enabled = $db->get_setting('telegram_enabled', '0');
        if ($enabled !== '1' && $enabled !== 1 && $enabled !== true) {
            return false;
        }

        $token = trim($db->get_setting('telegram_token', ''));
        $chatId = trim($db->get_setting('telegram_chat_id', ''));
        if (empty($token) || empty($chatId)) {
            return false;
        }

        $template = $db->get_setting('telegram_template', '');
        if (empty($template)) {
            $template = "⚡ Новая запись на мероприятие!\n\n📅 Дата: {date} в {time}\n🎪 Событие: {event_title}\n👤 Клиент: {name}\n📞 Телефон: {phone}\n📧 Email: {email}\n🎟 Билетов: {tickets}\n🔖 Код: #{code}";
        }

        $message = self::render_template($template, $bookingData);
        return self::send_message($token, $chatId, $message);
    }

    public static function render_template($template, $data) {
        $customText = '';
        if (!empty($data['custom_fields'])) {
            $cf = is_array($data['custom_fields']) ? $data['custom_fields'] : json_decode($data['custom_fields'], true);
            if (is_array($cf)) {
                $lines = [];
                foreach ($cf as $k => $v) {
                    if (is_array($v)) $v = implode(', ', $v);
                    $lines[] = "• $k: $v";
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

    public static function send_message($token, $chatId, $message) {
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $payload = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['success' => false, 'error' => $err];
        }

        $decoded = json_decode($res, true);
        if (!empty($decoded['ok'])) {
            return ['success' => true, 'response' => $decoded];
        }

        return ['success' => false, 'error' => $decoded['description'] ?? 'Telegram API error', 'response' => $decoded];
    }
}
