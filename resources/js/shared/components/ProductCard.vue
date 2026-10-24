<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useCart } from '@/shared/composables/useCart';
import { useToast } from '@/shared/composables/useToast';
import { useWishlist } from '@/shared/composables/useWishlist';
import { errorMessage, formatMoney } from '@/shared/utils/format';

const props = defineProps({
    product: { type: Object, required: true },
});

const router = useRouter();
const cart = useCart();
const wishlist = useWishlist();
const toast = useToast();

const busy = ref(false);
const wished = computed(() => wishlist.has(props.product.id));
const outOfStock = computed(() => Number(props.product.stock) < 1);

async function addToCart() {
    if (outOfStock.value) return;

    busy.value = true;

    try {
        await cart.add(props.product.id, 1);
        toast.success(`${props.product.name} added to your cart.`);
    } catch (error) {
        toast.error(errorMessage(error, 'Could not add this product to your cart.'));
    } finally {
        busy.value = false;
    }
}

async function toggleWishlist() {
    try {
        const result = await wishlist.toggle(props.product.id);

        if (result === 'auth-required') {
            toast.info('Please login to save products to your wishlist.');
            router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } });

            return;
        }

        toast.success(result === 'added' ? 'Added to your wishlist.' : 'Removed from your wishlist.');
    } catch (error) {
        toast.error(errorMessage(error, 'Could not update your wishlist.'));
    }
}
</script>

<template>
    <article class="product-card">
        <div class="product-card__media">
            <router-link :to="{ name: 'product.show', params: { slug: product.slug } }">
                <img :src="product.thumbnail" :alt="product.name" loading="lazy">
            </router-link>

            <div class="product-card__badges">
                <span v-if="product.discount_percent > 0" class="badge-sale">-{{ product.discount_percent }}%</span>
                <span v-else-if="product.is_featured" class="badge-featured">Featured</span>
            </div>

            <button
                type="button"
                class="wish-btn"
                :class="{ 'is-active': wished }"
                :aria-label="wished ? 'Remove from wishlist' : 'Add to wishlist'"
                @click.prevent="toggleWishlist"
            >
                <i class="bi" :class="wished ? 'bi-heart-fill' : 'bi-heart'"></i>
            </button>

            <span v-if="outOfStock" class="product-card__soldout">Out of stock</span>
        </div>

        <div class="product-card__body">
            <span class="product-card__brand">{{ product.brand?.name ?? 'AmarShop' }}</span>

            <router-link
                :to="{ name: 'product.show', params: { slug: product.slug } }"
                class="product-card__name"
            >
                {{ product.name }}
            </router-link>

            <div class="d-flex align-items-baseline gap-2 mt-auto pt-2">
                <span class="product-card__price">{{ formatMoney(product.effective_price) }}</span>
                <span v-if="product.is_on_sale" class="product-card__price-old">{{ formatMoney(product.price) }}</span>
            </div>

            <button
                class="btn btn-soft w-100 mt-2"
                type="button"
                :disabled="busy || outOfStock"
                @click="addToCart"
            >
                <span v-if="busy" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-bag-plus me-2"></i>
                {{ outOfStock ? 'Unavailable' : 'Add to cart' }}
            </button>
        </div>
    </article>
</template>
