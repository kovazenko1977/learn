<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('finance_pay')) {
    die("У вас недостаточно прав для доступа к кассе.");
}

$scheduleManager = new \Medical\Core\Managers\ScheduleManager();
$patientManager = new \Medical\Core\Managers\PatientManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && \Medical\Core\Auth::can('finance_pay')) {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        if ($_POST['action'] === 'pay') {
            $scheduleManager->markPaid($_POST['id']);
        } elseif ($_POST['action'] === 'bulk_pay') {
            $countToPay = (int)($_POST['count'] ?? 0);
            $patientId = $_POST['patient_id'] ?? '';
            $startDate = $_POST['start_date'] ?? '';
            $endDate = $_POST['end_date'] ?? '';

            // Fetch all unpaid for this patient in range
            $all = $scheduleManager->getAll();
            $unpaid = array_filter($all, function($app) use ($patientId, $startDate, $endDate) {
                return $app['patient_id'] == $patientId &&
                       ($app['status'] ?? '') === 'unpaid' &&
                       $app['date'] >= $startDate &&
                       $app['date'] <= $endDate;
            });

            // Sort by date/time
            usort($unpaid, function($a, $b) {
                if ($a['date'] === $b['date']) return $a['time'] <=> $b['time'];
                return $a['date'] <=> $b['date'];
            });

            $toPayIds = array_map(function($app) { return $app['id']; }, array_slice($unpaid, 0, $countToPay));
            $scheduleManager->bulkMarkPaid($toPayIds);

            $message = "Оплачено процедур: " . count($toPayIds);
        } elseif ($_POST['action'] === 'bulk_pay_procedure') {
            $countToPay = (int)($_POST['count'] ?? 0);
            $patientId = $_POST['patient_id'] ?? '';
            $procedureId = $_POST['procedure_id'] ?? '';
            $procedureName = $_POST['procedure_name'] ?? '';

            $all = $scheduleManager->getAll();
            $unpaid = array_filter($all, function($app) use ($patientId, $procedureId, $procedureName) {
                return $app['patient_id'] == $patientId &&
                       ($app['status'] ?? '') === 'unpaid' &&
                       (($procedureId && $app['procedure_id'] == $procedureId) || (!$procedureId && $app['procedure_name'] == $procedureName));
            });

            usort($unpaid, function($a, $b) {
                if ($a['date'] === $b['date']) return $a['time'] <=> $b['time'];
                return $a['date'] <=> $b['date'];
            });

            $toPayIds = array_map(function($app) { return $app['id']; }, array_slice($unpaid, 0, $countToPay));
            $scheduleManager->bulkMarkPaid($toPayIds);
            $message = "Оплачено процедур «" . ($procedureName) . "»: " . count($toPayIds);
        } elseif ($_POST['action'] === 'pay_selected') {
            $ids = $_POST['ids'] ?? [];
            if (!empty($ids)) {
                $count = $scheduleManager->bulkMarkPaid($ids);
                $message = "Оплачено выбранных процедур: " . $count;
            }
        } elseif ($_POST['action'] === 'refund') {
            if ($scheduleManager->refund($_POST['id'])) {
                $message = "Средства за процедуру возвращены";
            }
        } elseif ($_POST['action'] === 'bulk_refund_procedure') {
            $ids = $_POST['ids'] ?? [];
            if (!empty($ids)) {
                $count = $scheduleManager->bulkRefund($ids);
                $message = "Оформлен возврат процедур: " . $count;
            }
        }
    }
}

$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$query = $_GET['q'] ?? '';
$hidePaid = isset($_GET['hide_paid']);

$allAppointments = $scheduleManager->getAll();
$totalRevenue = 0;
$filteredUnpaidCount = 0;
$filteredUnpaidSum = 0;
$selectedPatientId = null;

