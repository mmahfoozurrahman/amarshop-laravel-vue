import api from './api';

export async function updateProfile(payload) {
    const { data } = await api.patch('/profile', payload);

    return data;
}

export async function placeOrder(payload) {
    const { data } = await api.post('/checkout', payload);

    return data;
}

export async function payOrder(orderNumber) {
    const { data } = await api.post(`/orders/${orderNumber}/pay`);

    return data;
}

export async function fetchOrders(params = {}) {
    const { data } = await api.get('/orders', { params });

    return data;
}

export async function fetchOrder(orderNumber) {
    const { data } = await api.get(`/orders/${orderNumber}`);

    return data.data;
}

export async function completeDemoPayment(orderNumber, result) {
    const { data } = await api.post(`/payment/demo/${orderNumber}/${result}`);

    return data;
}
