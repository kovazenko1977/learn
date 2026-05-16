<?php

namespace App\Models;

use App\Database\JsonStore;

class NewsItem
{
    private static ?JsonStore $store = null;

    public static function getStore(): JsonStore
    {
        if (self::$store === null) {
            self::$store = new JsonStore(__DIR__ . '/../../../data/news.json');
        }
        return self::$store;
    }

    public static function all(): array
    {
        return self::getStore()->getAll();
    }

    public static function find(string $id): ?array
    {
        return self::getStore()->findById($id);
    }

    public static function findBySection(string $sectionId, bool $onlyPublished = true): array
    {
        $items = self::all();
        $filtered = array_filter($items, function($item) use ($sectionId, $onlyPublished) {
            if ($item['section_id'] !== $sectionId) {
                return false;
            }
            if ($onlyPublished) {
                $now = date('Y-m-d H:i:s');
                if (isset($item['publish_at']) && $item['publish_at'] && $item['publish_at'] > $now) {
                    return false;
                }
                if (isset($item['expire_at']) && $item['expire_at'] && $item['expire_at'] < $now) {
                    return false;
                }
            }
            return true;
        });

        usort($filtered, function($a, $b) {
            return ($b['publish_at'] ?? $b['created_at']) <=> ($a['publish_at'] ?? $a['created_at']);
        });

        return array_values($filtered);
    }

    public static function save(array $data): void
    {
        if (!isset($data['id'])) {
            $data['id'] = uniqid();
        }
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        self::getStore()->saveItem($data);
    }

    public static function delete(string $id): void
    {
        self::getStore()->deleteById($id);
    }
}
