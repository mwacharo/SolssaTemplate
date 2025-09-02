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
                  <v-list-item @click="openNewcall" class="rounded-lg mb-2 hover-elevation-2">
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


                      <v-chip :color="webrtc.connectionStatusColor" small class="white--text px-2">
                        <v-icon left small>mdi-circle</v-icon>
                        {{ webrtc.connectionStatusText }}
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
                          <div class="text-h6">{{ stats.summary_rejected_incoming_calls +
                            stats.summary_rejected_outgoing_calls }}</div>
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
            <v-btn color="primary" variant="outlined" @click="refreshData" :loading="agentStore.loading" size="small">
              <v-icon start>mdi-refresh</v-icon>
              Refresh
            </v-btn>

            <v-select v-model="itemsPerPage" :items="itemsPerPageOptions" label="Rows per page" variant="outlined"
              density="compact" style="max-width: 120px;" class="ml-3" />
          </div>

          <div class="controls-right">
            <v-chip color="info" variant="outlined" size="small">
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
            <v-chip :color="getDurationColor(Number(item.durationInSeconds))" size="small" variant="tonal">
              {{ formatDuration(Number(item.durationInSeconds)) }}
            </v-chip>
          </template>

          <!-- Call Status -->
          <template #item.status="{ item }">
            <v-chip :color="getStatusColor(item.status)" size="small" variant="flat">
              <v-icon start size="12">{{ getStatusIcon(item.status) }}</v-icon>
              {{ item.status }}
            </v-chip>
          </template>

          <!-- Service Type -->
          <template #item.description="{ item }">
            <div class="service-cell">
              <v-icon :color="getServiceColor(item.description)" size="16" class="mr-2">
                {{ getServiceIcon(item.description) }}
              </v-icon>
              <span>{{ item.description }}</span>
            </div>
          </template>

          <!-- Hangup Cause -->
          <template #item.lastBridgeHangupCause="{ item }">
            <v-tooltip :text="getHangupCauseDescription(item.lastBridgeHangupCause)">
              <template #activator="{ props }">
                <v-chip v-bind="props" :color="getHangupCauseColor(item.lastBridgeHangupCause)" size="small" variant="outlined">
                  {{ item.lastBridgeHangupCause }}
                </v-chip>
              </template>
            </v-tooltip>
          </template>

          <!-- Call Session State -->
          <template #item.callSessionState="{ item }">
            <v-badge :color="getSessionStateColor(item.callSessionState)" dot inline>
              <span>{{ item.callSessionState }}</span>
            </v-badge>
          </template>

          <!-- Actions -->
          <template #item.actions="{ item }">
            <div class="action-buttons">
              <v-tooltip text="Play Recording">
                <template #activator="{ props }">
                  <v-btn v-bind="props" icon size="small" color="primary" variant="outlined"
                    @click="playRecording(item)" :disabled="!item.recordingUrl">
                    <v-icon>mdi-play</v-icon>
                  </v-btn>
                </template>
              </v-tooltip>

              <v-tooltip text="Download Recording">
                <template #activator="{ props }">
                  <v-btn v-bind="props" icon size="small" color="success" variant="outlined"
                    @click="downloadRecording(item)" :disabled="!item.recordingUrl" class="ml-2">
                    <v-icon>mdi-download</v-icon>
                  </v-btn>
                </template>
              </v-tooltip>

              <v-tooltip text="Call Back">
                <template #activator="{ props }">
                  <v-btn v-bind="props" icon size="small" color="warning" variant="outlined" @click="callBack(item)"
                    class="ml-2">
                    <v-icon>mdi-phone-return</v-icon>
                  </v-btn>
                </template>
              </v-tooltip>

              <v-menu>
                <template #activator="{ props }">
                  <v-btn v-bind="props" icon size="small" variant="text" class="ml-2">
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


    <!-- call analysis  -->


      <div 
      v-if="showDialog" 
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white w-full max-w-2xl rounded-lg shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="flex justify-between items-center bg-gray-100 px-4 py-2">
          <h2 class="text-lg font-semibold">Call Statistics</h2>
          <button @click="closeViewDetailsDialog" class="text-gray-500 hover:text-black">✖</button>
        </div>

        <!-- Content -->
        <div class="p-4 space-y-3">
          <p><strong>Direction:</strong> {{ selectedCall.direction }}</p>
          <p><strong>Caller:</strong> {{ selectedCall.callerNumber }}</p>
          <p><strong>Destination:</strong> {{ selectedCall.destinationNumber }}</p>
          <p><strong>Duration:</strong> {{ selectedCall.durationInSeconds }} seconds</p>
          <p><strong>Status:</strong> {{ selectedCall.status }}</p>
          <p><strong>Start Time:</strong> {{ selectedCall.callStartTime }}</p>

          <!-- Recording -->
          <div v-if="selectedCall.recordingUrl">
            <p class="font-semibold">Recording:</p>
            <audio controls :src="selectedCall.recordingUrl" class="w-full"></audio>
          </div>

          <!-- Transcript -->
          <div v-if="selectedCall.calltranscripts?.length">
            <h3 class="font-semibold mt-4">Transcript</h3>
            <p>{{ selectedCall.calltranscripts[0].transcript }}</p>

            <div class="mt-2 p-2 border rounded bg-gray-50">
              <p><strong>Sentiment:</strong> {{ selectedCall.calltranscripts[0].sentiment }}</p>
              <p><strong>Fulfillment Score:</strong> {{ selectedCall.calltranscripts[0].fulfillment_score }}%</p>
              <p><strong>Customer Service Rating:</strong> {{ selectedCall.calltranscripts[0].cs_rating }}/5</p>
              <p><strong>Intent:</strong> {{ selectedCall.calltranscripts[0].analysis.intent }}</p>
              <p><strong>Keywords:</strong> {{ selectedCall.calltranscripts[0].analysis.keywords.join(", ") }}</p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-end bg-gray-100 px-4 py-2">
          <button @click="closeViewDetailsDialog" class="bg-blue-600 text-white px-3 py-1 rounded">
            Close
          </button>
        </div>
      </div>
    </div>

  <CallDialogs v-model="callStore.dialogType" />

    <!-- Call Dialogs -->
    <!-- <CallDialogs v-model:dialogType="dialogType" v-model:isSmsDialog="isSmsDialog" v-model:callData="callData"
      v-model:contactForm="contactForm" @close="closeDialog" @saveContact="saveContact" @sendSms="sendSms" /> -->
  </AppLayout>
