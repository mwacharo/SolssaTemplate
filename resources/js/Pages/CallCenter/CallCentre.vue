
<template>
  <AppLayout title="Dashboard">
    <div class="p-4">
      <!-- Top Information Cards -->
      <v-container fluid class="pa-2">
        <v-row>
          <!-- Quick Actions Card -->
          <v-col cols="12" md="6">
            <v-card elevation="2" class="rounded-lg">
              <v-card-title class="primary white--text d-flex align-center py-3">
                <v-icon left color="white">mdi-lightning-bolt</v-icon>
                Quick Actions
              </v-card-title>
              <v-card-text class="py-4">
                <v-list nav dense>
                  <v-list-item @click="newCall = true" class="rounded-lg mb-2 hover-elevation-2">
                    <v-list-item-icon>
                      <v-icon color="success">mdi-phone-plus</v-icon>
                    </v-list-item-icon>
                    <v-list-item-content>
                      <v-list-item-title>Make a new Call</v-list-item-title>
                    </v-list-item-content>
                    <v-list-item-action>
                      <v-icon>mdi-chevron-right</v-icon>
                    </v-list-item-action>
                  </v-list-item>

                  <v-list-item @click="openCallAgent" class="rounded-lg mb-2 hover-elevation-2">
                    <v-list-item-icon>
                      <v-icon color="info">mdi-account-voice</v-icon>
                    </v-list-item-icon>
                    <v-list-item-content>
                      <v-list-item-title>Call an agent</v-list-item-title>
                    </v-list-item-content>
                    <v-list-item-action>
                      <v-icon>mdi-chevron-right</v-icon>
                    </v-list-item-action>
                  </v-list-item>

                  <v-list-item class="rounded-lg mb-2 hover-elevation-2">
                    <v-list-item-icon>
                      <v-icon color="error">mdi-phone-missed</v-icon>
                    </v-list-item-icon>
                    <v-list-item-content>
                      <v-list-item-title>Missed {{ stats.summary_call_missed }}</v-list-item-title>
                    </v-list-item-content>
                    <v-list-item-action>
                      <v-badge :content="summary_call_missed" :value="summary_call_missed > 0"
                          color="error" overlap>
                        <v-icon>mdi-chevron-right</v-icon>
                      </v-badge>
                    </v-list-item-action>
                  </v-list-item>

                  <v-list-item @click="openQueueDialog" class="rounded-lg hover-elevation-2">
                    <v-list-item-icon>
                      <v-icon color="warning">mdi-account-multiple-check</v-icon>
                    </v-list-item-icon>
                    <v-list-item-content>
                      <v-list-item-title>Check Queue</v-list-item-title>
                    </v-list-item-content>
                    <v-list-item-action>
                      <v-icon>mdi-chevron-right</v-icon>
                    </v-list-item-action>
                  </v-list-item>
                </v-list>
              </v-card-text>
            </v-card>
          </v-col>

          <!-- Agent Information Card -->
          <v-col cols="12" md="6">
            <v-card elevation="2" class="rounded-lg">
              <v-card-title class="primary white--text d-flex align-center py-3">
                <v-icon left color="white">mdi-account-details</v-icon>
                Agent Dashboard
              </v-card-title>

              <v-card-text class="py-4">
                <div class="d-flex align-center">
                  <v-avatar size="60" color="primary" class="mr-4">
                    <span class="white--text text-h5">{{ userInitials }}</span>
                  </v-avatar>
                  <div class="flex-grow-1">
                    <div class="d-flex align-center">
                      <div>
                        <p class="text-h6 mb-0">{{ $page.props.auth.user.name }}</p>
                        <p class="text-body-2 mb-0 grey--text">
                          <v-icon small class="mr-1">mdi-email</v-icon>
                          {{ $page.props.auth.user.email }}
                        </p>
                      </div>
                      <v-spacer></v-spacer>
                      <v-chip v-if="agent" :color="getStatusColor(agent.status)" small
                          class="white--text px-2">
                        <v-icon left small>mdi-circle</v-icon>
                        {{ getStatusText(agent.status) }}
                      </v-chip>
                    </div>
                  </div>
                </div>

                <v-divider class="my-4"></v-divider>

                <v-row class="mt-2">
                  <v-col cols="12" sm="6">
                    <v-card class="rounded-lg pa-3 mb-3" outlined>
                      <div class="d-flex align-center">
                        <v-icon color="success" left>mdi-phone-check</v-icon>
                        <div class="ml-2">
                          <div class="text-caption grey--text">Connected</div>
                          <div class="text-h6">{{ stats.summary_call_completed }}</div>
                        </div>
                      </div>
                    </v-card>
                  </v-col>

                  <v-col cols="12" sm="6">
                    <v-card class="rounded-lg pa-3 mb-3" outlined>
                      <div class="d-flex align-center">
                        <v-icon color="primary" left>mdi-phone-outgoing</v-icon>
                        <div class="ml-2">
                          <div class="text-caption grey--text">Outbound</div>
                          <div class="text-h6">{{ stats.summary_outbound_call_completed }}</div>
                        </div>
                      </div>
                    </v-card>
                  </v-col>

                  <v-col cols="12" sm="6">
                    <v-card class="rounded-lg pa-3 mb-3" outlined>
                      <div class="d-flex align-center">
                        <v-icon color="info" left>mdi-phone-incoming</v-icon>
                        <div class="ml-2">
                          <div class="text-caption grey--text">Incoming</div>
                          <div class="text-h6">{{ stats.summary_inbound_call_completed }}</div>
                        </div>
                      </div>
                    </v-card>
                  </v-col>

                  <v-col cols="12" sm="6">
                    <v-card class="rounded-lg pa-3" outlined>
                      <div class="d-flex align-center">
                        <v-icon color="error" left>mdi-phone-cancel</v-icon>
                        <div class="ml-2">
                          <div class="text-caption grey--text">Rejected</div>
                          <div class="text-h6">{{ stats.summary_rejected_incoming_calls + stats.summary_rejected_outgoing_calls }}</div>
                        </div>
                      </div>
                    </v-card>
                  </v-col>
                </v-row>

                <v-btn color="primary" text class="mt-2" @click="showCallDetails = !showCallDetails">
                  {{ showCallDetails ? 'Hide' : 'Show' }} detailed stats
                  <v-icon right>{{ showCallDetails ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
                </v-btn>

                <v-expand-transition>
                  <div v-if="showCallDetails">
                    <v-divider class="my-3"></v-divider>
                    <v-simple-table dense>
                      <template v-slot:default>
                        <tbody>
                          <tr>
                            <td>Incoming Rejected</td>
                            <td class="text-right">{{ stats.summary_rejected_incoming_calls }}</td>
                          </tr>
                          <tr>
                            <td>Outgoing Rejected</td>
                            <td class="text-right">{{ stats.summary_rejected_outgoing_calls }}</td>
                          </tr>
                          <tr>
                            <td>Outgoing User Busy</td>
                            <td class="text-right">{{ stats.summary_user_busy_outgoing_calls }}</td>
                          </tr>
                        </tbody>
                      </template>
                    </v-simple-table>
                  </div>
                </v-expand-transition>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>

      <!-- Call History Container -->
      <div class="call-history-container">
        <!-- Table Controls -->
        <div class="table-controls mb-4">
          <div class="controls-left">
            <v-btn
              color="primary"
              variant="outlined"
              @click="refreshData"
              :loading="agentStore.loading"
              size="small"
            >
              <v-icon start>mdi-refresh</v-icon>
              Refresh
            </v-btn>
            
            <v-select
              v-model="itemsPerPage"
              :items="itemsPerPageOptions"
              label="Rows per page"
              variant="outlined"
              density="compact"
              style="max-width: 120px;"
              class="ml-3"
            />
          </div>
          
          <div class="controls-right">
            <v-chip
              color="info"
              variant="outlined"
              size="small"
            >
              Total: {{ totalItems }}
            </v-chip>
          </div>
        </div>

        <!-- Data Table -->
        <v-data-table-server
          v-model:items-per-page="itemsPerPage"
          v-model:page="currentPage"
          v-model:sort-by="sortBy"
          :headers="headers"
          :items="agentStore.callHistory"
          :items-length="totalItems"
          :loading="agentStore.loading"
          :search="search"
          class="call-history-table"
          density="comfortable"
          @update:options="loadItems"
        >
          <!-- Date Column -->
          <template #item.created_at="{ item }">
            <div class="date-cell">
              <div class="date-primary">{{ formatDate(item.created_at) }}</div>
              <div class="date-secondary">{{ formatTime(item.created_at) }}</div>
            </div>
          </template>

          <!-- Phone Numbers -->
          <template #item.callerNumber="{ item }">
            <div class="phone-cell">
              <v-icon size="16" color="primary" class="mr-2">mdi-phone-outgoing</v-icon>
              <span class="phone-number">{{ formatPhoneNumber(item.callerNumber) }}</span>
            </div>
          </template>

          <template #item.destinationNumber="{ item }">
            <div class="phone-cell">
              <v-icon size="16" color="success" class="mr-2">mdi-phone-incoming</v-icon>
              <span class="phone-number">{{ formatPhoneNumber(item.destinationNumber) }}</span>
            </div>
          </template>

          <!-- Duration -->
          <template #item.durationInSeconds="{ item }">
            <v-chip
              :color="getDurationColor(item.durationInSeconds)"
              size="small"
              variant="tonal"
            >
              {{ formatDuration(item.durationInSeconds) }}
            </v-chip>
          </template>

          <!-- Call Status -->
          <template #item.status="{ item }">
            <v-chip
              :color="getStatusColor(item.status)"
              size="small"
              variant="flat"
            >
              <v-icon start size="12">{{ getStatusIcon(item.status) }}</v-icon>
              {{ item.status }}
            </v-chip>
          </template>

          <!-- Service Type -->
          <template #item.description="{ item }">
            <div class="service-cell">
              <v-icon
                :color="getServiceColor(item.description)"
                size="16"
                class="mr-2"
              >
                {{ getServiceIcon(item.description) }}
              </v-icon>
              <span>{{ item.description }}</span>
            </div>
          </template>

          <!-- Hangup Cause -->
          <template #item.lastBridgeHangupCause="{ item }">
            <v-tooltip :text="getHangupCauseDescription(item.lastBridgeHangupCause)">
              <template #activator="{ props }">
                <v-chip
                  v-bind="props"
                  :color="getHangupCauseColor(item.lastBridgeHangupCause)"
                  size="small"
                  variant="outlined"
                >
                  {{ item.lastBridgeHangupCause }}
                </v-chip>
              </template>
            </v-tooltip>
          </template>

          <!-- Call Session State -->
          <template #item.callSessionState="{ item }">
            <v-badge
              :color="getSessionStateColor(item.callSessionState)"
              dot
              inline
            >
              <span>{{ item.callSessionState }}</span>
            </v-badge>
          </template>

          <!-- Actions -->
          <template #item.actions="{ item }">
            <div class="action-buttons">
              <v-tooltip text="Play Recording">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon
                    size="small"
                    color="primary"
                    variant="outlined"
                    @click="playRecording(item)"
                    :disabled="!item.recordingUrl"
                  >
                    <v-icon>mdi-play</v-icon>
                  </v-btn>
                </template>
              </v-tooltip>

              <v-tooltip text="Download Recording">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon
                    size="small"
                    color="success"
                    variant="outlined"
                    @click="downloadRecording(item)"
                    :disabled="!item.recordingUrl"
                    class="ml-2"
                  >
                    <v-icon>mdi-download</v-icon>
                  </v-btn>
                </template>
              </v-tooltip>

              <v-tooltip text="Call Back">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon
                    size="small"
                    color="warning"
                    variant="outlined"
                    @click="callBack(item)"
                    class="ml-2"
                  >
                    <v-icon>mdi-phone-return</v-icon>
                  </v-btn>
                </template>
              </v-tooltip>

              <v-menu>
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon
                    size="small"
                    variant="text"
                    class="ml-2"
                  >
                    <v-icon>mdi-dots-vertical</v-icon>
                  </v-btn>
                </template>
                <v-list density="compact">
                  <v-list-item @click="viewDetails(item)">
                    <template #prepend>
                      <v-icon>mdi-eye</v-icon>
                    </template>
                    <v-list-item-title>View Details</v-list-item-title>
                  </v-list-item>
                  <v-list-item @click="addToContacts(item)">
                    <template #prepend>
                      <v-icon>mdi-account-plus</v-icon>
                    </template>
                    <v-list-item-title>Add to Contacts</v-list-item-title>
                  </v-list-item>
                  <v-list-item @click="sendSMS(item)">
                    <template #prepend>
                      <v-icon>mdi-message</v-icon>
                    </template>
                    <v-list-item-title>Send SMS</v-list-item-title>
                  </v-list-item>
                </v-list>
              </v-menu>
            </div>
          </template>

          <!-- No Data -->
          <template #no-data>
            <div class="no-data-container">
              <v-icon size="64" color="grey-lighten-1">mdi-phone-off</v-icon>
              <h3>No call history found</h3>
              <p>Call history will appear here once you start making calls.</p>
            </div>
          </template>

          <!-- Loading -->
          <template #loading>
            <div class="loading-container">
              <v-progress-circular indeterminate color="primary" size="48" />
              <p class="mt-4">Loading call history...</p>
            </div>
          </template>
        </v-data-table-server>
      </div>
    </div>
  </AppLayout>
</template>



<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue';

import { useAgentStore } from '@/stores/agent'
import { useCallCenterStore } from '@/stores/callCenter'

import { notify } from '@/utils/toast';


const props = defineProps({
  search: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['refresh'])

const agentStore = useAgentStore()
const callCenterStore = useCallCenterStore()
// const toast = useToast()




// Add missing reactive data
const stats = ref({
  summary_call_missed: 0,
  summary_call_completed: 0,
  summary_outbound_call_completed: 0,
  summary_inbound_call_completed: 0,
  summary_rejected_incoming_calls: 0,
  summary_rejected_outgoing_calls: 0,
  summary_user_busy_outgoing_calls: 0
})


// Reactive data
const currentPage = ref(1)
const itemsPerPage = ref(15)
const sortBy = ref([])
const totalItems = ref(0)

const itemsPerPageOptions = [10, 15, 25, 50, 100]

// Table headers
const headers = [
  { title: 'Date', value: 'created_at', width: '140px' },
  { title: 'Caller', value: 'callerNumber', width: '130px' },
  { title: 'Destination', value: 'destinationNumber', width: '130px' },
  { title: 'Client Dialed', value: 'clientDialedNumber', width: '130px' },
  { title: 'Duration', value: 'durationInSeconds', width: '100px' },
  { title: 'Service', value: 'description', width: '120px' },
  { title: 'Carrier', value: 'callerCarrierName', width: '120px' },
  { title: 'Status', value: 'status', width: '100px' },
  { title: 'Hangup Cause', value: 'lastBridgeHangupCause', width: '120px' },
  { title: 'Session State', value: 'callSessionState', width: '120px' },
  { title: 'Actions', value: 'actions', sortable: false, width: '200px' }
]

// Methods
const loadItems = async (options) => {
  try {
    const result = await agentStore.fetchCallHistory(options)
    totalItems.value = result.total
  } catch (error) {
    console.error('Error loading call history:', error)
    notify('Failed to load call history', 'error')
  }
}

const refreshData = () => {
  loadItems({
    page: currentPage.value,
    itemsPerPage: itemsPerPage.value,
    sortBy: sortBy.value,
    search: props.search
  })
  emit('refresh')
}

// Formatting functions
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: '2-digit',
    year: 'numeric'
  })
}

