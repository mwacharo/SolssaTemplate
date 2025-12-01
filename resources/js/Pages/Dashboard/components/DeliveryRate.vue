<template>
    <div class="bg-white p-6 rounded-2xl shadow-lg">
        <!-- Header Section -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">
                    Delivery Rate
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    Live vs Non-Live delivery performance
                </p>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <div class="text-xs text-gray-500">Success Rate</div>
                    <div class="text-sm font-semibold text-green-600">
                        {{ successRate }}%
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-gray-500">Trend</div>
                    <div class="text-sm font-semibold" :class="trendColor">
                        {{ trend > 0 ? "+" : "" }}{{ trend }}%
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Container -->
        <div class="relative mb-6">
            <apexchart
                type="donut"
                height="320"
                :options="chartOptions"
                :series="series"
            />

            <!-- Center Statistics Overlay -->
            <div
                class="absolute inset-0 flex items-center justify-center pointer-events-none"
            >
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-800 mb-1">
                        {{ totalDeliveries }}
                    </div>
                    <div class="text-sm text-gray-500">Total Deliveries</div>
                    <div class="text-xs text-gray-400 mt-1">
                        {{ currentPeriod }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Delivery Breakdown Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <!-- Live Deliveries Card -->
            <div
                class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center">
                        <div
                            class="w-4 h-4 bg-green-500 rounded-full mr-3"
                        ></div>
                        <span class="font-medium text-gray-800"
                            >Live Deliveries</span
                        >
                    </div>
                    <div class="flex items-center text-green-600">
                        <svg
                            class="w-4 h-4 mr-1"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <span class="text-xs font-medium">Active</span>
                    </div>
                </div>

                <div class="flex items-end justify-between">
                    <div>
                        <div class="text-2xl font-bold text-green-600">
                            {{ deliveryData.live.count }}
                        </div>
                        <div class="text-sm text-green-600 font-medium">
                            {{ deliveryData.live.percentage }}%
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Avg Time</div>
                        <div class="text-sm font-medium text-gray-700">
                            {{ deliveryData.live.avgTime }}min
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-3">
                    <div class="w-full bg-green-200 rounded-full h-2">
                        <div
                            class="bg-green-500 h-2 rounded-full transition-all duration-700"
                            :style="{
                                width: `${deliveryData.live.percentage}%`,
                            }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Non-Live Deliveries Card -->
            <div
                class="p-4 bg-gradient-to-r from-red-50 to-pink-50 rounded-lg border border-red-200"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-red-500 rounded-full mr-3"></div>
                        <span class="font-medium text-gray-800"
                            >Non-Live Deliveries</span
                        >
                    </div>
                    <div class="flex items-center text-red-600">
                        <svg
                            class="w-4 h-4 mr-1"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <span class="text-xs font-medium">Delayed</span>
                    </div>
                </div>

                <div class="flex items-end justify-between">
                    <div>
                        <div class="text-2xl font-bold text-red-600">
                            {{ deliveryData.nonLive.count }}
                        </div>
                        <div class="text-sm text-red-600 font-medium">
                            {{ deliveryData.nonLive.percentage }}%
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Avg Time</div>
                        <div class="text-sm font-medium text-gray-700">
                            {{ deliveryData.nonLive.avgTime }}min
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-3">
                    <div class="w-full bg-red-200 rounded-full h-2">
                        <div
                            class="bg-red-500 h-2 rounded-full transition-all duration-700"
                            :style="{
                                width: `${deliveryData.nonLive.percentage}%`,
                            }"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Insights -->
        <div class="bg-gray-50 rounded-lg p-4 mb-4">
            <h4 class="font-medium text-gray-800 mb-3 flex items-center">
                <svg
                    class="w-4 h-4 mr-2 text-blue-500"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenoever"
                    />
                </svg>
                Performance Insights
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="flex items-center text-sm">
                    <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                    <span class="text-gray-600"
                        >{{ insights.bestPeriod }} shows highest live delivery
                        rate</span
                    >
                </div>
                <div class="flex items-center text-sm">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mr-2"></div>
                    <span class="text-gray-600"
                        >Average delivery time:
                        {{ insights.avgDeliveryTime }} minutes</span
                    >
                </div>
                <div class="flex items-center text-sm">
                    <div class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></div>
                    <span class="text-gray-600"
                        >{{ insights.improvement }}% improvement needed to reach
                        target</span
                    >
                </div>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-4 gap-4 pt-4 border-t border-gray-100">
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">
                    {{ successRate }}%
                </div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">
                    Success Rate
                </div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">
                    {{ averageTime }}min
                </div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">
                    Avg Time
                </div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600">
                    {{ deliverySpeed }}
                </div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">
                    Daily Rate
                </div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-orange-600">
                    {{ efficiency }}%
                </div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">
                    Efficiency
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";

// Props for external data
const props = defineProps({
    deliveryRate: {
        type: Object,
        default: () => ({}),
    },
    period: {
        type: String,
        default: "This Month",
    },
});

// Enhanced delivery data with additional metrics
const defaultData = {
    live: {
        count: 320,
        avgTime: 25,
        trend: +5,
    },
    nonLive: {
        count: 180,
        avgTime: 45,
        trend: -2,
    },
    target: 400,
    insights: {
        bestPeriod: "Morning hours",
        improvement: 8,
    },
};

// Use props data or default
// const deliveryStats = computed(() =>
//     Object.keys(props.data).length > 0 ? props.data : defaultData
// );