</template>



<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue';
import { useAgensudotStore } from '@/stores/agent'
import { useCallCenterStore } from '@/stores/callCenter';
import { notify } from '@/utils/toast';
import { useWebRTCStore } from '@/stores/webrtc'

import CallDialogs from './Dialogs/CallDialogs.vue';
import { usePage } from '@inertiajs/vue3';


    const page = usePage();

        const userId = computed(() => {
            const id = page.props.auth?.user?.id;
            // console.debug("✅ Computed userId:", id);
            return id;
        });
    

const webrtc = useWebRTCStore()
const props = defineProps({
  search: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['refresh'])
const agentStore = useAgentStore()
const callStore = useCallCenterStore()

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


const showDialog = ref(false)
const selectedCall = ref({})





  const fetchAgentStats = () => {
      axios.get(`/api/v1/agent-stats/${userId.value}`)

        .then(response => {
          stats.value = response.data;
          console.log('Agent stats:', stats.value);

        })
        .catch(error => {
          console.error('Error fetching agent stats:', error);
        });
    };


// Table headers
const headers = [
  // { title: 'Date', value: 'created_at', width: '140px' },
  { title: 'Call Start', value: 'callStartTime', width: '140px' },
  // { title: 'Direction', value: 'direction', width: '100px' },
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          { title: 'Caller', value: 'callerNumber', width: '130px' },
  { title: 'Caller Carrier', value: 'callerCarrierName', width: '120px' },
  { title: 'Caller Country', value: 'callerCountryCode', width: '100px' },
  // { title: 'Destination', value: 'destinationNumber', width: '130px' },
  { title: 'Client Dialed', value: 'clientDialedNumber', width: '130px' },
  { title: 'Duration', value: 'durationInSeconds', width: '100px' },
  { title: 'Currency', value: 'currencyCode', width: '80px' },
  { title: 'Amount', value: 'amount', width: '90px' },
  // { title: 'Service', value: 'description', width: '120px' },
  { title: 'Status', value: 'status', width: '100px' },
  // { title: 'Hangup Cause', value: 'hangupCause', width: '120px' },
  { title: 'Bridge Hangup', value: 'lastBridgeHangupCause', width: '120px' },
  // { title: 'Session State', value: 'callSessionState', width: '120px' },
  // { title: 'Order No', value: 'orderNo', width: '100px' },
  // { title: 'Notes', value: 'notes', width: '150px' },
  // { title: 'Conference', value: 'conference', width: '100px' },
  // { title: 'Recording', value: 'recordingUrl', width: '100px' },
  { title: 'Actions', value: 'actions', sortable: false, width: '200px' }
]

// Methods
const loadItems = async (options) => {
  try {
    const result = await agentStore.fetchCallHistory(options)
    totalItems.value = result.total
  } catch (error) {
    console.error('Error loading call history:', error)
    notify.error('Failed to load call history', 'error')
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
    notify.error('Recording not available')
  }
}

const downloadRecording = (item) => {
  if (item.recordingUrl) {
    const link = document.createElement('a')
    link.href = item.recordingUrl
    link.download = `call-recording-${item.id}.mp3`
    link.click()
  } else {
    notify.error('Recording not available')
  }
}

const callBack = (item) => {
  const phoneNumber = item.callerNumber || item.destinationNumber
  if (phoneNumber) {
    callCenterStore.makeCall(phoneNumber)
    notify.info(`Calling back ${phoneNumber}`)
  } else {
    notify.error('Phone number not available')
  }
}

const viewDetails = (item) => {
  console.log('View details for:', item)

    selectedCall.value = item
  showDialog.value = true
  // Open details modal or navigate to details page
}

// Close dialog
const closeViewDetailsDialog = () => {
  showDialog.value = false
}

const addToContacts = (item) => {
  console.log('Add to contacts:', item)
  notify.success('Added to contacts')
}

const sendSMS = (item) => {
  console.log('Send SMS to:', item)
  notify.info('SMS composer opened')
}


// write functions to open the dedined dialogs 



const openNewcall = () => {
  console.debug('you clicked me')
  if (typeof callStore.openNewCallDialog === 'function') {
    callStore.openNewCallDialog()
  } else {
    notify.error('New Call dialog function not implemented in callStore')
  }
}
// Open dialog for "Call an agent"
const openCallAgent = () => {
  if (typeof callStore.openCallAgentDialog === 'function') {
    callStore.openCallAgentDialog()
  } else {
    notify.error('Call Agent dialog function not implemented in callStore')
  }
}

// Open dialog for "Check Queue"
const openQueueDialog = () => {
  if (typeof callStore.openQueueDialog === 'function') {
    callStore.openQueueDialog()
  } else {
    notify.error('Queue dialog function not implemented in callStore')
  }
}

// Close dialog handler
const closeDialog = () => {
  callStore.Dialog()

}

// Save contact handler (stub)
const saveContact = (contact) => {
  notify.success('Contact saved')
  closeDialog()
}

// Send SMS handler (stub)
const sendSms = (smsData) => {
  notify.success('SMS sent')
  closeDialog()
}

// User initials for avatar
const userInitials = computed(() => {
  const name = (typeof $page !== 'undefined' && $page.props?.auth?.user?.name) || ''
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
})



// Open dialog for "Make a new Call"
// watch(newCall, (val) => {
//   if (val) {
//     dialogType.value = 'newCall'
//     callData.value = null
//   }
// })


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
  fetchAgentStats()
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
  background: #ffffff;
  border-radius: 12px;
  overflow: hidden;
}