const formatTime = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatPhoneNumber = (phone) => {
  if (!phone) return 'N/A'
  // Simple phone formatting - customize as needed
  return phone.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3')
}

const formatDuration = (seconds) => {
  if (!seconds || seconds === 0) return '0s'
  
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60
  
  if (hours > 0) {
    return `${hours}h ${minutes}m ${secs}s`
  } else if (minutes > 0) {
    return `${minutes}m ${secs}s`
  } else {
    return `${secs}s`
  }
}

// Color and icon functions
const getDurationColor = (seconds) => {
  if (!seconds || seconds === 0) return 'error'
  if (seconds < 30) return 'warning'
  if (seconds < 300) return 'success'
  return 'primary'
}

const getStatusColor = (status) => {
  const colors = {
    'completed': 'success',
    'answered': 'success',
    'failed': 'error',
    'busy': 'warning',
    'no-answer': 'warning',
    'cancelled': 'error'
  }
  return colors[status?.toLowerCase()] || 'grey'
}

const getStatusIcon = (status) => {
  const icons = {
    'completed': 'mdi-check-circle',
    'answered': 'mdi-phone-check',
    'failed': 'mdi-phone-cancel',
    'busy': 'mdi-phone-busy',
    'no-answer': 'mdi-phone-missed',
    'cancelled': 'mdi-phone-hangup'
  }
  return icons[status?.toLowerCase()] || 'mdi-help-circle'
}

