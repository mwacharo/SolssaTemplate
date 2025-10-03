<template>
  <div>
    <!-- Make New Call Dialog -->
    <v-dialog 
      v-model="isNewCallDialog" 
      max-width="500" 
      persistent
      transition="dialog-bottom-transition"
    >
      <v-card class="modern-card">
        <v-card-title class="gradient-header">
          <v-icon left color="white" size="24">mdi-phone-plus</v-icon>
          <span class="text-h5 font-weight-bold">Make New Call</span>
          <v-spacer />
          <v-btn icon @click="closeDialog" color="white">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        
        <v-card-text class="pa-6">
          <v-form ref="newCallFormRef" @submit.prevent="callClient">
            <!-- Phone Number Input -->
            <div class="mb-6">
              <v-text-field
                v-model="newCallForm.phone"
                label="Phone Number"
                placeholder="+1 (555) 123-4567"
                variant="outlined"
                prepend-inner-icon="mdi-phone"
                :rules="phoneRules"
                class="modern-input"
                hide-details="auto"
              />
            </div>

            <!-- Contact Search -->
            <div class="mb-6">
              <v-autocomplete
                v-model="newCallForm.contact"
                :items="contacts"
                item-title="name"
                item-value="phone"
                label="Or select from contacts"
                variant="outlined"
                prepend-inner-icon="mdi-account-search"
                clearable
                class="modern-input"
                @update:model-value="onContactSelect"
              >
                <template #item="{ props, item }">
                  <v-list-item v-bind="props">
                    <template #prepend>
                      <v-avatar size="40" color="primary">
                        <span class="text-white">{{ getInitials(item.raw.name) }}</span>
                      </v-avatar>
                    </template>
                    <v-list-item-title>{{ item.raw.name }}</v-list-item-title>
                    <v-list-item-subtitle>{{ item.raw.phone }}</v-list-item-subtitle>
                  </v-list-item>
                </template>
              </v-autocomplete>
            </div>


            <!-- hold mute handgup buttons  -->
            <div class="d-flex justify-center mb-6">
              <v-btn icon color="grey" variant="outlined" class="mx-2">
                <v-icon>mdi-microphone-off</v-icon>
              </v-btn>
              <v-btn icon color="red" variant="outlined" class="mx-2">
                <v-icon>mdi-phone-hangup</v-icon>
              </v-btn>
              <!-- <v-btn icon color="grey" variant="outlined" class="mx-2">
                <v-icon>mdi-hand-back-right</v-icon>
              </v-btn> -->
            </div>

            <!-- Call Options -->
            <v-expansion-panels class="mb-4" variant="accordion">
              <v-expansion-panel>
                <v-expansion-panel-title>
                  <v-icon left>mdi-cog</v-icon>
                  Advanced Options
                </v-expansion-panel-title>
                <v-expansion-panel-text>
                  <div class="pt-4 space-y-4">
                    <v-select
                      v-model="newCallForm.callerId"
                      :items="callerIds"
                      label="Caller ID"
                      variant="outlined"
                      prepend-inner-icon="mdi-card-account-phone"
                    />
                    
                    <v-checkbox
                      v-model="newCallForm.recordCall"
                      label="Record this call"
                      color="primary"
                    />
                    
                    <v-checkbox
                      v-model="newCallForm.autoAnswer"
                      label="Auto-answer when connected"
                      color="primary"
                    />
                  </div>
                </v-expansion-panel-text>
              </v-expansion-panel>
            </v-expansion-panels>

            <!-- Dialpad -->
            <div v-if="showDialpad" class="dialpad-container mb-4">
              <div class="dialpad-grid">
                <v-btn
                  v-for="number in dialpadNumbers"
                  :key="number.digit"
                  @click="addToPhone(number.digit)"
                  class="dialpad-btn"
                  size="large"
                  variant="outlined"
                >
                  <div class="text-center">
                    <div class="text-h6 font-weight-bold">{{ number.digit }}</div>
                    <div class="text-caption">{{ number.letters }}</div>
                  </div>
                </v-btn>
              </div>
            </div>

            <v-btn
              @click="showDialpad = !showDialpad"
              variant="text"
              color="primary"
              class="mb-4"
            >
              <v-icon left>{{ showDialpad ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
              {{ showDialpad ? 'Hide' : 'Show' }} Dialpad
            </v-btn>
          </v-form>
        </v-card-text>

        <v-card-actions class="pa-6 pt-0">
          <v-btn
            color="grey"
            variant="outlined"
            @click="closeDialog"
            class="mr-3"
          >
            Cancel
          </v-btn>
          <v-spacer />
          <v-btn
            color="primary"
            variant="elevated"
            @click="callClient"
            :disabled="!newCallForm.phone"
            :loading="makingCall"
            size="large"
            class="px-8"
          >
            <v-icon left>mdi-phone</v-icon>
            Call Now
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Call Agent Dialog -->
    <v-dialog 
      v-model="isCallAgentDialog" 
      max-width="600"
      transition="dialog-bottom-transition"
    >
      <v-card class="modern-card">
        <v-card-title class="gradient-header">
          <v-icon left color="white" size="24">mdi-account-voice</v-icon>
          <span class="text-h5 font-weight-bold">Call Agent</span>
          <v-spacer />
          <v-btn icon @click="closeDialog" color="white">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-card-text class="pa-6">
          <!-- Agent List -->
          <div class="mb-4">
            <v-text-field
              v-model="agentSearch"
              label="Search agents..."
              variant="outlined"
              prepend-inner-icon="mdi-magnify"
              clearable
              class="mb-4"
            />
          </div>

          <v-list class="agent-list">
            <v-list-item
              v-for="agent in filteredAgents"
              :key="agent.id"
              @click="selectAgent(agent)"
              :class="{ 'selected-agent': selectedAgent?.id === agent.id }"
              class="agent-item mb-2"
            >
              <template #prepend>
                <v-avatar size="48" :color="getAgentStatusColor(agent.status)">
                  <v-img v-if="agent.avatar" :src="agent.avatar" />
                  <span v-else class="text-white font-weight-bold">
                    {{ getInitials(agent.name) }}
                  </span>
                </v-avatar>
              </template>

              <v-list-item-title class="font-weight-medium">
                {{ agent.name }}
              </v-list-item-title>
              <v-list-item-subtitle>
                <div class="d-flex align-center">
                  <v-chip
                    :color="getAgentStatusColor(agent.status)"
                    size="x-small"
                    class="mr-2"
                  >
                    {{ agent.status }}
                  </v-chip>
                  <span>Ext: {{ agent.extension }}</span>
                </div>
              </v-list-item-subtitle>

              <template #append>
                <div class="text-center">
                  <div class="text-caption">Calls Today</div>
                  <div class="text-h6 font-weight-bold">{{ agent.callsToday }}</div>
                </div>
              </template>
            </v-list-item>
          </v-list>
        </v-card-text>

        <v-card-actions class="pa-6 pt-0">
          <v-btn color="grey" variant="outlined" @click="closeDialog">
            Cancel
          </v-btn>
          <v-spacer />
          <v-btn
            color="primary"
            variant="elevated"
            @click="callSelectedAgent"
            :disabled="!selectedAgent"
            :loading="makingCall"
            size="large"
            class="px-8"
          >
            <v-icon left>mdi-phone</v-icon>
            Call Agent
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Queue Status Dialog -->
    <v-dialog 
      v-model="isQueueDialog" 
      max-width="800"
      transition="dialog-bottom-transition"
    >
      <v-card class="modern-card">
        <v-card-title class="gradient-header">
          <v-icon left color="white" size="24">mdi-account-multiple-check</v-icon>
          <span class="text-h5 font-weight-bold">Queue Dashboard</span>
          <v-spacer />
          <v-btn icon @click="closeDialog" color="white">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-card-text class="pa-6">
          <!-- Queue Statistics -->
          <v-row class="mb-6">
            <v-col cols="12" md="3" v-for="stat in queueStats" :key="stat.title">
              <v-card class="stat-card" :color="stat.color" variant="tonal">
                <v-card-text class="text-center pa-4">
                  <v-icon :color="stat.color" size="40" class="mb-2">
                    {{ stat.icon }}
                  </v-icon>
                  <div class="text-h4 font-weight-bold" :class="`${stat.color}--text`">
                    {{ stat.value }}
                  </div>
                  <div class="text-body-2">{{ stat.title }}</div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <!-- Real-time Queue -->
          <v-card class="queue-card mb-4" variant="outlined">
            <v-card-title class="d-flex align-center">
              <v-icon left color="primary">mdi-clock-outline</v-icon>
              Current Queue
              <v-spacer />
              <v-chip color="primary" size="small">
                Live Updates
                <v-icon right size="16" class="ml-1 animate-pulse">mdi-circle</v-icon>
              </v-chip>
            </v-card-title>
            
            <v-card-text>
              <v-data-table
                :headers="queueHeaders"
                :items="queueItems"
                :items-per-page="5"
                class="queue-table"
                density="comfortable"
              >
                <template #item.waitTime="{ item }">
                  <v-chip :color="getWaitTimeColor(item.waitTime)" size="small">
                    {{ formatWaitTime(item.waitTime) }}
                  </v-chip>
                </template>
                
                <template #item.priority="{ item }">
                  <v-rating
                    :model-value="item.priority"
                    color="orange"
                    size="small"
                    readonly
                    density="compact"
                  />
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>

          <!-- Agent Status Grid -->
          <v-card variant="outlined">
            <v-card-title>
              <v-icon left color="success">mdi-account-group</v-icon>
              Agent Status
            </v-card-title>
            <v-card-text>
              <v-row>
                <v-col
                  cols="12" sm="6" md="4"
                  v-for="agent in agentStatusList"
                  :key="agent.id"
                >
                  <v-card class="agent-status-card" variant="outlined">
                    <v-card-text class="pa-3">
                      <div class="d-flex align-center">
                        <v-avatar size="32" :color="getAgentStatusColor(agent.status)" class="mr-3">
                          <span class="text-white text-caption">{{ getInitials(agent.name) }}</span>
                        </v-avatar>
                        <div class="flex-grow-1">
                          <div class="text-body-2 font-weight-medium">{{ agent.name }}</div>
                          <div class="text-caption text-grey">{{ agent.status }}</div>
                        </div>
                        <div v-if="agent.currentCall" class="text-right">
                          <div class="text-caption">On Call</div>
                          <div class="text-body-2 font-weight-bold">{{ agent.callDuration }}</div>
                        </div>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-card-text>

        <v-card-actions class="pa-6 pt-0">
          <v-btn
            color="primary"
            variant="outlined"
            @click="refreshQueueData"
            :loading="loadingQueue"
          >
            <v-icon left>mdi-refresh</v-icon>
            Refresh
          </v-btn>
          <v-spacer />
          <v-btn color="primary" variant="elevated" @click="closeDialog">
            Close
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Transfer Call Dialog -->
    <v-dialog 
      v-model="isTransferDialog" 
      max-width="500"
      transition="dialog-bottom-transition"
    >
      <v-card class="modern-card">
        <v-card-title class="gradient-header">
          <v-icon left color="white" size="24">mdi-phone-forward</v-icon>
          <span class="text-h5 font-weight-bold">Transfer Call</span>
          <v-spacer />
          <v-btn icon @click="closeDialog" color="white">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-card-text class="pa-6">
          <v-tabs v-model="transferTab" class="mb-4">
            <v-tab value="number">Phone Number</v-tab>
            <v-tab value="agent">Agent</v-tab>
            <v-tab value="queue">Queue</v-tab>
          </v-tabs>

          <v-window v-model="transferTab">
            <v-window-item value="number">
              <v-text-field
                v-model="transferForm.phone"
                label="Transfer to Phone Number"
                variant="outlined"
                prepend-inner-icon="mdi-phone"
                :rules="phoneRules"
              />
            </v-window-item>

            <v-window-item value="agent">
              <v-select
                v-model="transferForm.agent"
                :items="availableAgents"
                item-title="name"
                item-value="id"
                label="Select Agent"
                variant="outlined"
                prepend-inner-icon="mdi-account"
              >
                <template #item="{ props, item }">
                  <v-list-item v-bind="props">
                    <template #prepend>
                      <v-avatar size="32" :color="getAgentStatusColor(item.raw.status)">
                        <span class="text-white">{{ getInitials(item.raw.name) }}</span>
                      </v-avatar>
                    </template>
                    <v-list-item-title>{{ item.raw.name }}</v-list-item-title>
                    <v-list-item-subtitle>{{ item.raw.status }} - Ext: {{ item.raw.extension }}</v-list-item-subtitle>
                  </v-list-item>
                </template>
              </v-select>
            </v-window-item>

            <v-window-item value="queue">
              <v-select
                v-model="transferForm.queue"
                :items="availableQueues"
                item-title="name"
                item-value="id"
                label="Select Queue"
                variant="outlined"
                prepend-inner-icon="mdi-account-multiple"
              />
            </v-window-item>
          </v-window>

          <v-checkbox
            v-model="transferForm.warmTransfer"
            label="Warm transfer (stay on call until connected)"
            color="primary"
            class="mt-4"
          />
        </v-card-text>

        <v-card-actions class="pa-6 pt-0">
          <v-btn color="grey" variant="outlined" @click="closeDialog">
            Cancel
          </v-btn>
          <v-spacer />
          <v-btn
            color="primary"
            variant="elevated"
            @click="executeTransfer"
            :loading="transferring"
            size="large"
            class="px-8"
          >
            <v-icon left>mdi-phone-forward</v-icon>
            Transfer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useCallCenterStore } from '@/stores/callCenter'
import { notify } from '@/utils/toast'

const callCenterStore = useCallCenterStore()

// Props
const props = defineProps({
  modelValue: String,
  callData: Object
})

const emit = defineEmits([
  'update:modelValue',
  'call-made',
  'call-transferred',
  'agent-called'
])

// Computed dialog states
const isNewCallDialog = computed({
  get: () => props.modelValue === 'newCall',
  set: v => emit('update:modelValue', v ? 'newCall' : null)
})

const isCallAgentDialog = computed({
  get: () => props.modelValue === 'callAgent',
  set: v => emit('update:modelValue', v ? 'callAgent' : null)
})

const isQueueDialog = computed({
  get: () => props.modelValue === 'queue',
  set: v => emit('update:modelValue', v ? 'queue' : null)
})

const isTransferDialog = computed({
  get: () => props.modelValue === 'transfer',
  set: v => emit('update:modelValue', v ? 'transfer' : null)
})

// Form data
const newCallForm = ref({
  phone: '',
  contact: null,
  callerId: '',
  recordCall: true,
  autoAnswer: false
})

const transferForm = ref({
  phone: '',
  agent: null,
  queue: null,
  warmTransfer: true
})

// States
const makingCall = ref(false)
const transferring = ref(false)
const loadingQueue = ref(false)
const showDialpad = ref(false)
const agentSearch = ref('')
const selectedAgent = ref(null)
const transferTab = ref('number')

// Validation rules
// const phoneRules = [
//   v => !!v || 'Phone number is required',
//   v => /^[\+]?[1-9][\d]{0,15}$/.test(v) || 'Enter a valid phone number'
// ]

const phoneRules = [
    v => !!v || 'Phone number is required'
]

// Dialpad data
const dialpadNumbers = [
  { digit: '1', letters: '' },
  { digit: '2', letters: 'ABC' },
  { digit: '3', letters: 'DEF' },
  { digit: '4', letters: 'GHI' },
  { digit: '5', letters: 'JKL' },
  { digit: '6', letters: 'MNO' },
  { digit: '7', letters: 'PQRS' },
  { digit: '8', letters: 'TUV' },
  { digit: '9', letters: 'WXYZ' },
  { digit: '*', letters: '' },
  { digit: '0', letters: '+' },
  { digit: '#', letters: '' }
]

// Sample data
const contacts = ref([
  { name: 'John Doe', phone: '+1234567890', email: 'john@example.com' },
  { name: 'Jane Smith', phone: '+1234567891', email: 'jane@example.com' },
  { name: 'Mike Johnson', phone: '+1234567892', email: 'mike@example.com' }
])

const callerIds = ref([
  '+1 (555) 000-0001',
  '+1 (555) 000-0002',
  '+1 (555) 000-0003'
])

const agents = ref([
  { id: 1, name: 'Alice Johnson', extension: '101', status: 'available', callsToday: 12, avatar: null },
  { id: 2, name: 'Bob Smith', extension: '102', status: 'busy', callsToday: 8, avatar: null },
  { id: 3, name: 'Carol Davis', extension: '103', status: 'available', callsToday: 15, avatar: null },
  { id: 4, name: 'David Wilson', extension: '104', status: 'away', callsToday: 5, avatar: null }
])

const availableQueues = ref([
  { id: 1, name: 'Sales Queue', waitingCount: 3 },
  { id: 2, name: 'Support Queue', waitingCount: 7 },
  { id: 3, name: 'Billing Queue', waitingCount: 2 }
])

const queueStats = ref([
  { title: 'Waiting', value: 12, color: 'warning', icon: 'mdi-clock-outline' },
  { title: 'In Progress', value: 8, color: 'info', icon: 'mdi-phone-in-talk' },
  { title: 'Completed', value: 45, color: 'success', icon: 'mdi-check-circle' },
  { title: 'Avg Wait', value: '2:30', color: 'primary', icon: 'mdi-timer-outline' }
])

const queueHeaders = [
  { title: 'Caller', value: 'caller', sortable: false },
  { title: 'Phone', value: 'phone', sortable: false },
  { title: 'Wait Time', value: 'waitTime', sortable: true },
  { title: 'Priority', value: 'priority', sortable: true },
  { title: 'Queue', value: 'queue', sortable: false }
]

const queueItems = ref([
  { caller: 'John Doe', phone: '+1234567890', waitTime: 45, priority: 3, queue: 'Sales' },
  { caller: 'Jane Smith', phone: '+1234567891', waitTime: 120, priority: 5, queue: 'Support' },
  { caller: 'Mike Wilson', phone: '+1234567892', waitTime: 30, priority: 2, queue: 'Billing' }
])

const agentStatusList = ref([
  { id: 1, name: 'Alice Johnson', status: 'available', currentCall: null },
  { id: 2, name: 'Bob Smith', status: 'busy', currentCall: true, callDuration: '05:23' },
  { id: 3, name: 'Carol Davis', status: 'available', currentCall: null },
  { id: 4, name: 'David Wilson', status: 'away', currentCall: null }
])

// Computed properties
const filteredAgents = computed(() => {
  if (!agentSearch.value) return agents.value
  return agents.value.filter(agent => 
    agent.name.toLowerCase().includes(agentSearch.value.toLowerCase()) ||
    agent.extension.includes(agentSearch.value)
  )
})

const availableAgents = computed(() => {
  return agents.value.filter(agent => agent.status === 'available')
})

// Methods
const closeDialog = () => {
  emit('update:modelValue', null)
  resetForms()
}

const resetForms = () => {
  newCallForm.value = {
    phone: '0741821113',
    contact: null,
    callerId: '',
    recordCall: true,
    autoAnswer: false
  }
  transferForm.value = {
    phone: '',
    agent: null,
    queue: null,
    warmTransfer: true
  }
  selectedAgent.value = null
  showDialpad.value = false
  transferTab.value = 'number'
}

const addToPhone = (digit) => {
  newCallForm.value.phone += digit
}

const onContactSelect = (phone) => {
  if (phone) {
    newCallForm.value.phone = phone
  }
}

const makeCall = async () => {
  if (!newCallForm.value.phone) return
  
  makingCall.value = true
  try {
    await callCenterStore.makeCall(newCallForm.value.phone, {
      callerId: newCallForm.value.callerId,
      record: newCallForm.value.recordCall,
      autoAnswer: newCallForm.value.autoAnswer
    })
    
    notify.success(`Calling ${newCallForm.value.phone}`)
    emit('call-made', newCallForm.value)
    closeDialog()
  } catch (error) {
    notify.error('Failed to make call')
  } finally {
    makingCall.value = false
  }
}


const callClient = async () => {
  if (!newCallForm.value.phone) return
  
  makingCall.value = true
  try {
    await callCenterStore.callClient(newCallForm.value.phone)
    notify.success(`Calling ${newCallForm.value.phone}`)
    emit('call-made', newCallForm.value)
    closeDialog()
  } catch (error) {
    notify.error('Failed to make call')
  } finally {
    makingCall.value = false
  }
}

const selectAgent = (agent) => {
  selectedAgent.value = agent
}

const callSelectedAgent = async () => {
  if (!selectedAgent.value) return
  
  makingCall.value = true
  try {
    await callCenterStore.makeCall(selectedAgent.value.extension)
    notify.success(`Calling ${selectedAgent.value.name}`)
    emit('agent-called', selectedAgent.value)
    closeDialog()
  } catch (error) {
    notify.error('Failed to call agent')
  } finally {
    makingCall.value = false
  }
}

const executeTransfer = async () => {
  transferring.value = true
  try {
    let target = ''
    if (transferTab.value === 'number') {
      target = transferForm.value.phone
    } else if (transferTab.value === 'agent') {
      const agent = agents.value.find(a => a.id === transferForm.value.agent)
      target = agent?.extension || ''
    } else if (transferTab.value === 'queue') {
      target = `queue:${transferForm.value.queue}`
    }
    
    await callCenterStore.transferCall(target)
    notify.success(`Call transferred to ${target}`)
    emit('call-transferred', { target, warmTransfer: transferForm.value.warmTransfer })
    closeDialog()
  } catch (error) {
    notify.error('Failed to transfer call')
  } finally {
    transferring.value = false
  }
}

const refreshQueueData = async () => {
  loadingQueue.value = true
  try {
    await callCenterStore.fetchQueueStatus()
    notify.success('Queue data refreshed')
  } catch (error) {
    notify.error('Failed to refresh queue data')
  } finally {
    loadingQueue.value = false
  }
}

// Utility functions
const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getAgentStatusColor = (status) => {
  const colors = {
    available: 'success',
    busy: 'error',
    away: 'warning',
    offline: 'grey'
  }
  return colors[status] || 'grey'
}

const getWaitTimeColor = (seconds) => {
  if (seconds < 30) return 'success'
  if (seconds < 120) return 'warning'
  return 'error'
}

const formatWaitTime = (seconds) => {
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins}:${secs.toString().padStart(2, '0')}`
}

onMounted(() => {
  // Initialize data
  callCenterStore.fetchAgentList()
  callCenterStore.fetchQueueStatus()
})
</script>

<style scoped>
.modern-card {
  border-radius: 24px;
  overflow: hidden;
  backdrop-filter: blur(20px);
  background: rgba(255, 255, 255, 0.95);
}

.gradient-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 20px 24px;
}

.modern-input :deep(.v-field) {
  border-radius: 16px;
  background: rgba(103, 126, 234, 0.05);
}

.dialpad-container {
  background: rgba(103, 126, 234, 0.05);
  border-radius: 20px;
  padding: 20px;
}

.dialpad-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.dialpad-btn {
  aspect-ratio: 1;
  border-radius: 16px !important;
  border: 2px solid rgba(103, 126, 234, 0.2) !important;
}

.dialpad-btn:hover {
  background: rgba(103, 126, 234, 0.1) !important;
  border-color: rgba(103, 126, 234, 0.4) !important;
}

.agent-list {
  max-height: 400px;
  overflow-y: auto;
}

.agent-item {
  border-radius: 16px;
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.agent-item:hover {
  background: rgba(103, 126, 234, 0.05);
  border-color: rgba(103, 126, 234, 0.2);
}

.selected-agent {
  background: rgba(103, 126, 234, 0.1) !important;
  border-color: rgba(103, 126, 234, 0.4) !important;
}

.stat-card {
  border-radius: 20px;
  transition: transform 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-4px);
}

.queue-card {
  border-radius: 20px;
  background: rgba(103, 126, 234, 0.02);
}

.queue-table :deep(.v-data-table__wrapper) {
  border-radius: 16px;
}

.agent-status-card {
  border-radius: 16px;
  transition: all 0.3s ease;
}

.agent-status-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(103, 126, 234, 0.15);
}

/* Custom scrollbar */
.agent-list::-webkit-scrollbar {
  width: 6px;
}

.agent-list::-webkit-scrollbar-track {
  background: rgba(103, 126, 234, 0.1);
  border-radius: 10px;
}

.agent-list::-webkit-scrollbar-thumb {
  background: rgba(103, 126, 234, 0.3);
  border-radius: 10px;
}

.agent-list::-webkit-scrollbar-thumb:hover {
  background: rgba(103, 126, 234, 0.5);
}

/* Animations */
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Responsive design */
@media (max-width: 768px) {
  .dialpad-grid {
    gap: 8px;
  }
  
  .gradient-header {
    padding: 16px 20px;
  }
  
  .modern-card {
    margin: 16px;
    border-radius: 20px;
  }
}

/* Dark mode support */
.v-theme--dark .modern-card {
  background: rgba(30, 30, 30, 0.95);
}

.v-theme--dark .modern-input :deep(.v-field) {
  background: rgba(255, 255, 255, 0.05);
}

.v-theme--dark .dialpad-container {
  background: rgba(255, 255, 255, 0.05);
}

.v-theme--dark .queue-card {
  background: rgba(255, 255, 255, 0.02);
}

.v-theme--dark .gradient-header {
  background: linear-gradient(135deg, #1e1e1e 0%, #2c2c2c 100%);
}



.v-theme--dark .stat-card {
  background: rgba(255, 255, 255, 0.1);
}   


.v-theme--dark .agent-status-card {
  background: rgba(255, 255, 255, 0.05);
}
</style>
