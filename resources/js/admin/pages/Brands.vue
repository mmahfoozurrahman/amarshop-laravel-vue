<script setup>
import { onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AdminModal from '@/admin/components/AdminModal.vue';
import Pagination from '@/shared/components/Pagination.vue';
import { useAdminCrud } from '@/shared/composables/useAdminCrud';
import { useConfirm } from '@/shared/composables/useConfirm';

const crud = useAdminCrud('brands', {
    emptyForm: { name: '', description: '', is_active: true, logo: null },
    perPage: 10,
});

const {
    items, meta, loading, saving, search, modalOpen, editing, form, errors,
    load, openCreate, openEdit, closeModal, save, remove,
} = crud;

const confirm = useConfirm();
const route = useRoute();
const router = useRouter();

/**
 * The search term lives in the URL query so a reload (F5) keeps the filtered
 * list — same pattern as the other admin list pages.
 */
function syncFromQuery() {
    search.value = route.query.q ?? '';
}

function applyFilters(page = 1) {
    const query = {};

    if (search.value.trim()) query.q = search.value.trim();
    if (page > 1) query.page = page;

    const unchanged = router.resolve({ query }).fullPath === route.fullPath;

    if (unchanged) {
        load(page); // same query pressed twice → refresh anyway

        return;
    }

    router.replace({ query });
}

function payload() {
    const data = new FormData();
    data.append('name', form.name ?? '');
    data.append('description', form.description ?? '');
    data.append('is_active', form.is_active ? '1' : '0');

    if (form.logo instanceof File) {
        data.append('logo', form.logo);
    }

    return data;
}

async function submit() {
    const wasCreate = !editing.value;
    const saved = await save(payload());

    if (saved && wasCreate) {
        // A freshly created row would be hidden by an active search: clear it.
        search.value = '';
        applyFilters(1);
    }
}

function onFile(event) {
    form.logo = event.target.files?.[0] ?? null;
}

async function destroy(brand) {
    const confirmed = await confirm.ask({
        title: 'Delete brand?',
        message: `Deleting “${brand.name}” will detach it from its products. Continue?`,
        confirmText: 'Delete',
    });

    if (confirmed) {
        await remove(brand);
    }
}

watch(() => route.query, () => {
    syncFromQuery();
    load(Number(route.query.page ?? 1));
});

onMounted(() => {
    syncFromQuery();
    load(Number(route.query.page ?? 1));
});
</script>

<template>
    <div>
        <div class="admin-card">
            <div class="admin-card__head">
                <div class="admin-toolbar">
                    <input v-model="search" type="search" class="form-control" placeholder="Search brands…" @keyup.enter="applyFilters(1)">
                    <button class="btn btn-light" type="button" @click="applyFilters(1)"><i class="bi bi-search"></i></button>
                </div>

                <button class="btn btn-brand" type="button" @click="openCreate">
                    <i class="bi bi-plus-lg me-1"></i> New brand
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-shop mb-0">
                    <thead>
                        <tr>
                            <th>Brand</th>
                            <th>Slug</th>
                            <th>Products</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="5" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</td>
                        </tr>
                        <tr v-for="brand in items" v-else :key="brand.id">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img :src="brand.logo" :alt="brand.name" class="table-thumb">
                                    <div>
                                        <span class="fw-semibold d-block">{{ brand.name }}</span>
                                        <small class="se-muted d-inline-block text-truncate" style="max-width: 220px">{{ brand.description }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="se-muted">{{ brand.slug }}</td>
                            <td>{{ brand.products_count ?? 0 }}</td>
                            <td>
                                <span class="pill" :class="brand.is_active ? 'pill-success' : 'pill-muted'">
                                    {{ brand.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-light" type="button" @click="openEdit(brand)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-light text-danger ms-1" type="button" @click="destroy(brand)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!loading && !items.length">
                            <td colspan="5" class="text-center se-muted py-4">No brands found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-3">
                <Pagination :meta="meta" @change="applyFilters" />
            </div>
        </div>

        <AdminModal v-if="modalOpen" :title="editing ? 'Edit brand' : 'New brand'" @close="closeModal">
            <form id="brand-form" @submit.prevent="submit">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="brand-name">Name</label>
                        <input id="brand-name" v-model="form.name" type="text" class="form-control" required maxlength="120">
                        <div v-if="errors.name" class="text-danger small mt-1">{{ errors.name[0] }}</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="brand-logo">Logo</label>
                        <input id="brand-logo" type="file" accept="image/*" class="form-control" @change="onFile">
                        <div v-if="errors.logo" class="text-danger small mt-1">{{ errors.logo[0] }}</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold" for="brand-description">Description</label>
                        <textarea id="brand-description" v-model="form.description" class="form-control" rows="3" maxlength="2000"></textarea>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input id="brand-active" v-model="form.is_active" class="form-check-input" type="checkbox">
                            <label class="form-check-label" for="brand-active">Active (visible on the storefront)</label>
                        </div>
                    </div>
                </div>
            </form>

            <template #footer>
                <button class="btn btn-light" type="button" @click="closeModal">Cancel</button>
                <button class="btn btn-brand" type="submit" form="brand-form" :disabled="saving">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                    {{ editing ? 'Save changes' : 'Create brand' }}
                </button>
            </template>
        </AdminModal>
    </div>
</template>
