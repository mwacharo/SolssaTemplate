<template>
    <div class="inv-root">
        <!-- Alert Banner -->
        <transition name="slide-down">
            <div v-if="criticalCount > 0" class="alert-banner">
                <span class="alert-pulse"></span>
                <svg
                    class="alert-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"
                    />
                </svg>
                <span class="alert-text">
                    <strong
                        >{{ criticalCount }} SKU{{
                            criticalCount > 1 ? "s" : ""
                        }}</strong
                    >
                    {{
                        outStockCount > 0
                            ? `(${outStockCount} out of stock)`
                            : ""
                    }}
                    require immediate restocking
                </span>
                <button class="alert-action" @click="activeTab = 'restock'">
                    View Queue →
                </button>
            </div>
        </transition>

        <!-- KPI Strip -->
        <div class="kpi-strip">
            <div class="kpi-card">
                <div class="kpi-icon kpi-icon--blue">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                        />
                    </svg>
                </div>
                <div class="kpi-body">
                    <p class="kpi-label">Total Units</p>
                    <p class="kpi-value">{{ formatNumber(inventory.items) }}</p>
                    <p class="kpi-sub">
                        {{ formatNumber(inventory.skus) }} SKUs tracked
                    </p>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon kpi-icon--emerald">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>
                <div class="kpi-body">
                    <p class="kpi-label">Stock Value</p>
                    <p class="kpi-value">KSH {{ stockValue }}</p>
                    <p class="kpi-sub">Active inventory worth</p>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon kpi-icon--green">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>
                <div class="kpi-body">
                    <p class="kpi-label">In Stock</p>
                    <p class="kpi-value kpi-value--green">
                        {{ inventory.inStock }}%
                    </p>
                    <p class="kpi-sub">
                        {{ calculateItems("inStock") }} units available
                    </p>
                </div>
            </div>

            <div
                class="kpi-card"
                :class="{ 'kpi-card--danger': criticalCount > 0 }"
            >
                <div
                    class="kpi-icon"
                    :class="
                        criticalCount > 0 ? 'kpi-icon--red' : 'kpi-icon--gray'
                    "
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"
                        />
                    </svg>
                </div>
                <div class="kpi-body">
                    <p class="kpi-label">Need Restock</p>
                    <p
                        class="kpi-value"
                        :class="criticalCount > 0 ? 'kpi-value--red' : ''"
                    >
                        {{ criticalCount }}
                    </p>
                    <p class="kpi-sub">
                        {{ outStockCount }} out · {{ lowStockSkuCount }} low
                    </p>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                class="tab-btn"
                :class="{ 'tab-btn--active': activeTab === tab.id }"
                @click="activeTab = tab.id"
            >
                {{ tab.label }}
                <span
                    v-if="tab.badge"
                    class="tab-badge"
                    :class="tab.badgeCls"
                    >{{ tab.badge }}</span
                >
            </button>
        </div>

        <!-- Tab: Overview -->
        <div v-show="activeTab === 'overview'" class="tab-panel">
            <!-- Distribution Bars -->
            <div class="section-card">
                <div class="section-header">
                    <span class="section-title">Stock Distribution</span>
                    <div class="chart-toggle">
                        <button
                            v-for="ct in ['column', 'donut']"
                            :key="ct"
                            class="toggle-btn"
                            :class="{ 'toggle-btn--active': chartType === ct }"
                            @click="chartType = ct"
                        >
                            {{ ct.charAt(0).toUpperCase() + ct.slice(1) }}
                        </button>
                    </div>
                </div>

                <div class="dist-rows">
                    <div
                        v-for="item in distributionItems"
                        :key="item.key"
                        class="dist-row"
                    >
                        <div class="dist-meta">
                            <span
                                class="dist-dot"
                                :style="{ background: item.color }"
                            ></span>
                            <span class="dist-name">{{ item.label }}</span>
                        </div>
                        <div class="dist-track">
                            <div
                                class="dist-fill"
                                :style="{
                                    width: item.pct + '%',
                                    background: item.color,
                                }"
                            ></div>
                        </div>
                        <div class="dist-stats">
                            <span
                                class="dist-pct"
                                :style="{ color: item.color }"
                                >{{ item.pct }}%</span
                            >
                            <span class="dist-count"
                                >{{ item.count }} SKUs</span
                            >
                        </div>
                    </div>
                </div>

                <div class="chart-wrap">
                    <apexchart
                        :type="chartType"
                        height="220"
                        :options="chartOptions"
                        :series="chartSeries"
                    />
                </div>
            </div>

            <!-- Status Breakdown -->
            <div class="status-grid">
                <div
                    v-for="item in distributionItems"
                    :key="item.key"
                    class="status-card"
                    :style="{
                        '--accent': item.color,
                        '--bg': item.bg,
                        '--border': item.border,
                    }"
                >
                    <div class="status-left">
                        <span
                            class="status-indicator"
                            :style="{ background: item.color }"
                        ></span>
                        <div>
                            <p
                                class="status-name"
                                :style="{ color: item.darkColor }"
                            >
                                {{ item.label }}
                            </p>
                            <p class="status-items">
                                {{ calculateItems(item.key) }} units
                            </p>
                        </div>
                    </div>
                    <div class="status-right">
                        <p
                            class="status-pct"
                            :style="{ color: item.darkColor }"
                        >
                            {{ item.pct }}%
                        </p>
                        <div class="status-bar">
                            <div
                                class="status-bar-fill"
                                :style="{
                                    width: item.pct + '%',
                                    background: item.color,
                                }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Restock Queue -->
        <div v-show="activeTab === 'restock'" class="tab-panel">
            <div
                v-if="
                    !inventory.restocking || inventory.restocking.length === 0
                "
                class="empty-state"
            >
                <svg
                    viewBox="0 0 48 48"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M24 8v32m-8-16l8 8 8-8M12 40h24"
                    />
                </svg>
                <p>All SKUs are well-stocked</p>
                <span>No restocking required at this time</span>
            </div>

            <div v-else>
                <!-- Filter Row -->
                <div class="filter-row">
                    <button
                        v-for="f in restockFilters"
                        :key="f.val"
                        class="filter-btn"
                        :class="{
                            'filter-btn--active': restockFilter === f.val,
                        }"
                        @click="restockFilter = f.val"
                    >
                        {{ f.label }}
                    </button>
                </div>

                <!-- Restock Table -->
                <div class="restock-wrap">
                    <table class="restock-table">
                        <thead>
                            <tr>
                                <th>Product / SKU</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Urgency</th>
                                <th class="text-center">Stock vs Min</th>
                                <th class="text-center">Committed</th>
                                <th class="text-right">Unit price</th>
                                <th class="text-right">Suggested order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in filteredRestock"
                                :key="item.id"
                                class="restock-row"
                            >
                                <td>
                                    <p class="sku-name">{{ item.name }}</p>
                                    <p class="sku-code">SKU: {{ item.sku }}</p>
                                </td>
                                <td class="text-center">
                                    <span
                                        class="status-badge"
                                        :class="statusBadgeClass(item.status)"
                                    >
                                        <span class="badge-dot"></span>
                                        {{ statusLabel(item.status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="urgency-pips">
                                        <span
                                            v-for="i in 5"
                                            :key="i"
                                            class="pip"
                                            :class="
                                                i <=
                                                Math.min(item.urgencyScore, 5)
                                                    ? item.status ===
                                                      'out_of_stock'
                                                        ? 'pip--red'
                                                        : 'pip--amber'
                                                    : 'pip--empty'
                                            "
                                        ></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <p
                                        class="stock-current"
                                        :style="{ color: stockColor(item) }"
                                    >
                                        {{ item.currentStock }}
                                    </p>
                                    <p class="stock-min">
                                        min {{ item.threshold }}
                                    </p>
                                </td>
                                <td class="text-center">
                                    <span class="committed-num">{{
                                        item.committed
                                    }}</span>
                                </td>
                                <td class="text-right">
                                    <span class="price-val"
                                        >KSH
                                        {{ formatNumber(item.price) }}</span
                                    >
                                </td>
                                <td class="text-right">
                                    <span class="suggest-pill"
                                        >+{{
                                            suggestedReorder(item)
                                        }}
                                        units</span
                                    >
                                    <p class="suggest-cost">
                                        KSH
                                        {{
                                            formatNumber(
                                                suggestedReorder(item) *
                                                    item.price,
                                            )
                                        }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Action Footer -->
        <div class="action-footer">
            <button class="btn-primary">
                <svg
                    class="btn-icon"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                    />
                </svg>
                Add Inventory
            </button>
            <button class="btn-secondary">
                <svg
                    class="btn-icon"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                    />
                </svg>
                View Report
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
    inventory: {
        type: Object,
        required: true,
        default: () => ({
            items: 0,
            skus: 0,
            inStock: 0,
            lowStock: 0,
            outStock: 0,
            stockValue: 0,
            restocking: [],
        }),
    },
});

const chartType = ref("column");
const activeTab = ref("overview");
const restockFilter = ref("all");

const restockFilters = [
    { label: "All", val: "all" },
    { label: "Out of stock", val: "out_of_stock" },
    { label: "Low stock", val: "low_stock" },
];

const stockValue = computed(() => {
    const value = props.inventory.stockValue ?? props.inventory.items * 45.5;
    return Number(value).toLocaleString();
});

const skuTotal = computed(() => props.inventory.skus || 1);

const outStockCount = computed(() =>
    Math.round((props.inventory.outStock / 100) * skuTotal.value),
);
const lowStockSkuCount = computed(() =>
    Math.round((props.inventory.lowStock / 100) * skuTotal.value),
);
const inStockSkuCount = computed(
    () => skuTotal.value - outStockCount.value - lowStockSkuCount.value,
);
const criticalCount = computed(
    () =>
        props.inventory.restocking?.length ||
        outStockCount.value + lowStockSkuCount.value,
);

const distributionItems = computed(() => [
    {
        key: "inStock",
        label: "In Stock",
        pct: props.inventory.inStock,
        count: inStockSkuCount.value,
        color: "#16a34a",
        darkColor: "#14532d",
        bg: "#f0fdf4",
        border: "#bbf7d0",
    },
    {
        key: "lowStock",
        label: "Low Stock",
        pct: props.inventory.lowStock,
        count: lowStockSkuCount.value,
        color: "#d97706",
        darkColor: "#78350f",
        bg: "#fffbeb",
        border: "#fde68a",
    },
    {
        key: "outStock",
        label: "Out of Stock",
        pct: props.inventory.outStock,
        count: outStockCount.value,
        color: "#dc2626",
        darkColor: "#7f1d1d",
        bg: "#fef2f2",
        border: "#fecaca",
    },
]);

const tabs = computed(() => [
    { id: "overview", label: "Overview", badge: null },
    {
        id: "restock",
        label: "Restock Queue",
        badge: criticalCount.value || null,
        badgeCls: outStockCount.value > 0 ? "badge--red" : "badge--amber",
    },
]);

const filteredRestock = computed(() => {
    const list = props.inventory.restocking || [];
    if (restockFilter.value === "all") return list;
    return list.filter((r) => r.status === restockFilter.value);
});

const chartSeries = computed(() => {
    if (chartType.value === "donut") {
        return [
            props.inventory.inStock,
            props.inventory.lowStock,
            props.inventory.outStock,
        ];
    }
    return [
        {
            name: "Stock %",
            data: [
                props.inventory.inStock,
                props.inventory.lowStock,
                props.inventory.outStock,
            ],
        },
    ];
});

const chartOptions = computed(() => {
    const base = {
        chart: {
            fontFamily: "DM Sans, sans-serif",
            toolbar: { show: false },
            animations: { enabled: true, easing: "easeinout", speed: 700 },
        },
        colors: ["#16a34a", "#d97706", "#dc2626"],
        dataLabels: {
            enabled: true,
            style: { fontSize: "11px", fontWeight: 600 },
        },
        legend: {
            show: chartType.value === "donut",
            position: "bottom",
            fontSize: "12px",
            fontFamily: "DM Sans, sans-serif",
        },
        tooltip: {
            theme: "light",
            style: { fontSize: "12px", fontFamily: "DM Sans, sans-serif" },
        },
    };

    if (chartType.value === "donut") {
        return {
            ...base,
            labels: ["In Stock", "Low Stock", "Out of Stock"],
            plotOptions: {
                pie: {
                    donut: {
                        size: "68%",
                        labels: {
                            show: true,
                            value: {
                                show: true,
                                fontSize: "22px",
                                fontWeight: 700,
                                formatter: (v) => v + "%",
                            },
                            total: {
                                show: true,
                                label: "Total Stock",
                                fontSize: "11px",
                                color: "#6B7280",
                                formatter: () =>
                                    props.inventory.items + " units",
                            },
                        },
                    },
                },
            },
        };
    }

    return {
        ...base,
        plotOptions: {
            bar: {
                borderRadius: 6,
                columnWidth: "55%",
                dataLabels: { position: "top" },
            },
        },
        xaxis: {
            categories: ["In Stock", "Low Stock", "Out of Stock"],
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: "#6B7280", fontSize: "12px" } },
        },
        yaxis: {
            labels: {
                style: { colors: "#6B7280", fontSize: "11px" },
                formatter: (v) => v + "%",
            },
        },
        grid: {
            borderColor: "#F3F4F6",
            strokeDashArray: 3,
            xaxis: { lines: { show: false } },
        },
    };
});

