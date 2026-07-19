const { createApp, ref, computed, onMounted } = Vue;

createApp({
    setup() {
        const activeTab = ref('settings');
        const dayNames = { "1": "Пн", "2": "Вт", "3": "Ср", "4": "Чт", "5": "Пт", "6": "Сб", "0": "Вс" };

        const settings = ref({
            widget_title: 'Помощник БЕРЕЗИНА',
            working_hours: { timezone: 'Europe/Minsk', out_of_hours_message: 'Извините, сейчас мы не работаем. Пожалуйста, оставьте ваш телефон, и мы перезвоним вам в рабочее время!' },
            contacts: { phone: '+375 (177) 74-21-44', whatsapp: '+375333533971', telegram: 'berezina_bot' },
            fallback: { threshold: 40, message: 'Извините, я не совсем понял ваш вопрос. Вы можете заказать обратный звонок или связаться с нами напрямую.', button_text: 'Заказать обратный звонок' },
            notifications: { email: { enabled: false, address: '' }, telegram: { enabled: false, token: '', chat_id: '' } },
            schedule: {
                "1": { enabled: true, start: "08:00", end: "17:00" },
                "2": { enabled: true, start: "08:00", end: "17:00" },
                "3": { enabled: true, start: "08:00", end: "17:00" },
                "4": { enabled: true, start: "08:00", end: "17:00" },
                "5": { enabled: true, start: "08:00", end: "16:00" },
                "6": { enabled: false, start: "09:00", end: "15:00" },
                "0": { enabled: false, start: "09:00", end: "15:00" }
            },
            quick_start_menu: [
                { text: '🌲 Цены на путевки', message: 'Цены на путевки' },
                { text: '🩺 Услуги и процедуры', message: 'Услуги и процедуры' }
            ]
        });

        const knowledge = ref([]);
        const history = ref([]);
        const uploads = ref([]);
        const banner = ref({ text: '', type: 'success' });
        const bulkText = ref('');

        const isWP = typeof window.berezinaAdminConfig !== 'undefined';
        const config = isWP ? window.berezinaAdminConfig : { ajax_url: 'admin.php', nonce: '' };

        const showBanner = (text, type = 'success') => {
            banner.value.text = text;
            banner.value.type = type;
            setTimeout(() => { banner.value.text = ''; }, 4000);
        };

        const fetchData = async () => {
            try {
                if (isWP) {
                    const formData = new FormData();
                    formData.append('action', 'berezina_chatbot_get_data');
                    formData.append('nonce', config.nonce);

                    const res = await fetch(config.ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success && data.data) {
                        if (data.data.settings && Object.keys(data.data.settings).length) {
                            settings.value = data.data.settings;
                        }
                        knowledge.value = data.data.knowledge || [];
                        history.value = data.data.history || [];
                        uploads.value = data.data.uploads || [];
                    }
                } else {
                    const res = await fetch('admin.php?action=get_data');
                    const data = await res.json();
                    if (data.settings) settings.value = data.settings;
                    knowledge.value = data.knowledge || [];

                    const hRes = await fetch('admin.php?action=get_history');
                    history.value = await hRes.json();

                    const uRes = await fetch('api/uploads.php?action=list');
                    uploads.value = await uRes.json();
                }
            } catch (e) {
                console.error(e);
            }
        };

        const saveData = async () => {
            try {
                if (isWP) {
                    const formData = new FormData();
                    formData.append('action', 'berezina_chatbot_save_settings');
                    formData.append('nonce', config.nonce);
                    formData.append('settings', JSON.stringify(settings.value));
                    formData.append('knowledge', JSON.stringify(knowledge.value));

                    const res = await fetch(config.ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        showBanner('Все настройки и база знаний сохранены!');
                    } else {
                        showBanner('Ошибка при сохранении: ' + (data.data.error || 'unknown'), 'error');
                    }
                } else {
                    await fetch('admin.php?action=save_data', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ settings: settings.value, knowledge: knowledge.value })
                    });
                    showBanner('Все настройки сохранены!');
                }
            } catch (e) {
                showBanner('Ошибка соединения с сервером', 'error');
            }
        };

        const handleFileDrop = (e) => {
            const files = e.dataTransfer.files;
            if (files.length) {
                uploadFile(files[0]);
            }
        };

        const handleFileSelect = (e) => {
            const files = e.target.files;
            if (files.length) {
                uploadFile(files[0]);
            }
        };

        const uploadFile = async (file) => {
            const formData = new FormData();
            formData.append('file', file);

            try {
                if (isWP) {
                    formData.append('action', 'berezina_chatbot_upload_file');
                    formData.append('nonce', config.nonce);
                    const res = await fetch(config.ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        showBanner('Файл успешно загружен!');
                        fetchData();
                    } else {
                        showBanner('Ошибка загрузки: ' + (data.data.error || 'unknown'), 'error');
                    }
                } else {
                    const res = await fetch('api/uploads.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        showBanner('Файл успешно загружен!');
                        fetchData();
                    } else {
                        showBanner('Ошибка загрузки: ' + (data.error || 'unknown'), 'error');
                    }
                }
            } catch (e) {
                showBanner('Сбой при передаче файла', 'error');
            }
        };

        const deleteUpload = async (name) => {
            if (!confirm('Удалить этот файл навсегда?')) return;
            try {
                if (isWP) {
                    const formData = new FormData();
                    formData.append('action', 'berezina_chatbot_delete_file');
                    formData.append('nonce', config.nonce);
                    formData.append('name', name);
                    const res = await fetch(config.ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        showBanner('Файл успешно удален');
                        fetchData();
                    }
                } else {
                    await fetch(`api/uploads.php?name=${name}`, { method: 'DELETE' });
                    showBanner('Файл успешно удален');
                    fetchData();
                }
            } catch (e) {
                showBanner('Ошибка удаления файла', 'error');
            }
        };

        const clearHistory = async () => {
            if (!confirm('Вы действительно хотите полностью очистить лог диалогов?')) return;
            try {
                if (isWP) {
                    const formData = new FormData();
                    formData.append('action', 'berezina_chatbot_clear_history');
                    formData.append('nonce', config.nonce);
                    await fetch(config.ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                } else {
                    await fetch('admin.php?action=clear_history');
                }
                history.value = [];
                showBanner('Лог диалогов успешно очищен!');
            } catch (e) {
                showBanner('Ошибка очистки лога', 'error');
            }
        };

        const deleteHistoryItem = async (id) => {
            try {
                if (isWP) {
                    const formData = new FormData();
                    formData.append('action', 'berezina_chatbot_delete_history_item');
                    formData.append('nonce', config.nonce);
                    formData.append('id', id);
                    await fetch(config.ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                } else {
                    await fetch(`admin.php?action=delete_history_item&id=${id}`);
                }
                history.value = history.value.filter(i => i.id !== id);
                showBanner('Запись удалена');
            } catch (e) {
                showBanner('Ошибка удаления записи', 'error');
            }
        };

        const addQAItem = () => {
            knowledge.value.unshift({ keywords: [], answer: '' });
        };

        const updateKeywords = (index, val) => {
            knowledge.value[index].keywords = val.split(',').map(s => s.trim()).filter(s => s);
        };

        const bulkAdd = async () => {
            if (!bulkText.value.trim()) return alert('Введите текст для добавления');
            try {
                if (isWP) {
                    const formData = new FormData();
                    formData.append('action', 'berezina_chatbot_bulk_add_qa');
                    formData.append('nonce', config.nonce);
                    formData.append('text', bulkText.value);

                    const res = await fetch(config.ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        knowledge.value = data.data.knowledge;
                        bulkText.value = '';
                        showBanner(`Успешно добавлено вопросов: ${data.data.added}`);
                    } else {
                        showBanner('Ошибка при групповом добавлении: ' + (data.data.error || 'unknown'), 'error');
                    }
                } else {
                    const res = await fetch('admin.php?action=bulk_add_qa', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ text: bulkText.value })
                    });
                    const data = await res.json();
                    if (data.success) {
                        knowledge.value = data.knowledge;
                        bulkText.value = '';
                        showBanner(`Успешно добавлено вопросов: ${data.added}`);
                    }
                }
            } catch (e) {
                showBanner('Ошибка соединения с сервером', 'error');
            }
        };

        const addQuickStart = () => {
            settings.value.quick_start_menu.push({ text: 'Новая кнопка', message: 'Текст для бота' });
        };

        onMounted(fetchData);

        return {
            activeTab, dayNames, settings, knowledge, history, uploads, banner, bulkText,
            saveData, addQAItem, updateKeywords, bulkAdd, addQuickStart, handleFileDrop, handleFileSelect, deleteUpload, clearHistory, deleteHistoryItem
        };
    }
}).mount('#berezina-chatbot-admin');
