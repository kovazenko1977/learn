<?php
if (!defined('ABSPATH')) exit;

$id = intval($_GET['id'] ?? 0);
$memorial = $id ? Memory_Pages_Memorial::get($id) : null;
$photos = $id ? Memory_Pages_Memorial::get_photos($id) : array();
$relatives = $id ? Memory_Pages_Memorial::get_relatives($id) : array();

$code = $memorial['code'] ?? '';
$public_url = $code ? home_url('/memory/' . $code . '/') : '';
?>
<div class="wrap mp-admin-wrap">
    <h1><?php echo $id ? 'Редактирование страницы памяти #' . esc_html($code) : 'Создание страницы памяти'; ?></h1>

    <?php if ($id && $code): ?>
    <div class="notice notice-info inline" style="margin-bottom: 20px; padding: 10px 15px;">
        <p>
            <strong>Цифровой код:</strong> <code><?php echo esc_html($code); ?></code> &nbsp;|&nbsp;
            <strong>URL:</strong> <a href="<?php echo esc_url($public_url); ?>" target="_blank"><?php echo esc_url($public_url); ?></a> &nbsp;|&nbsp;
            <a href="<?php echo esc_url(add_query_arg('preview', '1', $public_url)); ?>" target="_blank" class="button button-secondary">👁 Предпросмотр</a>
        </p>
    </div>
    <?php endif; ?>

    <form id="mp-memorial-form">
        <input type="hidden" name="id" id="mp_memorial_id" value="<?php echo esc_attr($id); ?>" />

        <!-- 1. Основная информация -->
        <div class="mp-card">
            <h3>1. Основная информация</h3>
            <div class="mp-form-grid">
                <div class="mp-form-group" style="grid-column: span 2;">
                    <label>ФИО *</label>
                    <input type="text" name="full_name" required value="<?php echo esc_attr($memorial['full_name'] ?? ''); ?>" placeholder="Иванов Иван Иванович" />
                </div>
                <div class="mp-form-group">
                    <label>Дата рождения</label>
                    <input type="date" name="birth_date" value="<?php echo esc_attr($memorial['birth_date'] ?? ''); ?>" />
                </div>
                <div class="mp-form-group">
                    <label>Дата смерти</label>
                    <input type="date" name="death_date" value="<?php echo esc_attr($memorial['death_date'] ?? ''); ?>" />
                </div>
                <div class="mp-form-group" style="grid-column: span 2;">
                    <label>Место рождения</label>
                    <input type="text" name="birth_place" value="<?php echo esc_attr($memorial['birth_place'] ?? ''); ?>" placeholder="г. Минск" />
                </div>
                <div class="mp-form-group">
                    <label>Статус</label>
                    <select name="status">
                        <option value="draft" <?php selected($memorial['status'] ?? 'draft', 'draft'); ?>>Черновик</option>
                        <option value="pending" <?php selected($memorial['status'] ?? '', 'pending'); ?>>На проверке</option>
                        <option value="published" <?php selected($memorial['status'] ?? '', 'published'); ?>>Опубликовано</option>
                        <option value="disabled" <?php selected($memorial['status'] ?? '', 'disabled'); ?>>Отключено</option>
                        <option value="archive" <?php selected($memorial['status'] ?? '', 'archive'); ?>>Архив</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- 2. Биография -->
        <div class="mp-card">
            <h3>2. Биография</h3>
            <?php
            $content = $memorial['biography'] ?? '';
            wp_editor($content, 'mp_biography', array(
                'textarea_name' => 'biography',
                'textarea_rows' => 10,
                'media_buttons' => true,
            ));
            ?>
        </div>

        <!-- 3. Место захоронения -->
        <div class="mp-card">
            <h3>3. Место захоронения</h3>
            <div class="mp-form-grid">
                <div class="mp-form-group">
                    <label>Кладбище</label>
                    <input type="text" name="cemetery" value="<?php echo esc_attr($memorial['cemetery'] ?? ''); ?>" placeholder="Северное кладбище" />
                </div>
                <div class="mp-form-group">
                    <label>Участок</label>
                    <input type="text" name="plot" value="<?php echo esc_attr($memorial['plot'] ?? ''); ?>" placeholder="12A" />
                </div>
                <div class="mp-form-group">
                    <label>Ряд</label>
                    <input type="text" name="row_number" value="<?php echo esc_attr($memorial['row_number'] ?? ''); ?>" placeholder="4" />
                </div>
                <div class="mp-form-group">
                    <label>Место</label>
                    <input type="text" name="place_number" value="<?php echo esc_attr($memorial['place_number'] ?? ''); ?>" placeholder="18" />
                </div>
            </div>
        </div>

        <!-- 4. Главное фото -->
        <div class="mp-card">
            <h3>4. Главное фото</h3>
            <input type="hidden" name="main_photo_id" id="mp_main_photo_id" value="<?php echo esc_attr($memorial['main_photo_id'] ?? 0); ?>" />
            <input type="hidden" name="main_photo_url" id="mp_main_photo_url" value="<?php echo esc_attr($memorial['main_photo_url'] ?? ''); ?>" />

            <div style="display: flex; gap: 20px; align-items: center;">
                <img id="mp-main-photo-preview" src="<?php echo esc_url($memorial['main_photo_url'] ?? MEMORY_PAGES_URL . 'admin/images/placeholder.png'); ?>" style="max-width: 150px; max-height: 180px; border: 1px solid #ddd; border-radius: 4px; object-fit: cover;" />
                <div>
                    <button type="button" class="button" id="mp-upload-main-photo-btn">Выбрать фотографию</button>
                </div>
            </div>
        </div>

        <p><button type="submit" class="button button-primary button-large">Сохранить страницу памяти</button></p>
    </form>

    <?php if ($id): ?>
    <!-- 5. Галерея -->
    <div class="mp-card">
        <h3>5. Галерея</h3>
        <button type="button" class="button" id="mp-add-gallery-photos-btn">+ Добавить фотографии в галерею</button>
        <div class="mp-gallery-grid" id="mp-gallery-list">
            <?php foreach ($photos as $photo): ?>
                <div class="mp-gallery-item" id="mp-photo-item-<?php echo esc_attr($photo['id']); ?>">
                    <img src="<?php echo esc_url($photo['photo_url']); ?>" />
                    <input type="text" value="<?php echo esc_attr($photo['caption']); ?>" placeholder="Подпись..." />
                    <button type="button" class="button button-small button-link-delete mp-delete-photo-btn" data-id="<?php echo esc_attr($photo['id']); ?>" style="margin-top: 5px;">Удалить</button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 6. Родственники -->
    <div class="mp-card">
        <h3>6. Родственники</h3>
        <table class="mp-relatives-table">
            <thead>
                <tr>
                    <th>ФИО</th>
                    <th>Степень родства</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Telegram</th>
                    <th>Показывать посетителю</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($relatives as $rel): ?>
                    <tr id="mp-rel-row-<?php echo esc_attr($rel['id']); ?>">
                        <td><?php echo esc_html($rel['full_name']); ?></td>
                        <td><?php echo esc_html($rel['kinship_degree']); ?></td>
                        <td><?php echo esc_html($rel['phone']); ?></td>
                        <td><?php echo esc_html($rel['email']); ?></td>
                        <td><?php echo esc_html($rel['telegram']); ?></td>
                        <td><?php echo $rel['show_contact_to_visitor'] ? 'Да' : 'Нет'; ?></td>
                        <td><button type="button" class="button button-small mp-delete-rel-btn" data-id="<?php echo esc_attr($rel['id']); ?>">Удалить</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h4 style="margin-top: 20px;">+ Добавить родственника</h4>
        <div class="mp-form-grid" style="align-items: end;">
            <div class="mp-form-group"><label>ФИО</label><input type="text" id="mp_rel_name" /></div>
            <div class="mp-form-group"><label>Степень родства</label><input type="text" id="mp_rel_kinship" placeholder="Сын, жена..." /></div>
            <div class="mp-form-group"><label>Телефон</label><input type="text" id="mp_rel_phone" /></div>
            <div class="mp-form-group"><label>E-mail</label><input type="email" id="mp_rel_email" /></div>
            <div class="mp-form-group"><label>Telegram</label><input type="text" id="mp_rel_tg" /></div>
            <div class="mp-form-group"><label><input type="checkbox" id="mp_rel_show" value="1" /> Показывать контакт посетителю</label></div>
            <div><button type="button" class="button" id="mp-save-relative-btn">Сохранить родственника</button></div>
        </div>
    </div>

    <!-- 7. QR-код -->
    <div class="mp-card">
        <h3>7. QR-код</h3>
        <div id="mp-qr-container">
            <?php
            $qr_img = Memory_Pages_QR::get_or_create_qr($id, $code);
            if ($qr_img): ?>
                <img src="<?php echo esc_url($qr_img); ?>" width="150" height="150" /><br>
                <a href="<?php echo esc_url($qr_img); ?>" download class="button button-small" style="margin-top:5px;">Скачать PNG</a>
            <?php else: ?>
                <button type="button" class="button" id="mp-generate-qr-btn">Сгенерировать QR-код</button>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
