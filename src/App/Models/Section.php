<?php

namespace App\Models;

use App\Database\JsonStore;

class Section
{
    private static ?JsonStore $store = null;

    public static function getStore(): JsonStore
    {
        if (self::$store === null) {
            self::$store = new JsonStore(__DIR__ . '/../../../data/sections.json');
        }
        return self::$store;
    }

    public static function all(): array
    {
        return self::getStore()->getAll();
    }

    public static function find(string $id): ?array
    {
        $section = self::getStore()->findById($id);
        if ($section) {
            return array_merge(self::getDefaults(), $section);
        }
        return null;
    }

    public static function getDefaults(): array
    {
        return [
            'view_type' => 'cards',
            'items_per_page' => 10,
            'show_date' => true,
            'show_title' => true,
            'show_views' => true,
            'show_author' => false,
            'show_reading_time' => true,
            'show_tags' => true,
            'show_search' => true,
            'show_reactions' => true,
            'show_share' => true,
            'custom_css' => '',
            'lang_read_more' => 'Читать далее',
            'lang_search_placeholder' => 'Поиск новостей...',
            'sort_by' => 'date_desc', // date_desc, date_asc, views_desc, reactions_desc
        ];
    }

    public static function save(array $data): void
    {
        if (!isset($data['id'])) {
            $data['id'] = uniqid();
        }
        self::getStore()->saveItem($data);
    }

    public static function delete(string $id): void
    {
        self::getStore()->deleteById($id);
    }
}
