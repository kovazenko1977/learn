<?php
/**
 * Корневой файл для запуска приложения.
 * Перенаправляет на основной интерфейс.
 */

// Простейшая проверка окружения
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    die("Ошибка: Требуется PHP версии 8.1 или выше. Текущая версия: " . PHP_VERSION);
}

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die("Ошибка: Зависимости не установлены. Запустите 'composer install'.");
}

// Перенаправление на основной интерфейс
header('Location: public/index.php');
exit;
