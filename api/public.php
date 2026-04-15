<?php
require_once __DIR__ . '/../includes/Storage.php';

header('Content-Type: text/html; charset=UTF-8');

$id = $_GET['id'] ?? '';
$prices = Storage::getPrices();

$targetList = null;
foreach ($prices as $pl) {
    if ($pl['id'] === $id) {
        $targetList = $pl;
        break;
    }
}

if (!$targetList) {
    echo "Прайс-лист не найден.";
    exit;
}

?>
<div class="price-list-container" style="font-family: sans-serif; margin: 20px 0;">
    <h3 style="color: #333; margin-bottom: 15px; border-left: 4px solid #7360f2; padding-left: 10px;"><?php echo htmlspecialchars($targetList['title']); ?></h3>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; min-width: 600px; border: 1px solid #eee;">
            <thead>
                <tr style="background-color: #f8f9fa;">
                    <?php foreach ($targetList['columns'] as $col): ?>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #7360f2; color: #444; font-size: 14px; text-transform: uppercase;"><?php echo htmlspecialchars($col); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($targetList['data'] as $row): ?>
                    <?php if ($row['isCategory']): ?>
                        <tr style="background-color: rgba(115, 96, 242, 0.05);">
                            <td colspan="<?php echo count($targetList['columns']); ?>" style="padding: 12px; font-weight: bold; color: #7360f2; border-bottom: 1px solid #eee;">
                                <?php echo htmlspecialchars($row['cells'][0]); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <?php foreach ($targetList['columns'] as $idx => $col): ?>
                                <td style="padding: 12px; color: #555; font-size: 14px;">
                                    <?php echo htmlspecialchars($row['cells'][$idx] ?? ''); ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<style>
    .price-list-container tr:hover:not(:first-child) {
        background-color: #fafafa;
    }
</style>
