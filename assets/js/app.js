const { createApp, ref, computed, onMounted, nextTick } = Vue;

createApp({
    setup() {
        const authenticated = ref(false);
        const loading = ref(false);
        const saving = ref(false);
        const error = ref('');
        const pinDigits = ref(['', '', '', '', '', '']);
        const newPinDigits = ref(['', '', '', '', '', '']);
        const priceLists = ref([]);
        const currentIdx = ref(null);
        const toasts = ref([]);

        const currentList = computed(() => {
            if (currentIdx.value === null || currentIdx.value === 'settings') return null;
            return priceLists.value[currentIdx.value];
        });

        const checkAuth = async () => {
            try {
                const res = await fetch('api/auth.php?action=check');
                const data = await res.json();
                authenticated.value = data.authenticated;
                if (authenticated.value) {
                    await fetchPrices();
                }
            } catch (e) {
                console.error('Auth check failed', e);
            }
        };

        const login = async () => {
            loading.value = true;
            error.value = '';
            const pin = pinDigits.value.join('');

            try {
                const res = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    body: JSON.stringify({ pin })
                });

                const data = await res.json();
                if (data.success) {
                    authenticated.value = true;
                    await fetchPrices();
                    showToast('Успешный вход');
                } else {
                    error.value = data.error || 'Ошибка входа';
                    pinDigits.value = ['', '', '', '', '', ''];
                    document.getElementById('pin-0').focus();
                }
            } catch (e) {
                error.value = 'Ошибка сервера';
            } finally {
                loading.value = false;
            }
        };

        const logout = async () => {
            await fetch('api/auth.php?action=logout');
            authenticated.value = false;
            priceLists.value = [];
            currentIdx.value = null;
            pinDigits.value = ['', '', '', '', '', ''];
        };

        const changePin = async () => {
            const newPin = newPinDigits.value.join('');
            if (newPin.length !== 6) {
                showToast('Введите 6 цифр', 'error');
                return;
            }

            loading.value = true;
            try {
                const res = await fetch('api/settings.php?action=change_pin', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ new_pin: newPin })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Пароль успешно изменен');
                    newPinDigits.value = ['', '', '', '', '', ''];
                } else {
                    showToast(data.error || 'Ошибка при смене пароля', 'error');
                }
            } catch (e) {
                showToast('Ошибка сервера', 'error');
            } finally {
                loading.value = false;
            }
        };

        const fetchPrices = async () => {
            try {
                const res = await fetch('api/prices.php');
                priceLists.value = await res.json();
            } catch (e) {
                showToast('Ошибка при загрузке данных', 'error');
            }
        };

        const saveAll = async () => {
            saving.value = true;
            try {
                const res = await fetch('api/prices.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(priceLists.value)
                });
                if (res.ok) {
                    showToast('Данные успешно сохранены');
                } else {
                    throw new Error();
                }
            } catch (e) {
                showToast('Ошибка при сохранении', 'error');
            } finally {
                saving.value = false;
            }
        };

        const addNewPriceList = () => {
            const id = 'pl_' + Math.random().toString(36).substr(2, 9);
            priceLists.value.push({
                id,
                title: 'Новый прайс-лист',
                columns: ['Наименование', 'Цена (BYN)'],
                data: []
            });
            currentIdx.value = priceLists.value.length - 1;
        };

        const deletePriceList = (idx) => {
            if (confirm('Вы уверены, что хотите удалить этот прайс-лист?')) {
                priceLists.value.splice(idx, 1);
                if (currentIdx.value === idx) {
                    currentIdx.value = null;
                } else if (currentIdx.value > idx) {
                    currentIdx.value--;
                }
            }
        };

        const addColumn = () => {
            currentList.value.columns.push('Новая колонка');
            currentList.value.data.forEach(row => {
                if (!row.isCategory) {
                    row.cells.push('');
                }
            });
        };

        const removeColumn = (idx) => {
            currentList.value.columns.splice(idx, 1);
            currentList.value.data.forEach(row => {
                if (!row.isCategory) {
                    row.cells.splice(idx, 1);
                }
            });
        };

        const addCategory = () => {
            currentList.value.data.push({
                isCategory: true,
                cells: ['Новая категория']
            });
        };

        const addRow = () => {
            currentList.value.data.push({
                isCategory: false,
                cells: new Array(currentList.value.columns.length).fill('')
            });
        };

        const removeRow = (idx) => {
            currentList.value.data.splice(idx, 1);
        };

        const focusNext = (e, i) => {
            if (e.target.value.length === 1 && i < 5) {
                document.getElementById(`pin-${i+1}`).focus();
            }
        };

        const focusPrev = (e, i) => {
            if (e.target.value.length === 0 && i > 0) {
                document.getElementById(`pin-${i-1}`).focus();
            }
        };

        const focusNextNewPin = (e, i) => {
            if (e.target.value.length === 1 && i < 5) {
                document.getElementById(`new-pin-${i+1}`).focus();
            }
        };

        const focusPrevNewPin = (e, i) => {
            if (e.target.value.length === 0 && i > 0) {
                document.getElementById(`new-pin-${i-1}`).focus();
            }
        };

        const showToast = (message, type = 'success') => {
            const id = Date.now();
            toasts.value.push({ id, message, type });
            setTimeout(() => {
                toasts.value = toasts.value.filter(t => t.id !== id);
            }, 3000);
        };

        const copyShortcode = (id) => {
            const code = `[price_list id="${id}"]`;
            navigator.clipboard.writeText(code).then(() => {
                showToast('Шорткод скопирован');
            });
        };

        onMounted(checkAuth);

        return {
            authenticated, loading, saving, error, pinDigits, newPinDigits,
            priceLists, currentIdx, currentList, toasts,
            login, logout, saveAll, addNewPriceList, deletePriceList,
            addColumn, removeColumn, addCategory, addRow, removeRow,
            focusNext, focusPrev, focusNextNewPin, focusPrevNewPin, copyShortcode, changePin
        };
    }
}).mount('#app');
