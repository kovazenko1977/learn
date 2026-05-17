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
                if (($item['status'] ?? 'published') !== 'published') {
                    return false;
                }
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
            $aPinned = $a['is_pinned'] ?? false;
            $bPinned = $b['is_pinned'] ?? false;
            if ($aPinned !== $bPinned) {
                return $bPinned <=> $aPinned;
            }
            return ($b['publish_at'] ?? $b['created_at']) <=> ($a['publish_at'] ?? $a['created_at']);
        });

        return array_values($filtered);
    }

    public static function incrementViews(string $id, string $visitorHash): void
    {
        $item = self::find($id);
        if ($item) {
            $viewLogs = $item['view_logs'] ?? [];
            $today = date('Y-m-d');

            // Check if this visitor already viewed this item today
            if (!isset($viewLogs[$visitorHash]) || $viewLogs[$visitorHash] !== $today) {
                $item['views'] = ($item['views'] ?? 0) + 1;
                $viewLogs[$visitorHash] = $today;

                // Cleanup old logs (keep only last 1000 visitors to avoid file bloat)
                if (count($viewLogs) > 1000) {
                    $viewLogs = array_slice($viewLogs, -1000, null, true);
                }

                $item['view_logs'] = $viewLogs;
                self::save($item);
            }
        }
    }

    public static function toggleReaction(string $id, string $visitorHash): array
    {
        $item = self::find($id);
        if (!$item) return ['success' => false];

        $reactions = $item['reactions'] ?? [];
        if (in_array($visitorHash, $reactions)) {
            $reactions = array_diff($reactions, [$visitorHash]);
            $active = false;
        } else {
            $reactions[] = $visitorHash;
            $active = true;
        }

        $item['reactions'] = array_values($reactions);
        $item['reaction_count'] = count($item['reactions']);
        self::save($item);

        return [
            'success' => true,
            'count' => $item['reaction_count'],
            'active' => $active
        ];
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
