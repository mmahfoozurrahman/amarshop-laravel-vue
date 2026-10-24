<script setup>
import { onMounted, ref } from 'vue';
import Breadcrumbs from '@/shared/components/Breadcrumbs.vue';
import CategoryCard from '@/shared/components/CategoryCard.vue';
import EmptyState from '@/shared/components/EmptyState.vue';
import { fetchCategories } from '@/shared/services/catalog';
import { errorMessage } from '@/shared/utils/format';

const categories = ref([]);
const loading = ref(true);
const error = ref('');

onMounted(async () => {
    try {
        categories.value = await fetchCategories();
    } catch (exception) {
        error.value = errorMessage(exception, 'Could not load categories.');
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="container py-4">
        <Breadcrumbs :items="[{ label: 'Categories' }]" />

        <div class="mt-3 mb-4">
            <h1 class="se-section-title mb-1">Shop by category</h1>
            <p class="se-muted mb-0">Find what you need without scrolling through everything.</p>
        </div>

        <div v-if="error" class="alert alert-danger">{{ error }}</div>

        <div v-if="loading" class="row g-3">
            <div v-for="n in 6" :key="`cat-skeleton-${n}`" class="col-6 col-md-4 col-lg-3">
                <div class="skeleton" style="aspect-ratio: 4 / 3; border-radius: 14px;"></div>
            </div>
        </div>

        <div v-else-if="categories.length" class="row g-3">
            <div v-for="category in categories" :key="category.id" class="col-6 col-md-4 col-lg-3">
                <CategoryCard :category="category" />
            </div>
        </div>

        <EmptyState v-else icon="bi-grid" title="No categories yet" message="Categories will appear here once they are added." />
    </div>
</template>
