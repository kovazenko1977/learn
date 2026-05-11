<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DocHub - Хранилище документов</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.3);
            --primary-color: #4a90e2;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }

        [v-cloak] { display: none; }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        }

        .upload-zone {
            border: 2px dashed var(--primary-color);
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(74, 144, 226, 0.05);
        }

        .upload-zone:hover {
            background: rgba(74, 144, 226, 0.1);
            transform: translateY(-2px);
        }

        .table {
            --bs-table-bg: transparent;
        }

        .btn-group .btn {
            border-radius: 8px;
            margin: 0 2px;
        }

        .glass-modal {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
        }

        .modal-header { border-bottom: none; }
        .modal-footer { border-top: none; }

        h1 {
            background: linear-gradient(to right, #4a90e2, #63b3ed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <div id="app" v-cloak>
        <div class="container py-5">
            <header class="mb-5 text-center">
                <h1 class="display-4 fw-bold">DocHub</h1>
                <p class="lead">Безопасное хранилище для ваших Word и Excel документов</p>
            </header>

            <div class="glass-card mb-5">
                <div class="p-4">
                    <div class="upload-zone" @click="$refs.fileInput.click()" @dragover.prevent @drop.prevent="handleDrop">
                        <input type="file" ref="fileInput" class="d-none" @change="handleFileChange">
                        <div class="text-center py-4">
                            <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-primary"></i>
                            <p class="mb-0">Нажмите или перетащите файл сюда</p>
                        </div>
                    </div>
                    <div v-if="uploading" class="mt-3">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" :style="{width: uploadProgress + '%'}"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card">
                <div class="p-4">
                    <h2 class="h4 mb-4"><i class="fas fa-file-alt me-2"></i> Ваши документы</h2>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Имя файла</th>
                                    <th>Тип</th>
                                    <th>Размер</th>
                                    <th>Дата загрузки</th>
                                    <th class="text-end">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="file in files" :key="file.id">
                                    <td>
                                        <i :class="getFileIcon(file.extension)" class="me-2 text-primary"></i>
                                        {{ file.original_name }}
                                    </td>
                                    <td>{{ file.extension.toUpperCase() }}</td>
                                    <td>{{ formatSize(file.size) }}</td>
                                    <td>{{ file.uploaded_at }}</td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <button @click="previewFile(file)" class="btn btn-sm btn-outline-info" title="Просмотр">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a :href="'api/download.php?id=' + file.id" class="btn btn-sm btn-outline-success" title="Скачать">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button @click="deleteFile(file.id)" class="btn btn-sm btn-outline-danger" title="Удалить">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="files.length === 0">
                                    <td colspan="5" class="text-center py-4 text-muted">Файлов пока нет. Загрузите что-нибудь!</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content glass-modal">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ previewingFile?.original_name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0 text-center">
                        <template v-if="previewingFile">
                            <img v-if="isImage(previewingFile.extension)" :src="'api/view.php?id=' + previewingFile.id" class="img-fluid p-3" style="max-height: 80vh">
                            <video v-else-if="isVideo(previewingFile.extension)" :src="'api/view.php?id=' + previewingFile.id" controls class="w-100 p-3" style="max-height: 80vh"></video>
                            <audio v-else-if="isAudio(previewingFile.extension)" :src="'api/view.php?id=' + previewingFile.id" controls class="w-75 my-5"></audio>
                            <iframe v-else :src="getPreviewUrl(previewingFile)" width="100%" height="600px" frameborder="0"></iframe>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const { createApp, ref, onMounted } = Vue;

        createApp({
            setup() {
                const files = ref([]);
                const uploading = ref(false);
                const uploadProgress = ref(0);
                const previewingFile = ref(null);
                let modal = null;

                const fetchFiles = async () => {
                    const res = await fetch('api/list.php');
                    files.value = await res.json();
                };

                const handleFileChange = (e) => {
                    const file = e.target.files[0];
                    if (file) uploadFile(file);
                };

                const handleDrop = (e) => {
                    const file = e.dataTransfer.files[0];
                    if (file) uploadFile(file);
                };

                const uploadFile = async (file) => {
                    const blacklisted = ['php', 'phtml', 'js', 'html', 'exe', 'sh'];
                    const ext = file.name.split('.').pop().toLowerCase();
                    if (blacklisted.includes(ext)) {
                        alert('Этот тип файла запрещен из соображений безопасности');
                        return;
                    }

                    uploading.value = true;
                    uploadProgress.value = 0;

                    const formData = new FormData();
                    formData.append('file', file);

                    try {
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', 'api/upload.php', true);

                        xhr.upload.onprogress = (e) => {
                            if (e.lengthComputable) {
                                uploadProgress.value = Math.round((e.loaded / e.total) * 100);
                            }
                        };

                        xhr.onload = () => {
                            if (xhr.status === 200) {
                                fetchFiles();
                                uploading.value = false;
                            } else {
                                alert('Ошибка при загрузке');
                                uploading.value = false;
                            }
                        };

                        xhr.send(formData);
                    } catch (error) {
                        console.error(error);
                        uploading.value = false;
                    }
                };

                const deleteFile = async (id) => {
                    if (!confirm('Вы уверены, что хотите удалить этот файл?')) return;

                    const res = await fetch('api/delete.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    });

                    if (res.ok) fetchFiles();
                };

                const previewFile = (file) => {
                    previewingFile.value = file;
                    if (!modal) {
                        modal = new bootstrap.Modal(document.getElementById('previewModal'));
                    }
                    modal.show();
                };

                const getPreviewUrl = (file) => {
                    // Using Google Docs Viewer. Note: it requires a public URL.
                    // For local dev, this might not show anything, but it's the standard way.
                    const baseUrl = window.location.origin + window.location.pathname.replace('index.php', '');
                    const fileUrl = baseUrl + 'api/view.php?id=' + file.id;
                    return `https://docs.google.com/viewer?url=${encodeURIComponent(fileUrl)}&embedded=true`;
                };

                const formatSize = (bytes) => {
                    if (bytes === 0) return '0 B';
                    const k = 1024;
                    const sizes = ['B', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                };

                const isImage = (ext) => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext);
                const isVideo = (ext) => ['mp4', 'webm', 'ogg'].includes(ext);
                const isAudio = (ext) => ['mp3', 'wav', 'ogg'].includes(ext);

                const getFileIcon = (ext) => {
                    if (['doc', 'docx'].includes(ext)) return 'far fa-file-word';
                    if (['xls', 'xlsx'].includes(ext)) return 'far fa-file-excel';
                    if (['pdf'].includes(ext)) return 'far fa-file-pdf';
                    if (isImage(ext)) return 'far fa-file-image';
                    if (isVideo(ext)) return 'far fa-file-video';
                    if (isAudio(ext)) return 'far fa-file-audio';
                    if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) return 'far fa-file-archive';
                    return 'far fa-file';
                };

                onMounted(fetchFiles);

                return {
                    files, uploading, uploadProgress, previewingFile,
                    handleFileChange, handleDrop, deleteFile, previewFile,
                    getPreviewUrl, formatSize, getFileIcon,
                    isImage, isVideo, isAudio
                };
            }
        }).mount('#app');
    </script>
</body>
</html>
