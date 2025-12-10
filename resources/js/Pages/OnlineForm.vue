<script setup>
import { ref, computed, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";

// Props (if passed from backend)
const props = defineProps({
    order_no: {
        type: String,
        // default: 'OTMOCT025-34219'
    },
});

// Form data
const formData = ref({
    orderNo: props.order_no,
    phone: "",
    altPhone: "",
    address: "",
    city: "",
    delivery_date: "",
    deliveryTime: "",
    landmark: "",
    requestCallback: false,
    notes: "",
});

// Populate formData when order data becomes available.
// Uses a computed so it re-runs when `order` is set (computed is already imported).
const _populateFromOrder = computed(() => {
    if (!order || !order.value) return null;

    const o = order.value;

    // Order number
    if (!formData.value.orderNo && o.order_no) {
        formData.value.orderNo = o.order_no;
    } else {
        // Always prefer prop/order_no if provided
        formData.value.orderNo = o.order_no || formData.value.orderNo;
    }

    // Phone from customer (if present) but don't overwrite if user already typed
    const custPhone = o.customer?.phone;
    if (custPhone && !formData.value.phone) {
        formData.value.phone = normalizeKenyanPhone(custPhone);
    }

    // Alternative phone: leave blank unless there's a secondary phone in customer
    if (!formData.value.altPhone && o.customer?.alt_phone) {
        formData.value.altPhone = normalizeKenyanPhone(o.customer.alt_phone);
    }

    // Address: prefer shipping_address, then pickup_address, then first address from addresses array
    if (!formData.value.address) {
        const addr =
            o.shipping_address ||
            o.pickup_address ||
            (Array.isArray(o.addresses) && o.addresses.length
                ? o.addresses[0]
                : null);
        // if address is an object, attempt common fields, otherwise use string
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

    // Delivery date: convert to yyyy-mm-dd if present
    if (!formData.value.delivery_date && o.delivery_date) {
        formData.value.delivery_date = o.delivery_date.split("T")[0];
    }

    // City: try shipping address city or warehouse related info
    if (!formData.value.city) {
        formData.value.city =
            (o.shipping_address && o.shipping_address.city) ||
            (o.addresses && o.addresses[0] && o.addresses[0].city) ||
            (o.warehouse && (o.warehouse.city || o.warehouse.city_name)) ||
            "";
    }

    // Landmark / notes: populate from order.customer_notes or reference
    if (!formData.value.landmark) {
        formData.value.landmark = o.customer_notes || "";
    }
    if (!formData.value.notes) {
        formData.value.notes = o.customer_notes || "";
    }

    return true;
});

// Validation errors
const errors = ref({
    phone: "",
    address: "",
    city: "",
    delivery_date: "",
});

// State
const isSubmitting = ref(false);
const showSuccess = ref(false);
const addressSuggestions = ref([]);
const showSuggestions = ref(false);

const order = ref(null);
const loadingOrder = ref(true);
const orderError = ref(null);

// Kenyan cities/areas
// const kenyaLocations = [
//   'Nairobi CBD', 'Westlands', 'Kilimani', 'Parklands', 'Karen', 'Lavington',
//   'Kileleshwa', 'Ngong Road', 'Mombasa Road', 'Thika Road', 'Kasarani',
//   'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret', 'Thika', 'Busia', 'Kakamega'
// ];

// Minimum date (tomorrow)
const minDate = computed(() => {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow.toISOString().split("T")[0];
});

// Validation functions
const validatePhone = () => {
    const phoneRegex = /^(?:254|\+254|0)([71])[0-9]{8}$/;
    if (!formData.value.phone) {
        errors.value.phone = "Phone number is required";
        return false;
    } else if (!phoneRegex.test(formData.value.phone.replace(/\s/g, ""))) {
        errors.value.phone = "Please enter a valid Kenyan phone number";
        return false;
    } else {
        errors.value.phone = "";
        return true;
    }
};

const validateAddress = () => {
    if (!formData.value.address || formData.value.address.trim().length < 5) {
        errors.value.address = "Please enter a complete address";
        return false;
    } else {
        errors.value.address = "";
        return true;
    }
};

const validateCity = () => {
    if (!formData.value.city) {
        errors.value.city = "Please select your town/city";
        return false;
    } else {
        errors.value.city = "";
        return true;
    }
};

const validateDate = () => {
    const selectedDate = new Date(formData.value.delivery_date);
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(0, 0, 0, 0);

    if (!formData.value.delivery_date) {
        errors.value.delivery_date = "Please select a delivery date";
        return false;
    } else if (selectedDate < tomorrow) {
        errors.value.delivery_date =
            "Delivery date must be from tomorrow onwards";
        return false;
    } else {
        errors.value.delivery_date = "";
        return true;
    }
};

// Address suggestions
// const showAddressSuggestions = () => {
//   const query = formData.value.address.toLowerCase();
//   if (query.length > 2) {
//     addressSuggestions.value = kenyaLocations
//       .filter(location => location.toLowerCase().includes(query))
//       .slice(0, 5);
//     showSuggestions.value = addressSuggestions.value.length > 0;
//   } else {
//     addressSuggestions.value = [];
//     showSuggestions.value = false;
//   }
// };

const selectAddress = (suggestion) => {
    formData.value.address = suggestion;
    addressSuggestions.value = [];
    showSuggestions.value = false;
};

const hideSuggestions = () => {
    setTimeout(() => {
        showSuggestions.value = false;
    }, 200);
};
const handleSubmit = async () => {
    // --- Basic validation only ---
    if (!formData.value.phone || formData.value.phone.trim().length < 7) {
        alert("Please enter a valid phone number.");
        console.warn("⚠️ Invalid phone number:", formData.value.phone);
        return;
    }

    if (!formData.value.address || formData.value.address.trim().length < 3) {
        alert("Please enter your delivery address.");
        console.warn("⚠️ Invalid address:", formData.value.address);
        return;
    }

    if (!formData.value.city || formData.value.city.trim().length < 2) {
        alert("Please enter your city.");
        console.warn("⚠️ Invalid city:", formData.value.city);
        return;
    }

    // Optional delivery date check
    if (
        formData.value.delivery_date &&
        isNaN(Date.parse(formData.value.delivery_date))
    ) {
        alert("Please enter a valid delivery date.");
        console.warn("⚠️ Invalid delivery date:", formData.value.delivery_date);
        return;
    }

    // --- Ready to submit ---
    isSubmitting.value = true;
    const url = `/api/form/${props.order_no}`;

    console.log("🚀 Submitting form to:", url);
    console.log("🧾 Payload:", JSON.stringify(formData.value, null, 2));

    try {
        const { data } = await axios.post(url, formData.value);

        console.log("📦 Response data:", data);

        if (data.success) {
            console.log(
                "✅ Submission successful. Server returned:",
                data.data
            );
            showSuccess.value = true;

            // Optionally show confirmation alert
            alert(
                `Order ${
                    data.data.order_no || props.order_no
                } updated successfully!`
            );
        } else {
            console.error("❌ Server responded with an error:", data);
            alert(data.message || "Submission failed. Please try again.");
        }
    } catch (error) {
        console.error("💥 Submission error:", error);
        // if (error.response) {
        //   console.error('📜 Server response:', error.response.data);
        //   alert(error.response.data.message || 'There was a problem submitting your form.');
        // } else {
        //   alert('Network error. Please check your connection.');
        // }
    } finally {
        isSubmitting.value = false;
        console.log("🏁 Submission complete");
    }
};

const closeModal = () => {
    showSuccess.value = false;
    // Optionally redirect or reset form
};

onMounted(async () => {
    try {
        const response = await fetch(`/api/v1/orders/${props.order_no}`);
        const data = await response.json();

        if (data.success) {
            order.value = data.data;
        } else {
            orderError.value = data.message || "Order not found.";
        }
    } catch (error) {
        console.error(error);
        orderError.value = "Error fetching order. Please try again.";
    } finally {
        loadingOrder.value = false;
    }
});
</script>

<template>
    <Head title="Update Delivery Information" />

    <div class="page-container">
        <div class="form-wrapper">
            <!-- Header -->
            <header class="header">
                <div class="logo-container">
                    <svg
                        class="logo-icon"
                        width="40"
                        height="40"
                        viewBox="0 0 40 40"
                        fill="none"
                    >
                        <rect
                            x="5"
                            y="8"
                            width="30"
                            height="24"
                            rx="2"
                            stroke="currentColor"
                            stroke-width="2"
                        />
                        <circle cx="15" cy="32" r="3" fill="currentColor" />
                        <circle cx="25" cy="32" r="3" fill="currentColor" />
                        <path
                            d="M10 20h20"
                            stroke="currentColor"
                            stroke-width="2"
                        />
                    </svg>
                    <div class="logo-text">
                        <h1 class="logo-title">CRM</h1>
                        <p class="logo-tagline">We tailor your Coat</p>
                    </div>
                </div>
            </header>

            <!-- Main Form -->
            <form @submit.prevent="handleSubmit" class="main-form">
                <h2 class="form-title">Order Confirmation</h2>

                <!-- Order Number -->
                <div class="form-group">
                    <label for="orderNo" class="form-label">Order Number</label>
                    <input
                        type="text"
                        id="orderNo"
                        v-model="formData.orderNo"
                        readonly
                        class="form-input input-readonly"
                    />
                </div>

                <!-- Two Column Layout for larger screens -->
                <div class="form-grid">
                    <!-- Phone Number -->
                    <div class="form-group">
                        <label for="phone" class="form-label">
                            Phone Number <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <svg
                                class="input-icon"
                                width="18"
                                height="18"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"
                                />
                            </svg>
                            <input
                                type="tel"
                                id="phone"
                                v-model="formData.phone"
                                placeholder="e.g., 0712345678"
                                @blur="validatePhone"
                                :class="[
                                    'form-input',
                                    'input-with-icon',
                                    { 'input-error': errors.phone },
                                ]"
                            />
                        </div>
                        <span v-if="errors.phone" class="error-message">{{
                            errors.phone
                        }}</span>
                    </div>

                    <!-- Alternative Phone -->
                    <div class="form-group">
                        <label for="altPhone" class="form-label"
                            >Alternative Phone (Optional)</label
                        >
                        <div class="input-icon-wrapper">
                            <svg
                                class="input-icon"
                                width="18"
                                height="18"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"
                                />
                            </svg>
                            <input
                                type="tel"
                                id="altPhone"
                                v-model="formData.altPhone"
                                placeholder="e.g., 0723456789"
                                class="form-input input-with-icon"
                            />
                        </div>
                    </div>
                </div>

                <!-- Delivery Address -->
                <div class="form-group">
                    <label for="address" class="form-label">
                        Update Address <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <svg
                            class="input-icon"
                            width="18"
                            height="18"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                            />
                        </svg>
                        <input
                            type="text"
                            id="address"
                            v-model="formData.address"
                            placeholder="Enter building, street, area"
                            @input="showAddressSuggestions"
                            @blur="hideSuggestions"
                            :class="[
                                'form-input',
                                'input-with-icon',
                                { 'input-error': errors.address },
                            ]"
                        />
                    </div>
                    <span v-if="errors.address" class="error-message">{{
                        errors.address
                    }}</span>

                    <!-- Address Suggestions -->
                    <div
                        v-if="showSuggestions && addressSuggestions.length > 0"
                        class="suggestions-dropdown"
                    >
                        <div
                            v-for="(suggestion, index) in addressSuggestions"
                            :key="index"
                            @mousedown="selectAddress(suggestion)"
                            class="suggestion-item"
                        >
                            {{ suggestion }}
                        </div>
                    </div>
                </div>

                <!-- City and Landmark Row -->
                <div class="form-grid">
                    <!-- Town/City -->
                    <div class="form-group">
                        <label for="city" class="form-label">
                            Town/City <span class="required">*</span>
                        </label>
                        <select
                            id="city"
                            v-model="formData.city"
                            @change="validateCity"
                            :class="[
                                'form-input',
                                { 'input-error': errors.city },
                            ]"
                        >
                            <option value="Nairobi">
                                Nairobi (Nairobi City County)
                            </option>

                            <!-- Mombasa & Coast -->
                            <option value="Mombasa">Mombasa</option>
                            <option value="Kwale">Kwale</option>
                            <option value="Kilifi">Kilifi</option>
                            <option value="Malindi">Malindi</option>
                            <option value="Lamu">Lamu</option>
                            <option value="Voi">Voi</option>
                            <option value="Wundanyi">Wundanyi</option>

                            <!-- Western & Nyanza -->
                            <option value="Kisumu">Kisumu</option>
                            <option value="Kakamega">Kakamega</option>
                            <option value="Busia">Busia</option>
                            <option value="Bungoma">Bungoma</option>
                            <option value="Vihiga">Vihiga</option>
                            <option value="Siaya">Siaya</option>
                            <option value="Kisii">Kisii</option>
                            <option value="Nyamira">Nyamira</option>
                            <option value="Homa Bay">Homa Bay</option>
                            <option value="Migori">Migori</option>
                            <option value="Kuria">Kuria</option>

                            <!-- Rift Valley -->
                            <option value="Nakuru">Nakuru</option>
                            <option value="Eldoret">
                                Eldoret (Uasin Gishu)
                            </option>
                            <option value="Kericho">Kericho</option>
                            <option value="Bomet">Bomet</option>
                            <option value="Narok">Narok</option>
                            <option value="Naivasha">Naivasha</option>
                            <option value="Nanyuki">Nanyuki</option>
                            <option value="Nyeri">Nyeri</option>
                            <option value="Nyahururu">Nyahururu</option>
                            <option value="Kitale">Kitale (Trans Nzoia)</option>
                            <option value="Kapsabet">Kapsabet</option>
                            <option value="Iten">Iten</option>
                            <option value="Lodwar">Lodwar</option>
                            <option value="Maralal">Maralal</option>
                            <option value="Kabarnet">Kabarnet</option>

                            <!-- Central & Mt. Kenya -->
                            <option value="Thika">Thika</option>
                            <option value="Kiambu">Kiambu</option>
                            <option value="Murang'a">Murang'a</option>
                            <option value="Karuri">Karuri</option>
                            <option value="Embu">Embu</option>
                            <option value="Meru">Meru</option>
                            <option value="Isiolo">Isiolo</option>
                            <option value="Maua">Maua</option>

                            <!-- Eastern -->
                            <option value="Machakos">Machakos</option>
                            <option value="Kitui">Kitui</option>
                            <option value="Mwingi">Mwingi</option>
                            <option value="Garissa">Garissa</option>
                            <option value="Wajir">Wajir</option>
                            <option value="Mandera">Mandera</option>
                            <option value="Moyale">Moyale</option>

                            <!-- North Eastern & Others -->
                            <option value="Marsabit">Marsabit</option>
                            <option value="Lokichoggio">Lokichoggio</option>
                            <option value="Kapenguria">Kapenguria</option>

                            <!-- Other fast-growing or commercially important towns -->
                            <option value="Ruiru">Ruiru</option>
                            <option value="Ongata Rongai">Ongata Rongai</option>
                            <option value="Juia">Juia</option>
                            <option value="Kikuyu">Kikuyu</option>
                            <option value="Webuye">Webuye</option>
                            <option value="Kerugoya">Kerugoya</option>
                            <option value="Diani Beach">Diani Beach</option>
                            <option value="Watamu">Watamu</option>
                        </select>
                        <span v-if="errors.city" class="error-message">{{
                            errors.city
                        }}</span>
                    </div>

                    <!-- Landmark -->
                    <div class="form-group">
                        <label for="landmark" class="form-label"
                            >Landmark/Building</label
                        >
                        <input
                            type="text"
                            id="landmark"
                            v-model="formData.landmark"
                            placeholder="e.g., Near Nakumatt"
                            class="form-input"
                        />
                        <span class="helper-text">Help us find you easily</span>
                    </div>
                </div>

                <!-- Date and Time Row -->
                <div class="form-grid">
                    <!-- Delivery Date -->
                    <div class="form-group">
                        <label for="delivery_date" class="form-label">
                            Delivery Date <span class="required">*</span>
                        </label>
                        <input
                            type="date"
                            id="delivery_date"
                            v-model="formData.delivery_date"
                            :min="minDate"
                            @change="validateDate"
                            :class="[
                                'form-input',
                                { 'input-error': errors.delivery_date },
                            ]"
                        />
                        <span
                            v-if="errors.delivery_date"
                            class="error-message"
                            >{{ errors.delivery_date }}</span
                        >
                        <span v-else class="helper-text"
                            >From tomorrow onwards</span
                        >
                    </div>

                    <!-- Delivery Time -->
                    <div class="form-group">
                        <label for="deliveryTime" class="form-label"
                            >Preferred Time</label
                        >
                        <select
                            id="deliveryTime"
                            v-model="formData.deliveryTime"
                            class="form-input"
                        >
                            <option value="">Any time</option>
                            <option value="morning">
                                Morning (8 AM - 12 PM)
                            </option>
                            <option value="afternoon">
                                Afternoon (12 PM - 4 PM)
                            </option>
                            <option value="evening">
                                Evening (4 PM - 7 PM)
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Request Callback -->
                <div class="checkbox-container">
                    <label class="checkbox-label">
                        <input
                            type="checkbox"
                            v-model="formData.requestCallback"
                            class="checkbox-input"
                        />
                        <span class="checkbox-content">
                            <strong class="checkbox-title"
                                >Request a callback from our delivery
                                team</strong
                            >
                            <small class="checkbox-subtitle"
                                >We'll call you to confirm the delivery
                                details</small
                            >
                        </span>
                    </label>
                </div>

                <!-- Special Instructions -->
                <div class="form-group">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea
                        id="notes"
                        v-model="formData.notes"
                        rows="4"
                        placeholder="Your message here..."
                        maxlength="500"
                        class="form-textarea"
                    ></textarea>
                    <div class="char-counter">
                        {{ formData.notes.length }}/500
                    </div>
                </div>

                <button
                    type="submit"
                    class="submit-button"
                    :disabled="isSubmitting"
                    style="
                        background-color: #2563eb;
                        color: white;
                        padding: 0.75rem 1.5rem;
                        border-radius: 0.5rem;
                        border: none;
                        font-weight: 600;
                    "
                >
                    <span v-if="!isSubmitting">Submit</span>
                    <span v-else class="loading-state">
                        <svg
                            class="spinner"
                            viewBox="0 0 24 24"
                            style="
                                width: 1rem;
                                height: 1rem;
                                margin-right: 0.5rem;
                                vertical-align: middle;
                            "
                        >
                            <circle
                                class="spinner-track"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="white"
                                stroke-width="4"
                                fill="none"
                            />
                            <path
                                class="spinner-head"
                                fill="white"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            />
                        </svg>
                        Submitting...
                    </span>
                </button>

                <!-- Help Text -->
                <p class="help-text">
                    Need help? Call us at
                    <a href="tel:+254700000000" class="help-link"
                        >+254 700 000 000</a
                    >
                </p>
            </form>

            <!-- Success Modal -->
            <Teleport to="body">
                <Transition name="modal">
                    <div
                        v-if="showSuccess"
                        class="modal-overlay"
                        @click="closeModal"
                    >
                        <div class="modal-content" @click.stop>
                            <div class="success-icon-wrapper">
                                <svg
                                    class="success-icon"
                                    width="60"
                                    height="60"
                                    viewBox="0 0 60 60"
                                    fill="none"
                                >
                                    <circle
                                        cx="30"
                                        cy="30"
                                        r="28"
                                        stroke="#10b981"
                                        stroke-width="4"
                                    />
                                    <path
                                        d="M20 30l8 8 12-16"
                                        stroke="#10b981"
                                        stroke-width="4"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>
                            <h3 class="modal-title">Success!</h3>
                            <p class="modal-text">
                                We've received your updated delivery
                                information. Our team will contact you soon.
                            </p>
                            <p class="modal-reference">
                                Reference: {{ formData.orderNo }}
                            </p>
                            <button @click="closeModal" class="modal-button">
                                Close
                            </button>
                        </div>
                    </div>
                </Transition>
            </Teleport>
        </div>
    </div>
