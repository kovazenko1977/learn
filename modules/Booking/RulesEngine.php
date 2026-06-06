<?php

declare(strict_types=1);

namespace App\Modules\Booking;

class RulesEngine
{
    public function validatePlacement(array $room, array $guest, array $existingGuests): array
    {
        $errors = [];

        // 1. Gender Rule: No mixed genders in one room unless family
        if (!$guest['is_family'] && !empty($existingGuests)) {
            foreach ($existingGuests as $existing) {
                if ($existing['gender'] !== $guest['gender']) {
                    $errors[] = "Нарушение правила подселения: запрещено размещение разных полов в одном номере (кроме семейных пар).";
                    break;
                }
            }
        }

        // 2. Age Rule: Check if room allows children
        if ($guest['age'] < 18 && !($room['allows_children'] ?? true)) {
            $errors[] = "Данная категория номера не предполагает размещение детей.";
        }

        // 3. Capacity Rule
        if (count($existingGuests) >= ($room['places'] ?? 1)) {
            $errors[] = "В номере нет свободных мест.";
        }

        return $errors;
    }
}
