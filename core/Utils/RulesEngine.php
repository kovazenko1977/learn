<?php

declare(strict_types=1);

namespace App\Utils;

class RulesEngine
{
    /**
     * Валидация возможности размещения гостя в номере.
     *
     * @param array $room Данные номера
     * @param array $newGuest Данные нового гостя (gender, age, is_family)
     * @param array $existingGuests Список уже проживающих гостей в этом номере
     * @return array Список ошибок валидации
     */
    public static function validatePlacement(array $room, array $newGuest, array $existingGuests): array
    {
        $errors = [];

        // 1. Правило пола (Запрет подселения мужчины к женщине, если не семья)
        $isFamily = ($newGuest['is_family'] ?? 'no') === 'yes';

        if (!$isFamily && !empty($existingGuests)) {
            foreach ($existingGuests as $guest) {
                if ($guest['gender'] !== $newGuest['gender']) {
                    $errors[] = "Нарушение правила пола: в данном номере уже проживает гость другого пола (" . ($guest['gender'] === 'male' ? 'Мужчина' : 'Женщина') . ").";
                    break;
                }
            }
        }

        // 2. Возрастные ограничения
        $minAge = (int)($room['min_age'] ?? 0);
        $guestAge = (int)($newGuest['age'] ?? 18);

        if ($guestAge < $minAge) {
            $errors[] = "Возрастное ограничение: минимальный возраст для этого номера — $minAge лет.";
        }

        // 3. Специфические правила номера (например, только для ветеранов, только для сотрудников и т.д.)
        if (isset($room['required_status']) && $room['required_status'] !== 'any') {
            if (($newGuest['status'] ?? '') !== $room['required_status']) {
                $errors[] = "Данный номер предназначен только для категории: " . $room['required_status'];
            }
        }

        return $errors;
    }
}
