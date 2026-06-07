/**
 * Профессиональный менеджер окон Sanatorium 2.0 ERP
 */
class WindowManager {
    constructor() {
        this.windows = [];
        this.zIndex = 1000;
        this.desktop = null;
        this.taskbarIcons = null;
        this.isMobile = window.innerWidth <= 640;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    init() {
        this.desktop = document.getElementById('desktop');
        this.taskbarIcons = document.getElementById('taskbar-icons');

        window.addEventListener('resize', () => {
            this.isMobile = window.innerWidth <= 640;
        });

        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link && link.closest('.window-content')) {
                const url = link.getAttribute('href');
                if (url && !url.startsWith('http') && !url.startsWith('javascript') && !url.startsWith('#')) {
                    e.preventDefault();
                    const win = link.closest('.window');
                    this.loadContent(win, url);
                }
            }
        });

        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.closest('.window-content')) {
                e.preventDefault();
                const win = form.closest('.window');
                this.submitForm(win, form);
            }
        });
    }

    async submitForm(win, form) {
        const url = form.getAttribute('action');
        const method = (form.getAttribute('method') || 'POST').toUpperCase();
        const formData = new FormData(form);

        this.setLoading(win, true);

        try {
            const options = {
                method: method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            };

            if (method === 'GET') {
                const params = new URLSearchParams(formData).toString();
                const fullUrl = url.includes('?') ? `${url}&${params}` : `${url}?${params}`;
                return this.loadContent(win, fullUrl);
            } else {
                options.body = formData;
            }

            const response = await fetch(url, options);
            const xRedirect = response.headers.get('X-Redirect');
            if (xRedirect) {
                return this.loadContent(win, xRedirect);
            }

            const html = await response.text();
            this.updateWindowContent(win, html);
        } catch (e) {
            this.showError(win, 'Ошибка отправки данных: ' + e.message);
        } finally {
            this.setLoading(win, false);
        }
    }

    createWindow(title, url, icon = 'fa-window-maximize', iconClass = 'text-blue-500') {
        const id = 'win-' + Math.random().toString(36).substr(2, 9);
        const win = document.createElement('div');
        win.id = id;
        win.className = 'window absolute bg-white border border-slate-200 flex flex-col overflow-hidden transition-all duration-300 transform scale-95 opacity-0';

        if (this.isMobile) {
            win.style.width = '100vw';
            win.style.height = 'calc(100vh - 56px)';
            win.style.left = '0';
            win.style.top = '0';
        } else {
            win.style.width = '1100px';
            win.style.height = '800px';
            win.style.left = (60 + (this.windows.length * 40)) + 'px';
            win.style.top = (40 + (this.windows.length * 40)) + 'px';
            win.classList.add('rounded-2xl');
        }

        win.style.zIndex = ++this.zIndex;

        win.innerHTML = `
            <div class="window-header h-14 bg-white flex items-center justify-between px-6 cursor-move border-b border-slate-100 flex-shrink-0">
                <div class="flex items-center space-x-4 overflow-hidden">
                    <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center flex-shrink-0 border border-slate-100">
                        <i class="fas ${icon} ${iconClass} text-sm"></i>
                    </div>
                    <span class="text-sm font-black text-slate-800 truncate tracking-tight uppercase">${title}</span>
                </div>
                <div class="flex items-center space-x-2">
                    ${!this.isMobile ? `
                    <button class="win-min w-10 h-10 hover:bg-slate-50 flex items-center justify-center rounded-xl transition-all text-slate-400 hover:text-slate-600"><i class="fas fa-minus text-xs"></i></button>
                    <button class="win-max w-10 h-10 hover:bg-slate-50 flex items-center justify-center rounded-xl transition-all text-slate-400 hover:text-slate-600"><i class="far fa-square text-xs"></i></button>
                    ` : ''}
                    <button class="win-close w-12 h-10 hover:bg-rose-600 hover:text-white flex items-center justify-center rounded-xl transition-all text-slate-400 border border-transparent hover:border-rose-600">
                        <i class="fas fa-times text-base"></i>
                    </button>
                </div>
            </div>
            <div class="window-content flex-grow bg-white overflow-auto p-6 sm:p-10 custom-scrollbar">
                <div class="flex items-center justify-center h-full">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 shadow-xl"></div>
                </div>
            </div>
            <div class="window-footer h-10 bg-slate-50 border-t border-slate-100 flex items-center px-6 text-[9px] text-slate-400 font-black uppercase tracking-[0.3em] flex-shrink-0">
                <i class="fas fa-shield-check text-emerald-500 mr-2"></i> Защищенное соединение • ${title}
            </div>
        `;

        this.desktop.appendChild(win);

        setTimeout(() => {
            win.classList.remove('scale-95', 'opacity-0');
        }, 10);

        this.windows.push({ id, title, icon, iconClass, minimized: false });
        this.addTaskbarIcon(id, title, icon, iconClass);
        this.makeDraggable(win);
        this.loadContent(win, url);

        win.addEventListener('mousedown', () => this.focusWindow(id));
        win.querySelector('.win-close').addEventListener('click', (e) => { e.stopPropagation(); this.closeWindow(id); });

        if (!this.isMobile) {
            win.querySelector('.win-min').addEventListener('click', (e) => { e.stopPropagation(); this.minimizeWindow(id); });
            win.querySelector('.win-max').addEventListener('click', (e) => { e.stopPropagation(); this.maximizeWindow(id); });
        }

        return id;
    }

    async loadContent(win, url) {
        this.setLoading(win, true);
        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });

            const xRedirect = response.headers.get('X-Redirect');
            if (xRedirect) {
                return this.loadContent(win, xRedirect);
            }

            if (!response.ok) throw new Error('Ошибка сервера: ' + response.status);
            const html = await response.text();
            this.updateWindowContent(win, html);
        } catch (e) {
            this.showError(win, e.message);
        } finally {
            this.setLoading(win, false);
        }
    }

    updateWindowContent(win, html) {
        const contentArea = win.querySelector('.window-content');
        contentArea.innerHTML = html;

        const scripts = contentArea.querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    setLoading(win, isLoading) {
        const footer = win.querySelector('.window-footer');
        if (isLoading) {
            footer.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2 text-blue-500"></i> Синхронизация данных...';
        } else {
            const title = this.windows.find(w => w.id === win.id)?.title || '';
            footer.innerHTML = `<i class="fas fa-shield-check text-emerald-500 mr-2"></i> Защищенное соединение • ${title}`;
        }
    }

    showError(win, message) {
        const contentArea = win.querySelector('.window-content');
        contentArea.innerHTML = `
            <div class="flex flex-col items-center justify-center h-full text-center p-10">
                <div class="w-24 h-24 bg-rose-50 text-rose-500 rounded-[2.5rem] flex items-center justify-center mb-8 shadow-inner">
                    <i class="fas fa-exclamation-triangle text-4xl"></i>
                </div>
                <h3 class="text-2xl font-black text-slate-800 tracking-tight">Системная ошибка</h3>
                <p class="text-sm text-slate-500 mt-4 leading-relaxed max-w-sm font-medium">${message}</p>
                <button onclick="location.reload()" class="mt-12 px-10 py-4 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-2xl shadow-slate-900/20 active:scale-95 transition-all">Перезагрузить Sanatorium ERP</button>
            </div>
        `;
    }

    addTaskbarIcon(id, title, icon, iconClass) {
        const btn = document.createElement('button');
        btn.id = 'task-' + id;
        btn.className = 'w-12 h-12 flex items-center justify-center bg-white/10 hover:bg-white/20 transition-all rounded-2xl relative group border border-white/5 flex-shrink-0 active:scale-90';
        btn.innerHTML = `
            <i class="fas ${icon} ${iconClass} text-xl"></i>
            <div class="hidden sm:group-hover:block absolute -top-16 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[10px] px-5 py-2.5 rounded-[1rem] whitespace-nowrap z-[7000] shadow-2xl font-black uppercase tracking-widest border border-white/10">
                ${title}
            </div>
            <div class="absolute bottom-2 left-1/2 -translate-x-1/2 w-2 h-1 bg-blue-400 rounded-full shadow-[0_0_10px_#60a5fa]"></div>
        `;
        btn.onclick = () => this.toggleWindow(id);
        this.taskbarIcons.appendChild(btn);
    }

    focusWindow(id) {
        const win = document.getElementById(id);
        if (win) {
            win.style.zIndex = ++this.zIndex;
            this.windows.forEach(w => {
                const icon = document.getElementById('task-' + w.id);
                if (icon) {
                    if (w.id === id) icon.classList.add('bg-white/40', 'border-white/20', 'scale-110', 'shadow-2xl');
                    else icon.classList.remove('bg-white/40', 'border-white/20', 'scale-110', 'shadow-2xl');
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
            win.style.width = '1100px';
            win.style.height = '800px';
            win.style.top = '60px';
            win.style.left = '80px';
            win.classList.add('rounded-2xl');
        } else {
            win.style.width = '100%';
            win.style.height = 'calc(100vh - 56px)';
            win.style.top = '0';
            win.style.left = '0';
            win.classList.remove('rounded-2xl');
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
        if (this.isMobile) return;
        const header = win.querySelector('.window-header');
        let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
        header.onmousedown = (e) => {
            if (win.style.width === '100%') return;
            e = e || window.event;
            if (e.target.closest('button')) return;
            e.preventDefault();
            pos3 = e.clientX;
            pos4 = e.clientY;
            document.onmouseup = () => {
                document.onmouseup = null;
                document.onmousemove = null;
            };
            document.onmousemove = (e) => {
                e = e || window.event;
                e.preventDefault();
                pos1 = pos3 - e.clientX;
                pos2 = pos4 - e.clientY;
                pos3 = e.clientX;
                pos4 = e.clientY;
                win.style.top = (win.offsetTop - pos2) + "px";
                win.style.left = (win.offsetLeft - pos1) + "px";
            };
            this.focusWindow(win.id);
        };
    }
}

window.wm = new WindowManager();
