<template>
  <v-dialog v-model="dialog" max-width="1400" persistent scrollable>
    <v-card class="order-details-card">
      <!-- Header -->
      <v-card-title class="pa-6 pb-4 gradient-header">
        <div class="d-flex align-center w-100">
          <div class="d-flex align-center">
            <v-avatar size="48" color="primary" class="me-4">
              <v-icon :icon="isCreateMode ? 'mdi-plus' : 'mdi-package-variant'" size="24" />
            </v-avatar>
            <div>
              <h2 class="text-h5 mb-1 text-white">
                {{ isCreateMode ? 'Create New Order' : (order?.order_no || 'Loading...') }}
              </h2>
              <div class="d-flex align-center gap-2">
                <v-chip v-if="order && !isCreateMode" :color="getStatusColor(order.status)" variant="flat" size="small"
                  class="font-weight-medium">
                  {{ order.status }}
                </v-chip>
                <v-chip v-if="isCreateMode" color="info" variant="flat" size="small">
                  Draft
                </v-chip>
                <v-chip v-if="hasUnsavedChanges" color="warning" variant="flat" size="small">
                  <v-icon start size="14">mdi-circle</v-icon>
                  Unsaved Changes
                </v-chip>
              </div>
            </div>
          </div>
          <div class="d-flex align-center gap-2">
            <v-btn v-if="!isCreateMode" icon="mdi-refresh" variant="text" size="small" color="white"
              @click="refreshOrder" :loading="isLoading" />
            <v-btn icon="mdi-close" variant="text" size="small" color="white" @click="closeDialog" />
          </div>
        </div>
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-0">
        <div v-if="(!order && !isCreateMode) || isLoading" class="pa-8 text-center">
          <v-progress-circular indeterminate color="primary" size="64" />
          <p class="text-h6 mt-4">{{ isCreateMode ? 'Preparing new order...' : 'Loading order details...' }}</p>
        </div>

        <div v-else>
          <!-- Tabs -->
          <v-tabs v-model="activeTab" color="primary" class="border-b bg-surface">
            <v-tab value="overview" prepend-icon="mdi-view-dashboard">
              <span class="font-weight-medium">Overview</span>
            </v-tab>
            <v-tab value="items" prepend-icon="mdi-cart">
              <span class="font-weight-medium">Items</span>
              <v-chip size="x-small" variant="flat" color="primary" class="ml-2">
                {{ (currentOrder.orderItems || []).length }}
              </v-chip>
            </v-tab>
            <v-tab value="charges" prepend-icon="mdi-currency-usd">
              <span class="font-weight-medium">Charges</span>
            </v-tab>
            <v-tab value="client" prepend-icon="mdi-account">
              <span class="font-weight-medium">Client</span>
            </v-tab>
            <v-tab value="fulfillment" prepend-icon="mdi-truck-delivery">
              <span class="font-weight-medium">Fulfillment</span>
            </v-tab>
          </v-tabs>

          <v-tabs-window v-model="activeTab">
            <!-- Overview Tab -->
            <v-tabs-window-item value="overview" class="pa-6">
              <v-row>
                <!-- Order Summary -->
                <v-col cols="12" lg="8">
                  <v-card variant="flat" class="h-100 elevation-1" style="background: #f8fafc;">
                    <v-card-title class="d-flex align-center justify-space-between pb-2" style="border-bottom: 1px solid #e3e8ee;">
                      <div class="d-flex align-center">
                        <v-icon icon="mdi-clipboard-text" class="me-2" color="primary" />
                        <span class="font-weight-bold text-primary">Order Details</span>
                      </div>
                      <div class="d-flex gap-2" v-if="!isCreateMode">
                        <v-btn v-if="!editingOrder" color="primary" variant="tonal" size="small"
                          @click="startEditingOrder">
                          <v-icon start>mdi-pencil</v-icon>
                          Edit
                        </v-btn>
                        <div v-else class="d-flex gap-2">
                          <v-btn color="success" variant="flat" size="small" @click="saveOrderChanges"
                            :loading="saving">
                            <v-icon start>mdi-check</v-icon>
                            Save
                          </v-btn>
                          <v-btn variant="tonal" size="small" @click="cancelOrderEdit">
                            Cancel
                          </v-btn>
                        </div>
                      </div>
                    </v-card-title>
                    <v-card-text>
                      <v-row>
                        <v-col cols="12" md="6">
                          <div class="info-grid">
                            <div class="info-item">
                              <div class="label">Order Number</div>
                              <v-text-field
                                v-if="isCreateMode || editingOrder"
                                v-model="orderEdit.order_no"
                                variant="solo"
                                density="comfortable"
                                placeholder="AUTO-GENERATED"
                                :disabled="!isCreateMode"
                                hide-details
                              />
                              <div v-else class="value">{{ currentOrder.order_no }}</div>
                            </div>
                            <div class="info-item">
                              <div class="label">Reference</div>
                              <v-text-field
                                v-if="isCreateMode || editingOrder"
                                v-model="orderEdit.reference"
                                variant="solo"
                                density="comfortable"
                                placeholder="Optional reference"
                                hide-details
                              />
                              <div v-else class="value">{{ currentOrder.reference || '-' }}</div>
                            </div>
                            <div class="info-item">
                              <div class="label">Platform</div>
                              <v-select
                                v-if="isCreateMode || editingOrder"
                                v-model="orderEdit.platform"
                                :items="platformOptions"
                                variant="solo"
                                density="comfortable"
                                hide-details
                              />
                              <v-chip v-else variant="tonal" size="small" color="primary">{{ currentOrder.platform }}</v-chip>
                            </div>
                            <div class="info-item">
                              <div class="label">Status</div>
                              <v-select
                                v-if="isCreateMode || editingOrder"
                                v-model="orderEdit.status"
                                :items="statusOptions"
                                variant="solo"
                                density="comfortable"
                                hide-details
                              />
                              <v-chip v-else :color="getStatusColor(currentOrder.status)" variant="flat" size="small">
                                {{ currentOrder.status || 'Pending' }}
                              </v-chip>
                            </div>
                          </div>
                        </v-col>
                        <v-col cols="12" md="6">
                          <div class="info-grid">
                            <div class="info-item">
                              <div class="label">Client</div>
                              <v-autocomplete
                                v-if="isCreateMode || editingOrder"
                                v-model="orderEdit.client_id"
                                :items="clientOptions"
                                item-title="name"
                                item-value="id"
                                variant="solo"
                                density="comfortable"
                                clearable
                                :loading="loadingClients"
                                @update:search="searchClients"
                                @update:model-value="onClientChange"
                                hide-details
                              />
                              <div v-else class="value">{{ currentOrder.client?.name || '-' }}</div>
                            </div>
                            <div class="info-item">
                              <div class="label">Vendor</div>
                              <v-autocomplete
                                v-if="isCreateMode || editingOrder"
                                v-model="orderEdit.vendor_id"
                                :items="vendorOptions"
                                item-title="company_name"
                                item-value="id"
                                variant="solo"
                                density="comfortable"
                                clearable
                                :loading="loadingVendors"
                                @update:model-value="onVendorChange"
                                hide-details
                              />
                              <div v-else class="value">{{ currentOrder.vendor.name?.company_name || '-' }}</div>
                            </div>
                            <div class="info-item">
                              <div class="label">Delivery Date</div>
                              <v-text-field
                                v-if="isCreateMode || editingOrder"
                                v-model="orderEdit.delivery_date"
                                type="date"
                                variant="solo"
                                density="comfortable"
                                hide-details
                              />
                              <div v-else class="value">{{ formatDate(currentOrder.delivery_date) }}</div>
                            </div>
                            <div class="info-item">
                              <div class="label">Weight (kg)</div>
                              <v-text-field
                                v-if="isCreateMode || editingOrder"
                                v-model="orderEdit.weight"
                                type="number"
                                step="0.1"
                                variant="solo"
                                density="comfortable"
                                hide-details
                              />
                              <div v-else class="value">{{ currentOrder.weight || 0 }} kg</div>
                            </div>
                          </div>
                        </v-col>
                      </v-row>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- Quick Actions -->
                <v-col cols="12" lg="4">
                  <v-card variant="flat" class="h-100 elevation-1" style="background: linear-gradient(135deg, #e3f2fd 0%, #f8fafc 100%);">
                    <v-card-title class="pb-2" style="border-bottom: 1px solid #e3e8ee;">
                      <v-icon icon="mdi-flash" class="me-2" color="primary" />
                      <span class="font-weight-bold text-primary">{{ isCreateMode ? 'Create Actions' : 'Quick Actions' }}</span>
                    </v-card-title>
                    <v-card-text>
                      <div class="d-flex flex-column gap-3">
                        <v-btn
                          v-if="isCreateMode"
                          color="success"
                          variant="flat"
                          block
                          @click="createOrder"
                          :loading="saving"
                          :disabled="!canCreateOrder"
                          class="rounded-pill"
                          style="font-weight:600;"
                        >
                          <v-icon start>mdi-plus</v-icon>
                          Create Order
                        </v-btn>
                        <template v-else>
                          <v-btn
                            color="primary"
                            variant="flat"
                            block
                            @click="callClient"
                            :disabled="!currentOrder.client?.phone_number"
                            class="rounded-pill"
                            style="font-weight:600;"
                          >
                            <v-icon start>mdi-phone</v-icon>
                            Call Client
                          </v-btn>
                          <v-btn
                            color="success"
                            variant="tonal"
                            block
                            @click="updateStatus('confirmed')"
                            class="rounded-pill"
                            style="font-weight:600;"
                          >
                            <v-icon start>mdi-check-circle</v-icon>
                            Confirm Order
                          </v-btn>
                          <v-btn
                            color="info"
                            variant="tonal"
                            block
                            @click="printOrder"
                            class="rounded-pill"
                            style="font-weight:600;"
                          >
                            <v-icon start>mdi-printer</v-icon>
                            Print Order
                          </v-btn>
                          <v-btn
                            color="warning"
                            variant="tonal"
                            block
                            @click="updateStatus('cancelled')"
                            class="rounded-pill"
                            style="font-weight:600;"
                          >
                            <v-icon start>mdi-cancel</v-icon>
                            Cancel Order
                          </v-btn>
                        </template>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-tabs-window-item>

            <!-- Order Items Tab -->
            <v-tabs-window-item value="items" class="pa-6">
              <v-card variant="outlined">
                <v-card-title class="d-flex align-center justify-space-between">
                  <div class="d-flex align-center">
                    <v-icon icon="mdi-cart" class="me-2" color="primary" />
                    Order Items
                    <v-chip variant="outlined" size="small" class="ml-3">
                      {{ (currentOrder.orderItems || []).length }} items
                    </v-chip>
                  </div>
                  <div class="d-flex gap-2">
                    <v-btn v-if="!editingItems && !isCreateMode" color="primary" variant="outlined" size="small"
                      @click="startEditingItems">
                      <v-icon start>mdi-pencil</v-icon>
                      Edit Items
                    </v-btn>
                    <v-btn color="success" variant="outlined" size="small" @click="addNewItem"
                      :disabled="(!editingItems && !isCreateMode) || !selectedVendorId">
                      <v-icon start>mdi-plus</v-icon>
                      Add Item
                    </v-btn>
                    <div v-if="editingItems" class="d-flex gap-2">
                      <v-btn color="success" variant="flat" size="small" @click="saveItemsChanges" :loading="saving">
                        <v-icon start>mdi-check</v-icon>
                        Save All
                      </v-btn>
                      <v-btn variant="outlined" size="small" @click="cancelItemsEdit">
                        Cancel
                      </v-btn>
                    </div>
                  </div>
                </v-card-title>
                <v-card-text class="pa-0">
                  <!-- Vendor Selection Notice -->
                  <v-alert v-if="(editingItems || isCreateMode) && !selectedVendorId" type="info" variant="tonal"
                    class="ma-4">
                    <template #prepend>
                      <v-icon>mdi-information</v-icon>
                    </template>
                    Please select a vendor first to add items to this order.
                  </v-alert>

                  <v-data-table v-else :headers="itemHeaders"
                    :items="editingItems || isCreateMode ? itemsEdit : (currentOrder.orderItems || [])"
                    class="order-items-table" :loading="saving || loadingProducts" loading-text="Updating items...">
                    <template #item.product="{ item }">
                      <div v-if="editingItems || isCreateMode" class="d-flex align-center gap-2">
                        <v-autocomplete v-model="item.product_id" :items="vendorProductOptions"
                          item-title="product_name" item-value="id" variant="outlined" density="compact"
                          style="min-width: 200px;" :loading="loadingProducts"
                          @update:model-value="onProductChange(item)" :disabled="!selectedVendorId"
                          :placeholder="selectedVendorId ? 'Select product' : 'Select vendor first'">
                          <template #item="{ props, item: productItem }">
                            <v-list-item v-bind="props">
                              <template #prepend>
                                <v-avatar size="32" rounded>
                                  <v-img :src="productItem.raw.image_url || '/api/placeholder/32/32'"
                                    :alt="productItem.raw.product_name" />
                                </v-avatar>
                              </template>
                              <v-list-item-title>{{ productItem.raw.product_name }}</v-list-item-title>
                              <v-list-item-subtitle>
                                {{ productItem.raw.sku }} - {{ formatCurrency(productItem.raw.price) }}
                              </v-list-item-subtitle>
                            </v-list-item>
                          </template>
                        </v-autocomplete>
                      </div>
                      <div v-else class="d-flex align-center">
                        <v-avatar size="40" class="me-3" rounded>
                          <v-img :src="item.product?.image_url || '/api/placeholder/40/40'"
                            :alt="item.product?.product_name" />
                        </v-avatar>
                        <div>
                          <div class="text-subtitle-2">{{ item.product?.product_name || '-' }}</div>
                          <div class="text-caption text-medium-emphasis">{{ item.product?.sku || '-' }}</div>
                        </div>
                      </div>
                    </template>

                    <template #item.quantity="{ item }">
                      <v-text-field v-if="editingItems || isCreateMode" v-model.number="item.quantity" type="number"
                        min="1" variant="outlined" density="compact" style="width: 80px;"
                        @input="updateItemTotal(item)" />
                      <v-chip v-else variant="outlined" size="small">
                        {{ item.quantity || 0 }}
                      </v-chip>
                    </template>

                    <template #item.price="{ item }">
                      <v-text-field v-if="editingItems || isCreateMode" v-model.number="item.price" type="number"
                        step="0.01" min="0" variant="outlined" density="compact" prefix="KSh" style="width: 120px;"
                        @input="updateItemTotal(item)" />
                      <div v-else class="text-body-2">{{ formatCurrency(item.price) }}</div>
                    </template>

                    <template #item.discount="{ item }">
                      <v-text-field v-if="editingItems || isCreateMode" v-model.number="item.discount" type="number"
                        step="0.01" min="0" variant="outlined" density="compact" prefix="KSh" style="width: 120px;"
                        @input="updateItemTotal(item)" />
                      <div v-else class="text-body-2">{{ formatCurrency(item.discount || 0) }}</div>
                    </template>

                    <template #item.total_price="{ item }">
                      <div class="text-subtitle-2 font-weight-medium">
                        {{ formatCurrency(item.total_price) }}
                      </div>
                    </template>

                    <template #item.actions="{ item }">
                      <div v-if="editingItems || isCreateMode" class="d-flex gap-1">
                        <v-btn icon="mdi-content-duplicate" variant="text" size="small" @click="duplicateItem(item)"
                          :disabled="!item.product_id" />
                        <v-btn icon="mdi-delete" variant="text" size="small" color="error" @click="removeItem(item)" />
                      </div>
                    </template>

                    <template #bottom>
                      <div class="d-flex justify-end pa-4 bg-surface-variant">
                        <div class="text-h6 font-weight-bold">
                          Total: {{ formatCurrency(calculateItemsTotal()) }}
                        </div>
                      </div>
                    </template>
                  </v-data-table>
                </v-card-text>
              </v-card>
            </v-tabs-window-item>

            <!-- Rest of the tabs remain the same as in your original code -->
            <!-- Charges Tab -->
            <v-tabs-window-item value="charges" class="pa-6">
              <!-- Your existing charges tab content -->
            </v-tabs-window-item>

            <!-- Client Tab -->
            <v-tabs-window-item value="client" class="pa-6">
              <!-- Your existing client tab content -->
            </v-tabs-window-item>

            <!-- Fulfillment Tab -->
            <v-tabs-window-item value="fulfillment" class="pa-6">
              <!-- Your existing fulfillment tab content -->
            </v-tabs-window-item>
          </v-tabs-window>
        </div>
      </v-card-text>

      <!-- Actions -->
      <v-divider />
      <v-card-actions class="pa-6">
        <div v-if="isCreateMode" class="d-flex w-100 gap-3">
          <v-btn variant="outlined" @click="resetForm">
            <v-icon icon="mdi-refresh" start />
            Reset Form
          </v-btn>
          <v-spacer />
          <v-btn variant="outlined" @click="closeDialog">
            Cancel
          </v-btn>
          <v-btn color="success" variant="flat" @click="createOrder" :loading="saving" :disabled="!canCreateOrder">
            <v-icon icon="mdi-plus" start />
            Create Order
          </v-btn>
        </div>

        <div v-else class="d-flex w-100 gap-3">
          <v-btn color="error" variant="outlined" @click="deleteOrder" :disabled="!order">
            <v-icon icon="mdi-delete" start />
            Delete Order
          </v-btn>
          <v-btn color="info" variant="outlined" @click="duplicateOrder" :disabled="!order">
            <v-icon icon="mdi-content-duplicate" start />
            Duplicate Order
          </v-btn>
          <v-spacer />
          <v-btn variant="outlined" @click="printOrder" :disabled="!order" class="me-3">
            <v-icon icon="mdi-printer" start />
            Print
          </v-btn>
          <v-btn color="primary" variant="flat" @click="saveAllChanges" :disabled="!order || !hasUnsavedChanges"
            :loading="saving">
            <v-icon icon="mdi-content-save" start />
            Save All Changes
          </v-btn>
        </div>
      </v-card-actions>
    </v-card>

    <!-- Confirmation Dialogs -->
    <v-dialog v-model="deleteConfirmDialog" max-width="400">
      <v-card>
        <v-card-title class="text-h6">
          <v-icon icon="mdi-alert" color="error" class="me-2" />
          Confirm Delete
        </v-card-title>
        <v-card-text>
          Are you sure you want to delete this order? This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteConfirmDialog = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" @click="confirmDelete" :loading="saving">
            Delete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Unsaved Changes Dialog -->
    <v-dialog v-model="unsavedChangesDialog" max-width="400">
      <v-card>
        <v-card-title class="text-h6">
          <v-icon icon="mdi-alert" color="warning" class="me-2" />
          Unsaved Changes
        </v-card-title>
        <v-card-text>
          You have unsaved changes. What would you like to do?
        </v-card-text>
        <v-card-actions>
          <v-btn variant="text" @click="discardChanges">
            Discard Changes
          </v-btn>
          <v-spacer />
          <v-btn variant="text" @click="unsavedChangesDialog = false">
            Continue Editing
          </v-btn>
          <v-btn color="primary" variant="flat" @click="saveAndClose" :loading="saving">
            Save & Close
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-dialog>
</template>

