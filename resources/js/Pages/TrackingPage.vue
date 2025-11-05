<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';

// Props
const props = defineProps({
  waybillNumber: {
    type: String,
    default: 'EACQDFEBTZ41498'
  },
  initialStatus: {
    type: String,
    default: 'order_received' // order_received, confirmed, in_transit, waiting_pickup, delivered
  }
});

// State
const waybillInput = ref(props.waybillNumber);
const isLoading = ref(false);
const trackingData = ref({
  waybillNumber: props.waybillNumber,
  currentStatus: props.initialStatus,
  estimatedDelivery: '2025-11-05',
  orderDate: '2025-11-01',
  customerName: 'John Doe',
  customerPhone: '+254 712 345 678',
  deliveryAddress: 'Westlands, Nairobi',
  timeline: [
    {
      status: 'order_received',
      label: 'Order received',
      date: '2025-11-01 10:30 AM',
      completed: true,
      description: 'Your order has been received and is being processed'
    },
    {
      status: 'confirmed',
      label: 'Confirmed',
      date: null,
      completed: false,
      description: 'Order details confirmed and ready for dispatch'
    },
    {
      status: 'in_transit',
      label: 'In transit',
      date: null,
      completed: false,
      description: 'Package is on the way to you'
    },
    {
      status: 'waiting_pickup',
      label: 'Waiting pickup',
      date: null,
      completed: false,
      description: 'Package ready for pickup at delivery location'
    },
    {
      status: 'delivered',
      label: 'Delivered',
      date: null,
      completed: false,
      description: 'Successfully delivered to customer'
    }
  ]
});

const showDetails = ref(false);
const error = ref('');

// Tracking statuses configuration
const trackingStatuses = {
  order_received: 0,
  confirmed: 1,
  in_transit: 2,
  waiting_pickup: 3,
  delivered: 4
};

// Computed
const currentStatusIndex = computed(() => {
  return trackingStatuses[trackingData.value.currentStatus] || 0;
});

const progressPercentage = computed(() => {
  return (currentStatusIndex.value / 4) * 100;
});

const isDelivered = computed(() => {
  return trackingData.value.currentStatus === 'delivered';
});

// Methods
const updateTimelineStatus = () => {
  const currentIndex = currentStatusIndex.value;
  trackingData.value.timeline = trackingData.value.timeline.map((item, index) => ({
    ...item,
    completed: index <= currentIndex
  }));
};

const trackOrder = async () => {
  if (!waybillInput.value || waybillInput.value.trim().length < 5) {
    error.value = 'Please enter a valid waybill number';
    return;
  }

  error.value = '';
  isLoading.value = true;

  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1500));
    
    // Replace with actual API endpoint
    // const response = await fetch(`/api/tracking/${waybillInput.value}`);
    // const data = await response.json();
    
    // For demo purposes, update with mock data
    trackingData.value.waybillNumber = waybillInput.value;
    updateTimelineStatus();
    
  } catch (err) {
    error.value = 'Unable to fetch tracking information. Please try again.';
    console.error('Tracking error:', err);
  } finally {
    isLoading.value = false;
  }
};

const refreshTracking = async () => {
  isLoading.value = true;
  try {
    await new Promise(resolve => setTimeout(resolve, 1000));
    // Simulate status update - in real app, fetch from API
    updateTimelineStatus();
  } catch (err) {
    console.error('Refresh error:', err);
  } finally {
    isLoading.value = false;
  }
};

const copyWaybill = () => {
  navigator.clipboard.writeText(trackingData.value.waybillNumber);
  // You could add a toast notification here
};

