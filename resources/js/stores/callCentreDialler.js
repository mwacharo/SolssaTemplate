// stores/callCenter.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const usecallCentreDiallerStore = defineStore('callCentreDialler', () => {
  // State
  const activeOrder = ref(null)
  const dialogOpen = ref(false)
  const activeTab = ref('call')
  
  // Call State
  const callState = ref({
    active: false,
    calling: false,
    muted: false,
    onHold: false,
    duration: 0,
    startTime: null,
    recordCall: true,
    autoLog: true
  })
  
  const callHistory = ref([])
  const callTimer = ref(null)
  
  // Message State
  const messageState = ref({
    type: 'whatsapp', // whatsapp, sms, email
    selectedTemplate: null,
    sending: false,
    content: '',
    subject: '',
    attachments: []
  })
  
  const messageTemplates = ref([
    {
      id: 1,
      name: 'Order Confirmation',
      description: 'Confirm order details',
      content: 'Dear {customer_name}, your order #{order_no} has been confirmed and will be delivered on {delivery_date}.',
      type: 'all'
    },
    {
      id: 2,
      name: 'Delivery Update',
      description: 'Update on delivery status',
      content: 'Hi {customer_name}, your order #{order_no} is out for delivery and will arrive on {delivery_date}.',
      type: 'all'
    },
    {
      id: 3,
      name: 'Payment Reminder',
      description: 'Remind about pending payment',
      content: 'Hello {customer_name}, please complete payment of KSH {total_price} for order #{order_no}. Thank you!',
      type: 'all'
    },
    {
      id: 4,
      name: 'Order Cancellation',
      description: 'Notify order cancellation',
      content: 'Dear {customer_name}, we regret to inform you that order #{order_no} has been cancelled. Please contact us for more details.',
      type: 'all'
    },
    {
      id: 5,
      name: 'Feedback Request',
      description: 'Request customer feedback',
      content: 'Hi {customer_name}, thank you for your order #{order_no}. We would love to hear your feedback!',
      type: 'all'
    }
  ])
  
  // Status State
  const statusState = ref({
    updating: false,
    formData: {
      status_id: null,
      status_category_id: null,
      status_notes: '',
      recall_date: null,
      notifyCustomer: true,
      notificationMethod: 'sms'
    }
  })
  
  const statusOptions = ref([
    { id: 1, name: 'Pending', color: 'orange', description: 'Order awaiting confirmation' },
    { id: 2, name: 'Confirmed', color: 'green', description: 'Order confirmed by customer' },
    { id: 3, name: 'Processing', color: 'blue', description: 'Order being prepared' },
    { id: 4, name: 'Shipped', color: 'purple', description: 'Order dispatched for delivery' },
    { id: 5, name: 'Delivered', color: 'teal', description: 'Order delivered to customer' },
    { id: 6, name: 'Follow Up', color: 'amber', description: 'Requires follow up' },
    { id: 7, name: 'Cancelled', color: 'red', description: 'Order cancelled' },
    { id: 8, name: 'On Hold', color: 'grey', description: 'Order temporarily on hold' }
  ])
  
  const statusCategories = ref([
    { id: 1, name: 'Standard', status_id: 1, description: 'Standard processing' },
    { id: 2, name: 'Expedited', status_id: 2, description: 'Fast-track processing' },
    { id: 3, name: 'Follow Up', status_id: 6, description: 'Requires callback', requiresRecall: true },
    { id: 4, name: 'Customer Request', status_id: 8, description: 'Customer requested hold' },
    { id: 5, name: 'Payment Issue', status_id: 6, description: 'Payment verification needed', requiresRecall: true }
  ])
  
  const statusHistory = ref([])
  
  // Notes State
  const notesState = ref({
    saving: false,
    formData: {
      type: 'General',
      content: '',
      pinned: false
    }
  })
  
  const noteTypes = ref([
    { value: 'General', text: 'General Note', color: 'blue-grey' },
    { value: 'Call Summary', text: 'Call Summary', color: 'blue' },
    { value: 'Customer Request', text: 'Customer Request', color: 'purple' },
    { value: 'Issue', text: 'Issue/Problem', color: 'red' },
    { value: 'Follow Up', text: 'Follow Up Required', color: 'orange' },
    { value: 'Payment', text: 'Payment Related', color: 'green' }
  ])
  
  const notes = ref([])
  
  // Notification State
  const notifications = ref([])
  
  // Loading States
  const loading = ref({
    order: false,
    statuses: false,
    notes: false
  })

  // Computed Properties
  const customer = computed(() => {
    if (!activeOrder.value) return null
    return activeOrder.value.customer || 
           activeOrder.value.customer_address || 
           activeOrder.value.shipping_address || 
           {}
  })
  
  const filteredStatusCategories = computed(() => {
    if (!statusState.value.formData.status_id) return []
    return statusCategories.value.filter(
      cat => cat.status_id === statusState.value.formData.status_id
    )
  })
  
  const shouldShowRecallDate = computed(() => {
    const selectedCategory = statusCategories.value.find(
      cat => cat.id === statusState.value.formData.status_category_id
    )
    return selectedCategory?.requiresRecall || false
  })
  
  const formattedCallDuration = computed(() => {
    const duration = callState.value.duration
    const mins = Math.floor(duration / 60)
    const secs = duration % 60
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
  })
  
  const pinnedNotes = computed(() => {
    return notes.value.filter(note => note.pinned)
  })
  
  const unpinnedNotes = computed(() => {
    return notes.value.filter(note => !note.pinned)
  })

  // Actions - Dialog Management
  // const openDialog = (phoneNumber) => {

  //   console.log("Opening call dialog for phone number:", phoneNumber);
  
  //     dialogOpen.value = true

  // }


  // In callCentreDialler.js store
