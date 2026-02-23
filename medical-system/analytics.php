<?php
require_once __DIR__ . '/Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('analytics_view')) {
    echo '<div class="card mica-effect"><h2>Доступ ограничен</h2><p>У вас нет прав для просмотра аналитики.</p></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$analytics = new \Medical\Core\Managers\AnalyticsManager();
$patientManager = new \Medical\Core\Managers\PatientManager();

$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-1 month'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// AJAX Handlers MUST be before any HTML output
if (isset($_GET['ajax_cabinet'])) {
    $cabName = $_GET['ajax_cabinet'];
    $apps = array_filter($analytics->getFilteredAppointments($startDate, $endDate), function($a) use ($cabName) {
        return $a['cabinet_id'] === $cabName;
    });
    ?>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align: left; border-bottom: 2px solid var(--win-border);">
                <th style="padding: 10px;">Дата/Время</th>
                <th style="padding: 10px;">Пациент</th>
                <th style="padding: 10px;">Процедура</th>
                <th style="padding: 10px;">Врач</th>
                <th style="padding: 10px;">Статус</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($apps as $a): ?>
            <tr style="border-bottom: 1px solid var(--win-border);">
                <td style="padding: 10px;"><?php echo $a['date']; ?> <span style="color:#666"><?php echo $a['time']; ?></span></td>
                <td style="padding: 10px;"><strong><?php echo htmlspecialchars($a['patient_name']); ?></strong></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($a['procedure_name']); ?></td>
                <td style="padding: 10px; font-size: 0.85rem;"><?php echo htmlspecialchars($a['doctor']); ?></td>
                <td style="padding: 10px;">
                    <span class="<?php echo $a['status'] === 'paid' ? 'status-green' : ($a['status'] === 'unpaid' ? 'status-red' : 'status-gray'); ?>">
                        <?php echo $a['status'] === 'paid' ? 'Оплачено' : ($a['status'] === 'unpaid' ? 'Ожидает' : 'Бесплатно'); ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    exit;
}

if (isset($_GET['ajax_doctor'])) {
    $docName = $_GET['ajax_doctor'];
    $apps = array_filter($analytics->getFilteredAppointments($startDate, $endDate), function($a) use ($docName) {
        return ($a['doctor'] ?? 'Не указан') === $docName;
    });
    ?>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align: left; border-bottom: 2px solid var(--win-border);">
                <th style="padding: 10px;">Дата/Время</th>
                <th style="padding: 10px;">Пациент</th>
                <th style="padding: 10px;">Процедура</th>
                <th style="padding: 10px;">Кабинет</th>
                <th style="padding: 10px;">Статус</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($apps as $a): ?>
            <tr style="border-bottom: 1px solid var(--win-border);">
                <td style="padding: 10px;"><?php echo $a['date']; ?> <span style="color:#666"><?php echo $a['time']; ?></span></td>
                <td style="padding: 10px;"><strong><?php echo htmlspecialchars($a['patient_name']); ?></strong></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($a['procedure_name']); ?></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($a['cabinet_id']); ?></td>
                <td style="padding: 10px;">
                    <span class="<?php echo $a['status'] === 'paid' ? 'status-green' : ($a['status'] === 'unpaid' ? 'status-red' : 'status-gray'); ?>">
                        <?php echo $a['status'] === 'paid' ? 'Оплачено' : ($a['status'] === 'unpaid' ? 'Ожидает' : 'Бесплатно'); ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    exit;
}

if (isset($_GET['ajax_proc'])) {
    $procName = $_GET['ajax_proc'];
    $apps = array_filter($analytics->getFilteredAppointments($startDate, $endDate), function($a) use ($procName) {
        return $a['procedure_name'] === $procName;
    });
    ?>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align: left; border-bottom: 2px solid var(--win-border);">
                <th style="padding: 10px;">Дата/Время</th>
                <th style="padding: 10px;">Пациент</th>
                <th style="padding: 10px;">Кабинет</th>
                <th style="padding: 10px;">Врач</th>
                <th style="padding: 10px;">Статус</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($apps as $a): ?>
            <tr style="border-bottom: 1px solid var(--win-border);">
                <td style="padding: 10px;"><?php echo $a['date']; ?> <span style="color:#666"><?php echo $a['time']; ?></span></td>
                <td style="padding: 10px;"><strong><?php echo htmlspecialchars($a['patient_name']); ?></strong></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($a['cabinet_id']); ?></td>
                <td style="padding: 10px; font-size: 0.85rem;"><?php echo htmlspecialchars($a['doctor']); ?></td>
                <td style="padding: 10px;">
                    <span class="<?php echo $a['status'] === 'paid' ? 'status-green' : ($a['status'] === 'unpaid' ? 'status-red' : 'status-gray'); ?>">
                        <?php echo $a['status'] === 'paid' ? 'Оплачено' : ($a['status'] === 'unpaid' ? 'Ожидает' : 'Бесплатно'); ?>
                    </span>
                    <?php if ($a['attended']): ?>
                        <span class="status-green" style="margin-left: 8px;">Принят</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    exit;
}

