<template>
  <div>

    <!-- Incoming Call Dialog -->
    <v-dialog 
      v-model="incomingCallDialog" 
      max-width="500"
      persistent
      transition="dialog-bottom-transition"
      no-click-animation
      :retain-focus="false"
    >
      <v-card class="incoming-call-card" :class="{ 'ringing': isRinging }">
        <!-- Incoming Call State -->
        <div v-if="!callAccepted" class="incoming-call-content">
          <v-card-title class="text-center pa-6">
            <div class="incoming-call-header">
              <v-avatar size="80" class="call-avatar mb-4">
                <v-img v-if="callerInfo.avatar" :src="callerInfo.avatar" />
                <v-icon v-else size="40" color="white">mdi-account</v-icon>
              </v-avatar>
              
              <h2 class="text-h4 font-weight-bold mb-2">
                {{ callerInfo.name || 'Unknown Caller' }}
              </h2>
              
              <p class="text-h6 text-grey-darken-1 mb-2">
                {{ formatPhoneNumber(incomingCall.from) }}
              </p>
              
              <v-chip 
                v-if="callerInfo.type" 
                :color="getCallerTypeColor(callerInfo.type)" 
                class="mb-4"
              >
                {{ callerInfo.type }}
              </v-chip>
              
              <div class="call-status">
                <v-icon class="ringing-icon mr-2" color="success">mdi-phone-ring</v-icon>
                <span class="text-body-1">Incoming Call...</span>
              </div>
            </div>
          </v-card-title>

          <v-card-actions class="justify-center pa-6">
            <v-btn
              @click="declineCall"
              color="error"
              size="x-large"
              class="call-action-btn decline-btn mr-4"
              variant="flat"
            >
              <v-icon size="30">mdi-phone-hangup</v-icon>
            </v-btn>
            
            <v-btn
              @click="acceptCall"
              color="success"
              size="x-large"
              class="call-action-btn accept-btn"
              variant="flat"
              :loading="accepting"
            >
              <v-icon size="30">mdi-phone</v-icon>
            </v-btn>
          </v-card-actions>
        </div>

        <!-- Active Call with Client Details -->
        <div v-else class="client-details-content">
          <v-card-title class="gradient-header">
            <div class="d-flex align-center">
              <v-avatar size="40" class="mr-3">
                <v-img v-if="callerInfo.avatar" :src="callerInfo.avatar" />
                <v-icon v-else color="white">mdi-account</v-icon>
              </v-avatar>
              
              <div class="flex-grow-1">
                <h3 class="text-h6 font-weight-bold">
                  {{ callerInfo.name || 'Unknown Caller' }}
                </h3>
                <p class="text-body-2 opacity-80">
                  {{ formatPhoneNumber(incomingCall.from) }}
                </p>
              </div>
              
              <div class="call-timer">
                <div class="d-flex align-center">
                  <v-icon class="recording-pulse mr-1" size="12" color="error">mdi-circle</v-icon>
                  <span class="text-body-2">{{ callDuration }}</span>
                </div>
              </div>
            </div>
            
            <v-spacer />
            
            <!-- Call Controls -->
            <div class="call-controls">
              <v-btn 
                icon 
                size="small" 
                color="white" 
                variant="text"
                @click="toggleMute"
                :class="{ 'active': isMuted }"
              >
                <v-icon>{{ isMuted ? 'mdi-microphone-off' : 'mdi-microphone' }}</v-icon>
              </v-btn>
              
              <v-btn 
                icon 
                size="small" 
                color="white" 
                variant="text"
                @click="holdCall"
                :class="{ 'active': isOnHold }"
              >
                <v-icon>{{ isOnHold ? 'mdi-play' : 'mdi-pause' }}</v-icon>
              </v-btn>
              
              <v-btn 
                icon 
                size="small" 
                color="error" 
                variant="flat"
                @click="hangupCall"
              >
                <v-icon>mdi-phone-hangup</v-icon>
              </v-btn>
            </div>
          </v-card-title>

          <v-card-text class="pa-0">
            <!-- Tab Navigation -->
            <v-tabs v-model="activeTab" class="client-tabs">
              <v-tab value="details">
                <v-icon left size="20">mdi-account-details</v-icon>
                Details
              </v-tab>
              
              <v-tab value="orders" :disabled="loadingOrders">
                <v-icon left size="20">mdi-package-variant</v-icon>
                Orders
                <v-chip v-if="clientOrders.length" size="x-small" class="ml-2">
                  {{ clientOrders.length }}
                </v-chip>
              </v-tab>
              
              <v-tab value="tickets" :disabled="loadingTickets">
                <v-icon left size="20">mdi-ticket-outline</v-icon>
                Tickets
                <v-chip v-if="clientTickets.length" size="x-small" class="ml-2">
                  {{ clientTickets.length }}
                </v-chip>
              </v-tab>
              
              <v-tab value="notes">
                <v-icon left size="20">mdi-note-text</v-icon>
                Notes
              </v-tab>
            </v-tabs>

            <!-- Tab Content -->
            <v-window v-model="activeTab" class="client-content">
              <!-- Client Details Tab -->
              <v-window-item value="details" class="pa-4">
                <div class="client-info">
                  <v-row>
                    <v-col cols="12" md="6">
                      <div class="info-group mb-4">
                        <h4 class="text-subtitle-1 font-weight-bold mb-2">Contact Information</h4>
                        <div class="info-item">
                          <v-icon left size="16">mdi-phone</v-icon>
                          <span>{{ callerInfo.phone }}</span>
                        </div>
                        <div v-if="callerInfo.email" class="info-item">
                          <v-icon left size="16">mdi-email</v-icon>
                          <span>{{ callerInfo.email }}</span>
                        </div>
                        <div v-if="callerInfo.address" class="info-item">
                          <v-icon left size="16">mdi-map-marker</v-icon>
                          <span>{{ callerInfo.address }}</span>
                        </div>
                      </div>
                    </v-col>
                    
                    <v-col cols="12" md="6">
                      <div class="info-group mb-4">
                        <h4 class="text-subtitle-1 font-weight-bold mb-2">Account Status</h4>
                        <v-chip 
                          :color="getStatusColor(callerInfo.status)" 
                          size="small" 
                          class="mb-2"
                        >
                          {{ callerInfo.status }}
                        </v-chip>
                        <div v-if="callerInfo.customerSince" class="info-item">
                          <v-icon left size="16">mdi-calendar</v-icon>
                          <span>Customer since {{ callerInfo.customerSince }}</span>
                        </div>
                        <div v-if="callerInfo.totalOrders" class="info-item">
                          <v-icon left size="16">mdi-package-variant</v-icon>
                          <span>{{ callerInfo.totalOrders }} total orders</span>
                        </div>
                      </div>
                    </v-col>
                  </v-row>
                  
                  <!-- Quick Actions -->
                  <div class="quick-actions mt-4">
                    <h4 class="text-subtitle-1 font-weight-bold mb-2">Quick Actions</h4>
                    <div class="d-flex flex-wrap gap-2">
                      <v-btn size="small" variant="outlined" prepend-icon="mdi-package-variant">
                        New Order
                      </v-btn>
                      <v-btn size="small" variant="outlined" prepend-icon="mdi-ticket-plus">
                        Create Ticket
                      </v-btn>
                      <v-btn size="small" variant="outlined" prepend-icon="mdi-calendar-plus">
                        Schedule Callback
                      </v-btn>
                    </div>
                  </div>
                </div>
              </v-window-item>

              <!-- Orders Tab -->
              <v-window-item value="orders" class="pa-4">
                <div v-if="loadingOrders" class="text-center py-8">
                  <v-progress-circular indeterminate color="primary" />
                  <p class="mt-2">Loading orders...</p>
                </div>
                
                <div v-else-if="clientOrders.length === 0" class="text-center py-8">
                  <v-icon size="64" color="grey-lighten-1">mdi-package-variant-closed</v-icon>
                  <p class="text-body-1 mt-2">No orders found</p>
                </div>
                
                <div v-else class="orders-list">
                  <v-card
                    v-for="order in clientOrders"
                    :key="order.id"
                    class="order-card mb-3"
                    variant="outlined"
                    hover
                  >
                    <v-card-text class="pa-3">
                      <div class="d-flex align-center">
                        <div class="flex-grow-1">
                          <h4 class="text-subtitle-1 font-weight-bold">
                            Order #{{ order.orderNumber }}
                          </h4>
                          <p class="text-body-2 text-grey-darken-1">
                            {{ order.description }}
                          </p>
                          <div class="d-flex align-center mt-1">
                            <v-chip :color="getOrderStatusColor(order.status)" size="x-small" class="mr-2">
                              {{ order.status }}
                            </v-chip>
                            <span class="text-caption">{{ formatDate(order.date) }}</span>
                          </div>
                        </div>
                        <div class="text-right">
                          <p class="text-h6 font-weight-bold">{{ order.amount }}</p>
                          <v-btn size="x-small" variant="text" icon>
                            <v-icon>mdi-chevron-right</v-icon>
                          </v-btn>
                        </div>
                      </div>
                    </v-card-text>
                  </v-card>
                </div>
              </v-window-item>

              <!-- Tickets Tab -->
              <v-window-item value="tickets" class="pa-4">
                <div v-if="loadingTickets" class="text-center py-8">
                  <v-progress-circular indeterminate color="primary" />
                  <p class="mt-2">Loading tickets...</p>
                </div>
                
                <div v-else-if="clientTickets.length === 0" class="text-center py-8">
                  <v-icon size="64" color="grey-lighten-1">mdi-ticket-outline</v-icon>
                  <p class="text-body-1 mt-2">No support tickets found</p>
                </div>
                
                <div v-else class="tickets-list">
                  <v-card
                    v-for="ticket in clientTickets"
                    :key="ticket.id"
                    class="ticket-card mb-3"
                    variant="outlined"
                    hover
                  >
                    <v-card-text class="pa-3">
                      <div class="d-flex align-center">
                        <div class="flex-grow-1">
                          <h4 class="text-subtitle-1 font-weight-bold">
                            Ticket #{{ ticket.ticketNumber }}
                          </h4>
                          <p class="text-body-2 text-grey-darken-1">
                            {{ ticket.subject }}
                          </p>
                          <div class="d-flex align-center mt-1">
                            <v-chip :color="getTicketStatusColor(ticket.status)" size="x-small" class="mr-2">
                              {{ ticket.status }}
                            </v-chip>
                            <v-chip :color="getPriorityColor(ticket.priority)" size="x-small" class="mr-2">
                              {{ ticket.priority }}
                            </v-chip>
                            <span class="text-caption">{{ formatDate(ticket.createdAt) }}</span>
                          </div>
                        </div>
                        <div class="text-right">
                          <v-btn size="x-small" variant="text" icon>
                            <v-icon>mdi-chevron-right</v-icon>
                          </v-btn>
                        </div>
                      </div>
                    </v-card-text>
                  </v-card>
                </div>
              </v-window-item>

              <!-- Notes Tab -->
              <v-window-item value="notes" class="pa-4">
                <div class="notes-section">
                  <v-textarea
                    v-model="callNotes"
                    label="Call Notes"
                    placeholder="Add notes about this call..."
                    rows="4"
                    variant="outlined"
                    class="mb-3"
                  />
                  
                  <div class="d-flex align-center mb-4">
                    <v-btn 
                      color="primary" 
                      variant="flat" 
                      @click="saveNotes"
                      :loading="savingNotes"
                    >
                      Save Notes
                    </v-btn>
                    
                    <v-spacer />
                    
                    <v-select
                      v-model="callOutcome"
                      :items="callOutcomes"
                      label="Call Outcome"
                      variant="outlined"
                      density="compact"
                      style="max-width: 200px;"
                    />
                  </div>
                  
                  <!-- Previous Notes -->
                  <div v-if="previousNotes.length" class="previous-notes">
                    <h4 class="text-subtitle-1 font-weight-bold mb-2">Previous Call Notes</h4>
                    <div
                      v-for="note in previousNotes"
                      :key="note.id"
                      class="note-item mb-2 pa-3"
                    >
                      <div class="d-flex align-center mb-1">
                        <span class="text-body-2 font-weight-medium">{{ note.agent }}</span>
                        <v-spacer />
                        <span class="text-caption text-grey-darken-1">{{ formatDate(note.date) }}</span>
                      </div>
                      <p class="text-body-2">{{ note.content }}</p>
                      <v-chip v-if="note.outcome" size="x-small" class="mt-1">
                        {{ note.outcome }}
                      </v-chip>
                    </div>
                  </div>
                </div>
              </v-window-item>
            </v-window>
          </v-card-text>
        </div>
      </v-card>
    </v-dialog>

    <!-- Debug toggle button -->
    <v-btn 
      v-if="!debugMode"
      @click="debugMode = true" 
      color="warning"
      size="small"
      style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;"
    >
      Debug
    </v-btn>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useWebRTCStore } from '@/stores/webrtc'

