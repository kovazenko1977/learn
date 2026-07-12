// Core Application State
let appState = {
  currentUser: null,
  users: [], // Loaded dynamically from roster
  notes: [],
  tasks: [],
  settings: {
    theme: 'light',
    accent: 'blue',
    fontSize: 'medium',
    audioFeedback: true
  },
  discussions: [],
  activeDiscussionId: null,
  currentCalendarDate: new Date(),
  selectedCalendarDate: new Date()
};

// Keypad audio feedback using Web Audio API (sine wave beep)
let audioCtx = null;
function playKeyBeep() {
  if (!appState.settings.audioFeedback) return;
  try {
    if (!audioCtx) {
      audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.connect(gain);
    gain.connect(audioCtx.destination);

    osc.type = 'sine';
    osc.frequency.setValueAtTime(440, audioCtx.currentTime); // A4 note
    gain.gain.setValueAtTime(0.05, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + 0.1);

    osc.start(audioCtx.currentTime);
    osc.stop(audioCtx.currentTime + 0.1);
  } catch (e) {
    console.warn('Audio feedback failed or not allowed', e);
  }
}

// Service Worker registration
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('sw.js')
      .then(reg => console.log('Service Worker registered successfully!', reg.scope))
      .catch(err => console.error('Service Worker registration failed:', err));
  });
}

// Custom PWA Install prompt handling
let deferredPrompt = null;
window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;
  const installBanner = document.getElementById('pwa-install-banner');
  if (installBanner) {
    installBanner.classList.remove('hidden');
  }
});

// Setup PWA Banner Events
document.addEventListener('DOMContentLoaded', () => {
  const installBanner = document.getElementById('pwa-install-banner');
  const installBtn = document.getElementById('pwa-install-btn');
  const closeBtn = document.getElementById('pwa-close-banner-btn');

  if (installBtn && closeBtn && installBanner) {
    installBtn.addEventListener('click', async () => {
      if (deferredPrompt) {
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        console.log(`User response to the install prompt: ${outcome}`);
        deferredPrompt = null;
      }
      installBanner.classList.add('hidden');
    });

    closeBtn.addEventListener('click', () => {
      installBanner.classList.add('hidden');
    });
  }
});

// Request Notification Permission on load
if ('Notification' in window && Notification.permission === 'default') {
  Notification.requestPermission();
}

// API helper functions
async function fetchFromApi(action, data = null, method = 'GET') {
  const url = `api.php?action=${action}`;
  const options = { method };
  if (data) {
    if (data instanceof FormData) {
      options.body = data;
    } else {
      options.headers = { 'Content-Type': 'application/json' };
      options.body = JSON.stringify(data);
    }
  }

  try {
    const response = await fetch(url, options);
    if (!response.ok) throw new Error('API request failed');
    return await response.json();
  } catch (error) {
    console.warn('API error (using offline fallback where applicable):', error);
    return { success: false, error: true };
  }
}

// Load all application data
async function loadAppData() {
  const res = await fetchFromApi('get_data');
  if (res && res.success) {
    appState.notes = res.notes || [];
    appState.tasks = res.tasks || [];

    // Default safe settings fallback
    const defaultSettings = { theme: 'light', accent: 'blue', fontSize: 'medium', audioFeedback: true };
    appState.settings = Object.assign(defaultSettings, res.settings);
    appState.discussions = res.discussions || [];

    // Save to localStorage for robust offline capability
    localStorage.setItem('pwa_notes', JSON.stringify(appState.notes));
    localStorage.setItem('pwa_tasks', JSON.stringify(appState.tasks));
    localStorage.setItem('pwa_settings', JSON.stringify(appState.settings));
    localStorage.setItem('pwa_discussions', JSON.stringify(appState.discussions));
  } else {
    // Offline mode: load from localStorage fallback
    appState.notes = JSON.parse(localStorage.getItem('pwa_notes')) || [];
    appState.tasks = JSON.parse(localStorage.getItem('pwa_tasks')) || [];
    appState.settings = JSON.parse(localStorage.getItem('pwa_settings')) || { theme: 'light', accent: 'blue', fontSize: 'medium', audioFeedback: true };
    appState.discussions = JSON.parse(localStorage.getItem('pwa_discussions')) || [];
  }

  // Load users roster
  const usersRes = await fetchFromApi('get_users');
  if (usersRes && usersRes.success) {
    appState.users = usersRes.users || [];
    localStorage.setItem('pwa_users_roster', JSON.stringify(appState.users));
  } else {
    appState.users = JSON.parse(localStorage.getItem('pwa_users_roster')) || [
      { id: 1, name: 'Коваженко С.Б.' },
      { id: 2, name: 'Иванов И.И.' },
      { id: 3, name: 'Петров П.П.' },
      { id: 4, name: 'Сидоров С.С.' }
    ];
  }

  populateAssigneesDropdowns();
  applySettings();
  renderAll();
}

function populateAssigneesDropdowns() {
  const select = document.getElementById('task-assignee-input');
  if (!select) return;
  select.innerHTML = '';

  appState.users.forEach(user => {
    const opt = document.createElement('option');
    opt.value = user.id;
    opt.innerText = user.name;
    select.appendChild(opt);
  });
}

// Save all application data (auto-saves on changes)
async function saveAppData() {
  // Save to local storage first for offline first
  localStorage.setItem('pwa_notes', JSON.stringify(appState.notes));
  localStorage.setItem('pwa_tasks', JSON.stringify(appState.tasks));
  localStorage.setItem('pwa_settings', JSON.stringify(appState.settings));
  localStorage.setItem('pwa_discussions', JSON.stringify(appState.discussions));

  // Try sending to the server
  const payload = {
    notes: appState.notes,
    tasks: appState.tasks,
    settings: appState.settings,
    discussions: appState.discussions
  };
  await fetchFromApi('save_data', payload, 'POST');
}

