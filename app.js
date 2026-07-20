// DOM Content Loaded Handler
document.addEventListener('DOMContentLoaded', () => {
  // Service Worker Registration
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('./sw.js')
        .then((reg) => console.log('Service Worker registered successfully:', reg.scope))
        .catch((err) => console.error('Service Worker registration failed:', err));
    });
  }

  // APP STATE
  let state = {
    selectedDate: getFormattedDate(new Date()), // YYYY-MM-DD
    currentMonth: new Date(), // Used for date-bar generation
    tasks: JSON.parse(localStorage.getItem('diary_tasks')) || [],
    cigarettes: JSON.parse(localStorage.getItem('diary_cigarettes')) || {}, // Keyed by YYYY-MM-DD
    currentFilter: 'all', // 'all', 'active', 'completed'
    currentPriority: 'medium', // Default task priority
    currentCategory: 'Личное', // Default task category
    isDark: true
  };

  // UI ELEMENTS
  const btnPrevMonth = document.getElementById('btn-prev-month');
  const btnNextMonth = document.getElementById('btn-next-month');
  const currentMonthLabel = document.getElementById('current-month-label');
  const dateScrollContainer = document.getElementById('date-scroll-container');
  const btnReadTasks = document.getElementById('btn-read-tasks');
  const btnToggleTheme = document.getElementById('btn-toggle-theme');
  const themeIcon = document.getElementById('theme-icon');
  const btnInstallPwa = document.getElementById('btn-install-pwa');
  const voiceStatusBanner = document.getElementById('voice-status-banner');
  const voiceRealtimeText = document.getElementById('voice-realtime-text');
  const btnStopVoice = document.getElementById('btn-stop-voice');

  // Cigarettes elements
  const cigCounterVal = document.getElementById('cig-counter-val');
  const cigTrackerHelper = document.getElementById('cig-tracker-helper');
  const btnCigMinus = document.getElementById('btn-cig-minus');
  const btnCigPlus = document.getElementById('btn-cig-plus');

  // Progress elements
  const taskProgressVal = document.getElementById('task-progress-val');
  const taskProgressSubtitle = document.getElementById('task-progress-subtitle');
  const taskProgressBar = document.getElementById('task-progress-bar');

  // Tasks elements
  const tasksList = document.getElementById('tasks-list');
  const tasksEmptyState = document.getElementById('tasks-empty-state');
  const filterPills = document.querySelectorAll('.filter-pill');

  // Stats elements
  const statMoneySpent = document.getElementById('stat-money-spent');
  const statLifeLost = document.getElementById('stat-life-lost');
  const statTotalTasks = document.getElementById('stat-total-tasks');
  const statTotalCigs = document.getElementById('stat-total-cigs');

  // Advanced stats elements
  const statAvgCigs = document.getElementById('stat-avg-cigs');
  const statPeakCigs = document.getElementById('stat-peak-cigs');
  const statSmokeFreeDays = document.getElementById('stat-smoke-free-days');

  // Category progress elements
  const catProgressValWork = document.getElementById('cat-progress-val-work');
  const catProgressBarWork = document.getElementById('cat-progress-bar-work');

  const catProgressValHome = document.getElementById('cat-progress-val-home');
  const catProgressBarHome = document.getElementById('cat-progress-bar-home');

  const catProgressValPersonal = document.getElementById('cat-progress-val-personal');
  const catProgressBarPersonal = document.getElementById('cat-progress-bar-personal');

  const catProgressValHealth = document.getElementById('cat-progress-val-health');
  const catProgressBarHealth = document.getElementById('cat-progress-bar-health');

  // Import/Export buttons
  const btnExportJson = document.getElementById('btn-export-json');
  const inputImportJson = document.getElementById('input-import-json');
  const btnImportJsonTrigger = document.getElementById('btn-import-json-trigger');

  // Add Task Input elements
  const btnVoiceInput = document.getElementById('btn-voice-input');
  const inputTaskText = document.getElementById('input-task-text');
  const btnTogglePriority = document.getElementById('btn-toggle-priority');
  const btnToggleCategory = document.getElementById('btn-toggle-category');
  const btnAddTask = document.getElementById('btn-add-task');

  // Popovers
  const popoverPriority = document.getElementById('popover-priority');
  const popoverCategory = document.getElementById('popover-category');

  // Voice help modal
  const voiceHelpModal = document.getElementById('voice-help-modal');
  const btnCloseVoiceHelp = document.getElementById('btn-close-voice-help');

  // PWA Install Prompt handling
  let deferredPrompt;
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    btnInstallPwa.classList.remove('hidden');
  });

  btnInstallPwa.addEventListener('click', async () => {
    if (deferredPrompt) {
      deferredPrompt.prompt();
      const { outcome } = await deferredPrompt.userChoice;
      console.log(`User response to install prompt: ${outcome}`);
      deferredPrompt = null;
      btnInstallPwa.classList.add('hidden');
    }
  });

  // SPEECH RECOGNITION (Voice Input)
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  let recognition = null;
  let isListening = false;

  if (SpeechRecognition) {
    recognition = new SpeechRecognition();
    recognition.continuous = false;
    recognition.lang = 'ru-RU';
    recognition.interimResults = false;

    recognition.onstart = () => {
      isListening = true;
      voiceStatusBanner.classList.remove('hidden');
      document.getElementById('mic-icon').textContent = 'mic_off';
      document.getElementById('mic-icon').classList.add('text-rose-500');
      voiceRealtimeText.textContent = 'Скажите команду или задачу...';
    };

    recognition.onend = () => {
      isListening = false;
      voiceStatusBanner.classList.add('hidden');
      document.getElementById('mic-icon').textContent = 'mic';
      document.getElementById('mic-icon').classList.remove('text-rose-500');
    };

    recognition.onerror = (e) => {
      console.error('Speech recognition error', e);
      isListening = false;
      voiceStatusBanner.classList.add('hidden');
      document.getElementById('mic-icon').textContent = 'mic';
      document.getElementById('mic-icon').classList.remove('text-rose-500');
    };

    recognition.onresult = (event) => {
      const transcript = event.results[0][0].transcript.trim();
      voiceRealtimeText.textContent = `Распознано: "${transcript}"`;
      handleVoiceCommand(transcript);
    };
  } else {
    console.warn('SpeechRecognition is not supported in this browser.');
    btnVoiceInput.title = "Голосовой ввод не поддерживается вашим браузером";
  }

  // SPEECH SYNTHESIS (Voice Output)
  function speakText(text) {
    if ('speechSynthesis' in window) {
      // Cancel any ongoing speaking
      window.speechSynthesis.cancel();

      const utterance = new SpeechSynthesisUtterance(text);
      utterance.lang = 'ru-RU';
      utterance.rate = 1.0;

      // Try to find a nice Russian voice if available
      const voices = window.speechSynthesis.getVoices();
      const ruVoice = voices.find(v => v.lang.startsWith('ru'));
      if (ruVoice) {
        utterance.voice = ruVoice;
      }

      window.speechSynthesis.speak(utterance);
    } else {
      alert('Синтез речи не поддерживается на этом устройстве.');
    }
  }

  // Load voices on change (for Chrome support)
  if ('speechSynthesis' in window) {
    window.speechSynthesis.onvoiceschanged = () => {};
  }

  // INITIALIZATION
  initTheme();
  renderDateScroll();
  updateUI();
  checkFirstVisit();

  // THEME MANAGEMENT
  function initTheme() {
    const cachedTheme = localStorage.getItem('diary_theme');
    if (cachedTheme === 'light') {
      state.isDark = false;
      document.documentElement.classList.remove('dark');
      themeIcon.textContent = 'dark_mode';
    } else {
      state.isDark = true;
      document.documentElement.classList.add('dark');
      themeIcon.textContent = 'light_mode';
    }
  }

  btnToggleTheme.addEventListener('click', () => {
    state.isDark = !state.isDark;
    if (state.isDark) {
      document.documentElement.classList.add('dark');
      themeIcon.textContent = 'light_mode';
      localStorage.setItem('diary_theme', 'dark');
    } else {
      document.documentElement.classList.remove('dark');
      themeIcon.textContent = 'dark_mode';
      localStorage.setItem('diary_theme', 'light');
    }
  });

  // DATE HELPERS
  function getFormattedDate(date) {
    const d = new Date(date);
    let month = '' + (d.getMonth() + 1);
    let day = '' + d.getDate();
    const year = d.getFullYear();

    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;

    return [year, month, day].join('-');
  }

  function getMonthName(date) {
    return date.toLocaleString('ru-RU', { month: 'long', year: 'numeric' });
  }

  // HORIZONTAL DATE LIST GENERATOR
  function renderDateScroll() {
    dateScrollContainer.innerHTML = '';
    const year = state.currentMonth.getFullYear();
    const month = state.currentMonth.getMonth();

    currentMonthLabel.textContent = getMonthName(state.currentMonth);

    // Get number of days in the month
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    for (let day = 1; day <= daysInMonth; day++) {
      const thisDate = new Date(year, month, day);
      const formatted = getFormattedDate(thisDate);
      const isSelected = formatted === state.selectedDate;
      const isToday = getFormattedDate(new Date()) === formatted;

      // Day of week
      const dow = thisDate.toLocaleString('ru-RU', { weekday: 'short' });

      const dateNode = document.createElement('button');
      dateNode.className = `flex-shrink-0 w-12 py-2 rounded-2xl flex flex-col items-center justify-center transition-all ${
        isSelected
          ? 'bg-brand-500 text-slate-950 font-bold shadow-md shadow-brand-500/20 scale-105'
          : isToday
            ? 'bg-slate-800/80 border border-brand-500/30 text-brand-400 font-semibold'
            : 'bg-slate-900 hover:bg-slate-800 text-slate-400'
      }`;

      dateNode.innerHTML = `
        <span class="text-[10px] uppercase font-bold tracking-tight opacity-80">${dow}</span>
        <span class="text-sm font-extrabold mt-0.5">${day}</span>
      `;

      dateNode.addEventListener('click', () => {
        state.selectedDate = formatted;
        renderDateScroll();
        updateUI();
      });

      dateScrollContainer.appendChild(dateNode);

      // Auto-scroll inside horizontal container specifically, avoiding page scrollIntoView side effects
      if (isSelected) {
        setTimeout(() => {
          const containerHalfWidth = dateScrollContainer.clientWidth / 2;
          const nodeHalfWidth = dateNode.clientWidth / 2;
          dateScrollContainer.scrollLeft = dateNode.offsetLeft - containerHalfWidth + nodeHalfWidth;
        }, 100);
      }
    }
  }

  btnPrevMonth.addEventListener('click', () => {
    state.currentMonth.setMonth(state.currentMonth.getMonth() - 1);
    renderDateScroll();
  });

  btnNextMonth.addEventListener('click', () => {
    state.currentMonth.setMonth(state.currentMonth.getMonth() + 1);
    renderDateScroll();
  });

  // GENERAL STATE SYNC & RENDER
  function updateUI() {
    saveState();
    renderTasks();
    renderCigarettes();
    renderAnalytics();
  }

  function saveState() {
    localStorage.setItem('diary_tasks', JSON.stringify(state.tasks));
    localStorage.setItem('diary_cigarettes', JSON.stringify(state.cigarettes));
  }

  // TASK HANDLING
  function renderTasks() {
    tasksList.innerHTML = '';

    // Filter tasks for the selected date
    const dayTasks = state.tasks.filter(task => task.date === state.selectedDate);

    // Apply Filter Tab
    let filtered = dayTasks;
    if (state.currentFilter === 'active') {
      filtered = dayTasks.filter(t => !t.completed);
    } else if (state.currentFilter === 'completed') {
      filtered = dayTasks.filter(t => t.completed);
    }

    // Sort by priority (high > medium > low) then by time
    const priorityWeight = { high: 3, medium: 2, low: 1 };
    filtered.sort((a, b) => {
      if (a.completed !== b.completed) return a.completed - b.completed;
      return (priorityWeight[b.priority] || 2) - (priorityWeight[a.priority] || 2);
    });

    if (filtered.length === 0) {
      tasksEmptyState.classList.remove('hidden');
    } else {
      tasksEmptyState.classList.add('hidden');

      filtered.forEach((task) => {
        const item = document.createElement('div');
        item.className = `fade-enter-active p-4 rounded-3xl border flex items-center justify-between transition-all duration-300 ${
          task.completed
            ? 'bg-slate-900/40 border-slate-900 text-slate-500 line-through'
            : 'bg-slate-900 border-slate-800 text-slate-100 hover:border-slate-700 shadow-sm'
        }`;

        // Get priority-specific color badge
        let prioColor = 'bg-sky-500/10 text-sky-400 border-sky-500/20';
        if (task.priority === 'high') prioColor = 'bg-rose-500/10 text-rose-400 border-rose-500/20';
        if (task.priority === 'medium') prioColor = 'bg-amber-500/10 text-amber-400 border-amber-500/20';

        // Choose category icon
        let catIcon = 'label';
        if (task.category === 'Работа') catIcon = 'work';
        if (task.category === 'Дом') catIcon = 'home';
        if (task.category === 'Личное') catIcon = 'person';
        if (task.category === 'Здоровье') catIcon = 'health_and_safety';

        item.innerHTML = `
          <div class="flex items-center space-x-3 flex-1 min-w-0">
            <!-- Complete toggle checkbox custom -->
            <button class="btn-toggle-complete w-6 h-6 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all ${
              task.completed
                ? 'bg-emerald-500 border-emerald-500 text-slate-950'
                : 'border-slate-700 hover:border-brand-500 text-transparent'
            }">
              <span class="material-icons-round text-sm font-bold">done</span>
            </button>

            <div class="flex-1 min-w-0">
              <span class="text-sm font-medium block truncate">${task.text}</span>
              <!-- Badges row -->
              <div class="flex items-center space-x-1.5 mt-1">
                <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold tracking-wide uppercase border ${prioColor}">
                  ${task.priority === 'high' ? 'Высокий' : task.priority === 'medium' ? 'Средний' : 'Низкий'}
                </span>
                <span class="flex items-center space-x-0.5 px-1.5 py-0.5 rounded-md text-[9px] font-bold tracking-wide uppercase bg-slate-800 text-slate-400">
                  <span class="material-icons-round text-[10px]">${catIcon}</span>
                  <span>${task.category || 'Личное'}</span>
                </span>
              </div>
            </div>
          </div>

          <div class="flex items-center space-x-1 ml-3">
            <!-- Audio voice out task individually -->
            <button class="btn-speak-single p-1.5 rounded-full hover:bg-slate-800 text-slate-400 hover:text-brand-400 active:scale-95 transition-all">
              <span class="material-icons-round text-lg">volume_up</span>
            </button>
            <!-- Delete task -->
            <button class="btn-delete-task p-1.5 rounded-full hover:bg-slate-800 text-slate-400 hover:text-rose-400 active:scale-95 transition-all">
              <span class="material-icons-round text-lg">delete_outline</span>
            </button>
          </div>
        `;

        // Action attachments
        item.querySelector('.btn-toggle-complete').addEventListener('click', () => {
          task.completed = !task.completed;
          updateUI();
        });

        item.querySelector('.btn-speak-single').addEventListener('click', () => {
          speakText(task.text);
        });

        item.querySelector('.btn-delete-task').addEventListener('click', () => {
          state.tasks = state.tasks.filter(t => t.id !== task.id);
          updateUI();
        });

        tasksList.appendChild(item);
      });
    }

    // Update Progress Indicator
    const total = dayTasks.length;
    const completed = dayTasks.filter(t => t.completed).length;
    const percent = total === 0 ? 0 : Math.round((completed / total) * 100);

    taskProgressVal.textContent = `${percent}%`;
    taskProgressSubtitle.textContent = `${completed} из ${total} задач сделано`;
    taskProgressBar.style.width = `${percent}%`;
  }

  // ADD TASK LOGIC
  function addNewTask(text, priority = 'medium', category = 'Личное') {
    if (!text.trim()) return;

    const newTask = {
      id: Date.now() + Math.random().toString(36).substr(2, 5),
      text: text.trim(),
      date: state.selectedDate,
      completed: false,
      priority: priority,
      category: category
    };

    state.tasks.push(newTask);
    updateUI();
  }

  btnAddTask.addEventListener('click', () => {
    const text = inputTaskText.value;
    if (text.trim()) {
      addNewTask(text, state.currentPriority, state.currentCategory);
      inputTaskText.value = '';
      // Reset input bar flags
      resetInputBarChoices();
    }
  });

  inputTaskText.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      const text = inputTaskText.value;
      if (text.trim()) {
        addNewTask(text, state.currentPriority, state.currentCategory);
        inputTaskText.value = '';
        resetInputBarChoices();
      }
    }
  });

  // Reset category/priority buttons back to default
  function resetInputBarChoices() {
    state.currentPriority = 'medium';
    state.currentCategory = 'Личное';
    document.getElementById('priority-icon').className = 'material-icons-round text-xl';
    document.getElementById('priority-icon').style.color = '';
    document.getElementById('category-icon').className = 'material-icons-round text-xl';
    document.getElementById('category-icon').textContent = 'label';
    document.getElementById('category-icon').style.color = '';
  }

  // CIGARETTES HANDLERS
  function renderCigarettes() {
    const count = state.cigarettes[state.selectedDate] || 0;
    cigCounterVal.textContent = count;

    if (count === 0) {
      cigTrackerHelper.textContent = 'Сегодня без затяжек! Отлично!';
      cigTrackerHelper.className = 'text-[10px] text-emerald-400 mt-1 font-medium';
    } else if (count < 5) {
      cigTrackerHelper.textContent = 'Умеренное количество. Стремитесь к нулю!';
      cigTrackerHelper.className = 'text-[10px] text-amber-400 mt-1 font-medium';
    } else {
      cigTrackerHelper.textContent = 'Многовато. Попробуйте дыхательную практику!';
      cigTrackerHelper.className = 'text-[10px] text-rose-400 mt-1 font-medium';
    }
  }

  btnCigMinus.addEventListener('click', () => {
    const cur = state.cigarettes[state.selectedDate] || 0;
    if (cur > 0) {
      state.cigarettes[state.selectedDate] = cur - 1;
      updateUI();
    }
  });

  btnCigPlus.addEventListener('click', () => {
    const cur = state.cigarettes[state.selectedDate] || 0;
    state.cigarettes[state.selectedDate] = cur + 1;
    updateUI();
  });

  // ANALYTICS & STATS ENGINE
  function renderAnalytics() {
    // Total cigarettes smoked all time
    let totalCigs = 0;
    let peakCigs = 0;
    let daysTracked = 0;
    let smokeFreeDaysCount = 0;

    Object.entries(state.cigarettes).forEach(([dateStr, count]) => {
      if (count > 0) {
        totalCigs += count;
        daysTracked++;
        if (count > peakCigs) {
          peakCigs = count;
        }
      } else if (count === 0) {
        // Explicitly set to zero count
        smokeFreeDaysCount++;
      }
    });

    const averageCigs = daysTracked > 0 ? (totalCigs / daysTracked).toFixed(1) : 0;

    // Output advanced cigarette stats
    statAvgCigs.textContent = averageCigs;
    statPeakCigs.textContent = peakCigs;
    statSmokeFreeDays.textContent = smokeFreeDaysCount;

    // Calculations based on rules
    const costPerCig = 10; // avg 10 Rubles
    const lifeMinutesLostPerCig = 11; // avg 11 minutes of life

    const moneySpent = totalCigs * costPerCig;
    const minutesLost = totalCigs * lifeMinutesLostPerCig;

    statMoneySpent.textContent = `${moneySpent.toLocaleString('ru-RU')} ₽`;
    statLifeLost.textContent = `${minutesLost.toLocaleString('ru-RU')} мин`;

    // Total tasks made
    const completedTasksCount = state.tasks.filter(t => t.completed).length;
    statTotalTasks.textContent = completedTasksCount;
    statTotalCigs.textContent = totalCigs;

    // Render Category-Specific Progress
    renderCategoryProgress('Работа', catProgressValWork, catProgressBarWork);
    renderCategoryProgress('Дом', catProgressValHome, catProgressBarHome);
    renderCategoryProgress('Личное', catProgressValPersonal, catProgressBarPersonal);
    renderCategoryProgress('Здоровье', catProgressValHealth, catProgressBarHealth);
  }

  function renderCategoryProgress(category, valueElement, barElement) {
    const catTasks = state.tasks.filter(t => t.category === category);
    const total = catTasks.length;
    const completed = catTasks.filter(t => t.completed).length;
    const percent = total === 0 ? 0 : Math.round((completed / total) * 100);

    valueElement.textContent = `${percent}% (${completed}/${total})`;
    barElement.style.width = `${percent}%`;
  }

  // POPOVERS & DIALOG TOGGLES
  btnTogglePriority.addEventListener('click', (e) => {
    e.stopPropagation();
    popoverCategory.classList.add('hidden');
    popoverPriority.classList.toggle('hidden');
  });

  btnToggleCategory.addEventListener('click', (e) => {
    e.stopPropagation();
    popoverPriority.classList.add('hidden');
    popoverCategory.classList.toggle('hidden');
  });

  // Close popovers on page clicks
  document.addEventListener('click', () => {
    popoverPriority.classList.add('hidden');
    popoverCategory.classList.add('hidden');
  });

  // Popover Priority click elements
  popoverPriority.querySelectorAll('[data-prio]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const prio = btn.getAttribute('data-prio');
      state.currentPriority = prio;

      const pIcon = document.getElementById('priority-icon');
      if (prio === 'high') {
        pIcon.style.color = '#f87171'; // red-400
      } else if (prio === 'medium') {
        pIcon.style.color = '#fbbf24'; // amber-400
      } else {
        pIcon.style.color = '#38bdf8'; // sky-400
      }

      popoverPriority.classList.add('hidden');
    });
  });

  // Popover Category click elements
  popoverCategory.querySelectorAll('[data-cat]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const cat = btn.getAttribute('data-cat');
      state.currentCategory = cat;

      const cIcon = document.getElementById('category-icon');
      if (cat === 'Работа') {
        cIcon.textContent = 'work';
        cIcon.style.color = '#818cf8';
      } else if (cat === 'Дом') {
        cIcon.textContent = 'home';
        cIcon.style.color = '#34d399';
      } else if (cat === 'Личное') {
        cIcon.textContent = 'person';
        cIcon.style.color = '#f472b6';
      } else if (cat === 'Здоровье') {
        cIcon.textContent = 'health_and_safety';
        cIcon.style.color = '#f87171';
      }

      popoverCategory.classList.add('hidden');
    });
  });

  // VOICE INSTRUCTIONS FIRST VISIT POPUP
  function checkFirstVisit() {
    const visited = localStorage.getItem('diary_visited');
    if (!visited) {
      voiceHelpModal.classList.remove('hidden');
    }
  }

  btnCloseVoiceHelp.addEventListener('click', () => {
    localStorage.setItem('diary_visited', 'true');
    voiceHelpModal.classList.add('hidden');
  });

  // VOICE CONTROL & INTERACTION COMMANDS PARSING
  btnVoiceInput.addEventListener('click', () => {
    if (!SpeechRecognition) {
      alert('Голосовое управление (SpeechRecognition) недоступно в вашей системе или браузере.');
      return;
    }
    if (isListening) {
      recognition.stop();
    } else {
      recognition.start();
    }
  });

  btnStopVoice.addEventListener('click', () => {
    if (recognition && isListening) {
      recognition.stop();
    }
  });

  // PARSE COMPREHENSIVE VOICE COMMANDS (RUSSIAN COGNITIVE MATCHING)
  function handleVoiceCommand(rawText) {
    const text = rawText.toLowerCase().trim();
    console.log('Parsing voice command:', text);

    // Pattern 1: Read all tasks (Speech Synthesis trigger)
    // Matches: "какие дела на сегодня", "прочитай задачи", "прочитай дела", "что у меня сегодня", "прочитай задачи на сегодня"
    if (
      text.includes('какие дела') ||
      text.includes('какие задачи') ||
      text.includes('прочитай') ||
      text.includes('что сегодня') ||
      text.includes('список дел')
    ) {
      readAllTasksSpeech();
      return;
    }

    // Pattern 2: Add cigarette
    // Matches: "добавь сигарету", "выкурил сигарету", "плюс одна", "плюс сигарета", "еще одна сигарета"
    if (
      text.includes('сигарету') ||
      text.includes('сигарета') ||
      text.includes('плюс одна') ||
      text.includes('выкурил')
    ) {
      const cur = state.cigarettes[state.selectedDate] || 0;
      state.cigarettes[state.selectedDate] = cur + 1;
      updateUI();
      speakText('Добавлена одна выкуренная сигарета.');
      return;
    }

    // Pattern 3: Delete/Remove task
    // Matches: "удали [текст задачи]", "удалить [текст задачи]"
    if (text.startsWith('удали') || text.startsWith('удалить')) {
      const query = text.replace(/^удалить\s+|^удали\s+/, '').trim();
      if (query) {
        // Find matching task for this day
        const dayTasks = state.tasks.filter(t => t.date === state.selectedDate);
        const match = dayTasks.find(t => t.text.toLowerCase().includes(query));
        if (match) {
          state.tasks = state.tasks.filter(t => t.id !== match.id);
          updateUI();
          speakText(`Удалена задача: ${match.text}`);
        } else {
          speakText(`Задача со словом "${query}" не найдена на сегодня.`);
        }
      } else {
        speakText('Что именно вы хотите удалить?');
      }
      return;
    }

    // Pattern 4: Add Task
    // Matches: "добавить [текст задачи]", "добавь [текст задачи]", "запиши [текст задачи]", "создай [текст задачи]"
    let taskContent = '';
    if (text.startsWith('добавить') || text.startsWith('добавь')) {
      taskContent = rawText.replace(/^добавить\s+|^добавь\s+/i, '').trim();
    } else if (text.startsWith('запиши') || text.startsWith('записать')) {
      taskContent = rawText.replace(/^запиши\s+|^записать\s+/i, '').trim();
    } else if (text.startsWith('создай') || text.startsWith('создать')) {
      taskContent = rawText.replace(/^создай\s+|^создать\s+/i, '').trim();
    } else {
      // Direct text addition if no command matches
      taskContent = rawText;
    }

    if (taskContent) {
      // Analyze category context
      let cat = 'Личное';
      if (taskContent.toLowerCase().includes('работу') || taskContent.toLowerCase().includes('проект')) cat = 'Работа';
      if (taskContent.toLowerCase().includes('дома') || taskContent.toLowerCase().includes('убрать') || taskContent.toLowerCase().includes('купить')) cat = 'Дом';
      if (taskContent.toLowerCase().includes('здоровье') || taskContent.toLowerCase().includes('аптека') || taskContent.toLowerCase().includes('врач')) cat = 'Здоровье';

      addNewTask(taskContent, 'medium', cat);
      speakText(`Добавлена задача: ${taskContent}`);
    } else {
      speakText('Повторите, пожалуйста, команду громче и четче.');
    }
  }

  // SPEECH SYNTHESIS ENGINE FOR LISTING TODAY'S TASKS
  function readAllTasksSpeech() {
    const dayTasks = state.tasks.filter(t => t.date === state.selectedDate);

    if (dayTasks.length === 0) {
      speakText('На выбранный день у вас нет запланированных задач. Отдыхайте!');
      return;
    }

    const active = dayTasks.filter(t => !t.completed);
    const completed = dayTasks.filter(t => t.completed);

    let report = `На сегодня у вас запланировано ${dayTasks.length} задач. `;

    if (active.length > 0) {
      report += `Активные задачи: ${active.map((t, idx) => `${idx + 1}, ${t.text}`).join('. ')}. `;
    } else {
      report += 'Все запланированные задачи на сегодня уже выполнены! Вы супер. ';
    }

    if (completed.length > 0) {
      report += `Выполненных задач: ${completed.length}. `;
    }

    // Cigarettes tracker stats voice reporting
    const cigs = state.cigarettes[state.selectedDate] || 0;
    if (cigs > 0) {
      report += `Обратите внимание, сегодня вы уже выкурили ${cigs} сигарет. Постарайтесь дышать свежим воздухом!`;
    } else {
      report += 'Сегодня вы не выкурили ни одной сигареты. Вы отлично заботитесь о здоровье!';
    }

    speakText(report);
  }

  // Trigger from top header button too
  btnReadTasks.addEventListener('click', () => {
    readAllTasksSpeech();
  });

  // DATABASE IMPORT & EXPORT LOGIC
  btnExportJson.addEventListener('click', () => {
    const backupData = {
      version: "1.0",
      tasks: state.tasks,
      cigarettes: state.cigarettes,
      theme: localStorage.getItem('diary_theme') || 'dark',
      visited: localStorage.getItem('diary_visited') || 'false'
    };

    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(backupData, null, 2));
    const downloadAnchor = document.createElement('a');
    downloadAnchor.setAttribute("href", dataStr);
    downloadAnchor.setAttribute("download", `diary_backup_${getFormattedDate(new Date())}.json`);
    document.body.appendChild(downloadAnchor);
    downloadAnchor.click();
    downloadAnchor.remove();
  });

  btnImportJsonTrigger.addEventListener('click', () => {
    inputImportJson.click();
  });

  inputImportJson.addEventListener('change', (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
      try {
        const importedData = JSON.parse(e.target.result);

        // Validation check
        if (importedData && typeof importedData === 'object') {
          if (Array.isArray(importedData.tasks)) {
            state.tasks = importedData.tasks;
          }
          if (importedData.cigarettes && typeof importedData.cigarettes === 'object') {
            state.cigarettes = importedData.cigarettes;
          }
          if (importedData.theme) {
            localStorage.setItem('diary_theme', importedData.theme);
          }
          if (importedData.visited) {
            localStorage.setItem('diary_visited', importedData.visited);
          }

          updateUI();
          initTheme();
          renderDateScroll();
          alert('Данные успешно импортированы!');
        } else {
          alert('Неверный формат файла резервной копии.');
        }
      } catch (err) {
        console.error('Error parsing imported JSON:', err);
        alert('Ошибка при импорте файла. Убедитесь, что это валидный JSON-файл.');
      }
    };
    reader.readAsText(file);
    // Clear input so same file can be re-uploaded if modified
    inputImportJson.value = '';
  });

  // FILTER PILLS LOGIC
  filterPills.forEach(pill => {
    pill.addEventListener('click', () => {
      filterPills.forEach(p => {
        p.classList.remove('active', 'bg-brand-500', 'text-slate-950');
        p.classList.add('bg-slate-800', 'text-slate-300');
      });
      pill.classList.add('active', 'bg-brand-500', 'text-slate-950');
      pill.classList.remove('bg-slate-800', 'text-slate-300');

      state.currentFilter = pill.getAttribute('data-filter');
      renderTasks();
    });
  });
});