$appointments = array_filter($allAppointments, function($app) use ($query, $startDate, $endDate, $hidePaid, &$totalRevenue, &$filteredUnpaidCount, &$filteredUnpaidSum, &$selectedPatientId) {
    // Date range check
    $appDate = $app['date'];
    if ($appDate < $startDate || $appDate > $endDate) return false;

    // Search query check
    if ($query && mb_strpos(mb_strtolower($app['patient_name']), mb_strtolower($query)) === false) {
        return false;
    }

    // A procedure is "payable" if status is unpaid or paid
    $isPayable = (($app['status'] ?? '') === 'unpaid' || ($app['status'] ?? '') === 'paid');
    if (!$isPayable) return false;
    if ($hidePaid && $app['status'] === 'paid') return false;

    if ($app['status'] === 'paid') {
        $totalRevenue += (float)($app['price'] ?? 0);
    } else {
        $filteredUnpaidCount++;
        $filteredUnpaidSum += (float)($app['price'] ?? 0);
        $selectedPatientId = $app['patient_id'];
    }

    return true;
});

// Grouping by patient and procedure
$grouped = [];
foreach ($appointments as $app) {
    $key = $app['patient_id'] . '_' . ($app['procedure_id'] ?? $app['procedure_name']);
    if (!isset($grouped[$key])) {
        $grouped[$key] = [
            'patient_name' => $app['patient_name'],
            'patient_id' => $app['patient_id'],
            'procedure_name' => $app['procedure_name'],
            'procedure_id' => $app['procedure_id'] ?? null,
            'price' => $app['price'],
            'items' => []
        ];
    }
    $grouped[$key]['items'][] = $app;
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <h1>Касса - Оплата процедур</h1>
    <?php if (\Medical\Core\Auth::can('finance_view')): ?>
        <div class="card mica-effect" style="margin: 0; padding: 10px 20px; border-left: 4px solid #107c10;">
            <div style="font-size: 0.8rem; color: var(--win-text-secondary);">Выручка за период</div>
            <div style="font-size: 1.2rem; font-weight: 700; color: #107c10;"><?php echo number_format($totalRevenue, 0, ',', ' '); ?> ₽</div>
        </div>
    <?php endif; ?>
</div>

<?php if (isset($message)): ?>
    <div style="background: #dff6dd; color: #107c10; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #107c10;">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="card mica-effect">
    <form method="GET" style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; align-items: flex-end;">
        <div style="flex-grow: 1; min-width: 200px;">
            <label style="display:block; font-size: 0.8rem; margin-bottom: 4px;">Поиск пациента</label>
            <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="ФИО..." style="width: 100%;">
        </div>
        <div>
            <label style="display:block; font-size: 0.8rem; margin-bottom: 4px;">С даты</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
        </div>
        <div>
            <label style="display:block; font-size: 0.8rem; margin-bottom: 4px;">По дату</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
        </div>
        <div style="border-left: 1px solid var(--win-border); padding-left: 20px; display: flex; gap: 15px; align-items: flex-end;">
            <div>
                <label style="display:block; margin-bottom: 8px; font-size: 0.8rem;">Дней вперед</label>
                <input type="number" id="days_ahead" min="0" max="365" placeholder="0" style="width: 80px;">
            </div>
            <div style="padding-bottom: 10px; display: flex; align-items: center; gap: 15px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="hide_paid" id="hide_paid" style="width: 18px; height: 18px; cursor: pointer;" <?php echo $hidePaid ? 'checked' : ''; ?> onchange="this.form.submit()">
                    <label for="hide_paid" style="font-size: 0.85rem; cursor: pointer; font-weight: 600; color: var(--win-accent);">Скрыть оплаченные</label>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="save_period" style="width: 18px; height: 18px; cursor: pointer;">
                    <label for="save_period" style="font-size: 0.85rem; cursor: pointer;">Запомнить</label>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="search" class="icon"></i> Найти
        </button>
    </form>

    <?php if ($query && $filteredUnpaidCount > 0): ?>
    <div style="background: rgba(0,120,212,0.05); padding: 20px; border-radius: 8px; margin-bottom: 24px; border: 1px solid var(--win-border);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div>
                <h3 style="margin-bottom: 8px;">Итого к оплате (<?php echo htmlspecialchars($query); ?>)</h3>
                <div style="font-size: 0.9rem; color: var(--win-text-secondary);">
                    Процедур: <strong><?php echo $filteredUnpaidCount; ?></strong> |
                    Сумма: <strong style="color: var(--win-accent); font-size: 1.1rem;"><?php echo number_format($filteredUnpaidSum, 0, ',', ' '); ?> ₽</strong>
                </div>
            </div>

            <form method="POST" style="display: flex; gap: 8px; align-items: center; background: #fff; padding: 10px; border-radius: 6px; border: 1px solid var(--win-border);">
                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                <input type="hidden" name="action" value="bulk_pay">
                <input type="hidden" name="patient_id" value="<?php echo $selectedPatientId; ?>">
                <input type="hidden" name="start_date" value="<?php echo $startDate; ?>">
                <input type="hidden" name="end_date" value="<?php echo $endDate; ?>">

                <label style="font-size: 0.8rem;">Оплатить (кол-во):</label>
                <input type="number" name="count" value="<?php echo $filteredUnpaidCount; ?>" min="1" max="<?php echo $filteredUnpaidCount; ?>" style="width: 70px;">
                <button type="submit" class="btn btn-primary btn-sm">Принять оплату</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Пациент</th>
                <th>Процедура</th>
                <th>Даты / Кол-во</th>
                <th>Сумма</th>
                <th>Статус</th>
                <th style="text-align: right;">Действие</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($grouped as $key => $group):
                $unpaidItems = array_filter($group['items'], function($it) { return ($it['status'] ?? '') === 'unpaid'; });
                $paidItems = array_filter($group['items'], function($it) { return ($it['status'] ?? '') === 'paid'; });
                $count = count($group['items']);
                $unpaidCount = count($unpaidItems);
                $isGroup = $count > 1;
            ?>
            <tr class="group-row" data-key="<?php echo $key; ?>">
                <td style="font-weight: 600;"><?php echo htmlspecialchars($group['patient_name']); ?></td>
                <td><?php echo htmlspecialchars($group['procedure_name']); ?></td>
                <td>
                    <?php if ($isGroup): ?>
                        <span class="badge" style="background: var(--win-accent); color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem;">
                            <?php echo $count; ?> раз(а)
                        </span>
                        <button class="btn btn-sm btn-ghost toggle-group" data-target="detail-<?php echo $key; ?>" style="padding: 2px 4px; margin-left: 8px;">
                            <i data-lucide="chevron-down" style="width: 14px; height: 14px;"></i> детали
                        </button>
                    <?php else: ?>
                        <?php echo $group['items'][0]['date']; ?>
                    <?php endif; ?>
                </td>
                <td style="font-weight: 700; color: var(--win-accent);">
                    <?php echo number_format($group['price'] * $count, 0, ',', ' '); ?> ₽
                </td>
                <td>
                    <?php if ($unpaidCount > 0): ?>
                        <span class="status-red">Ожидает оплаты (<?php echo $unpaidCount; ?>)</span>
                    <?php elseif (count(array_filter($group['items'], function($it){return ($it['status']??'') === 'refunded';})) === $count): ?>
                        <span class="status-gray">Возвращено</span>
                    <?php else: ?>
                        <span class="status-green">Оплачено</span>
                    <?php endif; ?>
                </td>
                <td style="text-align: right;">
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        <?php if ($unpaidCount > 0): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                                <input type="hidden" name="action" value="bulk_pay_procedure">
                                <input type="hidden" name="patient_id" value="<?php echo $group['patient_id']; ?>">
                                <input type="hidden" name="procedure_id" value="<?php echo $group['procedure_id']; ?>">
                                <input type="hidden" name="procedure_name" value="<?php echo $group['procedure_name']; ?>">
                                <input type="hidden" name="count" value="<?php echo $unpaidCount; ?>">
                                <button type="submit" class="btn btn-sm btn-primary">Оплатить все</button>
                            </form>
                        <?php elseif (!$isGroup && $group['items'][0]['status'] === 'paid' && !$group['items'][0]['attended']): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Выполнить возврат средств?')">
                                <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                                <input type="hidden" name="action" value="refund">
                                <input type="hidden" name="id" value="<?php echo $group['items'][0]['id']; ?>">
                                <button type="submit" class="btn btn-sm" style="color: #d13438;">Возврат</button>
                            </form>
                        <?php endif; ?>
                        <?php if (!$isGroup): ?>
                            <a href="export.php?action=print_contract&id=<?php echo $group['items'][0]['id']; ?>" target="_blank" class="btn btn-sm" title="Печать договора">
                                <i data-lucide="file-text" class="icon" style="margin: 0;"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php if ($isGroup): ?>
            <tr id="detail-<?php echo $key; ?>" style="display: none; background: rgba(0,0,0,0.02);">
                <td colspan="6" style="padding: 15px 25px;">
                    <form method="POST" class="selective-pay-form">
                        <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">
                        <input type="hidden" name="action" value="pay_selected">

                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; margin-bottom: 15px;">
                            <?php foreach ($group['items'] as $item): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: white; border: 1px solid var(--win-border); border-radius: 8px; transition: all 0.2s;">
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; flex-grow: 1;">
                                        <?php if ($item['status'] === 'unpaid' || ($item['status'] === 'paid' && !$item['attended'])): ?>
                                            <input type="checkbox" name="ids[]" value="<?php echo $item['id']; ?>" data-status="<?php echo $item['status']; ?>" class="item-checkbox" style="width: 18px; height: 18px;">
                                        <?php elseif ($item['status'] === 'refunded'): ?>
                                            <i data-lucide="rotate-ccw" style="width: 18px; height: 18px; color: #999;"></i>
                                        <?php else: ?>
                                            <i data-lucide="check" style="width: 18px; height: 18px; color: #107c10;"></i>
                                        <?php endif; ?>
                                        <span style="font-size: 0.9rem;"><?php echo $item['date']; ?> <small style="color: #666;"><?php echo $item['time']; ?></small></span>
                                    </label>
                                    <?php if ($item['status'] === 'paid'): ?>
                                        <span style="color: #107c10; font-size: 0.75rem; font-weight: 600;">Оплачено</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div style="display: flex; justify-content: flex-end; align-items: center; gap: 15px;">
                            <div style="font-size: 0.85rem; color: var(--win-text-secondary);">
                                Выбрано: <strong class="selected-count">0</strong>
                            </div>
                            <?php if ($unpaidCount > 0): ?>
                                <button type="submit" class="btn btn-primary btn-sm pay-selected-btn" disabled>
                                    <i data-lucide="credit-card" class="icon" style="width: 14px; height: 14px;"></i> Оплатить выбранные
                                </button>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-sm refund-selected-btn" style="color: #d13438;" disabled onclick="this.form.querySelector('input[name=action]').value='bulk_refund_procedure'">
                                <i data-lucide="rotate-ccw" class="icon" style="width: 14px; height: 14px;"></i> Возврат выбранных
                            </button>
                        </div>
                    </form>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
            <?php if (empty($grouped)): ?>
                <tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--win-text-secondary);">Нет процедур, ожидающих оплаты</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.toggle-group').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const target = document.getElementById(targetId);
            const icon = btn.querySelector('i');
            if (target.style.display === 'none') {
                target.style.display = 'table-row';
                icon.setAttribute('data-lucide', 'chevron-up');
            } else {
                target.style.display = 'none';
                icon.setAttribute('data-lucide', 'chevron-down');
            }
            if (window.lucide) lucide.createIcons();
        });
    });

    // Selective pay/refund logic
    document.querySelectorAll('.selective-pay-form').forEach(form => {
        const checkboxes = form.querySelectorAll('.item-checkbox');
        const payBtn = form.querySelector('.pay-selected-btn');
        const refundBtn = form.querySelector('.refund-selected-btn');
        const counter = form.querySelector('.selected-count');

        checkboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                const checked = Array.from(form.querySelectorAll('.item-checkbox:checked'));
                if (counter) counter.innerText = checked.length;

                // Pay button active if any selected is unpaid
                if (payBtn) payBtn.disabled = !checked.some(c => c.dataset.status === 'unpaid');

                // Refund button active if any selected is paid
                if (refundBtn) refundBtn.disabled = !checked.some(c => c.dataset.status === 'paid');

                // Highlight row
                cb.closest('div').style.borderColor = cb.checked ? 'var(--win-accent)' : 'var(--win-border)';
                cb.closest('div').style.background = cb.checked ? 'rgba(0,120,212,0.05)' : 'white';
            });
        });

        refundBtn.addEventListener('click', (e) => {
            if (!confirm('Выполнить возврат средств за выбранные процедуры?')) {
                e.preventDefault();
            }
        });
    });
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
