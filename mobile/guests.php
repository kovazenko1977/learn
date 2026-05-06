<?php
require_once "../core/autoload.php";
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Guests\GuestManager;

$store = new JsonStore(__DIR__ . '/data');
$guestManager = new GuestManager($store);

$guests = $guestManager->getAll();
$bookings = $store->findAll('bookings');

$pageTitle = 'Гости';
include 'includes/header.php';
?>

<div class="m-card" style="padding: 10px;">
    <input type="text" id="m-guest-search" placeholder="Поиск гостей..." class="form-control" style="margin-bottom: 0; padding: 10px; font-size: 0.9rem;" onkeyup="filterGuests()">
</div>

<div id="m-guests-list" style="padding-bottom: 80px;">
    <?php foreach ($guests as $g):
        $name = $g['name'] ?? $g['full_name'] ?? 'N/A';
        ?>
        <div class="m-card guest-item" style="padding: 12px; margin-bottom: 12px;"
             data-search="<?php echo mb_strtolower($name . ' ' . ($g['phone'] ?? '')); ?>">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="avatar" style="width: 40px; height: 40px; background: #e2e8f0; color: #475569; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                    <?php echo mb_substr($name, 0, 1); ?>
                </div>
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($name); ?></div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;"><?php echo $g['phone'] ?? ''; ?></div>
                </div>
                <div>
                    <?php if ($guestManager->isCurrentlyStaying($g['id'], $bookings)): ?>
                        <span class="badge-m badge-m-success">В санатории</span>
                    <?php endif; ?>
                </div>
            </div>
            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9; display: flex; gap: 10px;">
                <a href="tel:<?php echo $g['phone'] ?? ''; ?>" class="btn-m" style="background: #f1f5f9; color: #1e293b; font-size: 0.7rem; padding: 6px; flex: 1; display: flex; align-items: center; gap: 4px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    Позвонить
                </a>
                <a href="../admin/guests.php?id=<?php echo $g['id']; ?>" class="btn-m" style="background: #f1f5f9; color: #1e293b; font-size: 0.7rem; padding: 6px; flex: 1; display: flex; align-items: center; gap: 4px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    Инфо
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
function filterGuests() {
    const q = document.getElementById('m-guest-search').value.toLowerCase();
    const items = document.querySelectorAll('.guest-item');
    items.forEach(item => {
        const text = item.getAttribute('data-search');
        item.style.display = text.includes(q) ? 'block' : 'none';
    });
}
</script>

<?php include 'includes/footer.php'; ?>
