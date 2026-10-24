<script setup>
// Simple storefront 404.
</script>

<template>
    <div class="container py-5 text-center">
        <div class="display-1 fw-bold text-brand">404</div>
        <h1 class="h3 mb-2">We could not find that page</h1>
        <p class="se-muted mb-4">The link may be broken, or the product might no longer be available.</p>

        <div class="d-flex justify-content-center gap-2">
            <router-link :to="{ name: 'home' }" class="btn btn-brand">Back to home</router-link>
            <router-link :to="{ name: 'products' }" class="btn btn-outline-brand">Browse products</router-link>
        </div>
    </div>
</template>
