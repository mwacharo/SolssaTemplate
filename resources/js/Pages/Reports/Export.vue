<template>
    <AppLayout>
        <div class="p-6 max-w-screen-2xl mx-auto">
            <!-- Page Header -->
            <h1
                class="text-2xl font-bold text-gray-800 mb-6 tracking-wide uppercase"
            >
                Reports
            </h1>

            <!-- ── Filter Card ──────────────────────────────────────────── -->
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6"
            >
                <!-- Row 1: Report Type always visible -->
                <div
                    class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-4"
                >
                    <!-- Report Type -->
                    <div>
                        <label
                            class="block text-xs font-semibold text-red-600 mb-1 uppercase tracking-wider"
                        >
                            Report Type <span class="text-red-500">*</span>
                        </label>
                        <v-autocomplete
                            v-model="orderReportStore.selectedReportType"
                            :items="orderReportStore.reportTypes"
                            item-title="name"
                            item-value="id"
                            clearable
                            dense
                            outlined
                            placeholder="Select report type..."
                            class="w-full"
                            @update:modelValue="onReportTypeChange"
                        />
                    </div>

                    <!-- Dynamic Filters -->
                    <template v-if="orderReportStore.selectedReportType">
                        <!-- Merchant -->
                        <div v-if="showFilter('merchant')">
                            <label
                                class="block text-xs font-semibold text-red-600 mb-1 uppercase tracking-wider"
                                >Merchant</label
                            >
                            <v-autocomplete
                                v-model="orderStore.orderFilterVendor"
                                :items="orderStore.vendorOptions"
                                item-title="name"
                                item-value="id"
                                clearable
                                dense
                                outlined
                                placeholder="Select merchant..."
                                class="w-full"
                            />
                        </div>

                        <!-- Product -->
                        <div v-if="showFilter('product')">
                            <label
                                class="block text-xs font-semibold text-red-600 mb-1 uppercase tracking-wider"
                                >Product</label
                            >
                            <v-autocomplete
                                v-model="orderStore.orderFilterProduct"
                                :items="orderStore.productOptions"
                                item-title="product_name"
                                item-value="id"
                                clearable
                                dense
                                outlined
                                placeholder="Select product..."
                                class="w-full"
                            />
                        </div>

                        <!-- Category -->
                        <div v-if="showFilter('category')">
                            <label
                                class="block text-xs font-semibold text-red-600 mb-1 uppercase tracking-wider"
                                >Category</label
                            >
                            <v-autocomplete
                                v-model="orderStore.orderFilterCategory"
                                :items="orderStore.categoryOptions"
                                item-title="name"
                                item-value="id"
                                clearable
                                dense
                                outlined
                                placeholder="Select category..."
                                class="w-full"
                            />
                        </div>

                        <!-- Zone -->
                        <div v-if="showFilter('zone')">
                            <label
                                class="block text-xs font-semibold text-red-600 mb-1 uppercase tracking-wider"
                                >Zone</label
                            >
                            <v-autocomplete
                                v-model="orderStore.orderFilterZone"
                                :items="orderStore.zoneOptions"
                                item-title="name"
                                item-value="id"
                                clearable
                                dense
                                outlined
                                placeholder="Select zone..."
                                class="w-full"
                            />
                        </div>

                        <!-- City -->
                        <div v-if="showFilter('city')">
                            <label
                                class="block text-xs font-semibold text-red-600 mb-1 uppercase tracking-wider"
                                >City</label
                            >
                            <v-autocomplete
                                v-model="orderStore.orderFilterCity"
                                :items="orderStore.cityOptions"
                                item-title="name"
                                item-value="id"
                                clearable
                                dense
                                outlined
                                placeholder="Select city..."
                                class="w-full"
                            />
                        </div>

                        <!-- Rider / Delivery Person -->
                        <div v-if="showFilter('rider')">
                            <label
                                class="block text-xs font-semibold text-red-600 mb-1 uppercase tracking-wider"
                                >Delivery Person</label
                            >
                            <v-autocomplete
                                v-model="orderStore.orderFilterRider"
                                :items="orderStore.riderOptions"
                                item-title="name"
                                item-value="id"
                                clearable
                                dense
                                outlined
                                placeholder="Select rider..."
                                class="w-full"
                            />
                        </div>

                        <!-- Confirmation Status -->
                        <div v-if="showFilter('confirmationStatus')">
                            <label
                                class="block text-xs font-semibold text-red-600 mb-1 uppercase tracking-wider"
                                >Confirmation Status</label
                            >
                            <v-autocomplete
                                v-model="orderStore.orderFilterStatus"
                                :items="orderStore.statusOptions"
                                item-title="name"
                                item-value="id"
                                clearable
                                dense
                                outlined
                                placeholder="Select status..."
                                class="w-full"
                            />
                        </div>

                        <!-- Status Date -->

                        <div v-if="showFilter('statusDate')">
                            <label
                                class="block text-xs font-semibold text-red-600 mb-1 uppercase tracking-wider"
                                >Status Date</label
                            >
                            <DateRangePicker
                                v-model="orderStore.statusDateRange"
                            />
                        </div>

                        <!-- Order Date -->
                        <div v-if="showFilter('orderDate')">
                            <label
                                class="block text-xs font-semibold text-red-600 mb-1 uppercase tracking-wider"
                                >Created Date</label
                            >
                            <DateRangePicker
                                v-model="orderStore.createdDateRange"
                            />
                        </div>

                        <!-- Delivery Date -->
                        <div v-if="showFilter('deliveryDate')">
                            <label
                                class="block text-xs font-semibold text-red-600 mb-1 uppercase tracking-wider"
                                >Delivery Date</label
                            >
                            <!-- <DateRangePicker
                                v-model="orderStore.filters.deliveryDate"
                            /> -->

                            <DateRangePicker
                                v-model="orderStore.deliveryDateRange"
                            />
                        </div>
                    </template>
                </div>

                <!-- Actions -->
                <div
                    class="flex flex-wrap justify-end gap-3 pt-2 border-t border-gray-100"
                >
                    <!-- Generate -->
                    <button
                        @click="generate"
                        :disabled="
                            !orderReportStore.selectedReportType ||
                            orderReportStore.loading.generate
                        "
                        class="inline-flex items-center gap-2 px-6 py-2 bg-red-600 text-white text-sm font-semibold rounded-md hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span
                            v-if="orderReportStore.loading.generate"
                            class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"
                        />
                        <svg
                            v-else
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                        Generate Report
                    </button>

                    <!-- Download Excel -->
                    <button
                        @click="orderReportStore.downloadExcel"
                        :disabled="
                            !orderReportStore.results.length ||
                            orderReportStore.loading.download
                        "
                        class="inline-flex items-center gap-2 px-6 py-2 bg-green-600 text-white text-sm font-semibold rounded-md hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span
                            v-if="orderReportStore.loading.download"
                            class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"
                        />
                        <svg
                            v-else
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
                        Download Excel
                    </button>

                    <!-- Reset -->
                    <button
                        @click="reset"
                        class="inline-flex items-center gap-2 px-6 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition-colors"
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
                        Reset
                    </button>
                </div>

                <!-- Error Alert -->
                <div
                    v-if="orderReportStore.error"
                    class="mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm flex items-center gap-2"
                >
                    <svg
                        class="w-4 h-4 shrink-0"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    {{ orderReportStore.error }}
                </div>
            </div>

            <!-- ── Results Table ─────────────────────────────────────────── -->
            <div
                v-if="orderReportStore.selectedReportType"
                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
            >
                <!-- Table Header -->
                <div
                    class="px-6 py-4 border-b border-gray-100 flex items-center justify-between"
                >
                    <h2 class="text-lg font-semibold text-gray-800">
                        Report Results
                    </h2>
                    <span class="text-sm text-gray-500">
                        <template v-if="orderReportStore.pagination.total > 0">
                            Showing
                            {{
                                (orderReportStore.pagination.page - 1) *
                                    orderReportStore.pagination.perPage +
                                1
                            }}–{{
                                Math.min(
                                    orderReportStore.pagination.page *
                                        orderReportStore.pagination.perPage,
                                    orderReportStore.pagination.total,
                                )
                            }}
                            of {{ orderReportStore.pagination.total }} records
                        </template>
                        <template
                            v-else-if="!orderReportStore.loading.generate"
                        >
                            No results yet
                        </template>
                    </span>
                </div>

                <!-- Loading Skeleton -->
                <div
                    v-if="orderReportStore.loading.generate"
                    class="p-6 space-y-3"
                >
                    <div
                        v-for="i in 8"
                        :key="i"
                        class="h-8 bg-gray-100 rounded animate-pulse"
                    />
                </div>

                <!-- Table -->
                <div
                    v-else-if="orderReportStore.activeColumns.length"
                    class="overflow-x-auto"
                >
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-blue-600 text-white">
                                <th
                                    v-for="col in orderReportStore.activeColumns"
                                    :key="col.key"
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider whitespace-nowrap"
                                >
                                    {{ col.label }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template v-if="orderReportStore.results.length">
                                <tr
                                    v-for="(
                                        row, idx
                                    ) in orderReportStore.results"
                                    :key="idx"
                                    class="hover:bg-gray-50 transition-colors"
                                >
                                    <td
                                        v-for="col in orderReportStore.activeColumns"
                                        :key="col.key"
                                        class="px-4 py-3 text-gray-700 whitespace-nowrap"
                                    >
                                        {{ row[col.key] ?? "—" }}
                                    </td>
                                </tr>
                            </template>
                            <tr v-else>
                                <td
                                    :colspan="
                                        orderReportStore.activeColumns.length
                                    "
                                    class="px-4 py-10 text-center text-gray-400"
                                >
                                    No items found. Adjust filters and generate
                                    the report.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="totalPages > 1"
                    class="px-6 py-4 border-t border-gray-100 flex items-center justify-center gap-1"
                >
                    <button
                        v-for="page in paginationPages"
                        :key="page"
                        @click="
                            page !== '...' && orderReportStore.changePage(page)
                        "
                        :class="[
                            'w-8 h-8 rounded flex items-center justify-center text-sm font-medium transition-colors',
                            page === orderReportStore.pagination.page
                                ? 'bg-blue-600 text-white'
                                : page === '...'
                                  ? 'text-gray-400 cursor-default'
                                  : 'text-gray-600 hover:bg-gray-100',
                        ]"
                    >
                        {{ page }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, onMounted } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import DateRangePicker from "@/Components/DualDatePicker.vue";

