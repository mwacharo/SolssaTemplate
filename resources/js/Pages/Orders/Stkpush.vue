<template>
    <v-dialog v-model="dialog" max-width="500" persistent>
        <v-card>
            <!-- Header -->
            <v-card-title
                class="bg-success text-white d-flex align-center pa-4"
            >
                <v-icon size="large" class="mr-3">mdi-cellphone-check</v-icon>
                <div>
                    <div class="text-h6">M-PESA Payment</div>
                    <div class="text-caption opacity-90">
                        Send STK Push Request
                    </div>
                </div>
                <v-spacer></v-spacer>
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    @click="closeDialog"
                ></v-btn>
            </v-card-title>

            <v-divider></v-divider>

            <!-- Form Content -->
            <v-card-text class="pa-6">
                <v-form ref="formRef" @submit.prevent="handleSubmit">
                    <v-row dense>
                        <!-- Order Number -->
                        <v-col cols="12">
                            <v-text-field
                                v-model="formData.orderNumber"
                                label="Order Number"
                                prepend-inner-icon="mdi-package-variant"
                                placeholder="e.g., ORD-2024-001"
                                variant="outlined"
                                density="comfortable"
                                :rules="[rules.required]"
                                required
                            ></v-text-field>
                        </v-col>

                        <!-- Order Phone (Optional) -->
                        <v-col cols="12">
                            <v-text-field
                                v-model="formData.orderPhone"
                                label="Order Phone Number (Optional)"
                                prepend-inner-icon="mdi-phone"
                                placeholder="e.g., 0712345678"
                                variant="outlined"
                                density="comfortable"
                            ></v-text-field>
                        </v-col>

                        <!-- Payment Phone -->
                        <v-col cols="12">
                            <v-text-field
                                v-model="formData.paymentPhone"
                                label="Payment Phone Number"
                                prepend-inner-icon="mdi-cellphone"
                                placeholder="e.g., 0712345678 or 254712345678"
                                variant="outlined"
                                density="comfortable"
                                hint="The number that will receive the payment prompt"
                                persistent-hint
                                required
                            ></v-text-field>
                        </v-col>

                        <!-- Amount -->
                        <v-col cols="12">
                            <v-text-field
                                v-model="formData.amount"
                                label="Amount (KES)"
                                placeholder="e.g., 1500"
                                type="number"
                                variant="outlined"
                                density="comfortable"
                                :rules="[rules.required, rules.amount]"
                                required
                            ></v-text-field>
                        </v-col>

                        <!-- Description -->
                        <v-col cols="12">
                            <v-textarea
                                v-model="formData.description"
                                label="Description (Optional)"
                                placeholder="e.g., Courier delivery payment"
                                variant="outlined"
                                density="comfortable"
                                rows="2"
                                auto-grow
                            ></v-textarea>
                        </v-col>

                        <!-- Status Alert -->
                        <v-col cols="12" v-if="status">
                            <v-alert
                                :type="alertType"
                                :icon="alertIcon"
                                variant="tonal"
                                density="comfortable"
                            >
                                {{ message }}
                            </v-alert>
                        </v-col>
                    </v-row>
                </v-form>
            </v-card-text>

            <v-divider></v-divider>

            <!-- Footer Actions -->
            <v-card-actions class="pa-4">
                <v-spacer></v-spacer>
                <v-btn
                    variant="outlined"
                    @click="closeDialog"
                    :disabled="loading"
                >
                    Cancel
                </v-btn>
                <v-btn
                    color="success"
                    variant="flat"
                    @click="handleSubmit"
                    :loading="loading"
                    :disabled="loading"
                    prepend-icon="mdi-send"
                >
                    Send Request
                </v-btn>
            </v-card-actions>

            <!-- Info Footer -->
            <v-card-text class="bg-grey-lighten-4 text-center pa-3">
                <v-icon size="small" color="info" class="mr-1"
                    >mdi-information</v-icon
                >
                <span class="text-caption text-medium-emphasis">
                    The customer will receive an M-PESA prompt on their phone
                </span>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { ref, reactive, computed } from "vue";
import { useStkpushStore } from "@/stores/stkpushStore";