const webrtcStore = useWebRTCStore()

// Debug mode
const debugMode = ref(false)

// Props
const props = defineProps({
  // Add any additional props if needed
})

const emit = defineEmits([
  'call-accepted',
  'call-declined',
  'call-ended'
])

// Reactive data
const callAccepted = ref(false)
const accepting = ref(false)
const isRinging = ref(true)
const isMuted = ref(false)
const isOnHold = ref(false)
const callStartTime = ref(null)
const callDuration = ref('00:00')
const activeTab = ref('details')
const callNotes = ref('')
const callOutcome = ref('')
const savingNotes = ref(false)

// Client data loading states
const loadingOrders = ref(false)
const loadingTickets = ref(false)
const clientOrders = ref([])
const clientTickets = ref([])
const previousNotes = ref([])

// Call outcomes
const callOutcomes = [
  'Resolved',
  'Follow-up Required', 
  'Escalated',
  'Information Provided',
  'Complaint Logged',
  'Payment Arranged',
  'Delivery Scheduled',
  'Other'
]



const incomingCallDialog = computed({
  get: () => webrtcStore.incomingCallDialog,
  set: (val) => webrtcStore.incomingCallDialog = val,
});

const incomingCall = computed(() => webrtcStore.incomingCall);



// Sample caller info (in real app, this would be fetched based on phone number)
const callerInfo = ref({
  name: 'John Doe',
  phone: '+1234567890', 
  email: 'john.doe@email.com',
  address: '123 Main St, City, State',
  avatar: null,
  type: 'Premium Customer',
  status: 'Active',
  customerSince: '2020-01-15',
  totalOrders: 24
})

