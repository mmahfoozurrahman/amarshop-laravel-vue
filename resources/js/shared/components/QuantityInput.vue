<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: Number, default: 1 },
    min: { type: Number, default: 1 },
    max: { type: Number, default: 20 },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const value = computed(() => Number(props.modelValue) || props.min);

function change(delta) {
    if (props.disabled) return;

    const next = Math.min(props.max, Math.max(props.min, value.value + delta));

    if (next !== value.value) {
        emit('update:modelValue', next);
    }
}
</script>

<template>
    <div class="qty-input">
        <button type="button" :disabled="disabled || value <= min" aria-label="Decrease quantity" @click="change(-1)">
            <i class="bi bi-dash"></i>
        </button>
        <span>{{ value }}</span>
        <button type="button" :disabled="disabled || value >= max" aria-label="Increase quantity" @click="change(1)">
            <i class="bi bi-plus"></i>
        </button>
    </div>
</template>
