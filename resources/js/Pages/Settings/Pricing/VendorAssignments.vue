<template>
    <AppLayout>
        <div class="p-6">

            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">
                    Vendor Service Assignments
                </h1>

                <button
                    @click="showForm = !showForm"
                    class="bg-blue-600 text-white px-4 py-2 rounded"
                >
                    {{ showForm ? 'Close' : 'Assign Service' }}
                </button>
            </div>

            <!-- Assign Form -->
            <div v-if="showForm" class="bg-gray-50 p-6 rounded mb-8 border">

                <form @submit.prevent="submit">

                    <div class="grid grid-cols-2 gap-4">

                        <!-- Vendor -->
                        <div>
                            <label class="block mb-1 font-medium">Vendor</label>
                            <select
                                v-model="form.vendor_id"
                                class="w-full border rounded p-2"
                            >
                                <option value="">Select Vendor</option>
                                <option
                                    v-for="vendor in vendors"
                                    :key="vendor.id"
                                    :value="vendor.id"
                                >
                                    {{ vendor.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Service -->
                        <div>
                            <label class="block mb-1 font-medium">Service</label>
                            <select
                                v-model="form.service_id"
                                class="w-full border rounded p-2"
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

                    </div>

                    <div class="mt-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-green-600 text-white px-4 py-2 rounded"
                        >
                            Assign
                        </button>
                    </div>

                </form>
            </div>

            <!-- Assignments Table -->
            <div class="bg-white border rounded">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3 text-left">Vendor</th>
                            <th class="p-3 text-left">Service</th>
                            <th class="p-3 text-left">Status</th>
                            <th class="p-3 text-left">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="assignment in assignments"
                            :key="assignment.id"
                            class="border-t"
                        >
                            <td class="p-3">
                                {{ assignment.vendor?.name }}
                            </td>

                            <td class="p-3">
                                {{ assignment.service?.service_name }}
                            </td>

                            <td class="p-3">
                                <span
                                    :class="assignment.is_active
                                        ? 'text-green-600'
                                        : 'text-red-600'"
                                >
                                    {{ assignment.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <td class="p-3">
                                <button
                                    @click="removeAssignment(assignment.id)"
                                    class="text-red-600"
                                >
                                    Remove
                                </button>
                            </td>
                        </tr>

                        <tr v-if="assignments.length === 0">
                            <td colspan="4" class="p-4 text-center text-gray-500">
                                No assignments yet.
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
import { ref } from "vue";

const page = usePage();

const vendors = page.props.vendors ?? [];
const services = page.props.services ?? [];
const assignments = page.props.assignments ?? [];

const showForm = ref(false);

const form = useForm({
    vendor_id: "",
    service_id: ""
});

const submit = () => {
    form.post(route('vendor-assignments.store'), {
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        }
    });
};

const removeAssignment = (id) => {
    if (confirm("Are you sure you want to remove this assignment?")) {
        router.delete(route('vendor-assignments.destroy', id));
    }
};
</script>
