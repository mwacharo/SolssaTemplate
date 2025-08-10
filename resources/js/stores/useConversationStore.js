import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useConversationStore = defineStore('conversation', () => {
    // State
    const dialog = ref(false)
    const conversation = ref([])
    const loading = ref(false)
    const replyMessage = ref('')
    const attachment = ref({
        image: null,
        audio: null
    })
    const showEmojiPicker = ref(false)
    const imagePreviewDialog = ref(false)
    const previewImageUrl = ref('')
    const sending = ref(false)
    const audioStates = ref({})
    const selectedContactId = ref(null)
    const typingIndicator = ref(false)
    const connectionStatus = ref('online') // online, offline, connecting

    const commonEmojis = [
        '😊', '😂', '❤️', '👍', '👏', '🎉', '🔥', '💯',
        '😍', '😘', '🤔', '😎', '👌', '🙏', '💪', '✨',
        '🎈', '🌟', '⭐', '💫', '🌸', '🌺', '🎊', '🎁'
    ]

    // Computed
    const hasAttachment = computed(() => {
        return (attachment.value.image && attachment.value.image.length) ||
            (attachment.value.audio && attachment.value.audio.length)
    })

    const canSendMessage = computed(() => {
        return (replyMessage.value.trim() || hasAttachment.value) &&
            !sending.value &&
            connectionStatus.value === 'online'
    })

    const contactName = computed(() => {
        if (conversation.value && conversation.value.length > 0) {
            const firstMessage = conversation.value.find(msg => msg.sender_name)
            return firstMessage?.sender_name || 'Contact'
        }
        return 'Contact'
    })

    const contactPhone = computed(() => {
        if (conversation.value && conversation.value.length > 0) {
            const phoneMatch = conversation.value[0].from?.match(/(\d+)/)
            return phoneMatch ? `+${phoneMatch[1]}` : ''
        }
        return ''
    })

    const unreadCount = computed(() => {
        return conversation.value.filter(msg =>
            msg.direction === 'incoming' && msg.message_status !== 'read'
        ).length
    })

    const lastMessage = computed(() => {
        return conversation.value[conversation.value.length - 1] || null
    })




    // Alternative version that ensures dialog stays open:

const openDialog = async (contactId) => {
    console.log('=== openDialog called ===')
    console.log('contactId:', contactId)
    
    selectedContactId.value = contactId
    dialog.value = true
    
    console.log('Dialog set to true:', dialog.value)
    
    // Load conversation but don't let it affect dialog state
    try {
        await loadConversation(contactId)
    } catch (error) {
        console.error('Error loading conversation:', error)
    }
    
    // Ensure dialog is still true after loading
    if (!dialog.value) {
        console.log('Dialog was somehow set to false, correcting...')
        dialog.value = true
    }
    
    console.log('openDialog completed, final dialog state:', dialog.value)
}

    const closeDialog = () => {
        dialog.value = false
        clearForm()
        selectedContactId.value = null
    }

    const loadConversation = async (contactId) => {
        if (!contactId) return

        loading.value = true
        try {
            // Replace with your actual API endpoint
            const response = await fetch(`/api/v1/conversation/${contactId}`)

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`)
            }

            const data = await response.json()
            conversation.value = data.data || data.messages || []

            // Sort messages by timestamp to ensure proper order
            conversation.value.sort((a, b) =>
                new Date(a.created_at || a.timestamp) - new Date(b.created_at || b.timestamp)
            )

        } catch (error) {
            console.error('Failed to load conversation:', error)
            conversation.value = []
            showError('Failed to load conversation')
        } finally {
            loading.value = false
        }
    }


    const sendMessage = async () => {
        if (!canSendMessage.value) return

        const messageContent = replyMessage.value.trim()
        const tempId = `temp_${Date.now()}_${Math.random()}`

        // Create optimistic message for immediate UI update
        const optimisticMessage = {
            id: tempId,
            content: messageContent,
            direction: 'outgoing',
            from: 'system',
            message_status: 'sending',
            created_at: new Date().toISOString(),
            image_url: attachment.value.image ? URL.createObjectURL(attachment.value.image[0]) : null,
            audio_url: attachment.value.audio ? URL.createObjectURL(attachment.value.audio[0]) : null
        }

        // Add message to conversation immediately
        conversation.value.push(optimisticMessage)

        // Clear form
        const messageCopy = messageContent
        const attachmentCopy = { ...attachment.value }
        clearForm()

        sending.value = true

        try {
            const messageData = {
                content: messageCopy,
                contact_id: selectedContactId.value,
                temp_id: tempId
            }

            const response = await performSendMessage(messageData, attachmentCopy)

            // Update the optimistic message with the real message data
            const messageIndex = conversation.value.findIndex(msg => msg.id === tempId)
            if (messageIndex !== -1) {
                conversation.value[messageIndex] = {
                    ...conversation.value[messageIndex],
                    id: response.id,
                    message_status: 'sent',
                    created_at: response.created_at || response.timestamp
                }
            }

        } catch (error) {
            console.error('Failed to send message:', error)

            // Update message status to failed
            const messageIndex = conversation.value.findIndex(msg => msg.id === tempId)
            if (messageIndex !== -1) {
                conversation.value[messageIndex].message_status = 'failed'
            }

            showError('Failed to send message. Please try again.')
        } finally {
            sending.value = false
        }
    }

    const performSendMessage = async (messageData, attachmentData) => {
        const formData = new FormData()
        formData.append('content', messageData.content)
        formData.append('contact_id', messageData.contact_id)
        formData.append('temp_id', messageData.temp_id)

        if (attachmentData.image && attachmentData.image.length) {
            formData.append('image', attachmentData.image[0])
        }
        if (attachmentData.audio && attachmentData.audio.length) {
            formData.append('audio', attachmentData.audio[0])
        }

        const response = await fetch('/api/v1/messages', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`
            },
            body: formData
        })

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        return response.json()
    }

  
