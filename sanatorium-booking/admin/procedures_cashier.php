<?php require_once "auth.php";
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Procedures\ProceduresManager;

$store = new JsonStore(__DIR__ . '/../data');
$procManager = new ProceduresManager($store);

$selectedGuestId = isset($_GET['guest_id']) ? (int)$_GET['guest_id'] : null;
$assignments = [];
if ($selectedGuestId) {
    $assignments = $procManager->getPatientAssignments($selectedGuestId);
}

$guests = $store->findAll('guests');
$procedures = $store->findAll('procedures');
$procMap = []; foreach($procedures as $p) $procMap[$p['id']] = $p;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay') {
    $procManager->updateAssignmentStatus((int)$_POST['id'], 'paid');
    header('Location: procedures_cashier.php?guest_id=' . $selectedGuestId . '&paid=1');
    exit;
}

$pageTitle = 'Оплата процедур (Кассир)';
include 'includes/header.php';
?>

<div class="mica-card">
    <h2>💰 Прием оплаты</h2>
    <div style="margin-bottom: 20px;" class="patient-search-wrapper">
        <label>Поиск пациента</label>
        <div style="position: relative; max-width: 400px;">
            <input type="text" id="patient-search" placeholder="Введите фамилию или телефон..." autocomplete="off" style="padding-right: 35px;">
            <button type="button" id="clear-search" style="position: absolute; right: 5px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; display: none;">✕</button>
        </div>
        <div id="search-results" class="mica-card" style="display:none; position:absolute; z-index:100; width:400px; padding:10px; max-height: 300px; overflow-y: auto;"></div>
    </div>

    <?php if($selectedGuestId):
        $currentGuest = null;
        foreach($guests as $g) if($g['id'] == $selectedGuestId) $currentGuest = $g;
    ?>
        <div style="margin-top:20px; padding:15px; background:rgba(0,120,212,0.1); border-radius:8px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3>Пациент: <?php echo htmlspecialchars($currentGuest['name'] ?? ''); ?></h3>
                <p>Телефон: <?php echo htmlspecialchars($currentGuest['phone'] ?? ''); ?></p>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-secondary no-print">🖨️ Печать чека</button>
            </div>
        </div>

        <div class="table-responsive printable-area" style="margin-top:20px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Процедура</th>
                        <th>Дата и время</th>
                        <th>Стоимость</th>
                        <th>Статус</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($assignments)): ?>
                        <tr><td colspan="6" style="text-align:center;">Назначенных процедур нет</td></tr>
                    <?php else: ?>
                        <?php foreach($assignments as $a):
                            $p = $procMap[$a['procedure_id']] ?? null;
                            $isFree = (float)($a['price'] ?? 0) == 0;
                            $status = $a['status'] ?? 'assigned';

                            $rowClass = 'status-red'; // Default paid
                            if ($isFree) $rowClass = 'status-gray';
                            if ($status === 'paid' || $status === 'completed') $rowClass = 'status-green';
                        ?>
                        <tr>
                            <td><?php echo $a['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($p['name'] ?? 'Unknown'); ?></strong></td>
                            <td><?php echo $a['date']; ?> <?php echo $a['time']; ?></td>
                            <td><?php echo number_format($a['price'], 0, ',', ' '); ?> ₽</td>
                            <td>
                                <span class="status-badge <?php echo $rowClass; ?>">
                                    <?php
                                        if($status === 'completed') echo 'Выполнена';
                                        elseif($isFree) echo 'Бесплатно';
                                        elseif($status === 'paid') echo 'Оплачено';
                                        else echo 'Ожидает оплаты';
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php if(!$isFree && $status === 'assigned'): ?>
                                    <form method="post" style="margin:0;">
                                        <input type="hidden" name="action" value="pay">
                                        <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                        <button type="submit" class="btn">Оплатить</button>
                                    </form>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    const guests = <?php echo json_encode($guests); ?>;
    const patientSearch = document.getElementById('patient-search');
    const clearSearchBtn = document.getElementById('clear-search');
    const searchResults = document.getElementById('search-results');

    patientSearch.addEventListener('input', function() {
        const val = this.value.toLowerCase();
        clearSearchBtn.style.display = val.length > 0 ? 'block' : 'none';

        if (val.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        const filtered = guests.filter(g =>
            g.name.toLowerCase().includes(val) ||
            (g.phone && g.phone.toLowerCase().includes(val))
        );

        if (filtered.length > 0) {
            searchResults.innerHTML = filtered.map(g => `
                <div class="search-item" onclick="selectGuest(${g.id})" style="padding:10px; cursor:pointer; border-bottom:1px solid rgba(0,0,0,0.05);">
                    <strong>${g.name}</strong><br>
                    <small>${g.phone || ''}</small>
                </div>
            `).join('');
            searchResults.style.display = 'block';
        } else {
            searchResults.innerHTML = '<div style="padding:10px;">Ничего не найдено</div>';
            searchResults.style.display = 'block';
        }
    });

    clearSearchBtn.addEventListener('click', function() {
        patientSearch.value = '';
        this.style.display = 'none';
        searchResults.style.display = 'none';
        patientSearch.focus();
    });

    function selectGuest(id) {
        window.location.href = 'procedures_cashier.php?guest_id=' + id;
    }

    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!patientSearch.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
</script>

<style>
    .search-item:hover { background: rgba(0, 120, 212, 0.05); }
    @media print {
        .no-print, header, nav, .sidebar, .patient-search-wrapper { display: none !important; }
        .mica-card { border: none !important; box-shadow: none !important; background: white !important; }
        body { background: white !important; }
        .printable-area { margin-top: 0 !important; }
        .btn { display: none !important; }
    }
</style>

<?php include 'includes/footer.php'; ?>
