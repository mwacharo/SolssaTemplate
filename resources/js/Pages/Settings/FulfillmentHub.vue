<template>
    <AppLayout>
        <v-container fluid class="pa-0">
            <!-- Header -->
            <v-app-bar color="white" elevation="1" height="64">
                <v-container class="d-flex align-center">
                    <v-app-bar-title class="text-h5 font-weight-medium">
                        Team
                    </v-app-bar-title>
                </v-container>
            </v-app-bar>

            <v-container class="py-6">
                <!-- Info banner -->
                <v-card class="mb-6" elevation="1">
                    <v-card-text class="pa-6">
                        <v-card-title class="text-h6 pa-0 mb-4"
                            >Create Team</v-card-title
                        >
                        <v-row align="center">
                            <v-col>
                                <p
                                    class="text-body-2 text-medium-emphasis mb-0"
                                >
                                    Team management
                                </p>
                            </v-col>
                            <v-col cols="auto">
                                <v-btn
                                    color="primary"
                                    prepend-icon="mdi-plus"
                                    @click="openCreateWizard"
                                    :loading="store.loading"
                                >
                                    New Team
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                <!-- Hubs table -->
                <v-card elevation="1">
                    <v-card-text class="pa-6 pb-0">
                        <v-card-title class="text-h6 pa-0 mb-4"
                            >Teams</v-card-title
                        >

                        <v-row
                            class="mb-4"
                            align="center"
                            justify="space-between"
                        >
                            <v-col cols="auto">
                                <v-text-field
                                    v-model="searchQuery"
                                    placeholder="Search..."
                                    prepend-inner-icon="mdi-magnify"
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    style="min-width: 250px"
                                />
                            </v-col>
                        </v-row>
                    </v-card-text>

                    <v-data-table
                        :headers="headers"
                        :items="displayedHubs"
                        :search="searchQuery"
                        item-key="id"
                        class="elevation-0"
                        :loading="store.loading"
                    >
                        <template v-slot:item.name="{ item }">
                            <span class="font-weight-medium">{{
                                item.name
                            }}</span>
                        </template>

                        <template v-slot:item.country="{ item }">
                            <v-chip
                                size="small"
                                variant="tonal"
                                color="primary"
                            >
                                {{ item.country }}
                            </v-chip>
                        </template>

                        <template v-slot:item.status="{ item }">
                            <v-chip
                                :color="
                                    item.status === 'Active'
                                        ? 'success'
                                        : 'error'
                                "
                                size="small"
                                variant="tonal"
                            >
                                <v-icon
                                    icon="mdi-circle"
                                    size="x-small"
                                    class="mr-1"
                                />
                                {{ item.status }}
                            </v-chip>
                        </template>

                        <template v-slot:item.vendors="{ item }">
                            <span class="text-body-2">{{ item.vendors }}</span>
                        </template>

                        <template v-slot:item.agents="{ item }">
                            <span class="text-body-2">{{ item.agents }}</span>
                        </template>

                        <template v-slot:item.lastCallAgent="{ item }">
                            <span class="text-body-2">{{
                                item.lastCallAgent || "—"
                            }}</span>
                        </template>

                        <template v-slot:item.createdAt="{ item }">
                            <span class="text-body-2">{{
                                formatDate(item.createdAt)
                            }}</span>
                        </template>

                        <template v-slot:item.actions="{ item }">
                            <div class="d-flex ga-1">
                                <v-btn
                                    size="small"
                                    :color="
                                        item.status === 'Active'
                                            ? 'error'
                                            : 'success'
                                    "
                                    variant="flat"
                                    @click="toggleStatus(item.id)"
                                    :loading="store.loading"
                                >
                                    {{
                                        item.status === "Active"
                                            ? "Deactivate"
                                            : "Activate"
                                    }}
                                </v-btn>
                                <v-btn
                                    size="small"
                                    color="grey-darken-1"
                                    variant="flat"
                                    @click="openSettings(item)"
                                >
                                    Settings
                                </v-btn>
                                <v-btn
                                    size="small"
                                    color="error"
                                    variant="flat"
                                    @click="deleteHub(item.id)"
                                    :loading="store.loading"
                                >
                                    Delete
                                </v-btn>
                            </div>
                        </template>

                        <template v-slot:no-data>
                            <div class="text-center py-6">
                                <p class="text-body-1 mb-3">
                                    No fulfillment hubs found
                                </p>
                                <v-btn
                                    color="primary"
                                    @click="openCreateWizard"
                                >
                                    Create your first hub
                                </v-btn>
                            </div>
                        </template>
                    </v-data-table>
                </v-card>
            </v-container>

            <!-- ─── Create Wizard Dialog ─────────────────────────────────────── -->
            <v-dialog v-model="showWizard" max-width="620" persistent>
                <v-card>
                    <v-card-title
                        class="d-flex justify-space-between align-center pa-6 pb-2"
                    >
                        <span class="text-h6">New Team</span>
                        <v-btn icon variant="text" @click="closeWizard">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </v-card-title>

                    <!-- Stepper header -->
                    <v-card-text class="px-6 pt-2 pb-0">
                        <v-stepper
                            v-model="wizardStep"
                            :items="[
                                'Team details',
                                'Assign vendors',
                                'Assign agents',
                            ]"
                            hide-actions
                            flat
                            class="mb-4"
                        />
                    </v-card-text>

                    <v-divider />

                    <!-- Step 1: Team details -->
                    <v-card-text v-if="wizardStep === 1" class="pa-6">
                        <v-form ref="step1Form" v-model="step1Valid">
                            <v-text-field
                                v-model="newHub.name"
                                label="Hub name"
                                placeholder="e.g. Nairobi Fulfillment Hub"
                                variant="outlined"
                                :rules="[(v) => !!v || 'Hub name is required']"
                                class="mb-3"
                                hide-details="auto"
                            />

                            <v-textarea
                                v-model="newHub.description"
                                label="Description (optional)"
                                placeholder="Brief description of this hub's role…"
                                variant="outlined"
                                rows="3"
                                hide-details="auto"
                            />
                        </v-form>
                    </v-card-text>

                    <!-- Step 2: Assign vendors -->
                    <v-card-text v-if="wizardStep === 2" class="pa-6">
                        <v-select
                            v-model="newHub.vendor_ids"
                            :items="vendorOptions"
                            item-title="name"
                            item-value="id"
                            label="Select vendors"
                            multiple
                            chips
                            clearable
                            variant="outlined"
                            density="compact"
                            hide-details
                            class="mb-3"
                        />
                        <p class="text-caption text-medium-emphasis mb-2">
                            {{ newHub.vendor_ids.length }} vendor{{
                                newHub.vendor_ids.length === 1 ? "" : "s"
                            }}
                            selected
                        </p>
                        <v-row dense>
                            <v-col
                                v-for="vendor in vendorOptions"
                                :key="vendor.id"
                                cols="6"
                            >
                                <v-card
                                    :color="
                                        newHub.vendor_ids.includes(vendor.id)
                                            ? 'primary'
                                            : undefined
                                    "
                                    :variant="
                                        newHub.vendor_ids.includes(vendor.id)
                                            ? 'tonal'
                                            : 'outlined'
                                    "
                                    class="pa-3 cursor-pointer"
                                    @click="toggleVendor(vendor.id)"
                                >
                                    <div class="d-flex align-start gap-2">
                                        <v-checkbox-btn
                                            :model-value="
                                                newHub.vendor_ids.includes(
                                                    vendor.id,
                                                )
                                            "
                                            density="compact"
                                            color="primary"
                                            @click.stop="
                                                toggleVendor(vendor.id)
                                            "
                                        />
                                        <div>
                                            <p
                                                class="text-body-2 font-weight-medium mb-0"
                                            >
                                                {{ vendor.name }}
                                            </p>
                                            <p
                                                class="text-caption text-medium-emphasis mb-0"
                                            >
                                                {{ vendor.category }}
                                            </p>
                                        </div>
                                    </div>
                                </v-card>
                            </v-col>
                        </v-row>
                        <v-alert
                            v-if="!vendorOptions.length"
                            type="info"
                            variant="tonal"
                            class="mt-3"
                            density="compact"
                        >
                            No vendors found. Add vendors first.
                        </v-alert>
                    </v-card-text>

                    <!-- Step 3: Assign agents -->
                    <v-card-text v-if="wizardStep === 3" class="pa-6">
                        <v-autocomplete
                            v-model="newHub.agent_ids"
                            :items="agentOptions"
                            item-title="name"
                            item-value="id"
                            label="Select agents"
                            placeholder="Search agents…"
                            multiple
                            chips
                            clearable
                            variant="outlined"
                            hide-details="auto"
                            class="mb-3"
                        />
                        <p class="text-caption text-medium-emphasis mb-2">
                            {{ newHub.agent_ids.length }} agent{{
                                newHub.agent_ids.length === 1 ? "" : "s"
                            }}
                            selected
                        </p>

                        <v-alert
                            v-if="!agentOptions.length"
                            type="info"
                            variant="tonal"
                            density="compact"
                        >
                            No agents found. Add users with agent roles first.
                        </v-alert>
                    </v-card-text>

                    <v-divider />

                    <!-- Wizard actions -->
                    <v-card-actions class="pa-4">
                        <v-btn
                            v-if="wizardStep > 1"
                            variant="text"
                            @click="wizardStep--"
                        >
                            <v-icon start>mdi-arrow-left</v-icon>
                            Back
                        </v-btn>
                        <v-spacer />
                        <v-btn variant="text" @click="closeWizard"
                            >Cancel</v-btn
                        >
                        <v-btn
                            v-if="wizardStep < 3"
                            color="primary"
                            @click="nextStep"
                        >
                            Next
                            <v-icon end>mdi-arrow-right</v-icon>
                        </v-btn>
                        <v-btn
                            v-else
                            color="primary"
                            @click="submitHub"
                            :loading="store.loading"
                        >
                            <v-icon start>mdi-check</v-icon>
                            Create hub
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- ─── Settings Modal ────────────────────────────────────────────── -->
            <v-dialog v-model="showSettingsModal" max-width="560">
                <v-card>
                    <v-card-title
                        class="d-flex justify-space-between align-center pa-6 pb-2"
                    >
                        <span class="text-h6">Hub settings</span>
                        <v-btn
                            icon
                            variant="text"
                            @click="showSettingsModal = false"
                        >
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </v-card-title>

                    <v-card-text class="pa-6">
                        <v-form ref="settingsForm">
                            <v-text-field
                                v-model="currentSettings.name"
                                label="Hub name"
                                variant="outlined"
                                class="mb-3"
                                hide-details="auto"
                            />

                            <v-autocomplete
                                v-model="currentSettings.last_call_agent_id"
                                label="Last call agent (fallback)"
                                placeholder="None"
                                variant="outlined"
                                item-title="name"
                                item-value="id"
                                class="mb-3"
                                hide-details="auto"
                                clearable
                            />

                            <v-divider class="mb-4" />

                            <p class="text-subtitle-2 mb-3">Vendors</p>
                            <v-autocomplete
                                v-model="currentSettings.vendor_ids"
                                :items="vendorOptions"
                                label="Assigned vendors"
                                variant="outlined"
                                item-title="name"
                                item-value="id"
                                multiple
                                chips
                                closable-chips
                                class="mb-3"
                                hide-details="auto"
                            />

                            <p class="text-subtitle-2 mb-3">Agents</p>
                            <v-autocomplete
                                v-model="currentSettings.agent_ids"
                                :items="agentOptions"
                                label="Assigned agents"
                                variant="outlined"
                                item-title="name"
                                item-value="id"
                                multiple
                                chips
                                closable-chips
                                hide-details="auto"
                            />
                        </v-form>
                    </v-card-text>

                    <v-card-actions class="pa-4">
                        <v-spacer />
                        <v-btn variant="text" @click="showSettingsModal = false"
                            >Cancel</v-btn
                        >
                        <v-btn
                            color="primary"
                            @click="updateHub"
                            :loading="store.loading"
                        >
                            Save changes
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-container>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { useFulfillmentHubStore } from "@/stores/fulfillmentHub";
// import { use } from "@/stores/";
import { useClientStore } from "@/stores/clientStore";

