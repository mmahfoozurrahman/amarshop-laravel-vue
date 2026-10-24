<script setup>
import { computed, reactive, ref } from 'vue';
import Breadcrumbs from '@/shared/components/Breadcrumbs.vue';
import { useAuth } from '@/shared/composables/useAuth';
import { useToast } from '@/shared/composables/useToast';
import { updateProfile } from '@/shared/services/shop';
import { errorMessage, firstError, formatDate, validationErrors } from '@/shared/utils/format';

const auth = useAuth();
const toast = useToast();

const form = reactive({
    name: auth.state.user?.name ?? '',
    email: auth.state.user?.email ?? '',
});
const saving = ref(false);
const formError = ref('');
const errors = reactive({ name: '', email: '' });

const initials = computed(() =>
    (form.name || 'AmarShop')
        .split(' ')
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join(''),
);

async function save() {
    errors.name = '';
    errors.email = '';
    formError.value = '';
    saving.value = true;

    try {
        const response = await updateProfile({ name: form.name, email: form.email || null });
        auth.state.user = response.data;
        localStorage.setItem('auth_user', JSON.stringify(response.data));
        toast.success(response.message);
    } catch (exception) {
        // The form is `novalidate`, so the API is the only source of validation
        // truth: every message lands on its own field, like the OTP login page.
        const validation = validationErrors(exception);
        errors.name = firstError({ name: validation.name }) ?? '';
        errors.email = firstError({ email: validation.email }) ?? '';

        if (!errors.name && !errors.email) {
            formError.value = errorMessage(exception, 'Could not save your profile.');
        }
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="container py-4">
        <Breadcrumbs :items="[{ label: 'My profile' }]" />

        <div class="row g-4 mt-1">
            <div class="col-lg-4">
                <div class="se-card se-card-pad text-center">
                    <div class="profile-avatar mx-auto mb-3">{{ initials }}</div>
                    <h1 class="h5 mb-1">{{ auth.state.user?.name }}</h1>
                    <p class="se-muted mb-3">{{ auth.state.user?.phone }}</p>

                    <div class="row text-center g-2 mb-3">
                        <div class="col-6">
                            <div class="profile-stat">
                                <strong>{{ auth.state.user?.orders_count ?? 0 }}</strong>
                                <small>Orders</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="profile-stat">
                                <strong>{{ auth.state.user?.wishlists_count ?? 0 }}</strong>
                                <small>Wishlist</small>
                            </div>
                        </div>
                    </div>

                    <p class="se-muted small mb-3">
                        Member since {{ formatDate(auth.state.user?.created_at) }}
                    </p>

                    <div class="d-grid gap-2">
                        <router-link :to="{ name: 'orders' }" class="btn btn-outline-brand btn-sm">My orders</router-link>
                        <router-link :to="{ name: 'wishlist' }" class="btn btn-soft btn-sm">My wishlist</router-link>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="se-card se-card-pad">
                    <h2 class="h5 mb-1">Profile details</h2>
                    <p class="se-muted small mb-4">Your phone number is your login identity and cannot be changed here.</p>

                    <form novalidate @submit.prevent="save">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold" for="profile-name">Full name</label>
                                <input id="profile-name" v-model="form.name" type="text" class="form-control" :class="{ 'is-invalid': errors.name }" maxlength="120" required>
                                <div v-if="errors.name" class="invalid-feedback">{{ errors.name }}</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold" for="profile-phone">Phone</label>
                                <input id="profile-phone" :value="auth.state.user?.phone" type="text" class="form-control" disabled>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold" for="profile-email">Email <span class="se-muted">(optional)</span></label>
                                <input id="profile-email" v-model="form.email" type="email" class="form-control" :class="{ 'is-invalid': errors.email }" maxlength="190">
                                <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Role</label>
                                <input :value="auth.state.user?.role" type="text" class="form-control" disabled>
                            </div>
                        </div>

                        <div v-if="formError" class="alert alert-danger py-2 small mt-4 mb-0">{{ formError }}</div>

                        <button class="btn btn-brand mt-4" type="submit" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                            Save changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