function formatNumber(num) {
    if (num == null || isNaN(num)) return "0";
    if (num >= 1e6) return (num / 1e6).toFixed(1) + "M";
    if (num >= 1e3) return (num / 1e3).toFixed(1) + "K";
    return Math.round(num).toString();
}

function calculateItems(type) {
    const pct = props.inventory[type];
    return formatNumber(Math.round((pct / 100) * props.inventory.items));
}

function statusLabel(s) {
    return s === "out_of_stock"
        ? "Out of stock"
        : s === "low_stock"
          ? "Low stock"
          : "OK";
}

function statusBadgeClass(s) {
    return s === "out_of_stock"
        ? "badge--red"
        : s === "low_stock"
          ? "badge--amber"
          : "badge--green";
}

function stockColor(item) {
    if (item.currentStock === 0) return "#dc2626";
    if (item.currentStock <= item.threshold) return "#d97706";
    return "#16a34a";
}

function suggestedReorder(item) {
    const target = item.threshold * 2 + 3;
    return Math.max(item.threshold, target - item.currentStock);
}
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap");

.inv-root {
    font-family: "DM Sans", sans-serif;
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #e5e7eb;
    padding: 20px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
}

/* ─── Alert Banner ─── */
.alert-banner {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fff1f2;
    border: 1px solid #fecdd3;
    border-radius: 12px;
    padding: 10px 14px;
    margin-bottom: 16px;
    font-size: 13px;
    color: #881337;
}
.alert-pulse {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #f43f5e;
    animation: pulse 1.8s ease-in-out infinite;
    flex-shrink: 0;
}
@keyframes pulse {
    0%,
    100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(0.7);
    }
}
.alert-icon {
    width: 16px;
    height: 16px;
    color: #e11d48;
    flex-shrink: 0;
}
.alert-text {
    flex: 1;
}
.alert-action {
    background: none;
    border: 1px solid #fda4af;
    border-radius: 8px;
    color: #be123c;
    font-size: 12px;
    font-weight: 600;
    padding: 3px 10px;
    cursor: pointer;
    white-space: nowrap;
    font-family: "DM Sans", sans-serif;
    transition: background 0.15s;
}
.alert-action:hover {
    background: #ffe4e6;
}

