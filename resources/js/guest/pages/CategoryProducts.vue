<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import Breadcrumbs from '@/shared/components/Breadcrumbs.vue';
import Pagination from '@/shared/components/Pagination.vue';
import ProductGrid from '@/shared/components/ProductGrid.vue';
import { fetchCategory } from '@/shared/services/catalog';
import { errorMessage } from '@/shared/utils/format';

const route = useRoute();

const category = ref(null);
const products = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(true);
const error = ref('');

async function load(page = 1) {
    loading.value = true;
    error.value = '';

    try {
        const response = await fetchCategory(route.params.slug, { page, per_page: 12 });
        category.value = response.category;
        products.value = response.data;
        meta.value = response.meta;
    } catch (exception) {
        error.value = errorMessage(exception, 'This category could not be found.');
        category.value = null;
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
        <Breadcrumbs :items="[{ label: 'Categories', to: { name: 'categories' } }, { label: category?.name ?? 'Category' }]" />

        <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>

        <div v-if="category" class="category-hero mt-3 mb-4">
            <img :src="category.image" :alt="category.name">
            <div class="category-hero__body">
                <h1 class="h3 mb-1">{{ category.name }}</h1>
                <p class="mb-0">{{ category.description }}</p>
                <span class="pill pill-warning mt-2 d-inline-block">{{ meta.total }} products</span>
            </div>
        </div>

        <ProductGrid :products="products" :loading="loading" empty-title="No products in this category" />

        <Pagination :meta="meta" @change="load" />
    </div>
</template>
