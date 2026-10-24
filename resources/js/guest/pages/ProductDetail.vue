<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Breadcrumbs from '@/shared/components/Breadcrumbs.vue';
import ProductGrid from '@/shared/components/ProductGrid.vue';
import QuantityInput from '@/shared/components/QuantityInput.vue';
import SectionHeading from '@/shared/components/SectionHeading.vue';
import { useCart } from '@/shared/composables/useCart';
import { useToast } from '@/shared/composables/useToast';
import { useWishlist } from '@/shared/composables/useWishlist';
import { fetchProduct } from '@/shared/services/catalog';
import { errorMessage, formatMoney } from '@/shared/utils/format';

const route = useRoute();
const router = useRouter();
const cart = useCart();
const wishlist = useWishlist();
const toast = useToast();

const product = ref(null);
const related = ref([]);
const loading = ref(true);
const error = ref('');
const quantity = ref(1);
const activeTab = ref('description');
const adding = ref(false);

const wished = computed(() => (product.value ? wishlist.has(product.value.id) : false));
const outOfStock = computed(() => Number(product.value?.stock ?? 0) < 1);

async function load() {
    loading.value = true;
    error.value = '';

    try {
        const payload = await fetchProduct(route.params.slug);
        product.value = payload.product;
        related.value = payload.related ?? [];
        quantity.value = 1;
        activeTab.value = 'description';
    } catch (exception) {
        error.value = errorMessage(exception, 'This product could not be found.');
        product.value = null;
        related.value = [];
    } finally {
        loading.value = false;
    }
}

onMounted(load);
watch(() => route.params.slug, load);

async function addToCart() {
    if (!product.value || outOfStock.value) return;

    adding.value = true;

    try {
        await cart.add(product.value.id, quantity.value);
        toast.success(`${quantity.value} × ${product.value.name} added to your cart.`);
    } catch (exception) {
        toast.error(errorMessage(exception, 'Could not add this product to your cart.'));
    } finally {
        adding.value = false;
    }
}

async function toggleWishlist() {
    if (!product.value) return;

    try {
        const result = await wishlist.toggle(product.value.id);

        if (result === 'auth-required') {
            toast.info('Please login to use your wishlist.');
            router.push({ name: 'login', query: { redirect: route.fullPath } });

            return;
        }

        toast.success(result === 'added' ? 'Saved to your wishlist.' : 'Removed from your wishlist.');
    } catch (exception) {
        toast.error(errorMessage(exception, 'Could not update your wishlist.'));
    }
}
</script>

