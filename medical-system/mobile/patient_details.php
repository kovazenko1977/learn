<?php
require_once __DIR__ . '/../Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

$patientManager = new \Medical\Core\Managers\PatientManager();
$scheduleManager = new \Medical\Core\Managers\ScheduleManager();
$id = $_GET['id'] ?? $_GET['patient_id'] ?? '';
$patient = $patientManager->getById($id);

if (!$patient) {
    header("Location: patients.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        if ($_POST['action'] === 'add_comment') {
            $patientManager->addComment($id, [
                'author' => \Medical\Core\Auth::getUser()['name'],
                'role' => \Medical\Core\Auth::getUser()['role'],
                'text' => $_POST['text']
            ]);
        } elseif ($_POST['action'] === 'add_history') {
            $entry = [
                'doctor' => \Medical\Core\Auth::getUser()['name'],
                'diagnosis_code' => $_POST['diagnosis_code'] ?? 'Z00.0',
                'diagnosis_text' => $_POST['diagnosis_text'] ?? 'Обследование',
                'notes' => $_POST['notes']
            ];
            $patientManager->addHistoryEntry($id, $entry);
        }
        header("Location: patient_details.php?id=$id");
        exit;
    }
}

include __DIR__ . '/includes/header.php';
?>

<div style="padding: 16px; display: flex; align-items: center; gap: 12px; background: #F3EDF7;">
    <a href="patients.php" style="color: inherit;"><i data-lucide="arrow-left"></i></a>
    <h2 style="margin: 0; font-size: 20px; font-weight: 500;">Карточка пациента</h2>
</div>

