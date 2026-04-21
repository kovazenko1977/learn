(function() {
    // Determine the base path of the script
    const scripts = document.getElementsByTagName('script');
    const currentScript = scripts[scripts.length - 1];
    const scriptSrc = currentScript.src;
    const basePath = scriptSrc.substring(0, scriptSrc.lastIndexOf('/') + 1);
    const rootPath = basePath.replace('assets/js/', '');

    // Load CSS
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = rootPath + 'assets/css/chatbot.css';
    document.head.appendChild(link);

    // Load Vue if not present
    if (typeof Vue === 'undefined') {
        const vueScript = document.createElement('script');
        vueScript.src = 'https://unpkg.com/vue@3/dist/vue.global.js';
        vueScript.onload = initChat;
        document.head.appendChild(vueScript);
    } else {
        initChat();
    }

    function initChat() {
        // Create container
        const container = document.createElement('div');
        container.id = 'chat-widget-loader';
        document.body.appendChild(container);

        // Fetch template or inject HTML
        container.innerHTML = `
            <div id="chat-widget-container" v-cloak :style="'--chat-primary:' + (settings.visuals?.theme_color || '#2563eb') + '; --chat-user-bg:' + (settings.visuals?.theme_color || '#2563eb')">
                <style>
                    .lead-form { margin-top: 10px; padding: 10px; background: #f1f5f9; border-radius: 8px; font-size: 12px; }
                    .lead-input { width: 100%; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px 8px; margin-top: 5px; }
                    .lead-btn { background: var(--chat-primary); color: white; border: none; width: 100%; border-radius: 4px; padding: 4px; margin-top: 5px; cursor: pointer; }
                    .chat-footer { padding: 8px; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; background: #f8fafc; }
                    .chat-footer a { color: inherit; text-decoration: none; font-weight: bold; }
                    .quick-replies { display: flex; flex-wrap: wrap; gap: 5px; padding: 10px; }
                    .quick-reply-btn { font-size: 11px; padding: 4px 8px; border: 1px solid var(--chat-primary); border-radius: 12px; color: var(--chat-primary); background: white; cursor: pointer; }
                </style>
                <div v-if="isOpen" class="chat-window">
                    <div class="chat-header" :style="'background:' + (settings.visuals?.theme_color || '#2563eb')">
                        <span>{{ settings.bot_name }}</span>
                        <button @click="toggleChat" style="background:none; border:none; color:white; cursor:pointer; font-size:20px;">×</button>
                    </div>

                    <div class="chat-messages" ref="messagesContainer">
                        <div v-for="(msg, index) in messages" :key="index"
                             :class="['message', msg.isBot ? 'message-bot' : 'message-user']">
                            {{ msg.text }}
                            <a v-if="msg.fallback" :href="'tel:' + msg.fallback.phone" class="fallback-btn">
                                {{ msg.fallback.text }}
                            </a>

                            <div v-if="msg.showLeadForm && !leadSubmitted" class="lead-form">
                                <div>Оставьте ваш телефон, мы перезвоним:</div>
                                <input v-model="leadPhone" placeholder="+7..." class="lead-input">
                                <button @click="submitLead" class="lead-btn">Жду звонка</button>
                            </div>
                            <div v-if="msg.showLeadForm && leadSubmitted" class="lead-form" style="color: green;">
                                Спасибо! Мы скоро свяжемся с вами.
                            </div>
                        </div>
                        <div v-if="isLoading" class="message message-bot">...</div>
                    </div>

                    <div v-if="settings.directions?.length" class="quick-replies">
                        <button v-for="dir in settings.directions" :key="dir" @click="sendQuickReply(dir)" class="quick-reply-btn">{{ dir }}</button>
                    </div>

                    <form @submit.prevent="sendMessage" class="chat-input-area">
                        <input type="text" v-model="userInput" placeholder="Введите ваш вопрос..." class="chat-input">
                        <button type="submit" class="send-btn" :style="'background:' + (settings.visuals?.theme_color || '#2563eb')">Отправить</button>
                    </form>

                    <div class="chat-footer">
                        Разработано <a href="https://wes.by" target="_blank">wes.by</a> +375333533971
                    </div>
                </div>

                <button @click="toggleChat" class="chat-button" :style="'background:' + (settings.visuals?.theme_color || '#2563eb')">
                    <img v-if="!isOpen && settings.visuals?.chat_icon_url" :src="settings.visuals.chat_icon_url" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                    <svg v-else-if="!isOpen" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <span v-else>✕</span>
                </button>
            </div>
        `;

        // Load the logic
        const { createApp, ref, onMounted, nextTick } = Vue;
        createApp({
            setup() {
                const isOpen = ref(false);
                const settings = ref({});
                const messages = ref([]);
                const userInput = ref('');
                const isLoading = ref(false);
                const messagesContainer = ref(null);
                const leadPhone = ref('');
                const leadSubmitted = ref(false);

                const fetchSettings = async () => {
                    try {
                        const response = await fetch(rootPath + 'api/settings.php');
                        settings.value = await response.json();
                        messages.value.push({
                            text: settings.value.welcome_message,
                            isBot: true
                        });
                    } catch (error) {
                        console.error('Error fetching settings:', error);
                    }
                };

                const sendMessage = async () => {
                    if (!userInput.value.trim() || isLoading.value) return;
                    const text = userInput.value;
                    userInput.value = '';
                    messages.value.push({ text: text, isBot: false });
                    isLoading.value = true;
                    scrollToBottom();

                    try {
                        const response = await fetch(rootPath + 'api/chat.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ message: text })
                        });
                        const data = await response.json();
                        messages.value.push({
                            text: data.answer,
                            isBot: true,
                            showLeadForm: data.show_lead_form || false,
                            fallback: data.is_fallback ? {
                                text: data.button_text,
                                phone: data.phone
                            } : null
                        });
                    } catch (error) {
                        messages.value.push({ text: 'Ошибка связи с сервером.', isBot: true });
                    } finally {
                        isLoading.value = false;
                        scrollToBottom();
                    }
                };

                const scrollToBottom = async () => {
                    await nextTick();
                    const container = document.querySelector('.chat-messages');
                    if (container) container.scrollTop = container.scrollHeight;
                };

                const toggleChat = () => {
                    isOpen.value = !isOpen.value;
                    if (isOpen.value) scrollToBottom();
                };

                const submitLead = async () => {
                    if (!leadPhone.value.trim()) return;
                    await fetch(rootPath + 'api/chat.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ message: "LEAD_PHONE: " + leadPhone.value })
                    });
                    leadSubmitted.value = true;
                };

                const sendQuickReply = (text) => {
                    userInput.value = text;
                    sendMessage();
                };

                onMounted(fetchSettings);

                return { isOpen, settings, messages, userInput, isLoading, messagesContainer, sendMessage, toggleChat, leadPhone, leadSubmitted, submitLead, sendQuickReply };
            }
        }).mount('#chat-widget-loader');
    }
})();
