<script setup>
import { onMounted, ref, watch } from 'vue';
import Breadcrumbs from '@/shared/components/Breadcrumbs.vue';
import EmptyState from '@/shared/components/EmptyState.vue';
import Pagination from '@/shared/components/Pagination.vue';
import StatusBadge from '@/shared/components/StatusBadge.vue';
import { fetchOrders } from '@/shared/services/shop';
import { errorMessage, formatDate, formatMoney } from '@/shared/utils/format';

const tabs = [
    { value: '', label: 'All' },
    { value: 'pending', label: 'Pending' },
    { value: 'processing', label: 'Processing' },
    { value: 'shipped', label: 'Shipped' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
];

const status = ref('');
const orders = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(true);
const error = ref('');

async function load(page = 1) {
    loading.value = true;
    error.value = '';

    try {
        const response = await fetchOrders({
            page,
            per_page: 8,
            status: status.value || undefined,
        });

        orders.value = response.data;
        meta.value = response.meta;
    } catch (exception) {
        error.value = errorMessage(exception, 'Could not load your orders.');
        orders.value = [];
    } finally {
        loading.value = false;
    }
}

onMounted(() => load());
watch(status, () => load());
</script>

<template>
    <div class="container py-4">
        <Breadcrumbs :items="[{ label: 'My orders' }]" />

        <div class="mt-3 mb-4">
            <h1 class="se-section-title mb-1">Order history</h1>
            <p class="se-muted mb-0">{{ meta.total }} order(s) placed with AmarShop</p>
        </div>

        <div class="d-flex flex-wrap gap-1 mb-4">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                class="btn btn-sm"
                :class="status === tab.value ? 'btn-brand' : 'btn-light'"
                type="button"
                @click="status = tab.value"
            >
                {{ tab.label }}
            </button>
        </div>

        <div v-if="error" class="alert alert-danger">{{ error }}</div>

        <div v-if="loading" class="d-flex flex-column gap-3">
            <div v-for="n in 3" :key="`order-skeleton-${n}`" class="se-card order-card">
                <div class="skeleton mb-3" style="height: 20px; width: 40%;"></div>
                <div class="skeleton" style="height: 14px; width: 60%;"></div>
            </div>
        </div>

        <div v-else-if="orders.length" class="d-flex flex-column gap-3">
            <article v-for="order in orders" :key="order.id" class="se-card order-card">
                <div class="order-card__head">
                    <div>
                        <router-link :to="{ name: 'order.show', params: { orderNumber: order.order_number } }" class="fw-bold text-dark">
                            {{ order.order_number }}
                        </router-link>
                        <div class="order-meta mt-1">
                            <span><i class="bi bi-calendar3 me-1"></i>{{ formatDate(order.created_at) }}</span>
                            <span><i class="bi bi-box-seam me-1"></i>{{ order.items_count }} item(s)</span>
                            <span><i class="bi bi-wallet2 me-1"></i>{{ formatMoney(order.total) }}</span>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <StatusBadge :status="order.status" />
                        <StatusBadge :status="order.payment_status" />
                        <router-link :to="{ name: 'order.show', params: { orderNumber: order.order_number } }" class="btn btn-sm btn-outline-brand">
                            Details <i class="bi bi-arrow-right ms-1"></i>
                        </router-link>
                    </div>
                </div>
            </article>
        </div>

        <EmptyState
            v-else
            icon="bi-receipt"
            title="No orders here"
            message="Once you place an order it will appear in this list."
        >
            <router-link :to="{ name: 'products' }" class="btn btn-brand">Start shopping</router-link>
        </EmptyState>

        <Pagination :meta="meta" @change="load" />
    </div>
</template>
