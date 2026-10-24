<script setup>
import { computed } from 'vue';
import { statusLabel, statusTone } from '@/shared/utils/format';

const props = defineProps({
    status: { type: String, required: true },
});

const tone = computed(() => statusTone(props.status));
const label = computed(() => statusLabel(props.status));
</script>

<template>
    <span class="pill" :class="`pill-${tone}`">{{ label }}</span>
</template>
