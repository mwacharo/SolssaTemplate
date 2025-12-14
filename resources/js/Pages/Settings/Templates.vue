<template>
    <AppLayout>
        <v-container fluid class="pa-0">
            <v-card class="rounded-lg elevation-2 mb-6">
                <v-card-title
                    class="d-flex justify-space-between align-center py-4 px-6 bg-surface"
                >
                    <span class="text-h5 font-weight-bold"
                        >Template Management</span
                    >
                    <v-btn
                        color="primary"
                        prepend-icon="mdi-plus"
                        @click="openCreateDialog"
                        size="large"
                        variant="elevated"
                        rounded="pill"
                    >
                        New Template
                    </v-btn>
                </v-card-title>

                <v-card-text class="pa-6">
                    <v-row>
                        <v-col cols="12" md="6" lg="4">
                            <v-text-field
                                v-model="searchQuery"
                                label="Search templates"
                                prepend-inner-icon="mdi-magnify"
                                variant="outlined"
                                density="comfortable"
                                hide-details
                                clearable
                                @keyup.enter="handleSearch"
                                @click:clear="handleClearSearch"
                                class="rounded-lg"
                            ></v-text-field>
                        </v-col>
                    </v-row>
                </v-card-text>

                <v-data-table
                    :headers="headers"
                    :loading="store.loading"
                    :items="store.filteredTemplates"
                    :sort-by="[{ key: 'name', order: 'asc' }]"
                    :items-per-page="10"
                    :loading-text="'Loading templates...'"
                    class="rounded-b-lg"
                >
                    <template v-slot:loader>
                        <v-progress-linear
                            indeterminate
                            color="primary"
                            height="2"
                        ></v-progress-linear>
                    </template>

                    <template v-slot:item.channel="{ item }">
                        <v-chip
                            :color="store.getChannelColor(item.channel)"
                            variant="flat"
                            size="small"
                            class="font-weight-medium"
                        >
                            {{ item.channel }}
                        </v-chip>
                    </template>

                    <template v-slot:item.content="{ item }">
                        <span
                            class="text-truncate d-inline-block"
                            style="max-width: 200px"
                        >
                            {{ item.content }}
                        </span>
                    </template>

                    <template v-slot:item.actions="{ item }">
                        <div class="d-flex align-center">
                            <v-tooltip location="top" text="Edit">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        v-bind="props"
                                        icon
                                        size="small"
                                        color="primary"
                                        class="me-1"
                                        variant="text"
                                        @click="openEditDialog(item)"
                                    >
                                        <v-icon>mdi-pencil</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>

                            <v-tooltip location="top" text="Delete">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        v-bind="props"
                                        icon
                                        size="small"
                                        color="error"
                                        class="me-1"
                                        variant="text"
                                        @click="openDeleteDialog(item)"
                                    >
                                        <v-icon>mdi-delete</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>

                            <v-tooltip location="top" text="Clone">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        v-bind="props"
                                        icon
                                        size="small"
                                        color="info"
                                        class="me-1"
                                        variant="text"
                                        @click="handleClone(item)"
                                    >
                                        <v-icon>mdi-content-copy</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>

                            <v-tooltip location="top" text="Preview">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        v-bind="props"
                                        icon
                                        size="small"
                                        color="success"
                                        variant="text"
                                        @click="openPreviewDialog(item)"
                                    >
                                        <v-icon>mdi-eye</v-icon>
                                    </v-btn>
                                </template>
                            </v-tooltip>
                        </div>
                    </template>

                    <template v-slot:no-data>
                        <div class="d-flex flex-column align-center py-6">
                            <v-icon
                                size="64"
                                color="grey-lighten-1"
                                class="mb-4"
                                >mdi-file-document-outline</v-icon
                            >
                            <p class="text-body-1 text-medium-emphasis mb-4">
                                No templates found
                            </p>
                            <v-btn
                                color="primary"
                                variant="elevated"
                                @click="initialize"
                            >
                                Refresh Data
                            </v-btn>
                        </div>
                    </template>
                </v-data-table>
            </v-card>
        </v-container>

        <!-- Edit/Create Dialog -->
        <v-dialog v-model="dialog" max-width="900px" persistent>
            <v-card class="rounded-lg">
                <v-card-title
                    class="d-flex justify-space-between pa-6 bg-surface"
                >
                    <span class="text-h5 font-weight-bold">{{
                        formTitle
                    }}</span>
                    <v-btn icon @click="closeDialog" variant="text">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>

                <v-divider></v-divider>

                <v-card-text class="pa-6">
                    <v-form ref="form" v-model="isFormValid">
                        <v-row>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="formData.name"
                                    label="Template Name"
                                    variant="outlined"
                                    prepend-inner-icon="mdi-file-document-outline"
                                    :rules="[
                                        (v) =>
                                            !!v || 'Template name is required',
                                    ]"
                                ></v-text-field>
                            </v-col>

                            <v-col cols="12">
                                <v-textarea
                                    v-model="formData.content"
                                    label="Template Content"
                                    variant="outlined"
                                    prepend-inner-icon="mdi-card-text-outline"
                                    auto-grow
                                    rows="8"
                                    :rules="[
                                        (v) => !!v || 'Content is required',
                                    ]"
                                ></v-textarea>
                            </v-col>

                            <v-col cols="12">
                                <v-combobox
                                    v-model="placeholders"
                                    label="Available Placeholders"
                                    variant="outlined"
                                    prepend-inner-icon="mdi-code-braces"
                                    chips
                                    closable-chips
                                    multiple
                                    small-chips
                                    hide-details
                                ></v-combobox>
                                <div class="text-caption text-grey mt-2">
                                    Add placeholders that can be used in this
                                    template (e.g., {name}, {order_id})
                                </div>
                            </v-col>

                            <v-col
                                cols="12"
                                v-if="formData.channel === 'Email'"
                            >
                                <v-card variant="outlined" class="mt-4">
                                    <v-card-title class="text-subtitle-1">
                                        <v-icon start color="info"
                                            >mdi-information-outline</v-icon
                                        >
                                        Template Preview
                                    </v-card-title>
                                    <v-card-text>
                                        <div
                                            v-html="formData.content"
                                            class="preview-area pa-4 rounded-lg"
                                        ></div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                        </v-row>
                    </v-form>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-6">
                    <v-spacer></v-spacer>
                    <v-btn
                        color="error"
                        variant="text"
                        @click="closeDialog"
                        class="text-capitalize"
                    >
                        Cancel
                    </v-btn>
                    <v-btn
                        color="primary"
                        variant="elevated"
                        @click="handleSave"
                        :loading="store.saving"
                        :disabled="!isFormValid || store.saving"
                        class="text-capitalize"
                    >
                        Save
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation Dialog -->
        <v-dialog v-model="dialogDelete" max-width="500px" persistent>
            <v-card class="rounded-lg">
                <v-card-title class="text-h5 pa-6"
                    >Confirm Deletion</v-card-title
                >
                <v-card-text class="pa-6">
                    Are you sure you want to delete template
                    <strong>{{ itemToDelete?.name }}</strong
                    >? This action cannot be undone.
                </v-card-text>
                <v-divider></v-divider>
                <v-card-actions class="pa-6">
                    <v-spacer></v-spacer>
                    <v-btn
                        color="grey"
                        variant="text"
                        @click="closeDeleteDialog"
                        class="text-capitalize"
                    >
                        Cancel
                    </v-btn>
                    <v-btn
                        color="error"
                        variant="elevated"
                        @click="handleDelete"
                        :loading="store.deleting"
                        class="text-capitalize"
                    >
                        Delete
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Preview Dialog -->
        <v-dialog v-model="dialogPreview" max-width="800px">
            <v-card class="rounded-lg">
                <v-card-title
                    class="d-flex justify-space-between pa-6 bg-surface"
                >
                    <span class="text-h5 font-weight-bold"
                        >Template Preview</span
                    >
                    <v-btn icon @click="dialogPreview = false" variant="text">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>

                <v-divider></v-divider>

                <v-card-text class="pa-6">
                    <div
                        v-if="store.previewItem.channel === 'Email'"
                        class="email-preview"
                    >
                        <div
                            class="email-header pa-4 rounded-t-lg bg-grey-lighten-4 border-b"
                        >
                            <div class="text-subtitle-1 font-weight-bold">
                                {{ store.previewItem.name }}
                            </div>
                            <div class="text-caption text-grey">
                                {{ store.previewItem.module }} Template
                            </div>
                        </div>
                        <div
                            class="email-body pa-6"
                            v-html="store.processedPreviewContent"
                        ></div>
                    </div>

                    <div
                        v-else-if="store.previewItem.channel === 'SMS'"
                        class="sms-preview"
                    >
                        <v-card
                            variant="outlined"
                            class="pa-4 bg-grey-lighten-4"
                        >
                            <div class="text-body-1">
                                {{ store.processedPreviewContent }}
                            </div>
                        </v-card>
                    </div>

                    <div v-else class="generic-preview">
                        <v-card variant="outlined" class="pa-4">
                            <div class="text-subtitle-2 mb-2">
                                {{ store.previewItem.channel }} Template:
                                {{ store.previewItem.name }}
                            </div>
                            <div class="text-body-2">
                                {{ store.processedPreviewContent }}
                            </div>
                        </v-card>
                    </div>

                    <v-divider class="my-6"></v-divider>

                    <div>
                        <div class="text-subtitle-1 mb-3">
                            Replace Placeholders:
                        </div>
                        <v-row>
                            <v-col
                                cols="12"
                                md="6"
                                v-for="placeholder in previewPlaceholders"
                                :key="placeholder"
                            >
                                <v-text-field
                                    :model-value="
                                        store.placeholderValues[placeholder]
                                    "
                                    @update:model-value="
                                        updatePlaceholder(placeholder, $event)
                                    "
                                    :label="placeholder"
                                    variant="outlined"
                                    density="comfortable"
                                ></v-text-field>
                            </v-col>
                        </v-row>
                    </div>
                </v-card-text>

                <v-divider></v-divider>

                <v-card-actions class="pa-6">
                    <v-spacer></v-spacer>
                    <v-btn
                        color="primary"
                        variant="text"
                        @click="dialogPreview = false"
                        class="text-capitalize"
                    >
                        Close
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useTemplatesStore } from "@/stores/templates";
import AppLayout from "@/Layouts/AppLayout.vue";

