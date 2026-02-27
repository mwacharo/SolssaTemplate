<template>
    <AppLayout>
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Pricing Conditions</h1>

                <button
                    @click="toggleForm"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                >
                    {{ showForm ? "Close" : "Add Condition" }}
                </button>
            </div>

            <!-- Create/Edit Form -->
            <div v-if="showForm" class="bg-gray-50 p-6 rounded mb-8 border">
                <h2 class="text-lg font-semibold mb-4">
                    {{ isEditing ? "Edit Condition" : "Add New Condition" }}
                </h2>

                <form @submit.prevent="handleSubmit">
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Service -->
                        <div>
                            <label class="block mb-1 font-medium"
                                >Service</label
                            >
                            <select
                                v-model="form.service_id"
                                class="w-full border rounded p-2"
                                required
                            >
                                <option value="">Select Service</option>
                                <option
                                    v-for="service in services"
                                    :key="service.id"
                                    :value="service.id"
                                >
                                    {{ service.service_name }}
                                </option>
                            </select>
                        </div>

                        <!-- Condition Type -->
                        <div>
                            <label class="block mb-1 font-medium"
                                >Condition Type</label
                            >
                            <select
                                v-model="form.condition_type_id"
                                class="w-full border rounded p-2"
                                required
                            >
                                <option value="">Select Type</option>
                                <option
                                    v-for="type in conditionTypes"
                                    :key="type.id"
                                    :value="type.id"
                                >
                                    {{ type.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Min Value -->
                        <div>
                            <label class="block mb-1 font-medium"
                                >Min Value</label
                            >
                            <input
                                type="number"
                                v-model.number="form.min_value"
                                class="w-full border rounded p-2"
                                step="any"
                            />
                        </div>

                        <!-- Max Value -->
                        <div>
                            <label class="block mb-1 font-medium"
                                >Max Value</label
                            >
                            <input
                                type="number"
                                v-model.number="form.max_value"
                                class="w-full border rounded p-2"
                                step="any"
                            />
                        </div>

                        <!-- Rate -->
                        <div>
                            <label class="block mb-1 font-medium">Rate</label>
                            <input
                                type="number"
                                step="0.01"
                                v-model.number="form.rate"
                                class="w-full border rounded p-2"
                            />
                        </div>

                        <!-- Rate Type -->
                        <div>
                            <label class="block mb-1 font-medium"
                                >Rate Type</label
                            >
                            <select
                                v-model="form.rate_type"
                                class="w-full border rounded p-2"
                            >
                                <option value="">Select Rate Type</option>
                                <option value="fixed">Fixed</option>
                                <option value="percentage">Percentage</option>
                                <option value="per_unit">Per Unit</option>
                            </select>
                        </div>

                        <!-- Value -->
                        <div>
                            <label class="block mb-1 font-medium">Value</label>
                            <input
                                type="number"
                                step="0.01"
                                v-model.number="form.value"
                                class="w-full border rounded p-2"
                                placeholder="e.g. 100 or 0.15"
                            />
                        </div>

                        <!-- Priority -->
                        <div>
                            <label class="block mb-1 font-medium"
                                >Priority</label
                            >
                            <input
                                type="number"
                                v-model.number="form.priority"
                                class="w-full border rounded p-2"
                                min="0"
                                placeholder="Lower = higher priority"
                            />
                        </div>

                        <!-- Unit -->
                        <div>
                            <label class="block mb-1 font-medium">Unit</label>
                            <input
                                v-model="form.unit"
                                placeholder="kg, %, km"
                                class="w-full border rounded p-2"
                            />
                        </div>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 disabled:opacity-50"
                        >
                            {{
                                isSubmitting
                                    ? "Saving..."
                                    : isEditing
                                      ? "Update Condition"
                                      : "Save Condition"
                            }}
                        </button>

                        <button
                            v-if="isEditing"
                            type="button"
                            @click="cancelEdit"
                            class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Conditions Table -->
            <div class="bg-white rounded border overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3 text-left">Service</th>
                            <th class="p-3 text-left">Type</th>
                            <th class="p-3 text-left">Min</th>
                            <th class="p-3 text-left">Max</th>
                            <th class="p-3 text-left">Rate</th>
                            <th class="p-3 text-left">Rate Type</th>
                            <th class="p-3 text-left">Value</th>
                            <th class="p-3 text-left">Priority</th>
                            <th class="p-3 text-left">Unit</th>
                            <th class="p-3 text-left">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="condition in conditions"
                            class="border-t hover:bg-gray-50"
                        >
                            <td class="p-3">
                                {{ condition.service?.service_name || "N/A" }}
                            </td>
                            <td class="p-3">
                                {{ condition.condition_type?.name || "N/A" }}
                            </td>
                            <td class="p-3">
                                {{ condition.min_value ?? "-" }}
                            </td>
                            <td class="p-3">
                                {{ condition.max_value ?? "-" }}
                            </td>
                            <td class="p-3">
                                {{ condition.rate ?? "-" }}
                            </td>
                            <td class="p-3">
                                {{ condition.rate_type || "-" }}
                            </td>
                            <td class="p-3">
                                {{ condition.value ?? "-" }}
                            </td>
                            <td class="p-3">
                                {{ condition.priority ?? "-" }}
                            </td>
                            <td class="p-3">
                                {{ condition.unit || "-" }}
                            </td>
                            <td class="p-3">
                                <div class="flex gap-2">
                                    <button
                                        @click="editCondition(condition)"
                                        class="text-blue-600 hover:underline"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        @click="deleteCondition(condition.id)"
                                        class="text-red-600 hover:underline"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="conditions.length === 0">
                            <td
                                colspan="10"
                                class="p-4 text-center text-gray-500"
                            >
                                No conditions created yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, onMounted, computed } from "vue";
import { useServicesStore } from "@/stores/servicesStore";
import { useConditionTypeStore } from "@/stores/conditionTypeStore";
import { useConditionStore } from "@/stores/condition";

// Stores
const servicesStore = useServicesStore();
const conditionTypeStore = useConditionTypeStore();
const conditionStore = useConditionStore();

// Computed properties
const conditions = computed(() => conditionStore.conditions);
const services = computed(() => servicesStore.services);
const conditionTypes = computed(() => conditionTypeStore.conditionTypes);

// State
const showForm = ref(false);
const isEditing = ref(false);
const isSubmitting = ref(false);

// Form data with initial state
const initialFormState = {
    id: null,
    service_id: "",
    condition_type_id: "",
    min_value: "",
    max_value: "",
    rate: "",
    rate_type: "",
    value: "",
    priority: "",
    unit: "",
};

const form = ref({ ...initialFormState });

// Methods
const resetForm = () => {
    form.value = { ...initialFormState };
    isEditing.value = false;
};

const toggleForm = () => {
    if (showForm.value) {
        resetForm();
    }
    showForm.value = !showForm.value;
};

const cancelEdit = () => {
    resetForm();
    showForm.value = false;
};

const editCondition = (condition) => {
    showForm.value = true;
    isEditing.value = true;

    form.value = {
        id: condition.id,
        service_id: condition.service_id,
        condition_type_id: condition.condition_type_id,
        min_value: condition.min_value,
        max_value: condition.max_value,
        rate: condition.rate,
        rate_type: condition.rate_type,
        value: condition.value,
        priority: condition.priority,
        unit: condition.unit,
    };
};

const handleSubmit = async () => {
    isSubmitting.value = true;

    try {
        const payload = {
            service_id: form.value.service_id,
            condition_type_id: form.value.condition_type_id,
            min_value: form.value.min_value,
            max_value: form.value.max_value,
            rate: form.value.rate,
            rate_type: form.value.rate_type,
            value: form.value.value,
            priority: form.value.priority,
            unit: form.value.unit,
        };

        if (isEditing.value) {
            await conditionStore.updateCondition(form.value.id, payload);
        } else {
            await conditionStore.createCondition(payload);
        }

        resetForm();
        showForm.value = false;
    } catch (error) {
        console.error("Error submitting form:", error);
        alert("Failed to save condition. Please try again.");
    } finally {
        isSubmitting.value = false;
    }
};

const deleteCondition = async (id) => {
    if (!confirm("Are you sure you want to delete this condition?")) {
        return;
    }

    try {
        await conditionStore.deleteCondition(id);
    } catch (error) {
        console.error("Error deleting condition:", error);
        alert("Failed to delete condition. Please try again.");
    }
};

// Lifecycle
onMounted(async () => {
    try {
        await Promise.all([
            servicesStore.fetchServices(),
            conditionTypeStore.fetchAll(),
            conditionStore.fetchConditions(),
        ]);
    } catch (error) {
        console.error("Error loading data:", error);
    }
});
</script>
