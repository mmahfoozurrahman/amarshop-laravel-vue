<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuth } from '@/shared/composables/useAuth';
import { useCart } from '@/shared/composables/useCart';
import { useToast } from '@/shared/composables/useToast';
import { errorMessage, firstError, validationErrors } from '@/shared/utils/format';

const route = useRoute();
const router = useRouter();
const auth = useAuth();
const cart = useCart();
const toast = useToast();

const step = ref('phone');
const phone = ref('');
const name = ref('');
const otp = ref('');
const demoOtp = ref('');
const loading = ref(false);
const resendIn = ref(0);
const errors = reactive({ phone: '', otp: '' });

let timer = null;

const redirectTarget = computed(() =>
    typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/')
        ? route.query.redirect
        : '/',
);

function startResendTimer() {
    resendIn.value = 60;
    window.clearInterval(timer);
    timer = window.setInterval(() => {
        resendIn.value -= 1;

        if (resendIn.value <= 0) {
            window.clearInterval(timer);
        }
    }, 1000);
}

onBeforeUnmount(() => window.clearInterval(timer));

async function sendOtp() {
    errors.phone = '';
    loading.value = true;

    try {
        const response = await auth.requestOtp(phone.value);
        demoOtp.value = response.demo_otp ?? '';
        step.value = 'otp';
        startResendTimer();
        toast.success(response.message);
    } catch (exception) {
        errors.phone = firstError(validationErrors(exception)) ?? errorMessage(exception, 'Could not send the OTP.');
    } finally {
        loading.value = false;
    }
}

async function verifyOtp() {
    errors.otp = '';
    loading.value = true;

    try {
        await auth.verifyOtp({
            phone: phone.value,
            otp: otp.value,
            name: name.value.trim() || undefined,
        });
        await cart.refresh();
        toast.success('Welcome to AmarShop!');
        router.push(redirectTarget.value);
    } catch (exception) {
        errors.otp = firstError(validationErrors(exception)) ?? errorMessage(exception, 'Could not verify the OTP.');
    } finally {
        loading.value = false;
    }
}

function useDemoOtp() {
    otp.value = demoOtp.value;
}

function changeNumber() {
    step.value = 'phone';
    otp.value = '';
    demoOtp.value = '';
    window.clearInterval(timer);
    resendIn.value = 0;
}
</script>

<template>
    <div class="container py-4">
        <div class="auth-wrap">
            <div class="auth-promo">
                <span class="pill pill-warning mb-3 d-inline-block">Passwordless</span>
                <h1 class="h2 mb-2">Login with a one-time code</h1>
                <p class="mb-4">No passwords to remember. We send a 6-digit OTP to your phone and sign you in instantly.</p>

                <ul class="auth-list">
                    <li><i class="bi bi-shield-check"></i> OTP expires in 5 minutes</li>
                    <li><i class="bi bi-bag-check"></i> Your guest cart is merged automatically</li>
                    <li><i class="bi bi-heart"></i> Wishlist and order history unlock instantly</li>
                    <li><i class="bi bi-phone"></i> Demo mode shows the OTP on screen</li>
                </ul>
            </div>

            <div class="auth-card">
                <div class="auth-steps">
                    <span :class="{ 'is-active': step === 'phone' }">1 · Phone</span>
                    <span :class="{ 'is-active': step === 'otp' }">2 · Verify</span>
                </div>

                <form v-if="step === 'phone'" novalidate @submit.prevent="sendOtp">
                    <h2 class="h4 mb-1">Enter your mobile number</h2>
                    <p class="se-muted small mb-4">Use a Bangladeshi number, e.g. 01712345678.</p>

                    <label class="form-label small fw-semibold" for="phone">Mobile number</label>
                    <div class="input-group mb-1">
                        <span class="input-group-text">+880</span>
                        <input
                            id="phone"
                            v-model="phone"
                            type="tel"
                            inputmode="numeric"
                            class="form-control"
                            :class="{ 'is-invalid': errors.phone }"
                            placeholder="1712345678"
                            autocomplete="tel"
                            required
                        >
                    </div>
                    <div v-if="errors.phone" class="text-danger small mb-2">{{ errors.phone }}</div>

                    <label class="form-label small fw-semibold mt-3" for="name">Your name <span class="se-muted">(new customers)</span></label>
                    <input id="name" v-model="name" type="text" class="form-control" placeholder="e.g. Rahim Uddin" maxlength="120">
                    <p class="se-muted small mt-2 mb-4">Already registered? Leave the name empty.</p>

                    <button class="btn btn-brand w-100" type="submit" :disabled="loading">
                        <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                        Send OTP
                    </button>
                </form>

                <form v-else novalidate @submit.prevent="verifyOtp">
                    <h2 class="h4 mb-1">Enter the 6-digit code</h2>
                    <p class="se-muted small mb-3">
                        Sent to <strong>{{ phone }}</strong>.
                        <button class="btn btn-link btn-sm p-0" type="button" @click="changeNumber">Change number</button>
                    </p>

                    <div v-if="demoOtp" class="demo-otp mb-3">
                        <div>
                            <small class="d-block se-muted">Demo OTP (shown because APP_DEBUG is on)</small>
                            <strong class="fs-5">{{ demoOtp }}</strong>
                        </div>
                        <button class="btn btn-sm btn-soft" type="button" @click="useDemoOtp">Use this code</button>
                    </div>

                    <label class="form-label small fw-semibold" for="otp">OTP</label>
                    <input
                        id="otp"
                        v-model="otp"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        class="form-control otp-input"
                        :class="{ 'is-invalid': errors.otp }"
                        placeholder="••••••"
                        autocomplete="one-time-code"
                        required
                        @input="otp = otp.replace(/\D/g, '').slice(0, 6)"
                    >
                    <div v-if="errors.otp" class="text-danger small mt-1">{{ errors.otp }}</div>

                    <button class="btn btn-brand w-100 mt-4" type="submit" :disabled="loading">
                        <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                        Verify &amp; login
                    </button>

                    <div class="d-flex justify-content-between align-items-center mt-3 small">
                        <button class="btn btn-link btn-sm p-0" type="button" :disabled="resendIn > 0" @click="sendOtp">
                            {{ resendIn > 0 ? `Resend in ${resendIn}s` : 'Resend OTP' }}
                        </button>
                        <span class="se-muted">Code expires in 5 minutes</span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