const retryFailedMessage = async (messageId) => {
    const message = conversation.value.find(msg => msg.id === messageId)
    if (!message || message.message_status !== 'failed') return

    message.message_status = 'sending'

    try {
        await axios.post(`/api/v1/whatsapp/retry-message/${messageId}`)
        message.message_status = 'sent'
    } catch (error) {
        console.error('Failed to retry message:', error)
        message.message_status = 'failed'
    }
}

    const deleteMessage = async (messageId) => {
        try {
            const response = await fetch(`/api/v1/messages/${messageId}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${getAuthToken()}`
                }
            })

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`)
            }

            // Remove message from local state
            const messageIndex = conversation.value.findIndex(msg => msg.id === messageId)
            if (messageIndex !== -1) {
                conversation.value.splice(messageIndex, 1)
            }
        } catch (error) {
            console.error('Failed to delete message:', error)
            showError('Failed to delete message')
        }
    }

    // const markMessagesAsRead = async (contactId) => {
    //     try {
    //         await fetch(`/api/v1/conversations/${contactId}/mark-read`, {
    //             method: 'POST',
    //             headers: {
    //                 'Content-Type': 'application/json',
    //                 'Authorization': `Bearer ${getAuthToken()}`
    //             }
    //         })

    //         // Update local message statuses
    //         conversation.value.forEach(msg => {
    //             if (msg.direction === 'incoming' && msg.message_status !== 'read') {
    //                 msg.message_status = 'read'
    //             }
    //         })
    //     } catch (error) {
    //         console.error('Failed to mark messages as read:', error)
    //     }
    // }

    const addIncomingMessage = (message) => {
        const existingMessage = conversation.value.find(msg => msg.id === message.id)
        if (!existingMessage) {
            conversation.value.push(message)

            // Sort to maintain chronological order
            conversation.value.sort((a, b) =>
                new Date(a.created_at || a.timestamp) - new Date(b.created_at || b.timestamp)
            )
        }
    }

    const updateMessageStatus = (messageId, status) => {
        const message = conversation.value.find(msg => msg.id === messageId)
        if (message) {
            message.message_status = status
        }
    }

    const setTypingIndicator = (isTyping) => {
        typingIndicator.value = isTyping
    }

    const setConnectionStatus = (status) => {
        connectionStatus.value = status
    }

    const clearForm = () => {
        replyMessage.value = ''
        attachment.value.image = null
        attachment.value.audio = null
        showEmojiPicker.value = false
    }

    const appendEmoji = (emoji) => {
        replyMessage.value += emoji
        showEmojiPicker.value = false
    }

    const handleImageUpload = (file) => {
        if (file && file.type.startsWith('image/')) {
            attachment.value.image = [file]
        }
    }

    const handleAudioUpload = (file) => {
        if (file && file.type.startsWith('audio/')) {
            attachment.value.audio = [file]
        }
    }

    const openImagePreview = (imageUrl) => {
        previewImageUrl.value = imageUrl
        imagePreviewDialog.value = true
    }

    const closeImagePreview = () => {
        imagePreviewDialog.value = false
        previewImageUrl.value = ''
    }

    const toggleAudio = (messageId, audioElement) => {
        if (!audioElement) return

        if (!audioStates.value[messageId]) {
            audioStates.value[messageId] = { playing: false }
        }

        // Pause all other audio first
        Object.keys(audioStates.value).forEach(id => {
            if (id !== messageId && audioStates.value[id].playing) {
                audioStates.value[id].playing = false
            }
        })

        if (audioStates.value[messageId].playing) {
            audioElement.pause()
            audioStates.value[messageId].playing = false
        } else {
            audioElement.play()
            audioStates.value[messageId].playing = true
        }
    }

    const onAudioEnded = (messageId) => {
        if (audioStates.value[messageId]) {
            audioStates.value[messageId].playing = false
        }
    }

    const searchMessages = (query) => {
        if (!query.trim()) return conversation.value

        return conversation.value.filter(msg =>
            msg.content.toLowerCase().includes(query.toLowerCase())
        )
    }

    const exportConversation = () => {
        const exportData = {
            contact: {
                name: contactName.value,
                phone: contactPhone.value
            },
            messages: conversation.value.map(msg => ({
                id: msg.id,
                content: msg.content,
                direction: msg.direction,
                timestamp: msg.created_at || msg.timestamp,
                status: msg.message_status
            })),
            exportedAt: new Date().toISOString()
        }

        const blob = new Blob([JSON.stringify(exportData, null, 2)], {
            type: 'application/json'
        })
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `conversation_${contactName.value}_${new Date().toISOString().split('T')[0]}.json`
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        URL.revokeObjectURL(url)
    }

    // Utility functions
    const getAuthToken = () => {
        // Implement your authentication token retrieval logic
        return localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token')
    }

    const showError = (message) => {
        // Implement your error notification system
        console.error(message)
        // You could integrate with a toast notification library here
    }

    const resetStore = () => {
        dialog.value = false
        conversation.value = []
        loading.value = false
        replyMessage.value = ''
        attachment.value = { image: null, audio: null }
        showEmojiPicker.value = false
        imagePreviewDialog.value = false
        previewImageUrl.value = ''
        sending.value = false
        audioStates.value = {}
        selectedContactId.value = null
        typingIndicator.value = false
        connectionStatus.value = 'online'
    }

    return {
        // State
        dialog,
        conversation,
        loading,
        replyMessage,
        attachment,
        showEmojiPicker,
        imagePreviewDialog,
        previewImageUrl,
        sending,
        audioStates,
        selectedContactId,
        typingIndicator,
        connectionStatus,
        commonEmojis,

        // Computed
        hasAttachment,
        canSendMessage,
        contactName,
        contactPhone,
        unreadCount,
        lastMessage,

        // Actions
        openDialog,
        closeDialog,
        loadConversation,
        sendMessage,
        retryFailedMessage,
        deleteMessage,
        // markMessagesAsRead,
        addIncomingMessage,
        updateMessageStatus,
        setTypingIndicator,
        setConnectionStatus,
        clearForm,
        appendEmoji,
        handleImageUpload,
        handleAudioUpload,
        openImagePreview,
        closeImagePreview,
        toggleAudio,
        onAudioEnded,
        searchMessages,
        exportConversation,
        resetStore
    }
})