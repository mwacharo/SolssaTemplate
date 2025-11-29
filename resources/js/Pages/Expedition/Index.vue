<template>
    <AppLayout>
        <div class="min-h-screen bg-gray-50 p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800">
                                Expeditions
                            </h1>
                            <p class="text-gray-600 mt-1">
                                {{ filteredExpeditions.length }} total
                                expeditions
                            </p>
                        </div>
                        <button
                            @click="showAddModal = true"
                            class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
                        >
                            <Plus :size="20" />
                            Add Expedition
                        </button>
                    </div>

                    <!-- Search and Filters -->
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1 relative">
                            <Search
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
                                :size="20"
                            />
                            <input
                                type="text"
                                placeholder="Search by tracking, country, or transporter..."
                                class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                v-model="searchQuery"
                            />
                        </div>
                        <div class="flex items-center gap-2">
                            <Filter :size="20" class="text-gray-400" />
                            <select
                                class="border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                                v-model="filterStatus"
                            >
                                <option value="all">All Status</option>
                                <option value="expedited">Expedited</option>
                                <option value="pending">Pending</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Expeditions Table -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-blue-600 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left">ID</th>
                                    <th class="px-4 py-3 text-left">Vendor</th>
                                    <th class="px-4 py-3 text-left">Weight</th>
                                    <th class="px-4 py-3 text-left">
                                        Packages
                                    </th>
                                    <th class="px-4 py-3 text-left">Details</th>
                                    <th class="px-4 py-3 text-left">From</th>
                                    <th class="px-4 py-3 text-left">To</th>
                                    <th class="px-4 py-3 text-left">
                                        Expedition Date
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        Arrival Date
                                    </th>
                                    <th class="px-4 py-3 text-left">Fees</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-left">
                                        Approval
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        Remittance
                                    </th>
                                    <th class="px-4 py-3 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(
                                        expedition, index
                                    ) in paginatedExpeditions"
                                    :key="expedition.id"
                                    :class="
                                        index % 2 === 0
                                            ? 'bg-amber-50'
                                            : 'bg-white'
                                    "
                                >
                                    <td class="px-4 py-3">
                                        {{ expedition.id }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm"
                                            >
                                                {{
                                                    expedition.vendor?.name ||
                                                    "N/A"
                                                }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ expedition.weight }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ expedition.packages_number }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <button
                                            class="text-orange-600 hover:text-orange-700"
                                        >
                                            {{
                                                expedition.shipment_items.length
                                            }}
                                            products
                                        </button>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="text-2xl">{{
                                                getCountryFlag(
                                                    expedition.source_country
                                                )
                                            }}</span>
                                            <span class="text-sm">{{
                                                expedition.source_country
                                            }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ expedition.warehouse?.name }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ expedition.shipment_date }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{
                                            expedition.arrival_date.split(
                                                " "
                                            )[0]
                                        }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ expedition.shipment_fees }} KES
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs text-white',
                                                getStatusColor(
                                                    expedition.shipment_status
                                                ),
                                            ]"
                                        >
                                            <Truck :size="12" />
                                            {{ expedition.shipment_status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs text-white',
                                                getApprovalColor(
                                                    expedition.approval_status
                                                ),
                                            ]"
                                        >
                                            <CheckCircle
                                                v-if="
                                                    expedition.approval_status ===
                                                    'approved'
                                                "
                                                :size="12"
                                            />
                                            <Clock v-else :size="12" />
                                            {{ expedition.approval_status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-sm">{{
                                            expedition.transporter_reimbursement_status ===
                                            "paid"
                                                ? "Paid by vendor"
                                                : "Not paid"
                                        }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            <button
                                                @click="viewDetails(expedition)"
                                                class="p-2 hover:bg-gray-100 rounded-lg"
                                                title="View Details"
                                            >
                                                <Eye
                                                    :size="18"
                                                    class="text-gray-600"
                                                />
                                            </button>

                                            <button
                                                class="p-2 hover:bg-gray-100 rounded-lg"
                                                title="Edit"
                                            >
                                                <Edit
                                                    :size="18"
                                                    class="text-gray-600"
                                                />
                                            </button>

                                            <!-- delete -->

                                            <button
                                                @click="
                                                    handleDelete(expedition.id)
                                                "
                                                class="p-2 hover:bg-gray-100 rounded-lg transition"
                                                title="Delete"
                                            >
                                                <Trash2
                                                    :size="18"
                                                    class="text-red-600"
                                                />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="border-t p-4 flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            Showing
                            {{ (currentPage - 1) * itemsPerPage + 1 }} to
                            {{
                                Math.min(
                                    currentPage * itemsPerPage,
                                    filteredExpeditions.length
                                )
                            }}
                            of {{ filteredExpeditions.length }} expeditions
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="
                                    currentPage = Math.max(1, currentPage - 1)
                                "
                                :disabled="currentPage === 1"
                                class="px-4 py-2 border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Previous
                            </button>
                            <button
                                v-for="page in totalPages"
                                :key="page"
                                @click="currentPage = page"
                                :class="[
                                    'px-4 py-2 rounded-lg',
                                    currentPage === page
                                        ? 'bg-blue-600 text-white'
                                        : 'border hover:bg-gray-50',
                                ]"
                            >
                                {{ page }}
                            </button>
                            <button
                                @click="
                                    currentPage = Math.min(
                                        totalPages,
                                        currentPage + 1
                                    )
                                "
                                :disabled="currentPage === totalPages"
                                class="px-4 py-2 border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Modal -->
            <div
                v-if="showAddModal"
                class="fixed inset-0 bg-grey bg-opacity-50 flex items-center justify-center z-50 p-4"
            >
                <div
                    class="bg-white rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto"
                >
                    <div class="sticky top-0 bg-white border-b p-6">
                        <h2 class="text-2xl font-bold">Add New Expedition</h2>
                    </div>

                    <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2"
                                    >Source Country</label
                                >
                                <input
                                    type="text"
                                    required
                                    class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                                    v-model="formData.source_country"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2"
                                    >Destination</label
                                >

                                <v-select
                                    v-model="formData.warehouse_id"
                                    :items="orderStore.warehouseOptions"
                                    item-title="name"
                                    item-value="id"
                                    label="Destination Warehouse"
                                    prepend-inner-icon="mdi-warehouse"
                                    variant="outlined"
                                    density="comfortable"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2"
                                    >Shipment Date</label
                                >
                                <input
                                    type="date"
                                    required
                                    class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                                    v-model="formData.shipment_date"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2"
                                    >Expected Arrival</label
                                >
                                <input
                                    type="date"
                                    required
                                    class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                                    v-model="formData.arrival_date"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2"
                                    >Transporter</label
                                >
                                <input
                                    type="text"
                                    required
                                    class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                                    v-model="formData.transporter_name"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2"
                                    >Tracking Number</label
                                >
                                <input
                                    type="text"
                                    required
                                    class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                                    v-model="formData.tracking_number"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2"
                                    >Packages</label
                                >
                                <input
                                    type="number"
                                    min="1"
                                    required
                                    class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                                    v-model.number="formData.packages_number"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2"
                                    >Weight (kg)</label
                                >
                                <input
                                    type="number"
                                    min="0"
                                    step="0.1"
                                    class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                                    v-model.number="formData.weight"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2"
                                    >Shipment Fees (KES)</label
                                >
                                <input
                                    type="number"
                                    min="0"
                                    class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                                    v-model.number="formData.shipment_fees"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2"
                                    >Vendor
                                </label>
                                <v-select
                                    v-model="vendor_id"
                                    :items="vendorOptions"
                                    item-title="name"
                                    item-value="id"
                                    label="Vendor"
                                    prepend-inner-icon="mdi-domain"
                                    variant="outlined"
                                    density="comfortable"
                                    @update:model-value="onVendorChange"
                                />
                            </div>
                        </div>

                        <!-- add shipment items table  -->

                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="overflow-x-auto mt-4">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-left bg-gray-100">
                                            <th class="px-3 py-2 w-1/2">
                                                Product
                                            </th>
                                            <th class="px-3 py-2 w-32">
                                                Quantity
                                            </th>
                                            <th class="px-3 py-2 w-32">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr
                                            v-for="(
                                                item, index
                                            ) in formData.shipment_items"
                                            :key="index"
                                            class="bg-white even:bg-gray-50"
                                        >
                                            <!-- Product Selector Per Row -->
                                            <td class="px-3 py-2">
                                                <v-autocomplete
                                                    v-model="item.product.id"
                                                    :items="
                                                        orderStore.productOptions
                                                    "
                                                    item-title="product_name"
                                                    item-value="id"
                                                    placeholder="Select product"
                                                    density="compact"
                                                    hide-details
                                                    variant="outlined"
                                                    @update:modelValue="
                                                        updateProduct(item)
                                                    "
                                                />
                                            </td>

                                            <!-- Quantity -->
                                            <td class="px-3 py-2">
                                                <input
                                                    type="number"
                                                    min="1"
                                                    class="w-full border rounded px-2 py-1"
                                                    v-model.number="
                                                        item.quantity_sent
                                                    "
                                                />
                                            </td>

                                            <!-- Actions -->
                                            <td class="px-3 py-2">
                                                <button
                                                    type="button"
                                                    class="px-3 py-1 border rounded hover:bg-gray-100"
                                                    @click="removeItem(index)"
                                                >
                                                    Remove
                                                </button>
                                            </td>
                                        </tr>

                                        <tr
                                            v-if="
                                                !formData.shipment_items.length
                                            "
                                        >
                                            <td
                                                colspan="3"
                                                class="px-3 py-4 text-gray-500 text-center"
                                            >
                                                No products added yet.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="pt-4 flex justify-between items-center">
                                <button
                                    type="button"
                                    class="mt-3 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                                    @click="
                                        formData.shipment_items =
                                            formData.shipment_items || [];
                                        formData.shipment_items.push({
                                            product: { name: '' },
                                            quantity_sent: 1,
                                            product: { sku: '' },
                                            product: { id: '' },
                                        });
                                    "
                                >
                                    + Add Product
                                </button>

                                <div class="text-sm text-gray-600">
                                    Total units:
                                    {{
                                        (formData.shipment_items || []).reduce(
                                            (sum, it) =>
                                                sum +
                                                (Number(it.quantity_sent) || 0),
                                            0
                                        )
                                    }}
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-3 justify-end pt-4">
                            <button
                                type="button"
                                @click="showAddModal = false"
                                class="px-4 py-2 border rounded-lg hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                            >
                                Create Expedition
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Details Modal -->
            <div
                v-if="showDetailsModal && selectedExpedition"
                class="fixed inset-0 bg-grey bg-opacity-50 flex items-center justify-center z-50 p-4"
            >
                <div
                    class="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto"
                >
                    <div class="sticky top-0 bg-white border-b p-6">
                        <div class="flex justify-between items-center">
                            <h2 class="text-2xl font-bold">
                                Expedition Details #{{ selectedExpedition.id }}
                            </h2>
                            <button
                                @click="showDetailsModal = false"
                                class="text-gray-500 hover:text-gray-700"
                            >
                                <XCircle :size="24" />
                            </button>
                        </div>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Shipment Info -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="font-semibold text-lg mb-4">
                                Shipment Information
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Tracking Number
                                    </p>
                                    <p class="font-semibold">
                                        {{ selectedExpedition.tracking_number }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Transporter
                                    </p>
                                    <p class="font-semibold">
                                        {{
                                            selectedExpedition.transporter_name
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Source Country
                                    </p>
                                    <p class="font-semibold">
                                        {{ selectedExpedition.source_country }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Destination
                                    </p>
                                    <p class="font-semibold">
                                        {{
                                            selectedExpedition.warehouse?.name
                                        }},
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Shipment Date
                                    </p>
                                    <p class="font-semibold">
                                        {{ selectedExpedition.shipment_date }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Arrival Date
                                    </p>
                                    <p class="font-semibold">
                                        {{ selectedExpedition.arrival_date }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Weight</p>
                                    <p class="font-semibold">
                                        {{ selectedExpedition.weight }} kg
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Packages
                                    </p>
                                    <p class="font-semibold">
                                        {{ selectedExpedition.packages_number }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Fees</p>
                                    <p class="font-semibold">
                                        {{ selectedExpedition.shipment_fees }}
                                        KES
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Payment Status
                                    </p>
                                    <span
                                        :class="[
                                            'inline-block px-2 py-1 rounded text-xs text-white',
                                            selectedExpedition.transporter_reimbursement_status ===
                                            'paid'
                                                ? 'bg-green-500'
                                                : 'bg-red-500',
                                        ]"
                                    >
                                        {{
                                            selectedExpedition.transporter_reimbursement_status
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Products -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="font-semibold text-lg mb-4">
                                Products ({{
                                    selectedExpedition.shipment_items.length
                                }})
                            </h3>
                            <div class="space-y-3">
                                <div
                                    v-for="(
                                        item, index
                                    ) in selectedExpedition.shipment_items"
                                    :key="index"
                                    class="bg-white rounded-lg p-3 flex justify-between items-center"
                                >
                                    <div>
                                        <p class="font-medium">
                                            {{ item.product.product_name }}
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            Quantity:
                                            {{ item.quantity_sent }} units
                                        </p>
                                    </div>
                                    <Package class="text-blue-500" :size="20" />
                                </div>
                            </div>
                        </div>

                        <!-- Seller Info -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="font-semibold text-lg mb-4">
                                Vendor Information
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">Name</p>
                                    <p class="font-semibold">
                                        {{ selectedExpedition.vendor.name }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Username
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, computed, watch, onMounted } from "vue";
import { useOrderStore } from "@/stores/orderStore";

import {
    Truck,
    Package,
    Edit,
    Eye,
    Plus,
    Search,
    Filter,
    Calendar,
    MapPin,
    CheckCircle,
    Clock,
    XCircle,
} from "lucide-vue-next";

const orderStore = useOrderStore();

const selectedProduct = ref(null);

const handleDelete = (id) => {
    if (window.confirm("Are you sure you want to delete this expense?")) {
        expenses.value = expenses.value.filter((exp) => exp.id !== id);
    }
};

// Add item to list
const addItem = () => {
    if (!formData.value.shipment_items) {
        formData.value.shipment_items = [];
    }

    const prod = orderStore.productOptions.find(
        (p) => p.sku === selectedProduct.value
    );

    if (!prod) return;

    formData.value.shipment_items.push({
        product: {
            sku: prod.sku,
            name: prod.product_name,
        },
        quantity_sent: 1,
    });

    selectedProduct.value = null;
};

// Update product name if SKU changes
const updateProduct = (item) => {
    const p = orderStore.productOptions.find((x) => x.sku === item.product.sku);

    if (p) {
        item.product.name = p.product_name;
    }
};

// Remove shipment item
const removeItem = (index) => {
    formData.value.shipment_items.splice(index, 1);
};

// UI state
const expeditions = ref([]);
const selectedExpedition = ref(null);
const showAddModal = ref(false);
const showDetailsModal = ref(false);
const searchQuery = ref("");
const filterStatus = ref("all");
const currentPage = ref(1);
const itemsPerPage = 6;

// Vendor dropdown
const vendorOptions = computed(() => orderStore.vendorOptions);
const vendor_id = ref(null);

// FORM
const formData = ref({
    source_country: "",
    destination: "",
    warehouse_id: null,
    shipment_date: "",
    arrival_date: "",
    transporter_name: "",
    tracking_number: "",
    packages_number: 1,
    weight: 0,
    shipment_fees: 0,
    vendor_id: null,
    shipment_items: [],
    // name: "",
});

// Fetch vendors + expeditions
onMounted(async () => {
    try {
        await orderStore.fetchDropdownOptions();
    } catch (error) {
        console.error("Failed to load vendors:", error);
    }

    try {
        const response = await axios.get("/api/v1/expeditions");
        expeditions.value = response.data.expeditions.data ?? [];
    } catch (error) {
        console.error("Failed to load expeditions:", error);
        expeditions.value = [];
    }
});

// Filters
const filteredExpeditions = computed(() => {
    let result = expeditions.value;

    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(
            (exp) =>
                exp.tracking_number?.toLowerCase().includes(q) ||
                exp.source_country?.toLowerCase().includes(q) ||
                exp.transporter_name?.toLowerCase().includes(q)
        );
    }

    if (filterStatus.value !== "all") {
        result = result.filter(
            (exp) => exp.shipment_status === filterStatus.value
        );
    }

    return result;
});

const totalPages = computed(() =>
    Math.ceil(filteredExpeditions.value.length / itemsPerPage)
);

const paginatedExpeditions = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = currentPage.value * itemsPerPage;
    return filteredExpeditions.value.slice(start, end);
});

watch([searchQuery, filterStatus], () => {
    currentPage.value = 1;
});

// Color helpers
const getStatusColor = (status) => {
    const colors = {
        expedited: "bg-orange-500",
        delivered: "bg-green-500",
        pending: "bg-yellow-500",
        cancelled: "bg-red-500",
    };
    return colors[status] || "bg-gray-500";
};

const getApprovalColor = (status) => {
    const colors = {
        approved: "bg-green-500",
        pending: "bg-yellow-500",
        draft: "bg-gray-500",
    };
    return colors[status] || "bg-gray-500";
};

const getCountryFlag = (country) => {
    const flags = {
        Nigeria: "🇳🇬",
        China: "🇨🇳",
    };
    return flags[country] || "🌍";
};

// Vendor select
const onVendorChange = (value) => {
    formData.value.vendor_id = value;
};

// CREATE EXPEDITION — AXIOS VERSION
const handleSubmit = async () => {
    try {
        const response = await axios.post(
            "/api/v1/expeditions",
            formData.value
        );

        const newExpedition = response.data.expedition;

        expeditions.value = [newExpedition, ...expeditions.value];
        showAddModal.value = false;

        // Reset form
        formData.value = {
            source_country: "",
            destination: "",
            warehouse_id: null,
            shipment_date: "",
            arrival_date: "",
            transporter_name: "",
            tracking_number: "",
            packages_number: 1,
            weight: 0,
            shipment_fees: 0,
            vendor_id: null,
            shipment_items: [],
        };

        vendor_id.value = null;
    } catch (error) {
        console.error("Failed to create expedition:", error);

        if (error.response?.status === 422) {
            console.log("Validation Errors:", error.response.data.errors);
        }
    }
};

// Show details modal
const viewDetails = (expedition) => {
    selectedExpedition.value = expedition;
    showDetailsModal.value = true;
};
</script>
