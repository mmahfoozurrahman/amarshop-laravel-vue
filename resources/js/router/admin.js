const AdminLayout = () => import('@/shared/layouts/AdminLayout.vue');

export default [
    {
        path: '/admin/login',
        name: 'admin.login',
        component: () => import('@/admin/pages/Login.vue'),
        meta: { title: 'Admin sign in' },
    },
    {
        path: '/admin',
        component: AdminLayout,
        meta: { requiresAdmin: true, adminArea: true },
        children: [
            {
                path: '',
                name: 'admin.dashboard',
                component: () => import('@/admin/pages/Dashboard.vue'),
                meta: { title: 'Dashboard' },
            },
            {
                path: 'brands',
                name: 'admin.brands',
                component: () => import('@/admin/pages/Brands.vue'),
                meta: { title: 'Brands' },
            },
            {
                path: 'categories',
                name: 'admin.categories',
                component: () => import('@/admin/pages/Categories.vue'),
                meta: { title: 'Categories' },
            },
            {
                path: 'products',
                name: 'admin.products',
                component: () => import('@/admin/pages/Products.vue'),
                meta: { title: 'Products' },
            },
            {
                path: 'sliders',
                name: 'admin.sliders',
                component: () => import('@/admin/pages/Sliders.vue'),
                meta: { title: 'Sliders' },
            },
            {
                path: 'orders',
                name: 'admin.orders',
                component: () => import('@/admin/pages/Orders.vue'),
                meta: { title: 'Orders' },
            },
            {
                path: 'orders/:id',
                name: 'admin.orders.show',
                component: () => import('@/admin/pages/OrderShow.vue'),
                props: true,
                meta: { title: 'Order details' },
            },
            {
                path: 'users',
                name: 'admin.users',
                component: () => import('@/admin/pages/Users.vue'),
                meta: { title: 'Customers' },
            },
        ],
    },
];
