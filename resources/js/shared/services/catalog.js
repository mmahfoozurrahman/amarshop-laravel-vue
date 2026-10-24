import api from './api';

export async function fetchHome() {
    const { data } = await api.get('/home');

    return data.data;
}

export async function fetchProducts(params = {}) {
    const { data } = await api.get('/products', { params });

    return data;
}

export async function fetchProduct(slug) {
    const { data } = await api.get(`/products/${slug}`);

    return data.data;
}

export async function fetchBrands() {
    const { data } = await api.get('/brands');

    return data.data;
}

export async function fetchBrand(slug, params = {}) {
    const { data } = await api.get(`/brands/${slug}`, { params });

    return data;
}

export async function fetchCategories() {
    const { data } = await api.get('/categories');

    return data.data;
}

export async function fetchCategory(slug, params = {}) {
    const { data } = await api.get(`/categories/${slug}`, { params });

    return data;
}
