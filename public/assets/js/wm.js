/**
 * Windows 7 Window Manager for Sanatorium 2.0
 */
class WindowManager {
    constructor() {
        this.windows = [];
        this.zIndex = 100;
        this.desktop = document.getElementById('desktop');
        this.taskbarIcons = document.getElementById('taskbar-icons');
    }

    createWindow(title, url, icon = 'fa-window-maximize') {
        const id = 'win-' + Math.random().toString(36).substr(2, 9);
        const win = document.createElement('div');
        win.id = id;
        win.className = 'window absolute bg-[#f0f0f0] border border-[#71a3d9] rounded-t-lg shadow-2xl flex flex-col overflow-hidden transition-all duration-200';
        win.style.width = '800px';
        win.style.height = '600px';
        win.style.left = (50 + (this.windows.length * 30)) + 'px';
        win.style.top = (50 + (this.windows.length * 30)) + 'px';
        win.style.zIndex = ++this.zIndex;

        win.innerHTML = `
            <div class="window-header h-8 bg-gradient-to-b from-[#ebf3fe] to-[#cfe3ff] flex items-center justify-between px-3 cursor-move border-b border-[#a1c1e8] rounded-t-lg">
                <div class="flex items-center space-x-2 overflow-hidden">
                    <i class="fas ${icon} text-[#4a7ac9] text-xs"></i>
                    <span class="text-xs font-semibold text-[#1e395b] truncate">${title}</span>
                </div>
                <div class="flex items-center space-x-1">
                    <button class="win-min w-6 h-5 hover:bg-[#aecceb] flex items-center justify-center rounded-sm transition-colors"><i class="fas fa-minus text-[10px] text-[#1e395b]"></i></button>
                    <button class="win-max w-6 h-5 hover:bg-[#aecceb] flex items-center justify-center rounded-sm transition-colors"><i class="far fa-square text-[10px] text-[#1e395b]"></i></button>
                    <button class="win-close w-10 h-5 bg-gradient-to-b from-[#e8a3a3] to-[#c75050] hover:from-[#f0b5b5] hover:to-[#d66060] flex items-center justify-center rounded-sm border border-[#9b3838] transition-colors"><i class="fas fa-times text-[10px] text-white"></i></button>
                </div>
            </div>
            <div class="window-content flex-grow bg-white overflow-auto p-4 custom-scrollbar">
                <div class="animate-pulse flex space-y-4 flex-col">
                    <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                    <div class="h-4 bg-gray-200 rounded"></div>
                    <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                </div>
            </div>
            <div class="window-footer h-6 bg-[#f0f0f0] border-t border-[#d0d0d0] flex items-center px-3 text-[10px] text-gray-500">
                Ready
            </div>
        `;

        this.desktop.appendChild(win);
        this.windows.push({ id, title, icon, minimized: false });
        this.addTaskbarIcon(id, title, icon);
        this.makeDraggable(win);
        this.loadContent(win, url);

        win.addEventListener('mousedown', () => this.focusWindow(id));
        win.querySelector('.win-close').addEventListener('click', (e) => { e.stopPropagation(); this.closeWindow(id); });
        win.querySelector('.win-min').addEventListener('click', (e) => { e.stopPropagation(); this.minimizeWindow(id); });

        return id;
    }

    async loadContent(win, url) {
        const contentArea = win.querySelector('.window-content');
        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const html = await response.text();
            contentArea.innerHTML = html;
            // Re-initialize scripts in the content
            const scripts = contentArea.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
        } catch (e) {
            contentArea.innerHTML = '<p class="text-red-500">Error loading content.</p>';
        }
    }

    addTaskbarIcon(id, title, icon) {
        const btn = document.createElement('button');
        btn.id = 'task-' + id;
        btn.className = 'w-12 h-10 flex items-center justify-center hover:bg-white/20 transition-all rounded-md relative group';
        btn.innerHTML = `
            <div class="w-8 h-8 bg-gradient-to-br from-white/30 to-transparent rounded flex items-center justify-center border border-white/20 shadow-inner">
                <i class="fas ${icon} text-white text-lg drop-shadow"></i>
            </div>
            <div class="absolute -top-10 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-[10px] px-2 py-1 rounded hidden group-hover:block whitespace-nowrap z-50">
                ${title}
            </div>
        `;
        btn.onclick = () => this.toggleWindow(id);
        this.taskbarIcons.appendChild(btn);
    }

    focusWindow(id) {
        const win = document.getElementById(id);
        if (win) {
            win.style.zIndex = ++this.zIndex;
            win.classList.remove('opacity-50');
            this.windows.forEach(w => {
                if (w.id !== id) {
                    const otherWin = document.getElementById(w.id);
                    if (otherWin) otherWin.classList.add('opacity-90');
                }
            });
        }
    }

    toggleWindow(id) {
        const win = document.getElementById(id);
        const wData = this.windows.find(w => w.id === id);
        if (wData.minimized) {
            win.classList.remove('scale-0', 'opacity-0');
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
        win.classList.add('scale-0', 'opacity-0');
        this.windows.find(w => w.id === id).minimized = true;
    }

    closeWindow(id) {
        const win = document.getElementById(id);
        const taskIcon = document.getElementById('task-' + id);
        win.remove();
        taskIcon.remove();
        this.windows = this.windows.filter(w => w.id !== id);
    }

    makeDraggable(win) {
        const header = win.querySelector('.window-header');
        let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
        header.onmousedown = (e) => {
            e = e || window.event;
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
