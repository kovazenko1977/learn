// Standalone & WordPress dual-compatible chatbot logic
(function() {
    // If Vue is not loaded yet, wait or load it dynamically
    if (typeof Vue === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://unpkg.com/vue@3/dist/vue.global.js';
        script.onload = initChatbot;
        document.head.appendChild(script);
    } else {
        initChatbot();
    }

    function initChatbot() {
        const { createApp, ref, onMounted, nextTick } = Vue;

        createApp({
            setup() {
                const isOpen = ref(false);
                const settings = ref({
                    widget_title: 'Помощник БЕРЕЗИНА',
                    contacts: { phone: '', whatsapp: '', telegram: '' },
                    quick_start_menu: []
                });
                const messages = ref([]);
                const userInput = ref('');
                const isLoading = ref(false);
                const messagesContainer = ref(null);

                // Active lead form fields
                const activeForm = ref(null); // 'contact' or 'booking'
                const leadData = ref({
                    name: '',
                    email: '',
                    phone: '',
                    date_start: '',
                    date_end: '',
                    guests: 1,
                    room_type: 'Стандартный'
                });

                const isWP = typeof window.berezinaChatbotConfig !== 'undefined';
                const config = isWP ? window.berezinaChatbotConfig : { ajax_url: 'api/chat.php', nonce: '' };

                const fetchSettings = async () => {
                    try {
                        if (isWP && config.settings) {
                            settings.value = config.settings;
                            addWelcomeMessage();
                        } else {
                            const response = await fetch('api/settings.php');
                            settings.value = await response.json();
                            addWelcomeMessage();
                        }
                    } catch (error) {
                        console.error('Error fetching settings:', error);
                        // Safe defaults
                        settings.value = {
                            widget_title: 'Помощник БЕРЕЗИНА',
                            welcome_message: 'Здравствуйте! Я ваш виртуальный помощник. Чем могу помочь?',
                            contacts: { phone: '+375 (177) 74-21-44', whatsapp: '+375333533971', telegram: 'berezina_bot' },
                            quick_start_menu: [
                                { text: '🌲 Цены на путевки', message: 'Цены на путевки' },
                                { text: '🩺 Услуги и процедуры', message: 'Услуги и процедуры' }
                            ]
                        };
                        addWelcomeMessage();
                    }
                };

                const addWelcomeMessage = () => {
                    messages.value.push({
                        text: settings.value.welcome_message || 'Здравствуйте! Чем я могу помочь вам сегодня?',
                        isBot: true
                    });
                };

                const handleQuickStart = (messageText) => {
                    sendMessage(messageText);
                };

                const sendMessage = async (explicitText = null) => {
                    const text = (explicitText !== null ? explicitText : userInput.value).trim();
                    if (!text || isLoading.value) return;

                    if (explicitText === null) {
                        userInput.value = '';
                    }

                    messages.value.push({
                        text: text,
                        isBot: false
                    });

                    isLoading.value = true;
                    scrollToBottom();

                    try {
                        let data;
                        if (isWP) {
                            const formData = new FormData();
                            formData.append('action', 'berezina_chatbot_message');
                            formData.append('nonce', config.nonce);
                            formData.append('message', text);

                            const response = await fetch(config.ajax_url, {
                                method: 'POST',
                                body: formData
                            });
                            data = await response.json();
                        } else {
                            const response = await fetch('api/chat.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ message: text })
                            });
                            data = await response.json();
                        }

                        // Parse triggers for custom forms
                        if (data.form_id) {
                            activeForm.value = data.form_id;
                        }

                        messages.value.push({
                            text: data.answer,
                            isBot: true,
                            fallback: data.is_fallback ? {
                                text: data.button_text,
                                phone: data.phone
                            } : null,
                            form_id: data.form_id || null
                        });
                    } catch (error) {
                        messages.value.push({
                            text: 'Ошибка связи с сервером.',
                            isBot: true
                        });
                    } finally {
                        isLoading.value = false;
                        scrollToBottom();
                    }
                };

                const submitLeadForm = async () => {
                    let formMessage = `FORM_SUBMISSION: ${activeForm.value === 'booking' ? 'Бронирование путевки' : 'Контакты обратной связи'}\n`;
                    formMessage += `Имя: ${leadData.value.name}\nТелефон: ${leadData.value.phone}\n`;
                    if (leadData.value.email) {
                        formMessage += `Email: ${leadData.value.email}\n`;
                    }
                    if (activeForm.value === 'booking') {
                        formMessage += `Тип номера: ${leadData.value.room_type}\n`;
                        formMessage += `Период: с ${leadData.value.date_start} по ${leadData.value.date_end}\n`;
                        formMessage += `Количество гостей: ${leadData.value.guests}\n`;
                    }

                    // Reset form immediately
                    activeForm.value = null;

                    messages.value.push({
                        text: 'Отправка ваших контактных данных...',
                        isBot: true
                    });

                    isLoading.value = true;
                    scrollToBottom();

                    try {
                        let data;
                        if (isWP) {
                            const formData = new FormData();
                            formData.append('action', 'berezina_chatbot_message');
                            formData.append('nonce', config.nonce);
                            formData.append('message', formMessage);

                            const response = await fetch(config.ajax_url, {
                                method: 'POST',
                                body: formData
                            });
                            data = await response.json();
                        } else {
                            const response = await fetch('api/chat.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ message: formMessage })
                            });
                            data = await response.json();
                        }

                        messages.value.push({
                            text: 'Спасибо! Ваши данные успешно отправлены. Мы свяжемся с вами в ближайшее время.',
                            isBot: true
                        });
                    } catch (e) {
                        messages.value.push({
                            text: 'Не удалось отправить форму автоматически. Пожалуйста, позвоните нам по телефону: ' + settings.value.contacts.phone,
                            isBot: true
                        });
                    } finally {
                        isLoading.value = false;
                        scrollToBottom();
                    }
                };

                const scrollToBottom = async () => {
                    await nextTick();
                    if (messagesContainer.value) {
                        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
                    }
                };

                const toggleChat = () => {
                    isOpen.value = !isOpen.value;
                    if (isOpen.value) {
                        scrollToBottom();
                    }
                };

                onMounted(() => {
                    fetchSettings();
                });

                return {
                    isOpen,
                    settings,
                    messages,
                    userInput,
                    isLoading,
                    messagesContainer,
                    activeForm,
                    leadData,
                    sendMessage,
                    toggleChat,
                    handleQuickStart,
                    submitLeadForm
                };
            },
            template: `
                <div>
                    <!-- Widget Button -->
                    <button @click="toggleChat" class="berezina-chatbot-launcher shadow-2xl transition-all" :style="{ backgroundColor: '#2563eb' }">
                        <svg v-if="!isOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a.598.598 0 01-.61-.326 5.784 5.784 0 01-.389-2.257c0-.157.018-.313.051-.465C3.301 16.59 3 14.343 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Chat Window Panel -->
                    <div v-show="isOpen" class="berezina-chatbot-panel shadow-2xl flex flex-col overflow-hidden">
                        <!-- Top Header -->
                        <div class="berezina-chatbot-header flex items-center justify-between p-4 text-white" :style="{ backgroundColor: '#2563eb' }">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center font-black text-blue-600 shadow">Б</div>
                                <div>
                                    <div class="font-bold text-sm">{{ settings.widget_title }}</div>
                                    <div class="text-[10px] opacity-80 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 bg-green-400 rounded-full inline-block animate-pulse"></span> Онлайн
                                    </div>
                                </div>
                            </div>
                            <button @click="toggleChat" class="text-white hover:opacity-80 font-black">&times;</button>
                        </div>

                        <!-- Chat Messages Container -->
                        <div ref="messagesContainer" class="flex-1 p-4 overflow-y-auto space-y-4 bg-gray-50">
                            <div v-for="(msg, index) in messages" :key="index" :class="msg.isBot ? 'justify-start' : 'justify-end'" class="flex">
                                <div :class="msg.isBot ? 'bg-white text-gray-800' : 'bg-blue-600 text-white'" class="p-3 rounded-2xl max-w-[85%] text-xs shadow-sm font-medium">
                                    <div class="whitespace-pre-line leading-relaxed">{{ msg.text }}</div>

                                    <!-- Fallback action button -->
                                    <div v-if="msg.fallback" class="mt-3 pt-3 border-t border-gray-100 flex flex-col gap-2">
                                        <button @click="activeForm = 'contact'" class="bg-blue-50 text-blue-600 px-3 py-2 rounded-xl font-bold text-[10px] text-center shadow-sm">
                                            {{ msg.fallback.text }}
                                        </button>
                                        <a :href="'tel:' + msg.fallback.phone" class="bg-gray-100 text-gray-700 px-3 py-2 rounded-xl font-bold text-[10px] text-center shadow-sm">
                                            Позвонить нам
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Interactive Lead Form (Contact) -->
                            <div v-if="activeForm === 'contact'" class="bg-white p-4 rounded-2xl shadow-md border border-blue-100 space-y-3">
                                <div class="font-black text-xs text-gray-800">Заказать обратный звонок</div>
                                <input v-model="leadData.name" placeholder="Ваше имя" class="w-full bg-gray-50 p-2.5 rounded-xl text-xs border border-gray-100">
                                <input v-model="leadData.phone" placeholder="Ваш телефон" class="w-full bg-gray-50 p-2.5 rounded-xl text-xs border border-gray-100">
                                <div class="flex gap-2">
                                    <button @click="submitLeadForm" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] py-2 rounded-xl">Отправить</button>
                                    <button @click="activeForm = null" class="bg-gray-100 text-gray-600 font-bold text-[10px] py-2 px-3 rounded-xl">Отмена</button>
                                </div>
                            </div>

                            <!-- Interactive Booking Form -->
                            <div v-if="activeForm === 'booking'" class="bg-white p-4 rounded-2xl shadow-md border border-blue-100 space-y-3">
                                <div class="font-black text-xs text-gray-800">Бронирование путевки</div>
                                <input v-model="leadData.name" placeholder="Имя" class="w-full bg-gray-50 p-2 rounded-xl text-xs border-none shadow-inner">
                                <input v-model="leadData.phone" placeholder="Телефон" class="w-full bg-gray-50 p-2 rounded-xl text-xs border-none shadow-inner">
                                <select v-model="leadData.room_type" class="w-full bg-gray-50 p-2 rounded-xl text-xs border-none shadow-inner">
                                    <option>Одноместный 1-комнатный</option>
                                    <option>Двухместный 1-комнатный</option>
                                    <option>Двухместный 2-комнатный</option>
                                </select>
                                <div class="flex gap-1">
                                    <input type="text" v-model="leadData.date_start" placeholder="С какого" class="w-1/2 bg-gray-50 p-2 rounded-xl text-xs border-none shadow-inner">
                                    <input type="text" v-model="leadData.date_end" placeholder="По какое" class="w-1/2 bg-gray-50 p-2 rounded-xl text-xs border-none shadow-inner">
                                </div>
                                <div class="flex gap-2">
                                    <button @click="submitLeadForm" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] py-2 rounded-xl">Забронировать</button>
                                    <button @click="activeForm = null" class="bg-gray-100 text-gray-600 font-bold text-[10px] py-2 px-3 rounded-xl">Отмена</button>
                                </div>
                            </div>

                            <!-- Loading indicator -->
                            <div v-if="isLoading" class="flex justify-start">
                                <div class="bg-white p-3 rounded-2xl shadow-sm text-xs text-gray-400 font-medium flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 bg-blue-600 rounded-full animate-bounce"></span>
                                    <span class="w-1.5 h-1.5 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
                                    <span class="w-1.5 h-1.5 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 0.4s"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Quick start suggestions -->
                        <div v-if="settings.quick_start_menu && settings.quick_start_menu.length && messages.length === 1" class="p-3 bg-white border-t border-gray-100 flex flex-wrap gap-2">
                            <button v-for="btn in settings.quick_start_menu" @click="handleQuickStart(btn.message)" class="bg-blue-50 hover:bg-blue-100 text-blue-600 px-3 py-1.5 rounded-full font-bold text-[10px] shadow-sm transition-colors">
                                {{ btn.text }}
                            </button>
                        </div>

                        <!-- Bottom Footer Input Form -->
                        <div class="p-3 bg-white border-t border-gray-100 flex items-center gap-2">
                            <input v-model="userInput" @keyup.enter="sendMessage()" placeholder="Введите ваш вопрос..." class="flex-1 text-xs border-none bg-gray-50 p-2.5 rounded-xl focus:ring-1 focus:ring-blue-500">
                            <button @click="sendMessage()" class="bg-blue-600 text-white p-2.5 rounded-xl shadow-md hover:bg-blue-700 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                                </svg>
                            </button>
                        </div>

                        <!-- Messenger Shortcuts Footer Row -->
                        <div class="px-3 py-2 bg-gray-50 border-t border-gray-100 flex items-center justify-between text-[10px] font-bold text-gray-400">
                            <span class="uppercase tracking-widest text-[8px]">Наши контакты:</span>
                            <div class="flex items-center gap-3">
                                <a v-if="settings.contacts.whatsapp" :href="'https://wa.me/' + settings.contacts.whatsapp" target="_blank" class="text-green-500 hover:underline">WhatsApp</a>
                                <a v-if="settings.contacts.telegram" :href="'https://t.me/' + settings.contacts.telegram" target="_blank" class="text-blue-400 hover:underline">Telegram</a>
                            </div>
                        </div>
                    </div>
                </div>
            `
        }).mount('#berezina-chatbot-widget-wrapper');
    }
})();
