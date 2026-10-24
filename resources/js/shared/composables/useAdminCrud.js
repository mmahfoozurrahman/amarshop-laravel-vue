import { reactive, ref } from 'vue';
import { createRecord, deleteRecord, fetchResource, saveRecord, updateRecord } from '@/shared/services/admin';
import { errorMessage, validationErrors } from '@/shared/utils/format';
import { useToast } from './useToast';

/**
 * Small CRUD helper for the admin screens (no Pinia, just reactive state).
 */
export function useAdminCrud(resource, { emptyForm, perPage = 12 } = {}) {
    const toast = useToast();

    const items = ref([]);
    const meta = ref({ current_page: 1, last_page: 1, total: 0 });
    const loading = ref(true);
    const saving = ref(false);
    const search = ref('');
    const modalOpen = ref(false);
    const editing = ref(null);
    const form = reactive({ ...emptyForm });
    const errors = ref({});

    async function load(page = 1, params = {}) {
        loading.value = true;

        try {
            const response = await fetchResource(resource, {
                page,
                per_page: perPage,
                q: search.value || undefined,
                ...params,
            });

            items.value = response.data;
            meta.value = response.meta;
        } catch (exception) {
            toast.error(errorMessage(exception, 'Could not load records.'));
        } finally {
            loading.value = false;
        }
    }

    function resetForm(values = {}) {
        Object.keys(form).forEach((key) => delete form[key]);
        Object.assign(form, emptyForm, values);
        errors.value = {};
    }

    function openCreate() {
        editing.value = null;
        resetForm();
        modalOpen.value = true;
    }

    function openEdit(record, values) {
        editing.value = record;
        resetForm(values ?? record);
        modalOpen.value = true;
    }

    function closeModal() {
        modalOpen.value = false;
        editing.value = null;
    }

    /**
     * @param {FormData|object} payload
     */
    async function save(payload) {
        saving.value = true;
        errors.value = {};

        try {
            const isFormData = payload instanceof FormData;
            const response = editing.value
                ? (isFormData
                    ? await saveRecord(resource, editing.value.id, payload)
                    : await updateRecord(resource, editing.value.id, payload))
                : (isFormData
                    ? await saveRecord(resource, null, payload)
                    : await createRecord(resource, payload));

            toast.success(response.message);
            modalOpen.value = false;
            editing.value = null;
            await load(meta.value.current_page ?? 1);

            return true;
        } catch (exception) {
            errors.value = validationErrors(exception);
            toast.error(errorMessage(exception, 'Please check the form and try again.'));

            return false;
        } finally {
            saving.value = false;
        }
    }

    async function remove(record) {
        try {
            const response = await deleteRecord(resource, record.id);
            toast.success(response.message);
            await load(meta.value.current_page ?? 1);

            return true;
        } catch (exception) {
            toast.error(errorMessage(exception, 'Could not delete this record.'));

            return false;
        }
    }

    function onSearch() {
        load(1);
    }

    return {
        items,
        meta,
        loading,
        saving,
        search,
        modalOpen,
        editing,
        form,
        errors,
        load,
        resetForm,
        openCreate,
        openEdit,
        closeModal,
        save,
        remove,
        onSearch,
    };
}
