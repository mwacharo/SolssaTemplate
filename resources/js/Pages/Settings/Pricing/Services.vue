<template>
    <AppLayout>
        <div class="services-container">
            <div class="header">
                <h1 class="title">Services Management</h1>
                <button @click="openCreateModal" class="btn btn-primary">
                    <span class="icon">+</span>
                    Add New Service
                </button>
            </div>

            <!-- Loading State -->
            <div v-if="loading && !services.length" class="loading">
                Loading services...
            </div>

            <!-- Error State -->
            <div v-if="error" class="error-message">
                {{ error }}
                <button @click="clearError" class="btn-clear-error">×</button>
            </div>

            <!-- Services Table -->
            <div v-if="!loading || services.length" class="table-container">
                <table class="services-table">
                    <thead>
                        <tr>
                            <th>Service Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <!-- inbound -->
                            <th>Inbound</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="service in services"
                            :key="service?.id || Math.random()"
                        >
                            <td class="service-name">
                                {{ service?.service_name || "N/A" }}
                            </td>
                            <td class="description">
                                {{ service?.description || "No description" }}
                            </td>
                            <td>
                                <span
                                    :class="[
                                        'status-badge',
                                        service?.is_active
                                            ? 'active'
                                            : 'inactive',
                                    ]"
                                >
                                    {{
                                        service?.is_active
                                            ? "Active"
                                            : "Inactive"
                                    }}
                                </span>
                            </td>

                            <td>
                                <span
                                    :class="[
                                        'status-badge',
                                        service?.inbound
                                            ? 'active'
                                            : 'inactive',
                                    ]"
                                >
                                    {{
                                        service?.inbound
                                            ? "Inbound"
                                            : "Outbound"
                                    }}
                                </span>
                            </td>
                            <td class="date">
                                {{ formatDate(service?.created_at) }}
                            </td>
                            <td class="actions">
                                <button
                                    @click="toggleStatus(service)"
                                    :class="['btn-action', 'btn-toggle']"
                                    :disabled="!service"
                                    :title="
                                        service?.is_active
                                            ? 'Deactivate'
                                            : 'Activate'
                                    "
                                >
                                    <span v-if="service?.is_active">⏸</span>
                                    <span v-else>▶</span>
                                </button>
                                <button
                                    @click="openEditModal(service)"
                                    class="btn-action btn-edit"
                                    :disabled="!service"
                                    title="Edit"
                                >
                                    ✏️
                                </button>
                                <button
                                    @click="confirmDelete(service)"
                                    class="btn-action btn-delete"
                                    :disabled="!service"
                                    title="Delete"
                                >
                                    🗑️
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!services.length">
                            <td colspan="5" class="empty-state">
                                No services found. Create your first service to
                                get started.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Create/Edit Modal -->
            <div
                v-if="showModal"
                class="modal-overlay"
                @click.self="closeModal"
            >
                <div class="modal">
                    <div class="modal-header">
                        <h2>
                            {{
                                isEditMode
                                    ? "Edit Service"
                                    : "Create New Service"
                            }}
                        </h2>
                        <button @click="closeModal" class="btn-close">×</button>
                    </div>
                    <form @submit.prevent="handleSubmit" class="modal-body">
                        <div class="form-group">
                            <label for="service_name">Service Name *</label>
                            <input
                                id="service_name"
                                v-model="formData.service_name"
                                type="text"
                                required
                                class="form-control"
                                placeholder="Enter service name"
                            />
                            <span v-if="formErrors.service_name" class="error">
                                {{ formErrors.service_name }}
                            </span>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea
                                id="description"
                                v-model="formData.description"
                                class="form-control"
                                rows="4"
                                placeholder="Enter service description (optional)"
                            ></textarea>
                        </div>

                        <div class="form-group checkbox-group">
                            <label class="checkbox-label">
                                <input
                                    v-model="formData.is_active"
                                    type="checkbox"
                                    class="checkbox"
                                />
                                <span>Active Service</span>
                            </label>
                        </div>
                        <input type="text" />

                        <!--  inbound-->
                        <div class="form-group checkbox-group">
                            <label class="checkbox-label">
                                <input
                                    v-model="formData.inbound"
                                    type="checkbox"
                                    class="checkbox"
                                />
                                <span>Inbound Service</span>
                            </label>
                        </div>

                        <div class="modal-footer">
                            <button
                                type="button"
                                @click="closeModal"
                                class="btn btn-secondary"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="loading"
                                class="btn btn-primary"
                            >
                                {{
                                    loading
                                        ? "Saving..."
                                        : isEditMode
                                          ? "Update"
                                          : "Create"
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div
                v-if="showDeleteModal"
                class="modal-overlay"
                @click.self="showDeleteModal = false"
            >
                <div class="modal modal-small">
                    <div class="modal-header">
                        <h2>Confirm Delete</h2>
                        <button
                            @click="showDeleteModal = false"
                            class="btn-close"
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>
                            Are you sure you want to delete "<strong>{{
                                serviceToDelete?.service_name
                            }}</strong
                            >"?
                        </p>
                        <p class="warning-text">
                            This action cannot be undone.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button
                            @click="showDeleteModal = false"
                            class="btn btn-secondary"
                        >
                            Cancel
                        </button>
                        <button
                            @click="handleDelete"
                            :disabled="loading"
                            class="btn btn-danger"
                        >
                            {{ loading ? "Deleting..." : "Delete" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useServicesStore } from "@/stores/servicesStore";
import { storeToRefs } from "pinia";

const servicesStore = useServicesStore();
const { services, loading, error } = storeToRefs(servicesStore);

const showModal = ref(false);
const showDeleteModal = ref(false);
const isEditMode = ref(false);
const currentServiceId = ref(null);
const serviceToDelete = ref(null);

const formData = ref({
    servServiceice_name: "",
    description: "",
    is_active: true,
});

const formErrors = ref({});

onMounted(async () => {
    await servicesStore.fetchServices();
});

const resetForm = () => {
    formData.value = {
        service_name: "",
        description: "",
        is_active: true,
        inbound: false,
    };
    formErrors.value = {};
};

const openCreateModal = () => {
    resetForm();
    isEditMode.value = false;
    currentServiceId.value = null;
    showModal.value = true;
};

const openEditModal = (service) => {
    if (!service || !service.id) {
        console.error("Invalid service object");
        return;
    }

    formData.value = {
        service_name: service.service_name,
        description: service.description || "",
        is_active: service.is_active,
        inbound: service.inbound,
    };
    isEditMode.value = true;
    currentServiceId.value = service.id;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const validateForm = () => {
    formErrors.value = {};

    if (!formData.value.service_name.trim()) {
        formErrors.value.service_name = "Service name is required";
        return false;
    }

    if (formData.value.service_name.length > 255) {
        formErrors.value.service_name =
            "Service name must be less than 255 characters";
        return false;
    }

    return true;
};

const handleSubmit = async () => {
    if (!validateForm()) return;

    try {
        if (isEditMode.value && currentServiceId.value) {
            await servicesStore.updateService(
                currentServiceId.value,
                formData.value,
            );
        } else {
            await servicesStore.createService(formData.value);
        }
        closeModal();
    } catch (err) {
        if (err.response?.data?.errors) {
            formErrors.value = err.response.data.errors;
        }
    }
};

const confirmDelete = (service) => {
    if (!service || !service.id) {
        console.error("Invalid service object");
        return;
    }

    serviceToDelete.value = service;
    showDeleteModal.value = true;
};

const handleDelete = async () => {
    if (!serviceToDelete.value?.id) return;

    try {
        await servicesStore.deleteService(serviceToDelete.value.id);
        showDeleteModal.value = false;
        serviceToDelete.value = null;
    } catch (err) {
        console.error("Delete failed:", err);
    }
};

const toggleStatus = async (service) => {
    if (!service || !service.id) {
        console.error("Invalid service object");
        return;
    }

    try {
        await servicesStore.toggleServiceStatus(service.id);
    } catch (err) {
        console.error("Toggle status failed:", err);
    }
};

const clearError = () => {
    servicesStore.clearError();
};

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};
</script>

<style scoped>
.services-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.title {
    font-size: 2rem;
    font-weight: 700;
    color: #1a202c;
    margin: 0;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-secondary {
    background: #e5e7eb;
    color: #374151;
}

.btn-secondary:hover {
    background: #d1d5db;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

.icon {
    font-size: 1.25rem;
}

.loading,
.error-message {
    text-align: center;
    padding: 2rem;
    border-radius: 0.5rem;
    margin: 2rem 0;
}

.loading {
    background: #f3f4f6;
    color: #6b7280;
}

.error-message {
    background: #fee2e2;
    color: #dc2626;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.btn-clear-error {
    background: none;
    border: none;
    color: #dc2626;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.25rem;
}

.btn-clear-error:hover {
    background: rgba(220, 38, 38, 0.1);
}

.table-container {
    background: white;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.services-table {
    width: 100%;
    border-collapse: collapse;
}

.services-table thead {
    background: #f9fafb;
}

.services-table th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
}

.services-table td {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.service-name {
    font-weight: 600;
    color: #1a202c;
}

.description {
    color: #6b7280;
    max-width: 300px;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 600;
}

.status-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.date {
    color: #6b7280;
    font-size: 0.875rem;
}

.actions {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    padding: 0.5rem;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.2s;
    background: transparent;
    font-size: 1rem;
}

.btn-action:hover {
    background: #f3f4f6;
}

.btn-toggle:hover {
    background: #dbeafe;
}

.btn-edit:hover {
    background: #fef3c7;
}

.btn-delete:hover {
    background: #fee2e2;
}

.empty-state {
    text-align: center;
    color: #9ca3af;
    padding: 3rem !important;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal {
    background: white;
    border-radius: 0.75rem;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
}

.modal-small {
    max-width: 400px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    color: #1a202c;
}

.btn-close {
    background: none;
    border: none;
    font-size: 2rem;
    color: #9ca3af;
    cursor: pointer;
    padding: 0;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-close:hover {
    color: #374151;
}

.modal-body {
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

.checkbox-group {
    margin-bottom: 1rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-weight: normal;
}

.checkbox {
    width: 1.25rem;
    height: 1.25rem;
    cursor: pointer;
    border: 2px solid #555; /* makes it visible */
    border-radius: 4px; /* optional - smoother look */
    accent-color: #2563eb; /* modern blue check color */
    background-color: #fff; /* ensures contrast */
}

.error {
    display: block;
    color: #dc2626;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.warning-text {
    color: #dc2626;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .services-container {
        padding: 1rem;
    }

    .header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }

    .table-container {
        overflow-x: auto;
    }

    .services-table {
        min-width: 800px;
    }

    .modal {
        width: 95%;
        max-height: 95vh;
    }
}
</style>
