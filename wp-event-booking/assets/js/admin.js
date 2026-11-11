/**
 * WP Event Booking - Admin Panel Logic
 * Tab navigation, PIN submission, form builder manipulation, slot generator
 */

(function() {
  'use strict';

  function initAdmin() {
    const adminContainer = document.querySelector('#wpebAdminApp');
    if (!adminContainer) return;

    const ajaxUrl = window.wpebAdminConfig ? window.wpebAdminConfig.ajax_url : '/preview.php?admin_ajax=1';

    // Tab Switching
    const tabBtns = adminContainer.querySelectorAll('.wpeb-tab-btn');
    const tabPanels = adminContainer.querySelectorAll('.wpeb-tab-panel');

    tabBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        const targetTab = btn.dataset.tab;
        tabBtns.forEach(b => b.classList.remove('active'));
        tabPanels.forEach(p => p.style.display = 'none');

        btn.classList.add('active');
        const panel = adminContainer.querySelector(`#wpebTab_${targetTab}`);
        if (panel) panel.style.display = 'block';
      });
    });

    // Form Builder - Add New Field
    const addFieldBtn = adminContainer.querySelector('#wpebAddFieldBtn');
    const builderList = adminContainer.querySelector('#wpebBuilderList');

    if (addFieldBtn && builderList) {
      addFieldBtn.addEventListener('click', () => {
        const fieldKey = prompt('Введите системное имя поля (латиницей, например company или telegram):');
        if (!fieldKey) return;

        const fieldLabel = prompt('Введите подпись поля для посетителя (например «Ваша компания»):', 'Новое поле');
        if (!fieldLabel) return;

        const row = document.createElement('div');
        row.className = 'wpeb-builder-item';
        row.dataset.key = fieldKey.trim().toLowerCase().replace(/[^a-z0-9_]/g, '_');

        row.innerHTML = `
          <span class="wpeb-drag-handle">☰</span>
          <div style="flex:1;">
            <strong>${fieldLabel}</strong>
            <span style="font-size:12px; color:#64748b; margin-left:8px;">[key: ${row.dataset.key}]</span>
            <div style="margin-top:6px; display:flex; gap:10px; align-items:center;">
              <select class="wpeb-input wpeb-builder-type" style="width:140px; padding:4px 8px;">
                <option value="text">Текст</option>
                <option value="number">Число</option>
                <option value="select">Выпадающий список</option>
                <option value="radio">Радио-кнопки</option>
                <option value="checkbox">Чекбоксы</option>
                <option value="textarea">Многострочный текст</option>
              </select>
              <input type="text" class="wpeb-input wpeb-builder-placeholder" placeholder="Плейсхолдер" style="flex:1; padding:4px 8px;" />
              <label style="font-size:13px; display:flex; align-items:center; gap:4px;">
                <input type="checkbox" class="wpeb-builder-required" /> Обязательное
              </label>
            </div>
            <div class="wpeb-builder-options-box" style="margin-top:6px; display:none;">
              <textarea class="wpeb-textarea wpeb-builder-options" placeholder="Опции выбора (каждая с новой строки)" style="font-size:12px; height:60px;"></textarea>
            </div>
          </div>
          <button type="button" class="button wpeb-del-field-btn" style="color:#ef4444;">Удалить</button>
        `;

        const typeSelect = row.querySelector('.wpeb-builder-type');
        const optionsBox = row.querySelector('.wpeb-builder-options-box');
        typeSelect.addEventListener('change', () => {
          if (['select', 'radio', 'checkbox'].includes(typeSelect.value)) {
            optionsBox.style.display = 'block';
          } else {
            optionsBox.style.display = 'none';
          }
        });

        row.querySelector('.wpeb-del-field-btn').addEventListener('click', () => {
          row.remove();
        });

        builderList.appendChild(row);
      });
    }

    // Save Form Builder
    const saveBuilderBtn = adminContainer.querySelector('#wpebSaveBuilderBtn');
    if (saveBuilderBtn && builderList) {
      saveBuilderBtn.addEventListener('click', () => {
        saveBuilderBtn.disabled = true;
        saveBuilderBtn.textContent = 'Сохранение...';

        const fields = [];
        const items = builderList.querySelectorAll('.wpeb-builder-item');
        items.forEach((it, idx) => {
          const typeSelect = it.querySelector('.wpeb-builder-type');
          const placeholderInput = it.querySelector('.wpeb-builder-placeholder');
          const requiredCheckbox = it.querySelector('.wpeb-builder-required');
          const optionsText = it.querySelector('.wpeb-builder-options');
          const labelEl = it.querySelector('strong');

          fields.push({
            field_key: it.dataset.key,
            field_label: labelEl ? labelEl.textContent : it.dataset.key,
            field_type: typeSelect ? typeSelect.value : 'text',
            field_options: optionsText ? optionsText.value : '',
            placeholder: placeholderInput ? placeholderInput.value : '',
            is_required: (requiredCheckbox && requiredCheckbox.checked) ? 1 : 0,
            sort_order: idx
          });
        });

        const formData = new FormData();
        formData.append('action', 'wpeb_save_form_builder');
        formData.append('event_id', adminContainer.querySelector('#wpebBuilderEventSelect') ? adminContainer.querySelector('#wpebBuilderEventSelect').value : 0);
        formData.append('fields_json', JSON.stringify(fields));

        fetch(ajaxUrl, {
          method: 'POST',
          body: formData
        })
          .then(res => res.json())
          .then(res => {
            saveBuilderBtn.disabled = false;
            saveBuilderBtn.textContent = 'Сохранить форму конструктора';
            if (res.success) {
              alert('Поля формы успешно сохранены!');
            } else {
              alert('Ошибка сохранения: ' + (res.data ? res.data.error : 'Неизвестная ошибка'));
            }
          })
          .catch(err => {
            saveBuilderBtn.disabled = false;
            saveBuilderBtn.textContent = 'Сохранить форму конструктора';
            alert('Сетевая ошибка при сохранении');
          });
      });
    }

    // Test Telegram notification
    const testTgBtn = adminContainer.querySelector('#wpebTestTgBtn');
    if (testTgBtn) {
      testTgBtn.addEventListener('click', () => {
        const token = adminContainer.querySelector('input[name="telegram_token"]').value;
        const chatId = adminContainer.querySelector('input[name="telegram_chat_id"]').value;

        if (!token || !chatId) {
          alert('Сначала введите токен бота и Chat ID!');
          return;
        }

        testTgBtn.disabled = true;
        testTgBtn.textContent = 'Отправка...';

        const formData = new FormData();
        formData.append('action', 'wpeb_test_telegram');
        formData.append('token', token);
        formData.append('chat_id', chatId);

        fetch(ajaxUrl, {
          method: 'POST',
          body: formData
        })
          .then(res => res.json())
          .then(res => {
            testTgBtn.disabled = false;
            testTgBtn.textContent = '⚡ Протестировать Telegram';
            if (res.success) {
              alert('Тестовое сообщение успешно отправлено в Telegram!');
            } else {
              alert('Ошибка Telegram: ' + (res.data ? res.data.error : 'Проверьте токен и ID чата'));
            }
          })
          .catch(() => {
            testTgBtn.disabled = false;
            testTgBtn.textContent = '⚡ Протестировать Telegram';
            alert('Ошибка сети при запросе к Telegram');
          });
      });
    }

    // Color Live Preview
    const primaryColorInput = adminContainer.querySelector('#wpebPrimaryColorInput');
    if (primaryColorInput) {
      primaryColorInput.addEventListener('input', (e) => {
        const color = e.target.value;
        const previewBtn = adminContainer.querySelector('#wpebColorPreviewBtn');
        if (previewBtn) previewBtn.style.backgroundColor = color;
      });
    }
  }

  document.addEventListener('DOMContentLoaded', initAdmin);
})();
