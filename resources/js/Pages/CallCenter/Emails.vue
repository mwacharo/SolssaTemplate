<template>
  <AppLayout title="Email Dashboard">
    <div class="p-6 bg-gray-50 min-h-screen">
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Email Management</h1>
        <p class="text-gray-600">Manage your emails, templates, and communications</p>
      </div>

      <!-- Dashboard Tabs -->
      <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex space-x-8 px-6">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              @click="activeTab = tab.key"
              :class="[
                'py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap',
                activeTab === tab.key
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              {{ tab.name }}
              <span v-if="tab.count" class="ml-2 bg-gray-100 text-gray-600 py-0.5 px-2 rounded-full text-xs">
                {{ tab.count }}
              </span>
            </button>
          </nav>
        </div>

        <!-- Compose Tab -->
        <div v-if="activeTab === 'compose'" class="p-6">
          <div class="max-w-4xl mx-auto">
            <h2 class="text-xl font-semibold mb-4">Compose Email</h2>
            
            <!-- Email Form -->
            <form @submit.prevent="sendEmail" class="space-y-4">
              <!-- Recipients and Subject -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                  <input
                  v-model="composeForm.to"
                  type="email"
                  multiple
                  placeholder="recipient@example.com"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">CC</label>
                  <input
                  v-model="composeForm.cc"
                  type="email"
                  multiple
                  placeholder="cc@example.com"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">BCC</label>
                  <input
                  v-model="composeForm.bcc"
                  type="email"
                  multiple
                  placeholder="bcc@example.com"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                  <select
                    v-model="composeForm.client"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <vue-autocomplete
                      v-model="composeForm.client"
                      :items="emailStore.clients"
                      item-text="name"
                      item-value="id"
                      multiple
                      placeholder="Select Client(s)"
                      class="w-full"
                    ></vue-autocomplete>
                  </select>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                <input
                  v-model="composeForm.subject"
                  type="text"
                  placeholder="Email subject"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <!-- Template Selection -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Template</label>
                <select
                  v-model="selectedTemplate"
                  @change="loadTemplate"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">Select Template</option>
                  <option v-for="template in emailStore.templates" :key="template.id" :value="template.id">
                    {{ template.name }}
                  </option>
                </select>
              </div>

              <!-- Email Body -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                <textarea
                  v-model="composeForm.body"
                  rows="10"
                  placeholder="Type your message here..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                ></textarea>
              </div>

              <!-- File Attachments -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Attachments</label>
                <div class="flex items-center space-x-4">
                  <input
                    ref="fileInput"
                    type="file"
                    multiple
                    @change="handleFileUpload"
                    class="hidden"
                  />
                  <button
                    type="button"
                    @click="$refs.fileInput.click()"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                  >
                    Add Files
                  </button>
                  <div v-if="composeForm.attachments.length" class="text-sm text-gray-600">
                    {{ composeForm.attachments.length }} file(s) selected
                  </div>
                </div>
                
                <!-- Attachment List -->
                <div v-if="composeForm.attachments.length" class="mt-2">
                  <div v-for="(file, index) in composeForm.attachments" :key="index" 
                       class="flex items-center justify-between bg-gray-50 px-3 py-2 rounded mb-1">
                    <span class="text-sm">{{ file.name }} ({{ formatFileSize(file.size) }})</span>
                    <button @click="removeAttachment(index)" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex space-x-4">
                <button
                  type="submit"
                  class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  Send Email
                </button>
                <button
                  type="button"
                  @click="saveDraft"
                  class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500"
                >
                  Save as Draft
                </button>
                <button
                  type="button"
                  @click="clearForm"
                  class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50"
                >
                  Clear
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Drafts Tab -->
        <div v-if="activeTab === 'drafts'" class="p-6">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Draft Emails</h2>
          </div>

          <div class="space-y-3">
            <div v-for="draft in emailStore.drafts" :key="draft.id" 
                 class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
              <div class="flex justify-between items-start">
                <div class="flex-1">
                  <h3 class="font-medium text-gray-900">{{ draft.subject || 'No Subject' }}</h3>
                  <p class="text-sm text-gray-600 mt-1">To: {{ draft.to }}</p>
                  <p class="text-sm text-gray-500 mt-2">{{ truncateText(draft.body, 100) }}</p>
                  <p class="text-xs text-gray-400 mt-2">Saved: {{ formatDate(draft.createdAt) }}</p>
                </div>
                <div class="flex space-x-2 ml-4">
                  <button @click="editDraft(draft)" class="text-blue-600 hover:text-blue-800 text-sm">Edit</button>
                  <button @click="deleteDraft(draft.id)" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                </div>
              </div>
            </div>
          </div>

          <div v-if="!emailStore.drafts.length" class="text-center py-8 text-gray-500">
            No drafts found
          </div>
        </div>

        <!-- Outbox Tab -->
        <div v-if="activeTab === 'outbox'" class="p-6">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Sent Emails</h2>
            
            <!-- Filters -->
            <div class="flex space-x-4">
              <select v-model="filters.client" class="px-3 py-1 border border-gray-300 rounded text-sm">
                <option value="">All Clients</option>
                <option v-for="client in emailStore.clients" :key="client.id" :value="client.id">
                  {{ client.name }}
                </option>
              </select>
              <input
                v-model="filters.dateFrom"
                type="date"
                class="px-3 py-1 border border-gray-300 rounded text-sm"
                placeholder="From Date"
              />
              <input
                v-model="filters.dateTo"
                type="date"
                class="px-3 py-1 border border-gray-300 rounded text-sm"
                placeholder="To Date"
              />
            </div>
          </div>

          <div class="space-y-3">
            <div v-for="email in filteredSentEmails" :key="email.id" 
                 class="border border-gray-200 rounded-lg p-4">
              <div class="flex justify-between items-start">
                <div class="flex-1">
                  <h3 class="font-medium text-gray-900">{{ email.subject }}</h3>
                  <p class="text-sm text-gray-600 mt-1">To: {{ email.to }}</p>
                  <p class="text-sm text-gray-600">Client: {{ getClientName(email.clientId) }}</p>
                  <p class="text-sm text-gray-500 mt-2">{{ truncateText(email.body, 100) }}</p>
                  <div class="flex items-center space-x-4 mt-2">
                    <p class="text-xs text-gray-400">Sent: {{ formatDate(email.sentAt) }}</p>
                    <span v-if="email.attachments?.length" class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                      {{ email.attachments.length }} attachment(s)
                    </span>
                    <span :class="['text-xs px-2 py-1 rounded', email.status === 'delivered' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800']">
                      {{ email.status }}
                    </span>
                  </div>
                </div>
                <button @click="viewEmail(email)" class="text-blue-600 hover:text-blue-800 text-sm">View</button>
              </div>
            </div>
          </div>

          <div v-if="!filteredSentEmails.length" class="text-center py-8 text-gray-500">
            No sent emails found
          </div>
        </div>

        <!-- Templates Tab -->
        <div v-if="activeTab === 'templates'" class="p-6">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Email Templates</h2>
            <button @click="showNewTemplate = true" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
              New Template
            </button>
          </div>

          <!-- New Template Form -->
          <div v-if="showNewTemplate" class="mb-6 p-4 border border-gray-200 rounded-lg bg-gray-50">
            <h3 class="font-medium mb-3">Create New Template</h3>
            <div class="space-y-3">
              <input
                v-model="newTemplate.name"
                type="text"
                placeholder="Template name"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <input
                v-model="newTemplate.subject"
                type="text"
                placeholder="Email subject"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <textarea
                v-model="newTemplate.body"
                rows="6"
                placeholder="Email body (use {{client_name}}, {{date}} for variables)"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              ></textarea>
              <div class="flex space-x-3">
                <button @click="saveTemplate" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                  Save Template
                </button>
                <button @click="cancelNewTemplate" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                  Cancel
                </button>
              </div>
            </div>
          </div>

          <!-- Templates List -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="template in emailStore.templates" :key="template.id" 
                 class="border border-gray-200 rounded-lg p-4">
              <h3 class="font-medium text-gray-900 mb-2">{{ template.name }}</h3>
              <p class="text-sm text-gray-600 mb-2">Subject: {{ template.subject }}</p>
              <p class="text-sm text-gray-500 mb-3">{{ truncateText(template.body, 80) }}</p>
              <div class="flex space-x-2">
                <button @click="useTemplate(template)" class="text-blue-600 hover:text-blue-800 text-sm">Use</button>
                <button @click="editTemplate(template)" class="text-green-600 hover:text-green-800 text-sm">Edit</button>
                <button @click="deleteTemplate(template.id)" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
              </div>
            </div>
          </div>

          <div v-if="!emailStore.templates.length && !showNewTemplate" class="text-center py-8 text-gray-500">
            No templates found. Create your first template to get started.
          </div>
        </div>

        <!-- Bulk Email Tab -->
        <div v-if="activeTab === 'bulk'" class="p-6">
          <div class="max-w-4xl mx-auto">
            <h2 class="text-xl font-semibold mb-4">Bulk Email</h2>
            
            <div class="space-y-4">
              <!-- Recipients -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Recipients</label>
                <div class="flex space-x-4 mb-2">
                  <button
                    @click="selectAllClients"
                    class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700"
                  >
                    Select All Clients
                  </button>
                  <button
                    @click="clearAllClients"
                    class="px-3 py-1 border border-gray-300 text-gray-700 text-sm rounded hover:bg-gray-50"
                  >
                    Clear All
                  </button>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                  <label v-for="client in emailStore.clients" :key="client.id" class="flex items-center space-x-2">
                    <input
                      v-model="bulkForm.selectedClients"
                      :value="client.id"
                      type="checkbox"
                      class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                    <span class="text-sm">{{ client.name }}</span>
                  </label>
                </div>
              </div>

              <!-- Template Selection -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Template</label>
                <select
                  v-model="bulkForm.templateId"
                  @change="loadBulkTemplate"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">Select Template</option>
                  <option v-for="template in emailStore.templates" :key="template.id" :value="template.id">
                    {{ template.name }}
                  </option>
                </select>
              </div>

              <!-- Preview -->
              <div v-if="bulkForm.templateId">
                <label class="block text-sm font-medium text-gray-700 mb-1">Preview</label>
                <div class="border border-gray-200 rounded-md p-4 bg-gray-50">
                  <p class="font-medium">{{ bulkForm.subject }}</p>
                  <p class="text-sm text-gray-600 mt-2">{{ bulkForm.body }}</p>
                </div>
              </div>

              <!-- Send Options -->
              <div class="flex space-x-4">
                <button
                  @click="sendBulkEmail"
                  :disabled="!bulkForm.selectedClients.length || !bulkForm.templateId"
                  class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Send to {{ bulkForm.selectedClients.length }} Client(s)
                </button>
                <button
                  @click="previewBulkEmail"
                  :disabled="!bulkForm.selectedClients.length || !bulkForm.templateId"
                  class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Preview All
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useEmailStore } from '@/stores/emailStore'
import { useUsersStore } from '@/stores/users'

