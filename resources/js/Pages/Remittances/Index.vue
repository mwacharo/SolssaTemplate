<template>
    <AppLayout>
        <div class="pa-4">
            <h2 class="text-h4 mb-6">Agent Payments</h2>

            <v-row>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="filters.from"
                        label="Date From"
                        type="date"
                        variant="outlined"
                        density="comfortable"
                    />
                </v-col>

                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="filters.to"
                        label="Date To"
                        type="date"
                        variant="outlined"
                        density="comfortable"
                    />
                </v-col>

                <v-col cols="12" md="4">
                    <v-autocomplete
                        v-model="filters.agent"
                        :items="orderStore.agentOptions"
                        item-title="name"
                        item-value="id"
                        label="Call Agent"
                        variant="outlined"
                        density="comfortable"
                        clearable
                        placeholder="Search agents..."
                    />
                </v-col>

                <v-col cols="12">
                    <v-btn
                        color="primary"
                        @click="openDialog"
                        :disabled="!canGenerateInvoice"
                        size="large"
                    >
                        Generate Call Agent Invoice
                    </v-btn>
                </v-col>
            </v-row>

            <!-- Current Invoices Table -->
            <v-card class="mt-6" v-if="invoices.length > 0">
                <v-card-title>Recent Invoices</v-card-title>
                <v-card-text>
                    <v-table>
                        <thead>
                            <tr>
                                <th>Invoice No</th>
                                <th>Agent</th>
                                <th>Period</th>
                                <th>Orders</th>
                                <th>Commission</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="invoice in invoices" :key="invoice.id">
                                <td>{{ invoice.invoice_no }}</td>
                                <td>{{ invoice.agent_name }}</td>
                                <td>{{ invoice.period }}</td>
                                <td>{{ invoice.order_count }}</td>
                                <td>
                                    {{
                                        formatCurrency(invoice.total_commission)
                                    }}
                                </td>
                                <td>
                                    <v-chip
                                        :color="getStatusColor(invoice.status)"
                                        size="small"
                                    >
                                        {{ invoice.status }}
                                    </v-chip>
                                </td>
                                <td>
                                    <v-btn
                                        icon="mdi-eye"
                                        size="small"
                                        variant="text"
                                        @click="viewInvoice(invoice)"
                                    />
                                    <v-btn
                                        icon="mdi-download"
                                        size="small"
                                        variant="text"
                                        @click="downloadInvoice(invoice)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card-text>
            </v-card>
        </div>

        <!-- Invoice Generation Dialog -->
        <v-dialog v-model="dialog" max-width="1000" persistent>
            <v-card>
                <v-card-title class="d-flex justify-space-between align-center">
                    <span>Generate Call Agent Invoice</span>
                    <v-btn
                        icon="mdi-close"
                        variant="text"
                        @click="closeDialog"
                    />
                </v-card-title>

                <v-card-text>
                    <!-- Agent Info -->
                    <v-alert
                        type="info"
                        variant="tonal"
                        class="mb-4"
                        v-if="selectedAgent"
                    >
                        <strong>Agent:</strong> {{ selectedAgent.name }} <br />
                        <strong>Period:</strong>
                        {{ formatDate(filters.from) }} to
                        {{ formatDate(filters.to) }}
                    </v-alert>

                    <!-- Commission Rate -->
                    <v-row class="mb-4">
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model.number="rate"
                                label="Commission Rate (per order)"
                                type="number"
                                prefix="KES"
                                variant="outlined"
                                density="comfortable"
                            />
                        </v-col>
                    </v-row>

                    <!-- Loading State -->
                    <div v-if="loading" class="text-center py-8">
                        <v-progress-circular
                            indeterminate
                            color="primary"
                            size="64"
                        />
                        <p class="mt-4">Loading orders...</p>
                    </div>

                    <!-- Orders Table -->
                    <v-table v-else-if="filteredOrders.length > 0" class="mt-4">
                        <thead>
                            <tr>
                                <th>Order No</th>
                                <th>Customer</th>
                                <th>phone</th>
                                <th>COD</th>
                                <th>status date</th>
                                <th>quantity</th>
                                <th>product</th>
                                <th>address</th>
                                <!-- <th>zone</th>
                                <th>city</th> -->
                                <th>Payment</th>
                                <th>Commission</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in filteredOrders" :key="order.id">
                                <td>{{ order.order_no }}</td>
                                <td>
                                    {{ order.customer?.full_name || "N/A" }}
                                </td>
                                <td>{{ order.customer?.phone || "N/A" }}</td>
                                <td>
                                    {{
                                        formatCurrency(
                                            order.cod_amount ||
                                                order.total_price,
                                        )
                                    }}
                                </td>
                                <td>
                                    {{
                                        formatDate(
                                            order.latest_status?.created_at,
                                        )
                                    }}
                                </td>
                                <td>
                                    {{
                                        (order.order_items || []).reduce(
                                            (sum, it) =>
                                                sum +
                                                (Number(it.quantity) || 0),
                                            0,
                                        )
                                    }}
                                </td>
                                <td>
                                    {{
                                        (order.order_items || [])
                                            .map(
                                                (it) =>
                                                    it.product?.product_name ||
                                                    it.sku ||
                                                    "N/A",
                                            )
                                            .join(", ")
                                    }}
                                </td>
                                <td>
                                    {{
                                        order.customer?.address ||
                                        order.shipping_address?.address ||
                                        "N/A"
                                    }}
                                </td>
                                <!-- <td>
                                    {{
                                        order.zone?.name ||
                                        order.customer?.zone_id ||
                                        "N/A"
                                    }}
                                </td>
                                <td>
                                    {{
                                        order.city?.name ||
                                        order.customer?.city_id ||
                                        "N/A"
                                    }}
                                </td> -->
                                <td>
                                    {{
                                        order.payment_method ||
                                        (Number(order.amount_paid || 0) > 0
                                            ? "Prepaid"
                                            : "COD")
                                    }}
                                </td>
                                <td>{{ formatCurrency(rate) }}</td>
                            </tr>
                        </tbody>
                    </v-table>

                    <!-- No Orders Message -->
                    <v-alert v-else type="warning" variant="tonal" class="mt-4">
                        No orders found for the selected period and agent.
                    </v-alert>

                    <!-- Summary -->
                    <v-divider class="my-4" />
                    <v-card variant="tonal" color="primary">
                        <v-card-text>
                            <v-row>
                                <v-col cols="6">
                                    <strong>Total Orders:</strong>
                                    {{ filteredOrders.length }}
                                </v-col>
                                <v-col cols="6" class="text-right">
                                    <strong>Total Commission:</strong>
                                    {{ formatCurrency(totalCommission) }}
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>
                </v-card-text>

                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
                    <v-btn
                        color="success"
                        @click="generateInvoice"
                        :disabled="filteredOrders.length === 0 || loading"
                        :loading="submitting"
                    >
                        Generate Invoice
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Success Snackbar -->
        <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000">
            {{ snackbarText }}
        </v-snackbar>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import { router } from "@inertiajs/vue3";