// Use props data or default
const deliveryStats = computed(() => {
    if (props.deliveryRate && Object.keys(props.deliveryRate).length > 0) {
        return {
            live: {
                count: props.deliveryRate.live || 0,
                avgTime: 25, // Default since not provided in data
                trend: +5,
            },
            nonLive: {
                count: props.deliveryRate.non_live || 0,
                avgTime: 45, // Default since not provided in data
                trend: -2,
            },
            target: props.deliveryRate.total || 400,
            delivered: props.deliveryRate.delivered || 0,
            rate: props.deliveryRate.rate || 0,
            insights: {
                bestPeriod: "Morning hours",
                improvement: Math.max(0, 95 - (props.deliveryRate.rate || 0)),
            },
        };
    }
    return defaultData;
});

// Computed delivery data with percentages
const deliveryData = computed(() => {
    const liveCount = deliveryStats.value.live.count;
    const nonLiveCount = deliveryStats.value.nonLive.count;
    const total = liveCount + nonLiveCount;

    return {
        live: {
            count: liveCount,
            percentage: Math.round((liveCount / total) * 100),
            avgTime: deliveryStats.value.live.avgTime,
            trend: deliveryStats.value.live.trend || 0,
        },
        nonLive: {
            count: nonLiveCount,
            percentage: Math.round((nonLiveCount / total) * 100),
            avgTime: deliveryStats.value.nonLive.avgTime,
            trend: deliveryStats.value.nonLive.trend || 0,
        },
    };
});

// Computed statistics
const totalDeliveries = computed(
    () => deliveryData.value.live.count + deliveryData.value.nonLive.count
);
const successRate = computed(() => deliveryData.value.live.percentage);
const currentPeriod = computed(() => props.period);

const trend = computed(() => {
    return Math.round(
        (deliveryData.value.live.trend +
            Math.abs(deliveryData.value.nonLive.trend)) /
            2
    );
});

const trendColor = computed(() => {
    return trend.value >= 0 ? "text-green-600" : "text-red-600";
});

const averageTime = computed(() =>
    Math.round(
        (deliveryData.value.live.avgTime + deliveryData.value.nonLive.avgTime) /
            2
    )
);

const deliverySpeed = computed(() => Math.round(totalDeliveries.value / 30)); // Assuming monthly data
const efficiency = computed(() =>
    Math.round(
        (deliveryData.value.live.count / (deliveryStats.value.target || 400)) *
            100
    )
);

const insights = computed(() => ({
    bestPeriod: deliveryStats.value.insights?.bestPeriod || "Morning hours",
    avgDeliveryTime: averageTime.value,
    improvement:
        deliveryStats.value.insights?.improvement ||
        Math.max(0, 90 - successRate.value),
}));

// Chart data
const series = ref([
    deliveryData.value.live.count,
    deliveryData.value.nonLive.count,
]);

const chartOptions = ref({
    labels: ["Live Deliveries", "Non-Live Deliveries"],
    colors: ["#10b981", "#ef4444"],
    legend: {
        position: "bottom",
        fontSize: "13px",
        fontFamily: "Inter, sans-serif",
        offsetY: 10,
        markers: {
            width: 12,
            height: 12,
            radius: 6,
        },
        itemMargin: {
            horizontal: 15,
            vertical: 5,
        },
    },
    dataLabels: {
        enabled: true,
        formatter: (val) => val.toFixed(1) + "%",
        style: {
            fontSize: "14px",
            fontFamily: "Inter, sans-serif",
            fontWeight: "600",
            colors: ["#fff"],
        },
        dropShadow: {
            enabled: false,
        },
    },
    plotOptions: {
        pie: {
            donut: {
                size: "70%",
                labels: {
                    show: false, // We're using our own center content
                },
            },
        },
    },
    chart: {
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
    tooltip: {
        enabled: true,
        theme: "light",
        style: {
            fontSize: "12px",
            fontFamily: "Inter, sans-serif",
        },
        y: {
            formatter: function (val, { seriesIndex }) {
                const type = seriesIndex === 0 ? "live" : "nonLive";
                const avgTime = deliveryData.value[type].avgTime;
                return `${val} deliveries<br/>Avg Time: ${avgTime} min`;
            },
        },
    },
    stroke: {
        width: 2,
        colors: ["#fff"],
    },
    states: {
        hover: {
            filter: {
                type: "lighten",
                value: 0.1,
            },
        },
        active: {
            allowMultipleDataPointsSelection: false,
            filter: {
                type: "darken",
                value: 0.1,
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
                legend: {
                    position: "bottom",
                    offsetY: 0,
                },
            },
        },
    ],
});

// Watch for data changes
import { watch } from "vue";
watch(
    () => props.data,
    (newData) => {
        if (Object.keys(newData).length > 0) {
            const liveCount = newData.live?.count || 0;
            const nonLiveCount = newData.nonLive?.count || 0;
            series.value = [liveCount, nonLiveCount];
        }
    },
    { deep: true }
);

// Update series when computed data changes
watch(
    deliveryData,
    (newData) => {
        series.value = [newData.live.count, newData.nonLive.count];
    },
    { immediate: true }
);
</script>

<style scoped>
.apexcharts-canvas {
    font-family: "Inter", sans-serif;
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .grid-cols-4 {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .grid-cols-1.md\:grid-cols-2 {
        grid-template-columns: 1fr;
    }

    .grid-cols-1.md\:grid-cols-3 {
        grid-template-columns: 1fr;
    }
}

/* Animation for progress bars */
.transition-all {
    transition-delay: 0.5s;
}

/* Hover effects for cards */
.hover\:shadow-md {
    transition: box-shadow 0.2s ease-in-out;
}
</style>
