<?php
// api/controllers/StatsController.php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../storage.php';

class StatsController {
    private $storage;

    public function __construct() {
        $this->storage = Storage::getInstance();
    }

    public function log() {
        $data = json_in();
        $event = trim($data['event'] ?? 'page_view');
        $screen = trim($data['screen'] ?? 'main');
        $productId = trim($data['product_id'] ?? '');

        $stat = [
            'id' => 'stat_' . uniqid(),
            'event' => $event,
            'screen' => $screen,
            'product_id' => $productId ?: null,
            'data' => $data['data'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->storage->insert('stats', $stat);
        json_out(['success' => true]);
    }

    public function summary() {
        require_admin();

        $stats = $this->storage->all('stats');
        $users = $this->storage->all('users');
        $orders = $this->storage->all('orders');
        $products = $this->storage->all('products');
        $chats = $this->storage->all('chats');

        $activeProducts = count(array_filter($products, fn($p) => empty($p['deleted'])));
        $totalUsers = count($users);
        $totalOrders = count($orders);

        $totalRevenue = 0;
        $productSalesCount = [];

        foreach ($orders as $order) {
            if (($order['status'] ?? '') !== 'Отменён') {
                $totalRevenue += (float)($order['total_price'] ?? 0);
            }
            if (is_array($order['items'] ?? null)) {
                foreach ($order['items'] as $item) {
                    $pid = $item['id'] ?? $item['title'] ?? 'unknown';
                    $qty = (int)($item['qty'] ?? 1);
                    $productSalesCount[$pid] = ($productSalesCount[$pid] ?? 0) + $qty;
                }
            }
        }

        // Top screens views
        $screenCounts = [];
        foreach ($stats as $s) {
            if (($s['event'] ?? '') === 'screen_view' || ($s['event'] ?? '') === 'page_view') {
                $scr = $s['screen'] ?? 's-main';
                $screenCounts[$scr] = ($screenCounts[$scr] ?? 0) + 1;
            }
        }
        arsort($screenCounts);
        $topScreens = [];
        foreach (array_slice($screenCounts, 0, 5, true) as $scr => $cnt) {
            $topScreens[] = ['screen' => $scr, 'count' => $cnt];
        }

        // 14-day visits graph calculation
        $visitsByDate = [];
        for ($i = 13; $i >= 0; $i--) {
            $dateStr = date('Y-m-d', strtotime("-{$i} days"));
            $visitsByDate[$dateStr] = 0;
        }

        foreach ($stats as $s) {
            $createdAt = $s['created_at'] ?? '';
            $d = substr($createdAt, 0, 10);
            if (isset($visitsByDate[$d])) {
                $visitsByDate[$d]++;
            }
        }

        $dailyVisits = [];
        foreach ($visitsByDate as $date => $count) {
            $dailyVisits[] = ['date' => $date, 'count' => $count];
        }

        // Top sold products names
        arsort($productSalesCount);
        $topProducts = [];
        foreach (array_slice($productSalesCount, 0, 5, true) as $pid => $soldQty) {
            $pObj = $this->storage->getById('products', $pid);
            $title = $pObj ? $pObj['title'] : $pid;
            $topProducts[] = [
                'id' => $pid,
                'title' => $title,
                'sold_count' => $soldQty
            ];
        }

        json_out([
            'metrics' => [
                'total_events' => count($stats),
                'total_users' => $totalUsers,
                'total_orders' => $totalOrders,
                'total_revenue' => round($totalRevenue, 2),
                'total_chats' => count($chats),
                'active_products' => $activeProducts,
                'current_driver' => $this->storage->getDriverType()
            ],
            'daily_visits' => $dailyVisits,
            'top_screens' => $topScreens,
            'top_products' => $topProducts
        ]);
    }
}
