import { computed, reactive } from 'vue';
import api from '@/shared/services/api';

function readStoredUser() {
    try {
        return JSON.parse(localStorage.getItem('admin_user') ?? 'null');
    } catch {
        return null;
    }
}

const state = reactive({
    user: readStoredUser(),
    token: localStorage.getItem('admin_token') ?? '',
});

const isAdminAuthenticated = computed(() => Boolean(state.token));

function clearSession() {
    localStorage.removeItem('admin_token');
    localStorage.removeItem('admin_user');
    state.token = '';
    state.user = null;
}

async function login(email, password) {
    const { data } = await api.post('/admin/auth/login', { email, password });

    localStorage.setItem('admin_token', data.token);
    localStorage.setItem('admin_user', JSON.stringify(data.user));
    state.token = data.token;
    state.user = data.user;

    return data;
}

async function fetchMe() {
    const { data } = await api.get('/admin/auth/me');
    state.user = data.data;
    localStorage.setItem('admin_user', JSON.stringify(data.data));

    return data.data;
}

async function logout() {
    try {
        await api.post('/admin/auth/logout');
    } catch {
        // ignore network/token errors on logout
    }

    clearSession();
}

export function useAdminAuth() {
    return { state, isAdminAuthenticated, login, fetchMe, logout, clearSession };
}
