<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Breadcrumbs from '@/shared/components/Breadcrumbs.vue';
import Pagination from '@/shared/components/Pagination.vue';
import ProductGrid from '@/shared/components/ProductGrid.vue';
import { fetchBrands, fetchCategories, fetchProducts } from '@/shared/services/catalog';
import { errorMessage } from '@/shared/utils/format';

const route = useRoute();
const router = useRouter();

const filters = reactive({
    q: '',
    category: '',
    brand: '',
    featured: false,
    sort: 'newest',
    min_price: '',
    max_price: '',
});

const products = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const categories = ref([]);
const brands = ref([]);
const loading = ref(true);
const error = ref('');
const showFilters = ref(false);

function syncFromQuery() {
    const query = route.query;

    filters.q = query.q ?? '';
    filters.category = query.category ?? '';
    filters.brand = query.brand ?? '';
    filters.featured = query.featured === '1' || query.featured === 1 || query.featured === true;
    filters.sort = query.sort ?? 'newest';
    filters.min_price = query.min_price ?? '';
    filters.max_price = query.max_price ?? '';
}

async function load(page = 1) {
    loading.value = true;
    error.value = '';

    try {
        const response = await fetchProducts({
            ...route.query,
            page,
            per_page: 12,
            featured: filters.featured ? 1 : undefined,
        });

        products.value = response.data;
        meta.value = response.meta;
    } catch (exception) {
        error.value = errorMessage(exception, 'Could not load products.');
        products.value = [];
    } finally {
        loading.value = false;
    }
}

function applyFilters(page = 1) {
    const query = {};

    if (filters.q.trim()) query.q = filters.q.trim();
    if (filters.category) query.category = filters.category;
    if (filters.brand) query.brand = filters.brand;
    if (filters.featured) query.featured = 1;
    if (filters.sort && filters.sort !== 'newest') query.sort = filters.sort;
    if (filters.min_price !== '') query.min_price = filters.min_price;
    if (filters.max_price !== '') query.max_price = filters.max_price;
    if (page > 1) query.page = page;

    router.push({ name: 'products', query });
    showFilters.value = false;
}

function resetFilters() {
    Object.assign(filters, {
        q: '',
        category: '',
        brand: '',
        featured: false,
        sort: 'newest',
        min_price: '',
        max_price: '',
    });
    router.push({ name: 'products' });
}

watch(() => route.query, () => {
    syncFromQuery();
    load(Number(route.query.page ?? 1));
});

onMounted(async () => {
    syncFromQuery();
    load(Number(route.query.page ?? 1));

    try {
        const [categoryList, brandList] = await Promise.all([fetchCategories(), fetchBrands()]);
        categories.value = categoryList;
        brands.value = brandList;
    } catch {
        // The product grid already surfaces load errors.
    }
});
</script>

<template>
    <div class="container py-4">
        <Breadcrumbs :items="[{ label: 'Shop' }]" />

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mt-3 mb-4">
            <div>
                <h1 class="se-section-title mb-1">Shop all products</h1>
                <p class="se-muted mb-0">
                    {{ meta.total ?? 0 }} products available
                    <span v-if="filters.q">for “{{ filters.q }}”</span>
                </p>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-outline-brand d-lg-none" type="button" @click="showFilters = !showFilters">
                    <i class="bi bi-funnel me-1"></i> Filters
                </button>

                <select v-model="filters.sort" class="form-select" style="min-width: 175px" @change="applyFilters()">
                    <option value="newest">Newest first</option>
                    <option value="price_asc">Price: low to high</option>
                    <option value="price_desc">Price: high to low</option>
                    <option value="name">Name: A–Z</option>
                </select>
            </div>
        </div>

        <div v-if="error" class="alert alert-danger">{{ error }}</div>

        <div class="row g-4">
            <aside class="col-lg-3" :class="{ 'd-none d-lg-block': !showFilters }">
                <div class="se-card se-card-pad">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 mb-0">Filters</h2>
                        <button class="btn btn-link btn-sm p-0" type="button" @click="resetFilters">Reset</button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="filter-q">Search</label>
                        <input id="filter-q" v-model="filters.q" type="search" class="form-control" placeholder="Product name or SKU" @keyup.enter="applyFilters()">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="filter-category">Category</label>
                        <select id="filter-category" v-model="filters.category" class="form-select" @change="applyFilters()">
                            <option value="">All categories</option>
                            <option v-for="category in categories" :key="category.id" :value="category.slug">{{ category.name }}</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="filter-brand">Brand</label>
                        <select id="filter-brand" v-model="filters.brand" class="form-select" @change="applyFilters()">
                            <option value="">All brands</option>
                            <option v-for="brand in brands" :key="brand.id" :value="brand.slug">{{ brand.name }}</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <span class="form-label small fw-semibold d-block">Price range (৳)</span>
                        <div class="d-flex gap-2">
                            <input v-model="filters.min_price" type="number" min="0" class="form-control" placeholder="Min">
                            <input v-model="filters.max_price" type="number" min="0" class="form-control" placeholder="Max">
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input id="filter-featured" v-model="filters.featured" class="form-check-input" type="checkbox" @change="applyFilters()">
                        <label class="form-check-label small" for="filter-featured">Featured only</label>
                    </div>

                    <button class="btn btn-brand w-100" type="button" @click="applyFilters()">Apply filters</button>
                </div>
            </aside>

            <div class="col-lg-9">
                <ProductGrid
                    :products="products"
                    :loading="loading"
                    :skeleton-count="9"
                    empty-title="No products matched"
                    empty-message="Try clearing a filter or searching for something else."
                >
                    <template #empty-action>
                        <button class="btn btn-outline-brand" type="button" @click="resetFilters">Clear filters</button>
                    </template>
                </ProductGrid>

                <Pagination :meta="meta" @change="applyFilters" />
            </div>
        </div>
    </div>
</template>
