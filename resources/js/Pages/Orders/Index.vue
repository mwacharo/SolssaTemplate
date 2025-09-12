<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50">
      <div class="p-6">
        <!-- Search Bar -->
        <div class="mb-6">
          <div class="relative">
            <input v-model="orderStore.orderSearch" type="text"
              placeholder="Search orders by (id, customer name, phone, address)"
              class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              @input="debouncedSearch" />
            <button class="absolute right-2 top-2 p-1 text-gray-500 hover:text-gray-700">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </button>
          </div>
        </div>

        <!-- Filters -->
        <div class="bg-white p-4 rounded-lg shadow-sm mb-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
            <!-- Seller Filter -->
            <div>
              <label class="block text-sm font-medium text-red-600 mb-1">SELLER</label>
              <v-autocomplete
              v-model="orderStore.orderFilterVendor"
              :items="orderStore.vendorOptions"
              item-title="name"
              item-value="id"
              clearable
              dense
              outlined
              placeholder="Search sellers..."
              class="w-full"
              >
              </v-autocomplete>
            </div>

            <!-- Product Filter -->
            <div>
              <label class="block text-sm font-medium text-red-600 mb-1">PRODUCT</label>
              <select v-model="orderStore.orderFilterProduct"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                <option value="">Search products...</option>
                <option v-for="product in orderStore.productOptions" :key="product.id" :value="product.id">
                  {{ product.product_name }}
                </option>
              </select>
            </div>

            <!-- Category Filter -->
            <div>
              <label class="block text-sm font-medium text-red-600 mb-1">CATEGORY</label>
              <select v-model="orderStore.orderFilterCategory"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                <option value="">All categories</option>
                <option v-for="category in orderStore.categoryOptions" :key="category.id" :value="category.id">
                  {{ category.name }}
                </option>
              </select>
            </div>

            <!-- City Filter -->
            <div>
              <label class="block text-sm font-medium text-red-600 mb-1">CITY</label>
              <select v-model="orderStore.orderFilterCity"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                <option value="">All cities</option>
                <option v-for="city in orderStore.cityOptions" :key="city.id" :value="city.name">
                  {{ city.name }}
                </option>
              </select>
            </div>

            <!-- Call Agent Filter -->
            <div>
              <label class="block text-sm font-medium text-red-600 mb-1">CALL AGENT</label>
              <select v-model="orderStore.orderFilterAgent"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                <option value="">Select the call agent</option>
                <option v-for="agent in orderStore.agentOptions" :key="agent.id" :value="agent.id">
                  {{ agent.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <!-- Confirmation Status -->


            <div>
              <label class="block text-sm font-medium text-red-600 mb-1">CONFIRMATION STATUS</label>
              <v-autocomplete
              v-model="orderStore.orderFilterStatus"
              :items="orderStore.statusOptions"
              item-title="name"
              item-value="id"
              clearable
              dense
              outlined
              placeholder="Search statuses..."
              class="w-full"
              />
            </div>

            <!-- Created From -->
            <div>
              <label class="block text-sm font-medium text-red-600 mb-1">CREATED FROM</label>
              <input v-model="orderStore.orderDateRange[0]" type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                placeholder="Filter by date" />
            </div>

            <!-- Created To -->
            <div>
              <label class="block text-sm font-medium text-red-600 mb-1">CREATED TO</label>
              <input v-model="orderStore.orderDateRange[1]" type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                placeholder="Filter by date" />
            </div>
          </div>

          <!-- Filter Actions -->
          <div class="flex justify-center gap-4">
            <button @click="applyFilters" :disabled="orderStore.loading.orders"
              class="px-8 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition-colors flex items-center gap-2 disabled:opacity-50">
              <span v-if="orderStore.loading.orders"
                class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
              Filter
              <svg v-if="!orderStore.loading.orders" class="w-4 h-4" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.121A1 1 0 013 6.414V4z">
                </path>
              </svg>
            </button>
            <button @click="resetFilters"
              class="px-8 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors flex items-center gap-2">
              Reset
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                </path>
              </svg>
            </button>
          </div>

          <!-- Active Filters Display -->
          <div v-if="orderStore.activeFilterCount > 0" class="mt-4 flex items-center gap-2 text-sm text-gray-600">
            <span>Active filters: {{ orderStore.activeFilterCount }}</span>
            <button @click="resetFilters" class="text-red-600 hover:text-red-800 underline">Clear all</button>
          </div>
        </div>

        <!-- Orders Header -->
        <div class="flex justify-between items-center mb-4">
          <div class="flex items-center gap-2">
            <h1 class="text-2xl font-semibold">Orders</h1>
            <span class="text-sm text-gray-500">
              {{ orderStore.pagination.from }} - {{ orderStore.pagination.to }}/{{ orderStore.pagination.total }}
            </span>
          </div>

          <div class="flex gap-2 flex-wrap">
            <button
              class="px-3 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors flex items-center gap-2 text-sm">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Disassociate agents
            </button>

            <button @click="newOrder"
              class="px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors flex items-center gap-2 text-sm">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              New Order
            </button>

            <button @click="exportOrders" :disabled="orderStore.loading.orders"
              class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center gap-2 text-sm disabled:opacity-50">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z">
                </path>
              </svg>
              Export Orders
            </button>

            <button @click="bulkUpdateStatus" :disabled="selectedOrders.length === 0 || orderStore.loading.orders"
              class="px-3 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-colors flex items-center gap-2 text-sm disabled:opacity-50">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                </path>
              </svg>
              Change Status ({{ selectedOrders.length }})
            </button>

            <button @click="bulkDelete" :disabled="selectedOrders.length === 0 || orderStore.loading.orders"
              class="px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors flex items-center gap-2 text-sm disabled:opacity-50">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                </path>
              </svg>
              Bulk Delete ({{ selectedOrders.length }})
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="orderStore.loading.orders && orderStore.orders.length === 0"
          class="bg-white rounded-lg shadow-sm p-8 text-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p class="text-gray-600">Loading orders...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="orderStore.error && orderStore.orders.length === 0"
          class="bg-white rounded-lg shadow-sm p-8 text-center">
          <div class="text-red-600 mb-4">
            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z">
              </path>
            </svg>
          </div>
          <p class="text-gray-600 mb-4">{{ orderStore.error }}</p>
          <button @click="refreshOrders" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Retry
          </button>
        </div>

        <!-- Orders Table -->
        <div v-else class="bg-white rounded-lg shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-blue-600 text-white">
                <tr>
                  <th class="px-4 py-3 text-left">
                    <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="rounded"
                      aria-label="Select all orders">
                  </th>
                  <th class="px-4 py-3 text-left font-medium">ORDER MERCHANT ID</th>
                  <th class="px-4 py-3 text-left font-medium">ORDER NO</th>
                  <th class="px-4 py-3 text-left font-medium">SELLER</th>
                  <th class="px-4 py-3 text-left font-medium">SOURCE</th>
                  <th class="px-4 py-3 text-left font-medium">CUSTOMER</th>
                  <th class="px-4 py-3 text-left font-medium">DETAILS</th>
                  <th class="px-4 py-3 text-left font-medium">ADDRESS</th>
                  <th class="px-4 py-3 text-left font-medium">DATE</th>
                  <th class="px-4 py-3 text-left font-medium">TOTAL PRICE</th>
                  <th class="px-4 py-3 text-left font-medium">STATUS</th>
                  <th class="px-4 py-3 text-left font-medium">ACTIONS</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="orderStore.orders.length === 0">
                  <td colspan="12" class="px-4 py-8 text-center text-gray-500">
                    No orders found
                  </td>
                </tr>
                <tr v-for="order in orderStore.orders" :key="order.id" :class="[
                  'border-b hover:bg-gray-50',
                  order.status === 1 ? 'bg-green-50'
                    : order.status === 0 ? 'bg-yellow-50'
                      : order.status === 2 ? 'bg-red-50'
                        : 'bg-white'
                ]">
                  <td class="px-4 py-3">
                    <input type="checkbox" v-model="selectedOrders" :value="order.id" class="rounded">
                  </td>
                  <td class="px-4 py-3 font-mono text-sm">{{ order.reference || '-' }}</td>
                  <td class="px-4 py-3 font-mono text-sm">{{ order.order_no || '-' }}</td>
                  <td class="px-4 py-3 text-sm">{{ getVendorName(order.vendor_id) }}</td>
                  <td class="px-4 py-3">
                    <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded text-xs">
                      {{ order.source || 'Unknown' }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <div class="text-sm">
                      <div>{{ order.shipping_address?.full_name || '-' }}</div>
                      <div class="text-gray-500">{{ order.shipping_address?.phone || '-' }}</div>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <span
                      class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 text-red-800 rounded text-xs">
                      <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                      {{ order.order_items?.length || 0 }} product{{ order.order_items?.length !== 1 ? 's' : '' }}
                    </span>
                  </td>



                  <td class="px-4 py-3 text-sm">
                    <div>{{ order.shipping_address?.city || '-' }}</div>
                    <div class="text-gray-500 truncate max-w-32">{{ order.shipping_address?.address || '-' }}
                    </div>
                  </td>


                  <td class="px-4 py-3 text-sm">
                    <div>{{ formatDate(order.created_at) }}</div>
                  </td>


                  <!-- total_price -->

                  <td class="px-4 py-3 font-mono text-sm">${{ order.total_price || '0.00' }}</td>
                 
                 
                 
                  <td class="px-4 py-3">
                    <div class="flex flex-col gap-1">
                      <span
                        class="px-2 py-1 text-xs rounded"
                        :style="order.status_timestamps?.status?.color ? { backgroundColor: order.status_timestamps.status.color, color: 'white' } : {}"
                      >
                        {{ order.status_timestamps?.status?.name || orderStatusLabel(order.status) }}
                      </span>
                      <div v-if="order.status_timestamps?.created_at"
                        class="text-xs text-gray-600 bg-green-100 px-1 py-0.5 rounded">
                        Status at {{ formatDateTime(order.status_timestamps.created_at) }}
                      </div>
                      <div class="text-xs text-center">
                        <img v-if="order.agent && order.agent.avatar" :src="order.agent.avatar"
                          :alt="order.agent.name" class="w-6 h-6 rounded-full mx-auto">
                        <span v-else>-</span>
                      </div>
                    </div>
                  </td>





                  <td class="px-4 py-3">
                    <div class="flex gap-1 flex-wrap">
                      <button @click="editOrder(order.id)" class="p-1 text-orange-600 hover:bg-orange-100 rounded"
                        title="Edit Order">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                          </path>
                        </svg>
                      </button>
                      <button @click="viewOrder(order)" class="p-1 text-blue-600 hover:bg-blue-100 rounded"
                        title="View Order">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                          </path>
                        </svg>
                      </button>
                      <button @click="duplicateOrder(order)" class="p-1 text-green-600 hover:bg-green-100 rounded"
                        title="Duplicate Order">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                          </path>
                        </svg>
                      </button>
                      <button @click="deleteOrder(order)" class="p-1 text-red-600 hover:bg-red-100 rounded"
                        title="Delete Order">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                          </path>
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="flex justify-between items-center px-6 py-4 bg-gray-50">
            <div class="text-sm text-gray-700">
              Showing {{ orderStore.pagination.from }} to {{ orderStore.pagination.to }} of {{
                orderStore.pagination.total }} results
            </div>
            <div class="flex items-center gap-2">
              <button @click="previousPage"
                :disabled="orderStore.pagination.current_page === 1 || orderStore.loading.orders"
                class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed text-sm">
                Previous
              </button>

              <button v-for="page in visiblePages" :key="page" @click="goToPage(page)"
                :disabled="orderStore.loading.orders" :class="[
                  'px-3 py-1 border rounded-md text-sm',
                  page === orderStore.pagination.current_page
                    ? 'bg-blue-600 text-white border-blue-600'
                    : 'border-gray-300 hover:bg-gray-100 disabled:opacity-50'
                ]">
                {{ page }}
              </button>

              <button @click="nextPage"
                :disabled="orderStore.pagination.current_page === orderStore.pagination.last_page || orderStore.loading.orders"
                class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed text-sm">
                Next
              </button>
            </div>
          </div>
        </div>

        <!-- Notifications -->
        <div class="fixed bottom-4 right-4 z-50 space-y-2">
          <div v-for="notification in orderStore.notifications" :key="notification.id" :class="[
            'px-6 py-4 rounded-lg shadow-lg text-white max-w-sm',
            notification.type === 'success' ? 'bg-green-600' : 'bg-red-600'
          ]">
            <div class="flex justify-between items-center">
              <p class="text-sm">{{ notification.message }}</p>
              <button @click="orderStore.removeNotification(notification.id)"
                class="ml-4 text-white hover:text-gray-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                  </path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <CallDialogs />
    <OrderForm
      @order-saved="onOrderSaved"
      @dialog-closed="onDialogClosed"
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useOrderStore } from '@/stores/orderStore'
import { useCallCenterStore } from '@/stores/callCenter';
import OrderForm from './OrderForm.vue';