/* ─── KPI Strip ─── */
.kpi-strip {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 16px;
}
.kpi-card {
    background: #f9fafb;
    border: 1px solid #f3f4f6;
    border-radius: 14px;
    padding: 14px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    transition: border-color 0.15s;
}
.kpi-card:hover {
    border-color: #e5e7eb;
}
.kpi-card--danger {
    background: #fff1f2;
    border-color: #fecdd3;
}
.kpi-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.kpi-icon svg {
    width: 17px;
    height: 17px;
}
.kpi-icon--blue {
    background: #eff6ff;
    color: #2563eb;
}
.kpi-icon--emerald {
    background: #ecfdf5;
    color: #059669;
}
.kpi-icon--green {
    background: #f0fdf4;
    color: #16a34a;
}
.kpi-icon--red {
    background: #fff1f2;
    color: #e11d48;
}
.kpi-icon--gray {
    background: #f3f4f6;
    color: #6b7280;
}
.kpi-label {
    font-size: 10px;
    font-weight: 500;
    color: #9ca3af;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin-bottom: 2px;
}
.kpi-value {
    font-size: 20px;
    font-weight: 600;
    color: #111827;
    line-height: 1.1;
}
.kpi-value--green {
    color: #16a34a;
}
.kpi-value--red {
    color: #dc2626;
}
.kpi-sub {
    font-size: 11px;
    color: #9ca3af;
    margin-top: 2px;
}