// Apply settings (Theme, Accents, Font Size, Audio Toggles)
function applySettings() {
  // Reset all themes
  document.body.classList.remove('dark-theme', 'midnight-theme', 'sepia-theme');
  if (appState.settings.theme === 'dark') {
    document.body.classList.add('dark-theme');
  } else if (appState.settings.theme === 'midnight') {
    document.body.classList.add('midnight-theme');
  } else if (appState.settings.theme === 'sepia') {
    document.body.classList.add('sepia-theme');
  }

  // Set Accents
  document.body.classList.remove('accent-blue', 'accent-green', 'accent-purple', 'accent-orange');
  const accent = appState.settings.accent || 'blue';
  document.body.classList.add(`accent-${accent}`);

  // Update accent circular selection active state in Settings
  document.querySelectorAll('.accent-dot').forEach(dot => {
    if (dot.getAttribute('data-accent') === accent) {
      dot.classList.add('active');
    } else {
      dot.classList.remove('active');
    }
  });

  // Font Size
  document.body.classList.remove('font-small', 'font-medium', 'font-large');
  document.body.classList.add(`font-${appState.settings.fontSize || 'medium'}`);
  document.getElementById('font-size-select').value = appState.settings.fontSize || 'medium';

  // Sound feedback toggle check
  const soundChkBx = document.getElementById('settings-audio-feedback-input');
  if (soundChkBx) {
    soundChkBx.checked = !!appState.settings.audioFeedback;
  }

  // Set username profile field in settings
  if (appState.currentUser) {
    const nameInput = document.getElementById('settings-username-input');
    if (nameInput) nameInput.value = appState.currentUser.name;
  }
}

// Authorization logic
let pinBuffer = '';
function setupLockScreen() {
  const lockScreen = document.getElementById('lock-screen');
  const appContainer = document.getElementById('app-container');
  const pinDots = document.querySelectorAll('.pin-dot');
  const pinError = document.getElementById('pin-error');

  function updatePinDots() {
    pinDots.forEach((dot, index) => {
      if (index < pinBuffer.length) {
        dot.classList.add('filled');
      } else {
        dot.classList.remove('filled');
      }
    });
  }

  async function submitPin() {
    if (pinBuffer.length < 4) {
      pinError.innerText = 'Введите не менее 4 цифр';
      return;
    }

    // Request multi-user secure backend login verification
    const res = await fetchFromApi('login', { pin: pinBuffer }, 'POST');
    if (res && res.success) {
      appState.currentUser = res.user;
      document.getElementById('username-display').innerText = res.user.name;
      lockScreen.classList.add('hidden');
      appContainer.classList.remove('hidden');

      // Load and render app
      await loadAppData();
      startReminderScheduler();
    } else {
      pinError.innerText = res.message || 'Ошибка подключения к серверу';

      // Offline fallback bypass mapping for local demo simulation:
      // Map selector ID values to standard pins for seamless execution
      const selectedUserId = parseInt(document.getElementById('lock-user-select').value);
      const offlineUserMap = {
        1: { name: 'Коваженко С.Б.', pin: '1234' },
        2: { name: 'Иванов И.И.', pin: '5555' },
        3: { name: 'Петров П.П.', pin: '7777' },
        4: { name: 'Сидоров С.С.', pin: '9999' }
      };

      if (offlineUserMap[selectedUserId] && pinBuffer === offlineUserMap[selectedUserId].pin) {
        appState.currentUser = { id: selectedUserId, name: offlineUserMap[selectedUserId].name + ' (Офлайн)' };
        document.getElementById('username-display').innerText = appState.currentUser.name;
        lockScreen.classList.add('hidden');
        appContainer.classList.remove('hidden');
        await loadAppData();
        startReminderScheduler();
      } else {
        pinBuffer = '';
        updatePinDots();
      }
    }
  }

  document.querySelectorAll('.key-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      playKeyBeep();
      const key = btn.getAttribute('data-key');
      pinError.innerText = '';

      if (key === 'clear') {
        pinBuffer = pinBuffer.slice(0, -1);
        updatePinDots();
      } else if (key === 'submit') {
        submitPin();
      } else {
        if (pinBuffer.length < 8) {
          pinBuffer += key;
          updatePinDots();
          if (pinBuffer.length === 4) {
            submitPin();
          }
        }
      }
    });
  });
}

// Navigation / Route switcher
function setupNavigation() {
  const navItems = document.querySelectorAll('.nav-item');
  const screens = document.querySelectorAll('.app-screen');

  navItems.forEach(item => {
    item.addEventListener('click', () => {
      const screenId = item.getAttribute('data-screen');

      navItems.forEach(n => n.classList.remove('active'));
      item.classList.add('active');

      screens.forEach(screen => {
        if (screen.id === `screen-${screenId}`) {
          screen.classList.remove('hidden');
        } else {
          screen.classList.add('hidden');
        }
      });

      if (screenId === 'calendar') {
        renderCalendar();
      } else if (screenId === 'collab') {
        renderDiscussions();
      }
    });
  });

  window.navigateToScreen = function(screenId) {
    const targetNav = document.querySelector(`.nav-item[data-screen="${screenId}"]`);
    if (targetNav) targetNav.click();
  };

  // Adjust theme in Header
  document.getElementById('theme-toggle-btn').addEventListener('click', () => {
    // Quick cycling themes
    const themes = ['light', 'dark', 'midnight', 'sepia'];
    let idx = themes.indexOf(appState.settings.theme);
    idx = (idx + 1) % themes.length;
    appState.settings.theme = themes[idx];
    applySettings();
    saveAppData();
  });

  document.getElementById('logout-btn').addEventListener('click', () => {
    location.reload();
  });
}

// ----------------- RENDER ALL -----------------
function renderAll() {
  renderStats();
  renderTasks();
  renderNotes();
  renderCalendar();
}

// ----------------- STATISTICS -----------------
function renderStats() {
  // Scoped to current user tasks
  const myTasks = appState.tasks.filter(t => t.creatorId === appState.currentUser.id || t.assigneeId === appState.currentUser.id);
  const completed = myTasks.filter(t => t.completed).length;
  const pending = myTasks.filter(t => !t.completed).length;

  document.getElementById('stat-completed-count').innerText = completed;
  document.getElementById('stat-pending-count').innerText = pending;

  // Render Week Productivity Bar Chart
  const today = new Date();
  const startOfWeek = new Date(today);
  const currentDay = today.getDay();
  const distance = currentDay === 0 ? -6 : 1 - currentDay;
  startOfWeek.setDate(today.getDate() + distance);
  startOfWeek.setHours(0, 0, 0, 0);

  const dailyCounts = [0, 0, 0, 0, 0, 0, 0];

  myTasks.forEach(task => {
    if (task.completed && task.completedAt) {
      const compDate = new Date(task.completedAt);
      const diffTime = compDate.getTime() - startOfWeek.getTime();
      const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

      if (diffDays >= 0 && diffDays < 7) {
        dailyCounts[diffDays]++;
      }
    }
  });

  const chartContainer = document.getElementById('productivity-chart');
  chartContainer.innerHTML = '';

  const maxCount = Math.max(...dailyCounts, 1);

  dailyCounts.forEach((count) => {
    const heightPercent = (count / maxCount) * 100;

    const wrapper = document.createElement('div');
    wrapper.className = 'chart-bar-wrapper';

    const valueSpan = document.createElement('span');
    valueSpan.className = 'chart-bar-value';
    valueSpan.innerText = count > 0 ? count : '';

    const bar = document.createElement('div');
    bar.className = 'chart-bar';
    bar.style.height = `${Math.max(heightPercent, 2)}%`;

    wrapper.appendChild(valueSpan);
    wrapper.appendChild(bar);
    chartContainer.appendChild(wrapper);
  });

  const totalWeekly = dailyCounts.reduce((a, b) => a + b, 0);
  document.getElementById('total-weekly-completed').innerText = totalWeekly;
}

