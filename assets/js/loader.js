(function() {
    const scripts = document.getElementsByTagName('script');
    const currentScript = scripts[scripts.length - 1];
    const scriptSrc = currentScript.src;
    const basePath = scriptSrc.substring(0, scriptSrc.lastIndexOf('/') + 1);
    const rootPath = basePath.replace('assets/js/', '');

    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = rootPath + 'assets/css/chatbot.css';
    document.head.appendChild(link);

    if (typeof Vue === 'undefined') {
        const vueScript = document.createElement('script');
        vueScript.src = 'https://unpkg.com/vue@3/dist/vue.global.js';
        vueScript.onload = initChat;
        document.head.appendChild(vueScript);
    } else {
        initChat();
    }

    function initChat() {
        const container = document.createElement('div');
        container.id = 'chat-widget-loader';
        document.body.appendChild(container);

        container.innerHTML = `
            <div id="chat-widget-container" v-cloak
                 v-if="settings.enabled !== false"
                 :class="{'mobile-open': isOpen, 'dark-theme': isDark, 'has-unread': hasUnread}"
                 :style="containerStyle">

                <component is="style" v-if="settings.features?.custom_css_enabled">
                    {{ settings.visuals?.custom_css }}
                </component>

                <div v-if="isOpen" class="chat-window">
                    <div class="chat-header">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <img v-if="settings.visuals?.bot_avatar_url" :src="settings.visuals.bot_avatar_url" style="width:32px; height:32px; border-radius:50%; object-fit:cover; border:2px solid white;">
                            <div style="display:flex; flex-direction:column;">
                                <span style="font-weight:bold; font-size:14px;">{{ settings.bot_name }}</span>
                                <span v-if="selectedDepartment" style="font-size:10px; opacity:0.8;">Отдел: {{ selectedDepartment.name }}</span>
                            </div>
                        </div>
                        <div style="display:flex; gap:10px; align-items:center;">
                            <button v-if="settings.features?.chat_export" @click="exportChat" title="Скачать чат" style="background:none; border:none; color:white; cursor:pointer;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg></button>
                            <button @click="toggleChat" style="background:none; border:none; color:white; cursor:pointer; font-size:24px;">×</button>
                        </div>
                    </div>

                    <!-- Pre-chat Identifier -->
                    <div v-if="settings.features?.user_id_form && !userIdentified && messages.length <= 1" class="chat-messages" style="justify-content:center; align-items:center; text-align:center;">
                        <div style="padding:20px; background:rgba(0,0,0,0.03); border-radius:20px;">
                            <h3 style="font-weight:bold; margin-bottom:15px;">Представьтесь, пожалуйста</h3>
                            <input v-model="userData.name" placeholder="Ваше имя" class="custom-input" style="margin-bottom:10px;">
                            <input v-model="userData.email" placeholder="Email (необязательно)" class="custom-input">
                            <button @click="identifyUser" class="lead-btn" style="margin-top:15px;">Начать общение</button>
                        </div>
                    </div>

                    <!-- Department Selector -->
                    <div v-else-if="settings.features?.departments && !selectedDepartment && messages.length <= 1" class="chat-messages" style="justify-content:center; align-items:center;">
                        <div style="width:100%; padding:20px;">
                            <h3 style="font-weight:bold; text-align:center; margin-bottom:20px;">Выберите отдел</h3>
                            <div style="display:flex; flex-direction:column; gap:10px;">
                                <button v-for="dep in settings.departments" @click="selectDepartment(dep)" class="quick-reply-btn" style="padding:15px; border-radius:15px; font-weight:bold;">
                                    {{ dep.name }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="chat-messages" ref="messagesContainer" @scroll="handleScroll">
                        <div v-for="(msg, index) in messages" :key="index"
                             :class="['message', msg.isBot ? 'message-bot' : 'message-user']">
                            <div v-html="parseRichText(msg.text)"></div>

                            <div v-if="msg.attachment" style="margin-top:8px;">
                                <a :href="rootPath + msg.attachment.url" target="_blank" style="display:flex; align-items:center; gap:5px; font-size:11px; text-decoration:none; color:inherit; background:rgba(0,0,0,0.05); padding:5px 10px; border-radius:5px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
                                    {{ msg.attachment.name }}
                                </a>
                            </div>

                            <div v-if="msg.isBot && settings.features?.rating_system && index > 0 && !msg.rated" class="rating-area">
                                <span @click="rateMessage(msg, 1)" class="rating-btn">👍</span>
                                <span @click="rateMessage(msg, -1)" class="rating-btn">👎</span>
                            </div>
                        </div>
                        <div v-if="isLoading" class="message message-bot">
                            <span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span>
                        </div>
                    </div>

                    <!-- Quick Start suggestions on first load -->
                    <div v-if="settings.features?.quick_start && messages.length <= 1 && !isLoading" class="quick-replies" style="padding:0 20px 10px;">
                        <button v-for="qs in settings.quick_start_menu" @click="sendQuickReply(qs.message)" class="quick-reply-btn">
                            {{ qs.text }}
                        </button>
                    </div>

                    <div v-if="(settings.contacts?.whatsapp || settings.contacts?.telegram) && isOpen" class="social-btns">
                        <a v-if="settings.contacts.whatsapp" :href="'https://wa.me/' + settings.contacts.whatsapp" target="_blank" class="social-btn whatsapp-btn">WhatsApp</a>
                        <a v-if="settings.contacts.telegram" :href="'https://t.me/' + settings.contacts.telegram" target="_blank" class="social-btn telegram-btn">Telegram</a>
                    </div>

                    <form @submit.prevent="sendMessage" class="chat-input-area">
                        <div v-if="settings.features?.file_upload" style="position:relative;">
                            <button type="button" @click="$refs.fileInput.click()" class="voice-btn" title="Прикрепить файл">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                            </button>
                            <input type="file" ref="fileInput" @change="uploadFile" style="display:none;">
                        </div>
                        <input type="text" v-model="userInput" placeholder="Введите ваш вопрос..." class="chat-input" @keydown.enter.ctrl="sendMessage" @focus="scrollToBottom">
                        <button v-if="settings.features?.voice_input" type="button" @click="toggleVoice" :class="['voice-btn', {recording: isRecording}]">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/><path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>
                        </button>
                        <button type="submit" class="send-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        </button>
                    </form>

                    <div v-if="settings.features?.show_branding" class="chat-footer">
                        Разработанно <a href="https://wes.by" target="_blank">WES.BY</a>
                    </div>
                </div>

                <!-- Floating Bubble -->
                <div v-if="!isOpen && settings.visuals?.floating_text"
                     class="floating-text-bubble"
                     :class="[settings.visuals?.position === 'bottom-left' ? 'bubble-left' : (settings.visuals?.position === 'bottom-center' ? 'bubble-center' : 'bubble-right'), 'anim-' + (settings.visuals?.floating_animation || 'none')]"
                     :style="{'--bubble-bg': settings.visuals?.floating_bg, '--bubble-color': settings.visuals?.floating_color}">
                    {{ getDynamicGreeting() || settings.visuals.floating_text }}
                </div>

                <!-- Toggle Button -->
                <button @click="toggleChat" class="chat-button">
                    <img v-if="!isOpen && settings.visuals?.chat_icon_url" :src="settings.visuals.chat_icon_url" style="width:100%; height:100%; object-fit:cover;">
                    <svg v-else-if="!isOpen" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <span v-else style="font-size:28px;">✕</span>
                </button>
            </div>
        `;

        const { createApp, ref, computed, onMounted, nextTick } = Vue;
        createApp({
            setup() {
                const isOpen = ref(false);
                const settings = ref({});
                const messages = ref([]);
                const userInput = ref('');
                const isLoading = ref(false);
                const messagesContainer = ref(null);
                const isRecording = ref(false);
                const hasUnread = ref(false);
                const userIdentified = ref(false);
                const selectedDepartment = ref(null);
                const userData = ref({ name: '', email: '' });
                const lastActivity = ref(Date.now());
                const chime = new Audio('https://assets.mixkit.co/active_storage/sfx/2354/2354-preview.mp3');

                const isDark = computed(() => {
                    if (settings.value.features?.dark_mode === 'on') return true;
                    if (settings.value.features?.dark_mode === 'off') return false;
                    return window.matchMedia('(prefers-color-scheme: dark)').matches;
                });

                const containerStyle = computed(() => {
                    const visuals = settings.value.visuals || {};
                    const pos = visuals.position || 'bottom-right';
                    return {
                        '--chat-primary': visuals.theme_color,
                        '--chat-primary-light': visuals.theme_color + 'aa',
                        'bottom': pos.startsWith('top') ? 'auto' : visuals.offset_y + 'px',
                        'top': pos.startsWith('top') ? visuals.offset_y + 'px' : 'auto',
                        'left': pos.endsWith('left') ? visuals.offset_x + 'px' : (pos === 'bottom-center' ? '50%' : 'auto'),
                        'right': pos.endsWith('right') ? visuals.offset_x + 'px' : 'auto',
                        'transform': pos === 'bottom-center' ? 'translateX(-50%)' : 'none'
                    };
                });

                const fetchSettings = async () => {
                    const res = await fetch(rootPath + 'api/settings.php');
                    settings.value = await res.json();

                    if (settings.value.features?.persistence) {
                        const saved = localStorage.getItem('chat_history');
                        const savedUser = localStorage.getItem('chat_user');
                        if (saved) messages.value = JSON.parse(saved);
                        if (savedUser) {
                            userData.value = JSON.parse(savedUser);
                            userIdentified.value = true;
                        }
                    }

                    if (messages.value.length === 0) {
                        const welcomeMsg = { text: '', fullText: settings.value.welcome_message, isBot: true };
                        messages.value.push(welcomeMsg);
                        await typeText(welcomeMsg);
                    }

                    // Idle Reminder check
                    setInterval(() => {
                        if (isOpen.value && !isLoading.value && settings.value.features?.idle_reminder > 0) {
                            if (Date.now() - lastActivity.value > settings.value.features.idle_reminder * 1000) {
                                lastActivity.value = Date.now();
                                const msg = { text: 'Вы еще здесь? Если у вас возникли вопросы, я готов помочь!', isBot: true };
                                messages.value.push(msg);
                                scrollToBottom();
                            }
                        }
                    }, 10000);

                    // Esc key handler
                    window.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape' && isOpen.value && settings.value.features?.keyboard_shortcuts) toggleChat();
                    });
                };

                const typeText = async (msgObj) => {
                    const fullText = msgObj.fullText;
                    for (let i = 0; i <= fullText.length; i++) {
                        msgObj.text = fullText.substring(0, i);
                        await new Promise(res => setTimeout(res, settings.value.visuals.typing_speed));
                        if (settings.value.features?.smart_scroll) scrollToBottom();
                    }
                    if (settings.value.features?.sound_enabled) chime.play().catch(() => {});
                    saveLocal();
                };

                const sendMessage = async () => {
                    if (!userInput.value.trim() || isLoading.value) return;
                    const text = userInput.value;
                    userInput.value = '';
                    messages.value.push({ text, isBot: false });
                    isLoading.value = true;
                    lastActivity.value = Date.now();
                    scrollToBottom();

                    const res = await fetch(rootPath + 'api/chat.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            message: text,
                            user: userData.value,
                            department: selectedDepartment.value?.id
                        })
                    });
                    const data = await res.json();
                    isLoading.value = false;
                    const botMsg = { text: '', fullText: data.answer, isBot: true };
                    messages.value.push(botMsg);
                    await typeText(botMsg);
                };

                const uploadFile = async (e) => {
                    const file = e.target.files[0];
                    if (!file) return;
                    const formData = new FormData();
                    formData.append('file', file);
                    isLoading.value = true;
                    const res = await fetch(rootPath + 'api/uploads.php', { method: 'POST', body: formData });
                    const data = await res.json();
                    isLoading.value = false;
                    if (data.success) {
                        messages.value.push({ text: `Файл отправлен: ${data.file_name}`, isBot: false, attachment: { url: data.file_url, name: data.file_name } });
                        sendMessageManually(`USER_UPLOAD: ${data.file_url}`);
                    } else alert(data.error);
                };

                const sendMessageManually = async (text) => {
                    await fetch(rootPath + 'api/chat.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ message: text, user: userData.value })
                    });
                };

                const exportChat = () => {
                    const content = messages.value.map(m => `${m.isBot ? 'Bot' : 'You'}: ${m.text}`).join('\n');
                    const blob = new Blob([content], { type: 'text/plain' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url; a.download = 'chat-history.txt'; a.click();
                };

                const identifyUser = () => { if (userData.value.name) { userIdentified.value = true; localStorage.setItem('chat_user', JSON.stringify(userData.value)); } };
                const selectDepartment = (dep) => { selectedDepartment.value = dep; };
                const toggleChat = () => { isOpen.value = !isOpen.value; hasUnread.value = false; if (isOpen.value) { lastActivity.value = Date.now(); scrollToBottom(); } };
                const scrollToBottom = async () => { await nextTick(); if (messagesContainer.value) messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight; };
                const handleScroll = () => { lastActivity.value = Date.now(); };
                const saveLocal = () => { if (settings.value.features?.persistence) localStorage.setItem('chat_history', JSON.stringify(messages.value.slice(-30))); };
                const sendQuickReply = (text) => { userInput.value = text; sendMessage(); };
                const rateMessage = (msg, r) => { msg.rated = true; sendMessageManually(`FEEDBACK: ${r > 0 ? 'Pos' : 'Neg'} for ${msg.text.substring(0,20)}`); };
                const escapeHTML = (str) => {
                    const div = document.createElement('div');
                    div.textContent = str;
                    return div.innerHTML;
                };

                const parseRichText = (t) => {
                    const escaped = escapeHTML(t);
                    return escaped
                        .replace(/\*\*(.*?)\*\*/g, '<b>$1</b>')
                        .replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');
                };

                const getDynamicGreeting = () => {
                    if (!settings.value.features?.dynamic_greeting) return null;
                    const h = new Date().getHours();
                    if (h < 6) return "Доброй ночи! ✨";
                    if (h < 12) return "Доброе утро! ☀️";
                    if (h < 18) return "Добрый день! 👋";
                    return "Добрый вечер! 🌙";
                };

                const toggleVoice = () => {
                    const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (!SR) return alert("Браузер не поддерживает.");
                    const rec = new SR(); rec.lang = 'ru-RU';
                    rec.onstart = () => isRecording.value = true;
                    rec.onresult = (e) => { userInput.value = e.results[0][0].transcript; sendMessage(); };
                    rec.onend = () => isRecording.value = false;
                    rec.start();
                };

                onMounted(fetchSettings);
                return { isOpen, settings, messages, userInput, isLoading, messagesContainer, isRecording, hasUnread, userIdentified, selectedDepartment, userData, containerStyle, isDark, toggleChat, sendMessage, uploadFile, exportChat, identifyUser, selectDepartment, handleScroll, sendQuickReply, rateMessage, parseRichText, getDynamicGreeting, toggleVoice, rootPath };
            }
        }).mount('#chat-widget-loader');
    }
})();
