<
<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">
                    Confirmation Analytics
                </h2>
                <p class="text-sm text-gray-500">
                    Confirmation performance, trends and status breakdown
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
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div
                class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm"
            >
                <p class="text-sm text-gray-500">Total Orders</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">
                    {{ summary.total_orders || 0 }}
                </p>
            </div>

            <div
                class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm"
            >
                <p class="text-sm text-gray-500">Confirmed</p>
                <p class="text-3xl font-bold text-green-600 mt-2">
                    {{ summary.confirmed || 0 }}
                </p>
            </div>

            <div
                class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm"
            >
                <p class="text-sm text-gray-500">Pending</p>
                <p class="text-3xl font-bold text-yellow-600 mt-2">
                    {{ summary.pending || 0 }}
                </p>
            </div>

            <div
                class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm"
            >
                <p class="text-sm text-gray-500">Failed</p>
                <p class="text-3xl font-bold text-red-600 mt-2">
                    {{ summary.failed || 0 }}
                </p>
            </div>

            <div
                class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm"
            >
                <p class="text-sm text-gray-500">Confirmation Rate</p>
                <p class="text-3xl font-bold text-blue-600 mt-2">
                    {{ summary.confirmation_rate || 0 }}%
                </p>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-if="!hasData"
            class="bg-white rounded-xl border border-gray-100 p-10 text-center"
        >
            <p class="text-gray-500">
                No confirmation data available for the selected filters.
            </p>
        </div>

        <template v-else>
            <!-- Top Charts -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <!-- Status Breakdown -->
                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"
                >
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-900">
                            Confirmation Status Breakdown
                        </h3>
                        <p class="text-sm text-gray-500">
                            Distribution of order confirmations
                        </p>
                    </div>

                    <apexchart
                        type="donut"
                        height="320"
                        :options="statusChartOptions"
                        :series="statusChartSeries"
                    />
                </div>

                <!-- Orders vs Date -->
                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"
                >
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-900">
                            Orders vs Date
                        </h3>
                        <p class="text-sm text-gray-500">
                            Total orders received per day
                        </p>
                    </div>

                    <apexchart
                        type="bar"
                        height="320"
                        :options="ordersChartOptions"
                        :series="ordersChartSeries"
                    />
                </div>
            </div>

            <!-- Daily Confirmation Performance -->
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"
            >
                <div class="mb-4">
                    <h3 class="font-semibold text-gray-900">
                        Daily Confirmation Performance
                    </h3>
                    <p class="text-sm text-gray-500">
                        Confirmed, Pending and Failed orders by day
                    </p>
                </div>

                <apexchart
                    type="bar"
                    height="400"
                    :options="stackedChartOptions"
                    :series="stackedChartSeries"
                />
            </div>

            <!-- Daily Breakdown Table -->
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
            >
                <div class="p-5 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Daily Breakdown</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                >
                                    Date
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                                >
                                    Total
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                                >
                                    Confirmed
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                                >
                                    Pending
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                                >
                                    Failed
                                </th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr
                                v-for="item in confirmationData"
                                :key="item.date"
                            >
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ item.date }}
                                </td>

                                <td
                                    class="px-4 py-3 text-sm text-right font-medium"
                                >
                                    {{ item.total }}
                                </td>

                                <td
                                    class="px-4 py-3 text-sm text-right text-green-600"
                                >
                                    {{ item.confirmed }}
                                </td>

                                <td
                                    class="px-4 py-3 text-sm text-right text-yellow-600"
                                >
                                    {{ item.pending }}
                                </td>

                                <td
                                    class="px-4 py-3 text-sm text-right text-red-600"
                                >
                                    {{ item.failed }}
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
import { computed } from "vue";

import DateRangePicker from "@/Components/DualDatePicker.vue";
import SelectMerchant from "@/Components/SelectMerchant.vue";

const props = defineProps({
    data: {
        type: Object,
        default: () => ({}),
    },
});

const summary = computed(() => props.data || {});

const confirmationData = computed(
    () => summary.value.confirmation_per_day || [],
);

const dates = computed(() =>
    [...confirmationData.value].reverse().map((item) => item.date),
);

const hasData = computed(() => confirmationData.value.length > 0);

const statusChartSeries = computed(() => [
    summary.value.confirmed || 0,
    summary.value.pending || 0,
    summary.value.failed || 0,
]);

const statusChartOptions = computed(() => ({
    labels: ["Confirmed", "Pending", "Failed"],
    legend: {
        position: "bottom",
    },
}));

const ordersChartSeries = computed(() => [
    {
        name: "Orders",
        data: [...confirmationData.value].reverse().map((item) => item.total),
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
        data: [...confirmationData.value]
            .reverse()
            .map((item) => item.confirmed),
    },
    {
        name: "Pending",
        data: [...confirmationData.value].reverse().map((item) => item.pending),
    },
    {
        name: "Failed",
        data: [...confirmationData.value].reverse().map((item) => item.failed),
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
            columnWidth: "60%",
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
