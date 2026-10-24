export function formatMoney(value) {
    const amount = Number(value ?? 0);

    return `৳ ${amount.toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}`;
}

export function formatDate(value, withTime = false) {
    if (!value) return '—';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return '—';

    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
    });
}

const STATUS_LABELS = {
    pending: 'Pending',
    processing: 'Processing',
    shipped: 'Shipped',
    completed: 'Completed',
    cancelled: 'Cancelled',
    unpaid: 'Unpaid',
    paid: 'Paid',
    failed: 'Failed',
    refunded: 'Refunded',
};

export function statusLabel(status) {
    return STATUS_LABELS[status] ?? status;
}

export function statusTone(status) {
    return {
        completed: 'success',
        paid: 'success',
        processing: 'info',
        shipped: 'info',
        pending: 'warning',
        unpaid: 'warning',
        failed: 'danger',
        cancelled: 'danger',
        refunded: 'muted',
    }[status] ?? 'muted';
}

export function errorMessage(error, fallback = 'Something went wrong. Please try again.') {
    return error?.response?.data?.message || fallback;
}

export function validationErrors(error) {
    return error?.response?.data?.errors ?? {};
}

export function firstError(errors) {
    const first = Object.values(errors ?? {})[0];

    return Array.isArray(first) ? first[0] : null;
}
