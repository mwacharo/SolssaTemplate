<template>
  <v-dialog v-model="dialog" max-width="900" persistent scrollable>
    <v-card class="order-details-card">
      <!-- Header -->
      <v-card-title class="pa-6 pb-4">
        <div class="d-flex align-center justify-space-between w-100">
          <div>
            <h2 class="text-h5 mb-1">Order Details</h2>
            <div class="text-subtitle-1 text-medium-emphasis">
              {{ order?.order_no || 'Loading...' }}
            </div>
          </div>
          <v-btn
            icon="mdi-close"
            variant="text"
            size="small"
            @click="dialog = false"
          />
        </div>
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-0">
        <div v-if="!order" class="pa-8 text-center">
          <v-progress-circular indeterminate color="primary" />
          <p class="text-body-1 mt-4">Loading order details...</p>
        </div>

        <div v-else class="pa-6">
          <!-- Status Section -->
          <v-card variant="outlined" class="mb-6">
            <v-card-title class="text-h6 pa-4 pb-2">
              <v-icon icon="mdi-information-outline" class="me-2" />
              Order Status
            </v-card-title>
            <v-card-text class="pa-4 pt-2">
              <v-row>
                <v-col cols="12" md="6">
                  <div class="mb-3">
                    <div class="text-caption text-medium-emphasis mb-1">Status</div>
                    <v-chip
                      :color="getStatusColor(order.status)"
                      variant="flat"
                      size="small"
                    >
                      {{ order.status }}
                    </v-chip>
                  </div>
                  <div class="mb-3">
                    <div class="text-caption text-medium-emphasis mb-1">Delivery Status</div>
                    <v-chip
                      :color="getDeliveryStatusColor(order.delivery_status)"
                      variant="flat"
                      size="small"
                    >
                      {{ order.delivery_status }}
                    </v-chip>
                  </div>
                  <div class="mb-3">
                    <div class="text-caption text-medium-emphasis mb-1">Platform</div>
                    <v-chip variant="outlined" size="small">
                      {{ order.platform }}
                    </v-chip>
                  </div>
                </v-col>
                <v-col cols="12" md="6">
                  <div class="mb-3">
                    <div class="text-caption text-medium-emphasis mb-1">Delivery Date</div>
                    <div class="text-body-1">
                      <v-icon icon="mdi-calendar" size="small" class="me-2" />
                      {{ formatDate(order.delivery_date) }}
                    </div>
                  </div>
                  <div class="mb-3">
                    <div class="text-caption text-medium-emphasis mb-1">Created At</div>
                    <div class="text-body-1">
                      <v-icon icon="mdi-clock-outline" size="small" class="me-2" />
                      {{ formatDateTime(order.created_at) }}
                    </div>
                  </div>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- People Section -->
          <v-card variant="outlined" class="mb-6">
            <v-card-title class="text-h6 pa-4 pb-2">
              <v-icon icon="mdi-account-multiple" class="me-2" />
              People Involved
            </v-card-title>
            <v-card-text class="pa-4 pt-2">
              <v-row>
                <v-col cols="12" sm="6" md="4">
                  <div class="person-card pa-3 rounded border">
                    <div class="d-flex align-center mb-2">
                      <v-avatar size="32" color="primary" class="me-3">
                        <v-icon icon="mdi-account" />
                      </v-avatar>
                      <div>
                        <div class="text-caption text-medium-emphasis">Client</div>
                        <div class="text-subtitle-2">{{ order.client?.name || '-' }}</div>
                      </div>
                    </div>
                    <div class="text-body-2 text-medium-emphasis">
                      <div class="mb-1">
                        <v-icon icon="mdi-phone" size="small" class="me-2" />
                        {{ order.client?.phone_number || '-' }}
                      </div>
                      <div>
                        <v-icon icon="mdi-map-marker" size="small" class="me-2" />
                        {{ order.client?.city || '-' }}
                      </div>
                    </div>
                  </div>
                </v-col>
                
                <v-col cols="12" sm="6" md="4">
                  <div class="person-card pa-3 rounded border">
                    <div class="d-flex align-center mb-2">
                      <v-avatar size="32" color="secondary" class="me-3">
                        <v-icon icon="mdi-store" />
                      </v-avatar>
                      <div>
                        <div class="text-caption text-medium-emphasis">Vendor</div>
                        <div class="text-subtitle-2">{{ order.vendor?.company_name || '-' }}</div>
                      </div>
                    </div>
                  </div>
                </v-col>

                <v-col cols="12" sm="6" md="4">
                  <div class="person-card pa-3 rounded border">
                    <div class="d-flex align-center mb-2">
                      <v-avatar size="32" color="info" class="me-3">
                        <v-icon icon="mdi-account-tie" />
                      </v-avatar>
                      <div>
                        <div class="text-caption text-medium-emphasis">Agent</div>
                        <div class="text-subtitle-2">{{ order.agent?.name || '-' }}</div>
                      </div>
                    </div>
                  </div>
                </v-col>

                <v-col cols="12" sm="6" md="4">
                  <div class="person-card pa-3 rounded border">
                    <div class="d-flex align-center mb-2">
                      <v-avatar size="32" color="warning" class="me-3">
                        <v-icon icon="mdi-motorbike" />
                      </v-avatar>
                      <div>
                        <div class="text-caption text-medium-emphasis">Rider</div>
                        <div class="text-subtitle-2">{{ order.rider?.name || '-' }}</div>
                      </div>
                    </div>
                  </div>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Order Items Section -->
          <v-card variant="outlined">
            <v-card-title class="text-h6 pa-4 pb-2">
              <v-icon icon="mdi-cart" class="me-2" />
              Order Items
              <v-spacer />
              <v-chip variant="outlined" size="small">
                {{ (order.orderItems || []).length }} items
              </v-chip>
            </v-card-title>
            <v-card-text class="pa-0">
              <v-table class="order-items-table">
                <thead>
                  <tr>
                    <th class="text-left pa-4">Product</th>
                    <th class="text-center pa-4">Quantity</th>
                    <th class="text-right pa-4">Unit Price</th>
                    <th class="text-right pa-4">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr 
                    v-for="(item, index) in order.orderItems || []" 
                    :key="item.id || index"
                    class="order-item-row"
                  >
                    <td class="pa-4">
                      <div class="text-subtitle-2">{{ item.product?.product_name || '-' }}</div>
                    </td>
                    <td class="text-center pa-4">
                      <v-chip variant="outlined" size="small">
                        {{ item.quantity || 0 }}
                      </v-chip>
                    </td>
                    <td class="text-right pa-4">
                      <div class="text-body-2">{{ formatCurrency(item.price) }}</div>
                    </td>
                    <td class="text-right pa-4">
                      <div class="text-subtitle-2 font-weight-medium">
                        {{ formatCurrency(item.total_price) }}
                      </div>
                    </td>
                  </tr>
                </tbody>
                <tfoot v-if="orderTotal">
                  <tr class="total-row">
                    <td colspan="3" class="text-right pa-4">
                      <strong>Total Amount:</strong>
                    </td>
                    <td class="text-right pa-4">
                      <div class="text-h6 font-weight-bold primary--text">
                        {{ formatCurrency(orderTotal) }}
                      </div>
                    </td>
                  </tr>
                </tfoot>
              </v-table>
              
              <div v-if="!order.orderItems || order.orderItems.length === 0" class="pa-8 text-center">
                <v-icon icon="mdi-cart-off" size="48" class="text-medium-emphasis mb-3" />
                <p class="text-body-1 text-medium-emphasis">No items in this order</p>
              </div>
            </v-card-text>
          </v-card>
        </div>
      </v-card-text>

      <!-- Actions -->
      <v-divider />
      <v-card-actions class="pa-6">
        <v-spacer />
        <v-btn
          variant="outlined"
          @click="dialog = false"
          class="me-3"
        >
          Close
        </v-btn>
        <v-btn
          color="primary"
          variant="flat"
          @click="updateOrder"
          :disabled="!order"
          :loading="updating"
        >
          <v-icon icon="mdi-pencil" start />
          Update Order
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, toRefs, ref } from 'vue'
import { useOrderStore } from '@/stores/orderStore' // Pinia store

