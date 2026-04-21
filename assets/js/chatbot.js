const { createApp, ref, onMounted, nextTick } = Vue;

createApp({
    setup() {
        const isOpen = ref(false);
        const settings = ref({});
        const messages = ref([]);
        const userInput = ref('');
        const isLoading = ref(false);
        const messagesContainer = ref(null);

        const fetchSettings = async () => {
            try {
                const response = await fetch('api/settings.php');
                settings.value = await response.json();

                // Add welcome message
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

            messages.value.push({
                text: text,
                isBot: false
            });

            isLoading.value = true;
            scrollToBottom();

            try {
                const response = await fetch('api/chat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: text })
                });

                const data = await response.json();

                messages.value.push({
                    text: data.answer,
                    isBot: true,
                    fallback: data.is_fallback ? {
                        text: data.button_text,
                        phone: data.phone
                    } : null
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
            sendMessage,
            toggleChat
        };
    }
}).mount('#chat-widget-container');