<template>
    <div class="container py-4">
        <Breadcrumbs
            :items="[
                { label: 'Shop', to: { name: 'products' } },
                { label: product?.category?.name ?? 'Product' },
                { label: product?.name ?? 'Details' },
            ]"
        />

        <div v-if="error" class="alert alert-danger mt-3 d-flex justify-content-between align-items-center">
            <span>{{ error }}</span>
            <router-link :to="{ name: 'products' }" class="btn btn-sm btn-light">Back to shop</router-link>
        </div>

        <div v-if="loading" class="row g-4 mt-2">
            <div class="col-lg-5"><div class="skeleton" style="aspect-ratio: 1; border-radius: 16px;"></div></div>
            <div class="col-lg-7">
                <div class="skeleton mb-3" style="height: 22px; width: 35%;"></div>
                <div class="skeleton mb-3" style="height: 34px;"></div>
                <div class="skeleton mb-3" style="height: 26px; width: 45%;"></div>
                <div class="skeleton mb-3" style="height: 70px;"></div>
                <div class="skeleton" style="height: 46px; width: 60%;"></div>
            </div>
        </div>

        <div v-else-if="product" class="row g-4 mt-2">
            <div class="col-lg-5">
                <div class="se-card overflow-hidden position-relative">
                    <img :src="product.thumbnail" :alt="product.name" class="w-100" style="aspect-ratio: 1; object-fit: cover;">
                    <span v-if="product.discount_percent" class="badge-sale position-absolute top-0 start-0 m-3">
                        Save {{ product.discount_percent }}%
                    </span>
                </div>
            </div>

            <div class="col-lg-7">
                <router-link
                    v-if="product.brand"
                    :to="{ name: 'brand.show', params: { slug: product.brand.slug } }"
                    class="pill pill-muted d-inline-block mb-2"
                >
                    {{ product.brand.name }}
                </router-link>

                <h1 class="h2 mb-2">{{ product.name }}</h1>
                <p class="se-muted">{{ product.short_description }}</p>

                <div class="d-flex flex-wrap align-items-baseline gap-2 my-3">
                    <span class="display-6 price-tag">{{ formatMoney(product.effective_price) }}</span>
                    <span v-if="product.is_on_sale" class="text-muted text-decoration-line-through">
                        {{ formatMoney(product.price) }}
                    </span>
                    <span v-if="outOfStock" class="pill pill-danger ms-2">Out of stock</span>
                    <span v-else class="pill pill-success ms-2">{{ product.stock }} in stock</span>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-3 my-4">
                    <QuantityInput
                        v-model="quantity"
                        :max="Math.min(product.stock || 1, 20)"
                        :disabled="outOfStock"
                    />

                    <button class="btn btn-brand px-4" type="button" :disabled="adding || outOfStock" @click="addToCart">
                        <span v-if="adding" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="bi bi-bag-plus me-2"></i>
                        Add to cart
                    </button>

                    <button class="btn btn-outline-brand" type="button" @click="toggleWishlist">
                        <i class="bi me-1" :class="wished ? 'bi-heart-fill' : 'bi-heart'"></i>
                        {{ wished ? 'Saved' : 'Wishlist' }}
                    </button>
                </div>

                <ul class="list-unstyled small se-muted d-grid gap-2 mb-0">
                    <li><i class="bi bi-upc-scan me-2"></i>SKU: {{ product.sku }}</li>
                    <li>
                        <i class="bi bi-grid me-2"></i>Category:
                        <router-link v-if="product.category" :to="{ name: 'category.show', params: { slug: product.category.slug } }">
                            {{ product.category.name }}
                        </router-link>
                    </li>
                    <li><i class="bi bi-box-seam me-2"></i>Ships in 1–3 business days</li>
                </ul>
            </div>
        </div>

        <section v-if="product" class="se-section">
            <div class="se-card">
                <div class="product-tabs">
                    <button type="button" :class="{ 'is-active': activeTab === 'description' }" @click="activeTab = 'description'">
                        Description
                    </button>
                    <button type="button" :class="{ 'is-active': activeTab === 'shipping' }" @click="activeTab = 'shipping'">
                        Shipping &amp; returns
                    </button>
                    <button type="button" :class="{ 'is-active': activeTab === 'details' }" @click="activeTab = 'details'">
                        Details
                    </button>
                </div>

                <div class="p-3 p-md-4">
                    <p v-if="activeTab === 'description'" class="mb-0" style="white-space: pre-line;">{{ product.description }}</p>

                    <div v-else-if="activeTab === 'shipping'" class="se-muted">
                        <p>Orders placed before 4pm are dispatched the same working day. Delivery takes 1–2 days inside
                            Dhaka and 2–4 days elsewhere in Bangladesh.</p>
                        <p class="mb-0">Changed your mind? Return the item unused within 7 days for a full refund.
                            Shipping is free on orders above ৳1,000.</p>
                    </div>

                    <div v-else class="row g-3 small">
                        <div class="col-md-6"><strong>Brand:</strong> {{ product.brand?.name ?? '—' }}</div>
                        <div class="col-md-6"><strong>Category:</strong> {{ product.category?.name ?? '—' }}</div>
                        <div class="col-md-6"><strong>SKU:</strong> {{ product.sku }}</div>
                        <div class="col-md-6"><strong>Stock:</strong> {{ product.stock }} units</div>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="related.length" class="se-section pt-0">
            <SectionHeading title="You may also like" subtitle="More from the same category." />
            <ProductGrid :products="related" :skeleton-count="4" />
        </section>
    </div>
</template>
