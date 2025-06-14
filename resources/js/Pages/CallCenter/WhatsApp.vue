<script setup>
import { ref, onMounted, computed, toRefs, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import { useWhatsAppStore } from '@/stores/whatsappStore'

// Initialize the store
const store = useWhatsAppStore()

// Local UI state (not managed by store)
const selectedPhone = ref(null);
const dialog = ref(false);
const conversation = ref([]);

// Get user ID
const userId = computed(() => usePage().props.value.user?.id);

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
  showTemplateDialog,
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
const sendOrderMessage = () => store.sendOrderMessage(userId.value)
const importContacts = () => store.importContacts(userId.value)
const importOrders = () => store.importOrders(userId.value)
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

const viewMessageDetails = (message) => {
  selectedPhone.value = message.recipient_phone
  dialog.value = true
  loading.value.messages = true

  axios.get(`/api/v1/messages/chat/${message.recipient_phone}`)
    .then((response) => {
      conversation.value = response.data
      loading.value.messages = false
    })
    .catch((error) => {
      console.error("Error loading chat:", error)
      loading.value.messages = false
    })
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

              <v-btn color="secondary" block @click="showTemplateDialog = true">
                <v-icon class="mr-2">mdi-file-document-edit</v-icon>
                Manage Templates
              </v-btn>
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
                            {{ formatPhoneNumber(message.recipient_phone) }}
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
              label="Select Order Template" @change="onTemplateSelect" class="mt-3" return-object></v-select>
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
      <v-dialog v-model="dialog" max-width="600">
        <v-card>
          <v-card-title>
            <span class="text-h6">Conversation Details</span>
          </v-card-title>
          <v-card-text>
            <v-progress-linear v-if="loading.value" indeterminate color="primary"></v-progress-linear>
            <div v-else>
              <div v-if="Array.isArray(conversation) && conversation.length">
                <div v-for="msg in conversation" :key="msg.id" class="mb-2">
                  <div>
                    <strong>{{ msg.sender_name || 'You' }}:</strong>
                    <span>{{ msg.content }}</span>
                  </div>
                  <div class="text-caption text-grey">
                    {{ msg.created_at?.replace('T', ' ').slice(0, 19) || '-' }}
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-grey">
                No conversation found.
              </div>
            </div>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn text @click="dialog = false">Close</v-btn>
          </v-card-actions>
        </v-card>
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
    </v-container>
  </AppLayout>
</template>