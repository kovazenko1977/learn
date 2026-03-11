<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>WES МЕД Mobile</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0078d4">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/material.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js');
            });
        }
    </script>
</head>
<body>
<?php if (\Medical\Core\Auth::isLoggedIn()): ?>
    <div class="md-header">
        <i data-lucide="menu"></i>
        <h1 style="font-weight: 600; letter-spacing: -0.5px;">WES МЕД</h1>
        <div style="flex-grow:1; display: flex; justify-content: center;">
            <div id="mobile-header-clock" style="font-family: 'Roboto Mono', monospace; font-size: 0.9rem; font-weight: 500; color: var(--md-primary); background: rgba(103, 80, 164, 0.08); padding: 2px 8px; border-radius: 4px;">00:00</div>
        </div>
        <a href="login.php?logout=1" style="color: inherit; opacity: 0.7;"><i data-lucide="log-out"></i></a>
    </div>
    <script>
        function updateMobileClock() {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const el = document.getElementById('mobile-header-clock');
            if (el) el.textContent = `${h}:${m}`;
        }
        setInterval(updateMobileClock, 10000);
        updateMobileClock();
    </script>
<?php endif; ?>

<div id="global-preloader" class="mobile-preloader">
    <div class="md-spinner"></div>
    <div style="margin-top: 16px; font-size: 14px; color: var(--md-secondary);">Загрузка...</div>
    <script>
        window.hidePreloader = function() {
            const p = document.getElementById('global-preloader');
            if (p) p.style.display = 'none';
        };
        window.addEventListener('load', hidePreloader);
        document.addEventListener('DOMContentLoaded', () => setTimeout(hidePreloader, 1000));
        setTimeout(hidePreloader, 3000); // Failsafe
    </script>
</div>

<main>
