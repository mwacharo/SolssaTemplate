<template>
  <v-dialog 
    v-model="orderStore.bulkActionDialog" 
    max-width="600px"
    persistent
    class="elevation-0"
  >
    <v-card class="dialog-card overflow-hidden">
      <!-- Header -->
      <div class="dialog-header">
        <div class="flex items-center gap-3">
          <div class="header-icon">
            <svg v-if="orderStore.dialogType === 'assignDelivery'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <svg v-else-if="orderStore.dialogType === 'assignCallCentre'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
            <svg v-else-if="orderStore.dialogType === 'delete'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            <svg v-else-if="orderStore.dialogType === 'status'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div>
            <h2 class="dialog-title">{{ orderStore.dialogTitle }}</h2>
            <!-- <p class="dialog-subtitle">{{ orderStore.selectedOrders.length }} order(s) selected</p> -->
          </div>
        </div>
        <button 
          @click="close" 
          class="close-button"
          :disabled="orderStore.loading.bulkAction"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="dialog-content">
        <!-- Assign Delivery Person -->
        <div v-if="orderStore.dialogType === 'assignDelivery'" class="form-section">
          <div class="form-group">
            <label class="form-label">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              Delivery Person
            </label>
            <div class="select-wrapper">
                <v-autocomplete
                v-model="form.deliveryPerson"
                :items="orderStore.riderOptions"
                item-title="name"
                item-value="id"
                class="form-select"
                :class="{ 'error': errors.deliveryPerson }"
                placeholder="Choose a delivery person..."
                clearable
                dense
                />
              <div class="select-arrow">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </div>
            </div>
            <p v-if="errors.deliveryPerson" class="error-message">{{ errors.deliveryPerson }}</p>
          </div>
        </div>

        <!-- Assign Call Centre Agent -->
        <div v-if="orderStore.dialogType === 'assignCallCentre'" class="form-section">
          <div class="form-group">
            <label class="form-label">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              Call Centre Agent
            </label>
            <div class="select-wrapper">
              <v-autocomplete
                v-model="form.callCentreAgent"
                :items="orderStore.agentOptions"
                item-title="name"
                item-value="id"
                class="form-select"
                :class="{ 'error': errors.callCentreAgent }"
                placeholder="Choose an agent..."
                clearable
                dense
              />
              <div class="select-arrow">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </div>
            </div>
            <p v-if="errors.callCentreAgent" class="error-message">{{ errors.callCentreAgent }}</p>
          </div>
        </div>

        <!-- Bulk Delete -->
        <div v-if="orderStore.dialogType === 'delete'" class="form-section">
          <div class="warning-card">
            <div class="flex items-start gap-3">
              <div class="warning-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
              </div>
              <div>
                <h3 class="warning-title">Confirm Deletion</h3>
                <p class="warning-text">
                  You are about to delete <strong>{{ orderStore.selectedOrder ? 1 : 0 }} order(s)</strong>. 
                  This action is permanent and cannot be undone.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Change Status -->
        <div v-if="orderStore.dialogType === 'status'" class="form-section">
          <div class="form-grid">
            <!-- Status -->
            <div class="form-group">
              <label class="form-label">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Status
              </label>
              <div class="select-wrapper">
                <v-autocomplete
                  v-model="form.status"
                  :items="statusOptionsStore"
                  item-value="id"
                  item-title="name"
                  class="form-select"
                  :class="{ 'error': errors.status }"
                  placeholder="Select new status..."
                  clearable
                  dense
                />
                <div class="select-arrow">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                  </svg>
                </div>
              </div>
              <p v-if="errors.status" class="error-message">{{ errors.status }}</p>
            </div>

            <!-- Zone -->
            <div class="form-group">
              <label class="form-label">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              </svg>
              Zone
              </label>
              <div class="select-wrapper">
              <v-autocomplete
                v-model="form.zone"
                :items="zoneOptions"
                item-title="name"
                item-value="id"
                class="form-select"
                placeholder="Choose a zone..."
                clearable
                dense
              />
              <div class="select-arrow">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </div>
              </div>
            </div>

            <!-- City -->
            <div class="form-group">
              <label class="form-label">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
              City
              </label>
              <div class="select-wrapper">
              <v-autocomplete
                v-model="form.city"
                :items="cityOptions"
                item-title="name"
                item-value="id"
                class="form-select"
                placeholder="Choose a city..."
                clearable
                dense
              />
              <div class="select-arrow">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </div>
              </div>
            </div>
            </div>

          <!-- Notes -->
          <div class="form-group">
            <label class="form-label">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              Notes (Optional)
            </label>
            <textarea 
              v-model="form.notes" 
              class="form-textarea" 
              rows="3"
              placeholder="Add any additional notes for this status change..."
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Footer Actions -->
      <div class="dialog-footer">
        <div class="action-buttons">
          <button 
            @click="close" 
            class="btn-cancel"
            :disabled="orderStore.loading.bulkAction"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Cancel
          </button>
          
          <button 
            @click="confirm" 
            class="btn-confirm"
            :class="{
              'btn-danger': orderStore.dialogType === 'delete'
            }"
            :disabled="orderStore.loading.bulkAction"
          >
            <div v-if="orderStore.loading.bulkAction" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Processing...
            </div>
            <div v-else class="flex items-center">
              <svg v-if="orderStore.dialogType === 'delete'" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
              <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              {{ orderStore.dialogType === 'delete' ? 'Delete Orders' : 'Confirm Action' }}
            </div>
          </button>
        </div>
      </div>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { reactive, ref,computed } from "vue";
