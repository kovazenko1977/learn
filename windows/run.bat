@echo off
title wesbooking Pro Elite - Локальный сервер
echo Запуск локального сервера wesbooking...
echo Сервер будет доступен по адресу: http://localhost:8080
echo Нажмите Ctrl+C для остановки сервера.
echo.

:: Проверка наличия PHP
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo [ОШИБКА] PHP не найден в системе.
    echo Пожалуйста, установите PHP 8.1+ и добавьте его в переменную PATH.
    echo Инструкции: https://windows.php.net/download/
    pause
    exit /b
)

:: Запуск встроенного сервера
php -S localhost:8080
pause