const openDialog = (phoneNumber, order = null) => {
    console.log("Opening call dialog for phone number:", phoneNumber);
    
    // Set the active order if provided
    if (order) {
        activeOrder.value = order;
    }
    
    dialogOpen.value = true;
};
  
  function closeDialog() {
    dialogOpen.value = false
    activeTab.value = 'call'
    resetAllForms()
    
    // Stop call if active
    if (callState.value.active) {
      endCall()
    }
  }
  
  function setActiveTab(tab) {
    activeTab.value = tab
  }

  // Actions - Call Operations
  function initiateCall() {
    if (!customer.value?.phone) {
      addNotification('No phone number available', 'error')
      return
    }
    
    callState.value.calling = true
    
    // Simulate call connection
    setTimeout(() => {
      callState.value.calling = false
      callState.value.active = true
      callState.value.startTime = Date.now()
      startCallTimer()
      
      addNotification('Call connected successfully', 'success')
    }, 2000)
  }
  
  function startCallTimer() {
    callTimer.value = setInterval(() => {
      if (callState.value.active) {
        callState.value.duration++
      }
    }, 1000)
  }
  
  function stopCallTimer() {
    if (callTimer.value) {
      clearInterval(callTimer.value)
      callTimer.value = null
    }
  }
  
  function toggleMute() {
    callState.value.muted = !callState.value.muted
    addNotification(
      callState.value.muted ? 'Microphone muted' : 'Microphone unmuted',
      'info'
    )
  }
  
  function holdCall() {
    callState.value.onHold = !callState.value.onHold
    addNotification(
      callState.value.onHold ? 'Call on hold' : 'Call resumed',
      'info'
    )
  }
  
  function endCall() {
    const callDuration = callState.value.duration
    
    // Add to call history
    callHistory.value.unshift({
      id: Date.now(),
      order_id: activeOrder.value?.id,
      customer_name: customer.value?.full_name,
      phone: customer.value?.phone,
      type: 'outgoing',
      duration: formattedCallDuration.value,
      timestamp: new Date().toISOString(),
      recorded: callState.value.recordCall,
      notes: callState.value.autoLog ? 'Auto-logged call' : null
    })
    
    // Reset call state
    callState.value.active = false
    callState.value.calling = false
    callState.value.muted = false
    callState.value.onHold = false
    callState.value.duration = 0
    callState.value.startTime = null
    
    stopCallTimer()
    addNotification('Call ended', 'info')
    
    // Auto-create note if enabled
    if (callState.value.autoLog) {
      createAutoCallNote(callDuration)
    }
  }
  
  async function createAutoCallNote(duration) {
    const note = {
      id: Date.now(),
      type: 'Call Summary',
      content: `Called customer. Duration: ${formatDuration(duration)}. ${callState.value.recordCall ? 'Call was recorded.' : ''}`,
      pinned: false,
      created_at: new Date().toISOString(),
      user_name: 'Current User',
      auto_generated: true
    }
    
    notes.value.unshift(note)
  }

  // Actions - Message Operations
  function setMessageType(type) {
    messageState.value.type = type
    messageState.value.attachments = [] // Clear attachments when switching types
  }
  
  function loadTemplate(templateId) {
    const template = messageTemplates.value.find(t => t.id === templateId)
    if (template) {
      messageState.value.content = replaceVariables(template.content)
      if (messageState.value.type === 'email') {
        messageState.value.subject = template.name
      }
      messageState.value.selectedTemplate = templateId
    }
  }
  
  function replaceVariables(content) {
    if (!activeOrder.value || !customer.value) return content
    
    return content
      .replace(/{customer_name}/g, customer.value.full_name || 'Customer')
      .replace(/{order_no}/g, activeOrder.value.order_no || 'N/A')
      .replace(/{delivery_date}/g, formatDate(activeOrder.value.delivery_date) || 'N/A')
      .replace(/{total_price}/g, activeOrder.value.total_price || '0.00')
  }
  
  function insertVariable(variable) {
    messageState.value.content += variable
  }
  
  function addAttachment(file) {
    messageState.value.attachments.push(file)
  }
  
  function removeAttachment(index) {
    messageState.value.attachments.splice(index, 1)
  }
  
  async function sendMessage() {
    if (!messageState.value.content.trim()) {
      addNotification('Please enter a message', 'error')
      return false
    }
    
    messageState.value.sending = true
    
    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1500))
      
      // Log the message
      const messageLog = {
        id: Date.now(),
        order_id: activeOrder.value?.id,
        type: messageState.value.type,
        content: messageState.value.content,
        subject: messageState.value.subject,
        sent_at: new Date().toISOString(),
        status: 'sent'
      }
      
      addNotification(
        `${messageState.value.type.toUpperCase()} sent successfully`,
        'success'
      )
      
      // Reset form
      resetMessageForm()
      
      return true
    } catch (error) {
      addNotification('Failed to send message', 'error')
      return false
    } finally {
      messageState.value.sending = false
    }
  }

  // Actions - Status Operations
  async function updateOrderStatus() {
    const form = statusState.value.formData
    
    if (!form.status_id || !form.status_notes) {
      addNotification('Please fill in all required fields', 'error')
      return false
    }
    
    if (shouldShowRecallDate.value && !form.recall_date) {
      addNotification('Recall date is required for this status', 'error')
      return false
    }
    
    statusState.value.updating = true
    
    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1500))
      
      const selectedStatus = statusOptions.value.find(s => s.id === form.status_id)
      
      // Add to status history
      const newStatus = {
        id: Date.now(),
        order_id: activeOrder.value?.id,
        status_id: form.status_id,
        status_name: selectedStatus.name,
        status_category_id: form.status_category_id,
        notes: form.status_notes,
        recall_date: form.recall_date,
        created_at: new Date().toISOString(),
        user: 'Current User',
        color: selectedStatus.color
      }
      
      statusHistory.value.unshift(newStatus)
      
      // Update active order
      if (activeOrder.value) {
        activeOrder.value.status = selectedStatus.name
        activeOrder.value.latest_status = newStatus
      }
      
      addNotification('Status updated successfully', 'success')
      
      // Send notification if requested
      if (form.notifyCustomer) {
        await sendStatusNotification(form.notificationMethod, newStatus)
      }
      
      // Reset form
      resetStatusForm()
      
      return true
    } catch (error) {
      addNotification('Failed to update status', 'error')
      return false
    } finally {
      statusState.value.updating = false
    }
  }
  
  async function sendStatusNotification(method, statusData) {
    // Simulate sending notification
    console.log(`Sending ${method} notification for status update:`, statusData)
  }

  // Actions - Notes Operations
  async function saveNote() {
    const form = notesState.value.formData
    
    if (!form.content.trim()) {
      addNotification('Please enter note content', 'error')
      return false
    }
    
    notesState.value.saving = true
    
    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1000))
      
      const newNote = {
        id: Date.now(),
        order_id: activeOrder.value?.id,
        type: form.type,
        content: form.content,
        pinned: form.pinned,
        created_at: new Date().toISOString(),
        user_name: 'Current User'
      }
      
      notes.value.unshift(newNote)
      
      addNotification('Note saved successfully', 'success')
      
      // Reset form
      resetNoteForm()
      
      return true
    } catch (error) {
      addNotification('Failed to save note', 'error')
      return false
    } finally {
      notesState.value.saving = false
    }
  }
  
  async function deleteNote(noteId) {
    const index = notes.value.findIndex(n => n.id === noteId)
    if (index !== -1) {
      notes.value.splice(index, 1)
      addNotification('Note deleted', 'success')
    }
  }
  
  async function toggleNotePin(noteId) {
    const note = notes.value.find(n => n.id === noteId)
    if (note) {
      note.pinned = !note.pinned
      addNotification(
        note.pinned ? 'Note pinned' : 'Note unpinned',
        'success'
      )
    }
  }

  // Actions - Data Loading
  async function loadOrderData(orderId) {
    loading.value.order = true
    
    try {
      // Load status history
      await loadStatusHistory(orderId)
      
      // Load notes
      await loadNotes(orderId)
      
      // Load call history
      await loadCallHistory(orderId)
      
    } catch (error) {
      console.error('Error loading order data:', error)
      addNotification('Failed to load order data', 'error')
    } finally {
      loading.value.order = false
    }
  }
  
  async function loadStatusHistory(orderId) {
    loading.value.statuses = true
    
    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 500))
      
      // Mock data - replace with actual API call
      statusHistory.value = [
        {
          id: 1,
          status_name: 'Order Placed',
          notes: 'Customer placed order online',
          created_at: new Date(Date.now() - 86400000).toISOString(),
          user: 'System',
          color: 'blue'
        },
        {
          id: 2,
          status_name: 'Payment Confirmed',
          notes: 'Payment received via M-Pesa',
          created_at: new Date(Date.now() - 82800000).toISOString(),
          user: 'John Doe',
          color: 'green'
        }
      ]
    } finally {
      loading.value.statuses = false
    }
  }
  
  async function loadNotes(orderId) {
    loading.value.notes = true
    
    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 500))
      
      // Mock data - replace with actual API call
      notes.value = [
        {
          id: 1,
          type: 'Call Summary',
          content: 'Customer called to confirm delivery address. Updated address in system.',
          pinned: true,
          created_at: new Date(Date.now() - 3600000).toISOString(),
          user_name: 'Jane Smith'
        },
        {
          id: 2,
          type: 'General',
          content: 'Customer prefers morning deliveries between 8-10 AM.',
          pinned: false,
          created_at: new Date(Date.now() - 1800000).toISOString(),
          user_name: 'Jane Smith'
        }
      ]
    } finally {
      loading.value.notes = false
    }
  }
  
  async function loadCallHistory(orderId) {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 500))
    
    // Mock data - replace with actual API call
    callHistory.value = [
      {
        id: 1,
        type: 'outgoing',
        duration: '5:23',
        timestamp: new Date(Date.now() - 7200000).toISOString()
      },
      {
        id: 2,
        type: 'incoming',
        duration: '3:45',
        timestamp: new Date(Date.now() - 86400000).toISOString()
      }
    ]
  }

  // Helper Functions
  function resetAllForms() {
    resetMessageForm()
    resetStatusForm()
    resetNoteForm()
  }
  
  function resetMessageForm() {
    messageState.value.content = ''
    messageState.value.subject = ''
    messageState.value.attachments = []
    messageState.value.selectedTemplate = null
  }
  
  function resetStatusForm() {
    statusState.value.formData = {
      status_id: null,
      status_category_id: null,
      status_notes: '',
      recall_date: null,
      notifyCustomer: true,
      notificationMethod: 'sms'
    }
  }
  
  function resetNoteForm() {
    notesState.value.formData = {
      type: 'General',
      content: '',
      pinned: false
    }
  }
  
  function addNotification(message, type = 'info') {
    const notification = {
      id: Date.now(),
      message,
      type,
      timestamp: new Date().toISOString()
    }
    
    notifications.value.push(notification)
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
      const index = notifications.value.findIndex(n => n.id === notification.id)
      if (index !== -1) {
        notifications.value.splice(index, 1)
      }
    }, 5000)
  }
  
  function formatDate(date) {
    if (!date) return ''
    return new Date(date).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    })
  }
  
  function formatDateTime(datetime) {
    if (!datetime) return ''
    return new Date(datetime).toLocaleString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }
  
  function formatDuration(seconds) {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}m ${secs}s`
  }

  // Export everything
  return {
    // State
    activeOrder,
    dialogOpen,
    activeTab,
    callState,
    callHistory,
    messageState,
    messageTemplates,
    statusState,
    statusOptions,
    statusCategories,
    statusHistory,
    notesState,
    noteTypes,
    notes,
    notifications,
    loading,
    
    // Computed
    customer,
    filteredStatusCategories,
    shouldShowRecallDate,
    formattedCallDuration,
    pinnedNotes,
    unpinnedNotes,
    
    // Actions
    openDialog,
    closeDialog,
    setActiveTab,
    initiateCall,
    toggleMute,
    holdCall,
    endCall,
    setMessageType,
    loadTemplate,
    insertVariable,
    addAttachment,
    removeAttachment,
    sendMessage,
    updateOrderStatus,
    saveNote,
    deleteNote,
    toggleNotePin,
    loadOrderData,
    addNotification,
    formatDate,
    formatDateTime,
    replaceVariables
  }
})