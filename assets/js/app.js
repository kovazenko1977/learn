// --- GLOBALS & APP STATE ---
let authorized = false;
let currentPin = "";
const correctPinCode = "1234";
let userId = localStorage.getItem("pwa_token") || "";
let userName = "Коваженко С.Б.";
let activeTab = "dashboard";
let isOffline = !navigator.onLine;

let notesList = [];
let tasksList = [];
let systemCategories = ["Личное", "Работа", "Учеба", "Идеи", "Другое"];
let currentSelectedCategoryFilter = "";
let currentTaskStatusFilter = "pending";

// Calendar navigation states
let calendarYear = new Date().getFullYear();
let calendarMonth = new Date().getMonth();
let calendarSelectedDate = new Date().toISOString().split('T')[0];

// Synchronization local queue
let syncQueue = JSON.parse(localStorage.getItem("pwa_sync_queue") || "[]");

// Audio Voice recording variables
let mediaRecorder = null;
let audioChunks = [];
let localAudioBlob = null;
let currentAudioUrl = null;
let isRecording = false;

// Modal dialog Audio recording variables
let modalMediaRecorder = null;
let modalAudioChunks = [];
let modalLocalAudioBlob = null;
let modalCurrentAudioUrl = null;
let isModalRecording = false;

// Collaboration variables
let chatActiveNoteId = "";
let chatMessages = [];
let isAudioConferenceActive = false;

// --- INITIALIZATION ---
document.addEventListener("DOMContentLoaded", () => {
  // Check active internet connection status
  window.addEventListener("online", () => {
    isOffline = false;
    toggleOfflineBanner();
    processSyncQueue();
  });
  window.addEventListener("offline", () => {
    isOffline = true;
    toggleOfflineBanner();
  });
  toggleOfflineBanner();

  // Try to restore session
  if (userId) {
    checkAppSession();
  } else {
    showAuthScreen();
  }

  // Register PWA Service Worker
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js')
      .then(reg => console.log('SW registered', reg.scope))
      .catch(err => console.warn('SW failed', err));
  }

  // Start Header Clock Rotation Updates
  startDashboardClockRotation();
});

// --- HEAD CLOCK ROTATION ---
function startDashboardClockRotation() {
  setInterval(() => {
    const d = new Date();
    const hours = d.getHours() % 12;
    const mins = d.getMinutes();
    const sec = d.getSeconds();

    const hrHand = document.getElementById("clock-hour-hand");
    const minHand = document.getElementById("clock-min-hand");

    if (hrHand && minHand) {
      const hrDeg = (hours * 30) + (mins * 0.5) - 90;
      const minDeg = (mins * 6) - 90;
      hrHand.style.transform = `rotate(${hrDeg}deg)`;
      minHand.style.transform = `rotate(${minDeg}deg)`;
    }
  }, 1000);
}

// --- SESSION / AUTHENTICATION ---
function pressDigit(digit) {
  if (currentPin.length < 4) {
    currentPin += digit;
    updatePinDots();
    playBeepSound();
    if (navigator.vibrate) navigator.vibrate(25);
  }
  if (currentPin.length === 4) {
    setTimeout(submitPinLogin, 200);
  }
}

function clearDigit() {
  currentPin = "";
  updatePinDots();
}

function backspaceDigit() {
  currentPin = currentPin.slice(0, -1);
  updatePinDots();
}

function updatePinDots() {
  for (let i = 1; i <= 4; i++) {
    const dot = document.getElementById(`dot-${i}`);
    if (currentPin.length >= i) {
      dot.classList.add("filled");
    } else {
      dot.classList.remove("filled");
    }
  }
}

function playBeepSound() {
  try {
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioCtx.createOscillator();
    const gainNode = audioCtx.createGain();
    oscillator.connect(gainNode);
    gainNode.connect(audioCtx.destination);
    oscillator.type = 'sine';
    oscillator.frequency.setValueAtTime(440, audioCtx.currentTime);
    gainNode.gain.setValueAtTime(0.05, audioCtx.currentTime);
    oscillator.start();
    setTimeout(() => oscillator.stop(), 80);
  } catch (e) {
    // blocked
  }
}

function submitPinLogin() {
  if (isOffline) {
    // Offline authentication check
    const localUsers = JSON.parse(localStorage.getItem("pwa_offline_users") || `[{"id":"1","pin":"1234","name":"Коваженко С.Б."}]`);
    const found = localUsers.find(u => u.pin === currentPin);
    if (found) {
      successfulLogin(found.id, found.name);
    } else {
      failedLogin();
    }
  } else {
    fetch("/api.php?action=login", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ pin: currentPin })
    })
    .then(res => {
      if (res.status === 200) return res.json();
      throw new Error("Invalid");
    })
    .then(data => {
      if (data.success) {
        successfulLogin(data.user.id, data.user.name);
        localStorage.setItem("pwa_offline_users", JSON.stringify([data.user]));
      } else {
        failedLogin();
      }
    })
    .catch(() => {
      failedLogin();
    });
  }
}

function successfulLogin(id, name) {
  authorized = true;
  userId = id;
  userName = name;
  localStorage.setItem("pwa_token", id);

  document.getElementById("auth-screen").style.display = "none";
  document.getElementById("app-main-core").style.display = "flex";

  document.getElementById("dashboard-user-name").textContent = name;

  // Format today's date
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  document.getElementById("dashboard-date-str").textContent = new Date().toLocaleDateString('ru-RU', options);

  loadAppDatabases();
  applySavedTheme();
}

function failedLogin() {
  currentPin = "";
  updatePinDots();
  const screen = document.getElementById("auth-screen");
  screen.classList.add("shake-anim");
  if (navigator.vibrate) navigator.vibrate([100, 50, 100]);
  setTimeout(() => screen.classList.remove("shake-anim"), 500);
}

