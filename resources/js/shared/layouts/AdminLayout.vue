<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAdminAuth } from '@/shared/composables/useAdminAuth';

const route = useRoute();
const router = useRouter();
const adminAuth = useAdminAuth();

const sidebarOpen = ref(false);

const navigation = [
    { name: 'admin.dashboard', label: 'Dashboard', icon: 'bi-speedometer2' },
    { name: 'admin.products', label: 'Products', icon: 'bi-box-seam' },
    { name: 'admin.categories', label: 'Categories', icon: 'bi-grid' },
    { name: 'admin.brands', label: 'Brands', icon: 'bi-award' },
    { name: 'admin.sliders', label: 'Sliders', icon: 'bi-images' },
    { name: 'admin.orders', label: 'Orders', icon: 'bi-receipt' },
    { name: 'admin.users', label: 'Customers', icon: 'bi-people' },
];

const initials = computed(() => {
    const name = adminAuth.state.user?.name ?? 'Admin';

    return name
        .split(' ')
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
});

const isActive = (name) => route.name === name || route.name?.toString().startsWith(`${name}.`);

watch(() => route.fullPath, () => {
    sidebarOpen.value = false;
});

async function logout() {
    await adminAuth.logout();
    router.push({ name: 'admin.login' });
}
</script>

<template>
    <div class="admin-shell">
        <aside class="admin-sidebar" :class="{ 'is-open': sidebarOpen }">
            <div class="admin-brand">
                <i class="bi bi-bag-heart-fill"></i>
                <div>
                    <strong>AmarShop</strong>
                    <small>Admin panel</small>
                </div>
            </div>

            <nav class="admin-nav">
                <router-link
                    v-for="item in navigation"
                    :key="item.name"
                    :to="{ name: item.name }"
                    class="admin-nav__link"
                    :class="{ 'is-active': isActive(item.name) }"
                >
                    <i class="bi" :class="item.icon"></i>
                    <span>{{ item.label }}</span>
                </router-link>
            </nav>

            <div class="admin-sidebar__footer">
                <!-- Storefront links open in a new tab on purpose: the admin panel (and its
                     URL-query filters) must stay exactly as it is in this tab. -->
                <router-link :to="{ name: 'home' }" target="_blank" rel="noopener" class="admin-nav__link">
                    <i class="bi bi-box-arrow-up-right"></i>
                    <span>View storefront</span>
                </router-link>
            </div>
        </aside>

        <div v-if="sidebarOpen" class="admin-backdrop" @click="sidebarOpen = false"></div>

        <div class="admin-main">
            <header class="admin-topbar">
                <button class="btn btn-light d-lg-none" type="button" @click="sidebarOpen = true">
                    <i class="bi bi-list"></i>
                </button>

                <div class="admin-topbar__title">
                    <h1>{{ route.meta.title ?? 'Admin' }}</h1>
                    <p>Manage your store from one place.</p>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="admin-avatar">{{ initials }}</span>
                    <div class="d-none d-sm-block">
                        <strong class="d-block small">{{ adminAuth.state.user?.name }}</strong>
                        <small class="text-muted">{{ adminAuth.state.user?.email }}</small>
                    </div>
                    <button class="btn btn-outline-secondary btn-sm ms-2" type="button" @click="logout">
                        <i class="bi bi-box-arrow-right me-1"></i> Logout
                    </button>
                </div>
            </header>

            <div class="admin-content">
                <router-view />
            </div>
        </div>
    </div>
</template>
