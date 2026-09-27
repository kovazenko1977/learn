<?php

function renderCreatePageModal(): void {
?>
<div class="modal-backdrop" id="createPageModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Создание страницы памяти</h3>
            <button class="modal-close" onclick="App.closeModal('createPageModal')">&times;</button>
        </div>
        <form id="createPageForm" onsubmit="submitCreatePage(event)">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>ФИО усопшего *</label>
                <input type="text" name="full_name" class="form-control" required placeholder="Фамилия Имя Отчество">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 1rem;">
                <div class="form-group">
                    <label>Дата / Год рождения</label>
                    <input type="text" name="birth_date" class="form-control" placeholder="01.01.1950">
                </div>
                <div class="form-group">
                    <label>Дата / Год смерти</label>
                    <input type="text" name="death_date" class="form-control" placeholder="15.05.2023">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Фотографии (можно выбрать до 10 файлов)</label>
                <input type="file" name="photos[]" multiple class="form-control" accept="image/*">
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Эпитафия / Краткая цитата</label>
                <input type="text" name="epitaph" class="form-control" placeholder="Помним, любим, скорбим...">
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Биография / Памятная история</label>
                <textarea name="biography" class="form-control" rows="4" placeholder="Подробная информация о жизни и достижениях..."></textarea>
            </div>

            <h4 style="margin-top: 1.5rem; margin-bottom: 0.5rem; font-size: 1rem; color: var(--gold-light);">📍 Информация о месте захоронения</h4>

            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 10px; margin-bottom: 1rem;">
                <div class="form-group">
                    <label>Кладбище</label>
                    <input type="text" name="cemetery" class="form-control" placeholder="Название кладбища">
                </div>
                <div class="form-group">
                    <label>Участок / Сектор</label>
                    <input type="text" name="section" class="form-control" placeholder="Участок 12">
                </div>
                <div class="form-group">
                    <label>№ Могилы</label>
                    <input type="text" name="grave_num" class="form-control" placeholder="45">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 1rem;">
                <div class="form-group">
                    <label>Широта (GPS Latitude)</label>
                    <input type="number" step="any" name="latitude" class="form-control" placeholder="55.7558">
                </div>
                <div class="form-group">
                    <label>Долгота (GPS Longitude)</label>
                    <input type="number" step="any" name="longitude" class="form-control" placeholder="37.6173">
                </div>
            </div>

            <h4 style="margin-top: 1.5rem; margin-bottom: 0.5rem; font-size: 1rem; color: var(--gold-light);">👨‍👩‍👧 Контакты родственников</h4>
            <div id="relativesContainer">
                <div class="relative-input-row" style="display: grid; grid-template-columns: 1fr 1.5fr 1.5fr; gap: 8px; margin-bottom: 8px;">
                    <input type="text" class="form-control rel-type" placeholder="Степень родства (например, Сын)">
                    <input type="text" class="form-control rel-name" placeholder="ФИО родственника">
                    <input type="text" class="form-control rel-phone" placeholder="Телефон родственника">
                </div>
            </div>
            <button type="button" class="btn btn-outline btn-sm" onclick="addRelativeRow()" style="margin-bottom: 1.5rem;">+ Добавить еще родственника</button>

            <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 1rem;">
                <button type="button" class="btn btn-outline" onclick="App.closeModal('createPageModal')">Отмена</button>
                <button type="submit" class="btn btn-accent">Отправить на публикацию</button>
            </div>
        </form>
    </div>
</div>
<script>
function addRelativeRow() {
    const container = document.getElementById('relativesContainer');
    if (!container) return;
    const div = document.createElement('div');
    div.className = 'relative-input-row';
    div.style = 'display: grid; grid-template-columns: 1fr 1.5fr 1.5fr; gap: 8px; margin-bottom: 8px;';
    div.innerHTML = `
        <input type="text" class="form-control rel-type" placeholder="Степень родства">
        <input type="text" class="form-control rel-name" placeholder="ФИО родственника">
        <input type="text" class="form-control rel-phone" placeholder="Телефон родственника">
    `;
    container.appendChild(div);
}

async function submitCreatePage(e) {
    e.preventDefault();
    const form = document.getElementById('createPageForm');
    const formData = new FormData(form);

    const relatives = [];
    document.querySelectorAll('#relativesContainer .relative-input-row').forEach(row => {
        const type = row.querySelector('.rel-type').value.trim();
        const name = row.querySelector('.rel-name').value.trim();
        const phone = row.querySelector('.rel-phone').value.trim();
        if (name) {
            relatives.push({ relation_type: type, name: name, phone: phone, is_public: 1 });
        }
    });

    formData.append('relatives', JSON.stringify(relatives));

    const res = await App.fetch('create_page', {}, {
        method: 'POST',
        body: formData
    });

    if (res.success) {
        alert(res.message);
        App.closeModal('createPageModal');
        form.reset();
        if (res.status === 'approved') {
            window.location.href = `page.php?code=${res.code}`;
        } else if (typeof loadUserPages === 'function') {
            loadUserPages();
        } else {
            window.location.href = 'user.php';
        }
    } else {
        alert('Ошибка: ' + res.error);
    }
}
</script>
<?php
}
