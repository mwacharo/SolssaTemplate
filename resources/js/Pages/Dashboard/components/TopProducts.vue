<template>
    <div class="bg-white p-6 rounded-2xl shadow-lg">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">
                    Top Selling Products
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    Best performing items this month
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <div class="flex items-center text-xs text-gray-500">
                    <div class="w-2 h-2 bg-green-500 rounded-full mr-1"></div>
                    Units Sold
                </div>
            </div>
        </div>

        <div class="mb-4">
            <apexchart
                type="bar"
                height="380"
                :options="chartOptions"
                :series="series"
            />
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-3 gap-4 pt-4 border-t border-gray-100">
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-800">
                    {{ totalSales }}
                </div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">
                    Total Sales
                </div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">
                    {{ topProduct.name }}
                </div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">
                    Best Seller
                </div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">
                    {{ averageSales }}
                </div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">
                    Avg Sales
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, computed, watch } from "vue";

const props = defineProps({
    topProducts: {
        type: Array,
        default: () => [],
    },
});

// Default static data (fallback)
const defaultProducts = [
    { name: "CBD Scanner", sales: 83, category: "Electronics" },
    { name: "MK Oil", sales: 70, category: "Health" },
    { name: 'Tablet 10"', sales: 55, category: "Electronics" },
    { name: "Smartwatch", sales: 40, category: "Electronics" },
    { name: "Foot Patch", sales: 20, category: "Health" },
];

// Use props if available
const productData = computed(() =>
    props.topProducts?.length ? props.topProducts : defaultProducts
);

// Products & sales arrays
const products = computed(() => productData.value.map((item) => item.name));
const sales = computed(() => productData.value.map((item) => item.sales));

// Stats
const totalSales = computed(() =>
    sales.value.reduce((sum, val) => sum + val, 0)
);
const topProduct = computed(() => {
    const maxSales = Math.max(...sales.value);
    return productData.value.find((p) => p.sales === maxSales);
});
const averageSales = computed(() =>
    Math.round(totalSales.value / sales.value.length)
);

// Chart data
const series = ref([
    {
        name: "Units Sold",
        data: sales.value,
    },
]);

const chartOptions = ref({
    chart: {
        type: "bar",
        toolbar: { show: false },
        animations: {
            enabled: true,
            easing: "easeinout",
            speed: 800,
        },
    },
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 8,
            borderRadiusApplication: "end",
            distributed: true,
            barHeight: "70%",
        },
    },
    dataLabels: {
        enabled: true,
        style: {
            fontSize: "14px",
            fontWeight: "600",
            colors: ["#fff"],
        },
        formatter: (val) => `${val} units`,
    },
    xaxis: {
        categories: products.value,
        labels: {
            style: {
                colors: "#6b7280",
                fontSize: "12px",
            },
            formatter: (val) => `${val} units`,
        },
    },
    yaxis: {
        labels: {
            style: {
                colors: "#374151",
                fontSize: "13px",
                fontWeight: "500",
            },
        },
    },
    colors: ["#3b82f6", "#10b981", "#f59e0b", "#ef4444", "#8b5cf6"],
    grid: {
        borderColor: "#f3f4f6",
        xaxis: { lines: { show: true } },
    },
    tooltip: {
        y: { formatter: (val) => `${val} units sold` },
    },
    legend: { show: false },
});

// Watch for updates when parent loads new products
watch(
    () => props.topProducts,
    (newVal) => {
        const updated = newVal?.length ? newVal : defaultProducts;

        series.value = [
            {
                name: "Units Sold",
                data: updated.map((item) => item.sales),
            },
        ];

        chartOptions.value = {
            ...chartOptions.value,
            xaxis: {
                ...chartOptions.value.xaxis,
                categories: updated.map((item) => item.name),
            },
        };
    },
    { deep: true }
);
</script>

<style scoped>
/* Custom styling for better chart appearance */
.apexcharts-canvas {
    font-family: "Inter", sans-serif;
}

/* Ensure responsive behavior */
@media (max-width: 640px) {
    .grid-cols-3 {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}
</style>
