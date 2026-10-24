<script setup>
defineProps({
    icon: { type: String, default: 'bi-inbox' },
    title: { type: String, default: 'Nothing here yet' },
    message: { type: String, default: '' },
});
</script>

<template>
    <div class="empty-state se-card">
        <div class="empty-state__icon">
            <i class="bi" :class="icon"></i>
        </div>
        <h3 class="h5 mb-1">{{ title }}</h3>
        <p class="se-muted mb-3">{{ message }}</p>
        <slot></slot>
    </div>
</template>
