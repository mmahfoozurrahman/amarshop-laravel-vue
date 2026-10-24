import { reactive } from 'vue';
import api from '@/shared/services/api';

const state = reactive({
    ids: [],
    items: [],
    loading: false,
});

const isLoggedIn = () => Boolean(localStorage.getItem('access_token'));

async function refresh() {
    if (!isLoggedIn()) {
        reset();

        return;
    }

    state.loading = true;

    try {
        const { data } = await api.get('/wishlist');
        state.items = data.data ?? [];
        state.ids = state.items.map((item) => item.product?.id).filter(Boolean);
    } catch {
        reset();
    } finally {
        state.loading = false;
    }
}

function has(productId) {
    return state.ids.includes(productId);
}

/**
 * @returns {Promise<'added'|'removed'|'auth-required'>}
 */
async function toggle(productId) {
    if (!isLoggedIn()) {
        return 'auth-required';
    }

    if (has(productId)) {
        await api.delete(`/wishlist/${productId}`);
        state.ids = state.ids.filter((id) => id !== productId);
        state.items = state.items.filter((item) => item.product?.id !== productId);

        return 'removed';
    }

    await api.post('/wishlist', { product_id: productId });
    await refresh();

    return 'added';
}

async function remove(productId) {
    await api.delete(`/wishlist/${productId}`);
    state.ids = state.ids.filter((id) => id !== productId);
    state.items = state.items.filter((item) => item.product?.id !== productId);
}

function reset() {
    state.ids = [];
    state.items = [];
    state.loading = false;
}

export function useWishlist() {
    return { state, refresh, has, toggle, remove, reset };
}
