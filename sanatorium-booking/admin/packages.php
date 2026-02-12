<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;

$store = new JsonStore(__DIR__ . '/../data');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_package') {
        $data = [
            'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'base_price' => (float)$_POST['base_price'],
            'duration_days' => (int)$_POST['duration_days'],
            'procedure_ids' => $_POST['procedure_ids'] ?? []
        ];
        $store->save('packages', $data);
    } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $store->delete('packages', (int)$_POST['id']);
    }
    header('Location: packages.php');
    exit;
}

$packages = $store->findAll('packages');
$procedures = $store->findAll('procedures');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление пакетами</title>
    <link rel="stylesheet" href="../public/assets/css/admin.css">
</head>
<body>
    <header>
        <h1>Управление Санаторием</h1>
        <nav>
            <a href="dashboard.php">Бронирования</a>
            <a href="create_booking.php">Новое бронирование</a>
            <a href="rooms.php">Номера</a>
            <a href="room_classes.php">Классы</a>
            <a href="procedures.php">Процедуры</a>
            <a href="services.php">Услуги</a>
            <a href="packages.php">Пакеты</a>
            <a href="calendar.php">Календарь</a>
            <a href="analytics.php">Аналитика</a>
            <a href="logout.php">Выход</a>
        </nav>
    </header>
    <main>
        <section>
            <h2>Пакеты (Путевки)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Цена</th>
                        <th>Дней</th>
                        <th>Процедур</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($packages as $pkg): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pkg['id']); ?></td>
                        <td><?php echo htmlspecialchars($pkg['name']); ?></td>
                        <td><?php echo htmlspecialchars($pkg['base_price']); ?> руб.</td>
                        <td><?php echo htmlspecialchars($pkg['duration_days']); ?></td>
                        <td><?php echo count($pkg['procedure_ids'] ?? []); ?></td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Удалить?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $pkg['id']; ?>">
                                <button type="submit" style="background:none; border:none; color:red; cursor:pointer;">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section style="margin-top: 40px; background: #fff; padding: 20px; border-radius: 8px;">
            <h3>Добавить пакет</h3>
            <form method="post">
                <input type="hidden" name="action" value="save_package">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label>Название</label>
                        <input type="text" name="name" required style="width:100%; padding:8px;">
                    </div>
                    <div>
                        <label>Базовая цена</label>
                        <input type="number" name="base_price" required style="width:100%; padding:8px;">
                    </div>
                    <div>
                        <label>Длительность (дней)</label>
                        <input type="number" name="duration_days" required style="width:100%; padding:8px;">
                    </div>
                    <div style="grid-column: span 2;">
                        <label>Описание</label>
                        <textarea name="description" style="width:100%; padding:8px;"></textarea>
                    </div>
                    <div style="grid-column: span 2;">
                        <label>Включенные процедуры</label>
                        <div style="max-height: 150px; overflow-y: auto; border: 1px solid #ccc; padding: 5px;">
                            <?php foreach ($procedures as $p): ?>
                                <label style="display:block;">
                                    <input type="checkbox" name="procedure_ids[]" value="<?php echo $p['id']; ?>">
                                    <?php echo htmlspecialchars($p['name']); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn" style="margin-top: 20px;">Сохранить</button>
            </form>
        </section>
    </main>
</body>
</html>