</template>

<style scoped>
/* Base Reset & Variables */
* {
    box-sizing: border-box;
}

:root {
    --primary-color: #2563eb;
    --primary-dark: #1e40af;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --error-color: #ef4444;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-400: #9ca3af;
    --gray-500: #6b7280;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-800: #1f2937;
    --gray-900: #111827;
}

/* Page Container */
.page-container {
    min-height: 100vh;
    background-color: #2596be !important;

    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
        "Helvetica Neue", Arial, sans-serif;
}

/* Form Wrapper */
.form-wrapper {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    min-height: 100vh;
}

/* Header */
.header {
    background: linear-gradient(
        135deg,
        var(--primary-color) 0%,
        var(--primary-dark) 100%
    );
    padding: 1.25rem;
    color: white;
}

.logo-container {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}

.logo-icon {
    flex-shrink: 0;
    color: white;
}

.logo-text {
    flex: 1;
    min-width: 0;
}

.logo-title {
    margin: 0;
    font-size: 1.375rem;
    font-weight: 700;
    line-height: 1.2;
}

.logo-tagline {
    margin: 0.25rem 0 0 0;
    font-size: 0.813rem;
    opacity: 0.9;
}

/* Alert Banner */
.alert-banner {
    background: #fef3c7;
    border-left: 4px solid var(--warning-color);
    padding: 0.875rem 1.25rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    color: #92400e;
}