const store = useFulfillmentHubStore();

const clientStore = useClientStore();

// ── UI state ────────────────────────────────────────────────────────────────
const activeTab = ref("active");
const searchQuery = ref("");
const showWizard = ref(false);
const showSettingsModal = ref(false);
const wizardStep = ref(1);
const step1Form = ref(null);
const step1Valid = ref(false);
const vendorSearch = ref("");
const agentSearch = ref("");

// ── Options ─────────────────────────────────────────────────────────────────
const vendorOptions = ref([]);
const agentOptions = ref([]);
const vendorLoading = ref(false);
const agentLoading = ref(false);

// Load vendors
const loadVendors = async () => {
    try {
        vendorLoading.value = true;
        await clientStore.fetchVendors();
        vendorOptions.value = clientStore.vendors.map((vendor) => ({
            id: vendor.id,
            name: vendor.name,
        }));
    } catch (error) {
        console.error("Failed to load vendors:", error);
    } finally {
        vendorLoading.value = false;
    }
};

// load agents
const loadAgents = async () => {
    try {
        agentLoading.value = true;
        await clientStore.fetchAgents();
        agentOptions.value = clientStore.agents.map((agent) => ({
            id: agent.id,
            name: agent.name,
        }));
    } catch (error) {
        console.error("Failed to load agents:", error);
    } finally {
        agentLoading.value = false;
    }
};

