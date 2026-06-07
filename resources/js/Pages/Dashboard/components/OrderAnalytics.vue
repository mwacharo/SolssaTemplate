<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <!-- Header Section -->
        <div class="flex flex-col gap-4 mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-1">
                    Order Analytics
                </h3>
                <p class="text-sm text-gray-500">Daily orders over time</p>
            </div>

            <!-- Filters Row -->
            <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                <div class="flex-1">
                    <DateRangePicker v-model="dateRange" />
                </div>

                <div class="flex-1">
                    <SelectMerchant
                        v-model="merchantId"
                        label="Merchant"
                        @validation="formValid.merchant = $event"
                    />
                </div>
            </div>
        </div>

        <!-- Chart Container -->
        <div class="relative">
            <div v-if="isLoading" class="flex items-center justify-center h-80">
                <div
                    class="animate-spin rounded-full h-8 w-8 border-2 border-blue-600 border-t-transparent"
                ></div>
            </div>

            <div
                v-else-if="!filteredData.length"
                class="flex flex-col items-center justify-center h-80 text-gray-400"
            >
                <svg
                    class="w-12 h-12 mb-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                    />
                </svg>
                <p class="text-sm font-medium">No data for this period</p>
            </div>

            <apexchart
                v-else
                type="area"
                height="320"
                :options="chartOptions"
                :series="chartSeries"
                class="apex-chart"
            />
        </div>

        <!-- Stats Summary -->
        <div
            class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-6 pt-6 border-t border-gray-100"
        >
            <div class="text-center">
                <div
                    class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full mx-auto mb-2"
                >
                    <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
                </div>
                <p class="text-2xl font-bold text-gray-900">
                    {{ ordersGivenSummary.total_orders.toLocaleString() }}
                </p>
                <p class="text-xs text-gray-500">Total Orders (All Time)</p>
            </div>

            <div class="text-center">
                <div
                    class="flex items-center justify-center w-8 h-8 bg-green-100 rounded-full mx-auto mb-2"
                >
                    <div class="w-3 h-3 bg-green-600 rounded-full"></div>
                </div>
                <p class="text-2xl font-bold text-gray-900">
                    {{ periodTotal.toLocaleString() }}
                </p>
                <p class="text-xs text-gray-500">
                    Orders ({{ selectedPeriod }})
                </p>
            </div>

            <div class="text-center">
                <div
                    class="flex items-center justify-center w-8 h-8 bg-purple-100 rounded-full mx-auto mb-2"
                >
                    <svg
                        class="w-4 h-4 text-purple-600"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ avgPerDay }}</p>
                <p class="text-xs text-gray-500">Avg Orders / Day</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import DateRangePicker from "@/Components/DualDatePicker.vue";
import SelectMerchant from "@/Components/SelectMerchant.vue";

// Props
const props = defineProps({
    ordersGivenSummary: {
        type: Object,
        required: true,
        default: () => ({ total_orders: 0, orders_per_day: [] }),
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["filter-change"]);

// Filter state
const dateRange = ref(null);
const merchantId = ref(null);
const formValid = ref({ merchant: true });
const selectedPeriod = ref("7D");

const timePeriods = [
    { label: "7D", value: "7D" },
    { label: "14D", value: "14D" },
    { label: "30D", value: "30D" },
    { label: "All", value: "All" },
];

// Emit to parent whenever date range or merchant changes
watch(
    [dateRange, merchantId],
    ([newDateRange, newMerchantId]) => {
        emit("filter-change", {
            dateRange: newDateRange,
            merchantId: newMerchantId,
        });
    },
    { deep: true },
);

// Sort + slice by selected period
const filteredData = computed(() => {
    const sorted = [...(props.ordersGivenSummary.orders_per_day ?? [])].sort(
        (a, b) => new Date(a.date) - new Date(b.date),
    );
    if (selectedPeriod.value === "All") return sorted;
    const days = parseInt(selectedPeriod.value);
    return sorted.slice(-days);
});

const chartSeries = computed(() => [
    {
        name: "Orders",
        data: filteredData.value.map((item) => ({
            x: item.date,
            y: Number(item.total),
        })),
    },
]);

const chartOptions = computed(() => ({
    chart: {
        id: "orders-analytics",
        type: "area",
        height: 320,
        fontFamily: "Inter, sans-serif",
        toolbar: {
            show: true,
        },
        zoom: {
            enabled: true,
        },
    },

    colors: ["#3B82F6"],

    dataLabels: {
        enabled: false,
    },

    stroke: {
        curve: "smooth",
        width: 3,
    },

    fill: {
        type: "gradient",
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.35,
            opacityTo: 0.05,
            stops: [0, 100],
        },
    },

    xaxis: {
        type: "datetime",

        labels: {
            style: {
                colors: "#6B7280",
                fontSize: "12px",
            },
            datetimeFormatter: {
                year: "yyyy",
                month: "MMM dd",
                day: "dd MMM",
            },
        },

        axisBorder: {
            show: false,
        },

        axisTicks: {
            show: false,
        },
    },

    yaxis: {
        min: 0,
        forceNiceScale: true,

        labels: {
            formatter: (value) => Math.round(value),
            style: {
                colors: "#6B7280",
                fontSize: "12px",
            },
        },
    },

    grid: {
        borderColor: "#E5E7EB",
        strokeDashArray: 3,
    },

    tooltip: {
        x: {
            format: "dd MMM yyyy",
        },
        y: {
            formatter: (value) => `${value} Orders`,
        },
    },

    legend: {
        show: false,
    },

    noData: {
        text: "No orders found",
    },
}));

// Stats
const periodTotal = computed(() =>
    filteredData.value.reduce((sum, d) => sum + d.total, 0),
);

const avgPerDay = computed(() => {
    if (!filteredData.value.length) return 0;
    return Math.round(periodTotal.value / filteredData.value.length);
});
</script>

<style scoped>
.apex-chart {
    font-family: "Inter", sans-serif;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
