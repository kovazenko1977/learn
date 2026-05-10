<?php
if (!isset($pageTitle)) $pageTitle = 'Панель управления';
$currentFile = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/../../admin/auth.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $pageTitle; ?> | Sanatorium Mobile</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root {
            --mobile-bg: #f8fafc;
            --mobile-primary: #0078d4;
            --mobile-glass: rgba(255, 255, 255, 0.9);
            --mobile-border: rgba(0, 0, 0, 0.05);
            --safe-area-bottom: env(safe-area-inset-bottom);
        }
        body.mobile-body {
            background: var(--mobile-bg);
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            padding-bottom: calc(70px + var(--safe-area-bottom));
            -webkit-tap-highlight-color: transparent;
        }
        .mobile-header {
            position: sticky;
            top: 0;
            background: var(--mobile-glass);
            backdrop-filter: blur(10px);
            z-index: 100;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--mobile-border);
        }
        .mobile-header h1 {
            font-size: 1.1rem;
            margin: 0;
            color: #1e293b;
        }
        .mobile-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--mobile-glass);
            backdrop-filter: blur(10px);
            border-top: 1px solid var(--mobile-border);
            display: flex;
            justify-content: space-around;
            padding: 10px 0 calc(10px + var(--safe-area-bottom));
            z-index: 1000;
        }
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #64748b;
            font-size: 0.65rem;
            gap: 4px;
        }
        .nav-item.active {
            color: var(--mobile-primary);
        }
        .nav-item svg {
            width: 22px;
            height: 22px;
        }
        .mobile-container {
            padding: 16px;
        }
        .m-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .m-card h2 {
            font-size: 1rem;
            margin-top: 0;
            margin-bottom: 12px;
        }
        .btn-m {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            width: 100%;
            box-sizing: border-box;
            border: none;
            cursor: pointer;
        }
        .btn-m-primary {
            background: var(--mobile-primary);
            color: white;
        }
        .badge-m {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-m-success { background: #dcfce7; color: #166534; }
        .badge-m-warning { background: #fef9c3; color: #854d0e; }
        .badge-m-danger { background: #fee2e2; color: #991b1b; }

        /* Stats grid for mobile */
        .m-stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .m-stat-item {
            background: #f1f5f9;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
        }
        .m-stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
        }
        .m-stat-label {
            font-size: 0.7rem;
            color: #64748b;
            margin-top: 2px;
        }
    </style>
</head>
<body class="mobile-body">
    <header class="mobile-header">
        <h1><?php echo $pageTitle; ?></h1>
        <div style="display: flex; align-items: center; gap: 8px;">
            <a href="../index.php" style="color: #64748b; font-size: 0.75rem; text-decoration: none; background: #f1f5f9; padding: 4px 8px; border-radius: 4px;">PC Версия</a>
            <div class="avatar" style="width: 32px; height: 32px; font-size: 0.9rem;"><?php echo mb_substr($_SESSION['full_name'] ?? 'U', 0, 1); ?></div>
        </div>
    </header>

    <div class="mobile-container">
