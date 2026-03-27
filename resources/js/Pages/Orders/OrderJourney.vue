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

                        <!-- Changes block -->
                        <div v-if="event.type === 'order_updated'" class="mt-1">
                            <div
                                v-if="
                                    !getMeaningfulChanges(event.changes).length
                                "
                            >
                                <em class="text-grey text-caption"
                                    >No significant changes</em
                                >
                            </div>

                            <template v-else>
                                <div
                                    v-for="(
                                        change, key
                                    ) in getMeaningfulChangesMap(event.changes)"
                                    :key="key"
                                    class="mb-2"
                                >
                                    <!-- ORDER ITEMS -->
                                    <div v-if="key === 'order_items'">
                                        <div
                                            class="text-caption font-weight-bold mb-1"
                                        >
                                            Items:
                                        </div>
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
                                            <span class="text-body-2">{{
                                                item.sku
                                            }}</span>
                                            <span
                                                v-if="item.type === 'updated'"
                                                class="text-caption"
                                            >
                                                qty:
                                                <s class="text-red">{{
                                                    item.oldQty
                                                }}</s>
                                                →
                                                <span class="text-green">{{
                                                    item.newQty
                                                }}</span>
                                            </span>
                                            <span
                                                v-else-if="
                                                    item.type ===
                                                    'price_changed'
                                                "
                                                class="text-caption"
                                            >
                                                price:
                                                <s class="text-red">{{
                                                    item.oldPrice
                                                }}</s>
                                                →
                                                <span class="text-green">{{
                                                    item.newPrice
                                                }}</span>
                                            </span>
                                            <span
                                                v-else-if="
                                                    item.type === 'added'
                                                "
                                                class="text-caption text-green"
                                            >
                                                qty: {{ item.newQty }}
                                            </span>
                                            <span
                                                v-else-if="
                                                    item.type === 'removed'
                                                "
                                                class="text-caption text-red"
                                            >
                                                qty: {{ item.oldQty }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- DEFAULT FIELD CHANGES -->
                                    <div
                                        v-else
                                        class="d-flex align-center gap-2 flex-wrap"
                                    >
                                        <span
                                            class="text-caption font-weight-bold"
                                            >{{ formatKey(key) }}:</span
                                        >
                                        <span
                                            class="text-caption text-red text-decoration-line-through"
                                        >
                                            {{ formatValue(key, change.old) }}
                                        </span>
                                        <span class="text-caption">→</span>
                                        <span class="text-caption text-green">
                                            {{ formatValue(key, change.new) }}
                                        </span>
                                    </div>
                                </div>
                            </template>
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
        events.value = (res.data.data || []).map((event) => ({
            ...event,
            // Normalize: changes must always be a plain object, never an array
            changes: Array.isArray(event.changes) ? {} : (event.changes ?? {}),
        }));
    } catch (err) {
        console.error("Failed to fetch order events:", err);
        events.value = [];
    } finally {
        loading.value = false;
    }
};

// Watch orderNo — fires immediately if set, or when selectedOrder changes after mount
watch(
    orderNo,
    (val) => {
        if (val) fetchEvents();
    },
    { immediate: true },
);

// ─── Formatters ──────────────────────────────────────────────────────────────

const formatType = (type) => type.replaceAll("_", " ").toUpperCase();

const formatDate = (date) => new Date(date).toLocaleString();

const formatKey = (key) =>
    key.replaceAll("_", " ").replace(/\b\w/g, (c) => c.toUpperCase());

const formatValue = (key, val) => {
    if (val === null || val === undefined) return "—";
    if (key.includes("date")) return new Date(val).toLocaleString();
    return val;
};

// ─── Semantic equality ───────────────────────────────────────────────────────

/**
 * True if two values represent the same thing despite type/format differences.
 * Handles: "6290.00" == 6290, "2026-03-27T10:59" == "2026-03-27 10:59:00", etc.
 */
const semanticallyEqual = (a, b) => {
    if (a === b) return true;
    if (a === null || b === null || a === undefined || b === undefined)
        return false;
    // Numeric comparison: "6290.00" === 6290
    const na = Number(a),
        nb = Number(b);
    if (!isNaN(na) && !isNaN(nb) && na === nb) return true;
    // Date comparison: different ISO formats for the same moment
    const da = new Date(a),
        db = new Date(b);
    if (!isNaN(da) && !isNaN(db) && da.getTime() === db.getTime()) return true;
    return false;
};

// ─── Meaningful change filtering ─────────────────────────────────────────────

/**
 * Returns a filtered changes map, omitting:
 * - Fields where old and new are semantically equal (type coercion noise)
 * - order_items entries where the item diff is empty (backend re-save noise)
 */
const getMeaningfulChangesMap = (changes) => {
    const result = {};
    for (const [key, change] of Object.entries(changes)) {
        if (key === "order_items") {
            if (getItemDiff(change.old, change.new).length > 0) {
                result[key] = change;
            }
        } else if (!semanticallyEqual(change.old, change.new)) {
            result[key] = change;
        }
    }
    return result;
};

const getMeaningfulChanges = (changes) =>
    Object.keys(getMeaningfulChangesMap(changes));

// ─── Colors ──────────────────────────────────────────────────────────────────

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
        price_changed: "blue",
    })[type] ?? "grey";

// ─── Item Diff ────────────────────────────────────────────────────────────────

/**
 * Diffs old vs new item arrays.
 * Reports only MEANINGFUL changes:
 *   added         — SKU appears in new but not old
 *   removed       — SKU appears in old but not new
 *   updated       — quantity changed
 *   price_changed — unit_price changed
 *
 * Silently ignores items that only differ by DB id / timestamps (backend re-save noise).
 */
const getItemDiff = (oldItems = [], newItems = []) => {
    const safeOld = oldItems ?? [];
    const safeNew = newItems ?? [];

    const oldMap = Object.fromEntries(safeOld.map((i) => [i.sku, i]));
    const newMap = Object.fromEntries(safeNew.map((i) => [i.sku, i]));

    const result = [];

    for (const sku in newMap) {
        if (!oldMap[sku]) {
            result.push({ type: "added", sku, newQty: newMap[sku].quantity });
        } else {
            const o = oldMap[sku];
            const n = newMap[sku];
            if (o.quantity !== n.quantity) {
                result.push({
                    type: "updated",
                    sku,
                    oldQty: o.quantity,
                    newQty: n.quantity,
                });
            } else if (!semanticallyEqual(o.unit_price, n.unit_price)) {
                result.push({
                    type: "price_changed",
                    sku,
                    oldPrice: o.unit_price,
                    newPrice: n.unit_price,
                });
            }
            // ID/timestamp-only diff = backend re-save noise → skip
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