// ── New hub form ─────────────────────────────────────────────────────────────
const defaultNewHub = () => ({
    name: "",
    country_id: null,
    description: "",
    vendor_ids: [],
    agent_ids: [],
});
const newHub = ref(defaultNewHub());

// ── Settings modal ────────────────────────────────────────────────────────────
const currentSettings = ref({});

// ── Table headers ─────────────────────────────────────────────────────────────
const headers = [
    { title: "Hub name", key: "name", sortable: true },
    { title: "Country", key: "country", sortable: true },
    { title: "Status", key: "status", sortable: true },
    { title: "Vendors", key: "vendors", sortable: false },
    { title: "Agents", key: "agents", sortable: false },
    { title: "Last call agent", key: "lastCallAgent", sortable: false },
    { title: "Created", key: "createdAt", sortable: true },
    { title: "Actions", key: "actions", sortable: false },
];

// ── Computed ─────────────────────────────────────────────────────────────────
const mappedHubs = computed(() => {
    const hubs = store.hubs?.data || [];
    return hubs.map((h) => ({
        id: h.id,
        name: h.name,
        country: h.country?.name || "—",
        // status: h.active ? "Active" : "Inactive",
        status: "Active",
        vendors: h.vendors?.length ?? 0,
        agents: h.agents?.length ?? 0,
        lastCallAgent: h.last_call_agent?.name || null,
        createdAt: h.created_at,
        // raw refs for settings modal
        country_id: h.country_id,
        last_call_agent_id: h.last_call_agent_id,
        vendor_ids: h.vendors?.map((v) => v.id) || [],
        agent_ids: h.agents?.map((a) => a.id) || [],
    }));
});

