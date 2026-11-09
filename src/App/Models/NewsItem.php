<?php

namespace App\Models;

use App\Database\JsonStore;
use App\Helpers\Auth;

class NewsItem
{
    private static ?JsonStore $store = null;
    private static ?JsonStore $revisionStore = null;

    public static function getStore(): JsonStore
    {
        if (self::$store === null) {
            self::$store = new JsonStore(__DIR__ . '/../../../data/news.json');
        }
        return self::$store;
    }

    public static function getRevisionStore(): JsonStore
    {
        if (self::$revisionStore === null) {
            self::$revisionStore = new JsonStore(__DIR__ . '/../../../data/revisions.json');
        }
        return self::$revisionStore;
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

            if (!isset($viewLogs[$visitorHash]) || $viewLogs[$visitorHash] !== $today) {
                $item['views'] = ($item['views'] ?? 0) + 1;
                $viewLogs[$visitorHash] = $today;

                if (count($viewLogs) > 1000) {
                    $viewLogs = array_slice($viewLogs, -1000, null, true);
                }

                $item['view_logs'] = $viewLogs;
                self::save($item, false); // Don't create revision for view count
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
        self::save($item, false); // Don't create revision for reactions

        return [
            'success' => true,
            'count' => $item['reaction_count'],
            'active' => $active
        ];
    }

    public static function getRevisions(string $newsId): array
    {
        $revisions = self::getRevisionStore()->getAll();
        return array_filter($revisions, fn($r) => $r['news_id'] === $newsId);
    }

    public static function save(array $data, bool $createRevision = true): void
    {
        if (!isset($data['id'])) {
            $data['id'] = uniqid();
        }
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        if ($createRevision) {
            $oldItem = self::find($data['id']);
            $newContent = $data['content'] ?? '';
            $oldContent = $oldItem['content'] ?? '';
            if ($oldItem && $oldContent !== $newContent) {
                $revision = [
                    'id' => uniqid(),
                    'news_id' => $data['id'],
                    'content' => $oldContent,
                    'title' => $oldItem['title'] ?? '',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'user' => $_SESSION['username'] ?? 'System'
                ];
                $revStore = self::getRevisionStore();
                $revisions = $revStore->getAll();
                $revisions[] = $revision;
                $revStore->set(array_slice($revisions, -100)); // Last 100 revisions total
            }
        }

        self::getStore()->saveItem($data);
    }

    public static function delete(string $id): void
    {
        self::getStore()->deleteById($id);
        // Cleanup revisions
        $revStore = self::getRevisionStore();
        $revisions = $revStore->getAll();
        $revisions = array_filter($revisions, fn($r) => $r['news_id'] !== $id);
        $revStore->set(array_values($revisions));
    }
}
