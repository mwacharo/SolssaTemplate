<template>
    <div class="space-y-6">
        <!-- KPI Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-white rounded-xl p-4 shadow-sm border">
                <p class="text-sm text-gray-500">Total Orders</p>
                <p class="text-2xl font-bold">
                    {{ summary.total_orders }}
                </p>
            </div>

            <div class="bg-white rounded-xl p-4 shadow-sm border">
                <p class="text-sm text-gray-500">Confirmed</p>
                <p class="text-2xl font-bold text-green-600">
                    {{ summary.confirmed }}
                </p>
            </div>

            <div class="bg-white rounded-xl p-4 shadow-sm border">
                <p class="text-sm text-gray-500">Pending</p>
                <p class="text-2xl font-bold text-yellow-600">
                    {{ summary.pending }}
                </p>
            </div>

            <div class="bg-white rounded-xl p-4 shadow-sm border">
                <p class="text-sm text-gray-500">Failed</p>
                <p class="text-2xl font-bold text-red-600">
                    {{ summary.failed }}
                </p>
            </div>

            <div class="bg-white rounded-xl p-4 shadow-sm border">
                <p class="text-sm text-gray-500">Confirmation Rate</p>
                <p class="text-2xl font-bold text-blue-600">
                    {{ summary.confirmation_rate }}%
                </p>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <!-- Pie Chart -->
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <h3 class="font-semibold mb-4">
                    Confirmation Status Breakdown
                </h3>

                <apexchart
                    height="320"
                    type="donut"
                    :options="statusChartOptions"
                    :series="statusChartSeries"
                />
            </div>

            <!-- Orders vs Date -->
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <h3 class="font-semibold mb-4">Orders vs Date</h3>

                <apexchart
                    height="320"
                    type="bar"
                    :options="ordersChartOptions"
                    :series="ordersChartSeries"
                />
            </div>
        </div>

        <!-- Stacked Confirmation Trend -->
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <h3 class="font-semibold mb-4">Daily Confirmation Performance</h3>

            <apexchart
                height="400"
                type="bar"
                :options="stackedChartOptions"
                :series="stackedChartSeries"
            />
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    data: {
        type: Object,
        required: true,
    },
});

const summary = computed(() => props.data ?? {});

const dates = computed(() =>
    [...(summary.value.confirmation_per_day || [])]
        .reverse()
        .map((item) => item.date),
);

const statusChartSeries = computed(() =>
    (summary.value.status_breakdown || []).map((item) => item.total),
);

const statusChartOptions = computed(() => ({
    labels: (summary.value.status_breakdown || []).map((item) => item.status),
    legend: {
        position: "bottom",
    },
}));

const ordersChartSeries = computed(() => [
    {
        name: "Orders",
        data: [...(summary.value.confirmation_per_day || [])]
            .reverse()
            .map((item) => item.total),
    },
]);

const ordersChartOptions = computed(() => ({
    chart: {
        toolbar: {
            show: false,
        },
    },
    xaxis: {
        categories: dates.value,
    },
    dataLabels: {
        enabled: false,
    },
}));

const stackedChartSeries = computed(() => [
    {
        name: "Confirmed",
        data: [...(summary.value.confirmation_per_day || [])]
            .reverse()
            .map((item) => item.confirmed),
    },
    {
        name: "Pending",
        data: [...(summary.value.confirmation_per_day || [])]
            .reverse()
            .map((item) => item.pending),
    },
    {
        name: "Failed",
        data: [...(summary.value.confirmation_per_day || [])]
            .reverse()
            .map((item) => item.failed),
    },
]);

const stackedChartOptions = computed(() => ({
    chart: {
        stacked: true,
        toolbar: {
            show: false,
        },
    },
    plotOptions: {
        bar: {
            horizontal: false,
        },
    },
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
</script>
