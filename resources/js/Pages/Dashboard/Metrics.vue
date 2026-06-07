<template>
    <AppLayout>
        <div class="p-6">
            <!-- Grid with 5 rows and 2 columns -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Component 1 -->
                <div class="card">
                    <OrderStats :stats="orderStats" />
                </div>

                <div class="card">
                    <OrderAnalytics
                        :ordersGivenSummary="ordersGivenSummary"
                        :isLoading="isLoading"
                        @filter-change="handleFilterChange"
                    />
                </div>

                <!-- Confirmation Summary -->
                <div class="card">
                    <ConfirmationSummary :data="confirmationSummary" />
                </div>

                <!-- Delivery Summary -->
                <div class="card">
                    <DeliverySummary :data="deliverySummary" />
                </div>

                <!-- Component 3 -->
                <div class="card">
                    <InventoryStats :inventory="inventory" />
                </div>

                <!-- Component 4 -->
                <div class="card">
                    <StatusOverview :status="statusData" />
                </div>

                <!-- Component 5 -->
                <!-- <div class="card"><TopAgents topAgents:="topAgents" /></div> -->
                <TopAgents :topAgents="topAgents" />

                <!-- Component 6 -->
                <div class="card">
                    <DeliveryRate :deliveryRate="deliveryRate" />
                </div>

                <!-- Component 7 -->
                <div class="card">
                    <TopProducts :topProducts="topProducts" />
                </div>

                <!-- Component 8 -->
                <div class="card">
                    <TopSellers :topSellers="topSellers" />
                </div>

                <!-- Component 9 -->
                <div class="card">
                    <WalletEarnings :wallet="wallet" />
                </div>

                <!-- Component 10 -->
                <div
                    class="card flex items-center justify-center text-gray-500"
                >
                    Placeholder / Future Component
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";

import OrderStats from "@/Pages/Dashboard/components/OrderStats.vue";
import OrderAnalytics from "@/Pages/Dashboard/components/OrderAnalytics.vue";
import InventoryStats from "@/Pages/Dashboard/components/InventoryStats.vue";
import StatusOverview from "@/Pages/Dashboard/components/StatusOverview.vue";
import WalletEarnings from "@/Pages/Dashboard/components/WalletEarnings.vue";

import DeliveryRate from "@/Pages/Dashboard/components/DeliveryRate.vue";
import TopProducts from "@/Pages/Dashboard/components/TopProducts.vue";
import TopSellers from "@/Pages/Dashboard/components/TopSellers.vue";
import TopAgents from "@/Pages/Dashboard/components/TopAgents.vue";

import ConfirmationSummary from "@/Pages/Dashboard/components/ConfirmationSummary.vue";
import DeliverySummary from "@/Pages/Dashboard/components/DeliverySummary.vue";

const orderStats = ref({});
const orderChart = ref([]);
const inventory = ref({});
const statusData = ref([]);
const wallet = ref({});
const topAgents = ref([]);
const topProducts = ref([]);
const topSellers = ref([]);
const deliveryRate = ref({});

const ordersGivenSummary = ref({ total_orders: 0, orders_per_day: [] });
const confirmationSummary = ref({});
const deliverySummary = ref({});

onMounted(async () => {
    const { data } = await axios.get("/api/v1/dashboard");

    orderStats.value = data.orderStats;
    orderChart.value = data.orderChart;
    inventory.value = data.inventory;
    statusData.value = Object.values(data.statusData); // for chart
    wallet.value = data.wallet;
    topAgents.value = data.topAgents;
    topProducts.value = data.topProducts.map((product) => ({
        ...product,
        sales: Number(product.sales),
    }));
    topSellers.value = data.topSellers;
    deliveryRate.value = data.deliveryRate;
    ordersGivenSummary.value = data.ordersGivenSummary;
    confirmationSummary.value = data.confirmationSummary;
    deliverySummary.value = data.deliverySummary;
});
</script>
