<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Pagination from '@/shared/components/Pagination.vue';
import { useToast } from '@/shared/composables/useToast';
import { fetchCustomers, updateCustomerStatus } from '@/shared/services/admin';
import { errorMessage, formatDate } from '@/shared/utils/format';

const toast = useToast();
const customers = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(true);
const search = ref('');
const toggling = ref(null);

const route = useRoute();
const router = useRouter();

/**
 * The search term lives in the URL query so a reload (F5) keeps the filtered
 * list — same pattern as the admin products/orders pages.
 */
function syncFromQuery() {
    search.value = route.query.q ?? '';
}

async function load(page = 1) {
    loading.value = true;

    try {
        const response = await fetchCustomers({
            page,
            per_page: 12,
            q: search.value || undefined,
        });

        customers.value = response.data;
        meta.value = response.meta;
    } catch (exception) {
        toast.error(errorMessage(exception, 'Could not load customers.'));
    } finally {
        loading.value = false;
    }
}

function applyFilters(page = 1) {
    const query = {};

    if (search.value.trim()) query.q = search.value.trim();
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

async function toggle(customer) {
    toggling.value = customer.id;

    try {
        const response = await updateCustomerStatus(customer.id, !customer.is_active);
        toast.success(response.message);
        await load(meta.value.current_page ?? 1);
    } catch (exception) {
        toast.error(errorMessage(exception, 'Could not update this customer.'));
    } finally {
        toggling.value = null;
    }
}
</script>

<template>
    <div class="admin-card">
        <div class="admin-card__head">
            <div class="admin-toolbar">
                <input v-model="search" type="search" class="form-control" placeholder="Name, phone or email…" @keyup.enter="applyFilters(1)">
                <button class="btn btn-light" type="button" @click="applyFilters(1)"><i class="bi bi-search"></i></button>
            </div>
            <span class="se-muted small">{{ meta.total }} customer(s)</span>
        </div>

        <div class="table-responsive">
            <table class="table table-shop mb-0">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Orders</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td colspan="7" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</td>
                    </tr>
                    <tr v-for="customer in customers" v-else :key="customer.id">
                        <td class="fw-semibold">{{ customer.name }}</td>
                        <td>{{ customer.phone }}</td>
                        <td class="se-muted small">{{ customer.email ?? '—' }}</td>
                        <td>{{ customer.orders_count ?? 0 }}</td>
                        <td class="se-muted small">{{ formatDate(customer.created_at) }}</td>
                        <td>
                            <span class="pill" :class="customer.is_active ? 'pill-success' : 'pill-danger'">
                                {{ customer.is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <button
                                class="btn btn-sm"
                                :class="customer.is_active ? 'btn-light text-danger' : 'btn-soft'"
                                type="button"
                                :disabled="toggling === customer.id"
                                @click="toggle(customer)"
                            >
                                <span v-if="toggling === customer.id" class="spinner-border spinner-border-sm"></span>
                                <span v-else>{{ customer.is_active ? 'Disable' : 'Enable' }}</span>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!loading && !customers.length">
                        <td colspan="7" class="text-center se-muted py-4">No customers found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="p-3"><Pagination :meta="meta" @change="applyFilters" /></div>
    </div>
</template>
