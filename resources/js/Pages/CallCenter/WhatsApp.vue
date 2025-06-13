<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";

// Core state
const selectedPhone = ref(null);
const dialog = ref(false);
const conversation = ref([]);
const search = ref('');
const messages = ref([]);
const contacts = ref([]);
const orders = ref([]);
const selectedContacts = ref([]);
const selectedOrders = ref([]);
const messageText = ref('');
const templates = ref([]);
const currentPage = ref(1);
const perPage = ref(20);
const totalMessages = ref(0);
const totalOrders = ref(0);

// UI state
const showImportDialog = ref(false);
const showNewMessageDialog = ref(false);
const showTemplateDialog = ref(false);
const showOrderImportDialog = ref(false);
const showOrderMessageDialog = ref(false);
const showBulkOrderDialog = ref(false);
const activeTab = ref('messages');

// Loading states
const loading = ref({
  messages: false,
  contacts: false,
  orders: false,
  templates: false,
  sending: false,
  importing: false,
  savingTemplate: false,
  deletingTemplate: false,
  uploadingOrders: false
});

// Message states
const errorMessage = ref('');
const successMessage = ref('');
const csvFile = ref(null);
const orderFile = ref(null);

// Template management
const newTemplate = ref({
  name: '',
  content: '',
  channel: 'WhatsApp',
  module: 'Message'
});
const selectedTemplate = ref(null);
const isCreatingTemplate = ref(false);

// Connection status
const whatsappStatus = ref('Connected');
const stats = ref({
  sent: 0,
  delivered: 0,
  read: 0,
  failed: 0,
  pending: 0,
  totalOrders: 0,
  pendingOrders: 0,
  deliveredOrders: 0
});

// Filter states
const filterType = ref('all');
const filterStatus = ref('all');
const orderFilterStatus = ref('all');
const itemsPerPage = ref(10);

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

// Predefined order message templates
const orderTemplates = [
  {
    name: 'Order Confirmation',
    content: 'Hi {{customer_name}}, your order #{{order_number}} for {{product_name}} worth ${{price}} has been confirmed. We will notify you once shipped. Thank you for choosing our courier service!'
  },
  {
    name: 'Shipping Notification',
    content: 'Hello {{customer_name}}, great news! Your order #{{order_number}} is now shipped and on its way to you. Track your package with tracking ID: {{tracking_id}}'
  },
  {
    name: 'Delivery Confirmation',
    content: 'Hi {{customer_name}}, your order #{{order_number}} has been successfully delivered. Thank you for your business! Please rate our service.'
  },
  {
    name: 'Payment Reminder',
    content: 'Dear {{customer_name}}, this is a reminder that payment for order #{{order_number}} worth ${{price}} is still pending. Please complete payment to avoid delays.'
  }
];

// Computed properties
const filteredContacts = computed(() => {
  if (!Array.isArray(contacts.value)) return [];

  let filtered = [...contacts.value];

  if (filterType.value !== 'all') {
    filtered = filtered.filter(contact => contact.type === filterType.value);
  }

  if (filterStatus.value !== 'all') {
    filtered = filtered.filter(contact => contact.status === filterStatus.value);
  }

  return filtered;
});

const filteredOrders = computed(() => {
  if (!Array.isArray(orders.value)) return [];

  let filtered = [...orders.value];

  if (orderFilterStatus.value !== 'all') {
    filtered = filtered.filter(order => order.status === orderFilterStatus.value);
  }

  return filtered;
});

const filteredMessages = computed(() => {
  if (!search.value || !Array.isArray(messages.value)) return messages.value;

  const searchTerm = search.value.toLowerCase();
  return messages.value.filter(message =>
    (message.content?.toLowerCase().includes(searchTerm)) ||
    (message.status?.toLowerCase().includes(searchTerm)) ||
    (message.recipient_name?.toLowerCase().includes(searchTerm)) ||
    (message.recipient_phone?.toLowerCase().includes(searchTerm)) ||
    (message.order_number?.toLowerCase().includes(searchTerm))
  );
});

