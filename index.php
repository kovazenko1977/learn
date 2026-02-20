<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\RequestManager;

$requestStore = new JsonStore('data/requests.json');
// No notifier needed for simple listing
$requestManager = new RequestManager($requestStore);
$requests = $requestManager->getAll();

$userRole = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];

$filteredRequests = [];
foreach ($requests as $req) {
    if ($userRole === 'admin' || $userRole === 'manager') {
        $filteredRequests[] = $req;
    } elseif ($userRole === 'initiator') {
        if ($req['initiator_id'] === $userId) $filteredRequests[] = $req;
    } elseif ($userRole === 'performer') {
        if (isset($req['performer_id']) && $req['performer_id'] === $userId) $filteredRequests[] = $req;
    } elseif ($userRole === 'service_lead') {
        $userStore = new JsonStore('data/users.json');
        $user = (new \Hop\Core\UserManager($userStore))->getById($userId);
        if ($user && $req['service_id'] === $user['service_id']) $filteredRequests[] = $req;
    } elseif ($userRole === 'controller') {
        if ($req['status'] === 'checking' || $req['status'] === 'completed') $filteredRequests[] = $req;
    }
}

usort($filteredRequests, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

$servicesStore = new JsonStore('data/services.json');
$services = [];
foreach ($servicesStore->read() as $s) $services[$s['id']] = $s['name'];

$statusNames = [
    'new' => 'Новая', 'assigned' => 'Назначена', 'working' => 'В работе',
    'checking' => 'Проверка', 'returned' => 'Доработка', 'completed' => 'Выполнена', 'closed' => 'Закрыта'
];

$settingsStore = new JsonStore('data/settings.json');
$settings = $settingsStore->read();
$slaConfig = $settings['sla'] ?? [];

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="animation: slideDown 0.5s ease-out;">
        <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:24px;">
            <div>
                <h1>Главная панель</h1>
                <p style="color:var(--win-text-secondary);"><?php echo $_SESSION['user_name']; ?>, добро пожаловать в ХОП</p>
            </div>
            <?php if ($userRole === 'initiator' || $userRole === 'admin'): ?>
                <a href="create.php" class="btn-primary" style="text-decoration:none; display:flex; align-items:center; gap:8px; padding: 12px 24px;">
                    <i class="lucide-plus"></i> Новая заявка
                </a>
            <?php endif; ?>
        </div>

        <div class="stats-grid">
            <?php
                $newCount = 0; $workCount = 0; $overdueCount = 0;
                foreach($filteredRequests as $r) {
                    if ($r['status'] === 'new') $newCount++;
                    if ($r['status'] === 'working') $workCount++;
                    $hoursLimit = $slaConfig[$r['priority']] ?? 24;
                    if (!in_array($r['status'], ['completed', 'closed']) && time() > (strtotime($r['created_at']) + ($hoursLimit * 3600))) {
                        $overdueCount++;
                    }
                }
            ?>
            <div class="stat-card mica" style="border-bottom: 3px solid var(--status-new);">
                <div class="stat-value" style="color:var(--status-new);"><?php echo $newCount; ?></div>
                <div class="stat-label">Ожидают</div>
            </div>
            <div class="stat-card mica" style="border-bottom: 3px solid var(--status-working);">
                <div class="stat-value" style="color:var(--status-working);"><?php echo $workCount; ?></div>
                <div class="stat-label">В работе</div>
            </div>
            <div class="stat-card mica" style="border-bottom: 3px solid var(--priority-critical);">
                <div class="stat-value" style="color:var(--priority-critical);"><?php echo $overdueCount; ?></div>
                <div class="stat-label">Просрочено</div>
            </div>
            <div class="stat-card mica" style="border-bottom: 3px solid var(--win-accent);">
                <div class="stat-value" style="color:var(--win-accent);"><?php echo count($filteredRequests); ?></div>
                <div class="stat-label">Всего</div>
            </div>
        </div>
    </div>

    <div class="card mica" style="padding:20px; margin-bottom:24px; animation: slideUp 0.6s ease-out;">
        <div style="display:flex; gap:12px; margin-bottom:16px; flex-wrap: wrap;">
            <div style="flex:1; min-width: 250px; position:relative;">
                <i class="lucide-search" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); width:18px; height:18px; color:var(--win-text-secondary);"></i>
                <input type="text" id="search-input" placeholder="Поиск заявок..." style="padding-left:48px; height:48px; border-radius:12px; font-size:15px;">
            </div>
            <div style="width: 200px;">
                <select id="sort-select" style="height:48px; border-radius:12px;">
                    <option value="date-desc">Сначала новые</option>
                    <option value="date-asc">Сначала старые</option>
                    <option value="priority">По приоритету</option>
                </select>
            </div>
        </div>
        <div class="filter-chips" style="display:flex; gap:8px; overflow-x:auto; padding-bottom:8px; scrollbar-width: none;">
            <button class="filter-chip active" data-status="all">Все</button>
            <?php foreach ($statusNames as $code => $name): ?>
                <button class="filter-chip" data-status="<?php echo $code; ?>"><?php echo $name; ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="list-container" id="request-list" style="display: grid; gap: 12px; animation: slideUp 0.7s ease-out;">
        <?php if (empty($filteredRequests)): ?>
            <div class="card mica" style="text-align:center; color:var(--win-text-secondary); padding: 60px 20px;">
                <div style="width:64px; height:64px; background:rgba(0,0,0,0.03); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                    <i class="lucide-inbox" style="width:32px; height:32px; opacity: 0.5;"></i>
                </div>
                <p style="font-weight:600; font-size:16px; color:var(--win-text);">Активных заявок пока нет</p>
                <p style="font-size:14px;">Когда появятся задачи, они отобразятся здесь</p>
            </div>
        <?php else: ?>
            <?php foreach ($filteredRequests as $index => $req): ?>
                <?php
                    $isOverdue = false;
                    if (!in_array($req['status'], ['completed', 'closed'])) {
                        $hoursLimit = $slaConfig[$req['priority']] ?? 24;
                        if (time() > (strtotime($req['created_at']) + ($hoursLimit * 3600))) $isOverdue = true;
                    }
                    $priorityMap = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
                ?>
                <a href="view.php?id=<?php echo $req['id']; ?>"
                   class="card mica list-item request-card"
                   data-status="<?php echo $req['status']; ?>"
                   data-desc="<?php echo htmlspecialchars(mb_strtolower($req['description'])); ?>"
                   data-timestamp="<?php echo strtotime($req['created_at']); ?>"
                   data-priority="<?php echo $priorityMap[$req['priority']] ?? 0; ?>"
                   style="display:block; text-decoration:none; color:inherit; animation-delay: <?php echo $index * 0.05; ?>s; padding: 20px; border-left: 6px solid var(--priority-<?php echo $req['priority']; ?>);">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <span class="badge status-<?php echo $req['status']; ?>" style="padding: 4px 10px; font-size: 11px;">
                                <?php echo $statusNames[$req['status']]; ?>
                            </span>
                            <?php if ($isOverdue): ?>
                                <span class="badge" style="background:var(--priority-critical); padding: 4px 10px; font-size: 11px;">ПРОСРОЧЕНО</span>
                            <?php endif; ?>
                        </div>
                        <span style="font-size:11px; color:var(--win-text-secondary); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                            #<?php echo $req['id']; ?> • <?php echo date('d.m H:i', strtotime($req['created_at'])); ?>
                        </span>
                    </div>
                    <div style="font-weight:800; margin-bottom:8px; color:var(--win-text); font-size:16px;">
                        <?php echo htmlspecialchars($services[$req['service_id']] ?? 'Сервисная служба'); ?>
                    </div>
                    <div style="font-size:14px; color:var(--win-text-secondary); margin-bottom:16px; line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                        <?php echo htmlspecialchars($req['description']); ?>
                    </div>
                    <div style="display:flex; align-items:center; gap:16px; font-size:12px; color:var(--win-text-secondary);">
                        <span style="display:flex; align-items:center; gap:4px;">
                            <i class="lucide-map-pin" style="width:14px; height:14px;"></i>
                            <?php echo "Корп. {$req['location']['building']}, каб. {$req['location']['room']}"; ?>
                        </span>
                        <?php if (!empty($req['photo'])): ?>
                            <span style="display:flex; align-items:center; gap:4px;">
                                <i class="lucide-image" style="width:14px; height:14px;"></i>
                                Фото
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.filter-chip {
    background: rgba(0,0,0,0.03);
    border: 1px solid var(--win-border);
    border-radius: 20px;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 600;
    color: var(--win-text-secondary);
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
}
.filter-chip:hover { background: rgba(0,0,0,0.06); }
.filter-chip.active {
    background: var(--win-accent);
    color: white;
    border-color: var(--win-accent);
    box-shadow: 0 4px 10px rgba(0, 120, 212, 0.2);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search-input');
    const sortSelect = document.getElementById('sort-select');
    const filterChips = document.querySelectorAll('.filter-chip');
    const list = document.getElementById('request-list');
    let cards = Array.from(document.querySelectorAll('.request-card'));

    function filterCards() {
        const searchTerm = searchInput.value.toLowerCase();
        const activeStatus = document.querySelector('.filter-chip.active').dataset.status;

        cards.forEach(card => {
            const matchesSearch = card.dataset.desc.includes(searchTerm);
            const matchesStatus = activeStatus === 'all' || card.dataset.status === activeStatus;
            card.style.display = (matchesSearch && matchesStatus) ? 'block' : 'none';
        });
    }

    function sortCards() {
        const val = sortSelect.value;
        cards.sort((a, b) => {
            if (val === 'date-desc') return b.dataset.timestamp - a.dataset.timestamp;
            if (val === 'date-asc') return a.dataset.timestamp - b.dataset.timestamp;
            if (val === 'priority') return b.dataset.priority - a.dataset.priority;
            return 0;
        });
        cards.forEach(card => list.appendChild(card));
    }

    searchInput.addEventListener('input', filterCards);
    sortSelect.addEventListener('change', sortCards);

    filterChips.forEach(chip => {
        chip.addEventListener('click', () => {
            filterChips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            filterCards();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