/* ─── Tab Nav ─── */
.tab-nav {
    display: flex;
    gap: 4px;
    background: #f3f4f6;
    border-radius: 12px;
    padding: 4px;
    margin-bottom: 16px;
}
.tab-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: 9px;
    border: none;
    background: transparent;
    font-family: "DM Sans", sans-serif;
    font-size: 13px;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.15s;
}
.tab-btn:hover {
    color: #374151;
}
.tab-btn--active {
    background: #ffffff;
    color: #111827;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}
.tab-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 99px;
}
.badge--red {
    background: #fee2e2;
    color: #b91c1c;
}
.badge--amber {
    background: #fef3c7;
    color: #92400e;
}

/* ─── Section Card ─── */
.section-card {
    border: 1px solid #f3f4f6;
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 12px;
}
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}
.section-title {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
}
.chart-toggle {
    display: flex;
    gap: 3px;
}
.toggle-btn {
    padding: 4px 11px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: transparent;
    font-family: "DM Sans", sans-serif;
    font-size: 11px;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.15s;
}
.toggle-btn--active {
    background: #eef2ff;
    color: #4f46e5;
    border-color: #c7d2fe;
}

/* ─── Distribution Bars ─── */
.dist-rows {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 16px;
}
.dist-row {
    display: flex;
    align-items: center;
    gap: 10px;
}
.dist-meta {
    display: flex;
    align-items: center;
    gap: 7px;
    width: 86px;
    flex-shrink: 0;
}
.dist-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.dist-name {
    font-size: 12px;
    color: #4b5563;
}
.dist-track {
    flex: 1;
    height: 6px;
    background: #f3f4f6;
    border-radius: 99px;
    overflow: hidden;
}
.dist-fill {
    height: 100%;
    border-radius: 99px;
    transition: width 0.7s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.dist-stats {
    display: flex;
    align-items: center;
    gap: 8px;
}
.dist-pct {
    font-size: 13px;
    font-weight: 600;
    width: 36px;
    text-align: right;
}
.dist-count {
    font-size: 11px;
    color: #9ca3af;
    width: 46px;
}
.chart-wrap {
    margin-top: 4px;
}

/* ─── Status Cards Grid ─── */
.status-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
}
.status-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 12px;
}
.status-left {
    display: flex;
    align-items: center;
    gap: 10px;
}
.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--accent);
    flex-shrink: 0;
}
.status-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--darkColor, #111827);
}
.status-items {
    font-size: 11px;
    color: #6b7280;
    margin-top: 1px;
}
.status-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}
.status-pct {
    font-size: 18px;
    font-weight: 700;
}
.status-bar {
    width: 56px;
    height: 5px;
    background: rgba(0, 0, 0, 0.08);
    border-radius: 99px;
    overflow: hidden;
}
.status-bar-fill {
    height: 100%;
    border-radius: 99px;
    transition: width 0.6s ease;
}

