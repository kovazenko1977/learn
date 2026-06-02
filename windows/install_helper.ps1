# Вспомогательный скрипт установки wesbooking для Windows

Write-Host "Настройка wesbooking Pro Elite для локальной работы..." -ForegroundColor Cyan

$dataFolder = "..\data"
if (!(Test-Path $dataFolder)) {
    New-Item -ItemType Directory -Path $dataFolder
}

# Проверка прав записи
try {
    $testFile = Join-Path $dataFolder "test_write.tmp"
    "test" | Out-File $testFile
    Remove-Item $testFile
    Write-Host "[OK] Права на папку данных подтверждены." -ForegroundColor Green
} catch {
    Write-Host "[ОШИБКА] Нет прав записи в папку data/." -ForegroundColor Red
    exit
}

# Проверка PHP
$phpVersion = php -v
if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Найден PHP: $($phpVersion[0])" -ForegroundColor Green
} else {
    Write-Host "[ОШИБКА] PHP не установлен. Скачайте его с windows.php.net" -ForegroundColor Red
}

Write-Host "Готово! Используйте run.bat для запуска." -ForegroundColor Cyan
EOF