const getServiceColor = (service) => {
  const colors = {
    'voice': 'primary',
    'sms': 'success',
    'data': 'info'
  }
  return colors[service?.toLowerCase()] || 'grey'
}

const getServiceIcon = (service) => {
  const icons = {
    'voice': 'mdi-phone',
    'sms': 'mdi-message',
    'data': 'mdi-database'
  }
  return icons[service?.toLowerCase()] || 'mdi-cog'
}

const getHangupCauseColor = (cause) => {
  const colors = {
    'normal_clearing': 'success',
    'user_busy': 'warning',
    'no_answer': 'warning',
    'call_rejected': 'error',
    'network_out_of_order': 'error'
  }
  return colors[cause?.toLowerCase()] || 'grey'
}

const getHangupCauseDescription = (cause) => {
  const descriptions = {
    'normal_clearing': 'Call ended normally',
    'user_busy': 'User was busy',
    'no_answer': 'No answer from user',
    'call_rejected': 'Call was rejected',
    'network_out_of_order': 'Network error'
  }
  return descriptions[cause?.toLowerCase()] || 'Unknown cause'
}

const getSessionStateColor = (state) => {
  const colors = {
    'active': 'success',
    'inactive': 'grey',
    'terminated': 'error'
  }
  return colors[state?.toLowerCase()] || 'grey'
}