/* Fix data table styling for better visibility */
:deep(.v-data-table__wrapper) {
  border-radius: 12px;
  background: #ffffff;
}

:deep(.v-data-table-header) {
  background: #f5f5f5;
}

:deep(.v-data-table-header th) {
  color: #1976d2 !important;
  font-weight: 600;
  border-bottom: 1px solid #e0e0e0;
  background: #f8f9fa;
}

:deep(.v-data-table tbody tr) {
  transition: background-color 0.3s ease;
  background: #ffffff;
}

:deep(.v-data-table tbody tr:hover) {
  background: #f5f5f5 !important;
}

:deep(.v-data-table tbody td) {
  color: #333333 !important;
  border-bottom: 1px solid #e0e0e0;
  background: transparent;
}

/* Specific fixes for custom cell content */
.date-cell {
  display: flex;
  flex-direction: column;
}

.date-primary {
  font-weight: 500;
  color: #333333 !important;
}

.date-secondary {
  font-size: 0.75rem;
  color: #666666 !important;
}

.phone-cell {
  display: flex;
  align-items: center;
}

.phone-number {
  font-family: 'Roboto Mono', monospace;
  font-size: 0.85rem;
  color: #333333 !important;
}

.service-cell {
  display: flex;
  align-items: center;
  color: #333333 !important;
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
  color: #666666;
}