<div style="padding: 16px; padding-bottom: 100px;">
    <div class="md-card" style="margin: 0 0 16px 0;">
        <h1 style="margin: 0 0 8px 0; font-size: 22px;"><?php echo htmlspecialchars($patient['name']); ?></h1>
        <div style="font-size: 14px; color: var(--md-secondary); display: flex; flex-direction: column; gap: 4px;">
            <span><i data-lucide="cake" style="width:14px; height:14px; vertical-align: middle;"></i> <?php echo date('d.m.Y', strtotime($patient['birth_date'])); ?></span>
            <span><i data-lucide="phone" style="width:14px; height:14px; vertical-align: middle;"></i> <?php echo htmlspecialchars($patient['phone'] ?? '-'); ?></span>
            <span><i data-lucide="hash" style="width:14px; height:14px; vertical-align: middle;"></i> Карта: <?php echo htmlspecialchars($patient['card_number'] ?? '-'); ?></span>
            <span><i data-lucide="user-md" style="width:14px; height:14px; vertical-align: middle;"></i> Врач: <?php echo htmlspecialchars($patient['treating_doctor'] ?? 'не назначен'); ?></span>
        </div>

        <div style="display: flex; gap: 8px; margin-top: 16px; overflow-x: auto; padding-bottom: 8px;">
            <a href="patient_form.php?id=<?php echo $id; ?>" class="md-btn" style="background: #E8DEF8; color: #1D192B; border-radius: 8px; padding: 0 12px; font-size: 12px; height: 32px;">
                <i data-lucide="edit" style="width:14px; height:14px; margin-right: 4px;"></i> Изменить
            </a>
            <a href="schedule_assign.php?patient_id=<?php echo $id; ?>" class="md-btn" style="background: #D0E1FF; color: #001D35; border-radius: 8px; padding: 0 12px; font-size: 12px; height: 32px;">
                <i data-lucide="plus-square" style="width:14px; height:14px; margin-right: 4px;"></i> Назначить
            </a>
            <a href="attendance.php?patient_id=<?php echo $id; ?>" class="md-btn" style="background: #F3EDF7; color: #1D192B; border-radius: 8px; padding: 0 12px; font-size: 12px; height: 32px;">
                <i data-lucide="activity" style="width:14px; height:14px; margin-right: 4px;"></i> План
            </a>
            <a href="lab_results.php?patient_id=<?php echo $id; ?>" class="md-btn" style="background: #DFF6DD; color: #107C10; border-radius: 8px; padding: 0 12px; font-size: 12px; height: 32px;">
                <i data-lucide="microscope" style="width:14px; height:14px; margin-right: 4px;"></i> Анализы
            </a>
        </div>
    </div>

    <!-- Tabs for History / Notes -->
    <div style="display: flex; border-bottom: 1px solid #CAC4D0; margin-bottom: 16px;">
        <button onclick="switchTab('history')" id="tab-btn-history" style="flex: 1; padding: 12px; border: none; background: none; font-weight: 500; border-bottom: 3px solid var(--md-primary); color: var(--md-primary);">История</button>
        <button onclick="switchTab('notes')" id="tab-btn-notes" style="flex: 1; padding: 12px; border: none; background: none; font-weight: 500; color: var(--md-secondary);">Заметки</button>
    </div>

    <div id="tab-history">
        <?php if (\Medical\Core\Auth::can('history_add')): ?>
            <button onclick="document.getElementById('addHistoryModal').style.display='flex'" class="md-btn md-btn-primary" style="width: 100%; margin-bottom: 16px;">
                <i data-lucide="plus" style="width:18px; height:18px; margin-right: 8px;"></i> Добавить запись
            </button>
        <?php endif; ?>

        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php if (!empty($patient['history'])): ?>
                <?php foreach (array_reverse($patient['history']) as $entry): ?>
                    <div class="md-card" style="margin: 0; padding: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--md-secondary); margin-bottom: 4px;">
                            <span><?php echo $entry['date']; ?> • <?php echo htmlspecialchars($entry['doctor']); ?></span>
                            <span style="font-weight: 700; color: var(--md-primary);"><?php echo htmlspecialchars($entry['diagnosis_code']); ?></span>
                        </div>
                        <div style="font-weight: 600; font-size: 15px; margin-bottom: 4px;"><?php echo htmlspecialchars($entry['diagnosis_text']); ?></div>
                        <div style="font-size: 14px; line-height: 1.4; white-space: pre-wrap;"><?php echo htmlspecialchars($entry['notes']); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 32px; color: var(--md-secondary);">Медицинских записей нет</div>
            <?php endif; ?>
        </div>
    </div>

    <div id="tab-notes" style="display: none;">
        <form method="POST" style="margin-bottom: 16px;">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="add_comment">
            <textarea name="text" class="md-input" style="height: 80px; padding: 12px;" placeholder="Ваш комментарий..." required></textarea>
            <button type="submit" class="md-btn md-btn-primary" style="width: 100%;">Добавить</button>
        </form>

        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php if (!empty($patient['comments'])): ?>
                <?php foreach (array_reverse($patient['comments']) as $comm): ?>
                    <div style="padding: 12px; border-radius: 8px; background: #F3EDF7;">
                        <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--md-secondary); margin-bottom: 4px;">
                            <strong><?php echo htmlspecialchars($comm['author']); ?></strong>
                            <span><?php echo $comm['date']; ?></span>
                        </div>
                        <div style="font-size: 14px;"><?php echo nl2br(htmlspecialchars($comm['text'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 32px; color: var(--md-secondary);">Заметок нет</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add History Modal -->
<div id="addHistoryModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 16px;">
    <div class="md-card" style="width: 100%; margin: 0; max-height: 90vh; overflow-y: auto;">
        <h3 style="margin-top: 0;">Новая запись</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="add_history">

            <label style="display: block; font-size: 12px; color: var(--md-secondary); margin-bottom: 4px;">Код диагноза (МКБ-10)</label>
            <input type="text" name="diagnosis_code" placeholder="Z00.0" class="md-input" required>

            <label style="display: block; font-size: 12px; color: var(--md-secondary); margin-bottom: 4px;">Наименование диагноза</label>
            <input type="text" name="diagnosis_text" placeholder="Общее обследование" class="md-input" required>

            <label style="display: block; font-size: 12px; color: var(--md-secondary); margin-bottom: 4px;">Жалобы, осмотр, назначения</label>
            <textarea name="notes" class="md-input" style="height: 150px; padding: 12px;" required></textarea>

            <div style="display: flex; gap: 8px; margin-top: 16px;">
                <button type="button" onclick="document.getElementById('addHistoryModal').style.display='none'" class="md-btn" style="flex: 1; background: #eee;">Отмена</button>
                <button type="submit" class="md-btn md-btn-primary" style="flex: 1;">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tab) {
    document.getElementById('tab-history').style.display = tab === 'history' ? 'block' : 'none';
    document.getElementById('tab-notes').style.display = tab === 'notes' ? 'block' : 'none';

    document.getElementById('tab-btn-history').style.color = tab === 'history' ? 'var(--md-primary)' : 'var(--md-secondary)';
    document.getElementById('tab-btn-history').style.borderBottom = tab === 'history' ? '3px solid var(--md-primary)' : 'none';

    document.getElementById('tab-btn-notes').style.color = tab === 'notes' ? 'var(--md-primary)' : 'var(--md-secondary)';
    document.getElementById('tab-btn-notes').style.borderBottom = tab === 'notes' ? '3px solid var(--md-primary)' : 'none';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