import AppLayout from '@/Layouts/AppLayout.vue'
import { notify } from '@/utils/toast'

// Store
const usersStore = useUsersStore()

const emailStore = useEmailStore()

// Active tab
const activeTab = ref('compose')

// Tabs configuration
const tabs = computed(() => [
  { key: 'compose', name: 'Compose', count: null },
  { key: 'drafts', name: 'Drafts', count: emailStore.drafts.length },
  { key: 'outbox', name: 'Outbox', count: emailStore.sentEmails.length },
  { key: 'templates', name: 'Templates', count: emailStore.templates.length },
  { key: 'bulk', name: 'Bulk Email', count: null }
])

// Compose form
const composeForm = ref({
  to: '',
  subject: '',
  body: '',
  client: '',
  attachments: []
})

// Template selection
const selectedTemplate = ref('')

// File handling
const handleFileUpload = (event) => {
  const files = Array.from(event.target.files)
  composeForm.value.attachments = [...composeForm.value.attachments, ...files]
}

const removeAttachment = (index) => {
  composeForm.value.attachments.splice(index, 1)
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Email actions
const sendEmail = () => {
  if (!composeForm.value.to || !composeForm.value.subject) {
    notify.failed('Please fill in required fields', 'error')
    return
  }

  emailStore.sendEmail({
    ...composeForm.value,
    id: Date.now(),
    sentAt: new Date(),
    status: 'sent'
  })

  notify.success('Email sent successfully!', 'success')
  clearForm()
}

const saveDraft = () => {
  if (!composeForm.value.to && !composeForm.value.subject && !composeForm.value.body) {
    notify.failed('Cannot save empty draft', 'error')
    return
  }

  emailStore.saveDraft({
    ...composeForm.value,
    id: Date.now(),
    createdAt: new Date()
  })

  notify.sucess('Draft saved successfully!', 'success')
  clearForm()
}

const clearForm = () => {
  composeForm.value = {
    to: '',
    subject: '',
    body: '',
    client: '',
    attachments: []
  }
  selectedTemplate.value = ''
}

// Template management
const loadTemplate = () => {
  if (!selectedTemplate.value) return
  
  const template = emailStore.templates.find(t => t.id === parseInt(selectedTemplate.value))
  if (template) {
    composeForm.value.subject = template.subject
    composeForm.value.body = template.body
  }
}

const showNewTemplate = ref(false)
const newTemplate = ref({
  name: '',
  subject: '',
  body: ''
})

const saveTemplate = () => {
  if (!newTemplate.value.name || !newTemplate.value.subject || !newTemplate.value.body) {
    notify.error('Please fill in all template fields')
    return
  }

  if (newTemplate.value.id) {
    // Edit existing template
    emailStore.updateTemplate(newTemplate.value.id, {
      name: newTemplate.value.name,
      subject: newTemplate.value.subject,
      body: newTemplate.value.body,
      updatedAt: new Date()
    })
    notify.success('Template updated successfully!')
  } else {
    // Create new template
    emailStore.saveTemplate({
      ...newTemplate.value,
      id: Date.now(),
      createdAt: new Date()
    })
    notify.success('Template saved successfully!')
  }
  cancelNewTemplate()
}

const cancelNewTemplate = () => {
  showNewTemplate.value = false
  newTemplate.value = {
    name: '',
    subject: '',
    body: ''
  }
}

const useTemplate = (template) => {
  selectedTemplate.value = template.id.toString()
  loadTemplate()
  activeTab.value = 'compose'
  notify.success('Template loaded in compose tab', 'success')
}

const editTemplate = (template) => {
  newTemplate.value = { ...template }
  showNewTemplate.value = true
}

const deleteTemplate = (id) => {
  if (confirm('Are you sure you want to delete this template?')) {
    emailStore.deleteTemplate(id)
    notify.success('Template deleted successfully!', 'success')
  }
}

// Draft management
const editDraft = (draft) => {
  composeForm.value = { ...draft }
  activeTab.value = 'compose'
  emailStore.deleteDraft(draft.id)
  notify.success('Draft loaded for editing', 'success')
}

const deleteDraft = (id) => {
  if (confirm('Are you sure you want to delete this draft?')) {
    emailStore.deleteDraft(id)
    notify.success('Draft deleted successfully!', 'success')
  }
}

// Filtering
const filters = ref({
  client: '',
  dateFrom: '',
  dateTo: ''
})

const filteredSentEmails = computed(() => {
  let filtered = emailStore.sentEmails

  if (filters.value.client) {
    filtered = filtered.filter(email => email.clientId === filters.value.client)
  }

  if (filters.value.dateFrom) {
    const fromDate = new Date(filters.value.dateFrom)
    filtered = filtered.filter(email => new Date(email.sentAt) >= fromDate)
  }

  if (filters.value.dateTo) {
    const toDate = new Date(filters.value.dateTo)
    toDate.setHours(23, 59, 59, 999) // End of day
    filtered = filtered.filter(email => new Date(email.sentAt) <= toDate)
  }

  return filtered
})

// Bulk email
const bulkForm = ref({
  selectedClients: [],
  templateId: '',
  subject: '',
  body: ''
})

const selectAllClients = () => {
  bulkForm.value.selectedClients = emailStore.clients.map(c => c.id)
}

const clearAllClients = () => {
  bulkForm.value.selectedClients = []
}

const loadBulkTemplate = () => {
  if (!bulkForm.value.templateId) return
  
  const template = emailStore.templates.find(t => t.id === parseInt(bulkForm.value.templateId))
  if (template) {
    bulkForm.value.subject = template.subject
    bulkForm.value.body = template.body
  }
}

const sendBulkEmail = () => {
  if (!bulkForm.value.selectedClients.length) {
    notify.error('Please select at least one client', 'error')
    return
  }

  if (!bulkForm.value.templateId) {
    notify.error('Please select a template', 'error')
    return
  }

  // Send email to each selected client
  bulkForm.value.selectedClients.forEach(clientId => {
    const client = emailStore.clients.find(c => c.id === clientId)
    emailStore.sendEmail({
      to: client.email,
      subject: bulkForm.value.subject.replace('{{client_name}}', client.name),
      body: bulkForm.value.body.replace('{{client_name}}', client.name).replace('{{date}}', new Date().toLocaleDateString()),
      clientId: clientId,
      id: Date.now() + Math.random(),
      sentAt: new Date(),
      status: 'sent'
    })
  })

  notify.success(`Bulk email sent to ${bulkForm.value.selectedClients.length} clients!`, 'success')
  bulkForm.value.selectedClients = []
}

const previewBulkEmail = () => {
  // This would open a modal or new view to preview emails for each client
  notify.success('Preview functionality would open detailed preview', 'info')
}

// Utility functions
const truncateText = (text, length) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

const formatDate = (date) => {
  return new Date(date).toLocaleString()
}

const getClientName = (clientId) => {
  const client = emailStore.clients.find(c => c.id === clientId)
  return client ? client.name : 'Unknown'
}

const viewEmail = (email) => {
  // This would open a modal or detailed view
  notify('Email details would open in a modal', 'info')
}

// Initialize store data on mount
onMounted(() => {
  emailStore.initializeData()
})
</script>