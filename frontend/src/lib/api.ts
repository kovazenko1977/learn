const BASE_URL = 'http://localhost:3000';

export async function apiRequest(path: string, options: RequestInit = {}) {
  const token = typeof window !== 'undefined' ? localStorage.getItem('token') : null;

  const headers = {
    'Content-Type': 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...options.headers,
  };

  const response = await fetch(`${BASE_URL}${path}`, {
    ...options,
    headers,
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({ message: 'An error occurred' }));
    throw new Error(error.message || 'API request failed');
  }

  return response.json();
}

export const api = {
  get: (path: string) => apiRequest(path, { method: 'GET' }),
  post: (path: string, body: any) => apiRequest(path, { method: 'POST', body: JSON.stringify(body) }),
  put: (path: string, body: any) => apiRequest(path, { method: 'PUT', body: JSON.stringify(body) }),
  delete: (path: string) => apiRequest(path, { method: 'DELETE' }),
};