// FIXED: Add watchers for debugging
watch(() => webrtcStore.incomingCallDialog, (newValue, oldValue) => {
  console.log('🔄 Store incomingCallDialog changed:', { from: oldValue, to: newValue })
  if (newValue) {
    nextTick(() => {
      console.log('✅ Dialog should be visible now')
    })
  }
}, { immediate: true })

watch(() => webrtcStore.incomingCall, (newValue, oldValue) => {
  console.log('🔄 Store incomingCall changed:', { from: oldValue, to: newValue })
}, { deep: true, immediate: true })

// Methods
const acceptCall = async () => {
  accepting.value = true
  
  try {
    // Answer the call using AfricasTalking client
    if (webrtcStore.afClient) {
      await webrtcStore.afClient.answer()
    }
    
    callAccepted.value = true
    callStartTime.value = new Date()
    isRinging.value = false
    
    // Start call timer
    startCallTimer()
    
    // Load client data
    await loadClientData()
    
    emit('call-accepted', {
      from: incomingCall.value.from,
      caller: callerInfo.value
    })
    
  } catch (error) {
    console.error('Failed to accept call:', error)
  } finally {
    accepting.value = false
  }
}

const declineCall = async () => {
  try {
    // Decline the call using AfricasTalking client
    if (webrtcStore.afClient) {
      await webrtcStore.afClient.hangup()
    }
    
    incomingCallDialog.value = false
    resetCallState()
    
    emit('call-declined', {
      from: incomingCall.value.from
    })
    
  } catch (error) {
    console.error('Failed to decline call:', error)
  }
}

