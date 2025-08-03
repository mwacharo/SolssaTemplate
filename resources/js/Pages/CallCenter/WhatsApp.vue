<script setup>
import { ref, onMounted, computed, toRefs, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import { useWhatsAppStore } from '@/stores/whatsappStore'
import { useAuthStore } from '@/stores/auth'
import { useConversationStore } from '@/stores/useConversationStore'

import WhatsAppConversation from '@/Pages/CallCenter/WhatsAppConversation.vue'
import AssignDialog from './AssignDialog.vue';
import { useOrderStore } from '@/stores/orderStore' // Adjust path as needed

import OrderDialogs from './Dialogs/OrderDialogs.vue';


import { notify } from '@/utils/toast';



// Initialize the store
const store = useWhatsAppStore()
const conversationStore = useConversationStore()
const orderStore = useOrderStore()


const dialogMode = ref(null);
const showDialog = ref(false);

const openDialog = (mode, selectedOrders = []) => {
  dialogMode.value = mode;
  showDialog.value = true;
  // Set selectedOrders in the store or local ref if needed
  if (selectedOrders && selectedOrders.length) {
    console.log('openDialog selectedOrders:', JSON.stringify(selectedOrders));
    store.selectedOrders = selectedOrders;
  }
};

const handleConfirm = ({ mode, selected, orders }) => {
  console.log('handleConfirm called with:', JSON.stringify({ mode, selected, orders }));
  if (mode === 'rider') {
    assignRider(selected, orders);
  } else if (mode === 'agent') {
    assignAgent(selected, orders);
  } else if (mode === 'status') {
    updateStatus(selected, orders);
  }
};

const assignRider = async (riderId, orders) => {
  try {
    const response = await axios.post('/api/v1/assign-rider', {
      rider_id: riderId,
      order_ids: orders,
    });

    notify.success(response.data.message || 'Rider assigned successfully');
  } catch (error) {
    // notify.error(error.response?.data?.message || 'Failed to assign rider');
       notify.error('Failed to assign rider');

  }
};

const assignAgent = async (agentId, orders) => {
  try {
    const response = await axios.post('/api/v1/assign-agent', {
      agent_id: agentId,
      order_ids: orders,
    });

    notify.success(response.data.message || 'Agent assigned successfully');
  } catch (error) {
    notify.error(error.response?.data?.message || 'Failed to assign agent');
  }
};

const updateStatus = async (status, orders) => {
  try {
    const response = await axios.post('/api/v1/update-status', {
      status: status,
      order_ids: orders,
    });

    notify.success(response.data.message || 'Status updated successfully');
  } catch (error) {
    notify.error(error.response?.data?.message || 'Failed to update status');
  }
};

const viewOrderDetails = (order) => {

      orderStore.openDialog(order)

}
// Local UI state (not managed by store)
const selectedPhone = ref(null);
const dialog = ref(false);
const conversation = ref([])

const auth = useAuthStore()

const user = computed(() => auth.user)
const userId = computed(() => user.value?.id)

console.log('User:', JSON.stringify(user.value))

console.log('User ID:', userId.value)



const orderHeaders = [
  { title: 'Order #', key: 'order_no' },
  { title: 'Customer', key: 'client' },
  { title: 'Vendor', key: 'vendor' },
  { title: 'Status', key: 'status' },
  { title: 'Delivery Status', key: 'delivery_status' },
  { title: 'Items', key: 'order_items' },
  { title: 'Total Price', key: 'total_price' },
  { title: 'Date Created', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false }
]

// Contact type options
const contactTypes = [
  { title: 'All Types', value: 'all' },
  { title: 'Customer', value: 'customer' },
  { title: 'Vendor', value: 'vendor' },
  { title: 'Partner', value: 'partner' },
  { title: 'Employee', value: 'employee' }
];

// Status options
const statusOptions = [
  { title: 'All Statuses', value: 'all' },
  { title: 'Active', value: 1 },
  { title: 'Inactive', value: 0 }
];

// Order status options
const orderStatusOptions = [
  { title: 'All Orders', value: 'all' },
  { title: 'Pending', value: 'pending' },
  { title: 'Processing', value: 'processing' },
  { title: 'Shipped', value: 'shipped' },
  { title: 'Delivered', value: 'delivered' },
  { title: 'Cancelled', value: 'cancelled' }
];

// Computed properties from store


const orderDateRangeText = computed(() => {
  if (!orderStore.orderDateRange || orderStore.orderDateRange.length === 0) {
    return ''
  }
  if (orderStore.orderDateRange.length === 1) {
    return formatDate(orderStore.orderDateRange[0])
  }
  return `${formatDate(orderStore.orderDateRange[0])} - ${formatDate(orderStore.orderDateRange[1])}`
})

const hasActiveFilters = computed(() => {
  return !!(
    orderStore.orderFilterStatus ||
    orderStore.orderFilterProduct ||
    orderStore.orderFilterZone ||
    orderStore.orderFilterAgent ||
    orderStore.orderFilterRider ||
    orderStore.orderFilterVendor ||
    (orderStore.orderDateRange && orderStore.orderDateRange.length > 0) ||
    orderStore.orderSearch
  )
})
const {
  // Data

  // conversation 
  attachment,
  replyMessage,
  messages,
  contacts,
  orders,
  templates,
  selectedContacts,
  selectedOrders,
  selectedTemplate,
  messageText,

  // Pagination
  currentPage,
  perPage,
  totalMessages,
  totalOrders,

  // Loading states
  loading,

  // UI states
  showImportDialog,
  showNewMessageDialog,
  // showTemplateDialog,
  showOrderImportDialog,
  showOrderMessageDialog,
  activeTab,

  // Filters
  search,
  filterType,
  filterStatus,
  orderFilters,

  // Messages
  errorMessage,
  successMessage,

  // Statistics
  stats,
  whatsappStatus,

  // File uploads
  csvFile,
  orderFile
} = toRefs(store)

// Computed properties from store getters
const filteredContacts = computed(() => store.filteredContacts)
const filteredOrders = computed(() => store.filteredOrders)
const filteredMessages = computed(() => store.filteredMessages)
const validWhatsappContacts = computed(() => store.validWhatsappContacts)
const totalPages = computed(() => store.totalPages)
const totalOrderPages = computed(() => store.totalOrderPages)
const allTemplates = computed(() => store.allTemplates)

// Store action methods
const showError = (message) => store.showError(message)
const showSuccess = (message) => store.showSuccess(message)
const formatPhoneNumber = (phone) => store.formatPhoneNumber(phone)
const loadMessages = (page = 1) => store.loadMessages(page)
const loadContacts = () => store.loadContacts()
const loadOrders = (page = 1) => store.loadOrders(page)
const loadTemplates = () => store.loadTemplates()
// new
const loadRiders = () => store.loadRiders()
const loadAgents = () => store.loadAgents()
const calculateStats = () => store.calculateStats()
const calculateOrderStats = () => store.calculateOrderStats()
const onTemplateSelect = (selectedTemplate) => store.onTemplateSelect(selectedTemplate)
const resetFilters = () => store.resetFilters()
const sendMessage = () => store.sendMessage(userId.value)

const sendOrderMessage = () => store.sendOrderMessage({ userId: userId.value })
const importContacts = () => store.importContacts({ userId: userId.value })
const importOrders = () => store.importOrders({ userId: userId.value })
// const openNewMessageDialog = () => store.openNewMessageDialog()

const openNewMessageDialog = (selectedOrders) => {
  store.openNewMessageDialog(selectedOrders)
}

const openOrderMessageDialog = () => store.openOrderMessageDialog()
const deleteMessage = (messageId) => store.deleteMessage(messageId)

// Local methods (not in store)

const getTotalQuantity = (items) => {
  return items.reduce((total, item) => total + (item.quantity || 0), 0)
}

const openOrderPrint = (orderId) => {
  window.open(`/api/v1/orders/${orderId}/print-waybill`, '_blank');
}
const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  });
}
const openSendMessage = (isBulk = false, contact = null) => {
  store.errorMessage = ''
  store.messageText = ''

  if (isBulk) {
    store.selectedContacts = store.selectedContacts
  } else if (contact) {
    store.selectedContacts = [contact]
  } else {
    store.selectedContacts = []
  }

  if ((!Array.isArray(store.templates) || store.templates.length === 0) && !store.loading.templates) {
    store.loadTemplates()
  }

  store.showNewMessageDialog = true
}