<script setup>
import { computed, toRefs, ref, watch, nextTick } from 'vue'
import { useOrderStore } from '@/stores/orderStore'

// Props
const props = defineProps({
  createMode: {
    type: Boolean,
    default: false
  }
})

// Pinia store usage
const orderStore = useOrderStore()
const { dialog } = toRefs(orderStore)

// Local state
const activeTab = ref('overview')
const saving = ref(false)
const deleteConfirmDialog = ref(false)
const unsavedChangesDialog = ref(false)

// Loading states
const loadingClients = ref(false)
const loadingVendors = ref(false)
const loadingProducts = ref(false)

// Editing states
const editingOrder = ref(false)
const editingClient = ref(false)
const editingItems = ref(false)
const editingCharges = ref(false)

// Edit data
const orderEdit = ref({})
const clientEdit = ref({})
const itemsEdit = ref([])
const chargesEdit = ref({})

// Create mode state
const isCreateMode = ref(props.createMode)

// Change tracking
const originalOrderData = ref(null)
const originalItemsData = ref([])
const originalChargesData = ref({})
const originalClientData = ref({})

// Options
const clientOptions = ref([])
const vendorOptions = ref([])
const vendorProductOptions = ref([]) // Only products from selected vendor
const agentOptions = ref([])
const riderOptions = ref([])

