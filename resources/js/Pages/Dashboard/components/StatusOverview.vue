<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <!-- Header Section -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-3">
                <div class="p-2.5 bg-purple-100 rounded-xl">
                    <svg
                        class="w-5 h-5 text-purple-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        Order Status Overview
                    </h3>
                    <p class="text-sm text-gray-500">
                        Current distribution of order statuses
                    </p>
                </div>
            </div>

            <!-- Time Filter -->
            <div class="flex bg-gray-100 rounded-lg p-1">
                <button
                    v-for="period in timePeriods"
                    :key="period"
                    @click="selectedPeriod = period"
                    :class="[
                        'px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-200',
                        selectedPeriod === period
                            ? 'bg-white text-purple-600 shadow-sm'
                            : 'text-gray-600 hover:text-gray-900',
                    ]"
                >
                    {{ period }}
                </button>
            </div>
        </div>

        <!-- Chart Container -->
        <div class="relative">
            <apexchart
                type="donut"
                height="320"
                :options="chartOptions"
                :series="safeStatus"
                class="mx-auto"
            />

            <!-- Stats Overlay -->
            <div
                class="absolute top-4 right-4 bg-white/95 backdrop-blur-sm rounded-xl p-4 shadow-lg border border-gray-200"
            >
                <div class="space-y-3">
                    <div class="text-center pb-3 border-b border-gray-200">
                        <p
                            class="text-xs text-gray-500 font-medium uppercase tracking-wide"
                        >
                            Success Rate
                        </p>
                        <p class="text-2xl font-bold text-green-600 mt-1">
                            {{ successRate }}%
                        </p>
                    </div>
                    <div class="text-center">
                        <p
                            class="text-xs text-gray-500 font-medium uppercase tracking-wide"
                        >
                            Total Orders
                        </p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">
                            {{ totalOrders.toLocaleString() }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Breakdown List -->
        <div class="space-y-3 mt-8">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-sm font-semibold text-gray-700">
                    Detailed Status Breakdown
                </h4>
                <span
                    class="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full"
                >
                    {{ statusBreakdown.length }} Categories
                </span>
            </div>

            <div
                v-for="(item, index) in statusBreakdown"
                :key="item.label"
                class="group relative flex items-center justify-between p-4 rounded-xl border-2 transition-all duration-300 hover:shadow-lg hover:scale-[1.02] cursor-pointer"
                :class="item.bgClass"
            >
                <div class="flex items-center space-x-4 flex-1">
                    <!-- Status Icon -->
                    <div
                        :class="[
                            'w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-300 group-hover:scale-110',
                            item.iconBgClass,
                        ]"
                    >
                        <component
                            :is="item.icon"
                            :class="['w-6 h-6', item.iconColorClass]"
                        />
                    </div>

                    <!-- Status Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-2 mb-1">
                            <p :class="['text-base font-bold', item.textClass]">
                                {{ item.label }}
                            </p>
                            <div
                                :class="[
                                    'px-2 py-0.5 rounded-md text-xs font-semibold',
                                    item.badgeClass,
                                ]"
                            >
                                {{ item.count }} orders
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="flex items-center space-x-3">
                            <div
                                :class="[
                                    'flex-1 rounded-full h-2 overflow-hidden',
                                    item.progressBg,
                                ]"
                            >
                                <div
                                    :class="[
                                        'h-2 rounded-full transition-all duration-1000 ease-out',
                                        item.progressColor,
                                    ]"
                                    :style="{ width: item.percentage + '%' }"
                                ></div>
                            </div>
                            <span
                                :class="[
                                    'text-xs font-medium min-w-[3rem] text-right',
                                    item.subTextClass,
                                ]"
                            >
                                {{ item.percentage }}%
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Count & Percentage Display -->
                <div class="flex items-center space-x-4 ml-4">
                    <!-- Large Number Display -->
                    <div class="text-right">
                        <p
                            :class="[
                                'text-3xl font-black leading-none',
                                item.textClass,
                            ]"
                        >
                            {{ item.count }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            of {{ totalOrders }}
                        </p>
                    </div>

                    <!-- Trend Indicator -->
                    <div :class="['p-2 rounded-lg', item.trendBg]">
                        <svg
                            v-if="item.trend === 'up'"
                            class="w-4 h-4 text-green-600"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <svg
                            v-else-if="item.trend === 'down'"
                            class="w-4 h-4 text-red-600"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 112 0v11.586l2.293-2.293a1 1 0 011.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <div
                            v-else
                            class="w-4 h-4 flex items-center justify-center"
                        >
                            <div
                                class="w-3 h-0.5 rounded-full bg-gray-400"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Stats Row -->
        <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-gray-100">
            <div class="text-center p-3 bg-green-50 rounded-lg">
                <p class="text-xs text-green-600 font-medium uppercase">
                    Completed
                </p>
                <p class="text-2xl font-bold text-green-700 mt-1">
                    {{ safeStatus[2] }}
                </p>
            </div>
            <div class="text-center p-3 bg-amber-50 rounded-lg">
                <p class="text-xs text-amber-600 font-medium uppercase">
                    In Progress
                </p>
                <p class="text-2xl font-bold text-amber-700 mt-1">
                    {{ safeStatus[0] + safeStatus[1] }}
                </p>
            </div>
            <div class="text-center p-3 bg-red-50 rounded-lg">
                <p class="text-xs text-red-600 font-medium uppercase">Failed</p>
                <p class="text-2xl font-bold text-red-700 mt-1">
                    {{ safeStatus[3] + safeStatus[4] }}
                </p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 gap-3 mt-6">
            <button
                class="flex items-center justify-center space-x-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium py-3 px-4 rounded-lg transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0"
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
                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"
                    />
                </svg>
                <span>Manage Orders</span>
            </button>

            <button
                class="flex items-center justify-center space-x-2 border-2 border-gray-300 hover:bg-gray-50 hover:border-gray-400 text-gray-700 text-sm font-medium py-3 px-4 rounded-lg transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0"
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
                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                </svg>
                <span>View Report</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";

// Props
const props = defineProps({
    status: {
        type: Array,
        required: true,
        default: () => [20, 15, 40, 10, 5], // [pending, shipped, delivered, returned, cancelled]
    },
});

// Reactive data
const selectedPeriod = ref("7D");
const timePeriods = ["24H", "7D", "30D", "90D"];

// Status configuration
const statusConfig = [
    {
        label: "Pending",
        color: "#F59E0B",
        bgClass: "bg-amber-50 border-amber-200 hover:border-amber-300",
        iconBgClass: "bg-amber-100 group-hover:bg-amber-200",
        iconColorClass: "text-amber-600",
        textClass: "text-amber-800",
        subTextClass: "text-amber-700",
        badgeClass: "bg-amber-200 text-amber-800",
        progressBg: "bg-amber-100",
        progressColor: "bg-amber-500",
        trendBg: "bg-amber-100",
        trend: "up",
        icon: "ClockIcon",
    },
    {
        label: "Shipped",
        color: "#3B82F6",
        bgClass: "bg-blue-50 border-blue-200 hover:border-blue-300",
        iconBgClass: "bg-blue-100 group-hover:bg-blue-200",
        iconColorClass: "text-blue-600",
        textClass: "text-blue-800",
        subTextClass: "text-blue-700",
        badgeClass: "bg-blue-200 text-blue-800",
        progressBg: "bg-blue-100",
        progressColor: "bg-blue-500",
        trendBg: "bg-blue-100",
        trend: "up",
        icon: "TruckIcon",
    },
    {
        label: "Delivered",
        color: "#10B981",
        bgClass: "bg-green-50 border-green-200 hover:border-green-300",
        iconBgClass: "bg-green-100 group-hover:bg-green-200",
        iconColorClass: "text-green-600",
        textClass: "text-green-800",
        subTextClass: "text-green-700",
        badgeClass: "bg-green-200 text-green-800",
        progressBg: "bg-green-100",
        progressColor: "bg-green-500",
        trendBg: "bg-green-100",
        trend: "up",
        icon: "CheckIcon",
    },
    {
        label: "Returned",
        color: "#8B5CF6",
        bgClass: "bg-purple-50 border-purple-200 hover:border-purple-300",
        iconBgClass: "bg-purple-100 group-hover:bg-purple-200",
        iconColorClass: "text-purple-600",
        textClass: "text-purple-800",
        subTextClass: "text-purple-700",
        badgeClass: "bg-purple-200 text-purple-800",
        progressBg: "bg-purple-100",
        progressColor: "bg-purple-500",
        trendBg: "bg-purple-100",
        trend: "stable",
        icon: "ArrowLeftIcon",
    },
    {
        label: "Cancelled",
        color: "#EF4444",
        bgClass: "bg-red-50 border-red-200 hover:border-red-300",
        iconBgClass: "bg-red-100 group-hover:bg-red-200",
        iconColorClass: "text-red-600",
        textClass: "text-red-800",
        subTextClass: "text-red-700",
        badgeClass: "bg-red-200 text-red-800",
        progressBg: "bg-red-100",
        progressColor: "bg-red-500",
        trendBg: "bg-red-100",
        trend: "down",
        icon: "XIcon",
    },
];

// Filter out non-numeric values and ensure we only get the first 5 values
const safeStatus = computed(() => {
    if (!Array.isArray(props.status)) return [0, 0, 0, 0, 0];

    // Filter only numeric values and take first 5
    const filtered = props.status
        .filter((val) => typeof val === "number" && !isNaN(val))
        .slice(0, 5);

    // Pad with zeros if needed
    while (filtered.length < 5) {
        filtered.push(0);
    }

    return filtered;
});

const totalOrders = computed(() => safeStatus.value.reduce((a, b) => a + b, 0));

// Chart options
const chartOptions = computed(() => ({
    chart: {
        type: "donut",
        fontFamily: "Inter, sans-serif",
        toolbar: {
            show: false,
        },
        animations: {
            enabled: true,
            easing: "easeinout",
            speed: 800,
            animateGradually: {
                enabled: true,
                delay: 150,
            },
            dynamicAnimation: {
                enabled: true,
                speed: 350,
            },
        },
    },

    labels: statusConfig.map((s) => s.label),
    colors: statusConfig.map((s) => s.color),

    plotOptions: {
        pie: {
            donut: {
                size: "68%",
                labels: {
                    show: true,
                    name: {
                        show: true,
                        fontSize: "15px",
                        fontWeight: 600,
                        color: "#374151",
                        offsetY: -10,
                        formatter: (val) => val,
                    },
                    value: {
                        show: true,
                        fontSize: "32px",
                        fontWeight: 700,
                        color: "#111827",
                        offsetY: 10,
                        formatter: (val) => val,
                    },
                    total: {
                        show: true,
                        showAlways: true,
                        label: "Total Orders",
                        fontSize: "13px",
                        fontWeight: 600,
                        color: "#6B7280",
                        formatter: () => {
                            return totalOrders.value.toLocaleString();
                        },
                    },
                },
            },
        },
    },

    dataLabels: {
        enabled: true,
        formatter: (val, opt) => {
            const count = safeStatus.value[opt.seriesIndex];
            return count > 0 ? `${count}\n${Math.round(val)}%` : "";
        },
        style: {
            fontSize: "12px",
            fontWeight: 700,
            colors: ["#FFFFFF"],
        },
        dropShadow: {
            enabled: true,
            blur: 3,
            opacity: 0.9,
        },
    },

    legend: {
        show: false,
    },

    tooltip: {
        enabled: true,
        theme: "light",
        style: {
            fontSize: "13px",
            fontFamily: "Inter, sans-serif",
        },
        y: {
            formatter: (val, { seriesIndex }) => {
                const count = safeStatus.value[seriesIndex];
                return `${count} orders (${val.toFixed(1)}%)`;
            },
        },
    },

    stroke: {
        show: true,
        width: 3,
        colors: ["#FFFFFF"],
    },

    states: {
        hover: {
            filter: {
                type: "lighten",
                value: 0.15,
            },
        },
        active: {
            filter: {
                type: "darken",
                value: 0.15,
            },
        },
    },

    responsive: [
        {
            breakpoint: 480,
            options: {
                chart: {
                    height: 280,
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: "65%",
                            labels: {
                                value: {
                                    fontSize: "26px",
                                },
                                total: {
                                    fontSize: "12px",
                                },
                            },
                        },
                    },
                },
            },
        },
    ],
}));