// ----------------- TASKS -----------------
let taskModal = document.getElementById('task-modal');
function openTaskModal(taskId = null) {
  taskModal.classList.remove('hidden');
  populateAssigneesDropdowns();

  if (taskId) {
    document.getElementById('task-modal-title').innerText = 'Редактировать задачу';
    const task = appState.tasks.find(t => t.id === taskId);
    if (task) {
      document.getElementById('edit-task-id').value = task.id;
      document.getElementById('task-title-input').value = task.title;
      document.getElementById('task-desc-input').value = task.desc || '';
      document.getElementById('task-category-input').value = task.category || 'Работа';
      document.getElementById('task-priority-input').value = task.priority || 'medium';
      document.getElementById('task-assignee-input').value = task.assigneeId || appState.currentUser.id;
      document.getElementById('task-due-input').value = task.due || '';
      document.getElementById('task-reminder-input').value = task.reminder || 'none';
      document.getElementById('task-tags-input').value = (task.tags || []).join(', ');
    }
  } else {
    document.getElementById('task-modal-title').innerText = 'Новая задача';
    document.getElementById('edit-task-id').value = '';
    document.getElementById('task-title-input').value = '';
    document.getElementById('task-desc-input').value = '';
    document.getElementById('task-category-input').value = 'Работа';
    document.getElementById('task-priority-input').value = 'medium';
    document.getElementById('task-assignee-input').value = appState.currentUser.id;
    document.getElementById('task-due-input').value = '';
    document.getElementById('task-reminder-input').value = 'none';
    document.getElementById('task-tags-input').value = '';
  }
}

function closeTaskModal() {
  taskModal.classList.add('hidden');
}

function renderTasks() {
  const container = document.getElementById('tasks-list');
  if (!container) return;
  container.innerHTML = '';

  const categoryFilter = document.getElementById('task-filter-category').value;
  const priorityFilter = document.getElementById('task-filter-priority').value;
  const sortOption = document.getElementById('task-sort').value;
  const searchKeyword = document.getElementById('global-search-input').value.toLowerCase().trim();

  // Show tasks where current user is creator OR is assignee
  let filtered = appState.tasks.filter(t => t.creatorId === appState.currentUser.id || t.assigneeId === appState.currentUser.id);

  if (categoryFilter !== 'all') {
    filtered = filtered.filter(t => t.category === categoryFilter);
  }

  if (priorityFilter !== 'all') {
    filtered = filtered.filter(t => t.priority === priorityFilter);
  }

  if (searchKeyword) {
    filtered = filtered.filter(t => {
      const titleMatch = t.title.toLowerCase().includes(searchKeyword);
      const descMatch = (t.desc || '').toLowerCase().includes(searchKeyword);
      const tagMatch = (t.tags || []).some(tag => tag.toLowerCase().includes(searchKeyword));
      return titleMatch || descMatch || tagMatch;
    });
  }

  if (sortOption === 'date-desc') {
    filtered.sort((a, b) => b.id - a.id);
  } else if (sortOption === 'date-asc') {
    filtered.sort((a, b) => a.id - b.id);
  } else if (sortOption === 'priority-desc') {
    const priorityWeight = { high: 3, medium: 2, low: 1 };
    filtered.sort((a, b) => (priorityWeight[b.priority] || 0) - (priorityWeight[a.priority] || 0));
  }

  if (filtered.length === 0) {
    container.innerHTML = '<div class="no-data-msg">Нет задач соответствующих критериям</div>';
    return;
  }

  filtered.forEach(task => {
    const card = document.createElement('div');
    card.className = `task-card priority-${task.priority} ${task.completed ? 'completed' : ''}`;

    const chkWrapper = document.createElement('div');
    chkWrapper.className = 'task-checkbox-wrapper';
    const chk = document.createElement('input');
    chk.type = 'checkbox';
    chk.className = 'task-checkbox';
    chk.checked = task.completed;
    chk.addEventListener('change', () => toggleTaskCompleted(task.id));
    chkWrapper.appendChild(chk);

    const content = document.createElement('div');
    content.className = 'task-card-content';

    const title = document.createElement('div');
    title.className = 'task-title';
    title.innerText = task.title;

    const desc = document.createElement('div');
    desc.className = 'task-desc';
    desc.innerText = task.desc || '';

    const meta = document.createElement('div');
    meta.className = 'task-meta';

    const catBadge = document.createElement('span');
    catBadge.className = 'badge';
    catBadge.innerHTML = `<i class="fas fa-folder"></i> ${task.category}`;
    meta.appendChild(catBadge);

    // Creator/Assignee Badges
    const creatorUser = appState.users.find(u => u.id === task.creatorId);
    const assigneeUser = appState.users.find(u => u.id === task.assigneeId);

    if (creatorUser) {
      const crBadge = document.createElement('span');
      crBadge.className = 'badge';
      crBadge.innerHTML = `<i class="fas fa-user-edit"></i> От: ${creatorUser.name}`;
      meta.appendChild(crBadge);
    }
    if (assigneeUser && task.assigneeId !== task.creatorId) {
      const asBadge = document.createElement('span');
      asBadge.className = 'badge badge-tag';
      asBadge.innerHTML = `<i class="fas fa-user-check"></i> Кому: ${assigneeUser.name}`;
      meta.appendChild(asBadge);
    }

    if (task.due) {
      const dueBadge = document.createElement('span');
      dueBadge.className = 'badge badge-due';
      dueBadge.innerHTML = `<i class="fas fa-clock"></i> ${task.due}`;
      meta.appendChild(dueBadge);
    }

    if (task.reminder && task.reminder !== 'none') {
      const repBadge = document.createElement('span');
      repBadge.className = 'badge badge-repeat';
      let repText = 'Повторяется';
      if (task.reminder === 'daily') repText = 'Каждый день';
      if (task.reminder === 'weekly') repText = 'Каждую неделю';
      if (task.reminder === 'monthly') repText = 'Каждый месяц';
      repBadge.innerHTML = `<i class="fas fa-sync"></i> ${repText}`;
      meta.appendChild(repBadge);
    }

    (task.tags || []).forEach(tag => {
      const tagBadge = document.createElement('span');
      tagBadge.className = 'badge badge-tag';
      tagBadge.innerHTML = `<i class="fas fa-tag"></i> ${tag}`;
      meta.appendChild(tagBadge);
    });

    content.appendChild(title);
    if (task.desc) content.appendChild(desc);
    content.appendChild(meta);

    const actions = document.createElement('div');
    actions.className = 'task-actions';

    const editBtn = document.createElement('button');
    editBtn.className = 'icon-btn';
    editBtn.innerHTML = '<i class="fas fa-edit"></i>';
    editBtn.addEventListener('click', () => openTaskModal(task.id));

    const delBtn = document.createElement('button');
    delBtn.className = 'icon-btn';
    delBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
    delBtn.addEventListener('click', () => deleteTask(task.id));

    actions.appendChild(editBtn);
    actions.appendChild(delBtn);

    card.appendChild(chkWrapper);
    card.appendChild(content);
    card.appendChild(actions);

    container.appendChild(card);
  });
}