if (isset($_GET['ajax_patient'])) {
    $pid = $_GET['ajax_patient'];
    $apps = $analytics->getPatientDetails($pid);
    ?>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align: left; border-bottom: 2px solid var(--win-border);">
                <th style="padding: 10px;">Дата/Время</th>
                <th style="padding: 10px;">Процедура</th>
                <th style="padding: 10px;">Кабинет</th>
                <th style="padding: 10px;">Врач</th>
                <th style="padding: 10px;">Статус оплаты</th>
                <th style="padding: 10px;">Отметка о посещении</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($apps as $a): ?>
            <tr style="border-bottom: 1px solid var(--win-border);">
                <td style="padding: 10px;"><?php echo $a['date']; ?> <span style="color:#666"><?php echo $a['time']; ?></span></td>
                <td style="padding: 10px;"><strong><?php echo htmlspecialchars($a['procedure_name']); ?></strong></td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($a['cabinet_id']); ?></td>
                <td style="padding: 10px; font-size: 0.85rem;"><?php echo htmlspecialchars($a['doctor']); ?></td>
                <td style="padding: 10px;">
                    <span class="<?php echo $a['status'] === 'paid' ? 'status-green' : ($a['status'] === 'unpaid' ? 'status-red' : 'status-gray'); ?>">
                        <?php echo $a['status'] === 'paid' ? 'Оплачено' : ($a['status'] === 'unpaid' ? 'Ожидает' : 'Бесплатно'); ?>
                    </span>
                </td>
                <td style="padding: 10px;">
                    <?php if ($a['attended']): ?>
                        <div style="display: flex; align-items: center; gap: 8px; color: #107c10;">
                            <i data-lucide="check-circle-2" style="width:16px; height:16px;"></i>
                            <span><?php echo date('H:i', strtotime($a['attended_at'])); ?> (<?php echo htmlspecialchars($a['performed_by']); ?>)</span>
                        </div>
                    <?php else: ?>
                        <span style="color: #666;">-</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    exit;
}

$summary = $analytics->getSummary($startDate, $endDate);
$workload = $analytics->getWorkloadByCabinet($startDate, $endDate);
$procStats = $analytics->getProcedureStats($startDate, $endDate);
$doctorLoad = $analytics->getDoctorLoad($startDate, $endDate);
$ageStats = $analytics->getAgeStats();
$mkbStats = $analytics->getMkbStats();
$durationStats = $analytics->getStayDurationStats();
$cabTimeLoad = $analytics->getCabinetTimeLoad($startDate, $endDate);

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <h1>Аналитика: WES МЕД</h1>
    <div style="display: flex; gap: 10px;">
        <button class="btn" onclick="window.print()"><i data-lucide="printer" class="icon"></i> Печать</button>
        <a href="export.php?action=analytics_csv&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-primary"><i data-lucide="download" class="icon"></i> Экспорт CSV</a>
    </div>
</div>

