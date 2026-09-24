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
            if (class_exists($class_name)) {
                $widgets_manager->register(new $class_name());
            }
        }
    }
}

abstract class MP_Elementor_Widget_Base extends \Elementor\Widget_Base {
    public function get_categories() { return array('general'); }
    public function get_icon() { return 'eicon-post-title'; }

    protected function get_current_memorial() {
        $code = get_query_var('memorial_code');
        if (!empty($code)) {
            return Memory_Pages_Memorial::get($code);
        }
        return null;
    }
}

class MP_Elementor_Widget_Header extends MP_Elementor_Widget_Base {
    public function get_name() { return 'mp_widget_header'; }
    public function get_title() { return 'Memorial Header'; }
    protected function render() {
        $m = $this->get_current_memorial();
        if ($m) {
            echo '<div class="mp-header-card"><h1 class="mp-full-name">' . esc_html($m['full_name']) . '</h1></div>';
        } else {
            echo '<div class="mp-header-card"><h3>[Memorial Header]</h3></div>';
        }
    }
}

class MP_Elementor_Widget_Dates extends MP_Elementor_Widget_Base {
    public function get_name() { return 'mp_widget_dates'; }
    public function get_title() { return 'Memorial Dates'; }
    protected function render() {
        $m = $this->get_current_memorial();
        if ($m) {
            echo '<div class="mp-dates">' . esc_html($m['birth_date'] . ' — ' . $m['death_date']) . '</div>';
        } else {
            echo '<div class="mp-dates">[01.01.1950 — 01.01.2020]</div>';
        }
    }
}

class MP_Elementor_Widget_Biography extends MP_Elementor_Widget_Base {
    public function get_name() { return 'mp_widget_biography'; }
    public function get_title() { return 'Memorial Biography'; }
    protected function render() {
        $m = $this->get_current_memorial();
        if ($m && !empty($m['biography'])) {
            echo '<div class="mp-section"><h2 class="mp-section-title">Биография</h2>' . wp_kses_post(wpautop($m['biography'])) . '</div>';
        } else {
            echo '<div class="mp-section"><h3>[Memorial Biography]</h3></div>';
        }
    }
}

class MP_Elementor_Widget_Gallery extends MP_Elementor_Widget_Base {
    public function get_name() { return 'mp_widget_gallery'; }
    public function get_title() { return 'Memorial Gallery'; }
    protected function render() {
        echo '<div class="mp-section"><h3>[Memorial Gallery]</h3></div>';
    }
}

class MP_Elementor_Widget_Family extends MP_Elementor_Widget_Base {
    public function get_name() { return 'mp_widget_family'; }
    public function get_title() { return 'Memorial Family'; }
    protected function render() {
        echo '<div class="mp-section"><h3>[Memorial Family]</h3></div>';
    }
}

class MP_Elementor_Widget_Contacts extends MP_Elementor_Widget_Base {
    public function get_name() { return 'mp_widget_contacts'; }
    public function get_title() { return 'Memorial Contacts'; }
    protected function render() {
        echo '<div class="mp-section"><h3>[Memorial Contacts]</h3></div>';
    }
}

class MP_Elementor_Widget_Burial extends MP_Elementor_Widget_Base {
    public function get_name() { return 'mp_widget_burial'; }
    public function get_title() { return 'Memorial Burial'; }
    protected function render() {
        $m = $this->get_current_memorial();
        if ($m && !empty($m['cemetery'])) {
            echo '<div class="mp-section"><h2 class="mp-section-title">Место захоронения</h2><p>' . esc_html($m['cemetery']) . '</p></div>';
        } else {
            echo '<div class="mp-section"><h3>[Memorial Burial]</h3></div>';
        }
    }
}

class MP_Elementor_Widget_Flower extends MP_Elementor_Widget_Base {
    public function get_name() { return 'mp_widget_flower'; }
    public function get_title() { return 'Memorial Flower'; }
    protected function render() {
        echo '<button class="mp-btn mp-btn-flower">🌸 Возложить цветок</button>';
    }
}

class MP_Elementor_Widget_Candle extends MP_Elementor_Widget_Base {
    public function get_name() { return 'mp_widget_candle'; }
    public function get_title() { return 'Memorial Candle'; }
    protected function render() {
        echo '<button class="mp-btn mp-btn-candle">🕯 Зажечь свечу</button>';
    }
}

class MP_Elementor_Widget_Share extends MP_Elementor_Widget_Base {
    public function get_name() { return 'mp_widget_share'; }
    public function get_title() { return 'Memorial Share'; }
    protected function render() {
        echo '<button class="mp-btn mp-btn-share">🔗 Поделиться</button>';
    }
}

class MP_Elementor_Widget_QR extends MP_Elementor_Widget_Base {
    public function get_name() { return 'mp_widget_qr'; }
    public function get_title() { return 'Memorial QR'; }
    protected function render() {
        echo '<div class="mp-section"><h3>[Memorial QR]</h3></div>';
    }
}
