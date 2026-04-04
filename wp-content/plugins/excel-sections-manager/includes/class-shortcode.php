<?php

class ESM_Shortcode {

    public function __construct() {
        add_shortcode('excel_section', array($this, 'render_shortcode'));
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'name' => '',
            ),
            $atts,
            'excel_section'
        );

        $section_name = $atts['name'];
        if (empty($section_name)) {
            return '<p>Error: Sheet name not specified in shortcode.</p>';
        }

        $data = get_option('esm_sections_data', array());

        if (!isset($data[$section_name])) {
            return '<p>Error: Section "' . esc_html($section_name) . '" not found.</p>';
        }

        $items = $data[$section_name];

        if (empty($items)) {
            return '<p>Section "' . esc_html($section_name) . '" is empty.</p>';
        }

        ob_start();
        ?>
        <div class="esm-section-grid">
            <?php foreach ($items as $item): ?>
                <div class="esm-item">
                    <?php if (isset($item['image']) && !empty($item['image'])): ?>
                        <div class="esm-image">
                            <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title'] ?? ''); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="esm-content">
                        <?php if (isset($item['title'])): ?>
                            <h3 class="esm-title"><?php echo esc_html($item['title']); ?></h3>
                        <?php endif; ?>
                        <?php if (isset($item['content'])): ?>
                            <div class="esm-text"><?php echo wp_kses_post(wpautop($item['content'])); ?></div>
                        <?php elseif (isset($item['description'])): ?>
                            <div class="esm-text"><?php echo wp_kses_post(wpautop($item['description'])); ?></div>
                        <?php endif; ?>

                        <?php if (isset($item['link']) && !empty($item['link'])): ?>
                            <div class="esm-link">
                                <a href="<?php echo esc_url($item['link']); ?>" class="button"><?php _e('Read More', 'esm'); ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
