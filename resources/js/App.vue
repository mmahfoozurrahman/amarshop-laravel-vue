<script setup>
import { onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import ConfirmDialog from '@/shared/components/ConfirmDialog.vue';
import ToastHost from '@/shared/components/ToastHost.vue';
import { useCart } from '@/shared/composables/useCart';
import { useWishlist } from '@/shared/composables/useWishlist';

const router = useRouter();
const cart = useCart();
const wishlist = useWishlist();

function handleAuthExpired(event) {
    cart.reset();
    wishlist.reset();

    const isAdminArea = Boolean(event.detail?.admin);

    router.push(isAdminArea ? { name: 'admin.login' } : { name: 'login' });
}

onMounted(() => {
    window.addEventListener('auth:expired', handleAuthExpired);
    cart.refresh();
    wishlist.refresh();
});

onUnmounted(() => {
    window.removeEventListener('auth:expired', handleAuthExpired);
});
</script>

<template>
    <router-view v-slot="{ Component }">
        <transition name="page" mode="out-in">
            <component :is="Component" />
        </transition>
    </router-view>

    <ToastHost />
    <ConfirmDialog />
</template>