const platformOptions = ['Website', 'Mobile App', 'Phone', 'WhatsApp', 'Facebook', 'Instagram']
const statusOptions = ['Inprogress', 'active', 'inactive', 'confirmed', 'processing', 'completed', 'cancelled']
const paymentMethods = ['Cash on Delivery', 'Card', 'Bank Transfer', 'Mobile Money', 'PayPal']

// Item table headers
const itemHeaders = [
  { title: 'Product', key: 'product', sortable: false, width: '250px' },
  { title: 'Quantity', key: 'quantity', align: 'center', width: '100px' },
  { title: 'Unit Price', key: 'price', align: 'end', width: '120px' },
  { title: 'Discount', key: 'discount', align: 'end', width: '120px' },
  { title: 'Total', key: 'total_price', align: 'end', width: '120px' },
  { title: 'Actions', key: 'actions', sortable: false, align: 'center', width: '100px' }
]

// Computed properties
const order = computed(() => orderStore.selectedOrder)
const isLoading = computed(() => orderStore.loading.orders)

const selectedVendorId = computed(() => {
  if (isCreateMode.value || editingOrder.value) {
    return orderEdit.value.vendor_id
  }
  return currentOrder.value.vendor_id || currentOrder.value.vendor?.id
})

const currentOrder = computed(() => {
  if (isCreateMode.value) {
    return {
      ...orderEdit.value,
      orderItems: itemsEdit.value,
      client: clientOptions.value.find(c => c.id === orderEdit.value.client_id),
      vendor: vendorOptions.value.find(v => v.id === orderEdit.value.vendor_id),
      agent: agentOptions.value.find(a => a.id === orderEdit.value.agent_id),
      rider: riderOptions.value.find(r => r.id === orderEdit.value.rider_id)
    }
  }
  return order.value || {}
})

