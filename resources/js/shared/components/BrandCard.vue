<script setup>
defineProps({
    brand: { type: Object, required: true },
});
</script>

<template>
    <router-link :to="{ name: 'brand.show', params: { slug: brand.slug } }" class="brand-card">
        <img :src="brand.logo" :alt="brand.name" loading="lazy">
        <div class="brand-card__meta">
            <strong>{{ brand.name }}</strong>
            <small>{{ brand.products_count ?? 0 }} products</small>
        </div>
    </router-link>
</template>
