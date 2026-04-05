<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление новостями - WES.BY</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Quill Rich Text Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill-image-resize-module@3.0.0/image-resize.min.js"></script>
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
                        <button class="btn-secondary" @click="showSettingsModal = true">Настройки</button>
                        <button class="btn-secondary" @click="showGroupsModal = true">Группы</button>
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
                    <select v-model="groupFilter">
                        <option value="all">Все группы</option>
                        <option value="default">По умолчанию</option>
                        <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
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
                                <div style="display: flex; gap: 5px;">
                                    <span class="status-badge group">{{ getGroupName(item.group_id) }}</span>
                                    <span v-if="item.status === 'published' && isFuture(item.date)" class="status-badge scheduled">Запланировано</span>
                                    <span v-else :class="['status-badge', item.status]">{{ item.status === 'published' ? 'Опубликовано' : 'Черновик' }}</span>
                                </div>
                            </div>
                            <h3>{{ item.title }}</h3>
                            <div class="news-preview-content" v-html="truncate(item.content, 100)"></div>
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
        <div v-show="showEditor" class="modal-overlay">
            <div class="modal">
                <div class="modal-header">
                    <h2>{{ editingItem.id ? 'Изменить новость' : 'Новая новость' }}</h2>
                    <button class="close-btn" @click="closeEditor">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Заголовок</label>
                        <input type="text" v-model="editingItem.title" placeholder="Введите заголовок">
                    </div>
                    <div class="form-group">
                        <label>Контент новости</label>
                        <div id="editor-container" style="height: 300px; background: white;"></div>
                    </div>
                    <div class="form-group">
                        <label>Дата</label>
                        <input type="datetime-local" v-model="editingItem.date">
                    </div>
                    <div class="form-group">
                        <label>Группа</label>
                        <select v-model="editingItem.group_id">
                            <option value="default">По умолчанию</option>
                            <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Статус</label>
                        <select v-model="editingItem.status">
                            <option value="published">Опубликовано</option>
                            <option value="draft">Черновик</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Главное изображение (обложка)</label>
                        <div class="upload-area">
                            <img v-if="editingItem.image" :src="editingItem.image" class="preview-img">
                            <input type="file" @change="uploadImage" accept="image/*">
                            <p v-if="!editingItem.image">Нажмите для выбора фото</p>
                        </div>
                    </div>
                    <div class="form-group" v-if="editingItem.image">
                        <label>Ширина обложки (например: 100%, 500px или auto)</label>
                        <input type="text" v-model="editingItem.image_width" placeholder="auto">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-secondary" @click="closeEditor">Отмена</button>
                    <button class="btn-primary" @click="saveNews" :disabled="loading">Сохранить</button>
                </div>
            </div>
        </div>

        <!-- Settings Modal -->
        <div v-if="showSettingsModal" class="modal-overlay">
            <div class="modal">
                <div class="modal-header">
                    <h2>Настройки и Сервис</h2>
                    <button class="close-btn" @click="showSettingsModal = false">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Код доступа (6 цифр)</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="password" v-model="accessCodeInput" maxlength="6" placeholder="******">
                            <button class="btn-primary" @click="updateSettings" :disabled="loading">Обновить</button>
                        </div>
                    </div>
                    <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
                    <div class="service-actions">
                        <h3>Сервис</h3>
                        <p style="margin-bottom: 15px; font-size: 0.9rem; color: #888;">Внимание: эти действия необратимы</p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <button class="btn-delete" @click="cleanupFiles" :disabled="loading">Очистить мусор</button>
                            <button class="btn-delete" @click="deleteAllNews" :disabled="loading">Удалить все</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Groups Modal -->
        <div v-if="showGroupsModal" class="modal-overlay">
            <div class="modal">
                <div class="modal-header">
                    <h2>Управление группами</h2>
                    <button class="close-btn" @click="showGroupsModal = false">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Новая группа</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" v-model="editingGroup.name" placeholder="Название группы">
                            <button class="btn-primary" @click="saveGroup">Добавить</button>
                        </div>
                    </div>
                    <div class="groups-list" style="margin-top: 20px;">
                        <div v-for="g in groups" :key="g.id" class="group-item" style="display: flex; justify-content: space-between; padding: 10px; border-bottom: 1px solid #eee; align-items: center;">
                            <span>{{ g.name }}</span>
                            <button class="btn-delete" @click="deleteGroup(g.id)" style="padding: 5px 10px;">Удалить</button>
                        </div>
                    </div>
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
                    <div class="form-group">
                        <label>Выберите группу новостей</label>
                        <select v-model="embedGroup">
                            <option value="default">По умолчанию</option>
                            <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
                        </select>
                    </div>
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
