<template>
  <AppLayout>
    <div class="p-6">
      <!-- Search Bar -->
      <div class="mb-6">
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search orders by (id, customer name, phone, address)"
            class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
          <button class="absolute right-3 top-3 text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Shipping Status -->
        <div>
          <label class="block text-sm font-semibold text-red-600 mb-2">SHIPPING STATUS</label>
          <select v-model="filters.shippingStatus" class="w-full px-3 py-2 border border-gray-300 rounded">
            <option value="">Shipping status</option>
            <option value="pending">Pending</option>
            <option value="dispatched">Dispatched</option>
            <option value="delivered">Delivered</option>
            <option value="returned">Returned</option>
          </select>
        </div>

        <!-- Call Agent -->
        <div>
          <label class="block text-sm font-semibold text-red-600 mb-2">CALL AGENT</label>
          <select v-model="filters.callAgent" class="w-full px-3 py-2 border border-gray-300 rounded">
            <option value="">Select the call agent</option>
            <option value="agent1">Agent 1</option>
            <option value="agent2">Agent 2</option>
          </select>
        </div>

        <!-- Seller -->
        <div>
          <label class="block text-sm font-semibold text-red-600 mb-2">SELLER</label>
          <select v-model="filters.seller" class="w-full px-3 py-2 border border-gray-300 rounded">
            <option value="">All sellers</option>
            <option value="pressed_beauty">PRESSED_BEAUTY</option>
          </select>
        </div>

        <!-- Product -->
        <div>
          <label class="block text-sm font-semibold text-red-600 mb-2">PRODUCT</label>
          <select v-model="filters.product" class="w-full px-3 py-2 border border-gray-300 rounded">
            <option value="">Search products...</option>
          </select>
        </div>

        <!-- Dispatched On -->
        <div>
          <label class="block text-sm font-semibold text-red-600 mb-2">DISPATCHED ON</label>
          <input v-model="filters.dispatchedOn" type="date" class="w-full px-3 py-2 border border-gray-300 rounded" />
        </div>

        <!-- Shipped On -->
        <div>
          <label class="block text-sm font-semibold text-red-600 mb-2">SHIPPED ON</label>
          <input v-model="filters.shippedOn" type="date" class="w-full px-3 py-2 border border-gray-300 rounded" />
        </div>

        <!-- Returned On -->
        <div>
          <label class="block text-sm font-semibold text-red-600 mb-2">RETURNED ON</label>
          <input v-model="filters.returnedOn" type="date" class="w-full px-3 py-2 border border-gray-300 rounded" />
        </div>

        <!-- Delivery Date -->
        <div>
          <label class="block text-sm font-semibold text-red-600 mb-2">DELIVERY DATE</label>
          <input v-model="filters.deliveryDate" type="date" class="w-full px-3 py-2 border border-gray-300 rounded" />
        </div>

        <!-- Riders -->
        <div>
          <label class="block text-sm font-semibold text-red-600 mb-2">RIDERS</label>
          <select v-model="filters.riders" class="w-full px-3 py-2 border border-gray-300 rounded">
            <option value="">Select the courier</option>
          </select>
        </div>

        <!-- City To -->
        <div>
          <label class="block text-sm font-semibold text-red-600 mb-2">CITY TO</label>
          <select v-model="filters.cityTo" class="w-full px-3 py-2 border border-gray-300 rounded">
            <option value="">city</option>
            <option value="nairobi">Nairobi</option>
          </select>
        </div>

        <!-- Inbound/Outbound -->
        <div>
          <label class="block text-sm font-semibold text-red-600 mb-2">INBOUND/OUTBOUND</label>
          <select v-model="filters.direction" class="w-full px-3 py-2 border border-gray-300 rounded">
            <option value="">Select the direction</option>
            <option value="inbound">Inbound</option>
            <option value="outbound">Outbound</option>
          </select>
        </div>

        <!-- Delivered On -->
        <div>
          <label class="block text-sm font-semibold text-red-600 mb-2">DELIVERED ON</label>
          <input v-model="filters.deliveredOn" type="date" class="w-full px-3 py-2 border border-gray-300 rounded" />
        </div>

        <!-- Created On -->
        <div>
          <label class="block text-sm font-semibold text-red-600 mb-2">CREATED ON</label>
          <input v-model="filters.createdOn" type="date" class="w-full px-3 py-2 border border-gray-300 rounded" />
        </div>

        <!-- Zone To -->
        <div>
          <label class="block text-sm font-semibold text-red-600 mb-2">ZONE TO</label>
          <select v-model="filters.zoneTo" class="w-full px-3 py-2 border border-gray-300 rounded">
            <option value="">Select the zone</option>
          </select>
        </div>

        <!-- Refunded On -->
        <div>
          <label class="block text-sm font-semibold text-red-600 mb-2">REFUNDED ON</label>
          <input v-model="filters.refundedOn" type="date" class="w-full px-3 py-2 border border-gray-300 rounded" />
        </div>
      </div>

      <!-- Filter and Reset Buttons -->
      <div class="flex gap-4 mb-6">
        <button @click="applyFilters" class="flex-1 bg-black text-white px-6 py-3 rounded hover:bg-gray-800 transition">
          Filter
        </button>
        <button @click="resetFilters" class="flex-1 bg-gray-200 text-gray-700 px-6 py-3 rounded hover:bg-gray-300 transition">
          Reset
        </button>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-wrap gap-3 mb-6">
        <button @click="scheduleReturned" class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600 transition">
          Schedule(returned)
        </button>
        <button @click="toPending" class="bg-cyan-400 text-white px-4 py-2 rounded hover:bg-cyan-500 transition">
          To pending
        </button>
        <button @click="printWaybill" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition">
          Print waybill
        </button>
        <button @click="shipAwaitingReturn" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 transition">
          Ship Awaiting Return Orders
        </button>
        <button @click="returnOrders" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
          Return orders
        </button>
        <button @click="validateDelivery" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
          Validate Delivery
        </button>
        <button @click="refund" class="bg-orange-400 text-white px-4 py-2 rounded hover:bg-orange-500 transition">
          Refund
        </button>
        <button @click="exportData" class="bg-black text-white px-4 py-2 rounded hover:bg-gray-800 transition">
          Export
        </button>
      </div>

      <!-- Table Header -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b">
          <h2 class="text-xl font-bold">Shipping</h2>
          <span class="text-sm text-gray-600">1 - 15 / 1338</span>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-blue-600 text-white">
              <tr>
                <th class="px-4 py-3 text-left">
                  <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" />
                </th>
                <th class="px-4 py-3 text-left">ORDER MERCHANT ID</th>
                <th class="px-4 py-3 text-left">ORDER NO</th>
                <th class="px-4 py-3 text-left">SELLER</th>
                <th class="px-4 py-3 text-left">SOURCE</th>
                <th class="px-4 py-3 text-left">DELIVERY DATE</th>
                <th class="px-4 py-3 text-left">DELIVERED ON</th>
                <th class="px-4 py-3 text-left">TRACKING NUMBER</th>
                <th class="px-4 py-3 text-left">DETAILS</th>
                <th class="px-4 py-3 text-left">ZONE TO</th>
                <th class="px-4 py-3 text-left">DELIVERY MAN</th>
                <th class="px-4 py-3 text-left">TOTAL PRICE</th>
                <th class="px-4 py-3 text-left">STATUS</th>
                <th class="px-4 py-3 text-left">ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in filteredOrders" :key="order.id" class="border-b hover:bg-gray-50">
                <td class="px-4 py-3">
                  <input type="checkbox" v-model="selectedOrders" :value="order.id" />
                </td>
                <td class="px-4 py-3">{{ order.merchantId }}</td>
                <td class="px-4 py-3">{{ order.orderNo }}</td>
                <td class="px-4 py-3">{{ order.seller }}</td>
                <td class="px-4 py-3">{{ order.source }}</td>
                <td class="px-4 py-3">{{ order.deliveryDate }}</td>
                <td class="px-4 py-3">{{ order.deliveredOn || '-' }}</td>
                <td class="px-4 py-3">{{ order.trackingNumber }}</td>
                <td class="px-4 py-3">
                  <span class="bg-pink-100 text-pink-800 px-2 py-1 rounded text-sm">
                    {{ order.productCount }} products
                  </span>
                </td>
                <td class="px-4 py-3">{{ order.zoneTo }}</td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <span class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center text-xs">
                      {{ order.deliveryMan.initials }}
                    </span>
                    <span class="text-sm">{{ order.deliveryMan.name }}</span>
                  </div>
                  <div class="text-xs text-gray-500 mt-1">{{ order.confirmedAt }}</div>
                </td>
                <td class="px-4 py-3">{{ order.totalPrice }} KES</td>
                <td class="px-4 py-3">
                  <span :class="getStatusClass(order.status)" class="px-3 py-1 rounded-full text-sm">
                    {{ order.status }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex gap-2">
                    <button @click="editOrder(order.id)" class="text-gray-600 hover:text-gray-800" title="Edit">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button @click="viewOrder(order.id)" class="text-blue-600 hover:text-blue-800" title="View">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button @click="reverseDelivery(order.id)" class="text-orange-600 hover:text-orange-800" title="Reverse Delivery">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                      </svg>
                    </button>
                    <button @click="downloadOrder(order.id)" class="text-green-600 hover:text-green-800" title="Download">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                      </svg>
                    </button>
                    <button @click="deleteOrder(order.id)" class="text-red-600 hover:text-red-800" title="Delete">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';


import AppLayout from "@/Layouts/AppLayout.vue";
import { useOrderStore } from '@/stores/orderStore'


// const orderStore = useShippingStore();
const orderStore = useOrderStore()


const searchQuery = ref('');
const selectAll = ref(false);
const selectedOrders = ref([]);

const filters = ref({
  shippingStatus: '',
  callAgent: '',
  seller: '',
  product: '',
  dispatchedOn: '',
  shippedOn: '',
  returnedOn: '',
  deliveryDate: '',
  riders: '',
  cityTo: '',
  direction: '',
  deliveredOn: '',
  createdOn: '',
  zoneTo: '',
  refundedOn: ''
});

// Sample orders data
const orders = ref([
  {
    id: 1,
    merchantId: 'PSBQCT025-1701',
    orderNo: 'KE-67859895',
    seller: 'PRESSED_BEAUTY',
    source: 'google_sheets',
    deliveryDate: '2025-10-13 08:46:00',
    deliveredOn: null,
    trackingNumber: 'KE78499S012',
    productCount: 2,
    zoneTo: 'Nairobi',
    deliveryMan: {
      name: 'Agatha Muema',
      initials: 'AM'
    },
    confirmedAt: 'confirmed at (2025-10-11 11:47:13)',
    totalPrice: 7000,
    status: 'scheduled'
  }
]);

const filteredOrders = computed(() => {
  return orders.value.filter(order => {
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase();
      return (
        order.merchantId.toLowerCase().includes(query) ||
        order.orderNo.toLowerCase().includes(query) ||
        order.trackingNumber.toLowerCase().includes(query)
      );
    }
    return true;
  });
});

