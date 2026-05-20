<?php
require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Helpers\AuditLogger;

$store = new JsonStore(__DIR__ . '/../data');
$logger = new AuditLogger($store);
$logs = $logger->getLogs(200);

$pageTitle = 'Лог аудита';
include 'includes/header.php';
?>

<div class="mica-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h2>📝 История действий (Аудит)</h2>
        <div style="font-size: 0.85rem; color: #666;">Последние 200 операций</div>
    </div>

    <div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th>Время</th>
                <th>Пользователь</th>
                <th>Действие</th>
                <th>Объект</th>
                <th>Детали</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td style="white-space: nowrap; font-size: 0.85rem; color: #666;"><?php echo htmlspecialchars($log['timestamp']); ?></td>
                <td><span style="font-weight: 600;"><?php echo htmlspecialchars($log['user'] ?? 'admin'); ?></span></td>
                <td>
                    <?php
                        $action = $log['action'];
                        $class = '';
                        if ($action === 'CREATE') $class = 'status-confirmed';
                        if ($action === 'DELETE') $class = 'status-cancelled';
                        if ($action === 'UPDATE') $class = 'status-reserved';
                    ?>
                    <span class="status-badge <?php echo $class; ?>" style="font-size: 0.7rem; padding: 2px 6px;">
                        <?php echo htmlspecialchars($action); ?>
                    </span>
                </td>
                <td>
                    <span style="font-size: 0.8rem; background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px;">
                        <?php echo htmlspecialchars($log['target_type']); ?> #<?php echo htmlspecialchars($log['target_id']); ?>
                    </span>
                </td>
                <td style="font-size: 0.85rem;"><?php echo htmlspecialchars($log['details']); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
            <tr>
                <td colspan="5" style="text-align:center; padding: 40px; color: #888;">Логи пусты</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
