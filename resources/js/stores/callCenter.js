import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const useCallCenterStore = defineStore('callCenter', () => {
    // State
    const makingCall = ref(false)
    const lastCallNumber = ref(null)
    const callError = ref(null)
    // If you need to access agentStore, import and initialize it here:
    // import { useAgentStore } from './agentStore'
    // const agentStore = useAgentStore()

    // Actions
    async function makeCall(phoneNumber) {
        makingCall.value = true
        callError.value = null
        lastCallNumber.value = phoneNumber
        try {
            // Replace with your backend API endpoint for making a call
            await axios.post('/api/call-center/make-call', { phoneNumber })
            // Optionally handle response
        } catch (error) {
            callError.value = error?.response?.data?.message || 'Failed to initiate call'
        } finally {
            makingCall.value = false
        }
    }

    return {
        makingCall,
        lastCallNumber,
        callError,
        makeCall,
    }
})