const hasUnsavedChanges = computed(() => {
  return editingOrder.value || editingClient.value || editingItems.value || editingCharges.value || isCreateMode.value
})

const canCreateOrder = computed(() => {
  return isCreateMode.value &&
    orderEdit.value.client_id &&
    orderEdit.value.vendor_id &&
    itemsEdit.value.length > 0 &&
    itemsEdit.value.every(item => item.product_id && item.quantity > 0)
})

// Initialize create mode
watch(() => props.createMode, (newVal) => {
  isCreateMode.value = newVal
  if (newVal) {
    initializeCreateMode()
  }
}, { immediate: true })

// Watch for vendor changes to load products
watch(selectedVendorId, async (newVendorId) => {
  if (newVendorId) {
    await loadVendorProducts(newVendorId)
  } else {
    vendorProductOptions.value = []
  }
})

// Initialize create mode data
function initializeCreateMode() {
  orderEdit.value = {
    order_no: '',
    reference: '',
    platform: 'Website',
    status: 'Inprogress',
    client_id: null,
    vendor_id: null,
    agent_id: null,
    rider_id: null,
    delivery_date: null,
    weight: 0,
    customer_notes: ''
  }

  itemsEdit.value = []

  chargesEdit.value = {
    shipping_charges: 0,
    charges: 0,
    discount: 0,
    payment_method: 'Cash on Delivery',
    amount_paid: 0
  }

  clientEdit.value = {
    name: '',
    phone_number: '',
    email: '',
    address: '',
    city: ''
  }

  // Load options
  loadAllOptions()
}

