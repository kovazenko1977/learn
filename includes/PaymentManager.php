<?php
require_once __DIR__ . '/Storage.php';

/**
 * PaymentManager - Управление платежами
 */
class PaymentManager {
    private static $file = 'payments_data';

    /**
     * Создать запись о платеже
     */
    public static function createTransaction($bookingId, $amount, $method = 'online') {
        $payments = Storage::read(self::$file);
        $id = uniqid('PAY_');
        $transaction = [
            'id' => $id,
            'booking_id' => $bookingId,
            'amount' => $amount,
            'method' => $method,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        $payments[$id] = $transaction;
        Storage::write(self::$file, $payments);
        return $id;
    }

    /**
     * Подтвердить платеж
     */
    public static function confirmPayment($transactionId) {
        $payments = Storage::read(self::$file);
        if (isset($payments[$transactionId])) {
            $payments[$transactionId]['status'] = 'completed';
            $payments[$transactionId]['paid_at'] = date('Y-m-d H:i:s');
            return Storage::write(self::$file, $payments);
        }
        return false;
    }

    /**
     * Получить платежи по бронированию
     */
    public static function getByBooking($bookingId) {
        $payments = Storage::read(self::$file);
        return array_filter($payments, fn($p) => $p['booking_id'] == $bookingId);
    }
}
