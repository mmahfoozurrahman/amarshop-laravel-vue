<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AdminModal from '@/admin/components/AdminModal.vue';
import Pagination from '@/shared/components/Pagination.vue';
import { useAdminCrud } from '@/shared/composables/useAdminCrud';
import { useConfirm } from '@/shared/composables/useConfirm';
import { fetchResource } from '@/shared/services/admin';
import { formatMoney } from '@/shared/utils/format';

const crud = useAdminCrud('products', {
    emptyForm: {
        name: '',
        sku: '',
        brand_id: '',
        category_id: '',
        price: '',
        sale_price: '',
        stock: 0,
        short_description: '',
        description: '',
        is_featured: false,
        is_active: true,
        thumbnail: null,
    },
    perPage: 10,
});

const {
    items, meta, loading, saving, search, modalOpen, editing, form, errors,
    load, openCreate, openEdit, closeModal, save, remove,
} = crud;

const confirm = useConfirm();
const route = useRoute();
const router = useRouter();
const brands = ref([]);
const categories = ref([]);
const filters = reactive({ status: '', category_id: '', brand_id: '' });

/**
 * The toolbar state lives in the URL query, so a reload (F5), a bookmark or a
 * shared link keeps the search, the dropdown filters and the page number —
 * the same behaviour as the storefront shop page.
 */
function syncFromQuery() {
    search.value = route.query.q ?? '';
    filters.status = route.query.status ?? '';
    filters.category_id = route.query.category_id ?? '';
    filters.brand_id = route.query.brand_id ?? '';
}

async function loadPage(page = 1) {
    await load(page, {
        status: filters.status || undefined,
        category_id: filters.category_id || undefined,
        brand_id: filters.brand_id || undefined,
    });
}

function applyFilters(page = 1) {
    const query = {};

    if (search.value.trim()) query.q = search.value.trim();
    if (filters.status) query.status = filters.status;
    if (filters.category_id) query.category_id = filters.category_id;
    if (filters.brand_id) query.brand_id = filters.brand_id;
    if (page > 1) query.page = page;

    const unchanged = router.resolve({ query }).fullPath === route.fullPath;

    if (unchanged) {
        loadPage(page); // same query pressed twice → refresh anyway

        return;
    }

    router.replace({ query });
}

watch(() => route.query, () => {
    syncFromQuery();
    loadPage(Number(route.query.page ?? 1));
});

function payload() {
    const data = new FormData();
    data.append('brand_id', form.brand_id);
    data.append('category_id', form.category_id);
    data.append('name', form.name);
    data.append('sku', form.sku);
    data.append('price', form.price);
    data.append('stock', form.stock);
    data.append('short_description', form.short_description ?? '');
    data.append('description', form.description ?? '');
    data.append('is_featured', form.is_featured ? '1' : '0');
    data.append('is_active', form.is_active ? '1' : '0');

    if (form.sale_price !== '' && form.sale_price !== null) {
        data.append('sale_price', form.sale_price);
    } else {
        data.append('sale_price', '');
    }

    if (form.thumbnail instanceof File) {
        data.append('thumbnail', form.thumbnail);
    }

    return data;
}

async function submit() {
    const wasCreate = !editing.value;
    const saved = await save(payload());

    if (!saved) return;

    if (wasCreate) {
        // A freshly created row would be hidden by an active filter: clear them.
        // `applyFilters` reloads by itself (watcher when the query changes, direct call otherwise).
        search.value = '';
        Object.assign(filters, { status: '', category_id: '', brand_id: '' });
        applyFilters(1);

        return;
    }

    await loadPage(meta.value.current_page ?? 1);
}

function onFile(event) {
    form.thumbnail = event.target.files?.[0] ?? null;
}

async function destroy(product) {
    const confirmed = await confirm.ask({
        title: 'Delete product?',
        message: `Delete “${product.name}”? Existing orders keep their snapshot.`,
        confirmText: 'Delete',
    });

    if (!confirmed) return;

    if (await remove(product)) {
        await loadPage(meta.value.current_page ?? 1);
    }
}

onMounted(async () => {
    syncFromQuery();
    await loadPage(Number(route.query.page ?? 1));

    try {
        const [brandResponse, categoryResponse] = await Promise.all([
            fetchResource('brands', { per_page: 100 }),
            fetchResource('categories', { per_page: 100 }),
        ]);

        brands.value = brandResponse.data;
        categories.value = categoryResponse.data;
    } catch {
        // dropdowns stay empty; the table still works
    }
});
</script>

