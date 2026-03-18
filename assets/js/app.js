const App = {
    user: null,
    products: [],
    filteredProducts: [],
    clients: [],
    orders: [],
    cart: [],

    init() {
        this.checkAuth();
        this.bindEvents();
    },

    toast(msg) {
        const container = document.getElementById('toast-container');
        const el = document.createElement('div');
        el.className = 'toast';
        el.textContent = msg;
        container.appendChild(el);
        setTimeout(() => el.remove(), 3000);
    },

    escapeHTML(str) {
        if (!str) return "";
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    },

    async checkAuth() {
        const res = await fetch('api/auth.php?action=status');
        const data = await res.json();
        if (data.logged_in) {
            this.user = data.user;
            this.render();
        } else {
            this.render('login');
        }
    },

    bindEvents() {
        document.body.addEventListener('submit', (e) => {
            if (e.target.id === 'login-form') this.handleLogin(e);
            if (e.target.id === 'product-form') this.handleProductSave(e);
            if (e.target.id === 'client-form') this.handleClientSave(e);
        });

        document.body.addEventListener('input', (e) => {
            if (e.target.id === 'product-search') {
                this.filterProducts(e.target.value);
            }
        });

        document.body.addEventListener('change', (e) => {
            const action = e.target.dataset.action;
            const id = e.target.dataset.id;
            if (action === 'update-status') this.updateOrderStatus(id, e.target.value);
        });

        document.body.addEventListener('click', (e) => {
            const action = e.target.dataset.action;
            const id = e.target.dataset.id;

            if (action === 'logout') this.handleLogout();
            if (action === 'view-products') this.render('products');
            if (action === 'view-clients') this.render('clients');
            if (action === 'view-orders') this.render('orders');
            if (action === 'add-product') this.showProductModal();
            if (action === 'edit-product') this.showProductModal(id);
            if (action === 'delete-product') this.deleteProduct(id);
            if (action === 'add-client') this.showClientModal();
            if (action === 'edit-client') this.showClientModal(id);
            if (action === 'delete-client') this.deleteClient(id);
            if (action === 'add-to-cart') this.addToCart(id);
            if (action === 'place-order') this.placeOrder();
            if (action === 'export-1c') window.location.href = `api/export.php?id=${id}`;
            if (action === 'view-client-details') this.showClientDetails(id);
        });
    },

    async handleLogin(e) {
        e.preventDefault();
        const fd = new FormData(e.target);
        const res = await fetch('api/auth.php?action=login', {
            method: 'POST',
            body: JSON.stringify(Object.fromEntries(fd))
        });
        if (res.ok) {
            const data = await res.json();
            this.user = data.user;
            this.render();
            this.toast('Успешный вход');
        } else {
            this.toast('Неверный логин или пароль');
        }
    },

    async handleLogout() {
        await fetch('api/auth.php?action=logout');
        this.user = null;
        this.render('login');
    },

    async render(view = '') {
        const main = document.getElementById('main');
        const header = document.getElementById('app-header');

        if (!this.user || view === 'login') {
            header.innerHTML = '<h1>Витрина B2B</h1>';
            main.innerHTML = `
                <div class="card" style="max-width:400px; margin: 100px auto;">
                    <h2>Вход в систему</h2>
                    <form id="login-form">
                        <div><input type="text" name="username" placeholder="Логин" required></div>
                        <div><input type="password" name="password" placeholder="Пароль" required></div>
                        <button type="submit" class="btn">Войти</button>
                    </form>
                </div>
            `;
            return;
        }

        header.innerHTML = `
            <div>
                <h1>Витрина B2B</h1>
                <nav>
                    ${this.user.role === 'admin' ? `
                        <button class="btn" data-action="view-products">Товары</button>
                        <button class="btn" data-action="view-clients">Клиенты</button>
                        <button class="btn" data-action="view-orders">Заказы</button>
                    ` : `
                        <button class="btn" data-action="view-products">Витрина</button>
                        <button class="btn" data-action="view-orders">Мои заказы</button>
                    `}
                </nav>
            </div>
            <div>
                <span>${this.user.name}</span>
                <button class="btn" data-action="logout">Выйти</button>
            </div>
        `;

        if (view === 'products' || !view) this.renderProducts();
        if (view === 'clients') this.renderClients();
        if (view === 'orders') this.renderOrders();
    },

    filterProducts(query) {
        query = query.toLowerCase();
        this.filteredProducts = this.products.filter(p =>
            p.name.toLowerCase().includes(query) ||
            p.description.toLowerCase().includes(query)
        );
        this.renderProductsList(true);
    },

    async renderProducts() {
        const res = await fetch('api/products.php');
        this.products = await res.json();
        this.filteredProducts = [...this.products];
        this.renderProductsList();
    },

    renderProductsList(isFiltering = false) {
        const main = document.getElementById('main');
        const query = document.getElementById('product-search')?.value || '';

        let html = `
            <style>
                #product-search:focus { outline: 2px solid var(--accent-color); }
            </style>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
                <h2>Товары</h2>
                <div style="flex-grow:1; max-width:400px;">
                    <input type="text" id="product-search" placeholder="Поиск товаров..." style="width:100%" value="${this.escapeHTML(query)}">
                </div>
                ${this.user.role === 'admin' ? '<button class="btn" data-action="add-product">+ Добавить товар</button>' : ''}
            </div>
            <div class="grid" id="products-grid">
        `;

        this.renderGridItems(html, isFiltering);
    },

    renderGridItems(htmlPrefix, isFiltering) {
        const main = document.getElementById('main');
        let html = htmlPrefix;

        this.filteredProducts.forEach(p => {
            html += `
                <div class="card">
                    <img src="${p.image || 'assets/placeholder.png'}" class="product-img">
                    <h3>${this.escapeHTML(p.name)}</h3>
                    <p>${this.escapeHTML(p.description)}</p>
                    <p><strong>${p.price} BYN</strong></p>
                    <p>Остаток:
                        ${p.sufficient ?
                            '<span class="stock-tag sufficient">Достаточно</span>' :
                            `<span class="stock-tag">${p.stock} шт.</span>`
                        }
                    </p>
                    ${this.user.role === 'admin' ? `
                        <button class="btn" data-action="edit-product" data-id="${p.id}">Редактировать</button>
                        <button class="btn" data-action="delete-product" data-id="${p.id}" style="background:red">Удалить</button>
                    ` : `
                        <button class="btn" data-action="add-to-cart" data-id="${p.id}">В заказ</button>
                    `}
                </div>
            `;
        });

        html += '</div>';

        if (this.user.role === 'client' && this.cart.length > 0) {
            html += `
                <div class="card" style="position:sticky; bottom:20px; border-top: 4px solid var(--accent-color);">
                    <h3>Ваша корзина</h3>
                    <ul>
                        ${this.cart.map(i => `<li>${this.escapeHTML(i.name)} - ${i.quantity} x ${i.price}</li>`).join('')}
                    </ul>
                    <p><strong>Итого: ${this.cart.reduce((s, i) => s + i.price * i.quantity, 0)} BYN</strong></p>
                    <button class="btn" data-action="place-order">Подтвердить заказ</button>
                </div>
            `;
        }

        main.innerHTML = html;
    },

    async renderClients() {
        const res = await fetch('api/clients.php');
        this.clients = await res.json();
        const main = document.getElementById('main');

        let html = `
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h2>Клиенты</h2>
                <button class="btn" data-action="add-client">+ Добавить клиента</button>
            </div>
            <div class="card">
                <table>
                    <thead>
                        <tr><th>Наименование</th><th>Логин</th><th>Реквизиты</th><th>Действия</th></tr>
                    </thead>
                    <tbody>
                        ${this.clients.map(c => `
                            <tr>
                                <td>${this.escapeHTML(c.name)}</td>
                                <td>${this.escapeHTML(c.username)}</td>
                                <td>${this.escapeHTML(c.details)}</td>
                                <td>
                                    <button class="btn" data-action="edit-client" data-id="${c.id}">Ред.</button>
                                    <button class="btn" data-action="delete-client" data-id="${c.id}" style="background:red">Удал.</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
        main.innerHTML = html;
    },

    async updateOrderStatus(id, status) {
        await fetch(`api/orders.php?id=${id}`, {
            method: 'PUT',
            body: JSON.stringify({ status })
        });
        this.renderOrders();
        this.toast('Статус заказа обновлен');
    },

    async renderOrders() {
        const resOrders = await fetch('api/orders.php');
        this.orders = await resOrders.json();
        const resClients = await fetch('api/clients.php');
        this.clients = await resClients.json();
        const main = document.getElementById('main');

        const statuses = {
            'pending': 'Ожидается',
            'processing': 'В обработке',
            'shipped': 'Отгружен',
            'completed': 'Завершен',
            'cancelled': 'Отменен'
        };

        let html = `<h2>История заказов</h2>`;
        this.orders.sort((a,b) => new Date(b.date) - new Date(a.date)).forEach(o => {
            const currentStatusLabel = statuses[o.status] || o.status;
            html += `
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items: flex-start;">
                        <div>
                            <strong>Заказ #${o.id}</strong> - ${o.date} (${currentStatusLabel})<br>
                            Клиент: <a href="#" data-action="view-client-details" data-id="${o.client_id}" style="color:var(--accent-color); text-decoration:none">${this.escapeHTML(o.client_name)}</a>
                        </div>
                        <div style="text-align:right">
                            <strong>${o.total} BYN</strong><br>
                            <div style="margin-top:5px; display:flex; gap:5px; justify-content:flex-end;">
                                ${this.user.role === 'admin' ? `
                                    <select class="btn" style="background:#fff; color:#000; padding:4px;" data-action="update-status" data-id="${o.id}">
                                        ${Object.entries(statuses).map(([val, label]) => `
                                            <option value="${val}" ${o.status === val ? 'selected' : ''}>${label}</option>
                                        `).join('')}
                                    </select>
                                    <button class="btn" data-action="export-1c" data-id="${o.id}">Выгрузить в 1С</button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                    <ul style="margin-top:10px;">
                        ${o.items.map(i => `<li>${this.escapeHTML(i.name)} x ${i.quantity}</li>`).join('')}
                    </ul>
                </div>
            `;
        });
        main.innerHTML = html;
    },

    showProductModal(id = null) {
        const p = id ? this.products.find(x => x.id === id) : {id:'', name:'', description:'', price:0, stock:0, sufficient:false, image:''};
        document.getElementById('modal-body').innerHTML = `
            <h3>${id ? 'Редактировать' : 'Добавить'} товар</h3>
            <form id="product-form">
                <input type="hidden" name="id" value="${p.id}">
                <input type="hidden" name="existing_image" value="${p.image}">
                <div><input type="text" name="name" placeholder="Наименование" value="${p.name}" required></div>
                <div><textarea name="description" placeholder="Описание">${p.description}</textarea></div>
                <div><input type="number" step="0.01" name="price" placeholder="Цена" value="${p.price}" required></div>
                <div><input type="number" name="stock" placeholder="Количество на остатке" value="${p.stock}"></div>
                <div>
                    <label>
                        <input type="checkbox" name="sufficient" value="true" ${p.sufficient ? 'checked' : ''} style="width:auto"> Достаточный остаток (скрыть количество)
                    </label>
                </div>
                <div><input type="file" name="image" accept="image/*"></div>
                <button type="submit" class="btn">Сохранить</button>
                <button type="button" class="btn" style="background:#ccc" onclick="document.getElementById('modal').style.display='none'">Отмена</button>
            </form>
        `;
        document.getElementById('modal').style.display = 'flex';
    },

    async handleProductSave(e) {
        e.preventDefault();
        const fd = new FormData(e.target);
        await fetch('api/products.php', { method: 'POST', body: fd });
        document.getElementById('modal').style.display = 'none';
        this.renderProducts();
        this.toast('Товар сохранен');
    },

    async deleteProduct(id) {
        if (!confirm('Вы уверены?')) return;
        await fetch(`api/products.php?id=${id}`, { method: 'DELETE' });
        this.renderProducts();
        this.toast('Товар удален');
    },

    showClientModal(id = null) {
        const c = id ? this.clients.find(x => x.id === id) : {id:'', username:'', password:'', name:'', details:''};
        document.getElementById('modal-body').innerHTML = `
            <h3>${id ? 'Редактировать' : 'Добавить'} клиента</h3>
            <form id="client-form">
                <input type="hidden" name="id" value="${c.id}">
                <div><input type="text" name="name" placeholder="Наименование компании" value="${c.name}" required></div>
                <div><input type="text" name="username" placeholder="Логин" value="${c.username}" required></div>
                <div><input type="text" name="password" placeholder="Пароль" value="${c.password}" required></div>
                <div><textarea name="details" placeholder="Реквизиты (ИНН, КПП, Банк и т.д.)">${c.details}</textarea></div>
                <button type="submit" class="btn">Сохранить</button>
                <button type="button" class="btn" style="background:#ccc" onclick="document.getElementById('modal').style.display='none'">Отмена</button>
            </form>
        `;
        document.getElementById('modal').style.display = 'flex';
    },

    async handleClientSave(e) {
        e.preventDefault();
        const fd = new FormData(e.target);
        await fetch('api/clients.php', {
            method: 'POST',
            body: JSON.stringify(Object.fromEntries(fd))
        });
        document.getElementById('modal').style.display = 'none';
        this.renderClients();
        this.toast('Клиент сохранен');
    },

    async deleteClient(id) {
        if (!confirm('Вы уверены?')) return;
        await fetch(`api/clients.php?id=${id}`, { method: 'DELETE' });
        this.renderClients();
        this.toast('Клиент удален');
    },

    showClientDetails(id) {
        const c = this.clients.find(x => x.id === id);
        if (!c) return;
        document.getElementById('modal-body').innerHTML = `
            <h3>Реквизиты: ${this.escapeHTML(c.name)}</h3>
            <div class="card" style="white-space: pre-wrap; background: #f9f9f9;">${this.escapeHTML(c.details)}</div>
            <button type="button" class="btn" onclick="document.getElementById('modal').style.display='none'">Закрыть</button>
        `;
        document.getElementById('modal').style.display = 'flex';
    },

    addToCart(id) {
        const p = this.products.find(x => x.id === id);
        const item = this.cart.find(x => x.id === id);
        if (item) {
            item.quantity++;
        } else {
            this.cart.push({ id: p.id, name: p.name, price: p.price, quantity: 1 });
        }
        this.renderProductsList(true);
        this.toast('Добавлено в корзину');
    },

    async placeOrder() {
        const res = await fetch('api/orders.php', {
            method: 'POST',
            body: JSON.stringify({
                items: this.cart,
                total: this.cart.reduce((s, i) => s + i.price * i.quantity, 0)
            })
        });
        if (res.ok) {
            this.toast('Заказ успешно размещен!');
            this.cart = [];
            this.render('orders');
        }
    }
};

App.init();