const toggleSelectAll = () => {
  if (selectAll.value) {
    selectedOrders.value = filteredOrders.value.map(o => o.id);
  } else {
    selectedOrders.value = [];
  }
};

const applyFilters = () => {
  console.log('Applying filters:', filters.value);
  orderStore.applyFilters(filters.value);
};

const resetFilters = () => {
  filters.value = {
    shippingStatus: '',
    callAgent: '',
    seller: '',
    product: '',
    dispatchedOn: '',
    shippedOn: '',
    returnedOn: '',
    deliveryDate: '',
    riders: '',
    cityTo: '',
    direction: '',
    deliveredOn: '',
    createdOn: '',
    zoneTo: '',
    refundedOn: ''
  };
  searchQuery.value = '';
  selectedOrders.value = [];
};

const getStatusClass = (status) => {
  const classes = {
    scheduled: 'bg-yellow-100 text-yellow-800 border border-yellow-300',
    delivered: 'bg-green-100 text-green-800 border border-green-300',
    pending: 'bg-blue-100 text-blue-800 border border-blue-300',
    returned: 'bg-red-100 text-red-800 border border-red-300'
  };
  return classes[status] || 'bg-gray-100 text-gray-800 border border-gray-300';
};

// Action methods
const scheduleReturned = () => {
  console.log('Schedule returned orders:', selectedOrders.value);
  orderStore.scheduleReturned(selectedOrders.value);
};