const store = useTemplatesStore();

// Component state
const dialog = ref(false);
const dialogDelete = ref(false);
const dialogPreview = ref(false);
const isFormValid = ref(false);
const form = ref(null);
const searchQuery = ref("");
const placeholders = ref([]);
const itemToDelete = ref(null);
const editMode = ref(false);

// Form data
const formData = ref({
    id: null,
    name: "",
    channel: "",
    module: "",
    content: "",
    placeholders: "",
    owner_type: "system",
    owner_id: null,
});

// Table headers
const headers = [
    { title: "Template Name", align: "start", key: "name" },
    { title: "Content", key: "content" },
    { title: "Actions", key: "actions", sortable: false, align: "end" },
];

// Computed
const formTitle = computed(() =>
    editMode.value ? "Edit Template" : "Create New Template"
);

const previewPlaceholders = computed(() =>
    store.extractPlaceholders(store.previewItem.placeholders)
);

// Methods
const initialize = async () => {
    const result = await store.fetchTemplates();
    if (!result.success) {
        showError("Failed to load templates");
    }
};

const handleSearch = async () => {
    const result = await store.searchTemplates(searchQuery.value);
    if (!result.success) {
        showError("Search failed");
    }
};

const handleClearSearch = async () => {
    searchQuery.value = "";
    await initialize();
};

