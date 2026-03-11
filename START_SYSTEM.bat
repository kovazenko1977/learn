@echo off
setlocal enabledelayedexpansion
title WES MED - System Launch

:: Check for PHP
set "PHP_BIN=php"
where php >nul 2>nul
if %errorlevel% neq 0 (
    if exist "bin\php\php.exe" (
        set "PHP_BIN=bin\php\php.exe"
    ) else (
        echo PHP not found. Starting automatic installation...
        echo Creating directory bin\php...
        if not exist "bin\php" mkdir bin\php

        echo Downloading portable PHP 8.3... (This may take a minute)
        powershell -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; $url = 'https://windows.php.net/downloads/releases/php-8.3.3-nts-Win32-vs16-x64.zip'; $out = 'php.zip'; Invoke-WebRequest -Uri $url -OutFile $out"

        if not exist "php.zip" (
            echo ERROR: Failed to download PHP. Please check your internet connection.
            pause
            exit /b
        )

        echo Extracting files...
        powershell -Command "Expand-Archive -Path 'php.zip' -DestinationPath 'bin\php' -Force"
        del php.zip

        if exist "bin\php\php.exe" (
            set "PHP_BIN=bin\php\php.exe"
            echo PHP installed successfully.
        ) else (
            echo ERROR: Extraction failed or php.exe not found in bin\php.
            pause
            exit /b
        )
    )
)

echo Starting local PHP server...
echo Please do not close this window while using the program.

:: Start PHP server for medical-system folder
start /b "" %PHP_BIN% -S localhost:8000 -t medical-system/

:: Wait for server to start
timeout /t 2 >nul

:: Try to open in App mode using Edge or Chrome
set "EDGE_PATH=C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
set "CHROME_PATH=C:\Program Files\Google\Chrome\Application\chrome.exe"

if exist "%EDGE_PATH%" (
    start "" "%EDGE_PATH%" --app=http://localhost:8000/login.php
) else if exist "%CHROME_PATH%" (
    start "" "%CHROME_PATH%" --app=http://localhost:8000/login.php
) else (
    echo App-mode browser not found. Opening in default browser...
    start http://localhost:8000/login.php
)

echo System started.
