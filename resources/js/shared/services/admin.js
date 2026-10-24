import api from './api';

export async function fetchDashboard() {
    const { data } = await api.get('/admin/dashboard');

    return data.data;
}

export async function fetchResource(resource, params = {}) {
    const { data } = await api.get(`/admin/${resource}`, { params });

    return data;
}

export async function fetchRecord(resource, id) {
    const { data } = await api.get(`/admin/${resource}/${id}`);

    return data.data;
}

export async function createRecord(resource, payload) {
    const { data } = await api.post(`/admin/${resource}`, payload);

    return data;
}

export async function updateRecord(resource, id, payload) {
    const { data } = await api.put(`/admin/${resource}/${id}`, payload);

    return data;
}

export async function deleteRecord(resource, id) {
    const { data } = await api.delete(`/admin/${resource}/${id}`);

    return data;
}

/**
 * Multipart create/update (used when a file upload is involved).
 */
export async function saveRecord(resource, id, formData) {
    if (id) {
        formData.append('_method', 'PUT');
    }

    const { data } = await api.post(`/admin/${resource}${id ? `/${id}` : ''}`, formData);

    return data;
}

export async function fetchAdminOrders(params = {}) {
    const { data } = await api.get('/admin/orders', { params });

    return data;
}

export async function fetchAdminOrder(id) {
    const { data } = await api.get(`/admin/orders/${id}`);

    return data.data;
}

export async function updateOrderStatus(id, status) {
    const { data } = await api.patch(`/admin/orders/${id}/status`, { status });

    return data;
}

export async function fetchCustomers(params = {}) {
    const { data } = await api.get('/admin/users', { params });

    return data;
}

export async function updateCustomerStatus(id, isActive) {
    const { data } = await api.patch(`/admin/users/${id}/status`, { is_active: isActive });

    return data;
}