const hangupCall = async () => {
  try {
    // End the call using AfricasTalking client
    if (webrtcStore.afClient) {
      await webrtcStore.afClient.hangup()
    }
    
    incomingCallDialog.value = false
    resetCallState()
    
    emit('call-ended', {
      from: incomingCall.value.from,
      duration: callDuration.value,
      notes: callNotes.value,
      outcome: callOutcome.value
    })
    
  } catch (error) {
    console.error('Failed to hangup call:', error)
  }
}

const toggleMute = async () => {
  try {
    if (webrtcStore.afClient) {
      if (isMuted.value) {
        await webrtcStore.afClient.unmute()
      } else {
        await webrtcStore.afClient.mute()
      }
      isMuted.value = !isMuted.value
    }
  } catch (error) {
    console.error('Failed to toggle mute:', error)
  }
}

const holdCall = async () => {
  try {
    if (webrtcStore.afClient) {
      if (isOnHold.value) {
        await webrtcStore.afClient.unhold()
      } else {
        await webrtcStore.afClient.hold()
      }
      isOnHold.value = !isOnHold.value
    }
  } catch (error) {
    console.error('Failed to toggle hold:', error)
  }
}

const loadClientData = async () => {
  const phoneNumber = incomingCall.value.from
  
  try {
    // Load client info based on phone number
    await loadClientInfo(phoneNumber)
    
    // Load orders and tickets in parallel
    await Promise.all([
      loadClientOrders(phoneNumber),
      loadClientTickets(phoneNumber),
      loadPreviousNotes(phoneNumber)
    ])
    
  } catch (error) {
    console.error('Failed to load client data:', error)
  }
}

