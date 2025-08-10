// (stores/smsStore.js) =====
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useWhatsAppStore } from '@/stores/whatsappStore'
import axios from 'axios'



export const useSmsStore = defineStore('sms', () => {
  // State
  const showNewMessageDialog = ref(false)
  const selectedContacts = ref([])
  const selectedTemplate = ref(null)
  const messageText = ref('')
  const validWhatsappContacts = ref([])


  const selectedContactId = ref(null)

  const errorMessage = ref('')
  
  // Loading states
  const loading = ref({
    contacts: false,
    templates: false,
    sending: false
  })

  // Actions
  const openDialog = (phoneNumber = null) => {
    console.log('openDialog called with phoneNumber:', phoneNumber)
    
    // If a phone number is provided, set selectedContactId to it
    if (phoneNumber) {
      selectedContactId.value = phoneNumber

      // Try to find the contact in validWhatsappContacts
      let contact = validWhatsappContacts.value.find(
        c => c.phone_number === phoneNumber || c.client?.phone_number === phoneNumber
      )

      // If not found, create a minimal contact object
      if (!contact) {
        contact = {
          id: phoneNumber, // or null if you want
          name: phoneNumber,
          phone_number: phoneNumber
        }
      }

      selectedContacts.value = [contact]
    } else {
      selectedContacts.value = []
    }
    
    showNewMessageDialog.value = true
    errorMessage.value = '' // Clear any previous errors
  }

  const closeDialog = () => {
    showNewMessageDialog.value = false
    resetForm()
  }

  const resetForm = () => {
    selectedContacts.value = []
    selectedTemplate.value = null
    messageText.value = ''
    selectedContactId.value = null
    errorMessage.value = ''
  }

  const onTemplateSelect = (template) => {
    if (template) {
      messageText.value = template.content
    }
  }
  const sendSmsMessage = async () => {
    loading.value.sending = true
    try {
      const response = await axios.post('/api/v1/sms/send', {
        contacts: selectedContacts.value,
        message: messageText.value,
        template: selectedTemplate.value
      })
      closeDialog()
      return response.data
    } catch (error) {
      console.error('Send message error:', error)
      errorMessage.value = 'Failed to send message. Please try again.'
      throw error
    } finally {
      loading.value.sending = false
    }
  }

  return {
    // State
    showNewMessageDialog,
    selectedContacts,
    selectedTemplate,
    messageText,
    validWhatsappContacts,
    selectedContactId,
    errorMessage,
    loading,
    
    // Actions
    openDialog,
    closeDialog,
    resetForm,
    onTemplateSelect,
    sendSmsMessage
  }
})