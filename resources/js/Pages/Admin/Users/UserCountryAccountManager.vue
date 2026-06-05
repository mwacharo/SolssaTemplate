<template>
    <!-- NO activator slot — opened programmatically by parent -->
    <v-dialog v-model="dialog" max-width="680" scrollable>
        <v-card rounded="lg">
            <!-- Header -->
            <v-card-title class="d-flex align-center gap-2 pa-4 pb-3">
                <v-icon icon="mdi-wallet-outline" size="20" />
                <span class="text-body-1 font-weight-medium"
                    >Country accounts</span
                >
                <v-chip size="x-small" class="ml-1">{{
                    accounts.length
                }}</v-chip>
                <v-spacer />
                <v-btn
                    icon="mdi-plus"
                    size="small"
                    variant="tonal"
                    @click="openForm(null)"
                />
                <v-btn
                    icon="mdi-close"
                    size="small"
                    variant="text"
                    @click="dialog = false"
                />
            </v-card-title>
            <v-divider />

            <!-- Account list -->
            <v-card-text
                v-if="!showForm"
                class="pa-3"
                style="min-height: 300px"
            >
                <v-progress-linear
                    v-if="loading"
                    indeterminate
                    color="primary"
                    class="mb-2"
                />

                <v-alert
                    v-if="!loading && !accounts.length"
                    type="info"
                    variant="tonal"
                    text="No accounts found. Add one to get started."
                    class="mt-2"
                />

                <v-card
                    v-for="account in accounts"
                    :key="account.id"
                    variant="outlined"
                    rounded="lg"
                    class="mb-2"
                >
                    <v-card-text class="d-flex align-center gap-3 pa-3">
                        <v-avatar size="36" color="primary" variant="tonal">
                            {{ account.country?.code ?? "??" }}
                        </v-avatar>

                        <div class="flex-1" style="min-width: 0">
                            <div class="d-flex align-center gap-2">
                                <span class="text-body-2 font-weight-medium">{{
                                    account.client_name
                                }}</span>
                                <v-chip
                                    v-if="account.is_default"
                                    size="x-small"
                                    color="success"
                                    variant="tonal"
                                >
                                    default
                                </v-chip>
                            </div>
                            <div class="text-caption text-medium-emphasis">
                                {{ account.phone_number }} ·
                                {{ account.country?.name }}
                            </div>
                        </div>

                        <div class="d-flex gap-1">
                            <v-btn
                                v-if="!account.is_default"
                                icon="mdi-star-outline"
                                size="x-small"
                                variant="text"
                                title="Set as default"
                                @click="setDefault(account)"
                            />
                            <v-btn
                                icon="mdi-pencil-outline"
                                size="x-small"
                                variant="text"
                                color="warning"
                                @click="openForm(account)"
                            />
                            <v-btn
                                icon="mdi-delete-outline"
                                size="x-small"
                                variant="text"
                                color="error"
                                :loading="deletingId === account.id"
                                @click="confirmDelete(account)"
                            />
                        </div>
                    </v-card-text>
                </v-card>
            </v-card-text>

            <!-- Create / Edit form -->
            <v-card-text v-else class="pa-4" style="min-height: 300px">
                <div class="d-flex align-center gap-2 mb-4">
                    <v-btn
                        icon="mdi-arrow-left"
                        size="x-small"
                        variant="text"
                        @click="showForm = false"
                    />
                    <span class="text-body-2 font-weight-medium">
                        {{ editingAccount ? "Edit account" : "New account" }}
                    </span>
                </div>

                <v-form
                    ref="formRef"
                    v-model="formValid"
                    @submit.prevent="saveAccount"
                >
                    <v-row dense>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                v-model="form.client_name"
                                label="Client name"
                                density="compact"
                                variant="outlined"
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-autocomplete
                                v-model="form.country_id"
                                :items="countries"
                                item-title="name"
                                item-value="id"
                                label="Country"
                                density="compact"
                                variant="outlined"
                                :loading="loadingCountries"
                                :rules="[required]"
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                v-model="form.phone_number"
                                label="Phone number"
                                density="compact"
                                variant="outlined"
                                placeholder="+254 7XX XXX XXX"
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                v-model="form.alt_number"
                                label="Alt. number"
                                density="compact"
                                variant="outlined"
                                placeholder="Optional"
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                v-model="form.country_code"
                                label="Country code"
                                density="compact"
                                variant="outlined"
                                placeholder="+254"
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                v-model="form.token"
                                label="Token"
                                density="compact"
                                variant="outlined"
                                placeholder="API token"
                            />
                        </v-col>
                        <v-col cols="12">
                            <v-checkbox
                                v-model="form.is_default"
                                label="Set as default account"
                                density="compact"
                                hide-details
                            />
                        </v-col>
                    </v-row>
                </v-form>
            </v-card-text>

            <v-divider />
            <v-card-actions class="pa-3">
                <v-spacer />
                <v-btn
                    variant="text"
                    @click="showForm ? (showForm = false) : (dialog = false)"
                >
                    Cancel
                </v-btn>
                <v-btn
                    v-if="showForm"
                    color="primary"
                    variant="tonal"
                    :loading="saving"
                    :disabled="!formValid"
                    @click="saveAccount"
                >
                    Save
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- Delete confirm -->
    <v-dialog v-model="deleteDialog" max-width="400">
        <v-card rounded="lg">
            <v-card-title class="text-body-1 pa-4"
                >Delete account?</v-card-title
            >
            <v-card-text class="text-body-2 text-medium-emphasis">
                "{{ pendingDelete?.client_name }}" will be permanently removed.
            </v-card-text>
            <v-card-actions class="pa-3">
                <v-spacer />
                <v-btn variant="text" @click="deleteDialog = false"
                    >Cancel</v-btn
                >
                <v-btn
                    color="error"
                    variant="tonal"
                    :loading="deletingId === pendingDelete?.id"
                    @click="deleteAccount"
                >
                    Delete
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { ref, reactive } from "vue";
import axios from "axios";

