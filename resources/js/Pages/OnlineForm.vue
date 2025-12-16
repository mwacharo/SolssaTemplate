<script setup>
import { ref, computed, onMounted, watch } from "vue";

// Props
const props = defineProps({
    order_no: {
        type: String,
        default: 'OTMOCT025-34219'
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

// State
const isSubmitting = ref(false);
const showSuccess = ref(false);
const addressSuggestions = ref([]);
const showAddressSuggestions = ref(false);
const citySuggestions = ref([]);
const showCitySuggestions = ref(false);
const citySearchQuery = ref("");

const order = ref(null);
const loadingOrder = ref(true);
const orderError = ref(null);

// Kenyan locations for autocomplete
const kenyaLocations = [
    'Nairobi CBD', 'Westlands', 'Kilimani', 'Parklands', 'Karen', 'Lavington',
    'Kileleshwa', 'Ngong Road', 'Mombasa Road', 'Thika Road', 'Kasarani',
    'Upperhill', 'Kilimani', 'South B', 'South C', 'Langata', 'Kiambu Road',
    'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret', 'Thika', 'Busia', 'Kakamega'
];

// All cities
const allCities = [
    { value: "Nairobi", label: "Nairobi (Nairobi City County)" },
    { value: "Mombasa", label: "Mombasa" },
    { value: "Kwale", label: "Kwale" },
    { value: "Kilifi", label: "Kilifi" },
    { value: "Malindi", label: "Malindi" },
    { value: "Kisumu", label: "Kisumu" },
    { value: "Kakamega", label: "Kakamega" },
    { value: "Busia", label: "Busia" },
    { value: "Bungoma", label: "Bungoma" },
    { value: "Nakuru", label: "Nakuru" },
    { value: "Eldoret", label: "Eldoret (Uasin Gishu)" },
    { value: "Kericho", label: "Kericho" },
    { value: "Thika", label: "Thika" },
    { value: "Kiambu", label: "Kiambu" },
    { value: "Machakos", label: "Machakos" },
    { value: "Kitui", label: "Kitui" },
    { value: "Nyeri", label: "Nyeri" },
    { value: "Meru", label: "Meru" },
    { value: "Embu", label: "Embu" },
];

// Validation errors
const errors = ref({
    phone: "",
    address: "",
    city: "",
    delivery_date: "",
});

// Phone normalization
const normalizeKenyanPhone = (phone) => {
    if (!phone) return "";
    let cleaned = phone.replace(/\s/g, "");
    if (cleaned.startsWith("+254")) {
        cleaned = "0" + cleaned.substring(4);
    } else if (cleaned.startsWith("254")) {
        cleaned = "0" + cleaned.substring(3);
    }
    return cleaned;
};

// Minimum date (tomorrow)
const minDate = computed(() => {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow.toISOString().split("T")[0];
});

// Address autocomplete
const filterAddressSuggestions = () => {
    const query = formData.value.address.toLowerCase();
    if (query.length > 2) {
        addressSuggestions.value = kenyaLocations
            .filter(location => location.toLowerCase().includes(query))
            .slice(0, 5);
        showAddressSuggestions.value = addressSuggestions.value.length > 0;
    } else {
        addressSuggestions.value = [];
        showAddressSuggestions.value = false;
    }
};

const selectAddress = (suggestion) => {
    formData.value.address = suggestion;
    showAddressSuggestions.value = false;
};

// City search with autocomplete
watch(citySearchQuery, (newQuery) => {
    if (newQuery.length > 0) {
        citySuggestions.value = allCities.filter(city => 
            city.label.toLowerCase().includes(newQuery.toLowerCase())
        );
        showCitySuggestions.value = true;
    } else {
        citySuggestions.value = allCities;
        showCitySuggestions.value = false;
    }
});

const selectCity = (cityValue) => {
    formData.value.city = cityValue;
    citySearchQuery.value = allCities.find(c => c.value === cityValue)?.label || "";
    showCitySuggestions.value = false;
    errors.value.city = "";
};

const toggleCityDropdown = () => {
    showCitySuggestions.value = !showCitySuggestions.value;
    if (showCitySuggestions.value) {
        citySuggestions.value = allCities;
    }
};

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
        errors.value.delivery_date = "Delivery date must be from tomorrow onwards";
        return false;
    } else {
        errors.value.delivery_date = "";
        return true;
    }
};

const handleSubmit = async () => {
    if (!validatePhone() || !validateAddress() || !validateCity() || !validateDate()) {
        return;
    }

    isSubmitting.value = true;
    const url = `/api/form/${props.order_no}`;

    try {
        // Simulating API call
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        showSuccess.value = true;
        console.log("Form submitted:", formData.value);
    } catch (error) {
        console.error("Submission error:", error);
        alert("There was a problem submitting your form. Please try again.");
    } finally {
        isSubmitting.value = false;
    }
};

const closeModal = () => {
    showSuccess.value = false;
};

// Load order data
onMounted(async () => {
    try {
        // Simulating API call
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // Mock order data
        order.value = {
            order_no: props.order_no,
            customer: {
                phone: "0712345678"
            },
            shipping_address: {
                city: "Nairobi"
            }
        };
        
        if (order.value.customer?.phone) {
            formData.value.phone = normalizeKenyanPhone(order.value.customer.phone);
        }
        if (order.value.shipping_address?.city) {
            formData.value.city = order.value.shipping_address.city;
            citySearchQuery.value = allCities.find(c => c.value === order.value.shipping_address.city)?.label || "";
        }
        
        loadingOrder.value = false;
    } catch (error) {
        console.error(error);
        orderError.value = "Error fetching order. Please try again.";
        loadingOrder.value = false;
    }
});
</script>

<template>
    <div class="page-container">
        <div class="form-wrapper">
            <!-- Header -->
            <header class="header">
                <div class="logo-container">
                    <div class="logo-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                            <rect x="8" y="12" width="32" height="24" rx="2" stroke="currentColor" stroke-width="2.5"/>
                            <circle cx="18" cy="38" r="3" fill="currentColor"/>
                            <circle cx="30" cy="38" r="3" fill="currentColor"/>
                            <path d="M12 24h24" stroke="currentColor" stroke-width="2.5"/>
                        </svg>
                    </div>
                    <div class="logo-text">
                        <h1 class="logo-title">Delivery Hub</h1>
                        <p class="logo-tagline">Fast & Reliable Service</p>
                    </div>
                </div>
            </header>

            <!-- Loading State -->
            <div v-if="loadingOrder" class="loading-container">
                <div class="spinner-large"></div>
                <p class="loading-text">Loading your order details...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="orderError" class="error-container">
                <svg class="error-icon" width="48" height="48" viewBox="0 0 48 48" fill="none">
                    <circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="2"/>
                    <path d="M24 16v12M24 32v.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <p class="error-text">{{ orderError }}</p>
            </div>

            <!-- Main Form -->
            <form v-else @submit.prevent="handleSubmit" class="main-form">
                <div class="form-header">
                    <h2 class="form-title">Delivery Information</h2>
                    <p class="form-subtitle">Please confirm and update your delivery details</p>
                </div>

                <!-- Order Number -->
                <div class="form-section">
                    <div class="form-group">
                        <label for="orderNo" class="form-label">
                            <svg class="label-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M8 2a1 1 0 011 1v4h4a1 1 0 110 2H9v4a1 1 0 11-2 0V9H3a1 1 0 110-2h4V3a1 1 0 011-1z"/>
                            </svg>
                            Order Number
                        </label>
                        <input
                            type="text"
                            id="orderNo"
                            v-model="formData.orderNo"
                            readonly
                            class="form-input input-readonly"
                        />
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="form-section">
                    <h3 class="section-title">Contact Information</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="phone" class="form-label">
                                <svg class="label-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M2 2.4A1.4 1.4 0 013.4 1h1.8a.8.8 0 01.79.65l.6 3a.8.8 0 01-.43.85l-1.24.62a8.82 8.82 0 004.88 4.88l.62-1.24a.8.8 0 01.85-.43l3 .6a.8.8 0 01.65.79v1.8A1.4 1.4 0 0113 14h-.16C6.42 13.84 2.16 9.58 2 3.16V2.4z"/>
                                </svg>
                                Phone Number <span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <input
                                    type="tel"
                                    id="phone"
                                    v-model="formData.phone"
                                    placeholder="0712345678"
                                    @blur="validatePhone"
                                    :class="['form-input', { 'input-error': errors.phone }]"
                                />
                            </div>
                            <span v-if="errors.phone" class="error-message">{{ errors.phone }}</span>
                        </div>

                        <div class="form-group">
                            <label for="altPhone" class="form-label">Alternative Phone</label>
                            <div class="input-wrapper">
                                <input
                                    type="tel"
                                    id="altPhone"
                                    v-model="formData.altPhone"
                                    placeholder="0723456789 (Optional)"
                                    class="form-input"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delivery Address -->
                <div class="form-section">
                    <h3 class="section-title">Delivery Address</h3>
                    
                    <div class="form-group">
                        <label for="address" class="form-label">
                            <svg class="label-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                <path fill-rule="evenodd" d="M8 1a5 5 0 00-5 5c0 3.87 5 9 5 9s5-5.13 5-9a5 5 0 00-5-5zm0 7a2 2 0 110-4 2 2 0 010 4z"/>
                            </svg>
                            Street Address <span class="required">*</span>
                        </label>
                        <div class="input-wrapper autocomplete-wrapper">
                            <input
                                type="text"
                                id="address"
                                v-model="formData.address"
                                placeholder="Enter building name, street, area..."
                                @input="filterAddressSuggestions"
                                @blur="validateAddress"
                                :class="['form-input', { 'input-error': errors.address }]"
                            />
                            
                            <!-- Address Suggestions -->
                            <div v-if="showAddressSuggestions" class="autocomplete-dropdown">
                                <div
                                    v-for="(suggestion, index) in addressSuggestions"
                                    :key="index"
                                    @mousedown="selectAddress(suggestion)"
                                    class="autocomplete-item"
                                >
                                    <svg class="autocomplete-icon" width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
                                        <path d="M7 1a4 4 0 00-4 4c0 3.1 4 7.2 4 7.2s4-4.1 4-7.2a4 4 0 00-4-4zm0 5.6a1.6 1.6 0 110-3.2 1.6 1.6 0 010 3.2z"/>
                                    </svg>
                                    {{ suggestion }}
                                </div>
                            </div>
                        </div>
                        <span v-if="errors.address" class="error-message">{{ errors.address }}</span>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="city" class="form-label">
                                <svg class="label-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M3 2a1 1 0 011-1h8a1 1 0 011 1v12a1 1 0 01-1 1H4a1 1 0 01-1-1V2zm2 1v2h2V3H5zm4 0v2h2V3H9zM5 7v2h2V7H5zm4 0v2h2V7H9zM5 11v2h2v-2H5z"/>
                                </svg>
                                City <span class="required">*</span>
                            </label>
                            <div class="input-wrapper autocomplete-wrapper">
                                <input
                                    type="text"
                                    v-model="citySearchQuery"
                                    @focus="toggleCityDropdown"
                                    @input="showCitySuggestions = true"
                                    placeholder="Search city..."
                                    :class="['form-input', { 'input-error': errors.city }]"
                                />
                                
                                <!-- City Suggestions -->
                                <div v-if="showCitySuggestions" class="autocomplete-dropdown">
                                    <div
                                        v-for="city in citySuggestions"
                                        :key="city.value"
                                        @mousedown="selectCity(city.value)"
                                        class="autocomplete-item"
                                        :class="{ 'active': formData.city === city.value }"
                                    >
                                        <svg class="autocomplete-icon" width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
                                            <path d="M2 1a1 1 0 011-1h6a1 1 0 011 1v10a1 1 0 01-1 1H3a1 1 0 01-1-1V1zm2 1v1.5h1.5V2H4zm3 0v1.5h1.5V2H7zM4 5v1.5h1.5V5H4zm3 0v1.5h1.5V5H7zM4 8v1.5h1.5V8H4z"/>
                                        </svg>
                                        {{ city.label }}
                                    </div>
                                </div>
                            </div>
                            <span v-if="errors.city" class="error-message">{{ errors.city }}</span>
                        </div>

                        <div class="form-group">
                            <label for="landmark" class="form-label">Landmark</label>
                            <input
                                type="text"
                                id="landmark"
                                v-model="formData.landmark"
                                placeholder="e.g., Near ABC Mall"
                                class="form-input"
                            />
                            <span class="helper-text">Help us locate you easily</span>
                        </div>
                    </div>
                </div>

                <!-- Delivery Schedule -->
                <div class="form-section">
                    <h3 class="section-title">Delivery Schedule</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="delivery_date" class="form-label">
                                <svg class="label-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M4.5 1a.5.5 0 00-.5.5V2H3a2 2 0 00-2 2v9a2 2 0 002 2h10a2 2 0 002-2V4a2 2 0 00-2-2h-1v-.5a.5.5 0 00-1 0V2H5v-.5a.5.5 0 00-.5-.5zM3 5h10v8H3V5z"/>
                                </svg>
                                Delivery Date <span class="required">*</span>
                            </label>
                            <input
                                type="date"
                                id="delivery_date"
                                v-model="formData.delivery_date"
                                :min="minDate"
                                @change="validateDate"
                                :class="['form-input', { 'input-error': errors.delivery_date }]"
                            />
                            <span v-if="errors.delivery_date" class="error-message">{{ errors.delivery_date }}</span>
                            <span v-else class="helper-text">Available from tomorrow</span>
                        </div>

                        <div class="form-group">
                            <label for="deliveryTime" class="form-label">
                                <svg class="label-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M8 1a7 7 0 100 14A7 7 0 008 1zm0 2a.5.5 0 01.5.5v4.3l2.4 1.4a.5.5 0 11-.5.9l-2.7-1.6A.5.5 0 017.5 8V3.5A.5.5 0 018 3z"/>
                                </svg>
                                Preferred Time
                            </label>
                            <select
                                id="deliveryTime"
                                v-model="formData.deliveryTime"
                                class="form-input"
                            >
                                <option value="">Any time</option>
                                <option value="morning">Morning (8 AM - 12 PM)</option>
                                <option value="afternoon">Afternoon (12 PM - 4 PM)</option>
                                <option value="evening">Evening (4 PM - 7 PM)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Additional Options -->
                <div class="form-section">
                    <div class="checkbox-card">
                        <label class="checkbox-label">
                            <input
                                type="checkbox"
                                v-model="formData.requestCallback"
                                class="checkbox-input"
                            />
                            <div class="checkbox-content">
                                <div class="checkbox-header">
                                    <svg class="checkbox-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2 3a1 1 0 011-1h2.2a1 1 0 01.99.84l.74 4.4a1 1 0 01-.54 1.06l-1.55.77a11 11 0 006.1 6.1l.78-1.55a1 1 0 011.06-.54l4.4.74a1 1 0 01.84.99V17a1 1 0 01-1 1h-.2C7.8 17.84 2.16 12.2 2 5.2V3z"/>
                                    </svg>
                                    <strong class="checkbox-title">Request callback</strong>
                                </div>
                                <p class="checkbox-description">Our delivery team will call to confirm details</p>
                            </div>
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="notes" class="form-label">
                            <svg class="label-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M3 2a2 2 0 012-2h6a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V2zm3 1v1h4V3H6zm0 3v1h4V6H6zm0 3v1h4V9H6z"/>
                            </svg>
                            Special Instructions
                        </label>
                        <textarea
                            id="notes"
                            v-model="formData.notes"
                            rows="4"
                            placeholder="Any special delivery instructions..."
                            maxlength="500"
                            class="form-textarea"
                        ></textarea>
                        <div class="char-counter">{{ formData.notes.length }}/500</div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="submit-button"
                    :disabled="isSubmitting"
                >
                    <span v-if="!isSubmitting" class="button-content">
                        <svg class="button-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 2a1 1 0 011 1v6h6a1 1 0 110 2h-6v6a1 1 0 11-2 0v-6H3a1 1 0 110-2h6V3a1 1 0 011-1z"/>
                        </svg>
                        Confirm Delivery Details
                    </span>
                    <span v-else class="button-content">
                        <div class="spinner"></div>
                        Processing...
                    </span>
                </button>

                <!-- Help Text -->
                <p class="help-text">
                    Need assistance? Call 
                    <a href="tel:+254700000000" class="help-link">+254 700 000 000</a>
                </p>
            </form>

            <!-- Success Modal -->
            <Transition name="modal">
                <div v-if="showSuccess" class="modal-overlay" @click="closeModal">
                    <div class="modal-content" @click.stop>
                        <div class="success-animation">
                            <svg class="success-checkmark" width="80" height="80" viewBox="0 0 80 80">
                                <circle class="success-circle" cx="40" cy="40" r="36" stroke="#10b981" stroke-width="4" fill="none"/>
                                <path class="success-check" d="M25 40l12 12 18-24" stroke="#10b981" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                            </svg>
                        </div>
                        <h3 class="modal-title">Order Confirmed!</h3>
                        <p class="modal-text">
                            Your delivery information has been updated successfully. 
                            We'll contact you soon to confirm the delivery.
                        </p>
                        <div class="modal-reference">
                            <span class="reference-label">Order Number</span>
                            <span class="reference-value">{{ formData.orderNo }}</span>
                        </div>
                        <button @click="closeModal" class="modal-button">
                            Got it, thanks!
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </div>
</template>

<style scoped>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary: #2563eb;
    --primary-dark: #1e40af;
    --primary-light: #3b82f6;
    --success: #10b981;
    --error: #ef4444;
    --warning: #f59e0b;
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
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.page-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.form-wrapper {
    max-width: 900px;
    margin: 0 auto;
    background: white;
    min-height: 100vh;
}

/* Header */
.header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    padding: 2rem;
    color: white;
    box-shadow: var(--shadow-lg);
}

