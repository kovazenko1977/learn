<div class="wrap">
    <h1><?php _e('Helpdesk Enterprise v6.0', 'helpdesk-enterprise'); ?></h1>
    <p><?php _e('Добро пожаловать в корпоративную систему управления заявками.', 'helpdesk-enterprise'); ?></p>

    <div class="hd-admin-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-top: 24px;">
        <div class="hd-admin-stat-card" style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-top: 4px solid #2563eb; transition: transform 0.2s;">
            <span style="font-size: 32px; font-weight: 800; display: block; color: #1e293b;"><?php echo $stats['requests']; ?></span>
            <span style="color: #64748b; text-transform: uppercase; font-size: 12px; font-weight: 700; letter-spacing: 0.05em;"><?php _e('Всего заявок', 'helpdesk-enterprise'); ?></span>
        </div>
        <div class="hd-admin-stat-card" style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-top: 4px solid #ef4444;">
            <span style="font-size: 32px; font-weight: 800; display: block; color: #ef4444;"><?php echo $stats['overdue']; ?></span>
            <span style="color: #64748b; text-transform: uppercase; font-size: 12px; font-weight: 700; letter-spacing: 0.05em;"><?php _e('Просрочено', 'helpdesk-enterprise'); ?></span>
        </div>
        <div class="hd-admin-stat-card" style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-top: 4px solid #22c55e;">
            <span style="font-size: 32px; font-weight: 800; display: block; color: #1e293b;"><?php echo $stats['users']; ?></span>
            <span style="color: #64748b; text-transform: uppercase; font-size: 12px; font-weight: 700; letter-spacing: 0.05em;"><?php _e('Сотрудников', 'helpdesk-enterprise'); ?></span>
        </div>
        <div class="hd-admin-stat-card" style="background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-top: 4px solid #f59e0b;">
            <span style="font-size: 32px; font-weight: 800; display: block; color: #1e293b;"><?php echo $stats['departments']; ?></span>
            <span style="color: #64748b; text-transform: uppercase; font-size: 12px; font-weight: 700; letter-spacing: 0.05em;"><?php _e('Отделов', 'helpdesk-enterprise'); ?></span>
        </div>
    </div>

    <div style="margin-top: 40px; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0;"><?php _e('Справочник по шорткодам', 'helpdesk-enterprise'); ?></h2>
        <p><?php _e('Используйте эти шорткоды для создания автономных страниц управления на вашем сайте.', 'helpdesk-enterprise'); ?></p>

        <table class="widefat fixed striped" style="margin-top: 16px;">
            <thead>
                <tr>
                    <th style="width: 25%;"><?php _e('Шорткод', 'helpdesk-enterprise'); ?></th>
                    <th><?php _e('Описание и применение', 'helpdesk-enterprise'); ?></th>
                    <th style="width: 20%;"><?php _e('Доступ', 'helpdesk-enterprise'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[hd_dashboard]</code></td>
                    <td><?php _e('Основная панель управления: статистика, список заявок и детальный просмотр.', 'helpdesk-enterprise'); ?></td>
                    <td><?php _e('Все роли', 'helpdesk-enterprise'); ?></td>
                </tr>
                <tr>
                    <td><code>[hd_request_form]</code></td>
                    <td><?php _e('Автономная форма создания новой заявки с возможностью загрузки фото.', 'helpdesk-enterprise'); ?></td>
                    <td><?php _e('Ответственные / Админ', 'helpdesk-enterprise'); ?></td>
                </tr>
                <tr>
                    <td><code>[hd_request_list]</code></td>
                    <td><?php _e('Компактный список заявок с фильтрами.', 'helpdesk-enterprise'); ?></td>
                    <td><?php _e('Все роли', 'helpdesk-enterprise'); ?></td>
                </tr>
                <tr>
                    <td><code>[hd_admin_settings]</code></td>
                    <td><?php _e('Глобальные настройки: Telegram, компания, системные уведомления.', 'helpdesk-enterprise'); ?></td>
                    <td style="color: #ef4444; font-weight: 600;"><?php _e('Только Админ', 'helpdesk-enterprise'); ?></td>
                </tr>
                <tr>
                    <td><code>[hd_admin_users]</code></td>
                    <td><?php _e('Управление персоналом, регистрация новых сотрудников по номеру телефона.', 'helpdesk-enterprise'); ?></td>
                    <td style="color: #ef4444; font-weight: 600;"><?php _e('Только Админ', 'helpdesk-enterprise'); ?></td>
                </tr>
                <tr>
                    <td><code>[hd_admin_departments]</code></td>
                    <td><?php _e('Настройка структуры отделов, графиков работы и праздничных дней.', 'helpdesk-enterprise'); ?></td>
                    <td style="color: #ef4444; font-weight: 600;"><?php _e('Только Админ', 'helpdesk-enterprise'); ?></td>
                </tr>
                <tr>
                    <td><code>[hd_admin_categories]</code></td>
                    <td><?php _e('Управление категориями заявок и приоритетами SLA.', 'helpdesk-enterprise'); ?></td>
                    <td style="color: #ef4444; font-weight: 600;"><?php _e('Только Админ', 'helpdesk-enterprise'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 24px; color: #64748b; font-size: 13px;">
        <p><?php echo sprintf(__('Разработчик: %s | Версия: %s', 'helpdesk-enterprise'), '<strong>Kovazenko S.B.</strong>', '<strong>6.0</strong>'); ?></p>
    </div>
</div>
