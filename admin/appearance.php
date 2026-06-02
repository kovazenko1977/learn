<?php
require_once "auth.php";
require_once "../core/autoload.php";
use Sanatorium\Core\Database\JsonStore;

$pageTitle = 'Внешний вид';
$successMessage = '';
$errorMessage = '';

$settingsPath = __DIR__ . '/../data/settings.json';
$settings = json_decode(@file_get_contents($settingsPath), true) ?: [];
$appearance = $settings['appearance'] ?? [];

// Default values
$defaults = [
    // Colors
    'primary_color' => '#0078d4',
    'secondary_color' => '#2b88d8',
    'bg_color' => '#f3f2f1',
    'sidebar_bg' => '#ffffff',
    'sidebar_text' => '#323130',
    'sidebar_active_bg' => '#f3f2f1',
    'card_bg' => '#ffffff',
    'text_main' => '#323130',
    'text_muted' => '#605e5c',
    'border_color' => '#edebe9',
    'accent_color' => '#d83b01',

    // Typography
    'font_family' => "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
    'font_size_base' => '14px',
    'h1_size' => '24px',
    'h2_size' => '20px',
    'h3_size' => '16px',

    // Layout
    'border_radius' => '8px',
    'card_padding' => '20px',
    'sidebar_width' => '260px',
    'header_height' => '60px',
    'container_padding' => '25px',

    // Chessboard (Visual Grid)
    'cb_cell_width' => '40px',
    'cb_cell_height' => '45px',
    'cb_header_bg' => '#faf9f8',
    'cb_status_booked' => '#fee2e2',
    'cb_status_confirmed' => '#d1e7dd',
    'cb_status_cancelled' => '#f8d7da',
    'cb_status_partial' => '#fff3cd',
    'cb_today_bg' => 'rgba(0, 120, 212, 0.05)',

    // Effects
    'shadow_sm' => '0 2px 4px rgba(0,0,0,0.05)',
    'shadow_md' => '0 4px 12px rgba(0,0,0,0.1)',
    'transition_speed' => '0.2s',
    'glass_effect' => 'blur(10px)',
];

// Merge with current
$appearance = array_merge($defaults, $appearance);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_appearance') {
    foreach ($defaults as $key => $val) {
        if (isset($_POST[$key])) {
            $appearance[$key] = $_POST[$key];
        }
    }
    $settings['appearance'] = $appearance;
    file_put_contents($settingsPath, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $successMessage = "Настройки внешнего вида успешно сохранены!";
}

include 'includes/header.php';
?>

