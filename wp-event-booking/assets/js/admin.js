/**
 * WP Event Booking - Admin Panel Logic & Visual Form Builder
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

    function switchTab(targetTab) {
      tabBtns.forEach(b => b.classList.remove('active'));
      tabPanels.forEach(p => p.style.display = 'none');

      const activeBtn = adminContainer.querySelector(`.wpeb-tab-btn[data-tab="${targetTab}"]`);
      if (activeBtn) activeBtn.classList.add('active');

      const panel = adminContainer.querySelector(`#wpebTab_${targetTab}`);
      if (panel) panel.style.display = 'block';
    }

    tabBtns.forEach(btn => {
      btn.addEventListener('click', () => switchTab(btn.dataset.tab));
    });

    // --- VISUAL FORM BUILDER STATE & ENGINE ---
    let currentFields = Array.isArray(window.wpebInitialFormData) ? window.wpebInitialFormData : [];
    const canvasContainer = adminContainer.querySelector('#wpebCanvasFields');
    const formTitleInput = adminContainer.querySelector('#wpebFormTitleInput');
    const formIdInput = adminContainer.querySelector('#wpebActiveFormId');
    const saveBuilderBtn = adminContainer.querySelector('#wpebSaveBuilderBtn');
    const clearCanvasBtn = adminContainer.querySelector('#wpebClearCanvasBtn');
    const createNewFormBtn = adminContainer.querySelector('#wpebCreateNewFormBtn');

    function renderCanvas() {
      if (!canvasContainer) return;
      canvasContainer.innerHTML = '';

      if (!currentFields.length) {
        canvasContainer.innerHTML = '<div style="text-align:center; padding:40px 20px; color:#94a3b8;">Поля еще не добавлены. Кликните по компоненту в палитре слева, чтобы добавить его в форму.</div>';
        return;
      }

      currentFields.forEach((field, index) => {
        const row = document.createElement('div');
        row.className = 'wpeb-canvas-field-row';
        row.dataset.index = index;

        const typeLabels = {
          text: 'Текст',
          number: 'Число',
          select: 'Выпадающий список',
          radio: 'Радио-кнопки',
          checkbox: 'Чекбоксы',
          textarea: 'Многострочный',
          date: 'Дата',
          switch: 'Переключатель',
          consent: 'Согласие'
        };

        row.innerHTML = `
          <span class="wpeb-canvas-drag-handle">☰</span>
          <div class="wpeb-field-info">
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <strong class="wpeb-field-label-text">${field.field_label || 'Без подписи'}</strong>
              <span class="wpeb-field-badge">${typeLabels[field.field_type] || field.field_type}</span>
            </div>
            <div class="wpeb-field-badges">
              <span class="wpeb-field-badge">key: ${field.field_key}</span>
              ${field.is_required ? '<span class="wpeb-field-badge req">Обязательное</span>' : ''}
              <span class="wpeb-field-badge">Ширина: ${field.width || 100}%</span>
            </div>
          </div>
          <div class="wpeb-field-actions">
            <button type="button" class="button button-small wpeb-edit-field-btn">⚙ Ред.</button>
            <button type="button" class="button button-small wpeb-up-field-btn" ${index === 0 ? 'disabled' : ''}>↑</button>
            <button type="button" class="button button-small wpeb-down-field-btn" ${index === currentFields.length - 1 ? 'disabled' : ''}>↓</button>
            <button type="button" class="button button-small" style="color:#ef4444;" onclick="window.wpebRemoveField(${index})">✕</button>
          </div>
        `;

        // Field action events
        row.querySelector('.wpeb-edit-field-btn').addEventListener('click', () => editFieldSettings(index));
        const upBtn = row.querySelector('.wpeb-up-field-btn');
        if (upBtn) {
          upBtn.addEventListener('click', () => {
            if (index > 0) {
              const temp = currentFields[index];
              currentFields[index] = currentFields[index - 1];
              currentFields[index - 1] = temp;
              renderCanvas();
            }
          });
        }
        const downBtn = row.querySelector('.wpeb-down-field-btn');
        if (downBtn) {
          downBtn.addEventListener('click', () => {
            if (index < currentFields.length - 1) {
              const temp = currentFields[index];
              currentFields[index] = currentFields[index + 1];
              currentFields[index + 1] = temp;
              renderCanvas();
            }
          });
        }

        canvasContainer.appendChild(row);
      });
    }

    window.wpebRemoveField = function(idx) {
      currentFields.splice(idx, 1);
      renderCanvas();
    };

    function addFieldFromPalette(type) {
      const defaultLabels = {
        text: 'Новая текстовая строка',
        number: 'Числовое значение',
        select: 'Выберите вариант',
        radio: 'Выберите один вариант',
        checkbox: 'Выберите несколько вариантов',
        textarea: 'Подробный комментарий',
        date: 'Укажите дату',
        switch: 'Дополнительная опция',
        consent: 'Согласие на обработку данных'
      };

      const key = 'field_' + Math.random().toString(36).substring(2, 8);
      const newField = {
        field_key: key,
        field_label: defaultLabels[type] || 'Новое поле',
        field_type: type,
        placeholder: '',
        field_options: (['select', 'radio', 'checkbox'].includes(type)) ? "Вариант 1\nВариант 2\nВариант 3" : '',
        is_required: (type === 'consent') ? 1 : 0,
        width: '100'
      };

      currentFields.push(newField);
      renderCanvas();
    }

    // Palette Buttons Click Handlers
    adminContainer.querySelectorAll('.wpeb-palette-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        addFieldFromPalette(btn.dataset.type);
      });
    });

    // Edit Field Details Dialog
    function editFieldSettings(idx) {
      const f = currentFields[idx];
      const newLabel = prompt('Подпись поля (Label):', f.field_label);
      if (newLabel === null) return;
      f.field_label = newLabel;

      const newKey = prompt('Системное имя (field_key):', f.field_key);
      if (newKey) f.field_key = newKey.trim().toLowerCase().replace(/[^a-z0-9_]/g, '_');

      f.placeholder = prompt('Подсказка в поле (Placeholder):', f.placeholder || '') || '';
      f.is_required = confirm('Сделать это поле обязательным для заполнения?') ? 1 : 0;

      if (['select', 'radio', 'checkbox'].includes(f.field_type)) {
        const opts = prompt('Опции выбора (через точку с запятой ;):', (f.field_options || '').replace(/\n/g, '; '));
        if (opts !== null) {
          f.field_options = opts.split(';').map(s => s.trim()).filter(Boolean).join('\n');
        }
      }

      renderCanvas();
    }

    // Save Form to Backend
    if (saveBuilderBtn) {
      saveBuilderBtn.addEventListener('click', () => {
        saveBuilderBtn.disabled = true;
        saveBuilderBtn.textContent = 'Сохранение...';

        const formData = new FormData();
        formData.append('action', 'wpeb_save_form_builder');
        formData.append('form_id', formIdInput ? formIdInput.value : 0);
        formData.append('title', formTitleInput ? formTitleInput.value : 'Форма записи');
        formData.append('fields_json', JSON.stringify(currentFields));

        fetch(ajaxUrl, {
          method: 'POST',
          body: formData
        })
          .then(res => res.json())
          .then(res => {
            saveBuilderBtn.disabled = false;
            saveBuilderBtn.textContent = '💾 Сохранить форму';
            if (res.success) {
              alert('Форма и поля успешно сохранены!');
              window.location.reload();
            } else {
              alert('Ошибка: ' + (res.data ? res.data.error : 'Неизвестная ошибка'));
            }
          })
          .catch(() => {
            saveBuilderBtn.disabled = false;
            saveBuilderBtn.textContent = '💾 Сохранить форму';
            alert('Сетевая ошибка при сохранении');
          });
      });
    }

    if (clearCanvasBtn) {
      clearCanvasBtn.addEventListener('click', () => {
        if (confirm('Очистить все поля в конструкторе?')) {
          currentFields = [];
          renderCanvas();
        }
      });
    }

    // Create New Form Button
    if (createNewFormBtn) {
      createNewFormBtn.addEventListener('click', () => {
        const title = prompt('Введите название новой формы:', 'Онлайн-запись на событие');
        if (!title) return;

        const formData = new FormData();
        formData.append('action', 'wpeb_create_form');
        formData.append('title', title);

        fetch(ajaxUrl, { method: 'POST', body: formData })
          .then(res => res.json())
          .then(res => {
            if (res.success) {
              alert('Форма успешно создана!');
              window.location.href = `?page=admin&form_id=${res.data.form_id}`;
            } else {
              alert('Ошибка: ' + (res.data ? res.data.error : 'Не удалось создать форму'));
            }
          });
      });
    }

    // Global Export Handlers for Forms Table
    window.wpebEditForm = function(formId) {
      const forms = window.wpebAllForms || [];
      const target = forms.find(f => parseInt(f.id, 10) === parseInt(formId, 10));
      if (target) {
        if (formIdInput) formIdInput.value = target.id;
        if (formTitleInput) formTitleInput.value = target.title;
        try {
          currentFields = typeof target.fields_json === 'string' ? JSON.parse(target.fields_json) : (target.fields_json || []);
        } catch(e) {
          currentFields = [];
        }
        renderCanvas();
        switchTab('builder');
      }
    };

    window.wpebDuplicateForm = function(formId) {
      if (!confirm('Создать копию этой формы с уникальным шорткодом?')) return;
      const formData = new FormData();
      formData.append('action', 'wpeb_duplicate_form');
      formData.append('form_id', formId);

      fetch(ajaxUrl, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            alert('Форма успешно сдублирована!');
            window.location.reload();
          } else {
            alert('Ошибка дублирования: ' + (res.data ? res.data.error : ''));
          }
        });
    };

    window.wpebDeleteForm = function(formId) {
      if (!confirm('Вы уверены, что хотите удалить эту форму?')) return;
      const formData = new FormData();
      formData.append('action', 'wpeb_delete_form');
      formData.append('form_id', formId);

      fetch(ajaxUrl, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            alert('Форма удалена');
            window.location.reload();
          } else {
            alert('Ошибка удаления: ' + (res.data ? res.data.error : ''));
          }
        });
    };

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

        fetch(ajaxUrl, { method: 'POST', body: formData })
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

    // Initial render of Visual Canvas
    renderCanvas();
  }

  document.addEventListener('DOMContentLoaded', initAdmin);
})();
