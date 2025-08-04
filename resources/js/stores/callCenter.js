import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import { useWebRTCStore } from './webrtc' // Import the WebRTC store
import { notify } from '@/utils/toast'

export const useCallCenterStore = defineStore('callCenter', () => {
    // Get WebRTC store instance
    const webrtcStore = useWebRTCStore()
    
    // State
    const makingCall = ref(false)
    const lastCallNumber = ref(null)
    const callError = ref(null)
    const activeCall = ref(null)
    const callHistory = ref([])
    const isCallActive = ref(false)
    const callStartTime = ref(null)
    const callDuration = ref(0)
    const isOnHold = ref(false)
    const isMuted = ref(false)
    const volume = ref(80)
    const isCalling = ref(false)
    const selectedAgent = ref(null)
    const phone_number = ref('')
    
    // Dialog states
    const dialogType = ref(null)
    const callData = ref(null)
    const isSmsDialog = ref(false)
    const contactForm = ref({
        name: '',
        phone: '',
        email: '',
        company: ''
    })

    // Queue and agent states
    const queueStatus = ref({
        totalWaiting: 0,
        averageWaitTime: 0,
        agentsAvailable: 0,
        agentsOnCall: 0
    })
    
    const agentStatus = ref('available') // available, busy, away, offline
    const agentList = ref([])

    // Computed properties that reference WebRTC store
    const afClient = computed(() => webrtcStore.afClient)
    const incomingCall = computed(() => webrtcStore.incomingCall)
    const incomingCallDialog = computed(() => webrtcStore.incomingCallDialog)
    const connection_active = computed(() => webrtcStore.connection_active)

    // Local computed properties
    const isDialogOpen = computed(() => dialogType.value !== null)
    const formattedCallDuration = computed(() => {
        const minutes = Math.floor(callDuration.value / 60)
        const seconds = callDuration.value % 60
        return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
    })

    // Utility function to log events
    function logEvent(message) {
        console.log(`[Call Center]: ${message}`)
    }

    // Initialize AfricasTalking client (delegate to WebRTC store)
    function initializeAfClient(client) {
        webrtcStore.initializeAfClient(client)
    }

    // Call client using AfricasTalking
    function callClient(phone) {
        try {
            console.log(`Calling ${phone} from System...`)
            
            if (!afClient.value) {
                console.error('AfricasTalking client is not initialized.')
                notify.error('AfricasTalking client is not initialized.')
                return
            }

            afClient.value.call(phone)
            console.log("Call initiated successfully.")
            notify.success("Call started.")
            isCalling.value = true
            activeCall.value = {
                id: Date.now(),
                phoneNumber: phone,
                direction: 'outbound',
                status: 'connecting',
                startTime: new Date()
            }
            
            // Register call-specific event listeners to track progress
            afClient.value.on('afClienting', () => {
                logEvent("afClient is in progress (calling)...")
            })

            afClient.value.on('callaccepted', () => {
                console.log("callaccepted")
                notify.success("Call accepted!")
                logEvent("Call accepted (bridged between caller and callee).")
                isCallActive.value = true
                callStartTime.value = Date.now()
                startCallTimer()
            })

            afClient.value.on('hangup', (hangupCause) => {
                logEvent(`Call hung up (${hangupCause.code} - ${hangupCause.reason}).`)
                notify.error(`Call ended: ${hangupCause.reason}`)
                isCalling.value = false
                resetCallState()
            })

            afClient.value.on('mute', () => {
                logEvent("Call muted.")
                notify.info("Call muted.")
                isMuted.value = true
            })

            afClient.value.on('unmute', () => {
                logEvent("Call unmuted.")
                notify.info("Call unmuted.")
                isMuted.value = false
            })

            afClient.value.on('hold', () => {
                logEvent("Call on hold.")
                notify.info("Call on hold.")
                isOnHold.value = true
            })

            afClient.value.on('unhold', () => {
                logEvent("Call resumed from hold.")
                notify.info("Call resumed from hold.")
                isOnHold.value = false
            })

        } catch (error) {
            console.error("Call initiation error:", error)
            notify.error("Call failed: " + error.message)
        }
    }

    // Answer the call
    function answerCall() {
        if (incomingCall.value && afClient.value) {
            afClient.value.answer()
            isCallActive.value = true
            callStartTime.value = Date.now()
            startCallTimer()
        }
    }

    // Handle calling an agent
    function handleCall(agent) {
        isCalling.value = true
        selectedAgent.value = agent
        console.log('Selected agent:', agent)

        phone_number.value = agent.phone_number
        console.log('Agent phone number:', phone_number.value)

        if (!afClient.value) {
            console.error('AfricasTalking client is not initialized.')
            notify.error('AfricasTalking client is not initialized.')
            return
        }

        try {
            console.log('Attempting to call agent...')
            afClient.value.call(phone_number.value)
            console.log('Call initiated successfully.')
            notify.success('Call initiated successfully.')
            
            activeCall.value = {
                id: Date.now(),
                phoneNumber: phone_number.value,
                direction: 'outbound',
                status: 'connecting',
                startTime: new Date(),
                agent: agent
            }
        } catch (error) {
            console.error('Error while calling agent:', error)
            notify.error('Failed to call agent: ' + error.message)
        }
    }

    // Hangup the call
    function hangupCall() {
        if (afClient.value) {
            afClient.value.hangup()
            console.log('call ended')
            notify.error("Call ended.")
            // Note: incomingCallDialog should be managed by WebRTC store
            webrtcStore.setIncomingCallDialog(false)
            isCalling.value = false
            resetCallState()
        }
    }

    // Handle mute/unmute
    function handleMute() {
        if (isCalling.value && afClient.value) {
            isMuted.value = !isMuted.value

            if (isMuted.value) {
                afClient.value.muteAudio()
            } else {
                afClient.value.unmuteAudio()
            }

            console.log('Client mute property:', afClient.value.isAudioMuted)
            console.log('Our internal state:', isMuted.value)
        }
    }

    // Make call function (API-based)
    async function makeCall(phoneNumber, options = {}) {
        try {
            makingCall.value = true
            callError.value = null
            lastCallNumber.value = phoneNumber
            
            // If AfricasTalking client is available, use it
            if (afClient.value) {
                callClient(phoneNumber)
                return { success: true, callId: Date.now() }
            }
            
            // Otherwise, use API call
            const response = await axios.post('/api/v1/call-center/make-call', {
                phoneNumber,
                ...options
            })
            
            // Simulate call initiation
            activeCall.value = {
                id: response.data.callId || Date.now(),
                phoneNumber,
                direction: 'outbound',
                status: 'connecting',
                startTime: new Date()
            }
            
            isCallActive.value = true
            callStartTime.value = Date.now()
            startCallTimer()
            
            return response.data
        } catch (error) {
            callError.value = error?.response?.data?.message || 'Failed to initiate call'
            throw error
        } finally {
            makingCall.value = false
        }
    }

    // End call
    async function endCall() {
        try {
            if (afClient.value && isCalling.value) {
                hangupCall()
                return
            }

            if (activeCall.value) {
                await axios.post(`/api/v1/call-center/end/${activeCall.value.id}`)
                
                // Add to call history
                callHistory.value.unshift({
                    ...activeCall.value,
                    endTime: new Date(),
                    duration: callDuration.value,
                    status: 'completed'
                })
            }
            
            resetCallState()
        } catch (error) {
            callError.value = 'Failed to end call'
            resetCallState() // Reset anyway
        }
    }

    // Reject call
    async function rejectCall(call) {
        try {
            if (afClient.value) {
                hangupCall()
                return
            }

            await axios.post(`/api/v1/call-center/reject/${call.id}`)
            // Note: incomingCall should be managed by WebRTC store
            webrtcStore.setIncomingCall(null)
        } catch (error) {
            callError.value = 'Failed to reject call'
        }
    }

    // Hold call
    async function holdCall() {
        try {
            if (afClient.value && isCalling.value) {
                // Handle hold with AfricasTalking client
                if (isOnHold.value) {
                    afClient.value.unhold?.()
                } else {
                    afClient.value.hold?.()
                }
                return
            }

            isOnHold.value = !isOnHold.value
            await axios.post(`/api/v1/call-center/hold/${activeCall.value.id}`, {
                hold: isOnHold.value
            })
        } catch (error) {
            isOnHold.value = !isOnHold.value // Revert
            callError.value = 'Failed to toggle hold'
        }
    }

    // Mute call
    async function muteCall() {
        try {
            if (afClient.value && isCalling.value) {
                handleMute()
                return
            }

            isMuted.value = !isMuted.value
            await axios.post(`/api/v1/call-center/mute/${activeCall.value.id}`, {
                mute: isMuted.value
            })
        } catch (error) {
            isMuted.value = !isMuted.value // Revert
            callError.value = 'Failed to toggle mute'
        }
    }

    // Transfer call
    async function transferCall(targetNumber) {
        try {
            await axios.post(`/api/v1/call-center/transfer/${activeCall.value.id}`, {
                targetNumber
            })
            
            // Add to history and reset
            callHistory.value.unshift({
                ...activeCall.value,
                endTime: new Date(),
                duration: callDuration.value,
                status: 'transferred',
                transferredTo: targetNumber
            })
            
            resetCallState()
        } catch (error) {
            callError.value = 'Failed to transfer call'
            throw error
        }
    }

    // Start call timer
    function startCallTimer() {
        const timer = setInterval(() => {
            if (!isCallActive.value) {
                clearInterval(timer)
                return
            }
            callDuration.value = Math.floor((Date.now() - callStartTime.value) / 1000)
        }, 1000)
    }

    // Reset call state
    function resetCallState() {
        activeCall.value = null
        isCallActive.value = false
        callStartTime.value = null
        callDuration.value = 0
        isOnHold.value = false
        isMuted.value = false
        isCalling.value = false
        selectedAgent.value = null
    }

    // Dialog management
    function openNewCallDialog() {
        dialogType.value = 'newCall'
        callData.value = null
    }

    function openCallAgentDialog() {
        dialogType.value = 'callAgent'
        callData.value = null
    }

    function openQueueDialog() {
        dialogType.value = 'queue'
        callData.value = null
    }

    function openTransferDialog() {
        dialogType.value = 'transfer'
    }

    function openContactDialog(call = null) {
        dialogType.value = 'contact'
        callData.value = call
        if (call) {
            contactForm.value.phone = call.phoneNumber || call.callerNumber || ''
        }
    }

    function openSmsDialog(call = null) {
        dialogType.value = 'sms'
        isSmsDialog.value = true
        callData.value = call
    }

    function closeDialog() {
        dialogType.value = null
        isSmsDialog.value = false
        callData.value = null
        contactForm.value = {
            name: '',
            phone: '',
            email: '',
            company: ''
        }
    }

    // Agent status management
    async function updateAgentStatus(status) {
        try {
            await axios.post('/api/v1/call-center/agent/status', { status })
            agentStatus.value = status
        } catch (error) {
            callError.value = 'Failed to update status'
        }
    }

    // Fetch queue status
    async function fetchQueueStatus() {
        try {
            const response = await axios.get('/api/v1/call-center/queue/status')
            queueStatus.value = response.data
        } catch (error) {
            console.error('Failed to fetch queue status:', error)
        }
    }

    // Fetch agent list
    async function fetchAgentList() {
        try {
            const response = await axios.get('/api/v1/call-center/agents')
            agentList.value = response.data
        } catch (error) {
            console.error('Failed to fetch agent list:', error)
        }
    }

    // Simulate incoming call (for demo purposes)
    function simulateIncomingCall() {
        const simulatedCall = {
            id: Date.now(),
            phoneNumber: '+1234567890',
            callerName: 'John Doe',
            direction: 'inbound',
            startTime: new Date()
        }
        // Delegate to WebRTC store
        webrtcStore.setIncomingCall(simulatedCall)
    }

    return {
        // State
        makingCall,
        lastCallNumber,
        callError,
        activeCall,
        callHistory,
        isCallActive,
        callStartTime,
        callDuration,
        isOnHold,
        isMuted,
        volume,
        isCalling,
        selectedAgent,
        phone_number,
        dialogType,
        callData,
        isSmsDialog,
        contactForm,
        queueStatus,
        agentStatus,
        agentList,
        
        // Computed (including WebRTC store references)
        isDialogOpen,
        formattedCallDuration,
        afClient,
        incomingCall,
        incomingCallDialog,
        connection_active,
        
        // Actions
        initializeAfClient,
        callClient,
        makeCall,
        answerCall,
        endCall,
        rejectCall,
        holdCall,
        muteCall,
        transferCall,
        resetCallState,
        handleCall,
        hangupCall,
        handleMute,
        
        // Dialog actions
        openNewCallDialog,
        openCallAgentDialog,
        openQueueDialog,
        openTransferDialog,
        openContactDialog,
        openSmsDialog,
        closeDialog,
        
        // Agent actions
        updateAgentStatus,
        fetchQueueStatus,
        fetchAgentList,
        simulateIncomingCall,
        
        // Utility
        logEvent
    }
})