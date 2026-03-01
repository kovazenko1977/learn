<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\LogManager;
use Hop\Core\JsonStore;

checkRole('admin');

$logger = new LogManager();
$date = $_GET['date'] ?? date('Y-m-d');
$logs = $logger->getLogs($date);
$logFiles = $logger->getLogFiles();
rsort($logFiles);

$userStore = new JsonStore('data/users.json');
$users = [];
foreach ($userStore->read() as $u) $users[$u['id']] = $u['name'];

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="animation: slideDown 0.5s ease-out;">
        <div class="header-action-row">
            <div class="header-title-block">
                <h1>Системный лог</h1>
                <p style="color:var(--win-text-secondary);">История действий и событий в системе</p>
            </div>
            <div class="header-buttons-block">
                <select onchange="location.href='logs.php?date='+this.value" style="height:40px;">
                    <?php foreach ($logFiles as $lf): ?>
                        <option value="<?php echo $lf; ?>" <?php echo $lf === $date ? 'selected' : ''; ?>><?php echo $lf; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="card mica" style="padding:0; overflow:hidden; animation: slideUp 0.6s ease-out;">
        <div class="table-responsive">
            <table class="table" style="margin:0;">
                <thead>
                    <tr>
                        <th style="width:180px;">Время</th>
                        <th style="width:200px;">Пользователь</th>
                        <th style="width:180px;">Действие</th>
                        <th>Детали</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:40px; color:var(--win-text-secondary);">Логов за выбранную дату не найдено</td></tr>
                    <?php else: ?>
                        <?php foreach (array_reverse($logs) as $l): ?>
                            <tr>
                                <td style="font-family:monospace; font-size:12px;"><?php echo $l['timestamp']; ?></td>
                                <td style="font-weight:600;">
                                    <?php echo $l['user_id'] ? ($users[$l['user_id']] ?? "ID: {$l['user_id']}") : 'Система / Гость'; ?>
                                </td>
                                <td>
                                    <span class="badge" style="background:<?php echo $l['level'] === 'warning' ? 'var(--priority-critical)' : 'rgba(0,120,212,0.1)'; ?>; color:<?php echo $l['level'] === 'warning' ? '#fff' : 'var(--win-accent)'; ?>;">
                                        <?php echo $l['action']; ?>
                                    </span>
                                </td>
                                <td style="font-size:13px;"><?php echo htmlspecialchars($l['details']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