// Load all dropdown options
async function loadAllOptions() {
  try {
    await Promise.all([
      loadClients(),
      loadVendors(),
      loadAgents(),
      loadRiders()
    ])
  } catch (error) {
    console.error('Error loading options:', error)
  }
}

// Load clients with search functionality
async function loadClients(search = '') {
  loadingClients.value = true
  try {
    await orderStore.loadClientOptions(search)
    clientOptions.value = orderStore.clientOptions || []
  } catch (error) {
    console.error('Error loading clients:', error)
  } finally {
    loadingClients.value = false
  }
}

// Load vendors
async function loadVendors() {
  loadingVendors.value = true
  try {
    await orderStore.loadVendorOptions()
    vendorOptions.value = orderStore.vendorOptions || []
  } catch (error) {
    console.error('Error loading vendors:', error)
  } finally {
    loadingVendors.value = false
  }
}

// Load agents
async function loadAgents() {
  try {
    await orderStore.loadAgentOptions()
    agentOptions.value = orderStore.agentOptions || []
  } catch (error) {
    console.error('Error loading agents:', error)
  }
}

// Load riders
async function loadRiders() {
  try {
    await orderStore.loadRiderOptions()
    riderOptions.value = orderStore.riderOptions || []
  } catch (error) {
    console.error('Error loading riders:', error)
  }
}

// Load products for specific vendor - KEY FUNCTIONALITY
async function loadVendorProducts(vendorId) {
  if (!vendorId) {
    vendorProductOptions.value = []
    return
  }

  loadingProducts.value = true
  try {
    await orderStore.loadVendorProducts(vendorId)
    vendorProductOptions.value = orderStore.productOptions || []
  } catch (error) {
    console.error('Error loading vendor products:', error)
    vendorProductOptions.value = []
  } finally {
    loadingProducts.value = false
  }
}

// Search clients
function searchClients(search) {
  if (search && search.length > 2) {
    loadClients(search)
  }
}

// Client change handler
function onClientChange(clientId) {
  if (clientId && (isCreateMode.value || editingOrder.value)) {
    const selectedClient = clientOptions.value.find(c => c.id === clientId)
    if (selectedClient && isCreateMode.value) {
      // Auto-populate client details in create mode
      clientEdit.value = {
        name: selectedClient.name || '',
        phone_number: selectedClient.phone_number || '',
        email: selectedClient.email || '',
        address: selectedClient.address || '',
        city: selectedClient.city || ''
      }
    }
  }
}

