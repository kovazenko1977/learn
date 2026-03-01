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

$settingsStore = new JsonStore('data/settings.json');
$settings = $settingsStore->read();
$slaConfig = $settings['sla'] ?? [];

// Deep linking filters
$fStatus = $_GET['status'] ?? null;
$fPerformerId = isset($_GET['performer_id']) ? (int)$_GET['performer_id'] : null;
$fServiceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : null;
$fStart = $_GET['start_date'] ?? null;
$fEnd = $_GET['end_date'] ?? null;
$fOverdue = $_GET['overdue'] ?? null;

$currentUserData = null;
if ($userRole === 'service_lead' || $userRole === 'performer') {
    $userStore = new JsonStore('data/users.json');
    $currentUserData = (new \Hop\Core\UserManager($userStore))->getById($userId);
}

$roleFilteredRequests = [];
foreach ($requests as $req) {
    // Basic role access control
    if ($userRole === 'admin' || $userRole === 'manager') {
        $roleFilteredRequests[] = $req;
    } elseif ($userRole === 'initiator') {
        if ($req['initiator_id'] === $userId) $roleFilteredRequests[] = $req;
    } elseif ($userRole === 'performer') {
        if (($req['status'] === 'new' && $currentUserData && $req['service_id'] === $currentUserData['service_id']) || (isset($req['performer_id']) && $req['performer_id'] === $userId)) $roleFilteredRequests[] = $req;
    } elseif ($userRole === 'service_lead') {
        if ($currentUserData && $req['service_id'] === $currentUserData['service_id']) $roleFilteredRequests[] = $req;
    } elseif ($userRole === 'controller') {
        if ($req['status'] === 'checking' || $req['status'] === 'completed') $roleFilteredRequests[] = $req;
    }
}

$filteredRequests = $roleFilteredRequests;

// Apply deep filters
if ($fStatus || $fPerformerId || $fServiceId || $fStart || $fEnd || $fOverdue) {
    $filteredRequests = array_filter($roleFilteredRequests, function($req) use ($fStatus, $fPerformerId, $fServiceId, $fStart, $fEnd, $fOverdue, $slaConfig) {
        if ($fStatus && $req['status'] !== $fStatus) return false;
        if ($fPerformerId && ($req['performer_id'] ?? 0) !== $fPerformerId) return false;
        if ($fServiceId && ($req['service_id'] ?? 0) !== $fServiceId) return false;

        $createdAt = strtotime($req['created_at']);
        if ($fStart && $createdAt < strtotime($fStart . ' 00:00:00')) return false;
        if ($fEnd && $createdAt > strtotime($fEnd . ' 23:59:59')) return false;

        if ($fOverdue) {
            if (in_array($req['status'], ['completed', 'closed'])) return false;
            $hoursLimit = $slaConfig[$req['priority']] ?? 24;
            if (time() <= ($createdAt + ($hoursLimit * 3600))) return false;
        }

        return true;
    });
}