.alert-icon {
    flex-shrink: 0;
    color: var(--warning-color);
    margin-top: 0.125rem;
}

.alert-text {
    font-size: 0.875rem;
    line-height: 1.5;
}

/* Main Form */
.main-form {
    padding: 1.5rem 1.25rem;
}

.form-title {
    margin: 0 0 1.5rem 0;
    font-size: 1.25rem;
    color: var(--gray-800);
    font-weight: 600;
}

/* Form Grid - Responsive Two Column */
.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
}

/* Form Group */
.form-group {
    margin-bottom: 1.25rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--gray-700);
    font-size: 0.875rem;
}

.required {
    color: var(--error-color);
}

/* Form Inputs */
.form-input,
.form-textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid var(--gray-200);
    border-radius: 0.5rem;
    font-size: 0.938rem;
    transition: all 0.2s ease;
    font-family: inherit;
    background: white;
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.input-readonly {
    background: var(--gray-100);
    color: var(--gray-600);
    cursor: not-allowed;
}

.input-error {
    border-color: var(--error-color) !important;
}

/* Input with Icon */
.input-icon-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 0.875rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-400);
    pointer-events: none;
}

.input-with-icon {
    padding-left: 2.75rem;
}

/* Select Dropdown */
select.form-input {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    padding-right: 2.5rem;
}