function toggleTaskCompleted(taskId) {
  const task = appState.tasks.find(t => t.id === taskId);
  if (task) {
    task.completed = !task.completed;
    task.completedAt = task.completed ? new Date().toISOString() : null;

    if (task.completed && 'Notification' in window && Notification.permission === 'granted') {
      new Notification('Задача выполнена!', {
        body: `Вы выполнили задачу: "${task.title}"`,
        icon: 'icon-192.png'
      });
    }

    saveAppData();
    renderAll();
  }
}

function deleteTask(taskId) {
  if (confirm('Удалить эту задачу?')) {
    appState.tasks = appState.tasks.filter(t => t.id !== taskId);
    saveAppData();
    renderAll();
  }
}

function setupTaskEvents() {
  document.getElementById('add-task-btn').addEventListener('click', () => openTaskModal());
  document.getElementById('quick-add-task-btn').addEventListener('click', () => {
    openTaskModal();
  });

  document.querySelectorAll('#task-modal .close-modal-btn').forEach(btn => {
    btn.addEventListener('click', closeTaskModal);
  });

  document.getElementById('save-task-btn').addEventListener('click', () => {
    const title = document.getElementById('task-title-input').value.trim();
    if (!title) {
      alert('Введите название задачи!');
      return;
    }

    const editId = document.getElementById('edit-task-id').value;
    const desc = document.getElementById('task-desc-input').value.trim();
    const category = document.getElementById('task-category-input').value;
    const priority = document.getElementById('task-priority-input').value;
    const assigneeId = parseInt(document.getElementById('task-assignee-input').value);
    const due = document.getElementById('task-due-input').value;
    const reminder = document.getElementById('task-reminder-input').value;
    const tagsRaw = document.getElementById('task-tags-input').value;
    const tags = tagsRaw ? tagsRaw.split(',').map(t => t.trim()).filter(Boolean) : [];

    if (editId) {
      const task = appState.tasks.find(t => t.id === parseInt(editId));
      if (task) {
        task.title = title;
        task.desc = desc;
        task.category = category;
        task.priority = priority;
        task.assigneeId = assigneeId;
        task.due = due;
        task.reminder = reminder;
        task.tags = tags;
      }
    } else {
      const newTask = {
        id: Date.now(),
        creatorId: appState.currentUser.id,
        assigneeId: assigneeId,
        title,
        desc,
        category,
        priority,
        due,
        reminder,
        tags,
        completed: false,
        completedAt: null,
        createdAt: new Date().toISOString()
      };
      appState.tasks.push(newTask);
    }

    closeTaskModal();
    saveAppData();
    renderAll();
  });

  document.getElementById('task-filter-category').addEventListener('change', renderTasks);
  document.getElementById('task-filter-priority').addEventListener('change', renderTasks);
  document.getElementById('task-sort').addEventListener('change', renderTasks);
}

// ----------------- NOTES & AUDIO VOICE RECORDING -----------------
let noteModal = document.getElementById('note-modal');
let mediaRecorder = null;
let audioChunks = [];

function openNoteModal(noteId = null) {
  noteModal.classList.remove('hidden');

  audioChunks = [];
  document.getElementById('modal-audio-status').innerText = 'Готов';
  document.getElementById('modal-audio-status').className = '';
  document.getElementById('modal-audio-preview').classList.add('hidden');
  document.getElementById('modal-audio-url').value = '';
  document.getElementById('modal-stop-btn').classList.add('hidden');
  document.getElementById('modal-record-btn').classList.remove('hidden');

  if (noteId) {
    document.getElementById('note-modal-title').innerText = 'Редактировать заметку';
    const note = appState.notes.find(n => n.id === noteId);
    if (note) {
      document.getElementById('edit-note-id').value = note.id;
      document.getElementById('note-title-input').value = note.title;
      document.getElementById('note-content-input').value = note.content || '';
      document.getElementById('note-category-input').value = note.category || 'Работа';
      document.getElementById('note-tags-input').value = (note.tags || []).join(', ');
      document.getElementById('note-shared-input').checked = !!note.shared;

      if (note.audioUrl) {
        document.getElementById('modal-audio-url').value = note.audioUrl;
        document.getElementById('modal-audio-player').src = note.audioUrl;
        document.getElementById('modal-audio-preview').classList.remove('hidden');
      }
    }
  } else {
    document.getElementById('note-modal-title').innerText = 'Новая заметка';
    document.getElementById('edit-note-id').value = '';
    document.getElementById('note-title-input').value = '';
    document.getElementById('note-content-input').value = '';
    document.getElementById('note-category-input').value = 'Работа';
    document.getElementById('note-tags-input').value = '';
    document.getElementById('note-shared-input').checked = false;
  }
}

