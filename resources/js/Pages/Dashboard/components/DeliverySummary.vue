<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">
                    Delivery Analytics
                </h2>
                <p class="text-sm text-gray-500">
                    Delivery performance, trends and status breakdown
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-col sm:flex-row sm:items-end gap-4">
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

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-4">
            <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
                <p class="text-sm text-gray-500">Total Orders</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">
                    {{ summary.total_orders || 0 }}
                </p>
            </div>

            <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
                <p class="text-sm text-gray-500">Delivered</p>
                <p class="text-3xl font-bold text-green-600 mt-2">
                    {{ summary.delivered || 0 }}
                </p>
            </div>

            <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
                <p class="text-sm text-gray-500">Confirmed</p>
                <p class="text-3xl font-bold text-blue-600 mt-2">
                    {{ summary.confirmed || 0 }}
                </p>
            </div>

            <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
                <p class="text-sm text-gray-500">Shipping</p>
                <p class="text-3xl font-bold text-purple-600 mt-2">
                    {{ summary.shipping || 0 }}
                </p>
            </div>

            <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
                <p class="text-sm text-gray-500">Awaiting Fulfillment</p>
                <p class="text-3xl font-bold text-yellow-600 mt-2">
                    {{ summary.awaiting_fulfillment || 0 }}
                </p>
            </div>

            <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
                <p class="text-sm text-gray-500">Cancelled</p>
                <p class="text-3xl font-bold text-red-600 mt-2">
                    {{ summary.cancelled || 0 }}
                </p>
            </div>

            <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
                <p class="text-sm text-gray-500">Delivery Rate</p>
                <p class="text-3xl font-bold text-teal-600 mt-2">
                    {{ summary.delivery_rate || 0 }}%
                </p>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-if="!hasData"
            class="bg-white rounded-xl border border-gray-100 p-10 text-center"
        >
            <p class="text-gray-500">
                No delivery data available for the selected filters.
            </p>
        </div>

        <template v-else>
            <!-- Top Charts Row -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <!-- Status Breakdown Donut -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-900">
                            Delivery Status Breakdown
                        </h3>
                        <p class="text-sm text-gray-500">
                            Distribution of orders by current status
                        </p>
                    </div>

                    <apexchart
                        type="donut"
                        height="320"
                        :options="statusChartOptions"
                        :series="statusChartSeries"
                    />
                </div>

                <!-- Delivery Funnel -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-900">
                            Order Delivery Funnel
                        </h3>
                        <p class="text-sm text-gray-500">
                            Order progression from placement to delivery
                        </p>
                    </div>

                    <apexchart
                        type="bar"
                        height="320"
                        :options="funnelChartOptions"
                        :series="funnelChartSeries"
                    />
                </div>
            </div>

            <!-- Daily Performance Chart -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="mb-4">
                    <h3 class="font-semibold text-gray-900">
                        Daily Delivery Performance
                    </h3>
                    <p class="text-sm text-gray-500">
                        Confirmed vs Delivered orders by day
                    </p>
                </div>

                <apexchart
                    type="bar"
                    height="400"
                    :options="dailyChartOptions"
                    :series="dailyChartSeries"
                />
            </div>

            <!-- Efficiency Trend -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="mb-4">
                    <h3 class="font-semibold text-gray-900">
                        Daily Delivery Efficiency
                    </h3>
                    <p class="text-sm text-gray-500">
                        Delivery efficiency % (Delivered / Confirmed × 100) per day
                    </p>
                </div>

                <apexchart
                    type="line"
                    height="300"
                    :options="efficiencyChartOptions"
                    :series="efficiencyChartSeries"
                />
            </div>

            <!-- Bottom Row: Aging + Rates -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <!-- Order Aging -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-900">Order Aging</h3>
                        <p class="text-sm text-gray-500">
                            Undelivered orders by age bucket
                        </p>
                    </div>

                    <apexchart
                        type="bar"
                        height="260"
                        :options="agingChartOptions"
                        :series="agingChartSeries"
                    />
                </div>

                <!-- Confirmation to Delivery Rate Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col justify-between">
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-900">
                            Confirmation-to-Delivery Rate
                        </h3>
                        <p class="text-sm text-gray-500">
                            Percentage of confirmed orders that were delivered
                        </p>
                    </div>

                    <div class="flex items-center justify-center flex-1">
                        <apexchart
                            type="radialBar"
                            height="260"
                            :options="radialChartOptions"
                            :series="radialChartSeries"
                        />
                    </div>
                </div>
            </div>

            <!-- Daily Breakdown Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Daily Breakdown</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Date
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                    Confirmed
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                    Delivered
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                    Efficiency %
                                </th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr
                                v-for="item in dailyPerformance"
                                :key="item.date"
                            >
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ item.date }}
                                </td>

                                <td class="px-4 py-3 text-sm text-right text-blue-600 font-medium">
                                    {{ item.confirmed }}
                                </td>

                                <td class="px-4 py-3 text-sm text-right text-green-600 font-medium">
                                    {{ item.delivered }}
                                </td>

                                <td class="px-4 py-3 text-sm text-right">
                                    <span
                                        :class="efficiencyClass(item.efficiency)"
                                        class="font-medium"
                                    >
                                        {{ item.efficiency }}%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";

import DateRangePicker from "@/Components/DualDatePicker.vue";
import SelectMerchant from "@/Components/SelectMerchant.vue";

