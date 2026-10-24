import { reactive } from 'vue';
import api from '@/shared/services/api';

const state = reactive({
    items: [],
    itemCount: 0,
    subtotal: 0,
    shipping: 0,
    total: 0,
    freeShippingThreshold: 1000,
    loading: false,
    ready: false,
});

const isLoggedIn = () => Boolean(localStorage.getItem('access_token'));
const endpoint = () => (isLoggedIn() ? '/cart' : '/guest/cart');

function apply(payload = {}) {
    state.items = payload.items ?? [];
    state.itemCount = payload.item_count ?? 0;
    state.subtotal = payload.subtotal ?? 0;
    state.shipping = payload.shipping ?? 0;
    state.total = payload.total ?? 0;
    state.freeShippingThreshold = payload.free_shipping_threshold ?? 1000;
    state.ready = true;
}

async function refresh() {
    state.loading = true;

    try {
        const { data } = await api.get(endpoint());
        apply(data.data);
    } catch {
        apply();
    } finally {
        state.loading = false;
    }

    return state;
}

async function add(productId, quantity = 1) {
    const { data } = await api.post(endpoint(), { product_id: productId, quantity });
    apply(data.data);

    return data;
}

async function updateQuantity(productId, quantity) {
    const { data } = await api.patch(`${endpoint()}/${productId}`, { quantity });
    apply(data.data);

    return data;
}

async function remove(productId) {
    const { data } = await api.delete(`${endpoint()}/${productId}`);
    apply(data.data);

    return data;
}

async function mergeGuestCart() {
    if (!isLoggedIn()) return;

    const guestToken = localStorage.getItem('guest_token');

    if (!guestToken) return;

    try {
        const { data } = await api.post('/cart/merge', { guest_token: guestToken });
        apply(data.data);
    } catch {
        await refresh();
    }
}

function reset() {
    apply();
    state.ready = false;
}

export function useCart() {
    return { state, refresh, add, updateQuantity, remove, mergeGuestCart, reset };
}
