import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: { Accept: 'application/json' },
    timeout: 20000,
});

/**
 * Every visitor gets a stable UUID used to keep a cart server-side before login.
 */
export function getGuestToken() {
    let token = localStorage.getItem('guest_token');

    if (token) return token;

    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        token = crypto.randomUUID();
    } else {
        token = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
            const random = (Math.random() * 16) | 0;
            const value = char === 'x' ? random : (random & 0x3) | 0x8;

            return value.toString(16);
        });
    }

    localStorage.setItem('guest_token', token);

    return token;
}

api.interceptors.request.use((config) => {
    const url = config.url ?? '';
    const isAdminRequest = url.startsWith('/admin');
    const token = isAdminRequest
        ? localStorage.getItem('admin_token')
        : localStorage.getItem('access_token');

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    } else if (!isAdminRequest) {
        config.headers['X-Guest-Token'] = getGuestToken();
    }

    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const url = error.config?.url ?? '';
        const isAuthCall = url.includes('/auth/');

        if (status === 401 && !isAuthCall) {
            const isAdminArea = url.startsWith('/admin')
                || window.location.pathname.startsWith('/admin');

            if (isAdminArea) {
                localStorage.removeItem('admin_token');
                localStorage.removeItem('admin_user');
            } else {
                localStorage.removeItem('access_token');
                localStorage.removeItem('auth_user');
            }

            window.dispatchEvent(new CustomEvent('auth:expired', { detail: { admin: isAdminArea } }));
        }

        return Promise.reject(error);
    },
);

export default api;
