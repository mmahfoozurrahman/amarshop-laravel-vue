<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuth } from '@/shared/composables/useAuth';
import { useCart } from '@/shared/composables/useCart';
import { useWishlist } from '@/shared/composables/useWishlist';

const route = useRoute();
const router = useRouter();
const auth = useAuth();
const cart = useCart();
const wishlist = useWishlist();

const mobileOpen = ref(false);
const accountOpen = ref(false);
const search = ref(route.query.q ?? '');

const wishlistCount = computed(() => wishlist.state.ids.length);
const cartCount = computed(() => cart.state.itemCount);

function submitSearch() {
    router.push({
        name: 'products',
        query: search.value.trim() ? { q: search.value.trim() } : {},
    });
    mobileOpen.value = false;
}

async function logout() {
    await auth.logout();
    cart.reset();
    wishlist.reset();
    accountOpen.value = false;
    router.push({ name: 'home' });
}

watch(() => route.fullPath, () => {
    mobileOpen.value = false;
    accountOpen.value = false;
});

watch(() => route.query.q, (value) => {
    search.value = value ?? '';
});
</script>

<template>
    <header class="shop-header">
        <div class="shop-announcement">
            <div class="container shop-announcement__inner">
                <span class="shop-announcement__note"><i class="bi bi-truck me-1"></i> Free delivery on orders over ৳1,000</span>

                <form class="shop-search shop-search--bar" @submit.prevent="submitSearch">
                    <i class="bi bi-search"></i>
                    <input v-model="search" type="search" class="form-control" placeholder="Search products or SKU…" aria-label="Search products">
                    <button class="btn btn-brand" type="submit">Search</button>
                </form>

                <span class="shop-announcement__note d-none d-lg-inline"><i class="bi bi-shield-check me-1"></i> 7-day returns · SSLCommerz demo checkout</span>
            </div>
        </div>

        <nav class="navbar navbar-expand-lg shop-navbar">
            <div class="container">
                <router-link :to="{ name: 'home' }" class="navbar-brand shop-brand">
                    <span class="shop-brand__icon"><i class="bi bi-bag-heart-fill"></i></span>
                    <span>AmarShop</span>
                </router-link>

                <div class="shop-links d-none d-lg-flex">
                    <router-link :to="{ name: 'home' }">Home</router-link>
                    <router-link :to="{ name: 'products' }">Shop all</router-link>
                    <router-link :to="{ name: 'products', query: { featured: 1 } }">Featured</router-link>
                    <router-link :to="{ name: 'brands' }">Brands</router-link>
                    <router-link :to="{ name: 'categories' }">Categories</router-link>
                    <router-link :to="{ name: 'orders' }">Track order</router-link>
                </div>

                <div class="d-flex align-items-center gap-1 gap-sm-2">
                    <router-link :to="{ name: 'wishlist' }" class="nav-icon" aria-label="Wishlist">
                        <i class="bi bi-heart"></i>
                        <span v-if="wishlistCount" class="nav-icon__badge">{{ wishlistCount }}</span>
                    </router-link>

                    <router-link :to="{ name: 'cart' }" class="nav-icon" aria-label="Cart">
                        <i class="bi bi-bag"></i>
                        <span v-if="cartCount" class="nav-icon__badge">{{ cartCount }}</span>
                    </router-link>

                    <div v-if="auth.isAuthenticated.value" class="account-menu">
                        <button class="account-btn" type="button" @click.stop="accountOpen = !accountOpen">
                            <i class="bi bi-person-circle"></i>
                            <span class="d-none d-sm-inline">{{ auth.state.user?.name?.split(' ')[0] }}</span>
                            <i class="bi bi-chevron-down small"></i>
                        </button>

                        <div v-if="accountOpen" class="account-dropdown" @click.stop>
                            <router-link :to="{ name: 'profile' }"><i class="bi bi-person me-2"></i>My profile</router-link>
                            <router-link :to="{ name: 'orders' }"><i class="bi bi-receipt me-2"></i>My orders</router-link>
                            <router-link :to="{ name: 'wishlist' }"><i class="bi bi-heart me-2"></i>Wishlist</router-link>
                            <button type="button" class="text-danger" @click="logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                        </div>
                    </div>

                    <router-link v-else :to="{ name: 'login' }" class="btn btn-outline-brand btn-sm d-none d-sm-inline-flex">
                        <i class="bi bi-phone me-1"></i> OTP login
                    </router-link>

                    <button class="nav-icon d-lg-none" type="button" aria-label="Menu" @click="mobileOpen = !mobileOpen">
                        <i class="bi" :class="mobileOpen ? 'bi-x-lg' : 'bi-list'"></i>
                    </button>
                </div>
            </div>

            <transition name="page">
                <div v-if="mobileOpen" class="mobile-menu d-lg-none">
                    <form class="shop-search mb-3" @submit.prevent="submitSearch">
                        <i class="bi bi-search"></i>
                        <input v-model="search" type="search" class="form-control" placeholder="Search products…" aria-label="Search products">
                    </form>

                    <router-link :to="{ name: 'home' }">Home</router-link>
                    <router-link :to="{ name: 'products' }">Shop all</router-link>
                    <router-link :to="{ name: 'products', query: { featured: 1 } }">Featured</router-link>
                    <router-link :to="{ name: 'brands' }">Brands</router-link>
                    <router-link :to="{ name: 'categories' }">Categories</router-link>
                    <router-link :to="{ name: 'cart' }">Cart ({{ cartCount }})</router-link>
                    <router-link :to="{ name: 'wishlist' }">Wishlist ({{ wishlistCount }})</router-link>
                    <router-link v-if="auth.isAuthenticated.value" :to="{ name: 'orders' }">My orders</router-link>
                    <router-link v-if="!auth.isAuthenticated.value" :to="{ name: 'login' }" class="text-brand">
                        Login with OTP
                    </router-link>
                </div>
            </transition>
        </nav>

        <div v-if="accountOpen" class="account-backdrop" @click="accountOpen = false"></div>
    </header>
</template>
