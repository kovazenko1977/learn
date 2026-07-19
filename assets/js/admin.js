const { createApp, ref, computed, onMounted } = Vue;
createApp({
    setup() {
        const activeTab = ref('knowledge');
        const dayNames = { "1": "Пн", "2": "Вт", "3": "Ср", "4": "Чт", "5": "Пт", "6": "Сб", "0": "Вс" };
        const settings = ref({
            enabled: true, bot_name: 'Помощник PRO', welcome_message: '', admin_password: '',
            working_hours: { timezone: 'Europe/Moscow', out_of_hours_message: '' },
            contacts: { phone: '', email: '', address: '', whatsapp: '', telegram: '' },
            fallback: { threshold: 40, message: '', button_text: '' },
            notifications: { email: { enabled: false, address: '' }, telegram: { enabled: false, token: '', chat_id: '' } },
            features: {
                proactive_greeting: 0, sound_enabled: true, voice_input: true, persistence: true,
                rating_system: true, dark_mode: 'auto', typing_indicator: true, rich_text: true,
                show_branding: true, quick_start: true, departments: false, file_upload: true,
                chat_export: true, idle_reminder: 60, dynamic_greeting: true, keyboard_shortcuts: true,
                user_id_form: false, smart_scroll: true, custom_css_enabled: false
            },
            visuals: {
                theme_color: '#2563eb', chat_icon_url: '', bot_avatar_url: '',
                floating_text: '', floating_bg: '#2563eb', floating_color: '#ffffff',
                floating_animation: 'none', typing_speed: 30, position: 'bottom-right',
                offset_x: 20, offset_y: 20, custom_css: ''
            },
            schedule: {}, directions: [], forms: [], webhooks: [], departments: [], quick_start_menu: []
        });
        const knowledge = ref([]);
        const history = ref([]);
        const uploads = ref([]);
        const isLoaded = ref(false);
        const scriptUrl = ref('');
        const searchQuery = ref('');
        const bulkText = ref('');

        const stats = computed(() => {
            const total = history.value.length;
            const leads = history.value.filter(h => h.user_message.includes('LEAD_PHONE') || h.user_message.includes('FORM_SUBMISSION')).length;
            const fallbacks = history.value.filter(h => h.is_fallback).length;
            const avgScore = total ? history.value.reduce((acc, h) => acc + h.score, 0) / total : 0;
            return { total, leads, fallbacks, avgScore: Math.round(avgScore) };
        });

        const filteredKnowledge = computed(() => {
            if (!searchQuery.value) return knowledge.value;
            const q = searchQuery.value.toLowerCase();
            return knowledge.value.filter(item =>
                item.keywords.some(k => k.toLowerCase().includes(q)) ||
                item.answer.toLowerCase().includes(q)
            );
        });

        const fetchData = async () => {
            scriptUrl.value = window.location.origin + window.location.pathname.replace('admin.php', '') + 'assets/js/loader.js';
            try {
                const res = await fetch('admin.php?action=get_data');
                const data = await res.json();
                if (data.settings) {
                    const merge = (target, source) => {
                        for (const key in source) {
                            if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
                                if (!target[key]) target[key] = {};
                                merge(target[key], source[key]);
                            } else target[key] = source[key];
                        }
                    };
                    merge(settings.value, data.settings);
                }
                knowledge.value = data.knowledge || [];
                const hRes = await fetch('admin.php?action=get_history');
                history.value = await hRes.json();
                fetchUploads();
            } catch (e) {} finally { isLoaded.value = true; }
        };

        const fetchUploads = async () => {
            const res = await fetch('api/uploads.php?action=list');
            uploads.value = await res.json();
        };

        const deleteUpload = async (name) => {
            if (confirm('Удалить файл?')) {
                await fetch(`api/uploads.php?name=${name}`, { method: 'DELETE' });
                fetchUploads();
            }
        };

        const save = async () => {
            await fetch('admin.php?action=save_data', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ settings: settings.value, knowledge: knowledge.value })
            });
            alert('Сохранено!');
        };

        const addQnA = () => knowledge.value.unshift({ keywords: [], answer: '' });
        const removeQnA = (index) => knowledge.value.splice(index, 1);
        const updateKeywords = (index, val) => {
            knowledge.value[index].keywords = val.split(',').map(s => s.trim()).filter(s => s);
        };
        const bulkAdd = async () => {
            if (!bulkText.value.trim()) return alert('Введите текст для добавления');
            try {
                const res = await fetch('admin.php?action=bulk_add_qa', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ text: bulkText.value })
                });
                const data = await res.json();
                if (data.success) {
                    knowledge.value = data.knowledge;
                    bulkText.value = '';
                    alert(`Успешно добавлено вопросов: ${data.added}`);
                }
            } catch (e) {
                alert('Ошибка импорта');
            }
        };
        const triggerImport = () => document.querySelector('input[type="file"]').click();
        const clearHistory = async () => { if (confirm('Очистить историю?')) { await fetch('admin.php?action=clear_history'); history.value = []; } };
        const deleteHistoryItem = async (id) => { await fetch(`admin.php?action=delete_history_item&id=${id}`); history.value = history.value.filter(i => i.id !== id); };
        const addForm = () => settings.value.forms.push({ id: 'form_' + Date.now(), title: 'Новая форма', fields: [{ label: 'Имя', type: 'text', required: true }] });
        const addWebhook = () => settings.value.webhooks.push({ url: '', method: 'POST', enabled: true });
        const addDepartment = () => settings.value.departments.push({ id: 'dep_' + Date.now(), name: 'Новый отдел' });
        const addQuickStart = () => settings.value.quick_start_menu.push({ text: 'Вопрос?', message: 'Ответ' });

        onMounted(fetchData);

        return {
            activeTab, dayNames, settings, knowledge, history, uploads, isLoaded, scriptUrl,
            searchQuery, filteredKnowledge, stats, bulkText,
            save, addQnA, removeQnA, updateKeywords, bulkAdd, triggerImport, clearHistory,
            deleteHistoryItem, addForm, addWebhook, addDepartment, addQuickStart, deleteUpload
        };
    }
}).mount('#admin-app');