function checkAppSession() {
  if (isOffline) {
    successfulLogin(userId, userName);
    return;
  }

  fetch("/api.php?action=check_session")
  .then(res => res.json())
  .then(data => {
    if (data.authorized) {
      successfulLogin(data.user.id, data.user.name);
    } else {
      showAuthScreen();
    }
  })
  .catch(() => {
    successfulLogin(userId, userName);
  });
}

function triggerLogout() {
  authorized = false;
  userId = "";
  localStorage.removeItem("pwa_token");
  showAuthScreen();
  if (!isOffline) {
    fetch("/api.php?action=logout");
  }
}

function showAuthScreen() {
  document.getElementById("auth-screen").style.display = "flex";
  document.getElementById("app-main-core").style.display = "none";
  currentPin = "";
  updatePinDots();
}

// --- THEMING ---
function toggleAppTheme() {
  const current = localStorage.getItem("pwa_theme") || "light";
  const target = current === "light" ? "dark" : "light";
  localStorage.setItem("pwa_theme", target);
  applySavedTheme();
}

function applySavedTheme() {
  const theme = localStorage.getItem("pwa_theme") || "light";
  const icon = document.getElementById("theme-icon");
  if (theme === "dark") {
    document.documentElement.classList.add("dark");
    icon.className = "fas fa-sun";
  } else {
    document.documentElement.classList.remove("dark");
    icon.className = "fas fa-moon";
  }
}

// --- OFFLINE BANNER AND SYNC QUEUE ---
function toggleOfflineBanner() {
  const banner = document.getElementById("offline-banner");
  if (isOffline) {
    banner.style.display = "flex";
  } else {
    banner.style.display = "none";
  }
}