/* Textarea */
.form-textarea {
    resize: vertical;
    min-height: 100px;
}

/* Helper Text */
.helper-text {
    display: block;
    color: var(--gray-500);
    font-size: 0.813rem;
    margin-top: 0.375rem;
}

.error-message {
    display: block;
    color: var(--error-color);
    font-size: 0.813rem;
    margin-top: 0.375rem;
    font-weight: 500;
}

.char-counter {
    text-align: right;
    color: var(--gray-400);
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

/* Suggestions Dropdown */
.suggestions-dropdown {
    margin-top: 0.5rem;
    background: white;
    border: 2px solid var(--gray-200);
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    max-height: 200px;
    overflow-y: auto;
}

.suggestion-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: background 0.2s;
    font-size: 0.875rem;
}

.suggestion-item:hover {
    background: var(--gray-50);
}

.suggestion-item:not(:last-child) {
    border-bottom: 1px solid var(--gray-200);
}

/* Checkbox */
.checkbox-container {
    background: var(--gray-50);
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.25rem;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    cursor: pointer;
}

.checkbox-input {
    width: 1.25rem;
    height: 1.25rem;
    margin-top: 0.125rem;
    cursor: pointer;
    flex-shrink: 0;
    accent-color: var(--primary-color);
}

.checkbox-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    flex: 1;
}

