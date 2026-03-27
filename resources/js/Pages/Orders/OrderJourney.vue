<template>
    <v-dialog v-model="dialog2" max-width="900">
        <v-card>
            <!-- Header -->
            <v-card-title class="d-flex justify-space-between align-center">
                <span>Order Journey - #{{ orderNo }}</span>
                <v-btn icon="mdi-close" variant="text" @click="closeDialog" />
            </v-card-title>

            <v-card-subtitle>
                Track all updates and actions on this order
            </v-card-subtitle>

            <v-divider />

            <!-- Loading -->
            <v-card-text
                v-if="loading"
                class="d-flex justify-center align-center py-8"
            >
                <v-progress-circular indeterminate color="primary" />
            </v-card-text>

            <!-- Empty -->
            <v-card-text
                v-else-if="!events.length"
                class="text-center text-grey py-8"
            >
                No events found for this order.
            </v-card-text>

            <!-- Timeline -->
            <v-card-text v-else style="max-height: 70vh; overflow-y: auto">
                <v-timeline density="compact" align="start">
                    <v-timeline-item
                        v-for="(event, index) in events"
                        :key="index"
                        :dot-color="getColor(event.type)"
                        size="small"
                    >
                        <!-- Header -->
                        <div class="d-flex justify-space-between align-center">
                            <strong>{{ formatType(event.type) }}</strong>
                            <small class="text-grey">{{
                                formatDate(event.time)
                            }}</small>
                        </div>

                        <!-- Actor -->
                        <div class="text-caption mb-1">
                            By: {{ event.actor }}
                        </div>

                        <!-- Changes -->
                        <div v-if="event.type === 'order_updated'">
                            <div v-if="!Object.keys(event.changes).length">
                                <em class="text-grey">No changes recorded</em>
                            </div>

                            <div
                                v-for="(change, key) in event.changes"
                                :key="key"
                                class="mb-2"
                            >
                                <!-- ORDER ITEMS -->
                                <div v-if="key === 'order_items'">
                                    <div
                                        v-for="(item, i) in getItemDiff(
                                            change.old,
                                            change.new,
                                        )"
                                        :key="i"
                                        class="d-flex align-center gap-2 mb-1"
                                    >
                                        <v-chip
                                            size="x-small"
                                            :color="badgeColor(item.type)"
                                            variant="tonal"
                                        >
                                            {{ item.type }}
                                        </v-chip>

                                        <span>{{ item.sku }}</span>

                                        <span v-if="item.type === 'updated'">
                                            ({{ item.oldQty }} →
                                            {{ item.newQty }})
                                        </span>

                                        <span v-if="item.type === 'added'">
                                            (qty {{ item.newQty }})
                                        </span>

                                        <span v-if="item.type === 'removed'">
                                            (qty {{ item.oldQty }})
                                        </span>
                                    </div>
                                </div>

                                <!-- DEFAULT FIELD CHANGES -->
                                <div v-else class="d-flex align-center gap-2">
                                    <strong>{{ key }}:</strong>

                                    <span
                                        v-if="
                                            change.old !== null &&
                                            change.old !== undefined
                                        "
                                        class="text-red text-decoration-line-through"
                                    >
                                        {{ change.old }}
                                    </span>

                                    <span
                                        v-if="
                                            change.old !== null &&
                                            change.old !== undefined
                                        "
                                        >→</span
                                    >

                                    <span class="text-green">
                                        {{ change.new ?? "added" }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </v-timeline-item>
                </v-timeline>
            </v-card-text>

            <v-divider />

            <!-- Footer -->
            <v-card-actions>
                <v-spacer />
                <v-btn variant="outlined" @click="closeDialog">Close</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import axios from "axios";
import { useOrderStore } from "@/stores/orderStore";

const orderStore = useOrderStore();

const dialog2 = computed({
    get: () => orderStore.dialog2,
    set: (val) => (orderStore.dialog2 = val),
});

const orderNo = computed(() => orderStore.selectedOrder?.id);

const events = ref([]);
const loading = ref(false);

const closeDialog = () => {
    dialog2.value = false;
    orderStore.selectedOrder = null;
};

const fetchEvents = async () => {
    if (!orderNo.value) return;

    loading.value = true;
    try {
        const res = await axios.get(`/api/v1/orders/${orderNo.value}/events`);

        // Normalize: ensure changes is always a plain object, never an array
        events.value = (res.data.data || []).map((event) => ({
            ...event,
            changes: Array.isArray(event.changes) ? {} : (event.changes ?? {}),
        }));
    } catch (err) {
        console.error("Failed to fetch order events:", err);
        events.value = [];
    } finally {
        loading.value = false;
    }
};

// Watch orderNo so we fetch even if selectedOrder is set after mount
watch(
    orderNo,
    (val) => {
        if (val) fetchEvents();
    },
    { immediate: true },
);

const formatType = (type) => type.replaceAll("_", " ").toUpperCase();

const formatDate = (date) => new Date(date).toLocaleString();

const getColor = (type) =>
    ({
        order_created: "green",
        order_updated: "orange",
        dispatched: "blue",
        delivered: "green",
        cancelled: "red",
    })[type] ?? "grey";

const badgeColor = (type) =>
    ({
        added: "green",
        removed: "red",
        updated: "orange",
    })[type];

const getItemDiff = (oldItems = [], newItems = []) => {
    const oldMap = Object.fromEntries((oldItems ?? []).map((i) => [i.sku, i]));
    const newMap = Object.fromEntries((newItems ?? []).map((i) => [i.sku, i]));

    const result = [];

    for (const sku in newMap) {
        if (!oldMap[sku]) {
            result.push({ type: "added", sku, newQty: newMap[sku].quantity });
        } else if (oldMap[sku].quantity !== newMap[sku].quantity) {
            result.push({
                type: "updated",
                sku,
                oldQty: oldMap[sku].quantity,
                newQty: newMap[sku].quantity,
            });
        }
    }

    for (const sku in oldMap) {
        if (!newMap[sku]) {
            result.push({ type: "removed", sku, oldQty: oldMap[sku].quantity });
        }
    }

    return result;
};
</script>