// Vendor change handler - Critical for product filtering
function onVendorChange(vendorId) {
  console.log('Vendor changed to:', vendorId)

  // Clear existing items when vendor changes (only in edit mode to prevent data loss)
  if ((isCreateMode.value || editingItems.value) && itemsEdit.value.length > 0) {
    const shouldClear = confirm('Changing vendor will clear existing items. Continue?')
    if (shouldClear) {
      itemsEdit.value = []
    } else {
      // Revert vendor selection
      if (isCreateMode.value || editingOrder.value) {
        orderEdit.value.vendor_id = originalOrderData.value?.vendor_id || null
      }
      return
    }
  }

  // Products will be loaded automatically via the watcher
}

// Product change handler
function onProductChange(item) {
  const product = vendorProductOptions.value.find(p => p.id === item.product_id)
  if (product) {
    item.price = product.price || 0
    item.product = product
    updateItemTotal(item)
  }
}

// Helper functions
function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString()
}

function formatDateTime(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString()
}

function formatCurrency(amount) {
  if (amount == null) return 'KSh 0.00'
  return new Intl.NumberFormat('en-KE', {
    style: 'currency',
    currency: 'KES',
    currencyDisplay: 'symbol'
  }).format(amount)
}

// Color helper functions
function getStatusColor(status) {
  const colors = {
    'pending': 'warning',
    'inprogress': 'info',
    'active': 'success',
    'confirmed': 'success',
    'processing': 'primary',
    'completed': 'success',
    'cancelled': 'error',
    'inactive': 'error'
  }
  if (typeof status === 'string') {
    return colors[status.toLowerCase()] || 'default'
  }
  return 'default'
}

function getRiderStatusColor(status) {
  const colors = {
    'available': 'success',
    'busy': 'warning',
    'offline': 'error',
    'on_delivery': 'primary'
  }
  return colors[status?.toLowerCase()] || 'default'
}

// Change tracking functions
function trackOriginalData() {
  if (order.value && !isCreateMode.value) {
    originalOrderData.value = JSON.parse(JSON.stringify({
      reference: order.value.reference,
      platform: order.value.platform,
      status: order.value.status,
      client_id: order.value.client_id,
      vendor_id: order.value.vendor_id,
      agent_id: order.value.agent_id,
      rider_id: order.value.rider_id,
      delivery_date: order.value.delivery_date,
      weight: order.value.weight,
      customer_notes: order.value.customer_notes
    }))

    originalItemsData.value = JSON.parse(JSON.stringify(order.value.orderItems || []))

    originalChargesData.value = JSON.parse(JSON.stringify({
      shipping_charges: order.value.shipping_charges || 0,
      charges: order.value.charges || 0,
      discount: order.value.discount || 0,
      payment_method: order.value.payment_method || 'Cash on Delivery',
      amount_paid: order.value.amount_paid || 0
    }))

    originalClientData.value = JSON.parse(JSON.stringify({
      name: order.value.client?.name || '',
      phone_number: order.value.client?.phone_number || '',
      email: order.value.client?.email || '',
      address: order.value.client?.address || '',
      city: order.value.client?.city || ''
    }))
  }
}

// Order editing functions
function startEditingOrder() {
  editingOrder.value = true
  orderEdit.value = {
    reference: order.value.reference,
    platform: order.value.platform,
    status: order.value.status,
    client_id: order.value.client_id,
    vendor_id: order.value.vendor_id,
    agent_id: order.value.agent_id,
    rider_id: order.value.rider_id,
    delivery_date: order.value.delivery_date,
    weight: order.value.weight,
    customer_notes: order.value.customer_notes
  }
}

function cancelOrderEdit() {
  editingOrder.value = false
  orderEdit.value = {}
}

async function saveOrderChanges() {
  saving.value = true
  try {
    const updatedOrder = await orderStore.updateOrder(order.value.id, orderEdit.value)
    editingOrder.value = false
    orderEdit.value = {}

    // Update original data tracking
    trackOriginalData()

    console.log('Order updated successfully:', updatedOrder)
  } catch (error) {
    console.error('Failed to save order changes:', error)
    // You might want to show an error notification here
  } finally {
    saving.value = false
  }
}

// Client editing functions
function startEditingClient() {
  editingClient.value = true
  clientEdit.value = {
    name: order.value.client?.name || '',
    phone_number: order.value.client?.phone_number || '',
    email: order.value.client?.email || '',
    address: order.value.client?.address || '',
    city: order.value.client?.city || ''
  }
}

function cancelClientEdit() {
  editingClient.value = false
  clientEdit.value = {}
}

async function saveClientChanges() {
  saving.value = true
  try {
    await orderStore.updateClient(order.value.client.id, clientEdit.value)
    editingClient.value = false
    clientEdit.value = {}

    // Update original data tracking
    trackOriginalData()
  } catch (error) {
    console.error('Failed to save client changes:', error)
  } finally {
    saving.value = false
  }
}

// Items editing functions - Enhanced for vendor-specific products
function startEditingItems() {
  editingItems.value = true
  itemsEdit.value = JSON.parse(JSON.stringify(order.value.orderItems || []))

  // Load vendor products if vendor is set
  if (selectedVendorId.value) {
    loadVendorProducts(selectedVendorId.value)
  }
}

