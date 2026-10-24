<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import Breadcrumbs from '@/shared/components/Breadcrumbs.vue';
import EmptyState from '@/shared/components/EmptyState.vue';
import { useAuth } from '@/shared/composables/useAuth';
import { useCart } from '@/shared/composables/useCart';
import { useToast } from '@/shared/composables/useToast';
import { placeOrder } from '@/shared/services/shop';
import { errorMessage, formatMoney, validationErrors } from '@/shared/utils/format';

const router = useRouter();
const auth = useAuth();
const cart = useCart();
const toast = useToast();

const form = reactive({
    name: auth.state.user?.name ?? '',
    phone: auth.state.user?.phone ?? '',
    address: '',
    city: 'Dhaka',
    postcode: '',
    note: '',
});

const errors = reactive({});
const placing = ref(false);

const isEmpty = computed(() => cart.state.ready && cart.state.items.length === 0);

onMounted(() => cart.refresh());

function fieldError(field) {
    return errors[field]?.[0] ?? '';
}

async function submit() {
    Object.keys(errors).forEach((key) => delete errors[key]);
    placing.value = true;

    try {
        const response = await placeOrder({ ...form });
        toast.success(response.message);
        cart.reset();

        const redirect = response.redirect_url ?? '';

        // The demo gateway is a client-side route, so push it through the router.
        // An absolute URL (sandbox gateway) needs a real navigation instead.
        if (response.gateway === 'sandbox' || /^https?:\/\//i.test(redirect)) {
            window.location.assign(redirect);

            return;
        }

        router.push(redirect);
    } catch (exception) {
        Object.assign(errors, validationErrors(exception));

        if (!Object.keys(errors).length) {
            toast.error(errorMessage(exception, 'Could not place your order.'));
        }
    } finally {
        placing.value = false;
    }
}
</script>

<template>
    <div class="container py-4">
        <Breadcrumbs :items="[{ label: 'Cart', to: { name: 'cart' } }, { label: 'Checkout' }]" />

        <div class="mt-3 mb-4">
            <h1 class="se-section-title mb-1">Checkout</h1>
            <p class="se-muted mb-0">Confirm your delivery details, then complete the demo payment.</p>
        </div>

        <EmptyState
            v-if="isEmpty"
            icon="bi-bag-x"
            title="Nothing to check out"
            message="Your cart is empty, so there is no order to place yet."
        >
            <router-link :to="{ name: 'products' }" class="btn btn-brand">Browse products</router-link>
        </EmptyState>

        <form v-else class="row g-4" novalidate @submit.prevent="submit">
            <div class="col-lg-7">
                <div class="se-card se-card-pad">
                    <h2 class="h6 mb-3"><i class="bi bi-geo-alt me-2 text-brand"></i>Delivery details</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="checkout-name">Full name</label>
                            <input id="checkout-name" v-model="form.name" type="text" class="form-control" :class="{ 'is-invalid': fieldError('name') }" maxlength="120" required>
                            <div class="invalid-feedback">{{ fieldError('name') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="checkout-phone">Phone</label>
                            <input id="checkout-phone" v-model="form.phone" type="tel" class="form-control" :class="{ 'is-invalid': fieldError('phone') }" maxlength="20" required>
                            <div class="invalid-feedback">{{ fieldError('phone') }}</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold" for="checkout-address">Address</label>
                            <input id="checkout-address" v-model="form.address" type="text" class="form-control" :class="{ 'is-invalid': fieldError('address') }" placeholder="House, road, area" maxlength="255" required>
                            <div class="invalid-feedback">{{ fieldError('address') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="checkout-city">City</label>
                            <select id="checkout-city" v-model="form.city" class="form-select" :class="{ 'is-invalid': fieldError('city') }" required>
                                <option>Dhaka</option>
                                <option>Chattogram</option>
                                <option>Sylhet</option>
                                <option>Khulna</option>
                                <option>Rajshahi</option>
                                <option>Barishal</option>
                                <option>Rangpur</option>
                                <option>Mymensingh</option>
                            </select>
                            <div class="invalid-feedback">{{ fieldError('city') }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="checkout-postcode">Postcode <span class="se-muted">(optional)</span></label>
                            <input id="checkout-postcode" v-model="form.postcode" type="text" class="form-control" :class="{ 'is-invalid': fieldError('postcode') }" maxlength="20">
                            <div class="invalid-feedback">{{ fieldError('postcode') }}</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold" for="checkout-note">Order note <span class="se-muted">(optional)</span></label>
                            <textarea id="checkout-note" v-model="form.note" class="form-control" rows="2" maxlength="500" placeholder="Delivery instructions, preferred time…"></textarea>
                        </div>
                    </div>
                </div>

                <div class="se-card se-card-pad mt-3">
                    <h2 class="h6 mb-3"><i class="bi bi-credit-card me-2 text-brand"></i>Payment method</h2>
                    <div class="payment-method">
                        <span class="gateway-logo"><i class="bi bi-shield-lock-fill"></i></span>
                        <div class="flex-grow-1">
                            <strong>SSLCommerz (demo)</strong>
                            <p class="se-muted small mb-0">You will be redirected to the payment page after placing the order.</p>
                        </div>
                        <span class="pill pill-success">Selected</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="se-card se-card-pad checkout-summary">
                    <h2 class="h6 mb-3">Order summary</h2>

                    <ul class="checkout-items">
                        <li v-for="item in cart.state.items" :key="item.id">
                            <img :src="item.thumbnail" :alt="item.name">
                            <div class="flex-grow-1">
                                <span class="d-block small fw-semibold">{{ item.name }}</span>
                                <small class="se-muted">Qty {{ item.quantity }}</small>
                            </div>
                            <strong class="small">{{ formatMoney(item.line_total) }}</strong>
                        </li>
                    </ul>

                    <div class="d-flex justify-content-between py-2 border-top">
                        <span class="se-muted">Subtotal</span>
                        <strong>{{ formatMoney(cart.state.subtotal) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between pb-2">
                        <span class="se-muted">Delivery</span>
                        <strong>{{ cart.state.shipping ? formatMoney(cart.state.shipping) : 'Free' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-top pt-3 mb-3">
                        <span class="fw-semibold">Total</span>
                        <span class="h4 mb-0 price-tag">{{ formatMoney(cart.state.total) }}</span>
                    </div>

                    <button class="btn btn-brand w-100" type="submit" :disabled="placing || !cart.state.items.length">
                        <span v-if="placing" class="spinner-border spinner-border-sm me-2"></span>
                        Place order &amp; pay
                    </button>

                    <p class="text-center small se-muted mt-3 mb-0">
                        By placing this order you agree to our demo terms. No real charge is made.
                    </p>
                </div>
            </div>
        </form>
    </div>
</template>