const validWhatsappContacts = computed(() => {
  if (!Array.isArray(contacts.value)) return [];
  return contacts.value.filter(contact => contact.whatsapp || contact.alt_phone || contact.phone);
});

const totalPages = computed(() => Math.ceil(totalMessages.value / perPage.value));
const totalOrderPages = computed(() => Math.ceil(totalOrders.value / perPage.value));

// Utility functions
const formatPhoneNumber = (phone) => {
  if (!phone) return 'Unknown';
  return phone.replace(/@c\.us$/, '');
};

const showError = (message) => {
  errorMessage.value = message;
  setTimeout(() => {
    errorMessage.value = '';
  }, 5000);
};

const showSuccess = (message) => {
  successMessage.value = message;
  setTimeout(() => {
    successMessage.value = '';
  }, 5000);
};

// API functions
const loadMessages = async (page = 1) => {
  try {
    loading.value.messages = true;
    currentPage.value = page;

    const response = await axios.get(`/api/v1/whatsapp-messages`, {
      params: {
        page: page,
        per_page: perPage.value
      }
    });

    if (Array.isArray(response.data.data)) {
      messages.value = response.data.data;
      totalMessages.value = response.data.meta?.total || messages.value.length;
      calculateStats();
    } else {
      console.error('Unexpected API response format:', response.data);
      if (!Array.isArray(messages.value)) {
        messages.value = [];
      }
      showError('Invalid data format received from server');
    }
  } catch (error) {
    console.error('Error loading messages:', error);
    showError(`Failed to load messages: ${error.response?.data?.message || error.message}`);
    if (!Array.isArray(messages.value)) {
      messages.value = [];
    }
  } finally {
    loading.value.messages = false;
  }
};

const loadContacts = async () => {
  try {
    loading.value.contacts = true;
    const response = await axios.get('/api/v1/contacts');

    if (response.data?.data?.data && Array.isArray(response.data.data.data)) {
      contacts.value = response.data.data.data;
      console.log('Contacts loaded:', contacts.value.length);
    } else {
      console.error('Unexpected API response format:', response.data);
      if (!Array.isArray(contacts.value) || contacts.value.length === 0) {
        contacts.value = [];
      }
      showError('Invalid contact data format received from server');
    }
  } catch (error) {
    console.error('Error loading contacts:', error);
    showError(`Failed to load contacts: ${error.response?.data?.message || error.message}`);
    if (!Array.isArray(contacts.value) || contacts.value.length === 0) {
      contacts.value = [];
    }
  } finally {
    loading.value.contacts = false;
  }
};

const loadOrders = async (page = 1) => {
  try {
    loading.value.orders = true;
    const response = await axios.get('/api/v1/orders', {
      params: {
        page: page,
        per_page: perPage.value
      }
    });

    if (Array.isArray(response.data.data)) {
      orders.value = response.data.data;
      totalOrders.value = response.data.meta?.total || orders.value.length;
      calculateOrderStats();
    } else {
      console.error('Unexpected API response format:', response.data);
      if (!Array.isArray(orders.value)) {
        orders.value = [];
      }
      showError('Invalid order data format received from server');
    }
  } catch (error) {
    console.error('Error loading orders:', error);
    showError(`Failed to load orders: ${error.response?.data?.message || error.message}`);
    if (!Array.isArray(orders.value)) {
      orders.value = [];
    }
  } finally {
    loading.value.orders = false;
  }
};

const loadTemplates = async () => {
  try {
    loading.value.templates = true;
    const response = await axios.get('/api/v1/templates');

    if (response.data?.data && Array.isArray(response.data.data)) {
      templates.value = [...response.data.data, ...orderTemplates];
      console.log('Templates loaded:', templates.value.length);
    } else {
      console.error('Unexpected API response format:', response.data);
      templates.value = [...orderTemplates];
      showError('Failed to load custom templates, using default order templates');
    }
  } catch (error) {
    console.error('Error loading templates:', error);
    templates.value = [...orderTemplates];
    showError('Failed to load custom templates, using default order templates');
  } finally {
    loading.value.templates = false;
  }
};