.logo-container {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.logo-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    backdrop-filter: blur(10px);
}

.logo-icon svg {
    color: white;
}

.logo-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0;
}

.logo-tagline {
    font-size: 0.875rem;
    opacity: 0.9;
    margin: 0.25rem 0 0 0;
}

/* Loading & Error States */
.loading-container,
.error-container {
    padding: 4rem 2rem;
    text-align: center;
}

.spinner-large {
    width: 48px;
    height: 48px;
    border: 4px solid var(--gray-200);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

.loading-text {
    color: var(--gray-600);
    font-size: 1rem;
}

.error-icon {
    color: var(--error);
    margin-bottom: 1rem;
}

.error-text {
    color: var(--gray-700);
    font-size: 1rem;
}

/* Main Form */
.main-form {
    padding: 2rem;
}

.form-header {
    margin-bottom: 2rem;
}

.form-title {
    font-size: 1.875rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0 0 0.5rem 0;
}

.form-subtitle {
    color: var(--gray-600);
    font-size: 1rem;
    margin: 0;
}

.form-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 2px solid var(--gray-100);
}

.form-section:last-of-type {
    border-bottom: none;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--gray-800);
    margin: 0 0 1.25rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-title::before {
    content: '';
    width: 4px;
    height: 20px;
    background: var(--primary);
    border-radius: 2px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
}

