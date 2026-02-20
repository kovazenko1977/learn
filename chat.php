<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\ChatManager;
use Hop\Core\UserManager;

$chatStore = new JsonStore('data/chat.json');
$chatManager = new ChatManager($chatStore);

$userStore = new JsonStore('data/users.json');
$userManager = new UserManager($userStore);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'post') {
        $text = trim($_POST['text']);
        if ($text) {
            $chatManager->postMessage($_SESSION['user_id'], $text);
        }
        header('Location: chat.php');
        exit;
    } elseif ($action === 'delete' && $_SESSION['user_role'] === 'admin') {
        $chatManager->deleteMessage((int)$_POST['id']);
        header('Location: chat.php');
        exit;
    }
}

$messages = $chatManager->getMessages();
$users = [];
foreach ($userManager->getAll() as $u) $users[$u['id']] = $u;

include 'includes/header.php';
?>

<div class="container" style="max-width: 800px;">
    <div class="page-header" style="animation: slideDown 0.5s ease-out;">
        <h1>Общий чат</h1>
        <p style="color:var(--win-text-secondary);">Объявления и сообщения для всех сотрудников</p>
    </div>

    <section class="card mica" style="animation: slideUp 0.6s ease-out; padding: 20px; margin-bottom: 32px;">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="post">
            <div style="display: flex; gap: 12px; align-items: flex-end;">
                <div style="flex: 1;">
                    <textarea name="text" rows="2" required placeholder="Напишите сообщение или объявление..." style="resize: none;"></textarea>
                </div>
                <button type="submit" class="btn-primary" style="height: 48px; padding: 0 24px;">
                    <i data-lucide="send"></i>
                </button>
            </div>
        </form>
    </section>

    <div class="chat-container" style="display: flex; flex-direction: column; gap: 16px; animation: slideUp 0.7s ease-out;">
        <?php if (empty($messages)): ?>
            <div style="text-align: center; color: var(--win-text-secondary); padding: 40px;">
                <i data-lucide="message-square" style="width: 48px; height: 48px; opacity: 0.3; margin-bottom: 12px;"></i>
                <p>Сообщений пока нет. Будьте первым!</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $index => $msg):
                $u = $users[$msg['user_id']] ?? ['name' => 'Удален', 'role' => 'none'];
                $isOwn = $msg['user_id'] == $_SESSION['user_id'];
            ?>
                <div class="card mica" style="padding: 16px; margin-bottom: 0; animation-delay: <?php echo $index * 0.05; ?>s; <?php echo $isOwn ? 'border-left: 4px solid var(--win-accent);' : ''; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 32px; height: 32px; background: <?php echo $isOwn ? 'var(--win-accent)' : 'rgba(0,0,0,0.05)'; ?>; color: <?php echo $isOwn ? 'white' : 'inherit'; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">
                                <?php echo mb_substr($u['name'], 0, 1); ?>
                            </div>
                            <div>
                                <div style="font-size: 14px; font-weight: 700;"><?php echo htmlspecialchars($u['name']); ?></div>
                                <div style="font-size: 11px; color: var(--win-text-secondary);"><?php echo date('d.m.Y H:i', strtotime($msg['timestamp'])); ?></div>
                            </div>
                        </div>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <form method="POST" onsubmit="return confirm('Удалить сообщение?')">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $msg['id']; ?>">
                                <button type="submit" class="btn-icon" style="background:none; border:none; color:var(--priority-critical); cursor:pointer; padding:4px;">
                                    <i data-lucide="trash-2" style="width: 16px;"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 15px; line-height: 1.5; color: var(--win-text); white-space: pre-wrap;"><?php echo htmlspecialchars($msg['text']); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