// Initialize store
const orderStore = useOrderStore()

const createMode = ref(false)

// Component state
const selectedOrders = ref([])
const selectAll = ref(false)

// Computed properties
const visiblePages = computed(() => {
  const current = orderStore.pagination.current_page
  const last = orderStore.pagination.last_page
  const pages = []

  // Show max 5 pages
  const start = Math.max(1, current - 2)
  const end = Math.min(last, start + 4)

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  return pages
})

// Methods
const editOrder = (id) => {
  orderStore.openDialog(id) 
}

const newOrder = async() => {
  await orderStore.fetchDropdownOptions()
  orderStore.openCreateDialog() 
}

// emitted events
const onOrderSaved = () => {
  // refresh orders list or show toast
  refreshOrders()
}

const onDialogClosed = () => {
  // optional cleanup
}

const debouncedSearch = async () => {
  await orderStore.applyFilters()
}

const resetFilters = async () => {
  orderStore.clearAllFilters()
  await orderStore.applyFilters()
}

const applyFilters = async () => {
  await orderStore.applyFilters()
}

const refreshOrders = async () => {
  await orderStore.fetchOrders({ page: orderStore.pagination.current_page })
}

const toggleSelectAll = () => {
  if (selectAll.value) {
    selectedOrders.value = orderStore.orders.map(order => order.id)
  } else {
    selectedOrders.value = []
  }
}