usort($filteredRequests, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

$servicesStore = new JsonStore('data/services.json');
$services = [];
foreach ($servicesStore->read() as $s) $services[$s['id']] = $s['name'];

$userStoreForList = new JsonStore('data/users.json');
$usersList = [];
foreach ($userStoreForList->read() as $u) {
    $usersList[$u['id']] = [
        'name' => $u['name'],
        'phone' => $u['phone'] ?? ''
    ];
}

$statusNames = [
    'new' => 'Новая', 'assigned' => 'Назначена', 'working' => 'В работе',
    'checking' => 'Проверка', 'returned' => 'Доработка', 'completed' => 'Выполнена', 'closed' => 'Закрыта'
];

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="animation: slideDown 0.5s ease-out;">
        <div class="header-action-row">
            <div class="header-title-block">
                <h1>Главная панель</h1>
                <p style="color:var(--win-text-secondary);"><?php echo $_SESSION['user_name']; ?>, добро пожаловать в ХОП</p>
            </div>
            <div class="header-buttons-block">
                <a href="export.php?<?php echo http_build_query($_GET); ?>" class="btn-secondary" style="text-decoration:none; display:flex; align-items:center; gap:8px; padding: 12px 24px;">
                    <i data-lucide="download"></i> Экспорт
                </a>
                <button onclick="window.print()" class="btn-secondary" style="display:flex; align-items:center; gap:8px; padding: 12px 24px;">
                    <i data-lucide="printer"></i> Печать
                </button>
                <a href="create.php" class="btn-primary" style="text-decoration:none; display:flex; align-items:center; gap:8px; padding: 12px 24px;">
                    <i data-lucide="plus"></i> Новая заявка
                </a>
            </div>
        </div>

        <div class="stats-grid">
            <?php
                $newCount = 0; $workCount = 0; $overdueCount = 0;
                $totalBase = count($roleFilteredRequests);
                foreach($roleFilteredRequests as $r) {
                    if ($r['status'] === 'new') $newCount++;
                    if ($r['status'] === 'working') $workCount++;
                    $hoursLimit = $slaConfig[$r['priority']] ?? 24;
                    if (!in_array($r['status'], ['completed', 'closed']) && time() > (strtotime($r['created_at']) + ($hoursLimit * 3600))) {
                        $overdueCount++;
                    }
                }
            ?>
            <a href="index.php?status=new" class="stat-card stat-new-card <?php echo ($newCount > 0) ? 'pulse-new' : ''; ?>">
                <div class="stat-value"><?php echo $newCount; ?></div>
                <div class="stat-label">Ожидают</div>
            </a>
            <a href="index.php?status=working" class="stat-card stat-working-card">
                <div class="stat-value"><?php echo $workCount; ?></div>
                <div class="stat-label">В работе</div>
            </a>
            <a href="index.php?overdue=1" class="stat-card stat-overdue-card">
                <div class="stat-value"><?php echo $overdueCount; ?></div>
                <div class="stat-label">Просрочено</div>
            </a>
            <a href="index.php" class="stat-card stat-total-card">
                <div class="stat-value"><?php echo $totalBase; ?></div>
                <div class="stat-label">Всего</div>
            </a>
        </div>
    </div>

    <div class="card" style="padding:20px; margin-bottom:24px; animation: slideUp 0.6s ease-out;">
        <form method="GET" style="display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--win-border);">
            <div class="form-group" style="margin:0; flex: 1; min-width: 150px;">
                <label style="font-size: 11px; font-weight:700;">Дата С</label>
                <input type="date" name="start_date" value="<?php echo $fStart; ?>" style="height: 40px;">
            </div>
            <div class="form-group" style="margin:0; flex: 1; min-width: 150px;">
                <label style="font-size: 11px; font-weight:700;">Дата По</label>
                <input type="date" name="end_date" value="<?php echo $fEnd; ?>" style="height: 40px;">
            </div>
            <?php if ($fStatus): ?>
                <input type="hidden" name="status" value="<?php echo $fStatus; ?>">
            <?php endif; ?>
            <?php if ($fPerformerId): ?>
                <input type="hidden" name="performer_id" value="<?php echo $fPerformerId; ?>">
            <?php endif; ?>
            <?php if (isset($_GET['service_id'])): ?>
                <input type="hidden" name="service_id" value="<?php echo (int)$_GET['service_id']; ?>">
            <?php endif; ?>

            <button type="submit" class="btn-primary" style="height: 40px; padding: 0 20px;">
                Применить
            </button>
            <?php if ($fStart || $fEnd): ?>
                <a href="index.php?<?php
                    $params = $_GET;
                    unset($params['start_date'], $params['end_date']);
                    echo http_build_query($params);
                ?>" class="btn-secondary" style="height: 40px; text-decoration: none; display: flex; align-items: center; justify-content: center; padding: 0 16px;">
                    <i data-lucide="x" style="width:16px; height:16px;"></i>
                </a>
            <?php endif; ?>
        </form>

        <div style="display:flex; gap:12px; margin-bottom:16px; flex-wrap: wrap; align-items: center;">
            <div style="flex:1; min-width: 200px; position:relative;">
                <i data-lucide="search" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); width:18px; height:18px; color:var(--win-text-secondary);"></i>
                <input type="text" id="search-input" placeholder="Поиск..." style="padding-left:48px; height:48px; border-radius:12px; font-size:15px;">
            </div>
            <div style="width: 180px;">
                <select id="service-quick-filter" style="height:48px; border-radius:12px;">
                    <option value="all">Все службы</option>
                    <?php foreach ($services as $sid => $sname): ?>
                        <option value="<?php echo $sid; ?>"><?php echo htmlspecialchars($sname); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="width: 180px;">
                <select id="sort-select" style="height:48px; border-radius:12px;">
                    <option value="date-desc">Сначала новые</option>
                    <option value="date-asc">Сначала старые</option>
                    <option value="priority">По приоритету</option>
                </select>
            </div>
            <button id="compact-view-toggle" class="btn-secondary desktop-only" style="height:48px; width:48px; padding:0; display:flex; align-items:center; justify-content:center; border-radius:12px;" title="Компактный вид">
                <i data-lucide="list"></i>
            </button>
        </div>
        <div class="filter-chips" style="display:flex; gap:8px; overflow-x:auto; padding-bottom:8px; scrollbar-width: none;">
            <button class="filter-chip <?php echo (!$fStatus && !$fOverdue) ? 'active' : ''; ?>" data-status="all">Все</button>
            <?php foreach ($statusNames as $code => $name): ?>
                <button class="filter-chip <?php echo $fStatus === $code ? 'active' : ''; ?>" data-status="<?php echo $code; ?>"><?php echo $name; ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="list-container" id="request-list" style="display: grid; gap: 12px; animation: slideUp 0.7s ease-out;">
        <div class="mobile-only" style="background: rgba(0,120,212,0.05); padding: 10px; border-radius: 8px; font-size: 11px; color: var(--win-accent); margin-bottom: 8px; text-align: center; border: 1px dashed var(--win-accent);">
            <i data-lucide="info" style="width:12px; height:12px; vertical-align:middle;"></i> Смахните влево для быстрых действий или вправо для чата
        </div>
        <?php if (empty($filteredRequests)): ?>
            <div class="card mica" style="text-align:center; color:var(--win-text-secondary); padding: 60px 20px;">
                <div style="width:64px; height:64px; background:rgba(0,0,0,0.03); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                    <i data-lucide="inbox" style="width:32px; height:32px; opacity: 0.5;"></i>
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
                   data-service-id="<?php echo $req['service_id']; ?>"
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
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="display:flex; align-items:center; gap:16px; font-size:12px; color:var(--win-text-secondary);">
                            <span style="display:flex; align-items:center; gap:4px;">
                                <i data-lucide="map-pin" style="width:14px; height:14px;"></i>
                                <?php
                                    if (is_array($req['location'])) {
                                        echo "Корп. {$req['location']['building']}, каб. {$req['location']['room']}";
                                    } else {
                                        echo htmlspecialchars($req['location']);
                                    }
                                ?>
                            </span>
                            <?php if (!empty($req['photo'])): ?>
                                <span style="display:flex; align-items:center; gap:4px;">
                                    <i data-lucide="image" style="width:14px; height:14px;"></i>
                                    Фото
                                </span>
                            <?php endif; ?>
                        </div>

                        <div style="display:flex; align-items:center; gap:8px;">
                            <?php
                                $partnerId = ($userRole === 'performer') ? $req['initiator_id'] : ($req['performer_id'] ?? null);
                                $partnerPhone = ($partnerId && isset($usersList[$partnerId])) ? $usersList[$partnerId]['phone'] : '';
                            ?>
                            <?php if ($partnerPhone): ?>
                                <button type="button" class="btn-icon partner-call-btn"
                                        onclick="event.preventDefault(); window.location.href='tel:<?php echo $partnerPhone; ?>'"
                                        style="background:rgba(0,120,212,0.05); color:var(--win-accent); border:none; border-radius:50%; width:32px; height:32px; display:flex; align-items:center; justify-content:center;">
                                    <i data-lucide="phone" style="width:14px; height:14px;"></i>
                                </button>
                            <?php endif; ?>
                        </div>
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
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
.filter-chip.active {
    background: var(--win-accent-gradient);
    color: white;
    border-color: var(--win-accent);
    box-shadow: 0 4px 10px rgba(0, 120, 212, 0.2);
}
.colorful-stat-card {
    background: var(--bg-grad) !important;
    border: none !important;
    color: white !important;
}
.colorful-stat-card .stat-value { color: white !important; font-size: 36px; }
.colorful-stat-card .stat-label { color: rgba(255,255,255,0.9) !important; font-weight: 800; }

