<?php
require_once "auth.php";
require_once "../core/autoload.php";
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Rooms\RoomManager;

$store = new JsonStore(__DIR__ . '/../data');
$roomManager = new RoomManager($store);

if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $roomManager->updateHousekeeping($_POST['room_id'], $_POST['status']);
    header('Location: housekeeping.php');
    return;
}

$rooms = $roomManager->getAllRooms();
$pageTitle = 'Уборка номеров';
include 'includes/header.php';
?>
<div class="mica-card">
    <h2>🧹 Статус уборки номеров</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
        <?php foreach ($rooms as $room): ?>
            <div style="padding: 15px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.1); background: <?php
                echo ($room['housekeeping']??'clean') === 'dirty' ? 'rgba(216,59,1,0.05)' : (($room['housekeeping']??'') === 'cleaning' ? 'rgba(255,170,68,0.05)' : 'rgba(16,124,16,0.05)');
            ?>;">
                <strong>Номер <?php echo htmlspecialchars($room['room_number']); ?></strong>
                <p>Статус: <?php
                    echo ($room['housekeeping']??'clean') === 'dirty' ? '🔴 Грязный' : (($room['housekeeping']??'') === 'cleaning' ? '🟡 Уборка' : '🟢 Чисто');
                ?></p>
                <form method="POST" style="display: flex; gap: 5px; margin-top: 10px;">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                    <select name="status" onchange="this.form.submit()" style="font-size: 0.8rem; padding: 4px;">
                        <option value="clean" <?php if(($room['housekeeping']??'')==='clean') echo 'selected';?>>Чисто</option>
                        <option value="cleaning" <?php if(($room['housekeeping']??'')==='cleaning') echo 'selected';?>>Уборка</option>
                        <option value="dirty" <?php if(($room['housekeeping']??'')==='dirty') echo 'selected';?>>Грязный</option>
                    </select>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
