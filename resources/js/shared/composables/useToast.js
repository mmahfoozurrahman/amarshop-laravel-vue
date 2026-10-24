import { ref } from 'vue';

let nextId = 1;
const toasts = ref([]);

function dismiss(id) {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
}

function push(message, type = 'success', timeout = 3500) {
    const id = nextId++;
    toasts.value = [...toasts.value, { id, message, type }];

    if (timeout > 0) {
        window.setTimeout(() => dismiss(id), timeout);
    }

    return id;
}

export function useToast() {
    return {
        toasts,
        dismiss,
        push,
        success: (message) => push(message, 'success'),
        error: (message) => push(message, 'danger', 5000),
        info: (message) => push(message, 'info'),
        warning: (message) => push(message, 'warning'),
    };
}