const formatDate = (dateString) => {
  if (!dateString) return 'Pending';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Lifecycle
onMounted(() => {
  updateTimelineStatus();
});
</script>

<template>
  <Head title="Track Your Order" />
  
  <div class="page-container">
    <div class="tracking-wrapper">
      <!-- Header -->
      <header class="header">
        <div class="logo-container">
          <svg class="logo-icon" width="40" height="40" viewBox="0 0 40 40" fill="none">
            <rect x="5" y="8" width="30" height="24" rx="2" stroke="currentColor" stroke-width="2"/>
            <circle cx="15" cy="32" r="3" fill="currentColor"/>
            <circle cx="25" cy="32" r="3" fill="currentColor"/>
            <path d="M10 20h20" stroke="currentColor" stroke-width="2"/>
          </svg>
          <div class="logo-text">
            <h1 class="logo-title">CRM</h1>
            <p class="logo-tagline">Track Your Delivery</p>
          </div>
        </div>
      </header>

      <!-- Search Section -->
      <div class="search-section">
        <div class="search-container">
          <label for="waybill" class="search-label">Track waybill</label>
          <div class="search-input-wrapper">
            <input
              type="text"
              id="waybill"
              v-model="waybillInput"
              placeholder="Enter waybill number"
              class="search-input"
              @keyup.enter="trackOrder"
            />
            <button 
              @click="trackOrder" 
              class="search-button"
              :disabled="isLoading"
            >
              <svg v-if="!isLoading" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"/>
              </svg>
              <svg v-else class="spinner" width="20" height="20" viewBox="0 0 24 24">
                <circle class="spinner-track" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                <path class="spinner-head" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
              </svg>
            </button>
          </div>
          <p v-if="error" class="error-message">{{ error }}</p>
        </div>
      </div>

      <!-- Tracking Content -->
      <div class="tracking-content">
        <!-- Waybill Display -->
        <div class="waybill-card">
          <div class="waybill-header">
            <div>
              <p class="waybill-label">Waybill Number</p>
              <p class="waybill-number">{{ trackingData.waybillNumber }}</p>
            </div>
            <div class="waybill-actions">
              <button @click="copyWaybill" class="icon-button" title="Copy waybill">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/>
                  <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"/>
                </svg>
              </button>
              <button @click="refreshTracking" class="icon-button refresh-button" title="Refresh tracking" :class="{ 'spinning': isLoading }">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"/>
                </svg>
              </button>
            </div>
          </div>
          
          <!-- Delivery Info -->
          <div v-if="!isDelivered" class="delivery-info">
            <div class="info-item">
              <svg class="info-icon" width="18" height="18" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/>
              </svg>
              <div>
                <p class="info-label">Estimated Delivery</p>
                <p class="info-value">{{ formatDate(trackingData.estimatedDelivery) }}</p>
              </div>
            </div>
          </div>
          
          <div v-else class="delivered-banner">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="10" stroke="#10b981" stroke-width="2"/>
              <path d="M8 12l3 3 5-6" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Package Delivered Successfully!</span>
          </div>
        </div>

        <!-- Progress Tracker -->
        <div class="progress-tracker">
          <div class="progress-bar-container">
            <div class="progress-bar-bg"></div>
            <div class="progress-bar-fill" :style="{ width: progressPercentage + '%' }"></div>
          </div>
          
          <div class="progress-steps">
            <div 
              v-for="(step, index) in trackingData.timeline" 
              :key="step.status"
              class="progress-step"
              :class="{ 
                'step-completed': step.completed,
                'step-active': index === currentStatusIndex,
                'step-pending': !step.completed
              }"
            >
              <div class="step-icon-wrapper">
                <div class="step-icon">
                  <svg v-if="step.status === 'order_received'" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"/>
                  </svg>
                  <svg v-else-if="step.status === 'confirmed'" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                  </svg>
                  <svg v-else-if="step.status === 'in_transit'" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M4 16a1 1 0 011-1h1.5l.37-2.595A2 2 0 018.837 11h6.326a2 2 0 011.967 1.405L17.5 15H19a1 1 0 110 2h-1.05a3.5 3.5 0 11-6.9 0H8.05a3.5 3.5 0 11-3-2zm8 4a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm-6-1.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                  </svg>
                  <svg v-else-if="step.status === 'waiting_pickup'" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z"/>
                  </svg>
                  <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                  </svg>
                </div>
              </div>
              <div class="step-content">
                <p class="step-label">{{ step.label }}</p>
                <p v-if="step.date" class="step-date">{{ formatDate(step.date) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Timeline Details Toggle -->
        <button @click="showDetails = !showDetails" class="details-toggle">
          <span>{{ showDetails ? 'Hide' : 'Show' }} Details</span>
          <svg 
            width="20" 
            height="20" 
            viewBox="0 0 20 20" 
            fill="currentColor"
            :class="{ 'rotated': showDetails }"
          >
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
          </svg>
        </button>

        <!-- Detailed Timeline -->
        <Transition name="slide-fade">
          <div v-if="showDetails" class="timeline-details">
            <h3 class="details-title">Tracking Timeline</h3>
            <div class="timeline-list">
              <div 
                v-for="(step, index) in trackingData.timeline" 
                :key="step.status"
                class="timeline-item"
                :class="{ 'timeline-completed': step.completed }"
              >
                <div class="timeline-marker"></div>
                <div class="timeline-content-box">
                  <div class="timeline-header">
                    <h4 class="timeline-title">{{ step.label }}</h4>
                    <span class="timeline-status" :class="{ 'status-completed': step.completed }">
                      {{ step.completed ? 'Completed' : 'Pending' }}
                    </span>
                  </div>
                  <p class="timeline-description">{{ step.description }}</p>
                  <p v-if="step.date" class="timeline-time">{{ formatDate(step.date) }}</p>
                </div>
              </div>
            </div>

            <!-- Customer Info -->
            <div class="customer-info">
              <h3 class="info-section-title">Delivery Information</h3>
              <div class="info-grid">
                <div class="info-box">
                  <svg class="box-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                  </svg>
                  <div>
                    <p class="box-label">Customer Name</p>
                    <p class="box-value">{{ trackingData.customerName }}</p>
                  </div>
                </div>
                <div class="info-box">
                  <svg class="box-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                  </svg>
                  <div>
                    <p class="box-label">Phone Number</p>
                    <p class="box-value">{{ trackingData.customerPhone }}</p>
                  </div>
                </div>
                <div class="info-box info-box-full">
                  <svg class="box-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"/>
                  </svg>
                  <div>
                    <p class="box-label">Delivery Address</p>
                    <p class="box-value">{{ trackingData.deliveryAddress }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </Transition>

        <!-- Help Section -->
        <div class="help-section">
          <p class="help-text">
            Need assistance? Contact us at 
            <a href="tel:+254700000000" class="help-link">+254 700 000 000</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Reset & Variables */
* {
  box-sizing: border-box;
}

:root {
  --primary-color: #2563eb;
  --primary-dark: #1e40af;
  --primary-light: #3b82f6;
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
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.tracking-wrapper {
  max-width: 900px;
  margin: 0 auto;
  background: white;
  min-height: 100vh;
}

/* Header */
.header {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
  padding: 1.5rem 1.25rem;
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

.logo-title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.2;
}

.logo-tagline {
  margin: 0.25rem 0 0 0;
  font-size: 0.875rem;
  opacity: 0.95;
}

/* Search Section */
.search-section {
  background: linear-gradient(to bottom, var(--primary-color), transparent);
  padding: 2rem 1.25rem;
}

.search-container {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}

.search-label {
  display: block;
  font-weight: 600;
  color: var(--gray-700);
  margin-bottom: 0.75rem;
  font-size: 0.938rem;
}

.search-input-wrapper {
  display: flex;
  gap: 0.75rem;
}

.search-input {
  flex: 1;
  padding: 0.875rem 1rem;
  border: 2px solid var(--gray-200);
  border-radius: 0.5rem;
  font-size: 1rem;
  transition: all 0.2s ease;
  font-family: 'Courier New', monospace;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.search-button {
  padding: 0 1.5rem;
  background: var(--primary-color);
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.search-button:hover:not(:disabled) {
  background: var(--primary-dark);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.search-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Tracking Content */
.tracking-content {
  padding: 1.5rem 1.25rem;
}

/* Waybill Card */
.waybill-card {
  background: white;
  border: 2px solid var(--gray-200);
  border-radius: 1rem;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.waybill-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--gray-200);
}

.waybill-label {
  font-size: 0.813rem;
  color: var(--gray-500);
  margin: 0 0 0.25rem 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.waybill-number {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--gray-900);
  margin: 0;
  font-family: 'Courier New', monospace;
  letter-spacing: 1px;
}

.waybill-actions {
  display: flex;
  gap: 0.5rem;
}

.icon-button {
  width: 2.5rem;
  height: 2.5rem;
  border: 2px solid var(--gray-200);
  background: white;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  color: var(--gray-600);
}

.icon-button:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
  transform: translateY(-2px);
}

.refresh-button.spinning svg {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Delivery Info */
.delivery-info {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: var(--gray-50);
  border-radius: 0.5rem;
}

.info-icon {
  color: var(--primary-color);
  flex-shrink: 0;
}

.info-label {
  font-size: 0.75rem;
  color: var(--gray-500);
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-value {
  font-size: 0.938rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0.125rem 0 0 0;
}

/* Delivered Banner */
.delivered-banner {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: #d1fae5;
  border-radius: 0.5rem;
  color: #065f46;
  font-weight: 600;
}

/* Progress Tracker */
.progress-tracker {
  margin-bottom: 2rem;
}

.progress-bar-container {
  position: relative;
  height: 4px;
  margin-bottom: 2rem;
}

.progress-bar-bg {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 100%;
  background: var(--gray-200);
  border-radius: 2px;
}

.progress-bar-fill {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
  border-radius: 2px;
  transition: width 0.6s ease;
}

.progress-steps {
  display: flex;
  justify-content: space-between;
  gap: 0.5rem;
}

.progress-step {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.step-icon-wrapper {
  margin-bottom: 0.75rem;
}

.step-icon {
  width: 3.5rem;
  height: 3.5rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  border: 3px solid var(--gray-200);
  background: white;
  color: var(--gray-400);
}

.step-completed .step-icon {
  border-color: var(--primary-color);
  background: var(--primary-color);
  color: white;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.step-active .step-icon {
  border-color: var(--primary-color);
  color: var(--primary-color);
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 0 0 0 10px rgba(37, 99, 235, 0);
  }
}

.step-content {
  width: 100%;
}

.step-label {
  font-size: 0.813rem;
  font-weight: 600;
  color: var(--gray-700);
  margin: 0 0 0.25rem 0;
}

.step-pending .step-label {
  color: var(--gray-400);
}

.step-date {
  font-size: 0.688rem;
  color: var(--gray-500);
  margin: 0;
}

/* Details Toggle */
.details-toggle {
  width: 100%;
  padding: 0.875rem;
  background: var(--gray-50);
  border: 2px solid var(--gray-200);
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-weight: 600;
  color: var(--gray-700);
  cursor: pointer;
  transition: all 0.2s ease;
  margin-bottom: 1.5rem;
}

.details-toggle:hover {
  background: white;
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.details-toggle svg {
  transition: transform 0.3s ease;
}

.details-toggle svg.rotated {
  transform: rotate(180deg);
}

/* Timeline Details */
.timeline-details {
  margin-bottom: 2rem;
}

.details-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--gray-900);
  margin: 0 0 1.5rem 0;
}

.timeline-list {
  position: relative;
  padding-left: 2rem;
}

.timeline-list::before {
  content: '';
  position: absolute;
  left: 0.625rem;
  top: 0;
  bottom: 0;
  width: 2px;
  background: var(--gray-200);
}

.timeline-item {
  position: relative;
  padding-bottom: 2rem;
}

.timeline-item:last-child {
  padding-bottom: 0;
}

.timeline-marker {
  position: absolute;
  left: -1.375rem;
  top: 0.25rem;
  width: 0.75rem;
  height: 0.75rem;
  border-radius: 50%;
  background: var(--gray-300);
  border: 3px solid white;
  box-shadow: 0 0 0 2px var(--gray-200);
  transition: all 0.3s ease;
}

.timeline-completed .timeline-marker {
  background: var(--primary-color);
  box-shadow: 0 0 0 2px var(--primary-color);
}

.timeline-content-box {
  background: white;
  border: 2px solid var(--gray-200);
  border-radius: 0.75rem;
  padding: 1rem;
  transition: all 0.3s ease;
}

.timeline-completed .timeline-content-box {
  border-color: var(--primary-color);
  background: #eff6ff;
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.timeline-title {
  font-size: 0.938rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0;
}

.timeline-status {
  font-size: 0.75rem;
  padding: 0.25rem 0.625rem;
  border-radius: 1rem;
  background: var(--gray-200);
  color: var(--gray-600);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.timeline-status.status-completed {
  background: #d1fae5;
  color: #065f46;
}

.timeline-description {
  font-size: 0.813rem;
  color: var(--gray-600);
  margin: 0 0 0.5rem 0;
  line-height: 1.5;
}

.timeline-time {
  font-size: 0.75rem;
  color: var(--gray-500);
  margin: 0;
}

/* Customer Info */
.customer-info {
  background: var(--gray-50);
  border-radius: 1rem;
  padding: 1.5rem;
  margin-top: 2rem;
}

.info-section-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--gray-900);
  margin: 0 0 1rem 0;
}

.info-grid {
  display: grid;
  gap: 1rem;
}

.info-box {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem;
  background: white;
  border-radius: 0.5rem;
  border: 1px solid var(--gray-200);
}

.info-box-full {
  grid-column: 1 / -1;
}

.box-icon {
  color: var(--primary-color);
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.box-label {
  font-size: 0.75rem;
  color: var(--gray-500);
  margin: 0 0 0.25rem 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.box-value {
  font-size: 0.938rem;
  font-weight: 600;
  color: var(--gray-900);
  margin: 0;
}

/* Help Section */
.help-section {
  text-align: center;
  padding: 2rem 0;
  border-top: 1px solid var(--gray-200);
  margin-top: 2rem;
}

.help-text {
  color: var(--gray-600);
  font-size: 0.938rem;
  margin: 0;
}

.help-link {
  color: var(--primary-color);
  text-decoration: none;
  font-weight: 600;
}

.help-link:hover {
  text-decoration: underline;
}

/* Error Message */
.error-message {
  display: block;
  color: var(--error-color);
  font-size: 0.813rem;
  margin-top: 0.5rem;
  font-weight: 500;
}

/* Spinner */
.spinner {
  animation: spin 1s linear infinite;
}

.spinner-track {
  opacity: 0.25;
}

/* Transitions */
.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
  transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateY(-20px);
  opacity: 0;
}

/* Responsive Design */
@media (min-width: 640px) {
  .page-container {
    padding: 1.5rem;
  }

  .tracking-wrapper {
    border-radius: 1.5rem;
    overflow: hidden;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    min-height: auto;
  }

  .header {
    padding: 2rem 2.5rem;
  }

  .search-section {
    padding: 2.5rem 2.5rem 3rem;
  }

  .tracking-content {
    padding: 2rem 2.5rem;
  }

  .waybill-card {
    padding: 2rem;
  }

  .progress-steps {
    gap: 1rem;
  }

  .step-label {
    font-size: 0.875rem;
  }

  .info-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 768px) {
  .logo-title {
    font-size: 1.875rem;
  }

  .waybill-number {
    font-size: 1.5rem;
  }

  .step-icon {
    width: 4rem;
    height: 4rem;
  }
}

@media (min-width: 1024px) {
  .tracking-wrapper {
    max-width: 1000px;
  }
}

/* Touch Devices */
@media (hover: none) and (pointer: coarse) {
  .icon-button,
  .search-button {
    min-height: 48px;
    min-width: 48px;
  }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>