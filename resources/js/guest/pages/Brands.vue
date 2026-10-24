<script setup>
import { onMounted, ref } from 'vue';
import Breadcrumbs from '@/shared/components/Breadcrumbs.vue';
import BrandCard from '@/shared/components/BrandCard.vue';
import EmptyState from '@/shared/components/EmptyState.vue';
import { fetchBrands } from '@/shared/services/catalog';
import { errorMessage } from '@/shared/utils/format';

const brands = ref([]);
const loading = ref(true);
const error = ref('');

onMounted(async () => {
    try {
        brands.value = await fetchBrands();
    } catch (exception) {
        error.value = errorMessage(exception, 'Could not load brands.');
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="container py-4">
        <Breadcrumbs :items="[{ label: 'Brands' }]" />

        <div class="mt-3 mb-4">
            <h1 class="se-section-title mb-1">All brands</h1>
            <p class="se-muted mb-0">Browse the catalogue by the brands behind it.</p>
        </div>

        <div v-if="error" class="alert alert-danger">{{ error }}</div>

        <div v-if="loading" class="row g-3">
            <div v-for="n in 8" :key="`brand-skeleton-${n}`" class="col-6 col-md-4">
                <div class="skeleton" style="height: 70px; border-radius: 14px;"></div>
            </div>
        </div>

        <div v-else-if="brands.length" class="row g-3">
            <div v-for="brand in brands" :key="brand.id" class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <BrandCard :brand="brand" />
            </div>
        </div>

        <EmptyState v-else icon="bi-award" title="No brands yet" message="Brands will appear here once they are added." />
    </div>
</template>
