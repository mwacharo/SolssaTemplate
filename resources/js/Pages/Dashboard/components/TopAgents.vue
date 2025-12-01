<template>
    <div class="bg-white p-6 rounded-2xl shadow-lg">
        <!-- Header Section -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">
                    Call Agents Performance
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    Confirmation vs delivery success rates
                </p>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <div class="text-xs text-gray-500">Team Average</div>
                    <div class="text-sm font-semibold text-blue-600">
                        {{ teamAverage }}%
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="flex items-center text-xs text-gray-500">
                        <div class="w-3 h-3 bg-blue-500 rounded mr-2"></div>
                        Confirmation
                    </div>
                    <div class="flex items-center text-xs text-gray-500">
                        <div class="w-3 h-3 bg-green-500 rounded mr-2"></div>
                        Delivery
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Container -->
        <div class="mb-6">
            <apexchart
                type="bar"
                height="420"
                :options="chartOptions"
                :series="series"
            />
        </div>

        <!-- Agent Performance Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div
                v-for="(agent, index) in agentData"
                :key="agent.name"
                class="p-4 border border-gray-100 rounded-lg hover:shadow-md transition-all duration-200"
                :class="getAgentCardClass(agent)"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center">
                        <div
                            class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-400 to-purple-500 flex items-center justify-center text-white font-semibold mr-3"
                        >
                            {{ agent.name.split(" ")[1]?.[0] || agent.name[0] }}
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-800">
                                {{ agent.name }}
                            </h4>
                            <p
                                class="text-xs flex items-center space-x-2 text-gray-500"
                            >
                                <span
                                    class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full font-medium"
                                >
                                    {{
                                        agent.BuyoutRate !== undefined
                                            ? agent.BuyoutRate + "%"
                                            : "—"
                                    }}
                                </span>
                                <span class="text-gray-400">/</span>
                                <span
                                    class="px-2 py-0.5 bg-red-50 text-red-700 rounded-full font-medium"
                                >
                                    {{
                                        agent.ReturnRate !== undefined
                                            ? agent.ReturnRate + "%"
                                            : "—"
                                    }}
                                </span>
                                <span class="mx-1 text-gray-300">•</span>
                                <span class="text-gray-500">{{
                                    agent.experience || "N/A"
                                }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span
                            class="px-2 py-1 rounded-full text-xs font-medium"
                            :class="getPerformanceBadge(agent)"
                        >
                            {{ getPerformanceLabel(agent) }}
                        </span>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="grid grid-cols-2 gap-4 mb-3">
                    <div>
                        <div class="text-xs text-gray-500 mb-1">
                            Confirmation Rate
                        </div>
                        <div class="flex items-center">
                            <span class="text-lg font-bold text-blue-600"
                                >{{ agent.confirmationRate }}%</span
                            >
                            <span
                                class="ml-1 text-xs"
                                :class="
                                    agent.confirmationTrend >= 0
                                        ? 'text-green-500'
                                        : 'text-red-500'
                                "
                            >
                                {{ agent.confirmationTrend > 0 ? "+" : ""
                                }}{{ agent.confirmationTrend }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                            <div
                                class="bg-blue-500 h-2 rounded-full transition-all duration-500"
                                :style="{ width: `${agent.confirmationRate}%` }"
                            ></div>
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500 mb-1">
                            Delivery Rate
                        </div>
                        <div class="flex items-center">
                            <span class="text-lg font-bold text-green-600"
                                >{{ agent.deliveryRate }}%</span
                            >
                            <span
                                class="ml-1 text-xs"
                                :class="
                                    agent.deliveryTrend >= 0
                                        ? 'text-green-500'
                                        : 'text-red-500'
                                "
                            >
                                {{ agent.deliveryTrend > 0 ? "+" : ""
                                }}{{ agent.deliveryTrend }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                            <div
                                class="bg-green-500 h-2 rounded-full transition-all duration-500"
                                :style="{ width: `${agent.deliveryRate}%` }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Additional Metrics -->
                <div class="flex justify-between text-sm">
                    <div class="text-center">
                        <div class="font-semibold text-gray-800">
                            {{ agent.totalCalls }}
                        </div>
                        <div class="text-xs text-gray-500">Total Calls</div>
                    </div>
                    <div class="text-center">
                        <div class="font-semibold text-gray-800">
                            {{ agent.avgCallTime }}m
                        </div>
                        <div class="text-xs text-gray-500">Avg Call</div>
                    </div>
                    <div class="text-center">
                        <div class="font-semibold text-gray-800">
                            {{ agent.rating }}⭐
                        </div>
                        <div class="text-xs text-gray-500">Rating</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Insights -->
        <div class="bg-gray-50 rounded-lg p-4 mb-4">
            <h4 class="font-medium text-gray-800 mb-3">Performance Insights</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="flex items-center text-sm">
                    <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                    <span class="text-gray-600"
                        >{{ topPerformer.name }} leads with
                        {{ topPerformer.confirmationRate }}% confirmation</span
                    >
                </div>
                <div class="flex items-center text-sm">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mr-2"></div>
                    <span class="text-gray-600"
                        >Average conversion gap: {{ conversionGap }}%</span
                    >
                </div>
                <div class="flex items-center text-sm">
                    <div class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></div>
                    <span class="text-gray-600"
                        >{{ improvingAgents }} agents showing improvement</span
                    >
                </div>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-4 gap-4 pt-4 border-t border-gray-100">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">
                    {{ overallConfirmation }}%
                </div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">
                    Team Confirmation
                </div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">
                    {{ overallDelivery }}%
                </div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">
                    Team Delivery
                </div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-800">
                    {{ totalCalls }}
                </div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">
                    Total Calls
                </div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-purple-600">
                    {{ activeAgents }}
                </div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">
                    Active Agents
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";

// Props for external data
const props = defineProps({
    topAgents: {
        type: Array,
        default: () => [],
    },
});

// Enhanced agent data with additional metrics
const defaultAgentData = [
    {
        name: "Agent A",
        confirmationRate: 85,
        deliveryRate: 78,
        confirmationTrend: +3,
        deliveryTrend: +2,
        totalCalls: 245,
        avgCallTime: 8.5,
        rating: 4.8,
        shift: "Morning",
        experience: "2 years",
    },
    {
        name: "Agent B",
        confirmationRate: 72,
        deliveryRate: 68,
        confirmationTrend: -1,
        deliveryTrend: +1,
        totalCalls: 198,
        avgCallTime: 9.2,
        rating: 4.5,
        shift: "Evening",
        experience: "1.5 years",
    },
    {
        name: "Agent C",
        confirmationRate: 60,
        deliveryRate: 55,
        confirmationTrend: +2,
        deliveryTrend: +3,
        totalCalls: 167,
        avgCallTime: 7.8,
        rating: 4.2,
        shift: "Night",
        experience: "8 months",
    },
    {
        name: "Agent D",
        confirmationRate: 50,
        deliveryRate: 45,
        confirmationTrend: -2,
        deliveryTrend: 0,
        totalCalls: 134,
        avgCallTime: 10.1,
        rating: 3.9,
        shift: "Morning",
        experience: "6 months",
    },
];

// Use props data if available, otherwise use default
// const agentData = computed(() =>
//     props.topAgents.length > 0 ? props.topAgents : defaultAgentData
// );

const agentData = computed(() =>
    props.topAgents.length > 0
        ? props.topAgents.map((agent) => ({
              name: agent.name,
              confirmationRate: agent.SchedulingRate,
              deliveryRate: agent.DeliveryRate,
              confirmationTrend: agent.confirmationTrend,
              deliveryTrend: agent.deliveryTrend,
              totalCalls: agent.totalCalls,
              avgCallTime: agent.avgCallTime,
              rating: agent.rating,
              shift: agent.shift,
              experience: agent.experience,
              BuyoutRate: agent.BuyoutRate,
              ReturnRate: agent.ReturnRate,
          }))
        : defaultAgentData
);

// Extract data for chart
const agents = computed(() => agentData.value.map((agent) => agent.name));
const confirmations = computed(() =>
    agentData.value.map((agent) => agent.confirmationRate)
);
const deliveries = computed(() =>
    agentData.value.map((agent) => agent.deliveryRate)
);

// Computed analytics
const overallConfirmation = computed(() =>
    Math.round(
        confirmations.value.reduce((sum, val) => sum + val, 0) /
            confirmations.value.length
    )
);
const overallDelivery = computed(() =>
    Math.round(
        deliveries.value.reduce((sum, val) => sum + val, 0) /
            deliveries.value.length
    )
);
const teamAverage = computed(() =>
    Math.round((overallConfirmation.value + overallDelivery.value) / 2)
);
const totalCalls = computed(() =>
    agentData.value.reduce((sum, agent) => sum + agent.totalCalls, 0)
);
const activeAgents = computed(() => agentData.value.length);

const topPerformer = computed(() =>
    agentData.value.reduce((top, agent) =>
        agent.confirmationRate > top.confirmationRate ? agent : top
    )
);

const conversionGap = computed(() =>
    Math.round(overallConfirmation.value - overallDelivery.value)
);

const improvingAgents = computed(
    () =>
        agentData.value.filter(
            (agent) => agent.confirmationTrend > 0 || agent.deliveryTrend > 0
        ).length
);

// Helper functions for styling
const getAgentCardClass = (agent) => {
    const avg = (agent.confirmationRate + agent.deliveryRate) / 2;
    if (avg >= 80) return "ring-2 ring-green-200 bg-green-50";
    if (avg >= 70) return "ring-2 ring-blue-200 bg-blue-50";
    if (avg >= 60) return "ring-2 ring-yellow-200 bg-yellow-50";
    return "ring-2 ring-red-200 bg-red-50";
};

const getPerformanceBadge = (agent) => {
    const avg = (agent.confirmationRate + agent.deliveryRate) / 2;
    if (avg >= 80) return "bg-green-100 text-green-800";
    if (avg >= 70) return "bg-blue-100 text-blue-800";
    if (avg >= 60) return "bg-yellow-100 text-yellow-800";
    return "bg-red-100 text-red-800";
};

const getPerformanceLabel = (agent) => {
    const avg = (agent.confirmationRate + agent.deliveryRate) / 2;
    if (avg >= 80) return "Excellent";
    if (avg >= 70) return "Good";
    if (avg >= 60) return "Average";
    return "Needs Improvement";
};

const series = ref([
    {
        name: "Confirmation Rate",
        data: confirmations.value,
    },
    {
        name: "Delivery Rate",
        data: deliveries.value,
    },
]);

const chartOptions = ref({
    chart: {
        type: "bar",
        stacked: false,
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
        },
    },
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 6,
            borderRadiusApplication: "end",
            barHeight: "65%",
            dataLabels: {
                position: "center",
            },
        },
    },
    dataLabels: {
        enabled: true,
        formatter: (val) => val + "%",
        style: {
            fontSize: "13px",
            fontFamily: "Inter, sans-serif",
            fontWeight: "600",
            colors: ["#fff"],
        },
    },
    xaxis: {
        categories: agents.value,
        title: {
            text: "Performance Rate (%)",
            style: {
                color: "#6b7280",
                fontSize: "12px",
                fontFamily: "Inter, sans-serif",
            },
        },
        max: 100,
        labels: {
            style: {
                colors: "#6b7280",
                fontSize: "12px",
                fontFamily: "Inter, sans-serif",
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
        labels: {
            style: {
                colors: "#374151",
                fontSize: "13px",
                fontFamily: "Inter, sans-serif",
                fontWeight: "500",
            },
        },
    },
    colors: ["#3b82f6", "#10b981"],
    legend: {
        position: "top",
        horizontalAlign: "right",
        floating: false,
        fontSize: "12px",
        fontFamily: "Inter, sans-serif",
        offsetY: -10,
        markers: {
            width: 12,
            height: 12,
            radius: 2,
        },
    },
    grid: {
        show: true,
        borderColor: "#f3f4f6",
        strokeDashArray: 0,
        xaxis: {
            lines: {
                show: true,
            },
        },
        yaxis: {
            lines: {
                show: false,
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
            formatter: function (val, { seriesIndex, dataPointIndex }) {
                const agent = agentData.value[dataPointIndex];
                const metric = seriesIndex === 0 ? "confirmation" : "delivery";
                return `${val}% ${metric} rate<br/>Calls: ${agent.totalCalls}<br/>Rating: ${agent.rating}⭐`;
            },
        },
    },
    responsive: [
        {
            breakpoint: 768,
            options: {
                plotOptions: {
                    bar: {
                        barHeight: "55%",
                    },
                },
                legend: {
                    position: "bottom",
                    offsetY: 10,
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
        if (newData.length > 0) {
            series.value = [
                {
                    name: "Confirmation Rate",
                    data: newData.map((agent) => agent.confirmationRate),
                },
                {
                    name: "Delivery Rate",
                    data: newData.map((agent) => agent.deliveryRate),
                },
            ];

            chartOptions.value.xaxis.categories = newData.map(
                (agent) => agent.name
            );
        }
    },
    { deep: true }
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
}

@media (max-width: 768px) {
    .grid-cols-1.md\:grid-cols-2 {
        grid-template-columns: 1fr;
    }

    .grid-cols-1.md\:grid-cols-3 {
        grid-template-columns: 1fr;
    }
}

/* Animation for progress bars */
@keyframes fillProgress {
    from {
        width: 0%;
    }
    to {
        width: var(--target-width);
    }
}
</style>
