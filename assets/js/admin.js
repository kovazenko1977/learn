const { createApp, ref, onMounted } = Vue;
createApp({
    setup() {
        const settings = ref({
            working_hours: {},
            contacts: {},
            fallback: {},
            directions: []
        });
        const knowledge = ref([]);

        const fetchData = async () => {
            const res = await fetch('admin.php?action=get_data');
            const data = await res.json();
            settings.value = data.settings;
            knowledge.value = data.knowledge;
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

        onMounted(fetchData);

        return { settings, knowledge, save, addQnA, removeQnA, updateKeywords };
    }
}).mount('#admin-app');