const toPending = () => {
  console.log('Move to pending:', selectedOrders.value);
  orderStore.moveToPending(selectedOrders.value);
};

const printWaybill = () => {
  console.log('Print waybill for:', selectedOrders.value);
  orderStore.printWaybill(selectedOrders.value);
};

const shipAwaitingReturn = () => {
  console.log('Ship awaiting return orders:', selectedOrders.value);
  orderStore.shipAwaitingReturn(selectedOrders.value);
};

const returnOrders = () => {
  console.log('Return orders:', selectedOrders.value);
  orderStore.returnOrders(selectedOrders.value);
};

const validateDelivery = () => {
  console.log('Validate delivery:', selectedOrders.value);
  orderStore.validateDelivery(selectedOrders.value);
};

const refund = () => {
  console.log('Refund orders:', selectedOrders.value);
  orderStore.refundOrders(selectedOrders.value);
};

const exportData = () => {
  console.log('Export data');
  orderStore.exportData(selectedOrders.value);
};

const editOrder = (id) => {
  console.log('Edit order:', id);
  orderStore.editOrder(id);
};

const viewOrder = (id) => {
  console.log('View order:', id);
  orderStore.viewOrder(id);
};

const reverseDelivery = (id) => {
  console.log('Reverse delivery for order:', id);
  orderStore.reverseDelivery(id);
};

const downloadOrder = (id) => {
  console.log('Download order:', id);
  orderStore.downloadOrder(id);
};

const deleteOrder = (id) => {
  if (confirm('Are you sure you want to delete this order?')) {
    console.log('Delete order:', id);
    orderStore.deleteOrder(id);
  }
};
</script>