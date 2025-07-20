<template>
  <AppLayout title="telegram">
    <div class="space-y-6">
      <!-- Header Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-lg shadow-md">
          <h3 class="text-sm font-medium text-gray-500">Total SMS Sent</h3>
          <p class="text-2xl font-bold text-blue-600">{{ stats.totalSms }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
          <h3 class="text-sm font-medium text-gray-500">Form Responses</h3>
          <p class="text-2xl font-bold text-green-600">{{ stats.formResponses }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
          <h3 class="text-sm font-medium text-gray-500">Telegram Messages</h3>
          <p class="text-2xl font-bold text-purple-600">{{ stats.telegramMessages }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
          <h3 class="text-sm font-medium text-gray-500">AI Queries</h3>
          <p class="text-2xl font-bold text-indigo-600">{{ stats.aiQueries }}</p>
        </div>
      </div>

      <!-- Main Content Tabs -->
      <div class="bg-white rounded-lg shadow-md">
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex space-x-8">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'py-4 px-1 border-b-2 font-medium text-sm',
                activeTab === tab.id
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              {{ tab.name }}
            </button>
          </nav>
        </div>

        <div class="p-6">
          <!-- SMS Campaign Tab -->
          <div v-if="activeTab === 'sms'" class="space-y-6">
            <div class="flex justify-between items-center">
              <h2 class="text-xl font-semibold">SMS Campaigns</h2>
              <button
                @click="showSmsModal = true"
                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"
              >
                New Campaign
              </button>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
              <h3 class="font-medium mb-2">Recent Campaigns</h3>
              <div class="space-y-2">
                <div
                  v-for="campaign in smsCampaigns"
                  :key="campaign.id"
                  class="bg-white p-3 rounded border flex justify-between items-center"
                >
                  <div>
                    <p class="font-medium">{{ campaign.name }}</p>
                    <p class="text-sm text-gray-600">Sent to {{ campaign.recipients }} recipients</p>
                  </div>
                  <span
                    :class="[
                      'px-2 py-1 rounded text-xs',
                      campaign.status === 'completed' ? 'bg-green-100 text-green-800' :
                      campaign.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                      'bg-blue-100 text-blue-800'
                    ]"
                  >
                    {{ campaign.status }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Responses Tab -->
          <div v-if="activeTab === 'responses'" class="space-y-6">
            <div class="flex justify-between items-center">
              <h2 class="text-xl font-semibold">Form Responses</h2>
              <div class="flex space-x-2">
                <select v-model="selectedVendor" class="border rounded px-3 py-2">
                  <option value="">All Vendors</option>
                  <option v-for="vendor in vendors" :key="vendor.id" :value="vendor.id">
                    {{ vendor.name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Response</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr v-for="response in filteredResponses" :key="response.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ response.clientName }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ response.vendorName }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                      {{ response.message }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ formatTime(response.timestamp) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <button
                        @click="broadcastToTelegram(response)"
                        class="text-purple-600 hover:text-purple-900 mr-3"
                      >
                        Broadcast
                      </button>
                      <button
                        @click="generateAiResponse(response)"
                        class="text-indigo-600 hover:text-indigo-900"
                      >
                        AI Reply
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Telegram Integration Tab -->
          <div v-if="activeTab === 'telegram'" class="space-y-6">
            <div class="flex justify-between items-center">
              <h2 class="text-xl font-semibold">Telegram Integration</h2>
              <button
                @click="showTelegramModal = true"
                class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700"
              >
                Configure Groups
              </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-medium mb-2">Connected Groups</h3>
                <div class="space-y-2">
                  <div
                    v-for="group in telegramGroups"
                    :key="group.id"
                    class="bg-white p-3 rounded border flex justify-between items-center"
                  >
                    <div>
                      <p class="font-medium">{{ group.name }}</p>
                      <p class="text-sm text-gray-600">{{ group.members }} members</p>
                    </div>
                    <span
                      :class="[
                        'px-2 py-1 rounded text-xs',
                        group.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                      ]"
                    >
                      {{ group.active ? 'Active' : 'Inactive' }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-medium mb-2">Broadcast History</h3>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                  <div
                    v-for="broadcast in telegramBroadcasts"
                    :key="broadcast.id"
                    class="bg-white p-3 rounded border"
                  >
                    <p class="text-sm font-medium">{{ broadcast.groupName }}</p>
                    <p class="text-xs text-gray-600 truncate">{{ broadcast.message }}</p>
                    <p class="text-xs text-gray-500">{{ formatTime(broadcast.timestamp) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- AI Assistant Tab -->
          <div v-if="activeTab === 'ai'" class="space-y-6">
            <div class="flex justify-between items-center">
              <h2 class="text-xl font-semibold">AI Assistant</h2>
              <button
                @click="trainAiModel"
                class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700"
              >
                Train Model
              </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-medium mb-2">AI Query Interface</h3>
                <div class="space-y-4">
                  <textarea
                    v-model="aiQuery"
                    placeholder="Enter your query here..."
                    class="w-full p-3 border rounded-lg resize-none"
                    rows="4"
                  ></textarea>
                  <button
                    @click="processAiQuery"
                    :disabled="!aiQuery.trim()"
                    class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 disabled:bg-gray-400"
                  >
                    Process Query
                  </button>
                </div>
              </div>

              <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-medium mb-2">Recent AI Responses</h3>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                  <div
                    v-for="response in aiResponses"
                    :key="response.id"
                    class="bg-white p-3 rounded border"
                  >
                    <p class="text-sm font-medium">Query: {{ response.query.substring(0, 50) }}...</p>
                    <p class="text-xs text-gray-600">Response: {{ response.response.substring(0, 100) }}...</p>
                    <p class="text-xs text-gray-500">{{ formatTime(response.timestamp) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Analytics Tab -->
          <div v-if="activeTab === 'analytics'" class="space-y-6">
            <h2 class="text-xl font-semibold">Analytics & Reports</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-medium mb-2">Response Rate</h3>
                <div class="text-3xl font-bold text-green-600">
                  {{ ((stats.formResponses / stats.totalSms) * 100).toFixed(1) }}%
                </div>
                <p class="text-sm text-gray-600">{{ stats.formResponses }} responses from {{ stats.totalSms }} SMS sent</p>
              </div>
              
              <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="font-medium mb-2">Top Performing Vendors</h3>
                <div class="space-y-2">
                  <div v-for="vendor in topVendors" :key="vendor.id" class="flex justify-between">
                    <span class="text-sm">{{ vendor.name }}</span>
                    <span class="text-sm font-medium">{{ vendor.responseCount }} responses</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SMS Campaign Modal -->
      <div v-if="showSmsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg w-full max-w-md">
          <h3 class="text-lg font-semibold mb-4">Create SMS Campaign</h3>
          <div class="space-y-4">
            <input
              v-model="newCampaign.name"
              placeholder="Campaign Name"
              class="w-full p-2 border rounded"
            >
            <textarea
              v-model="newCampaign.message"
              placeholder="SMS Message with {form_link} placeholder"
              class="w-full p-2 border rounded resize-none"
              rows="3"
            ></textarea>
            <select v-model="newCampaign.vendorId" class="w-full p-2 border rounded">
              <option value="">Select Vendor</option>
              <option v-for="vendor in vendors" :key="vendor.id" :value="vendor.id">
                {{ vendor.name }}
              </option>
            </select>
          </div>
          <div class="flex justify-end space-x-2 mt-4">
            <button @click="showSmsModal = false" class="px-4 py-2 text-gray-600 hover:text-gray-800">
              Cancel
            </button>
            <button @click="createSmsCampaign" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
              Create Campaign
            </button>
          </div>
        </div>
      </div>

      <!-- Telegram Modal -->
      <div v-if="showTelegramModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg w-full max-w-md">
          <h3 class="text-lg font-semibold mb-4">Configure Telegram Group</h3>
          <div class="space-y-4">
            <input
              v-model="newTelegramGroup.name"
              placeholder="Group Name"
              class="w-full p-2 border rounded"
            >
            <input
              v-model="newTelegramGroup.chatId"
              placeholder="Chat ID"
              class="w-full p-2 border rounded"
            >
            <input
              v-model="newTelegramGroup.botToken"
              placeholder="Bot Token"
              class="w-full p-2 border rounded"
              type="password"
            >
          </div>
          <div class="flex justify-end space-x-2 mt-4">
            <button @click="showTelegramModal = false" class="px-4 py-2 text-gray-600 hover:text-gray-800">
              Cancel
            </button>
            <button @click="addTelegramGroup" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
              Add Group
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useCrmStore } from '@/stores/telegram'
import { storeToRefs } from 'pinia'
import AppLayout from '@/Layouts/AppLayout.vue'
import { notify } from '@/utils/toast'

// Store
const crmStore = useCrmStore()
const {
  stats,
  vendors,
  smsCampaigns,
  formResponses,
  telegramGroups,
  telegramBroadcasts,
  aiResponses
} = storeToRefs(crmStore)

// Reactive data
const activeTab = ref('sms')
const selectedVendor = ref('')
const showSmsModal = ref(false)
const showTelegramModal = ref(false)
const aiQuery = ref('')

// Form data
const newCampaign = ref({
  name: '',
  message: '',
  vendorId: ''
})

const newTelegramGroup = ref({
  name: '',
  chatId: '',
  botToken: ''
})

// Tab configuration
const tabs = [
  { id: 'sms', name: 'SMS Campaigns' },
  { id: 'responses', name: 'Form Responses' },
  { id: 'telegram', name: 'Telegram' },
  { id: 'ai', name: 'AI Assistant' },
  { id: 'analytics', name: 'Analytics' }
]

// Computed properties
const filteredResponses = computed(() => {
  if (!selectedVendor.value) return formResponses.value
  return formResponses.value.filter(response => response.vendorId === selectedVendor.value)
})

const topVendors = computed(() => {
  const vendorStats = vendors.value.map(vendor => ({
    ...vendor,
    responseCount: formResponses.value.filter(r => r.vendorId === vendor.id).length
  }))
  return vendorStats.sort((a, b) => b.responseCount - a.responseCount).slice(0, 5)
})

// Methods
const formatTime = (timestamp) => {
  return new Date(timestamp).toLocaleString()
}

const createSmsCampaign = async () => {
  try {
    await crmStore.createSmsCampaign(newCampaign.value)
    notify('SMS Campaign created successfully', 'success')
    showSmsModal.value = false
    newCampaign.value = { name: '', message: '', vendorId: '' }
  } catch (error) {
    notify('Failed to create SMS campaign', 'error')
  }
}

const broadcastToTelegram = async (response) => {
  try {
    await crmStore.broadcastToTelegram(response)
    notify('Message broadcasted to Telegram', 'success')
  } catch (error) {
    notify('Failed to broadcast message', 'error')
  }
}

const generateAiResponse = async (response) => {
  try {
    await crmStore.generateAiResponse(response)
    notify('AI response generated', 'success')
  } catch (error) {
    notify('Failed to generate AI response', 'error')
  }
}

const addTelegramGroup = async () => {
  try {
    await crmStore.addTelegramGroup(newTelegramGroup.value)
    notify('Telegram group added successfully', 'success')
    showTelegramModal.value = false
    newTelegramGroup.value = { name: '', chatId: '', botToken: '' }
  } catch (error) {
    notify('Failed to add Telegram group', 'error')
  }
}

const processAiQuery = async () => {
  try {
    await crmStore.processAiQuery(aiQuery.value)
    notify('Query processed successfully', 'success')
    aiQuery.value = ''
  } catch (error) {
    notify('Failed to process query', 'error')
  }
}

const trainAiModel = async () => {
  try {
    await crmStore.trainAiModel()
    notify('AI model training initiated', 'success')
  } catch (error) {
    notify('Failed to train AI model', 'error')
  }
}

// Lifecycle
onMounted(() => {
  crmStore.loadDashboardData()
})

// Watch for real-time updates
watch(() => crmStore.newResponse, (newResponse) => {
  if (newResponse) {
    notify(`New response from ${newResponse.clientName}`, 'info')
  }
}, { deep: true })
</script>