const loadClientInfo = async (phoneNumber) => {
  // In real app, fetch from API
  // const response = await fetch(`/api/clients/by-phone/${phoneNumber}`)
  // const clientData = await response.json()
  // callerInfo.value = clientData
  
  // Mock implementation
  callerInfo.value.phone = phoneNumber
}

const loadClientOrders = async (phoneNumber) => {
  loadingOrders.value = true
  
  try {
    // In real app: const response = await fetch(`/api/clients/${clientId}/orders`)
    // Mock data
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    clientOrders.value = [
      {
        id: 1,
        orderNumber: 'ORD-2024-001',
        description: 'Package delivery to downtown office',
        status: 'In Transit',
        amount: '$149.99',
        date: '2024-01-15'
      },
      {
        id: 2,
        orderNumber: 'ORD-2024-002', 
        description: 'Express document courier',
        status: 'Delivered',
        amount: '$89.50',
        date: '2024-01-10'
      }
    ]
    
  } catch (error) {
    console.error('Failed to load orders:', error)
  } finally {
    loadingOrders.value = false
  }
}

const loadClientTickets = async (phoneNumber) => {
  loadingTickets.value = true
  
  try {
    // In real app: const response = await fetch(`/api/clients/${clientId}/tickets`)
    // Mock data
    await new Promise(resolve => setTimeout(resolve, 800))
    
    clientTickets.value = [
      {
        id: 1,
        ticketNumber: 'TKT-2024-001',
        subject: 'Delayed package delivery inquiry',
        status: 'Open',
        priority: 'High',
        createdAt: '2024-01-14'
      },
      {
        id: 2,
        ticketNumber: 'TKT-2024-002',
        subject: 'Request for delivery time change',
        status: 'Resolved',
        priority: 'Medium', 
        createdAt: '2024-01-08'
      }
    ]
    
  } catch (error) {
    console.error('Failed to load tickets:', error)
  } finally {
    loadingTickets.value = false
  }
}

