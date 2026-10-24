<script setup>
import { onMounted, ref } from 'vue';
import StatusBadge from '@/shared/components/StatusBadge.vue';
import { fetchDashboard } from '@/shared/services/admin';
import { errorMessage, formatDate, formatMoney } from '@/shared/utils/format';

const loading = ref(true);
const error = ref('');
const data = ref({ stats: {}, recent_orders: [], low_stock: [] });

onMounted(async () => {
    try {
        data.value = await fetchDashboard();
    } catch (exception) {
        error.value = errorMessage(exception, 'Could not load the dashboard.');
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div>
        <div v-if="error" class="alert alert-danger">{{ error }}</div>

        <div v-if="loading" class="row g-3">
            <div v-for="n in 4" :key="`stat-${n}`" class="col-6 col-xl-3">
                <div class="skeleton" style="height: 110px; border-radius: 14px;"></div>
            </div>
        </div>

        <template v-else>
            <div class="row g-3">
                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card__icon"><i class="bi bi-box-seam"></i></div>
                        <div class="stat-card__value">{{ data.stats.products }}</div>
                        <div class="stat-card__label">{{ data.stats.active_products }} active products</div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card__icon"><i class="bi bi-people"></i></div>
                        <div class="stat-card__value">{{ data.stats.customers }}</div>
                        <div class="stat-card__label">Registered customers</div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card__icon"><i class="bi bi-receipt"></i></div>
                        <div class="stat-card__value">{{ data.stats.orders }}</div>
                        <div class="stat-card__label">{{ data.stats.pending_orders }} awaiting action</div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-card__icon"><i class="bi bi-cash-stack"></i></div>
                        <div class="stat-card__value">{{ formatMoney(data.stats.revenue) }}</div>
                        <div class="stat-card__label">Paid revenue</div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-xl-8">
                    <div class="admin-card">
                        <div class="admin-card__head">
                            <h2 class="h6 mb-0">Recent orders</h2>
                            <router-link :to="{ name: 'admin.orders' }" class="btn btn-sm btn-outline-brand">All orders</router-link>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-shop mb-0">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="order in data.recent_orders" :key="order.id">
                                        <td>
                                            <router-link :to="{ name: 'admin.orders.show', params: { id: order.id } }" class="fw-semibold">
                                                {{ order.order_number }}
                                            </router-link>
                                        </td>
                                        <td>{{ order.customer?.name ?? order.shipping_address?.name }}</td>
                                        <td>{{ formatMoney(order.total) }}</td>
                                        <td><StatusBadge :status="order.status" /></td>
                                        <td class="se-muted">{{ formatDate(order.created_at) }}</td>
                                    </tr>
                                    <tr v-if="!data.recent_orders.length">
                                        <td colspan="5" class="text-center se-muted py-4">No orders yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="admin-card h-100">
                        <div class="admin-card__head">
                            <h2 class="h6 mb-0">Low stock</h2>
                            <router-link :to="{ name: 'admin.products' }" class="btn btn-sm btn-light">Manage</router-link>
                        </div>
                        <div class="admin-card__body d-flex flex-column gap-3">
                            <div v-for="product in data.low_stock" :key="product.id" class="d-flex align-items-center gap-2">
                                <img :src="product.thumbnail" :alt="product.name" class="table-thumb">
                                <div class="flex-grow-1" style="min-width: 0">
                                    <span class="d-block small fw-semibold text-truncate">{{ product.name }}</span>
                                    <small class="se-muted">{{ formatMoney(product.effective_price) }}</small>
                                </div>
                                <span class="pill" :class="product.stock ? 'pill-warning' : 'pill-danger'">{{ product.stock }} left</span>
                            </div>
                            <p v-if="!data.low_stock.length" class="se-muted small mb-0">Everything is well stocked.</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