const displayedHubs = computed(() => {
    if (activeTab.value === "active") {
        return mappedHubs.value.filter((h) => h.status === "Active");
    }
    return mappedHubs.value;
});

// ── Helpers ───────────────────────────────────────────────────────────────────
const formatDate = (d) => (d ? new Date(d).toLocaleDateString() : "—");
const initials = (name) =>
    name
        ?.split(" ")
        .map((p) => p[0])
        .join("")
        .toUpperCase()
        .slice(0, 2) || "?";

// ── Wizard ────────────────────────────────────────────────────────────────────
const openCreateWizard = () => {
    newHub.value = defaultNewHub();
    wizardStep.value = 1;
    vendorSearch.value = "";
    agentSearch.value = "";
    showWizard.value = true;
};

const closeWizard = () => {
    showWizard.value = false;
};

const nextStep = async () => {
    if (wizardStep.value === 1) {
        const { valid } = await step1Form.value.validate();
        if (!valid) return;
    }
    wizardStep.value++;
};

const toggleVendor = (id) => {
    const idx = newHub.value.vendor_ids.indexOf(id);
    idx === -1
        ? newHub.value.vendor_ids.push(id)
        : newHub.value.vendor_ids.splice(idx, 1);
};

const toggleAgent = (id) => {
    const idx = newHub.value.agent_ids.indexOf(id);
    idx === -1
        ? newHub.value.agent_ids.push(id)
        : newHub.value.agent_ids.splice(idx, 1);
};

const submitHub = async () => {
    try {
        await store.addHub(newHub.value);
        closeWizard();
    } catch (e) {
        console.error("Failed to create hub:", e);
    }
};

// ── Table actions ─────────────────────────────────────────────────────────────
const toggleStatus = async (id) => {
    try {
        await store.toggleStatus(id);
    } catch (e) {
        console.error("Failed to toggle status:", e);
    }
};

const deleteHub = async (id) => {
    if (!confirm("Delete this fulfillment hub? This action cannot be undone."))
        return;
    try {
        await store.deleteHub(id);
    } catch (e) {
        console.error("Failed to delete hub:", e);
    }
};

const openSettings = (hub) => {
    const raw = store.hubs?.data?.find((h) => h.id === hub.id);
    currentSettings.value = {
        id: hub.id,
        name: hub.name,
        country_id: hub.country_id,
        last_call_agent_id: hub.last_call_agent_id,
        vendor_ids: [...(hub.vendor_ids || [])],
        agent_ids: [...(hub.agent_ids || [])],
        ...(raw || {}),
    };
    showSettingsModal.value = true;
};

const updateHub = async () => {
    try {
        await store.updateHub(currentSettings.value.id, {
            name: currentSettings.value.name,
            country_id: currentSettings.value.country_id,
            last_call_agent_id: currentSettings.value.last_call_agent_id,
            vendor_ids: currentSettings.value.vendor_ids,
            agent_ids: currentSettings.value.agent_ids,
        });
        showSettingsModal.value = false;
    } catch (e) {
        console.error("Failed to update hub:", e);
    }
};

// ── Init ──────────────────────────────────────────────────────────────────────
onMounted(async () => {
    try {
        await Promise.all([store.fetchHubs(), loadVendors(), loadAgents()]);
    } catch (e) {
        console.error("Failed to initialise FulfillmentHub page:", e);
    }
});
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}
</style>
