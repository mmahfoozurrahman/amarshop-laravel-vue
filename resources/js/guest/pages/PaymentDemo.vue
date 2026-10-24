<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from '@/shared/composables/useToast';
import { completeDemoPayment, fetchOrder } from '@/shared/services/shop';
import { errorMessage, formatMoney } from '@/shared/utils/format';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const order = ref(null);
const loading = ref(true);
const processing = ref('');

onMounted(async () => {
    try {
        order.value = await fetchOrder(route.params.orderNumber);
    } catch (exception) {
        toast.error(errorMessage(exception, 'Could not load this order.'));
    } finally {
        loading.value = false;
    }
});

async function choose(result) {
    if (!order.value) return;

    processing.value = result;

    try {
        const response = await completeDemoPayment(order.value.order_number, result);
        toast.success(response.message);

        const status = result === 'success' ? 'success' : result === 'cancel' ? 'cancelled' : 'failed';

        router.push({
            name: 'order.show',
            params: { orderNumber: order.value.order_number },
            query: { payment: status },
        });
    } catch (exception) {
        toast.error(errorMessage(exception, 'The demo payment could not be completed.'));
    } finally {
        processing.value = '';
    }
}
</script>

<template>
    <div class="gateway-page">
        <div class="gateway-card">
            <div class="gateway-head">
                <div class="d-flex align-items-center gap-2">
                    <span class="gateway-logo"><i class="bi bi-shield-lock-fill"></i></span>
                    <div>
                        <strong>SSLCommerz</strong>
                        <small class="d-block">Demo payment gateway</small>
                    </div>
                </div>
                <span class="pill pill-warning">Sandbox</span>
            </div>

            <div v-if="loading" class="p-4">
                <div class="skeleton mb-3" style="height: 24px; width: 60%;"></div>
                <div class="skeleton mb-3" style="height: 18px;"></div>
                <div class="skeleton" style="height: 80px;"></div>
            </div>

            <div v-else-if="order" class="p-4">
                <p class="se-muted small mb-1">Merchant</p>
                <h2 class="h5 mb-3">AmarShop · {{ order.order_number }}</h2>

                <div class="gateway-amount">
                    <span>Amount payable</span>
                    <strong>{{ formatMoney(order.total) }}</strong>
                </div>

                <p class="small se-muted mt-3">
                    This is a demonstration gateway. No real money moves — choosing “Success” marks the order
                    as paid so the full checkout and order history flow can be reviewed.
                </p>

                <div class="d-grid gap-2 mt-3">
                    <button class="btn btn-brand" type="button" :disabled="!!processing" @click="choose('success')">
                        <span v-if="processing === 'success'" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="bi bi-check2-circle me-2"></i>
                        Complete payment (success)
                    </button>

                    <button class="btn btn-outline-danger" type="button" :disabled="!!processing" @click="choose('fail')">
                        <span v-if="processing === 'fail'" class="spinner-border spinner-border-sm me-2"></span>
                        Simulate failure
                    </button>

                    <button class="btn btn-light" type="button" :disabled="!!processing" @click="choose('cancel')">
                        <span v-if="processing === 'cancel'" class="spinner-border spinner-border-sm me-2"></span>
                        Cancel and return
                    </button>
                </div>
            </div>

            <div class="gateway-foot">
                <i class="bi bi-lock-fill me-1"></i>
                Payments are validated server-side before an order is marked paid.
            </div>
        </div>
    </div>
</template>
