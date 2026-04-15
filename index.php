<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление ценами - Санаторий Березина</title>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary: #7360f2;
            --primary-dark: #5a4ad1;
        }
        .bg-primary { background-color: var(--primary); }
        .text-primary { color: var(--primary); }
        .border-primary { border-color: var(--primary); }
        .hover-bg-primary:hover { background-color: var(--primary-dark); }

        [v-cloak] { display: none; }

        .fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
        .fade-enter-from, .fade-leave-to { opacity: 0; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div id="app" v-cloak>
        <!-- Login View -->
        <div v-if="!authenticated" class="flex items-center justify-center min-h-screen">
            <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-gray-100">
                <div class="text-center mb-8">
                    <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800">Вход в систему</h1>
                    <p class="text-gray-500 mt-2">Введите 6-значный пароль администратора</p>
                </div>

                <form @submit.prevent="login">
                    <div class="flex justify-between mb-6">
                        <input v-for="(n, i) in 6" :key="i" :id="'pin-'+i"
                               v-model="pinDigits[i]"
                               @input="focusNext($event, i)"
                               @keydown.delete="focusPrev($event, i)"
                               type="password" maxlength="1"
                               class="w-12 h-14 text-center text-2xl font-bold border-2 rounded-xl focus:border-primary focus:outline-none bg-gray-50 transition-all"
                               required autocomplete="off">
                    </div>

                    <button type="submit" :disabled="loading"
                            class="w-full bg-primary hover-bg-primary text-white font-bold py-4 rounded-xl shadow-lg transition-all disabled:opacity-50">
                        {{ loading ? 'Проверка...' : 'Войти' }}
                    </button>

                    <p v-if="error" class="text-red-500 text-center mt-4 text-sm font-medium">{{ error }}</p>
                </form>
            </div>
        </div>

        <!-- Admin View -->
        <div v-else class="flex flex-col min-h-screen">
            <!-- Header -->
            <header class="bg-white border-b border-gray-200 sticky top-0 z-10">
                <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="bg-primary p-2 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Панель управления ценами</h2>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-500 hidden md:inline">Администратор</span>
                        <button @click="logout" class="text-gray-400 hover:text-red-500 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </div>
                </div>
            </header>

            <main class="flex-grow max-w-7xl w-full mx-auto px-4 py-8">
                <!-- Sidebar & Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    <!-- Sidebar: Price Lists List -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                                <h3 class="font-bold text-gray-700">Прайс-листы</h3>
                                <button @click="addNewPriceList" class="p-1 text-primary hover:bg-primary/10 rounded transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                            <div class="divide-y divide-gray-100">
                                <div v-for="(pl, idx) in priceLists" :key="idx"
                                     @click="currentIdx = idx"
                                     :class="['p-4 cursor-pointer transition-colors hover:bg-gray-50 flex items-center gap-3', currentIdx === idx ? 'bg-primary/5 border-l-4 border-primary' : 'border-l-4 border-transparent']">
                                    <div class="flex-grow">
                                        <div class="font-medium text-gray-800">{{ pl.title || 'Без названия' }}</div>
                                        <div class="text-xs text-gray-400 mt-1">ID: {{ pl.id }}</div>
                                    </div>
                                    <button @click.stop="deletePriceList(idx)" class="text-gray-300 hover:text-red-500 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                                <div v-if="priceLists.length === 0" class="p-8 text-center text-gray-400 italic">
                                    Нет созданных прайс-листов
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content: Editor -->
                    <div class="lg:col-span-3">
                        <div v-if="currentList" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <!-- Meta Editor -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-2">Название прайс-листа</label>
                                    <input v-model="currentList.title" type="text"
                                           class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-2">ID (для шорткода)</label>
                                    <input v-model="currentList.id" type="text"
                                           class="w-full px-4 py-2 rounded-lg border bg-gray-50 text-gray-500 cursor-not-allowed outline-none" readonly>
                                </div>
                            </div>

                            <!-- Columns Editor -->
                            <div class="mb-8">
                                <label class="block text-sm font-medium text-gray-500 mb-2">Колонки таблицы</label>
                                <div class="flex flex-wrap gap-2 mb-3">
                                    <div v-for="(col, cIdx) in currentList.columns" :key="cIdx"
                                         class="flex items-center gap-2 bg-gray-100 px-3 py-1.5 rounded-lg border border-gray-200">
                                        <input v-model="currentList.columns[cIdx]" type="text"
                                               class="bg-transparent text-sm font-medium outline-none w-32 focus:text-primary">
                                        <button @click="removeColumn(cIdx)" class="text-gray-400 hover:text-red-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                    <button @click="addColumn" class="flex items-center gap-1 px-3 py-1.5 rounded-lg border-2 border-dashed border-gray-200 text-gray-400 hover:border-primary hover:text-primary transition-all text-sm font-medium">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                        </svg>
                                        Добавить
                                    </button>
                                </div>
                            </div>

                            <!-- Data Grid -->
                            <div class="mb-8">
                                <div class="flex items-center justify-between mb-4">
                                    <label class="text-sm font-medium text-gray-500 uppercase tracking-wider">Данные (Строки)</label>
                                    <div class="flex gap-2">
                                        <button @click="addCategory" class="text-xs bg-gray-800 text-white px-3 py-1.5 rounded-lg hover:bg-black transition-colors">
                                            + Категория
                                        </button>
                                        <button @click="addRow" class="text-xs bg-primary text-white px-3 py-1.5 rounded-lg hover-bg-primary transition-colors shadow-sm">
                                            + Строка цены
                                        </button>
                                    </div>
                                </div>

                                <div class="overflow-x-auto border border-gray-100 rounded-xl">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="bg-gray-50">
                                                <th class="p-3 text-xs font-bold text-gray-400 uppercase w-10">#</th>
                                                <th v-for="col in currentList.columns" class="p-3 text-xs font-bold text-gray-400 uppercase">{{ col }}</th>
                                                <th class="p-3 w-10"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(row, rIdx) in currentList.data" :key="rIdx"
                                                :class="[row.isCategory ? 'bg-primary/5' : 'hover:bg-gray-50 border-b border-gray-50']">
                                                <td class="p-3 text-gray-300 text-sm">{{ rIdx + 1 }}</td>

                                                <template v-if="row.isCategory">
                                                    <td :colspan="currentList.columns.length" class="p-3">
                                                        <input v-model="row.cells[0]" type="text"
                                                               placeholder="Название категории..."
                                                               class="w-full bg-transparent font-bold text-primary placeholder-primary/40 outline-none">
                                                    </td>
                                                </template>
                                                <template v-else>
                                                    <td v-for="(col, cIdx) in currentList.columns" :key="cIdx" class="p-3">
                                                        <input v-model="row.cells[cIdx]" type="text"
                                                               class="w-full bg-transparent text-sm text-gray-700 outline-none focus:text-primary">
                                                    </td>
                                                </template>

                                                <td class="p-3">
                                                    <button @click="removeRow(rIdx)" class="text-gray-300 hover:text-red-500 transition-colors">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="currentList.data.length === 0">
                                                <td :colspan="currentList.columns.length + 2" class="p-12 text-center text-gray-400 italic">
                                                    Таблица пуста. Добавьте строки или категории.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-col md:flex-row items-center justify-between gap-6 pt-6 border-t border-gray-100">
                                <div>
                                    <div class="text-xs font-bold text-gray-400 uppercase mb-2">Шорткод для сайта</div>
                                    <div class="flex items-center gap-2 bg-gray-50 px-3 py-2 rounded-lg border border-gray-200 group">
                                        <code class="text-primary font-mono text-sm">[price_list id="{{ currentList.id }}"]</code>
                                        <button @click="copyShortcode(currentList.id)" class="text-gray-400 hover:text-primary transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <button @click="saveAll" :disabled="saving"
                                        class="w-full md:w-auto bg-primary hover-bg-primary text-white font-bold px-8 py-3 rounded-xl shadow-lg transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                                    <svg v-if="saving" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ saving ? 'Сохранение...' : 'Сохранить все изменения' }}
                                </button>
                            </div>
                        </div>

                        <div v-else class="h-full flex flex-col items-center justify-center text-center p-12 bg-white rounded-2xl border-2 border-dashed border-gray-100">
                            <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mb-6">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">Выберите или создайте прайс-лист</h3>
                            <p class="text-gray-500 max-w-sm">Выберите один из существующих прайс-листов слева или создайте новый, чтобы начать редактирование.</p>
                            <button @click="addNewPriceList" class="mt-8 bg-primary text-white font-bold px-6 py-3 rounded-xl hover-bg-primary transition-all">
                                Создать первый прайс-лист
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Global Toasts -->
        <div class="fixed bottom-6 right-6 flex flex-col gap-2 z-50">
            <div v-for="toast in toasts" :key="toast.id"
                 :class="['px-6 py-3 rounded-xl shadow-2xl text-white font-medium flex items-center gap-3 animate-bounce', toast.type === 'error' ? 'bg-red-500' : 'bg-green-500']">
                <span>{{ toast.message }}</span>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