.checkbox-title {
    color: var(--gray-800);
    font-size: 0.875rem;
    font-weight: 600;
}

.checkbox-subtitle {
    color: var(--gray-600);
    font-size: 0.813rem;
}

/* Submit Button */
.submit-button {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(
        135deg,
        var(--primary-color) 0%,
        var(--primary-dark) 100%
    );
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);
}

.submit-button:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.4);
}

.submit-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.loading-state {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.625rem;
}

.spinner {
    width: 1.25rem;
    height: 1.25rem;
    animation: spin 1s linear infinite;
}

.spinner-track {
    opacity: 0.25;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Help Text */
.help-text {
    text-align: center;
    margin-top: 1.25rem;
    color: var(--gray-600);
    font-size: 0.875rem;
}

.help-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
}

.help-link:hover {
    text-decoration: underline;
}

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.75);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    z-index: 9999;
}

.modal-content {
    background: white;
    padding: 2rem 1.5rem;
    border-radius: 1rem;
    max-width: 400px;
    width: 100%;
    text-align: center;
}

.success-icon-wrapper {
    margin-bottom: 1.25rem;
    display: flex;
    justify-content: center;
}

.modal-title {
    margin: 0 0 1rem 0;
    color: var(--gray-800);
    font-size: 1.5rem;
    font-weight: 700;
}