.form-group {
    position: relative;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--gray-700);
    font-size: 0.875rem;
}

.label-icon {
    color: var(--primary);
}

.required {
    color: var(--error);
}

.input-wrapper {
    position: relative;
}

.form-input,
.form-textarea {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid var(--gray-200);
    border-radius: 0.75rem;
    font-size: 1rem;
    transition: all 0.2s ease;
    font-family: inherit;
    background: white;
    color: var(--gray-900);
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
}

.form-input:hover:not(:focus) {
    border-color: var(--gray-300);
}

.input-readonly {
    background: var(--gray-50);
    color: var(--gray-600);
    cursor: not-allowed;
}

.input-error {
    border-color: var(--error) !important;
    background: #fef2f2;
}

.form-textarea {
    resize: vertical;
    min-height: 120px;
}

.helper-text {
    display: block;
    color: var(--gray-500);
    font-size: 0.813rem;
    margin-top: 0.375rem;
}

.error-message {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    color: var(--error);
    font-size: 0.813rem;
    margin-top: 0.375rem;
    font-weight: 500;
}

.error-message::before {
    content: '⚠';
    font-size: 0.875rem;
}

.char-counter {
    text-align: right;
    color: var(--gray-400);
    font-size: 0.75rem;
    margin-top: 0.375rem;
}

