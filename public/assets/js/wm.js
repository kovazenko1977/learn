/**
 * Windows-like Window Manager for Sanatorium 2.0
 */
class WindowManager {
    constructor() {
        this.windows = [];
        this.zIndex = 100;
        this.desktop = document.getElementById('desktop');
        this.taskbarIcons = document.getElementById('taskbar-icons');
    }

    createWindow(title, url, icon = 'fa-window-maximize', iconClass = 'text-blue-500') {
        const id = 'win-' + Math.random().toString(36).substr(2, 9);
        const win = document.createElement('div');
        win.id = id;
        win.className = 'window absolute bg-[#f8fafc] border border-slate-200 shadow-2xl flex flex-col overflow-hidden transition-all duration-300 transform scale-95 opacity-0';
        win.style.width = '900px';
        win.style.height = '650px';
        win.style.left = (100 + (this.windows.length * 40)) + 'px';
        win.style.top = (60 + (this.windows.length * 40)) + 'px';
        win.style.zIndex = ++this.zIndex;

        win.innerHTML = `
            <div class="window-header h-12 bg-white flex items-center justify-between px-4 cursor-move border-b border-slate-100">
                <div class="flex items-center space-x-3 overflow-hidden">
                    <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center">
                        <i class="fas ${icon} ${iconClass} text-sm"></i>
                    </div>
                    <span class="text-sm font-bold text-slate-700 truncate">${title}</span>
                </div>
                <div class="flex items-center space-x-2">
                    <button class="win-min w-8 h-8 hover:bg-slate-100 flex items-center justify-center rounded-lg transition-colors"><i class="fas fa-minus text-[10px] text-slate-400"></i></button>
                    <button class="win-max w-8 h-8 hover:bg-slate-100 flex items-center justify-center rounded-lg transition-colors"><i class="far fa-square text-[10px] text-slate-400"></i></button>
                    <button class="win-close w-8 h-8 hover:bg-rose-500 hover:text-white flex items-center justify-center rounded-lg transition-colors text-slate-400"><i class="fas fa-times text-[10px]"></i></button>
                </div>
            </div>
            <div class="window-content flex-grow bg-white overflow-auto p-8 custom-scrollbar">
                <div class="flex items-center justify-center h-full">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                </div>
            </div>
            <div class="window-footer h-8 bg-slate-50 border-t border-slate-100 flex items-center px-4 text-[10px] text-slate-400 font-bold uppercase tracking-widest">
                Система готова
            </div>
        `;

        this.desktop.appendChild(win);

        // Trigger animation
        setTimeout(() => {
            win.classList.remove('scale-95', 'opacity-0');
        }, 10);

        this.windows.push({ id, title, icon, iconClass, minimized: false });
        this.addTaskbarIcon(id, title, icon, iconClass);
        this.makeDraggable(win);
        this.loadContent(win, url);

        win.addEventListener('mousedown', () => this.focusWindow(id));
        win.querySelector('.win-close').addEventListener('click', (e) => { e.stopPropagation(); this.closeWindow(id); });
        win.querySelector('.win-min').addEventListener('click', (e) => { e.stopPropagation(); this.minimizeWindow(id); });
        win.querySelector('.win-max').addEventListener('click', (e) => { e.stopPropagation(); this.maximizeWindow(id); });

        return id;
    }