const loadPreviousNotes = async (phoneNumber) => {
  try {
    // Mock data
    previousNotes.value = [
      {
        id: 1,
        agent: 'Sarah Johnson',
        date: '2024-01-10',
        content: 'Customer called regarding delayed package. Provided tracking information and expected delivery date.',
        outcome: 'Information Provided'
      },
      {
        id: 2,
        agent: 'Mike Wilson',
        date: '2024-01-05',
        content: 'Scheduled delivery for next business day. Customer confirmed availability.',
        outcome: 'Delivery Scheduled'
      }
    ]
  } catch (error) {
    console.error('Failed to load previous notes:', error)
  }
}

const saveNotes = async () => {
  if (!callNotes.value.trim()) return
  
  savingNotes.value = true
  
  try {
    // In real app: await fetch('/api/call-notes', { ... })
    await new Promise(resolve => setTimeout(resolve, 500))
    
    // Add to previous notes
    previousNotes.value.unshift({
      id: Date.now(),
      agent: 'Current Agent', // Get from auth
      date: new Date().toISOString().split('T')[0],
      content: callNotes.value,
      outcome: callOutcome.value
    })
    
    // Clear current notes
    callNotes.value = ''
    
  } catch (error) {
    console.error('Failed to save notes:', error)
  } finally {
    savingNotes.value = false
  }
}

const startCallTimer = () => {
  const timer = setInterval(() => {
    if (!callStartTime.value || !callAccepted.value) {
      clearInterval(timer)
      return
    }
    
    const elapsed = Math.floor((new Date() - callStartTime.value) / 1000)
    const minutes = Math.floor(elapsed / 60)
    const seconds = elapsed % 60
    callDuration.value = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
  }, 1000)
}

const resetCallState = () => {
  callAccepted.value = false
  accepting.value = false
  isRinging.value = true
  isMuted.value = false
  isOnHold.value = false
  callStartTime.value = null
  callDuration.value = '00:00'
  activeTab.value = 'details'
  callNotes.value = ''
  callOutcome.value = ''
  clientOrders.value = []
  clientTickets.value = []
  previousNotes.value = []
  loadingOrders.value = false
  loadingTickets.value = false
  savingNotes.value = false
}

// Utility functions
const formatPhoneNumber = (phone) => {
  // Format phone number for display
  if (!phone) return 'Unknown'
  return phone.replace(/(\+\d{1,3})(\d{3})(\d{3})(\d{4})/, '$1 ($2) $3-$4')
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short', 
    day: 'numeric'
  })
}

const getCallerTypeColor = (type) => {
  const colors = {
    'Premium Customer': 'gold',
    'Regular Customer': 'primary',
    'New Customer': 'success',
    'VIP': 'purple'
  }
  return colors[type] || 'primary'
}

const getStatusColor = (status) => {
  const colors = {
    'Active': 'success',
    'Inactive': 'warning', 
    'Suspended': 'error',
    'Pending': 'info'
  }
  return colors[status] || 'grey'
}

const getOrderStatusColor = (status) => {
  const colors = {
    'Delivered': 'success',
    'In Transit': 'info',
    'Processing': 'warning',
    'Cancelled': 'error',
    'Pending': 'grey'
  }
  return colors[status] || 'grey'
}

const getTicketStatusColor = (status) => {
  const colors = {
    'Open': 'error',
    'In Progress': 'warning',
    'Resolved': 'success',
    'Closed': 'grey'
  }
  return colors[status] || 'grey'
}