const openCreateDialog = async () => {
    editMode.value = false;
    resetForm();
    await store.fetchUsers();
    dialog.value = true;
};

const openEditDialog = async (item) => {
    editMode.value = true;
    formData.value = { ...item };

    // Parse placeholders
    placeholders.value = store.extractPlaceholders(item.placeholders);

    await store.fetchUsers();
    dialog.value = true;
};

const openDeleteDialog = (item) => {
    itemToDelete.value = item;
    dialogDelete.value = true;
};

const openPreviewDialog = (item) => {
    store.setPreviewItem(item);
    dialogPreview.value = true;
};

const handleClone = async (item) => {
    const clonedItem = store.cloneTemplate(item);
    formData.value = clonedItem;
    editMode.value = false;

    placeholders.value = store.extractPlaceholders(clonedItem.placeholders);

    await store.fetchUsers();
    dialog.value = true;
};

const handleSave = async () => {
    if (!isFormValid.value) return;

    const payload = {
        ...formData.value,
        placeholders: placeholders.value,
    };

    const result = editMode.value
        ? await store.updateTemplate(formData.value.id, payload)
        : await store.createTemplate(payload);

    if (result.success) {
        showSuccess(
            `Template ${editMode.value ? "updated" : "created"} successfully`
        );
        closeDialog();
    } else {
        showError(`Failed to ${editMode.value ? "update" : "create"} template`);
    }
};

const handleDelete = async () => {
    if (!itemToDelete.value) return;

    const result = await store.deleteTemplate(itemToDelete.value.id);

    if (result.success) {
        showSuccess("Template deleted successfully");
        closeDeleteDialog();
    } else {
        showError("Failed to delete template");
    }
};

const closeDialog = () => {
    dialog.value = false;
    resetForm();
};

const closeDeleteDialog = () => {
    dialogDelete.value = false;
    itemToDelete.value = null;
};

const resetForm = () => {
    if (form.value) {
        form.value.reset();
    }
    formData.value = {
        id: null,
        name: "",
        channel: "",
        module: "",
        content: "",
        placeholders: "",
        owner_type: "system",
        owner_id: null,
    };
    placeholders.value = [];
    editMode.value = false;
};

const updatePlaceholder = (placeholder, value) => {
    store.updatePlaceholderValue(placeholder, value);
};

// Toast notifications
const showSuccess = (message) => {
    // Replace with your toast library
    console.log("Success:", message);
};

const showError = (message) => {
    // Replace with your toast library
    console.error("Error:", message);
};

// Lifecycle
onMounted(() => {
    initialize();
});

// Watchers
watch(dialog, (val) => {
    if (!val) {
        closeDialog();
    }
});

watch(dialogDelete, (val) => {
    if (!val) {
        closeDeleteDialog();
    }
});
</script>

<style scoped>
.v-data-table :deep(.v-table__wrapper) {
    border-radius: 0 0 8px 8px;
}

.v-data-table {
    box-shadow: none !important;
}

.preview-area {
    border: 1px solid #e0e0e0;
    min-height: 150px;
    background-color: #f9f9f9;
    overflow-wrap: break-word;
}

.email-preview .email-header {
    border-bottom: 1px solid #e0e0e0;
}

.email-preview .email-body {
    min-height: 200px;
    overflow-wrap: break-word;
}
</style>
