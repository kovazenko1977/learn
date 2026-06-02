@echo off
setlocal enabledelayedexpansion

echo #######################################################
echo # wesbooking Pro Elite - Windows Installer             #
echo #######################################################
echo.

:: 1. Проверка наличия PHP
where php >nul 2>nul
if %ERRORLEVEL% equ 0 (
    echo [OK] PHP уже установлен.
) else (
    echo [!] PHP не найден. Попытка автоматической установки через winget...
    winget install -e --id PHP.PHP
    if %ERRORLEVEL% neq 0 (
        echo [ОШИБКА] Не удалось установить PHP автоматически.
        echo Пожалуйста, скачайте PHP вручную с https://windows.php.net/download/
        echo И добавьте путь к php.exe в переменную окружения PATH.
        pause
        exit /b 1
    )
    echo [OK] PHP установлен. Пожалуйста, перезапустите этот скрипт, чтобы применить изменения PATH.
    pause
    exit /b 0
)

:: 2. Запуск PowerShell помощника для настройки папок и прав
echo [*] Настройка структуры папок и прав доступа...
powershell -ExecutionPolicy Bypass -File "windows\install_helper.ps1"

:: 3. Создание ярлыка на рабочем столе
echo [*] Создание ярлыка на рабочем столе...
set "SCRIPT_PATH=%~dp0windows\run.bat"
set "ICON_PATH=%~dp0assets\favicon.ico"
set "SHORTCUT_NAME=wesbooking.lnk"

powershell -Command "$s=(New-Object -COM WScript.Shell).CreateShortcut([System.IO.Path]::Combine([Environment]::GetFolderPath('Desktop'), '%SHORTCUT_NAME%'));$s.TargetPath='%SCRIPT_PATH%';$s.WorkingDirectory='%~dp0';if(Test-Path '%ICON_PATH%'){$s.IconLocation='%ICON_PATH%'};$s.Save()"

echo.
echo #######################################################
echo # УСТАНОВКА ЗАВЕРШЕНА!                                #
echo #######################################################
echo.
echo Теперь вы можете запустить систему через ярлык "wesbooking"
echo на вашем рабочем столе или выполнив windows\run.bat.
echo.
pause