function cancelItemsEdit() {
  editingItems.value = false
  itemsEdit.value = []
  vendorProductOptions.value = []
}

async function saveItemsChanges() {
  saving.value = true
  try {
    // Validate items before saving
    const validItems = itemsEdit.value.filter(item =>
      item.product_id &&
      item.quantity > 0 &&
      item.price >= 0
    )

    if (validItems.length !== itemsEdit.value.length) {
      throw new Error('Some items have invalid data. Please check all fields.')
    }

    await orderStore.updateOrderItems(order.value.id, validItems)
    editingItems.value = false
    itemsEdit.value = []
    vendorProductOptions.value = []

    // Update original data tracking
    trackOriginalData()
  } catch (error) {
    console.error('Failed to save items changes:', error)
    // You might want to show an error notification here
  } finally {
    saving.value = false
  }
}

function addNewItem() {
  if (!selectedVendorId.value) {
    alert('Please select a vendor first before adding items.')
    return
  }

  const newItem = {
    id: `temp-${Date.now()}`,
    product_id: null,
    quantity: 1,
    price: 0,
    discount: 0,
    total_price: 0,
    product: { product_name: '', sku: '' }
  }
  itemsEdit.value.push(newItem)
}

function duplicateItem(item) {
  if (!item.product_id) {
    alert('Please select a product first before duplicating.')
    return
  }

  const newItem = {
    ...JSON.parse(JSON.stringify(item)),
    id: `temp-${Date.now()}-${Math.random()}`
  }
  const index = itemsEdit.value.findIndex(i => i.id === item.id)
  itemsEdit.value.splice(index + 1, 0, newItem)
}

function removeItem(item) {
  const index = itemsEdit.value.findIndex(i => i.id === item.id)
  if (index > -1) {
    itemsEdit.value.splice(index, 1)
  }
}

function updateItemTotal(item) {
  const quantity = Number(item.quantity) || 0
  const price = Number(item.price) || 0
  const discount = Number(item.discount) || 0
  item.total_price = Math.max(0, (quantity * price) - discount)
}

function calculateItemsTotal() {
  const items = (editingItems.value || isCreateMode.value) ? itemsEdit.value : (currentOrder.value.orderItems || [])
  return items.reduce((sum, item) => sum + (item.total_price || 0), 0)
}

// Charges editing functions
function startEditingCharges() {
  editingCharges.value = true
  chargesEdit.value = {
    shipping_charges: order.value.shipping_charges || 0,
    charges: order.value.charges || 0,
    discount: order.value.discount || 0,
    payment_method: order.value.payment_method || 'Cash on Delivery',
    amount_paid: order.value.amount_paid || 0
  }
}

function cancelChargesEdit() {
  editingCharges.value = false
  chargesEdit.value = {}
}

async function saveChargesChanges() {
  saving.value = true
  try {
    await orderStore.updateOrder(order.value.id, chargesEdit.value)
    editingCharges.value = false
    chargesEdit.value = {}

    // Update original data tracking
    trackOriginalData()
  } catch (error) {
    console.error('Failed to save charges changes:', error)
  } finally {
    saving.value = false
  }
}

function calculateTotalWithCharges() {
  const subtotal = calculateItemsTotal()
  const charges = (editingCharges.value || isCreateMode.value) ? chargesEdit.value : currentOrder.value

  const shipping = Number(charges.shipping_charges) || 0
  const otherCharges = Number(charges.charges) || 0
  const discount = Number(charges.discount) || 0

  return Math.max(0, subtotal + shipping + otherCharges - discount)
}

// Create order function
async function createOrder() {
  if (!canCreateOrder.value) return

  saving.value = true
  try {
    // Validate items
    const validItems = itemsEdit.value.filter(item =>
      item.product_id &&
      item.quantity > 0 &&
      item.price >= 0
    )

    if (validItems.length === 0) {
      throw new Error('Please add at least one valid item to the order.')
    }

    const orderData = {
      ...orderEdit.value,
      ...chargesEdit.value,
      items: validItems.map(item => ({
        product_id: item.product_id,
        quantity: item.quantity,
        price: item.price,
        discount: item.discount || 0
      })),
      sub_total: calculateItemsTotal(),
      total_price: calculateTotalWithCharges()
    }

    const newOrder = await orderStore.createOrder(orderData)

    // Switch to view mode with the new order
    isCreateMode.value = false
    orderStore.selectedOrder = newOrder
    orderStore.selectedOrderId = newOrder.id

    // Track original data for the new order
    trackOriginalData()

    console.log('Order created successfully:', newOrder)

  } catch (error) {
    console.error('Failed to create order:', error)
    alert(error.message || 'Failed to create order. Please check all required fields.')
  } finally {
    saving.value = false
  }
}

// Action functions
function callClient() {
  const phone = currentOrder.value?.client?.phone_number
  if (phone) {
    window.open(`tel:${phone}`)
  }
}

async function updateStatus(status) {
  if (!order.value) return

  saving.value = true
  try {
    await orderStore.updateOrder(order.value.id, { status })
  } catch (error) {
    console.error('Failed to update status:', error)
  } finally {
    saving.value = false
  }
}

function printOrder() {
  window.print()
}