// Statistics calculation
const calculateStats = () => {
  if (!Array.isArray(messages.value)) {
    console.error('messages.value is not an array:', messages.value);
    return;
  }

  try {
    const statuses = messages.value.reduce((acc, message) => {
      const status = (message && message.status && typeof message.status === 'string')
        ? message.status.toLowerCase()
        : 'unknown';
      acc[status] = (acc[status] || 0) + 1;
      return acc;
    }, {});

    stats.value = {
      ...stats.value,
      sent: statuses.sent || 0,
      delivered: statuses.delivered || 0,
      read: statuses.read || 0,
      failed: statuses.failed || 0,
      pending: statuses.pending || 0
    };
  } catch (error) {
    console.error('Error calculating message stats:', error);
  }
};

const calculateOrderStats = () => {
  if (!Array.isArray(orders.value)) return;

  try {
    const orderStats = orders.value.reduce((acc, order) => {
      const status = order.status?.toLowerCase() || 'unknown';
      acc[status] = (acc[status] || 0) + 1;
      return acc;
    }, {});

    stats.value = {
      ...stats.value,
      totalOrders: orders.value.length,
      pendingOrders: orderStats.pending || 0,
      deliveredOrders: orderStats.delivered || 0
    };
  } catch (error) {
    console.error('Error calculating order stats:', error);
  }
};

// Template handling
const onTemplateSelect = (templateId) => {
  if (!templateId) return;

  const template = templates.value.find(t => t.id === templateId || t.name === templateId);
  if (template) {
    messageText.value = template.content;
    selectedTemplate.value = template;
  }
};

// Message sending
const sendMessage = async () => {
  if (!messageText.value.trim()) {
    return showError('Please enter a message');
  }

  if (!Array.isArray(selectedContacts.value) || selectedContacts.value.length === 0) {
    return showError('Please select at least one recipient');
  }

  try {
    loading.value.sending = true;

    const response = await axios.post('/api/v1/whatsapp-send', {
      user_id: userId.value,
      contacts: selectedContacts.value.map(c => ({
        id: c.id,
        name: c.name,
        chatId: c.whatsapp || c.alt_phone || c.phone,
      })),
      message: messageText.value,
      template_id: selectedTemplate.value?.id || null,
    });

    const now = new Date();

    if (!Array.isArray(messages.value)) {
      messages.value = [];
    }

    messages.value.unshift({
      id: response.data?.id || Date.now(),
      content: messageText.value,
      recipients: selectedContacts.value.length,
      status: 'sent',
      message_status: 'sent',
      sent_at: now.toISOString().split('T')[0],
      created_at: now.toISOString(),
      recipient_name: selectedContacts.value.map(c => c.name).join(', '),
      recipient_phone: selectedContacts.value.map(c => c.whatsapp || c.alt_phone || c.phone).join(', '),
      results: response.data?.results || []
    });

    stats.value.sent += selectedContacts.value.length;
    showSuccess(`Message successfully sent to ${selectedContacts.value.length} recipients`);

    messageText.value = '';
    selectedContacts.value = [];
    selectedTemplate.value = null;
    showNewMessageDialog.value = false;

    setTimeout(() => {
      loadMessages(1);
    }, 1000);

  } catch (error) {
    console.error('Error sending message:', error);
    showError(`Failed to send message: ${error.response?.data?.message || error.message}`);
  } finally {
    loading.value.sending = false;
  }
};

// Order messaging
const sendOrderMessage = async () => {
  if (!messageText.value.trim()) {
    return showError('Please enter a message');
  }

  if (!Array.isArray(selectedOrders.value) || selectedOrders.value.length === 0) {
    return showError('Please select at least one order');
  }

  try {
    loading.value.sending = true;

    const response = await axios.post('/api/v1/whatsapp-send-orders', {
      user_id: userId.value,
      orders: selectedOrders.value.map(order => ({
        id: order.id,
        order_number: order.order_number,
        customer_name: order.customer_name,
        customer_phone: order.customer_phone,
        product_name: order.product_name,
        price: order.price,
        tracking_id: order.tracking_id
      })),
      message: messageText.value,
      template_id: selectedTemplate.value?.id || null,
    });

    showSuccess(`Order messages sent to ${selectedOrders.value.length} customers`);

    messageText.value = '';
    selectedOrders.value = [];
    selectedTemplate.value = null;
    showOrderMessageDialog.value = false;

    setTimeout(() => {
      loadMessages(1);
      loadOrders(1);
    }, 1000);

  } catch (error) {
    console.error('Error sending order messages:', error);
    showError(`Failed to send order messages: ${error.response?.data?.message || error.message}`);
  } finally {
    loading.value.sending = false;
  }
};