function processSyncQueue() {
  if (isOffline || syncQueue.length === 0) return;

  document.getElementById("sync-banner").style.display = "flex";
  let chain = Promise.resolve();
  const headers = {
    "Content-Type": "application/json",
    "Authorization": "Bearer " + userId
  };

  const queue = [...syncQueue];
  queue.forEach(item => {
    chain = chain.then(() => {
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

  chain.then(() => {
    syncQueue = [];
    localStorage.setItem("pwa_sync_queue", "[]");
    loadAppDatabases();
    alert("Офлайн изменения успешно сохранены на сервере!");
  })
  .catch(err => {
    console.error("Sync error", err);
  })
  .finally(() => {
    document.getElementById("sync-banner").style.display = "none";
  });
}

function pushToSyncQueue(actionName, payload) {
  syncQueue.push({
    action: actionName,
    id: payload.id,
    data: actionName.includes('delete') ? null : payload,
    timestamp: Date.now()
  });
  localStorage.setItem("pwa_sync_queue", JSON.stringify(syncQueue));
}

// --- NAVIGATION TABS ---
function switchActiveTab(tabName) {
  activeTab = tabName;

  // Set active tab CSS class
  document.querySelectorAll(".nav-btn").forEach(btn => btn.classList.remove("active"));
  document.getElementById(`nav-${tabName}`).classList.add("active");

  // Show active section container
  document.querySelectorAll(".view-section").forEach(sec => sec.classList.remove("active"));
  document.getElementById(`tab-${tabName}`).classList.add("active");

  // Toggle float create button based on active view
  const fab = document.getElementById("fab-create-btn");
  if (tabName === "notes" || tabName === "tasks") {
    fab.style.display = "flex";
  } else {
    fab.style.display = "none";
  }

  // Reload views with specialized rendering
  if (tabName === "notes") {
    renderNotesList();
    renderCategoryFilterChips();
  } else if (tabName === "tasks") {
    renderTasksList();
  } else if (tabName === "calendar") {
    renderCalendarGrid();
  } else if (tabName === "statistics") {
    renderProductivityStatistics();
  } else if (tabName === "collaboration") {
    renderCollaborationDropdown();
  }
}

function onFabClicked() {
  if (activeTab === "notes") {
    openNoteFormModal();
  } else if (activeTab === "tasks") {
    openTaskFormModal();
  }
}

// --- DATA ACCESS ENDPOINTS ---
function loadAppDatabases() {
  notesList = JSON.parse(localStorage.getItem("pwa_notes") || "[]");
  tasksList = JSON.parse(localStorage.getItem("pwa_tasks") || "[]");

  renderDashboardWidgets();

  if (isOffline) return;

  const headers = { "Authorization": "Bearer " + userId };
  Promise.all([
    fetch("/api.php?action=get_notes", { headers }).then(r => r.json()),
    fetch("/api.php?action=get_tasks", { headers }).then(r => r.json())
  ])
  .then(([notes, tasks]) => {
    if (Array.isArray(notes)) {
      notesList = notes;
      localStorage.setItem("pwa_notes", JSON.stringify(notes));
    }
    if (Array.isArray(tasks)) {
      tasksList = tasks;
      localStorage.setItem("pwa_tasks", JSON.stringify(tasks));
    }
    renderDashboardWidgets();
    if (activeTab === "notes") { renderNotesList(); renderCategoryFilterChips(); }
    if (activeTab === "tasks") { renderTasksList(); }
    if (activeTab === "calendar") { renderCalendarGrid(); }
  })
  .catch(err => console.warn("Failed fetching fresh backend data", err));
}

function manualSync() {
  const syncBtn = document.getElementById("sync-icon");
  syncBtn.classList.add("fa-spin");

  if (isOffline) {
    alert("Вы находитесь в офлайн-режиме. Подключитесь к сети для синхронизации.");
    syncBtn.classList.remove("fa-spin");
    return;
  }

  setTimeout(() => {
    processSyncQueue();
    loadAppDatabases();
    syncBtn.classList.remove("fa-spin");
  }, 600);
}

// --- DASHBOARD RENDERING ---
function renderDashboardWidgets() {
  const activeCount = tasksList.filter(t => t.status === "pending").length;
  document.getElementById("widget-active-tasks-count").textContent = activeCount;
  document.getElementById("widget-notes-count").textContent = notesList.length;

  // Render today's task list
  const container = document.getElementById("today-tasks-container");
  container.innerHTML = "";

  const todayStr = new Date().toISOString().split('T')[0];
  const todayTasks = tasksList.filter(t => t.due_date === todayStr);

  if (todayTasks.length === 0) {
    container.innerHTML = `<div class="card" style="padding:16px; text-align:center; color:var(--text-muted); border-style:dashed; font-size:12px;">Нет запланированных задач на сегодня</div>`;
  } else {
    todayTasks.forEach(task => {
      const pBadge = task.priority === 'High' ? 'priority-High' : (task.priority === 'Medium' ? 'priority-Medium' : 'priority-Low');
      const div = document.createElement("div");
      div.className = "task-item";
      div.onclick = () => openTaskFormModal(task);
      div.style.cursor = "pointer";
      div.innerHTML = `
        <div class="task-item-left">
          <span class="priority-badge ${pBadge}" style="display:inline-block; width:8px; height:8px; border-radius:50%; padding:0;"></span>
          <div class="task-details">
            <div class="task-title ${task.status === 'completed' ? 'completed' : ''}">${task.title}</div>
            <div class="task-meta">
              <span class="note-cat cat-${task.category}">${task.category}</span>
            </div>
          </div>
        </div>
        <span class="priority-badge ${pBadge}">${task.priority}</span>
      `;
      container.appendChild(div);
    });
  }
}

// --- NOTES OPERATIONS & RENDERING ---
function renderCategoryFilterChips() {
  const container = document.getElementById("notes-filter-chips");
  container.innerHTML = "";

  // Add "All" chip
  const allChip = document.createElement("button");
  allChip.className = `filter-chip ${currentSelectedCategoryFilter === "" ? "active" : ""}`;
  allChip.textContent = "Все";
  allChip.onclick = () => {
    currentSelectedCategoryFilter = "";
    renderCategoryFilterChips();
    renderNotesList();
  };
  container.appendChild(allChip);

  systemCategories.forEach(cat => {
    const chip = document.createElement("button");
    chip.className = `filter-chip ${currentSelectedCategoryFilter === cat ? "active" : ""}`;
    chip.textContent = cat;
    chip.onclick = () => {
      currentSelectedCategoryFilter = cat;
      renderCategoryFilterChips();
      renderNotesList();
    };
    container.appendChild(chip);
  });
}

function renderNotesList() {
  const container = document.getElementById("notes-grid-container");
  container.innerHTML = "";

  const query = document.getElementById("search-notes-input").value.toLowerCase();

  const filtered = notesList.filter(n => {
    const matchesQuery = n.title.toLowerCase().includes(query) ||
                         (n.content && n.content.toLowerCase().includes(query)) ||
                         (n.tags && n.tags.some(t => t.toLowerCase().includes(query)));
    const matchesCategory = !currentSelectedCategoryFilter || n.category === currentSelectedCategoryFilter;
    return matchesQuery && matchesCategory;
  });

  if (filtered.length === 0) {
    container.innerHTML = `<div style="grid-column: 1 / span 2; text-align:center; padding:32px; color:var(--text-muted); font-size:12px;"><i class="fas fa-file-signature text-3xl mb-2 block"></i>Заметки не найдены</div>`;
    return;
  }

  filtered.forEach(note => {
    const card = document.createElement("div");
    card.className = "note-card";
    card.onclick = () => openNoteFormModal(note);

    let tagSpans = "";
    if (note.tags && note.tags.length > 0) {
      note.tags.forEach(t => {
        tagSpans += `<span class="note-tag">#${t}</span>`;
      });
    }

    let indicators = "";
    if (note.shared) indicators += `<i class="fas fa-users" style="color:var(--accent-color);"></i>`;
    if (note.audio_url) indicators += `<i class="fas fa-microphone" style="color:#ef4444;"></i>`;

    card.innerHTML = `
      <div>
        <div class="note-top">
          <span class="note-cat cat-${note.category}">${note.category}</span>
          <div class="note-indicators">${indicators}</div>
        </div>
        <div class="note-title">${note.title}</div>
        <div class="note-content">${note.content || ""}</div>
      </div>
      <div class="note-tags">${tagSpans}</div>
    `;
    container.appendChild(card);
  });
}

// --- NOTES MODAL & SUBMIT ---
function openNoteFormModal(note = null) {
  const modal = document.getElementById("note-modal-overlay");
  modal.classList.add("active");

  const titleEl = document.getElementById("note-modal-title");
  const delBtn = document.getElementById("note-delete-form-btn");

  if (note) {
    titleEl.textContent = "Редактировать заметку";
    delBtn.style.display = "block";
    document.getElementById("note-form-id").value = note.id;
    document.getElementById("note-form-title").value = note.title;
    document.getElementById("note-form-content").value = note.content || "";
    document.getElementById("note-form-category").value = note.category || "Личное";
    document.getElementById("note-form-priority").value = note.priority || "Medium";
    document.getElementById("note-form-tags").value = (note.tags || []).join(", ");
    document.getElementById("note-form-shared").checked = !!note.shared;

    modalCurrentAudioUrl = note.audio_url || null;
    updateModalAudioStatus();
  } else {
    titleEl.textContent = "Создать заметку";
    delBtn.style.display = "none";
    document.getElementById("note-form-id").value = "";
    document.getElementById("note-form-title").value = "";
    document.getElementById("note-form-content").value = "";
    document.getElementById("note-form-category").value = "Личное";
    document.getElementById("note-form-priority").value = "Medium";
    document.getElementById("note-form-tags").value = "";
    document.getElementById("note-form-shared").checked = false;

    modalCurrentAudioUrl = null;
    updateModalAudioStatus();
  }
}

function closeNoteModal() {
  document.getElementById("note-modal-overlay").classList.remove("active");
  stopModalMicRecording();
}

function saveNoteForm() {
  const id = document.getElementById("note-form-id").value;
  const title = document.getElementById("note-form-title").value;
  const content = document.getElementById("note-form-content").value;
  const category = document.getElementById("note-form-category").value;
  const priority = document.getElementById("note-form-priority").value;
  const tagsStr = document.getElementById("note-form-tags").value;
  const shared = document.getElementById("note-form-shared").checked;

  if (!title) {
    alert("Введите название заметки.");
    return;
  }

  const tags = tagsStr.split(",").map(t => t.trim()).filter(t => t.length > 0);

  const payload = {
    id: id || undefined,
    title,
    content,
    category,
    priority,
    tags,
    shared,
    audio_url: modalCurrentAudioUrl
  };

  if (isOffline) {
    if (!payload.id) {
      payload.id = "local_note_" + Date.now();
      notesList.push(payload);
    } else {
      const idx = notesList.findIndex(n => n.id === payload.id);
      if (idx !== -1) notesList[idx] = payload;
    }
    localStorage.setItem("pwa_notes", JSON.stringify(notesList));
    pushToSyncQueue('save_note', payload);
    closeNoteModal();
    renderNotesList();
    renderDashboardWidgets();
  } else {
    fetch("/api.php?action=save_note", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Authorization": "Bearer " + userId
      },
      body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(() => {
      loadAppDatabases();
      closeNoteModal();
    });
  }
}

function deleteCurrentFormNote() {
  const id = document.getElementById("note-form-id").value;
  if (!id || !confirm("Действительно удалить эту заметку?")) return;

  if (isOffline) {
    notesList = notesList.filter(n => n.id !== id);
    localStorage.setItem("pwa_notes", JSON.stringify(notesList));
    pushToSyncQueue('delete_note', { id });
    closeNoteModal();
    renderNotesList();
    renderDashboardWidgets();
  } else {
    fetch(`/api.php?action=delete_note&id=${id}`, {
      method: "DELETE",
      headers: { "Authorization": "Bearer " + userId }
    })
    .then(res => res.json())
    .then(() => {
      loadAppDatabases();
      closeNoteModal();
    });
  }
}

// --- TASKS OPERATIONS & RENDERING ---
function setTaskStatusFilter(status) {
  currentTaskStatusFilter = status;
  document.getElementById("task-filter-pending").className = `filter-chip ${status === 'pending' ? 'active' : ''}`;
  document.getElementById("task-filter-completed").className = `filter-chip ${status === 'completed' ? 'active' : ''}`;
  renderTasksList();
}

function renderTasksList() {
  const container = document.getElementById("tasks-list-container");
  container.innerHTML = "";

  const query = document.getElementById("search-tasks-input").value.toLowerCase();

  const filtered = tasksList.filter(t => {
    const matchesQuery = t.title.toLowerCase().includes(query) ||
                         (t.description && t.description.toLowerCase().includes(query));
    const matchesStatus = t.status === currentTaskStatusFilter;
    return matchesQuery && matchesStatus;
  });

  if (filtered.length === 0) {
    container.innerHTML = `<div style="text-align:center; padding:32px; color:var(--text-muted); font-size:12px;"><i class="fas fa-check-double text-3xl mb-2 block"></i>Нет задач в этой категории</div>`;
    return;
  }

  filtered.forEach(task => {
    const pBadge = task.priority === 'High' ? 'priority-High' : (task.priority === 'Medium' ? 'priority-Medium' : 'priority-Low');
    const isCompleted = task.status === 'completed';

    const div = document.createElement("div");
    div.className = "task-item";

    let subInfo = "";
    if (task.due_date) {
      subInfo += `<span style="font-size:10px; color:var(--accent-color); margin-right:8px;"><i class="fas fa-calendar-alt"></i> ${task.due_date}</span>`;
    }
    if (task.repeat_interval && task.repeat_interval !== 'none') {
      subInfo += `<span style="font-size:10px; color:var(--text-muted);"><i class="fas fa-redo"></i> ${task.repeat_interval}</span>`;
    }

    div.innerHTML = `
      <div class="task-item-left">
        <button class="task-check-btn ${isCompleted ? 'checked' : ''}" onclick="toggleTaskCheckbox('${task.id}', event)">
          <i class="${isCompleted ? 'fas fa-check-circle' : 'far fa-circle'}"></i>
        </button>
        <div class="task-details" onclick="openTaskFormModalByDataId('${task.id}')" style="cursor:pointer;">
          <div class="task-title ${isCompleted ? 'completed' : ''}">${task.title}</div>
          <div class="task-desc">${task.description || ""}</div>
          <div class="task-meta">
            <span class="note-cat cat-${task.category}">${task.category}</span>
            ${subInfo}
          </div>
        </div>
      </div>
      <div class="task-right">
        <span class="priority-badge ${pBadge}">${task.priority}</span>
      </div>
    `;
    container.appendChild(div);
  });
}

function toggleTaskCheckbox(taskId, event) {
  event.stopPropagation();
  const task = tasksList.find(t => t.id === taskId);
  if (!task) return;

  task.status = task.status === 'completed' ? 'pending' : 'completed';

  if (isOffline) {
    localStorage.setItem("pwa_tasks", JSON.stringify(tasksList));
    pushToSyncQueue('save_task', task);
    renderTasksList();
    renderDashboardWidgets();
  } else {
    fetch("/api.php?action=save_task", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Authorization": "Bearer " + userId
      },
      body: JSON.stringify(task)
    })
    .then(res => res.json())
    .then(() => {
      loadAppDatabases();
    });
  }
}

function openTaskFormModalByDataId(taskId) {
  const task = tasksList.find(t => t.id === taskId);
  if (task) openTaskFormModal(task);
}

function openTaskFormModal(task = null) {
  const modal = document.getElementById("task-modal-overlay");
  modal.classList.add("active");

  const titleEl = document.getElementById("task-modal-title");
  const delBtn = document.getElementById("task-delete-form-btn");

  if (task) {
    titleEl.textContent = "Редактировать задачу";
    delBtn.style.display = "block";
    document.getElementById("task-form-id").value = task.id;
    document.getElementById("task-form-title").value = task.title;
    document.getElementById("task-form-description").value = task.description || "";
    document.getElementById("task-form-category").value = task.category || "Личное";
    document.getElementById("task-form-priority").value = task.priority || "Medium";
    document.getElementById("task-form-duedate").value = task.due_date || "";
    document.getElementById("task-form-remindertime").value = task.reminder_time || "";
    document.getElementById("task-form-repeat").value = task.repeat_interval || "none";
    document.getElementById("task-form-completed").checked = task.status === "completed";
  } else {
    titleEl.textContent = "Создать задачу";
    delBtn.style.display = "none";
    document.getElementById("task-form-id").value = "";
    document.getElementById("task-form-title").value = "";
    document.getElementById("task-form-description").value = "";
    document.getElementById("task-form-category").value = "Личное";
    document.getElementById("task-form-priority").value = "Medium";
    document.getElementById("task-form-duedate").value = new Date().toISOString().split('T')[0];
    document.getElementById("task-form-remindertime").value = "";
    document.getElementById("task-form-repeat").value = "none";
    document.getElementById("task-form-completed").checked = false;
  }
}

function closeTaskModal() {
  document.getElementById("task-modal-overlay").classList.remove("active");
}

function saveTaskForm() {
  const id = document.getElementById("task-form-id").value;
  const title = document.getElementById("task-form-title").value;
  const description = document.getElementById("task-form-description").value;
  const category = document.getElementById("task-form-category").value;
  const priority = document.getElementById("task-form-priority").value;
  const due_date = document.getElementById("task-form-duedate").value;
  const reminder_time = document.getElementById("task-form-remindertime").value;
  const repeat_interval = document.getElementById("task-form-repeat").value;
  const completed = document.getElementById("task-form-completed").checked;

  if (!title) {
    alert("Введите название задачи.");
    return;
  }

  const payload = {
    id: id || undefined,
    title,
    description,
    category,
    priority,
    due_date: due_date || null,
    reminder_time: reminder_time || null,
    repeat_interval,
    status: completed ? 'completed' : 'pending'
  };

  if (isOffline) {
    if (!payload.id) {
      payload.id = "local_task_" + Date.now();
      tasksList.push(payload);
    } else {
      const idx = tasksList.findIndex(t => t.id === payload.id);
      if (idx !== -1) tasksList[idx] = payload;
    }
    localStorage.setItem("pwa_tasks", JSON.stringify(tasksList));
    pushToSyncQueue('save_task', payload);
    closeTaskModal();
    renderTasksList();
    renderDashboardWidgets();
  } else {
    fetch("/api.php?action=save_task", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Authorization": "Bearer " + userId
      },
      body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(() => {
      loadAppDatabases();
      closeTaskModal();
    });
  }
}

function deleteCurrentFormTask() {
  const id = document.getElementById("task-form-id").value;
  if (!id || !confirm("Удалить эту задачу?")) return;

  if (isOffline) {
    tasksList = tasksList.filter(t => t.id !== id);
    localStorage.setItem("pwa_tasks", JSON.stringify(tasksList));
    pushToSyncQueue('delete_task', { id });
    closeTaskModal();
    renderTasksList();
    renderDashboardWidgets();
  } else {
    fetch(`/api.php?action=delete_task&id=${id}`, {
      method: "DELETE",
      headers: { "Authorization": "Bearer " + userId }
    })
    .then(res => res.json())
    .then(() => {
      loadAppDatabases();
      closeTaskModal();
    });
  }
}

// --- CALENDAR RENDERING ---
function navigateCalendarMonth(dir) {
  calendarMonth += dir;
  if (calendarMonth < 0) {
    calendarMonth = 11;
    calendarYear--;
  } else if (calendarMonth > 11) {
    calendarMonth = 0;
    calendarYear++;
  }
  renderCalendarGrid();
}

function renderCalendarGrid() {
  const monthNames = ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];
  document.getElementById("calendar-month-title").textContent = `${monthNames[calendarMonth]} ${calendarYear}`;

  const weekdaysHeader = document.getElementById("calendar-weekday-header");
  weekdaysHeader.innerHTML = "";
  ["Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс"].forEach(day => {
    const el = document.createElement("span");
    el.className = "calendar-weekday";
    el.textContent = day;
    weekdaysHeader.appendChild(el);
  });

  const firstDayIdx = (new Date(calendarYear, calendarMonth, 1).getDay() + 6) % 7;
  const daysInMonth = new Date(calendarYear, calendarMonth + 1, 0).getDate();

  const daysContainer = document.getElementById("calendar-days-container");
  daysContainer.innerHTML = "";

  const todayStr = new Date().toISOString().split('T')[0];

  const prevMonthDays = new Date(calendarYear, calendarMonth, 0).getDate();
  for (let i = firstDayIdx - 1; i >= 0; i--) {
    const d = new Date(calendarYear, calendarMonth - 1, prevMonthDays - i);
    const dateStr = d.toISOString().split('T')[0];
    createCalendarCell(prevMonthDays - i, dateStr, false, dateStr === todayStr);
  }

  for (let i = 1; i <= daysInMonth; i++) {
    const d = new Date(calendarYear, calendarMonth, i + 1);
    const dateStr = d.toISOString().split('T')[0];
    createCalendarCell(i, dateStr, true, dateStr === todayStr);
  }

  const cellsRendered = firstDayIdx + daysInMonth;
  const remaining = 42 - cellsRendered;
  for (let i = 1; i <= remaining; i++) {
    const d = new Date(calendarYear, calendarMonth + 1, i + 1);
    const dateStr = d.toISOString().split('T')[0];
    createCalendarCell(i, dateStr, false, dateStr === todayStr);
  }

  renderCalendarDetailsList();
}

function createCalendarCell(dayNum, dateStr, isCurrentMonth, isToday) {
  const container = document.getElementById("calendar-days-container");
  const cell = document.createElement("div");
  cell.className = `calendar-cell ${isCurrentMonth ? '' : 'muted'} ${dateStr === calendarSelectedDate ? 'selected' : ''} ${isToday ? 'today' : ''}`;
  cell.onclick = () => {
    calendarSelectedDate = dateStr;
    renderCalendarGrid();
  };

  const tasksCount = tasksList.filter(t => t.due_date === dateStr).length;
  const notesCount = notesList.filter(n => n.created_at && n.created_at.split(' ')[0] === dateStr).length;

  let dotsHtml = "";
  if (tasksCount > 0) dotsHtml += `<span class="calendar-dot task"></span>`;
  if (notesCount > 0) dotsHtml += `<span class="calendar-dot note"></span>`;

  cell.innerHTML = `
    <span>${dayNum}</span>
    <div class="calendar-dots">${dotsHtml}</div>
  `;
  container.appendChild(cell);
}

function renderCalendarDetailsList() {
  const title = document.getElementById("calendar-details-title");
  const container = document.getElementById("calendar-details-container");

  const d = new Date(calendarSelectedDate);
  title.textContent = `События на ${d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' })}`;

  container.innerHTML = "";

  const dayTasks = tasksList.filter(t => t.due_date === calendarSelectedDate);
  const dayNotes = notesList.filter(n => n.created_at && n.created_at.split(' ')[0] === calendarSelectedDate);

  if (dayTasks.length === 0 && dayNotes.length === 0) {
    container.innerHTML = `<div class="card" style="padding:16px; text-align:center; color:var(--text-muted); font-size:12px;">Запланированных событий на этот день нет</div>`;
    return;
  }

  dayTasks.forEach(task => {
    const el = document.createElement("div");
    el.className = "card";
    el.onclick = () => openTaskFormModal(task);
    el.style.cssText = "display:flex; justify-content:space-between; align-items:center; padding:12px; margin-bottom:8px; cursor:pointer;";
    el.innerHTML = `
      <div style="display:flex; align-items:center; gap:8px;">
        <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background-color:var(--accent-color);"></span>
        <span style="font-size:13px; font-weight:600;">${task.title}</span>
      </div>
      <span class="priority-badge" style="background-color:var(--border-color); color:var(--text-main); font-size:7px;">ЗАДАЧА</span>
    `;
    container.appendChild(el);
  });

  dayNotes.forEach(note => {
    const el = document.createElement("div");
    el.className = "card";
    el.onclick = () => openNoteFormModal(note);
    el.style.cssText = "display:flex; justify-content:space-between; align-items:center; padding:12px; margin-bottom:8px; cursor:pointer;";
    el.innerHTML = `
      <div style="display:flex; align-items:center; gap:8px;">
        <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background-color:#10b981;"></span>
        <span style="font-size:13px; font-weight:600;">${note.title}</span>
      </div>
      <span class="priority-badge" style="background-color:var(--border-color); color:var(--text-main); font-size:7px;">ЗАМЕТКА</span>
    `;
    container.appendChild(el);
  });
}

// --- COLLABORATION DISCUSSIONS ---
function renderCollaborationDropdown() {
  const select = document.getElementById("chat-note-selector");
  select.innerHTML = `<option value="">-- Выберите заметку --</option>`;

  const shared = notesList.filter(n => n.shared);
  shared.forEach(note => {
    const opt = document.createElement("option");
    opt.value = note.id;
    opt.textContent = `${note.title} (${note.category})`;
    if (note.id === chatActiveNoteId) {
      opt.selected = true;
    }
    select.appendChild(opt);
  });

  onChatNoteChanged();
}

function onChatNoteChanged() {
  const select = document.getElementById("chat-note-selector");
  chatActiveNoteId = select.value;

  const chatPane = document.getElementById("chat-active-pane");
  const introPane = document.getElementById("chat-placeholder-pane");

  if (chatActiveNoteId) {
    chatPane.style.display = "flex";
    if (introPane) introPane.style.display = "none";
    loadChatMessages();
  } else {
    chatPane.style.display = "none";
    if (introPane) introPane.style.display = "block";
  }
}

function loadChatMessages() {
  if (isOffline) {
    renderChatMessages([]);
    return;
  }

  fetch(`/api.php?action=get_discussions&note_id=${chatActiveNoteId}`, {
    headers: { "Authorization": "Bearer " + userId }
  })
  .then(res => res.json())
  .then(data => {
    if (Array.isArray(data)) {
      renderChatMessages(data);
    }
  });
}

function renderChatMessages(msgs) {
  const scroller = document.getElementById("chat-messages-scroller");
  scroller.innerHTML = "";

  if (msgs.length === 0) {
    scroller.innerHTML = `<div style="text-align:center; color:var(--text-muted); padding:32px; font-size:11px;">Нет сообщений в этой теме. Начните обсуждение!</div>`;
    return;
  }

  msgs.forEach(msg => {
    const isMe = msg.user_id === userId;
    const msgDiv = document.createElement("div");
    msgDiv.className = `chat-bubble ${isMe ? 'me' : 'other'}`;

    msgDiv.innerHTML = `
      <div class="chat-top-row">
        <span class="chat-sender">${msg.user_name}</span>
        <span class="chat-time">${msg.timestamp}</span>
      </div>
      <p style="white-space:pre-wrap; leading-relaxed:normal;">${msg.text}</p>
    `;
    scroller.appendChild(msgDiv);
  });

  scroller.scrollTop = scroller.scrollHeight;
}

function sendChatMessage() {
  const input = document.getElementById("chat-message-input");
  const text = input.value.trim();
  if (!text || !chatActiveNoteId) return;

  fetch("/api.php?action=post_message", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Authorization": "Bearer " + userId
    },
    body: JSON.stringify({
      note_id: chatActiveNoteId,
      text: text
    })
  })
  .then(res => res.json())
  .then(() => {
    input.value = "";
    loadChatMessages();
  });
}

