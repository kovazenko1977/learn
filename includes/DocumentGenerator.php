<?php
/**
 * DocumentGenerator - Генерация ваучеров и счетов
 */
class DocumentGenerator {
    /**
     * Сгенерировать HTML ваучера
     */
    public static function generateVoucher($booking) {
        return "
        <div style='font-family: sans-serif; border: 2px solid #000; padding: 20px; max-width: 600px;'>
            <h1>ПОДТВЕРЖДЕНИЕ БРОНИРОВАНИЯ #{$booking['id']}</h1>
            <p><strong>Гость:</strong> {$booking['guest']['name']}</p>
            <p><strong>Заезд:</strong> {$booking['check_in']}</p>
            <p><strong>Программа:</strong> {$booking['program_id']}</p>
            <p><strong>Номер:</strong> {$booking['room_id']}</p>
            <hr>
            <p>Ждем вас в нашем санатории!</p>
            <p style='font-size: 0.8em;'>© HIS Booking System</p>
        </div>";
    }

    /**
     * Сгенерировать HTML счета
     */
    public static function generateInvoice($booking, $amount) {
        return "
        <div style='font-family: sans-serif; padding: 20px; max-width: 600px; border: 1px solid #ccc;'>
            <h2>СЧЕТ НА ОПЛАТУ №{$booking['id']}/INV</h2>
            <p>Плательщик: {$booking['guest']['name']}</p>
            <p>Сумма к оплате: <strong>{$amount} руб.</strong></p>
            <hr>
            <p>Назначение: Оплата бронирования путевки</p>
            <p>Реквизиты: ООО 'Санаторий-Курорт', ИНН 1234567890</p>
        </div>";
    }
}
