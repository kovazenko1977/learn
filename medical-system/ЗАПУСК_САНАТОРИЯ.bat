@echo off
setlocal enabledelayedexpansion
title WES МЕД - Автоматический запуск

set "PHP_DIR=%~dp0bin\php"
set "PHP_ZIP=%~dp0php.zip"
set "PORT=8000"

echo [1/4] Проверка окружения...

:: 1. Проверяем наличие PHP в папке bin
if exist "%PHP_DIR%\php.exe" (
    echo [OK] Портативный PHP найден.
    set "PHP_EXE=%PHP_DIR%\php.exe"
) else (
    :: 2. Проверяем системный PHP
    where php >nul 2>nul
    if %errorlevel% equ 0 (
        echo [OK] Системный PHP найден.
        set "PHP_EXE=php"
    ) else (
        echo [!] PHP не найден. Начинаю автоматическую загрузку...

        :: 3. Скачиваем портативный PHP (x64 Thread Safe)
        echo [2/4] Загрузка PHP 8.3 с windows.php.net...
        powershell -Command "& { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri 'https://windows.php.net/downloads/releases/archives/php-8.3.0-Win32-vs16-x64.zip' -OutFile '%PHP_ZIP%' }"

        if not exist "%PHP_ZIP%" (
            echo [ОШИБКА] Не удалось скачать PHP. Проверьте интернет-соединение.
            pause
            exit /b
        )

        echo [3/4] Распаковка архива...
        powershell -Command "Expand-Archive -Path '%PHP_ZIP%' -DestinationPath '%PHP_DIR%' -Force"
        del "%PHP_ZIP%"

        if exist "%PHP_DIR%\php.exe" (
            echo [OK] PHP успешно установлен в папку bin.
            set "PHP_EXE=%PHP_DIR%\php.exe"
        ) else (
            echo [ОШИБКА] Не удалось распаковать PHP.
            pause
            exit /b
        )
    )
)

echo [4/4] Запуск сервера и приложения...

:: Запуск PHP сервера в фоновом окне
start "WES MED SERVER" /min "%PHP_EXE%" -S localhost:%PORT% -t "%~dp0."

:: Ожидание инициализации
timeout /t 2 >nul

:: Поиск браузера для запуска в режиме APP
set "EDGE_PATH=C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
set "CHROME_PATH=C:\Program Files\Google\Chrome\Application\chrome.exe"

if exist "%EDGE_PATH%" (
    start "" "%EDGE_PATH%" --app=http://localhost:%PORT%/login.php
) else if exist "%CHROME_PATH%" (
    start "" "%CHROME_PATH%" --app=http://localhost:%PORT%/login.php
) else (
    start http://localhost:%PORT%/login.php
)

echo ==================================================
echo СИСТЕМА ЗАПУЩЕНА
echo Адрес: http://localhost:%PORT%
echo Не закрывайте черное окно сервера (свернуто в панель).
echo ==================================================
timeout /t 5
