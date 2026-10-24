<script setup>
defineProps({
    title: { type: String, default: '' },
    wide: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);
</script>

<template>
    <div class="admin-modal-backdrop" @click.self="emit('close')">
        <div class="admin-modal" :style="wide ? 'width: min(880px, 100%)' : ''">
            <div class="admin-modal__head">
                <h2 class="h6 mb-0">{{ title }}</h2>
                <button class="btn btn-sm btn-light" type="button" aria-label="Close" @click="emit('close')">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="admin-modal__body">
                <slot></slot>
            </div>

            <div class="admin-modal__foot">
                <slot name="footer"></slot>
            </div>
        </div>
    </div>
</template>