import { useOrderStore } from "@/stores/orderStore";

// Props
const props = defineProps({
    invoices: {
        type: Array,
        default: () => [],
    },
});

const orderStore = useOrderStore();

// State
const dialog = ref(false);
const loading = ref(false);
const submitting = ref(false);
const snackbar = ref(false);
const snackbarText = ref("");
const snackbarColor = ref("success");

const filters = ref({
    from: "",
    to: "",
    agent: null,
});

const rate = ref(100); // Default commission rate
const filteredOrders = ref([]);

// Computed
const canGenerateInvoice = computed(() => {
    return filters.value.from && filters.value.to && filters.value.agent;
});

const selectedAgent = computed(() => {
    return orderStore.agentOptions.find((a) => a.id === filters.value.agent);
});

const totalCommission = computed(() => {
    return filteredOrders.value.length * rate.value;
});

// Methods
const openDialog = async () => {
    if (!canGenerateInvoice.value) {
        showSnackbar("Please select date range and agent", "error");
        return;
    }

    dialog.value = true;
    loading.value = true;

    try {
        // Fetch orders for the selected agent and date range
        const response = await fetch(
            `/api/v1/orders/agent/${filters.value.agent}?from=${filters.value.from}&to=${filters.value.to}`,
        );
        const data = await response.json();

        if (data.success) {
            filteredOrders.value = data.data || [];
        } else {
            throw new Error(data.message || "Failed to fetch orders");
        }
    } catch (error) {
        console.error("Error fetching orders:", error);
        showSnackbar("Error loading orders", "error");
        filteredOrders.value = [];
    } finally {
        loading.value = false;
    }
};

const closeDialog = () => {
    dialog.value = false;
    filteredOrders.value = [];
};

const generateInvoice = async () => {
    submitting.value = true;

    try {
        await router.post(
            "/call-agent-invoices",
            {
                agent_id: filters.value.agent,
                from: filters.value.from,
                to: filters.value.to,
                rate: rate.value,
                orders: filteredOrders.value.map((o) => o.id),
                total_commission: totalCommission.value,
            },
            {
                onSuccess: () => {
                    showSnackbar("Invoice generated successfully", "success");
                    closeDialog();
                    // Reset filters
                    filters.value = { from: "", to: "", agent: null };
                },
                onError: (errors) => {
                    showSnackbar("Error generating invoice", "error");
                    console.error(errors);
                },
            },
        );
    } finally {
        submitting.value = false;
    }
};

const viewInvoice = (invoice) => {
    router.visit(`/call-agent-invoices/${invoice.id}`);
};

const downloadInvoice = async (invoice) => {
    window.open(`/call-agent-invoices/${invoice.id}/download`, "_blank");
};

const showSnackbar = (text, color = "success") => {
    snackbarText.value = text;
    snackbarColor.value = color;
    snackbar.value = true;
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("en-KE", {
        style: "currency",
        currency: "KES",
    }).format(amount);
};

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("en-KE", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const getStatusColor = (status) => {
    const colors = {
        pending: "warning",
        paid: "success",
        cancelled: "error",
        processing: "info",
    };
    return colors[status?.toLowerCase()] || "default";
};

// Lifecycle hooks
onMounted(async () => {
    // Initialize the order store if not already initialized
    if (!orderStore.initialized) {
        await orderStore.initialize();
    } else {
        // If already initialized, just fetch dropdown options to ensure we have agents
        await orderStore.fetchDropdownOptions();
    }
});
</script>
