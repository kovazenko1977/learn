@echo off
set PORT=8000
echo #######################################################
echo # wesbooking Pro Elite - Запуск сервера               #
echo #######################################################
echo.

:: 1. Убиваем процесс, если порт занят
echo [*] Проверка порта %PORT%...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :%PORT% ^| findstr LISTENING') do taskkill /f /pid %%a >nul 2>&1

:: 2. Открываем браузер (с небольшой задержкой, чтобы сервер успел подняться)
echo [*] Запуск браузера...
start http://localhost:%PORT%

:: 3. Запуск встроенного PHP сервера из корня проекта
echo [*] Сервер запущен на http://localhost:%PORT%
echo [!] Не закрывайте это окно во время работы с программой.
echo.

cd ..
php -S localhost:%PORT%
pause
