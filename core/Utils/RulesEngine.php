<?php

declare(strict_types=1);

namespace App\Utils;

class RulesEngine
{
    /**
     * Validate placement of a new guest in a room.
     *
     * @param array $room The room data.
     * @param array $newGuest The guest being placed.
     * @param array $existingGuests List of guests already in the room.
     * @return array List of error messages, empty if valid.
     */
    public static function validatePlacement(array $room, array $newGuest, array $existingGuests): array
    {
        $errors = [];

        // 1. Capacity check
        if (count($existingGuests) >= ($room['places'] ?? 1)) {
            $errors[] = "В номере нет свободных мест.";
            return $errors;
        }

        // 2. Gender check (General rule: no mixed genders unless family)
        if (!empty($existingGuests) && $newGuest['is_family'] !== 'yes') {
            foreach ($existingGuests as $guest) {
                if ($guest['gender'] !== $newGuest['gender']) {
                    $errors[] = "Запрещено подселение мужчины к женщине (за исключением семейных пар).";
                    break;
                }
            }
        }

        // 3. Age restrictions (Room specific)
        if (isset($room['min_age']) && $newGuest['age'] < $room['min_age']) {
            $errors[] = "Возраст гостя ({$newGuest['age']}) меньше минимально допустимого для этого номера ({$room['min_age']}).";
        }

        return $errors;
    }
}
