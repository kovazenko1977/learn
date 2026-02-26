<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('patients_view')) {
    die("У вас недостаточно прав для просмотра карточки пациента.");
}

$patientManager = new \Medical\Core\Managers\PatientManager();
$scheduleManager = new \Medical\Core\Managers\ScheduleManager();
$bookingManager = new \Medical\Core\Managers\BookingManager();
$roomManager = new \Medical\Core\Managers\RoomManager();

$id = $_GET['id'] ?? '';
$patient = $patientManager->getById($id);
$appointments = $scheduleManager->getByPatient($id);
$bookings = $bookingManager->getByPatient($id);

if (!$patient) {
    echo '<div class="card mica-effect"><h2>Пациент не найден</h2><a href="patients.php" class="btn btn-primary">Назад к списку</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        die("CSRF Error");
    }

    if ($_POST['action'] === 'pay' && \Medical\Core\Auth::can('finance_pay')) {
        $scheduleManager->markPaid($_POST['appointment_id']);
        header("Location: patient_card.php?id=$id");
        exit;
    } elseif ($_POST['action'] === 'cancel_appointment' && \Medical\Core\Auth::can('procedures_cancel')) {
        $scheduleManager->cancel($_POST['appointment_id']);
        header("Location: patient_card.php?id=$id");
        exit;
    } elseif ($_POST['action'] === 'delete_appointment' && (\Medical\Core\Auth::can('settings_system') || \Medical\Core\Auth::can('procedures_delete'))) {
        $appToDelete = $scheduleManager->getById($_POST['appointment_id']);
        if ($appToDelete && ($appToDelete['status'] ?? '') === 'paid' && !\Medical\Core\Auth::can('settings_system')) {
            die("Нельзя удалить оплаченную процедуру без прав администратора. Сначала выполните возврат.");
        }
        $scheduleManager->delete($_POST['appointment_id']);
        header("Location: patient_card.php?id=$id");
        exit;
    } elseif ($_POST['action'] === 'edit_appointment' && \Medical\Core\Auth::can('settings_system')) {
        $scheduleManager->update($_POST['appointment_id'], [
            'price' => (float)$_POST['price'],
            'status' => $_POST['status'],
            'cabinet_id' => $_POST['cabinet_id'],
            'time' => $_POST['time']
        ]);
        header("Location: patient_card.php?id=$id");
        exit;
    } elseif ($_POST['action'] === 'add_history' && \Medical\Core\Auth::can('history_add')) {
        $entry = [
            'doctor' => \Medical\Core\Auth::getUser()['name'],
            'diagnosis_code' => $_POST['diagnosis_code'],
            'diagnosis_text' => $_POST['diagnosis_text'],
            'notes' => $_POST['notes']
        ];
        $patientManager->addHistoryEntry($id, $entry);
        header("Location: patient_card.php?id=$id");
        exit;
    } elseif ($_POST['action'] === 'add_comment' && \Medical\Core\Auth::can('history_add')) {
        $patientManager->addComment($id, [
            'author' => \Medical\Core\Auth::getUser()['name'],
            'role' => \Medical\Core\Auth::getUser()['role'],
            'text' => $_POST['text']
        ]);
        header("Location: patient_card.php?id=$id");
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <h1>Карточка пациента: <span style="color: var(--win-accent);"><?php echo htmlspecialchars($patient['name']); ?></span></h1>
    <a href="patients.php" class="btn">
        <i data-lucide="arrow-left" class="icon"></i> Назад к списку
    </a>
</div>

<div style="display: grid; grid-template-columns: 320px 1fr; gap: 24px;">
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="card mica-effect">
            <h3 style="margin-bottom: 20px;"><i data-lucide="info" class="icon"></i> Личные данные</h3>
            <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.95rem;">
                <div>
                    <div style="color: var(--win-text-secondary); font-size: 0.8rem; text-transform: uppercase;">Дата рождения</div>
                    <strong><?php echo date('d.m.Y', strtotime($patient['birth_date'])); ?></strong>
                </div>
                <div>
                    <div style="color: var(--win-text-secondary); font-size: 0.8rem; text-transform: uppercase;">Телефон</div>
                    <strong><?php echo htmlspecialchars($patient['phone'] ?? '-'); ?></strong>
                </div>
                <div>
                    <div style="color: var(--win-text-secondary); font-size: 0.8rem; text-transform: uppercase;">№ Истории болезни</div>
                    <code><?php echo htmlspecialchars($patient['card_number'] ?? '-'); ?></code>
                </div>
                <div>
                    <div style="color: var(--win-text-secondary); font-size: 0.8rem; text-transform: uppercase;">Место жительства</div>
                    <strong><?php echo htmlspecialchars($patient['residence'] ?? '-'); ?></strong>
                </div>
                <div>
                    <div style="color: var(--win-text-secondary); font-size: 0.8rem; text-transform: uppercase;">Доп. информация</div>
                    <div style="font-size: 0.85rem; background: rgba(0,0,0,0.02); padding: 8px; border-radius: 4px; max-height: 100px; overflow-y: auto;">
                        <?php echo nl2br(htmlspecialchars($patient['extra_info'] ?? '-')); ?>
                    </div>
                </div>
            </div>
            <hr style="border:0; border-top: 1px solid var(--win-border); margin: 24px 0;">
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php if (\Medical\Core\Auth::can('procedures_assign')): ?>
                    <a href="procedures_doctor.php?patient_id=<?php echo $id; ?>" class="btn btn-primary">
                        <i data-lucide="plus-square" class="icon"></i> Назначить процедуры
                    </a>
                <?php endif; ?>
                <?php if (\Medical\Core\Auth::can('history_view')): ?>
                    <a href="export.php?action=epicrisis&patient_id=<?php echo $id; ?>" target="_blank" class="btn">
                        <i data-lucide="file-text" class="icon"></i> Выписной эпикриз
                    </a>
                <?php endif; ?>
                <?php if (\Medical\Core\Auth::can('lab_view')): ?>
                    <a href="lab_results.php?patient_id=<?php echo $id; ?>" class="btn">
                        <i data-lucide="microscope" class="icon"></i> Результаты анализов
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mica-effect">
            <h3 style="margin-bottom: 20px;"><i data-lucide="calendar-check" class="icon"></i> История проживания</h3>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php if (empty($bookings)): ?>
                    <p style="text-align: center; color: var(--win-text-secondary); font-size: 0.85rem;">Нет данных о бронировании</p>
                <?php else: ?>
                    <?php foreach (array_reverse($bookings) as $b):
                        $room = $roomManager->getById($b['room_id']);
                        $sMap = [
                            'preliminary' => ['bg' => '#fff8e1', 'text' => '#b7791f', 'label' => 'Предв.'],
                            'confirmed' => ['bg' => '#fde7e9', 'text' => '#d13438', 'label' => 'Подтв.'],
                            'checked_in' => ['bg' => '#dff6dd', 'text' => '#107c10', 'label' => 'Проживает'],
                            'checked_out' => ['bg' => '#f3f2f1', 'text' => '#605e5c', 'label' => 'Выехал'],
                            'cancelled' => ['bg' => '#f3f2f1', 'text' => '#a19f9d', 'label' => 'Отмена']
                        ];
                        $st = $sMap[$b['status']] ?? $sMap['preliminary'];
                    ?>
                        <div style="padding: 10px; border: 1px solid var(--win-border); border-radius: 8px; font-size: 0.85rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <strong><?php echo htmlspecialchars($room['name'] ?? 'Удален'); ?></strong>
                                <span style="background: <?php echo $st['bg']; ?>; color: <?php echo $st['text']; ?>; padding: 1px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: 600;">
                                    <?php echo $st['label']; ?>
                                </span>
                            </div>
                            <div style="color: var(--win-text-secondary);">
                                <?php echo date('d.m', strtotime($b['check_in'])); ?> — <?php echo date('d.m.Y', strtotime($b['check_out'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <a href="booking.php" class="btn btn-sm btn-ghost" style="margin-top: 15px; width: 100%;">Перейти в бронирование</a>
        </div>

        <div class="card mica-effect">
            <h3 style="margin-bottom: 20px;"><i data-lucide="message-square" class="icon"></i> Заметки</h3>
            <form method="POST" style="margin-bottom: 20px;">
                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                <input type="hidden" name="action" value="add_comment">
                <textarea name="text" style="width: 100%; height: 80px; margin-bottom: 12px; resize: vertical;" placeholder="Добавить комментарий..." required></textarea>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Добавить</button>
            </form>

            <div class="comments-list" style="display: flex; flex-direction: column; gap: 16px;">
                <?php if (isset($patient['comments']) && is_array($patient['comments'])): ?>
                    <?php foreach (array_reverse($patient['comments']) as $comm): ?>
                        <div style="padding: 12px; border-radius: 8px; background: rgba(0,0,0,0.03);">
                            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--win-text-secondary); margin-bottom: 6px;">
                                <strong><?php echo htmlspecialchars($comm['author']); ?></strong>
                                <span><?php echo $comm['date']; ?></span>
                            </div>
                            <p style="font-size: 0.9rem; margin: 0; line-height: 1.4;"><?php echo nl2br(htmlspecialchars($comm['text'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: var(--win-text-secondary); font-size: 0.9rem;">Нет комментариев</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="card mica-effect">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin:0;"><i data-lucide="activity" class="icon"></i> План лечения</h3>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label style="font-size: 0.8rem; color: var(--win-text-secondary);">Показывать по:</label>
                    <select id="pagination-limit" style="padding: 4px 8px; font-size: 0.8rem;">
                        <option value="19">19</option>
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="all">Все</option>
                    </select>
                </div>
            </div>
            <table style="font-size: 0.9rem;" id="appointments-table">
                <thead>
                    <tr>
                        <th>Процедура</th>
                        <th>Дата/Время</th>
                        <th>Статус</th>
                        <th style="text-align: right;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($appointments) as $app): ?>
                    <tr class="appointment-row">
                        <td style="font-weight: 500;"><?php echo htmlspecialchars($app['procedure_name']); ?></td>
                        <td><?php echo $app['date']; ?> <span style="color: var(--win-text-secondary);"><?php echo $app['time']; ?></span></td>
                        <td>
                            <?php if ($app['attended']): ?>
                                <span class="status-green">Выполнена</span>
                            <?php elseif (($app['status'] ?? '') === 'cancelled'): ?>
                                <span class="status-red" style="opacity: 0.6; text-decoration: line-through;">Отменена</span>
                            <?php else: ?>
                                <span class="status-gray">Ожидает</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
                                <?php if (\Medical\Core\Auth::canSeeMoney()): ?>
                                <span class="<?php echo $app['status'] === 'paid' ? 'status-green' : ($app['status'] === 'unpaid' ? 'status-red' : 'status-gray'); ?>">
                                    <?php echo $app['status'] === 'paid' ? 'Оплачено' : ($app['status'] === 'unpaid' ? 'Ожидает' : 'Бесплатно'); ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($app['status'] === 'unpaid' && \Medical\Core\Auth::can('finance_pay')): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="pay">
                                        <input type="hidden" name="appointment_id" value="<?php echo $app['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-primary">Оплатить</button>
                                    </form>
                                <?php endif; ?>
                                <?php if (($app['status'] ?? '') !== 'cancelled' && !$app['attended'] && \Medical\Core\Auth::can('procedures_cancel')): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите ОТМЕНИТЬ эту процедуру?\n\nЗапись останется в истории со статусом «Отменена».')">
                                        <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="cancel_appointment">
                                        <input type="hidden" name="appointment_id" value="<?php echo $app['id']; ?>">
                                        <button type="submit" class="btn btn-sm" style="border-color: #d13438; color: #d13438; background: rgba(209, 52, 56, 0.05);" title="Отменить назначение">
                                            <i data-lucide="ban" class="icon" style="width: 14px; height: 14px; margin: 0 4px 0 0; color: #d13438;"></i> Отменить
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if (\Medical\Core\Auth::can('settings_system')): ?>
                                    <button class="btn btn-sm" style="padding: 4px;" onclick='openEditAppModal(<?php echo htmlspecialchars(json_encode($app), ENT_QUOTES); ?>)'><i data-lucide="edit" class="icon" style="width:14px; height:14px; margin:0;"></i></button>
                                <?php endif; ?>
                                <?php if (\Medical\Core\Auth::can('settings_system') || \Medical\Core\Auth::can('procedures_delete')): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить назначение?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="delete_appointment">
                                        <input type="hidden" name="appointment_id" value="<?php echo $app['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" style="padding: 4px;" title="Удалить навсегда"><i data-lucide="trash-2" class="icon" style="width:14px; height:14px; margin:0;"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($appointments)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 30px; color: var(--win-text-secondary);">Процедуры еще не назначены</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card mica-effect">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h3 style="margin:0;"><i data-lucide="clipboard" class="icon"></i> История болезни</h3>
                <?php if (\Medical\Core\Auth::can('history_add')): ?>
                <button class="btn btn-primary" onclick="document.getElementById('historyModal').style.display='block'">
                    <i data-lucide="plus" class="icon"></i> Добавить запись
                </button>
                <?php endif; ?>
            </div>

            <div class="history-list" style="display: flex; flex-direction: column; gap: 20px;">
                <?php if (\Medical\Core\Auth::can('history_view') && isset($patient['history']) && is_array($patient['history'])): ?>
                    <?php foreach (array_reverse($patient['history']) as $entry): ?>
                        <div style="padding: 20px; border: 1px solid var(--win-border); border-radius: 12px; background: rgba(255,255,255,0.4); transition: transform 0.2s;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 12px; align-items: center;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="background: var(--win-accent); color: white; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: bold;">
                                        <?php echo $entry['date']; ?>
                                    </div>
                                    <span style="font-weight: 600; font-size: 1.1rem;"><?php echo htmlspecialchars($entry['diagnosis_text']); ?></span>
                                </div>
                                <span style="font-size: 0.8rem; color: var(--win-text-secondary); display: flex; align-items: center; gap: 5px;">
                                    <i data-lucide="user" style="width:14px; height:14px;"></i> <?php echo htmlspecialchars($entry['doctor']); ?>
                                </span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="background: #eee; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 0.9rem; font-weight: 600;">
                                    <?php echo htmlspecialchars($entry['diagnosis_code']); ?>
                                </span>
                            </div>
                            <p style="font-size: 0.95rem; line-height: 1.6; color: #333; margin: 0; white-space: pre-wrap;"><?php echo htmlspecialchars($entry['notes']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php elseif (!\Medical\Core\Auth::can('history_view')): ?>
                    <p style="text-align: center; padding: 40px; color: var(--win-text-secondary);">У вас нет прав для просмотра медицинской истории</p>
                <?php else: ?>
                    <p style="text-align: center; padding: 40px; color: var(--win-text-secondary);">История болезни пуста</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Appointment Modal -->
<div id="editAppModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
    <div class="card mica-effect" style="width: 400px; margin: 100px auto; padding: 32px;">
        <h2 style="margin-bottom: 24px;">Редактировать назначение</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="edit_appointment">
            <input type="hidden" name="appointment_id" id="edit_app_id">

            <div style="margin-bottom: 15px;">
                <label style="display:block;">Время</label>
                <input type="time" name="time" id="edit_app_time" style="width: 100%;" required>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display:block;">Кабинет</label>
                <input type="text" name="cabinet_id" id="edit_app_cabinet" style="width: 100%;" required>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display:block;">Цена (₽)</label>
                <input type="number" step="0.01" name="price" id="edit_app_price" style="width: 100%;" required>
            </div>
            <div style="margin-bottom: 24px;">
                <label style="display:block;">Статус</label>
                <select name="status" id="edit_app_status" style="width: 100%;">
                    <option value="free">Бесплатно</option>
                    <option value="unpaid">Ожидает оплаты</option>
                    <option value="paid">Оплачено</option>
                    <option value="cancelled">Отменено</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn" onclick="document.getElementById('editAppModal').style.display='none'">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<!-- History Modal -->
<div id="historyModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
    <div class="card mica-effect" style="width: 600px; margin: 60px auto; padding: 32px;">
        <h2 style="margin-bottom: 24px;">Новая запись в историю болезни</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
            <input type="hidden" name="action" value="add_history">
            <div style="margin-bottom: 20px; position: relative;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">Диагноз (МКБ-10)</label>
                <input type="text" id="mkb_search" placeholder="Начните вводить код или название..." style="width: 100%; margin-bottom: 10px;" onkeyup="searchMKB(this.value)">
                <div id="mkb_results" class="card mica-effect" style="display:none; position: absolute; z-index: 1001; width: 100%; max-height: 200px; overflow-y: auto; padding: 10px;"></div>
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="diagnosis_code" id="diag_code" placeholder="Код" style="width: 100px; font-weight: bold;" required readonly>
                    <input type="text" name="diagnosis_text" id="diag_text" placeholder="Наименование диагноза" style="flex-grow: 1;" required readonly>
                </div>
            </div>
            <div style="margin-bottom: 24px;">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">Жалобы, осмотр, рекомендации</label>
                <textarea name="notes" style="width: 100%; height: 180px; resize: vertical;" required></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn" onclick="document.getElementById('historyModal').style.display='none'">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить запись</button>
            </div>
        </form>
    </div>
</div>

<script>
let mkbData = [];
fetch('assets/data/mkb10.json').then(r => r.json()).then(d => mkbData = d);

function searchMKB(query) {
    const resultsDiv = document.getElementById('mkb_results');
    if (query.length < 2) {
        resultsDiv.style.display = 'none';
        return;
    }
    const filtered = mkbData.filter(i => i.code.toLowerCase().includes(query.toLowerCase()) || i.name.toLowerCase().includes(query.toLowerCase()));
    if (filtered.length > 0) {
        resultsDiv.innerHTML = filtered.map(i => `<div style="padding: 10px; cursor: pointer; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='rgba(0,120,212,0.1)'" onmouseout="this.style.background='transparent'" onclick="selectMKB('${i.code}', '${i.name}')"><strong>${i.code}</strong> ${i.name}</div>`).join('');
        resultsDiv.style.display = 'block';
    } else {
        resultsDiv.style.display = 'none';
    }
}

function openEditAppModal(app) {
    document.getElementById('edit_app_id').value = app.id;
    document.getElementById('edit_app_time').value = app.time;
    document.getElementById('edit_app_cabinet').value = app.cabinet_id;
    document.getElementById('edit_app_price').value = app.price;
    document.getElementById('edit_app_status').value = app.status;
    document.getElementById('editAppModal').style.display = 'block';
}

function selectMKB(code, name) {
    document.getElementById('diag_code').value = code;
    document.getElementById('diag_text').value = name;
    document.getElementById('mkb_results').style.display = 'none';
    document.getElementById('mkb_search').value = '';
}
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const limitSelect = document.getElementById('pagination-limit');
    const table = document.getElementById('appointments-table');
    const rows = Array.from(table.querySelectorAll('.appointment-row'));

    function updatePagination() {
        const limit = limitSelect.value;
        if (limit === 'all') {
            rows.forEach(row => row.style.display = '');
        } else {
            const count = parseInt(limit);
            rows.forEach((row, index) => {
                row.style.display = index < count ? '' : 'none';
            });
        }
    }

    limitSelect.addEventListener('change', updatePagination);
    updatePagination();
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