.modal-text {
    color: var(--gray-600);
    line-height: 1.6;
    margin-bottom: 1rem;
    font-size: 0.938rem;
}

.modal-reference {
    background: var(--gray-100);
    padding: 0.75rem;
    border-radius: 0.5rem;
    font-family: "Courier New", monospace;
    font-weight: 600;
    color: var(--primary-color);
    margin: 1rem 0;
    font-size: 0.938rem;
}

.modal-button {
    margin-top: 1.5rem;
    padding: 0.75rem 2rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.modal-button:hover {
    background: var(--primary-dark);
}

/* Modal Transitions */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
    transition: transform 0.3s ease;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
    transform: translateY(20px);
}

/* Responsive Design */

/* Small Mobile (320px+) */
@media (min-width: 320px) {
    .logo-title {
        font-size: 1.25rem;
    }

    .form-title {
        font-size: 1.125rem;
    }
}

/* Mobile (375px+) */
@media (min-width: 375px) {
    .alert-banner {
        padding: 1rem 1.25rem;
    }

    .main-form {
        padding: 1.75rem 1.5rem;
    }
}

/* Large Mobile (425px+) */
@media (min-width: 425px) {
    .logo-title {
        font-size: 1.5rem;
    }

    .form-title {
        font-size: 1.375rem;
    }
}