function closeNoteModal() {
  noteModal.classList.add('hidden');
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    mediaRecorder.stop();
  }
}

function renderNotes() {
  const container = document.getElementById('notes-list');
  if (!container) return;
  container.innerHTML = '';

  const categoryFilter = document.getElementById('note-filter-category').value;
  const sortOption = document.getElementById('note-sort').value;
  const searchKeyword = document.getElementById('global-search-input').value.toLowerCase().trim();

  // Show notes created by me OR marked as shared
  let filtered = appState.notes.filter(n => n.creatorId === appState.currentUser.id || !!n.shared);

  if (categoryFilter !== 'all') {
    filtered = filtered.filter(n => n.category === categoryFilter);
  }

  if (searchKeyword) {
    filtered = filtered.filter(n => {
      const titleMatch = n.title.toLowerCase().includes(searchKeyword);
      const contentMatch = (n.content || '').toLowerCase().includes(searchKeyword);
      const tagMatch = (n.tags || []).some(tag => tag.toLowerCase().includes(searchKeyword));
      return titleMatch || contentMatch || tagMatch;
    });
  }

  if (sortOption === 'date-desc') {
    filtered.sort((a, b) => b.id - a.id);
  } else if (sortOption === 'date-asc') {
    filtered.sort((a, b) => a.id - b.id);
  } else if (sortOption === 'title-asc') {
    filtered.sort((a, b) => a.title.localeCompare(b.title));
  }

  if (filtered.length === 0) {
    container.innerHTML = '<div class="no-data-msg">Нет заметок</div>';
    return;
  }

  filtered.forEach(note => {
    const card = document.createElement('div');
    card.className = 'note-card';

    const header = document.createElement('div');
    header.className = 'note-header';
    const title = document.createElement('h3');
    title.className = 'note-title';
    title.innerText = note.title;
    header.appendChild(title);

    if (note.shared) {
      const sharedBadge = document.createElement('span');
      sharedBadge.className = 'note-shared-badge';
      sharedBadge.innerHTML = '<i class="fas fa-users" title="Общий доступ"></i>';
      header.appendChild(sharedBadge);
    }

    const body = document.createElement('div');
    body.className = 'note-body';
    body.innerText = note.content || '';

    let audioContainer = null;
    if (note.audioUrl) {
      audioContainer = document.createElement('div');
      audioContainer.className = 'note-audio-player-wrapper';
      const aud = document.createElement('audio');
      aud.src = note.audioUrl;
      aud.controls = true;
      aud.style.width = '100%';
      aud.style.marginTop = '10px';
      audioContainer.appendChild(aud);
    }

    const footer = document.createElement('div');
    footer.className = 'note-footer';

    const metaInfo = document.createElement('div');
    metaInfo.style.display = 'flex';
    metaInfo.style.flexWrap = 'wrap';
    metaInfo.style.gap = '8px';

    const catSpan = document.createElement('span');
    catSpan.className = 'badge';
    catSpan.innerHTML = `<i class="fas fa-folder"></i> ${note.category}`;
    metaInfo.appendChild(catSpan);

    const creatorUser = appState.users.find(u => u.id === note.creatorId);
    if (creatorUser) {
      const creatorSpan = document.createElement('span');
      creatorSpan.className = 'badge';
      creatorSpan.innerHTML = `<i class="fas fa-user"></i> ${creatorUser.name}`;
      metaInfo.appendChild(creatorSpan);
    }

    if (note.audioUrl) {
      const audBadge = document.createElement('span');
      audBadge.className = 'note-audio-badge';
      audBadge.innerHTML = '<i class="fas fa-microphone"></i> Голос';
      metaInfo.appendChild(audBadge);
    }

    const actions = document.createElement('div');
    actions.className = 'task-actions';

    // Only author can edit/delete personal note
    if (note.creatorId === appState.currentUser.id) {
      const editBtn = document.createElement('button');
      editBtn.className = 'icon-btn';
      editBtn.innerHTML = '<i class="fas fa-edit"></i>';
      editBtn.addEventListener('click', () => openNoteModal(note.id));

      const delBtn = document.createElement('button');
      delBtn.className = 'icon-btn';
      delBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
      delBtn.addEventListener('click', () => deleteNote(note.id));

      actions.appendChild(editBtn);
      actions.appendChild(delBtn);
    }

    footer.appendChild(metaInfo);
    footer.appendChild(actions);

    card.appendChild(header);
    card.appendChild(body);
    if (audioContainer) card.appendChild(audioContainer);
    card.appendChild(footer);

    container.appendChild(card);
  });
}

function deleteNote(noteId) {
  if (confirm('Вы уверены, что хотите удалить эту заметку?')) {
    appState.notes = appState.notes.filter(n => n.id !== noteId);
    saveAppData();
    renderAll();
  }
}

