<script setup>
defineProps({
    items: { type: Array, default: () => [] },
});
</script>

<template>
    <nav v-if="items.length" aria-label="breadcrumb">
        <ol class="breadcrumb se-breadcrumb">
            <li class="breadcrumb-item">
                <router-link :to="{ name: 'home' }">Home</router-link>
            </li>
            <li
                v-for="(item, index) in items"
                :key="`${item.label}-${index}`"
                class="breadcrumb-item"
                :class="{ active: index === items.length - 1 }"
                :aria-current="index === items.length - 1 ? 'page' : undefined"
            >
                <router-link v-if="item.to && index !== items.length - 1" :to="item.to">{{ item.label }}</router-link>
                <span v-else>{{ item.label }}</span>
            </li>
        </ol>
    </nav>
</template>