const props = defineProps({
    data: {
        type: Object,
        default: () => ({}),
    },
});

const dateRange = ref(null);
const merchantId = ref(null);
const formValid = ref({ merchant: true });

const summary = computed(() => props.data || {});

const dailyPerformance = computed(
    () => summary.value.daily_performance || [],
);

const statusBreakdown = computed(
    () => summary.value.status_breakdown || [],
);

const aging = computed(() => summary.value.aging || {});

const funnel = computed(() => summary.value.funnel || []);

const dates = computed(() =>
    [...dailyPerformance.value].map((item) => item.date),
);

const hasData = computed(
    () => dailyPerformance.value.length > 0 || statusBreakdown.value.length > 0,
);

// Status Breakdown Donut
const statusChartSeries = computed(() =>
    statusBreakdown.value.map((item) => item.count),
);

const statusChartOptions = computed(() => ({
    labels: statusBreakdown.value.map((item) => item.status),
    legend: {
        position: "bottom",
    },
    colors: [
        "#3B82F6", // In transit - blue
        "#F59E0B", // Returned - amber
        "#EAB308", // Pending - yellow
        "#22C55E", // Delivered - green
        "#EF4444", // Cancelled - red
        "#8B5CF6", // Scheduled - violet
        "#06B6D4", // Dispatched - cyan
    ],
    dataLabels: {
        enabled: true,
        formatter: (val) => `${val.toFixed(1)}%`,
    },
}));

// Funnel Bar Chart
const funnelChartSeries = computed(() => [
    {
        name: "Orders",
        data: funnel.value.map((item) => item.value),
    },
]);

const funnelChartOptions = computed(() => ({
    chart: {
        toolbar: { show: false },
    },
    plotOptions: {
        bar: {
            horizontal: true,
            distributed: true,
            dataLabels: { position: "top" },
        },
    },
    colors: ["#6366F1", "#3B82F6", "#8B5CF6", "#22C55E"],
    xaxis: {
        categories: funnel.value.map((item) => item.stage),
    },
    dataLabels: {
        enabled: true,
        offsetX: 5,
    },
    legend: { show: false },
}));

// Daily Performance Grouped Bar
const dailyChartSeries = computed(() => [
    {
        name: "Confirmed",
        data: dates.value.map(
            (d) =>
                dailyPerformance.value.find((i) => i.date === d)?.confirmed ?? 0,
        ),
    },
    {
        name: "Delivered",
        data: dates.value.map(
            (d) =>
                dailyPerformance.value.find((i) => i.date === d)?.delivered ?? 0,
        ),
    },
]);

const dailyChartOptions = computed(() => ({
    chart: {
        toolbar: { show: false },
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: "55%",
        },
    },
    colors: ["#3B82F6", "#22C55E"],
    xaxis: {
        categories: dates.value,
    },
    legend: {
        position: "top",
    },
    dataLabels: {
        enabled: false,
    },
}));

// Efficiency Line Chart
const efficiencyChartSeries = computed(() => [
    {
        name: "Efficiency %",
        data: dates.value.map(
            (d) =>
                dailyPerformance.value.find((i) => i.date === d)?.efficiency ?? 0,
        ),
    },
]);

const efficiencyChartOptions = computed(() => ({
    chart: {
        toolbar: { show: false },
    },
    stroke: {
        curve: "smooth",
        width: 3,
    },
    colors: ["#F59E0B"],
    xaxis: {
        categories: dates.value,
    },
    yaxis: {
        labels: {
            formatter: (val) => `${val}%`,
        },
    },
    markers: {
        size: 5,
    },
    dataLabels: {
        enabled: false,
    },
}));

// Aging Bar Chart
const agingChartSeries = computed(() => [
    {
        name: "Orders",
        data: [
            aging.value["0_2_days"] || 0,
            aging.value["3_5_days"] || 0,
            aging.value["6_7_days"] || 0,
        ],
    },
]);

const agingChartOptions = computed(() => ({
    chart: {
        toolbar: { show: false },
    },
    plotOptions: {
        bar: {
            distributed: true,
            columnWidth: "50%",
        },
    },
    colors: ["#22C55E", "#F59E0B", "#EF4444"],
    xaxis: {
        categories: ["0–2 Days", "3–5 Days", "6–7 Days"],
    },
    legend: { show: false },
    dataLabels: {
        enabled: true,
    },
}));

// Radial Bar for Confirmation-to-Delivery Rate
const radialChartSeries = computed(() => [
    Math.min(summary.value.confirmed_to_delivery_rate || 0, 100),
]);

const radialChartOptions = computed(() => ({
    chart: {
        toolbar: { show: false },
    },
    plotOptions: {
        radialBar: {
            hollow: {
                size: "60%",
            },
            dataLabels: {
                name: {
                    show: true,
                    offsetY: -10,
                    fontSize: "14px",
                    color: "#6B7280",
                },
                value: {
                    show: true,
                    fontSize: "28px",
                    fontWeight: "bold",
                    color: "#111827",
                    formatter: () =>
                        `${summary.value.confirmed_to_delivery_rate || 0}%`,
                },
            },
        },
    },
    colors: ["#22C55E"],
    labels: ["C→D Rate"],
}));

// Efficiency row coloring helper
const efficiencyClass = (value) => {
    if (value >= 80) return "text-green-600";
    if (value >= 40) return "text-yellow-600";
    return "text-red-600";
};
</script>