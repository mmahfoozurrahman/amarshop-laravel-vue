<script setup>
import { computed, onMounted } from 'vue';
import Breadcrumbs from '@/shared/components/Breadcrumbs.vue';
import EmptyState from '@/shared/components/EmptyState.vue';
import ProductCard from '@/shared/components/ProductCard.vue';
import { useConfirm } from '@/shared/composables/useConfirm';
import { useToast } from '@/shared/composables/useToast';
import { useWishlist } from '@/shared/composables/useWishlist';
import { errorMessage } from '@/shared/utils/format';

const wishlist = useWishlist();
const confirm = useConfirm();
const toast = useToast();

const products = computed(() =>
    wishlist.state.items.map((item) => item.product).filter(Boolean),
);

onMounted(() => wishlist.refresh());

async function remove(product) {
    const confirmed = await confirm.ask({
        title: 'Remove from wishlist?',
        message: `Remove “${product.name}” from your saved products?`,
        confirmText: 'Remove',
    });

    if (!confirmed) return;

    try {
        await wishlist.remove(product.id);
        toast.success('Removed from your wishlist.');
    } catch (exception) {
        toast.error(errorMessage(exception, 'Could not update your wishlist.'));
    }
}
</script>

<template>
    <div class="container py-4">
        <Breadcrumbs :items="[{ label: 'Wishlist' }]" />

        <div class="mt-3 mb-4">
            <h1 class="se-section-title mb-1">Your wishlist</h1>
            <p class="se-muted mb-0">{{ products.length }} saved product(s)</p>
        </div>

        <div v-if="wishlist.state.loading" class="row g-3 g-md-4">
            <div v-for="n in 4" :key="`wish-skeleton-${n}`" class="col-6 col-md-4 col-xl-3">
                <div class="se-card overflow-hidden">
                    <div class="skeleton" style="aspect-ratio: 4 / 3; border-radius: 0;"></div>
                    <div class="p-3"><div class="skeleton" style="height: 60px;"></div></div>
                </div>
            </div>
        </div>

        <div v-else-if="products.length" class="row g-3 g-md-4">
            <div v-for="product in products" :key="product.id" class="col-6 col-md-4 col-xl-3">
                <div class="position-relative">
                    <ProductCard :product="product" />
                    <button class="wish-remove" type="button" @click="remove(product)">
                        <i class="bi bi-x-lg me-1"></i> Remove
                    </button>
                </div>
            </div>
        </div>

        <EmptyState
            v-else
            icon="bi-heart"
            title="Your wishlist is empty"
            message="Tap the heart on any product to save it here for later."
        >
            <router-link :to="{ name: 'products' }" class="btn btn-brand">Find something you love</router-link>
        </EmptyState>
    </div>
</template>
