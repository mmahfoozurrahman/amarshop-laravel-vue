<script setup>
import { onMounted, ref } from 'vue';
import StatusBadge from '@/shared/components/StatusBadge.vue';
import { useToast } from '@/shared/composables/useToast';
import { fetchAdminOrder, updateOrderStatus } from '@/shared/services/admin';
import { errorMessage, formatDate, formatMoney } from '@/shared/utils/format';

const props = defineProps({
    id: { type: [String, Number], required: true },
});

const toast = useToast();
const order = ref(null);
const loading = ref(true);
const updating = ref(false);
const nextStatus = ref('');

const statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];

async function load() {
    loading.value = true;

    try {
        order.value = await fetchAdminOrder(props.id);
    } catch (exception) {
        toast.error(errorMessage(exception, 'Could not load this order.'));
    } finally {
        loading.value = false;
    }
}

onMounted(load);

async function changeStatus() {
    if (!nextStatus.value || !order.value) return;

    updating.value = true;

    try {
        const response = await updateOrderStatus(order.value.id, nextStatus.value);
        order.value = response.data;
        nextStatus.value = '';
        toast.success(response.message);
    } catch (exception) {
        toast.error(errorMessage(exception, 'Could not update the order status.'));
    } finally {
        updating.value = false;
    }
}
</script>

<template>
    <div>
        <router-link :to="{ name: 'admin.orders' }" class="btn btn-sm btn-light mb-3">
            <i class="bi bi-arrow-left me-1"></i> Back to orders
        </router-link>

        <div v-if="loading" class="admin-card admin-card__body">
            <div class="skeleton mb-3" style="height: 26px; width: 40%;"></div>
            <div class="skeleton" style="height: 140px;"></div>
        </div>

        <template v-else-if="order">
            <div class="admin-card admin-card__body mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h2 class="h5 mb-1">{{ order.order_number }}</h2>
                        <p class="se-muted small mb-0">Placed {{ formatDate(order.created_at, true) }}</p>
                        <div class="d-flex gap-2 mt-2">
                            <StatusBadge :status="order.status" />
                            <StatusBadge :status="order.payment_status" />
                        </div>
                    </div>

                    <div class="admin-toolbar">
                        <select v-model="nextStatus" class="form-select" style="min-width: 180px">
                            <option value="">Change status…</option>
                            <option v-for="status in statuses" :key="status" :value="status" :disabled="status === order.status">
                                {{ status }}
                            </option>
                        </select>
                        <button class="btn btn-brand" type="button" :disabled="!nextStatus || updating" @click="changeStatus">
                            <span v-if="updating" class="spinner-border spinner-border-sm me-2"></span>
                            Update
                        </button>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="admin-card">
                        <div class="admin-card__head"><h2 class="h6 mb-0">Items</h2></div>

                        <div class="table-responsive">
                            <table class="table table-shop mb-0">
                                <thead>
                                    <tr><th>Product</th><th>Price</th><th>Qty</th><th class="text-end">Total</th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in order.items" :key="item.id">
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img v-if="item.thumbnail" :src="item.thumbnail" :alt="item.product_name" class="table-thumb">
                                                <div>
                                                    <span class="fw-semibold d-block">{{ item.product_name }}</span>
                                                    <small class="se-muted">{{ item.sku }}</small>
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

                        <div class="admin-card__body border-top">
                            <div class="order-total-row"><span class="se-muted">Subtotal</span><span>{{ formatMoney(order.subtotal) }}</span></div>
                            <div class="order-total-row"><span class="se-muted">Delivery</span><span>{{ order.shipping ? formatMoney(order.shipping) : 'Free' }}</span></div>
                            <div class="order-total-row"><span class="se-muted">Discount</span><span>{{ formatMoney(order.discount) }}</span></div>
                            <div class="order-total-row is-grand"><span>Total</span><span>{{ formatMoney(order.total) }}</span></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="admin-card admin-card__body mb-3">
                        <h2 class="h6 mb-2">Customer</h2>
                        <p class="mb-1 fw-semibold">{{ order.customer?.name ?? order.shipping_address?.name }}</p>
                        <p class="mb-1 small se-muted">{{ order.customer?.phone ?? order.shipping_address?.phone }}</p>
                        <p class="mb-0 small se-muted">{{ order.customer?.email ?? 'No email on file' }}</p>
                    </div>

                    <div class="admin-card admin-card__body mb-3">
                        <h2 class="h6 mb-2">Shipping address</h2>
                        <p class="mb-1 fw-semibold">{{ order.shipping_address.name }}</p>
                        <p class="mb-1 small se-muted">{{ order.shipping_address.phone }}</p>
                        <p class="mb-0 small se-muted">
                            {{ order.shipping_address.address }}, {{ order.shipping_address.city }}
                            <span v-if="order.shipping_address.postcode">- {{ order.shipping_address.postcode }}</span>
                        </p>
                        <p v-if="order.note" class="small se-muted mt-2 mb-0"><em>{{ order.note }}</em></p>
                    </div>

                    <div class="admin-card admin-card__body">
                        <h2 class="h6 mb-2">Payment</h2>
                        <p class="mb-1 small se-muted">Method: <strong>{{ order.payment_method }}</strong></p>
                        <p class="mb-1 small se-muted">Transaction: {{ order.transaction_id ?? '—' }}</p>
                        <p class="mb-0 small se-muted">Paid at: {{ order.paid_at ? formatDate(order.paid_at, true) : '—' }}</p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
