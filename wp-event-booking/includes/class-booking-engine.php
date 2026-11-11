<?php
/**
 * Booking Engine: manages slot availability, capacity checking, booking validation,
 * reservation creation and ticket confirmation generation.
 */

if (!defined('ABSPATH') && !defined('WPEB_STANDALONE')) {
    exit;
}

class WPEB_Booking_Engine {
    private $db;

    public function __construct() {
        $this->db = WPEB_Database::instance();
    }

    /**
     * Get months available for an event
     */
    public function get_event_months($eventId) {
        $raw = $this->db->get_available_months($eventId);
        $monthsMap = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
            5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
            9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
        ];

        $result = [];
        foreach ($raw as $item) {
            $year = (int)$item['slot_year'];
            $month = (int)$item['slot_month'];
            $result[] = [
                'year' => $year,
                'month' => $month,
                'month_name' => ($monthsMap[$month] ?? "Месяц $month") . ' ' . $year,
                'key' => sprintf('%04d-%02d', $year, $month)
            ];
        }
        return $result;
    }

    /**
     * Get dates available in a given month for an event
     */
    public function get_event_dates($eventId, $year, $month) {
        $dates = $this->db->get_available_dates_for_month($eventId, $year, $month);
        $result = [];
        $daysWeek = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];

        foreach ($dates as $d) {
            $ts = strtotime($d);
            $dayOfWeek = $daysWeek[date('w', $ts)];
            $dayNum = date('j', $ts);
            $result[] = [
                'date' => $d,
                'day_num' => $dayNum,
                'day_of_week' => $dayOfWeek,
                'formatted' => date('d.m.Y', $ts)
            ];
        }
        return $result;
    }

    /**
     * Get available time slots for a given date.
     * Note: Once max_capacity is reached (booked_count >= max_capacity),
     * the slot is completely filtered out from the visitor view.
     */
    public function get_event_time_slots($eventId, $dateStr) {
        $slots = $this->db->get_available_times_for_date($eventId, $dateStr);
        $result = [];

        foreach ($slots as $s) {
            $rem = (int)$s['remaining_capacity'];
            // Strictly exclude saturated slots
            if ($rem <= 0) {
                continue;
            }

            $result[] = [
                'id' => (int)$s['id'],
                'slot_time' => $s['slot_time'],
                'max_capacity' => (int)$s['max_capacity'],
                'booked_count' => (int)$s['booked_count'],
                'remaining' => $rem,
                'badge' => ($rem <= 3) ? "Осталось всего $rem" : "Свободно мест: $rem"
            ];
        }

        return $result;
    }

    /**
     * Validate and create a booking
     */
    public function create_booking($data) {
        $eventId = (int)($data['event_id'] ?? 0);
        $slotId = (int)($data['slot_id'] ?? 0);
        $name = trim($data['client_name'] ?? '');
        $email = trim($data['client_email'] ?? '');
        $phone = trim($data['client_phone'] ?? '');
        $ticketCount = max(1, (int)($data['ticket_count'] ?? 1));
        $customFields = $data['custom_fields'] ?? [];

        // Validation
        if (!$eventId || !$slotId) {
            return ['success' => false, 'error' => 'Пожалуйста, выберите мероприятие, дату и время.'];
        }

        if (empty($name)) {
            return ['success' => false, 'error' => 'Пожалуйста, укажите ваше имя.'];
        }

        if (empty($phone)) {
            return ['success' => false, 'error' => 'Пожалуйста, укажите контактный телефон.'];
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Пожалуйста, укажите корректный адрес эл. почты.'];
        }

        $event = $this->db->get_event($eventId);
        if (!$event) {
            return ['success' => false, 'error' => 'Мероприятие не найдено.'];
        }

        $slot = $this->db->get_slot($slotId);
        if (!$slot || empty($slot['is_active'])) {
            return ['success' => false, 'error' => 'Выбранный слот времени недоступен.'];
        }

        // Capacity check
        $remaining = (int)$slot['max_capacity'] - (int)$slot['booked_count'];
        if ($remaining < $ticketCount) {
            if ($remaining <= 0) {
                return ['success' => false, 'error' => 'К сожалению, все места на выбранное время уже забронированы.'];
            } else {
                return ['success' => false, 'error' => "На выбранное время осталось только {$remaining} мест. Выберите другое количество или другой сеанс."];
            }
        }

        // Validate custom fields
        $fieldsConfig = $this->db->get_form_fields($eventId);
        $cfValues = [];
        foreach ($fieldsConfig as $field) {
            $key = $field['field_key'];
            $val = $customFields[$key] ?? '';
            if (!empty($field['is_required']) && ($val === '' || $val === null || (is_array($val) && empty($val)))) {
                return ['success' => false, 'error' => "Поле «{$field['field_label']}» обязательно для заполнения."];
            }
            $cfValues[$field['field_label']] = $val;
        }

        // Generate unique booking code
        $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

        // Create booking in database
        $bookingId = $this->db->insert_booking([
            'event_id' => $eventId,
            'slot_id' => $slotId,
            'booking_code' => $code,
            'client_name' => $name,
            'client_email' => $email,
            'client_phone' => $phone,
            'ticket_count' => $ticketCount,
            'custom_fields' => json_encode($cfValues, JSON_UNESCAPED_UNICODE),
            'status' => 'confirmed',
            'check_in' => 0,
            'admin_notes' => ''
        ]);

        // Increment slot counter
        $this->db->increment_slot_booked($slotId, $ticketCount);

        // Prepare full payload for notification
        $fullBooking = $this->db->get_booking($bookingId);

        // Send Email & Telegram notifications
        try {
            WPEB_Telegram::send_booking_notification($fullBooking);
        } catch (\Throwable $e) {
            error_log("WPEB Telegram error: " . $e->getMessage());
        }

        try {
            WPEB_Mailer::send_booking_email($fullBooking);
        } catch (\Throwable $e) {
            error_log("WPEB Mailer error: " . $e->getMessage());
        }

        return [
            'success' => true,
            'booking_id' => $bookingId,
            'booking_code' => $code,
            'booking' => $fullBooking,
            'message' => 'Ваша запись успешно подтверждена! Детали отправлены на указанный email.'
        ];
    }
}