/* Tablet (640px+) */
@media (min-width: 640px) {
    .page-container {
        padding: 1.25rem;
    }

    .form-wrapper {
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1),
            0 10px 10px -5px rgba(0, 0, 0, 0.04);
        min-height: auto;
    }

    .header {
        padding: 1.875rem 2rem;
    }

    .logo-title {
        font-size: 1.75rem;
    }

    .logo-tagline {
        font-size: 0.875rem;
    }

    .alert-banner {
        padding: 1.125rem 2rem;
    }

    .main-form {
        padding: 2.5rem 2rem;
    }

    .form-title {
        font-size: 1.5rem;
        margin-bottom: 2rem;
    }

    .form-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .modal-content {
        padding: 2.5rem 2rem;
    }
}

/* Desktop (768px+) */
@media (min-width: 768px) {
    .page-container {
        padding: 2rem;
    }

    .form-wrapper {
        border-radius: 1.25rem;
    }

    .header {
        padding: 2rem 2.5rem;
    }

    .alert-banner {
        padding: 1.25rem 2.5rem;
    }

    .alert-text {
        font-size: 0.938rem;
    }

    .main-form {
        padding: 3rem 2.5rem;
    }
}

/* Large Desktop (1024px+) */
@media (min-width: 1024px) {
    .form-wrapper {
        max-width: 900px;
    }

    .form-input,
    .form-textarea {
        font-size: 1rem;
    }

    .submit-button:hover:not(:disabled) {
        transform: translateY(-3px);
        box-shadow: 0 15px 25px -5px rgba(37, 99, 235, 0.4);
    }
}

/* Extra Large Desktop (1440px+) */
@media (min-width: 1440px) {
    .page-container {
        padding: 3rem;
    }
}

/* Print Styles */
@media print {
    .page-container {
        background: white;
        padding: 0;
    }

    .submit-button,
    .help-text,
    .alert-banner {
        display: none;
    }
}

/* High DPI Screens */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
    .form-input,
    .form-textarea {
        border-width: 1.5px;
    }
}

/* Touch Devices - Larger Touch Targets */
@media (hover: none) and (pointer: coarse) {
    .form-input,
    .form-textarea,
    .submit-button {
        min-height: 48px;
    }

    .checkbox-input {
        width: 1.5rem;
        height: 1.5rem;
    }

    .suggestion-item {
        padding: 1rem;
    }
}

/* Reduce Motion */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Dark Mode Support (Optional) */
@media (prefers-color-scheme: dark) {
    /* Add dark mode styles here if needed */
}

/* Landscape Mobile */
@media (max-height: 500px) and (orientation: landscape) {
    .page-container {
        padding: 0.5rem;
    }

    .header {
        padding: 1rem;
    }

    .main-form {
        padding: 1.5rem;
    }

    .form-title {
        margin-bottom: 1rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }
}
</style>
