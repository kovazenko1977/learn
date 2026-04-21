const { createApp, ref, onMounted } = Vue;
createApp({
    setup() {
        const activeTab = ref('knowledge');
        const dayNames = {
            "1": "Понедельник",
            "2": "Вторник",
            "3": "Среда",
            "4": "Четверг",
            "5": "Пятница",
            "6": "Суббота",
            "0": "Воскресенье"
        };
        const settings = ref({
            working_hours: { timezone: 'Europe/Moscow', out_of_hours_message: '' },
            contacts: { phone: '', email: '', address: '' },
            fallback: { threshold: 40, message: '', button_text: '' },
            visuals: { theme_color: '#2563eb', chat_icon_url: '', bot_avatar_url: '' },
            schedule: {
                "1": { enabled: true, start: "09:00", end: "18:00" },
                "2": { enabled: true, start: "09:00", end: "18:00" },
                "3": { enabled: true, start: "09:00", end: "18:00" },
                "4": { enabled: true, start: "09:00", end: "18:00" },
                "5": { enabled: true, start: "09:00", end: "18:00" },
                "6": { enabled: false, start: "00:00", end: "00:00" },
                "0": { enabled: false, start: "00:00", end: "00:00" }
            },
            directions: []
        });
        const knowledge = ref([]);
        const history = ref([]);
        const isLoaded = ref(false);
        const scriptUrl = ref('');

        const fetchData = async () => {
            scriptUrl.value = window.location.origin + window.location.pathname.replace('admin.php', '') + 'assets/js/loader.js';
            try {
                const res = await fetch('admin.php?action=get_data');
                const data = await res.json();

                // Deep merge or specific assignment to avoid losing keys
                if (data.settings) {
                    settings.value = { ...settings.value, ...data.settings };
                }
                knowledge.value = data.knowledge || [];

                const hRes = await fetch('admin.php?action=get_history');
                history.value = await hRes.json();
            } catch (e) {
                console.error("Fetch error:", e);
            } finally {
                isLoaded.value = true;
            }
        };

        const save = async () => {
            try {
                const response = await fetch('admin.php?action=save_data', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        settings: settings.value,
                        knowledge: knowledge.value
                    })
                });
                if (response.ok) {
                    alert('Настройки сохранены!');
                }
            } catch (e) {
                alert('Ошибка сохранения');
            }
        };

        const addQnA = () => {
            knowledge.value.unshift({ keywords: [], answer: '' });
        };

        const removeQnA = (index) => {
            knowledge.value.splice(index, 1);
        };

        const updateKeywords = (index, val) => {
            knowledge.value[index].keywords = val.split(',').map(s => s.trim()).filter(s => s);
        };

        const triggerImport = () => {
            document.querySelector('input[type="file"]').click();
        };

        const clearHistory = async () => {
            if (confirm('Очистить всю историю?')) {
                await fetch('admin.php?action=clear_history');
                history.value = [];
            }
        };

        const deleteHistoryItem = async (id) => {
            await fetch(`admin.php?action=delete_history_item&id=${id}`);
            history.value = history.value.filter(i => i.id !== id);
        };

        onMounted(fetchData);

        return { activeTab, dayNames, settings, knowledge, history, isLoaded, scriptUrl, save, addQnA, removeQnA, updateKeywords, triggerImport, clearHistory, deleteHistoryItem };
    }
}).mount('#admin-app');
