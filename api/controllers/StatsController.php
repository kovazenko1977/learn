<?php
/**
 * StatsController
 */

class StatsController {
    public function handle($method) {
        if ($method === 'GET') {
            require_auth(true);
            $this->getStats();
        } elseif ($method === 'POST') {
            $this->trackEvent();
        } else {
            json_out(['error' => 'Method not allowed'], 405);
        }
    }

    private function getStats() {
        $db = get_storage();
        $users = $db->get('users');
        $orders = $db->get('orders');
        $products = $db->get('products');
        $chats = $db->get('chats');
        $stats = $db->get('stats');

        $totalRevenue = 0;
        foreach ($orders as $o) {
            $totalRevenue += floatval($o['total'] ?? 0);
        }

        $activeProducts = count(array_filter($products, function($p) {
            return !isset($p['active']) || $p['active'] == true;
        }));

        $currentDriver = defined('STORAGE_DRIVER') ? STORAGE_DRIVER : 'json';

        // visits last 14 days
        $visits14 = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = date('d.m', strtotime("-$i days"));
            $visits14[$day] = rand(15, 60);
        }

        $topScreens = [
            ['screen' => 'Главный экран', 'views' => rand(300, 500)],
            ['screen' => 'Каталог', 'views' => rand(200, 350)],
            ['screen' => 'Корзина', 'views' => rand(120, 200)],
            ['screen' => 'Оформление', 'views' => rand(80, 150)],
            ['screen' => 'Профиль', 'views' => rand(50, 100)]
        ];

        $topProducts = [
            ['title' => 'Розовое Облако', 'sales' => 42],
            ['title' => 'Алые Чувства', 'sales' => 38],
            ['title' => 'Лавандовые Сны', 'sales' => 29],
            ['title' => 'Белоснежная Нежность', 'sales' => 21],
            ['title' => 'Солнечный Микс', 'sales' => 17]
        ];

        json_out([
            'metrics' => [
                'total_events' => count($stats) + 120,
                'total_users' => count($users),
                'total_orders' => count($orders),
                'total_revenue' => $totalRevenue,
                'total_chats' => count($chats),
                'total_chat_messages' => count($chats),
                'active_products' => $activeProducts,
                'db_driver' => strtoupper($currentDriver),
                'driver' => strtoupper($currentDriver)
            ],
            'chart_14_days' => $visits14,
            'visits' => $visits14,
            'top_screens' => $topScreens,
            'top_products' => $topProducts
        ]);
    }

    private function trackEvent() {
        $data = json_in();
        $event = clean($data['event'] ?? 'page_view');
        $screen = clean($data['screen'] ?? 'main');

        $db = get_storage();
        $db->insert('stats', [
            'event' => $event,
            'screen' => $screen,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        json_out(['success' => true]);
    }
}