function duplicateOrder() {
  if (!order.value) return

  // Switch to create mode with duplicated data
  isCreateMode.value = true

  orderEdit.value = {
    ...order.value,
    order_no: '',
    reference: `${order.value.reference || order.value.order_no}-COPY`,
    status: 'Inprogress'
  }

  itemsEdit.value = order.value.orderItems?.map(item => ({
    ...item,
    id: `temp-${Date.now()}-${Math.random()}`
  })) || []

  chargesEdit.value = {
    shipping_charges: order.value.shipping_charges || 0,
    charges: order.value.charges || 0,
    discount: order.value.discount || 0,
    payment_method: order.value.payment_method || 'Cash on Delivery',
    amount_paid: 0
  }

  // Load vendor products if vendor is set
  if (orderEdit.value.vendor_id) {
    loadVendorProducts(orderEdit.value.vendor_id)
  }

  activeTab.value = 'overview'
}

async function saveAllChanges() {
  if (!hasUnsavedChanges.value) return

  saving.value = true
  try {
    const promises = []

    if (editingOrder.value) {
      promises.push(saveOrderChanges())
    }
    if (editingClient.value) {
      promises.push(saveClientChanges())
    }
    if (editingItems.value) {
      promises.push(saveItemsChanges())
    }
    if (editingCharges.value) {
      promises.push(saveChargesChanges())
    }

    await Promise.all(promises)

    console.log('All changes saved successfully')

  } catch (error) {
    console.error('Failed to save all changes:', error)
  } finally {
    saving.value = false
  }
}

// Dialog management functions
function closeDialog() {
  if (hasUnsavedChanges.value && !isCreateMode.value) {
    unsavedChangesDialog.value = true
    return
  }

  performCloseDialog()
}

function performCloseDialog() {
  orderStore.closeDialog()
  resetDialog()
}

function discardChanges() {
  unsavedChangesDialog.value = false
  performCloseDialog()
}

async function saveAndClose() {
  unsavedChangesDialog.value = false

  if (isCreateMode.value) {
    await createOrder()
  } else {
    await saveAllChanges()
  }

  performCloseDialog()
}

function resetEditing() {
  editingOrder.value = false
  editingClient.value = false
  editingItems.value = false
  editingCharges.value = false
  orderEdit.value = {}
  clientEdit.value = {}
  itemsEdit.value = []
  chargesEdit.value = {}
  vendorProductOptions.value = []
}

function resetDialog() {
  activeTab.value = 'overview'
  isCreateMode.value = false
  resetEditing()
  originalOrderData.value = null
  originalItemsData.value = []
  originalChargesData.value = {}
  originalClientData.value = {}
}

function resetForm() {
  if (isCreateMode.value) {
    initializeCreateMode()
    activeTab.value = 'overview'
  }
}

function refreshOrder() {
  if (order.value?.id) {
    orderStore.loadOrder(order.value.id).then(() => {
      trackOriginalData()
    })
  }
}

function deleteOrder() {
  deleteConfirmDialog.value = true
}

async function confirmDelete() {
  if (!order.value) return

  saving.value = true
  try {
    await orderStore.deleteOrder(order.value.id)
    deleteConfirmDialog.value = false
    performCloseDialog()
  } catch (error) {
    console.error('Failed to delete order:', error)
  } finally {
    saving.value = false
  }
}

// Watchers
watch(dialog, async (newVal) => {
  if (newVal && !isCreateMode.value) {
    // Load options when dialog opens in edit mode
    await loadAllOptions()

    // Load vendor products if vendor is set
    if (order.value?.vendor_id) {
      await loadVendorProducts(order.value.vendor_id)
    }

    // Track original data for change detection
    trackOriginalData()
  } else if (!newVal) {
    // Reset dialog when closed
    resetDialog()
  }
})

watch(() => isCreateMode.value, (newVal) => {
  if (newVal) {
    editingItems.value = true
    editingCharges.value = true
    editingOrder.value = true
  } else {
    resetEditing()
  }
})

// Watch for order changes to update tracking
watch(order, () => {
  if (order.value && !isCreateMode.value) {
    trackOriginalData()
  }
}, { deep: true })
</script>

<style scoped>
.order-details-card {
  min-height: 80vh;
}

.gradient-header {
  background: linear-gradient(135deg, #1976d2 0%, #42a5f5 100%);
}

.info-grid {
  display: grid;
  gap: 16px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-item .label {
  font-size: 0.875rem;
  font-weight: 500;
  color: rgba(0, 0, 0, 0.6);
}

.info-item .value {
  font-size: 0.875rem;
  font-weight: 400;
  color: rgba(0, 0, 0, 0.87);
  min-height: 20px;
}

.charges-breakdown {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.charge-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}

.charge-label {
  font-weight: 500;
}

.charge-value {
  font-weight: 600;
}

.total-charge {
  border-top: 2px solid #e0e0e0;
  padding-top: 12px;
  margin-top: 8px;
}

.payment-info {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.order-items-table {
  border-radius: 0;
}

.order-items-table .v-data-table__wrapper {
  border-radius: 0;
}

/* Custom styling for better UX */
.v-text-field--density-compact .v-field__input {
  min-height: 32px;
}

.v-autocomplete--density-compact .v-field__input {
  min-height: 32px;
}

/* Highlight unsaved changes */
.v-text-field--dirty .v-field {
  border-color: #ff9800;
}

.v-select--dirty .v-field {
  border-color: #ff9800;
}
</style>