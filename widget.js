(function () {
    // Determine backend base url based on widget.js location
    const scripts = document.getElementsByTagName('script');
    let baseUrl = '';
    for (let i = 0; i < scripts.length; i++) {
        if (scripts[i].src && scripts[i].src.indexOf('widget.js') !== -1) {
            baseUrl = scripts[i].src.replace('widget.js', '');
            break;
        }
    }
    if (!baseUrl) {
        baseUrl = window.location.origin + window.location.pathname.replace('index.php', '');
    }

    // Generate unique session identifier per user
    let session_id = localStorage.getItem('wes_chat_session');
    if (!session_id) {
        session_id = 'sess_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        localStorage.setItem('wes_chat_session', session_id);
    }

    // Fetch dynamic configurations
    fetch(baseUrl + 'api.php?action=widget_config')
        .then(res => res.json())
        .then(config => {
            if (!config.widget_enabled) {
                console.log('WES.BOT widget is disabled globally.');
                return;
            }
            initializeWidget(config, baseUrl, session_id);
        })
        .catch(err => {
            console.error('Failed to load WES.BOT configuration', err);
        });

    function initializeWidget(config, baseUrl, session_id) {
        // Create widget container element on main DOM
        const container = document.createElement('div');
        container.id = 'wes-bot-widget-root';
        container.style.position = 'fixed';
        container.style.zIndex = '999999';

        // Apply placement coords from configuration
        applyContainerPosition(container, config);

        document.body.appendChild(container);

        // Attach Shadow DOM for encapsulation
        const shadow = container.attachShadow({ mode: 'open' });

        // Include FontAwesome & Google Fonts
        const faLink = document.createElement('link');
        faLink.rel = 'stylesheet';
        faLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
        shadow.appendChild(faLink);

        const fontLink = document.createElement('link');
        fontLink.rel = 'stylesheet';
        fontLink.href = 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap';
        shadow.appendChild(fontLink);

        // Inject Stylesheet inside Shadow DOM
        const styles = document.createElement('style');
        styles.textContent = `
            :host {
                font-family: 'Plus Jakarta Sans', sans-serif;
                box-sizing: border-box;
            }
            *, *::before, *::after {
                box-sizing: inherit;
            }

            /* Launcher & Badge */
            .launcher-wrapper {
                display: flex;
                align-items: center;
                gap: 12px;
                cursor: pointer;
                user-select: none;
                transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }
            .launcher-wrapper:hover {
                transform: scale(1.05);
            }

            .launcher-btn {
                width: 56px;
                height: 56px;
                border-radius: 50%;
                color: #ffffff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
                position: relative;
                transition: background-color 0.3s;
            }

            .badge-bubble {
                background-color: ${config.widget_badge_bg || '#10b981'};
                color: ${config.widget_badge_color || '#ffffff'};
                padding: 8px 14px;
                border-radius: 16px;
                border-bottom-right-radius: 4px;
                font-size: 11px;
                font-weight: 700;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                max-width: 200px;
                white-space: normal;
                word-wrap: break-word;
                line-height: 1.4;
            }

            /* Badge Animations */
            .anim-pulse {
                animation: pulse-ring 2s infinite;
            }
            .anim-bounce {
                animation: bounce-badge 2s infinite;
            }
            .anim-slide-in {
                animation: slide-in-badge 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }

            @keyframes pulse-ring {
                0% { transform: scale(0.98); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
                70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
                100% { transform: scale(0.98); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
            }
            @keyframes bounce-badge {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-8px); }
            }
            @keyframes slide-in-badge {
                0% { opacity: 0; transform: translateX(20px); }
                100% { opacity: 1; transform: translateX(0); }
            }

            /* Main Chat Panel */
            .chat-window {
                position: absolute;
                bottom: 72px;
                right: 0;
                width: 360px;
                height: 520px;
                background-color: #ffffff;
                border-radius: 20px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
                display: none;
                flex-direction: column;
                overflow: hidden;
                border: 1px border-slate-100;
                animation: pop-in 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }

            /* Positions handling inside widget */
            .pos-left .chat-window {
                left: 0;
                right: auto;
            }
            .pos-center .chat-window {
                left: 50%;
                transform: translateX(-50%);
                right: auto;
            }
            .pos-top .chat-window {
                top: 72px;
                bottom: auto;
            }

            @keyframes pop-in {
                0% { opacity: 0; transform: scale(0.8) translateY(30px); }
                100% { opacity: 1; transform: scale(1) translateY(0); }
            }

            /* Header */
            .chat-header {
                padding: 16px;
                color: #ffffff;
                display: flex;
                align-items: center;
                justify-content: space-between;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            }
            .chat-header-info {
                display: flex;
                align-items: center;
                gap: 10px;
                min-w-0;
            }
            .chat-header-avatar {
                width: 38px;
                height: 38px;
                border-radius: 50%;
                background-color: rgba(255,255,255,0.2);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 18px;
            }
            .chat-header-text {
                min-w-0;
            }
            .chat-header-title {
                font-weight: 700;
                font-size: 14px;
                margin: 0;
                padding: 0;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .chat-header-subtitle {
                font-size: 10px;
                margin: 2px 0 0 0;
                padding: 0;
                opacity: 0.85;
                font-weight: 600;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .chat-close-btn {
                background: none;
                border: none;
                color: #ffffff;
                cursor: pointer;
                font-size: 16px;
                opacity: 0.8;
                padding: 4px;
                transition: opacity 0.2s;
            }
            .chat-close-btn:hover {
                opacity: 1;
            }

            /* Message Body content */
            .chat-body {
                flex: 1;
                padding: 16px;
                overflow-y: auto;
                background-color: ${config.chat_bg_color || '#f8fafc'};
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            /* Custom scrollbar */
            .chat-body::-webkit-scrollbar {
                width: 5px;
            }
            .chat-body::-webkit-scrollbar-track {
                background: #f1f5f9;
            }
            .chat-body::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 3px;
            }

            .msg {
                display: flex;
                align-items: flex-end;
                gap: 8px;
                max-width: 85%;
                animation: fade-up 0.3s ease;
            }
            .msg-bot {
                align-self: flex-start;
            }
            .msg-user {
                align-self: flex-end;
                flex-direction: row-reverse;
                max-width: 80%;
            }

            @keyframes fade-up {
                0% { opacity: 0; transform: translateY(10px); }
                100% { opacity: 1; transform: translateY(0); }
            }

            .msg-avatar {
                width: 24px;
                height: 24px;
                border-radius: 50%;
                background-color: ${config.widget_color || '#2563eb'};
                color: #ffffff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 11px;
                flex-shrink: 0;
            }
            .msg-bubble {
                padding: 10px 14px;
                border-radius: 16px;
                font-size: 12px;
                line-height: 1.4;
                font-weight: 500;
                word-break: break-word;
            }
            .msg-bot .msg-bubble {
                background-color: ${config.bot_bubble_bg || '#ffffff'};
                color: ${config.bot_bubble_color || '#1e293b'};
                border: 1px solid #e2e8f0;
                border-bottom-left-radius: 4px;
            }
            .msg-user .msg-bubble {
                background-color: ${config.user_bubble_bg || config.widget_color || '#2563eb'};
                color: ${config.user_bubble_color || '#ffffff'};
                border-bottom-right-radius: 4px;
            }

            /* Typing Animation indicator */
            .typing-indicator {
                display: none;
                align-self: flex-start;
                align-items: center;
                gap: 4px;
                background-color: #ffffff;
                border: 1px solid #e2e8f0;
                padding: 8px 12px;
                border-radius: 16px;
                border-bottom-left-radius: 4px;
            }
            .typing-dot {
                width: 6px;
                height: 6px;
                background-color: #94a3b8;
                border-radius: 50%;
                animation: typing-bounce 1.4s infinite ease-in-out both;
            }
            .typing-dot:nth-child(1) { animation-delay: -0.32s; }
            .typing-dot:nth-child(2) { animation-delay: -0.16s; }

            @keyframes typing-bounce {
                0%, 80%, 100% { transform: scale(0); }
                40% { transform: scale(1.0); }
            }

            /* Interactive custom forms rendering */
            .form-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 14px;
                width: 100%;
                margin-top: 4px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.03);
            }
            .form-card-title {
                font-size: 12px;
                font-weight: 800;
                color: #0f172a;
                margin-bottom: 10px;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .form-group {
                margin-bottom: 8px;
            }
            .form-label {
                display: block;
                font-size: 10px;
                font-weight: 700;
                color: #475569;
                margin-bottom: 4px;
            }
            .form-input {
                width: 100%;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                padding: 6px 10px;
                font-size: 11px;
                font-weight: 500;
                outline: none;
                transition: border-color 0.2s;
            }
            .form-input:focus {
                border-color: ${config.widget_color || '#2563eb'};
            }
            .form-submit-btn {
                width: 100%;
                background-color: ${config.widget_color || '#2563eb'};
                color: #ffffff;
                border: none;
                padding: 8px;
                border-radius: 8px;
                font-size: 11px;
                font-weight: 700;
                cursor: pointer;
                transition: opacity 0.2s;
            }
            .form-submit-btn:hover {
                opacity: 0.9;
            }

            /* Inline Quick action Buttons */
            .quick-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                margin-top: 4px;
                align-self: flex-start;
                max-width: 90%;
            }
            .quick-btn {
                background-color: #ffffff;
                border: 1px solid ${config.widget_color || '#2563eb'};
                color: ${config.widget_color || '#2563eb'};
                font-size: 11px;
                font-weight: 700;
                padding: 6px 12px;
                border-radius: 12px;
                cursor: pointer;
                transition: all 0.2s;
            }
            .quick-btn:hover {
                background-color: ${config.widget_color || '#2563eb'};
                color: #ffffff;
            }

            /* Input Area */
            .chat-footer {
                padding: 12px;
                background-color: #ffffff;
                border-top: 1px solid #f1f5f9;
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .chat-input-row {
                display: flex;
                gap: 8px;
                align-items: center;
            }
            .chat-text-input {
                flex: 1;
                border: 1px solid #cbd5e1;
                border-radius: 12px;
                padding: 8px 12px;
                font-size: 12px;
                outline: none;
                font-weight: 500;
                transition: border-color 0.2s;
            }
            .chat-text-input:focus {
                border-color: ${config.widget_color || '#2563eb'};
            }
            .chat-send-btn {
                width: 34px;
                height: 34px;
                border-radius: 50%;
                background-color: ${config.widget_color || '#2563eb'};
                color: #ffffff;
                border: none;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
                cursor: pointer;
                transition: transform 0.2s, opacity 0.2s;
            }
            .chat-send-btn:hover {
                transform: scale(1.05);
            }

            /* Branding bottom */
            .branding-footer {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 4px;
                font-size: 9px;
                color: #94a3b8;
                font-weight: 700;
                text-decoration: none;
                padding: 2px 0;
            }
            .branding-footer strong {
                color: #475569;
            }
            .branding-footer:hover strong {
                color: ${config.widget_color || '#2563eb'};
            }

            /* Exit Intent popup mockup */
            .exit-popup-mask {
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(15, 23, 42, 0.4);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 99999999;
                animation: fade-in 0.25s ease;
            }
            .exit-popup-card {
                background: #ffffff;
                border-radius: 20px;
                padding: 24px;
                width: 320px;
                box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
                text-align: center;
                animation: pop-in 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            }

            /* Star rating styles */
            .rating-row {
                display: flex;
                justify-content: center;
                gap: 6px;
                margin-top: 8px;
            }
            .star-icon {
                font-size: 18px;
                color: #cbd5e1;
                cursor: pointer;
                transition: color 0.15s;
            }
            .star-icon.active {
                color: #fbbf24;
            }

            /* Responsive layout for mobile devices */
            @media (max-width: 480px) {
                .chat-window {
                    width: calc(100vw - 32px);
                    height: calc(100vh - 100px);
                    bottom: 72px;
                    right: 0px;
                }
                .pos-left .chat-window {
                    left: 0px;
                }
                .pos-center .chat-window {
                    left: 50%;
                }
                .pos-top .chat-window {
                    top: 10px;
                    bottom: auto;
                    height: calc(100vh - 100px);
                }
            }

            /* Custom styling injection override */
            ${config.custom_css || ''}
        `;
        shadow.appendChild(styles);

        // Core HTML elements structuring
        const launcherWrapper = document.createElement('div');
        launcherWrapper.className = `launcher-wrapper pos-${config.widget_position}`;

        // Horizontal orientation flipping
        if (config.widget_position.indexOf('left') !== -1) {
            launcherWrapper.style.flexDirection = 'row';
        } else {
            launcherWrapper.style.flexDirection = 'row-reverse';
        }

        // Launcher Button
        const launcherBtn = document.createElement('div');
        launcherBtn.className = 'launcher-btn';
        launcherBtn.style.backgroundColor = config.widget_color || '#2563eb';

        let launcherIcon = null;
        if (config.widget_avatar_url) {
            launcherBtn.style.overflow = 'hidden';
            launcherBtn.innerHTML = `<img src="${config.widget_avatar_url}" style="width:100%;height:100%;object-fit:cover;">`;
        } else {
            launcherIcon = document.createElement('i');
            launcherIcon.className = getIconClassName(config.widget_icon);
            launcherBtn.appendChild(launcherIcon);
        }

        // Text Badge bubble beside button
        let badgeBubble = null;
        if (config.widget_badge_text) {
            badgeBubble = document.createElement('div');
            badgeBubble.className = `badge-bubble ${config.widget_badge_animation !== 'none' ? 'anim-' + config.widget_badge_animation : ''}`;
            badgeBubble.textContent = config.widget_badge_text;
            launcherWrapper.appendChild(badgeBubble);
        }
        launcherWrapper.appendChild(launcherBtn);
        shadow.appendChild(launcherWrapper);

        // Dialogue Window Shell
        const chatWindow = document.createElement('div');
        chatWindow.className = 'chat-window';

        // Top Bar Header
        const chatHeader = document.createElement('div');
        chatHeader.className = 'chat-header';
        chatHeader.style.backgroundColor = config.widget_color || '#2563eb';

        const headerInfo = document.createElement('div');
        headerInfo.className = 'chat-header-info';

        const avatar = document.createElement('div');
        avatar.className = 'chat-header-avatar';
        const avIcon = document.createElement('i');
        avIcon.className = 'fa-solid fa-robot';
        avatar.appendChild(avIcon);

        const headerText = document.createElement('div');
        headerText.className = 'chat-header-text';

        const titleH4 = document.createElement('h4');
        titleH4.className = 'chat-header-title';
        titleH4.textContent = config.widget_title;

        const subtitleP = document.createElement('p');
        subtitleP.className = 'chat-header-subtitle';
        subtitleP.textContent = config.widget_subtitle;

        headerText.appendChild(titleH4);
        headerText.appendChild(subtitleP);
        headerInfo.appendChild(avatar);
        headerInfo.appendChild(headerText);

        const closeBtn = document.createElement('button');
        closeBtn.className = 'chat-close-btn';
        closeBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';

        chatHeader.appendChild(headerInfo);
        chatHeader.appendChild(closeBtn);
        chatWindow.appendChild(chatHeader);

        // Messages Box Body
        const chatBody = document.createElement('div');
        chatBody.className = 'chat-body';
        chatWindow.appendChild(chatBody);

        // Typing dot animation block
        const typingIndicator = document.createElement('div');
        typingIndicator.className = 'typing-indicator';
        typingIndicator.innerHTML = '<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>';
        chatBody.appendChild(typingIndicator);

        // Footer block
        const chatFooter = document.createElement('div');
        chatFooter.className = 'chat-footer';

        const inputRow = document.createElement('div');
        inputRow.className = 'chat-input-row';

        const textInput = document.createElement('input');
        textInput.type = 'text';
        textInput.className = 'chat-text-input';
        textInput.placeholder = 'Введите ваш вопрос...';

        const sendBtn = document.createElement('button');
        sendBtn.className = 'chat-send-btn';
        sendBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';

        inputRow.appendChild(textInput);
        inputRow.appendChild(sendBtn);
        chatFooter.appendChild(inputRow);

        // Branding Credit Tag (WES.BY)
        const brandTag = document.createElement('a');
        brandTag.className = 'branding-footer';
        brandTag.href = 'tel:+375333533971';
        brandTag.target = '_blank';
        brandTag.innerHTML = 'Разработано <strong>WES.BY +375333533971</strong>';
        chatFooter.appendChild(brandTag);

        chatWindow.appendChild(chatFooter);
        shadow.appendChild(chatWindow);

        // Interactive Event Triggers
        let isOpened = false;
        let deadEndCount = 0;

        // Toggle opened state on launcher and close clicks
        launcherWrapper.addEventListener('click', (e) => {
            if (badgeBubble && badgeBubble.contains(e.target)) {
                // Clicking the bubble can also open it
            }
            toggleChat();
        });
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleChat();
        });

        // Send triggers
        sendBtn.addEventListener('click', handleSendMessage);
        textInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                handleSendMessage();
            }
        });

        // Initialize first welcoming greetings from bot
        appendBotGreeting();

        // Audio notification synthesizer trigger
        function playAudioAlert() {
            if (!config.sound_enabled) return;
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();

                const sound_type = config.sound_type || 'synth';
                osc.connect(gain);
                gain.connect(ctx.destination);

                if (sound_type === 'synth') {
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
                    osc.frequency.setValueAtTime(880.00, ctx.currentTime + 0.1); // A5
                    gain.gain.setValueAtTime(0.05, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.35);
                } else if (sound_type === 'alert') {
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(440.00, ctx.currentTime); // A4
                    osc.frequency.setValueAtTime(523.25, ctx.currentTime + 0.08); // C5
                    osc.frequency.setValueAtTime(659.25, ctx.currentTime + 0.16); // E5
                    gain.gain.setValueAtTime(0.06, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.4);
                } else if (sound_type === 'chime') {
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(987.77, ctx.currentTime); // B5
                    osc.frequency.setValueAtTime(1318.51, ctx.currentTime + 0.06); // E6
                    gain.gain.setValueAtTime(0.04, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.5);
                }
            } catch (e) {
                // Ignore silent browser restrictions
            }
        }

        function toggleChat() {
            isOpened = !isOpened;
            if (isOpened) {
                chatWindow.style.display = 'flex';
                textInput.focus();
                // Hide badge on open
                if (badgeBubble) badgeBubble.style.display = 'none';
                if (config.widget_avatar_url) {
                    launcherBtn.innerHTML = '<i class="fa-solid fa-chevron-down"></i>';
                } else {
                    if (launcherIcon) launcherIcon.className = 'fa-solid fa-chevron-down';
                }
            } else {
                chatWindow.style.display = 'none';
                if (badgeBubble) badgeBubble.style.display = 'block';
                if (config.widget_avatar_url) {
                    launcherBtn.innerHTML = `<img src="${config.widget_avatar_url}" style="width:100%;height:100%;object-fit:cover;">`;
                } else {
                    if (launcherIcon) launcherIcon.className = getIconClassName(config.widget_icon);
                }

                // Show Rating stars if enabled
                if (config.chat_rating_enabled) {
                    showStarsFeedback();
                }
            }
        }

        // Output first message with organic character-typing effect
        function appendBotGreeting() {
            const greetingText = config.extra_greetings || "Здравствуйте! Как я могу вам помочь?";
            showTypingAndReply(greetingText, false);
        }

        function showTypingAndReply(text, isDeadEnd, formIdToTrigger = null) {
            typingIndicator.style.display = 'flex';
            chatBody.scrollTop = chatBody.scrollHeight;

            // Character velocity multiplier
            const charCount = text.length;
            const typingDuration = Math.min(Math.max(charCount * (config.typing_speed || 20), 400), 2500);

            setTimeout(() => {
                typingIndicator.style.display = 'none';

                // Build Bot message Bubble
                const msgWrap = document.createElement('div');
                msgWrap.className = 'msg msg-bot';

                const av = document.createElement('div');
                av.className = 'msg-avatar';
                if (config.widget_avatar_url) {
                    av.style.overflow = 'hidden';
                    av.innerHTML = `<img src="${config.widget_avatar_url}" style="width:100%;height:100%;object-fit:cover;">`;
                } else {
                    av.innerHTML = '<i class="fa-solid fa-robot"></i>';
                }

                const bubble = document.createElement('div');
                bubble.className = 'msg-bubble';
                bubble.innerHTML = text.replace(/\n/g, '<br>');

                msgWrap.appendChild(av);
                msgWrap.appendChild(bubble);
                chatBody.insertBefore(msgWrap, typingIndicator);
                chatBody.scrollTop = chatBody.scrollHeight;

                playAudioAlert();

                // If anti-dead end form triggers
                if (isDeadEnd || formIdToTrigger) {
                    const fid = formIdToTrigger || config.fallback_form_id || 'feedback';
                    appendInlineForm(fid);
                } else {
                    // Show standard helper Quick Answer triggers if start or contacts
                    if (charCount > 10 && !isDeadEnd) {
                        appendQuickReplies();
                    }
                }
            }, typingDuration);
        }

        // Output quick-button topics
        function appendQuickReplies() {
            // Remove existing quicks to prevent duplicate clusters
            const oldQuicks = chatBody.querySelectorAll('.quick-actions');
            oldQuicks.forEach(q => q.remove());

            const quickRow = document.createElement('div');
            quickRow.className = 'quick-actions';

            const topics = [
                { title: '📞 Контакты', msg: 'Какие у вас контакты?' },
                { title: '⏰ График работы', msg: 'Какой у вас график работы?' },
                { title: '📝 Оставить заявку', form: 'feedback' },
                { title: '🗓 Запись на прием', form: 'booking' }
            ];

            topics.forEach(t => {
                const btn = document.createElement('button');
                btn.className = 'quick-btn';
                btn.textContent = t.title;
                btn.addEventListener('click', () => {
                    if (t.form) {
                        appendInlineForm(t.form);
                    } else {
                        textInput.value = t.msg;
                        handleSendMessage();
                    }
                });
                quickRow.appendChild(btn);
            });

            chatBody.insertBefore(quickRow, typingIndicator);
            chatBody.scrollTop = chatBody.scrollHeight;
        }

        // Custom visual form constructor engine built directly inline
        function appendInlineForm(formId) {
            // Remove older forms if any to avoid confusion
            const oldForms = chatBody.querySelectorAll('.form-card');
            oldForms.forEach(f => f.remove());

            // Seek form schema from configurations
            const formSchema = config.forms.find(f => f.id === formId) || config.forms[0];
            if (!formSchema) return;

            const card = document.createElement('div');
            card.className = 'form-card';

            const cardTitle = document.createElement('div');
            cardTitle.className = 'form-card-title';
            cardTitle.innerHTML = `<i class="fa-solid fa-clipboard-question" style="color:${config.widget_color}"></i> ${formSchema.title}`;
            card.appendChild(cardTitle);

            const formObj = document.createElement('form');
            const fieldsRefs = {};

            formSchema.fields.forEach(f => {
                const group = document.createElement('div');
                group.className = 'form-group';

                const label = document.createElement('label');
                label.className = 'form-label';
                label.textContent = f.label + (f.required ? ' *' : '');

                let input = null;
                if (f.type === 'textarea') {
                    input = document.createElement('textarea');
                    input.rows = 2;
                } else {
                    input = document.createElement('input');
                    input.type = f.type;
                }

                input.className = 'form-input';
                input.required = f.required;

                group.appendChild(label);
                group.appendChild(input);
                formObj.appendChild(group);

                fieldsRefs[f.label] = input;
            });

            const submitBtn = document.createElement('button');
            submitBtn.type = 'submit';
            submitBtn.className = 'form-submit-btn';
            submitBtn.textContent = 'Отправить заявку';
            formObj.appendChild(submitBtn);

            card.appendChild(formObj);
            chatBody.insertBefore(card, typingIndicator);
            chatBody.scrollTop = chatBody.scrollHeight;

            formObj.addEventListener('submit', (e) => {
                e.preventDefault();
                submitBtn.disabled = true;
                submitBtn.textContent = 'Отправка...';

                // Extract form field data
                const payloadFields = {};
                for (const lbl in fieldsRefs) {
                    payloadFields[lbl] = fieldsRefs[lbl].value;
                }

                // AJAX submit request
                fetch(baseUrl + 'api.php?action=submit_form', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        form_id: formId,
                        session_id: session_id,
                        fields: payloadFields
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        card.innerHTML = `<div style="text-align:center;color:#10b981;font-weight:700;font-size:12px;padding:10px 0;">
                            <i class="fa-solid fa-circle-check" style="font-size:24px;margin-bottom:6px;"></i><br>
                            ${data.message}
                        </div>`;
                        playAudioAlert();
                    } else {
                        alert(data.error || 'Ошибка отправки');
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Отправить заявку';
                    }
                    chatBody.scrollTop = chatBody.scrollHeight;
                })
                .catch(() => {
                    alert('Ошибка сети при отправке формы');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Отправить заявку';
                });
            });
        }

        // Send Text Message Event
        function handleSendMessage() {
            const userMsg = textInput.value.trim();
            if (!userMsg) return;

            textInput.value = '';

            // Render User Bubble immediately
            const msgWrap = document.createElement('div');
            msgWrap.className = 'msg msg-user';

            const bubble = document.createElement('div');
            bubble.className = 'msg-bubble';
            bubble.textContent = userMsg;
            msgWrap.appendChild(bubble);
            chatBody.insertBefore(msgWrap, typingIndicator);
            chatBody.scrollTop = chatBody.scrollHeight;

            // Call AJAX messaging API
            fetch(baseUrl + 'api.php?action=send_message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_id: session_id,
                    message: userMsg,
                    dead_end_count: deadEndCount
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.dead_end) {
                    deadEndCount = 5; // Lock to trigger forms
                } else if (data.dead_end_count !== undefined) {
                    deadEndCount = data.dead_end_count;
                }

                showTypingAndReply(data.reply, data.dead_end, data.trigger_form);

                // Execute Smart command script triggers if returned
                if (data.smart_action) {
                    setTimeout(() => {
                        if (data.smart_action === 'open_url') {
                            window.open(data.smart_payload, '_blank');
                        } else if (data.smart_action === 'alert') {
                            alert(data.smart_payload);
                        }
                    }, 1000);
                }
            })
            .catch(err => {
                console.error('Messaging server error', err);
                showTypingAndReply('Простите, возникла сетевая ошибка при связи с сервером. Попробуйте написать позже.', false);
            });
        }

        // Dialogue satisfaction scale rating pop-up
        function showStarsFeedback() {
            // Only suggest once per session
            if (sessionStorage.getItem('wes_chat_rated')) return;

            const ratingCard = document.createElement('div');
            ratingCard.className = 'form-card';
            ratingCard.style.position = 'absolute';
            ratingCard.style.bottom = '10px';
            ratingCard.style.left = '16px';
            ratingCard.style.right = '16px';
            ratingCard.style.width = 'auto';
            ratingCard.style.zIndex = '99999';

            ratingCard.innerHTML = `
                <div class="form-card-title" style="justify-content:center;">⭐ ${config.chat_rating_text || 'Оцените нашу работу!'}</div>
                <div class="rating-row">
                    <i class="fa-solid fa-star star-icon" data-val="1"></i>
                    <i class="fa-solid fa-star star-icon" data-val="2"></i>
                    <i class="fa-solid fa-star star-icon" data-val="3"></i>
                    <i class="fa-solid fa-star star-icon" data-val="4"></i>
                    <i class="fa-solid fa-star star-icon" data-val="5"></i>
                </div>
            `;
            chatBody.appendChild(ratingCard);
            chatBody.scrollTop = chatBody.scrollHeight;

            const stars = ratingCard.querySelectorAll('.star-icon');
            stars.forEach(st => {
                st.addEventListener('mouseover', () => {
                    const val = parseInt(st.getAttribute('data-val'));
                    stars.forEach((s, idx) => {
                        if (idx < val) s.classList.add('active');
                        else s.classList.remove('active');
                    });
                });
                st.addEventListener('click', () => {
                    sessionStorage.setItem('wes_chat_rated', 'true');
                    ratingCard.innerHTML = `<div style="color:#10b981;font-weight:700;font-size:11px;text-align:center;padding:5px;">Спасибо за вашу оценку!</div>`;
                    setTimeout(() => { ratingCard.remove(); }, 2000);
                });
            });
        }

        // Exit intent integration popup triggers
        if (config.exit_intent_enabled) {
            document.addEventListener('mouseleave', handleExitIntent);
        }

        function handleExitIntent(e) {
            if (e.clientY < 50) { // Moving mouse to the upper URL bar area
                // Only trigger once to avoid annoying spam
                document.removeEventListener('mouseleave', handleExitIntent);
                if (!isOpened) {
                    toggleChat();
                    showTypingAndReply('Подождите! Не уходите с пустыми руками. 😊 Оставьте нам вопрос или контактные данные, и наш менеджер свяжется с вами с лучшим предложением!', false, 'feedback');
                }
            }
        }
    }

    // Positions calculator helper
    function applyContainerPosition(container, config) {
        const offset_x = (config.widget_offset_x || 20) + 'px';
        const offset_y = (config.widget_offset_y || 20) + 'px';

        switch (config.widget_position) {
            case 'bottom-right':
                container.style.bottom = offset_y;
                container.style.right = offset_x;
                break;
            case 'bottom-left':
                container.style.bottom = offset_y;
                container.style.left = offset_x;
                break;
            case 'bottom-center':
                container.style.bottom = offset_y;
                container.style.left = '50%';
                container.style.transform = 'translateX(-50%)';
                break;
            case 'top-right':
                container.style.top = offset_y;
                container.style.right = offset_x;
                break;
            case 'top-left':
                container.style.top = offset_y;
                container.style.left = offset_x;
                break;
            default:
                container.style.bottom = '20px';
                container.style.right = '20px';
        }
    }

    function getIconClassName(key) {
        switch (key) {
            case 'support': return 'fa-solid fa-headset';
            case 'bot': return 'fa-solid fa-robot';
            case 'wave': return 'fa-solid fa-hand-peace';
            default: return 'fa-solid fa-comment-dots';
        }
    }
})();
