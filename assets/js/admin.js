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
            working_hours: {},
            contacts: {},
            fallback: {},
            visuals: {},
            schedule: {},
            directions: []
        });
        const knowledge = ref([]);
        const history = ref([]);

        const fetchData = async () => {
            const res = await fetch('admin.php?action=get_data');
            const data = await res.json();
            settings.value = data.settings;
            knowledge.value = data.knowledge;

            const hRes = await fetch('admin.php?action=get_history');
            history.value = await hRes.json();
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

        return { activeTab, dayNames, settings, knowledge, history, save, addQnA, removeQnA, updateKeywords, triggerImport, clearHistory, deleteHistoryItem };
    }
}).mount('#admin-app');
