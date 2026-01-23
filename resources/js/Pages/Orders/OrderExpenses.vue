<template>
    <v-dialog v-model="dialog" max-width="900">
        <v-card rounded="xl">
            <!-- Header -->
            <v-card-title class="d-flex justify-space-between align-center">
                <span class="text-h6">
                    Order Expenses — #{{ order?.order_no }}
                </span>
                <v-btn icon @click="dialog = false">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-card-title>

            <v-divider />

            <!-- Expenses Table -->
            <v-card-text>
                <v-table density="comfortable">
                    <thead>
                        <tr>
                            <th>Expense Type</th>
                            <th>Party</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Invoice</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="expense in expenses" :key="expense.id">
                            <td>
                                <v-chip size="small" variant="tonal">
                                    {{ formatType(expense.expense_type) }}
                                </v-chip>
                            </td>

                            <td>
                                {{ expense.party_name || expense.party_type }}
                            </td>

                            <td class="font-weight-bold">
                                KES {{ formatMoney(expense.amount) }}
                            </td>

                            <td>
                                <v-chip
                                    size="small"
                                    :color="statusColor(expense.status)"
                                    variant="flat"
                                >
                                    {{ expense.status }}
                                </v-chip>
                            </td>

                            <td>
                                <span v-if="expense.invoice_no">
                                    {{ expense.invoice_no }}
                                </span>
                                <span v-else class="text-grey"> — </span>
                            </td>

                            <!-- actions -->

                            <td>
                                <div v-if="true" class="d-flex align-center">
                                    <v-btn
                                        v-if="expense.invoice_url"
                                        :href="expense.invoice_url"
                                        target="_blank"
                                        icon
                                        size="small"
                                        color="primary"
                                    >
                                        <v-icon
                                            >mdi-file-download-outline</v-icon
                                        >
                                    </v-btn>

                                    <v-btn
                                        icon
                                        size="small"
                                        color="primary"
                                        @click="$emit('edit-expense', expense)"
                                        title="Edit expense"
                                    >
                                        <v-icon>mdi-pencil</v-icon>
                                    </v-btn>

                                    <v-btn
                                        icon
                                        size="small"
                                        color="error"
                                        @click="
                                            $emit('remove-expense', expense)
                                        "
                                        title="Remove expense"
                                    >
                                        <v-icon>mdi-delete</v-icon>
                                    </v-btn>
                                </div>
                                <span v-else class="text-grey"> — </span>
                            </td>
                        </tr>

                        <tr v-if="!expenses.length">
                            <td colspan="5" class="text-center text-grey py-6">
                                No expenses recorded for this order
                            </td>
                        </tr>
                    </tbody>
                </v-table>

                <v-btn
                    color="primary"
                    variant="text"
                    prepend-icon="mdi-plus"
                    @click="addOrderItem"
                    class="mt-2"
                >
                    Add Item
                </v-btn>
            </v-card-text>

            <v-divider />

            <!-- Footer Totals -->
            <v-card-actions class="justify-space-between px-6 py-4">
                <div class="text-subtitle-1">
                    Total Expenses:
                    <strong>KES {{ formatMoney(totalExpenses) }}</strong>
                </div>

                <v-btn color="primary" variant="flat" @click="dialog = false">
                    Close
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { computed } from "vue";

import { ref } from "vue";

const props = defineProps({
    modelValue: Boolean,
    order: Object,
    expenses: {
        type: Array,
        default: () => [],
    },
});



const addOrderItem = () => {
    // orderEdit.value.order_items.push({
    //     sku: "",
    //     quantity: 1,
    //     unit_price: 0.0,
    //     editable: true,
    // });
    // updateTotals();
};

defineEmits(["update:modelValue"]);

const dialog = ref(false);

const open = (order) => {
    dialog.value = true;
};

const totalExpenses = computed(() =>
    props.expenses.reduce((sum, e) => sum + Number(e.amount), 0),
);

const formatMoney = (amount) =>
    Number(amount).toLocaleString("en-KE", {
        minimumFractionDigits: 2,
    });

const formatType = (type) => type.replaceAll("_", " ").toUpperCase();

const statusColor = (status) => {
    if (status === "paid") return "green";
    if (status === "invoiced") return "blue";
    if (status === "pending") return "orange";
    return "grey";
};

defineExpose({
    open,
});
</script>