<!-- Filters -->
<div class="card mica-effect" style="margin-bottom: 24px;">
    <form method="GET" style="display: flex; gap: 20px; align-items: flex-end;">
        <div>
            <label style="display:block; margin-bottom: 8px;">Начало периода</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
        </div>
        <div>
            <label style="display:block; margin-bottom: 8px;">Конец периода</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
        </div>
        <div style="border-left: 1px solid var(--win-border); padding-left: 20px; display: flex; gap: 15px; align-items: flex-end;">
            <div>
                <label style="display:block; margin-bottom: 8px;">Дней вперед</label>
                <input type="number" id="days_ahead" min="0" max="365" placeholder="0" style="width: 80px;">
            </div>
            <div style="padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="save_period" style="width: 18px; height: 18px; cursor: pointer;">
                <label for="save_period" style="font-size: 0.85rem; cursor: pointer;">Запомнить</label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="refresh-cw" class="icon"></i> Применить фильтр
        </button>
    </form>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <div class="card mica-effect">
        <h3 style="color: var(--win-text-secondary); font-size: 0.85rem;">Всего назначений</h3>
        <div style="font-size: 2rem; font-weight: 700;"><?php echo $summary['total_appointments']; ?></div>
        <div style="font-size: 0.8rem; color: #107c10; margin-top: 5px;">Выполнено: <?php echo $summary['attended_count']; ?></div>
    </div>
    <?php if (\Medical\Core\Auth::can('finance_view')): ?>
    <div class="card mica-effect">
        <h3 style="color: var(--win-text-secondary); font-size: 0.85rem;">Общая выручка</h3>
        <div style="font-size: 2rem; font-weight: 700; color: #107c10;"><?php echo number_format($summary['total_revenue'], 0, ',', ' '); ?> ₽</div>
        <div style="font-size: 0.8rem; color: var(--win-text-secondary); margin-top: 5px;">За выбранный период</div>
    </div>
    <?php endif; ?>
    <div class="card mica-effect">
        <h3 style="color: var(--win-text-secondary); font-size: 0.85rem;">Пациентов в базе</h3>
        <div style="font-size: 2rem; font-weight: 700;"><?php echo $summary['total_patients']; ?></div>
    </div>
    <div class="card mica-effect">
        <h3 style="color: var(--win-text-secondary); font-size: 0.85rem;">Ср. длительность</h3>
        <?php
            $avgStay = !empty($durationStats) ? array_sum(array_keys($durationStats)) / count($durationStats) : 0;
        ?>
        <div style="font-size: 2rem; font-weight: 700;"><?php echo round($avgStay, 1); ?> дн.</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
    <div class="card mica-effect">
        <h2>Возрастные категории</h2>
        <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 20px;">
            <?php foreach ($ageStats as $label => $count):
                $perc = $summary['total_patients'] > 0 ? ($count / $summary['total_patients']) * 100 : 0;
            ?>
                <div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 0.9rem;">
                        <span><?php echo $label; ?> лет</span>
                        <strong><?php echo $count; ?> чел.</strong>
                    </div>
                    <div style="height: 8px; background: rgba(0,0,0,0.05); border-radius: 4px; overflow: hidden;">
                        <div style="height: 100%; width: <?php echo $perc; ?>%; background: var(--win-accent);"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card mica-effect">
        <h2>Длительность пребывания (дни)</h2>
        <div style="display: flex; align-items: flex-end; gap: 10px; height: 150px; margin-top: 20px; border-bottom: 1px solid var(--win-border); padding-bottom: 5px;">
            <?php
            $maxDur = !empty($durationStats) ? max($durationStats) : 1;
            foreach ($durationStats as $days => $count):
                $h = ($count / $maxDur) * 100;
            ?>
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 5px;">
                    <div style="width: 100%; height: <?php echo $h; ?>%; background: var(--win-accent); border-radius: 4px 4px 0 0;" title="<?php echo $count; ?> чел."></div>
                    <span style="font-size: 0.7rem; color: var(--win-text-secondary);"><?php echo $days; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <p style="font-size: 0.75rem; color: var(--win-text-secondary); text-align: center; margin-top: 10px;">Дней пребывания по последним записям</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px; margin-bottom: 32px;">
    <div class="card mica-effect">
        <h2>Статистика по процедурам</h2>
        <table style="font-size: 0.9rem;">
            <thead>
                <tr>
                    <th>Процедура</th>
                    <th style="text-align:center;">Записей</th>
                    <th style="text-align:center;">Посещений</th>
                    <?php if (\Medical\Core\Auth::can('finance_view')): ?>
                        <th style="text-align:right;">Выручка</th>
                        <th style="text-align:right;">Не оплачено</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($procStats as $stat): ?>
                <tr class="clickable-row" onclick="showProcDetail('<?php echo htmlspecialchars($stat['name']); ?>')">
                    <td><strong><?php echo htmlspecialchars($stat['name']); ?></strong></td>
                    <td style="text-align:center;"><?php echo $stat['total_records']; ?></td>
                    <td style="text-align:center;"><?php echo $stat['attended']; ?></td>
                    <?php if (\Medical\Core\Auth::can('finance_view')): ?>
                        <td style="text-align:right; font-weight:600; color:#107c10;"><?php echo number_format($stat['revenue'], 0); ?> ₽</td>
                        <td style="text-align:right; color:#d13438;"><?php echo $stat['unpaid']; ?> шт.</td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card mica-effect">
        <h2>Загрузка кабинетов (мин.)</h2>
        <div style="margin-top: 20px;">
            <?php foreach ($cabTimeLoad as $cab => $mins):
                $maxMins = max($cabTimeLoad);
                $perc = ($mins / $maxMins) * 100;
            ?>
                <div style="margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 4px;">
                        <span>Каб. <?php echo htmlspecialchars($cab); ?></span>
                        <span><?php echo $mins; ?> мин.</span>
                    </div>
                    <div style="height: 6px; background: rgba(0,0,0,0.05); border-radius: 3px; overflow: hidden; cursor: pointer;" onclick="showCabinetDetail('<?php echo htmlspecialchars($cab); ?>')">
                        <div style="height: 100%; width: <?php echo $perc; ?>%; background: #107c10;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
    <div class="card mica-effect">
        <h2>Статистика по диагнозам (МКБ-10)</h2>
        <table style="font-size: 0.85rem; margin-top: 15px;">
            <thead>
                <tr>
                    <th>Код</th>
                    <th>Диагноз</th>
                    <th style="text-align: right;">Пациентов</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mkbStats as $code => $data): ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 700;"><?php echo $code; ?></td>
                        <td style="color: var(--win-text-secondary);"><?php echo htmlspecialchars($data['text']); ?></td>
                        <td style="text-align: right; font-weight: 600;"><?php echo $data['count']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card mica-effect">
        <h2>Загрузка врачей</h2>
        <?php foreach ($doctorLoad as $doc => $count):
            $percent = $summary['total_appointments'] > 0 ? ($count / $summary['total_appointments']) * 100 : 0;
        ?>
            <div style="margin-bottom: 16px; cursor: pointer;" onclick="showDoctorDetail('<?php echo htmlspecialchars($doc); ?>')">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span style="font-size: 0.9rem; font-weight: 500;"><?php echo htmlspecialchars($doc); ?></span>
                    <span style="font-size: 0.85rem; color: var(--win-text-secondary);"><?php echo $count; ?> назн.</span>
                </div>
                <div style="height: 6px; background: rgba(0,0,0,0.05); border-radius: 3px; overflow: hidden;">
                    <div style="height: 100%; width: <?php echo $percent; ?>%; background: var(--win-accent);"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card mica-effect">
    <h2>Поиск пациента для анализа</h2>
    <input type="text" id="patient_search" placeholder="Введите ФИО пациента для детальной статистики..." style="width: 100%; padding: 12px; margin-bottom: 20px;" onkeyup="searchPatient(this.value)">
    <div id="patient_results" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;"></div>
