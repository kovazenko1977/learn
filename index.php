<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление новостями - WES.BY</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div id="app">
        <!-- Login Screen -->
        <div v-if="!authenticated" class="login-container">
            <div class="login-box">
                <h2>Вход в панель</h2>
                <p>Введите 6-значный код доступа</p>
                <div class="code-input">
                    <input type="password" v-model="loginCode" maxlength="6" @keyup.enter="login" placeholder="******">
                </div>
                <button @click="login" :disabled="loading">Войти</button>
                <p v-if="loginError" class="error">{{ loginError }}</p>
            </div>
        </div>

        <!-- Dashboard -->
        <div v-else class="dashboard">
            <header>
                <div class="header-content">
                    <h1>Управление новостями</h1>
                    <div class="header-actions">
                        <button class="btn-secondary" @click="showEmbedModal = true">Код для сайта</button>
                        <button class="btn-primary" @click="openEditor()">Добавить новость</button>
                        <button class="btn-logout" @click="logout">Выйти</button>
                    </div>
                </div>
            </header>

            <main>
                <div class="filters">
                    <input type="text" v-model="searchQuery" placeholder="Поиск по заголовку...">
                    <select v-model="statusFilter">
                        <option value="all">Все статусы</option>
                        <option value="published">Опубликовано</option>
                        <option value="draft">Черновик</option>
                    </select>
                </div>

                <div class="news-list">
                    <div v-if="filteredNews.length === 0" class="empty-state">
                        Новости не найдены
                    </div>
                    <div v-for="item in filteredNews" :key="item.id" class="news-card">
                        <div class="news-image" :style="{ backgroundImage: 'url(' + (item.image || 'assets/img/no-image.png') + ')' }"></div>
                        <div class="news-info">
                            <div class="news-header">
                                <span class="news-date">{{ formatDate(item.date) }}</span>
                                <span v-if="item.status === 'published' && isFuture(item.date)" class="status-badge scheduled">Запланировано</span>
                                <span v-else :class="['status-badge', item.status]">{{ item.status === 'published' ? 'Опубликовано' : 'Черновик' }}</span>
                            </div>
                            <h3>{{ item.title }}</h3>
                            <p>{{ truncate(item.content, 100) }}</p>
                            <div class="news-actions">
                                <button class="btn-edit" @click="openEditor(item)">Изменить</button>
                                <button class="btn-delete" @click="deleteNews(item.id)">Удалить</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <footer>
                <div class="footer-content">
                    <p>Разработано <strong>WES.BY</strong> &bull; Тел: <a href="tel:+375333533971">+375 33 353 39 71</a></p>
                </div>
            </footer>
        </div>

        <!-- Editor Modal -->
        <div v-if="showEditor" class="modal-overlay">
            <div class="modal">
                <div class="modal-header">
                    <h2>{{ editingItem.id ? 'Изменить новость' : 'Новая новость' }}</h2>
                    <button class="close-btn" @click="showEditor = false">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Заголовок</label>
                        <input type="text" v-model="editingItem.title" placeholder="Введите заголовок">
                    </div>
                    <div class="form-group">
                        <label>Контент (HTML поддерживается)</label>
                        <textarea v-model="editingItem.content" rows="6" placeholder="Текст новости..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Дата</label>
                        <input type="datetime-local" v-model="editingItem.date">
                    </div>
                    <div class="form-group">
                        <label>Статус</label>
                        <select v-model="editingItem.status">
                            <option value="published">Опубликовано</option>
                            <option value="draft">Черновик</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Изображение</label>
                        <div class="upload-area">
                            <img v-if="editingItem.image" :src="editingItem.image" class="preview-img">
                            <input type="file" @change="uploadImage" accept="image/*">
                            <p v-if="!editingItem.image">Нажмите для выбора фото</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-secondary" @click="showEditor = false">Отмена</button>
                    <button class="btn-primary" @click="saveNews" :disabled="loading">Сохранить</button>
                </div>
            </div>
        </div>

        <!-- Embed Modal -->
        <div v-if="showEmbedModal" class="modal-overlay">
            <div class="modal">
                <div class="modal-header">
                    <h2>Код для размещения на сайте</h2>
                    <button class="close-btn" @click="showEmbedModal = false">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Скопируйте этот код и вставьте в нужное место на вашем сайте:</p>
                    <div class="code-preview">
                        <pre><code>{{ embedCode }}</code></pre>
                    </div>
                    <button class="btn-primary" @click="copyEmbedCode">Копировать код</button>
                </div>
            </div>
        </div>

        <!-- Notification Toast -->
        <div v-if="toast" :class="['toast', toast.type]">
            {{ toast.message }}
        </div>
    </div>

    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
