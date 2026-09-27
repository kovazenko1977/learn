<?php

require_once __DIR__ . '/../includes/Storage.php';
require_once __DIR__ . '/../includes/Functions.php';
require_once __DIR__ . '/../includes/Auth.php';

$pdo = Storage::getPDO();
$action = $_REQUEST['action'] ?? '';

// Helpers for JSON body or POST
$jsonBody = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $rawInput = file_get_contents('php://input');
    $jsonBody = json_decode($rawInput, true) ?: [];
}

function getParam(string $key, $default = '') {
    global $jsonBody;
    if (isset($jsonBody[$key])) return $jsonBody[$key];
    if (isset($_POST[$key])) return $_POST[$key];
    if (isset($_GET[$key])) return $_GET[$key];
    return $default;
}

switch ($action) {

    // --- AUTHENTICATION ENDPOINTS ---

    case 'auth_register':
        $phone = getParam('phone');
        $password = getParam('password');
        $fullName = getParam('full_name');
        $res = Auth::registerUser($phone, $password, $fullName);
        jsonResponse($res);
        break;

    case 'auth_login':
        $phone = trim(getParam('phone'));
        $password = trim(getParam('password'));

        // First check if credentials match an Admin user
        $adminRes = Auth::loginAdmin($phone, $password);
        if ($adminRes['success']) {
            $adminRes['is_admin'] = true;
            jsonResponse($adminRes);
        }

        // Otherwise check regular user account
        $userRes = Auth::loginUser($phone, $password);
        $userRes['is_admin'] = false;
        jsonResponse($userRes);
        break;

    case 'auth_logout':
        Auth::logoutUser();
        jsonResponse(['success' => true]);
        break;

    case 'auth_current':
        $user = Auth::getCurrentUser();
        $isAdmin = Auth::isAdmin();
        jsonResponse(['success' => true, 'user' => $user, 'is_admin' => $isAdmin]);
        break;

    case 'admin_login':
        $login = getParam('login');
        $password = getParam('password');
        $res = Auth::loginAdmin($login, $password);
        jsonResponse($res);
        break;

    case 'admin_logout':
        Auth::logoutAdmin();
        jsonResponse(['success' => true]);
        break;

    case 'admin_update_profile':
        $newLogin = getParam('login');
        $newPassword = getParam('password');
        $res = Auth::updateAdminCredentials($newLogin, $newPassword);
        jsonResponse($res);
        break;

    // --- PAGE MANAGEMENT ENDPOINTS ---

    case 'search_pages':
        $query = trim(getParam('q'));
        $birthYear = trim(getParam('birth_year'));
        $deathYear = trim(getParam('death_year'));

        $sql = "SELECT id, code, full_name, birth_date, death_date, photo, epitaph, cemetery, section, grave_num, views, created_at FROM pages WHERE status = 'approved'";
        $params = [];

        if (!empty($query)) {
            $sql .= " AND full_name LIKE ?";
            $params[] = '%' . $query . '%';
        }

        if (!empty($birthYear)) {
            $sql .= " AND (birth_date LIKE ? OR birth_date LIKE ?)";
            $params[] = '%' . $birthYear . '%';
            $params[] = $birthYear . '-%';
        }

        if (!empty($deathYear)) {
            $sql .= " AND (death_date LIKE ? OR death_date LIKE ?)";
            $params[] = '%' . $deathYear . '%';
            $params[] = $deathYear . '-%';
        }

        $sql .= " ORDER BY created_at DESC LIMIT 100";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $pages = $stmt->fetchAll();

        jsonResponse(['success' => true, 'pages' => $pages]);
        break;

    case 'get_page':
        $code = getParam('code');
        $id = getParam('id');

        if (!empty($code)) {
            $stmt = $pdo->prepare("SELECT * FROM pages WHERE code = ?");
            $stmt->execute([$code]);
        } else if (!empty($id)) {
            $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            jsonResponse(['success' => false, 'error' => 'Не указан ID или код страницы'], 400);
        }

        $page = $stmt->fetch();
        if (!$page) {
            jsonResponse(['success' => false, 'error' => 'Страница не найдена'], 404);
        }

        // Check view permissions: approved page is public; pending/rejected visible to owner or admin
        $currentUser = Auth::getCurrentUser();
        $isAdmin = Auth::isAdmin();

        if ($page['status'] !== 'approved') {
            if (!$isAdmin && (!$currentUser || $currentUser['id'] != $page['user_id'])) {
                jsonResponse(['success' => false, 'error' => 'Страница находится на модерации и пока недоступна'], 403);
            }
        }

        // Increment view counter for approved pages
        if ($page['status'] === 'approved') {
            $pdo->prepare("UPDATE pages SET views = views + 1 WHERE id = ?")->execute([$page['id']]);
            $page['views']++;
        }

        // Fetch relatives
        $relStmt = $pdo->prepare("SELECT * FROM relatives WHERE page_id = ?");
        $relStmt->execute([$page['id']]);
        $relatives = $relStmt->fetchAll();

        // Fetch additional photos
        $photoStmt = $pdo->prepare("SELECT photo_path FROM page_photos WHERE page_id = ? ORDER BY sort_order ASC, id ASC");
        $photoStmt->execute([$page['id']]);
        $photosList = $photoStmt->fetchAll(PDO::FETCH_COLUMN);

        // If page has a main photo and it's not in photosList, prepend it
        if (!empty($page['photo']) && !in_array($page['photo'], $photosList)) {
            array_unshift($photosList, $page['photo']);
        }

        // Fetch candle count & recent candles
        $candleStmt = $pdo->prepare("SELECT COUNT(*) as total FROM candles WHERE page_id = ?");
        $candleStmt->execute([$page['id']]);
        $candleCount = $candleStmt->fetchColumn();

        // Fetch condolences
        $condStmt = $pdo->prepare("SELECT * FROM condolences WHERE page_id = ? ORDER BY created_at DESC");
        $condStmt->execute([$page['id']]);
        $condolences = $condStmt->fetchAll();

        jsonResponse([
            'success' => true,
            'page' => $page,
            'relatives' => $relatives,
            'photos' => $photosList,
            'candle_count' => $candleCount,
            'condolences' => $condolences,
            'permalink' => getBaseUrl() . '/page.php?code=' . $page['code']
        ]);
        break;

    case 'create_page':
        $user = Auth::getCurrentUser();
        if (!$user) {
            jsonResponse(['success' => false, 'error' => 'Для создания страницы необходимо войти в личный кабинет'], 401);
        }

        $fullName = sanitizeInput(getParam('full_name'));
        if (empty($fullName)) {
            jsonResponse(['success' => false, 'error' => 'Укажите ФИО усопшего'], 400);
        }

        $birthDate = sanitizeInput(getParam('birth_date'));
        $deathDate = sanitizeInput(getParam('death_date'));
        $epitaph = sanitizeInput(getParam('epitaph'));
        $biography = sanitizeInput(getParam('biography'));
        $cemetery = sanitizeInput(getParam('cemetery'));
        $section = sanitizeInput(getParam('section'));
        $graveNum = sanitizeInput(getParam('grave_num'));
        $latitude = floatval(getParam('latitude', 0));
        $longitude = floatval(getParam('longitude', 0));

        // Photo uploads (up to 10 photos)
        $uploadedPhotos = [];
        if (isset($_FILES['photos'])) {
            $uploadedPhotos = uploadMultipleImages($_FILES['photos'], 10);
        } else if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $single = uploadImage($_FILES['photo']);
            if ($single) $uploadedPhotos[] = $single;
        }

        $photoPath = !empty($uploadedPhotos) ? $uploadedPhotos[0] : '';

        // Auto approve setting check
        $stmtSetting = $pdo->prepare("SELECT value FROM settings WHERE key = 'auto_approve'");
        $stmtSetting->execute();
        $autoApprove = $stmtSetting->fetchColumn() === '1';

        $status = $autoApprove ? 'approved' : 'pending';
        $code = generatePageCode(8);

        $stmt = $pdo->prepare("INSERT INTO pages
            (code, user_id, full_name, birth_date, death_date, photo, epitaph, biography, cemetery, section, grave_num, latitude, longitude, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $code, $user['id'], $fullName, $birthDate, $deathDate, $photoPath, $epitaph, $biography,
            $cemetery, $section, $graveNum, $latitude, $longitude, $status
        ]);
        $pageId = $pdo->lastInsertId();

        // Save multiple photos to page_photos table
        if (!empty($uploadedPhotos)) {
            $photoInsertStmt = $pdo->prepare("INSERT INTO page_photos (page_id, photo_path, sort_order) VALUES (?, ?, ?)");
            foreach ($uploadedPhotos as $order => $path) {
                $photoInsertStmt->execute([$pageId, $path, $order]);
            }
        }

        // Process Relatives
        $relativesRaw = getParam('relatives');
        if (is_string($relativesRaw)) {
            $relativesRaw = json_decode($relativesRaw, true) ?: [];
        }
        if (is_array($relativesRaw)) {
            $relStmt = $pdo->prepare("INSERT INTO relatives (page_id, relation_type, name, phone, email, is_public) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($relativesRaw as $rel) {
                if (!empty($rel['name'])) {
                    $relStmt->execute([
                        $pageId,
                        sanitizeInput($rel['relation_type'] ?? 'Родственник'),
                        sanitizeInput($rel['name']),
                        sanitizeInput($rel['phone'] ?? ''),
                        sanitizeInput($rel['email'] ?? ''),
                        isset($rel['is_public']) ? intval($rel['is_public']) : 1
                    ]);
                }
            }
        }

        // Send admin notification
        $notifStmt = $pdo->prepare("INSERT INTO admin_notifications (page_id, message) VALUES (?, ?)");
        $notifMsg = "Создана новая страница памяти №{$pageId} ({$fullName}). Требуется проверка администратором.";
        $notifStmt->execute([$pageId, $notifMsg]);

        jsonResponse([
            'success' => true,
            'message' => $autoApprove ? 'Страница успешно опубликована' : 'Страница отправлена на модерацию администратору',
            'code' => $code,
            'id' => $pageId,
            'status' => $status
        ]);
        break;

    case 'update_page':
        $user = Auth::getCurrentUser();
        $isAdmin = Auth::isAdmin();

        if (!$user && !$isAdmin) {
            jsonResponse(['success' => false, 'error' => 'Авторизуйтесь для изменения данных'], 401);
        }

        $pageId = intval(getParam('id'));
        $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
        $stmt->execute([$pageId]);
        $existingPage = $stmt->fetch();

        if (!$existingPage) {
            jsonResponse(['success' => false, 'error' => 'Страница не найдена'], 404);
        }

        if (!$isAdmin && $existingPage['user_id'] != $user['id']) {
            jsonResponse(['success' => false, 'error' => 'Нет прав для редактирования этой страницы'], 403);
        }

        $fullName = sanitizeInput(getParam('full_name', $existingPage['full_name']));
        $birthDate = sanitizeInput(getParam('birth_date', $existingPage['birth_date']));
        $deathDate = sanitizeInput(getParam('death_date', $existingPage['death_date']));
        $epitaph = sanitizeInput(getParam('epitaph', $existingPage['epitaph']));
        $biography = sanitizeInput(getParam('biography', $existingPage['biography']));
        $cemetery = sanitizeInput(getParam('cemetery', $existingPage['cemetery']));
        $section = sanitizeInput(getParam('section', $existingPage['section']));
        $graveNum = sanitizeInput(getParam('grave_num', $existingPage['grave_num']));
        $latitude = floatval(getParam('latitude', $existingPage['latitude']));
        $longitude = floatval(getParam('longitude', $existingPage['longitude']));

        $uploadedPhotos = [];
        if (isset($_FILES['photos'])) {
            $uploadedPhotos = uploadMultipleImages($_FILES['photos'], 10);
        } else if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $single = uploadImage($_FILES['photo']);
            if ($single) $uploadedPhotos[] = $single;
        }

        $photoPath = !empty($uploadedPhotos) ? $uploadedPhotos[0] : $existingPage['photo'];

        if (!empty($uploadedPhotos)) {
            $photoInsertStmt = $pdo->prepare("INSERT INTO page_photos (page_id, photo_path, sort_order) VALUES (?, ?, ?)");
            foreach ($uploadedPhotos as $order => $path) {
                $photoInsertStmt->execute([$pageId, $path, $order]);
            }
        }

        // If updated by user, switch status to 'pending' unless admin edited it
        $newStatus = $isAdmin ? $existingPage['status'] : 'pending';

        $stmt = $pdo->prepare("UPDATE pages SET
            full_name = ?, birth_date = ?, death_date = ?, photo = ?, epitaph = ?,
            biography = ?, cemetery = ?, section = ?, grave_num = ?, latitude = ?, longitude = ?,
            status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?");
        $stmt->execute([
            $fullName, $birthDate, $deathDate, $photoPath, $epitaph,
            $biography, $cemetery, $section, $graveNum, $latitude, $longitude,
            $newStatus, $pageId
        ]);

        // Update relatives
        $relativesRaw = getParam('relatives');
        if ($relativesRaw !== null && $relativesRaw !== '') {
            if (is_string($relativesRaw)) {
                $relativesRaw = json_decode($relativesRaw, true) ?: [];
            }
            if (is_array($relativesRaw)) {
                // Delete existing relatives and re-insert
                $pdo->prepare("DELETE FROM relatives WHERE page_id = ?")->execute([$pageId]);
                $relStmt = $pdo->prepare("INSERT INTO relatives (page_id, relation_type, name, phone, email, is_public) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($relativesRaw as $rel) {
                    if (!empty($rel['name'])) {
                        $relStmt->execute([
                            $pageId,
                            sanitizeInput($rel['relation_type'] ?? 'Родственник'),
                            sanitizeInput($rel['name']),
                            sanitizeInput($rel['phone'] ?? ''),
                            sanitizeInput($rel['email'] ?? ''),
                            isset($rel['is_public']) ? intval($rel['is_public']) : 1
                        ]);
                    }
                }
            }
        }

        if (!$isAdmin && $newStatus === 'pending') {
            $pdo->prepare("INSERT INTO admin_notifications (page_id, message) VALUES (?, ?)")
                ->execute([$pageId, "Страница памяти №{$pageId} ({$fullName}) была обновлена пользователем и ожидает повторной проверки."]);
        }

        jsonResponse(['success' => true, 'message' => 'Изменения успешно сохранены', 'status' => $newStatus]);
        break;

    case 'user_get_pages':
        $user = Auth::getCurrentUser();
        if (!$user) {
            jsonResponse(['success' => false, 'error' => 'Требуется авторизация'], 401);
        }

        $stmt = $pdo->prepare("SELECT * FROM pages WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user['id']]);
        $pages = $stmt->fetchAll();

        jsonResponse(['success' => true, 'pages' => $pages]);
        break;

    // --- PUBLIC INTERACTIONS ---

    case 'add_candle':
        $pageId = intval(getParam('page_id'));
        $authorName = sanitizeInput(getParam('author_name', 'Гость'));

        $stmt = $pdo->prepare("SELECT id FROM pages WHERE id = ? AND status = 'approved'");
        $stmt->execute([$pageId]);
        if (!$stmt->fetch()) {
            jsonResponse(['success' => false, 'error' => 'Страница не найдена'], 404);
        }

        $pdo->prepare("INSERT INTO candles (page_id, author_name) VALUES (?, ?)")->execute([$pageId, $authorName]);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM candles WHERE page_id = ?");
        $countStmt->execute([$pageId]);
        $candleCount = $countStmt->fetchColumn();

        jsonResponse(['success' => true, 'message' => 'Свеча зажжена', 'candle_count' => $candleCount]);
        break;

    case 'add_condolence':
        $pageId = intval(getParam('page_id'));
        $authorName = sanitizeInput(getParam('author_name', 'Гость'));
        $message = sanitizeInput(getParam('message'));

        if (empty($message)) {
            jsonResponse(['success' => false, 'error' => 'Напишите текст соболезнования'], 400);
        }

        $stmt = $pdo->prepare("SELECT id FROM pages WHERE id = ? AND status = 'approved'");
        $stmt->execute([$pageId]);
        if (!$stmt->fetch()) {
            jsonResponse(['success' => false, 'error' => 'Страница не найдена'], 404);
        }

        $pdo->prepare("INSERT INTO condolences (page_id, author_name, message) VALUES (?, ?, ?)")
            ->execute([$pageId, $authorName, $message]);

        jsonResponse(['success' => true, 'message' => 'Ваше соболезнование опубликовано']);
        break;

    // --- ADMIN ENDPOINTS ---

    case 'admin_get_pages':
        if (!Auth::isAdmin()) {
            jsonResponse(['success' => false, 'error' => 'Доступ только для администратора'], 403);
        }

        $statusFilter = getParam('status');
        $sql = "SELECT p.*, u.phone as owner_phone, u.full_name as owner_name FROM pages p LEFT JOIN users u ON p.user_id = u.id";
        $params = [];

        if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
            $sql .= " WHERE p.status = ?";
            $params[] = $statusFilter;
        }

        $sql .= " ORDER BY p.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $pages = $stmt->fetchAll();

        jsonResponse(['success' => true, 'pages' => $pages]);
        break;

    case 'admin_approve_page':
        if (!Auth::isAdmin()) {
            jsonResponse(['success' => false, 'error' => 'Доступ только для администратора'], 403);
        }

        $pageId = intval(getParam('id'));
        $stmt = $pdo->prepare("UPDATE pages SET status = 'approved', rejection_reason = '' WHERE id = ?");
        $stmt->execute([$pageId]);

        jsonResponse(['success' => true, 'message' => 'Страница одобрена и опубликована']);
        break;

    case 'admin_reject_page':
        if (!Auth::isAdmin()) {
            jsonResponse(['success' => false, 'error' => 'Доступ только для администратора'], 403);
        }

        $pageId = intval(getParam('id'));
        $reason = sanitizeInput(getParam('reason', 'Не соответствует правилам публикаций'));

        $stmt = $pdo->prepare("UPDATE pages SET status = 'rejected', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $pageId]);

        jsonResponse(['success' => true, 'message' => 'Страница отклонена']);
        break;

    case 'admin_delete_page':
        if (!Auth::isAdmin()) {
            jsonResponse(['success' => false, 'error' => 'Доступ только для администратора'], 403);
        }

        $pageId = intval(getParam('id'));
        $pdo->prepare("DELETE FROM pages WHERE id = ?")->execute([$pageId]);

        jsonResponse(['success' => true, 'message' => 'Страница успешно удалена']);
        break;

    case 'admin_get_notifications':
        if (!Auth::isAdmin()) {
            jsonResponse(['success' => false, 'error' => 'Доступ только для администратора'], 403);
        }

        $stmt = $pdo->query("SELECT n.*, p.full_name as page_name, p.code as page_code FROM admin_notifications n LEFT JOIN pages p ON n.page_id = p.id ORDER BY n.created_at DESC LIMIT 50");
        $notifications = $stmt->fetchAll();

        $unreadCount = $pdo->query("SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0")->fetchColumn();

        jsonResponse(['success' => true, 'notifications' => $notifications, 'unread_count' => $unreadCount]);
        break;

    case 'admin_mark_notifications_read':
        if (!Auth::isAdmin()) {
            jsonResponse(['success' => false, 'error' => 'Доступ только для администратора'], 403);
        }

        $pdo->exec("UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0");
        jsonResponse(['success' => true]);
        break;

    case 'admin_get_users':
        if (!Auth::isAdmin()) {
            jsonResponse(['success' => false, 'error' => 'Доступ только для администратора'], 403);
        }

        $stmt = $pdo->query("SELECT u.id, u.phone, u.full_name, u.created_at, COUNT(p.id) as pages_count FROM users u LEFT JOIN pages p ON u.id = p.user_id GROUP BY u.id ORDER BY u.created_at DESC");
        $users = $stmt->fetchAll();

        jsonResponse(['success' => true, 'users' => $users]);
        break;

    case 'admin_get_settings':
        if (!Auth::isAdmin()) {
            jsonResponse(['success' => false, 'error' => 'Доступ только для администратора'], 403);
        }

        $stmt = $pdo->query("SELECT key, value FROM settings");
        $settingsRaw = $stmt->fetchAll();
        $settings = [];
        foreach ($settingsRaw as $row) {
            $settings[$row['key']] = $row['value'];
        }

        jsonResponse(['success' => true, 'settings' => $settings]);
        break;

    case 'admin_save_settings':
        if (!Auth::isAdmin()) {
            jsonResponse(['success' => false, 'error' => 'Доступ только для администратора'], 403);
        }

        $settings = getParam('settings', []);
        if (is_array($settings)) {
            $stmt = $pdo->prepare("INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value");
            foreach ($settings as $k => $v) {
                $stmt->execute([sanitizeInput($k), sanitizeInput($v)]);
            }
        }

        jsonResponse(['success' => true, 'message' => 'Настройки сохранены']);
        break;

    case 'admin_export_backup':
        if (!Auth::isAdmin()) {
            jsonResponse(['success' => false, 'error' => 'Доступ только для администратора'], 403);
        }

        $backupData = [
            'version' => '1.0',
            'exported_at' => date('Y-m-d H:i:s'),
            'users' => $pdo->query("SELECT * FROM users")->fetchAll(),
            'admin_users' => $pdo->query("SELECT id, login, password_hash, updated_at FROM admin_users")->fetchAll(),
            'pages' => $pdo->query("SELECT * FROM pages")->fetchAll(),
            'relatives' => $pdo->query("SELECT * FROM relatives")->fetchAll(),
            'condolences' => $pdo->query("SELECT * FROM condolences")->fetchAll(),
            'candles' => $pdo->query("SELECT * FROM candles")->fetchAll(),
            'admin_notifications' => $pdo->query("SELECT * FROM admin_notifications")->fetchAll(),
            'settings' => $pdo->query("SELECT * FROM settings")->fetchAll()
        ];

        $filename = 'memory_backup_' . date('Y-m-d_H-i-s') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode($backupData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;

    case 'admin_import_backup':
        if (!Auth::isAdmin()) {
            jsonResponse(['success' => false, 'error' => 'Доступ только для администратора'], 403);
        }

        $jsonStr = '';
        if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
            $jsonStr = file_get_contents($_FILES['backup_file']['tmp_name']);
        } else {
            $jsonStr = getParam('backup_json');
        }

        if (empty($jsonStr)) {
            jsonResponse(['success' => false, 'error' => 'Файл резервной копии не передан'], 400);
        }

        $data = json_decode($jsonStr, true);
        if (!is_array($data) || empty($data['pages'])) {
            jsonResponse(['success' => false, 'error' => 'Некорректная структура файла резервной копии'], 400);
        }

        $pdo->beginTransaction();
        try {
            // Clear existing tables
            $pdo->exec("DELETE FROM admin_notifications");
            $pdo->exec("DELETE FROM candles");
            $pdo->exec("DELETE FROM condolences");
            $pdo->exec("DELETE FROM relatives");
            $pdo->exec("DELETE FROM pages");
            $pdo->exec("DELETE FROM users");
            $pdo->exec("DELETE FROM settings");

            // Restore Users
            if (!empty($data['users'])) {
                $stmt = $pdo->prepare("INSERT INTO users (id, phone, password_hash, full_name, created_at) VALUES (?, ?, ?, ?, ?)");
                foreach ($data['users'] as $u) {
                    $stmt->execute([$u['id'], $u['phone'], $u['password_hash'], $u['full_name'] ?? '', $u['created_at'] ?? date('Y-m-d H:i:s')]);
                }
            }

            // Restore Pages
            if (!empty($data['pages'])) {
                $stmt = $pdo->prepare("INSERT INTO pages (id, code, user_id, full_name, birth_date, death_date, photo, epitaph, biography, cemetery, section, grave_num, latitude, longitude, status, rejection_reason, views, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['pages'] as $p) {
                    $stmt->execute([
                        $p['id'], $p['code'], $p['user_id'], $p['full_name'], $p['birth_date'] ?? '', $p['death_date'] ?? '',
                        $p['photo'] ?? '', $p['epitaph'] ?? '', $p['biography'] ?? '', $p['cemetery'] ?? '', $p['section'] ?? '',
                        $p['grave_num'] ?? '', $p['latitude'] ?? 0, $p['longitude'] ?? 0, $p['status'] ?? 'approved',
                        $p['rejection_reason'] ?? '', $p['views'] ?? 0, $p['created_at'] ?? date('Y-m-d H:i:s'), $p['updated_at'] ?? date('Y-m-d H:i:s')
                    ]);
                }
            }

            // Restore Relatives
            if (!empty($data['relatives'])) {
                $stmt = $pdo->prepare("INSERT INTO relatives (id, page_id, relation_type, name, phone, email, is_public) VALUES (?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['relatives'] as $r) {
                    $stmt->execute([$r['id'], $r['page_id'], $r['relation_type'] ?? '', $r['name'], $r['phone'] ?? '', $r['email'] ?? '', $r['is_public'] ?? 1]);
                }
            }

            // Restore Condolences
            if (!empty($data['condolences'])) {
                $stmt = $pdo->prepare("INSERT INTO condolences (id, page_id, author_name, message, created_at) VALUES (?, ?, ?, ?, ?)");
                foreach ($data['condolences'] as $c) {
                    $stmt->execute([$c['id'], $c['page_id'], $c['author_name'], $c['message'], $c['created_at'] ?? date('Y-m-d H:i:s')]);
                }
            }

            // Restore Candles
            if (!empty($data['candles'])) {
                $stmt = $pdo->prepare("INSERT INTO candles (id, page_id, author_name, created_at) VALUES (?, ?, ?, ?)");
                foreach ($data['candles'] as $cd) {
                    $stmt->execute([$cd['id'], $cd['page_id'], $cd['author_name'] ?? 'Гость', $cd['created_at'] ?? date('Y-m-d H:i:s')]);
                }
            }

            // Restore Notifications
            if (!empty($data['admin_notifications'])) {
                $stmt = $pdo->prepare("INSERT INTO admin_notifications (id, page_id, message, is_read, created_at) VALUES (?, ?, ?, ?, ?)");
                foreach ($data['admin_notifications'] as $an) {
                    $stmt->execute([$an['id'], $an['page_id'], $an['message'], $an['is_read'] ?? 0, $an['created_at'] ?? date('Y-m-d H:i:s')]);
                }
            }

            // Restore Settings
            if (!empty($data['settings'])) {
                $stmt = $pdo->prepare("INSERT INTO settings (key, value) VALUES (?, ?)");
                foreach ($data['settings'] as $s) {
                    $stmt->execute([$s['key'], $s['value']]);
                }
            }

            $pdo->commit();
            jsonResponse(['success' => true, 'message' => 'Данные системы успешно восстановлены из резервной копии']);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'error' => 'Ошибка при восстановлении данных: ' . $e->getMessage()], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Неизвестное действие'], 400);
        break;
}