// In your <script setup>
const viewMessageDetails = (message) => {
  console.log('viewMessageDetails called with:', message) // Debug log
  console.log('conversationStore:', conversationStore) // Debug log

  if (message.to) {
    console.log('Calling openDialog with:', message.to) // Debug log
    conversationStore.openDialog(message.to)
  } else {
    console.log("Missing contact ID in message", message)
  }
}

const hasWhatsAppNumber = (contact) => {
  return Boolean(contact.whatsapp || contact.alt_phone || contact.phone)
}

const viewContact = (contact) => {
  alert(`Contact: ${contact.name}
Phone: ${contact.phone || 'N/A'}
WhatsApp: ${contact.whatsapp || 'N/A'}
Type: ${contact.type || 'N/A'}
Company: ${contact.company_name || 'N/A'}
Country: ${contact.country_name || 'N/A'}`)
}


const statusColor = (status) => ({
  active: 'success',
  inactive: 'grey',
  pending: 'warning',
  processing: 'primary',
  cancelled: 'error'
}[status?.toLowerCase()] || 'grey')

const deliveryStatusColor = (status) => ({
  inprogress: 'warning',
  delivered: 'success',
  cancelled: 'error',
  pending: 'primary',
  shipped: 'info'
}[status?.toLowerCase()] || 'grey')




// Watch for search changes
watch(() => store.search, (newValue) => {
  if (newValue === '') {
    store.loadMessages(store.currentPage)
  }
})

// Helper functions for conversation dialog
function getContactName() {
  // Try to find the contact by selectedPhone
  if (!selectedPhone.value) return 'Unknown';
  const contact = contacts.value?.find(
    c => c.phone === selectedPhone.value || c.whatsapp === selectedPhone.value
  );
  return contact?.name || selectedPhone.value;
}

function getContactPhone() {
  return selectedPhone.value || '-';
}

// Add these functions for status chip in conversation dialog
function getStatusColor() {
  // You can adjust this logic based on your actual status variable
  if (whatsappStatus.value === 'Connected') return 'success';
  if (whatsappStatus.value === 'Connecting...') return 'warning';
  return 'error';
}

function getConnectionStatus() {
  // You can adjust this logic based on your actual status variable
  return whatsappStatus.value || 'Unknown';
}


const handleFilterChange = () => {
  // Apply filters immediately for select dropdowns
  applyFilters()
}

const handleSearchChange = () => {
  // Debounce search to avoid too many API calls
  debouncedFilter.value()
}

const handleDateChange = () => {
  orderDateMenu.value = false
  applyFilters()
}

const clearDateRange = () => {
  orderStore.orderDateRange = []
  applyFilters()
}

const clearAllFilters = () => {
  orderStore.clearAllFilters()
  applyFilters()
}

