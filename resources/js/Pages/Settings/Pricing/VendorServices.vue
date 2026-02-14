<template>
    <div
        v-if="store.isModalOpen"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
        @click.self="store.closeModal()"
    >
        <div class="bg-white w-4/5 max-h-[90vh] overflow-auto rounded p-6">
            <!-- Header -->
            <div class="flex justify-between mb-4">
                <h2 class="text-xl font-bold">Vendor Services</h2>
                <button
                    @click="store.closeModal()"
                    class="text-gray-500 hover:text-gray-700 text-2xl leading-none"
                >
                    ✕
                </button>
            </div>

            <!-- Loading State -->
            <div v-if="store.loading" class="text-center py-8">
                <div
                    class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"
                ></div>
                <p class="mt-2 text-gray-600">Loading...</p>
            </div>

            <template v-else>
                <!-- Assign New Service -->
                <div class="flex gap-2 mb-6">
                    <v-autocomplete
                        v-model="selectedService"
                        label="Select Service"
                        :items="servicesStore.services"
                        item-title="service_name"
                        item-value="id"
                        dense
                        clearable
                        class="w-full"
                    />
                </div>

                <div class="flex gap-2 mb-6">
                    <button
                        @click="handleAssignService"
                        :disabled="!selectedService"
                        class="bg-blue-600 text-white px-4 rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
                    >
                        Assign
                    </button>
                </div>

                <!-- Empty State -->
                <div
                    v-if="store.vendorServices.length === 0"
                    class="text-center py-8 text-gray-500"
                >
                    No services assigned yet
                </div>

                <!-- Vendor Services -->
                <div
                    v-for="vs in store.vendorServices"
                    :key="vs.id"
                    class="border rounded mb-6 p-4"
                >
                    <div class="flex justify-between mb-3">
                        <h3 class="font-semibold text-lg">
                            {{ vs.service.service_name }}
                        </h3>

                        <!-- add service rates -->

                        <button
                            @click="handleRemoveService(vs.id)"
                            class="text-red-600 text-sm hover:text-red-800"
                        >
                            Remove
                        </button>
                    </div>

                    <!-- service Conditions -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-2 text-left">Condition</th>
                                    <th class="p-2 text-center">Rate Type</th>
                                    <th class="p-2 text-center">Value</th>

                                    <th class="p-2 text-center">Override</th>
                                    <th class="p-2 text-center">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <!-- if service rates available show them else loop all the (conditions)service_conditions for user to overider and store the records in service rates    -->
                                <tr
                                    v-for="condition in conditions"
                                    :key="condition.id"
                                    class="border-t hover:bg-gray-50"
                                >
                                    <!-- condition name -->

                                    <td class="p-2">
                                        {{ condition.condition_type.name }}
                                    </td>

                                    <td class="p-2 text-center">
                                        <span
                                            class="text-gray-500 text-xs ml-1"
                                        >
                                            ({{ condition.rate_type }})
                                        </span>
                                    </td>

                                    <!-- value -->
                                    <td class="p-2 text-center">
                                        <span
                                            class="text-gray-500 text-xs ml-1"
                                        >
                                            ({{ condition.value }})
                                        </span>
                                    </td>

                                    <td class="p-2">
                                        <input
                                            type="number"
                                            step="0.01"
                                            :value="
                                                store.overrides[vs.id]?.[
                                                    condition.id
                                                ]?.custom_rate
                                            "
                                            @input="
                                                handleOverrideChange(
                                                    vs.id,
                                                    condition.id,
                                                    'custom_rate',
                                                    $event.target.value,
                                                )
                                            "
                                            class="border p-1 w-24 rounded text-center"
                                            placeholder="override value"
                                        />
                                    </td>

                                    <td class="p-2 text-center">
                                        <button
                                            @click="
                                                handleSaveOverride(
                                                    vs.id,
                                                    condition.id,
                                                )
                                            "
                                            class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700"
                                        >
                                            Save
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { useVendorServicesStore } from "@/stores/vendorServices";
import { useServicesStore } from "@/stores/servicesStore";
import { useConditionStore } from "@/stores/condition";
import { useConditionTypeStore } from "@/stores/conditionTypeStore";

// Store
const store = useVendorServicesStore();
const servicesStore = useServicesStore();
const conditionStore = useConditionStore();
const conditionTypeStore = useConditionTypeStore();

const conditions = computed(() => conditionStore.conditions);
const services = computed(() => servicesStore.services);
const conditionTypes = computed(() => conditionTypeStore.conditionTypes);

// Local state
const selectedService = ref("");
const selectedConditionType = ref("");
const selectedRateType = ref("");
const custom_rate = ref("");

// Methods
const handleAssignService = async () => {
    if (!selectedService.value) return;

    try {
        await store.assignService(selectedService.value);
        selectedService.value = "";

        alert("Service assigned successfully!");
    } catch (error) {
        alert("Failed to assign service");
    }
};

const handleRemoveService = async (vendorServiceId) => {
    if (!confirm("Are you sure you want to remove this service?")) return;

    try {
        await store.removeService(vendorServiceId);
        alert("Service removed successfully!");
    } catch (error) {
        alert("Failed to remove service");
    }
};

const handleOverrideChange = (vendorServiceId, conditionId, field, value) => {
    store.updateOverride(vendorServiceId, conditionId, field, value);
};

const handleSaveOverride = async (vendorServiceId, conditionId) => {
    try {
        await store.saveOverride(vendorServiceId, conditionId);
        alert("Rate override saved successfully!");
    } catch (error) {
        alert(error.message || "Failed to save rate override");
    }
};

onMounted(async () => {
    // Load each independently to see which one fails
    try {
        await servicesStore.fetchServices();
    } catch (error) {
        console.error("Error loading services:", error);
    }

    try {
        await store.fetchVendorServices();
    } catch (error) {
        console.error("Error loading vendor services:", error);
    }

    try {
        await conditionTypeStore.fetchAll();
        console.log(
            "Condition types loaded:",
            conditionTypeStore.conditionTypes,
        );

        // service_conditions
        try {
            await conditionStore.fetchConditions();
            console.log("Conditions loaded:", conditionStore.conditions);
        } catch (error) {
            console.error("Error loading conditions:", error);
        }
    } catch (error) {
        console.error("Error loading condition types:", error);
    }
});
</script>