function toggleConferenceMic() {
  isAudioConferenceActive = !isAudioConferenceActive;

  const pulse = document.getElementById("audio-conf-pulse");
  const title = document.getElementById("audio-conf-title");
  const btn = document.getElementById("audio-conf-toggle-btn");
  const bar = document.getElementById("audio-conf-participants-bar");

  if (isAudioConferenceActive) {
    navigator.mediaDevices.getUserMedia({ audio: true })
    .then(() => {
      pulse.style.backgroundColor = "#ef4444";
      title.textContent = "Аудио-конференция активна";
      btn.textContent = "Покинуть";
      btn.style.backgroundColor = "#ef4444";
      bar.style.display = "block";
      if (navigator.vibrate) navigator.vibrate(50);
    })
    .catch(err => {
      isAudioConferenceActive = false;
      alert("Не удалось запустить микрофон: " + err.message);
    });
  } else {
    pulse.style.backgroundColor = "#10b981";
    title.textContent = "Конференция выключена";
    btn.textContent = "Подключиться";
    btn.style.backgroundColor = "#4f46e5";
    bar.style.display = "none";
  }
}

// --- PRODUCTIVITY PROGRESS BAR GRAPHICS ---
function renderProductivityStatistics() {
  const total = tasksList.length;
  const completed = tasksList.filter(t => t.status === "completed").length;
  const pending = total - completed;
  const pct = total === 0 ? 0 : Math.round((completed / total) * 100);

  document.getElementById("stats-val-pending").textContent = pending;
  document.getElementById("stats-val-completed").textContent = completed;
  document.getElementById("stats-val-percent").textContent = pct + "%";

  const container = document.getElementById("productivity-bars-container");
  container.innerHTML = "";

  systemCategories.forEach(cat => {
    const catTasks = tasksList.filter(t => t.category === cat);
    const catComp = catTasks.filter(t => t.status === "completed").length;
    const catPct = catTasks.length === 0 ? 0 : Math.round((catComp / catTasks.length) * 100);

    const row = document.createElement("div");
    row.className = "stats-row";
    row.innerHTML = `
      <div class="stats-label-row">
        <span style="font-weight:700;">${cat} (${catComp}/${catTasks.length})</span>
        <span style="font-weight:700; color:var(--accent-color);">${catPct}%</span>
      </div>
      <div class="stats-bar-outer">
        <div class="stats-bar-inner" style="width: ${catPct}%;"></div>
      </div>
    `;
    container.appendChild(row);
  });
}

