import { computed, reactive } from 'vue';
import api from '@/shared/services/api';
import { getGuestToken } from '@/shared/services/api';

function readStoredUser() {
    try {
        return JSON.parse(localStorage.getItem('auth_user') ?? 'null');
    } catch {
        return null;
    }
}

const state = reactive({
    user: readStoredUser(),
    token: localStorage.getItem('access_token') ?? '',
});

const isAuthenticated = computed(() => Boolean(state.token));
const isAdmin = computed(() => state.user?.role === 'admin');

function persist(token, user) {
    localStorage.setItem('access_token', token);
    localStorage.setItem('auth_user', JSON.stringify(user));
    state.token = token;
    state.user = user;
}

function clearSession() {
    localStorage.removeItem('access_token');
    localStorage.removeItem('auth_user');
    state.token = '';
    state.user = null;
}

async function requestOtp(phone) {
    const { data } = await api.post('/auth/request-otp', { phone });

    return data;
}

async function verifyOtp(payload) {
    const { data } = await api.post('/auth/verify-otp', {
        ...payload,
        guest_token: getGuestToken(),
    });

    persist(data.token, data.user);

    return data;
}

async function fetchMe() {
    const { data } = await api.get('/auth/me');
    state.user = data.data;
    localStorage.setItem('auth_user', JSON.stringify(data.data));

    return data.data;
}

async function updateProfile(payload) {
    const { data } = await api.patch('/profile', payload);
    state.user = data.data;
    localStorage.setItem('auth_user', JSON.stringify(data.data));

    return data;
}

async function logout() {
    try {
        await api.post('/auth/logout');
    } catch {
        // The token may already be invalid; clearing locally is enough.
    }

    clearSession();
}

export function useAuth() {
    return {
        state,
        isAuthenticated,
        isAdmin,
        persist,
        clearSession,
        requestOtp,
        verifyOtp,
        fetchMe,
        updateProfile,
        logout,
    };
}
