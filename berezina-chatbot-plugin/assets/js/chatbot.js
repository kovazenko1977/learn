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
                <div id="chat-widget-container" :class="{'mobile-open': isOpen}">
                    <!-- Widget Button -->
                    <button @click="toggleChat" class="chat-button">
                        <svg v-if="!isOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 32px; height: 32px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a.598.598 0 01-.61-.326 5.784 5.784 0 01-.389-2.257c0-.157.018-.313.051-.465C3.301 16.59 3 14.343 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                        </svg>
                        <span v-else style="font-size: 28px;">✕</span>
                    </button>

                    <!-- Chat Window Panel -->
                    <div v-show="isOpen" class="chat-window">
                        <!-- Top Header -->
                        <div class="chat-header">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 36px; height: 36px; border-radius: 50%; background: white; color: var(--chat-primary); display: flex; align-items: center; justify-content: center; font-weight: 900; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">Б</div>
                                <div style="display: flex; flex-direction: column; text-align: left;">
                                    <span style="font-weight: bold; font-size: 14px; line-height: 1.2;">{{ settings.widget_title }}</span>
                                    <span style="font-size: 10px; opacity: 0.9; display: flex; align-items: center; gap: 4px;">
                                        <span style="width: 6px; height: 6px; background: #4ade80; border-radius: 50%; display: inline-block;"></span> Онлайн
                                    </span>
                                </div>
                            </div>
                            <button @click="toggleChat" style="background: none; border: none; color: white; cursor: pointer; font-size: 24px;">&times;</button>
                        </div>

                        <!-- Chat Messages Container -->
                        <div ref="messagesContainer" class="chat-messages">
                            <div v-for="(msg, index) in messages" :key="index" :class="['message', msg.isBot ? 'message-bot' : 'message-user']">
                                <div style="white-space: pre-line; line-height: 1.4;">{{ msg.text }}</div>

                                <!-- Fallback action button -->
                                <div v-if="msg.fallback" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 6px;">
                                    <button @click="activeForm = 'contact'" class="quick-reply-btn" style="text-align: center;">
                                        {{ msg.fallback.text }}
                                    </button>
                                    <a :href="'tel:' + msg.fallback.phone" class="quick-reply-btn" style="text-align: center; background: #e2e8f0; color: #475569; border-color: transparent;">
                                        Позвонить нам
                                    </a>
                                </div>
                            </div>

                            <!-- Interactive Lead Form (Contact) -->
                            <div v-if="activeForm === 'contact'" style="padding: 15px; background: var(--chat-bot-bg); border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-top: 10px;">
                                <div style="font-weight: bold; font-size: 13px; margin-bottom: 10px; color: var(--chat-text);">Заказать обратный звонок</div>
                                <input v-model="leadData.name" placeholder="Ваше имя" class="custom-input">
                                <input v-model="leadData.phone" placeholder="Ваш телефон" class="custom-input">
                                <div style="display: flex; gap: 8px; margin-top: 10px;">
                                    <button @click="submitLeadForm" class="lead-btn" style="padding: 8px 15px; font-size: 11px;">Отправить</button>
                                    <button @click="activeForm = null" class="quick-reply-btn" style="flex: 1; padding: 8px 15px; font-size: 11px;">Отмена</button>
                                </div>
                            </div>

                            <!-- Interactive Booking Form -->
                            <div v-if="activeForm === 'booking'" style="padding: 15px; background: var(--chat-bot-bg); border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-top: 10px;">
                                <div style="font-weight: bold; font-size: 13px; margin-bottom: 10px; color: var(--chat-text);">Бронирование путевки</div>
                                <input v-model="leadData.name" placeholder="Имя" class="custom-input">
                                <input v-model="leadData.phone" placeholder="Телефон" class="custom-input">
                                <select v-model="leadData.room_type" class="custom-input">
                                    <option>Одноместный 1-комнатный</option>
                                    <option>Двухместный 1-комнатный</option>
                                    <option>Двухместный 2-комнатный</option>
                                </select>
                                <div style="display: flex; gap: 6px;">
                                    <input type="text" v-model="leadData.date_start" placeholder="С какого" class="custom-input" style="width: 50%;">
                                    <input type="text" v-model="leadData.date_end" placeholder="По какое" class="custom-input" style="width: 50%;">
                                </div>
                                <div style="display: flex; gap: 8px; margin-top: 10px;">
                                    <button @click="submitLeadForm" class="lead-btn" style="padding: 8px 15px; font-size: 11px;">Забронировать</button>
                                    <button @click="activeForm = null" class="quick-reply-btn" style="flex: 1; padding: 8px 15px; font-size: 11px;">Отмена</button>
                                </div>
                            </div>

                            <!-- Loading indicator -->
                            <div v-if="isLoading" class="message message-bot">
                                <span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span>
                            </div>
                        </div>

                        <!-- Quick start suggestions -->
                        <div v-if="settings.quick_start_menu && settings.quick_start_menu.length && messages.length === 1" class="quick-replies" style="padding: 10px 15px; background: var(--chat-bot-bg);">
                            <button v-for="btn in settings.quick_start_menu" @click="handleQuickStart(btn.message)" class="quick-reply-btn">
                                {{ btn.text }}
                            </button>
                        </div>

                        <!-- Bottom Footer Input Form -->
                        <div class="chat-input-area">
                            <input v-model="userInput" @keyup.enter="sendMessage()" placeholder="Введите ваш вопрос..." class="chat-input">
                            <button @click="sendMessage()" class="send-btn" style="padding: 8px 12px; display: flex; align-items: center; justify-content: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                                </svg>
                            </button>
                        </div>

                        <!-- Messenger Shortcuts Footer Row -->
                        <div class="chat-footer" style="display: flex; justify-content: space-between; align-items: center; padding: 6px 15px;">
                            <span style="font-size: 9px; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px;">Контакты:</span>
                            <div style="display: flex; gap: 10px;">
                                <a v-if="settings.contacts.whatsapp" :href="'https://wa.me/' + settings.contacts.whatsapp" target="_blank" style="color: #25d366; font-weight: bold; text-decoration: none;">WhatsApp</a>
                                <a v-if="settings.contacts.telegram" :href="'https://t.me/' + settings.contacts.telegram" target="_blank" style="color: #0088cc; font-weight: bold; text-decoration: none;">Telegram</a>
                            </div>
                        </div>
                    </div>
                </div>
            `
        }).mount('#berezina-chatbot-widget-wrapper');
    }
})();