const getPriorityColor = (priority) => {
  const colors = {
    'High': 'error',
    'Medium': 'warning',
    'Low': 'success'
  }
  return colors[priority] || 'grey'
}

// Watch for incoming calls - FIXED: More explicit watching
watch(incomingCallDialog, (newValue) => {
  console.log('🔍 IncomingCallDialog watcher triggered:', newValue)
  if (newValue) {
    isRinging.value = true
    // Force update caller info with incoming call data
    if (incomingCall.value && incomingCall.value.from) {
      callerInfo.value.phone = incomingCall.value.from
    }
  } else {
    resetCallState()
  }
})

// FIXED: Force reactivity check on mount
onMounted(() => {
  console.log('🚀 Component mounted')
  console.log('📊 Initial store state:')
  console.log('  - incomingCallDialog:', webrtcStore.incomingCallDialog)
  console.log('  - incomingCall:', webrtcStore.incomingCall)
  console.log('  - afClient:', webrtcStore.afClient ? 'initialized' : 'not initialized')
  
  // Force reactivity update
  nextTick(() => {
    if (webrtcStore.incomingCallDialog) {
      console.log('🔄 Forcing dialog to show on mount')
      // This shouldn't be needed but helps debug
      // incomingCallDialog.value = true
    }
  })
})

onUnmounted(() => {
  console.log('🧹 Component unmounted')
  // Cleanup if needed
})
</script>

<style scoped>
.debug-overlay {
  position: fixed;
  top: 10px;
  left: 10px;
  background: rgba(0, 0, 0, 0.9);
  color: white;
  padding: 15px;
  border-radius: 8px;
  z-index: 10000;
  font-family: monospace;
  font-size: 12px;
  max-width: 400px;
  word-break: break-all;
}

.debug-overlay button {
  background: #ff4444;
  color: white;
  border: none;
  padding: 5px 10px;
  border-radius: 3px;
  margin-top: 10px;
  cursor: pointer;
}

.incoming-call-card {
  border-radius: 24px;
  overflow: hidden;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  min-height: 400px;
}

.incoming-call-card.ringing {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.02); }
  100% { transform: scale(1); }
}

.incoming-call-content {
  background: white;
  color: inherit;
  border-radius: 24px;
  margin: 4px;
}

.incoming-call-header {
  text-align: center;
  color: #333;
}

.call-avatar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  margin: 0 auto;
}

.call-action-btn {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.accept-btn {
  animation: bounce 2s infinite;
}

@keyframes bounce {
  0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
  40% { transform: translateY(-10px); }
  60% { transform: translateY(-5px); }
}

.decline-btn:hover {
  transform: scale(1.1);
}

.accept-btn:hover {
  transform: scale(1.1);
}

.call-status {
  display: flex;
  align-items: center;
  justify-content: center;
}

.ringing-icon {
  animation: ring 1s infinite;
}

@keyframes ring {
  0% { transform: rotate(0deg); }
  25% { transform: rotate(15deg); }
  50% { transform: rotate(0deg); }
  75% { transform: rotate(-15deg); }
  100% { transform: rotate(0deg); }
}

/* Client Details Styles */
.client-details-content {
  background: white;
  color: inherit;
}

.gradient-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 16px 24px;
}

.call-timer {
  background: rgba(255, 255, 255, 0.2);
  padding: 8px 12px;
  border-radius: 12px;
  backdrop-filter: blur(10px);
}

.recording-pulse {
  animation: pulse-dot 1.5s infinite;
}

@keyframes pulse-dot {
  0% { opacity: 1; }
  50% { opacity: 0.3; }
  100% { opacity: 1; }
}

.call-controls {
  display: flex;
  gap: 8px;
  align-items: center;
}

.call-controls .v-btn.active {
  background: rgba(255, 255, 255, 0.2) !important;
}

.client-tabs {
  background: rgba(103, 126, 234, 0.05);
  border-bottom: 1px solid rgba(103, 126, 234, 0.1);
}

