<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Database\JsonStore;

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$store = new JsonStore(__DIR__ . '/../data');
$popups = $store->findAll('popups');

$pageTitle = 'Управление попапами';
include 'includes/header.php';
?>

<div class="mica-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>🔔 Всплывающие окна</h2>
        <button class="btn btn-primary" onclick="showPopupModal()">+ Создать попап</button>
    </div>

    <div class="table-responsive">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Код</th>
                    <th>Заголовок</th>
                    <th>Анимация</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($popups as $p): ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($p['code']); ?></code></td>
                    <td><?php echo htmlspecialchars($p['title']); ?></td>
                    <td><?php echo htmlspecialchars($p['animation'] ?? 'fade'); ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline" onclick="editPopup(<?php echo htmlspecialchars(json_encode($p)); ?>)">✏️</button>
                        <form method="POST" action="delete.php" style="display:inline;" onsubmit="return confirm('Удалить?')">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            <input type="hidden" name="type" value="popup">
                            <button class="btn btn-sm btn-danger">🗑</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($popups)): ?>
                <tr><td colspan="4" style="text-align:center;">Попапов пока нет</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="popup-modal" class="modal-overlay" style="display:none;">
    <div class="mica-card modal-content" style="max-width: 500px;">
        <h3 id="modal-title">Создать попап</h3>
        <form method="POST" action="save_popup.php" enctype="multipart/form-data">
            <input type="hidden" name="id" id="popup-id">
            <div class="form-group">
                <label>Код вызова (data-popup-code)</label>
                <input type="text" name="code" id="popup-code" required>
            </div>
            <div class="form-group">
                <label>Заголовок</label>
                <input type="text" name="title" id="popup-title" required>
            </div>
            <div class="form-group">
                <label>Контент (текст)</label>
                <textarea name="content" id="popup-content" required></textarea>
            </div>
            <div class="form-group">
                <label>Анимация</label>
                <select name="animation" id="popup-animation">
                    <option value="fade">Fade</option>
                    <option value="zoom">Zoom</option>
                    <option value="slide">Slide</option>
                </select>
            </div>
            <div class="form-group">
                <label>Изображение (URL или файл)</label>
                <input type="text" name="image" id="popup-image" placeholder="https://...">
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="hidePopupModal()" class="btn btn-secondary">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<script>
function showPopupModal() {
    document.getElementById('modal-title').textContent = 'Создать попап';
    document.getElementById('popup-id').value = '';
    document.getElementById('popup-code').value = '';
    document.getElementById('popup-title').value = '';
    document.getElementById('popup-content').value = '';
    document.getElementById('popup-animation').value = 'fade';
    document.getElementById('popup-image').value = '';
    document.getElementById('popup-modal').style.display = 'flex';
}

function hidePopupModal() {
    document.getElementById('popup-modal').style.display = 'none';
}

function editPopup(p) {
    document.getElementById('modal-title').textContent = 'Редактировать попап';
    document.getElementById('popup-id').value = p.id;
    document.getElementById('popup-code').value = p.code;
    document.getElementById('popup-title').value = p.title;
    document.getElementById('popup-content').value = p.content;
    document.getElementById('popup-animation').value = p.animation || 'fade';
    document.getElementById('popup-image').value = p.image || '';
    document.getElementById('popup-modal').style.display = 'flex';
}
</script>

<?php include 'includes/footer.php'; ?>
