import { createRouter, createWebHistory } from 'vue-router';
import { useAdminAuth } from '@/shared/composables/useAdminAuth';
import { useAuth } from '@/shared/composables/useAuth';
import adminRoutes from './admin';

const ShopLayout = () => import('@/shared/layouts/ShopLayout.vue');

const routes = [
    {
        path: '/',
        component: ShopLayout,
        children: [
            { path: '', name: 'home', component: () => import('@/guest/pages/Home.vue'), meta: { title: 'Home' } },
            { path: 'products', name: 'products', component: () => import('@/guest/pages/Products.vue'), meta: { title: 'Shop' } },
            { path: 'products/:slug', name: 'product.show', component: () => import('@/guest/pages/ProductDetail.vue'), meta: { title: 'Product' } },
            { path: 'brands', name: 'brands', component: () => import('@/guest/pages/Brands.vue'), meta: { title: 'Brands' } },
            { path: 'brands/:slug', name: 'brand.show', component: () => import('@/guest/pages/BrandProducts.vue'), meta: { title: 'Brand' } },
            { path: 'categories', name: 'categories', component: () => import('@/guest/pages/Categories.vue'), meta: { title: 'Categories' } },
            { path: 'categories/:slug', name: 'category.show', component: () => import('@/guest/pages/CategoryProducts.vue'), meta: { title: 'Category' } },
            { path: 'cart', name: 'cart', component: () => import('@/guest/pages/Cart.vue'), meta: { title: 'Cart' } },
            { path: 'login', name: 'login', component: () => import('@/user/pages/LoginOtp.vue'), meta: { title: 'Sign in' } },
            { path: 'profile', name: 'profile', component: () => import('@/user/pages/Profile.vue'), meta: { title: 'Profile', requiresAuth: true } },
            { path: 'wishlist', name: 'wishlist', component: () => import('@/user/pages/Wishlist.vue'), meta: { title: 'Wishlist', requiresAuth: true } },
            { path: 'checkout', name: 'checkout', component: () => import('@/user/pages/Checkout.vue'), meta: { title: 'Checkout', requiresAuth: true } },
            { path: 'orders', name: 'orders', component: () => import('@/user/pages/Orders.vue'), meta: { title: 'My orders', requiresAuth: true } },
            { path: 'orders/:orderNumber', name: 'order.show', component: () => import('@/user/pages/OrderDetail.vue'), meta: { title: 'Order details', requiresAuth: true } },
            { path: 'payment/demo/:orderNumber', name: 'payment.demo', component: () => import('@/guest/pages/PaymentDemo.vue'), meta: { title: 'Demo payment', requiresAuth: true } },
        ],
    },
    ...adminRoutes,
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('@/guest/pages/NotFound.vue'),
        meta: { title: 'Page not found' },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: (to, from, savedPosition) => savedPosition ?? { top: 0 },
});

const appName = import.meta.env.VITE_APP_NAME ?? 'AmarShop';

router.beforeEach(async (to) => {
    const auth = useAuth();
    const adminAuth = useAdminAuth();

    if (to.meta.requiresAdmin) {
        if (!adminAuth.isAdminAuthenticated.value) {
            return { name: 'admin.login', query: { redirect: to.fullPath } };
        }

        if (!adminAuth.state.user) {
            try {
                await adminAuth.fetchMe();
            } catch {
                adminAuth.clearSession();

                return { name: 'admin.login', query: { redirect: to.fullPath } };
            }
        }

        if (adminAuth.state.user?.role !== 'admin') {
            adminAuth.clearSession();

            return { name: 'admin.login' };
        }
    }

    if (to.name === 'admin.login' && adminAuth.isAdminAuthenticated.value) {
        return { name: 'admin.dashboard' };
    }

    if (to.meta.requiresAuth) {
        if (!auth.isAuthenticated.value) {
            return { name: 'login', query: { redirect: to.fullPath } };
        }

        if (!auth.state.user) {
            try {
                await auth.fetchMe();
            } catch {
                auth.clearSession();

                return { name: 'login', query: { redirect: to.fullPath } };
            }
        }
    }

    if (to.name === 'login' && auth.isAuthenticated.value) {
        return { name: 'home' };
    }

    return true;
});

router.afterEach((to) => {
    const title = to.meta?.title;
    document.title = title ? `${title} · ${appName}` : appName;
});

export default router;