function setupNoteEvents() {
  document.getElementById('add-note-btn').addEventListener('click', () => openNoteModal());
  document.getElementById('quick-add-note-btn').addEventListener('click', () => {
    navigateToScreen('notes');
    openNoteModal();
  });

  document.querySelectorAll('#note-modal .close-modal-btn').forEach(btn => {
    btn.addEventListener('click', closeNoteModal);
  });

  document.getElementById('save-note-btn').addEventListener('click', () => {
    const title = document.getElementById('note-title-input').value.trim();
    if (!title) {
      alert('Введите заголовок заметки!');
      return;
    }

    const editId = document.getElementById('edit-note-id').value;
    const content = document.getElementById('note-content-input').value.trim();
    const category = document.getElementById('note-category-input').value;
    const shared = document.getElementById('note-shared-input').checked;
    const audioUrl = document.getElementById('modal-audio-url').value;
    const tagsRaw = document.getElementById('note-tags-input').value;
    const tags = tagsRaw ? tagsRaw.split(',').map(t => t.trim()).filter(Boolean) : [];

    if (editId) {
      const note = appState.notes.find(n => n.id === parseInt(editId));
      if (note) {
        note.title = title;
        note.content = content;
        note.category = category;
        note.shared = shared;
        note.audioUrl = audioUrl;
        note.tags = tags;
      }
    } else {
      const newNote = {
        id: Date.now(),
        creatorId: appState.currentUser.id,
        title,
        content,
        category,
        shared,
        audioUrl,
        tags,
        createdAt: new Date().toISOString()
      };
      appState.notes.push(newNote);
    }

    closeNoteModal();
    saveAppData();
    renderAll();
  });

  document.getElementById('note-filter-category').addEventListener('change', renderNotes);
  document.getElementById('note-sort').addEventListener('change', renderNotes);

  const globalSearch = document.getElementById('global-search-input');
  const clearSearchBtn = document.getElementById('clear-search-btn');

  globalSearch.addEventListener('input', () => {
    if (globalSearch.value.trim() !== '') {
      clearSearchBtn.classList.remove('hidden-btn');
    } else {
      clearSearchBtn.classList.add('hidden-btn');
    }
    renderTasks();
    renderNotes();
  });

  clearSearchBtn.addEventListener('click', () => {
    globalSearch.value = '';
    clearSearchBtn.classList.add('hidden-btn');
    renderTasks();
    renderNotes();
  });

  // Microphone recording
  const recordBtn = document.getElementById('modal-record-btn');
  const stopBtn = document.getElementById('modal-stop-btn');
  const statusSpan = document.getElementById('modal-audio-status');

  recordBtn.addEventListener('click', async () => {
    audioChunks = [];
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      mediaRecorder = new MediaRecorder(stream);

      mediaRecorder.ondataavailable = (event) => {
        if (event.data.size > 0) {
          audioChunks.push(event.data);
        }
      };

      mediaRecorder.onstop = async () => {
        statusSpan.innerText = 'Обработка аудио...';
        const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });

        const formData = new FormData();
        formData.append('audio', audioBlob, 'note_voice.webm');

        const res = await fetchFromApi('upload_audio', formData, 'POST');
        if (res && res.success) {
          statusSpan.innerText = 'Аудио успешно записано!';
          statusSpan.className = 'text-success';
          document.getElementById('modal-audio-url').value = res.url;
          document.getElementById('modal-audio-player').src = res.url;
          document.getElementById('modal-audio-preview').classList.remove('hidden');
        } else {
          statusSpan.innerText = 'Ошибка сохранения аудио на сервере.';
          statusSpan.className = 'text-danger';
        }
        stream.getTracks().forEach(track => track.stop());
      };

      mediaRecorder.start();
      statusSpan.innerText = 'Запись голосовой заметки...';
      statusSpan.className = 'text-danger';
      recordBtn.classList.add('hidden');
      stopBtn.classList.remove('hidden');
      stopBtn.disabled = false;

    } catch (err) {
      console.error('Microphone access denied or audio recording issue:', err);
      statusSpan.innerText = 'Микрофон не доступен.';
      statusSpan.className = 'text-danger';
    }
  });

  stopBtn.addEventListener('click', () => {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
      mediaRecorder.stop();
      recordBtn.classList.remove('hidden');
      stopBtn.classList.add('hidden');
      stopBtn.disabled = true;
    }
  });

  // Quick Voice widget
  const quickRecBtn = document.getElementById('quick-record-btn');
  const quickRecStatus = document.getElementById('quick-record-status');
  let quickMediaRecorder = null;
  let quickAudioChunks = [];
  let isQuickRecording = false;

  quickRecBtn.addEventListener('click', async () => {
    if (!isQuickRecording) {
      quickAudioChunks = [];
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        quickMediaRecorder = new MediaRecorder(stream);

        quickMediaRecorder.ondataavailable = (event) => {
          if (event.data.size > 0) {
            quickAudioChunks.push(event.data);
          }
        };

        quickMediaRecorder.onstop = async () => {
          quickRecStatus.innerText = 'Обработка...';
          const audioBlob = new Blob(quickAudioChunks, { type: 'audio/webm' });
          const formData = new FormData();
          formData.append('audio', audioBlob, 'quick_voice.webm');

          const res = await fetchFromApi('upload_audio', formData, 'POST');
          if (res && res.success) {
            quickRecStatus.innerText = 'Создана новая голосовая заметка!';

            const newVoiceNote = {
              id: Date.now(),
              creatorId: appState.currentUser.id,
              title: `Голосовая заметка ${new Date().toLocaleDateString('ru-RU')}`,
              content: 'Голосовая запись с главного экрана.',
              category: 'Личное',
              shared: false,
              audioUrl: res.url,
              tags: ['голос', 'быстрое'],
              createdAt: new Date().toISOString()
            };
            appState.notes.push(newVoiceNote);
            saveAppData();
            renderAll();
          } else {
            quickRecStatus.innerText = 'Ошибка сохранения аудио.';
          }
          stream.getTracks().forEach(track => track.stop());
        };

        quickMediaRecorder.start();
        isQuickRecording = true;
        quickRecBtn.classList.add('recording-pulse');
        quickRecBtn.style.backgroundColor = '#34a853';
        quickRecBtn.innerHTML = '<i class="fas fa-stop"></i>';
        quickRecStatus.innerText = 'Запись пошла...';

      } catch (err) {
        console.error(err);
        quickRecStatus.innerText = 'Доступ к микрофону заблокирован';
      }
    } else {
      if (quickMediaRecorder && quickMediaRecorder.state !== 'inactive') {
        quickMediaRecorder.stop();
      }
      isQuickRecording = false;
      quickRecBtn.classList.remove('recording-pulse');
      quickRecBtn.style.backgroundColor = '';
      quickRecBtn.innerHTML = '<i class="fas fa-microphone"></i>';
    }
  });
}