    async loadContent(win, url) {
        const contentArea = win.querySelector('.window-content');
        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('Сетевая ошибка при загрузке данных');
            const html = await response.text();
            contentArea.innerHTML = html;

            // Execute scripts
            const scripts = contentArea.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
        } catch (e) {
            contentArea.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full text-center">
                    <i class="fas fa-triangle-exclamation text-rose-500 text-4xl mb-4"></i>
                    <h3 class="text-lg font-bold text-slate-800">Ошибка загрузки модуля</h3>
                    <p class="text-sm text-slate-500">${e.message}</p>
                </div>
            `;
        }
    }

    addTaskbarIcon(id, title, icon, iconClass) {
        const btn = document.createElement('button');
        btn.id = 'task-' + id;
        btn.className = 'w-12 h-10 flex items-center justify-center bg-white/10 hover:bg-white/20 transition-all rounded-xl relative group border border-white/5 active:scale-95';
        btn.innerHTML = `
            <i class="fas ${icon} ${iconClass} text-lg"></i>
            <div class="absolute -top-12 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[10px] px-3 py-1.5 rounded-lg hidden group-hover:block whitespace-nowrap z-50 shadow-xl border border-white/10 font-bold uppercase tracking-wider">
                ${title}
            </div>
            <div class="absolute bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 bg-blue-400 rounded-full shadow-[0_0_5px_#60a5fa]"></div>
        `;
        btn.onclick = () => this.toggleWindow(id);
        this.taskbarIcons.appendChild(btn);
    }

    focusWindow(id) {
        const win = document.getElementById(id);
        if (win) {
            win.style.zIndex = ++this.zIndex;
            win.classList.remove('opacity-60');
            this.windows.forEach(w => {
                if (w.id !== id) {
                    const otherWin = document.getElementById(w.id);
                    if (otherWin) otherWin.classList.add('opacity-90');
                }
            });

            // Highlight taskbar icon
            this.windows.forEach(w => {
                const icon = document.getElementById('task-' + w.id);
                if (icon) {
                    if (w.id === id) icon.classList.add('bg-white/30', 'border-white/20');
                    else icon.classList.remove('bg-white/30', 'border-white/20');
                }
            });
        }
    }

    toggleWindow(id) {
        const win = document.getElementById(id);
        const wData = this.windows.find(w => w.id === id);
        if (wData.minimized) {
            win.classList.remove('scale-95', 'opacity-0');
            win.style.pointerEvents = 'auto';
            wData.minimized = false;
            this.focusWindow(id);
        } else {
            if (parseInt(win.style.zIndex) < this.zIndex) {
                this.focusWindow(id);
            } else {
                this.minimizeWindow(id);
            }
        }
    }

    minimizeWindow(id) {
        const win = document.getElementById(id);
        win.classList.add('scale-95', 'opacity-0');
        win.style.pointerEvents = 'none';
        this.windows.find(w => w.id === id).minimized = true;
    }

    maximizeWindow(id) {
        const win = document.getElementById(id);
        if (win.style.width === '100%') {
            win.style.width = '900px';
            win.style.height = '650px';
            win.style.top = '60px';
            win.style.left = '100px';
            win.classList.add('rounded-xl');
        } else {
            win.style.width = '100%';
            win.style.height = 'calc(100vh - 56px)';
            win.style.top = '0';
            win.style.left = '0';
            win.classList.remove('rounded-xl');
        }
    }

    closeWindow(id) {
        const win = document.getElementById(id);
        const taskIcon = document.getElementById('task-' + id);
        win.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            win.remove();
            taskIcon.remove();
            this.windows = this.windows.filter(w => w.id !== id);
        }, 300);
    }

    makeDraggable(win) {
        const header = win.querySelector('.window-header');
        let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
        header.onmousedown = (e) => {
            if (win.style.width === '100%') return;
            e = e || window.event;
            if (e.target.closest('button')) return;
            e.preventDefault();
            pos3 = e.clientX;
            pos4 = e.clientY;
            document.onmouseup = closeDragElement;
            document.onmousemove = elementDrag;
            this.focusWindow(win.id);
        };

        const elementDrag = (e) => {
            e = e || window.event;
            e.preventDefault();
            pos1 = pos3 - e.clientX;
            pos2 = pos4 - e.clientY;
            pos3 = e.clientX;
            pos4 = e.clientY;
            win.style.top = (win.offsetTop - pos2) + "px";
            win.style.left = (win.offsetLeft - pos1) + "px";
        };

        function closeDragElement() {
            document.onmouseup = null;
            document.onmousemove = null;
        }
    }
}

window.wm = new WindowManager();
