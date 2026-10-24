import { reactive } from 'vue';

const state = reactive({
    open: false,
    title: 'Are you sure?',
    message: '',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    variant: 'danger',
});

let resolver = null;

export function useConfirm() {
    function ask(options = {}) {
        Object.assign(state, {
            open: true,
            title: options.title ?? 'Are you sure?',
            message: options.message ?? '',
            confirmText: options.confirmText ?? 'Confirm',
            cancelText: options.cancelText ?? 'Cancel',
            variant: options.variant ?? 'danger',
        });

        return new Promise((resolve) => {
            resolver = resolve;
        });
    }

    function approve() {
        state.open = false;
        resolver?.(true);
        resolver = null;
    }

    function cancel() {
        state.open = false;
        resolver?.(false);
        resolver = null;
    }

    return { state, ask, approve, cancel };
}
