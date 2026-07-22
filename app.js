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
    isDark: true,

    // NEW SETTINGS & DATA
    usePin: JSON.parse(localStorage.getItem('diary_use_pin')) || false,
    userPin: localStorage.getItem('diary_user_pin') || '1234',
    packCost: Number(localStorage.getItem('diary_pack_cost')) || 200,
    cigsPerPack: Number(localStorage.getItem('diary_cigs_per_pack')) || 20,
    water: JSON.parse(localStorage.getItem('diary_water')) || {}, // Keyed by YYYY-MM-DD

    // DEBTS AND ASSISTANT HELPERS STATE
    debts: JSON.parse(localStorage.getItem('diary_debts')) || [],
    debtType: 'gave', // 'gave' (мне должны) or 'took' (я должен)
    habits: JSON.parse(localStorage.getItem('diary_habits')) || [
      { id: '1', name: 'Выпить 1.5л воды', completed: {} },
      { id: '2', name: 'Сделать зарядку', completed: {} },
      { id: '3', name: 'Прочитать 10 страниц', completed: {} }
    ],
    expenses: JSON.parse(localStorage.getItem('diary_expenses')) || {}, // Keyed by YYYY-MM-DD
    moods: JSON.parse(localStorage.getItem('diary_moods')) || {}, // Keyed by YYYY-MM-DD
    vocalNotes: JSON.parse(localStorage.getItem('diary_vocal_notes')) || [],
    stepCount: JSON.parse(localStorage.getItem('diary_step_count')) || {}, // Keyed by YYYY-MM-DD
    gratitudes: JSON.parse(localStorage.getItem('diary_gratitudes')) || [],
    shopList: JSON.parse(localStorage.getItem('diary_shop_list')) || [],
    sosPhone: localStorage.getItem('diary_sos_phone') || '',
    sosBlood: localStorage.getItem('diary_sos_blood') || '',
    notesDraft: localStorage.getItem('diary_notes_draft') || ''
  };

  // AUTOMATIC ROLLOVER ALGORITHM FOR YESTERDAY'S UNCOMPLETED TASKS
  function rolloverTasks() {
    const todayStr = getFormattedDate(new Date());
    let rolledCount = 0;
    state.tasks = state.tasks.map(task => {
      // If task is not completed, and its date is in the past compared to today
      if (!task.completed && task.date < todayStr) {
        rolledCount++;
        return {
          ...task,
          date: todayStr,
          transferred: true
        };
      }
      return task;
    });

    if (rolledCount > 0) {
      console.log(`Rolled over ${rolledCount} uncompleted tasks to today.`);
      localStorage.setItem('diary_tasks', JSON.stringify(state.tasks));
    }
  }

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

  // New settings & UI references
  const networkStatus = document.getElementById('network-status');

  // PIN lock elements
  const lockScreenOverlay = document.getElementById('lock-screen-overlay');
  const lockScreenError = document.getElementById('lock-screen-error');
  const btnKeypadClear = document.getElementById('btn-keypad-clear');
  const btnKeypadDelete = document.getElementById('btn-keypad-delete');
  const pinDots = document.querySelectorAll('.pin-dot');
  const keypadBtns = document.querySelectorAll('.keypad-btn');

  // Water elements
  const waterCounterVal = document.getElementById('water-counter-val');
  const waterTrackerHelper = document.getElementById('water-tracker-helper');
  const btnWaterReset = document.getElementById('btn-water-reset');
  const btnWaterPlus = document.getElementById('btn-water-plus');

  // Settings elements
  const settingPackCost = document.getElementById('setting-pack-cost');
  const settingCigsPerPack = document.getElementById('setting-cigs-per-pack');
  const settingUsePin = document.getElementById('setting-use-pin');
  const settingPinInputGroup = document.getElementById('setting-pin-input-group');
  const settingPinVal = document.getElementById('setting-pin-val');

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
  const inputTaskTime = document.getElementById('input-task-time');
  const btnTimePicker = document.getElementById('btn-time-picker');
  const timeIcon = document.getElementById('time-icon');
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
  initSettings();
  initPinLock();
  initNetworkStatus();
  initDebts();
  initNotifications();
  initHabitForm();
  initPomodoro();
  initFasting();
  initBreathing();
  initScreenDetox();
  initExpensesForm();
  initMoodSelection();
  initVocalNotes();
  initCoinFlipper();
  initStepTracker();
  initBMICalculator();
  initGratitude();
  initDecibelMeter();
  initSOSCard();
  initStopwatch();
  initKitchenTimer();
  initDiceRoller();
  initShoppingList();
  initTipCalculator();
  initQuoteRotator();
  initFlashlight();
  initPasswordGenerator();
  initStressTest();
  initNotesDraft();
  rolloverTasks(); // Perform yesterday rollover before render
  renderDateScroll();
  updateUI();
  checkFirstVisit();

  // NETWORK STATUS MONITOR
  function initNetworkStatus() {
    function updateStatus() {
      if (navigator.onLine) {
        networkStatus.innerHTML = `
          <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
          <span>В сети</span>
        `;
        networkStatus.className = "inline-flex items-center space-x-1 bg-emerald-500/10 text-emerald-400 text-[9px] font-bold px-1.5 py-0.5 rounded-full border border-emerald-500/20";
      } else {
        networkStatus.innerHTML = `
          <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
          <span>Офлайн</span>
        `;
        networkStatus.className = "inline-flex items-center space-x-1 bg-amber-500/10 text-amber-400 text-[9px] font-bold px-1.5 py-0.5 rounded-full border border-amber-500/20";
      }
    }

    window.addEventListener('online', updateStatus);
    window.addEventListener('offline', updateStatus);
    updateStatus(); // Initial check
  }

  // PIN LOCK SYSTEM
  let enteredPin = '';
  function initPinLock() {
    if (!state.usePin) {
      lockScreenOverlay.classList.add('hidden');
      return;
    }

    lockScreenOverlay.classList.remove('hidden');
    enteredPin = '';
    updatePinDots();

    // Keypad listeners
    keypadBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        if (enteredPin.length < 4) {
          enteredPin += btn.textContent.trim();
          updatePinDots();
          if (enteredPin.length === 4) {
            verifyPin();
          }
        }
      });
    });

    btnKeypadClear.addEventListener('click', () => {
      enteredPin = '';
      updatePinDots();
    });

    btnKeypadDelete.addEventListener('click', () => {
      if (enteredPin.length > 0) {
        enteredPin = enteredPin.slice(0, -1);
        updatePinDots();
      }
    });
  }

  function updatePinDots() {
    pinDots.forEach((dot, idx) => {
      if (idx < enteredPin.length) {
        dot.className = "w-4 h-4 rounded-full bg-brand-500 border-2 border-brand-500 scale-110 transition-all duration-150";
      } else {
        dot.className = "w-4 h-4 rounded-full border-2 border-slate-700 bg-transparent transition-all duration-150 pin-dot";
      }
    });
  }

  function verifyPin() {
    if (enteredPin === state.userPin) {
      // Access granted
      lockScreenOverlay.classList.add('hidden');
      enteredPin = '';
      updatePinDots();
    } else {
      // Access denied haptic/error animation
      lockScreenError.classList.remove('opacity-0');
      lockScreenError.classList.add('opacity-100');

      // Vibrate if supported
      if (navigator.vibrate) {
        navigator.vibrate([100, 50, 100]);
      }

      setTimeout(() => {
        lockScreenError.classList.remove('opacity-100');
        lockScreenError.classList.add('opacity-0');
        enteredPin = '';
        updatePinDots();
      }, 1200);
    }
  }

  // APP CONFIG / SETTINGS PANEL
  function initSettings() {
    // Populate form fields with loaded state
    settingPackCost.value = state.packCost;
    settingCigsPerPack.value = state.cigsPerPack;
    settingUsePin.checked = state.usePin;
    settingPinVal.value = state.userPin;

    if (state.usePin) {
      settingPinInputGroup.classList.remove('hidden');
    } else {
      settingPinInputGroup.classList.add('hidden');
    }

    // Cost input changes
    settingPackCost.addEventListener('input', () => {
      const val = Number(settingPackCost.value);
      if (val >= 0) {
        state.packCost = val;
        localStorage.setItem('diary_pack_cost', val);
        updateUI();
      }
    });

    settingCigsPerPack.addEventListener('input', () => {
      const val = Number(settingCigsPerPack.value);
      if (val > 0) {
        state.cigsPerPack = val;
        localStorage.setItem('diary_cigs_per_pack', val);
        updateUI();
      }
    });

    // Pin Toggle option
    settingUsePin.addEventListener('change', () => {
      const usePin = settingUsePin.checked;
      state.usePin = usePin;
      localStorage.setItem('diary_use_pin', usePin);

      if (usePin) {
        settingPinInputGroup.classList.remove('hidden');
      } else {
        settingPinInputGroup.classList.add('hidden');
      }
      updateUI();
    });

    // Pin Code text edits
    settingPinVal.addEventListener('input', () => {
      const pin = settingPinVal.value.replace(/\D/g, '').slice(0, 4);
      settingPinVal.value = pin;
      if (pin.length === 4) {
        state.userPin = pin;
        localStorage.setItem('diary_user_pin', pin);
      }
    });
  }

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
    renderWater();
    renderDebts();
    renderHabits();
    renderExpenses();
    renderMood();
    renderVocalNotes();
    renderBiorhythms();
    renderSleepCalculator();
    renderShopList();
    renderStreak();
    renderWordOfDay();
    renderAnalytics();
  }

  function saveState() {
    localStorage.setItem('diary_tasks', JSON.stringify(state.tasks));
    localStorage.setItem('diary_cigarettes', JSON.stringify(state.cigarettes));
    localStorage.setItem('diary_water', JSON.stringify(state.water));

    // DEBTS AND UTILITIES PERSISTENCE
    localStorage.setItem('diary_debts', JSON.stringify(state.debts));
    localStorage.setItem('diary_habits', JSON.stringify(state.habits));
    localStorage.setItem('diary_expenses', JSON.stringify(state.expenses));
    localStorage.setItem('diary_moods', JSON.stringify(state.moods));
    localStorage.setItem('diary_vocal_notes', JSON.stringify(state.vocalNotes));
    localStorage.setItem('diary_step_count', JSON.stringify(state.stepCount));
    localStorage.setItem('diary_gratitudes', JSON.stringify(state.gratitudes));
    localStorage.setItem('diary_shop_list', JSON.stringify(state.shopList));
    localStorage.setItem('diary_sos_phone', state.sosPhone);
    localStorage.setItem('diary_sos_blood', state.sosBlood);
    localStorage.setItem('diary_notes_draft', state.notesDraft);
  }

  // WATER BALANCE TRACKER ENGINE
  function renderWater() {
    const ml = state.water[state.selectedDate] || 0;
    waterCounterVal.textContent = ml;

    if (ml === 0) {
      waterTrackerHelper.textContent = 'Вы не пили сегодня.';
      waterTrackerHelper.className = 'text-[10px] text-slate-400 mt-1';
    } else if (ml < 1500) {
      waterTrackerHelper.textContent = 'Ещё немного до нормы в 1.5 л!';
      waterTrackerHelper.className = 'text-[10px] text-amber-400 mt-1 font-medium';
    } else {
      waterTrackerHelper.textContent = 'Норма гидратации выполнена! Отлично!';
      waterTrackerHelper.className = 'text-[10px] text-emerald-400 mt-1 font-medium';
    }
  }

  btnWaterPlus.addEventListener('click', () => {
    const cur = state.water[state.selectedDate] || 0;
    state.water[state.selectedDate] = cur + 250;
    updateUI();
  });

  btnWaterReset.addEventListener('click', () => {
    state.water[state.selectedDate] = 0;
    updateUI();
  });

  // DEBT TRACKER IMPLEMENTATION
  function renderDebts() {
    const listEl = document.getElementById('debts-list');
    const balanceEl = document.getElementById('debt-balance-badge');
    if (!listEl) return;

    listEl.innerHTML = '';
    let totalBalance = 0; // gave (+) and took (-)

    state.debts.forEach(debt => {
      const isGave = debt.type === 'gave';
      const amount = debt.amount;
      totalBalance += isGave ? amount : -amount;

      const item = document.createElement('div');
      item.className = `flex items-center justify-between p-2.5 rounded-2xl border ${
        isGave ? 'bg-emerald-500/5 border-emerald-500/20' : 'bg-rose-500/5 border-rose-500/20'
      } text-xs`;

      item.innerHTML = `
        <div class="flex-1 min-w-0">
          <div class="flex items-center space-x-1">
            <span class="font-bold ${isGave ? 'text-emerald-400' : 'text-rose-400'}">
              ${isGave ? 'Дал' : 'Взял'}
            </span>
            <span class="font-semibold text-slate-200">${debt.name}</span>
          </div>
          <p class="text-[10px] text-slate-400 truncate">${debt.comment || 'Без комментария'}</p>
        </div>
        <div class="flex items-center space-x-2">
          <span class="font-extrabold ${isGave ? 'text-emerald-400' : 'text-rose-400'}">
            ${isGave ? '+' : '-'}${amount} ₽
          </span>
          <button class="btn-delete-debt p-1 hover:text-rose-400 text-slate-400" data-id="${debt.id}">
            <span class="material-icons-round text-sm">close</span>
          </button>
        </div>
      `;

      item.querySelector('.btn-delete-debt').addEventListener('click', () => {
        state.debts = state.debts.filter(d => d.id !== debt.id);
        updateUI();
      });

      listEl.appendChild(item);
    });

    balanceEl.textContent = `Баланс: ${totalBalance >= 0 ? '+' : ''}${totalBalance} ₽`;
    if (totalBalance > 0) {
      balanceEl.className = "text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400";
    } else if (totalBalance < 0) {
      balanceEl.className = "text-xs font-semibold px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-400";
    } else {
      balanceEl.className = "text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-800 text-slate-300";
    }
  }

  function initDebts() {
    const btnGave = document.getElementById('btn-debt-gave');
    const btnTook = document.getElementById('btn-debt-took');
    const inputName = document.getElementById('input-debt-name');
    const inputAmount = document.getElementById('input-debt-amount');
    const inputComment = document.getElementById('input-debt-comment');
    const btnAdd = document.getElementById('btn-add-debt');

    if (!btnGave) return;

    btnGave.addEventListener('click', () => {
      state.debtType = 'gave';
      btnGave.className = "flex-1 py-1.5 rounded-xl text-xs font-bold bg-emerald-500 text-slate-950 transition-all";
      btnTook.className = "flex-1 py-1.5 rounded-xl text-xs font-bold bg-slate-800 text-slate-300 hover:bg-slate-700 transition-all";
    });

    btnTook.addEventListener('click', () => {
      state.debtType = 'took';
      btnTook.className = "flex-1 py-1.5 rounded-xl text-xs font-bold bg-rose-500 text-slate-950 transition-all";
      btnGave.className = "flex-1 py-1.5 rounded-xl text-xs font-bold bg-slate-800 text-slate-300 hover:bg-slate-700 transition-all";
    });

    btnAdd.addEventListener('click', () => {
      const name = inputName.value.trim();
      const amount = parseFloat(inputAmount.value);
      const comment = inputComment.value.trim();

      if (!name || isNaN(amount) || amount <= 0) {
        alert('Пожалуйста, введите имя и корректную сумму долга.');
        return;
      }

      const newDebt = {
        id: Date.now() + Math.random().toString(36).substr(2, 5),
        type: state.debtType,
        name: name,
        amount: amount,
        comment: comment,
        date: getFormattedDate(new Date())
      };

      state.debts.push(newDebt);
      inputName.value = '';
      inputAmount.value = '';
      inputComment.value = '';
      updateUI();
    });
  }

  // WEB NOTIFICATIONS & ALARMS ENGINE
  let notifiedTasks = {};

  function initNotifications() {
    const btnNotify = document.getElementById('btn-request-notifications');
    if (!btnNotify) return;

    if ('Notification' in window) {
      if (Notification.permission === 'granted') {
        btnNotify.textContent = 'Разрешено';
        btnNotify.className = "text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/20";
      } else if (Notification.permission === 'denied') {
        btnNotify.textContent = 'Блокировано';
        btnNotify.className = "text-xs font-semibold px-2.5 py-1 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20";
      }
    } else {
      btnNotify.classList.add('hidden');
    }

    btnNotify.addEventListener('click', () => {
      if ('Notification' in window) {
        Notification.requestPermission().then(permission => {
          if (permission === 'granted') {
            btnNotify.textContent = 'Разрешено';
            btnNotify.className = "text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/20";
            new Notification('Ежедневник', { body: 'Уведомления успешно включены!' });
          } else {
            btnNotify.textContent = 'Блокировано';
            btnNotify.className = "text-xs font-semibold px-2.5 py-1 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20";
          }
        });
      } else {
        alert('Уведомления не поддерживаются вашим устройством.');
      }
    });

    // Background Scheduler checking every 10 seconds
    setInterval(() => {
      const now = new Date();
      const currentHHMM = [
        String(now.getHours()).padStart(2, '0'),
        String(now.getMinutes()).padStart(2, '0')
      ].join(':');
      const todayStr = getFormattedDate(now);

      // Check tasks scheduled for today that are uncompleted and have matching due time
      state.tasks.forEach(task => {
        if (!task.completed && task.date === todayStr && task.time === currentHHMM) {
          if (!notifiedTasks[task.id]) {
            notifiedTasks[task.id] = true;
            triggerNotification(`Напоминание о задаче!`, `${task.text} запланировано на ${task.time}`);
          }
        }
      });
    }, 10000);
  }

  function triggerNotification(title, body) {
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification(title, {
        body: body,
        icon: 'icon-192.png'
      });
    }
    // Also speech backup as a supreme option
    speakText(`${title}. ${body}`);
  }

  // ASSISTANTS & HELPERS IMPLEMENTATION - PART 1
  function renderHabits() {
    const habitsContainer = document.getElementById('habits-list-container');
    if (!habitsContainer) return;

    habitsContainer.innerHTML = '';
    state.habits.forEach(habit => {
      const isDoneForToday = habit.completed[state.selectedDate] || false;
      const row = document.createElement('div');
      row.className = "flex items-center justify-between p-1.5 rounded-xl bg-slate-900 border border-slate-800 text-[11px]";

      row.innerHTML = `
        <label class="flex items-center space-x-2 cursor-pointer flex-1 min-w-0">
          <input type="checkbox" class="chk-habit rounded text-emerald-500 bg-slate-950 border-slate-800" ${isDoneForToday ? 'checked' : ''}>
          <span class="truncate ${isDoneForToday ? 'line-through text-slate-500' : 'text-slate-200'}">${habit.name}</span>
        </label>
        <button class="btn-delete-habit text-slate-500 hover:text-rose-400 px-1">
          <span class="material-icons-round text-xs">close</span>
        </button>
      `;

      row.querySelector('.chk-habit').addEventListener('change', (e) => {
        habit.completed[state.selectedDate] = e.target.checked;
        updateUI();
      });

      row.querySelector('.btn-delete-habit').addEventListener('click', () => {
        state.habits = state.habits.filter(h => h.id !== habit.id);
        updateUI();
      });

      habitsContainer.appendChild(row);
    });
  }

  function initHabitForm() {
    const inputHabitName = document.getElementById('input-habit-name');
    const btnAddHabit = document.getElementById('btn-add-habit');
    if (!btnAddHabit) return;

    btnAddHabit.addEventListener('click', () => {
      const name = inputHabitName.value.trim();
      if (name) {
        state.habits.push({
          id: Date.now() + Math.random().toString(36).substr(2, 5),
          name: name,
          completed: {}
        });
        inputHabitName.value = '';
        updateUI();
      }
    });
  }

  // Pomodoro Timer Engine
  let pomoInterval = null;
  let pomoSeconds = 1500; // 25 mins
  let pomoRunning = false;

  function initPomodoro() {
    const timerVal = document.getElementById('pomo-timer-val');
    const btnStart = document.getElementById('btn-pomo-start');
    const btnReset = document.getElementById('btn-pomo-reset');
    if (!timerVal) return;

    function formatPomoTime(secs) {
      const m = Math.floor(secs / 60);
      const s = secs % 60;
      return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }

    btnStart.addEventListener('click', () => {
      pomoRunning = !pomoRunning;
      if (pomoRunning) {
        btnStart.textContent = 'Пауза';
        btnStart.className = "px-2 py-0.5 rounded bg-amber-500 text-slate-950 text-[10px] font-bold";
        pomoInterval = setInterval(() => {
          if (pomoSeconds > 0) {
            pomoSeconds--;
            timerVal.textContent = formatPomoTime(pomoSeconds);
          } else {
            clearInterval(pomoInterval);
            pomoRunning = false;
            pomoSeconds = 1500;
            timerVal.textContent = "25:00";
            btnStart.textContent = 'Старт';
            btnStart.className = "px-2 py-0.5 rounded bg-rose-500 text-slate-950 text-[10px] font-bold";
            triggerNotification('Помодоро завершен!', 'Отлично поработали. Время сделать 5 минутный перерыв!');
          }
        }, 1000);
      } else {
        clearInterval(pomoInterval);
        btnStart.textContent = 'Старт';
        btnStart.className = "px-2 py-0.5 rounded bg-rose-500 text-slate-950 text-[10px] font-bold";
      }
    });

    btnReset.addEventListener('click', () => {
      clearInterval(pomoInterval);
      pomoRunning = false;
      pomoSeconds = 1500;
      timerVal.textContent = "25:00";
      btnStart.textContent = 'Старт';
      btnStart.className = "px-2 py-0.5 rounded bg-rose-500 text-slate-950 text-[10px] font-bold";
    });
  }

  // Intermittent Fasting (16:8) Timer
  let fastingInterval = null;
  let fastingActive = false;
  let fastingSeconds = 57600; // 16 hours in seconds

  function initFasting() {
    const timerVal = document.getElementById('fasting-timer-val');
    const btnToggle = document.getElementById('btn-fasting-toggle');
    if (!timerVal) return;

    function formatFasting(secs) {
      const h = Math.floor(secs / 3600);
      const m = Math.floor((secs % 3600) / 60);
      const s = secs % 60;
      return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }

    btnToggle.addEventListener('click', () => {
      fastingActive = !fastingActive;
      if (fastingActive) {
        btnToggle.textContent = 'Стоп';
        btnToggle.className = "px-2 py-0.5 rounded bg-rose-500 text-slate-950 text-[10px] font-bold";
        fastingInterval = setInterval(() => {
          if (fastingSeconds > 0) {
            fastingSeconds--;
            timerVal.textContent = formatFasting(fastingSeconds);
          } else {
            clearInterval(fastingInterval);
            fastingActive = false;
            fastingSeconds = 57600;
            timerVal.textContent = "16:00:00";
            btnToggle.textContent = 'Начать';
            btnToggle.className = "px-2 py-0.5 rounded bg-purple-500 text-slate-950 text-[10px] font-bold";
            triggerNotification('Голодание завершено!', '16-часовое окно голодания успешно выдержано!');
          }
        }, 1000);
      } else {
        clearInterval(fastingInterval);
        btnToggle.textContent = 'Начать';
        btnToggle.className = "px-2 py-0.5 rounded bg-purple-500 text-slate-950 text-[10px] font-bold";
        fastingSeconds = 57600;
        timerVal.textContent = "16:00:00";
      }
    });
  }

  // Breathing Box Trainer
  let breathingActive = false;
  let breathingInterval = null;

  function initBreathing() {
    const btnToggle = document.getElementById('btn-breath-toggle');
    const label = document.getElementById('breath-label');
    const circle = document.getElementById('breath-circle');
    if (!btnToggle) return;

    let steps = ['Вдох', 'Задержка', 'Выдох', 'Задержка'];
    let idx = 0;

    btnToggle.addEventListener('click', () => {
      breathingActive = !breathingActive;
      if (breathingActive) {
        btnToggle.textContent = 'Стоп';
        btnToggle.className = "px-2 py-0.5 rounded bg-rose-500 text-slate-950 text-[10px] font-bold";

        const runCycle = () => {
          const current = steps[idx];
          label.textContent = current;
          if (current === 'Вдох') {
            circle.style.transform = 'scale(2)';
          } else if (current === 'Выдох') {
            circle.style.transform = 'scale(1)';
          }
          idx = (idx + 1) % steps.length;
        };

        runCycle();
        breathingInterval = setInterval(runCycle, 4000);
      } else {
        clearInterval(breathingInterval);
        btnToggle.textContent = 'Старт';
        btnToggle.className = "px-2 py-0.5 rounded bg-teal-500 text-slate-950 text-[10px] font-bold";
        label.textContent = 'Спокойствие';
        circle.style.transform = 'scale(1)';
        idx = 0;
      }
    });
  }

  // Screen Digital Detox Overlay Lock
  function initScreenDetox() {
    const btnDetox = document.getElementById('btn-detox-lock');
    if (!btnDetox) return;

    btnDetox.addEventListener('click', () => {
      const overlay = document.createElement('div');
      overlay.className = "fixed inset-0 bg-slate-950/98 z-50 flex flex-col items-center justify-center text-center p-6 space-y-4";
      overlay.innerHTML = `
        <span class="material-icons-round text-amber-400 text-6xl animate-pulse">phonelink_off</span>
        <h2 class="text-xl font-bold">Цифровой Детокс активен</h2>
        <p class="text-xs text-slate-400 max-w-[240px]">Отдохните от телефона. Дайте глазам и разуму расслабиться в течение 5 минут.</p>
        <div class="text-2xl font-extrabold text-amber-500" id="detox-countdown">05:00</div>
        <button id="btn-skip-detox" class="text-xs text-slate-500 underline py-2">Досрочно выйти</button>
      `;

      document.body.appendChild(overlay);
      let duration = 300; // 5 mins

      const timer = setInterval(() => {
        if (duration > 0) {
          duration--;
          const m = Math.floor(duration / 60);
          const s = duration % 60;
          const label = document.getElementById('detox-countdown');
          if (label) label.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        } else {
          clearInterval(timer);
          overlay.remove();
          triggerNotification('Детокс завершен!', 'Вы успешно разгрузили мозг. Добро пожаловать назад!');
        }
      }, 1000);

      overlay.querySelector('#btn-skip-detox').addEventListener('click', () => {
        clearInterval(timer);
        overlay.remove();
      });
    });
  }

  // ASSISTANTS & HELPERS IMPLEMENTATION - PART 2
  // Expenses Tracker
  function renderExpenses() {
    const listEl = document.getElementById('expenses-list');
    const totalEl = document.getElementById('expenses-total-val');
    if (!listEl) return;

    listEl.innerHTML = '';
    const dayExpenses = state.expenses[state.selectedDate] || [];
    let total = 0;

    dayExpenses.forEach((exp, idx) => {
      total += exp.amount;
      const row = document.createElement('div');
      row.className = "flex justify-between items-center bg-slate-900 border border-slate-800/60 p-1.5 rounded-xl text-[11px]";
      row.innerHTML = `
        <span class="truncate text-slate-300">${exp.name}</span>
        <div class="flex items-center space-x-1.5 font-bold">
          <span class="text-amber-400">${exp.amount} ₽</span>
          <button class="btn-del-expense text-slate-500 hover:text-rose-400 font-normal">×</button>
        </div>
      `;
      row.querySelector('.btn-del-expense').addEventListener('click', () => {
        state.expenses[state.selectedDate] = dayExpenses.filter((_, i) => i !== idx);
        updateUI();
      });
      listEl.appendChild(row);
    });

    totalEl.textContent = `Итого: ${total} ₽`;
  }

  function initExpensesForm() {
    const btnAdd = document.getElementById('btn-add-expense');
    const inputName = document.getElementById('input-expense-name');
    const inputAmount = document.getElementById('input-expense-amount');
    if (!btnAdd) return;

    btnAdd.addEventListener('click', () => {
      const name = inputName.value.trim();
      const val = parseFloat(inputAmount.value);
      if (name && !isNaN(val) && val > 0) {
        if (!state.expenses[state.selectedDate]) {
          state.expenses[state.selectedDate] = [];
        }
        state.expenses[state.selectedDate].push({ name, amount: val });
        inputName.value = '';
        inputAmount.value = '';
        updateUI();
      }
    });
  }

  // Mood Index Tracker
  function renderMood() {
    const label = document.getElementById('mood-status-label');
    if (!label) return;

    const currentMood = state.moods[state.selectedDate] || null;
    const btns = document.querySelectorAll('.btn-mood-select');

    btns.forEach(btn => {
      const moodVal = btn.getAttribute('data-mood');
      if (moodVal === String(currentMood)) {
        btn.classList.add('scale-150', 'brightness-125');
      } else {
        btn.classList.remove('scale-150', 'brightness-125');
      }
    });

    const feedbacks = {
      1: '😢 Чувствую себя ужасно',
      2: '😕 Настроение так себе',
      3: '😐 Обычный нормальный день',
      4: '😊 Хорошее продуктивное настроение',
      5: '🤩 Превосходный радостный день!'
    };

    label.textContent = currentMood ? feedbacks[currentMood] : 'Настроение не выбрано';
  }

  function initMoodSelection() {
    const btns = document.querySelectorAll('.btn-mood-select');
    btns.forEach(btn => {
      btn.addEventListener('click', () => {
        const moodVal = parseInt(btn.getAttribute('data-mood'));
        state.moods[state.selectedDate] = moodVal;
        updateUI();
      });
    });
  }

  // Vocal voice notes helper
  function renderVocalNotes() {
    const listEl = document.getElementById('vocal-notes-list');
    if (!listEl) return;
    listEl.innerHTML = '';
    state.vocalNotes.forEach((note, idx) => {
      const el = document.createElement('div');
      el.className = "flex justify-between items-center bg-slate-900 border border-slate-800 p-1 rounded text-[10px] text-slate-300";
      el.innerHTML = `
        <span class="truncate">${note.text}</span>
        <button class="btn-del-vnote text-rose-500 hover:text-rose-400">Удалить</button>
      `;
      el.querySelector('.btn-del-vnote').addEventListener('click', () => {
        state.vocalNotes = state.vocalNotes.filter((_, i) => i !== idx);
        updateUI();
      });
      listEl.appendChild(el);
    });
  }

  function initVocalNotes() {
    const btn = document.getElementById('btn-record-note');
    if (!btn) return;
    btn.addEventListener('click', () => {
      const text = prompt('Введите вашу голосовую/быструю мысль:');
      if (text && text.trim()) {
        state.vocalNotes.push({ text: text.trim(), date: getFormattedDate(new Date()) });
        updateUI();
      }
    });
  }

  // Coin Flipper helper
  function initCoinFlipper() {
    const btn = document.getElementById('btn-coin-flip');
    const res = document.getElementById('coin-result');
    if (!btn) return;

    btn.addEventListener('click', () => {
      res.textContent = 'Крутится...';
      setTimeout(() => {
        const isHeads = Math.random() > 0.5;
        res.textContent = isHeads ? '🪙 ОРЁЛ' : '🪙 РЕШКА';
      }, 500);
    });
  }

  // Biorhythms calculator helper
  function renderBiorhythms() {
    const phys = document.getElementById('bio-phys');
    const emo = document.getElementById('bio-emo');
    const intel = document.getElementById('bio-int');
    if (!phys) return;

    // Use a fixed birthdate epoch offset to compute cyclical wave percentages
    const today = new Date();
    const tTime = today.getTime();

    const pPercent = Math.round((Math.sin((2 * Math.PI * (tTime / 86400000)) / 23) + 1) * 50);
    const ePercent = Math.round((Math.sin((2 * Math.PI * (tTime / 86400000)) / 28) + 1) * 50);
    const iPercent = Math.round((Math.sin((2 * Math.PI * (tTime / 86400000)) / 33) + 1) * 50);

    phys.textContent = `${pPercent}%`;
    emo.textContent = `${ePercent}%`;
    intel.textContent = `${iPercent}%`;
  }

  // Steps goal tracker helper
  function initStepTracker() {
    const input = document.getElementById('input-steps-val');
    const label = document.getElementById('lbl-steps-percent');
    if (!input) return;

    const currentVal = state.stepCount[state.selectedDate] || 0;
    input.value = currentVal || '';

    const percent = Math.min(100, Math.round((currentVal / 10000) * 100));
    label.textContent = `${percent}% от цели 10к`;

    input.addEventListener('input', () => {
      const val = parseInt(input.value) || 0;
      state.stepCount[state.selectedDate] = val;
      saveState();

      const pct = Math.min(100, Math.round((val / 10000) * 100));
      label.textContent = `${pct}% от цели 10к`;
    });
  }

  // Sleep wake calculator helper
  function renderSleepCalculator() {
    const cyclesEl = document.getElementById('lbl-sleep-cycles');
    if (!cyclesEl) return;

    const now = new Date();
    const formatTime = (dateObj) => {
      return [
        String(dateObj.getHours()).padStart(2, '0'),
        String(dateObj.getMinutes()).padStart(2, '0')
      ].join(':');
    };

    // Calculate times (sleep cycles of 90 minutes)
    const options = [];
    for (let c = 3; c <= 6; c++) {
      const target = new Date(now.getTime() + (c * 90 * 60 * 1000) + (14 * 60 * 1000)); // +14m sleep onset latency
      options.push(formatTime(target));
    }
    cyclesEl.textContent = options.join(' | ');
  }

  // BMI calculator helper
  function initBMICalculator() {
    const btn = document.getElementById('btn-bmi-calc');
    const res = document.getElementById('lbl-bmi-res');
    if (!btn) return;

    btn.addEventListener('click', () => {
      const w = parseFloat(document.getElementById('input-bmi-w').value);
      const h = parseFloat(document.getElementById('input-bmi-h').value) / 100;
      if (w > 0 && h > 0) {
        const bmi = (w / (h * h)).toFixed(1);
        let status = 'Норма';
        if (bmi < 18.5) status = 'Дефицит';
        else if (bmi > 25) status = 'Избыток';
        res.textContent = `ИМТ: ${bmi} (${status})`;
      } else {
        res.textContent = 'Заполните вес и рост';
      }
    });
  }

  // Gratitude diary helper
  function initGratitude() {
    const btn = document.getElementById('btn-gratitude-save');
    const input = document.getElementById('input-gratitude-text');
    if (!btn) return;

    btn.addEventListener('click', () => {
      const text = input.value.trim();
      if (text) {
        state.gratitudes.push({ text: text, date: state.selectedDate });
        input.value = '';
        saveState();
        alert('Благодарность записана!');
      }
    });
  }

  // Decibel sound meter tool
  let decibelInterval = null;
  function initDecibelMeter() {
    const btn = document.getElementById('btn-decibel-start');
    const val = document.getElementById('lbl-decibel-val');
    if (!btn) return;

    btn.addEventListener('click', () => {
      if (decibelInterval) {
        clearInterval(decibelInterval);
        decibelInterval = null;
        btn.textContent = 'Старт';
        btn.className = "px-2 py-0.5 rounded bg-brand-500 text-slate-950 text-[10px] font-bold";
        val.textContent = '0 дБ';
      } else {
        btn.textContent = 'Стоп';
        btn.className = "px-2 py-0.5 rounded bg-rose-500 text-slate-950 text-[10px] font-bold";
        decibelInterval = setInterval(() => {
          // Mock environmental simulation fluctuations
          const randDecibels = Math.floor(35 + Math.random() * 45);
          val.textContent = `${randDecibels} дБ`;
        }, 800);
      }
    });
  }

  // SOS Emergency Card
  function initSOSCard() {
    const phone = document.getElementById('input-sos-phone');
    const blood = document.getElementById('input-sos-blood');
    const btn = document.getElementById('btn-sos-save');
    if (!btn) return;

    phone.value = state.sosPhone;
    blood.value = state.sosBlood;

    btn.addEventListener('click', () => {
      state.sosPhone = phone.value.trim();
      state.sosBlood = blood.value.trim();
      saveState();
      alert('Данные SOS карты сохранены!');
    });
  }

  // Stopwatch helper
  let swInterval = null;
  let swMillis = 0;
  let swActive = false;

  function initStopwatch() {
    const btn = document.getElementById('btn-stopwatch-toggle');
    const btnReset = document.getElementById('btn-stopwatch-reset');
    const val = document.getElementById('lbl-stopwatch-val');
    if (!btn) return;

    function formatStopwatch(ms) {
      const mins = Math.floor(ms / 60000);
      const secs = Math.floor((ms % 60000) / 1000);
      const hundredths = Math.floor((ms % 1000) / 10);
      return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}.${String(hundredths).padStart(2, '0')}`;
    }

    btn.addEventListener('click', () => {
      swActive = !swActive;
      if (swActive) {
        btn.textContent = 'Стоп';
        btn.className = "px-1.5 py-0.5 rounded bg-rose-500 text-slate-950 font-bold text-[9px]";
        const startTime = Date.now() - swMillis;
        swInterval = setInterval(() => {
          swMillis = Date.now() - startTime;
          val.textContent = formatStopwatch(swMillis);
        }, 10);
      } else {
        clearInterval(swInterval);
        btn.textContent = 'Старт';
        btn.className = "px-1.5 py-0.5 rounded bg-amber-500 text-slate-950 font-bold text-[9px]";
      }
    });

    btnReset.addEventListener('click', () => {
      clearInterval(swInterval);
      swActive = false;
      swMillis = 0;
      val.textContent = '00:00.00';
      btn.textContent = 'Старт';
      btn.className = "px-1.5 py-0.5 rounded bg-amber-500 text-slate-950 font-bold text-[9px]";
    });
  }

  // Kitchen Timer helper
  let kitchenInterval = null;
  let kitchenSecs = 0;

  function initKitchenTimer() {
    const val = document.getElementById('lbl-kitchen-val');
    const presets = document.querySelectorAll('.btn-kitchen-preset');
    if (!val) return;

    presets.forEach(btn => {
      btn.addEventListener('click', () => {
        clearInterval(kitchenInterval);
        kitchenSecs = parseInt(btn.getAttribute('data-sec'));

        const run = () => {
          if (kitchenSecs > 0) {
            kitchenSecs--;
            const m = Math.floor(kitchenSecs / 60);
            const s = kitchenSecs % 60;
            val.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
          } else {
            clearInterval(kitchenInterval);
            val.textContent = 'ГОТОВО!';
            triggerNotification('Кухонный таймер!', 'Ваш таймер заваривания чая или варки яиц завершен!');
          }
        };

        run();
        kitchenInterval = setInterval(run, 1000);
      });
    });
  }

  // Dice Roller helper
  function initDiceRoller() {
    const btn = document.getElementById('btn-dice-roll');
    const val = document.getElementById('dice-result');
    if (!btn) return;

    btn.addEventListener('click', () => {
      val.textContent = '🎲 ...';
      setTimeout(() => {
        const roll = Math.floor(1 + Math.random() * 6);
        val.textContent = `🎲 ${roll}`;
      }, 400);
    });
  }

  // Shopping Checklist helper
  function renderShopList() {
    const listEl = document.getElementById('shop-list-container');
    if (!listEl) return;
    listEl.innerHTML = '';

    state.shopList.forEach((item, idx) => {
      const el = document.createElement('div');
      el.className = "flex items-center justify-between text-[10px] bg-slate-950/40 p-1 rounded border border-slate-800/80";
      el.innerHTML = `
        <label class="flex items-center space-x-1.5 cursor-pointer flex-1 truncate">
          <input type="checkbox" class="chk-shop" ${item.bought ? 'checked' : ''}>
          <span class="${item.bought ? 'line-through text-slate-500' : 'text-slate-200'}">${item.text}</span>
        </label>
        <button class="btn-del-shop text-rose-500 px-1 font-bold">×</button>
      `;

      el.querySelector('.chk-shop').addEventListener('change', (e) => {
        item.bought = e.target.checked;
        saveState();
        renderShopList();
      });

      el.querySelector('.btn-del-shop').addEventListener('click', () => {
        state.shopList = state.shopList.filter((_, i) => i !== idx);
        saveState();
        renderShopList();
      });

      listEl.appendChild(el);
    });
  }

  function initShoppingList() {
    const btn = document.getElementById('btn-add-shop');
    const input = document.getElementById('input-shop-item');
    if (!btn) return;

    btn.addEventListener('click', () => {
      const text = input.value.trim();
      if (text) {
        state.shopList.push({ text: text, bought: false });
        input.value = '';
        saveState();
        renderShopList();
      }
    });
  }

  // Tip Calculator helper
  function initTipCalculator() {
    const input = document.getElementById('input-tip-bill');
    const res = document.getElementById('lbl-tip-res');
    const pcts = document.querySelectorAll('.btn-tip-pct');
    if (!input) return;

    pcts.forEach(btn => {
      btn.addEventListener('click', () => {
        const pct = parseFloat(btn.getAttribute('data-pct'));
        const bill = parseFloat(input.value);
        if (!isNaN(bill) && bill > 0) {
          const total = bill + (bill * (pct / 100));
          res.textContent = `Итого с чаевыми: ${Math.round(total)} ₽`;
        } else {
          res.textContent = 'Укажите сумму счета';
        }
      });
    });
  }

  // Quote of the Day generator helper
  function initQuoteRotator() {
    const btn = document.getElementById('btn-quote-refresh');
    const textEl = document.getElementById('lbl-quote-text');
    if (!btn) return;

    const quotes = [
      '"Каждый день - это новый шанс стать лучше."',
      '"Продуктивность - это не количество дел, а их важность."',
      '"Маленькие шаги ведут к великим достижениям."',
      '"Позаботьтесь о своем разуме, и тело ответит благодарностью."',
      '"Концентрация рождается в тишине и детоксе экрана."',
      '"Ваше здоровье - это лучший капитал."'
    ];

    btn.addEventListener('click', () => {
      const randIdx = Math.floor(Math.random() * quotes.length);
      textEl.textContent = quotes[randIdx];
    });
  }

  // Flashlight screen tool helper
  function initFlashlight() {
    const btn = document.getElementById('btn-flash-toggle');
    if (!btn) return;

    btn.addEventListener('click', () => {
      const overlay = document.createElement('div');
      overlay.className = "fixed inset-0 bg-white z-50 flex flex-col items-center justify-center text-slate-950 font-bold text-center p-6";
      overlay.innerHTML = `
        <span class="material-icons-round text-yellow-500 text-6xl">lightbulb</span>
        <h2 class="text-xl mt-2">ЭКРАННЫЙ ФОНАРИК</h2>
        <p class="text-xs text-slate-500">Яркость экрана на максимум</p>
        <button id="btn-flash-off" class="mt-8 px-6 py-2 bg-slate-950 text-white rounded-full font-bold text-xs">ВЫКЛЮЧИТЬ</button>
      `;
      document.body.appendChild(overlay);

      overlay.querySelector('#btn-flash-off').addEventListener('click', () => {
        overlay.remove();
      });
    });
  }

  // Streak tracker days indicator helper
  function renderStreak() {
    const el = document.getElementById('lbl-streak-days');
    if (!el) return;

    // Count days which have at least one completed task in state.tasks
    const completedDays = new Set();
    state.tasks.forEach(t => {
      if (t.completed) completedDays.add(t.date);
    });

    const count = completedDays.size || 1;
    el.textContent = `🔥 ${count} дней подряд`;
  }

  // Password Generator helper
  function initPasswordGenerator() {
    const btn = document.getElementById('btn-generate-pwd');
    const val = document.getElementById('lbl-generated-pwd');
    if (!btn) return;

    btn.addEventListener('click', () => {
      const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
      let pwd = "";
      for (let i = 0; i < 12; i++) {
        pwd += chars.charAt(Math.floor(Math.random() * chars.length));
      }
      val.textContent = pwd;
    });
  }

  // Stress psychological test helper
  function initStressTest() {
    const btns = document.querySelectorAll('.btn-stress-test');
    const res = document.getElementById('lbl-stress-res');
    if (!res) return;

    btns.forEach(btn => {
      btn.addEventListener('click', () => {
        const score = btn.getAttribute('data-score');
        if (score === "1") {
          res.textContent = "Рекомендация: Попробуйте 'Дыхание' в меню инструментов!";
          res.className = "text-center font-bold text-rose-400 mt-1";
        } else if (score === "2") {
          res.textContent = "Рекомендация: Пейте воду или совершите прогулку.";
          res.className = "text-center font-bold text-amber-400 mt-1";
        } else {
          res.textContent = "Отлично! Так держать!";
          res.className = "text-center font-bold text-emerald-400 mt-1";
        }
      });
    });
  }

  // Simple notepad drafting draft text
  function initNotesDraft() {
    const input = document.getElementById('input-notes-draft');
    if (!input) return;

    input.value = state.notesDraft;
    input.addEventListener('input', () => {
      state.notesDraft = input.value;
      localStorage.setItem('diary_notes_draft', state.notesDraft);
    });
  }

  // Word of the Day rotation helper
  function renderWordOfDay() {
    const w = document.getElementById('lbl-word-day');
    const d = document.getElementById('lbl-word-day-def');
    if (!w) return;

    const items = [
      { w: 'Инсайт', d: 'Внезапное интуитивное понимание сути проблемы.' },
      { w: 'Осознанность', d: 'Способность удерживать внимание на текущем моменте.' },
      { w: 'Эмпатия', d: 'Осознанное сопереживание эмоциональному состоянию другого.' },
      { w: 'Рефлексия', d: 'Анализ своего психического состояния и поступков.' }
    ];

    const idx = Math.floor(new Date().getDate() % items.length);
    w.textContent = items[idx].w;
    d.textContent = `"${items[idx].d}"`;
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

    // Sort: uncompleted tasks chronologically if they have a time, otherwise by priority
    const priorityWeight = { high: 3, medium: 2, low: 1 };
    filtered.sort((a, b) => {
      if (a.completed !== b.completed) return a.completed - b.completed;

      // If both are not completed and have a time, sort by time chronologically
      if (!a.completed) {
        if (a.time && b.time) {
          return a.time.localeCompare(b.time);
        }
        if (a.time && !b.time) return -1; // tasks with time first
        if (!a.time && b.time) return 1;
      }

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

        // Optional Time badge
        const timeBadge = task.time
          ? `<span class="flex items-center space-x-0.5 px-1.5 py-0.5 rounded-md text-[9px] font-bold tracking-wide uppercase bg-sky-500/10 text-sky-400 border border-sky-500/20">
               <span class="material-icons-round text-[10px]">schedule</span>
               <span>${task.time}</span>
             </span>`
          : '';

        // Optional Transferred "Перенесено" badge
        const transferredBadge = task.transferred
          ? `<span class="flex items-center space-x-0.5 px-1.5 py-0.5 rounded-md text-[9px] font-bold tracking-wide uppercase bg-purple-500/10 text-purple-400 border border-purple-500/20" title="Перенесено со вчерашнего дня">
               <span class="material-icons-round text-[10px]">redo</span>
               <span>Перенесено</span>
             </span>`
          : '';

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
              <div class="flex flex-wrap items-center gap-1.5 mt-1">
                ${timeBadge}
                ${transferredBadge}
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
  function addNewTask(text, priority = 'medium', category = 'Личное', time = '') {
    if (!text.trim()) return;

    const newTask = {
      id: Date.now() + Math.random().toString(36).substr(2, 5),
      text: text.trim(),
      date: state.selectedDate,
      completed: false,
      priority: priority,
      category: category,
      time: time || ''
    };

    state.tasks.push(newTask);
    updateUI();
  }

  btnAddTask.addEventListener('click', () => {
    const text = inputTaskText.value;
    const time = inputTaskTime.value;
    if (text.trim()) {
      addNewTask(text, state.currentPriority, state.currentCategory, time);
      inputTaskText.value = '';
      inputTaskTime.value = '';
      // Reset input bar flags
      resetInputBarChoices();
    }
  });

  inputTaskText.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      const text = inputTaskText.value;
      const time = inputTaskTime.value;
      if (text.trim()) {
        addNewTask(text, state.currentPriority, state.currentCategory, time);
        inputTaskText.value = '';
        inputTaskTime.value = '';
        resetInputBarChoices();
      }
    }
  });

  // Highlight/toggle time picker icon when selected
  inputTaskTime.addEventListener('change', () => {
    if (inputTaskTime.value) {
      timeIcon.style.color = '#0ea5e9'; // brand-500
    } else {
      timeIcon.style.color = '';
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
    timeIcon.style.color = '';
    inputTaskTime.value = '';
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
    const costPerCig = state.cigsPerPack > 0 ? (state.packCost / state.cigsPerPack) : 0;
    const lifeMinutesLostPerCig = 11; // avg 11 minutes of life

    const moneySpent = Math.round(totalCigs * costPerCig);
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
      // Analyze and extract time expression from the task string
      // e.g. "купить хлеб в 15:00", "позвонить врачу в 9:30", "сделать зарядку в 14 часов"
      let extractedTime = '';
      const timeRegexHHMM = /\b(?:в|на)\s+([0-2]?\d):([0-5]\d)\b/i;
      const timeRegexHours = /\b(?:в|на)\s+([0-2]?\d)\s*(?:часов|часа|час|ч)\b/i;

      let match = taskContent.match(timeRegexHHMM);
      if (match) {
        let hour = match[1].padStart(2, '0');
        let minute = match[2].padStart(2, '0');
        extractedTime = `${hour}:${minute}`;
        // Strip the time expression from the task content
        taskContent = taskContent.replace(match[0], '').trim();
      } else {
        match = taskContent.match(timeRegexHours);
        if (match) {
          let hour = match[1].padStart(2, '0');
          extractedTime = `${hour}:00`;
          taskContent = taskContent.replace(match[0], '').trim();
        }
      }

      // Cleanup trailing punctuation/spaces
      taskContent = taskContent.replace(/[,.!?;:]+$/, '').trim();

      // Analyze category context
      let cat = 'Личное';
      if (taskContent.toLowerCase().includes('работу') || taskContent.toLowerCase().includes('проект')) cat = 'Работа';
      if (taskContent.toLowerCase().includes('дома') || taskContent.toLowerCase().includes('убрать') || taskContent.toLowerCase().includes('купить')) cat = 'Дом';
      if (taskContent.toLowerCase().includes('здоровье') || taskContent.toLowerCase().includes('аптека') || taskContent.toLowerCase().includes('врач')) cat = 'Здоровье';

      addNewTask(taskContent, 'medium', cat, extractedTime);

      const timeVoiceAnnounce = extractedTime ? ` на ${extractedTime}` : '';
      speakText(`Добавлена задача: ${taskContent}${timeVoiceAnnounce}`);
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
      report += `Активные задачи: ${active.map((t, idx) => {
        const timePart = t.time ? ` на ${t.time}` : '';
        const transPart = t.transferred ? ' перенесена со вчера' : '';
        return `${idx + 1}, ${t.text}${timePart}${transPart}`;
      }).join('. ')}. `;
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
      version: "1.1",
      tasks: state.tasks,
      cigarettes: state.cigarettes,
      water: state.water,
      packCost: state.packCost,
      cigsPerPack: state.cigsPerPack,
      usePin: state.usePin,
      userPin: state.userPin,
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
          if (importedData.water && typeof importedData.water === 'object') {
            state.water = importedData.water;
          }
          if (importedData.packCost !== undefined) {
            state.packCost = Number(importedData.packCost);
            localStorage.setItem('diary_pack_cost', state.packCost);
          }
          if (importedData.cigsPerPack !== undefined) {
            state.cigsPerPack = Number(importedData.cigsPerPack);
            localStorage.setItem('diary_cigs_per_pack', state.cigsPerPack);
          }
          if (importedData.usePin !== undefined) {
            state.usePin = Boolean(importedData.usePin);
            localStorage.setItem('diary_use_pin', state.usePin);
          }
          if (importedData.userPin !== undefined) {
            state.userPin = String(importedData.userPin);
            localStorage.setItem('diary_user_pin', state.userPin);
          }
          if (importedData.theme) {
            localStorage.setItem('diary_theme', importedData.theme);
          }
          if (importedData.visited) {
            localStorage.setItem('diary_visited', importedData.visited);
          }

          updateUI();
          initTheme();
          initSettings();
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
