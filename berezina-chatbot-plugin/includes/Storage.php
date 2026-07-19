<?php
/**
 * Storage class for Berezina Chatbot.
 * Securely uses WordPress native Options API to prevent data loss on plugin updates
 * and avoid write failures on read-only hosting environments.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Berezina_Chatbot_Storage {
    private static function get_option_key($filename) {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        return 'berezina_chatbot_' . $name;
    }

    private static function get_default_file_path($filename) {
        return plugin_dir_path(dirname(__FILE__)) . 'data/' . $filename;
    }

    public static function read($filename) {
        $option_key = self::get_option_key($filename);
        $data = get_option($option_key);

        if ($data !== false) {
            return is_array($data) ? $data : json_decode($data, true);
        }

        // Fallback to read default packaged files from plugin data folder
        $default_path = self::get_default_file_path($filename);
        if (file_exists($default_path)) {
            $content = file_get_contents($default_path);
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                // Seed database option with default data so subsequent operations are dynamic
                update_option($option_key, $decoded);
                return $decoded;
            }
        }

        return array();
    }

    public static function write($filename, $data) {
        $option_key = self::get_option_key($filename);
        return update_option($option_key, $data);
    }
}
