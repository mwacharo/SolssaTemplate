<template>
    <div>
        <!-- Details Dialog -->
        <v-dialog v-model="dialogType" value="details" max-width="600">
            <v-card>
                <v-card-title>
                    Call Details
                    <v-spacer />
                    <v-btn icon @click="closeDialog">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text v-if="callData">
                    <div><strong>Caller:</strong> {{ callData.callerNumber }}</div>
                    <div><strong>Destination:</strong> {{ callData.destinationNumber }}</div>
                    <div><strong>Date:</strong> {{ formatDate(callData.startTime) }}</div>
                    <div><strong>Time:</strong> {{ formatTime(callData.startTime) }}</div>
                    <div><strong>Duration:</strong> {{ formatDuration(callData.duration) }}</div>
                    <div v-if="callData.recordingUrl" class="mt-4">
                        <audio controls :src="callData.recordingUrl" style="width:100%"></audio>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn color="primary" @click="closeDialog">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Add to Contacts Dialog -->
        <v-dialog v-model="dialogType" value="contact" max-width="400">
            <v-card>
                <v-card-title>
                    Add to Contacts
                    <v-spacer />
                    <v-btn icon @click="closeDialog">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    <v-form ref="contactForm" @submit.prevent="saveContact">
                        <v-text-field
                            v-model="contactForm.name"
                            label="Name"
                            required
                        />
                        <v-text-field
                            v-model="contactForm.phone"
                            label="Phone"
                            required
                            :readonly="!!callData?.callerNumber"
                        />
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn color="primary" @click="saveContact">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Send SMS Dialog -->
        <v-dialog v-model="isSmsDialog" max-width="400">
            <v-card>
                <v-card-title>
                    Send SMS
                    <v-spacer />
                    <v-btn icon @click="closeDialog">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    <v-form ref="smsForm" @submit.prevent="sendSms">
                        <v-text-field
                            v-model="smsForm.phone"
                            label="To"
                            required
                            :readonly="!!callData?.callerNumber"
                        />
                        <v-textarea
                            v-model="smsForm.message"
                            label="Message"
                            rows="3"
                            required
                        />
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn color="primary" @click="sendSms">Send</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Transfer Call Dialog -->
        <v-dialog v-model="isTransferDialog" max-width="400">
            <v-card>
                <v-card-title>
                    Transfer Call
                    <v-spacer />
                    <v-btn icon @click="closeDialog">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    <v-form ref="transferForm" @submit.prevent="transferCall">
                        <v-text-field
                            v-model="transferForm.phone"
                            label="Transfer To"
                            required
                        />
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn color="primary" @click="transferCall">Transfer</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Make Call Dialog -->
        <v-dialog v-model="isMakeCallDialog" max-width="400">
            <v-card>
                <v-card-title>
                    Make Call
                    <v-spacer />
                    <v-btn icon @click="closeDialog">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text>
                    <v-form ref="makeCallForm" @submit.prevent="makeCall">
                        <v-text-field
                            v-model="makeCallForm.phone"
                            label="Phone Number"
                            required
                        />
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn color="primary" @click="makeCall">Call</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useCallCenterStore } from '@/stores/callCenter'
import { notify } from '@/utils/toast'

const props = defineProps({
    modelValue: String,
    callData: Object
})
const emit = defineEmits([
    'update:modelValue',
    'call-made',
    'call-transferred',
    'contact-saved'
])

const dialogType = computed({
    get: () => props.modelValue,
    set: v => emit('update:modelValue', v)
})

const isSmsDialog = computed({
    get: () => dialogType.value === 'sms',
    set: v => emit('update:modelValue', v ? 'sms' : null)
})

const isTransferDialog = computed({
    get: () => dialogType.value === 'transfer',
    set: v => emit('update:modelValue', v ? 'transfer' : null)
})

const isMakeCallDialog = computed({
    get: () => dialogType.value === 'makeCall',
    set: v => emit('update:modelValue', v ? 'makeCall' : null)
})

const callData = computed(() => props.callData || {})

const contactForm = ref({
    name: '',
    phone: ''
})
const smsForm = ref({
    phone: '',
    message: ''
})
const transferForm = ref({
    phone: ''
})
const makeCallForm = ref({
    phone: ''
})

watch(
    () => props.callData,
    (val) => {
        if (dialogType.value === 'contact' && val) {
            contactForm.value.phone = val.callerNumber || ''
            contactForm.value.name = ''
        }
        if (dialogType.value === 'sms' && val) {
            smsForm.value.phone = val.callerNumber || ''
            smsForm.value.message = ''
        }
        if (dialogType.value === 'transfer' && val) {
            transferForm.value.phone = ''
        }
    },
    { immediate: true }
)

function closeDialog() {
    emit('update:modelValue', null)
}

function saveContact() {
    // Simulate save
    notify('Contact saved', 'success')
    emit('contact-saved')
    closeDialog()
}

function sendSms() {
    // Simulate SMS send
    notify(`SMS sent to ${smsForm.value.phone}`, 'success')
    closeDialog()
}

function transferCall() {
    // Simulate transfer
    notify(`Call transferred to ${transferForm.value.phone}`, 'success')
    emit('call-transferred')
    closeDialog()
}

function makeCall() {
    const callCenterStore = useCallCenterStore()
    callCenterStore.makeCall(makeCallForm.value.phone)
    notify(`Calling ${makeCallForm.value.phone}`, 'info')
    emit('call-made')
    closeDialog()
}

function formatDate(date) {
    if (!date) return ''
    return new Date(date).toLocaleDateString()
}
function formatTime(date) {
    if (!date) return ''
    return new Date(date).toLocaleTimeString()
}
function formatDuration(sec) {
    if (!sec) return '0s'
    const m = Math.floor(sec / 60)
    const s = sec % 60
    return m ? `${m}m ${s}s` : `${s}s`
}
</script>