// File import functions
const importContacts = async () => {
  if (!csvFile.value) {
    return showError('Please select a CSV file to import');
  }

  try {
    loading.value.importing = true;

    const formData = new FormData();
    formData.append('file', csvFile.value);
    formData.append('user_id', userId.value);

    const response = await axios.post('/api/v1/contacts/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    if (response.data && response.data.success) {
      showSuccess(`Successfully imported ${response.data.imported || 'multiple'} contacts`);
      await loadContacts();
      showImportDialog.value = false;
      csvFile.value = null;
    } else {
      showError(response.data?.message || 'Import failed');
    }
  } catch (error) {
    console.error('Error importing contacts:', error);
    showError(`Failed to import contacts: ${error.response?.data?.message || error.message}`);
  } finally {
    loading.value.importing = false;
  }
};

const importOrders = async () => {
  if (!orderFile.value) {
    return showError('Please select an Excel or CSV file to import');
  }

  try {
    loading.value.uploadingOrders = true;

    const formData = new FormData();
    formData.append('file', orderFile.value);
    formData.append('user_id', userId.value);

    const response = await axios.post('/api/v1/orders/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    if (response.data && response.data.success) {
      showSuccess(`Successfully imported ${response.data.imported || 'multiple'} orders`);
      await loadOrders();
      showOrderImportDialog.value = false;
      orderFile.value = null;
    } else {
      showError(response.data?.message || 'Order import failed');
    }
  } catch (error) {
    console.error('Error importing orders:', error);
    showError(`Failed to import orders: ${error.response?.data?.message || error.message}`);
  } finally {
    loading.value.uploadingOrders = false;
  }
};

// Dialog functions
const openNewMessageDialog = async () => {
  errorMessage.value = '';
  messageText.value = '';
  selectedContacts.value = [];
  selectedTemplate.value = null;

  if ((!Array.isArray(contacts.value) || contacts.value.length === 0) && !loading.value.contacts) {
    await loadContacts();
  }

  if ((!Array.isArray(templates.value) || templates.value.length === 0) && !loading.value.templates) {
    await loadTemplates();
  }

  showNewMessageDialog.value = true;
};

const openOrderMessageDialog = async () => {
  errorMessage.value = '';
  messageText.value = '';
  selectedOrders.value = [];
  selectedTemplate.value = null;

  if ((!Array.isArray(orders.value) || orders.value.length === 0) && !loading.value.orders) {
    await loadOrders();
  }

  if ((!Array.isArray(templates.value) || templates.value.length === 0) && !loading.value.templates) {
    await loadTemplates();
  }

  showOrderMessageDialog.value = true;
};

const openSendMessage = (isBulk = false, contact = null) => {
  errorMessage.value = '';
  messageText.value = '';

  if (isBulk) {
    selectedContacts.value = selectedContacts.value;
  } else if (contact) {
    selectedContacts.value = [contact];
  } else {
    selectedContacts.value = [];
  }

  if ((!Array.isArray(templates.value) || templates.value.length === 0) && !loading.value.templates) {
    loadTemplates();
  }

  showNewMessageDialog.value = true;
};

const viewMessageDetails = (message) => {
  selectedPhone.value = message.recipient_phone;
  dialog.value = true;
  loading.value = true;

  axios.get(`/api/v1/messages/chat/${message.recipient_phone}`)
    .then((response) => {
      conversation.value = response.data;
      loading.value = false;
    })
    .catch((error) => {
      console.error("Error loading chat:", error);
      loading.value = false;
    });
};