import { useOrderStore } from "@/stores/orderStore";

const orderStore = useOrderStore();
const errors = ref({});

const form = reactive({
  deliveryPerson: "",
  callCentreAgent: "",
  status: "",
  notes: "",
  zone: "",
  city: "",
});
// const vendorOptions = computed(() => orderStore.vendorOptions)
// const categoryOptions = computed(() => orderStore.categoryOptions)
const cityOptions = computed(() => orderStore.cityOptions)
const zoneOptions = computed(() => orderStore.zoneOptions)
// const warehouseOptions = computed(() => orderStore.warehouseOptions)
const statusOptionsStore = computed(() => orderStore.statusOptions)
const agentOptions = computed(() => orderStore.agentOptions);
const riderOptions = computed(() => orderStore.riderOptions);

const resetForm = () => {
  form.deliveryPerson = "";
  form.callCentreAgent = "";
  form.status = "";
  form.notes = "";
  form.zone = "";
  form.city = "";
  errors.value = {};
};

const validateForm = () => {
  errors.value = {};
  let isValid = true;

  if (orderStore.dialogType === "assignDelivery" && !form.deliveryPerson) {
    errors.value.deliveryPerson = "Please select a delivery person";
    isValid = false;
  }

  if (orderStore.dialogType === "assignCallCentre" && !form.callCentreAgent) {
    errors.value.callCentreAgent = "Please select a call centre agent";
    isValid = false;
  }

  if (orderStore.dialogType === "status" && !form.status) {
    errors.value.status = "Please select a status";
    isValid = false;
  }

  return isValid;
};

const close = () => {
  orderStore.closeBulkActionDialog();
  resetForm();
};




function confirm() {
  if (!validateForm()) return;

  orderStore.handleBulkAction({
    type: orderStore.dialogType,
    data: { ...form },
    orders: orderStore.selectedOrders,
  });

  resetForm();
}
</script>

<style scoped>
.dialog-card {
  @apply bg-white rounded-xl shadow-2xl;
}

.dialog-header {
  @apply flex items-center justify-between p-6 border-b border-gray-100;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.header-icon {
  @apply w-10 h-10 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center;
}

.dialog-title {
  @apply text-xl font-bold text-white;
}

.dialog-subtitle {
  @apply text-sm text-white/80 mt-1;
}

.close-button {
  @apply w-8 h-8 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center text-white hover:bg-white/30 transition-all duration-200 disabled:opacity-50;
}

.dialog-content {
  @apply p-6 max-h-96 overflow-y-auto;
}

.form-section {
  @apply space-y-6;
}

.form-group {
  @apply space-y-2;
}

.form-label {
  @apply flex items-center text-sm font-semibold text-gray-700;
}

.select-wrapper {
  @apply relative;
}

.form-select {
  @apply w-full appearance-none bg-white border border-gray-300 rounded-xl px-4 py-3 pr-10 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200;
}

.form-select.error {
  @apply border-red-500 focus:ring-red-500;
}

.select-arrow {
  @apply absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none text-gray-400;
}

.form-input {
  @apply w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200;
}

.form-textarea {
  @apply w-full bg-white border border-gray-300 rounded-xl px-4 py-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none transition-all duration-200;
}

.form-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-6;
}

.warning-card {
  @apply bg-red-50 border border-red-200 rounded-xl p-4;
}

.warning-icon {
  @apply flex-shrink-0 w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-red-600;
}

.warning-title {
  @apply text-lg font-semibold text-red-800 mb-2;
}

.warning-text {
  @apply text-red-700 leading-relaxed;
}

.error-message {
  @apply text-red-500 text-sm flex items-center gap-1;
}

.dialog-footer {
  @apply p-6 bg-gray-50 border-t border-gray-100;
}

.action-buttons {
  @apply flex justify-end gap-3;
}

/* Orange Cancel Button */
.btn-cancel {
  @apply inline-flex items-center px-6 py-3 rounded-xl text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg hover:shadow-xl;
  background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
}

.btn-cancel:hover:not(:disabled) {
  background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
  transform: translateY(-1px);
}

.btn-cancel:focus {
  @apply ring-orange-500;
}

/* Green Confirm Button */
.btn-confirm {
  @apply inline-flex items-center px-6 py-3 rounded-xl text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg hover:shadow-xl;
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
}

.btn-confirm:hover:not(:disabled) {
  background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
  transform: translateY(-1px);
}

.btn-confirm:focus {
  @apply ring-green-500;
}

/* Red Delete Button Override */
.btn-confirm.btn-danger {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.btn-confirm.btn-danger:hover:not(:disabled) {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
}

.btn-confirm.btn-danger:focus {
  @apply ring-red-500;
}
</style>