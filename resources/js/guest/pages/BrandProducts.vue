<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import Breadcrumbs from '@/shared/components/Breadcrumbs.vue';
import Pagination from '@/shared/components/Pagination.vue';
import ProductGrid from '@/shared/components/ProductGrid.vue';
import { fetchBrand } from '@/shared/services/catalog';
import { errorMessage } from '@/shared/utils/format';

const route = useRoute();

const brand = ref(null);
const products = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(true);
const error = ref('');

async function load(page = 1) {
    loading.value = true;
    error.value = '';

    try {
        const response = await fetchBrand(route.params.slug, { page, per_page: 12 });
        brand.value = response.brand;
        products.value = response.data;
        meta.value = response.meta;
    } catch (exception) {
        error.value = errorMessage(exception, 'This brand could not be found.');
        brand.value = null;
        products.value = [];
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());
watch(() => route.params.slug, () => load());
</script>

<template>
    <div class="container py-4">
        <Breadcrumbs :items="[{ label: 'Brands', to: { name: 'brands' } }, { label: brand?.name ?? 'Brand' }]" />

        <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>

        <div v-if="brand" class="se-card se-card-pad mt-3 mb-4 d-flex flex-wrap align-items-center gap-3">
            <img :src="brand.logo" :alt="brand.name" class="brand-hero-logo">
            <div class="flex-grow-1">
                <h1 class="h3 mb-1">{{ brand.name }}</h1>
                <p class="se-muted mb-0">{{ brand.description }}</p>
            </div>
            <span class="pill pill-muted">{{ meta.total }} products</span>
        </div>

        <ProductGrid :products="products" :loading="loading" empty-title="No products from this brand" />

        <Pagination :meta="meta" @change="load" />
    </div>
</template>
