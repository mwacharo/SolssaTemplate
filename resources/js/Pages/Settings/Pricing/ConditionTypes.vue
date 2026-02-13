<script setup>
import { ref, onMounted } from "vue";
import { useConditionTypeStore } from "@/stores/conditionTypeStore";
import AppLayout from "@/Layouts/AppLayout.vue";

const store = useConditionTypeStore();

const editing = ref(false);
const editId = ref(null);

const form = ref({
    name: "",
    code: "",
    input_type: "text",
    supports_range: false,
    unit: "",
    meta: "",
    is_active: true,
});

onMounted(() => {
    store.fetchAll();
});

const resetForm = () => {
    form.value = {
        name: "",
        code: "",
        input_type: "text",
        supports_range: false,
        unit: "",
        meta: "",
        is_active: true,
    };
    editing.value = false;
    editId.value = null;
};

const submit = async () => {
    if (editing.value) {
        await store.update(editId.value, form.value);
    } else {
        await store.create(form.value);
    }
    resetForm();
};

// const editItem = (item) => {
//     editing.value = true;
//     editId.value = item.id;
//     form.value = { ...item };
// };

const editItem = (item) => {
    editing.value = true;
    editId.value = item.id;

    Object.assign(form.value, {
        name: item.name,
        code: item.code,
        input_type: item.input_type,
        supports_range: item.supports_range,
        unit: item.unit,
        meta: item.meta ? JSON.stringify(item.meta, null, 2) : null,
        is_active: item.is_active,
    });
};

const removeItem = async (id) => {
    if (confirm("Are you sure?")) {
        await store.delete(id);
    }
};
</script>

<template>
    <AppLayout title="Condition Types">
        <div class="p-6 space-y-6">
            <!-- FORM -->
            <div class="bg-white p-4 rounded shadow">
                <h2 class="text-lg font-bold mb-4">
                    {{ editing ? "Edit" : "Create" }} Condition Type
                </h2>

                <div class="grid grid-cols-2 gap-4">
                    <input
                        v-model="form.name"
                        placeholder="Name"
                        class="input"
                    />
                    <input
                        v-model="form.code"
                        placeholder="Code"
                        class="input"
                    />

                    <select v-model="form.input_type" class="input">
                        <option value="text">Text</option>
                        <option value="number">Number</option>
                        <option value="select">Select</option>
                    </select>

                    <input
                        v-model="form.unit"
                        placeholder="Unit"
                        class="input"
                    />

                    <label class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.supports_range" />
                        Supports Range
                    </label>

                    <label class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.is_active" />
                        Active
                    </label>

                    <textarea
                        v-model="form.meta"
                        placeholder="Meta (JSON)"
                        class="input col-span-2"
                    />
                </div>

                <div class="mt-4 flex gap-2">
                    <button @click="submit" class="btn-primary">
                        {{ editing ? "Update" : "Save" }}
                    </button>
                    <button
                        v-if="editing"
                        @click="resetForm"
                        class="btn-secondary"
                    >
                        Cancel
                    </button>
                </div>
            </div>

            <!-- TABLE -->
            <div class="bg-white p-4 rounded shadow">
                <h2 class="text-lg font-bold mb-4">All Condition Types</h2>

                <table class="w-full border">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="p-2">Name</th>
                            <th class="p-2">Code</th>
                            <th class="p-2">Input Type</th>
                            <th class="p-2">Range</th>
                            <th class="p-2">Active</th>
                            <th class="p-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in store.conditionTypes"
                            :key="item.id"
                            class="border-t"
                        >
                            <td class="p-2">{{ item.name }}</td>
                            <td class="p-2">{{ item.code }}</td>
                            <td class="p-2">{{ item.input_type }}</td>
                            <td class="p-2">
                                {{ item.supports_range ? "Yes" : "No" }}
                            </td>
                            <td class="p-2">
                                <input
                                    type="checkbox"
                                    :checked="item.is_active"
                                    @change="store.toggleActive(item)"
                                />
                            </td>
                            <td class="p-2 flex gap-2">
                                <button
                                    @click="editItem(item)"
                                    class="text-blue-600"
                                >
                                    Edit
                                </button>
                                <button
                                    @click="removeItem(item.id)"
                                    class="text-red-600"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="store.loading" class="mt-3 text-gray-500">
                    Loading...
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.input {
    @apply border rounded px-3 py-2 w-full;
}

.btn-primary {
    @apply bg-blue-600 text-white px-4 py-2 rounded;
}

.btn-secondary {
    @apply bg-gray-500 text-white px-4 py-2 rounded;
}
</style>
