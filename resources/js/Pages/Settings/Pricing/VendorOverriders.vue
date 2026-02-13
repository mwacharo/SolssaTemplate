<template>
    <AppLayout>
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Vendor Rate Overrides</h1>

                <button
                    @click="showForm = !showForm"
                    class="bg-blue-600 text-white px-4 py-2 rounded"
                >
                    {{ showForm ? "Close" : "Add Override" }}
                </button>
            </div>

            <!-- Override Form -->
            <div v-if="showForm" class="bg-gray-50 p-6 rounded mb-8 border">
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Vendor Service -->
                        <div>
                            <label class="block mb-1 font-medium">
                                Vendor Service
                            </label>
                            <select
                                v-model="form.vendor_service_id"
                                class="w-full border rounded p-2"
                            >
                                <option value="">Select Vendor Service</option>
                                <option
                                    v-for="vs in vendorServices"
                                    :key="vs.id"
                                    :value="vs.id"
                                >
                                    {{ vs.vendor?.name }} -
                                    {{ vs.service?.service_name }}
                                </option>
                            </select>
                        </div>

                        <!-- Service Condition -->
                        <div>
                            <label class="block mb-1 font-medium">
                                Service Condition
                            </label>
                            <select
                                v-model="form.service_condition_id"
                                class="w-full border rounded p-2"
                            >
                                <option value="">Select Condition</option>
                                <option
                                    v-for="condition in filteredConditions"
                                    :key="condition.id"
                                    :value="condition.id"
                                >
                                    {{ condition.condition_type }}
                                    ({{ condition.min_value }} -
                                    {{ condition.max_value }}) → Default:
                                    {{ condition.rate }}
                                </option>
                            </select>
                        </div>

                        <!-- Custom Rate -->
                        <div>
                            <label class="block mb-1 font-medium">
                                Custom Rate
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                v-model="form.custom_rate"
                                class="w-full border rounded p-2"
                                placeholder="Enter override rate"
                            />
                        </div>
                    </div>

                    <div class="mt-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-green-600 text-white px-4 py-2 rounded"
                        >
                            Save Override
                        </button>
                    </div>
                </form>
            </div>

            <!-- Overrides Table -->
            <div class="bg-white border rounded">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3 text-left">Vendor</th>
                            <th class="p-3 text-left">Service</th>
                            <th class="p-3 text-left">Condition</th>
                            <th class="p-3 text-left">Default Rate</th>
                            <th class="p-3 text-left">Custom Rate</th>
                            <th class="p-3 text-left">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="override in overrides"
                            :key="override.id"
                            class="border-t"
                        >
                            <td class="p-3">
                                {{ override.vendorService?.vendor?.name }}
                            </td>

                            <td class="p-3">
                                {{
                                    override.vendorService?.service
                                        ?.service_name
                                }}
                            </td>

                            <td class="p-3">
                                {{ override.condition?.condition_type }}
                                ({{ override.condition?.min_value }} -
                                {{ override.condition?.max_value }})
                            </td>

                            <td class="p-3">
                                {{ override.condition?.rate }}
                            </td>

                            <td class="p-3 font-semibold">
                                {{ override.custom_rate }}
                            </td>

                            <td class="p-3">
                                <button
                                    @click="removeOverride(override.id)"
                                    class="text-red-600"
                                >
                                    Remove
                                </button>
                            </td>
                        </tr>

                        <tr v-if="overrides.length === 0">
                            <td
                                colspan="6"
                                class="p-4 text-center text-gray-500"
                            >
                                No overrides created yet.
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
import { usePage, useForm, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const page = usePage();

const vendorServices = page.props.vendorServices ?? [];
const conditions = page.props.conditions ?? [];
const overrides = page.props.overrides ?? [];

const showForm = ref(false);

const form = useForm({
    vendor_service_id: "",
    service_condition_id: "",
    custom_rate: "",
});

/*
    Only show conditions that belong to the selected service
*/
const filteredConditions = computed(() => {
    if (!form.vendor_service_id) return [];

    const selected = vendorServices.find(
        (vs) => vs.id === form.vendor_service_id,
    );

    if (!selected) return [];

    return conditions.filter((c) => c.service_id === selected.service_id);
});

const submit = () => {
    form.post(route("vendor-overrides.store"), {
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
};

const removeOverride = (id) => {
    if (confirm("Remove this override?")) {
        router.delete(route("vendor-overrides.destroy", id));
    }
};
</script>