/* ─── Restock Table ─── */
.filter-row {
    display: flex;
    gap: 6px;
    margin-bottom: 12px;
}
.filter-btn {
    padding: 5px 13px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: transparent;
    font-family: "DM Sans", sans-serif;
    font-size: 12px;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.15s;
}
.filter-btn--active {
    background: #1e1b4b;
    color: #fff;
    border-color: #1e1b4b;
}
.restock-wrap {
    overflow-x: auto;
}
.restock-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.restock-table th {
    text-align: left;
    padding: 8px 10px 8px;
    font-size: 10px;
    font-weight: 600;
    color: #9ca3af;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    border-bottom: 1px solid #f3f4f6;
}
.restock-table td {
    padding: 10px 10px;
    border-bottom: 1px solid #f9fafb;
    vertical-align: middle;
}
.restock-row:hover td {
    background: #f9fafb;
}
.text-center {
    text-align: center;
}
.text-right {
    text-align: right;
}
.sku-name {
    font-weight: 600;
    color: #111827;
    font-size: 12px;
}
.sku-code {
    font-size: 10px;
    color: #9ca3af;
    margin-top: 2px;
}
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 99px;
}
.status-badge .badge-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: currentColor;
}
.badge--red {
    background: #fee2e2;
    color: #b91c1c;
}
.badge--amber {
    background: #fef3c7;
    color: #92400e;
}
.badge--green {
    background: #dcfce7;
    color: #15803d;
}
.urgency-pips {
    display: flex;
    gap: 3px;
    justify-content: center;
}
.pip {
    width: 7px;
    height: 15px;
    border-radius: 3px;
}
.pip--red {
    background: #ef4444;
}
.pip--amber {
    background: #f59e0b;
}
.pip--empty {
    background: #f3f4f6;
}
.stock-current {
    font-size: 14px;
    font-weight: 700;
    line-height: 1;
}
.stock-min {
    font-size: 10px;
    color: #9ca3af;
    margin-top: 1px;
}
.committed-num {
    font-size: 13px;
    color: #6b7280;
}
.price-val {
    font-size: 12px;
    color: #374151;
}
.suggest-pill {
    display: inline-block;
    background: #eef2ff;
    color: #4338ca;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 99px;
}
.suggest-cost {
    font-size: 10px;
    color: #9ca3af;
    margin-top: 2px;
}