/* Autocomplete */
.autocomplete-wrapper {
    position: relative;
}

.autocomplete-dropdown {
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 0;
    right: 0;
    background: white;
    border: 2px solid var(--gray-200);
    border-radius: 0.75rem;
    box-shadow: var(--shadow-lg);
    max-height: 240px;
    overflow-y: auto;
    z-index: 50;
}

.autocomplete-item {
    padding: 0.875rem 1rem;
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-bottom: 1px solid var(--gray-100);
}

.autocomplete-item:last-child {
    border-bottom: none;
}

.autocomplete-item:hover {
    background: var(--gray-50);
}

.autocomplete-item.active {
    background: #eff6ff;
    color: var(--primary);
}

.autocomplete-icon {
    color: var(--gray-400);
    flex-shrink: 0;
}

.autocomplete-item:hover .autocomplete-icon,
.autocomplete-item.active .autocomplete-icon {
    color: var(--primary);
}

/* Checkbox Card */
.checkbox-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 2px solid #bae6fd;
    border-radius: 0.75rem;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    transition: all 0.2s ease;
}

.checkbox-card:hover {
    border-color: var(--primary-light);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    cursor: pointer;
}

.checkbox-input {
    width: 20px;
    height: 20px;
    margin-top: 0.125rem;
    cursor: pointer;
    flex-shrink: 0;
    accent-color: var(--primary);
    border: 2px solid var(--gray-300);
    border-radius: 0.25rem;
}