const formatDate = (dateStr) => {
  const options = { year: 'numeric', month: 'short', day: 'numeric' }
  return new Date(dateStr).toLocaleDateString(undefined, options)
}

const formatTime = (dateStr) => {
  const options = { hour: '2-digit', minute: '2-digit' }
  return new Date(dateStr).toLocaleTimeString(undefined, options)
}

const formatDateTime = (dateStr) => {
  const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }
  return new Date(dateStr).toLocaleString(undefined, options)
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount)
}

const orderStatusLabel = (status) => {
  switch (status) {
    case 1: return 'Confirmed'
    case 0: return 'Pending'
    case 2: return 'Cancelled'
    default: return 'Unknown'
  }
}

const getVendorName = (vendorId) => {
  const vendor = orderStore.vendorOptions.find(v => v.id === vendorId)
  return vendor ? vendor.name : 'Unknown'
}

const viewOrder = (order) => {
  // Open the view dialog for the selected order
  orderStore.openViewDialog(order.id)
}

const duplicateOrder = (order) => {
  orderStore.duplicateOrder(order)
}

const deleteOrder = (order) => {
  orderStore.deleteOrder(order.id)
}

const exportOrders = () => {
  orderStore.exportOrders()
}

const bulkUpdateStatus = () => {
  orderStore.openBulkStatusDialog(selectedOrders.value)
}

const bulkDelete = () => {
  orderStore.bulkDelete(selectedOrders.value)
}

const previousPage = async () => {
  if (orderStore.pagination.current_page > 1) {
    await orderStore.fetchOrders({ page: orderStore.pagination.current_page - 1 })
    selectAll.value = false
    selectedOrders.value = []
  }
}

const nextPage = async () => {
  if (orderStore.pagination.current_page < orderStore.pagination.last_page) {
    await orderStore.fetchOrders({ page: orderStore.pagination.current_page + 1 })
    selectAll.value = false
    selectedOrders.value = []
  }
}

const goToPage = async (page) => {
  if (page !== orderStore.pagination.current_page) {
    await orderStore.fetchOrders({ page })
    selectAll.value = false
    selectedOrders.value = []
  }
}

// Watch for selection changes
watch(selectedOrders, (newSelection) => {
  const shouldSelectAll = newSelection.length === orderStore.orders.length && orderStore.orders.length > 0
  if (selectAll.value !== shouldSelectAll) {
    selectAll.value = shouldSelectAll
  }
})

// Lifecycle hooks
onMounted(async () => {
  await orderStore.initialize()
  await orderStore.fetchOrders({ page: 1 })
})

</script>