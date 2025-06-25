<script setup>
import { ref, onMounted, computed, toRefs, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import { useWhatsAppStore } from '@/stores/whatsappStore'
import { useAuthStore } from '@/stores/auth'
import { useConversationStore } from '@/stores/useConversationStore'

import WhatsAppConversation from '@/Pages/CallCenter/WhatsAppConversation.vue'




// Initialize the store
const store = useWhatsAppStore()
const conversationStore = useConversationStore()


// Local UI state (not managed by store)
const selectedPhone = ref(null);
const dialog = ref(false);
const conversation = ref([]);


const auth = useAuthStore()

const user = computed(() => auth.user)
const userId = computed(() => user.value?.id)

console.log('User:', JSON.stringify(user.value))

console.log('User ID:', userId.value)

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
const calculateStats = () => store.calculateStats()
const calculateOrderStats = () => store.calculateOrderStats()
const onTemplateSelect = (templateId) => store.onTemplateSelect(templateId)
const resetFilters = () => store.resetFilters()
const sendMessage = () => store.sendMessage(userId.value)

const sendOrderMessage = () => store.sendOrderMessage({ userId: userId.value })
const importContacts = () => store.importContacts({ userId: userId.value })
const importOrders = () => store.importOrders({ userId: userId.value })
const openNewMessageDialog = () => store.openNewMessageDialog()
const openOrderMessageDialog = () => store.openOrderMessageDialog()
const deleteMessage = (messageId) => store.deleteMessage(messageId)

// Local methods (not in store)

const getTotalQuantity = (items) => {
  return items.reduce((total, item) => total + (item.quantity || 0), 0)
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

// Component mount
onMounted(async () => {
  // Initialize the store which will load all data
  await store.initialize()
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
                <v-card-title class="d-flex flex-wrap justify-space-between align-center">
                  <div class="text-h6">Orders</div>
                  <div class="d-flex align-center gap-2">
                    <v-select v-model="orderFilterStatus" :items="orderStatusOptions" item-title="title"
                      item-value="value" label="Filter by Status" hide-details density="compact"
                      class="max-w-xs"></v-select>
                    <v-btn icon @click="loadOrders" :loading="loading.orders">
                      <v-icon>mdi-refresh</v-icon>
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
                        <v-btn variant="text" size="small" @click="clearSelection">
                          Clear Selection
                        </v-btn>
                      </div>

                      <div class="d-flex align-center gap-2 flex-wrap">
                        <!-- Bulk Delete -->
                        <v-btn color="error" variant="outlined" size="small" @click="showBulkDeleteDialog = true"
                          :loading="loading.bulkActions">
                          <v-icon start>mdi-delete</v-icon>
                          Delete Selected
                        </v-btn>

                        <!-- Bulk Assign Rider -->
                        <v-btn color="primary" variant="outlined" size="small" @click="showAssignRiderDialog = true"
                          :loading="loading.bulkActions">
                          <v-icon start>mdi-motorbike</v-icon>
                          Assign Rider
                        </v-btn>

                        <!-- Bulk Send Messages -->
                        <v-btn color="success" variant="outlined" size="small" @click="showBulkMessageDialog = true"
                          :loading="loading.bulkActions">
                          <v-icon start>mdi-whatsapp</v-icon>
                          Send Messages
                        </v-btn>

                        <!-- Bulk Status Update -->
                        <v-btn color="warning" variant="outlined" size="small" @click="showBulkStatusDialog = true"
                          :loading="loading.bulkActions">
                          <v-icon start>mdi-update</v-icon>
                          Update Status
                        </v-btn>
                      </div>
                    </div>
                  </v-alert>
                </v-card-text>

                <v-card-text>
                  <v-progress-linear v-if="loading.orders" indeterminate color="primary"></v-progress-linear>

                  <div v-else-if="!Array.isArray(filteredOrders) || filteredOrders.length === 0"
                    class="text-center pa-4">
                    <v-icon size="large" color="grey">mdi-package-variant</v-icon>
                    <p class="text-body-1 mt-2">No orders found.</p>
                  </div>

                  <v-table v-else>
                    <thead>
                      <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Vendor</th>
                        <th>Status</th>
                        <th>Delivery Status</th>
                        <th>Items</th>
                        <th>Total Price</th>
                        <th>Date Created</th>
                        <th class="text-center">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="order in filteredOrders" :key="order.id">
                        <td>
                          <strong>{{ order.order_no }}</strong>
                          <div v-if="order.reference" class="text-caption text-grey">
                            Ref: {{ order.reference }}
                          </div>
                        </td>
                        <td>
                          <div>{{ order.client?.name || 'N/A' }}</div>
                          <div class="text-caption text-grey">
                            {{ formatPhoneNumber(order.client?.phone_number) }}
                          </div>
                          <div v-if="order.client?.city" class="text-caption text-grey">
                            {{ order.client.city }}
                          </div>
                        </td>
                        <td>
                          <div>{{ order.vendor?.name || 'N/A' }}</div>
                          <div v-if="order.vendor?.company_name" class="text-caption text-grey">
                            {{ order.vendor.company_name }}
                          </div>
                        </td>
                        <td>
                          <v-chip :color="{
                            active: 'success',
                            inactive: 'grey',
                            pending: 'warning',
                            processing: 'primary',
                            cancelled: 'error'
                          }[order.status?.toLowerCase()] || 'grey'" small>
                            {{ order.status || 'Unknown' }}
                          </v-chip>
                        </td>
                        <td>
                          <v-chip :color="{
                            'inprogress': 'warning',
                            'delivered': 'success',
                            'cancelled': 'error',
                            'pending': 'primary',
                            'shipped': 'info'
                          }[order.delivery_status?.toLowerCase()] || 'grey'" small>
                            {{ order.delivery_status || 'Unknown' }}
                          </v-chip>
                        </td>
                        <td>
                          <div v-if="order.order_items && order.order_items.length">
                            {{ order.order_items.length }} item(s)
                            <div class="text-caption text-grey">
                              Qty: {{ getTotalQuantity(order.order_items) }}
                            </div>
                          </div>
                          <div v-else class="text-grey">No items</div>
                        </td>
                        <td>
                          <div v-if="order.total_price">
                            ${{ parseFloat(order.total_price).toFixed(2) }}
                          </div>
                          <div v-else-if="order.invoice_value && order.invoice_value !== '0.00'">
                            ${{ parseFloat(order.invoice_value).toFixed(2) }}
                          </div>
                          <div v-else class="text-grey">$0.00</div>
                          <div v-if="order.shipping_charges && order.shipping_charges !== '0.00'"
                            class="text-caption text-grey">
                            Shipping: ${{ parseFloat(order.shipping_charges).toFixed(2) }}
                          </div>
                        </td>
                        <td>
                          {{ formatDate(order.created_at) }}
                          <div v-if="order.delivery_date" class="text-caption text-grey">
                            Delivery: {{ formatDate(order.delivery_date) }}
                          </div>
                        </td>
                        <td class="text-center">
                          <v-btn icon size="small" @click="viewOrderDetails(order)">
                            <v-icon>mdi-eye</v-icon>
                          </v-btn>
                          <v-btn icon size="small" color="primary" @click="sendOrderMessage([order])">
                            <v-icon>mdi-whatsapp</v-icon>
                          </v-btn>
                        </td>
                      </tr>
                    </tbody>
                  </v-table>

                  <div class="d-flex justify-end mt-4">
                    <v-pagination v-model="currentOrderPage" :length="totalOrderPages"
                      @input="loadOrders"></v-pagination>
                  </div>
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
      <!-- New Message Dialog -->
      <v-dialog v-model="showNewMessageDialog" max-width="600">
        <v-card>
          <v-card-title>
            <span class="text-h6">Send WhatsApp Message</span>
          </v-card-title>
          <v-card-text>
            <v-select v-model="selectedContacts" :items="validWhatsappContacts" item-title="name" item-value="id"
              label="Select Recipients" multiple chips :disabled="loading.contacts" :loading="loading.contacts"
              return-object></v-select>
            <v-select v-model="selectedTemplate" :items="templates" item-title="name" item-value="id"
              label="Select Template" @change="onTemplateSelect" :disabled="loading.templates"
              :loading="loading.templates" return-object class="mt-3"></v-select>
            <v-textarea v-model="messageText" label="Message" rows="4" auto-grow class="mt-3"></v-textarea>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn color="primary" :loading="loading.sending" @click="sendMessage">
              Send
            </v-btn>
            <v-btn text @click="showNewMessageDialog = false">Cancel</v-btn>
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
      <v-dialog v-model="showOrderDetailsDialog" max-width="800">
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
      <WhatsAppConversation />

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