const deleteMessage = async (messageId) => {
  if (!messageId) {
    return showError('Invalid message ID');
  }

  if (confirm('Are you sure you want to delete this message?')) {
    try {
      await axios.delete(`/api/v1/whatsapp-messages/${messageId}`);

      if (Array.isArray(messages.value)) {
        messages.value = messages.value.filter(msg => msg.id !== messageId);
        calculateStats();
      }

      showSuccess('Message deleted successfully');
    } catch (error) {
      console.error('Error deleting message:', error);
      showError(`Failed to delete message: ${error.response?.data?.message || error.message}`);
    }
  }
};

const resetFilters = () => {
  search.value = '';
  filterType.value = 'all';
  filterStatus.value = 'all';
  orderFilterStatus.value = 'all';
};

const hasWhatsAppNumber = (contact) => {
  return Boolean(contact.whatsapp || contact.alt_phone || contact.phone);
};

const viewContact = (contact) => {
  alert(`Contact: ${contact.name}
Phone: ${contact.phone || 'N/A'}
WhatsApp: ${contact.whatsapp || 'N/A'}
Type: ${contact.type || 'N/A'}
Company: ${contact.company_name || 'N/A'}
Country: ${contact.country_name || 'N/A'}`);
};

// Watch for search changes
watch(search, (newValue) => {
  if (newValue === '') {
    loadMessages(currentPage.value);
  }
});

// Component mount
onMounted(() => {
  messages.value = [];
  contacts.value = [];
  orders.value = [];
  templates.value = [];

  loadMessages(1);
  loadContacts();
  loadOrders(1);
  loadTemplates();
});
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

            <!-- Messages Tab -->
            <v-tab-item value="messages">
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
            </v-tab-item>

            <!-- Orders Tab -->
            <v-tab-item value="orders">
              <v-card-title class="d-flex flex-wrap justify-space-between align-center">
                <div class="text-h6">Orders</div>
                <v-select v-model="orderFilterStatus" :items="orderStatusOptions" item-title="title" item-value="value"
                  label="Filter by Status" hide-details density="compact" class="max-w-xs mt-2 mt-sm-0"></v-select>
              </v-card-title>
              <v-card-text>
                <v-progress-linear v-if="loading.orders" indeterminate color="primary"></v-progress-linear>
                <div v-else-if="!Array.isArray(filteredOrders) || filteredOrders.length === 0" class="text-center pa-4">
                  <v-icon size="large" color="grey">mdi-package-variant</v-icon>
                  <p class="text-body-1 mt-2">No orders found.</p>
                </div>
                <v-table v-else>
                  <thead>
                    <tr>
                      <th>Order #</th>
                      <th>Customer</th>
                      <th>Status</th>
                      <th>Product</th>
                      <th>Price</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="order in filteredOrders" :key="order.id">
                      <td>{{ order.order_number }}</td>
                      <td>
                        <div>{{ order.customer_name }}</div>
                        <div class="text-caption text-grey">
                          {{ formatPhoneNumber(order.customer_phone) }}
                        </div>
                      </td>
                      <td>
                        <v-chip :color="{
                          pending: 'warning',
                          processing: 'primary',
                          shipped: 'info',
                          delivered: 'success',
                          cancelled: 'error'
                        }[order.status?.toLowerCase()] || 'grey'" small>
                          {{ order.status || 'Unknown' }}
                        </v-chip>
                      </td>
                      <td>{{ order.product_name }}</td>
                      <td>${{ order.price }}</td>
                      <td>{{ order.created_at?.split('T')[0] || '-' }}</td>
                    </tr>
                  </tbody>
                </v-table>
                <div class="d-flex justify-end mt-4">
                  <v-pagination v-model="currentPage" :length="totalOrderPages" @input="loadOrders"></v-pagination>
                </div>
              </v-card-text>
            </v-tab-item>

            <!-- Contacts Tab -->
            <v-tab-item value="contacts">
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
            </v-tab-item>
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
            <v-select v-model="selectedOrders" :items="orders" item-title="order_number" item-value="id"
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
    </v-container>
  </AppLayout>
</template>