/* ─── Empty State ─── */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    color: #9ca3af;
    text-align: center;
}
.empty-state svg {
    width: 40px;
    height: 40px;
    margin-bottom: 12px;
}
.empty-state p {
    font-size: 14px;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 4px;
}
.empty-state span {
    font-size: 12px;
}

/* ─── Action Footer ─── */
.action-footer {
    display: flex;
    gap: 10px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #f3f4f6;
}
.btn-primary,
.btn-secondary {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 10px;
    font-family: "DM Sans", sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
    border: none;
}
.btn-primary {
    background: #4f46e5;
    color: #fff;
}
.btn-primary:hover {
    background: #4338ca;
    transform: translateY(-1px);
}
.btn-secondary {
    background: #f9fafb;
    color: #374151;
    border: 1px solid #e5e7eb;
}
.btn-secondary:hover {
    background: #f3f4f6;
    transform: translateY(-1px);
}
.btn-icon {
    width: 15px;
    height: 15px;
}

/* ─── Slide transition ─── */
.slide-down-enter-active {
    transition: all 0.3s ease;
}
.slide-down-leave-active {
    transition: all 0.2s ease;
}
.slide-down-enter-from {
    opacity: 0;
    transform: translateY(-8px);
}
.slide-down-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}

/* ─── Mobile ─── */
@media (max-width: 640px) {
    .kpi-strip {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
