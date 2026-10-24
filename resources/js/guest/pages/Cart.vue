<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import Breadcrumbs from '@/shared/components/Breadcrumbs.vue';
import EmptyState from '@/shared/components/EmptyState.vue';
import QuantityInput from '@/shared/components/QuantityInput.vue';
import { useAuth } from '@/shared/composables/useAuth';
import { useCart } from '@/shared/composables/useCart';
import { useConfirm } from '@/shared/composables/useConfirm';
import { useToast } from '@/shared/composables/useToast';
import { errorMessage, formatMoney } from '@/shared/utils/format';

const router = useRouter();
const auth = useAuth();
const cart = useCart();
const confirm = useConfirm();
const toast = useToast();

const pendingId = ref(null);

const remainingForFreeShipping = computed(() =>
    Math.max(0, cart.state.freeShippingThreshold - cart.state.subtotal),
);

const shippingProgress = computed(() => {
    if (!cart.state.subtotal) return 0;

    return Math.min(100, Math.round((cart.state.subtotal / cart.state.freeShippingThreshold) * 100));
});

async function changeQuantity(item, quantity) {
    pendingId.value = item.product_id;

    try {
        await cart.updateQuantity(item.product_id, quantity);
    } catch (exception) {
        toast.error(errorMessage(exception, 'Could not update the quantity.'));
    } finally {
        pendingId.value = null;
    }
}

async function removeItem(item) {
    const confirmed = await confirm.ask({
        title: 'Remove item?',
        message: `Remove “${item.name}” from your cart?`,
        confirmText: 'Remove',
    });

    if (!confirmed) return;

    pendingId.value = item.product_id;

    try {
        await cart.remove(item.product_id);
        toast.success('Item removed from your cart.');
    } catch (exception) {
        toast.error(errorMessage(exception, 'Could not remove this item.'));
    } finally {
        pendingId.value = null;
    }
}

function goToCheckout() {
    if (!auth.isAuthenticated.value) {
        toast.info('Please login with OTP to continue to checkout.');
        router.push({ name: 'login', query: { redirect: '/checkout' } });

        return;
    }

    router.push({ name: 'checkout' });
}
</script>

<template>
    <div class="container py-4">
        <Breadcrumbs :items="[{ label: 'Cart' }]" />

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mt-3 mb-4">
            <div>
                <h1 class="se-section-title mb-1">Your cart</h1>
                <p class="se-muted mb-0">{{ cart.state.itemCount }} item(s) ready to go</p>
            </div>
            <router-link :to="{ name: 'products' }" class="btn btn-outline-brand btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Continue shopping
            </router-link>
        </div>

        <EmptyState
            v-if="cart.state.ready && !cart.state.items.length"
            icon="bi-bag"
            title="Your cart is empty"
            message="Add a few products and they will show up here — even before you login."
        >
            <router-link :to="{ name: 'products' }" class="btn btn-brand">Browse products</router-link>
        </EmptyState>

        <div v-else class="row g-4">
            <div class="col-lg-8">
                <div v-if="cart.state.loading && !cart.state.items.length" class="se-card se-card-pad">
                    <div class="skeleton mb-3" style="height: 64px;"></div>
                    <div class="skeleton" style="height: 64px;"></div>
                </div>

                <div v-else class="d-flex flex-column gap-3">
                    <article v-for="item in cart.state.items" :key="item.id" class="se-card cart-item">
                        <router-link :to="{ name: 'product.show', params: { slug: item.slug } }" class="cart-item__media">
                            <img :src="item.thumbnail" :alt="item.name">
                        </router-link>

                        <div class="cart-item__body">
                            <div class="d-flex justify-content-between gap-3">
                                <div>
                                    <span class="product-card__brand">{{ item.brand ?? 'AmarShop' }}</span>
                                    <router-link
                                        :to="{ name: 'product.show', params: { slug: item.slug } }"
                                        class="d-block fw-semibold text-dark"
                                    >
                                        {{ item.name }}
                                    </router-link>
                                    <small class="se-muted">
                                        {{ formatMoney(item.effective_price) }} each
                                        <span v-if="item.stock < 5" class="text-danger ms-1">· only {{ item.stock }} left</span>
                                    </small>
                                </div>

                                <button
                                    class="btn btn-sm btn-light text-danger align-self-start"
                                    type="button"
                                    :disabled="pendingId === item.product_id"
                                    aria-label="Remove item"
                                    @click="removeItem(item)"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>

                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                                <QuantityInput
                                    :model-value="item.quantity"
                                    :max="item.max_quantity"
                                    :disabled="pendingId === item.product_id"
                                    @update:model-value="changeQuantity(item, $event)"
                                />
                                <strong class="price-tag">{{ formatMoney(item.line_total) }}</strong>
                            </div>
                        </div>
                    </article>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="se-card se-card-pad cart-summary">
                    <h2 class="h6 mb-3">Order summary</h2>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="se-muted">Subtotal</span>
                        <strong>{{ formatMoney(cart.state.subtotal) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="se-muted">Delivery</span>
                        <strong>{{ cart.state.shipping ? formatMoney(cart.state.shipping) : 'Free' }}</strong>
                    </div>

                    <div v-if="remainingForFreeShipping > 0" class="free-ship mt-3">
                        <small>Add {{ formatMoney(remainingForFreeShipping) }} more for free delivery</small>
                        <div class="progress">
                            <div class="progress-bar" :style="{ width: `${shippingProgress}%` }"></div>
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-semibold">Total</span>
                        <span class="h4 mb-0 price-tag">{{ formatMoney(cart.state.total) }}</span>
                    </div>

                    <button class="btn btn-brand w-100" type="button" :disabled="!cart.state.items.length" @click="goToCheckout">
                        Proceed to checkout <i class="bi bi-arrow-right ms-1"></i>
                    </button>

                    <p v-if="!auth.isAuthenticated.value" class="text-center small se-muted mt-3 mb-0">
                        You can fill the cart as a guest. Login with OTP is required at checkout.
                    </p>
                    <p v-else class="text-center small se-muted mt-3 mb-0">
                        <i class="bi bi-shield-check me-1"></i> Secure demo payment with SSLCommerz
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
