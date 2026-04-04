<?php

class ESM_Admin_Page {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_actions'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Excel Sections',
            'Excel Sections',
            'manage_options',
            'excel-sections',
            array($this, 'render_admin_page'),
            'dashicons-media-spreadsheet'
        );
    }

    public function handle_actions() {
        // Handle Upload
        if (isset($_POST['esm_upload_nonce']) && wp_verify_nonce($_POST['esm_upload_nonce'], 'esm_upload_action')) {
            if (!empty($_FILES['esm_excel_file']['tmp_name'])) {
                $file = $_FILES['esm_excel_file'];

                $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                if (strtolower($file_ext) !== 'xlsx') {
                    add_settings_error('esm_messages', 'esm_file_error', 'Only .xlsx files are allowed. / Разрешены только файлы .xlsx', 'error');
                    return;
                }

                $data = ESM_Excel_Parser::parse($file['tmp_name']);

                if (!empty($data)) {
                    update_option('esm_sections_data', $data, 'no');
                    add_settings_error('esm_messages', 'esm_success', 'Excel file processed successfully. / Файл Excel успешно обработан.', 'updated');
                } else {
                    add_settings_error('esm_messages', 'esm_parse_error', 'Could not parse Excel file or file is empty. / Не удалось прочитать файл или он пуст.', 'error');
                }
            }
        }

        // Handle Clear Data
        if (isset($_POST['esm_clear_nonce']) && wp_verify_nonce($_POST['esm_clear_nonce'], 'esm_clear_action')) {
            delete_option('esm_sections_data');
            add_settings_error('esm_messages', 'esm_cleared', 'All data has been cleared. / Все данные удалены.', 'updated');
        }
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>Excel Sections Manager / Менеджер разделов Excel</h1>
            <?php settings_errors('esm_messages'); ?>

            <div class="card" style="max-width: 800px;">
                <h2>Инструкция по использованию / How to Use</h2>
                <ol>
                    <li><strong>Подготовьте Excel:</strong> Создайте файл <code>.xlsx</code>. Каждый лист — это отдельный раздел.</li>
                    <li><strong>Заголовки:</strong> Первая строка должна содержать: <code>Title</code>, <code>Content</code> (или <code>Description</code>), <code>Image</code>, <code>Link</code>.</li>
                    <li><strong>Загрузите:</strong> Используйте форму ниже для загрузки файла.</li>
                    <li><strong>Шорткоды:</strong> Используйте <code>[excel_section name="ИмяЛиста"]</code> для вывода данных на сайте.</li>
                </ol>
            </div>

            <div class="card" style="margin-top: 20px; max-width: 800px;">
                <h2>Загрузить файл Excel / Upload Excel File</h2>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('esm_upload_action', 'esm_upload_nonce'); ?>
                    <p>
                        <input type="file" name="esm_excel_file" accept=".xlsx" required>
                    </p>
                    <?php submit_button('Upload and Process / Загрузить и обработать'); ?>
                </form>
            </div>

            <div class="card" style="margin-top: 20px; max-width: 800px;">
                <h2>Доступные разделы / Available Sections</h2>
                <?php
                $data = get_option('esm_sections_data', array());
                if (empty($data)) {
                    echo '<p>Данные не загружены. / No data loaded.</p>';
                } else {
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr><th>Раздел (Лист)</th><th>Кол-во элементов</th><th>Шорткод</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($data as $sheetName => $items) {
                        echo '<tr>';
                        echo '<td><strong>' . esc_html($sheetName) . '</strong></td>';
                        echo '<td>' . count($items) . '</td>';
                        echo '<td><code>[excel_section name="' . esc_attr($sheetName) . '"]</code></td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';

                    ?>
                    <form method="post" style="margin-top: 20px;">
                        <?php wp_nonce_field('esm_clear_action', 'esm_clear_nonce'); ?>
                        <input type="submit" name="esm_clear_data" class="button button-link-delete" value="Очистить все данные / Clear all data" onclick="return confirm('Вы уверены? / Are you sure?');">
                    </form>
                    <?php
                }
                ?>
            </div>
        </div>
        <style>
            .card { background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin-top: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
        </style>
        <?php
    }
}
