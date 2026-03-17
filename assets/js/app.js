const App = {
    user: null,
    products: [],
    clients: [],
    orders: [],
    cart: [],

    init() {
        this.checkAuth();
        this.bindEvents();
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
        } else {
            alert('Invalid credentials');
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
            header.innerHTML = '<h1>B2B Vitrina</h1>';
            main.innerHTML = `
                <div class="card" style="max-width:400px; margin: 100px auto;">
                    <h2>Login</h2>
                    <form id="login-form">
                        <div><input type="text" name="username" placeholder="Username" required></div>
                        <div><input type="password" name="password" placeholder="Password" required></div>
                        <button type="submit" class="btn">Enter</button>
                    </form>
                </div>
            `;
            return;
        }

        header.innerHTML = `
            <div>
                <h1>B2B Vitrina</h1>
                <nav>
                    ${this.user.role === 'admin' ? `
                        <button class="btn" data-action="view-products">Products</button>
                        <button class="btn" data-action="view-clients">Clients</button>
                        <button class="btn" data-action="view-orders">Orders</button>
                    ` : `
                        <button class="btn" data-action="view-products">Showcase</button>
                        <button class="btn" data-action="view-orders">My Orders</button>
                    `}
                </nav>
            </div>
            <div>
                <span>${this.user.name}</span>
                <button class="btn" data-action="logout">Logout</button>
            </div>
        `;

        if (view === 'products' || !view) this.renderProducts();
        if (view === 'clients') this.renderClients();
        if (view === 'orders') this.renderOrders();
    },

    async renderProducts() {
        const res = await fetch('api/products.php');
        this.products = await res.json();
        const main = document.getElementById('main');

        let html = `
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h2>Products</h2>
                ${this.user.role === 'admin' ? '<button class="btn" data-action="add-product">+ Add Product</button>' : ''}
            </div>
            <div class="grid">
        `;

        this.products.forEach(p => {
            html += `
                <div class="card">
                    <img src="${p.image || 'assets/placeholder.png'}" class="product-img">
                    <h3>${this.escapeHTML(p.name)}</h3>
                    <p>${this.escapeHTML(p.description)}</p>
                    <p><strong>${p.price} RUB</strong></p>
                    <p>Stock:
                        ${p.sufficient ?
                            '<span class="stock-tag sufficient">Sufficient</span>' :
                            `<span class="stock-tag">${p.stock} pcs</span>`
                        }
                    </p>
                    ${this.user.role === 'admin' ? `
                        <button class="btn" data-action="edit-product" data-id="${p.id}">Edit</button>
                        <button class="btn" data-action="delete-product" data-id="${p.id}" style="background:red">Delete</button>
                    ` : `
                        <button class="btn" data-action="add-to-cart" data-id="${p.id}">Add to Order</button>
                    `}
                </div>
            `;
        });

        html += '</div>';

        if (this.user.role === 'client' && this.cart.length > 0) {
            html += `
                <div class="card" style="position:sticky; bottom:20px; border-top: 4px solid var(--accent-color);">
                    <h3>Your Cart</h3>
                    <ul>
                        ${this.cart.map(i => `<li>${this.escapeHTML(i.name)} - ${i.quantity} x ${i.price}</li>`).join('')}
                    </ul>
                    <p><strong>Total: ${this.cart.reduce((s, i) => s + i.price * i.quantity, 0)} RUB</strong></p>
                    <button class="btn" data-action="place-order">Confirm Order</button>
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
                <h2>Clients</h2>
                <button class="btn" data-action="add-client">+ Add Client</button>
            </div>
            <div class="card">
                <table>
                    <thead>
                        <tr><th>Name</th><th>Username</th><th>Details</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        ${this.clients.map(c => `
                            <tr>
                                <td>${this.escapeHTML(c.name)}</td>
                                <td>${this.escapeHTML(c.username)}</td>
                                <td>${this.escapeHTML(c.details)}</td>
                                <td>
                                    <button class="btn" data-action="edit-client" data-id="${c.id}">Edit</button>
                                    <button class="btn" data-action="delete-client" data-id="${c.id}" style="background:red">Delete</button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
        main.innerHTML = html;
    },

    async renderOrders() {
        const res = await fetch('api/orders.php');
        this.orders = await res.json();
        const main = document.getElementById('main');

        let html = `<h2>Orders History</h2>`;
        this.orders.sort((a,b) => new Date(b.date) - new Date(a.date)).forEach(o => {
            html += `
                <div class="card">
                    <div style="display:flex; justify-content:space-between;">
                        <div>
                            <strong>Order #${o.id}</strong> - ${o.date} (${o.status})<br>
                            Client: ${this.escapeHTML(o.client_name)}
                        </div>
                        <div>
                            <strong>${o.total} RUB</strong>
                            ${this.user.role === 'admin' ? `<button class="btn" data-action="export-1c" data-id="${o.id}">Export to 1C</button>` : ''}
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
            <h3>${id ? 'Edit' : 'Add'} Product</h3>
            <form id="product-form">
                <input type="hidden" name="id" value="${p.id}">
                <input type="hidden" name="existing_image" value="${p.image}">
                <div><input type="text" name="name" placeholder="Name" value="${p.name}" required></div>
                <div><textarea name="description" placeholder="Description">${p.description}</textarea></div>
                <div><input type="number" step="0.01" name="price" placeholder="Price" value="${p.price}" required></div>
                <div><input type="number" name="stock" placeholder="Stock Quantity" value="${p.stock}"></div>
                <div>
                    <label>
                        <input type="checkbox" name="sufficient" value="true" ${p.sufficient ? 'checked' : ''} style="width:auto"> Sufficient Stock (hide quantity)
                    </label>
                </div>
                <div><input type="file" name="image" accept="image/*"></div>
                <button type="submit" class="btn">Save</button>
                <button type="button" class="btn" style="background:#ccc" onclick="document.getElementById('modal').style.display='none'">Cancel</button>
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
    },

    async deleteProduct(id) {
        if (!confirm('Are you sure?')) return;
        await fetch(`api/products.php?id=${id}`, { method: 'DELETE' });
        this.renderProducts();
    },

    showClientModal(id = null) {
        const c = id ? this.clients.find(x => x.id === id) : {id:'', username:'', password:'', name:'', details:''};
        document.getElementById('modal-body').innerHTML = `
            <h3>${id ? 'Edit' : 'Add'} Client</h3>
            <form id="client-form">
                <input type="hidden" name="id" value="${c.id}">
                <div><input type="text" name="name" placeholder="Client Name" value="${c.name}" required></div>
                <div><input type="text" name="username" placeholder="Username" value="${c.username}" required></div>
                <div><input type="text" name="password" placeholder="Password" value="${c.password}" required></div>
                <div><textarea name="details" placeholder="Details (INN, Bank, etc.)">${c.details}</textarea></div>
                <button type="submit" class="btn">Save</button>
                <button type="button" class="btn" style="background:#ccc" onclick="document.getElementById('modal').style.display='none'">Cancel</button>
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
    },

    async deleteClient(id) {
        if (!confirm('Are you sure?')) return;
        await fetch(`api/clients.php?id=${id}`, { method: 'DELETE' });
        this.renderClients();
    },

    addToCart(id) {
        const p = this.products.find(x => x.id === id);
        const item = this.cart.find(x => x.id === id);
        if (item) {
            item.quantity++;
        } else {
            this.cart.push({ id: p.id, name: p.name, price: p.price, quantity: 1 });
        }
        this.renderProducts();
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
            alert('Order placed successfully!');
            this.cart = [];
            this.render('orders');
        }
    }
};

App.init();