// No props — userId is set via open(userId) from the parent
const currentUserId = ref(null);

const dialog = ref(false);
const showForm = ref(false);
const loading = ref(false);
const saving = ref(false);
const formValid = ref(false);
const formRef = ref(null);

const accounts = ref([]);
const countries = ref([]);
const loadingCountries = ref(false);
const editingAccount = ref(null);
const deletingId = ref(null);
const deleteDialog = ref(false);
const pendingDelete = ref(null);

const emptyForm = () => ({
    user_id: null,

    client_name: "",
    country_id: null,
    phone_number: "",
    alt_number: "",
    country_code: "",
    token: "",
    is_default: false,
});

const form = reactive(emptyForm());
const required = (v) => !!v || "Required";

// ── Public API ──────────────────────────────────────────────
function open(userId) {
    currentUserId.value = userId;
    showForm.value = false;
    editingAccount.value = null;
    Object.assign(form, emptyForm());

    form.user_id = userId;

    dialog.value = true;
    fetchAccounts();
    fetchCountries();
}

defineExpose({ open });
// ────────────────────────────────────────────────────────────

async function fetchAccounts() {
    loading.value = true;
    try {
        const { data } = await axios.get("/api/v1/user/accounts", {
            params: { user_id: currentUserId.value },
        });
        accounts.value = data.data;
    } catch (e) {
        console.error("Failed to fetch accounts", e);
    } finally {
        loading.value = false;
    }
}

async function fetchCountries() {
    if (countries.value.length) return;
    loadingCountries.value = true;
    try {
        const { data } = await axios.get("/api/v1/countries");
        countries.value = data.data ?? data;
    } finally {
        loadingCountries.value = false;
    }
}

function openForm(account) {
    editingAccount.value = account;
    Object.assign(form, account ? { ...account } : emptyForm());
    form.user_id = currentUserId.value;

    showForm.value = true;
}

async function saveAccount() {
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    saving.value = true;

    // Only send what the API expects
    const payload = {
        user_id:      form.user_id,
        client_name:  form.client_name  || null,
        country_id:   form.country_id,
        phone_number: form.phone_number || null,
        alt_number:   form.alt_number   || null,
        country_code: form.country_code || null,
        token:        form.token        || null,
        is_default:   form.is_default,
    };

    try {
        let saved;

        if (editingAccount.value) {
            const { data } = await axios.put(
                `/api/v1/user/accounts/${editingAccount.value.id}`,
                payload,   // ← not form
            );
            saved = data.data;
            const idx = accounts.value.findIndex(a => a.id === editingAccount.value.id);
            accounts.value.splice(idx, 1, saved);
        } else {
            const { data } = await axios.post('/api/v1/user/accounts', payload);  // ← not form
            saved = data.data;
            accounts.value.push(saved);
        }

        if (form.is_default) {
            accounts.value.forEach(a => {
                if (a.id !== saved.id) a.is_default = false;
            });
        }

        showForm.value = false;
    } catch (e) {
        console.error('Failed to save account', e);
    } finally {
        saving.value = false;
    }
}

async function setDefault(account) {
    try {
        await axios.patch(`/api/v1/user/accounts/${account.id}/default`);
        accounts.value.forEach((a) => {
            a.is_default = a.id === account.id;
        });
    } catch (e) {
        console.error("Failed to set default", e);
    }
}

function confirmDelete(account) {
    pendingDelete.value = account;
    deleteDialog.value = true;
}

async function deleteAccount() {
    deletingId.value = pendingDelete.value.id;
    try {
        await axios.delete(`/api/v1/user/accounts/${pendingDelete.value.id}`);
        accounts.value = accounts.value.filter(
            (a) => a.id !== pendingDelete.value.id,
        );
        deleteDialog.value = false;
    } catch (e) {
        console.error("Failed to delete account", e);
    } finally {
        deletingId.value = null;
    }
}
</script>
