<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    linkTo: { type: [Object, String], default: null },
    linkText: { type: String, default: 'View all' },
});
</script>

<template>
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h2 class="se-section-title mb-1">{{ title }}</h2>
            <p v-if="subtitle" class="se-muted mb-0">{{ subtitle }}</p>
        </div>

        <router-link v-if="linkTo" :to="linkTo" class="btn btn-outline-brand btn-sm">
            {{ linkText }} <i class="bi bi-arrow-right ms-1"></i>
        </router-link>
    </div>
</template>
