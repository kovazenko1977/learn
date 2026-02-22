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
        <h1>WES МЕД</h1>
        <div style="flex-grow:1"></div>
        <a href="login.php?logout=1" style="color: inherit;"><i data-lucide="log-out"></i></a>
    </div>
<?php endif; ?>
<main>