const applyFilters = async () => {
  try {
    orderStore.loading.orders = true

    // Build filter object
    const filters = {
      status: orderStore.orderFilterStatus,
      product: orderStore.orderFilterProduct,
      zone: orderStore.orderFilterZone,
      agent: orderStore.orderFilterAgent,
      rider: orderStore.orderFilterRider,
      vendor: orderStore.orderFilterVendor,
      dateRange: orderStore.orderDateRange,
      search: orderStore.orderSearch
    }

    // Remove null/empty values
    const cleanFilters = Object.fromEntries(
      Object.entries(filters).filter(([_, value]) =>
        value !== null && value !== '' &&
        !(Array.isArray(value) && value.length === 0)
      )
    )

    // Apply filters using store action
    await orderStore.loadOrdersWithFilters(cleanFilters)

  } catch (error) {
    console.error('Error applying filters:', error)
    // Handle error appropriately
  } finally {
    orderStore.loading.orders = false
  }
}

const refreshData = async () => {
  try {
    orderStore.loading.refresh = true

    // Refresh data using store actions
    await Promise.all([
      orderStore.loadOrderStatusOptions(),
      orderStore.loadProductOptions(),
      orderStore.loadZoneOptions(),
      orderStore.loadAgentOptions(),
      orderStore.loadRiderOptions(),
      orderStore.loadVendorOptions()
    ])

    // Reapply current filters
    await applyFilters()
  } catch (error) {
    console.error('Error refreshing data:', error)
  } finally {
    orderStore.loading.refresh = false
  }
}

// Show filter dialog
const showFilterDialog = ref(false);

function openFilterDialog() {
  showFilterDialog.value = true;
}

// const formatDate = (date) => {
//   if (!date) return ''
//   return new Date(date).toLocaleDateString()
// }