// --- DIRECT VOICE NOTES RECORDER ---
function startMicRecording() {
  audioChunks = [];
  navigator.mediaDevices.getUserMedia({ audio: true })
  .then(stream => {
    mediaRecorder = new MediaRecorder(stream);
    mediaRecorder.ondataavailable = e => {
      if (e.data.size > 0) audioChunks.push(e.data);
    };
    mediaRecorder.onstop = () => {
      localAudioBlob = new Blob(audioChunks, { type: 'audio/wav' });
      currentAudioUrl = URL.createObjectURL(localAudioBlob);

      document.getElementById("rec-btn-start").style.display = "none";
      document.getElementById("rec-btn-stop").style.display = "none";
      document.getElementById("rec-btn-play").style.display = "inline-flex";
      document.getElementById("rec-btn-save").style.display = "inline-flex";
      document.getElementById("rec-btn-delete").style.display = "inline-flex";
      document.getElementById("audio-record-status").textContent = "Голосовая запись готова!";
    };

    mediaRecorder.start();
    isRecording = true;
    document.getElementById("rec-btn-start").style.display = "none";
    document.getElementById("rec-btn-stop").style.display = "inline-flex";
    document.getElementById("recording-indicator").style.display = "flex";
    document.getElementById("audio-record-status").textContent = "Идёт запись аудио...";
  })
  .catch(err => alert("Микрофон не отвечает: " + err.message));
}

