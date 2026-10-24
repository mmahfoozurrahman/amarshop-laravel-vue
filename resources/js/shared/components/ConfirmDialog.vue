<script setup>
import { useConfirm } from '@/shared/composables/useConfirm';

const { state, approve, cancel } = useConfirm();
</script>

<template>
    <transition name="page">
        <div v-if="state.open" class="confirm-backdrop" @click.self="cancel">
            <div class="confirm-card" role="dialog" aria-modal="true">
                <div class="confirm-icon" :class="`is-${state.variant}`">
                    <i class="bi" :class="state.variant === 'danger' ? 'bi-exclamation-triangle' : 'bi-question-circle'"></i>
                </div>

                <h2 class="h5 mb-1">{{ state.title }}</h2>
                <p class="se-muted mb-4">{{ state.message }}</p>

                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-light" type="button" @click="cancel">{{ state.cancelText }}</button>
                    <button
                        class="btn"
                        :class="state.variant === 'danger' ? 'btn-danger' : 'btn-brand'"
                        type="button"
                        @click="approve"
                    >
                        {{ state.confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>
