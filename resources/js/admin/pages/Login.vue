<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAdminAuth } from '@/shared/composables/useAdminAuth';
import { errorMessage, firstError, validationErrors } from '@/shared/utils/format';

const route = useRoute();
const router = useRouter();
const adminAuth = useAdminAuth();

const form = reactive({
    email: 'admin@amarshop.test',
    password: 'password',
});
const loading = ref(false);
const error = ref('');

async function submit() {
    error.value = '';
    loading.value = true;

    try {
        await adminAuth.login(form.email, form.password);

        const redirect = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/admin')
            ? route.query.redirect
            : '/admin';

        router.push(redirect);
    } catch (exception) {
        const validation = validationErrors(exception);
        error.value = firstError(validation) ?? errorMessage(exception, 'Could not sign in.');
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="admin-login">
        <div class="admin-login__card">
            <div class="admin-login__brand">
                <span class="shop-brand__icon"><i class="bi bi-bag-heart-fill"></i></span>
                <div>
                    <strong>AmarShop Admin</strong>
                    <small class="d-block text-muted">Store management panel</small>
                </div>
            </div>

            <h1 class="h4 mt-4 mb-1">Sign in to manage your store</h1>
            <p class="se-muted small mb-4">Use the seeded admin account to explore the dashboard.</p>

            <div v-if="error" class="alert alert-danger py-2 small">{{ error }}</div>

            <form novalidate @submit.prevent="submit">
                <label class="form-label small fw-semibold" for="admin-email">Email</label>
                <input id="admin-email" v-model="form.email" type="email" class="form-control mb-3" autocomplete="username" required>

                <label class="form-label small fw-semibold" for="admin-password">Password</label>
                <input id="admin-password" v-model="form.password" type="password" class="form-control mb-4" autocomplete="current-password" required>

                <button class="btn btn-brand w-100" type="submit" :disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                    Sign in
                </button>
            </form>

            <div class="demo-credentials mt-4">
                <strong class="d-block small mb-1">Demo credentials</strong>
                <code>admin@amarshop.test</code> / <code>password</code>
            </div>

            <router-link :to="{ name: 'home' }" class="d-block text-center small mt-4">
                <i class="bi bi-arrow-left me-1"></i>Back to storefront
            </router-link>
        </div>
    </div>
</template>
