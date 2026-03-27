<template>
    <v-dialog v-model="dialog1" max-width="900" scrollable>
        <v-card>
            <v-btn icon="mdi-close" variant="text" @click="closeDialog" />

            <div class="p-4">
                <h2 class="text-xl font-bold mb-4">Order History</h2>

                <div v-if="loading" class="text-gray-500">
                    Loading history...
                </div>
                <div v-else-if="error" class="text-red-500">{{ error }}</div>
                <div v-else>
                    <ul class="space-y-4">
                        <li
                            v-for="log in sortedLogs"
                            :key="log.time"
                            class="border-l-4 border-blue-500 pl-4"
                        >
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-semibold">{{
                                    log.title
                                }}</span>
                                <span class="text-sm text-gray-400">{{
                                    formatDate(log.time)
                                }}</span>
                            </div>
                            <div class="text-gray-600 mb-1">
                                <strong>Actor:</strong> {{ log.actor }} |
                                <strong>Type:</strong> {{ log.type }}
                            </div>
                            <div class="bg-gray-50 p-2 rounded">
                                <ul>
                                    <li
                                        v-for="(change, key) in log.changes"
                                        :key="key"
                                    >
                                        <span class="font-medium"
                                            >{{ key }}:</span
                                        >
                                        <span v-if="isObject(change)">
                                            <span class="text-red-500">{{
                                                change.old
                                            }}</span>
                                            →
                                            <span class="text-green-500">{{
                                                change.new
                                            }}</span>
                                        </span>
                                        <span v-else>
                                            {{ change }}
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";

import { useOrderStore } from "@/stores/orderStore";

const orderStore = useOrderStore();

const dialog1 = computed({
    get: () => orderStore.dialog1,
    set: (val) => (orderStore.dialog1 = val),
});

const closeDialog = () => {
    dialog1.value = false;
    orderStore.selectedOrder = null; // Clear selected order when closing
};

const loading = computed(() => orderStore.loading.orders);
const error = computed(() => orderStore.error);
const history = computed(() => orderStore.selectedOrder || []);

// Fetch order history from API
const fetchHistory = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get(`/api/v1/orders/${orderId}/history`);
        if (response.data.success) {
            history.value = response.data.data;
        } else {
            error.value = "Failed to fetch order history";
        }
    } catch (err) {
        error.value = err.message || "Error fetching history";
    } finally {
        loading.value = false;
    }
};

// Sort logs by time descending
const sortedLogs = computed(() => {
    return [...history.value].sort(
        (a, b) => new Date(b.time) - new Date(a.time),
    );
});

// Helper to check if a value is an object
const isObject = (val) => val && typeof val === "object" && !Array.isArray(val);

// Format date nicely
const formatDate = (date) => {
    return new Date(date).toLocaleString();
};

onMounted(fetchHistory);
</script>

<style scoped>
/* optional styling */
</style>
