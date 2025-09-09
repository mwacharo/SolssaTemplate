<template>
  <v-dialog v-model="dialog" max-width="800" persistent>
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-icon class="mr-2" :color="isCreateMode ? 'success' : 'primary'">
          {{ isCreateMode ? 'mdi-plus-circle' : 'mdi-pencil' }}
        </v-icon>
        <span class="text-h6">{{ isCreateMode ? 'Create New Order' : 'Edit Order' }}</span>
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" @click="closeDialog" />
      </v-card-title>
      
      <v-divider />
      
      <v-card-text class="pa-6">
        <v-form ref="formRef" v-model="formValid" @submit.prevent="saveOrder">
          <!-- Basic Order Information -->
          <div class="mb-6">
            <h3 class="text-subtitle-1 font-weight-medium mb-3 text-primary">
              <v-icon class="mr-1" size="small">mdi-information</v-icon>
              Basic Information
            </h3>
            
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field 
                  v-model="orderEdit.reference" 
                  label="Order Reference" 
                  placeholder="Optional reference number"
                  prepend-inner-icon="mdi-tag"
                  variant="outlined"
                  density="comfortable"
                />
              </v-col>
              
              <v-col cols="12" md="6">
                <v-select 
                  v-model="orderEdit.platform" 
                  :items="platformOptions" 
                  label="Platform"
                  prepend-inner-icon="mdi-devices"
                  variant="outlined"
                  density="comfortable"
                  :rules="[rules.required]"
                />
              </v-col>
              
              <v-col cols="12" md="6">
                <v-select 
                  v-model="orderEdit.source" 
                  :items="sourceOptions" 
                  label="Order Source"
                  prepend-inner-icon="mdi-source-branch"
                  variant="outlined"
                  density="comfortable"
                  :rules="[rules.required]"
                />
              </v-col>
              
              <v-col cols="12" md="6">
                <v-text-field 
                  v-model="orderEdit.delivery_date" 
                  label="Delivery Date" 
                  type="datetime-local"
                  prepend-inner-icon="mdi-calendar-clock"
                  variant="outlined"
                  density="comfortable"
                />
              </v-col>
            </v-row>
          </div>

          <!-- Vendor Information -->
          <div class="mb-6">
            <h3 class="text-subtitle-1 font-weight-medium mb-3 text-primary">
              <v-icon class="mr-1" size="small">mdi-store</v-icon>
              Vendor & Warehouse
            </h3>
                
                <v-row>
                  <v-col cols="12" md="6">
                    <v-select 
                      v-model="orderEdit.vendor_id" 
                      :items="vendorOptions" 
                      item-title="name"
                      item-value="id" 
                      label="Vendor"
                      prepend-inner-icon="mdi-domain"
                      variant="outlined"
                      density="comfortable"
                      :rules="[rules.required]"
                      @update:model-value="onVendorChange"
                    />
                  </v-col>
                  
                    <v-col cols="12" md="6">
                    <v-select 
                      v-model="orderEdit.warehouse_id" 
                      :items="warehouseOptions" 
                      item-title="name"
                      item-value="id" 
                      label="Warehouse"
                      prepend-inner-icon="mdi-warehouse"
                      variant="outlined"
                      density="comfortable"
                      :rules="[rules.required]"
                    />
                    </v-col>
                </v-row>
              </div>

          <!-- Address Section - Smart UI based on order type -->
            <div class="mb-6">
            <h3 class="text-subtitle-1 font-weight-medium mb-3 text-primary">
              <v-icon class="mr-1" size="small">mdi-map-marker</v-icon>
              Address Information
            </h3>
            
            <!-- Address Type Selector -->
            <v-radio-group 
              v-model="addressType" 
              inline 
              class="mb-4"
              @update:model-value="onAddressTypeChange"
            >
              <v-radio 
              label="Pickup & Drop-off" 
              value="pickup_dropoff"
              color="primary"
              />
              <v-radio 
              label="Customer Delivery" 
              value="customer"
              color="primary"
              />
            </v-radio-group>

            <!-- Pickup & Drop-off Addresses -->
            <div v-if="addressType === 'pickup_dropoff'">
              <v-row>
              <!-- Pickup Address -->
              <v-col cols="12" md="6">
                <v-card variant="outlined" class="pa-4">
                <v-card-title class="text-subtitle-2 pa-0 mb-3">
                  <v-icon class="mr-1" color="orange">mdi-map-marker-up</v-icon>
                  Pickup Address
                </v-card-title>
                
                <v-text-field 
                  v-model="orderEdit.pickup_address.full_name" 
                  label="Full Name"
                  prepend-inner-icon="mdi-account"
                  variant="outlined"
                  density="comfortable"
                  :rules="[rules.required]"
                  class="mb-3"
                />
                <v-text-field 
                  v-model="orderEdit.pickup_address.phone" 
                  label="Phone Number"
                  prepend-inner-icon="mdi-phone"
                  variant="outlined"
                  density="comfortable"
                  :rules="[rules.required, rules.phone]"
                  class="mb-3"
                />
                <v-text-field 
                  v-model="orderEdit.pickup_address.email" 
                  label="Email"
                  prepend-inner-icon="mdi-email"
                  variant="outlined"
                  density="comfortable"
                  class="mb-3"
                />
                <v-select 
                  v-model="orderEdit.pickup_address.city" 
                  :items="cityOptions" 
                  item-title="name"
                  item-value="name"
                  label="City"
                  prepend-inner-icon="mdi-city"
                  variant="outlined"
                  density="comfortable"
                  :rules="[rules.required]"
                  class="mb-3"
                />
                <v-select 
                  v-model="orderEdit.pickup_address.zone_id" 
                  :items="zoneOptions" 
                  item-title="name"
                  item-value="id"
                  label="Zone"
                  prepend-inner-icon="mdi-map-marker-radius"
                  variant="outlined"
                  density="comfortable"
                  class="mb-3"
                />
                <v-text-field 
                  v-model="orderEdit.pickup_address.region"
                  label="Region"
                  prepend-inner-icon="mdi-map"
                  variant="outlined"
                  density="comfortable"
                  class="mb-3"
                />
                <v-text-field 
                  v-model="orderEdit.pickup_address.zipcode"
                  label="Zip Code"
                  prepend-inner-icon="mdi-numeric"
                  variant="outlined"
                  density="comfortable"
                  class="mb-3"
                />
                <v-textarea 
                  v-model="orderEdit.pickup_address.address" 
                  label="Full Address"
                  prepend-inner-icon="mdi-home"
                  variant="outlined"
                  density="comfortable"
                  rows="2"
                  :rules="[rules.required]"
                />
                </v-card>
              </v-col>
              <!-- Drop-off Address -->
              <v-col cols="12" md="6">
                <v-card variant="outlined" class="pa-4">
                <v-card-title class="text-subtitle-2 pa-0 mb-3">
                  <v-icon class="mr-1" color="green">mdi-map-marker-down</v-icon>
                  Drop-off Address
                </v-card-title>
                
                <v-text-field 
                  v-model="orderEdit.dropoff_address.full_name" 
                  label="Full Name"
                  prepend-inner-icon="mdi-account"
                  variant="outlined"
                  density="comfortable"
                  :rules="[rules.required]"
                  class="mb-3"
                />
                <v-text-field 
                  v-model="orderEdit.dropoff_address.phone" 
                  label="Phone Number"
                  prepend-inner-icon="mdi-phone"
                  variant="outlined"
                  density="comfortable"
                  :rules="[rules.required, rules.phone]"
                  class="mb-3"
                />
                <v-text-field 
                  v-model="orderEdit.dropoff_address.email" 
                  label="Email"
                  prepend-inner-icon="mdi-email"
                  variant="outlined"
                  density="comfortable"
                  class="mb-3"
                />
                <v-select 
                  v-model="orderEdit.dropoff_address.city" 
                  :items="cityOptions" 
                  item-title="name"
                  item-value="name"
                  label="City"
                  prepend-inner-icon="mdi-city"
                  variant="outlined"
                  density="comfortable"
                  :rules="[rules.required]"
                  class="mb-3"
                />
                <v-select 
                  v-model="orderEdit.dropoff_address.zone_id" 
                  :items="zoneOptions" 
                  item-title="name"
                  item-value="id"
                  label="Zone"
                  prepend-inner-icon="mdi-map-marker-radius"
                  variant="outlined"
                  density="comfortable"
                  class="mb-3"
                />
                <v-text-field 
                  v-model="orderEdit.dropoff_address.region"
                  label="Region"
                  prepend-inner-icon="mdi-map"
                  variant="outlined"
                  density="comfortable"
                  class="mb-3"
                />
                <v-text-field 
                  v-model="orderEdit.dropoff_address.zipcode"
                  label="Zip Code"
                  prepend-inner-icon="mdi-numeric"
                  variant="outlined"
                  density="comfortable"
                  class="mb-3"
                />
                <v-textarea 
                  v-model="orderEdit.dropoff_address.address" 
                  label="Full Address"
                  prepend-inner-icon="mdi-home"
                  variant="outlined"
                  density="comfortable"
                  rows="2"
                  :rules="[rules.required]"
                />
                </v-card>
              </v-col>
              </v-row>
            </div>

            <!-- Customer Address (Single Address) -->
            <div v-else-if="addressType === 'customer'">
              <v-row>
              <v-col cols="12" md="6">
                <v-text-field 
                v-model="orderEdit.customer_address.full_name" 
                label="Customer Name"
                prepend-inner-icon="mdi-account"
                variant="outlined"
                density="comfortable"
                :rules="[rules.required]"
                />
              </v-col>
              
              <v-col cols="12" md="6">
                <v-text-field 
                v-model="orderEdit.customer_address.phone" 
                label="Phone Number"
                prepend-inner-icon="mdi-phone"
                variant="outlined"
                density="comfortable"
                :rules="[rules.required, rules.phone]"
                />
              </v-col>
              
              <v-col cols="12" md="6">
                <v-select 
                v-model="orderEdit.customer_address.city" 
                :items="cityOptions" 
                item-title="name"
                item-value="name"
                label="City"
                prepend-inner-icon="mdi-city"
                variant="outlined"
                density="comfortable"
                :rules="[rules.required]"
                />
              </v-col>
              
              <v-col cols="12" md="6">
                <v-select 
                v-model="orderEdit.customer_address.zone_id" 
                :items="zoneOptions" 
                item-title="name"
                item-value="id"
                label="Zone"
                prepend-inner-icon="mdi-map-marker-radius"
                variant="outlined"
                density="comfortable"
                />
              </v-col>
              
              <v-col cols="12">
                <v-textarea 
                v-model="orderEdit.customer_address.address" 
                label="Full Address"
                prepend-inner-icon="mdi-home"
                variant="outlined"
                density="comfortable"
                rows="2"
                :rules="[rules.required]"
                />
              </v-col>
              </v-row>
            </div>
            </div>

          <!-- Order Items Preview (if editing) -->
          <div v-if="!isCreateMode && order?.order_items?.length" class="mb-4">
            <h3 class="text-subtitle-1 font-weight-medium mb-3 text-primary">
              <v-icon class="mr-1" size="small">mdi-package-variant</v-icon>
              Order Items ({{ order.order_items.length }})
            </h3>
            
            <v-card variant="outlined">
              <v-list density="compact">
                <v-list-item 
                  v-for="item in order.order_items.slice(0, 3)" 
                  :key="item.id"
                >
                  <v-list-item-title>{{ item.product_name || 'Product' }}</v-list-item-title>
                  <v-list-item-subtitle>
                    Qty: {{ item.quantity }} × ${{ item.unit_price }}
                  </v-list-item-subtitle>
                  
                  <template #append>
                    <span class="text-subtitle-2 font-weight-medium">
                      ${{ (item.quantity * item.unit_price).toFixed(2) }}
                    </span>
                  </template>
                </v-list-item>
                
                <v-list-item v-if="order.order_items.length > 3">
                  <v-list-item-title class="text-center text-grey">
                    +{{ order.order_items.length - 3 }} more items
                  </v-list-item-title>
                </v-list-item>
              </v-list>
            </v-card>
          </div>

          <!-- Status (if editing) -->
          <div v-if="!isCreateMode" class="mb-4">
            <h3 class="text-subtitle-1 font-weight-medium mb-3 text-primary">
              <v-icon class="mr-1" size="small">mdi-flag</v-icon>
              Order Status
            </h3>
            
            <v-select 
              v-model="orderEdit.status" 
              :items="statusOptions" 
              label="Status"
              prepend-inner-icon="mdi-flag"
              variant="outlined"
              density="comfortable"
            />
          </div>
        </v-form>
      </v-card-text>
      
      <v-divider />
      
      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn 
          variant="outlined" 
          @click="closeDialog"
          :disabled="saving"
        >
          Cancel
        </v-btn>
        <v-btn 
          :color="isCreateMode ? 'success' : 'primary'" 
          variant="flat" 
          @click="saveOrder" 
          :loading="saving"
          :disabled="!formValid"
        >
          <v-icon class="mr-1">
            {{ isCreateMode ? 'mdi-plus' : 'mdi-content-save' }}
          </v-icon>
          {{ isCreateMode ? 'Create Order' : 'Save Changes' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>


<script setup>
import { ref, computed, toRefs, watch } from 'vue'
import { useOrderStore } from '@/stores/orderStore'

const emit = defineEmits(['order-saved', 'dialog-closed'])

const orderStore = useOrderStore()

const formRef = ref(null)
const formValid = ref(false)


        const { dialog, isCreateMode } = toRefs(orderStore)

const saving = ref(false)
const addressType = ref('customer') // 'customer' or 'warehouse'

// Form validation rules
const rules = {
  required: value => !!value || 'This field is required',
  phone: value => {
    if (!value) return true
    const phonePattern = /^[\d\s\-\+\(\)]+$/
    return phonePattern.test(value) || 'Invalid phone number format'
  }
}

// Static options
const platformOptions = [
  { title: 'API', value: 'api' },
  { title: 'Web', value: 'web' },
  { title: 'Mobile App', value: 'mobile' },
  { title: 'WhatsApp', value: 'whatsapp' },
  { title: 'Facebook', value: 'facebook' },
  { title: 'Instagram', value: 'instagram' }
]

const sourceOptions = [
  { title: 'Vendor API', value: 'vendor_api' },
  { title: 'Customer Web', value: 'customer_web' },
  { title: 'Customer Mobile', value: 'customer_mobile' },
  { title: 'Agent', value: 'agent' },
  { title: 'Call Center', value: 'call_center' }
]

const statusOptions = [
  { title: 'Pending', value: 0 },
  { title: 'Confirmed', value: 1 },
  { title: 'Cancelled', value: 2 }
]

// Computed options from store
const vendorOptions = computed(() => orderStore.vendorOptions)
const categoryOptions = computed(() => orderStore.categoryOptions)
const cityOptions = computed(() => orderStore.cityOptions)
const zoneOptions = computed(() => orderStore.zoneOptions)
const warehouseOptions = computed(() => orderStore.warehouseOptions)

const order = computed(() => orderStore.selectedOrder)

// Form data
const orderEdit = ref({
  reference: '',
  platform: 'api',
  source: 'vendor_api',
  vendor_id: null,
  category_id: null,
  delivery_date: '',
  status: 0,
  customer_address: {
    full_name: '',
    phone: '',
    city: '',
    zone_id: null,
    address: ''
  },
  pickup_address: {
    full_name: '',
    phone: '',
    email: '',
    city: '',
    zone_id: null,
    region: '',
    zipcode: '',
    address: ''
  },
  dropoff_address: {
    full_name: '',
    phone: '',
    email: '',
    city: '',
    zone_id: null,
    region: '',
    zipcode: '',
    address: ''
  }
})

// Initialize form for editing
const initializeEditForm = (orderData) => {
  if (!orderData) return
  
  orderEdit.value = {
    reference: orderData.reference || '',
    platform: orderData.platform || 'api',
    source: orderData.source || 'vendor_api',
    vendor_id: orderData.vendor_id || null,
    category_id: orderData.category_id || null,
    delivery_date: orderData.delivery_date || '',
    status: orderData.status || 0,
    customer_address: {
      full_name: orderData.shipping_address?.full_name || '',
      phone: orderData.shipping_address?.phone || '',
      city: orderData.shipping_address?.city || '',
      zone_id: orderData.shipping_address?.zone_id || null,
      address: orderData.shipping_address?.address || ''
    },
    from_warehouse_id: orderData.from_warehouse_id || null,
    to_warehouse_id: orderData.to_warehouse_id || null,
    from_address: {
      city: orderData.from_address?.city || ''
    },
    to_address: {
      city: orderData.to_address?.city || ''
    }
  }
  
  // Set address type based on existing data
  if (orderData.from_warehouse_id && orderData.to_warehouse_id) {
    addressType.value = 'warehouse'
  } else {
    addressType.value = 'customer'
  }
}

// Initialize form for creation
const initializeCreateForm = () => {
  orderEdit.value = {
    reference: '',
    platform: 'api',
    source: 'vendor_api',
    vendor_id: null,
    category_id: null,
    delivery_date: '',
    status: 0,
    customer_address: {
      full_name: '',
      phone: '',
      city: '',
      zone_id: null,
      address: ''
    },
    from_warehouse_id: null,
    to_warehouse_id: null,
    from_address: {
      city: ''
    },
    to_address: {
      city: ''
    }
  }
  addressType.value = 'customer'
}





// Methods
const onVendorChange = (vendorId) => {
  if (vendorId) {
    orderStore.fetchDropdownOptions(vendorId)
  }
}

const onAddressTypeChange = (type) => {
  // Clear address data when switching types
  if (type === 'customer') {
    orderEdit.value.from_warehouse_id = null
    orderEdit.value.to_warehouse_id = null
    orderEdit.value.from_address = { city: '' }
    orderEdit.value.to_address = { city: '' }
  } else {
    orderEdit.value.customer_address = {
      full_name: '',
      phone: '',
      city: '',
      zone_id: null,
      address: ''
    }
  }
}

const closeDialog = () => {
  orderStore.closeDialog()
  emit('dialog-closed')
}

const saveOrder = async () => {
  if (!formRef.value?.validate()) return
  
  saving.value = true
  try {
    let orderData = { ...orderEdit.value }
    
    // Clean up data based on address type
    if (addressType.value === 'customer') {
      delete orderData.from_warehouse_id
      delete orderData.to_warehouse_id
      delete orderData.from_address
      delete orderData.to_address
      orderData.shipping_address = orderData.customer_address
      delete orderData.customer_address
    } else {
      delete orderData.customer_address
      delete orderData.shipping_address
    }
    
    if (isCreateMode.value) {
      await orderStore.createOrder(orderData)
    } else {
      await orderStore.updateOrder(order.value.id, orderData)
    }
    
    emit('order-saved')
    closeDialog()
  } catch (error) {
    console.error('Error saving order:', error)
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.v-card-title {
  border-bottom: 1px solid rgba(0, 0, 0, 0.12);
}

.v-card-actions {
  border-top: 1px solid rgba(0, 0, 0, 0.12);
}
</style>