function stopMicRecording() {
  if (mediaRecorder && isRecording) {
    mediaRecorder.stop();
    isRecording = false;
    document.getElementById("recording-indicator").style.display = "none";
  }
}

function playCurrentRecord() {
  if (currentAudioUrl) {
    new Audio(currentAudioUrl).play();
  }
}

function clearCurrentRecord() {
  currentAudioUrl = null;
  localAudioBlob = null;
  document.getElementById("rec-btn-start").style.display = "inline-flex";
  document.getElementById("rec-btn-play").style.display = "none";
  document.getElementById("rec-btn-save").style.display = "none";
  document.getElementById("rec-btn-delete").style.display = "none";
  document.getElementById("audio-record-status").textContent = "Записать мысли прямо сейчас";
}

function saveRecordToNote() {
  if (!localAudioBlob) return;
  if (isOffline) {
    alert("Офлайн-режим. Сохранение аудио временно недоступно.");
    return;
  }

  const fd = new FormData();
  formDataAppendHelper(fd, localAudioBlob);
}

function formDataAppendHelper(fd, blob, isModal = false) {
  fd.append("audio", blob, "voice_rec.wav");
  fetch("/api.php?action=upload_voice", {
    method: "POST",
    headers: { "Authorization": "Bearer " + userId },
    body: fd
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      if (isModal) {
        modalCurrentAudioUrl = data.audio_url;
        updateModalAudioStatus();
      } else {
        // Open empty modal prefilled
        openNoteFormModal();
        document.getElementById("note-form-title").value = "Голосовая заметка от " + new Date().toLocaleDateString('ru-RU');
        document.getElementById("note-form-content").value = "Голосовое сообщение привязано к заметке.";
        document.getElementById("note-form-tags").value = "Голос";
        modalCurrentAudioUrl = data.audio_url;
        updateModalAudioStatus();
        clearCurrentRecord();
      }
    } else {
      alert("Ошибка при сохранении записи: " + data.error);
    }
  });
}

