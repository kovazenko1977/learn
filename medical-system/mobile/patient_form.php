<?php
require_once __DIR__ . '/../Core/Autoloader.php';
\Medical\Core\Autoloader::register();
\Medical\Core\Auth::init();
\Medical\Core\Auth::requireLogin();

if (!\Medical\Core\Auth::can('patients_edit')) {
    die("У вас недостаточно прав.");
}

$patientManager = new \Medical\Core\Managers\PatientManager();
$staffManager = new \Medical\Core\Managers\StaffManager();
$id = $_GET['id'] ?? '';
$patient = $id ? $patientManager->getById($id) : null;

$doctors = array_filter($staffManager->getAll(), function($s) {
    return $s['role'] === 'doctor' || $s['role'] === 'chief';
});

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (\Medical\Core\Auth::checkCsrf($_POST['csrf_token'] ?? '')) {
        $data = [
            'name' => $_POST['name'],
            'birth_date' => $_POST['birth_date'],
            'phone' => $_POST['phone'],
            'card_number' => $_POST['card_number'],
            'residence' => $_POST['residence'] ?? '',
            'treating_doctor' => $_POST['treating_doctor'] ?? '',
            'extra_info' => $_POST['extra_info'] ?? ''
        ];

        if ($id) {
            $patientManager->update($id, $data);
        } else {
            $id = $patientManager->add($data);
        }
        header("Location: patient_details.php?id=$id&saved=1");
        exit;
    } else {
        $error = 'Ошибка CSRF';
    }
}

include __DIR__ . '/includes/header.php';
?>

<div style="padding: 16px; display: flex; align-items: center; gap: 12px; background: #F3EDF7;">
    <a href="<?php echo $id ? "patient_details.php?id=$id" : "patients.php"; ?>" style="color: inherit;"><i data-lucide="arrow-left"></i></a>
    <h2 style="margin: 0; font-size: 20px; font-weight: 500;"><?php echo $id ? 'Редактировать' : 'Новый пациент'; ?></h2>
</div>

<div style="padding: 16px; padding-bottom: 100px;">
    <?php if ($error): ?>
        <div style="background: #F9DEDC; color: #410E0B; padding: 12px; border-radius: 8px; margin-bottom: 16px;"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo \Medical\Core\Auth::getCsrfToken(); ?>">

        <label style="display: block; font-size: 14px; margin-bottom: 4px; color: var(--md-secondary);">ФИО</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($patient['name'] ?? ''); ?>" class="md-input" required>

        <label style="display: block; font-size: 14px; margin-bottom: 4px; color: var(--md-secondary);">Дата рождения</label>
        <input type="date" name="birth_date" value="<?php echo htmlspecialchars($patient['birth_date'] ?? ''); ?>" class="md-input" required>

        <label style="display: block; font-size: 14px; margin-bottom: 4px; color: var(--md-secondary);">Телефон</label>
        <input type="tel" name="phone" value="<?php echo htmlspecialchars($patient['phone'] ?? ''); ?>" class="md-input">

        <label style="display: block; font-size: 14px; margin-bottom: 4px; color: var(--md-secondary);">№ Истории болезни</label>
        <input type="text" name="card_number" value="<?php echo htmlspecialchars($patient['card_number'] ?? ''); ?>" class="md-input">

        <label style="display: block; font-size: 14px; margin-bottom: 4px; color: var(--md-secondary);">Место жительства</label>
        <input type="text" name="residence" value="<?php echo htmlspecialchars($patient['residence'] ?? ''); ?>" class="md-input">

        <label style="display: block; font-size: 14px; margin-bottom: 4px; color: var(--md-secondary);">Лечащий врач</label>
        <select name="treating_doctor" class="md-input" style="appearance: none;">
            <option value="">Не назначен</option>
            <?php foreach ($doctors as $d): ?>
                <option value="<?php echo htmlspecialchars($d['name']); ?>" <?php echo (($patient['treating_doctor'] ?? '') === $d['name']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($d['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label style="display: block; font-size: 14px; margin-bottom: 4px; color: var(--md-secondary);">Дополнительно</label>
        <textarea name="extra_info" class="md-input" style="height: 100px; padding: 12px;"><?php echo htmlspecialchars($patient['extra_info'] ?? ''); ?></textarea>

        <button type="submit" class="md-btn md-btn-primary" style="width: 100%; height: 48px; border-radius: 24px;">Сохранить</button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
