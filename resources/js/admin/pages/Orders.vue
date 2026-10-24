<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Pagination from '@/shared/components/Pagination.vue';
import StatusBadge from '@/shared/components/StatusBadge.vue';
import { fetchAdminOrders } from '@/shared/services/admin';
import { errorMessage, formatDate, formatMoney } from '@/shared/utils/format';

const filters = reactive({ status: '', payment_status: '', q: '' });
const orders = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(true);
const error = ref('');

const route = useRoute();
const router = useRouter();

/**
 * Filters live in the URL query so a reload (F5) or a shared link keeps the
 * search, the dropdowns and the page number — same pattern as the admin
 * products page and the storefront shop page.
 */
function syncFromQuery() {
    filters.q = route.query.q ?? '';
    filters.status = route.query.status ?? '';
    filters.payment_status = route.query.payment_status ?? '';
}

async function load(page = 1) {
    loading.value = true;
    error.value = '';

    try {
        const response = await fetchAdminOrders({
            page,
            per_page: 12,
            status: filters.status || undefined,
            payment_status: filters.payment_status || undefined,
            q: filters.q || undefined,
        });

        orders.value = response.data;
        meta.value = response.meta;
    } catch (exception) {
        error.value = errorMessage(exception, 'Could not load orders.');
    } finally {
        loading.value = false;
    }
}

function applyFilters(page = 1) {
    const query = {};

    if (filters.q.trim()) query.q = filters.q.trim();
    if (filters.status) query.status = filters.status;
    if (filters.payment_status) query.payment_status = filters.payment_status;
    if (page > 1) query.page = page;

    const unchanged = router.resolve({ query }).fullPath === route.fullPath;

    if (unchanged) {
        load(page); // same query pressed twice → refresh anyway

        return;
    }

    router.replace({ query });
}

watch(() => route.query, () => {
    syncFromQuery();
    load(Number(route.query.page ?? 1));
});

onMounted(() => {
    syncFromQuery();
    load(Number(route.query.page ?? 1));
});
</script>

<template>
    <div class="admin-card">
        <div class="admin-card__head">
            <div class="admin-toolbar">
                <input v-model="filters.q" type="search" class="form-control" placeholder="Order number, phone, name…" @keyup.enter="applyFilters(1)">
                <select v-model="filters.status" class="form-select" @change="applyFilters(1)">
                    <option value="">Any status</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select v-model="filters.payment_status" class="form-select" @change="applyFilters(1)">
                    <option value="">Any payment</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="paid">Paid</option>
                    <option value="failed">Failed</option>
                </select>
            </div>

            <span class="se-muted small">{{ meta.total }} order(s)</span>
        </div>

        <div v-if="error" class="alert alert-danger m-3">{{ error }}</div>

        <div class="table-responsive">
            <table class="table table-shop mb-0">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td colspan="8" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</td>
                    </tr>
                    <tr v-for="order in orders" v-else :key="order.id">
                        <td>
                            <router-link :to="{ name: 'admin.orders.show', params: { id: order.id } }" class="fw-semibold">
                                {{ order.order_number }}
                            </router-link>
                        </td>
                        <td>
                            <span class="d-block small">{{ order.shipping_address?.name }}</span>
                            <small class="se-muted">{{ order.shipping_address?.phone }}</small>
                        </td>
                        <td>{{ order.items_count ?? order.items?.length ?? 0 }}</td>
                        <td class="fw-semibold">{{ formatMoney(order.total) }}</td>
                        <td><StatusBadge :status="order.status" /></td>
                        <td><StatusBadge :status="order.payment_status" /></td>
                        <td class="se-muted small">{{ formatDate(order.created_at) }}</td>
                        <td class="text-end">
                            <router-link :to="{ name: 'admin.orders.show', params: { id: order.id } }" class="btn btn-sm btn-light">
                                <i class="bi bi-eye"></i>
                            </router-link>
                        </td>
                    </tr>
                    <tr v-if="!loading && !orders.length">
                        <td colspan="8" class="text-center se-muted py-4">No orders matched.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="p-3"><Pagination :meta="meta" @change="applyFilters" /></div>
    </div>
</template>