// Pinia store usage
const orderStore = useOrderStore()
const { dialog } = toRefs(orderStore)

// Local state
const updating = ref(false)

// Compute the selected order based on selectedOrderId
const order = computed(() => {
  if (!orderStore.selectedOrderId || !orderStore.orders) return null
  return orderStore.orders.find(o => o.id === orderStore.selectedOrderId) || null
})

// Compute order total
const orderTotal = computed(() => {
  if (!order.value?.orderItems) return 0
  return order.value.orderItems.reduce((sum, item) => {
    return sum + (item.total_price || 0)
  }, 0)
})

// Helper functions
function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function formatDateTime(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function formatCurrency(amount) {
  if (amount == null) return '-'
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

function getStatusColor(status) {
  const statusColors = {
    'pending': 'warning',
    'confirmed': 'info',
    'processing': 'primary',
    'completed': 'success',
    'cancelled': 'error',
    'refunded': 'secondary'
  }
  return statusColors[status?.toLowerCase()] || 'default'
}

function getDeliveryStatusColor(status) {
  const statusColors = {
    'pending': 'warning',
    'assigned': 'info',
    'picked_up': 'primary',
    'in_transit': 'primary',
    'delivered': 'success',
    'failed': 'error',
    'returned': 'secondary'
  }
  return statusColors[status?.toLowerCase()] || 'default'
}

async function updateOrder() {
  if (!order.value) return
  
  updating.value = true
  try {
    // Add your update logic here
    await orderStore.updateOrder(order.value.id)
    // Could show success message
  } catch (error) {
    console.error('Failed to update order:', error)
    // Could show error message
  } finally {
    updating.value = false
  }
}
</script>

<style scoped>
.order-details-card {
  max-height: 90vh;
}

.person-card {
  background-color: rgb(var(--v-theme-surface-variant));
  border: 1px solid rgb(var(--v-border-color));
  transition: all 0.2s ease;
}

.person-card:hover {
  background-color: rgb(var(--v-theme-surface-bright));
}

.order-items-table {
  border-collapse: separate;
  border-spacing: 0;
}

.order-item-row:hover {
  background-color: rgb(var(--v-theme-surface-variant));
}

.total-row {
  background-color: rgb(var(--v-theme-surface-variant));
  border-top: 2px solid rgb(var(--v-border-color));
}

.total-row td {
  font-weight: 500;
}
</style>