// Component mount
onMounted(async () => {
  // Initialize the store which will load all data
  await store.initialize()
    // notify.success('🎉 Toastify is working!')

})
</script>
<template>
  <AppLayout>

    <Head title="WhatsApp Business - Courier Management" />

    <v-container fluid>
      <!-- Alert for errors and success messages -->
      <v-alert v-if="errorMessage" type="error" closable class="mb-4">
        {{ errorMessage }}
      </v-alert>

      <v-alert v-if="successMessage" type="success" closable class="mb-4">
        {{ successMessage }}
      </v-alert>

      <v-row>
        <!-- Left sidebar with status and actions -->
        <v-col cols="12" lg="3">
          <v-card class="mb-4">
            <v-card-text class="text-center">
              <v-avatar size="80" :color="whatsappStatus === 'Connected' ? 'green' : 'grey'" class="mb-3">
                <v-icon size="48" color="white">mdi-whatsapp</v-icon>
              </v-avatar>
              <h2 class="text-h6">WhatsApp Business</h2>
              <h3 class="text-subtitle-2 text-grey">Courier Management System</h3>

              <div class="d-flex justify-center align-center mt-2">
                <v-chip :color="whatsappStatus === 'Connected' ? 'success' :
                  whatsappStatus === 'Connecting...' ? 'warning' : 'error'" class="mr-2">
                  {{ whatsappStatus }}
                </v-chip>
              </div>

              <v-divider class="my-4"></v-divider>

              <!-- Message Stats -->
              <div class="mb-4">
                <h4 class="text-subtitle-1 mb-2">Message Stats</h4>
                <v-row>
                  <v-col cols="6" class="py-1">
                    <div class="text-subtitle-2">Sent</div>
                    <div class="text-h6 text-primary">{{ stats.sent }}</div>
                  </v-col>
                  <v-col cols="6" class="py-1">
                    <div class="text-subtitle-2">Delivered</div>
                    <div class="text-h6 text-success">{{ stats.delivered }}</div>
                  </v-col>
                  <v-col cols="6" class="py-1">
                    <div class="text-subtitle-2">Read</div>
                    <div class="text-h6 text-info">{{ stats.read }}</div>
                  </v-col>
                  <v-col cols="6" class="py-1">
                    <div class="text-subtitle-2">Failed</div>
                    <div class="text-h6 text-error">{{ stats.failed }}</div>
                  </v-col>
                </v-row>
              </div>

              <v-divider class="my-4"></v-divider>

              <!-- Order Stats -->
              <div class="mb-4">
                <h4 class="text-subtitle-1 mb-2">Order Stats</h4>
                <v-row>
                  <v-col cols="6" class="py-1">
                    <div class="text-subtitle-2">Total Orders</div>
                    <div class="text-h6 text-primary">{{ stats.totalOrders }}</div>
                  </v-col>
                  <v-col cols="6" class="py-1">
                    <div class="text-subtitle-2">Pending</div>
                    <div class="text-h6 text-warning">{{ stats.pendingOrders }}</div>
                  </v-col>
                  <v-col cols="12" class="py-1">
                    <div class="text-subtitle-2">Delivered</div>
                    <div class="text-h6 text-success">{{ stats.deliveredOrders }}</div>
                  </v-col>
                </v-row>
              </div>

              <v-divider class="my-4"></v-divider>

              <!-- Action Buttons -->
              <v-btn color="primary" block class="mb-2" @click="openNewMessageDialog">
                <v-icon class="mr-2">mdi-message-text</v-icon>
                New Message
              </v-btn>

              <v-btn color="success" block class="mb-2" @click="openOrderMessageDialog">
                <v-icon class="mr-2">mdi-package-variant</v-icon>
                Order Messages
              </v-btn>

              <v-btn color="info" block class="mb-2" @click="showImportDialog = true">
                <v-icon class="mr-2">mdi-file-import</v-icon>
                Import Contacts
              </v-btn>

              <v-btn color="orange" block class="mb-2" @click="showOrderImportDialog = true">
                <v-icon class="mr-2">mdi-file-excel</v-icon>
                Import Orders
              </v-btn>

              <!-- <v-btn color="secondary" block @click="showTemplateDialog = true">
                <v-icon class="mr-2">mdi-file-document-edit</v-icon>
                Manage Templates
              </v-btn> -->
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Main content area -->
        <v-col cols="12" lg="9">
          <v-card>
            <v-tabs v-model="activeTab" color="primary">
              <v-tab value="messages">
                <v-icon class="mr-2">mdi-message-text</v-icon>
                Messages
              </v-tab>
              <v-tab value="orders">
                <v-icon class="mr-2">mdi-package-variant</v-icon>
                Orders
              </v-tab>
              <v-tab value="contacts">
                <v-icon class="mr-2">mdi-account-multiple</v-icon>
                Contacts
              </v-tab>
            </v-tabs>

            <v-window v-model="activeTab">
              <!-- Messages Tab -->
              <v-window-item value="messages">
                <v-card-title class="d-flex flex-wrap justify-space-between align-center">
                  <div class="text-h6">Recent Messages</div>
                  <v-text-field v-model="search" append-icon="mdi-magnify" label="Search messages" single-line
                    hide-details density="compact" class="max-w-xs mt-2 mt-sm-0"></v-text-field>
                </v-card-title>

                <v-card-text>
                  <v-progress-linear v-if="loading.messages" indeterminate color="primary"></v-progress-linear>

                  <div v-else-if="!Array.isArray(filteredMessages) || filteredMessages.length === 0"
                    class="text-center pa-4">
                    <v-icon size="large" color="grey">mdi-message-text-outline</v-icon>
                    <p class="text-body-1 mt-2">No messages found. Try sending one!</p>
                  </div>

                  <v-table v-else>
                    <thead>
                      <tr>
                        <th class="text-left">Content</th>
                        <th class="text-left">Recipient</th>
                        <th class="text-left">Order #</th>
                        <th class="text-left">Status</th>
                        <th class="text-left">Date</th>
                        <th class="text-center">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="message in filteredMessages" :key="message.id">
                        <td>
                          <span>{{ message.content }}</span>
                        </td>
                        <td>
                          <div>{{ message.recipient_name || 'N/A' }}</div>
                          <div class="text-caption text-grey">
                            {{ formatPhoneNumber(message.to) }}
                          </div>
                        </td>
                        <td>
                          {{ message.order_number || '-' }}
                        </td>
                        <td>
                          <v-chip :color="{
                            sent: 'primary',
                            delivered: 'success',
                            read: 'info',
                            failed: 'error',
                            pending: 'warning'
                          }[message.status?.toLowerCase()] || 'grey'" small>
                            {{ message.status || 'Unknown' }}
                          </v-chip>
                        </td>
                        <td>
                          {{ message.sent_at || message.created_at?.split('T')[0] || '-' }}
                        </td>
                        <td class="text-center">
                          <v-btn icon size="small" @click="viewMessageDetails(message)">
                            <v-icon>mdi-eye</v-icon>
                          </v-btn>
                          <v-btn icon size="small" color="error" @click="deleteMessage(message.id)">
                            <v-icon>mdi-delete</v-icon>
                          </v-btn>
                        </td>
                      </tr>
                    </tbody>
                  </v-table>

                  <!-- Pagination -->
                  <div class="d-flex justify-end mt-4">
                    <v-pagination v-model="currentPage" :length="totalPages" @input="loadMessages"></v-pagination>
                  </div>
                </v-card-text>
              </v-window-item>
              <!-- Orders Tab -->
              <v-window-item value="orders">
                <!-- Header -->
                <v-card-title class="d-flex flex-wrap justify-space-between align-center">
                  <div class="text-h6">Orders</div>

                  <v-text-field v-model="search" append-icon="mdi-magnify" label="Search orders"
                    placeholder="Client, Order #, Phone" single-line hide-details density="compact" clearable
                    class="max-w-xs" @keyup.enter="loadOrders" />

                  <div class="d-flex align-center gap-2 flex-wrap">
                    <v-btn icon @click="openFilterDialog" :loading="loading.orders" color="primary"
                      title="Apply Filters">
                      <v-icon>mdi-filter</v-icon>
                    </v-btn>
                  </div>
                </v-card-title>

                <!-- Bulk Actions Bar -->
                <v-card-text v-if="selectedOrders.length > 0" class="py-2">
                  <v-alert type="info" variant="tonal" class="mb-0">
                    <div class="d-flex align-center justify-space-between flex-wrap gap-2">
                      <div class="d-flex align-center gap-2">
                        <v-icon>mdi-checkbox-marked</v-icon>
                        <span>{{ selectedOrders.length }} order(s) selected</span>
                        <v-btn variant="text" size="small" @click="selectedOrders = []">
                          Clear Selection
                        </v-btn>
                      </div>

                      <div class="d-flex align-center gap-2 flex-wrap">
                        <v-btn color="error" variant="outlined" size="small" @click="showBulkDeleteDialog = true">
                          <v-icon start>mdi-delete</v-icon>
                          Delete Selected
                        </v-btn>
                        <v-btn color="primary" variant="outlined" size="small"
                          @click="openDialog('rider', selectedOrders)">
                          <v-icon start>mdi-motorbike</v-icon>
                          Assign Rider
                        </v-btn>

                        <v-btn color="secondary" variant="outlined" size="small"
                          @click="openDialog('agent', selectedOrders)">
                          <v-icon start>mdi-account-tie</v-icon>
                          Assign Agent
                        </v-btn>


                        <v-btn color="success" variant="outlined" size="small"
                          @click="openNewMessageDialog(selectedOrders)">
                          <v-icon start>mdi-whatsapp</v-icon>
                          Send Messages
                        </v-btn>

                        <v-btn color="warning" variant="outlined" size="small"
                          @click="openDialog('status', selectedOrders)">
                          <v-icon start>mdi-update</v-icon>
                          Update Status
                        </v-btn>


                        <!-- <v-btn color="success" variant="outlined" size="small" @click="openNewMessageDialog ">
                          <v-icon start>mdi-whatsapp</v-icon>
                          Send Messages
                        </v-btn> -->




                      </div>
                    </div>
                  </v-alert>
                </v-card-text>

                <!-- Orders Table -->
                <v-card-text>
                  <v-progress-linear v-if="loading.orders" indeterminate color="primary"></v-progress-linear>

                  <v-data-table v-else v-model="selectedOrders" v-model:page="currentOrderPage" :headers="orderHeaders"
                    :items="orders" :items-per-page="perPage" :server-items-length="totalOrderCount"
                    :loading="loading.orders" :search="search" show-select item-value="id" class="elevation-1">
                    <!-- Custom Columns -->
                    <template #item.order_no="{ item }">
                      <strong>{{ item.order_no }}</strong>
                      <div v-if="item.reference" class="text-caption text-grey">
                        Ref: {{ item.reference }}
                      </div>
                    </template>

                    <template #item.client="{ item }">
                      <div>{{ item.client?.name || 'N/A' }}</div>
                      <div class="text-caption text-grey">
                        {{ formatPhoneNumber(item.client?.phone_number) }}
                      </div>
                      <div v-if="item.client?.city" class="text-caption text-grey">
                        {{ item.client.city }}
                      </div>
                    </template>

                    <template #item.vendor="{ item }">
                      <div>{{ item.vendor?.name || 'N/A' }}</div>
                      <div v-if="item.vendor?.company_name" class="text-caption text-grey">
                        {{ item.vendor.company_name }}
                      </div>
                    </template>

                    <template #item.status="{ item }">
                      <v-chip :color="statusColor(item.status)" small>
                        {{ item.status || 'Unknown' }}
                      </v-chip>
                    </template>

                    <template #item.delivery_status="{ item }">
                      <v-chip :color="deliveryStatusColor(item.delivery_status)" small>
                        {{ item.delivery_status || 'Unknown' }}
                      </v-chip>
                    </template>

                    <template #item.order_items="{ item }">
                      <div v-if="item.order_items?.length">
                        {{ item.order_items.length }} item(s)
                        <div class="text-caption text-grey">
                          Qty: {{ getTotalQuantity(item.order_items) }}
                        </div>
                      </div>
                      <div v-else class="text-grey">No items</div>
                    </template>

                    <template #item.total_price="{ item }">
                      <div>
                        KSH{{ parseFloat(item.total_price || item.invoice_value || 0).toFixed(2) }}
                      </div>
                      <div v-if="item.shipping_charges && item.shipping_charges !== '0.00'"
                        class="text-caption text-grey">
                        Shipping: KSH{{ parseFloat(item.shipping_charges).toFixed(2) }}
                      </div>
                    </template>

                    <template #item.created_at="{ item }">
                      {{ formatDate(item.created_at) }}
                      <div v-if="item.delivery_date" class="text-caption text-grey">
                        Delivery: {{ formatDate(item.delivery_date) }}
                      </div>
                    </template>

                    <template #item.actions="{ item }">
                      <v-btn icon size="small" @click="viewOrderDetails(item)">
                        <v-icon>mdi-eye</v-icon>
                      </v-btn>
                      <v-btn icon size="small" color="primary" @click="sendOrderMessage([item])">
                        <v-icon>mdi-whatsapp</v-icon>

                      </v-btn>
                        <v-btn icon size="small" color="info" @click="openOrderPrint(item.id)">
                        <v-icon>mdi-printer</v-icon>
                        </v-btn>
                    </template>
                  </v-data-table>
                </v-card-text>
              </v-window-item>

              <!-- Contacts Tab -->
              <v-window-item value="contacts">
                <v-card-title class="d-flex flex-wrap justify-space-between align-center">
                  <div class="text-h6">Contacts</div>
                  <div class="d-flex">
                    <v-select v-model="filterType" :items="contactTypes" item-title="title" item-value="value"
                      label="Type" hide-details density="compact" class="mr-2"></v-select>
                    <v-select v-model="filterStatus" :items="statusOptions" item-title="title" item-value="value"
                      label="Status" hide-details density="compact"></v-select>
                    <v-btn icon class="ml-2" @click="resetFilters">
                      <v-icon>mdi-filter-remove</v-icon>
                    </v-btn>
                  </div>
                </v-card-title>

                <v-card-text>
                  <v-progress-linear v-if="loading.contacts" indeterminate color="primary"></v-progress-linear>

                  <div v-else-if="!Array.isArray(filteredContacts) || filteredContacts.length === 0"
                    class="text-center pa-4">
                    <v-icon size="large" color="grey">mdi-account-multiple</v-icon>
                    <p class="text-body-1 mt-2">No contacts found.</p>
                  </div>

                  <v-table v-else>
                    <thead>
                      <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Company</th>
                        <th>Country</th>
                        <th class="text-center">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="contact in filteredContacts" :key="contact.id">
                        <td>{{ contact.name }}</td>
                        <td>
                          <div>{{ formatPhoneNumber(contact.phone) }}</div>
                          <div v-if="contact.whatsapp" class="text-caption text-success">
                            WhatsApp: {{ formatPhoneNumber(contact.whatsapp) }}
                          </div>
                        </td>
                        <td>{{ contact.type || '-' }}</td>
                        <td>
                          <v-chip :color="contact.status === 1 ? 'success' : 'grey'" small>
                            {{ contact.status === 1 ? 'Active' : 'Inactive' }}
                          </v-chip>
                        </td>
                        <td>{{ contact.company_name || '-' }}</td>
                        <td>{{ contact.country_name || '-' }}</td>
                        <td class="text-center">
                          <v-btn icon size="small" @click="viewContact(contact)">
                            <v-icon>mdi-eye</v-icon>
                          </v-btn>
                          <v-btn icon size="small" color="primary" :disabled="!hasWhatsAppNumber(contact)"
                            @click="openSendMessage(false, contact)">
                            <v-icon>mdi-whatsapp</v-icon>
                          </v-btn>
                        </td>
                      </tr>
                    </tbody>
                  </v-table>
                </v-card-text>
              </v-window-item>
            </v-window>
          </v-card>
        </v-col>
      </v-row>

      <!-- Dialogs and Modals -->


      <!-- filter dialog -->

      <v-dialog v-model="showFilterDialog" max-width="400" content-class="custom-filter-dialog" persistent scrollable>
        <!-- filter dialog -->
        <v-card>
          <v-card-title>
            <span class="text-h6">Order Filters</span>
          </v-card-title>
          <v-card-text>
            <div class="d-flex flex-column gap-2">
              <!-- Status Filter -->
              <v-select v-model="orderStore.orderFilterStatus" :items="orderStore.orderStatusOptions" item-title="title"
                item-value="value" label="Status" hide-details density="compact" clearable class="mb-2"
                @update:model-value="handleFilterChange" />
              <!-- Product Filter -->
              <v-select v-model="orderStore.orderFilterProduct" :items="orderStore.productOptions" item-title="name"
                item-value="id" label="Product" hide-details density="compact" clearable class="mb-2"
                @update:model-value="handleFilterChange" />
              <!-- Zone Filter -->
              <v-select v-model="orderStore.orderFilterZone" :items="orderStore.zoneOptions" item-title="name"
                item-value="id" label="Zone" hide-details density="compact" clearable class="mb-2"
                @update:model-value="handleFilterChange" />
              <!-- Agent Filter -->
              <v-select v-model="orderStore.orderFilterAgent" :items="orderStore.agentOptions" item-title="name"
                item-value="id" label="Agent" hide-details density="compact" clearable class="mb-2"
                @update:model-value="handleFilterChange" />
              <!-- Rider Filter -->
              <v-select v-model="orderStore.orderFilterRider" :items="orderStore.riderOptions" item-title="name"
                item-value="id" label="Rider" hide-details density="compact" clearable class="mb-2"
                @update:model-value="handleFilterChange" />
              <!-- Vendor Filter -->
              <v-select v-model="orderStore.orderFilterVendor" :items="orderStore.vendorOptions" item-title="name"
                item-value="id" label="Vendor" hide-details density="compact" clearable class="mb-2"
                @update:model-value="handleFilterChange" />
              <!-- Date Range Filter -->
              <v-menu v-model="orderDateMenu" :close-on-content-click="false" transition="scale-transition" offset-y
                min-width="auto">
                <template #activator="{ props }">
                  <v-text-field v-model="orderDateRangeText" label="Date Range" prepend-icon="mdi-calendar" readonly
                    v-bind="props" hide-details density="compact" clearable class="mb-2"
                    @click:clear="clearDateRange" />
                </template>
                <v-date-picker v-model="orderStore.orderDateRange" range @update:model-value="handleDateChange" />
              </v-menu>
            </div>
          </v-card-text>
          <v-card-actions>
            <v-btn text color="primary" @click="clearAllFilters">Clear All</v-btn>
            <v-spacer />
            <v-btn text @click="showFilterDialog = false">Close</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>



      <v-dialog v-model="store.showNewMessageDialog" max-width="600">
        <v-card>
          <v-card-title>
            <span class="text-h6">Send WhatsApp Message</span>
          </v-card-title>
          <v-card-text>
            <!-- Recipients -->
            <v-select v-model="store.selectedContacts" :items="store.validWhatsappContacts" item-title="name"
              item-value="id" label="Select Recipients" multiple chips :disabled="store.loading.contacts"
              :loading="store.loading.contacts" return-object />

            <!-- Template Select -->
            <v-select v-model="store.selectedTemplate" :items="store.allTemplates" item-title="name" item-value="id"
              label="Select Template" return-object :disabled="store.loading.templates"
              :loading="store.loading.templates" @update:model-value="store.onTemplateSelect" class="mt-3" clearable />

            <!-- Message Preview -->
            <v-textarea v-model="store.messageText" label="Message" rows="4" auto-grow class="mt-3"
              placeholder="Type your message or select a template above" />
          </v-card-text>

          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="primary" :loading="store.loading.sending" @click="sendMessage"
              :disabled="!store.messageText.trim()">
              Send
            </v-btn>
            <v-btn text @click="store.showNewMessageDialog = false">Cancel</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Order Message Dialog -->
      <v-dialog v-model="showOrderMessageDialog" max-width="600">
        <v-card>
          <v-card-title>
            <span class="text-h6">Send Order Message</span>
          </v-card-title>
          <v-card-text>
            <v-select v-model="selectedOrders" :items="orders" item-title="order_no" item-value="id"
              label="Select Orders" multiple chips :disabled="loading.orders" :loading="loading.orders"
              return-object></v-select>
            <v-select v-model="selectedTemplate" :items="orderTemplates" item-title="name" item-value="name"
              label="Select Order Template" @change="p" class="mt-3" return-object></v-select>
            <v-textarea v-model="messageText" label="Message" rows="4" auto-grow class="mt-3"></v-textarea>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="success" :loading="loading.sending" @click="sendOrderMessage">
              Send
            </v-btn>
            <v-btn text @click="showOrderMessageDialog = false">Cancel</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Import Contacts Dialog -->
      <v-dialog v-model="showImportDialog" max-width="500">
        <v-card>
          <v-card-title>
            <span class="text-h6">Import Contacts</span>
          </v-card-title>
          <v-card-text>
            <v-file-input v-model="csvFile" label="Select CSV File" accept=".csv"
              prepend-icon="mdi-file"></v-file-input>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="info" :loading="loading.importing" @click="importContacts">
              Import
            </v-btn>
            <v-btn text @click="showImportDialog = false">Cancel</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Import Orders Dialog -->
      <v-dialog v-model="showOrderImportDialog" max-width="500">
        <v-card>
          <v-card-title>
            <span class="text-h6">Import Orders</span>
          </v-card-title>
          <v-card-text>
            <v-file-input v-model="orderFile" label="Select Excel or CSV File" accept=".csv,.xlsx,.xls"
              prepend-icon="mdi-file"></v-file-input>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="orange" :loading="loading.uploadingOrders" @click="importOrders">
              Import
            </v-btn>
            <v-btn text @click="showOrderImportDialog = false">Cancel</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Message Details Dialog -->
      <v-dialog v-model="dialog" max-width="800" persistent>
        <v-card class="conversation-dialog">
          <!-- Header with contact info -->
          <v-card-title class="conversation-header pa-4">
            <div class="d-flex align-center w-100">
              <v-avatar color="primary" size="40" class="mr-3">
                <v-icon color="white">mdi-account</v-icon>
              </v-avatar>
              <div class="flex-grow-1">
                <div class="text-h6 mb-0">{{ getContactName() }}</div>
                <div class="text-caption text-grey">{{ getContactPhone() }}</div>
              </div>
              <v-chip :color="getStatusColor()" small outlined class="mr-2">
                {{ getConnectionStatus() }}
              </v-chip>
              <v-btn icon @click="dialog = false">
                <v-icon>mdi-close</v-icon>
              </v-btn>
            </div>
          </v-card-title>

          <v-divider />

          <!-- Messages Container -->
          <v-card-text class="pa-0">
            <v-progress-linear v-if="loading.value" indeterminate color="primary" />

            <div v-else class="messages-container" ref="messagesContainer">
              <div v-if="Array.isArray(conversation) && conversation.length" class="pa-4">
                <div v-for="msg in conversation" :key="msg.id" class="message-wrapper mb-3"
                  :class="getMessageAlignment(msg)">
                  <!-- Message Bubble -->
                  <div class="message-bubble elevation-1" :class="getMessageBubbleClass(msg)">
                    <!-- Sender name for incoming messages -->
                    <div v-if="isIncomingMessage(msg)" class="message-sender text-caption font-weight-medium mb-1">
                      {{ msg.sender_name || getContactName() }}
                    </div>

                    <!-- Message content -->
                    <div class="message-content">
                      {{ msg.content }}
                    </div>

                    <!-- Media attachments -->
                    <div v-if="msg.image_url" class="message-media mt-2">
                      <v-img :src="msg.image_url" max-height="200" max-width="300" contain class="rounded"
                        @click="openImagePreview(msg.image_url)" style="cursor: pointer;" />
                    </div>

                    <div v-if="msg.audio_url" class="message-media mt-2">
                      <div class="audio-container">
                        <v-btn icon small @click="toggleAudio(msg.id)" class="mr-2">
                          <v-icon>{{ audioStates[msg.id]?.playing ? 'mdi-pause' : 'mdi-play' }}</v-icon>
                        </v-btn>
                        <audio :ref="`audio-${msg.id}`" :src="msg.audio_url" @ended="onAudioEnded(msg.id)"
                          style="display: none;"></audio>
                        <span class="text-caption">Voice message</span>
                      </div>
                    </div>

                    <!-- Message timestamp and status -->
                    <div class="message-meta d-flex align-center justify-end mt-1">
                      <span class="text-caption text-grey mr-2">
                        {{ formatTimestamp(msg.created_at || msg.timestamp) }}
                      </span>
                      <v-icon v-if="isOutgoingMessage(msg)" :color="getStatusIconColor(msg.message_status)" size="14">
                        {{ getStatusIcon(msg.message_status) }}
                      </v-icon>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Empty state -->
              <div v-else class="empty-state pa-8 text-center">
                <v-icon size="64" color="grey lighten-2">mdi-message-outline</v-icon>
                <div class="text-h6 mt-4 text-grey">No messages yet</div>
                <div class="text-caption text-grey">Start the conversation below</div>
              </div>
            </div>
          </v-card-text>

          <!-- Reply Input Section -->
          <v-divider />
          <v-card-text class="reply-section pa-4">
            <!-- Attachment Preview -->
            <div v-if="hasAttachment" class="attachment-preview mb-3">
              <v-chip v-if="attachment.image && attachment.image.length" close @click:close="attachment.image = null"
                color="blue lighten-4" class="mr-2">
                <v-icon left small>mdi-image</v-icon>
                {{ attachment.image[0]?.name }}
              </v-chip>
              <v-chip v-if="attachment.audio && attachment.audio.length" close @click:close="attachment.audio = null"
                color="green lighten-4">
                <v-icon left small>mdi-microphone</v-icon>
                {{ attachment.audio[0]?.name }}
              </v-chip>
            </div>

            <!-- Message Input -->
            <div class="message-input-container">
              <v-textarea v-model="replyMessage" label="Type your message..." rows="2" auto-grow outlined dense
                hide-details class="message-input" @keydown.enter.exact.prevent="sendMessage"
                @keydown.enter.shift.exact="addNewLine" />

              <!-- Input Actions -->
              <div class="input-actions d-flex align-center mt-2">
                <div class="d-flex align-center flex-grow-1">
                  <!-- Emoji Button -->
                  <v-btn icon small @click="showEmojiPicker = !showEmojiPicker" class="mr-2">
                    <v-icon>mdi-emoticon-happy-outline</v-icon>
                  </v-btn>

                  <!-- Attachment Buttons -->
                  <v-menu offset-y>
                    <template v-slot:activator="{ on, attrs }">
                      <v-btn icon small v-bind="attrs" v-on="on" class="mr-2">
                        <v-icon>mdi-attachment</v-icon>
                      </v-btn>
                    </template>
                    <v-list dense>
                      <v-list-item @click="$refs.imageInput.click()">
                        <v-list-item-icon>
                          <v-icon>mdi-image</v-icon>
                        </v-list-item-icon>
                        <v-list-item-content>
                          <v-list-item-title>Image</v-list-item-title>
                        </v-list-item-content>
                      </v-list-item>
                      <v-list-item @click="$refs.audioInput.click()">
                        <v-list-item-icon>
                          <v-icon>mdi-microphone</v-icon>
                        </v-list-item-icon>
                        <v-list-item-content>
                          <v-list-item-title>Voice Note</v-list-item-title>
                        </v-list-item-content>
                      </v-list-item>
                    </v-list>
                  </v-menu>

                  <!-- Hidden file inputs -->
                  <input ref="imageInput" type="file" accept="image/*" @change="handleImageUpload"
                    style="display: none;" />
                  <input ref="audioInput" type="file" accept="audio/*" @change="handleAudioUpload"
                    style="display: none;" />

                  <!-- Character count -->
                  <span v-if="replyMessage.length > 0" class="text-caption text-grey ml-2">
                    {{ replyMessage.length }}
                  </span>
                </div>

                <!-- Send Button -->
                <v-btn color="primary" :disabled="!canSendMessage" @click="sendMessage" class="ml-2" :loading="sending">
                  <v-icon>mdi-send</v-icon>
                </v-btn>
              </div>

              <!-- Emoji Picker -->
              <v-expand-transition>
                <div v-if="showEmojiPicker" class="emoji-picker mt-3 pa-3">
                  <div class="emoji-grid">
                    <v-btn v-for="emoji in commonEmojis" :key="emoji" text x-small @click="appendEmoji(emoji)"
                      class="emoji-btn">
                      {{ emoji }}
                    </v-btn>
                  </div>
                </div>
              </v-expand-transition>
            </div>
          </v-card-text>
        </v-card>

        <!-- Image Preview Dialog -->
        <v-dialog v-model="imagePreviewDialog" max-width="90vw">
          <v-card>
            <v-card-title class="pa-2">
              <v-spacer />
              <v-btn icon @click="imagePreviewDialog = false">
                <v-icon>mdi-close</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text class="pa-2">
              <v-img :src="previewImageUrl" contain max-height="80vh" />
            </v-card-text>
          </v-card>
        </v-dialog>
      </v-dialog>
      <!-- Order Details Dialog -->
      <!-- <v-dialog v-model="showOrderDetailsDialog" max-width="800">
        <v-card>
          <v-card-title>
            <span class="text-h6">Order Details - {{ selectedOrder?.order_no }}</span>
          </v-card-title>
          <v-card-text>
            <div v-if="selectedOrder">
              <v-row>
                <v-col cols="6">
                  <h4>Customer Information</h4>
                  <p><strong>Name:</strong> {{ selectedOrder.client?.name || 'N/A' }}</p>
                  <p><strong>Phone:</strong> {{ formatPhoneNumber(selectedOrder.client?.phone_number) }}</p>
                  <p><strong>City:</strong> {{ selectedOrder.client?.city || 'N/A' }}</p>
                </v-col>
                <v-col cols="6">
                  <h4>Order Information</h4>
                  <p><strong>Status:</strong> {{ selectedOrder.status }}</p>
                  <p><strong>Delivery Status:</strong> {{ selectedOrder.delivery_status }}</p>
                  <p><strong>Platform:</strong> {{ selectedOrder.platform }}</p>
                </v-col>
              </v-row>

              <v-divider class="my-4"></v-divider>

              <h4>Order Items</h4>
              <v-table v-if="selectedOrder.order_items && selectedOrder.order_items.length">
                <thead>
                  <tr>
                    <th>Product ID</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in selectedOrder.order_items" :key="item.id">
                    <td>{{ item.product_id }}</td>
                    <td>{{ item.quantity }}</td>
                    <td>${{ parseFloat(item.price || 0).toFixed(2) }}</td>
                    <td>${{ parseFloat(item.total_price || 0).toFixed(2) }}</td>
                  </tr>
                </tbody>
              </v-table>
              <p v-else>No items found for this order.</p>
            </div>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn text @click="showOrderDetailsDialog = false">Close</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
 -->

      <!-- bulk actions -->

      <!-- Reusable Dialog -->
      <AssignDialog v-model="showDialog" :mode="dialogMode" :selected-orders="selectedOrders"
        @confirmed="handleConfirm" />

      <WhatsAppConversation />
      <OrderDialogs/>

    </v-container>

  </AppLayout>
