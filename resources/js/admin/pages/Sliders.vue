<script setup>
import { onMounted } from 'vue';
import AdminModal from '@/admin/components/AdminModal.vue';
import Pagination from '@/shared/components/Pagination.vue';
import { useAdminCrud } from '@/shared/composables/useAdminCrud';
import { useConfirm } from '@/shared/composables/useConfirm';

const crud = useAdminCrud('sliders', {
    emptyForm: {
        title: '',
        subtitle: '',
        button_text: 'Shop now',
        link_url: '/products',
        sort_order: 0,
        is_active: true,
        image: null,
    },
    perPage: 20,
});

const {
    items, meta, loading, saving, modalOpen, editing, form, errors,
    load, openCreate, openEdit, closeModal, save, remove,
} = crud;

const confirm = useConfirm();

function payload() {
    const data = new FormData();
    data.append('title', form.title ?? '');
    data.append('subtitle', form.subtitle ?? '');
    data.append('button_text', form.button_text ?? '');
    data.append('link_url', form.link_url ?? '');
    data.append('sort_order', form.sort_order ?? 0);
    data.append('is_active', form.is_active ? '1' : '0');

    if (form.image instanceof File) {
        data.append('image', form.image);
    }

    return data;
}

async function submit() {
    await save(payload());
}

function onFile(event) {
    form.image = event.target.files?.[0] ?? null;
}

async function destroy(slider) {
    const confirmed = await confirm.ask({
        title: 'Delete slider?',
        message: `Delete the “${slider.title}” hero slide?`,
        confirmText: 'Delete',
    });

    if (confirmed) {
        await remove(slider);
    }
}

onMounted(() => load());
</script>

<template>
    <div>
        <div class="admin-card">
            <div class="admin-card__head">
                <h2 class="h6 mb-0">Home hero slides</h2>
                <button class="btn btn-brand" type="button" @click="openCreate">
                    <i class="bi bi-plus-lg me-1"></i> New slider
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-shop mb-0">
                    <thead>
                        <tr>
                            <th>Slide</th>
                            <th>Link</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="5" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</td>
                        </tr>
                        <tr v-for="slider in items" v-else :key="slider.id">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img :src="slider.image" :alt="slider.title" class="table-thumb" style="width: 84px;">
                                    <div>
                                        <span class="fw-semibold d-block">{{ slider.title }}</span>
                                        <small class="se-muted">{{ slider.subtitle }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="se-muted small">{{ slider.link_url || '—' }}</td>
                            <td>{{ slider.sort_order }}</td>
                            <td>
                                <span class="pill" :class="slider.is_active ? 'pill-success' : 'pill-muted'">
                                    {{ slider.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-light" type="button" @click="openEdit(slider)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-light text-danger ms-1" type="button" @click="destroy(slider)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!loading && !items.length">
                            <td colspan="5" class="text-center se-muted py-4">No sliders yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-3"><Pagination :meta="meta" @change="load" /></div>
        </div>

        <AdminModal v-if="modalOpen" :title="editing ? 'Edit slider' : 'New slider'" @close="closeModal">
            <form id="slider-form" @submit.prevent="submit">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold" for="slider-title">Title</label>
                        <input id="slider-title" v-model="form.title" type="text" class="form-control" required maxlength="150">
                        <div v-if="errors.title" class="text-danger small mt-1">{{ errors.title[0] }}</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="slider-order">Sort order</label>
                        <input id="slider-order" v-model="form.sort_order" type="number" min="0" class="form-control">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold" for="slider-subtitle">Subtitle</label>
                        <input id="slider-subtitle" v-model="form.subtitle" type="text" class="form-control" maxlength="255">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="slider-button">Button text</label>
                        <input id="slider-button" v-model="form.button_text" type="text" class="form-control" maxlength="60">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="slider-link">Link</label>
                        <input id="slider-link" v-model="form.link_url" type="text" class="form-control" maxlength="255" placeholder="/products">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold" for="slider-image">Background image</label>
                        <input id="slider-image" type="file" accept="image/*" class="form-control" @change="onFile">
                        <small class="se-muted d-block mt-1">Recommended: 1600 × 600 px (8:3). The hero keeps this ratio, so other sizes are cropped with <code>object-fit: cover</code>.</small>
                        <div v-if="errors.image" class="text-danger small mt-1">{{ errors.image[0] }}</div>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input id="slider-active" v-model="form.is_active" class="form-check-input" type="checkbox">
                            <label class="form-check-label" for="slider-active">Active</label>
                        </div>
                    </div>
                </div>
            </form>

            <template #footer>
                <button class="btn btn-light" type="button" @click="closeModal">Cancel</button>
                <button class="btn btn-brand" type="submit" form="slider-form" :disabled="saving">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                    {{ editing ? 'Save changes' : 'Create slider' }}
                </button>
            </template>
        </AdminModal>
    </div>
</template>