<?php if ($successMessage): ?>
    <div style="background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #badbcc;">
        <?php echo $successMessage; ?>
    </div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="action" value="update_appearance">

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px;">

        <!-- Colors -->
        <div class="mica-card">
            <h2>🎨 Основные цвета</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Основной цвет</label>
                    <input type="color" name="primary_color" value="<?php echo $appearance['primary_color']; ?>">
                </div>
                <div class="form-group">
                    <label>Вторичный цвет</label>
                    <input type="color" name="secondary_color" value="<?php echo $appearance['secondary_color']; ?>">
                </div>
                <div class="form-group">
                    <label>Фон страницы</label>
                    <input type="color" name="bg_color" value="<?php echo $appearance['bg_color']; ?>">
                </div>
                <div class="form-group">
                    <label>Фон карточек</label>
                    <input type="color" name="card_bg" value="<?php echo $appearance['card_bg']; ?>">
                </div>
                <div class="form-group">
                    <label>Цвет текста (осн)</label>
                    <input type="color" name="text_main" value="<?php echo $appearance['text_main']; ?>">
                </div>
                <div class="form-group">
                    <label>Цвет текста (втор)</label>
                    <input type="color" name="text_muted" value="<?php echo $appearance['text_muted']; ?>">
                </div>
                <div class="form-group">
                    <label>Цвет границ</label>
                    <input type="color" name="border_color" value="<?php echo $appearance['border_color']; ?>">
                </div>
                <div class="form-group">
                    <label>Акцентный (опасный)</label>
                    <input type="color" name="accent_color" value="<?php echo $appearance['accent_color']; ?>">
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="mica-card">
            <h2>📂 Боковая панель</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Фон сайдбара</label>
                    <input type="color" name="sidebar_bg" value="<?php echo $appearance['sidebar_bg']; ?>">
                </div>
                <div class="form-group">
                    <label>Текст сайдбара</label>
                    <input type="color" name="sidebar_text" value="<?php echo $appearance['sidebar_text']; ?>">
                </div>
                <div class="form-group">
                    <label>Активный пункт (фон)</label>
                    <input type="color" name="sidebar_active_bg" value="<?php echo $appearance['sidebar_active_bg']; ?>">
                </div>
                <div class="form-group">
                    <label>Ширина сайдбара</label>
                    <input type="text" name="sidebar_width" value="<?php echo $appearance['sidebar_width']; ?>">
                </div>
            </div>
        </div>

        <!-- Typography -->
        <div class="mica-card">
            <h2>✍️ Типографика</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Шрифт (Family)</label>
                    <input type="text" name="font_family" value="<?php echo htmlspecialchars($appearance['font_family']); ?>">
                </div>
                <div class="form-group">
                    <label>Размер шрифта (осн)</label>
                    <input type="text" name="font_size_base" value="<?php echo $appearance['font_size_base']; ?>">
                </div>
                <div class="form-group">
                    <label>Заголовок H1</label>
                    <input type="text" name="h1_size" value="<?php echo $appearance['h1_size']; ?>">
                </div>
                <div class="form-group">
                    <label>Заголовок H2</label>
                    <input type="text" name="h2_size" value="<?php echo $appearance['h2_size']; ?>">
                </div>
                <div class="form-group">
                    <label>Заголовок H3</label>
                    <input type="text" name="h3_size" value="<?php echo $appearance['h3_size']; ?>">
                </div>
            </div>
        </div>

        <!-- Spacing & Layout -->
        <div class="mica-card">
            <h2>📐 Разметка и отступы</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Радиус скругления</label>
                    <input type="text" name="border_radius" value="<?php echo $appearance['border_radius']; ?>">
                </div>
                <div class="form-group">
                    <label>Внутренний отступ карт</label>
                    <input type="text" name="card_padding" value="<?php echo $appearance['card_padding']; ?>">
                </div>
                <div class="form-group">
                    <label>Высота шапки</label>
                    <input type="text" name="header_height" value="<?php echo $appearance['header_height']; ?>">
                </div>
                <div class="form-group">
                    <label>Отступ контента</label>
                    <input type="text" name="container_padding" value="<?php echo $appearance['container_padding']; ?>">
                </div>
            </div>
        </div>

        <!-- Chessboard -->
        <div class="mica-card">
            <h2>🏁 Шахматка (Сетка)</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Ширина ячейки</label>
                    <input type="text" name="cb_cell_width" value="<?php echo $appearance['cb_cell_width']; ?>">
                </div>
                <div class="form-group">
                    <label>Высота ячейки</label>
                    <input type="text" name="cb_cell_height" value="<?php echo $appearance['cb_cell_height']; ?>">
                </div>
                <div class="form-group">
                    <label>Фон заголовков</label>
                    <input type="color" name="cb_header_bg" value="<?php echo $appearance['cb_header_bg']; ?>">
                </div>
                <div class="form-group">
                    <label>Цвет "Забронировано"</label>
                    <input type="color" name="cb_status_booked" value="<?php echo $appearance['cb_status_booked']; ?>">
                </div>
                <div class="form-group">
                    <label>Цвет "Проживает"</label>
                    <input type="color" name="cb_status_confirmed" value="<?php echo $appearance['cb_status_confirmed']; ?>">
                </div>
                <div class="form-group">
                    <label>Цвет "Частично"</label>
                    <input type="color" name="cb_status_partial" value="<?php echo $appearance['cb_status_partial']; ?>">
                </div>
                <div class="form-group">
                    <label>Цвет "Отменено"</label>
                    <input type="color" name="cb_status_cancelled" value="<?php echo $appearance['cb_status_cancelled']; ?>">
                </div>
                <div class="form-group">
                    <label>Подсветка "Сегодня"</label>
                    <input type="text" name="cb_today_bg" value="<?php echo $appearance['cb_today_bg']; ?>">
                </div>
            </div>
        </div>

        <!-- Effects -->
        <div class="mica-card">
            <h2>✨ Эффекты и анимация</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Малая тень</label>
                    <input type="text" name="shadow_sm" value="<?php echo $appearance['shadow_sm']; ?>">
                </div>
                <div class="form-group">
                    <label>Средняя тень</label>
                    <input type="text" name="shadow_md" value="<?php echo $appearance['shadow_md']; ?>">
                </div>
                <div class="form-group">
                    <label>Скорость анимации</label>
                    <input type="text" name="transition_speed" value="<?php echo $appearance['transition_speed']; ?>">
                </div>
                <div class="form-group">
                    <label>Стеклянный эффект</label>
                    <input type="text" name="glass_effect" value="<?php echo $appearance['glass_effect']; ?>">
                </div>
            </div>
        </div>

    </div>

    <div style="margin-top: 30px; position: sticky; bottom: 20px; z-index: 100;">
        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1.1rem; box-shadow: 0 10px 20px rgba(0,0,0,0.2);">
            💾 Сохранить и применить внешний вид
        </button>
    </div>
</form>

<style>
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}
.form-group label {
    display: block;
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 5px;
}
.form-group input[type="color"] {
    width: 100%;
    height: 40px;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 2px;
}
.form-group input[type="text"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.mica-card h2 {
    margin-top: 0;
    margin-bottom: 20px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
    font-size: 1.1rem;
}
</style>

<?php include 'includes/footer.php'; ?>
