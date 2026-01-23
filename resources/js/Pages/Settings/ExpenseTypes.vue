<template>
    <AppLayout title="Expense Types">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Expense Types
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Header with Add Button -->
                    <div class="p-6 sm:px-8 bg-white border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">
                                Manage Expense Types
                            </h3>
                            <button
                                @click="openCreateModal"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Add Expense Type
                            </button>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div v-if="store.loading" class="p-6 text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                        <p class="mt-2 text-sm text-gray-500">Loading expense types...</p>
                    </div>

                    <!-- Table -->
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Display Name
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Category
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Slug
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Level
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        VAT Rate
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="type in store.types"
                                    :key="type.id"
                                >
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                    >
                                        {{ type.display_name }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                    >
                                        {{ getCategoryName(type.expense_category_id) }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                    >
                                        <code class="bg-gray-100 px-2 py-1 rounded text-xs">
                                            {{ type.slug }}
                                        </code>
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                    >
                                        <span
                                            :class="
                                                type.is_order_level
                                                    ? 'bg-blue-100 text-blue-800'
                                                    : 'bg-purple-100 text-purple-800'
                                            "
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        >
                                            {{ type.is_order_level ? "Order" : "Item" }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                    >
                                        {{ type.default_vat_rate }}%
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                    >
                                        <button
                                            @click="openEditModal(type)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-4"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="confirmDelete(type)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!store.hasTypes">
                                    <td
                                        colspan="6"
                                        class="px-6 py-4 text-center text-sm text-gray-500"
                                    >
                                        No expense types found. Click "Add Expense Type" to create one.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div
            v-if="showModal"
            class="fixed z-10 inset-0 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div
                class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
            >
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    aria-hidden="true"
                    @click="closeModal"
                ></div>
                <span
                    class="hidden sm:inline-block sm:align-middle sm:h-screen"
                    aria-hidden="true"
                    >&#8203;</span
                >

                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                >
                    <form @submit.prevent="submitForm">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3
                                class="text-lg leading-6 font-medium text-gray-900 mb-4"
                                id="modal-title"
                            >
                                {{ isEditing ? "Edit" : "Create" }} Expense Type
                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <label
                                        for="expense_category_id"
                                        class="block text-sm font-medium text-gray-700"
                                        >Expense Category *</label
                                    >
                                    <select
                                        id="expense_category_id"
                                        v-model="form.expense_category_id"
                                        required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    >
                                        <option value="">Select a category</option>
                                        <option
                                            v-for="category in store.categories"
                                            :key="category.id"
                                            :value="category.id"
                                        >
                                            {{ category.name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label
                                        for="display_name"
                                        class="block text-sm font-medium text-gray-700"
                                        >Display Name *</label
                                    >
                                    <input
                                        type="text"
                                        id="display_name"
                                        v-model="form.display_name"
                                        @input="generateSlug"
                                        required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="e.g., Transportation Cost"
                                    />
                                </div>

                                <div>
                                    <label
                                        for="slug"
                                        class="block text-sm font-medium text-gray-700"
                                        >Slug *</label
                                    >
                                    <input
                                        type="text"
                                        id="slug"
                                        v-model="form.slug"
                                        required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="e.g., transportation-cost"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">
                                        Auto-generated from display name. Use lowercase letters, numbers, and hyphens only.
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="default_vat_rate"
                                        class="block text-sm font-medium text-gray-700"
                                        >Default VAT Rate (%) *</label
                                    >
                                    <input
                                        type="number"
                                        id="default_vat_rate"
                                        v-model.number="form.default_vat_rate"
                                        required
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="e.g., 16.00"
                                    />
                                </div>

                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        id="is_order_level"
                                        v-model="form.is_order_level"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                    <label
                                        for="is_order_level"
                                        class="ml-2 block text-sm text-gray-900"
                                    >
                                        Order Level Expense
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500 ml-6">
                                    Check if this expense applies to the entire order rather than individual items.
                                </p>
                            </div>
                        </div>

                        <div
                            class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse"
                        >
                            <button
                                type="submit"
                                :disabled="submitting"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="!submitting">
                                    {{ isEditing ? "Update" : "Create" }}
                                </span>
                                <span v-else>Processing...</span>
                            </button>
                            <button
                                type="button"
                                @click="closeModal"
                                :disabled="submitting"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="showDeleteModal"
            class="fixed z-10 inset-0 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div
                class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
            >
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    aria-hidden="true"
                    @click="showDeleteModal = false"
                ></div>
                <span
                    class="hidden sm:inline-block sm:align-middle sm:h-screen"
                    aria-hidden="true"
                    >&#8203;</span
                >

                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                >
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10"
                            >
                                <svg
                                    class="h-6 w-6 text-red-600"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                    />
                                </svg>
                            </div>
                            <div
                                class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left"
                            >
                                <h3
                                    class="text-lg leading-6 font-medium text-gray-900"
                                >
                                    Delete Expense Type
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Are you sure you want to delete "{{
                                            typeToDelete?.display_name
                                        }}"? This action cannot be undone.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse"
                    >
                        <button
                            type="button"
                            @click="deleteType"
                            :disabled="deleting"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="!deleting">Delete</span>
                            <span v-else>Deleting...</span>
                        </button>
                        <button
                            type="button"
                            @click="showDeleteModal = false"
                            :disabled="deleting"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { useExpenseTypeStore } from "@/stores/expenseTypeStore";
import AppLayout from "@/Layouts/AppLayout.vue";

// Store
const store = useExpenseTypeStore();

// State
const showModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const submitting = ref(false);
const deleting = ref(false);
const typeToDelete = ref(null);

const form = ref({
    id: null,
    expense_category_id: "",
    display_name: "",
    slug: "",
    is_order_level: false,
    default_vat_rate: 16.00,
});

// Lifecycle
onMounted(async () => {
    await store.fetchCategories();
    await store.fetchTypes();
});

// Computed
const getCategoryName = (categoryId) => {
    const category = store.categories.find(cat => cat.id === categoryId);
    return category ? category.name : 'N/A';
};

// Methods
const generateSlug = () => {
    if (!isEditing.value && form.value.display_name) {
        form.value.slug = form.value.display_name
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
};

const openCreateModal = () => {
    isEditing.value = false;
    form.value = {
        id: null,
        expense_category_id: "",
        display_name: "",
        slug: "",
        is_order_level: false,
        default_vat_rate: 16.00,
    };
    showModal.value = true;
};

const openEditModal = (type) => {
    isEditing.value = true;
    form.value = {
        id: type.id,
        expense_category_id: type.expense_category_id,
        display_name: type.display_name,
        slug: type.slug,
        is_order_level: type.is_order_level,
        default_vat_rate: parseFloat(type.default_vat_rate),
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.value = {
        id: null,
        expense_category_id: "",
        display_name: "",
        slug: "",
        is_order_level: false,
        default_vat_rate: 16.00,
    };
};

const submitForm = async () => {
    submitting.value = true;
    
    try {
        if (isEditing.value) {
            await store.updateType(form.value.id, form.value);
        } else {
            await store.createType(form.value);
        }
        closeModal();
    } catch (error) {
        console.error('Form submission error:', error);
    } finally {
        submitting.value = false;
    }
};

const confirmDelete = (type) => {
    typeToDelete.value = type;
    showDeleteModal.value = true;
};

const deleteType = async () => {
    deleting.value = true;
    
    try {
        await store.deleteType(typeToDelete.value.id);
        showDeleteModal.value = false;
        typeToDelete.value = null;
    } catch (error) {
        console.error('Delete error:', error);
    } finally {
        deleting.value = false;
    }
};
</script>