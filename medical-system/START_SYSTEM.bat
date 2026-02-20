@echo off
setlocal enabledelayedexpansion
title WES MED - Automated Launch

set "PHP_DIR=%~dp0bin\php"
set "PHP_ZIP=%~dp0php.zip"
set "PORT=8000"

echo [1/4] Checking environment...

if exist "%PHP_DIR%\php.exe" (
    echo [OK] Portable PHP found.
    set "PHP_EXE=%PHP_DIR%\php.exe"
) else (
    where php >nul 2>nul
    if %errorlevel% equ 0 (
        echo [OK] System PHP found.
        set "PHP_EXE=php"
    ) else (
        echo [!] PHP not found. Starting automatic download...
        echo [2/4] Downloading PHP 8.3 from windows.php.net...
        powershell -Command "& { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri 'https://windows.php.net/downloads/releases/archives/php-8.3.0-Win32-vs16-x64.zip' -OutFile '%PHP_ZIP%' }"

        if not exist "%PHP_ZIP%" (
            echo [ERROR] Failed to download PHP. Check your internet connection.
            pause
            exit /b
        )

        echo [3/4] Extracting archive...
        powershell -Command "Expand-Archive -Path '%PHP_ZIP%' -DestinationPath '%PHP_DIR%' -Force"
        del "%PHP_ZIP%"

        if exist "%PHP_DIR%\php.exe" (
            echo [OK] PHP successfully installed in bin folder.
            set "PHP_EXE=%PHP_DIR%\php.exe"
        ) else (
            echo [ERROR] Extraction failed.
            pause
            exit /b
        )
    )
)

echo [4/4] Starting server and application...

start "WES MED SERVER" /min "%PHP_EXE%" -S localhost:%PORT% -t "%~dp0."
timeout /t 2 >nul

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
echo SYSTEM STARTED
echo URL: http://localhost:%PORT%
echo ==================================================
timeout /t 5
