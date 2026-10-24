<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Breadcrumbs from '@/shared/components/Breadcrumbs.vue';
import StatusBadge from '@/shared/components/StatusBadge.vue';
import { useToast } from '@/shared/composables/useToast';
import { fetchOrder, payOrder } from '@/shared/services/shop';
import { errorMessage, formatDate, formatMoney } from '@/shared/utils/format';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const order = ref(null);
const loading = ref(true);
const paying = ref(false);

const paymentNotice = computed(() => {
    const result = route.query.payment;

    if (result === 'success') {
        return { type: 'success', text: 'Payment completed. Your order is confirmed.' };
    }

    if (result === 'cancelled') {
        return { type: 'warning', text: 'Payment was cancelled and the order was cancelled. Stock has been restored.' };
    }

    if (result === 'failed') {
        return { type: 'danger', text: 'Payment failed and the order was cancelled. You can place a new order anytime.' };
    }

    return null;
});

async function load() {
    loading.value = true;

    try {
        order.value = await fetchOrder(route.params.orderNumber);
    } catch (exception) {
        toast.error(errorMessage(exception, 'Could not load this order.'));
    } finally {
        loading.value = false;
    }
}

onMounted(load);

async function payNow() {
    if (!order.value) return;

    paying.value = true;

    try {
        const response = await payOrder(order.value.order_number);

        const redirect = response.redirect_url ?? '';

        // The demo gateway is a client-side route, so push it through the router.
        // An absolute URL (sandbox gateway) needs a real navigation instead.
        if (response.gateway === 'sandbox' || /^https?:\/\//i.test(redirect)) {
            window.location.assign(redirect);

            return;
        }

        router.push(redirect);
    } catch (exception) {
        toast.error(errorMessage(exception, 'Could not start the payment.'));
    } finally {
        paying.value = false;
    }
}
</script>

<template>
    <div class="container py-4">
        <Breadcrumbs :items="[{ label: 'My orders', to: { name: 'orders' } }, { label: order?.order_number ?? 'Order' }]" />

        <div v-if="loading" class="se-card se-card-pad mt-3">
            <div class="skeleton mb-3" style="height: 26px; width: 50%;"></div>
            <div class="skeleton mb-2" style="height: 16px;"></div>
            <div class="skeleton" style="height: 120px;"></div>
        </div>

        <template v-else-if="order">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mt-3 mb-4">
                <div>
                    <h1 class="se-section-title mb-1">{{ order.order_number }}</h1>
                    <p class="se-muted mb-0">Placed {{ formatDate(order.created_at, true) }}</p>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <StatusBadge :status="order.status" />
                    <StatusBadge :status="order.payment_status" />
                    <button v-if="order.can_pay" class="btn btn-brand btn-sm" type="button" :disabled="paying" @click="payNow">
                        <span v-if="paying" class="spinner-border spinner-border-sm me-2"></span>
                        Pay now
                    </button>
                </div>
            </div>

            <div v-if="paymentNotice" class="alert" :class="`alert-${paymentNotice.type}`">
                <i class="bi bi-info-circle me-2"></i>{{ paymentNotice.text }}
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="se-card">
                        <div class="admin-card__head">
                            <h2 class="h6 mb-0">Items</h2>
                            <span class="se-muted small">{{ order.items.length }} line(s)</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-shop mb-0">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in order.items" :key="item.id">
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img v-if="item.thumbnail" :src="item.thumbnail" :alt="item.product_name" class="table-thumb">
                                                <div>
                                                    <router-link v-if="item.slug" :to="{ name: 'product.show', params: { slug: item.slug } }" class="fw-semibold text-dark">
                                                        {{ item.product_name }}
                                                    </router-link>
                                                    <span v-else class="fw-semibold">{{ item.product_name }}</span>
                                                    <small class="d-block se-muted">SKU {{ item.sku }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ formatMoney(item.unit_price) }}</td>
                                        <td>{{ item.quantity }}</td>
                                        <td class="text-end fw-semibold">{{ formatMoney(item.line_total) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="se-card se-card-pad mb-3">
                        <h2 class="h6 mb-3">Payment summary</h2>
                        <div class="order-total-row"><span class="se-muted">Subtotal</span><span>{{ formatMoney(order.subtotal) }}</span></div>
                        <div class="order-total-row"><span class="se-muted">Delivery</span><span>{{ order.shipping ? formatMoney(order.shipping) : 'Free' }}</span></div>
                        <div class="order-total-row"><span class="se-muted">Discount</span><span>{{ formatMoney(order.discount) }}</span></div>
                        <div class="order-total-row is-grand"><span>Total</span><span>{{ formatMoney(order.total) }}</span></div>

                        <div class="small se-muted mt-3 d-grid gap-1">
                            <span v-if="order.transaction_id">Transaction: {{ order.transaction_id }}</span>
                            <span v-if="order.paid_at">Paid on {{ formatDate(order.paid_at, true) }}</span>
                            <span>Method: {{ order.payment_method }}</span>
                        </div>
                    </div>

                    <div class="se-card se-card-pad">
                        <h2 class="h6 mb-3">Delivery address</h2>
                        <p class="mb-1 fw-semibold">{{ order.shipping_address.name }}</p>
                        <p class="mb-1 small se-muted">{{ order.shipping_address.phone }}</p>
                        <p class="mb-0 small se-muted">
                            {{ order.shipping_address.address }}, {{ order.shipping_address.city }}
                            <span v-if="order.shipping_address.postcode">- {{ order.shipping_address.postcode }}</span>
                        </p>
                        <p v-if="order.note" class="small se-muted mt-2 mb-0"><em>{{ order.note }}</em></p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