const successRate = computed(() => {
    if (totalOrders.value === 0) return 0;
    const delivered = safeStatus.value[2] || 0;
    return Math.round((delivered / totalOrders.value) * 100);
});

const statusBreakdown = computed(() => {
    return statusConfig.map((config, index) => ({
        ...config,
        count: safeStatus.value[index] || 0,
        percentage:
            totalOrders.value > 0
                ? Math.round(
                      (safeStatus.value[index] / totalOrders.value) * 100
                  )
                : 0,
    }));
});

// Icon components
const ClockIcon = {
    template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
};

const TruckIcon = {
    template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>`,
};

const CheckIcon = {
    template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
};

const ArrowLeftIcon = {
    template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>`,
};

const XIcon = {
    template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
};
</script>

<style scoped>
/* Enhanced animations */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.group:nth-child(1) {
    animation: slideIn 0.3s ease-out 0.1s both;
}
.group:nth-child(2) {
    animation: slideIn 0.3s ease-out 0.2s both;
}
.group:nth-child(3) {
    animation: slideIn 0.3s ease-out 0.3s both;
}
.group:nth-child(4) {
    animation: slideIn 0.3s ease-out 0.4s both;
}
.group:nth-child(5) {
    animation: slideIn 0.3s ease-out 0.5s both;
}

/* Enhanced hover effects */
.group:hover {
    transform: translateX(4px);
}

/* Progress bar animations */
.transition-all {
    transition: all 0.7s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Button animations */
button {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ApexCharts custom styling */
:deep(.apexcharts-tooltip) {
    border-radius: 10px !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15) !important;
    border: 1px solid #e5e7eb !important;
}

:deep(.apexcharts-datalabel) {
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}
</style>