</template>
<style scoped>
.conversation-dialog {
  height: 90vh;
  display: flex;
  flex-direction: column;
}

.conversation-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.messages-container {
  height: calc(90vh - 200px);
  overflow-y: auto;
  scroll-behavior: smooth;
}

.message-wrapper {
  display: flex;
  width: 100%;
}

.message-wrapper.outgoing {
  justify-content: flex-end;
}

.message-wrapper.incoming {
  justify-content: flex-start;
}

.message-bubble {
  max-width: 70%;
  padding: 12px 16px;
  border-radius: 18px;
  position: relative;
  word-wrap: break-word;
}

.message-bubble.outgoing {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-bottom-right-radius: 4px;
}

.message-bubble.incoming {
  background: #f5f5f5;
  color: #333;
  border-bottom-left-radius: 4px;
}

.message-content {
  line-height: 1.4;
}

.message-sender {
  color: #666;
  font-size: 11px;
}

.message-meta {
  margin-top: 4px;
  font-size: 11px;
}

.reply-section {
  background: #fafafa;
  border-top: 1px solid #e0e0e0;
}

.message-input-container {
  position: relative;
}

.attachment-preview {
  border: 1px dashed #ccc;
  border-radius: 8px;
  padding: 8px;
  background: #f9f9f9;
}

.emoji-picker {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  background: white;
}

.emoji-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
  gap: 4px;
}

.emoji-btn {
  min-width: 40px !important;
  font-size: 18px;
}

.audio-container {
  display: flex;
  align-items: center;
  padding: 8px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
}

.empty-state {
  height: 300px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

/* Scrollbar styling */
.messages-container::-webkit-scrollbar {
  width: 6px;
}

.messages-container::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.messages-container::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.messages-container::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>