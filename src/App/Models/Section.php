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
            'mode' => 'news', // news, info
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
            'sort_by' => 'date_desc',

            // New Advanced Settings
            'animation' => 'none', // none, fade, slide, zoom
            'bg_type' => 'none', // none, color, gradient
            'bg_color' => '#ffffff',
            'bg_gradient' => 'linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)',
            'text_color' => '#333333',
            'container_shadow' => false,
            'border_radius' => 15,
            'font_family' => 'inherit',
            'show_toc' => false,
            'show_progress_bar' => false,
            'password_protection' => '',
            'show_accessibility' => false,
            'allow_theme_toggle' => false,
            'show_qr' => false,
            'show_copy_link' => false,
            'show_breadcrumbs' => false,
            'show_scroll_top' => false,
            'custom_header' => '',
            'custom_footer' => '',
            'related_count' => 0,
            'webhook_url' => '',
            'lazy_load' => true,
            'show_subscribe' => false,
            'lang_subscribe_title' => 'Подпишитесь на новости',
            'lang_subscribe_btn' => 'ОК'
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