import { useOrderStore } from "@/stores/orderStore";

import useOrderReportStore from "@/stores/orderReport";

const orderReportStore = useOrderReportStore();

const orderStore = useOrderStore();

// ─── Helpers ────────────────────────────────────────────────────────────────

/** Check if a filter field should be rendered for the current report type */
function showFilter(fieldName) {
    return orderReportStore.activeFilterFields.includes(fieldName);
}

/** When report type changes, clear results but keep filter state */
function onReportTypeChange() {
    orderReportStore.results = [];
    orderReportStore.error = null;
}

async function generate() {
    await orderReportStore.generateReport();
}

function reset() {
    orderReportStore.resetFilters();
}

// ─── Pagination helpers ──────────────────────────────────────────────────────

const totalPages = computed(() =>
    Math.ceil(
        orderReportStore.pagination.total / orderReportStore.pagination.perPage,
    ),
);

const paginationPages = computed(() => {
    const total = totalPages.value;
    const current = orderReportStore.pagination.page;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

    const pages = [];
    if (current > 3) {
        pages.push(1);
        if (current > 4) pages.push("...");
    }
    for (
        let i = Math.max(1, current - 2);
        i <= Math.min(total, current + 2);
        i++
    ) {
        pages.push(i);
    }
    if (current < total - 3) pages.push("...");
    if (current < total - 2) pages.push(total);
    return pages;
});

// ─── Init ────────────────────────────────────────────────────────────────────

onMounted(async () => {
    await orderStore.initialize();

    orderReportStore.fetchOptions();
});
</script>
