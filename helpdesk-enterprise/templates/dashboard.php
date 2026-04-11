<div class="hd-dashboard-wrapper">
    <!-- Lucide Icons Library -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <header class="hd-header">
        <div class="hd-title">
            <i data-lucide="layout-dashboard"></i>
            <span>Helpdesk Enterprise <small style="font-weight: 400; opacity: 0.7;">v8.0</small></span>
        </div>
        <div class="hd-user-settings" style="display: flex; gap: 16px; align-items: center;">
            <div style="text-align: right; line-height: 1.2;">
                <div style="font-weight: 700; font-size: 14px;"><?php echo esc_html(HD_Auth::get_user()->display_name); ?></div>
                <div style="font-size: 11px; color: var(--win-text-sec); text-transform: uppercase;"><?php echo esc_html(HD_Auth::get_user()->role); ?></div>
            </div>
            <button id="hd-logout-btn" class="hd-btn" style="padding: 6px 12px; border-radius: 4px;"><i data-lucide="log-out" style="width: 14px; height: 14px;"></i></button>
        </div>
    </header>

    <div class="hd-desktop-layout">
        <aside class="hd-sidebar">
            <div class="hd-nav-item active" data-view="dashboard">
                <i data-lucide="home"></i> <?php _e('Главный экран', 'helpdesk-enterprise'); ?>
            </div>
            <div class="hd-nav-item" data-view="all-requests">
                <i data-lucide="list"></i> <?php _e('Все заявки', 'helpdesk-enterprise'); ?>
            </div>
            <?php if (HD_Auth::current_user_can('hd_create_requests')): ?>
                <div class="hd-nav-item" data-view="create-request">
                    <i data-lucide="plus-circle"></i> <?php _e('Создать заявку', 'helpdesk-enterprise'); ?>
                </div>
            <?php endif; ?>

            <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid var(--win-border);">
                <div style="font-size: 11px; color: var(--win-text-sec); margin-bottom: 12px; text-transform: uppercase;"><?php _e('Инструменты', 'helpdesk-enterprise'); ?></div>
                <?php if (HD_Auth::current_user_can('hd_export_dept')): ?>
                    <a href="<?php echo admin_url('admin-ajax.php?action=hd_export_requests&nonce=' . wp_create_nonce('hd_nonce')); ?>" class="hd-nav-item">
                        <i data-lucide="download"></i> <?php _e('Экспорт CSV', 'helpdesk-enterprise'); ?>
                    </a>
                <?php endif; ?>
                <div class="hd-nav-item" id="hd-tg-settings-trigger">
                    <i data-lucide="message-square"></i> <?php _e('Telegram Bot', 'helpdesk-enterprise'); ?>
                </div>
                <div class="hd-nav-item" id="hd-theme-toggle">
                    <i data-lucide="moon"></i> <span><?php _e('Темная тема', 'helpdesk-enterprise'); ?></span>
                </div>
            </div>
        </aside>

        <main class="hd-workspace">
            <div class="hd-view" id="hd-view-dashboard">
                <div class="hd-stats-grid">
                    <div class="hd-stat-card">
                        <div class="hd-stat-label"><?php _e('Всего', 'helpdesk-enterprise'); ?></div>
                        <div class="hd-stat-value"><?php echo $stats['total']; ?></div>
                    </div>
                    <div class="hd-stat-card" style="border-left: 4px solid #0078d4;">
                        <div class="hd-stat-label"><?php _e('Новые', 'helpdesk-enterprise'); ?></div>
                        <div class="hd-stat-value"><?php echo $stats['new']; ?></div>
                    </div>
                    <div class="hd-stat-card" style="border-left: 4px solid #ffb900;">
                        <div class="hd-stat-label"><?php _e('В работе', 'helpdesk-enterprise'); ?></div>
                        <div class="hd-stat-value"><?php echo $stats['in_progress']; ?></div>
                    </div>
                    <div class="hd-stat-card" style="border-left: 4px solid #ef4444;">
                        <div class="hd-stat-label"><?php _e('Просрочено', 'helpdesk-enterprise'); ?></div>
                        <div class="hd-stat-value" style="color: #ef4444;"><?php echo $stats['overdue']; ?></div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
                    <div class="hd-main-col">
                        <div class="hd-search-container">
                            <i data-lucide="search" class="hd-search-icon"></i>
                            <input type="text" id="hd-global-search" class="hd-search-input" placeholder="<?php _e('Поиск заявок по ID, заголовку или описанию...', 'helpdesk-enterprise'); ?>">
                        </div>

                        <div class="hd-table-container">
                    <table class="hd-table" id="hd-requests-table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">ID</th>
                                <th><?php _e('Заявка', 'helpdesk-enterprise'); ?></th>
                                <th><?php _e('Статус', 'helpdesk-enterprise'); ?></th>
                                <th><?php _e('Дедлайн', 'helpdesk-enterprise'); ?></th>
                                <th style="text-align: right;"><?php _e('Действия', 'helpdesk-enterprise'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr class="hd-request-row" data-search-content="<?php echo esc_attr(strtolower($request->id . ' ' . $request->title . ' ' . $request->description)); ?>">
                                    <td style="font-weight: 700; color: var(--win-accent);">#<?php echo $request->id; ?></td>
                                    <td>
                                        <div style="font-weight: 600;"><?php echo esc_html($request->title); ?></div>
                                        <div style="font-size: 11px; color: var(--win-text-sec);"><?php echo esc_html($request->cat_name); ?> | <?php echo esc_html($request->dept_name); ?></div>
                                    </td>
                                    <td>
                                        <span class="hd-badge badge-<?php echo $request->status; ?>">
                                            <?php
                                                $status_labels = array('new' => 'Новая', 'in_progress' => 'В работе', 'pending' => 'Ожидание', 'completed' => 'Выполнена', 'rejected' => 'Отклонена');
                                                echo isset($status_labels[$request->status]) ? $status_labels[$request->status] : $request->status;
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                            $deadline_ts = strtotime($request->deadline);
                                            $is_overdue = ($request->status !== 'completed' && $deadline_ts < current_time('timestamp'));
                                        ?>
                                        <div style="display: flex; align-items: center; gap: 6px; <?php echo $is_overdue ? 'color: #ef4444; font-weight: 700;' : ''; ?>">
                                            <i data-lucide="calendar" style="width: 14px; height: 14px;"></i>
                                            <?php echo date('d.m.Y H:i', $deadline_ts); ?>
                                        </div>
                                    </td>
                                    <td style="text-align: right;">
                                        <button class="hd-btn hd-view-request" data-id="<?php echo $request->id; ?>" style="padding: 6px 16px;"><?php _e('Открыть', 'helpdesk-enterprise'); ?></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="hd-side-col">
                        <div style="background: #fff; border: 1px solid var(--win-border); border-radius: var(--win-radius); padding: 20px;">
                            <h3 style="margin-top: 0; font-size: 14px; text-transform: uppercase; color: var(--win-text-sec); display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="activity" style="width: 14px; height: 14px;"></i>
                                <?php _e('Живая лента', 'helpdesk-enterprise'); ?>
                            </h3>
                            <div class="hd-activity-feed" style="display: flex; flex-direction: column; gap: 12px; margin-top: 16px;">
                                <?php foreach ($recent_activity as $act): ?>
                                    <div style="border-left: 2px solid var(--win-accent); padding-left: 12px; font-size: 13px;">
                                        <div style="font-weight: 600;"><?php echo $act->title ? esc_html($act->title) : __('Удаленная заявка', 'helpdesk-enterprise'); ?></div>
                                        <div style="color: var(--win-text-sec); font-size: 11px;">
                                            <?php
                                                $e_types = array('status_changed' => 'Статус', 'executor_changed' => 'Исполнитель', 'comment_added' => 'Комментарий');
                                                echo isset($e_types[$act->event_type]) ? $e_types[$act->event_type] : $act->event_type;
                                            ?> • <?php echo date('H:i', strtotime($act->created_at)); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div style="background: #fff; border: 1px solid var(--win-border); border-radius: var(--win-radius); padding: 20px; margin-top: 24px;">
                            <h3 style="margin-top: 0; font-size: 14px; text-transform: uppercase; color: var(--win-text-sec); display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="monitor" style="width: 14px; height: 14px;"></i>
                                <?php _e('О системе', 'helpdesk-enterprise'); ?>
                            </h3>
                            <div style="font-size: 12px; line-height: 1.6; margin-top: 12px;">
                                <div><strong>Версия:</strong> 8.0 Enterprise</div>
                                <div><strong>Лицензия:</strong> Unlimited Corporate</div>
                                <div><strong>Разработчик:</strong> Kovazenko S.B.</div>
                                <div style="margin-top: 8px; color: var(--win-accent); cursor: pointer;"><?php _e('Проверить обновления', 'helpdesk-enterprise'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hd-view" id="hd-view-create-request" style="display:none;">
                <div class="hd-create-form-container">
                    <h2 style="margin-top: 0; display: flex; align-items: center; gap: 12px;"><i data-lucide="plus-circle" style="color: var(--win-accent);"></i> <?php _e('Новая заявка', 'helpdesk-enterprise'); ?></h2>
                    <form id="hd-create-form">
                        <div class="hd-form-group">
                            <label><?php _e('Шаблон заявки (опционально)', 'helpdesk-enterprise'); ?></label>
                            <select id="hd-request-template" class="hd-input">
                                <option value=""><?php _e('Свой заголовок...', 'helpdesk-enterprise'); ?></option>
                                <option value="Сбой в работе ПО" data-desc="Программа перестала отвечать или выдает ошибку.">Сбой в работе ПО</option>
                                <option value="Проблема с доступом" data-desc="Не удается войти в систему или сетевую папку.">Проблема с доступом</option>
                                <option value="Заявка на расходные материалы" data-desc="Необходим картридж, бумага или другие комплектующие.">Заявка на расходные материалы</option>
                            </select>
                        </div>
                        <div class="hd-form-group">
                            <label><?php _e('Заголовок', 'helpdesk-enterprise'); ?></label>
                            <input type="text" name="title" id="hd-create-title" class="hd-input" required placeholder="<?php _e('Кратко опишите суть...', 'helpdesk-enterprise'); ?>">
                        </div>
                        <div class="hd-form-group">
                            <label><?php _e('Подробное описание', 'helpdesk-enterprise'); ?></label>
                            <textarea name="description" class="hd-textarea" rows="8" required></textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="hd-form-group">
                                <label><?php _e('Категория', 'helpdesk-enterprise'); ?></label>
                                <select name="category_id" class="hd-input" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat->id; ?>"><?php echo esc_html($cat->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="hd-form-group">
                                <label><?php _e('Вложение (Фото)', 'helpdesk-enterprise'); ?></label>
                                <input type="file" name="photo" class="hd-input" accept="image/*">
                            </div>
                        </div>
                        <button type="submit" class="hd-btn hd-btn-primary" style="width: 100%; margin-top: 20px; padding: 16px; font-size: 16px;"><?php _e('Оформить заявку', 'helpdesk-enterprise'); ?></button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Windows Modals -->
    <div id="hd-modal">
        <div class="hd-modal-content">
            <div class="hd-modal-header">
                <div style="display: flex; align-items: center; gap: 10px; font-weight: 600;">
                    <i data-lucide="file-text" style="width: 16px; height: 16px; color: var(--win-accent);"></i>
                    <span><?php _e('Карточка заявки', 'helpdesk-enterprise'); ?></span>
                </div>
                <span class="hd-close">&times;</span>
            </div>
            <div class="hd-modal-body" id="hd-request-details"></div>
        </div>
    </div>

    <div id="hd-tg-settings-modal" class="hd-modal" style="display:none;">
        <div class="hd-modal-content" style="width: 400px; height: auto;">
             <div class="hd-modal-header">
                <span><?php _e('Настройки Telegram', 'helpdesk-enterprise'); ?></span>
                <span class="hd-close" onclick="jQuery('#hd-tg-settings-modal').hide();">&times;</span>
            </div>
            <div class="hd-modal-body">
                <form id="hd-user-settings-form">
                    <p style="font-size: 13px; color: var(--win-text-sec); margin-bottom: 20px;"><?php _e('Введите ваш Chat ID для получения мгновенных уведомлений.', 'helpdesk-enterprise'); ?></p>
                    <div class="hd-form-group">
                        <label><?php _e('API Token:', 'helpdesk-enterprise'); ?></label>
                        <code style="display: block; padding: 10px; background: #f0f0f0; border-radius: 4px;"><?php echo esc_html(HD_Auth::get_user()->api_token); ?></code>
                    </div>
                    <div class="hd-form-group" style="margin-top: 16px;">
                        <label><?php _e('Telegram Chat ID:', 'helpdesk-enterprise'); ?></label>
                        <input type="text" name="telegram_chat_id" class="hd-input" value="<?php echo esc_attr(HD_Auth::get_user()->telegram_chat_id); ?>">
                    </div>
                    <button type="submit" class="hd-btn hd-btn-primary" style="width: 100%; margin-top: 16px;"><?php _e('Сохранить', 'helpdesk-enterprise'); ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    jQuery(document).ready(function($) {
        // Global Search Logic
        $('#hd-global-search').on('input', function() {
            var val = $(this).val().toLowerCase();
            $('.hd-request-row').each(function() {
                var content = $(this).data('search-content');
                if (content.indexOf(val) !== -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Navigation Logic
        $('.hd-nav-item').on('click', function() {
            var view = $(this).data('view');
            if(!view) return;

            $('.hd-nav-item').removeClass('active');
            $(this).addClass('active');

            $('.hd-view').hide();
            $('#hd-view-' + view).show();
        });

        $('#hd-tg-settings-trigger').on('click', function() {
            $('#hd-tg-settings-modal').css('display', 'flex');
        });

        // Theme Toggle
        $('#hd-theme-toggle').on('click', function() {
            $('body').toggleClass('hd-dark-theme');
            var isDark = $('body').hasClass('hd-dark-theme');
            $(this).find('span').text(isDark ? 'Светлая тема' : 'Темная тема');
            $(this).find('i').attr('data-lucide', isDark ? 'sun' : 'moon');
            lucide.createIcons();
        });

        // Request Templates
        $('#hd-request-template').on('change', function() {
            var val = $(this).val();
            if(!val) return;
            var desc = $(this).find('option:selected').data('desc');
            $('#hd-create-title').val(val);
            $('#hd-view-create-request textarea[name="description"]').val(desc);
        });
    });
</script>