// --- MODAL DIALOG ATTACHMENT RECORDINGS ---
function startModalMicRecording() {
  modalAudioChunks = [];
  navigator.mediaDevices.getUserMedia({ audio: true })
  .then(stream => {
    modalMediaRecorder = new MediaRecorder(stream);
    modalMediaRecorder.ondataavailable = e => {
      if (e.data.size > 0) modalAudioChunks.push(e.data);
    };
    modalMediaRecorder.onstop = () => {
      modalLocalAudioBlob = new Blob(modalAudioChunks, { type: 'audio/wav' });

      const fd = new FormData();
      formDataAppendHelper(fd, modalLocalAudioBlob, true);
    };

    modalMediaRecorder.start();
    isModalRecording = true;
    document.getElementById("modal-rec-btn").style.display = "none";
    document.getElementById("modal-stop-btn").style.display = "inline-flex";
    document.getElementById("modal-audio-status").textContent = "Идёт запись...";
  })
  .catch(err => alert("Микрофон заблокирован: " + err.message));
}

function stopModalMicRecording() {
  if (modalMediaRecorder && isModalRecording) {
    modalMediaRecorder.stop();
    isModalRecording = false;
  }
}

function updateModalAudioStatus() {
  const status = document.getElementById("modal-audio-status");
  const recBtn = document.getElementById("modal-rec-btn");
  const stopBtn = document.getElementById("modal-stop-btn");
  const playBtn = document.getElementById("modal-play-btn");
  const delBtn = document.getElementById("modal-delete-btn");

  recBtn.style.display = "none";
  stopBtn.style.display = "none";
  playBtn.style.display = "none";
  delBtn.style.display = "none";

  if (isModalRecording) {
    stopBtn.style.display = "inline-flex";
    status.textContent = "Идёт запись...";
  } else if (modalCurrentAudioUrl) {
    playBtn.style.display = "inline-flex";
    delBtn.style.display = "inline-flex";
    status.textContent = "Аудиозапись приложена";
  } else {
    recBtn.style.display = "inline-flex";
    status.textContent = "Нет аудиозаписи";
  }
}

