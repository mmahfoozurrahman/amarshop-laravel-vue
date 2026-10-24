<script setup>
import { useToast } from '@/shared/composables/useToast';

const { toasts, dismiss } = useToast();

const icons = {
    success: 'bi-check-circle-fill',
    danger: 'bi-exclamation-octagon-fill',
    warning: 'bi-exclamation-triangle-fill',
    info: 'bi-info-circle-fill',
};
</script>

<template>
    <div class="toast-stack">
        <transition-group name="page">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="toast-item"
                :class="`is-${toast.type}`"
                role="status"
            >
                <i class="bi" :class="icons[toast.type] ?? icons.info"></i>
                <span class="flex-grow-1">{{ toast.message }}</span>
                <button type="button" class="toast-close" aria-label="Dismiss" @click="dismiss(toast.id)">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </transition-group>
    </div>
</template>
