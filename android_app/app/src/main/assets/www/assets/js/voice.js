/**
 * Voice Engine & Speech Parser Module
 * Handles Web Speech API Dictation, Intelligent Voice Command Parsing,
 * MediaRecorder Audio Memos, and Text-To-Speech (TTS).
 */

class VoiceEngine {
    constructor() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        this.recognitionSupported = !!SpeechRecognition;
        this.recognition = this.recognitionSupported ? new SpeechRecognition() : null;

        if (this.recognition) {
            this.recognition.continuous = true;
            this.recognition.interimResults = true;
            this.recognition.lang = 'ru-RU';
        }

        this.mediaRecorder = null;
        this.audioChunks = [];
        this.isRecordingAudio = false;
        this.currentLang = 'ru-RU';
    }

    setLanguage(lang) {
        this.currentLang = lang;
        if (this.recognition) {
            this.recognition.lang = lang;
        }
    }

    startDictation(onResult, onError, onEnd) {
        if (!this.recognitionSupported) {
            if (onError) onError('Ваш браузер не поддерживает Web Speech API. Введите текст вручную.');
            return false;
        }

        try {
            this.recognition.onresult = (event) => {
                let interimTranscript = '';
                let finalTranscript = '';

                for (let i = event.resultIndex; i < event.results.length; ++i) {
                    if (event.results[i].isFinal) {
                        finalTranscript += event.results[i][0].transcript;
                    } else {
                        interimTranscript += event.results[i][0].transcript;
                    }
                }
                if (onResult) onResult({ final: finalTranscript, interim: interimTranscript });
            };

            this.recognition.onerror = (event) => {
                console.warn('Speech recognition error', event.error);
                if (onError) onError(event.error);
            };

            this.recognition.onend = () => {
                if (onEnd) onEnd();
            };

            this.recognition.start();
            return true;
        } catch (e) {
            console.error(e);
            if (onError) onError(e.message);
            return false;
        }
    }

    stopDictation() {
        if (this.recognition) {
            try {
                this.recognition.stop();
            } catch (e) {}
        }
    }

    /**
     * Smart Command Parser for Voice Inputs
     * Extracts target action (task, note, habit, planner), date, time, and priority from speech text.
     */
    parseVoiceCommand(text) {
        if (!text) return null;
        const lower = text.toLowerCase().trim();
        let targetType = 'task'; // Default action

        // Command Intent Detection
        if (lower.startsWith('заметка') || lower.startsWith('запиши') || lower.includes('создай заметку') || lower.includes('блокнот')) {
            targetType = 'note';
        } else if (lower.startsWith('событие') || lower.includes('в расписание') || lower.includes('в план') || lower.includes('ежедневник')) {
            targetType = 'planner';
        } else if (lower.startsWith('привычка') || lower.includes('трекер')) {
            targetType = 'habit';
        }

        // Date extraction
        let dueDate = null;
        const todayObj = new Date();

        if (lower.includes('сегодня')) {
            dueDate = todayObj.toISOString().split('T')[0];
        } else if (lower.includes('завтра')) {
            const tmrw = new Date();
            tmrw.setDate(todayObj.getDate() + 1);
            dueDate = tmrw.toISOString().split('T')[0];
        } else if (lower.includes('послезавтра')) {
            const dayAfter = new Date();
            dayAfter.setDate(todayObj.getDate() + 2);
            dueDate = dayAfter.toISOString().split('T')[0];
        } else if (lower.includes('в понедельник')) {
            dueDate = this._getNextWeekdayDate(1);
        } else if (lower.includes('во вторник')) {
            dueDate = this._getNextWeekdayDate(2);
        } else if (lower.includes('в среду')) {
            dueDate = this._getNextWeekdayDate(3);
        } else if (lower.includes('в четверг')) {
            dueDate = this._getNextWeekdayDate(4);
        } else if (lower.includes('в пятницу')) {
            dueDate = this._getNextWeekdayDate(5);
        } else if (lower.includes('в субботу')) {
            dueDate = this._getNextWeekdayDate(6);
        } else if (lower.includes('в воскресенье')) {
            dueDate = this._getNextWeekdayDate(0);
        }

        // Time extraction (e.g. "в 15:00", "в 9 утра", "в 18 часов")
        let dueTime = null;
        const timeMatch = lower.match(/в\s+(\d{1,2})(?::(\d{2}))?\s*(утра|вечера|часов)?/i);
        if (timeMatch) {
            let hour = parseInt(timeMatch[1], 10);
            let min = timeMatch[2] ? parseInt(timeMatch[2], 10) : 0;
            const period = timeMatch[3] ? timeMatch[3].toLowerCase() : '';

            if (period === 'вечера' && hour < 12) hour += 12;
            if (period === 'утра' && hour === 12) hour = 0;

            dueTime = `${String(hour).padStart(2, '0')}:${String(min).padStart(2, '0')}`;
        }

        // Priority extraction
        let priority = 'medium';
        if (lower.includes('срочно') || lower.includes('важно') || lower.includes('высокий приоритет')) {
            priority = 'urgent';
        } else if (lower.includes('низкий приоритет') || lower.includes('не срочно')) {
            priority = 'low';
        }

        // Clean action keywords from main title string
        let cleanedTitle = text
            .replace(/^(напомнить|напомни|создай задачу|добавь задачу|заметка|запиши|создай заметку|событие|в расписание|в план)/i, '')
            .replace(/(сегодня|завтра|послезавтра|в понедельник|во вторник|в среду|в четверг|в пятницу|в субботу|в воскресенье)/gi, '')
            .replace(/в\s+\d{1,2}(:\d{2})?\s*(утра|вечера|часов)?/gi, '')
            .replace(/(срочно|важно|высокий приоритет|низкий приоритет|не срочно)/gi, '')
            .trim();

        if (!cleanedTitle) cleanedTitle = text;

        return {
            type: targetType,
            title: cleanedTitle,
            date: dueDate,
            time: dueTime,
            priority: priority,
            raw: text
        };
    }

    _getNextWeekdayDate(targetDay) {
        const d = new Date();
        const currentDay = d.getDay();
        let distance = targetDay - currentDay;
        if (distance <= 0) distance += 7;
        d.setDate(d.getDate() + distance);
        return d.toISOString().split('T')[0];
    }

    /**
     * Start recording audio voice clip memo via MediaRecorder
     */
    async startAudioRecording() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error('Запись аудио не поддерживается в данном браузере.');
        }

        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        this.audioChunks = [];
        this.mediaRecorder = new MediaRecorder(stream);

        this.mediaRecorder.ondataavailable = (e) => {
            if (e.data.size > 0) {
                this.audioChunks.push(e.data);
            }
        };

        this.mediaRecorder.start();
        this.isRecordingAudio = true;
    }

    /**
     * Stop audio recording and return audio Blob file
     */
    stopAudioRecording() {
        return new Promise((resolve, reject) => {
            if (!this.mediaRecorder || !this.isRecordingAudio) {
                return reject(new Error('Запись не запущена'));
            }

            this.mediaRecorder.onstop = () => {
                this.isRecordingAudio = false;
                const audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                // Stop audio tracks
                this.mediaRecorder.stream.getTracks().forEach(track => track.stop());
                resolve(audioBlob);
            };

            this.mediaRecorder.stop();
        });
    }

    /**
     * Speak text using SpeechSynthesis (TTS)
     */
    speakText(text) {
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel(); // Stop active speech
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = this.currentLang;
            window.speechSynthesis.speak(utterance);
        }
    }
}

window.voiceEngine = new VoiceEngine();
