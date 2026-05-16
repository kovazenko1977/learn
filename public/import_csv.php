<?php require_once __DIR__ . "/auth.php";
require_once __DIR__ . '/../src/autoload.php';
use App\Database\JsonStore;
$store = new JsonStore(__DIR__ . '/../data');

$pageTitle = 'Импорт данных из CSV';
include 'includes/header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $type = $_POST['import_type'] ?? 'rooms';
    $file = $_FILES['csv_file']['tmp_name'];

    if (($handle = fopen($file, "r")) !== FALSE) {
        $header = fgetcsv($handle, 1000, ",");
        $count = 0;

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($header) !== count($data)) continue;
            $row = array_combine($header, $data);
            if ($type === 'rooms') {
                $store->save('rooms', [
                    'room_number' => $row['room_number'] ?? $row['name'] ?? 'N/A',
                    'room_class_id' => (int)($row['room_class_id'] ?? 1),
                    'building' => $row['building'] ?? '1',
                    'price_per_day' => (float)($row['price_per_day'] ?? 0),
                    'capacity' => (int)($row['capacity'] ?? 1)
                ]);
            } elseif ($type === 'bookings') {
                $store->save('bookings', [
                    'client_name' => $row['client_name'],
                    'phone' => $row['phone'],
                    'room_id' => (int)$row['room_id'],
                    'check_in' => $row['check_in'],
                    'check_out' => $row['check_out'],
                    'status' => $row['status'] ?? 'confirmed',
                    'persons' => (int)($row['persons'] ?? 1)
                ]);
            }
            $count++;
        }
        fclose($handle);
        $message = "Успешно импортировано $count записей ($type).";
    } else {
        $error = "Ошибка открытия файла.";
    }
}
?>

<div class="mica-card">
    <h2>📥 Импорт данных</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="modern-form">
        <div class="form-group">
            <label>Тип данных</label>
            <select name="import_type">
                <option value="rooms">Номера</option>
                <option value="bookings">Бронирования</option>
            </select>
        </div>
        <div class="form-group">
            <label>Файл CSV</label>
            <input type="file" name="csv_file" accept=".csv" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Загрузить и импортировать</button>
        </div>
    </form>

    <div style="margin-top: 20px; font-size: 0.9rem; color: #666;">
        <p><strong>Формат CSV для номеров:</strong> room_number,room_class_id,building,price_per_day,capacity</p>
        <p><strong>Формат CSV для бронирований:</strong> client_name,phone,room_id,check_in,check_out,status,persons</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