// Props
const props = defineProps({
    order: {
        type: Object,
        default: null,
    },
});

// Emits
const emit = defineEmits(["stkPushInitiated", "success", "error"]);

// Store
const store = useStkpushStore();

// State
const dialog = ref(false);
const formRef = ref(null);
const status = ref(null);
const message = ref("");
const loading = ref(false);

const formData = reactive({
    orderNumber: "",
    orderPhone: "",
    paymentPhone: "",
    amount: "",
    description: "",
});

// Validation Rules
const rules = {
    required: (v) => !!v || "This field is required",
    phone: (v) => {
        if (!v) return true;
        const phone = formatPhoneNumber(v);
        return (
            (phone.length === 12 && phone.startsWith("254")) ||
            "Invalid phone number format"
        );
    },
    amount: (v) => {
        if (!v) return true;
        return parseFloat(v) > 0 || "Amount must be greater than 0";
    },
};

// Computed
const alertType = computed(() => {
    if (status.value === "success") return "success";
    if (status.value === "error") return "error";
    if (status.value === "loading") return "info";
    return "info";
});

const alertIcon = computed(() => {
    if (status.value === "success") return "mdi-check-circle";
    if (status.value === "error") return "mdi-alert-circle";
    if (status.value === "loading") return "mdi-loading mdi-spin";
    return "mdi-information";
});

// Methods
const formatPhoneNumber = (phone) => {
    let cleaned = phone.replace(/\D/g, "");

    if (cleaned.startsWith("254")) {
        return cleaned;
    }
    if (cleaned.startsWith("0")) {
        return "254" + cleaned.substring(1);
    }
    if (cleaned.length === 9) {
        return "254" + cleaned;
    }
    return cleaned;
};

const handleSubmit = async () => {
    // Validate form
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    status.value = "loading";
    loading.value = true;
    message.value = "Sending STK push request...";

    try {
        // Prepare data
        const stkData = {
            orderId: formData.orderNumber,
            phone: formatPhoneNumber(
                formData.paymentPhone || formData.orderPhone || ""
            ),
            amount: parseFloat(formData.amount),
            description: formData.description || "Order payment",
        };

        // Emit event
        emit("stkPushInitiated", stkData);

        // Call store method (uncomment when ready)
        await store.initiateStkPush(stkData);

        // Simulate API call (remove this in production)
        // await new Promise((resolve) => setTimeout(resolve, 2000));

        // Success
        status.value = "success";
        message.value = `STK push sent successfully to ${formatPhoneNumber(
            formData.paymentPhone
        )}. Customer will receive payment prompt shortly.`;

        emit("success", stkData);

        // Auto close after success
        setTimeout(() => {
            closeDialog();
        }, 3000);
    } catch (error) {
        status.value = "error";
        message.value =
            error.message ||
            "Failed to send STK push. Please verify the phone number and try again.";
        emit("error", error);
    } finally {
        loading.value = false;
    }
};

const resetForm = () => {
    formData.orderNumber = "";
    formData.orderPhone = "";
    formData.paymentPhone = "";
    formData.amount = "";
    formData.description = "";
    status.value = null;
    message.value = "";
    loading.value = false;
    formRef.value?.reset();
    formRef.value?.resetValidation();
};

const openDialog = (orderData = null) => {
    if (orderData) {
        formData.orderNumber = orderData.order_no || orderData.id || "";
        formData.orderPhone =
            orderData.phone || orderData.customer?.phone || "";
        formData.amount = orderData.amount || orderData.total_price || "";
    } else if (props.order) {
        formData.orderNumber = props.order.order_no || props.order.id || "";
        formData.orderPhone =
            props.order.phone || props.order.customer?.phone || "";
        formData.amount = props.order.amount || props.order.total_price || "";
    }
    dialog.value = true;
};

const closeDialog = () => {
    dialog.value = false;
    setTimeout(resetForm, 300); // Delay reset for smooth animation
};

// Expose methods
defineExpose({
    openDialog,
    closeDialog,
});
</script>

<style scoped>
/* Add any custom styles here */
.v-card-title {
    position: sticky;
    top: 0;
    z-index: 1;
}

/* Vuetify doesn't need these as it handles animations */
</style>