.client-content {
  max-height: 500px;
  overflow-y: auto;
}

.info-group {
  background: rgba(103, 126, 234, 0.03);
  border-radius: 12px;
  padding: 16px;
}

.info-item {
  display: flex;
  align-items: center;
  margin-bottom: 8px;
  font-size: 14px;
}

.info-item:last-child {
  margin-bottom: 0;
}

.quick-actions .v-btn {
  margin-right: 8px;
  margin-bottom: 8px;
}

.order-card, .ticket-card {
  border-radius: 12px;
  transition: all 0.3s ease;
}

.order-card:hover, .ticket-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(103, 126, 234, 0.15);
}

.note-item {
  background: rgba(103, 126, 234, 0.05);
  border-radius: 12px;
  border-left: 4px solid #667eea;
}

/* Scrollbar Styling */
.client-content::-webkit-scrollbar {
  width: 6px;
}

.client-content::-webkit-scrollbar-track {
  background: rgba(103, 126, 234, 0.1);
  border-radius: 10px;
}

.client-content::-webkit-scrollbar-thumb {
  background: rgba(103, 126, 234, 0.3);
  border-radius: 10px;
}

.client-content::-webkit-scrollbar-thumb:hover {
  background: rgba(103, 126, 234, 0.5);
}

/* Responsive Design */
@media (max-width: 768px) {
  .incoming-call-card {
    margin: 8px;
    border-radius: 20px;
  }
  
  .call-action-btn {
    width: 70px;
    height: 70px;
  }
  
  .gradient-header {
    padding: 12px 16px;
  }
  
  .client-content {
    max-height: 400px;
  }
  
  .call-controls {
    flex-direction: column;
    gap: 4px;
  }
}

/* Dark Mode Support */
.v-theme--dark .incoming-call-content,
.v-theme--dark .client-details-content {
  background: #1e1e1e;
}

.v-theme--dark .info-group {
  background: rgba(255, 255, 255, 0.05);
}

.v-theme--dark .note-item {
  background: rgba(255, 255, 255, 0.05);
}

.v-theme--dark .client-tabs {
  background: rgba(255, 255, 255, 0.05);
  border-bottom-color: rgba(255, 255, 255, 0.1);
}

/* Loading States */
.orders-list, .tickets-list {
  min-height: 200px;
}

.order-card .v-card-text,
.ticket-card .v-card-text {
  padding: 12px 16px;
}

/* Status Indicators */
.v-chip {
  font-weight: 500;
  font-size: 11px;
}

/* Enhanced Animations */
.incoming-call-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.order-card, .ticket-card {
  border: 1px solid rgba(103, 126, 234, 0.1);
}

.order-card:hover, .ticket-card:hover {
  border-color: rgba(103, 126, 234, 0.3);
}

/* Tab Content Animation */
.v-window-item {
  transition: all 0.3s ease;
}

/* Action Button Styles */
.quick-actions .v-btn {
  border-color: rgba(103, 126, 234, 0.3);
  transition: all 0.3s ease;
}

.quick-actions .v-btn:hover {
  border-color: rgba(103, 126, 234, 0.6);
  background: rgba(103, 126, 234, 0.05);
}

/* Call Duration Display */
.call-timer .text-body-2 {
  font-family: 'Courier New', monospace;
  font-weight: bold;
}

/* Enhanced Focus States */
.v-btn:focus-visible {
  outline: 2px solid rgba(103, 126, 234, 0.5);
  outline-offset: 2px;
}

/* Accessibility Improvements */
.v-btn {
  cursor: pointer;
  transition: background-color 0.2s ease;       
}
.v-btn:hover {
  background-color: rgba(103, 126, 234, 0.1);
}
.v-btn:active {
  background-color: rgba(103, 126, 234, 0.2);           
}
.v-btn:focus {
  box-shadow: 0 0 0 3px rgba(103, 126, 234, 0.3);
}   
</style>