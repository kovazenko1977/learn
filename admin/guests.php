<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Guests\GuestManager;

try {
    $store = new JsonStore(__DIR__ . '/../data');
    $guestManager = new GuestManager($store);
    $guests = $guestManager->getAll();
    $bookings = $store->findAll('bookings');
} catch (Throwable $e) {
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}

if (!is_array($guests)) $guests = [];
if (!is_array($bookings)) $bookings = [];

// Filters
$filterName = $_GET['name'] ?? '';
$filterPhone = $_GET['phone'] ?? '';
$filterStatus = $_GET['status'] ?? ''; // 'staying', 'not_staying'

if ($filterName || $filterPhone || $filterStatus) {
    $guests = array_filter($guests, function($g) {
        return is_array($g);
    });
    $guests = array_filter($guests, function($g) use ($filterName, $filterPhone, $filterStatus, $guestManager, $bookings) {
        $name = $g['name'] ?? '';
        $phone = $g['phone'] ?? '';
        $id = $g['id'] ?? 0;

        if ($filterName && stripos($name, $filterName) === false) return false;
        if ($filterPhone && strpos($phone, $filterPhone) === false) return false;

        $isStaying = false;
        try {
            $isStaying = $id ? $guestManager->isCurrentlyStaying($id, $bookings) : false;
        } catch (Exception $e) {}

        if ($filterStatus === 'staying' && !$isStaying) return false;
        if ($filterStatus === 'not_staying' && $isStaying) return false;

        return true;
    });
}

$pageTitle = 'Справочник гостей';
include 'includes/header.php';
?>

<div class="mica-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h2>👥 Список гостей</h2>
    </div>

    <form method="get" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:15px; margin-bottom: 30px; background: rgba(0,0,0,0.02); padding: 15px; border-radius: 8px;">
        <div>
            <label>ФИО</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($filterName); ?>" placeholder="Поиск по имени...">
        </div>
        <div>
            <label>Телефон</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($filterPhone); ?>" placeholder="+...">
        </div>
        <div>
            <label>Статус</label>
            <select name="status" onchange="this.form.submit()">
                <option value="">Все</option>
                <option value="staying" <?php echo $filterStatus === 'staying' ? 'selected' : ''; ?>>Сейчас в санатории</option>
                <option value="not_staying" <?php echo $filterStatus === 'not_staying' ? 'selected' : ''; ?>>Не у нас</option>
            </select>
        </div>
        <div style="display:flex; align-items:flex-end; gap:10px;">
            <button type="submit" class="btn">Применить</button>
            <a href="guests.php" class="btn btn-secondary">Сбросить</a>
        </div>
    </form>

    <div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>ФИО</th>
                <th>Телефон</th>
                <th>Гражданство</th>
                <th>Статус</th>
                <th>История посещений</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($guests as $g):
                if (!is_array($g)) continue;
                $gid = $g['id'] ?? 0;
                $isStaying = $gid ? $guestManager->isCurrentlyStaying($gid, $bookings) : false;
                $history = $gid ? $guestManager->getStayHistory($gid, $bookings) : [];
            ?>
            <tr>
                <td><?php echo $gid; ?></td>
                <td><strong><?php echo htmlspecialchars($g['name'] ?? 'N/A'); ?></strong><br><small style="color:#888;"><?php echo htmlspecialchars($g['address'] ?? ''); ?></small></td>
                <td><?php echo htmlspecialchars($g['phone'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($g['citizenship'] ?? '—'); ?></td>
                <td>
                    <?php if ($isStaying): ?>
                        <span class="status-badge status-confirmed">Гостит сейчас</span>
                    <?php else: ?>
                        <span class="status-badge" style="background:rgba(0,0,0,0.05); color:#666;">Не у нас</span>
                    <?php endif; ?>
                </td>
                <td style="font-size: 0.85rem;">
                    <?php if (empty($history)): ?>
                        Нет записей
                    <?php else: ?>
                        <div style="max-height: 60px; overflow-y: auto;">
                            <?php
                            $statusMap = ['new' => 'новое', 'confirmed' => 'подтверждено', 'cancelled' => 'отменено'];
                            foreach (array_reverse($history) as $h): ?>
                                <div><?php echo $h['check_in']; ?> — <?php echo $h['check_out']; ?> (<?php echo $statusMap[$h['status']] ?? $h['status']; ?>)</div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-secondary" onclick='editGuest(<?php echo htmlspecialchars(json_encode($g), ENT_QUOTES, 'UTF-8'); ?>)' style="padding: 4px 10px; font-size: 0.8rem;">Изм.</button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($guests)): ?>
            <tr>
                <td colspan="7" style="text-align:center; padding: 40px; color:#888;">Гости не найдены</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div id="modal-guest" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.3); backdrop-filter: blur(4px); z-index:1000; align-items:center; justify-content:center;">
    <div class="mica-card" style="width: 500px; margin-bottom: 0;">
        <h3>📝 Редактировать данные гостя</h3>
        <form method="post" action="guests_action.php">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="g-id">

            <label>ФИО</label>
            <input type="text" name="name" id="g-name" required>

            <label>Телефон</label>
            <input type="text" name="phone" id="g-phone" required>

            <label>Гражданство</label>
            <input type="text" name="citizenship" id="g-citizenship">

            <label>Адрес</label>
            <textarea name="address" id="g-address" style="width:100%; height:60px; padding:10px; border-radius:6px; border:1px solid rgba(0,0,0,0.2);"></textarea>

            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Отмена</button>
                <button type="submit" class="btn">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editGuest(g) {
        document.getElementById('g-id').value = g.id;
        document.getElementById('g-name').value = g.name;
        document.getElementById('g-phone').value = g.phone;
        document.getElementById('g-citizenship').value = g.citizenship || '';
        document.getElementById('g-address').value = g.address || '';
        document.getElementById('modal-guest').style.display = 'flex';
    }
    function closeModal() {
        document.getElementById('modal-guest').style.display = 'none';
    }
</script>

<?php include 'includes/footer.php'; ?>
