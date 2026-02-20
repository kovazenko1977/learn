@echo off
setlocal enabledelayedexpansion
title WES МЕД - Запуск системы

:: Проверка наличия PHP
set "PHP_BIN=php"
where php >nul 2>nul
if %errorlevel% neq 0 (
    if exist "bin\php\php.exe" (
        set "PHP_BIN=bin\php\php.exe"
    ) else (
        echo PHP не найден. Начинаем автоматическую установку...
        echo Создание папки bin\php...
        if not exist "bin\php" mkdir bin\php

        echo Загрузка переносной версии PHP 8.3... (Это может занять минуту)
        powershell -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $url = 'https://windows.php.net/downloads/releases/php-8.3.3-nts-Win32-vs16-x64.zip'; $out = 'php.zip'; Invoke-WebRequest -Uri $url -OutFile $out"

        if not exist "php.zip" (
            echo ОШИБКА: Не удалось загрузить PHP. Проверьте подключение к интернету.
            pause
            exit /b
        )

        echo Распаковка файлов...
        powershell -Command "Expand-Archive -Path 'php.zip' -DestinationPath 'bin\php' -Force"
        del php.zip

        if exist "bin\php\php.exe" (
            set "PHP_BIN=bin\php\php.exe"
            echo PHP успешно установлен.
        ) else (
            echo ОШИБКА: Ошибка распаковки или php.exe не найден в bin\php.
            pause
            exit /b
        )
    )
)

echo Запуск локального сервера PHP...
echo Пожалуйста, не закрывайте это окно во время работы с программой.

:: Запуск PHP сервера для папки medical-system
start /b "" %PHP_BIN% -S localhost:8000 -t medical-system/

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
