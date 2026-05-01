const API = {
    baseUrl: '/api',

    async request(endpoint, options = {}) {
        const token = localStorage.getItem('token');
        const headers = {
            'Content-Type': 'application/json',
            ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
            ...options.headers
        };

        const config = {
            ...options,
            headers
        };

        try {
            const response = await fetch(`${this.baseUrl}${endpoint}`, config);
            if (response.status === 401 && !endpoint.includes('auth.php?action=login')) {
                localStorage.removeItem('token');
                window.location.hash = '#login';
                return null;
            }
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || 'Ошибка запроса');
            }
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    auth: {
        login: (login, password) => API.request('/auth.php?action=login', {
            method: 'POST',
            body: JSON.stringify({ login, password })
        }),
        me: () => API.request('/auth.php?action=me'),
        updateProfile: (data) => API.request('/auth.php?action=profile', {
            method: 'POST',
            body: JSON.stringify(data)
        })
    },

    requests: {
        list: () => API.request('/requests.php?action=list'),
        get: (id) => API.request(`/requests.php?action=get&id=${id}`),
        create: (data) => API.request('/requests.php?action=create', {
            method: 'POST',
            body: JSON.stringify(data)
        }),
        updateStatus: (id, status) => API.request('/requests.php?action=update_status', {
            method: 'POST',
            body: JSON.stringify({ id, status })
        }),
        assign: (id, executorId) => API.request('/requests.php?action=assign', {
            method: 'POST',
            body: JSON.stringify({ id, executor_id: executorId })
        }),
        addComment: (data) => API.request('/requests.php?action=add_comment', {
            method: 'POST',
            body: JSON.stringify(data)
        })
    },

    admin: {
        getUsers: () => API.request('/admin.php?action=users'),
        saveUser: (data) => API.request('/admin.php?action=users', {
            method: 'POST',
            body: JSON.stringify(data)
        }),
        deleteUser: (id) => API.request(`/admin.php?action=users&id=${id}`, {
            method: 'DELETE'
        }),
        getSettings: () => API.request('/admin.php?action=settings'),
        saveSettings: (data) => API.request('/admin.php?action=settings', {
            method: 'POST',
            body: JSON.stringify(data)
        }),
        setStorageMode: (mode, dbConfig) => API.request('/admin.php?action=storage_mode', {
            method: 'POST',
            body: JSON.stringify({ mode, db_config: dbConfig })
        })
    },

    analytics: {
        getStats: () => API.request('/analytics.php')
    },

    upload: async (file) => {
        const formData = new FormData();
        formData.append('file', file);
        const token = localStorage.getItem('token');

        const response = await fetch('/api/upload.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            body: formData
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Ошибка загрузки');
        }
        return await response.json();
    }
};
