const { createApp, ref, computed, onMounted, nextTick, watch } = Vue;

createApp({
  setup() {
    // Authentication
    const authorized = ref(false);
    const pinCode = ref("");
    const pinError = ref(false);
    const userId = ref("");
    const userName = ref("");

    // Navigation & Theme
    const currentTab = ref("dashboard");
    const theme = ref(localStorage.getItem("pwa_theme") || "light");
    const isOffline = ref(!navigator.onLine);

    // Dynamic Collections
    const notes = ref([]);
    const tasks = ref([]);
    const discussions = ref([]);
    const systemCategories = ref(["Личное", "Работа", "Учеба", "Идеи", "Другое"]);

    // Filters & Searches
    const noteQuery = ref("");
    const noteCategoryFilter = ref("");
    const taskQuery = ref("");
    const taskStatusFilter = ref("pending");

    // Dialog Modals
    const noteModalOpen = ref(false);
    const taskModalOpen = ref(false);
    const editingNote = ref({ title: "", content: "", category: "Личное", tags: [], priority: "Medium", shared: false });
    const editingNoteTagsString = ref("");
    const editingTask = ref({ title: "", description: "", category: "Личное", tags: [], priority: "Medium", due_date: "", reminder_time: "", repeat_interval: "none", status: "pending" });
    const editingTaskCompleted = ref(false);

    // Collaboration Chat
    const selectedDiscussionNoteId = ref("");
    const discussionMessages = ref([]);
    const newMessageText = ref("");
    const audioConferenceActive = ref(false);

    // Local Sync Queue
    const syncQueue = ref(JSON.parse(localStorage.getItem("pwa_sync_queue") || "[]"));
    const syncPending = ref(false);

    // Calendar
    const currentYear = ref(new Date().getFullYear());
    const currentMonth = ref(new Date().getMonth());
    const selectedDate = ref(new Date().toISOString().split('T')[0]);

    // Audio Voice Note Recording State
    const recording = ref(false);
    const recordingSeconds = ref(0);
    const voiceBlobUrl = ref(null);
    let mediaRecorder = null;
    let audioChunks = [];
    let recordingInterval = null;
    let localVoiceBlob = null;

    // Charts
    let chartInstance = null;

    // Computed Counts & Lists
    const activeTasksCount = computed(() => tasks.value.filter(t => t.status === 'pending').length);
    const completedTasksCount = computed(() => tasks.value.filter(t => t.status === 'completed').length);
    const completionRate = computed(() => {
      if (tasks.value.length === 0) return 0;
      return Math.round((completedTasksCount.value / tasks.value.length) * 100);
    });

    const todayFormatted = computed(() => {
      return new Date().toLocaleDateString('ru-RU', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    });

    const todayTasks = computed(() => {
      const todayStr = new Date().toISOString().split('T')[0];
      return tasks.value.filter(t => t.due_date === todayStr);
    });

    const filteredNotes = computed(() => {
      return notes.value.filter(n => {
        const matchesQuery = n.title.toLowerCase().includes(noteQuery.value.toLowerCase()) ||
                             (n.content && n.content.toLowerCase().includes(noteQuery.value.toLowerCase())) ||
                             (n.tags && n.tags.some(t => t.toLowerCase().includes(noteQuery.value.toLowerCase())));
        const matchesCategory = !noteCategoryFilter.value || n.category === noteCategoryFilter.value;
        return matchesQuery && matchesCategory;
      });
    });

    const filteredTasks = computed(() => {
      return tasks.value.filter(t => {
        const matchesQuery = t.title.toLowerCase().includes(taskQuery.value.toLowerCase()) ||
                             (t.description && t.description.toLowerCase().includes(taskQuery.value.toLowerCase()));
        const matchesStatus = t.status === taskStatusFilter.value;
        return matchesQuery && matchesStatus;
      });
    });

    const sharedNotes = computed(() => {
      return notes.value.filter(n => n.shared);
    });

    // Calendar Cells Generation
    const monthNamesRu = ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];
    const currentMonthName = computed(() => monthNamesRu[currentMonth.value]);

    const calendarDays = computed(() => {
      const year = currentYear.value;
      const month = currentMonth.value;

      // Get first day of the month
      const firstDayIndex = (new Date(year, month, 1).getDay() + 6) % 7; // Monday-indexed
      const daysInMonth = new Date(year, month + 1, 0).getDate();

      const days = [];

      // Pad previous month days
      const prevMonthDays = new Date(year, month, 0).getDate();
      for (let i = firstDayIndex - 1; i >= 0; i--) {
        const d = new Date(year, month - 1, prevMonthDays - i);
        days.push({
          day: prevMonthDays - i,
          date: d.toISOString().split('T')[0],
          isCurrentMonth: false
        });
      }

      // Current month days
      for (let i = 1; i <= daysInMonth; i++) {
        const d = new Date(year, month, i + 1); // safe timezone offset addition
        days.push({
          day: i,
          date: d.toISOString().split('T')[0],
          isCurrentMonth: true
        });
      }

      // Pad next month days to make complete 42 cells grid
      const remainingCells = 42 - days.length;
      for (let i = 1; i <= remainingCells; i++) {
        const d = new Date(year, month + 1, i + 1);
        days.push({
          day: i,
          date: d.toISOString().split('T')[0],
          isCurrentMonth: false
        });
      }

      return days;
    });

    // --- AUDIO NOTIFICATION SOUND FOR KEYPAD ---
    function playBeep() {
      try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();
        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(440, audioCtx.currentTime); // A4 beep
        gainNode.gain.setValueAtTime(0.05, audioCtx.currentTime);
        oscillator.start();
        setTimeout(() => oscillator.stop(), 80);
      } catch (e) {
        // Audio API not supported/blocked
      }
    }

    // --- PIN AUTHENTICATION INTERACTION ---
    function pressPinDigit(digit) {
      if (pinCode.value.length < 4) {
        pinCode.value += digit;
        playBeep();

        // Haptic feedback
        if (navigator.vibrate) {
          navigator.vibrate(25);
        }
      }

      if (pinCode.value.length === 4) {
        submitLogin();
      }
    }

    function clearPin() {
      pinCode.value = "";
    }

    function deletePinLastDigit() {
      pinCode.value = pinCode.value.slice(0, -1);
    }

    function submitLogin() {
      if (isOffline.value) {
        // Offline local auth check
        const offlineUsers = JSON.parse(localStorage.getItem("pwa_offline_users") || '[{"id":"1","pin":"1234","name":"Коваженко С.Б."}]');
        const user = offlineUsers.find(u => u.pin === pinCode.value);
        if (user) {
          authorized.value = true;
          userId.value = user.id;
          userName.value = user.name;
          loadAllData();
        } else {
          handlePinError();
        }
      } else {
        fetch("/api.php?action=login", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ pin: pinCode.value })
        })
        .then(res => {
          if (res.status === 200) return res.json();
          throw new Error("Invalid Pin");
        })
        .then(data => {
          if (data.success) {
            authorized.value = true;
            userId.value = data.user.id;
            userName.value = data.user.name;
            localStorage.setItem("pwa_token", data.user.id);
            // Save offline user fallback
            localStorage.setItem("pwa_offline_users", JSON.stringify([data.user]));
            loadAllData();
          } else {
            handlePinError();
          }
        })
        .catch(() => {
          handlePinError();
        });
      }
    }

    function handlePinError() {
      pinError.value = true;
      pinCode.value = "";
      if (navigator.vibrate) {
        navigator.vibrate([100, 50, 100]); // double error vibration
      }
      setTimeout(() => {
        pinError.value = false;
      }, 500);
    }

    function checkSession() {
      const storedToken = localStorage.getItem("pwa_token");
      if (storedToken) {
        // Skip calling server if offline
        if (isOffline.value) {
          authorized.value = true;
          userId.value = storedToken;
          userName.value = "Коваженко С.Б.";
          loadAllData();
          return;
        }

        fetch("/api.php?action=check_session")
        .then(res => res.json())
        .then(data => {
          if (data.authorized) {
            authorized.value = true;
            userId.value = data.user.id;
            userName.value = data.user.name;
            loadAllData();
          } else {
            authorized.value = false;
          }
        })
        .catch(() => {
          // fallback if server is unreachable
          authorized.value = true;
          userId.value = storedToken;
          userName.value = "Коваженко С.Б.";
          loadAllData();
        });
      }
    }

    function logout() {
      authorized.value = false;
      localStorage.removeItem("pwa_token");
      if (!isOffline.value) {
        fetch("/api.php?action=logout");
      }
    }

    // --- SYSTEM OPTIONS & CONFIGURATION CHANGE ---
    function submitChangePin() {
      if (!changePinOld.value || !changePinNew.value) {
        alert("Пожалуйста, заполните оба поля PIN-кода.");
        return;
      }
      if (isOffline.value) {
        alert("Смена PIN-кода недоступна в офлайн-режиме.");
        return;
      }

      fetch("/api.php?action=change_pin", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Authorization": "Bearer " + userId.value
        },
        body: JSON.stringify({ old_pin: changePinOld.value, new_pin: changePinNew.value })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert("PIN-код успешно изменен!");
          changePinOld.value = "";
          changePinNew.value = "";
        } else {
          alert("Ошибка: " + data.error);
        }
      });
    }

    const changePinOld = ref("");
    const changePinNew = ref("");
    const notificationsEnabled = ref(true);

    // --- DATA FETCHING & SYNCHRONIZATION ---
    function loadAllData() {
      // Load offline cache first
      notes.value = JSON.parse(localStorage.getItem("pwa_notes") || "[]");
      tasks.value = JSON.parse(localStorage.getItem("pwa_tasks") || "[]");

      if (isOffline.value) {
        nextTick(() => {
          refreshIconsAndCharts();
        });
        return;
      }

      // Fetch fresh data from backend
      const headers = { "Authorization": "Bearer " + userId.value };

      Promise.all([
        fetch("/api.php?action=get_notes", { headers }).then(r => r.json()),
        fetch("/api.php?action=get_tasks", { headers }).then(r => r.json())
      ])
      .then(([notesData, tasksData]) => {
        if (Array.isArray(notesData)) {
          notes.value = notesData;
          localStorage.setItem("pwa_notes", JSON.stringify(notesData));
        }
        if (Array.isArray(tasksData)) {
          tasks.value = tasksData;
          localStorage.setItem("pwa_tasks", JSON.stringify(tasksData));
        }
        nextTick(() => {
          refreshIconsAndCharts();
        });
      })
      .catch(err => {
        console.error("Error fetching remote data", err);
      });
    }

    function syncData() {
      if (isOffline.value) {
        alert("Вы находитесь в офлайн-режиме. Подключитесь к сети для синхронизации.");
        return;
      }

      syncPending.value = true;
      const queue = [...syncQueue.value];

      if (queue.length === 0) {
        loadAllData();
        setTimeout(() => {
          syncPending.value = false;
        }, 600);
        return;
      }

      // Process synchronization queue sequentially
      let promiseChain = Promise.resolve();
      const headers = {
        "Content-Type": "application/json",
        "Authorization": "Bearer " + userId.value
      };

      queue.forEach((item) => {
        promiseChain = promiseChain.then(() => {
          if (item.action === 'save_note') {
            return fetch("/api.php?action=save_note", { method: "POST", headers, body: JSON.stringify(item.data) });
          } else if (item.action === 'delete_note') {
            return fetch(`/api.php?action=delete_note&id=${item.id}`, { method: "DELETE", headers });
          } else if (item.action === 'save_task') {
            return fetch("/api.php?action=save_task", { method: "POST", headers, body: JSON.stringify(item.data) });
          } else if (item.action === 'delete_task') {
            return fetch(`/api.php?action=delete_task&id=${item.id}`, { method: "DELETE", headers });
          }
        });
      });

      promiseChain.then(() => {
        syncQueue.value = [];
        localStorage.setItem("pwa_sync_queue", "[]");
        loadAllData();
        alert("Синхронизация успешно завершена!");
      })
      .catch(() => {
        alert("Произошла ошибка при отправке офлайн-изменений.");
      })
      .finally(() => {
        syncPending.value = false;
      });
    }

    function pushToSyncQueue(actionName, payload) {
      syncQueue.value.push({
        action: actionName,
        id: payload.id,
        data: payload,
        timestamp: Date.now()
      });
      localStorage.setItem("pwa_sync_queue", JSON.stringify(syncQueue.value));
    }

    // --- NOTES MODAL & OPERATIONS ---
    function openNewNoteModal() {
      editingNote.value = {
        title: "",
        content: "",
        category: "Личное",
        tags: [],
        priority: "Medium",
        shared: false,
        audio_url: null
      };
      editingNoteTagsString.value = "";
      noteModalOpen.value = true;
      nextTick(() => lucide.createIcons());
    }

    function editNote(note) {
      editingNote.value = JSON.parse(JSON.stringify(note));
      editingNoteTagsString.value = (note.tags || []).join(", ");
      noteModalOpen.value = true;
      nextTick(() => lucide.createIcons());
    }

    function closeNoteModal() {
      noteModalOpen.value = false;
      clearVoiceBlob();
    }

    function saveNote() {
      if (!editingNote.value.title) {
        alert("Пожалуйста, введите название заметки.");
        return;
      }

      // Convert tag string to array
      editingNote.value.tags = editingNoteTagsString.value
        .split(",")
        .map(t => t.trim())
        .filter(t => t.length > 0);

      const noteToSave = { ...editingNote.value };

      if (isOffline.value) {
        if (!noteToSave.id) {
          noteToSave.id = "local_" + Date.now();
          notes.value.push(noteToSave);
        } else {
          const idx = notes.value.findIndex(n => n.id === noteToSave.id);
          if (idx !== -1) notes.value[idx] = noteToSave;
        }
        localStorage.setItem("pwa_notes", JSON.stringify(notes.value));
        pushToSyncQueue('save_note', noteToSave);
        closeNoteModal();
      } else {
        fetch("/api.php?action=save_note", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "Authorization": "Bearer " + userId.value
          },
          body: JSON.stringify(noteToSave)
        })
        .then(res => res.json())
        .then(() => {
          loadAllData();
          closeNoteModal();
        });
      }
    }

    function deleteNote(noteId) {
      if (!confirm("Вы действительно хотите удалить эту заметку?")) return;

      if (isOffline.value) {
        notes.value = notes.value.filter(n => n.id !== noteId);
        localStorage.setItem("pwa_notes", JSON.stringify(notes.value));
        pushToSyncQueue('delete_note', { id: noteId });
        closeNoteModal();
      } else {
        fetch(`/api.php?action=delete_note&id=${noteId}`, {
          method: "DELETE",
          headers: { "Authorization": "Bearer " + userId.value }
        })
        .then(res => res.json())
        .then(() => {
          loadAllData();
          closeNoteModal();
        });
      }
    }

    // --- TASKS MODAL & OPERATIONS ---
    function openNewTaskModal() {
      editingTask.value = {
        title: "",
        description: "",
        category: "Личное",
        tags: [],
        priority: "Medium",
        due_date: new Date().toISOString().split('T')[0],
        reminder_time: "",
        repeat_interval: "none",
        status: "pending"
      };
      editingTaskCompleted.value = false;
      taskModalOpen.value = true;
      nextTick(() => lucide.createIcons());
    }

    function editTask(task) {
      editingTask.value = JSON.parse(JSON.stringify(task));
      editingTaskCompleted.value = task.status === 'completed';
      taskModalOpen.value = true;
      nextTick(() => lucide.createIcons());
    }

    function closeTaskModal() {
      taskModalOpen.value = false;
    }

    function toggleTaskStatus(task) {
      task.status = task.status === 'completed' ? 'pending' : 'completed';

      if (isOffline.value) {
        localStorage.setItem("pwa_tasks", JSON.stringify(tasks.value));
        pushToSyncQueue('save_task', task);
      } else {
        fetch("/api.php?action=save_task", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "Authorization": "Bearer " + userId.value
          },
          body: JSON.stringify(task)
        })
        .then(res => res.json())
        .then(() => {
          loadAllData();
        });
      }
    }

    function saveTask() {
      if (!editingTask.value.title) {
        alert("Пожалуйста, введите название задачи.");
        return;
      }

      editingTask.value.status = editingTaskCompleted.value ? 'completed' : 'pending';
      const taskToSave = { ...editingTask.value };

      if (isOffline.value) {
        if (!taskToSave.id) {
          taskToSave.id = "local_task_" + Date.now();
          tasks.value.push(taskToSave);
        } else {
          const idx = tasks.value.findIndex(t => t.id === taskToSave.id);
          if (idx !== -1) tasks.value[idx] = taskToSave;
        }
        localStorage.setItem("pwa_tasks", JSON.stringify(tasks.value));
        pushToSyncQueue('save_task', taskToSave);
        closeTaskModal();
      } else {
        fetch("/api.php?action=save_task", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "Authorization": "Bearer " + userId.value
          },
          body: JSON.stringify(taskToSave)
        })
        .then(res => res.json())
        .then(() => {
          loadAllData();
          closeTaskModal();
        });
      }
    }

    function deleteTask(taskId) {
      if (!confirm("Вы действительно хотите удалить эту задачу?")) return;

      if (isOffline.value) {
        tasks.value = tasks.value.filter(t => t.id !== taskId);
        localStorage.setItem("pwa_tasks", JSON.stringify(tasks.value));
        pushToSyncQueue('delete_task', { id: taskId });
        closeTaskModal();
      } else {
        fetch(`/api.php?action=delete_task&id=${taskId}`, {
          method: "DELETE",
          headers: { "Authorization": "Bearer " + userId.value }
        })
        .then(res => res.json())
        .then(() => {
          loadAllData();
          closeTaskModal();
        });
      }
    }

    // --- AUDIO VOICE NOTES RECORDER ---
    function startRecording() {
      audioChunks = [];
      recordingSeconds.value = 0;

      navigator.mediaDevices.getUserMedia({ audio: true })
      .then(stream => {
        mediaRecorder = new MediaRecorder(stream);
        mediaRecorder.ondataavailable = e => {
          if (e.data.size > 0) audioChunks.push(e.data);
        };

        mediaRecorder.onstop = () => {
          localVoiceBlob = new Blob(audioChunks, { type: 'audio/wav' });
          voiceBlobUrl.value = URL.createObjectURL(localVoiceBlob);
        };

        mediaRecorder.start();
        recording.value = true;

        recordingInterval = setInterval(() => {
          recordingSeconds.value++;
        }, 1000);
      })
      .catch(err => {
        alert("Доступ к микрофону заблокирован: " + err.message);
      });
    }

    function stopRecording() {
      if (mediaRecorder && recording.value) {
        mediaRecorder.stop();
        recording.value = false;
        clearInterval(recordingInterval);
      }
    }

    function playVoiceBlob() {
      if (voiceBlobUrl.value) {
        const audio = new Audio(voiceBlobUrl.value);
        audio.play();
      }
    }

    function clearVoiceBlob() {
      voiceBlobUrl.value = null;
      localVoiceBlob = null;
      recordingSeconds.value = 0;
    }

    function saveVoiceBlobToNote() {
      if (!localVoiceBlob) return;
      if (isOffline.value) {
        alert("Загрузка аудиозаписей временно недоступна в офлайн-режиме.");
        return;
      }

      const formData = new FormData();
      formData.append("audio", localVoiceBlob, "voice_record.wav");

      fetch("/api.php?action=upload_voice", {
        method: "POST",
        headers: { "Authorization": "Bearer " + userId.value },
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          // Open new note modal prefilled with audio
          editingNote.value = {
            title: "Голосовая заметка от " + new Date().toLocaleDateString('ru-RU'),
            content: "Аудиозапись приложена к заметке.",
            category: "Личное",
            tags: ["Голос"],
            priority: "Medium",
            shared: false,
            audio_url: data.audio_url
          };
          editingNoteTagsString.value = "Голос";
          noteModalOpen.value = true;
          clearVoiceBlob();
        } else {
          alert("Ошибка сохранения: " + data.error);
        }
      })
      .catch(() => {
        alert("Произошла ошибка при загрузке аудио на сервер.");
      });
    }

    // Modal Attachments Audio Recording logic
    function stopRecordingAndAttachToNote() {
      if (mediaRecorder && recording.value) {
        mediaRecorder.stop();
        recording.value = false;
        clearInterval(recordingInterval);

        setTimeout(() => {
          if (!localVoiceBlob) return;
          const formData = new FormData();
          formData.append("audio", localVoiceBlob, "voice_note.wav");

          fetch("/api.php?action=upload_voice", {
            method: "POST",
            headers: { "Authorization": "Bearer " + userId.value },
            body: formData
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              editingNote.value.audio_url = data.audio_url;
            } else {
              alert("Ошибка привязки записи: " + data.error);
            }
          });
        }, 100);
      }
    }

    function playAudioUrl(url) {
      const audio = new Audio(url);
      audio.play();
    }

    function removeAttachedAudio() {
      editingNote.value.audio_url = null;
    }

    // --- COLLABORATION CHAT MINI-CONFERENCE ---
    function loadDiscussionMessages() {
      if (!selectedDiscussionNoteId.value) {
        discussionMessages.value = [];
        return;
      }

      fetch(`/api.php?action=get_discussions&note_id=${selectedDiscussionNoteId.value}`, {
        headers: { "Authorization": "Bearer " + userId.value }
      })
      .then(res => res.json())
      .then(data => {
        if (Array.isArray(data)) {
          discussionMessages.value = data;
          scrollToBottomChat();
        }
      });
    }

    function postDiscussionMessage() {
      if (!newMessageText.value.trim() || !selectedDiscussionNoteId.value) return;

      fetch("/api.php?action=post_message", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Authorization": "Bearer " + userId.value
        },
        body: JSON.stringify({
          note_id: selectedDiscussionNoteId.value,
          text: newMessageText.value
        })
      })
      .then(res => res.json())
      .then(() => {
        newMessageText.value = "";
        loadDiscussionMessages();
      });
    }

    function toggleAudioConference() {
      audioConferenceActive.value = !audioConferenceActive.value;
      if (audioConferenceActive.value) {
        // request microphone permission immediately
        navigator.mediaDevices.getUserMedia({ audio: true })
        .then(() => {
          if (navigator.vibrate) navigator.vibrate(50);
        })
        .catch(err => {
          audioConferenceActive.value = false;
          alert("Для конференции необходим микрофон: " + err.message);
        });
      }
    }

    const chatStream = ref(null);
    function scrollToBottomChat() {
      nextTick(() => {
        if (chatStream.value) {
          chatStream.value.scrollTop = chatStream.value.scrollHeight;
        }
      });
    }

    // --- CALENDAR LOGIC ---
    function prevMonth() {
      if (currentMonth.value === 0) {
        currentMonth.value = 11;
        currentYear.value--;
      } else {
        currentMonth.value--;
      }
    }

    function nextMonth() {
      if (currentMonth.value === 11) {
        currentMonth.value = 0;
        currentYear.value++;
      } else {
        currentMonth.value++;
      }
    }

    function selectCalendarDate(dateStr) {
      selectedDate.value = dateStr;
    }

    function getTasksForDate(dateStr) {
      return tasks.value.filter(t => t.due_date === dateStr);
    }

    function getNotesForDate(dateStr) {
      return notes.value.filter(n => n.created_at && n.created_at.split(' ')[0] === dateStr);
    }

    function isSameDay(d1, d2) {
      return d1 === d2;
    }

    function isToday(dateStr) {
      const today = new Date().toISOString().split('T')[0];
      return dateStr === today;
    }

    function formatDateRu(dateStr) {
      if (!dateStr) return '';
      const d = new Date(dateStr);
      return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });
    }

    // --- CHART GRAPHICS & REPORT ---
    function renderProductivityChart() {
      const ctx = document.getElementById('productivityChart');
      if (!ctx) return;

      if (chartInstance) {
        chartInstance.destroy();
      }

      // Group tasks completed vs total by category
      const labels = systemCategories.value;
      const totalData = labels.map(cat => tasks.value.filter(t => t.category === cat).length);
      const completedData = labels.map(cat => tasks.value.filter(t => t.category === cat && t.status === 'completed').length);

      chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Всего задач',
              data: totalData,
              backgroundColor: 'rgba(99, 102, 241, 0.4)',
              borderColor: 'rgba(99, 102, 241, 1)',
              borderWidth: 1.5,
              borderRadius: 6
            },
            {
              label: 'Выполнено',
              data: completedData,
              backgroundColor: 'rgba(16, 185, 129, 0.7)',
              borderColor: 'rgba(16, 185, 129, 1)',
              borderWidth: 1.5,
              borderRadius: 6
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              labels: {
                color: theme.value === 'dark' ? '#cbd5e1' : '#334155',
                font: { size: 10, weight: '600' }
              }
            }
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { color: theme.value === 'dark' ? '#94a3b8' : '#64748b', font: { size: 9 } }
            },
            y: {
              grid: { color: theme.value === 'dark' ? 'rgba(51, 65, 85, 0.5)' : 'rgba(226, 232, 240, 0.8)' },
              ticks: { precision: 0, color: theme.value === 'dark' ? '#94a3b8' : '#64748b', font: { size: 9 } }
            }
          }
        }
      });
    }

    // --- OTHER UI UTILITIES ---
    function getCategoryBadgeClass(category) {
      const colors = {
        'Личное': 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-400',
        'Работа': 'bg-sky-100 text-sky-700 dark:bg-sky-950/50 dark:text-sky-400',
        'Учеба': 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400',
        'Идеи': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400',
        'Другое': 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400'
      };
      return colors[category] || colors['Другое'];
    }

    function getPriorityClass(priority) {
      if (priority === 'High') return 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400';
      if (priority === 'Medium') return 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400';
      return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400';
    }

    function getPriorityBg(priority) {
      if (priority === 'High') return 'bg-rose-500';
      if (priority === 'Medium') return 'bg-amber-500';
      return 'bg-slate-400';
    }

    function toggleTheme() {
      theme.value = theme.value === "light" ? "dark" : "light";
      localStorage.setItem("pwa_theme", theme.value);
      applyTheme();
    }

    function applyTheme() {
      if (theme.value === "dark") {
        document.documentElement.classList.add("dark");
      } else {
        document.documentElement.classList.remove("dark");
      }
      renderProductivityChart();
    }

    function switchTab(tab) {
      currentTab.value = tab;
      nextTick(() => {
        lucide.createIcons();
        if (tab === 'statistics') {
          renderProductivityChart();
        }
        if (tab === 'collaboration') {
          loadDiscussionMessages();
        }
      });
    }

    function requestSystemPermissions() {
      // Notification Access
      if ('Notification' in window) {
        Notification.requestPermission().then(permission => {
          alert("Уведомления: " + (permission === 'granted' ? 'Разрешено' : 'Заблокировано'));
        });
      }
      // Camera/Mic access prompt
      navigator.mediaDevices.getUserMedia({ audio: true, video: false })
      .then(() => {
        alert("Доступ к аудиозаписи успешно проверен.");
      })
      .catch(e => {
        console.warn("Permissions error", e);
      });
    }

    function testPushNotification() {
      if (!('Notification' in window) || Notification.permission !== 'granted') {
        alert("Пожалуйста, сначала разрешите получение уведомлений в браузере.");
        return;
      }

      // Local notification fallback
      new Notification("PWA Напоминание", {
        body: "Привет! Ваши напоминания работают стабильно.",
        icon: "/icon-192.png"
      });
    }

    // --- EXPORT / IMPORT LOGIC ---
    function exportData() {
      window.location.href = "/api.php?action=export";
    }

    function importData(event) {
      const file = event.target.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append("backup_file", file);

      fetch("/api.php?action=import", {
        method: "POST",
        headers: { "Authorization": "Bearer " + userId.value },
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert("Данные успешно импортированы!");
          loadAllData();
        } else {
          alert("Ошибка импорта: " + data.error);
        }
      });
    }

    // Network connection status changes
    window.addEventListener('online', () => {
      isOffline.value = false;
      syncData();
    });
    window.addEventListener('offline', () => {
      isOffline.value = true;
    });

    function refreshIconsAndCharts() {
      lucide.createIcons();
      if (currentTab.value === 'statistics') {
        renderProductivityChart();
      }
    }

    onMounted(() => {
      checkSession();
      applyTheme();

      // Auto register PWA Service Worker
      if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
        .then(reg => {
          console.log('Service Worker Registered successfully', reg.scope);
        })
        .catch(err => {
          console.warn('Service Worker registration failed', err);
        });
      }
    });

    return {
      authorized,
      pinCode,
      pinError,
      userId,
      userName,
      currentTab,
      theme,
      isOffline,
      notes,
      tasks,
      discussions,
      systemCategories,
      noteQuery,
      noteCategoryFilter,
      taskQuery,
      taskStatusFilter,
      noteModalOpen,
      taskModalOpen,
      editingNote,
      editingNoteTagsString,
      editingTask,
      editingTaskCompleted,
      selectedDiscussionNoteId,
      discussionMessages,
      newMessageText,
      audioConferenceActive,
      syncQueue,
      syncPending,
      currentYear,
      currentMonth,
      selectedDate,
      recording,
      recordingSeconds,
      voiceBlobUrl,
      activeTasksCount,
      completedTasksCount,
      completionRate,
      todayFormatted,
      todayTasks,
      filteredNotes,
      filteredTasks,
      sharedNotes,
      currentMonthName,
      calendarDays,
      changePinOld,
      changePinNew,
      notificationsEnabled,
      chatStream,

      // Functions
      pressPinDigit,
      clearPin,
      deletePinLastDigit,
      logout,
      submitChangePin,
      syncData,
      openNewNoteModal,
      editNote,
      closeNoteModal,
      saveNote,
      deleteNote,
      openNewTaskModal,
      editTask,
      closeTaskModal,
      toggleTaskStatus,
      saveTask,
      deleteTask,
      startRecording,
      stopRecording,
      playVoiceBlob,
      clearVoiceBlob,
      saveVoiceBlobToNote,
      stopRecordingAndAttachToNote,
      playAudioUrl,
      removeAttachedAudio,
      loadDiscussionMessages,
      postDiscussionMessage,
      toggleAudioConference,
      prevMonth,
      nextMonth,
      selectCalendarDate,
      getTasksForDate,
      getNotesForDate,
      isSameDay,
      isToday,
      formatDateRu,
      getCategoryBadgeClass,
      getPriorityClass,
      getPriorityBg,
      toggleTheme,
      switchTab,
      requestSystemPermissions,
      testPushNotification,
      exportData,
      importData
    };
  }
}).mount('#app');
