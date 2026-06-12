<script setup>
import { ref, computed, onMounted, watch } from "vue";

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
    order_no: {
        type: String,
        required: true,
    },
});

// ─── State ────────────────────────────────────────────────────────────────────
const order = ref(null);
const loadingOrder = ref(true);
const orderError = ref(null);
const isSubmitting = ref(false);
const showSuccess = ref(false);

const formData = ref({
    orderNo: props.order_no,
    phone: "",
    altPhone: "",
    address: "",
    city: "",
    delivery_date: "",
    timeSlot: "",
    landmark: "",
    requestCallback: false,
    notes: "",
});

const errors = ref({
    phone: "",
    address: "",
    city: "",
    delivery_date: "",
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
const normalizeKenyanPhone = (raw) => {
    if (!raw) return "";
    const digits = String(raw).replace(/\D/g, "");
    if (digits.startsWith("254") && digits.length === 12)
        return "0" + digits.slice(3);
    if (digits.startsWith("0") && digits.length === 10) return digits;
    return raw;
};

// ─── Populate form from fetched order ────────────────────────────────────────
const populateFromOrder = (o) => {
    if (!o) return;

    formData.value.orderNo = o.order_no || props.order_no;

    if (!formData.value.phone && o.customer?.phone) {
        formData.value.phone = normalizeKenyanPhone(o.customer.phone);
    }
    if (!formData.value.altPhone && o.customer?.alt_phone) {
        formData.value.altPhone = normalizeKenyanPhone(o.customer.alt_phone);
    }

    if (!formData.value.address) {
        const addr =
            o.shipping_address ||
            o.pickup_address ||
            (Array.isArray(o.addresses) && o.addresses.length
                ? o.addresses[0]
                : null);

        if (addr && typeof addr === "object") {
            formData.value.address =
                addr.full_address ||
                addr.address ||
                `${addr.street || ""} ${addr.area || ""}`.trim() ||
                "";
        } else {
            formData.value.address = addr || "";
        }
    }

    if (!formData.value.city) {
        formData.value.city =
            o.shipping_address?.city ||
            o.addresses?.[0]?.city ||
            o.warehouse?.city ||
            o.warehouse?.city_name ||
            "";
    }

    if (!formData.value.delivery_date && o.delivery_date) {
        formData.value.delivery_date = o.delivery_date.split("T")[0];
    }

    if (!formData.value.notes && o.customer_notes) {
        formData.value.notes = o.customer_notes;
    }
};

// ─── Derived display values ───────────────────────────────────────────────────
const orderStatus = computed(
    () => order.value?.latest_status?.status?.name || "Pending",
);

const statusColor = computed(() => {
    const name = orderStatus.value.toLowerCase();
    if (name.includes("deliver")) return "#16a34a";
    if (name.includes("cancel")) return "#dc2626";
    if (name.includes("transit")) return "#d97706";
    return "#2563eb";
});

const customerName = computed(() => order.value?.customer?.full_name || "");

const orderItems = computed(() => order.value?.order_items || []);

const minDate = computed(() => {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    return d.toISOString().split("T")[0];
});

// ─── Validation ───────────────────────────────────────────────────────────────
const validatePhone = () => {
    const regex = /^(?:254|\+254|0)([71])[0-9]{8}$/;
    if (!formData.value.phone) {
        errors.value.phone = "Phone number is required";
        return false;
    }
    if (!regex.test(formData.value.phone.replace(/\s/g, ""))) {
        errors.value.phone = "Please enter a valid Kenyan phone number";
        return false;
    }
    errors.value.phone = "";
    return true;
};

const validateAddress = () => {
    if (!formData.value.address || formData.value.address.trim().length < 5) {
        errors.value.address = "Please enter a complete address";
        return false;
    }
    errors.value.address = "";
    return true;
};

const validateCity = () => {
    if (!formData.value.city || formData.value.city.trim().length < 2) {
        errors.value.city = "Please enter your town / city";
        return false;
    }
    errors.value.city = "";
    return true;
};

const validateDate = () => {
    if (!formData.value.delivery_date) {
        errors.value.delivery_date = "Please select a delivery date";
        return false;
    }
    const selected = new Date(formData.value.delivery_date);
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(0, 0, 0, 0);
    if (selected < tomorrow) {
        errors.value.delivery_date =
            "Delivery date must be from tomorrow onwards";
        return false;
    }
    errors.value.delivery_date = "";
    return true;
};

const isFormValid = () =>
    validatePhone() && validateAddress() && validateCity() && validateDate();

// ─── Submit ───────────────────────────────────────────────────────────────────
const handleSubmit = async () => {
    if (!isFormValid()) return;

    isSubmitting.value = true;

    try {
        const { data } = await axios.post(
            `/api/form/${props.order_no}`,
            formData.value,
        );

        if (data.success) {
            showSuccess.value = true;
        } else {
            alert(data.message || "Submission failed. Please try again.");
        }
    } catch (err) {
        console.error("Submission error:", err);
        const msg =
            err.response?.data?.message ||
            "Network error. Please check your connection.";
        alert(msg);
    } finally {
        isSubmitting.value = false;
    }
};

const closeModal = () => {
    showSuccess.value = false;
};

// ─── Fetch order on mount ─────────────────────────────────────────────────────
onMounted(async () => {
    try {
        const response = await fetch(`/api/v1/orders/${props.order_no}`);
        const data = await response.json();

        if (data.success) {
            order.value = data.data;
            populateFromOrder(data.data);
        } else {
            orderError.value = data.message || "Order not found.";
        }
    } catch (err) {
        console.error(err);
        orderError.value = "Error fetching order. Please try again.";
    } finally {
        loadingOrder.value = false;
    }
});
</script>

<template>
    <div class="wrapper">
        <!-- ── Loading ── -->
        <div v-if="loadingOrder" class="state-screen">
            <div class="spinner"></div>
            <p>Loading order details…</p>
        </div>

        <!-- ── Error ── -->
        <div v-else-if="orderError" class="state-screen error-state">
            <p>⚠️ {{ orderError }}</p>
            <button class="btn" @click="() => window.location.reload()">
                Retry
            </button>
        </div>

        <!-- ── Main form ── -->
        <template v-else>
            <!-- Header -->
            <div class="header">
                <div class="header-content">
                    <div>
                        <h2>Order Confirmation</h2>
                        <p v-if="customerName">Hello, {{ customerName }}</p>
                        <p v-else>Update your delivery details below</p>
                    </div>
                    <span class="badge" :style="{ background: statusColor }">
                        {{ orderStatus }}
                    </span>
                </div>
            </div>

            <!-- Order bar -->
            <div class="order-bar">
                <span>Order Number</span>
                <strong>#{{ formData.orderNo }}</strong>
            </div>

            <!-- Order items summary -->
            <div v-if="orderItems.length" class="items-summary">
                <p class="items-label">Items in this order</p>
                <div v-for="item in orderItems" :key="item.id" class="item-row">
                    <span class="item-name">{{
                        item.product?.product_name || item.name || item.sku
                    }}</span>
                    <span class="item-qty">× {{ item.quantity }}</span>
                </div>
            </div>

            <!-- Form -->
            <form class="form" @submit.prevent="handleSubmit">
                <!-- Contact -->
                <section>
                    <h4>CONTACT</h4>
                    <div class="grid">
                        <div>
                            <label>Phone *</label>
                            <input
                                v-model="formData.phone"
                                @blur="validatePhone"
                                placeholder="0712 345 678"
                                inputmode="tel"
                            />
                            <small class="error">{{ errors.phone }}</small>
                        </div>
                        <div>
                            <label>Alternative Phone</label>
                            <input
                                v-model="formData.altPhone"
                                placeholder="Optional"
                                inputmode="tel"
                            />
                        </div>
                    </div>
                </section>

                <!-- Delivery Address -->
                <section>
                    <h4>DELIVERY ADDRESS</h4>

                    <label>Street / Building *</label>
                    <input
                        v-model="formData.address"
                        @blur="validateAddress"
                        placeholder="Upperhill, Community Rd"
                    />
                    <small class="error">{{ errors.address }}</small>

                    <div class="grid" style="margin-top: 15px">
                        <div>
                            <label>Town / City *</label>
                            <input
                                type="text"
                                v-model="formData.city"
                                @blur="validateCity"
                                placeholder="e.g. Nairobi"
                            />
                            <small class="error">{{ errors.city }}</small>
                        </div>
                        <div>
                            <label>Landmark</label>
                            <input
                                v-model="formData.landmark"
                                placeholder="Near Total Petrol Station"
                            />
                        </div>
                    </div>
                </section>

                <!-- Schedule -->
                <section>
                    <h4>SCHEDULE</h4>
                    <div class="grid">
                        <div>
                            <label>Delivery Date *</label>
                            <input
                                type="date"
                                :min="minDate"
                                v-model="formData.delivery_date"
                                @change="validateDate"
                            />
                            <small class="error">{{
                                errors.delivery_date
                            }}</small>
                        </div>
                        <div>
                            <label>Preferred Time</label>
                            <select v-model="formData.timeSlot">
                                <option value="">Any Time</option>
                                <option value="morning">
                                    Morning (8AM–12PM)
                                </option>
                                <option value="afternoon">
                                    Afternoon (12PM–4PM)
                                </option>
                                <option value="evening">
                                    Evening (4PM–7PM)
                                </option>
                            </select>
                        </div>
                    </div>
                </section>

                <!-- Callback -->
                <div class="callback-box">
                    <label class="checkbox-label">
                        <input
                            type="checkbox"
                            v-model="formData.requestCallback"
                        />
                        Request a callback from our delivery team
                    </label>
                </div>

                <!-- Notes -->
                <section>
                    <label>Notes</label>
                    <textarea
                        rows="4"
                        maxlength="500"
                        v-model="formData.notes"
                    />
                    <div class="count">{{ formData.notes.length }}/500</div>
                </section>

                <!-- Submit -->
                <button class="btn" type="submit" :disabled="isSubmitting">
                    {{ isSubmitting ? "Saving…" : "Confirm Delivery Details" }}
                </button>
            </form>
        </template>

        <!-- ── Success Modal ── -->
        <div v-if="showSuccess" class="modal-backdrop">
            <div class="modal">
                <div class="modal-icon">✓</div>
                <h2>All Set!</h2>
                <p>
                    We've received your delivery details for order
                    <strong>#{{ formData.orderNo }}</strong
                    >.
                </p>
                <p class="modal-sub">
                    Our team will be in touch to confirm your delivery.
                </p>
                <button class="btn" @click="closeModal">Close</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ── Base ── */
*,
*::before,
*::after {
    box-sizing: border-box;
}

.wrapper {
    max-width: 600px;
    margin: auto;
    background: #0f172a;
    color: #e2e8f0;
    min-height: 100vh;
    font-family: system-ui, sans-serif;
}

/* ── Loading / Error states ── */
.state-screen {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 60vh;
    gap: 16px;
    color: #94a3b8;
}

.error-state {
    color: #f87171;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #334155;
    border-top-color: #2563eb;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* ── Header ── */
.header {
    background: linear-gradient(135deg, #1e40af, #2563eb);
    padding: 20px;
    color: white;
}

.header h2 {
    margin: 0 0 4px;
    font-size: 1.25rem;
}
.header p {
    margin: 0;
    font-size: 0.875rem;
    opacity: 0.85;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: white;
    white-space: nowrap;
}

/* ── Order bar ── */
.order-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 13px 20px;
    background: #111827;
    font-size: 0.875rem;
    color: #94a3b8;
}

.order-bar strong {
    color: #e2e8f0;
}

/* ── Items summary ── */
.items-summary {
    background: #1e293b;
    border-bottom: 1px solid #334155;
    padding: 12px 20px;
}

.items-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
    margin: 0 0 8px;
}

.item-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.82rem;
    color: #cbd5e1;
    padding: 3px 0;
}

.item-name {
    max-width: 85%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.item-qty {
    color: #94a3b8;
    flex-shrink: 0;
}

/* ── Form ── */
.form {
    padding: 20px;
}

section {
    margin-bottom: 24px;
}

h4 {
    font-size: 0.7rem;
    letter-spacing: 0.1em;
    color: #64748b;
    margin: 0 0 14px;
    text-transform: uppercase;
}

.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

@media (max-width: 500px) {
    .grid {
        grid-template-columns: 1fr;
    }
}

label {
    display: block;
    margin-bottom: 6px;
    font-size: 0.85rem;
    color: #cbd5e1;
}

input,
select,
textarea {
    width: 100%;
    background: #1e293b;
    border: 1px solid #334155;
    color: #f1f5f9;
    border-radius: 8px;
    padding: 11px 12px;
    font-size: 0.9rem;
    outline: none;
    transition: border-color 0.15s;
}

input:focus,
select:focus,
textarea:focus {
    border-color: #2563eb;
}

input::placeholder,
textarea::placeholder {
    color: #475569;
}

/* ── Callback ── */
.callback-box {
    background: #1e293b;
    border: 1px solid #334155;
    padding: 14px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.875rem;
    color: #cbd5e1;
    cursor: pointer;
    margin: 0;
}

.checkbox-label input[type="checkbox"] {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
    accent-color: #2563eb;
}

/* ── Count / error ── */
.count {
    text-align: right;
    margin-top: 5px;
    font-size: 0.78rem;
    color: #64748b;
}

.error {
    display: block;
    margin-top: 4px;
    font-size: 0.78rem;
    color: #f87171;
    min-height: 1em;
}

/* ── Button ── */
.btn {
    width: 100%;
    padding: 14px;
    background: #2563eb;
    border: none;
    color: white;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition:
        background 0.15s,
        opacity 0.15s;
}

.btn:hover:not(:disabled) {
    background: #1d4ed8;
}
.btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

/* ── Modal ── */
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    z-index: 50;
}

.modal {
    background: #1e293b;
    border: 1px solid #334155;
    padding: 32px 28px;
    border-radius: 14px;
    text-align: center;
    max-width: 360px;
    width: 100%;
}

.modal-icon {
    width: 52px;
    height: 52px;
    background: #16a34a;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: white;
    margin: 0 auto 16px;
}

.modal h2 {
    margin: 0 0 10px;
    color: #f1f5f9;
}
.modal p {
    color: #94a3b8;
    font-size: 0.9rem;
    margin: 0 0 6px;
}

.modal-sub {
    font-size: 0.82rem !important;
    margin-bottom: 20px !important;
}

.modal .btn {
    margin-top: 6px;
}
</style>
