<?php
/**
 * Корневой файл для запуска приложения.
 * Перенаправляет на основной интерфейс в папке public.
 */

$target = 'public/index.php';
if (file_exists($target)) {
    header('Location: ' . $target);
} else {
    echo "Ошибка: Файл $target не найден.";
}
exit;