function playModalRecord() {
  if (modalCurrentAudioUrl) {
    new Audio(modalCurrentAudioUrl).play();
  }
}

function deleteModalRecord() {
  modalCurrentAudioUrl = null;
  modalLocalAudioBlob = null;
  updateModalAudioStatus();
}

// --- OPTION CONFIGURATIONS & BACKUPS ---
function changeUserPin() {
  const oldPin = document.getElementById("settings-pin-old").value;
  const newPin = document.getElementById("settings-pin-new").value;

  if (!oldPin || !newPin) {
    alert("Пожалуйста, заполните поля текущего и нового PIN-кода.");
    return;
  }

  fetch("/api.php?action=change_pin", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Authorization": "Bearer " + userId
    },
    body: JSON.stringify({ old_pin: oldPin, new_pin: newPin })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert("PIN-код успешно изменен!");
      document.getElementById("settings-pin-old").value = "";
      document.getElementById("settings-pin-new").value = "";
    } else {
      alert("Ошибка: " + data.error);
    }
  });
}

function requestAppPermissions() {
  if ('Notification' in window) {
    Notification.requestPermission().then(perm => {
      alert("Доступ к уведомлениям: " + (perm === 'granted' ? 'Разрешено' : 'Заблокировано'));
    });
  }
  navigator.mediaDevices.getUserMedia({ audio: true })
  .then(() => alert("Микрофон успешно проверен и подключен."))
  .catch(e => console.warn(e));
}

function testLocalPush() {
  if ('Notification' in window && Notification.permission === 'granted') {
    new Notification("PWA Напоминание", {
      body: "Ваше тестовое push-уведомление успешно сработало!",
      icon: "/icon-192.png"
    });
  } else {
    alert("Сначала предоставьте доступ к уведомлениям.");
  }
}

function exportBackupData() {
  window.location.href = "/api.php?action=export";
}

function importBackupData(event) {
  const file = event.target.files[0];
  if (!file) return;

  const fd = new FormData();
  fd.append("backup_file", file);

  fetch("/api.php?action=import", {
    method: "POST",
    headers: { "Authorization": "Bearer " + userId },
    body: fd
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert("Резервная копия импортирована!");
      loadAppDatabases();
    } else {
      alert("Ошибка импорта: " + data.error);
    }
  });
}
