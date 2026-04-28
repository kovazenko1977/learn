<?php
class Translator {
    private static $lang = 'ru';
    private static $strings = [
        'ru' => [
            'app_name' => 'Service CRM PRO',
            'login' => 'Вход в систему',
            'username' => 'Логин',
            'password' => 'Пароль',
            'enter' => 'Войти',
            'new_request' => 'Новая заявка',
            'my_requests' => 'Мои заявки',
            'dashboard' => 'Дашборд',
            'kanban' => 'Канбан',
            'users' => 'Сотрудники',
            'settings' => 'Настройки',
            'logout' => 'Выйти'
        ],
        'en' => [
            'app_name' => 'Service CRM PRO',
            'login' => 'Login',
            'username' => 'Username',
            'password' => 'Password',
            'enter' => 'Login',
            'new_request' => 'New Request',
            'my_requests' => 'My Requests',
            'dashboard' => 'Dashboard',
            'kanban' => 'Kanban',
            'users' => 'Users',
            'settings' => 'Settings',
            'logout' => 'Logout'
        ]
    ];

    public static function setLang($lang) {
        self::$lang = $lang;
    }

    public static function t($key) {
        return self::$strings[self::$lang][$key] ?? $key;
    }
}