</div>

<!-- Details Modal -->
<div id="detailModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(8px);">
    <div class="card mica-effect" style="width: 90%; max-width: 1000px; margin: 5vh auto; height: 80vh; overflow-y: auto; padding: 40px;">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 id="modalTitle">Детализация</h2>
            <button class="btn" onclick="document.getElementById('detailModal').style.display='none'">Закрыть</button>
        </div>
        <div id="modalBody"></div>
    </div>
</div>

<script>
function searchPatient(q) {
    if (q.length < 2) {
        document.getElementById('patient_results').innerHTML = '';
        return;
    }
    fetch('patients.php?ajax=1&q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            let html = '';
            data.forEach(p => {
                html += `<div class="card mica-effect" style="margin-bottom:0; padding: 15px; cursor: pointer;" onclick="showPatientDetail('${p.id}', '${p.name.replace(/'/g, "\\'")}')">
                    <strong>${p.name}</strong><br>
                    <small style="color: var(--win-text-secondary)">${p.birth_date}</small>
                </div>`;
            });
            document.getElementById('patient_results').innerHTML = html;
        });
}

function showPatientDetail(id, name) {
    document.getElementById('modalTitle').innerText = 'Анализ пациента: ' + name;
    document.getElementById('detailModal').style.display = 'block';
    document.getElementById('modalBody').innerHTML = '<p>Загрузка данных...</p>';

    fetch('analytics.php?ajax_patient=' + id)
        .then(r => r.text())
        .then(html => {
            document.getElementById('modalBody').innerHTML = html;
            lucide.createIcons();
        });
}

function showCabinetDetail(cab) {
    document.getElementById('modalTitle').innerText = 'Детализация кабинета: ' + cab;
    document.getElementById('detailModal').style.display = 'block';
    document.getElementById('modalBody').innerHTML = '<p>Загрузка данных...</p>';

    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;

    fetch(`analytics.php?ajax_cabinet=${encodeURIComponent(cab)}&start_date=${startDate}&end_date=${endDate}`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('modalBody').innerHTML = html;
            lucide.createIcons();
        });
}

function showDoctorDetail(name) {
    document.getElementById('modalTitle').innerText = 'Детализация врача: ' + name;
    document.getElementById('detailModal').style.display = 'block';
    document.getElementById('modalBody').innerHTML = '<p>Загрузка данных...</p>';

    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;

    fetch(`analytics.php?ajax_doctor=${encodeURIComponent(name)}&start_date=${startDate}&end_date=${endDate}`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('modalBody').innerHTML = html;
            lucide.createIcons();
        });
}

function showProcDetail(name) {
    document.getElementById('modalTitle').innerText = 'Детализация процедуры: ' + name;
    document.getElementById('detailModal').style.display = 'block';
    document.getElementById('modalBody').innerHTML = '<p>Загрузка данных...</p>';

    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;

    fetch(`analytics.php?ajax_proc=${encodeURIComponent(name)}&start_date=${startDate}&end_date=${endDate}`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('modalBody').innerHTML = html;
            lucide.createIcons();
        });
}
</script>


<?php include __DIR__ . '/includes/footer.php'; ?>
