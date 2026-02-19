@echo off
title WES МЕД - Запуск системы
echo Запуск локального сервера PHP...
echo Пожалуйста, не закрывайте это окно во время работы с программой.

:: Проверка наличия PHP
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo ОШИБКА: PHP не найден в системе.
    echo Установите PHP и добавьте его в PATH.
    pause
    exit /b
)

:: Запуск PHP сервера в фоне (через старт)
start /b php -S localhost:8000 -t .

:: Ожидание запуска сервера
timeout /t 2 >nul

:: Поиск Edge или Chrome для запуска в режиме приложения
set "EDGE_PATH=C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
set "CHROME_PATH=C:\Program Files\Google\Chrome\Application\chrome.exe"

if exist "%EDGE_PATH%" (
    start "" "%EDGE_PATH%" --app=http://localhost:8000/login.php
) else if exist "%CHROME_PATH%" (
    start "" "%CHROME_PATH%" --app=http://localhost:8000/login.php
) else (
    echo Браузер для режима приложения не найден. Запуск в браузере по умолчанию...
    start http://localhost:8000/login.php
)

echo Система запущена.