// Action handlers
const playRecording = (item) => {
  if (item.recordingUrl) {
    window.open(item.recordingUrl, '_blank')
  } else {
    toast.error('Recording not available')
  }
}

const downloadRecording = (item) => {
  if (item.recordingUrl) {
    const link = document.createElement('a')
    link.href = item.recordingUrl
    link.download = `call-recording-${item.id}.mp3`
    link.click()
  } else {
    toast.error('Recording not available')
  }
}

const callBack = (item) => {
  const phoneNumber = item.callerNumber || item.destinationNumber
  if (phoneNumber) {
    callCenterStore.makeCall(phoneNumber)
    toast.info(`Calling back ${phoneNumber}`)
  } else {
    toast.error('Phone number not available')
  }
}

const viewDetails = (item) => {
  console.log('View details for:', item)
  // Open details modal or navigate to details page
}

const addToContacts = (item) => {
  console.log('Add to contacts:', item)
  toast.success('Added to contacts')
}

const sendSMS = (item) => {
  console.log('Send SMS to:', item)
  toast.info('SMS composer opened')
}

// Watch for search changes
watch(() => props.search, () => {
  currentPage.value = 1
  loadItems({
    page: currentPage.value,
    itemsPerPage: itemsPerPage.value,
    sortBy: sortBy.value,
    search: props.search
  })
})