// ----------------- CALENDAR PLANNER INTEGRATION -----------------
function renderCalendar() {
  const monthYearHeader = document.getElementById('calendar-month-year');
  const daysGrid = document.getElementById('calendar-days');
  if (!daysGrid) return;

  daysGrid.innerHTML = '';

  const currentYear = appState.currentCalendarDate.getFullYear();
  const currentMonth = appState.currentCalendarDate.getMonth();

  const monthNames = [
    'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
    'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
  ];

  monthYearHeader.innerText = `${monthNames[currentMonth]} ${currentYear}`;

  const firstDayIndex = new Date(currentYear, currentMonth, 1).getDay();
  const adjustedFirstDay = firstDayIndex === 0 ? 6 : firstDayIndex - 1;
  const totalDays = new Date(currentYear, currentMonth + 1, 0).getDate();

  for (let i = 0; i < adjustedFirstDay; i++) {
    const emptyCell = document.createElement('div');
    emptyCell.className = 'cal-day empty';
    daysGrid.appendChild(emptyCell);
  }

  const today = new Date();

  for (let day = 1; day <= totalDays; day++) {
    const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    const dayDate = new Date(currentYear, currentMonth, day);

    const cell = document.createElement('div');
    cell.className = 'cal-day';

    if (dayDate.toDateString() === today.toDateString()) {
      cell.classList.add('today');
    }
    if (dayDate.toDateString() === appState.selectedCalendarDate.toDateString()) {
      cell.classList.add('selected');
    }

    cell.innerText = day;

    // Scoped to my calendar tasks
    const tasksForDay = appState.tasks.filter(t => (t.creatorId === appState.currentUser.id || t.assigneeId === appState.currentUser.id) && t.due === dateStr && !t.completed);
    if (tasksForDay.length > 0) {
      const dotsContainer = document.createElement('div');
      dotsContainer.className = 'cal-day-dots';
      tasksForDay.slice(0, 3).forEach(() => {
        const dot = document.createElement('div');
        dot.className = 'dot-indicator';
        dotsContainer.appendChild(dot);
      });
      cell.appendChild(dotsContainer);
    }

    cell.addEventListener('click', () => {
      appState.selectedCalendarDate = dayDate;
      renderCalendar();
      renderCalendarTasksList();
    });

    daysGrid.appendChild(cell);
  }

  renderCalendarTasksList();
}