<template>
    <div>
        <div class="admin-card">
            <div class="admin-card__head">
                <div class="admin-toolbar">
                    <input v-model="search" type="search" class="form-control" placeholder="Search name or SKU…" @keyup.enter="applyFilters(1)">
                    <select v-model="filters.category_id" class="form-select" @change="applyFilters(1)">
                        <option value="">All categories</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                    <select v-model="filters.brand_id" class="form-select" @change="applyFilters(1)">
                        <option value="">All brands</option>
                        <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
                    </select>
                    <select v-model="filters.status" class="form-select" @change="applyFilters(1)">
                        <option value="">Any status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <button class="btn btn-brand" type="button" @click="openCreate">
                    <i class="bi bi-plus-lg me-1"></i> New product
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-shop mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category / Brand</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Flags</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="6" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</td>
                        </tr>
                        <tr v-for="product in items" v-else :key="product.id">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img :src="product.thumbnail" :alt="product.name" class="table-thumb">
                                    <div>
                                        <router-link :to="{ name: 'product.show', params: { slug: product.slug } }" target="_blank" class="fw-semibold d-block">
                                            {{ product.name }}
                                        </router-link>
                                        <small class="se-muted">{{ product.sku }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="d-block small">{{ product.category?.name ?? '—' }}</span>
                                <small class="se-muted">{{ product.brand?.name ?? '—' }}</small>
                            </td>
                            <td>
                                <span class="fw-semibold d-block">{{ formatMoney(product.effective_price) }}</span>
                                <small v-if="product.is_on_sale" class="se-muted text-decoration-line-through">{{ formatMoney(product.price) }}</small>
                            </td>
                            <td>
                                <span class="pill" :class="product.stock > 5 ? 'pill-success' : (product.stock ? 'pill-warning' : 'pill-danger')">
                                    {{ product.stock }}
                                </span>
                            </td>
                            <td>
                                <span v-if="product.is_featured" class="pill pill-info me-1">Featured</span>
                                <span class="pill" :class="product.is_active ? 'pill-success' : 'pill-muted'">
                                    {{ product.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-light" type="button" @click="openEdit(product)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-light text-danger ms-1" type="button" @click="destroy(product)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!loading && !items.length">
                            <td colspan="6" class="text-center se-muted py-4">No products found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-3">
                <Pagination :meta="meta" @change="applyFilters" />
            </div>
        </div>

        <AdminModal v-if="modalOpen" :title="editing ? 'Edit product' : 'New product'" wide @close="closeModal">
            <form id="product-form" @submit.prevent="submit">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold" for="product-name">Name</label>
                        <input id="product-name" v-model="form.name" type="text" class="form-control" required maxlength="180">
                        <div v-if="errors.name" class="text-danger small mt-1">{{ errors.name[0] }}</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="product-sku">SKU</label>
                        <input id="product-sku" v-model="form.sku" type="text" class="form-control" required maxlength="40">
                        <div v-if="errors.sku" class="text-danger small mt-1">{{ errors.sku[0] }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="product-brand">Brand</label>
                        <select id="product-brand" v-model="form.brand_id" class="form-select" required>
                            <option value="" disabled>Select a brand</option>
                            <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
                        </select>
                        <div v-if="errors.brand_id" class="text-danger small mt-1">{{ errors.brand_id[0] }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="product-category">Category</label>
                        <select id="product-category" v-model="form.category_id" class="form-select" required>
                            <option value="" disabled>Select a category</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                        <div v-if="errors.category_id" class="text-danger small mt-1">{{ errors.category_id[0] }}</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="product-price">Price (৳)</label>
                        <input id="product-price" v-model="form.price" type="number" min="1" step="0.01" class="form-control" required>
                        <div v-if="errors.price" class="text-danger small mt-1">{{ errors.price[0] }}</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="product-sale-price">Sale price (৳)</label>
                        <input id="product-sale-price" v-model="form.sale_price" type="number" min="1" step="0.01" class="form-control" placeholder="Optional">
                        <div v-if="errors.sale_price" class="text-danger small mt-1">{{ errors.sale_price[0] }}</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="product-stock">Stock</label>
                        <input id="product-stock" v-model="form.stock" type="number" min="0" class="form-control" required>
                        <div v-if="errors.stock" class="text-danger small mt-1">{{ errors.stock[0] }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="product-thumbnail">Thumbnail</label>
                        <input id="product-thumbnail" type="file" accept="image/*" class="form-control" @change="onFile">
                        <div v-if="errors.thumbnail" class="text-danger small mt-1">{{ errors.thumbnail[0] }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="product-short">Short description</label>
                        <input id="product-short" v-model="form.short_description" type="text" class="form-control" maxlength="255">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold" for="product-description">Full description</label>
                        <textarea id="product-description" v-model="form.description" class="form-control" rows="4" maxlength="20000"></textarea>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input id="product-featured" v-model="form.is_featured" class="form-check-input" type="checkbox">
                            <label class="form-check-label" for="product-featured">Featured on the home page</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input id="product-active" v-model="form.is_active" class="form-check-input" type="checkbox">
                            <label class="form-check-label" for="product-active">Active (visible in the shop)</label>
                        </div>
                    </div>
                </div>
            </form>

            <template #footer>
                <button class="btn btn-light" type="button" @click="closeModal">Cancel</button>
                <button class="btn btn-brand" type="submit" form="product-form" :disabled="saving">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                    {{ editing ? 'Save changes' : 'Create product' }}
                </button>
            </template>
        </AdminModal>
    </div>
</template>