.checkbox-content {
    flex: 1;
}

.checkbox-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.375rem;
}

.checkbox-icon {
    color: var(--primary);
}

.checkbox-title {
    color: var(--gray-800);
    font-size: 0.938rem;
    font-weight: 600;
}

.checkbox-description {
    color: var(--gray-600);
    font-size: 0.813rem;
    margin: 0;
    line-height: 1.5;
}

/* Submit Button */
.submit-button {
    width: 100%;
    padding: 1.125rem 1.5rem;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    border: none;
    border-radius: 0.75rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
    margin-bottom: 1rem;
}

.submit-button:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.5);
}

.submit-button:active:not(:disabled) {
    transform: translateY(0);
}

.submit-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.button-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.625rem;
}

.button-icon {
    flex-shrink: 0;
}

.spinner {
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.help-text {
    text-align: center;
    color: var(--gray-600);
    font-size: 0.875rem;
}

.help-link {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
}

.help-link:hover {
    color: var(--primary-dark);
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
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    z-index: 9999;
}

.modal-content {
    background: white;
    padding: 2.5rem 2rem;
    border-radius: 1.25rem;
    max-width: 440px;
    width: 100%;
    text-align: center;
    box-shadow: var(--shadow-xl);
}

.success-animation {
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: center;
}

.success-checkmark {
    animation: scaleIn 0.5s ease-out;
}

.success-circle {
    stroke-dasharray: 226;
    stroke-dashoffset: 226;
    animation: drawCircle 0.6s ease-out forwards;
}

.success-check {
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: drawCheck 0.4s 0.4s ease-out forwards;
}

@keyframes scaleIn {
    from { transform: scale(0.8); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

@keyframes drawCircle {
    to { stroke-dashoffset: 0; }
}

@keyframes drawCheck {
    to { stroke-dashoffset: 0; }
}

.modal-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0 0 1rem 0;
}

.modal-text {
    color: var(--gray-600);
    line-height: 1.6;
    margin-bottom: 1.5rem;
    font-size: 1rem;
}

.modal-reference {
    background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
    padding: 1rem 1.25rem;
    border-radius: 0.75rem;
    margin: 1.5rem 0;
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.reference-label {
    font-size: 0.75rem;
    color: var(--gray-500);
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.05em;
}

.reference-value {
    font-family: 'Courier New', monospace;
    font-weight: 700;
    color: var(--primary);
    font-size: 1.125rem;
}

.modal-button {
    padding: 0.875rem 2rem;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 0.625rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.modal-button:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: var(--shadow);
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

.modal-enter-active .modal-content {
    animation: modalSlideIn 0.3s ease-out;
}

.modal-leave-active .modal-content {
    animation: modalSlideOut 0.3s ease-in;
}

@keyframes modalSlideIn {
    from {
        transform: translateY(30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes modalSlideOut {
    from {
        transform: translateY(0);
        opacity: 1;
    }
    to {
        transform: translateY(20px);
        opacity: 0;
    }
}

/* Responsive Design */
@media (min-width: 640px) {
    .page-container {
        padding: 2rem;
    }

    .form-wrapper {
        border-radius: 1.25rem;
        overflow: hidden;
        box-shadow: var(--shadow-xl);
        min-height: auto;
    }

    .form-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }
}

@media (min-width: 768px) {
    .main-form {
        padding: 3rem;
    }

    .form-title {
        font-size: 2.25rem;
    }

    .form-subtitle {
        font-size: 1.125rem;
    }
}

@media (min-width: 1024px) {
    .form-wrapper {
        max-width: 900px;
    }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Focus States for Keyboard Navigation */
.form-input:focus-visible,
.form-textarea:focus-visible,
.submit-button:focus-visible,
.checkbox-input:focus-visible {
    outline: 3px solid var(--primary);
    outline-offset: 2px;
}

/* Print Styles */
@media print {
    .page-container {
        background: white;
        padding: 0;
    }

    .submit-button,
    .help-text,
    .modal-overlay {
        display: none;
    }
}