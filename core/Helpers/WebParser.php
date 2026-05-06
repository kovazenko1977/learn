<?php
namespace Sanatorium\Core\Helpers;

use Sanatorium\Core\Database\JsonStore;

class WebParser {
    private $store;

    public function __construct() {
        $dataDir = __DIR__ . '/../data';
        $this->store = new JsonStore($dataDir);
    }

    /**
     * Attempts to parse a website and import found data.
     */
    public function parseAndImport($url) {
        try {
            // Use @ to suppress warnings from file_get_contents (e.g. 404)
            $html = @file_get_contents($url);
            if (!$html) {
                return ['success' => false, 'message' => "Не удалось загрузить сайт $url. Убедитесь, что URL корректен и сервер доступен."];
            }

            $details = [
                'Номера' => 0,
                'Процедуры' => 0,
                'Услуги' => 0,
                'Путевки' => 0
            ];

            // 1. Extract Procedures (looking for words like "Процедура", "Лечение", "Прайс")
            $procedures = $this->extractData($html, ['процедура', 'лечение', 'медицина'], 500, 5000);
            foreach ($procedures as $p) {
                $this->store->save('procedures', [
                    'name' => $p['name'],
                    'price' => $p['price'],
                    'description' => 'Импортировано с ' . $url,
                    'duration' => '30 мин'
                ]);
                $details['Процедуры']++;
            }

            // 2. Extract Services
            $services = $this->extractData($html, ['услуга', 'сервис', 'дополнительно'], 100, 2000);
            foreach ($services as $s) {
                $this->store->save('extra_services', [
                    'name' => $s['name'],
                    'price' => $s['price']
                ]);
                $details['Услуги']++;
            }

            // 3. Extract Rooms (heuristic: "Номер", "Люкс", "Стандарт")
            $rooms = $this->extractData($html, ['номер', 'люкс', 'стандарт', 'апартаменты'], 1500, 15000);
            foreach ($rooms as $r) {
                $this->store->save('rooms', [
                    'room_number' => 'Имп-' . rand(100, 999),
                    'room_class_id' => 1, // Default to first class
                    'price_per_day' => $r['price'],
                    'capacity' => 2,
                    'status' => 'free'
                ]);
                $details['Номера']++;
            }

            // 4. Extract Packages
            $packages = $this->extractData($html, ['путевка', 'пакет', 'тур'], 10000, 100000);
            foreach ($packages as $pkg) {
                $this->store->save('packages', [
                    'name' => $pkg['name'],
                    'base_price' => $pkg['base_price'] ?? $pkg['price'],
                    'duration_days' => 7,
                    'description' => 'Импортированный пакет услуг'
                ]);
                $details['Путевки']++;
            }

            $totalCount = array_sum($details);
            if ($totalCount === 0) {
                // If nothing found, let's add some mock data to show it "works" for the demo
                // but only if the URL is somewhat valid and not a blank page.
                return ['success' => true, 'count' => 0, 'details' => $details, 'message' => 'На сайте не найдено подходящих структур данных.'];
            }

            return [
                'success' => true,
                'count' => $totalCount,
                'details' => $details
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Heuristic data extraction using regex.
     */
    private function extractData($html, $keywords, $minPrice, $maxPrice) {
        $results = [];
        // Look for patterns like "Item Name ... 1200 руб"
        // or "Item Name - 1200"
        foreach ($keywords as $kw) {
            $pattern = '/([A-ZА-Я][^<>]{5,50}?)\s*[-—:]?\s*(\d{2,})\s*(?:руб|р|BYN|\$|€)?/ui';
            if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $name = trim($match[1]);
                    $price = (int)$match[2];

                    // Filter by keyword in name and price range
                    $containsKw = false;
                    foreach ($keywords as $k) {
                        if (mb_stripos($name, $k) !== false) {
                            $containsKw = true;
                            break;
                        }
                    }

                    if ($containsKw && $price >= $minPrice && $price <= $maxPrice) {
                        // Avoid duplicates
                        $exists = false;
                        foreach ($results as $res) {
                            if ($res['name'] === $name) { $exists = true; break; }
                        }
                        if (!$exists) {
                            $results[] = ['name' => $name, 'price' => $price];
                        }
                    }
                }
            }
        }

        // Limit to 5 items per category to avoid clutter
        return array_slice($results, 0, 5);
    }
}
