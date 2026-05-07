<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Planning\PlanningManager;

$store = new JsonStore(__DIR__ . '/../data');
$planManager = new PlanningManager($store);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $planManager->save([
            'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'date' => $_POST['date'],
            'priority' => $_POST['priority'] ?? 'medium',
            'status' => $_POST['status'] ?? 'pending'
        ]);
    } elseif ($_POST['action'] === 'delete') {
        $planManager->delete((int)$_POST['id']);
    }
    header('Location: planning.php');
    exit;
}

$plans = $planManager->getAll();
if (!is_array($plans)) $plans = [];

// Sort by date desc
usort($plans, function($a, $b) {
    return strcmp($b['date'] ?? '', $a['date'] ?? '');
});

$pageTitle = 'Планирование';
include 'includes/header.php';
?>

<div class="mica-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h2>📝 Планы и задачи</h2>
        <button class="btn" onclick="openPlanModal()">+ Новый план</button>
    </div>

    <div class="table-responsive"><table>
        <thead>
            <tr>
                <th>Дата</th>
                <th>Заголовок</th>
                <th>Приоритет</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($plans as $p): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($p['date'] ?? ''); ?></strong></td>
                <td>
                    <strong><?php echo htmlspecialchars($p['title'] ?? ''); ?></strong>
                    <?php if(!empty($p['description'])): ?>
                        <br><small style="color:#666;"><?php echo htmlspecialchars($p['description']); ?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                        $priColors = ['low' => '#6c757d', 'medium' => '#0078d4', 'high' => '#d83b01'];
                        $priLabels = ['low' => 'Низкий', 'medium' => 'Средний', 'high' => 'Высокий'];
                        $pri = $p['priority'] ?? 'medium';
                    ?>
                    <span style="color: <?php echo $priColors[$pri]; ?>; font-weight:600;">
                        <?php echo $priLabels[$pri]; ?>
                    </span>
                </td>
                <td>
                    <span class="status-badge" style="background: <?php echo ($p['status'] ?? '') === 'completed' ? '#d1e7dd' : '#e2e2e2'; ?>; color: <?php echo ($p['status'] ?? '') === 'completed' ? '#0f5132' : '#333'; ?>;">
                        <?php echo ($p['status'] ?? '') === 'completed' ? 'Выполнено' : 'В ожидании'; ?>
                    </span>
                </td>
                <td>
                    <div style="display:flex; gap:8px;">
                        <button class="btn btn-secondary" onclick='editPlan(<?php echo json_encode($p); ?>)' style="padding: 4px 10px; font-size: 0.8rem;">Изм.</button>
                        <form method="post" style="margin:0;" onsubmit="return confirm('Удалить этот план?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 4px 10px; font-size: 0.8rem;">Удалить</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($plans)): ?>
            <tr>
                <td colspan="5" style="text-align:center; padding: 40px; color:#888;">Планов пока нет</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table></div>
</div>

<div id="modal-plan" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.3); backdrop-filter: blur(4px); z-index:1000; align-items:center; justify-content:center;">
    <div class="mica-card" style="width: 500px; margin-bottom: 0;">
        <h3 id="modal-title">📝 Создать план</h3>
        <form method="post">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="p-id">

            <label>Заголовок</label>
            <input type="text" name="title" id="p-title" required placeholder="Напр: Заказать продукты">

            <label>Дата</label>
            <input type="date" name="date" id="p-date" required value="<?php echo date('Y-m-d'); ?>">

            <label>Приоритет</label>
            <select name="priority" onchange="this.form.submit()" id="p-priority">
                <option value="low">Низкий</option>
                <option value="medium" selected>Средний</option>
                <option value="high">Высокий</option>
            </select>

            <label>Статус</label>
            <select name="status" onchange="this.form.submit()" id="p-status">
                <option value="pending">В ожидании</option>
                <option value="completed">Выполнено</option>
            </select>

            <label>Описание</label>
            <textarea name="description" id="p-description" style="width:100%; height:80px; padding:10px; border-radius:6px; border:1px solid rgba(0,0,0,0.2); margin-bottom: 10px;"></textarea>

            <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Отмена</button>
                <button type="submit" class="btn">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openPlanModal() {
        document.getElementById('p-id').value = '';
        document.getElementById('p-title').value = '';
        document.getElementById('p-description').value = '';
        document.getElementById('p-date').value = '<?php echo date('Y-m-d'); ?>';
        document.getElementById('p-priority').value = 'medium';
        document.getElementById('p-status').value = 'pending';
        document.getElementById('modal-title').textContent = '📝 Создать план';
        document.getElementById('modal-plan').style.display = 'flex';
    }
    function editPlan(p) {
        document.getElementById('p-id').value = p.id;
        document.getElementById('p-title').value = p.title;
        document.getElementById('p-description').value = p.description || '';
        document.getElementById('p-date').value = p.date;
        document.getElementById('p-priority').value = p.priority || 'medium';
        document.getElementById('p-status').value = p.status || 'pending';
        document.getElementById('modal-title').textContent = '📝 Редактировать план';
        document.getElementById('modal-plan').style.display = 'flex';
    }
    function closeModal() {
        document.getElementById('modal-plan').style.display = 'none';
    }
</script>

<?php include 'includes/footer.php'; ?>