onMounted(() => {
  loadItems({
    page: currentPage.value,
    itemsPerPage: itemsPerPage.value,
    sortBy: sortBy.value,
    search: props.search
  })
})
</script>

<style scoped>
.call-history-container {
  padding: 1.5rem;
}

.table-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.controls-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.controls-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.call-history-table {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  overflow: hidden;
}

:deep(.v-data-table__wrapper) {
  border-radius: 12px;
}

:deep(.v-data-table-header) {
  background: rgba(255, 255, 255, 0.1);
}

:deep(.v-data-table-header th) {
  color: rgba(255, 255, 255, 0.9) !important;
  font-weight: 600;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

:deep(.v-data-table tbody tr) {
  transition: background-color 0.3s ease;
}

:deep(.v-data-table tbody tr:hover) {
  background: rgba(255, 255, 255, 0.05);
}

:deep(.v-data-table tbody td) {
  color: rgba(255, 255, 255, 0.8);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.date-cell {
  display: flex;
  flex-direction: column;
}

.date-primary {
  font-weight: 500;
  color: rgba(255, 255, 255, 0.9);
}

.date-secondary {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.6);
}

.phone-cell {
  display: flex;
  align-items: center;
}

.phone-number {
  font-family: 'Roboto Mono', monospace;
  font-size: 0.85rem;
}

.service-cell {
  display: flex;
  align-items: center;
}

.action-buttons {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.no-data-container,
.loading-container {
  text-align: center;
  padding: 3rem;
  color: rgba(255, 255, 255, 0.6);
}

.no-data-container h3 {
  margin: 1rem 0 0.5rem 0;
  color: rgba(255, 255, 255, 0.8);
}

.no-data-container p {
  margin: 0;
  font-size: 0.9rem;
}

.loading-container p {
  margin: 0;
  color: rgba(255, 255, 255, 0.7);
}

/* Responsive design */
@media (max-width: 768px) {
  .call-history-container {
    padding: 1rem;
  }
  
  .table-controls {
    flex-direction: column;
    align-items: stretch;
  }
  
  .controls-left,
  .controls-right {
    justify-content: center;
  }
  
  .action-buttons {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  :deep(.v-data-table) {
    font-size: 0.8rem;
  }
}

/* Custom scrollbars */
:deep(.v-data-table__wrapper) {
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
}

:deep(.v-data-table__wrapper::-webkit-scrollbar) {
  height: 8px;
  width: 8px;
}

:deep(.v-data-table__wrapper::-webkit-scrollbar-track) {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
}

:deep(.v-data-table__wrapper::-webkit-scrollbar-thumb) {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 4px;
}

:deep(.v-data-table__wrapper::-webkit-scrollbar-thumb:hover) {
  background: rgba(255, 255, 255, 0.5);
}
</style>