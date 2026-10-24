<script setup>
import { computed } from 'vue';

const props = defineProps({
    meta: { type: Object, required: true },
});

const emit = defineEmits(['change']);

const current = computed(() => Number(props.meta.current_page ?? 1));
const last = computed(() => Number(props.meta.last_page ?? 1));

const pages = computed(() => {
    const total = last.value;
    const start = Math.max(1, current.value - 2);
    const end = Math.min(total, start + 4);
    const list = [];

    for (let page = Math.max(1, end - 4); page <= end; page++) {
        list.push(page);
    }

    return list;
});

function go(page) {
    if (page < 1 || page > last.value || page === current.value) return;

    emit('change', page);
}
</script>

<template>
    <nav v-if="last > 1" class="d-flex justify-content-center mt-4" aria-label="Pagination">
        <ul class="pagination se-pagination mb-0">
            <li class="page-item" :class="{ disabled: current === 1 }">
                <button class="page-link" type="button" aria-label="Previous page" @click="go(current - 1)">
                    <i class="bi bi-chevron-left"></i>
                </button>
            </li>

            <li v-for="page in pages" :key="page" class="page-item" :class="{ active: page === current }">
                <button class="page-link" type="button" @click="go(page)">{{ page }}</button>
            </li>

            <li class="page-item" :class="{ disabled: current === last }">
                <button class="page-link" type="button" aria-label="Next page" @click="go(current + 1)">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </li>
        </ul>
    </nav>
</template>
