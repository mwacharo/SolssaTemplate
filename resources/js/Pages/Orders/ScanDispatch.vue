<template>
    <AppLayout>
        <div class="min-h-screen bg-gray-50 p-6">
            <!-- Page Header -->
            <div class="bg-white rounded-lg shadow-sm mb-6 p-4">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <!-- Title + Count -->
                    <div class="flex items-center gap-4">
                        <h1 class="text-2xl font-bold text-gray-900">
                            Scan &amp; Dispatch orders
                        </h1>
                        <div
                            class="flex items-center gap-1 border border-gray-300 rounded px-3 py-1 text-sm text-gray-700"
                        >
                            <span
                                >{{ pagination.from }}-{{ pagination.to }} /
                                {{ pagination.total }}</span
                            >
                            <svg
                                class="w-4 h-4 ml-1 text-gray-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-2">
                        <button
                            @click="openScanDispatchDialog"
                            class="flex items-center gap-2 px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-lg transition-colors"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h10l2-2z"
                                />
                            </svg>
                            Scan &amp; Dispatch orders
                        </button>

                        <!-- <button
                            class="flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                            Download Picklist
                        </button>

                        <button
                            class="flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                />
                            </svg>
                            Reschedule orders
                        </button>

                        <button
                            class="flex items-center gap-2 px-4 py-2 bg-teal-500 hover:bg-teal-600 text-white text-sm font-medium rounded-lg transition-colors"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"
                                />
                            </svg>
                            Undispatch Orders
                        </button>

                        <button
                            class="flex items-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-lg transition-colors"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            Rescheduled to Awaiting Dispatch
                        </button>

                        <button
                            class="flex items-center gap-2 px-4 py-2 bg-lime-500 hover:bg-lime-600 text-white text-sm font-medium rounded-lg transition-colors"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                            Affect Deliveryman
                        </button> -->
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-blue-600 text-white">
                            <tr>
                                <th class="px-4 py-3 text-left">
                                    <input type="checkbox" class="rounded" />
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-sm font-semibold"
                                >
                                    ORDER NO
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-sm font-semibold"
                                >
                                    CUSTOMER
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-sm font-semibold"
                                >
                                    ADDRESS
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-sm font-semibold"
                                >
                                    SELLER
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-sm font-semibold"
                                >
                                    DETAILS
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-sm font-semibold"
                                >
                                    ZONE
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-sm font-semibold"
                                >
                                    TRACKING NUMBER
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-sm font-semibold"
                                >
                                    TOTAL PRICE
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-sm font-semibold"
                                >
                                    STATUS
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-sm font-semibold"
                                >
                                    DELIVERY MAN
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td
                                    colspan="11"
                                    class="px-4 py-12 text-center text-gray-400 text-sm"
                                >
                                    No items
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    class="flex justify-center items-center py-4 bg-gray-50 border-t"
                >
                    <button
                        class="px-3 py-1 border border-red-400 text-red-600 rounded text-sm font-medium"
                    >
                        1
                    </button>
                </div>
            </div>
        </div>

        <!-- ===== SCAN & DISPATCH DIALOG ===== -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="dialogOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    @click.self="closeDialog"
                >
                    <!-- Backdrop -->
                    <div
                        class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                        @click="closeDialog"
                    ></div>

                    <!-- Dialog -->
                    <div
                        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden"
                    >
                        <!-- Dialog Header -->
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2
                                class="text-xl font-bold text-gray-900 text-center"
                            >
                                Dispatch orders /
                                <span class="text-blue-600"
                                    >{{ scannedOrders.length }} selected</span
                                >
                            </h2>
                        </div>

                        <!-- Dialog Body -->
                        <div class="flex-1 overflow-y-auto p-6 space-y-5">
                            <!-- City / Zone Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- City From -->
                                <!-- <div>
                                    <label
                                        class="block text-sm font-semibold text-red-600 mb-1"
                                        >CITY FROM
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <select
                                        v-model="form.cityFrom"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="">
                                            Select city from
                                        </option>
                                        <option
                                            v-for="city in cityOptions"
                                            :key="city.id"
                                            :value="city.id"
                                        >
                                            {{ city.name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-semibold text-red-600 mb-1"
                                        >ZONE FROM
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <select
                                        v-model="form.zoneFrom"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="">
                                            Select zone from
                                        </option>
                                        <option
                                            v-for="zone in zoneFromOptions"
                                            :key="zone.id"
                                            :value="zone.id"
                                        >
                                            {{ zone.name }}
                                        </option>
                                    </select>
                                </div> -->

                                <!-- City To -->
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-red-600 mb-1"
                                        >CITY TO
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <select
                                        v-model="form.cityTo"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="">Select city to</option>
                                        <option
                                            v-for="city in cityOptions"
                                            :key="city.id"
                                            :value="city.id"
                                        >
                                            {{ city.name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Zone To -->
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-red-600 mb-1"
                                        >ZONE TO
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <select
                                        v-model="form.zoneTo"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="">Select zone to</option>
                                        <option
                                            v-for="zone in zoneToOptions"
                                            :key="zone.id"
                                            :value="zone.id"
                                        >
                                            {{ zone.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Delivery Man -->
                            <div>
                                <label
                                    class="block text-sm font-semibold text-red-600 mb-1"
                                    >DELIVERY MAN
                                    <span class="text-red-500">*</span></label
                                >
                                <select
                                    v-model="form.deliveryMan"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    <option value="">
                                        Select a delivery man
                                    </option>
                                    <option
                                        v-for="rider in riderOptions"
                                        :key="rider.id"
                                        :value="rider.id"
                                    >
                                        {{ rider.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Courrier (optional) -->
                            <div>
                                <label
                                    class="block text-sm font-semibold text-gray-700 mb-1"
                                    >COURRIER</label
                                >
                                <select
                                    v-model="form.courrier"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    <option value="">
                                        Select a courrier (optional)
                                    </option>
                                    <option
                                        v-for="c in courrierOptions"
                                        :key="c.id"
                                        :value="c.id"
                                    >
                                        {{ c.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Scan Mode Toggle -->
                            <div class="flex gap-2">
                                <button
                                    @click="scanMode = 'one'"
                                    :class="[
                                        'px-4 py-1.5 rounded-full text-sm font-medium border transition-colors',
                                        scanMode === 'one'
                                            ? 'bg-red-100 border-red-400 text-red-700'
                                            : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50',
                                    ]"
                                >
                                    Scan One by One
                                </button>
                                <button
                                    @click="scanMode = 'bulk'"
                                    :class="[
                                        'px-4 py-1.5 rounded-full text-sm font-medium border transition-colors',
                                        scanMode === 'bulk'
                                            ? 'bg-blue-100 border-blue-400 text-blue-700'
                                            : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50',
                                    ]"
                                >
                                    Bulk Input (comma or line separated)
                                </button>

                                <!-- add button to search order with selected city to or zone to  and where their deleivery date is today or tommow  -->
                            </div>

                            <!-- Search / Scan Input -->
                            <div>
                                <label
                                    class="block text-sm font-semibold text-red-600 mb-1"
                                >
                                    SEARCH ORDERS (AWAITING DISPATCH, BY
                                    TRACKING NUMBER)
                                </label>

                                <!-- One by one -->
                                <div
                                    v-if="scanMode === 'one'"
                                    class="flex gap-2"
                                >
                                    <input
                                        v-model="scanInput"
                                        @keyup.enter="scanOrder"
                                        type="text"
                                        placeholder="Scan or type tracking number and press Enter..."
                                        class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        autofocus
                                    />
                                    <button
                                        @click="scanOrder"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors"
                                    >
                                        Add
                                    </button>
                                </div>

                                <!-- Bulk -->
                                <div v-else>
                                    <textarea
                                        v-model="bulkInput"
                                        placeholder="Enter tracking numbers separated by commas or new lines..."
                                        rows="4"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                    ></textarea>
                                    <button
                                        @click="processBulkInput"
                                        class="mt-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors"
                                    >
                                        Process
                                    </button>
                                </div>
                            </div>

                            <!-- Scanned Orders Table -->
                            <div
                                class="border border-gray-200 rounded-lg overflow-hidden"
                            >
                                <table class="w-full">
                                    <thead class="bg-blue-600 text-white">
                                        <tr>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-semibold"
                                            >
                                                ORDER ID
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-semibold"
                                            >
                                                SELLER
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-semibold"
                                            >
                                                CUSTOMER NAME
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-semibold"
                                            >
                                                SHIPPING DATE
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-semibold"
                                            >
                                                TRACKING NUMBER
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-semibold"
                                            >
                                                TOTAL PRICE
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-semibold"
                                            >
                                                SHIPPING STATUS
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-semibold"
                                            >
                                                ACTIONS
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="scannedOrders.length === 0">
                                            <td
                                                colspan="8"
                                                class="px-4 py-8 text-center text-gray-400 text-sm"
                                            >
                                                No items
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="(
                                                order, index
                                            ) in scannedOrders"
                                            :key="order.id"
                                            class="border-t border-gray-100 hover:bg-gray-50"
                                        >
                                            <td
                                                class="px-3 py-2 text-sm font-mono"
                                            >
                                                {{ order.id }}
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                {{ order.seller }}
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                {{ order.customerName }}
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                {{ order.shippingDate }}
                                            </td>
                                            <td
                                                class="px-3 py-2 text-sm font-mono"
                                            >
                                                {{ order.trackingNumber }}
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                KSH {{ order.totalPrice }}
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                <span
                                                    class="px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded text-xs"
                                                    >{{
                                                        order.shippingStatus
                                                    }}</span
                                                >
                                            </td>
                                            <td class="px-3 py-2">
                                                <button
                                                    @click="removeOrder(index)"
                                                    class="p-1 text-red-500 hover:bg-red-50 rounded"
                                                    title="Remove"
                                                >
                                                    <svg
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M6 18L18 6M6 6l12 12"
                                                        />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Error message -->
                            <div
                                v-if="scanError"
                                class="text-red-600 text-sm bg-red-50 border border-red-200 rounded-lg px-4 py-2"
                            >
                                {{ scanError }}
                            </div>
                        </div>

                        <!-- Dialog Footer -->
                        <div
                            class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3"
                        >
                            <button
                                @click="closeDialog"
                                class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                @click="dispatchOrders"
                                :disabled="
                                    scannedOrders.length === 0 ||
                                    // !form.cityFrom ||
                                    !form.cityTo ||
                                    !form.deliveryMan ||
                                    dispatching
                                "
                                class="px-6 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2"
                            >
                                <span
                                    v-if="dispatching"
                                    class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"
                                ></span>
                                Dispatch Orders
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import axios from "axios";

// ─── State ──────────────────────────────────────────────────────────────────
const dialogOpen = ref(false);
const scanMode = ref("one");
const scanInput = ref("");
const bulkInput = ref("");
const scanError = ref("");
const dispatching = ref(false);

const scannedOrders = ref([]);

const form = ref({
    // cityFrom: "",
    // zoneFrom: "",
    cityTo: "",
    zoneTo: "",
    deliveryMan: "",
    courrier: "",
});

// ─── Dropdown Options (populate from your store or API) ─────────────────────
const cityOptions = ref([]);
const zoneFromOptions = ref([]);
const zoneToOptions = ref([]);
const riderOptions = ref([]);
const courrierOptions = ref([]);

const pagination = ref({ from: 1, to: 25, total: 0 });

// ─── Fetch options on mount ─────────────────────────────────────────────────
onMounted(async () => {
    try {
        const [citiesRes, zonesRes, ridersRes] = await Promise.all([
            axios.get("/api/v1/cities").catch(() => ({ data: { data: [] } })),
            axios.get("/api/v1/zones").catch(() => ({ data: { data: [] } })),
            axios.get("/api/v1/riders").catch(() => ({ data: { data: [] } })),
        ]);
        const normalize = (res) => {
            const d = res?.data;
            if (Array.isArray(d)) return d;
            if (Array.isArray(d?.data)) return d.data;
            return [];
        };
        cityOptions.value = normalize(citiesRes);
        zoneFromOptions.value = normalize(zonesRes);
        zoneToOptions.value = normalize(zonesRes);
        riderOptions.value = normalize(ridersRes);
    } catch (e) {
        console.error("Failed to load options", e);
    }
});

// ─── Dialog controls ─────────────────────────────────────────────────────────
const openScanDispatchDialog = () => {
    scannedOrders.value = [];
    scanInput.value = "";
    bulkInput.value = "";
    scanError.value = "";
    form.value = {
        // cityFrom: "",
        // zoneFrom: "",
        cityTo: "",
        zoneTo: "",
        deliveryMan: "",
        courrier: "",
    };
    dialogOpen.value = true;
};

const closeDialog = () => {
    dialogOpen.value = false;
};

// ─── Scanning ────────────────────────────────────────────────────────────────
const scanOrder = async () => {
    const tracking = scanInput.value.trim();
    if (!tracking) return;
    scanError.value = "";

    // Check duplicate
    if (scannedOrders.value.find((o) => o.trackingNumber === tracking)) {
        scanError.value = `Order with tracking number "${tracking}" is already added.`;
        return;
    }

    try {
        const res = await axios.get(`/api/v1/orders/by-tracking/${tracking}`);
        const order = res.data?.data;
        if (!order) {
            scanError.value = `No order found for tracking number: ${tracking}`;
            return;
        }
        scannedOrders.value.push({
            id: order.id,
            seller: order.vendor?.name || "Unknown",
            customerName:
                order.shipping_address?.full_name ||
                order.customer?.full_name ||
                "-",
            shippingDate: order.delivery_date || "-",
            trackingNumber: tracking,
            totalPrice: order.total_price || "0.00",
            shippingStatus:
                order.latest_status?.status?.name || "Awaiting Dispatch",
        });
        scanInput.value = "";
    } catch (err) {
        scanError.value =
            err.response?.data?.message || `Order not found for: ${tracking}`;
    }
};

const processBulkInput = async () => {
    const lines = bulkInput.value
        .split(/[\n,]+/)
        .map((s) => s.trim())
        .filter(Boolean);

    scanError.value = "";
    for (const tracking of lines) {
        if (!scannedOrders.value.find((o) => o.trackingNumber === tracking)) {
            scanInput.value = tracking;
            await scanOrder();
        }
    }
    bulkInput.value = "";
    scanInput.value = "";
};

const removeOrder = (index) => {
    scannedOrders.value.splice(index, 1);
};

// ─── Dispatch ─────────────────────────────────────────────────────────────────
const dispatchOrders = async () => {
    if (scannedOrders.value.length === 0) return;
    if (!form.value.cityTo || !form.value.deliveryMan) {
        scanError.value =
            "Please fill in all required fields (City To, Delivery Man).";
        return;
    }

    dispatching.value = true;
    scanError.value = "";
    try {
        await axios.post("/api/v1/orders/dispatch", {
            order_ids: scannedOrders.value.map((o) => o.id),
            // city_from: form.value.cityFrom,
            // zone_from: form.value.zoneFrom,
            city_to: form.value.cityTo,
            zone_to: form.value.zoneTo,
            rider_id: form.value.deliveryMan,
            courrier_id: form.value.courrier || null,
        });
        closeDialog();
    } catch (err) {
        scanError.value =
            err.response?.data?.message ||
            "Failed to dispatch orders. Please try again.";
    } finally {
        dispatching.value = false;
    }
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.2s ease;
}
.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.96) translateY(8px);
}
</style>
