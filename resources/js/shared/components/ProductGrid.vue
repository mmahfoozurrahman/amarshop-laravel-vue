<script setup>
import EmptyState from '@/shared/components/EmptyState.vue';
import ProductCard from '@/shared/components/ProductCard.vue';

defineProps({
    products: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    skeletonCount: { type: Number, default: 8 },
    emptyTitle: { type: String, default: 'No products found' },
    emptyMessage: { type: String, default: 'Try a different search or filter combination.' },
});
</script>

<template>
    <div>
        <div v-if="loading" class="row g-3 g-md-4">
            <div v-for="n in skeletonCount" :key="`skeleton-${n}`" class="col-6 col-md-4 col-xl-3">
                <div class="se-card overflow-hidden">
                    <div class="skeleton" style="aspect-ratio: 4 / 3; border-radius: 0;"></div>
                    <div class="p-3">
                        <div class="skeleton mb-2" style="height: 12px; width: 40%;"></div>
                        <div class="skeleton mb-3" style="height: 16px;"></div>
                        <div class="skeleton" style="height: 34px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="products.length" class="row g-3 g-md-4">
            <div v-for="product in products" :key="product.id" class="col-6 col-md-4 col-xl-3">
                <ProductCard :product="product" />
            </div>
        </div>

        <EmptyState v-else icon="bi-search" :title="emptyTitle" :message="emptyMessage">
            <slot name="empty-action"></slot>
        </EmptyState>
    </div>
</template>
