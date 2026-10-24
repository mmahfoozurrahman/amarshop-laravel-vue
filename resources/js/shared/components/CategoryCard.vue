<script setup>
defineProps({
    category: { type: Object, required: true },
});
</script>

<template>
    <router-link :to="{ name: 'category.show', params: { slug: category.slug } }" class="category-card">
        <img :src="category.image" :alt="category.name" loading="lazy">
        <div class="category-card__overlay">
            <strong>{{ category.name }}</strong>
            <small>{{ category.products_count ?? 0 }} products</small>
        </div>
    </router-link>
</template>