.no-data-container h3 {
  margin: 1rem 0 0.5rem 0;
  color: #333333;
}

.no-data-container p {
  margin: 0;
  font-size: 0.9rem;
  color: #666666;
}

.loading-container p {
  margin: 0;
  color: #666666;
}

/* Dark theme support (optional) */
.theme--dark .call-history-table {
  background: #1e1e1e;
}

.theme--dark :deep(.v-data-table__wrapper) {
  background: #1e1e1e;
}

.theme--dark :deep(.v-data-table-header) {
  background: #2d2d2d;
}

.theme--dark :deep(.v-data-table-header th) {
  color: #ffffff !important;
  border-bottom: 1px solid #424242;
  background: #2d2d2d;
}

.theme--dark :deep(.v-data-table tbody tr) {
  background: #1e1e1e;
}

.theme--dark :deep(.v-data-table tbody tr:hover) {
  background: #2d2d2d !important;
}

.theme--dark :deep(.v-data-table tbody td) {
  color: #ffffff !important;
  border-bottom: 1px solid #424242;
}

.theme--dark .date-primary {
  color: #ffffff !important;
}

.theme--dark .date-secondary {
  color: #b0b0b0 !important;
}

.theme--dark .phone-number {
  color: #ffffff !important;
}

.theme--dark .service-cell {
  color: #ffffff !important;
}

.theme--dark .no-data-container,
.theme--dark .loading-container {
  color: #b0b0b0;
}

.theme--dark .no-data-container h3 {
  color: #ffffff;
}

.theme--dark .no-data-container p,
.theme--dark .loading-container p {
  color: #b0b0b0;
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
  scrollbar-color: #c0c0c0 transparent;
}

:deep(.v-data-table__wrapper::-webkit-scrollbar) {
  height: 8px;
  width: 8px;
}

:deep(.v-data-table__wrapper::-webkit-scrollbar-track) {
  background: #f0f0f0;
  border-radius: 4px;
}

:deep(.v-data-table__wrapper::-webkit-scrollbar-thumb) {
  background: #c0c0c0;
  border-radius: 4px;
}

:deep(.v-data-table__wrapper::-webkit-scrollbar-thumb:hover) {
  background: #a0a0a0;
}

/* Additional fixes for chip colors to ensure visibility */
:deep(.v-chip) {
  color: #ffffff !important;
}

:deep(.v-chip.v-chip--variant-outlined) {
  color: inherit !important;
}

:deep(.v-chip.v-chip--variant-tonal) {
  color: rgba(0, 0, 0, 0.87) !important;
}
</style>