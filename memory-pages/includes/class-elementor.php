<?php
if (!defined('ABSPATH')) exit;

class Memory_Pages_Elementor {

    public static function init() {
        add_action('elementor/widgets/register', array(__CLASS__, 'register_widgets'));
    }

    public static function register_widgets($widgets_manager) {
        if (!class_exists('\Elementor\Widget_Base')) {
            return;
        }

        // Register 11 widgets dynamically
        $widget_names = array(
            'Header'    => 'Memorial Header',
            'Dates'     => 'Memorial Dates',
            'Biography' => 'Memorial Biography',
            'Gallery'   => 'Memorial Gallery',
            'Family'    => 'Memorial Family',
            'Contacts'  => 'Memorial Contacts',
            'Burial'    => 'Memorial Burial',
            'Flower'    => 'Memorial Flower',
            'Candle'    => 'Memorial Candle',
            'Share'     => 'Memorial Share',
            'QR'        => 'Memorial QR'
        );

        foreach ($widget_names as $key => $title) {
            $class_name = 'MP_Elementor_Widget_' . $key;
            if (!class_exists($class_name)) {
                self::create_widget_class($class_name, $title, $key);
            }
            if (class_exists($class_name)) {
                $widgets_manager->register(new $class_name());
            }
        }
    }

    private static function create_widget_class($class_name, $title, $key) {
        eval("
            class $class_name extends \Elementor\Widget_Base {
                public function get_name() { return 'mp_widget_" . strtolower($key) . "'; }
                public function get_title() { return '$title'; }
                public function get_icon() { return 'eicon-post-title'; }
                public function get_categories() { return [ 'general' ]; }
                protected function render() {
                    echo '<div class=\"mp-elementor-widget-placeholder\"><strong>" . $title . "</strong></div>';
                }
            }
        ");
    }
}