.compact-view { grid-template-columns: 1fr 1fr; }
.compact-view .request-card { padding: 12px; margin-bottom: 0; }
.compact-view .request-card > div:nth-child(2) { font-size: 14px; margin-bottom: 4px; }
.compact-view .request-card > div:nth-child(3) { display: none; }
@media (max-width: 1100px) { .compact-view { grid-template-columns: 1fr; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search-input');
    const sortSelect = document.getElementById('sort-select');
    const serviceQuickFilter = document.getElementById('service-quick-filter');
    const compactToggle = document.getElementById('compact-view-toggle');
    const filterChips = document.querySelectorAll('.filter-chip');
    const list = document.getElementById('request-list');
    let cards = Array.from(document.querySelectorAll('.request-card'));

    function filterCards() {
        const searchTerm = searchInput.value.toLowerCase();
        const activeStatus = document.querySelector('.filter-chip.active').dataset.status;
        const selectedSvc = serviceQuickFilter.value;

        cards.forEach(card => {
            const matchesSearch = card.dataset.desc.includes(searchTerm);
            const matchesStatus = activeStatus === 'all' || card.dataset.status === activeStatus;
            const matchesSvc = selectedSvc === 'all' || card.dataset.serviceId === selectedSvc;
            card.style.display = (matchesSearch && matchesStatus && matchesSvc) ? 'block' : 'none';
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
    serviceQuickFilter.addEventListener('change', filterCards);

    compactToggle?.addEventListener('click', () => {
        list.classList.toggle('compact-view');
        const icon = compactToggle.querySelector('i');
        if (list.classList.contains('compact-view')) {
            icon.setAttribute('data-lucide', 'grid');
        } else {
            icon.setAttribute('data-lucide', 'list');
        }
        lucide.createIcons();
    });

    filterChips.forEach(chip => {
        chip.addEventListener('click', () => {
            filterChips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            filterCards();
        });
    });

    // Swipe actions for mobile
    let touchstartX = 0;
    let touchendX = 0;
    const swipeThreshold = 100;

    cards.forEach(card => {
        card.addEventListener('touchstart', e => {
            touchstartX = e.changedTouches[0].screenX;
        }, {passive: true});

        card.addEventListener('touchend', e => {
            touchendX = e.changedTouches[0].screenX;
            handleSwipe(card);
        }, {passive: true});
    });

    function handleSwipe(card) {
        const diff = touchendX - touchstartX;
        const id = card.getAttribute('href').split('=')[1];
        const status = card.dataset.status;

        if (diff < -swipeThreshold) {
            // Swipe Left -> Quick action (e.g., In Work)
            if (status === 'assigned' || status === 'new') {
                if (confirm('Взять заявку #' + id + ' в работу?')) {
                    window.location.href = 'view.php?id=' + id + '&action=work';
                }
            } else if (status === 'working') {
                if (confirm('Завершить работу по заявке #' + id + ' и отправить на проверку?')) {
                    window.location.href = 'view.php?id=' + id + '&action=check';
                }
            }
        } else if (diff > swipeThreshold) {
            // Swipe Right -> Open comments
            window.location.href = 'view.php?id=' + id + '#chat';
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