function renderCalendarTasksList() {
  const container = document.getElementById('calendar-day-tasks-list');
  if (!container) return;
  container.innerHTML = '';

  const y = appState.selectedCalendarDate.getFullYear();
  const m = appState.selectedCalendarDate.getMonth() + 1;
  const d = appState.selectedCalendarDate.getDate();
  const dateStr = `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;

  document.getElementById('selected-day-title').innerText = `Задачи на ${appState.selectedCalendarDate.toLocaleDateString('ru-RU')}`;

  const tasksForDay = appState.tasks.filter(t => (t.creatorId === appState.currentUser.id || t.assigneeId === appState.currentUser.id) && t.due === dateStr);

  if (tasksForDay.length === 0) {
    container.innerHTML = '<div class="no-data-msg">Нет запланированных задач на этот день</div>';
    return;
  }

  tasksForDay.forEach(task => {
    const card = document.createElement('div');
    card.className = `task-card priority-${task.priority} ${task.completed ? 'completed' : ''}`;

    const chkWrapper = document.createElement('div');
    chkWrapper.className = 'task-checkbox-wrapper';
    const chk = document.createElement('input');
    chk.type = 'checkbox';
    chk.className = 'task-checkbox';
    chk.checked = task.completed;
    chk.addEventListener('change', () => toggleTaskCompleted(task.id));
    chkWrapper.appendChild(chk);

    const content = document.createElement('div');
    content.className = 'task-card-content';
    const title = document.createElement('div');
    title.className = 'task-title';
    title.innerText = task.title;
    content.appendChild(title);

    card.appendChild(chkWrapper);
    card.appendChild(content);
    container.appendChild(card);
  });
}

function setupCalendarEvents() {
  document.getElementById('cal-prev-btn').addEventListener('click', () => {
    appState.currentCalendarDate.setMonth(appState.currentCalendarDate.getMonth() - 1);
    renderCalendar();
  });

  document.getElementById('cal-next-btn').addEventListener('click', () => {
    appState.currentCalendarDate.setMonth(appState.currentCalendarDate.getMonth() + 1);
    renderCalendar();
  });
}

// ----------------- COLLABORATION / DISCUSSION CHAT -----------------
let collabPollInterval = null;

function renderDiscussions() {
  const listContainer = document.getElementById('discussions-ul');
  if (!listContainer) return;
  listContainer.innerHTML = '';

  if (appState.discussions.length === 0) {
    listContainer.innerHTML = '<li class="no-data-msg">Нет активных тем</li>';
    return;
  }

  appState.discussions.forEach(disc => {
    const li = document.createElement('li');
    li.innerText = disc.title;
    if (appState.activeDiscussionId === disc.id) {
      li.classList.add('active');
    }

    li.addEventListener('click', () => {
      appState.activeDiscussionId = disc.id;
      renderDiscussions();
      openDiscussionChat();
    });

    listContainer.appendChild(li);
  });
}

function openDiscussionChat() {
  const activeDisc = appState.discussions.find(d => d.id === appState.activeDiscussionId);
  const placeholder = document.getElementById('chat-placeholder');
  const chatWindow = document.getElementById('chat-active-window');

  if (!activeDisc) {
    placeholder.classList.remove('hidden');
    chatWindow.classList.add('hidden');
    return;
  }

  placeholder.classList.add('hidden');
  chatWindow.classList.remove('hidden');

  document.getElementById('chat-topic-title').innerText = activeDisc.title;

  const msgContainer = document.getElementById('chat-messages');
  msgContainer.innerHTML = '';

  const messages = activeDisc.messages || [];
  messages.forEach(msg => {
    const bubble = document.createElement('div');
    const isMe = msg.userId === appState.currentUser.id;
    bubble.className = `message-bubble ${isMe ? 'me' : ''}`;

    const sender = document.createElement('div');
    sender.className = 'message-sender';
    sender.innerText = isMe ? 'Вы' : msg.userName;

    const text = document.createElement('div');
    text.className = 'message-text';
    text.innerText = msg.text;

    const time = document.createElement('div');
    time.className = 'message-time';
    const date = new Date(msg.timestamp);
    time.innerText = date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });

    bubble.appendChild(sender);
    bubble.appendChild(text);
    bubble.appendChild(time);

    msgContainer.appendChild(bubble);
  });
  msgContainer.scrollTop = msgContainer.scrollHeight;
}

async function startCollabPolling() {
  if (collabPollInterval) clearInterval(collabPollInterval);
  collabPollInterval = setInterval(async () => {
    if (appState.currentUser) {
      const res = await fetchFromApi('get_data');
      if (res && res.success) {
        appState.discussions = res.discussions || [];
        localStorage.setItem('pwa_discussions', JSON.stringify(appState.discussions));

        if (appState.activeDiscussionId) {
          openDiscussionChat();
        }
        renderDiscussions();
      }
    }
  }, 3500);
}

function setupCollabEvents() {
  const createBtn = document.getElementById('create-discussion-btn');
  createBtn.addEventListener('click', () => {
    const titleInput = document.getElementById('new-discussion-title');
    const title = titleInput.value.trim();
    if (!title) {
      alert('Введите название темы обсуждения!');
      return;
    }

    const newDisc = {
      id: Date.now(),
      title,
      messages: [],
      createdAt: new Date().toISOString()
    };

    appState.discussions.push(newDisc);
    appState.activeDiscussionId = newDisc.id;
    titleInput.value = '';

    saveAppData();
    renderDiscussions();
    openDiscussionChat();
  });

  const sendBtn = document.getElementById('chat-send-btn');
  const chatInput = document.getElementById('chat-message-input');

  async function sendMessage() {
    const text = chatInput.value.trim();
    if (!text) return;

    const disc = appState.discussions.find(d => d.id === appState.activeDiscussionId);
    if (disc) {
      const newMsg = {
        id: Date.now(),
        userId: appState.currentUser.id,
        userName: appState.currentUser.name,
        text,
        timestamp: new Date().toISOString()
      };

      disc.messages = disc.messages || [];
      disc.messages.push(newMsg);
      chatInput.value = '';

      await saveAppData();
      openDiscussionChat();
    }
  }

  sendBtn.addEventListener('click', sendMessage);
  chatInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      sendMessage();
    }
  });

  startCollabPolling();
}

// ----------------- SETTINGS & PREFERENCES -----------------
function setupSettingsEvents() {
  // Themes
  document.getElementById('theme-light-btn').addEventListener('click', () => {
    appState.settings.theme = 'light';
    applySettings();
    saveAppData();
  });
  document.getElementById('theme-dark-btn').addEventListener('click', () => {
    appState.settings.theme = 'dark';
    applySettings();
    saveAppData();
  });
  document.getElementById('theme-midnight-btn').addEventListener('click', () => {
    appState.settings.theme = 'midnight';
    applySettings();
    saveAppData();
  });
  document.getElementById('theme-sepia-btn').addEventListener('click', () => {
    appState.settings.theme = 'sepia';
    applySettings();
    saveAppData();
  });

  // Custom Accents
  document.querySelectorAll('.accent-dot').forEach(dot => {
    dot.addEventListener('click', () => {
      appState.settings.accent = dot.getAttribute('data-accent');
      applySettings();
      saveAppData();
    });
  });

  // Fonts Sizes
  document.getElementById('font-size-select').addEventListener('change', (e) => {
    appState.settings.fontSize = e.target.value;
    applySettings();
    saveAppData();
  });

  // Audio Keyboard Feedback
  document.getElementById('settings-audio-feedback-input').addEventListener('change', (e) => {
    appState.settings.audioFeedback = e.target.checked;
    applySettings();
    saveAppData();
  });

  // Change username profile
  document.getElementById('settings-save-profile-btn').addEventListener('click', async () => {
    const inputName = document.getElementById('settings-username-input').value.trim();
    const statusMsg = document.getElementById('settings-profile-msg');

    if (!inputName) {
      statusMsg.innerText = 'Имя не должно быть пустым';
      statusMsg.className = 'pin-status-msg error';
      return;
    }

    const res = await fetchFromApi('update_username', {
      user_id: appState.currentUser.id,
      name: inputName
    }, 'POST');

    if (res && res.success) {
      statusMsg.innerText = 'Имя успешно обновлено!';
      statusMsg.className = 'pin-status-msg';
      appState.currentUser.name = inputName;
      document.getElementById('username-display').innerText = inputName;
    } else {
      statusMsg.innerText = res.message || 'Ошибка обновления';
      statusMsg.className = 'pin-status-msg error';
    }
  });

  // Change PIN security settings
  document.getElementById('settings-save-pin-btn').addEventListener('click', async () => {
    const oldPin = document.getElementById('settings-old-pin').value.trim();
    const newPin = document.getElementById('settings-new-pin').value.trim();
    const statusMsg = document.getElementById('settings-pin-msg');

    if (!oldPin || !newPin) {
      statusMsg.innerText = 'Заполните оба поля';
      statusMsg.className = 'pin-status-msg error';
      return;
    }

    const res = await fetchFromApi('change_pin', {
      old_pin: oldPin,
      new_pin: newPin,
      user_id: appState.currentUser.id
    }, 'POST');

    if (res && res.success) {
      statusMsg.innerText = 'PIN-код успешно изменен!';
      statusMsg.className = 'pin-status-msg';
      document.getElementById('settings-old-pin').value = '';
      document.getElementById('settings-new-pin').value = '';
    } else {
      statusMsg.innerText = res.message || 'Ошибка смены PIN-кода';
      statusMsg.className = 'pin-status-msg error';
    }
  });

  // Backup upload JSON
  const fileInput = document.getElementById('import-file-input');
  fileInput.addEventListener('change', async () => {
    const file = fileInput.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('backup', file);

    const res = await fetchFromApi('import', formData, 'POST');
    if (res && res.success) {
      alert('Данные успешно импортированы!');
      loadAppData();
    } else {
      alert(res.message || 'Ошибка импорта файла');
    }
  });
}

// ----------------- FLEXIBLE REMINDER SCHEDULER & NOTIFICATIONS -----------------
function startReminderScheduler() {
  setInterval(() => {
    const now = new Date();
    const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

    appState.tasks.forEach(task => {
      if (!task.completed && task.due === todayStr && (task.creatorId === appState.currentUser.id || task.assigneeId === appState.currentUser.id)) {
        if ('Notification' in window && Notification.permission === 'granted') {
          new Notification('Напоминание о задаче!', {
            body: `Сегодня срок выполнения задачи: "${task.title}"`,
            icon: 'icon-192.png',
            tag: `reminder-${task.id}`
          });
        }

        if (task.reminder && task.reminder !== 'none') {
          const nextDue = new Date();
          if (task.reminder === 'daily') {
            nextDue.setDate(now.getDate() + 1);
          } else if (task.reminder === 'weekly') {
            nextDue.setDate(now.getDate() + 7);
          } else if (task.reminder === 'monthly') {
            nextDue.setMonth(now.getMonth() + 1);
          }
          task.due = `${nextDue.getFullYear()}-${String(nextDue.getMonth() + 1).padStart(2, '0')}-${String(nextDue.getDate()).padStart(2, '0')}`;
          saveAppData();
          renderAll();
        }
      }
    });
  }, 60000);
}

// ----------------- INITIALIZATION -----------------
document.addEventListener('DOMContentLoaded', () => {
  setupLockScreen();
  setupNavigation();
  setupTaskEvents();
  setupNoteEvents();
  setupCalendarEvents();
  setupCollabEvents();
  setupSettingsEvents();
});
